/**
 * Frontend Editor
 * Inline editing for cart prices and shipping costs
 */

(function($) {
	'use strict';

	// Cart Price Editor
	class CartPriceEditor {
		constructor() {
			this.bindEvents();
		}

		bindEvents() {
			$(document).on('click', '.wc-tp-cart-price-edit', this.handleEditClick.bind(this));
			$(document).on('click', '.wc-tp-save-btn', this.handleSaveClick.bind(this));
			$(document).on('click', '.wc-tp-cancel-btn', this.handleCancelClick.bind(this));
			$(document).on('keydown', '.wc-tp-price-input', this.handleKeyDown.bind(this));
			$(document).on('click', this.handleClickOutside.bind(this));
		}

		handleEditClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $wrapper = $btn.closest('.wc-tp-cart-price-wrapper');
			
			if ($('.wc-tp-cart-price-wrapper.editing').length > 0) {
				showToast(wcTpEditor.i18n.error, 'error');
				return;
			}

			this.enterEditMode($wrapper);
		}

		enterEditMode($wrapper) {
			const currentPrice = $wrapper.data('current-price');
			
			$wrapper.addClass('editing');
			
			if ($wrapper.find('.wc-tp-price-input').length === 0) {
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
					html: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>'
				});
				
				const $cancelBtn = $('<button>', {
					type: 'button',
					class: 'wc-tp-action-btn wc-tp-cancel-btn',
					title: wcTpEditor.i18n.cancel,
					html: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'
				});
				
				$actions.append($saveBtn, $cancelBtn);
				$wrapper.append($input, $actions);
			}
			
			setTimeout(() => {
				$wrapper.find('.wc-tp-price-input').focus().select();
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
						
						// Update cart totals
						$(document.body).trigger('update_checkout');
						$(document.body).trigger('updated_cart_totals');
					} else {
						this.handleError($wrapper, response.data.message);
					}
				},
				error: () => {
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
			this.bindEvents();
		}

		bindEvents() {
			$(document).on('click', '.wc-tp-shipping-edit', this.handleEditClick.bind(this));
			$(document).on('click', '.wc-tp-shipping-wrapper .wc-tp-save-btn', this.handleSaveClick.bind(this));
			$(document).on('click', '.wc-tp-shipping-wrapper .wc-tp-cancel-btn', this.handleCancelClick.bind(this));
			$(document).on('keydown', '.wc-tp-shipping-input', this.handleKeyDown.bind(this));
		}

		handleEditClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $wrapper = $btn.closest('.wc-tp-shipping-wrapper');
			
			if ($('.wc-tp-shipping-wrapper.editing').length > 0) {
				showToast(wcTpEditor.i18n.error, 'error');
				return;
			}

			this.enterEditMode($wrapper);
		}

		enterEditMode($wrapper) {
			const currentCost = $wrapper.data('current-cost');
			
			$wrapper.addClass('editing');
			
			if ($wrapper.find('.wc-tp-shipping-input').length === 0) {
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
					html: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>'
				});
				
				const $cancelBtn = $('<button>', {
					type: 'button',
					class: 'wc-tp-action-btn wc-tp-cancel-btn',
					title: wcTpEditor.i18n.cancel,
					html: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'
				});
				
				$actions.append($saveBtn, $cancelBtn);
				$wrapper.append($input, $actions);
			}
			
			setTimeout(() => {
				$wrapper.find('.wc-tp-shipping-input').focus().select();
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
						showToast(response.data.message, 'success');
						
						// Reload page to show updated shipping (session needs page reload)
						setTimeout(() => {
							window.location.reload();
						}, 500);
					} else {
						this.handleError($wrapper, response.data.message);
					}
				},
				error: () => {
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
			? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>'
			: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
		
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

	// Initialize
	$(document).ready(function() {
		new CartPriceEditor();
		new ShippingEditor();
	});

})(jQuery);
