<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialReportController extends Controller
{
    /**
     * Tampilkan laporan keuangan utama
     */
    public function index(Request $request)
    {
        // Default bulan ini
        $period = $request->get('period', 'bulanan');
        
        // Statistik utama untuk card dashboard
        $mainStats = $this->getMainStatistics();
        
        // Data untuk chart trend penjualan (default bulanan)
        $salesTrend = $this->getSalesTrendDataByPeriod($period);
        
        // Data produk terlaris
        $topProducts = $this->getTopSellingProducts();
        
        // Total item di toko (donut chart)
        $storeItemsData = $this->getStoreItemsData();

        return view('financial-reports.index', compact(
            'mainStats',
            'salesTrend', 
            'topProducts',
            'storeItemsData'
        ));
    }

    /**
     * Tampilkan halaman Profit Loss
     */
    public function profitLoss()
    {
        return view('financial-reports.profit-loss');
    }

    /**
     * Dapatkan statistik utama untuk dashboard cards
     */
    private function getMainStatistics()
    {
        // Bulan ini
        $dateFrom = Carbon::now()->startOfMonth();
        $dateTo = Carbon::now();

        // Pendapatan Bulan Ini
        $currentMonthRevenue = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->sum('total');

        // Pengeluaran Bulan Ini
        $currentMonthExpenditure = Purchase::whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->sum('total');

        // Laba Bersih
        $netProfit = $currentMonthRevenue - $currentMonthExpenditure;

        // Total Modal (berdasarkan nilai stok saat ini)
        $totalCapital = DB::table('products')
            ->where('is_active', true)
            ->selectRaw('SUM(current_stock * purchase_price) as total')
            ->value('total') ?? 0;

        // Perhitungan persentase perubahan dibandingkan bulan sebelumnya
        $previousPeriodStart = Carbon::now()->subMonth()->startOfMonth();
        $previousPeriodEnd = Carbon::now()->subMonth()->endOfMonth();

        $previousRevenue = Sale::whereBetween('sale_date', [$previousPeriodStart, $previousPeriodEnd])
            ->where('status', 'completed')
            ->sum('total');

        $previousExpenditure = Purchase::whereBetween('purchase_date', [$previousPeriodStart, $previousPeriodEnd])
            ->where('status', 'completed')
            ->sum('total');

        $revenueGrowth = $previousRevenue > 0 ? 
            (($currentMonthRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

        $expenditureGrowth = $previousExpenditure > 0 ? 
            (($currentMonthExpenditure - $previousExpenditure) / $previousExpenditure) * 100 : 0;

        return [
            'revenue' => [
                'amount' => $currentMonthRevenue,
                'label' => 'Pendapatan Bulan Ini',
                'growth' => round($revenueGrowth, 1),
                'growth_label' => abs(round($revenueGrowth, 1)) . '% dari bulan lalu'
            ],
            'expenditure' => [
                'amount' => $currentMonthExpenditure,
                'label' => 'Pengeluaran Bulan Ini',
                'growth' => round($expenditureGrowth, 1),
                'growth_label' => abs(round($expenditureGrowth, 1)) . '% dari bulan lalu'
            ],
            'net_profit' => [
                'amount' => $netProfit,
                'label' => 'Laba Bersih',
                'growth' => 0,
                'growth_label' => ''
            ],
            'total_capital' => [
                'amount' => $totalCapital,
                'label' => 'Total Modal',
                'growth' => 0,
                'growth_label' => ''
            ]
        ];
    }

    /**
     * Dapatkan data trend penjualan berdasarkan periode
     */
    private function getSalesTrendDataByPeriod($period)
    {
        switch ($period) {
            case 'harian':
                // 7 hari terakhir
                return $this->getHarianData();
            case 'mingguan':
                // 4 minggu terakhir
                return $this->getMingguanData();
            case 'bulanan':
            default:
                // 6 bulan terakhir
                return $this->getBulananData();
        }
    }

    /**
     * Data harian (7 hari terakhir)
     */
    private function getHarianData()
    {
        $dateFrom = Carbon::now()->subDays(6)->startOfDay();
        $dateTo = Carbon::now()->endOfDay();

        $salesData = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->selectRaw('DATE(sale_date) as date, SUM(total) as total_penjualan')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $purchaseData = Purchase::whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->selectRaw('DATE(purchase_date) as date, SUM(total) as total_pembelian')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $sale = $salesData->get($date);
            $purchase = $purchaseData->get($date);

            $result->push([
                'period' => Carbon::parse($date)->format('d M'),
                'penjualan' => $sale ? round($sale->total_penjualan / 1000000, 1) : 0,
                'pembelian' => $purchase ? round($purchase->total_pembelian / 1000000, 1) : 0,
            ]);
        }

        return $result;
    }

    /**
     * Data mingguan (4 minggu terakhir)
     */
    private function getMingguanData()
    {
        $result = collect();
        
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();

            $sales = Sale::whereBetween('sale_date', [$weekStart, $weekEnd])
                ->where('status', 'completed')
                ->sum('total');

            $purchases = Purchase::whereBetween('purchase_date', [$weekStart, $weekEnd])
                ->where('status', 'completed')
                ->sum('total');

            $result->push([
                'period' => 'Minggu ' . ($i == 0 ? 'Ini' : $i),
                'penjualan' => round($sales / 1000000, 1),
                'pembelian' => round($purchases / 1000000, 1),
            ]);
        }

        return $result;
    }

    /**
     * Data bulanan (6 bulan terakhir)
     */
    private function getBulananData()
    {
        $result = collect();
        
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

            $sales = Sale::whereBetween('sale_date', [$monthStart, $monthEnd])
                ->where('status', 'completed')
                ->sum('total');

            $purchases = Purchase::whereBetween('purchase_date', [$monthStart, $monthEnd])
                ->where('status', 'completed')
                ->sum('total');

            $result->push([
                'period' => $monthStart->format('M Y'),
                'penjualan' => round($sales / 1000000, 1),
                'pembelian' => round($purchases / 1000000, 1),
            ]);
        }

        return $result;
    }

    /**
     * Dapatkan produk terlaris
     */
    private function getTopSellingProducts($limit = 5)
    {
        // Bulan ini
        $dateFrom = Carbon::now()->startOfMonth();
        $dateTo = Carbon::now();

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
            ->where('sales.status', 'completed')
            ->select(
                'products.name as product_name',
                'products.code as product_code',
                'products.unit',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('AVG(sale_items.unit_price) as avg_price'),
                DB::raw('SUM(sale_items.total_price) as total_sales')
            )
            ->groupBy('products.id', 'products.name', 'products.code', 'products.unit')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();

        // Hitung total penjualan untuk persentase
        $totalSales = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
            ->where('sales.status', 'completed')
            ->sum('sale_items.total_price');

        return $topProducts->map(function ($product, $index) use ($totalSales) {
            return [
                'rank' => $index + 1,
                'product_name' => $product->product_name,
                'product_code' => $product->product_code,
                'unit' => $product->unit,
                'total_quantity' => $product->total_quantity,
                'avg_price' => $product->avg_price,
                'total_sales' => $product->total_sales,
                'percentage' => $totalSales > 0 ? round(($product->total_sales / $totalSales) * 100) : 0
            ];
        });
    }

    /**
     * Dapatkan data untuk donut chart Total Item Toko
     */
    private function getStoreItemsData()
    {
        $categories = Category::with(['products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->where('is_active', true)
            ->get();

        $totalProducts = Product::where('is_active', true)->count();
        
        $categoryData = $categories->map(function ($category) use ($totalProducts) {
            $productCount = $category->products->count();
            $percentage = $totalProducts > 0 ? round(($productCount / $totalProducts) * 100) : 0;
            
            return [
                'name' => $category->name,
                'count' => $productCount,
                'percentage' => $percentage
            ];
        })
        ->sortByDesc('count')
        ->take(4)
        ->values();

        $topCategoriesCount = $categoryData->sum('count');
        $otherCount = $totalProducts - $topCategoriesCount;
        
        if ($otherCount > 0) {
            $categoryData->push([
                'name' => 'Lainnya',
                'count' => $otherCount,
                'percentage' => round(($otherCount / $totalProducts) * 100)
            ]);
        }

        return [
            'total_items' => $totalProducts,
            'categories' => $categoryData
        ];
    }

    /**
     * Dapatkan data chart berdasarkan filter
     */
    public function getChartData(Request $request)
    {
        $validated = $request->validate([
            'chart_type' => 'required|in:sales_trend,category_sales,monthly_comparison',
            'period' => 'nullable|in:harian,mingguan,bulanan'
        ]);

        $chartType = $validated['chart_type'];
        $period = $validated['period'] ?? 'bulanan';

        switch ($chartType) {
            case 'sales_trend':
                $data = $this->getSalesTrendDataByPeriod($period);
                break;
            case 'category_sales':
                $data = $this->getCategorySalesData();
                break;
            case 'monthly_comparison':
                $data = $this->getMonthlyComparisonData();
                break;
            default:
                $data = [];
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Dapatkan summary profit loss
     */
    public function getProfitLossData(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from'
        ]);

        $dateFrom = $validated['date_from'];
        $dateTo = $validated['date_to'];

        // Pendapatan
        $totalRevenue = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->sum('total');

        $discountGiven = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->sum('discount');

        $netRevenue = $totalRevenue - $discountGiven;

        // Harga Pokok Penjualan (HPP)
        $cogs = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
            ->where('sales.status', 'completed')
            ->sum(DB::raw('sale_items.quantity * products.purchase_price'));

        // Laba Kotor
        $grossProfit = $netRevenue - $cogs;

        // Beban Operasional
        $operationalExpenses = 0;

        // Laba Bersih
        $netProfit = $grossProfit - $operationalExpenses;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                    'label' => Carbon::parse($dateFrom)->format('d M Y') . ' - ' . Carbon::parse($dateTo)->format('d M Y')
                ],
                'revenue' => [
                    'gross_revenue' => $totalRevenue,
                    'discount' => $discountGiven,
                    'net_revenue' => $netRevenue
                ],
                'costs' => [
                    'cogs' => $cogs,
                    'operational_expenses' => $operationalExpenses,
                    'total_costs' => $cogs + $operationalExpenses
                ],
                'profit' => [
                    'gross_profit' => $grossProfit,
                    'net_profit' => $netProfit,
                    'profit_margin' => $netRevenue > 0 ? round(($netProfit / $netRevenue) * 100, 2) : 0
                ]
            ]
        ]);
    }

    /**
     * Export laporan keuangan
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|in:excel,pdf'
        ]);

        // Bulan ini
        $dateFrom = Carbon::now()->startOfMonth();
        $dateTo = Carbon::now();

        $mainStats = $this->getMainStatistics();
        $salesTrend = $this->getBulananData();
        $topProducts = $this->getTopSellingProducts(10);

        // Detail transaksi
        $salesDetails = Sale::with(['customer', 'saleItems.product'])
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->orderBy('sale_date', 'desc')
            ->get();

        $purchaseDetails = Purchase::with(['supplier', 'purchaseItems.product'])
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->orderBy('purchase_date', 'desc')
            ->get();

        $reportData = [
            'period' => [
                'from' => $dateFrom->format('Y-m-d'),
                'to' => $dateTo->format('Y-m-d'),
                'label' => $dateFrom->format('d M Y') . ' - ' . $dateTo->format('d M Y')
            ],
            'main_stats' => $mainStats,
            'sales_trend' => $salesTrend,
            'top_products' => $topProducts,
            'sales_details' => $salesDetails,
            'purchase_details' => $purchaseDetails,
            'generated_at' => now(),
            'generated_by' => auth()->user()->name
        ];

        if ($validated['format'] === 'excel') {
            return $this->exportToExcel($reportData);
        } else {
            return $this->exportToPdf($reportData);
        }
    }

    /**
     * Export ke Excel (placeholder)
     */
    private function exportToExcel($data)
    {
        // Implementasi export menggunakan Laravel Excel
        return response()->json([
            'success' => true,
            'message' => 'Export Excel akan segera tersedia',
            'data' => $data
        ]);
    }

    /**
     * Export ke PDF (placeholder)
     */
    private function exportToPdf($data)
    {
        // Implementasi export menggunakan DomPDF
        return response()->json([
            'success' => true,
            'message' => 'Export PDF akan segera tersedia',
            'data' => $data
        ]);
    }

    /**
     * Helper methods
     */
    private function getCategorySalesData()
    {
        $dateFrom = Carbon::now()->startOfMonth();
        $dateTo = Carbon::now();
        
        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
            ->where('sales.status', 'completed')
            ->select(
                'categories.name as category_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total_price) as total_sales')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_sales')
            ->get();
    }

    private function getMonthlyComparisonData()
    {
        $currentYear = Carbon::now()->year;
        $previousYear = $currentYear - 1;

        $currentYearData = Sale::whereYear('sale_date', $currentYear)
            ->where('status', 'completed')
            ->selectRaw('MONTH(sale_date) as month, SUM(total) as total_sales')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $previousYearData = Sale::whereYear('sale_date', $previousYear)
            ->where('status', 'completed')
            ->selectRaw('MONTH(sale_date) as month, SUM(total) as total_sales')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $comparison = [];
        for ($month = 1; $month <= 12; $month++) {
            $currentSales = $currentYearData->get($month)?->total_sales ?? 0;
            $previousSales = $previousYearData->get($month)?->total_sales ?? 0;

            $comparison[] = [
                'month' => Carbon::create($currentYear, $month, 1)->format('M'),
                'current_year' => round($currentSales / 1000000, 1),
                'previous_year' => round($previousSales / 1000000, 1),
                'growth' => $previousSales > 0 ? 
                    round((($currentSales - $previousSales) / $previousSales) * 100, 1) : 0
            ];
        }

        return $comparison;
    }
}