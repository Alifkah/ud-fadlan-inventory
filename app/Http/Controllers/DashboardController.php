<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\StockTransaction;
use App\Models\Category;
use App\Models\SaleItem;
use App\Exports\DashboardTransactionsExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    /**
     * Display dashboard
     */
    public function index()
    {
        try {
            // Statistik utama
            $totalProducts = Product::where('is_active', true)->count();
            
            $lowStockProducts = Product::whereColumn('current_stock', '<=', 'minimum_stock')
                ->where('is_active', true)
                ->count();

            $activeCategories = Category::where('is_active', true)->count();

            // Transaksi hari ini dan total
            $todayTransactions = Sale::whereDate('created_at', Carbon::today())->count();
            $totalTransactions = Sale::count();

            // Total penjualan bulan ini
            $currentMonthSales = Sale::whereMonth('sale_date', Carbon::now()->month)
                ->whereYear('sale_date', Carbon::now()->year)
                ->where('status', 'completed')
                ->sum('total');

            // Rata-rata penjualan harian bulan ini
            $daysInMonth = Carbon::now()->daysInMonth;
            $averageDailySales = $currentMonthSales / $daysInMonth;

            // Data untuk trend penjualan (6 bulan terakhir)
            $salesTrend = $this->getMonthlySalesTrend();

            // Data kategori penjualan dengan persentase
            $categoryData = $this->getCategoryData();

            // Transaksi terbaru
            $recentTransactions = $this->getRecentTransactions();

            return view('dashboard.index', compact(
                'totalProducts',
                'lowStockProducts',
                'activeCategories',
                'todayTransactions',
                'totalTransactions',
                'currentMonthSales',
                'averageDailySales',
                'salesTrend',
                'categoryData',
                'recentTransactions'
            ));

        } catch (\Exception $e) {
            \Log::error('Dashboard index error: ' . $e->getMessage());
            
            return back()->with('error', 'Gagal memuat dashboard: ' . $e->getMessage());
        }
    }
    
    /**
     * Get sales chart data based on period
     */
    public function salesChart(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            
            switch ($period) {
                case 'week':
                    $salesData = $this->getWeeklySales();
                    break;
                case 'day':
                    $salesData = $this->getDailySales();
                    break;
                default:
                    $salesData = $this->getMonthlySales();
                    break;
            }
            
            return response()->json($salesData);

        } catch (\Exception $e) {
            \Log::error('Sales chart error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Gagal memuat data chart'
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        try {
            $stats = [
                'today_sales' => Sale::whereDate('sale_date', Carbon::today())
                    ->where('status', 'completed')
                    ->sum('total'),
                'today_transactions' => Sale::whereDate('created_at', Carbon::today())
                    ->count(),
                'low_stock_items' => Product::whereColumn('current_stock', '<=', 'minimum_stock')
                    ->where('is_active', true)
                    ->count(),
                'total_products' => Product::where('is_active', true)->count(),
            ];
            
            return response()->json($stats);

        } catch (\Exception $e) {
            \Log::error('Stats error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Gagal memuat statistik'
            ], 500);
        }
    }

    /**
     * Export transactions to Excel
     */
    public function exportTransactions(Request $request)
    {
        try {
            $query = Sale::with(['customer', 'user', 'saleItems.product']);

            // Apply filters
            if ($request->filled('start_date')) {
                $query->whereDate('sale_date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('sale_date', '<=', $request->end_date);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $transactions = $query->orderBy('created_at', 'desc')->get();

            // Export using Excel
            return Excel::download(
                new DashboardTransactionsExport($transactions),
                'transaksi_' . date('Y-m-d_His') . '.xlsx'
            );

        } catch (\Exception $e) {
            \Log::error('Export error: ' . $e->getMessage());
            
            return back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }

    /**
     * Get transaction details for modal
     */
    public function getTransactionDetails($id)
    {
        try {
            $sale = Sale::with(['customer', 'saleItems.product'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice_number' => $sale->invoice_number,
                    'customer_name' => $sale->customer->name ?? 'Guest',
                    'sale_date' => $sale->sale_date,
                    'status' => $sale->status,
                    'payment_method' => $sale->payment_method,
                    'subtotal' => $sale->subtotal,
                    'discount' => $sale->discount ?? 0,
                    'tax' => $sale->tax ?? 0,
                    'total' => $sale->total,
                    'notes' => $sale->notes,
                    'items' => $sale->saleItems->map(function($item) {
                        return [
                            'product_name' => $item->product->name,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching transaction details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail transaksi'
            ], 500);
        }
    }

    /**
     * Get edit data for quick update
     */
    public function getEditData($id)
    {
        try {
            $sale = Sale::with('customer')->findOrFail($id);

            if ($sale->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya transaksi dengan status pending yang dapat diubah'
                ], 422);
            }

            $customers = Customer::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice_number' => $sale->invoice_number,
                    'customer_id' => $sale->customer_id,
                    'sale_date' => $sale->sale_date->format('Y-m-d'),
                    'payment_method' => $sale->payment_method,
                    'notes' => $sale->notes,
                    'total' => $sale->total
                ],
                'customers' => $customers
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching edit data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data transaksi'
            ], 500);
        }
    }

    /**
     * Quick update transaction
     */
    public function quickUpdate(Request $request, $id)
    {
        try {
            $sale = Sale::findOrFail($id);

            if ($sale->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya transaksi dengan status pending yang dapat diubah'
                ], 422);
            }

            $validated = $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'sale_date' => 'required|date',
                'payment_method' => 'required|in:cash,credit,transfer',
                'notes' => 'nullable|string|max:1000'
            ]);

            $sale->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diperbarui',
                'data' => [
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error updating sale: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui transaksi'
            ], 500);
        }
    }

    // ============================================
    // PRIVATE HELPER METHODS
    // ============================================

    /**
     * Get monthly sales trend (6 months)
     */
    private function getMonthlySalesTrend()
    {
        $salesTrend = collect();
        
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->translatedFormat('M');
            
            $totalSales = Sale::whereMonth('sale_date', $month->month)
                ->whereYear('sale_date', $month->year)
                ->where('status', 'completed')
                ->sum('total');
            
            $salesTrend->push([
                'period' => $monthName,
                'total' => $totalSales / 1000000 // Convert to millions
            ]);
        }

        return $salesTrend;
    }

    /**
     * Get category data with percentages
     */
    private function getCategoryData()
    {
        $categoryData = Category::with(['products.saleItems' => function($query) {
            $query->whereHas('sale', function($q) {
                $q->whereMonth('sale_date', Carbon::now()->month)
                  ->whereYear('sale_date', Carbon::now()->year)
                  ->where('status', 'completed');
            });
        }])
        ->where('is_active', true)
        ->get()
        ->map(function($category) {
            $totalSold = $category->products->sum(function($product) {
                return $product->saleItems->sum('quantity');
            });
            
            return [
                'name' => $category->name,
                'total' => $totalSold
            ];
        })
        ->filter(function($category) {
            return $category['total'] > 0;
        })
        ->sortByDesc('total')
        ->take(6);

        // Calculate percentages
        $totalCategorySold = $categoryData->sum('total');
        
        $categoryData = $categoryData->map(function($item) use ($totalCategorySold) {
            $item['percentage'] = $totalCategorySold > 0 
                ? round(($item['total'] / $totalCategorySold) * 100) 
                : 0;
            return $item;
        });

        // If no data, return empty collection
        if ($categoryData->isEmpty()) {
            $categoryData = collect([]);
        }

        return $categoryData;
    }

    /**
     * Get recent transactions
     */
    private function getRecentTransactions()
    {
        return Sale::with(['customer', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'invoice' => $sale->invoice_number,
                    'customer' => $sale->customer->name ?? 'Guest',
                    'total' => $sale->total,
                    'date' => $sale->sale_date,
                    'status' => $sale->status
                ];
            });
    }

    /**
     * Get monthly sales data
     */
    private function getMonthlySales()
    {
        $data = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            
            $totalSales = Sale::whereMonth('sale_date', $month->month)
                ->whereYear('sale_date', $month->year)
                ->where('status', 'completed')
                ->sum('total');
                
            $data[] = [
                'period' => $month->translatedFormat('M Y'),
                'total' => $totalSales / 1000000
            ];
        }
        
        return $data;
    }

    /**
     * Get weekly sales data
     */
    private function getWeeklySales()
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subWeeks($i);
            $startOfWeek = $date->copy()->startOfWeek();
            $endOfWeek = $date->copy()->endOfWeek();
            
            $totalSales = Sale::whereBetween('sale_date', [$startOfWeek, $endOfWeek])
                ->where('status', 'completed')
                ->sum('total');
                
            $data[] = [
                'period' => 'W' . $date->week,
                'total' => $totalSales / 1000000
            ];
        }
        
        return $data;
    }

    /**
     * Get daily sales data
     */
    private function getDailySales()
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            $totalSales = Sale::whereDate('sale_date', $date)
                ->where('status', 'completed')
                ->sum('total');
                
            $data[] = [
                'period' => $date->translatedFormat('d M'),
                'total' => $totalSales / 1000000
            ];
        }
        
        return $data;
    }

    /**
     * Get dashboard summary (for API)
     */
    public function summary()
    {
        try {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth();
            
            // Today's stats
            $todayStats = [
                'sales' => Sale::whereDate('sale_date', $today)
                    ->where('status', 'completed')
                    ->sum('total'),
                'transactions' => Sale::whereDate('sale_date', $today)->count(),
                'customers' => Sale::whereDate('sale_date', $today)
                    ->distinct('customer_id')
                    ->count('customer_id')
            ];
            
            // This month vs last month
            $thisMonthSales = Sale::whereBetween('sale_date', [$thisMonth, Carbon::now()])
                ->where('status', 'completed')
                ->sum('total');
                
            $lastMonthSales = Sale::whereBetween('sale_date', [
                    $lastMonth->startOfMonth(), 
                    $lastMonth->endOfMonth()
                ])
                ->where('status', 'completed')
                ->sum('total');
            
            // Growth calculation
            $salesGrowth = $lastMonthSales > 0 
                ? round((($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100, 1) 
                : 0;
            
            // Stock alerts
            $stockAlerts = [
                'low_stock' => Product::whereColumn('current_stock', '<=', 'minimum_stock')
                    ->where('current_stock', '>', 0)
                    ->where('is_active', true)
                    ->count(),
                'out_of_stock' => Product::where('current_stock', 0)
                    ->where('is_active', true)
                    ->count(),
                'total_products' => Product::where('is_active', true)->count()
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'today' => $todayStats,
                    'monthly' => [
                        'current' => $thisMonthSales,
                        'previous' => $lastMonthSales,
                        'growth' => $salesGrowth
                    ],
                    'stock' => $stockAlerts
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Summary error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat ringkasan'
            ], 500);
        }
    }

    /**
     * Get top performing products
     */
    public function topProducts(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            $limit = $request->get('limit', 10);
            
            switch ($period) {
                case 'week':
                    $startDate = Carbon::now()->startOfWeek();
                    break;
                case 'day':
                    $startDate = Carbon::today();
                    break;
                default:
                    $startDate = Carbon::now()->startOfMonth();
            }
            
            $topProducts = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->where('sales.sale_date', '>=', $startDate)
                ->where('sales.status', 'completed')
                ->select(
                    'products.name',
                    'products.code',
                    DB::raw('SUM(sale_items.quantity) as total_quantity'),
                    DB::raw('SUM(sale_items.total_price) as total_sales'),
                    DB::raw('COUNT(DISTINCT sales.id) as transaction_count')
                )
                ->groupBy('products.id', 'products.name', 'products.code')
                ->orderByDesc('total_quantity')
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $topProducts
            ]);

        } catch (\Exception $e) {
            \Log::error('Top products error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat produk terlaris'
            ], 500);
        }
    }
}