/**
 * Order Editor JavaScript
 * Handles order editing functionality
 *
 * @package WooCommerce Team Payroll
 * @since 1.7.65
 */

(function($) {
    'use strict';

    var OrderEditor = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.wc-tp-edit-item', this.handleEditItem);
            $(document).on('click', '.wc-tp-remove-item', this.handleRemoveItem);
            $(document).on('click', '.wc-tp-add-product', this.handleAddProduct);
            $(document).on('click', '.wc-tp-edit-order-meta', this.handleEditOrderMeta);
            $(document).on('click', '.wc-tp-recalculate-order', this.handleRecalculateOrder);
        },

        handleEditItem: function(e) {
            e.preventDefault();
            OrderEditor.showNotice('info', 'Item editing coming soon.');
        },

        handleRemoveItem: function(e) {
            e.preventDefault();
            OrderEditor.showNotice('info', 'Item removal coming soon.');
        },

        handleAddProduct: function(e) {
            e.preventDefault();
            OrderEditor.showNotice('info', 'Product adding coming soon.');
        },

        handleEditOrderMeta: function(e) {
            e.preventDefault();
            OrderEditor.showNotice('info', 'Order meta editing coming soon.');
        },

        handleRecalculateOrder: function(e) {
            e.preventDefault();
            OrderEditor.showNotice('info', 'Order recalculation coming soon.');
        },

        showNotice: function(type, message) {
            var noticeClass = type === 'success' ? 'notice-success' : (type === 'error' ? 'notice-error' : 'notice-info');
            var noticeHtml = '<div class="notice ' + noticeClass + ' is-dismissible wc-tp-notice" style="position: fixed; top: 32px; right: 20px; z-index: 999999; max-width: 400px;"><p>' + message + '</p></div>';
            
            $('.wc-tp-notice').remove();
            $('body').append(noticeHtml);
            
            setTimeout(function() {
                $('.wc-tp-notice').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    $(document).ready(function() {
        OrderEditor.init();
    });

})(jQuery);
