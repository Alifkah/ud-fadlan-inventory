<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
   <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'UD Fadlan') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#007bff",
                        "background-light": "#f8f9fa",
                        "background-dark": "#1a202c",
                        "card-light": "#ffffff",
                        "card-dark": "#2d3748",
                        "text-light": "#212529",
                        "text-dark": "#e2e8f0",
                        "text-muted-light": "#6c757d",
                        "text-muted-dark": "#a0aec0",
                    },
                    fontFamily: {
                        display: ["Poppins", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.5rem",
                    },
                },
            },
        };
    </script>

    @stack('styles')
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-card-light dark:bg-card-dark text-text-light dark:text-text-dark flex flex-col">
            <div class="p-6 text-l font-bold border-b border-gray-200 dark:border-gray-700 flex items-center">
                <span class="material-icons mr-2 text-primary">store</span> 
                {{ config('app.name', 'UD Fadlan') }}
            </div>
            
            <nav class="flex-1 p-4 space-y-2">
                <!-- Dashboard -->
                <div>
                    <h3 class="px-4 mb-2 text-xs font-semibold tracking-wider text-text-muted-light dark:text-text-muted-dark uppercase">Dashboard</h3>
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('dashboard') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">dashboard</span>
                        Dashboard
                    </a>
                </div>

                <!-- Inventori -->
                <div>
                    <h3 class="px-4 mb-2 text-xs font-semibold tracking-wider text-text-muted-light dark:text-text-muted-dark uppercase">Inventori</h3>
                    <a href="{{ route('stock.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('stock.*') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">inventory_2</span>
                        Stok Barang
                    </a>
                    <a href="{{ route('categories.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('categories.*') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">category</span>
                        Kategori Barang
                    </a>
                    <a href="{{ route('stock-opnames.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('stock-opnames.*') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">fact_check</span>
                        Stok Opname
                    </a>
                </div>

                <!-- Transaksi -->
                <div>
                    <h3 class="px-4 mb-2 text-xs font-semibold tracking-wider text-text-muted-light dark:text-text-muted-dark uppercase">Transaksi</h3>
                    <a href="{{ route('sales.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('sales.*') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">point_of_sale</span>
                        Penjualan
                    </a>
                    <a href="{{ route('purchases.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('purchases.*') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">shopping_cart</span>
                        Pembelian
                    </a>
                </div>

                <!-- Pelanggan -->
                <div>
                    <h3 class="px-4 mb-2 text-xs font-semibold tracking-wider text-text-muted-light dark:text-text-muted-dark uppercase">Pelanggan</h3>
                    <a href="{{ route('customers.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('customers.*') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">people</span>
                        Data Customer
                    </a>
                    <a href="{{ route('suppliers.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('suppliers.*') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">local_shipping</span>
                        Data Supplier
                    </a>
                    <a href="{{ route('customer-credits.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('customer-credits.*') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">credit_card</span>
                        Kredit Customer
                    </a>
                </div>

                <!-- Laporan -->
                <div>
                    <h3 class="px-4 mb-2 text-xs font-semibold tracking-wider text-text-muted-light dark:text-text-muted-dark uppercase">Laporan</h3>
                    <a href="{{ route('financial-reports.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('financial-reports.*') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">assessment</span>
                        Laporan Keuangan
                    </a>
                    <a href="{{ route('stock-reports.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('stock-reports.*') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">inventory</span>
                        Laporan Stok
                    </a>
                </div>

                <!-- Lainnya -->
                <div class="!mt-auto">
                    <h3 class="px-4 mb-2 text-xs font-semibold tracking-wider text-text-muted-light dark:text-text-muted-dark uppercase">Lainnya</h3>
                    <a href="{{ route('settings.index') }}" class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('settings.*') ? 'text-white bg-primary' : 'text-text-light dark:text-text-dark hover:bg-gray-100 dark:hover:bg-gray-700' }} rounded-lg">
                        <span class="material-icons mr-3">settings</span>
                        Pengaturan
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
            <!-- Header -->
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-text-light dark:text-text-dark">@yield('page-title', 'Dashboard')</h1>
                    <p class="text-text-muted-light dark:text-text-muted-dark">@yield('page-description', 'Selamat datang di dashboard')</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Dark Mode Toggle -->
                    <button id="darkModeToggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <span class="material-icons text-text-light dark:text-text-dark">dark_mode</span>
                    </button>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-bold text-lg">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div class="text-left">
                                <p class="font-semibold text-sm text-text-light dark:text-text-dark">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-text-muted-light dark:text-text-muted-dark">{{ ucfirst(Auth::user()->role ?? 'Admin') }}</p>
                            </div>
                            <span class="material-icons text-text-muted-light dark:text-text-muted-dark">keyboard_arrow_down</span>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-1 z-50">
                            <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-2 text-sm">person</span>
                                Profil
                            </a>
                            <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <span class="material-icons mr-2 text-sm">settings</span>
                                Pengaturan
                            </a>
                            <hr class="my-1 border-gray-200 dark:border-gray-700">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <span class="material-icons mr-2 text-sm">logout</span>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('warning') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="mb-6 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('info') }}</span>
                </div>
            @endif

            <!-- Page Content -->
            @yield('content')
        </main>
    </div>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Dark Mode Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const html = document.documentElement;
            
            // Check for saved theme preference or default to system preference
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const currentTheme = savedTheme || systemTheme;
            
            if (currentTheme === 'dark') {
                html.classList.add('dark');
            }
            
            darkModeToggle.addEventListener('click', function() {
                html.classList.toggle('dark');
                
                // Save preference to localStorage
                if (html.classList.contains('dark')) {
                    localStorage.setItem('theme', 'dark');
                } else {
                    localStorage.setItem('theme', 'light');
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>