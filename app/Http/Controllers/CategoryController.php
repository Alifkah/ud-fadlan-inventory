<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Tampilkan daftar kategori beserta statistik produk
     */
    public function index()
    {
        // Ambil semua kategori dengan relasi dan hitung statistik
        $categories = Category::withCount([
            'products',
            'products as active_products_count' => function ($query) {
                $query->where('is_active', true);
            }
        ])->with(['products' => function ($query) {
            $query->select('category_id', 'current_stock', 'minimum_stock', 'is_active')
                  ->where('is_active', true);
        }])
        ->get(); // Tampilkan semua kategori (aktif dan tidak aktif)

        // Hitung statistik untuk setiap kategori
        $categoryStats = $categories->map(function ($category) {
            $totalProducts = $category->products_count ?? 0;
            $activeProducts = $category->active_products_count ?? 0;
            
            // Hitung produk dengan stok rendah
            $lowStockProducts = 0;
            if ($category->products) {
                $lowStockProducts = $category->products->filter(function ($product) {
                    return $product->current_stock <= $product->minimum_stock;
                })->count();
            }

            $totalStock = $category->products->sum('current_stock') ?? 0;

            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'low_stock_products' => $lowStockProducts,
                'total_stock' => $totalStock,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at
            ];
        });

        // Statistik keseluruhan
        $overallStats = [
            'total_categories' => Category::count(),
            'active_categories' => Category::where('is_active', true)->count(),
            'low_stock_items' => Product::whereColumn('current_stock', '<=', 'minimum_stock')
                                       ->where('is_active', true)
                                       ->count(),
            'total_products' => Product::where('is_active', true)->count()
        ];

        return view('categories.index', compact('categoryStats', 'overallStats'));
    }

    /**
     * Tampilkan formulir untuk membuat kategori baru
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Simpan kategori yang baru dibuat di penyimpanan
     */
    public function store(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
                'description' => 'nullable|string|max:1000',
                'icon' => 'nullable|string|max:100',
            ]);

            // Handle checkbox is_active secara manual
            $validated['is_active'] = $request->has('is_active') ? 1 : 0;

            // Debug log untuk melihat data yang akan disimpan
            Log::info('Category data to save:', $validated);

            $category = Category::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil ditambahkan',
                'data' => $category
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error:', $e->errors());
            
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan kategori: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan kategori yang ditentukan beserta produk-produknya
     */
    public function show(Category $category)
    {
        $category->load(['products' => function ($query) {
            $query->with('category')
                  ->where('is_active', true)
                  ->orderBy('name');
        }]);

        $categoryStats = [
            'total_products' => $category->products->count(),
            'total_stock_value' => $category->products->sum(function ($product) {
                return $product->current_stock * $product->selling_price;
            }),
            'low_stock_products' => $category->products->filter(function ($product) {
                return $product->current_stock <= $product->minimum_stock;
            })->count(),
            'out_of_stock_products' => $category->products->where('current_stock', 0)->count()
        ];

        return view('categories.show', compact('category', 'categoryStats'));
    }

    /**
     * Tampilkan formulir untuk mengedit kategori yang ditentukan
     */
    public function edit(Category $category)
    {
        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Perbarui kategori yang ditentukan di penyimpanan
     */
    public function update(Request $request, Category $category)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category->id)],
                'description' => 'nullable|string|max:1000',
            ]);

            // Handle checkbox is_active
            $validated['is_active'] = $request->has('is_active') ? 1 : 0;

            $category->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diperbarui',
                'data' => $category
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui kategori: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus kategori yang ditentukan dari penyimpanan
     */
    public function destroy(Category $category)
    {
        try {
            // Periksa apakah kategori memiliki produk
            if ($category->products()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak dapat dihapus karena masih memiliki produk'
                ], 422);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus kategori'
            ], 500);
        }
    }

    /**
     * Dapatkan produk berdasarkan kategori untuk manajemen persediaan
     */
    public function getProductsByCategory(Category $category)
    {
        $products = $category->products()
            ->with(['category', 'stockTransactions' => function ($query) {
                $query->latest()->limit(5);
            }])
            ->where('is_active', true)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'unit' => $product->unit,
                    'current_stock' => $product->current_stock,
                    'minimum_stock' => $product->minimum_stock,
                    'purchase_price' => $product->purchase_price,
                    'selling_price' => $product->selling_price,
                    'location' => $product->location,
                    'status' => $this->getStockStatus($product),
                    'stock_value' => $product->current_stock * $product->purchase_price,
                    'last_transaction' => $product->stockTransactions->first()
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Dapatkan statistik kategori untuk dashboard
     */
    public function getCategoryStatistics()
    {
        $stats = Category::select('id', 'name')
            ->withCount([
                'products',
                'products as active_products_count' => function ($query) {
                    $query->where('is_active', true);
                },
                'products as low_stock_products_count' => function ($query) {
                    $query->whereColumn('current_stock', '<=', 'minimum_stock')
                          ->where('is_active', true);
                },
                'products as out_of_stock_products_count' => function ($query) {
                    $query->where('current_stock', 0)
                          ->where('is_active', true);
                }
            ])
            ->where('is_active', true)
            ->get()
            ->map(function ($category) {
                $totalStockValue = $category->products()
                    ->where('is_active', true)
                    ->get()
                    ->sum(function ($product) {
                        return $product->current_stock * $product->purchase_price;
                    });

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'total_products' => $category->products_count,
                    'active_products' => $category->active_products_count,
                    'low_stock_products' => $category->low_stock_products_count,
                    'out_of_stock_products' => $category->out_of_stock_products_count,
                    'total_stock_value' => $totalStockValue
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Aktifkan/nonaktifkan status kategori
     */
    public function toggleStatus(Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);

        $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Kategori berhasil {$status}",
            'data' => $category
        ]);
    }

    /**
     * Bulk delete categories
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        try {
            $categories = Category::whereIn('id', $validated['category_ids'])->get();
            $deletedCount = 0;
            $skippedCount = 0;

            foreach ($categories as $category) {
                // Cek apakah kategori memiliki produk
                if ($category->products()->exists()) {
                    $skippedCount++;
                    continue;
                }
                
                $category->delete();
                $deletedCount++;
            }

            $message = "Berhasil menghapus {$deletedCount} kategori";
            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} kategori dilewati karena memiliki produk";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'deleted_count' => $deletedCount,
                    'skipped_count' => $skippedCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus kategori: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search categories untuk autocomplete
     */
    public function search(Request $request)
    {
        $search = $request->get('search', '');
        
        $categories = Category::where('is_active', true)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'is_active' => $category->is_active
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get category info untuk dropdown/select
     */
    public function getCategoryInfo(Category $category)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'products_count' => $category->products()->count(),
                'active_products_count' => $category->products()->where('is_active', true)->count()
            ]
        ]);
    }

    /**
     * Get categories untuk dropdown
     */
    public function getActiveCategories()
    {
        $categories = Category::where('is_active', true)
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Generate laporan kategori
     */
    public function getCategoryReport(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'category_id' => 'nullable|exists:categories,id'
        ]);

        $query = Category::with(['products' => function ($productQuery) use ($validated) {
            $productQuery->where('is_active', true);
            
            if (isset($validated['date_from'])) {
                $productQuery->whereDate('created_at', '>=', $validated['date_from']);
            }
            
            if (isset($validated['date_to'])) {
                $productQuery->whereDate('created_at', '<=', $validated['date_to']);
            }
        }]);

        if (isset($validated['category_id'])) {
            $query->where('id', $validated['category_id']);
        }

        $categories = $query->get()->map(function ($category) {
            $totalStockValue = $category->products->sum(function ($product) {
                return $product->current_stock * $product->purchase_price;
            });
            
            $lowStockProducts = $category->products->filter(function ($product) {
                return $product->current_stock <= $product->minimum_stock;
            })->count();

            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'total_products' => $category->products->count(),
                'active_products' => $category->products->where('is_active', true)->count(),
                'low_stock_products' => $lowStockProducts,
                'total_stock_value' => $totalStockValue,
                'is_active' => $category->is_active
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Periksa status persediaan berdasarkan persediaan saat ini dan persediaan minimum
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
     * Ekspor data kategori
     */
    public function export()
    {
        $categories = Category::with(['products' => function ($query) {
            $query->where('is_active', true);
        }])->get(); // Export semua kategori

        $data = $categories->map(function ($category) {
            $totalStockValue = $category->products->sum(function ($product) {
                return $product->current_stock * $product->purchase_price;
            });
            
            $lowStockProducts = $category->products->filter(function ($product) {
                return $product->current_stock <= $product->minimum_stock;
            })->count();

            return [
                'Nama Kategori' => $category->name,
                'Deskripsi' => $category->description ?: 'Tidak ada deskripsi',
                'Total Produk' => $category->products->count(),
                'Total Stok' => $category->products->sum('current_stock'),
                'Produk Stok Rendah' => $lowStockProducts,
                'Nilai Stok' => 'Rp ' . number_format($totalStockValue, 0, ',', '.'),
                'Status' => $category->is_active ? 'Aktif' : 'Tidak Aktif',
                'Dibuat' => $category->created_at->format('d/m/Y H:i'),
                'Diperbarui' => $category->updated_at->format('d/m/Y H:i')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'filename' => 'kategori_barang_' . date('Y-m-d_H-i-s') . '.xlsx'
        ]);
    }

    /**
     * Import data kategori dari file Excel/CSV
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            DB::beginTransaction();

            // Implementasi import logic here
            // Anda bisa menggunakan Laravel Excel package
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data kategori berhasil diimport'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing categories: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data kategori: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate category
     */
    public function duplicate(Category $category)
    {
        try {
            $newCategory = $category->replicate();
            $newCategory->name = $category->name . ' (Copy)';
            $newCategory->save();

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diduplikasi',
                'data' => $newCategory
            ]);
        } catch (\Exception $e) {
            Log::error('Error duplicating category: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menduplikasi kategori: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category with products untuk detailed view
     */
    public function getCategoryWithProducts(Category $category, Request $request)
    {
        $perPage = $request->get('per_page', 10);
        
        $products = $category->products()
            ->where('is_active', true)
            ->with(['stockTransactions' => function ($query) {
                $query->latest()->limit(3);
            }])
            ->paginate($perPage);

        $categoryData = [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'is_active' => $category->is_active,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
            'statistics' => [
                'total_products' => $category->products()->count(),
                'active_products' => $category->products()->where('is_active', true)->count(),
                'total_stock' => $category->products()->where('is_active', true)->sum('current_stock'),
                'low_stock_products' => $category->products()
                    ->where('is_active', true)
                    ->whereColumn('current_stock', '<=', 'minimum_stock')
                    ->count(),
                'total_stock_value' => $category->products()
                    ->where('is_active', true)
                    ->get()
                    ->sum(function ($product) {
                        return $product->current_stock * $product->purchase_price;
                    })
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $categoryData,
                'products' => $products
            ]
        ]);
    }
}