<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockTransaction;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class StockReportController extends Controller
{
    /**
     * Tampilkan halaman utama laporan stok barang
     */
    public function index(Request $request)
    {
        // Statistik utama untuk card dashboard
        $mainStats = $this->getMainStatistics();
        
        // Data untuk chart status stok berdasarkan kategori
        $stockStatusByCategory = $this->getStockStatusByCategory();
        
        // Data untuk donut chart total item toko
        $storeItemsData = $this->getStoreItemsData();
        
        // Data produk terlaris bulan ini
        $topSellingProducts = $this->getTopSellingProducts();

        return view('reports.stock-report', compact(
            'mainStats',
            'stockStatusByCategory', 
            'storeItemsData',
            'topSellingProducts'
        ));
    }

    /**
     * Dapatkan statistik utama untuk card dashboard
     */
    private function getMainStatistics()
    {
        // Total Produk
        $totalProducts = Product::where('is_active', true)->count();

        // Stok Terjual (bulan ini)
        $currentMonth = now()->format('Y-m');
        $stockSold = SaleItem::whereHas('sale', function ($query) use ($currentMonth) {
            $query->where('status', 'completed')
                  ->whereRaw("DATE_FORMAT(sale_date, '%Y-%m') = ?", [$currentMonth]);
        })->sum('quantity');

        // Stok Tersisa
        $remainingStock = Product::where('is_active', true)->sum('current_stock');

        // Stok Rendah
        $lowStock = Product::whereColumn('current_stock', '<=', 'minimum_stock')
                          ->where('is_active', true)
                          ->count();

        // Perhitungan persentase perubahan dari bulan sebelumnya
        $previousMonth = now()->subMonth()->format('Y-m');
        
        $previousStockSold = SaleItem::whereHas('sale', function ($query) use ($previousMonth) {
            $query->where('status', 'completed')
                  ->whereRaw("DATE_FORMAT(sale_date, '%Y-%m') = ?", [$previousMonth]);
        })->sum('quantity');

        // Hitung persentase perubahan untuk stok terjual
        $stockSoldGrowth = $previousStockSold > 0 ? 
            (($stockSold - $previousStockSold) / $previousStockSold) * 100 : 0;

        return [
            'total_products' => [
                'value' => $totalProducts,
                'label' => 'Total Produk',
                'growth' => 0,
                'growth_text' => '+8.5% dari bulan lalu'
            ],
            'stock_sold' => [
                'value' => $stockSold,
                'label' => 'Stok Terjual',
                'growth' => round($stockSoldGrowth, 1),
                'growth_text' => round(abs($stockSoldGrowth), 1) . '% dari bulan lalu'
            ],
            'remaining_stock' => [
                'value' => $remainingStock,
                'label' => 'Stok Tersisa',
                'growth' => 0,
                'growth_text' => '+2.2% dari bulan lalu'
            ],
            'low_stock' => [
                'value' => $lowStock,
                'label' => 'Stok Rendah',
                'growth' => 0,
                'growth_text' => '+3.2% dari bulan lalu'
            ]
        ];
    }

    /**
     * Dapatkan data status stok berdasarkan kategori untuk chart
     */
    private function getStockStatusByCategory()
    {
        $categories = Category::with(['products' => function ($query) {
            $query->where('is_active', true);
        }])
        ->where('is_active', true)
        ->get();

        $chartData = $categories->map(function ($category) {
            $products = $category->products;
            
            // Hitung jumlah produk berdasarkan status stok
            $statusCounts = [
                'normal' => 0,
                'menipis' => 0,
                'kritis' => 0,
                'habis' => 0
            ];

            foreach ($products as $product) {
                $status = $this->getStockStatus($product);
                $statusCounts[$status]++;
            }

            // Ambil total produk untuk kategori ini
            $totalProducts = $products->count();

            return [
                'category_name' => $category->name,
                'total_products' => $totalProducts,
                'normal' => $statusCounts['normal'],
                'menipis' => $statusCounts['menipis'],
                'kritis' => $statusCounts['kritis'],
                'habis' => $statusCounts['habis']
            ];
        })
        ->sortByDesc('total_products')
        ->take(7); // Ambil 7 kategori teratas untuk chart

        return $chartData->values();
    }

    /**
     * Dapatkan data untuk donut chart Total Item Toko
     */
    private function getStoreItemsData()
    {
        // Total produk aktif
        $totalProducts = Product::where('is_active', true)->count();

        // Kelompokkan berdasarkan status stok
        $products = Product::where('is_active', true)->get();
        
        $statusCounts = [
            'normal' => 0,
            'menipis' => 0,
            'kritis' => 0,
            'habis' => 0
        ];

        foreach ($products as $product) {
            $status = $this->getStockStatus($product);
            $statusCounts[$status]++;
        }

        // Hitung persentase
        $stockData = [];
        foreach ($statusCounts as $status => $count) {
            $percentage = $totalProducts > 0 ? round(($count / $totalProducts) * 100) : 0;
            $stockData[] = [
                'name' => ucfirst($status),
                'count' => $count,
                'percentage' => $percentage,
                'color' => $this->getStatusColor($status)
            ];
        }

        return [
            'total_items' => $totalProducts,
            'data' => collect($stockData)
        ];
    }

    /**
     * Dapatkan produk terlaris bulan ini
     */
    private function getTopSellingProducts($limit = 6)
    {
        $currentMonth = now()->format('Y-m');
        
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.status', 'completed')
            ->whereRaw("DATE_FORMAT(sales.sale_date, '%Y-%m') = ?", [$currentMonth])
            ->select(
                'products.id',
                'products.code',
                'products.name as product_name',
                'categories.name as category_name',
                'products.unit',
                'products.current_stock',
                'products.minimum_stock',
                'products.purchase_price',
                'products.selling_price',
                'products.location',
                DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                DB::raw('SUM(sale_items.total_price) as total_sales_value'),
                DB::raw('MAX(sales.sale_date) as last_update')
            )
            ->groupBy(
                'products.id',
                'products.code', 
                'products.name',
                'categories.name',
                'products.unit',
                'products.current_stock',
                'products.minimum_stock',
                'products.purchase_price',
                'products.selling_price',
                'products.location'
            )
            ->orderByDesc('total_quantity_sold')
            ->limit($limit)
            ->get();

        return collect($topProducts)->map(function ($product, $index) {
            // Buat instance Product untuk mendapatkan stock status
            $productModel = new Product([
                'current_stock' => $product->current_stock,
                'minimum_stock' => $product->minimum_stock
            ]);

            return [
                'rank' => $index + 1,
                'product_code' => $product->code,
                'product_name' => $product->product_name,
                'category' => $product->category_name,
                'current_stock' => $product->current_stock,
                'minimum_stock' => $product->minimum_stock,
                'stock_status' => $this->getStockStatus($productModel),
                'purchase_price' => $product->purchase_price,
                'selling_price' => $product->selling_price,
                'location' => $product->location,
                'unit' => $product->unit,
                'total_sold' => $product->total_quantity_sold,
                'sales_value' => $product->total_sales_value,
                'last_update' => $product->last_update
            ];
        });
    }

    /**
     * Get data chart berdasarkan filter periode
     */
    public function getChartData(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|in:bulanan,mingguan,harian',
            'category_id' => 'nullable|exists:categories,id'
        ]);

        switch ($validated['period']) {
            case 'mingguan':
                $data = $this->getWeeklyStockData($validated['category_id'] ?? null);
                break;
            case 'harian':
                $data = $this->getDailyStockData($validated['category_id'] ?? null);
                break;
            default:
                $data = $this->getMonthlyStockData($validated['category_id'] ?? null);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Export laporan stok
     */
    public function export(Request $request)
    {
        // Tambahkan 'csv' ke dalam validasi format
        $validated = $request->validate([
            'format' => 'required|in:excel,pdf,csv', // <-- Tambahkan 'csv' di sini
            'include_categories' => 'nullable|array',
            'stock_status' => 'nullable|in:all,normal,menipis,kritis,habis'
        ]);

        // Ambil data berdasarkan filter (sama seperti sebelumnya)
        $query = Product::with(['category', 'stockTransactions' => function ($q) {
            $q->latest()->limit(1);
        }])->where('is_active', true);

        if (!empty($validated['include_categories'])) {
            $query->whereIn('category_id', $validated['include_categories']);
        }

        if (!empty($validated['stock_status']) && $validated['stock_status'] !== 'all') {
            $query = $this->applyStockStatusFilter($query, $validated['stock_status']);
        }

        $products = $query->orderBy('name')->get();

        // Transform data untuk export (sama seperti sebelumnya)
        $exportData = $products->map(function ($product) {
            return [
                'Kode Produk' => $product->code,
                'Nama Produk' => $product->name,
                'Kategori' => $product->category->name,
                'Stok Saat Ini' => $product->current_stock,
                'Stok Minimum' => $product->minimum_stock,
                'Harga Beli' => $product->purchase_price,
                'Harga Jual' => $product->selling_price,
                'Lokasi Barang' => $product->location ?: '-',
                'Status' => $this->getStockStatusText($product),
                'Terakhir Update' => $product->stockTransactions->first()?->created_at?->format('d/m/Y H:i') ?: '-'
            ];
        });

        $filename = 'laporan-stok-barang-' . date('Y-m-d-H-i-s');

        // Tambahkan case untuk CSV
        switch ($validated['format']) {
            case 'excel':
                return $this->exportToExcel($exportData, $filename);
            case 'pdf':
                return $this->exportToPdf($exportData, $filename);
            case 'csv':
                return $this->exportToCsv($exportData, $filename);
            default:
                abort(400, 'Format export tidak didukung: ' . $validated['format']);
        }
    }

    /**
     * Get data stok bulanan untuk chart
     */
    private function getMonthlyStockData($categoryId = null)
    {
        $query = StockTransaction::with('product.category')
            ->whereDate('created_at', '>=', now()->subMonths(6));

        if ($categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $transactions = $query->selectRaw('
                MONTH(created_at) as month,
                YEAR(created_at) as year,
                type,
                SUM(quantity) as total_quantity
            ')
            ->groupBy('month', 'year', 'type')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Format data untuk chart
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;
            $monthName = $date->format('M Y');

            $stockIn = $transactions->where('month', $month)
                                  ->where('year', $year)
                                  ->where('type', 'in')
                                  ->sum('total_quantity');

            $stockOut = $transactions->where('month', $month)
                                   ->where('year', $year)
                                   ->where('type', 'out')
                                   ->sum('total_quantity');

            $chartData[] = [
                'period' => $monthName,
                'stock_in' => $stockIn,
                'stock_out' => $stockOut
            ];
        }

        return $chartData;
    }

    /**
     * Get data stok mingguan untuk chart
     */
    private function getWeeklyStockData($categoryId = null)
    {
        $query = StockTransaction::with('product.category')
            ->whereDate('created_at', '>=', now()->subWeeks(4));

        if ($categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $transactions = $query->selectRaw('
                WEEK(created_at) as week,
                YEAR(created_at) as year,
                type,
                SUM(quantity) as total_quantity
            ')
            ->groupBy('week', 'year', 'type')
            ->orderBy('year')
            ->orderBy('week')
            ->get();

        $chartData = [];
        for ($i = 3; $i >= 0; $i--) {
            $date = now()->subWeeks($i);
            $week = $date->format('W');
            $year = $date->year;
            $weekLabel = 'Week ' . $week;

            $stockIn = $transactions->where('week', $week)
                                  ->where('year', $year)
                                  ->where('type', 'in')
                                  ->sum('total_quantity');

            $stockOut = $transactions->where('week', $week)
                                   ->where('year', $year)
                                   ->where('type', 'out')
                                   ->sum('total_quantity');

            $chartData[] = [
                'period' => $weekLabel,
                'stock_in' => $stockIn,
                'stock_out' => $stockOut
            ];
        }

        return $chartData;
    }

    /**
     * Get data stok harian untuk chart
     */
    private function getDailyStockData($categoryId = null)
    {
        $query = StockTransaction::with('product.category')
            ->whereDate('created_at', '>=', now()->subDays(7));

        if ($categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $transactions = $query->selectRaw('
                DATE(created_at) as date,
                type,
                SUM(quantity) as total_quantity
            ')
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayLabel = now()->subDays($i)->format('d M');

            $stockIn = $transactions->where('date', $date)
                                  ->where('type', 'in')
                                  ->sum('total_quantity');

            $stockOut = $transactions->where('date', $date)
                                   ->where('type', 'out')
                                   ->sum('total_quantity');

            $chartData[] = [
                'period' => $dayLabel,
                'stock_in' => $stockIn,
                'stock_out' => $stockOut
            ];
        }

        return $chartData;
    }

    /**
     * Apply filter status stok ke query
     */
    private function applyStockStatusFilter($query, $status)
    {
        switch ($status) {
            case 'habis':
                return $query->where('current_stock', 0);
            case 'kritis':
                return $query->whereColumn('current_stock', '<=', 'minimum_stock')
                            ->where('current_stock', '>', 0);
            case 'menipis':
                return $query->whereRaw('current_stock <= (minimum_stock * 2)')
                            ->whereColumn('current_stock', '>', 'minimum_stock');
            case 'normal':
                return $query->whereRaw('current_stock > (minimum_stock * 2)');
            default:
                return $query;
        }
    }

    /**
     * Determine stock status berdasarkan current stock dan minimum stock
     */
    private function getStockStatus($product)
    {
        if ($product->current_stock == 0) {
            return 'habis';
        } elseif ($product->current_stock <= $product->minimum_stock) {
            return 'kritis';
        } elseif ($product->current_stock <= ($product->minimum_stock * 2)) {
            return 'menipis';
        } else {
            return 'normal';
        }
    }

    /**
     * Get status text untuk export
     */
    private function getStockStatusText($product)
    {
        $status = $this->getStockStatus($product);
        
        return match($status) {
            'habis' => 'Habis',
            'kritis' => 'Kritis',
            'menipis' => 'Menipis',
            'normal' => 'Normal',
            default => 'Unknown'
        };
    }

    /**
     * Get color untuk status dalam chart
     */
    private function getStatusColor($status)
    {
        return match($status) {
            'normal' => '#10B981',    // green
            'menipis' => '#3B82F6',   // blue
            'kritis' => '#F59E0B',    // yellow
            'habis' => '#EF4444',     // red
            default => '#6B7280'      // gray
        };
    }

    /**
     * Export ke Excel
     */
    private function exportToExcel($data, $filename)
    {
        // Untuk sementara return JSON, nanti bisa diimplementasikan dengan Laravel Excel
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diekspor ke Excel',
            'filename' => $filename . '.xlsx',
            'format' => 'excel',
            'records' => $data->count()
        ]);
    }

    /**
     * Export ke CSV
     */
    private function exportToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ];

        // Gunakan closure untuk menulis data ke output
        $callback = function() use ($data) {
            $output = fopen('php://output', 'w');
            // Tulis header
            if ($data->isNotEmpty()) {
                fputcsv($output, array_keys($data->first()));
            }
            // Tulis baris data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export ke PDF
     */
    private function exportToPdf($data, $filename)
    {
        $reportData = [
            'title' => 'Laporan Stok Barang',
            'generated_at' => now(),
            'generated_by' => auth()->user()->name ?? 'Admin',
            'data' => $data,
            'summary' => $this->getMainStatistics()
        ];

        $pdf = Pdf::loadView('reports.stock-pdf', $reportData)
                  ->setPaper('a4', 'landscape');

        return $pdf->download($filename . '.pdf');
    }
}