@extends('layouts.app')

@section('title', 'Detail Supplier')
@section('page-title', 'Detail Supplier')
@section('page-description', 'Informasi lengkap supplier')

@section('content')
<!-- Back Button -->
<div class="mb-6">
    <a href="{{ route('suppliers.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
        <span class="material-icons">arrow_back</span>
        Kembali
    </a>
</div>

<!-- Supplier Information Card -->
<div class="bg-card-light dark:bg-card-dark rounded-lg shadow mb-6">
    <div class="p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $supplier->name }}</h2>
                <p class="text-gray-600 dark:text-gray-400">{{ $supplier->code }}</p>
            </div>
            <div class="flex gap-2">
                @if($supplier->is_active)
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Aktif</span>
                @else
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Tidak Aktif</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Nama Perusahaan</h3>
                <p class="text-gray-800 dark:text-white">{{ $supplier->company_name ?? '-' }}</p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Email</h3>
                <p class="text-gray-800 dark:text-white">{{ $supplier->email ?? '-' }}</p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Telepon</h3>
                <p class="text-gray-800 dark:text-white">{{ $supplier->phone ?? '-' }}</p>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Tanggal Kerja Sama</h3>
                <p class="text-gray-800 dark:text-white">{{ $supplier->created_at->format('d/m/Y') }}</p>
            </div>
            
            <div class="md:col-span-2">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Alamat</h3>
                <p class="text-gray-800 dark:text-white">{{ $supplier->address ?? '-' }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Total Pembelian</p>
                <h3 class="text-2xl font-bold text-text-light dark:text-text-dark mt-1">{{ $stats['total_purchases'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-primary">shopping_cart</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Nilai Total</p>
                <h3 class="text-xl font-bold text-green-600 mt-1">Rp {{ number_format($stats['total_purchase_value'], 0, ',', '.') }}</h3>
            </div>
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-green-600">payments</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Rata-rata Nilai</p>
                <h3 class="text-lg font-bold text-orange-600 mt-1">Rp {{ number_format($stats['average_purchase_value'], 0, ',', '.') }}</h3>
            </div>
            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-orange-600">trending_up</span>
            </div>
        </div>
    </div>

    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-text-muted-light dark:text-text-muted-dark text-sm">Pembelian Pending</p>
                <h3 class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['pending_purchases'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                <span class="material-icons text-yellow-600">pending</span>
            </div>
        </div>
    </div>
</div>

<!-- Purchase History -->
<div class="bg-card-light dark:bg-card-dark rounded-lg shadow">
    <div class="p-6">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Riwayat Pembelian</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3">No. Invoice</th>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Metode Bayar</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Kasir</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                    <tr class="bg-card-light dark:bg-card-dark border-b dark:border-gray-700">
                        <td class="px-6 py-4 font-medium">{{ $purchase->invoice_number }}</td>
                        <td class="px-6 py-4">{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">Rp {{ number_format($purchase->total, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if($purchase->payment_method == 'cash')
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Tunai</span>
                            @elseif($purchase->payment_method == 'credit')
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">Kredit</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">Transfer</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($purchase->status == 'completed')
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Selesai</span>
                            @elseif($purchase->status == 'pending')
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">Pending</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Dibatalkan</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $purchase->user->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('purchases.show', $purchase->id) }}" class="px-3 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-text-muted-light dark:text-text-muted-dark">
                            <span class="material-icons text-4xl mb-2">inbox</span>
                            <p>Belum ada riwayat pembelian</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($purchases->hasPages())
        <div class="mt-6">
            {{ $purchases->links() }}
        </div>
        @endif
    </div>
</div>
@endsection