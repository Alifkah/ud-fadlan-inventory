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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistik utama
        $totalProducts = Product::where('is_active', true)->count();
        $totalCustomers = Customer::where('is_active', true)->count();
        $lowStockProducts = Product::whereColumn('current_stock', '<=', 'minimum_stock')
            ->where('is_active', true)
            ->count();

        // Total penjualan bulan ini
        $currentMonthSales = Sale::whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->where('status', 'completed')
            ->sum('total');

        // Data untuk trend penjualan (6 bulan terakhir)
        $salesTrend = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->format('M');
            $totalSales = Sale::whereMonth('sale_date', $month->month)
                ->whereYear('sale_date', $month->year)
                ->where('status', 'completed')
                ->sum('total');
            
            $salesTrend->push([
                'period' => $monthName,
                'total' => $totalSales / 1000000 // Convert to millions
            ]);
        }

        // Data kategori penjualan dengan persentase
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
            return $category['total'] > 0; // Only show categories with sales
        })
        ->sortByDesc('total')
        ->take(4);

        // Hitung persentase untuk kategori
        $totalCategorySold = $categoryData->sum('total');
        $categoryData = $categoryData->map(function($item) use ($totalCategorySold) {
            $item['percentage'] = $totalCategorySold > 0 ? round(($item['total'] / $totalCategorySold) * 100) : 0;
            return $item;
        });

        // Jika tidak ada data kategori, buat data dummy
        if ($categoryData->isEmpty()) {
            $categoryData = collect([
                ['name' => 'Semen', 'total' => 45, 'percentage' => 45],
                ['name' => 'Paving', 'total' => 30, 'percentage' => 30], 
                ['name' => 'Genteng', 'total' => 25, 'percentage' => 25]
            ]);
        }

        // Transaksi terbaru
        $recentTransactions = Sale::with(['customer', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(6)
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

        return view('dashboard.index', compact(
            'totalProducts',
            'totalCustomers', 
            'lowStockProducts',
            'currentMonthSales',
            'salesTrend',
            'categoryData',
            'recentTransactions'
        ));
    }
    
    public function salesChart(Request $request)
    {
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
    }

    public function stats(Request $request)
    {
        $stats = [
            'today_sales' => Sale::whereDate('sale_date', Carbon::today())
                ->where('status', 'completed')
                ->sum('total'),
            'today_transactions' => Sale::whereDate('created_at', Carbon::today())
                ->count(),
            'low_stock_items' => Product::whereColumn('current_stock', '<=', 'minimum_stock')
                ->where('is_active', true)
                ->count(),
            'total_customers' => Customer::where('is_active', true)->count(),
        ];
        
        return response()->json($stats);
    }

    public function getEditData(Sale $sale)
    {
        try {
            // Only allow editing pending transactions
            if ($sale->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya transaksi dengan status pending yang dapat diubah'
                ], 422);
            }

            $sale->load(['customer', 'saleItems.product.category']);

            $customers = Customer::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'sale' => [
                        'id' => $sale->id,
                        'invoice_number' => $sale->invoice_number,
                        'customer_id' => $sale->customer_id,
                        'sale_date' => $sale->sale_date->format('Y-m-d'),
                        'payment_method' => $sale->payment_method,
                        'notes' => $sale->notes,
                        'status' => $sale->status
                    ],
                    'customers' => $customers
                ]
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
     * Quick update transaction from dashboard
     */
    public function quickUpdate(Request $request, Sale $sale)
    {
        try {
            // Validate that transaction can be edited
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

            // Update sale
            $sale->update([
                'customer_id' => $validated['customer_id'],
                'sale_date' => $validated['sale_date'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes']
            ]);

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
                'message' => 'Gagal memperbarui transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

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
                'period' => $month->format('M Y'),
                'total' => $totalSales
            ];
        }
        return $data;
    }

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
                'period' => 'W' . $date->weekOfYear,
                'total' => $totalSales
            ];
        }
        return $data;
    }

    private function getDailySales()
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $totalSales = Sale::whereDate('sale_date', $date)
                ->where('status', 'completed')
                ->sum('total');
            $data[] = [
                'period' => $date->format('d M'),
                'total' => $totalSales
            ];
        }
        return $data;
    }

    /**
     * Get category colors for charts
     */
    protected function getCategoryColor($index)
    {
        $colors = [
            '#60a5fa', // blue-400
            '#facc15', // yellow-400
            '#4ade80', // green-400
            '#f87171', // red-400
            '#a78bfa', // purple-400
            '#fb7185'  // pink-400
        ];
        
        return $colors[$index % count($colors)];
    }

    /**
     * Get dashboard summary for API calls
     */
    public function summary()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth();
        
        // Today's stats
        $todayStats = [
            'sales' => Sale::whereDate('sale_date', $today)->where('status', 'completed')->sum('total'),
            'transactions' => Sale::whereDate('sale_date', $today)->count(),
            'customers' => Sale::whereDate('sale_date', $today)->distinct('customer_id')->count('customer_id')
        ];
        
        // This month vs last month
        $thisMonthSales = Sale::whereBetween('sale_date', [$thisMonth, Carbon::now()])
            ->where('status', 'completed')->sum('total');
        $lastMonthSales = Sale::whereBetween('sale_date', [$lastMonth->startOfMonth(), $lastMonth->endOfMonth()])
            ->where('status', 'completed')->sum('total');
        
        // Growth calculation
        $salesGrowth = $lastMonthSales > 0 ? 
            round((($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100, 1) : 0;
        
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
    }

    /**
     * Get top performing products
     */
    public function topProducts(Request $request)
    {
        $period = $request->get('period', 'month'); // month, week, day
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
    }

    /**
     * Get recent activities for dashboard
     */
    public function recentActivities()
    {
        $activities = collect();
        
        // Recent sales
        $recentSales = Sale::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($sale) {
                return [
                    'type' => 'sale',
                    'description' => "Penjualan {$sale->invoice_number} - " . ($sale->customer ? $sale->customer->name : 'Guest'),
                    'amount' => $sale->total,
                    'created_at' => $sale->created_at
                ];
            });
        
        // Recent purchases
        $recentPurchases = Purchase::with('supplier')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function($purchase) {
                return [
                    'type' => 'purchase',
                    'description' => "Pembelian {$purchase->invoice_number} - {$purchase->supplier->name}",
                    'amount' => $purchase->total,
                    'created_at' => $purchase->created_at
                ];
            });
        
        // Recent stock transactions
        $recentStock = StockTransaction::with('product')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function($stock) {
                return [
                    'type' => 'stock',
                    'description' => "Stok {$stock->type_text} - {$stock->product->name} ({$stock->quantity} {$stock->product->unit})",
                    'amount' => null,
                    'created_at' => $stock->created_at
                ];
            });
        
        $activities = $activities->merge($recentSales)
                                ->merge($recentPurchases)
                                ->merge($recentStock)
                                ->sortByDesc('created_at')
                                ->take(10);
        
        return response()->json([
            'success' => true,
            'data' => $activities->values()
        ]);
    }
}