/**
 * Order Editor JavaScript
 * Handles order editing functionality
 *
 * @package WooCommerce Team Payroll
 * @since 1.7.61
 */

(function($) {
    'use strict';

    const OrderEditor = {
        init: function() {
            this.bindEvents();
            this.initProductSelect();
        },

        bindEvents: function() {
            // Edit item
            $(document).on('click', '.wc-tp-edit-item', this.handleEditItem);
            
            // Remove item
            $(document).on('click', '.wc-tp-remove-item', this.handleRemoveItem);
            
            // Add product
            $(document).on('click', '.wc-tp-add-product', this.handleAddProduct);
            
            // Edit order meta
            $(document).on('click', '.wc-tp-edit-order-meta', this.handleEditOrderMeta);
            
            // Recalculate order
            $(document).on('click', '.wc-tp-recalculate-order', this.handleRecalculateOrder);
        },

        initProductSelect: function() {
            // Initialize WooCommerce product select if available
            if (typeof $.fn.selectWoo !== 'undefined') {
                $('.wc-tp-product-search').selectWoo({
                    placeholder: wcTpOrderEditor.i18n.select_product,
                    minimumInputLength: 3,
                    ajax: {
                        url: wcTpOrderEditor.ajax_url,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                action: 'woocommerce_json_search_products',
                                term: params.term,
                                security: wcTpOrderEditor.nonce
                            };
                        },
                        processResults: function(data) {
                            const results = [];
                            if (data) {
                                $.each(data, function(id, text) {
                                    results.push({
                                        id: id,
                                        text: text
                                    });
                                });
                            }
                            return {
                                results: results
                            };
                        }
                    }
                });
            }
        },

        handleEditItem: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const itemId = $button.data('item-id');
            const $row = $button.closest('.woocommerce_order_item');
            
            // Get current values
            const $qtyInput = $row.find('.quantity input');
            const $lineSubtotal = $row.find('.line_subtotal input');
            const $lineTotal = $row.find('.line_total input');
            
            const currentQty = $qtyInput.val();
            const currentSubtotal = $lineSubtotal.val();
            const currentTotal = $lineTotal.val();
            
            // Create edit modal
            const modalHtml = `
                <div class="wc-tp-modal-overlay">
                    <div class="wc-tp-modal">
                        <div class="wc-tp-modal-header">
                            <h2>${wcTpOrderEditor.i18n.edit_item || 'Edit Order Item'}</h2>
                            <button class="wc-tp-modal-close">&times;</button>
                        </div>
                        <div class="wc-tp-modal-body">
                            <div class="wc-tp-form-group">
                                <label>Quantity:</label>
                                <input type="number" class="wc-tp-edit-quantity" value="${currentQty}" min="1" step="1">
                            </div>
                            <div class="wc-tp-form-group">
                                <label>Subtotal:</label>
                                <input type="number" class="wc-tp-edit-subtotal" value="${currentSubtotal}" min="0" step="0.01">
                            </div>
                            <div class="wc-tp-form-group">
                                <label>Total:</label>
                                <input type="number" class="wc-tp-edit-total" value="${currentTotal}" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="wc-tp-modal-footer">
                            <button class="button button-secondary wc-tp-modal-close">Cancel</button>
                            <button class="button button-primary wc-tp-save-item" data-item-id="${itemId}">Save Changes</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Bind modal events
            $('.wc-tp-modal-close').on('click', function() {
                $('.wc-tp-modal-overlay').remove();
            });
            
            $('.wc-tp-save-item').on('click', function() {
                OrderEditor.saveItemChanges(itemId);
            });
        },

        saveItemChanges: function(itemId) {
            const quantity = $('.wc-tp-edit-quantity').val();
            const subtotal = $('.wc-tp-edit-subtotal').val();
            const total = $('.wc-tp-edit-total').val();
            
            const $saveBtn = $('.wc-tp-save-item');
            $saveBtn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: wcTpOrderEditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_tp_update_order_item',
                    nonce: wcTpOrderEditor.nonce,
                    order_id: wcTpOrderEditor.order_id,
                    item_id: itemId,
                    quantity: quantity,
                    subtotal: subtotal,
                    total: total
                },
                success: function(response) {
                    if (response.success) {
                        $('.wc-tp-modal-overlay').remove();
                        OrderEditor.showNotice('success', response.data.message);
                        
                        // Reload page to show updated values
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        OrderEditor.showNotice('error', response.data.message);
                        $saveBtn.prop('disabled', false).text('Save Changes');
                    }
                },
                error: function() {
                    OrderEditor.showNotice('error', 'An error occurred while updating the item.');
                    $saveBtn.prop('disabled', false).text('Save Changes');
                }
            });
        },

        handleRemoveItem: function(e) {
            e.preventDefault();
            
            if (!confirm(wcTpOrderEditor.i18n.confirm_remove)) {
                return;
            }
            
            const $button = $(this);
            const itemId = $button.data('item-id');
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: wcTpOrderEditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_tp_remove_order_item',
                    nonce: wcTpOrderEditor.nonce,
                    order_id: wcTpOrderEditor.order_id,
                    item_id: itemId
                },
                success: function(response) {
                    if (response.success) {
                        OrderEditor.showNotice('success', response.data.message);
                        
                        // Reload page to show updated order
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        OrderEditor.showNotice('error', response.data.message);
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    OrderEditor.showNotice('error', 'An error occurred while removing the item.');
                    $button.prop('disabled', false);
                }
            });
        },

        handleAddProduct: function(e) {
            e.preventDefault();
            
            // Create add product modal
            const modalHtml = `
                <div class="wc-tp-modal-overlay">
                    <div class="wc-tp-modal wc-tp-modal-large">
                        <div class="wc-tp-modal-header">
                            <h2>Add Product to Order</h2>
                            <button class="wc-tp-modal-close">&times;</button>
                        </div>
                        <div class="wc-tp-modal-body">
                            <div class="wc-tp-form-group">
                                <label>Search Product:</label>
                                <select class="wc-tp-product-search" style="width: 100%;"></select>
                            </div>
                            <div class="wc-tp-form-group">
                                <label>Quantity:</label>
                                <input type="number" class="wc-tp-add-quantity" value="1" min="1" step="1">
                            </div>
                        </div>
                        <div class="wc-tp-modal-footer">
                            <button class="button button-secondary wc-tp-modal-close">Cancel</button>
                            <button class="button button-primary wc-tp-add-product-submit">Add Product</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Initialize product search
            OrderEditor.initProductSelect();
            
            // Bind modal events
            $('.wc-tp-modal-close').on('click', function() {
                $('.wc-tp-modal-overlay').remove();
            });
            
            $('.wc-tp-add-product-submit').on('click', function() {
                OrderEditor.addProductToOrder();
            });
        },

        addProductToOrder: function() {
            const productId = $('.wc-tp-product-search').val();
            const quantity = $('.wc-tp-add-quantity').val();
            
            if (!productId) {
                OrderEditor.showNotice('error', 'Please select a product.');
                return;
            }
            
            const $addBtn = $('.wc-tp-add-product-submit');
            $addBtn.prop('disabled', true).text('Adding...');
            
            $.ajax({
                url: wcTpOrderEditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_tp_add_order_item',
                    nonce: wcTpOrderEditor.nonce,
                    order_id: wcTpOrderEditor.order_id,
                    product_id: productId,
                    quantity: quantity
                },
                success: function(response) {
                    if (response.success) {
                        $('.wc-tp-modal-overlay').remove();
                        OrderEditor.showNotice('success', response.data.message);
                        
                        // Reload page to show new item
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        OrderEditor.showNotice('error', response.data.message);
                        $addBtn.prop('disabled', false).text('Add Product');
                    }
                },
                error: function() {
                    OrderEditor.showNotice('error', 'An error occurred while adding the product.');
                    $addBtn.prop('disabled', false).text('Add Product');
                }
            });
        },

        handleEditOrderMeta: function(e) {
            e.preventDefault();
            
            const orderId = $(this).data('order-id');
            
            // Get current order meta
            $.ajax({
                url: wcTpOrderEditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_tp_get_order_edit_data',
                    nonce: wcTpOrderEditor.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        OrderEditor.showMetaEditor(response.data.meta);
                    } else {
                        OrderEditor.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    OrderEditor.showNotice('error', 'An error occurred while loading order meta.');
                }
            });
        },

        showMetaEditor: function(metaData) {
            let metaRows = '';
            
            if (metaData && metaData.length > 0) {
                metaData.forEach(function(meta) {
                    // Skip internal WooCommerce meta
                    if (meta.key.startsWith('_')) {
                        return;
                    }
                    
                    metaRows += `
                        <tr>
                            <td><input type="text" class="wc-tp-meta-key" value="${meta.key}" readonly></td>
                            <td><input type="text" class="wc-tp-meta-value" value="${meta.value}" data-key="${meta.key}"></td>
                            <td>
                                <button class="button button-small wc-tp-update-meta" data-key="${meta.key}">Update</button>
                                <button class="button button-small wc-tp-delete-meta" data-key="${meta.key}">Delete</button>
                            </td>
                        </tr>
                    `;
                });
            }
            
            const modalHtml = `
                <div class="wc-tp-modal-overlay">
                    <div class="wc-tp-modal wc-tp-modal-large">
                        <div class="wc-tp-modal-header">
                            <h2>Edit Order Meta</h2>
                            <button class="wc-tp-modal-close">&times;</button>
                        </div>
                        <div class="wc-tp-modal-body">
                            <table class="wc-tp-meta-table">
                                <thead>
                                    <tr>
                                        <th>Meta Key</th>
                                        <th>Meta Value</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${metaRows || '<tr><td colspan="3">No custom meta fields found.</td></tr>'}
                                </tbody>
                            </table>
                            <div class="wc-tp-add-meta-section" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                                <h3>Add New Meta Field</h3>
                                <div class="wc-tp-form-group">
                                    <label>Meta Key:</label>
                                    <input type="text" class="wc-tp-new-meta-key" placeholder="custom_field_name">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Meta Value:</label>
                                    <input type="text" class="wc-tp-new-meta-value" placeholder="Value">
                                </div>
                                <button class="button button-primary wc-tp-add-new-meta">Add Meta Field</button>
                            </div>
                        </div>
                        <div class="wc-tp-modal-footer">
                            <button class="button button-secondary wc-tp-modal-close">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Bind modal events
            $('.wc-tp-modal-close').on('click', function() {
                $('.wc-tp-modal-overlay').remove();
            });
            
            $('.wc-tp-update-meta').on('click', function() {
                const metaKey = $(this).data('key');
                const metaValue = $(this).closest('tr').find('.wc-tp-meta-value').val();
                OrderEditor.updateOrderMeta(metaKey, metaValue, 'update');
            });
            
            $('.wc-tp-delete-meta').on('click', function() {
                if (!confirm(wcTpOrderEditor.i18n.confirm_delete_meta)) {
                    return;
                }
                const metaKey = $(this).data('key');
                OrderEditor.updateOrderMeta(metaKey, '', 'delete');
            });
            
            $('.wc-tp-add-new-meta').on('click', function() {
                const metaKey = $('.wc-tp-new-meta-key').val();
                const metaValue = $('.wc-tp-new-meta-value').val();
                
                if (!metaKey) {
                    OrderEditor.showNotice('error', 'Meta key is required.');
                    return;
                }
                
                OrderEditor.updateOrderMeta(metaKey, metaValue, 'add');
            });
        },

        updateOrderMeta: function(metaKey, metaValue, actionType) {
            $.ajax({
                url: wcTpOrderEditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_tp_update_order_meta',
                    nonce: wcTpOrderEditor.nonce,
                    order_id: wcTpOrderEditor.order_id,
                    meta_key: metaKey,
                    meta_value: metaValue,
                    action_type: actionType
                },
                success: function(response) {
                    if (response.success) {
                        OrderEditor.showNotice('success', response.data.message);
                        
                        // Reload modal to show updated meta
                        setTimeout(function() {
                            $('.wc-tp-modal-overlay').remove();
                            $('.wc-tp-edit-order-meta').trigger('click');
                        }, 1000);
                    } else {
                        OrderEditor.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    OrderEditor.showNotice('error', 'An error occurred while updating order meta.');
                }
            });
        },

        handleRecalculateOrder: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            $button.prop('disabled', true).text('Recalculating...');
            
            // Trigger WooCommerce recalculation
            $('#woocommerce-order-items').find('.calculate-action').trigger('click');
            
            setTimeout(function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Recalculate Totals');
                OrderEditor.showNotice('success', 'Order totals recalculated successfully.');
            }, 2000);
        },

        showNotice: function(type, message) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const noticeHtml = `
                <div class="notice ${noticeClass} is-dismissible wc-tp-notice" style="position: fixed; top: 32px; right: 20px; z-index: 999999; max-width: 400px;">
                    <p>${message}</p>
                </div>
            `;
            
            $('.wc-tp-notice').remove();
            $('body').append(noticeHtml);
            
            setTimeout(function() {
                $('.wc-tp-notice').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        OrderEditor.init();
    });

})(jQuery);

        // Add shipping
        $(document).on('click', '.wc-tp-add-shipping', this.handleAddShipping);
        
        // Add fee
        $(document).on('click', '.wc-tp-add-fee', this.handleAddFee);
        
        // Add coupon
        $(document).on('click', '.wc-tp-add-coupon', this.handleAddCoupon);
        
        // Edit addresses
        $(document).on('click', '.wc-tp-edit-addresses', this.handleEditAddresses);
    },

    handleAddShipping: function(e) {
        e.preventDefault();
        
        const modalHtml = `
            <div class="wc-tp-modal-overlay">
                <div class="wc-tp-modal">
                    <div class="wc-tp-modal-header">
                        <h2>Add/Edit Shipping Method</h2>
                        <button class="wc-tp-modal-close">&times;</button>
                    </div>
                    <div class="wc-tp-modal-body">
                        <div class="wc-tp-form-group">
                            <label>Shipping Method Name:</label>
                            <input type="text" class="wc-tp-shipping-title" placeholder="Flat Rate" value="Flat Rate">
                        </div>
                        <div class="wc-tp-form-group">
                            <label>Method ID:</label>
                            <input type="text" class="wc-tp-shipping-id" placeholder="flat_rate" value="flat_rate">
                        </div>
                        <div class="wc-tp-form-group">
                            <label>Shipping Cost:</label>
                            <input type="number" class="wc-tp-shipping-cost" value="0" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="wc-tp-modal-footer">
                        <button class="button button-secondary wc-tp-modal-close">Cancel</button>
                        <button class="button button-primary wc-tp-save-shipping">Add Shipping</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        $('.wc-tp-modal-close').on('click', function() {
            $('.wc-tp-modal-overlay').remove();
        });
        
        $('.wc-tp-save-shipping').on('click', function() {
            OrderEditor.saveShipping();
        });
    },

    saveShipping: function() {
        const methodTitle = $('.wc-tp-shipping-title').val();
        const methodId = $('.wc-tp-shipping-id').val();
        const cost = $('.wc-tp-shipping-cost').val();
        
        const $saveBtn = $('.wc-tp-save-shipping');
        $saveBtn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: wcTpOrderEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_tp_add_shipping',
                nonce: wcTpOrderEditor.nonce,
                order_id: wcTpOrderEditor.order_id,
                method_title: methodTitle,
                method_id: methodId,
                cost: cost
            },
            success: function(response) {
                if (response.success) {
                    $('.wc-tp-modal-overlay').remove();
                    OrderEditor.showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    OrderEditor.showNotice('error', response.data.message);
                    $saveBtn.prop('disabled', false).text('Add Shipping');
                }
            },
            error: function() {
                OrderEditor.showNotice('error', 'An error occurred while adding shipping.');
                $saveBtn.prop('disabled', false).text('Add Shipping');
            }
        });
    },

    handleAddFee: function(e) {
        e.preventDefault();
        
        const modalHtml = `
            <div class="wc-tp-modal-overlay">
                <div class="wc-tp-modal">
                    <div class="wc-tp-modal-header">
                        <h2>Add Fee</h2>
                        <button class="wc-tp-modal-close">&times;</button>
                    </div>
                    <div class="wc-tp-modal-body">
                        <div class="wc-tp-form-group">
                            <label>Fee Name:</label>
                            <input type="text" class="wc-tp-fee-name" placeholder="Processing Fee">
                        </div>
                        <div class="wc-tp-form-group">
                            <label>Fee Amount:</label>
                            <input type="number" class="wc-tp-fee-amount" value="0" min="0" step="0.01">
                        </div>
                        <div class="wc-tp-form-group">
                            <label>
                                <input type="checkbox" class="wc-tp-fee-taxable">
                                Taxable
                            </label>
                        </div>
                    </div>
                    <div class="wc-tp-modal-footer">
                        <button class="button button-secondary wc-tp-modal-close">Cancel</button>
                        <button class="button button-primary wc-tp-save-fee">Add Fee</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        $('.wc-tp-modal-close').on('click', function() {
            $('.wc-tp-modal-overlay').remove();
        });
        
        $('.wc-tp-save-fee').on('click', function() {
            OrderEditor.saveFee();
        });
    },

    saveFee: function() {
        const feeName = $('.wc-tp-fee-name').val();
        const feeAmount = $('.wc-tp-fee-amount').val();
        const taxable = $('.wc-tp-fee-taxable').is(':checked');
        
        if (!feeName) {
            OrderEditor.showNotice('error', 'Fee name is required.');
            return;
        }
        
        const $saveBtn = $('.wc-tp-save-fee');
        $saveBtn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: wcTpOrderEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_tp_add_fee',
                nonce: wcTpOrderEditor.nonce,
                order_id: wcTpOrderEditor.order_id,
                fee_name: feeName,
                fee_amount: feeAmount,
                taxable: taxable
            },
            success: function(response) {
                if (response.success) {
                    $('.wc-tp-modal-overlay').remove();
                    OrderEditor.showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    OrderEditor.showNotice('error', response.data.message);
                    $saveBtn.prop('disabled', false).text('Add Fee');
                }
            },
            error: function() {
                OrderEditor.showNotice('error', 'An error occurred while adding fee.');
                $saveBtn.prop('disabled', false).text('Add Fee');
            }
        });
    },

    handleAddCoupon: function(e) {
        e.preventDefault();
        
        const modalHtml = `
            <div class="wc-tp-modal-overlay">
                <div class="wc-tp-modal">
                    <div class="wc-tp-modal-header">
                        <h2>Add Coupon</h2>
                        <button class="wc-tp-modal-close">&times;</button>
                    </div>
                    <div class="wc-tp-modal-body">
                        <div class="wc-tp-form-group">
                            <label>Coupon Code:</label>
                            <input type="text" class="wc-tp-coupon-code" placeholder="DISCOUNT10">
                            <p class="description">Enter an existing coupon code</p>
                        </div>
                    </div>
                    <div class="wc-tp-modal-footer">
                        <button class="button button-secondary wc-tp-modal-close">Cancel</button>
                        <button class="button button-primary wc-tp-apply-coupon">Apply Coupon</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        $('.wc-tp-modal-close').on('click', function() {
            $('.wc-tp-modal-overlay').remove();
        });
        
        $('.wc-tp-apply-coupon').on('click', function() {
            OrderEditor.applyCoupon();
        });
    },

    applyCoupon: function() {
        const couponCode = $('.wc-tp-coupon-code').val();
        
        if (!couponCode) {
            OrderEditor.showNotice('error', 'Coupon code is required.');
            return;
        }
        
        const $applyBtn = $('.wc-tp-apply-coupon');
        $applyBtn.prop('disabled', true).text('Applying...');
        
        $.ajax({
            url: wcTpOrderEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_tp_add_coupon',
                nonce: wcTpOrderEditor.nonce,
                order_id: wcTpOrderEditor.order_id,
                coupon_code: couponCode
            },
            success: function(response) {
                if (response.success) {
                    $('.wc-tp-modal-overlay').remove();
                    OrderEditor.showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    OrderEditor.showNotice('error', response.data.message);
                    $applyBtn.prop('disabled', false).text('Apply Coupon');
                }
            },
            error: function() {
                OrderEditor.showNotice('error', 'An error occurred while applying coupon.');
                $applyBtn.prop('disabled', false).text('Apply Coupon');
            }
        });
    },

    handleEditAddresses: function(e) {
        e.preventDefault();
        
        // Get current order data first
        $.ajax({
            url: wcTpOrderEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_tp_get_order_edit_data',
                nonce: wcTpOrderEditor.nonce,
                order_id: wcTpOrderEditor.order_id
            },
            success: function(response) {
                if (response.success) {
                    OrderEditor.showAddressEditor();
                } else {
                    OrderEditor.showNotice('error', response.data.message);
                }
            }
        });
    },

    showAddressEditor: function() {
        const modalHtml = `
            <div class="wc-tp-modal-overlay">
                <div class="wc-tp-modal wc-tp-modal-large">
                    <div class="wc-tp-modal-header">
                        <h2>Edit Addresses</h2>
                        <button class="wc-tp-modal-close">&times;</button>
                    </div>
                    <div class="wc-tp-modal-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <h3>Billing Address</h3>
                                <div class="wc-tp-form-group">
                                    <label>First Name:</label>
                                    <input type="text" class="wc-tp-billing-first-name">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Last Name:</label>
                                    <input type="text" class="wc-tp-billing-last-name">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Company:</label>
                                    <input type="text" class="wc-tp-billing-company">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Address 1:</label>
                                    <input type="text" class="wc-tp-billing-address-1">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Address 2:</label>
                                    <input type="text" class="wc-tp-billing-address-2">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>City:</label>
                                    <input type="text" class="wc-tp-billing-city">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>State:</label>
                                    <input type="text" class="wc-tp-billing-state">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Postcode:</label>
                                    <input type="text" class="wc-tp-billing-postcode">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Country:</label>
                                    <input type="text" class="wc-tp-billing-country">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Email:</label>
                                    <input type="email" class="wc-tp-billing-email">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Phone:</label>
                                    <input type="text" class="wc-tp-billing-phone">
                                </div>
                                <button class="button button-primary wc-tp-save-billing">Save Billing Address</button>
                            </div>
                            <div>
                                <h3>Shipping Address</h3>
                                <div class="wc-tp-form-group">
                                    <label>First Name:</label>
                                    <input type="text" class="wc-tp-shipping-first-name">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Last Name:</label>
                                    <input type="text" class="wc-tp-shipping-last-name">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Company:</label>
                                    <input type="text" class="wc-tp-shipping-company">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Address 1:</label>
                                    <input type="text" class="wc-tp-shipping-address-1">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Address 2:</label>
                                    <input type="text" class="wc-tp-shipping-address-2">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>City:</label>
                                    <input type="text" class="wc-tp-shipping-city">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>State:</label>
                                    <input type="text" class="wc-tp-shipping-state">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Postcode:</label>
                                    <input type="text" class="wc-tp-shipping-postcode">
                                </div>
                                <div class="wc-tp-form-group">
                                    <label>Country:</label>
                                    <input type="text" class="wc-tp-shipping-country">
                                </div>
                                <button class="button button-primary wc-tp-save-shipping-address">Save Shipping Address</button>
                            </div>
                        </div>
                    </div>
                    <div class="wc-tp-modal-footer">
                        <button class="button button-secondary wc-tp-modal-close">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        $('.wc-tp-modal-close').on('click', function() {
            $('.wc-tp-modal-overlay').remove();
        });
        
        $('.wc-tp-save-billing').on('click', function() {
            OrderEditor.saveAddress('billing');
        });
        
        $('.wc-tp-save-shipping-address').on('click', function() {
            OrderEditor.saveAddress('shipping');
        });
    },

    saveAddress: function(addressType) {
        const prefix = 'wc-tp-' + addressType + '-';
        const addressData = {
            first_name: $('.' + prefix + 'first-name').val(),
            last_name: $('.' + prefix + 'last-name').val(),
            company: $('.' + prefix + 'company').val(),
            address_1: $('.' + prefix + 'address-1').val(),
            address_2: $('.' + prefix + 'address-2').val(),
            city: $('.' + prefix + 'city').val(),
            state: $('.' + prefix + 'state').val(),
            postcode: $('.' + prefix + 'postcode').val(),
            country: $('.' + prefix + 'country').val()
        };
        
        if (addressType === 'billing') {
            addressData.email = $('.' + prefix + 'email').val();
            addressData.phone = $('.' + prefix + 'phone').val();
        }
        
        const $saveBtn = addressType === 'billing' ? $('.wc-tp-save-billing') : $('.wc-tp-save-shipping-address');
        $saveBtn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: wcTpOrderEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_tp_update_addresses',
                nonce: wcTpOrderEditor.nonce,
                order_id: wcTpOrderEditor.order_id,
                address_type: addressType,
                address_data: addressData
            },
            success: function(response) {
                if (response.success) {
                    OrderEditor.showNotice('success', response.data.message);
                    $saveBtn.prop('disabled', false).text('Save ' + (addressType === 'billing' ? 'Billing' : 'Shipping') + ' Address');
                } else {
                    OrderEditor.showNotice('error', response.data.message);
                    $saveBtn.prop('disabled', false).text('Save ' + (addressType === 'billing' ? 'Billing' : 'Shipping') + ' Address');
                }
            },
            error: function() {
                OrderEditor.showNotice('error', 'An error occurred while saving address.');
                $saveBtn.prop('disabled', false).text('Save ' + (addressType === 'billing' ? 'Billing' : 'Shipping') + ' Address');
            }
        });

    // Handle custom field edit icon clicks
    handleEditCustomField: function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $icon = $(this);
        const label = $icon.data('label');
        const value = $icon.data('value');
        const metaKey = $icon.data('key');
        const orderId = $icon.data('order-id');
        
        // Detect field type from value
        let fieldType = 'text';
        let fieldHtml = '';
        
        if (value === '1' || value === '0' || value.toLowerCase() === 'yes' || value.toLowerCase() === 'no') {
            fieldType = 'checkbox';
            const checked = (value === '1' || value.toLowerCase() === 'yes') ? 'checked' : '';
            fieldHtml = '<label style="display: flex; align-items: center; gap: 8px;"><input type="checkbox" class="wc-tp-field-value" ' + checked + ' style="margin: 0;"> <span>Yes</span></label>';
        } else if (value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            fieldType = 'email';
            fieldHtml = '<input type="email" class="wc-tp-field-value" value="' + value + '" style="width: 100%;">';
        } else if (value.match(/^https?:\/\//)) {
            fieldType = 'url';
            fieldHtml = '<input type="url" class="wc-tp-field-value" value="' + value + '" style="width: 100%;">';
        } else if (value.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
            fieldType = 'date';
            fieldHtml = '<input type="text" class="wc-tp-field-value" value="' + value + '" placeholder="DD/MM/YYYY" style="width: 100%;">';
        } else if (value.length > 100 || value.includes(';') || value.includes(',') || value.includes('\n')) {
            fieldType = 'textarea';
            fieldHtml = '<textarea class="wc-tp-field-value" rows="4" style="width: 100%;">' + value + '</textarea>';
        } else {
            fieldHtml = '<input type="text" class="wc-tp-field-value" value="' + value + '" style="width: 100%;">';
        }
        
        const modalHtml = '<div class="wc-tp-modal-overlay">' +
            '<div class="wc-tp-modal">' +
            '<div class="wc-tp-modal-header">' +
            '<h2>Edit: ' + label + '</h2>' +
            '<button class="wc-tp-modal-close">&times;</button>' +
            '</div>' +
            '<div class="wc-tp-modal-body">' +
            '<div class="wc-tp-form-group">' +
            '<label>' + label + ':</label>' +
            fieldHtml +
            '</div>' +
            '<input type="hidden" class="wc-tp-field-key" value="' + metaKey + '">' +
            '</div>' +
            '<div class="wc-tp-modal-footer">' +
            '<button class="button button-secondary wc-tp-modal-close">Cancel</button>' +
            '<button class="button button-primary wc-tp-save-custom-field">Save Changes</button>' +
            '</div>' +
            '</div>' +
            '</div>';
        
        $('body').append(modalHtml);
        
        $('.wc-tp-modal-close').on('click', function() {
            $('.wc-tp-modal-overlay').remove();
        });
        
        $('.wc-tp-save-custom-field').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).text('Saving...');
            
            const newValue = $('.wc-tp-field-value').is(':checkbox') ? 
                ($('.wc-tp-field-value').is(':checked') ? '1' : '0') : 
                $('.wc-tp-field-value').val();
            const key = $('.wc-tp-field-key').val();
            
            $.ajax({
                url: wcTpOrderEditor.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_tp_update_order_meta',
                    nonce: wcTpOrderEditor.nonce,
                    order_id: orderId,
                    meta_key: key,
                    meta_value: newValue,
                    action_type: 'update'
                },
                success: function(response) {
                    if (response.success) {
                        $('.wc-tp-modal-overlay').remove();
                        OrderEditor.showNotice('success', 'Field updated successfully');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        OrderEditor.showNotice('error', response.data.message || 'Error saving field');
                        $btn.prop('disabled', false).text('Save Changes');
                    }
                },
                error: function() {
                    OrderEditor.showNotice('error', 'Error saving field');
                    $btn.prop('disabled', false).text('Save Changes');
                }
            });
        });
        
        return false;
    }
};

// Bind custom field edit icon clicks using event delegation
$(document).on('click', '.wc-tp-edit-custom-field', function(e) {
    OrderEditor.handleEditCustomField.call(this, e);
});
