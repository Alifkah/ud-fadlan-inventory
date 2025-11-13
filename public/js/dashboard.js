// Dashboard JavaScript functionality

// Utility functions
const DashboardUtils = {
    // Format currency
    formatCurrency: (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    },

    // Format number
    formatNumber: (number) => {
        return new Intl.NumberFormat('id-ID').format(number);
    },

    // Get CSRF token
    getCSRFToken: () => {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    },

    // Show toast notification
    showToast: (message, type = 'success') => {
        // Implementation for toast notifications
        console.log(`${type.toUpperCase()}: ${message}`);
    },

    // Debounce function
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Dashboard specific functionality
const Dashboard = {
    // Initialize dashboard
    init: () => {
        Dashboard.initCharts();
        Dashboard.initEventListeners();
        Dashboard.initAutoRefresh();
    },

    // Initialize charts
    initCharts: () => {
        // Sales chart will be initialized in the blade template
        // Category chart will be initialized in the blade template
    },

    // Initialize event listeners
    initEventListeners: () => {
        // Search functionality
        const searchInput = document.getElementById('search-transactions');
        if (searchInput) {
            searchInput.addEventListener('input', DashboardUtils.debounce((e) => {
                Dashboard.searchTransactions(e.target.value);
            }, 500));
        }

        // Date filters
        const startDate = document.getElementById('start-date');
        const endDate = document.getElementById('end-date');
        
        if (startDate && endDate) {
            startDate.addEventListener('change', Dashboard.filterTransactions);
            endDate.addEventListener('change', Dashboard.filterTransactions);
        }

        // Transaction type filter
        const transactionType = document.getElementById('transaction-type');
        if (transactionType) {
            transactionType.addEventListener('change', Dashboard.filterTransactions);
        }
    },

    // Initialize auto-refresh
    initAutoRefresh: () => {
        // Refresh stats every 5 minutes
        setInterval(() => {
            Dashboard.refreshStats();
        }, 300000);
    },

    // Search transactions
    searchTransactions: (query) => {
        // Implementation for transaction search
        console.log('Searching transactions:', query);
    },

    // Filter transactions
    filterTransactions: () => {
        const startDate = document.getElementById('start-date')?.value;
        const endDate = document.getElementById('end-date')?.value;
        const transactionType = document.getElementById('transaction-type')?.value;
        
        console.log('Filtering transactions:', { startDate, endDate, transactionType });
    },

    // Refresh stats
    refreshStats: async () => {
        try {
            const response = await fetch('/dashboard/stats');
            const data = await response.json();
            
            // Update stats in DOM
            const todayRevenue = document.getElementById('today-revenue');
            const todayTransactions = document.getElementById('today-transactions');
            const lowStock = document.getElementById('low-stock');
            
            if (todayRevenue && data.today_sales) {
                todayRevenue.textContent = DashboardUtils.formatCurrency(data.today_sales);
            }
            
            if (todayTransactions && data.today_transactions) {
                todayTransactions.textContent = DashboardUtils.formatNumber(data.today_transactions);
            }
            
            if (lowStock && data.low_stock_items) {
                lowStock.textContent = DashboardUtils.formatNumber(data.low_stock_items);
            }
            
        } catch (error) {
            console.error('Error refreshing stats:', error);
        }
    },

    // Update chart data
    updateChart: async (chart, period) => {
        try {
            const response = await fetch(`/dashboard/sales-chart?period=${period}`);
            const data = await response.json();
            
            chart.data.labels = data.map(item => item.period || item.date);
            chart.data.datasets[0].data = data.map(item => item.total / 1000000);
            chart.update();
            
        } catch (error) {
            console.error('Error updating chart:', error);
        }
    }
};

// Export for global use
window.Dashboard = Dashboard;
window.DashboardUtils = DashboardUtils;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    Dashboard.init();
});