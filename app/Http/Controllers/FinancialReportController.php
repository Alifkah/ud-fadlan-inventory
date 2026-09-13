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
use Maatwebsite\Excel\Facades\Excel; 
use Barryvdh\DomPDF\Facade\Pdf; 

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
        $operationalExpenses = 0; // Placeholder, sesuaikan dengan logika beban Anda

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
            'format' => 'required|in:excel,pdf,csv'
        ]);

        // Ambil periode dari request, default bulanan
        $period = $request->get('period', 'bulanan');

        // Bulan ini untuk data utama
        $dateFrom = Carbon::now()->startOfMonth();
        $dateTo = Carbon::now();

        $mainStats = $this->getMainStatistics();
        $salesTrend = $this->getBulananData(); // Gunakan data bulanan default untuk ekspor
        $topProducts = $this->getTopSellingProducts(10); // Ambil 10 produk terlaris untuk ekspor

        // Detail transaksi penjualan dan pembelian
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

        $format = $validated['format'];

        // Gunakan nama file yang dinamis
        $fileName = 'Laporan_Keuangan_' . $dateFrom->format('Y-m-d') . '_to_' . $dateTo->format('Y-m-d') . '_' . strtoupper($format);

        if ($format === 'excel') {
            return $this->exportToExcel($reportData, $fileName);
        } elseif ($format === 'pdf') {
            return $this->exportToPdf($reportData, $fileName);
        } else { // csv
            return $this->exportToCsv($reportData, $fileName);
        }
    }

    /**
     * Export ke Excel menggunakan Laravel Excel
     */
    private function exportToExcel($data, $fileName)
    {
        // Buat instance dari kelas export (Anda perlu membuatnya)
        // Contoh: $export = new FinancialReportExport($data);
        // return Excel::download($export, $fileName . '.xlsx');

        // Karena implementasi Excel membutuhkan kelas export tambahan,
        // kita gunakan library bawaan atau kelas export buatan sendiri.
        // Contoh sederhana dengan array ke Excel (menggunakan Laravel Excel Collection):
        $topProductsExport = collect($data['top_products'])->map(function ($item) {
            return [
                'Rank' => $item['rank'],
                'Nama Produk' => $item['product_name'],
                'Kode Produk' => $item['product_code'],
                'Jumlah Terjual' => $item['total_quantity'] . ' ' . $item['unit'],
                'Harga Rata-rata' => 'Rp ' . number_format($item['avg_price'], 0, ',', '.'),
                'Total Penjualan' => 'Rp ' . number_format($item['total_sales'], 0, ',', '.'),
                'Persentase' => $item['percentage'] . '%'
            ];
        });

        return Excel::download(new class($topProductsExport) implements \Maatwebsite\Excel\Concerns\FromCollection
        {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function collection()
            {
                return $this->data;
            }
        }, $fileName . '.xlsx');
    }

    /**
     * Export ke PDF menggunakan DomPDF
     */
    private function exportToPdf($data, $fileName)
    {
        // Buat view PDF (Anda perlu membuat view PDF, misalnya financial-reports.pdf)
        // Contoh: $pdf = PDF::loadView('financial-reports.pdf', $data);
        // return $pdf->download($fileName . '.pdf');

        // Karena implementasi PDF membutuhkan view tambahan,
        // kita gunakan view sederhana yang menampilkan data utama dan produk terlaris.
        $pdf = PDF::loadView('financial-reports.pdf_export', $data);
        return $pdf->download($fileName . '.pdf');
    }

    /**
     * Export ke CSV
     * Fungsi ini menghasilkan file CSV secara manual.
     */
    private function exportToCsv($data, $fileName)
    {
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            // Header utama
            fputcsv($file, ['LAPORAN KEUANGAN']);
            fputcsv($file, []);
            fputcsv($file, ['Periode:', $data['period']['label']]);
            fputcsv($file, ['Dibuat pada:', $data['generated_at']->format('d/m/Y H:i')]);
            fputcsv($file, ['Dibuat oleh:', $data['generated_by']]);
            fputcsv($file, []);

            // Statistik Utama
            fputcsv($file, ['STATISTIK UTAMA']);
            fputcsv($file, ['Deskripsi', 'Jumlah', 'Perubahan']);
            fputcsv($file, ['Pendapatan Bulan Ini', 'Rp ' . number_format($data['main_stats']['revenue']['amount'], 0, ',', '.'), $data['main_stats']['revenue']['growth_label']]);
            fputcsv($file, ['Pengeluaran Bulan Ini', 'Rp ' . number_format($data['main_stats']['expenditure']['amount'], 0, ',', '.'), $data['main_stats']['expenditure']['growth_label']]);
            fputcsv($file, ['Laba Bersih', 'Rp ' . number_format($data['main_stats']['net_profit']['amount'], 0, ',', '.'), '']);
            fputcsv($file, ['Total Modal', 'Rp ' . number_format($data['main_stats']['total_capital']['amount'], 0, ',', '.'), '']);
            fputcsv($file, []);

            // Produk Terlaris
            fputcsv($file, ['PRODUK TERLARIS']);
            fputcsv($file, ['Rank', 'Nama Produk', 'Kode Produk', 'Jumlah Terjual', 'Harga Rata-rata', 'Total Penjualan', 'Persentase']);
            foreach ($data['top_products'] as $product) {
                fputcsv($file, [
                    $product['rank'],
                    $product['product_name'],
                    $product['product_code'],
                    $product['total_quantity'] . ' ' . $product['unit'],
                    'Rp ' . number_format($product['avg_price'], 0, ',', '.'),
                    'Rp ' . number_format($product['total_sales'], 0, ',', '.'),
                    $product['percentage'] . '%'
                ]);
            }
            fputcsv($file, []);

            // Detail Penjualan (Opsional, bisa ditambahkan)
            // fputcsv($file, ['DETAIL PENJUALAN']);
            // fputcsv($file, ['Tanggal', 'Kode Penjualan', 'Pelanggan', 'Total']);
            // foreach ($data['sales_details'] as $sale) {
            //     fputcsv($file, [$sale->sale_date->format('d/m/Y'), $sale->code, $sale->customer->name ?? 'N/A', 'Rp ' . number_format($sale->total, 0, ',', '.')]);
            // }
            // fputcsv($file, []);

            // Detail Pembelian (Opsional, bisa ditambahkan)
            // fputcsv($file, ['DETAIL PEMBELIAN']);
            // fputcsv($file, ['Tanggal', 'Kode Pembelian', 'Supplier', 'Total']);
            // foreach ($data['purchase_details'] as $purchase) {
            //     fputcsv($file, [$purchase->purchase_date->format('d/m/Y'), $purchase->code, $purchase->supplier->name ?? 'N/A', 'Rp ' . number_format($purchase->total, 0, ',', '.')]);
            // }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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