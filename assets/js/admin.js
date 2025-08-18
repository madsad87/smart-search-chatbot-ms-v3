/**
 * Smart Search Chatbot Admin JavaScript
 * 
 * Handles admin interface interactions and AJAX requests
 */

(function($) {
    'use strict';
    
    // Wait for DOM ready
    $(document).ready(function() {
        
        // Initialize admin functionality
        initAdminFeatures();
        
    });
    
    /**
     * Initialize admin features
     */
    function initAdminFeatures() {
        
        // Auto-dismiss notices after 5 seconds
        setTimeout(function() {
            $('.notice.is-dismissible').fadeOut();
        }, 5000);
        
        // Handle dismissible notices
        $(document).on('click', '.notice-dismiss', function() {
            $(this).closest('.notice').fadeOut();
        });
        
        // Confirm dangerous actions
        $('.ssgc-admin-page').on('click', '[data-confirm]', function(e) {
            var message = $(this).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Handle loading states
        $('.ssgc-admin-page').on('click', '.button[data-loading-text]', function() {
            var $btn = $(this);
            var originalText = $btn.text();
            var loadingText = $btn.data('loading-text');
            
            $btn.prop('disabled', true).text(loadingText);
            
            // Re-enable after 3 seconds (fallback)
            setTimeout(function() {
                $btn.prop('disabled', false).text(originalText);
            }, 3000);
        });
        
    }
    
})(jQuery);
