/**
 * Frontend Editor
 * Inline editing for cart prices and shipping costs
 * Works with any theme including AJAX-based themes
 */

(function($) {
	'use strict';

	// Cart Price Editor
	class CartPriceEditor {
		constructor() {
			this.init();
		}

		init() {
			// Use event delegation on body for maximum compatibility
			this.bindEvents();
			
			// Re-initialize after WooCommerce updates
			$(document.body).on('updated_cart_totals updated_checkout', () => {
				if (wcTpEditor.debug) {
					console.log('WooCommerce updated - re-checking elements');
					this.checkElements();
				}
			});
			
			if (wcTpEditor.debug) {
				console.log('CartPriceEditor initialized');
				this.checkElements();
			}
		}

		checkElements() {
			const wrappers = $('.wc-tp-cart-price-wrapper');
			const buttons = $('.wc-tp-cart-price-edit');
			console.log('Cart price wrappers:', wrappers.length);
			console.log('Cart price edit buttons:', buttons.length);
			
			if (wrappers.length > 0) {
				console.log('Sample wrapper HTML:', wrappers.first().html());
			} else {
				// If no wrappers found, let's see what price elements exist
				console.log('No wrappers found. Checking for price elements...');
				const priceElements = $('.woocommerce-Price-amount, .amount, .product-price, [class*="price"]');
				console.log('Found', priceElements.length, 'potential price elements');
				if (priceElements.length > 0) {
					console.log('Sample price element HTML:', priceElements.first().parent().html());
				}
			}
		}

		bindEvents() {
			// Use body as the root for event delegation to catch dynamically added elements
			$('body').on('click', '.wc-tp-cart-price-edit', this.handleEditClick.bind(this));
			$('body').on('click', '.wc-tp-price-actions .wc-tp-save-btn', this.handleSaveClick.bind(this));
			$('body').on('click', '.wc-tp-price-actions .wc-tp-cancel-btn', this.handleCancelClick.bind(this));
			$('body').on('keydown', '.wc-tp-price-input', this.handleKeyDown.bind(this));
			$('body').on('click', this.handleClickOutside.bind(this));
			
			if (wcTpEditor.debug) {
				console.log('CartPriceEditor events bound to body');
			}
		}

		handleEditClick(e) {
			e.preventDefault();
			e.stopPropagation();

			if (wcTpEditor.debug) {
				console.log('Cart price edit button clicked');
			}

			const $btn = $(e.currentTarget);
			const $wrapper = $btn.closest('.wc-tp-cart-price-wrapper');
			
			if (wcTpEditor.debug) {
				console.log('Wrapper found:', $wrapper.length);
			}
			
			// Close any other open editors
			$('.wc-tp-cart-price-wrapper.editing').each((i, el) => {
				this.exitEditMode($(el));
			});

			this.enterEditMode($wrapper);
		}

		enterEditMode($wrapper) {
			const currentPrice = $wrapper.data('current-price');
			
			if (wcTpEditor.debug) {
				console.log('Entering edit mode, current price:', currentPrice);
			}
			
			$wrapper.addClass('editing');
			
			// Remove existing input if any
			$wrapper.find('.wc-tp-price-input, .wc-tp-price-actions').remove();
			
			const $input = $('<input>', {
				type: 'text',
				class: 'wc-tp-price-input',
				value: formatPrice(currentPrice),
				'data-original-value': currentPrice
			});
			
			const $actions = $('<span>', { class: 'wc-tp-price-actions' });
			
			const $saveBtn = $('<button>', {
				type: 'button',
				class: 'wc-tp-action-btn wc-tp-save-btn',
				title: wcTpEditor.i18n.save,
				html: '<i class="ph ph-check"></i>'
			});
			
			const $cancelBtn = $('<button>', {
				type: 'button',
				class: 'wc-tp-action-btn wc-tp-cancel-btn',
				title: wcTpEditor.i18n.cancel,
				html: '<i class="ph ph-x"></i>'
			});
			
			$actions.append($saveBtn, $cancelBtn);
			$wrapper.append($input, $actions);
			
			setTimeout(() => {
				$input.focus().select();
			}, 50);
		}

		handleSaveClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $wrapper = $btn.closest('.wc-tp-cart-price-wrapper');
			const $input = $wrapper.find('.wc-tp-price-input');
			
			const cartKey = $wrapper.data('cart-key');
			const newPrice = $input.val().trim();
			const originalPrice = $input.data('original-value');
			
			if (wcTpEditor.debug) {
				console.log('Save clicked - Cart key:', cartKey, 'New price:', newPrice);
			}
			
			if (!isValidPrice(newPrice)) {
				showToast(wcTpEditor.i18n.invalid_price, 'error');
				$wrapper.addClass('error');
				setTimeout(() => $wrapper.removeClass('error'), 1000);
				return;
			}
			
			const parsedPrice = parsePrice(newPrice);
			
			if (parseFloat(parsedPrice) === parseFloat(originalPrice)) {
				this.exitEditMode($wrapper);
				return;
			}
			
			this.updatePrice($wrapper, cartKey, parsedPrice);
		}

		handleCancelClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $wrapper = $btn.closest('.wc-tp-cart-price-wrapper');
			
			this.exitEditMode($wrapper);
		}

		handleKeyDown(e) {
			const $input = $(e.currentTarget);
			const $wrapper = $input.closest('.wc-tp-cart-price-wrapper');
			
			if (e.key === 'Enter') {
				e.preventDefault();
				$wrapper.find('.wc-tp-save-btn').click();
			} else if (e.key === 'Escape') {
				e.preventDefault();
				this.exitEditMode($wrapper);
			}
		}

		handleClickOutside(e) {
			const $target = $(e.target);
			
			if (!$target.closest('.wc-tp-cart-price-wrapper.editing').length) {
				$('.wc-tp-cart-price-wrapper.editing').each((i, el) => {
					this.exitEditMode($(el));
				});
			}
		}

		exitEditMode($wrapper) {
			$wrapper.removeClass('editing error loading');
			$wrapper.find('.wc-tp-price-input, .wc-tp-price-actions').remove();
		}

		updatePrice($wrapper, cartKey, newPrice) {
			$wrapper.addClass('loading');
			$wrapper.find('.wc-tp-action-btn').prop('disabled', true);
			
			$.ajax({
				url: wcTpEditor.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_update_cart_item_price',
					nonce: wcTpEditor.nonce,
					cart_key: cartKey,
					new_price: newPrice
				},
				success: (response) => {
					if (response.success) {
						$wrapper.find('.wc-tp-price-display').html(response.data.new_price_html);
						$wrapper.data('current-price', response.data.new_price);
						
						this.exitEditMode($wrapper);
						
						$wrapper.addClass('success');
						setTimeout(() => $wrapper.removeClass('success'), 600);
						
						showToast(response.data.message, 'success');
						
						// Trigger WooCommerce cart update
						$(document.body).trigger('wc_fragment_refresh');
						$(document.body).trigger('update_checkout');
					} else {
						this.handleError($wrapper, response.data.message);
					}
				},
				error: (xhr, status, error) => {
					if (wcTpEditor.debug) {
						console.error('AJAX error:', status, error);
					}
					this.handleError($wrapper, wcTpEditor.i18n.error);
				},
				complete: () => {
					$wrapper.removeClass('loading');
					$wrapper.find('.wc-tp-action-btn').prop('disabled', false);
				}
			});
		}

		handleError($wrapper, message) {
			$wrapper.addClass('error');
			showToast(message, 'error');
			
			setTimeout(() => {
				$wrapper.removeClass('error');
			}, 2000);
		}
	}

	// Shipping Cost Editor
	class ShippingEditor {
		constructor() {
			this.init();
		}

		init() {
			this.bindEvents();
			
			// Re-initialize after WooCommerce updates
			$(document.body).on('updated_cart_totals updated_checkout updated_shipping_method', () => {
				if (wcTpEditor.debug) {
					console.log('WooCommerce shipping updated - re-checking elements');
					this.checkElements();
				}
			});
			
			if (wcTpEditor.debug) {
				console.log('ShippingEditor initialized');
				this.checkElements();
			}
		}

		checkElements() {
			const wrappers = $('.wc-tp-shipping-wrapper');
			const buttons = $('.wc-tp-shipping-edit');
			console.log('Shipping wrappers:', wrappers.length);
			console.log('Shipping edit buttons:', buttons.length);
			
			if (wrappers.length > 0) {
				console.log('Sample shipping wrapper HTML:', wrappers.first().html());
			}
		}

		bindEvents() {
			$('body').on('click', '.wc-tp-shipping-edit', this.handleEditClick.bind(this));
			$('body').on('click', '.wc-tp-shipping-actions .wc-tp-save-btn', this.handleSaveClick.bind(this));
			$('body').on('click', '.wc-tp-shipping-actions .wc-tp-cancel-btn', this.handleCancelClick.bind(this));
			$('body').on('keydown', '.wc-tp-shipping-input', this.handleKeyDown.bind(this));
			
			if (wcTpEditor.debug) {
				console.log('ShippingEditor events bound to body');
			}
		}

		handleEditClick(e) {
			e.preventDefault();
			e.stopPropagation();

			if (wcTpEditor.debug) {
				console.log('Shipping edit button clicked');
			}

			const $btn = $(e.currentTarget);
			const $wrapper = $btn.closest('.wc-tp-shipping-wrapper');
			
			if (wcTpEditor.debug) {
				console.log('Shipping wrapper found:', $wrapper.length);
			}
			
			// Close any other open editors
			$('.wc-tp-shipping-wrapper.editing').each((i, el) => {
				this.exitEditMode($(el));
			});

			this.enterEditMode($wrapper);
		}

		enterEditMode($wrapper) {
			const currentCost = $wrapper.data('current-cost');
			
			if (wcTpEditor.debug) {
				console.log('Entering shipping edit mode, current cost:', currentCost);
			}
			
			$wrapper.addClass('editing');
			
			// Remove existing input if any
			$wrapper.find('.wc-tp-shipping-input, .wc-tp-shipping-actions').remove();
			
			const $input = $('<input>', {
				type: 'text',
				class: 'wc-tp-shipping-input',
				value: formatPrice(currentCost),
				'data-original-value': currentCost
			});
			
			const $actions = $('<span>', { class: 'wc-tp-shipping-actions' });
			
			const $saveBtn = $('<button>', {
				type: 'button',
				class: 'wc-tp-action-btn wc-tp-save-btn',
				title: wcTpEditor.i18n.save,
				html: '<i class="ph ph-check"></i>'
			});
			
			const $cancelBtn = $('<button>', {
				type: 'button',
				class: 'wc-tp-action-btn wc-tp-cancel-btn',
				title: wcTpEditor.i18n.cancel,
				html: '<i class="ph ph-x"></i>'
			});
			
			$actions.append($saveBtn, $cancelBtn);
			$wrapper.append($input, $actions);
			
			setTimeout(() => {
				$input.focus().select();
			}, 50);
		}

		handleSaveClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $wrapper = $btn.closest('.wc-tp-shipping-wrapper');
			const $input = $wrapper.find('.wc-tp-shipping-input');
			
			const methodId = $wrapper.data('method-id');
			const newCost = $input.val().trim();
			const originalCost = $input.data('original-value');
			
			if (wcTpEditor.debug) {
				console.log('Shipping save clicked - Method:', methodId, 'New cost:', newCost);
			}
			
			if (!isValidPrice(newCost)) {
				showToast(wcTpEditor.i18n.invalid_price, 'error');
				$wrapper.addClass('error');
				setTimeout(() => $wrapper.removeClass('error'), 1000);
				return;
			}
			
			const parsedCost = parsePrice(newCost);
			
			if (parseFloat(parsedCost) === parseFloat(originalCost)) {
				this.exitEditMode($wrapper);
				return;
			}
			
			this.updateCost($wrapper, methodId, parsedCost);
		}

		handleCancelClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $wrapper = $btn.closest('.wc-tp-shipping-wrapper');
			
			this.exitEditMode($wrapper);
		}

		handleKeyDown(e) {
			const $input = $(e.currentTarget);
			const $wrapper = $input.closest('.wc-tp-shipping-wrapper');
			
			if (e.key === 'Enter') {
				e.preventDefault();
				$wrapper.find('.wc-tp-save-btn').click();
			} else if (e.key === 'Escape') {
				e.preventDefault();
				this.exitEditMode($wrapper);
			}
		}

		exitEditMode($wrapper) {
			$wrapper.removeClass('editing error loading');
			$wrapper.find('.wc-tp-shipping-input, .wc-tp-shipping-actions').remove();
		}

		updateCost($wrapper, methodId, newCost) {
			$wrapper.addClass('loading');
			$wrapper.find('.wc-tp-action-btn').prop('disabled', true);
			
			$.ajax({
				url: wcTpEditor.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_update_shipping_cost',
					nonce: wcTpEditor.nonce,
					method_id: methodId,
					new_cost: newCost
				},
				success: (response) => {
					if (response.success) {
						$wrapper.find('.wc-tp-shipping-display').html(response.data.new_cost_html);
						$wrapper.data('current-cost', response.data.new_cost);
						
						this.exitEditMode($wrapper);
						
						$wrapper.addClass('success');
						setTimeout(() => $wrapper.removeClass('success'), 600);
						
						showToast(response.data.message, 'success');
						
						// Trigger WooCommerce cart update (no reload needed)
						$(document.body).trigger('wc_fragment_refresh');
						$(document.body).trigger('update_checkout');
					} else {
						this.handleError($wrapper, response.data.message);
					}
				},
				error: (xhr, status, error) => {
					if (wcTpEditor.debug) {
						console.error('AJAX error:', status, error);
					}
					this.handleError($wrapper, wcTpEditor.i18n.error);
				},
				complete: () => {
					$wrapper.removeClass('loading');
					$wrapper.find('.wc-tp-action-btn').prop('disabled', false);
				}
			});
		}

		handleError($wrapper, message) {
			$wrapper.addClass('error');
			showToast(message, 'error');
			
			setTimeout(() => {
				$wrapper.removeClass('error');
			}, 2000);
		}
	}

	// Utility Functions
	function isValidPrice(price) {
		if (!price || price === '') return false;
		price = price.replace(/[^\d.,\-]/g, '');
		price = price.replace(',', '.');
		const num = parseFloat(price);
		return !isNaN(num) && num >= 0;
	}

	function parsePrice(price) {
		price = price.replace(/[^\d.,\-]/g, '');
		price = price.replace(',', '.');
		return parseFloat(price);
	}

	function formatPrice(price) {
		if (!price || price === '') return '';
		const formatted = parseFloat(price).toFixed(wcTpEditor.decimals);
		return formatted.replace('.', wcTpEditor.decimal_separator);
	}

	function showToast(message, type = 'success') {
		$('.wc-tp-toast').remove();
		
		const icon = type === 'success' 
			? '<i class="ph ph-check-circle"></i>'
			: '<i class="ph ph-warning-circle"></i>';
		
		const $toast = $('<div>', {
			class: 'wc-tp-toast ' + type,
			html: `
				<span class="wc-tp-toast-icon">${icon}</span>
				<span class="wc-tp-toast-message">${message}</span>
			`
		});
		
		$('body').append($toast);
		
		setTimeout(() => {
			$toast.addClass('hiding');
			setTimeout(() => $toast.remove(), 300);
		}, 3000);
	}

	// Initialize - works with any theme including AJAX themes
	function initialize() {
		if (typeof wcTpEditor === 'undefined') {
			console.error('wcTpEditor object not found - scripts may not be loaded correctly');
			return;
		}
		
		if (wcTpEditor.debug) {
			console.log('WC Team Payroll Frontend Editor loaded');
			console.log('jQuery version:', $.fn.jquery);
			console.log('Current page:', window.location.href);
		}
		
		new CartPriceEditor();
		new ShippingEditor();
		
		if (wcTpEditor.debug) {
			console.log('Editors initialized and ready');
		}
	}

	// Initialize on DOM ready
	$(document).ready(initialize);
	
	// Re-initialize on AJAX page loads (for AJAX themes)
	$(document).ajaxComplete(function(event, xhr, settings) {
		// Check if this is a WooCommerce AJAX call
		if (settings.url && settings.url.indexOf('wc-ajax') !== -1) {
			if (wcTpEditor.debug) {
				console.log('WooCommerce AJAX detected, re-initializing...');
			}
			// Small delay to let DOM update
			setTimeout(initialize, 100);
		}
	});

	// ============================================================================
	// CHECKOUT ENHANCEMENT: Trigger shipping recalculation on state/country change
	// ============================================================================
	// WooCommerce by default only triggers update_checkout on address_1, city, postcode
	// This enhancement ensures shipping updates when state or country changes
	// Works with all themes and improves user experience
	// ============================================================================
	$(document).ready(function() {
		// Listen for state and country changes on both billing and shipping fields
		$('body').on('change', 'select#billing_state, select#billing_country, select#shipping_state, select#shipping_country', function() {
			if (wcTpEditor.debug) {
				console.log('State/Country changed, triggering checkout update');
			}
			// Trigger WooCommerce checkout update to recalculate shipping
			$('body').trigger('update_checkout');
		});
		
		if (wcTpEditor.debug) {
			console.log('Checkout enhancement: State/Country change listener initialized');
		}
	});

})(jQuery);
