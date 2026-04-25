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
            var self = this;
            $(document).on('click', '.wc-tp-edit-item', function(e) { self.handleEditItem.call(self, e); });
            $(document).on('click', '.wc-tp-remove-item', function(e) { self.handleRemoveItem.call(self, e); });
            $(document).on('click', '.wc-tp-add-product', function(e) { self.handleAddProduct.call(self, e); });
            $(document).on('click', '.wc-tp-edit-order-meta', function(e) { self.handleEditOrderMeta.call(self, e); });
            $(document).on('click', '.wc-tp-recalculate-order', function(e) { self.handleRecalculateOrder.call(self, e); });
            $(document).on('click', '.wc-tp-edit-custom-field-btn', function(e) { self.handleEditCustomField.call(self, e, this); });
            $(document).on('click', '.wc-tp-toggle-custom-fields-edit', function(e) { self.handleToggleCustomFieldsEdit.call(self, e); });
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

        handleToggleCustomFieldsEdit: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var orderId = $btn.data('order-id');
            var $section = $btn.closest('.wc-tp-custom-fields-edit-section');
            
            // Find all p tags in Additional Information that contain custom fields
            var $additionalInfo = $section.closest('.order_data_column').find('p');
            var isEditing = $btn.hasClass('editing');
            
            if (!isEditing) {
                // Make fields editable
                $additionalInfo.each(function() {
                    var $p = $(this);
                    var text = $p.text().trim();
                    
                    // Skip empty or section headers
                    if (!text || text.length === 0) {
                        return;
                    }
                    
                    // Make the content editable
                    $p.attr('contenteditable', 'true');
                    $p.css({
                        'background': '#fffbea',
                        'border': '1px solid #ffb81c',
                        'padding': '8px',
                        'border-radius': '4px',
                        'cursor': 'text'
                    });
                });
                
                $btn.addClass('editing');
                $btn.html('<span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span> Save Custom Fields');
                $btn.css('background-color', '#28a745');
                $btn.css('border-color', '#28a745');
                $btn.css('color', '#fff');
                
                OrderEditor.showNotice('info', 'Click on any field to edit. Click Save when done.');
            } else {
                // Save and disable editing
                var fieldsData = {};
                $additionalInfo.each(function() {
                    var $p = $(this);
                    var originalText = $p.attr('data-original-text');
                    var newText = $p.text().trim();
                    
                    if (originalText && originalText !== newText) {
                        // Parse field name and value
                        var match = newText.match(/^([^:]+):\s*(.*)$/);
                        if (match) {
                            var fieldName = match[1].trim().toLowerCase().replace(/\s+/g, '_');
                            fieldsData[fieldName] = match[2].trim();
                        }
                    }
                    
                    $p.removeAttr('contenteditable');
                    $p.css({
                        'background': '',
                        'border': '',
                        'padding': '',
                        'border-radius': '',
                        'cursor': ''
                    });
                });
                
                $btn.removeClass('editing');
                $btn.html('<span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span> Edit Custom Fields');
                $btn.css('background-color', '');
                $btn.css('border-color', '');
                $btn.css('color', '');
                
                OrderEditor.showNotice('success', 'Custom fields updated. Save the order to apply changes.');
            }
        },

        handleEditCustomField: function(e, btnElement) {
            e.preventDefault();
            e.stopPropagation();
            
            var $btn = $(btnElement);
            var $row = $btn.closest('.wc-tp-custom-field-row');
            
            // Prevent multiple edits on same row
            if ($row.find('.wc-tp-field-editor').length > 0) {
                return;
            }
            
            var metaKey = $row.data('meta-key');
            var value = $btn.data('value');
            var fieldType = $row.data('field-type');
            var orderId = $row.data('order-id');
            
            // Hide display and button
            $row.find('.wc-tp-field-display').hide();
            $btn.hide();
            
            // Create editable field based on type using WooCommerce native inputs
            var fieldHtml = '';
            
            if (fieldType === 'checkbox') {
                var checked = (value === '1' || value.toLowerCase() === 'yes') ? 'checked' : '';
                fieldHtml = '<label style="display: flex; align-items: center; gap: 8px;"><input type="checkbox" class="wc-tp-field-value" ' + checked + ' style="margin: 0;"> <span>Yes</span></label>';
            } else if (fieldType === 'email') {
                fieldHtml = '<input type="email" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">';
            } else if (fieldType === 'url') {
                fieldHtml = '<input type="url" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">';
            } else if (fieldType === 'date') {
                fieldHtml = '<input type="date" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">';
            } else if (fieldType === 'number') {
                fieldHtml = '<input type="number" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">';
            } else if (fieldType === 'textarea') {
                fieldHtml = '<textarea class="wc-tp-field-value" rows="3" style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">' + value + '</textarea>';
            } else {
                fieldHtml = '<input type="text" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">';
            }
            
            // Create action buttons
            var buttonsHtml = '<button type="button" class="button button-small wc-tp-save-field" style="margin-left: 5px;">Save</button>' +
                '<button type="button" class="button button-small wc-tp-cancel-field" style="margin-left: 5px;">Cancel</button>';
            
            // Insert editable field
            var $fieldContainer = $('<div class="wc-tp-field-editor" style="display: flex; align-items: center; gap: 5px; flex: 1;"></div>');
            $fieldContainer.html(fieldHtml + buttonsHtml);
            
            $row.append($fieldContainer);
            
            // Focus on input
            var $input = $row.find('.wc-tp-field-value').first();
            $input.focus();
            
            // Helper function to save field
            var saveField = function() {
                var newValue = $row.find('.wc-tp-field-value').is(':checkbox') ? 
                    ($row.find('.wc-tp-field-value').is(':checked') ? '1' : '0') : 
                    $row.find('.wc-tp-field-value').val();
                
                var $saveBtn = $row.find('.wc-tp-save-field');
                $saveBtn.prop('disabled', true).text('Saving...');
                
                var ajaxData = {
                    action: 'wc_tp_save_custom_fields',
                    nonce: wcTpOrderEditor.nonce,
                    order_id: orderId,
                    fields: {}
                };
                ajaxData.fields[metaKey] = newValue;
                
                $.ajax({
                    url: wcTpOrderEditor.ajax_url,
                    type: 'POST',
                    data: ajaxData,
                    success: function(response) {
                        if (response.success) {
                            // Update display
                            $row.find('.wc-tp-field-display').text(newValue).show();
                            $row.find('.wc-tp-field-editor').remove();
                            $btn.show();
                            OrderEditor.showNotice('success', 'Field saved successfully.');
                        } else {
                            alert(response.data.message || 'Error saving field');
                            $saveBtn.prop('disabled', false).text('Save');
                        }
                    },
                    error: function() {
                        alert('Error saving field');
                        $saveBtn.prop('disabled', false).text('Save');
                    }
                });
            };
            
            // Helper function to cancel edit
            var cancelEdit = function() {
                $row.find('.wc-tp-field-display').show();
                $row.find('.wc-tp-field-editor').remove();
                $btn.show();
            };
            
            // Save handler
            $row.find('.wc-tp-save-field').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                saveField();
            });
            
            // Cancel handler
            $row.find('.wc-tp-cancel-field').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                cancelEdit();
            });
            
            // Keyboard shortcuts
            $input.on('keydown', function(e) {
                if (e.key === 'Enter' && fieldType !== 'textarea') {
                    e.preventDefault();
                    saveField();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelEdit();
                }
            });
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
