// public/js/stock-management.js

$(document).ready(function() {
    // Auto generate product code
    generateProductCode();
    
    // Format number inputs
    formatNumberInputs();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Real-time search
    initializeRealTimeSearch();
    
    // Auto-save form data
    initializeAutoSave();
});

/**
 * Generate product code automatically
 */
function generateProductCode() {
    if ($('#addStockModal input[name="code"]').length && !$('#addStockModal input[name="code"]').val()) {
        $.get('/stock/api/generate-code', function(response) {
            if (response.success) {
                $('input[name="code"]').val(response.code);
            }
        });
    }
}

/**
 * Format number inputs for better UX
 */
function formatNumberInputs() {
    // Format price inputs with thousand separator
    $('input[name="purchase_price"], input[name="selling_price"]').on('input', function() {
        let value = $(this).val().replace(/[^0-9]/g, '');
        if (value) {
            $(this).val(value);
            // Calculate profit margin in real-time
            calculateProfitMargin();
        }
    });
    
    // Stock quantity validation
    $('input[name="current_stock"], input[name="minimum_stock"]').on('input', function() {
        let value = parseInt($(this).val()) || 0;
        $(this).val(value);
        
        // Visual indicator for minimum stock
        if ($(this).attr('name') === 'current_stock') {
            updateStockIndicator();
        }
    });
}

/**
 * Calculate and display profit margin
 */
function calculateProfitMargin() {
    const purchasePrice = parseInt($('input[name="purchase_price"]').val()) || 0;
    const sellingPrice = parseInt($('input[name="selling_price"]').val()) || 0;
    
    if (purchasePrice > 0 && sellingPrice > 0) {
        const margin = ((sellingPrice - purchasePrice) / purchasePrice * 100).toFixed(1);
        
        // Create or update margin indicator
        if (!$('.profit-margin-indicator').length) {
            $('input[name="selling_price"]').after(
                '<div class="profit-margin-indicator mt-1"></div>'
            );
        }
        
        const color = margin > 0 ? 'success' : 'danger';
        $('.profit-margin-indicator').html(
            `<small class="text-${color}">Margin: ${margin}%</small>`
        );
    }
}

/**
 * Update stock status indicator
 */
function updateStockIndicator() {
    const currentStock = parseInt($('input[name="current_stock"]').val()) || 0;
    const minimumStock = parseInt($('input[name="minimum_stock"]').val()) || 10;
    
    if (!$('.stock-indicator').length) {
        $('input[name="current_stock"]').after(
            '<div class="stock-indicator mt-1"></div>'
        );
    }
    
    let status = 'normal';
    let statusText = 'Normal';
    let color = 'success';
    
    if (currentStock === 0) {
        status = 'habis';
        statusText = 'Habis';
        color = 'danger';
    } else if (currentStock <= minimumStock) {
        status = 'kritis';
        statusText = 'Kritis';
        color = 'warning';
    } else if (currentStock <= (minimumStock * 2)) {
        status = 'menipis';
        statusText = 'Menipis';
        color = 'info';
    }
    
    $('.stock-indicator').html(
        `<small class="text-${color}">Status: ${statusText}</small>`
    );
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Real-time search functionality
 */
function initializeRealTimeSearch() {
    let searchTimeout;
    
    $('#searchStock').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val().toLowerCase();
        
        searchTimeout = setTimeout(function() {
            searchTable(searchTerm);
        }, 300);
    });
}

/**
 * Auto-save form data to localStorage for recovery
 */
function initializeAutoSave() {
    const formFields = ['code', 'name', 'category_id', 'unit', 'purchase_price', 'selling_price', 'current_stock', 'minimum_stock', 'location', 'description', 'supplier'];
    
    // Save form data on change
    formFields.forEach(field => {
        $(`[name="${field}"]`).on('change input', function() {
            saveFormData();
        });
    });
    
    // Load saved data when modal opens
    $('#addStockModal').on('shown.bs.modal', function() {
        if (!editingId) { // Only for new entries
            loadFormData();
        }
    });
    
    // Clear saved data on successful submission
    $('#stockForm').on('submit', function() {
        clearFormData();
    });
}

/**
 * Save form data to sessionStorage
 */
function saveFormData() {
    const formData = {};
    $('#stockForm').find('input, select, textarea').each(function() {
        const name = $(this).attr('name');
        if (name) {
            formData[name] = $(this).val();
        }
    });
    
    sessionStorage.setItem('stockFormData', JSON.stringify(formData));
}

/**
 * Load saved form data
 */
function loadFormData() {
    const savedData = sessionStorage.getItem('stockFormData');
    if (savedData) {
        const formData = JSON.parse(savedData);
        
        Object.keys(formData).forEach(field => {
            const element = $(`[name="${field}"]`);
            if (element.length && formData[field]) {
                element.val(formData[field]);
                
                // Trigger events for calculated fields
                if (['purchase_price', 'selling_price'].includes(field)) {
                    element.trigger('input');
                }
            }
        });
    }
}

/**
 * Clear saved form data
 */
function clearFormData() {
    sessionStorage.removeItem('stockFormData');
}

/**
 * Enhanced search function with highlighting
 */
function searchTable(searchTerm) {
    const rows = $('#stockTable tbody tr');
    let visibleCount = 0;
    
    rows.each(function() {
        const text = $(this).text().toLowerCase();
        const isVisible = text.includes(searchTerm);
        
        $(this).toggle(isVisible);
        
        if (isVisible) {
            visibleCount++;
            // Highlight search term
            if (searchTerm.length > 0) {
                highlightSearchTerm($(this), searchTerm);
            }
        }
    });
    
    // Show no results message
    toggleNoResultsMessage(visibleCount === 0 && searchTerm.length > 0);
    
    // Update result count
    updateResultCount(visibleCount, rows.length);
}

/**
 * Highlight search terms in table rows
 */
function highlightSearchTerm(row, searchTerm) {
    row.find('td').each(function() {
        const html = $(this).html();
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        const highlightedHtml = html.replace(regex, '<mark class="bg-warning">$1</mark>');
        $(this).html(highlightedHtml);
    });
}

/**
 * Toggle no results message
 */
function toggleNoResultsMessage(show) {
    if (show && !$('.no-results-message').length) {
        $('#stockTable tbody').append(`
            <tr class="no-results-message">
                <td colspan="10" class="text-center py-4 text-muted">
                    <i class="mdi mdi-magnify font-24"></i>
                    <p class="mt-2">Tidak ada hasil yang ditemukan</p>
                </td>
            </tr>
        `);
    } else if (!show) {
        $('.no-results-message').remove();
    }
}

/**
 * Update search result count
 */
function updateResultCount(visible, total) {
    if (!$('.search-result-count').length) {
        $('.table-responsive').before(`
            <div class="search-result-count text-muted mb-2"></div>
        `);
    }
    
    if ($('#searchStock').val().length > 0) {
        $('.search-result-count').text(`Menampilkan ${visible} dari ${total} item`);
    } else {
        $('.search-result-count').text('');
    }
}

/**
 * Enhanced filter functionality
 */
function filterTable(filter) {
    const rows = $('#stockTable tbody tr');
    let visibleCount = 0;
    
    // Remove search highlights first
    rows.find('mark').contents().unwrap();
    
    if (filter === 'semua') {
        rows.show();
        visibleCount = rows.length;
    } else {
        rows.each(function() {
            const badge = $(this).find('.badge');
            if (badge.length) {
                const status = badge.text().toLowerCase();
                const shouldShow = status.includes(filter);
                $(this).toggle(shouldShow);
                
                if (shouldShow) {
                    visibleCount++;
                }
            }
        });
    }
    
    updateResultCount(visibleCount, rows.length);
    toggleNoResultsMessage(visibleCount === 0);
}

/**
 * Export functionality
 */
function exportData(format = 'excel') {
    const filters = {
        category_id: $('[name="category_filter"]').val(),
        stock_status: $('.btn-group .btn.active').data('filter'),
        format: format
    };
    
    showLoadingIndicator('Mengekspor data...');
    
    $.post('/stock/api/export', filters, function(response) {
        hideLoadingIndicator();
        
        if (response.success) {
            // Create download link
            const blob = new Blob([JSON.stringify(response.data)], { type: 'application/json' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = response.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showAlert('success', 'Data berhasil diekspor');
        } else {
            showAlert('error', 'Gagal mengekspor data');
        }
    }).fail(function() {
        hideLoadingIndicator();
        showAlert('error', 'Terjadi kesalahan saat mengekspor data');
    });
}

/**
 * Show loading indicator
 */
function showLoadingIndicator(message = 'Memuat...') {
    if (!$('.loading-overlay').length) {
        $('body').append(`
            <div class="loading-overlay position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5); z-index: 9999;">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="mb-0">${message}</p>
                    </div>
                </div>
            </div>
        `);
    }
}

/**
 * Hide loading indicator
 */
function hideLoadingIndicator() {
    $('.loading-overlay').remove();
}

/**
 * Bulk operations
 */
function initializeBulkOperations() {
    // Select all checkbox
    $('#selectAll').change(function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActionsBar();
    });
    
    // Individual checkboxes
    $(document).on('change', '.row-checkbox', function() {
        updateBulkActionsBar();
    });
    
    // Bulk delete
    $('#bulkDelete').click(function() {
        const selectedIds = getSelectedIds();
        if (selectedIds.length > 0) {
            confirmBulkDelete(selectedIds);
        }
    });
}

/**
 * Get selected row IDs
 */
function getSelectedIds() {
    return $('.row-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
}

/**
 * Update bulk actions bar
 */
function updateBulkActionsBar() {
    const selectedCount = $('.row-checkbox:checked').length;
    
    if (selectedCount > 0) {
        if (!$('.bulk-actions-bar').length) {
            $('.table-responsive').before(`
                <div class="bulk-actions-bar alert alert-info d-flex justify-content-between align-items-center">
                    <span class="selected-count">${selectedCount} item dipilih</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-danger" id="bulkDelete">
                            <i class="mdi mdi-delete me-1"></i> Hapus Terpilih
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary ms-2" onclick="clearSelection()">
                            Batal
                        </button>
                    </div>
                </div>
            `);
        } else {
            $('.selected-count').text(`${selectedCount} item dipilih`);
        }
    } else {
        $('.bulk-actions-bar').remove();
        $('#selectAll').prop('checked', false);
    }
}

/**
 * Clear selection
 */
function clearSelection() {
    $('.row-checkbox, #selectAll').prop('checked', false);
    updateBulkActionsBar();
}

/**
 * Confirm bulk delete
 */
function confirmBulkDelete(ids) {
    if (confirm(`Apakah Anda yakin ingin menghapus ${ids.length} item terpilih?`)) {
        $.ajax({
            url: '/stock/bulk-delete',
            method: 'POST',
            data: {
                product_ids: ids,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    location.reload();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Terjadi kesalahan saat menghapus data');
            }
        });
    }
}

/**
 * Keyboard shortcuts
 */
function initializeKeyboardShortcuts() {
    $(document).on('keydown', function(e) {
        // Ctrl + N: New stock
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            $('#addStockModal').modal('show');
        }
        
        // Ctrl + F: Focus search
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            $('#searchStock').focus();
        }
        
        // Escape: Close modal or clear search
        if (e.key === 'Escape') {
            if ($('.modal.show').length) {
                $('.modal.show').modal('hide');
            } else if ($('#searchStock').val()) {
                $('#searchStock').val('').trigger('input');
            }
        }
    });
}