<?php

namespace App\Helpers;

class DashboardHelper
{
    /**
     * Get color for category chart
     */
    public static function getCategoryColor($index)
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
     * Format currency
     */
    public static function formatCurrency($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Get status badge class
     */
    public static function getStatusBadge($status)
    {
        $badges = [
            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'pending' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'canceled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'inactive' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
        ];

        return $badges[$status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }

    /**
     * Get stock status color
     */
    public static function getStockStatusColor($status)
    {
        $colors = [
            'normal' => '#10B981',    // green
            'menipis' => '#3B82F6',   // blue  
            'kritis' => '#F59E0B',    // yellow
            'habis' => '#EF4444',     // red
        ];

        return $colors[$status] ?? '#6B7280'; // gray
    }
}