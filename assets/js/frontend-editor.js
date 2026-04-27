/**
 * Frontend Editor
 * Inline editing for prices and shipping fees
 */

(function($) {
	'use strict';

	// Frontend Editor Class
	class FrontendEditor {
		constructor() {
			this.init();
		}

		init() {
			this.priceEditor = new PriceEditor();
			this.shippingEditor = new ShippingEditor();
		}
	}

	// Price Editor Class
	class PriceEditor {
		constructor() {
			this.bindEvents();
		}

		bindEvents() {
			$(document).on('click', '.wc-tp-price-edit-btn', this.handleEditClick.bind(this));
			$(document).on('click', '.wc-tp-price-save', this.handleSaveClick.bind(this));
			$(document).on('click', '.wc-tp-price-cancel', this.handleCancelClick.bind(this));
			$(document).on('keydown', '.wc-tp-price-input', this.handleKeyDown.bind(this));
			$(document).on('click', this.handleClickOutside.bind(this));
		}

		handleEditClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $wrapper = $btn.closest('.wc-tp-price-wrapper');
			
			if ($('.wc-tp-price-wrapper.editing').length > 0) {
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
					class: 'wc-tp-price-action-btn wc-tp-price-save',
					title: wcTpEditor.i18n.save,
					html: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>'
				});
				
				const $cancelBtn = $('<button>', {
					type: 'button',
					class: 'wc-tp-price-action-btn wc-tp-price-cancel',
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
			const $wrapper = $btn.closest('.wc-tp-price-wrapper');
			const $input = $wrapper.find('.wc-tp-price-input');
			
			const productId = $wrapper.data('product-id');
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
			
			this.updatePrice($wrapper, productId, parsedPrice);
		}

		handleCancelClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $wrapper = $btn.closest('.wc-tp-price-wrapper');
			
			this.exitEditMode($wrapper);
		}

		handleKeyDown(e) {
			const $input = $(e.currentTarget);
			const $wrapper = $input.closest('.wc-tp-price-wrapper');
			
			if (e.key === 'Enter') {
				e.preventDefault();
				$wrapper.find('.wc-tp-price-save').click();
			} else if (e.key === 'Escape') {
				e.preventDefault();
				this.exitEditMode($wrapper);
			}
		}

		handleClickOutside(e) {
			const $target = $(e.target);
			
			if (!$target.closest('.wc-tp-price-wrapper.editing').length) {
				$('.wc-tp-price-wrapper.editing').each((i, el) => {
					this.exitEditMode($(el));
				});
			}
		}

		exitEditMode($wrapper) {
			$wrapper.removeClass('editing error loading');
		}

		updatePrice($wrapper, productId, newPrice) {
			$wrapper.addClass('loading');
			$wrapper.find('.wc-tp-price-action-btn').prop('disabled', true);
			
			$.ajax({
				url: wcTpEditor.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_update_product_price',
					nonce: wcTpEditor.nonce,
					product_id: productId,
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
						
						$(document).trigger('wc_tp_price_updated', [productId, response.data.new_price]);
					} else {
						this.handleError($wrapper, response.data.message);
					}
				},
				error: () => {
					this.handleError($wrapper, wcTpEditor.i18n.error);
				},
				complete: () => {
					$wrapper.removeClass('loading');
					$wrapper.find('.wc-tp-price-action-btn').prop('disabled', false);
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

	// Shipping Editor Class
	class ShippingEditor {
		constructor() {
			this.bindEvents();
		}

		bindEvents() {
			$(document).on('click', '.wc-tp-add-shipping-btn', this.handleAddClick.bind(this));
			$(document).on('click', '.wc-tp-fee-edit-btn', this.handleEditClick.bind(this));
			$(document).on('click', '.wc-tp-fee-remove-btn', this.handleRemoveClick.bind(this));
			$(document).on('click', '.wc-tp-fee-save', this.handleSaveClick.bind(this));
			$(document).on('click', '.wc-tp-fee-cancel', this.handleCancelClick.bind(this));
		}

		handleAddClick(e) {
			e.preventDefault();
			this.showAddModal();
		}

		showAddModal() {
			const $modal = $('<div>', { class: 'wc-tp-add-fee-modal active' });
			const $content = $('<div>', { class: 'wc-tp-modal-content' });
			
			$content.html(`
				<h3>${wcTpEditor.i18n.add_fee}</h3>
				<div class="wc-tp-modal-field">
					<label>${wcTpEditor.i18n.fee_name}</label>
					<input type="text" class="wc-tp-modal-fee-name" placeholder="${wcTpEditor.i18n.fee_name}">
				</div>
				<div class="wc-tp-modal-field">
					<label>${wcTpEditor.i18n.fee_amount}</label>
					<input type="text" class="wc-tp-modal-fee-amount" placeholder="0.00">
				</div>
				<div class="wc-tp-modal-actions">
					<button type="button" class="wc-tp-modal-btn wc-tp-modal-btn-secondary wc-tp-modal-cancel">${wcTpEditor.i18n.cancel}</button>
					<button type="button" class="wc-tp-modal-btn wc-tp-modal-btn-primary wc-tp-modal-save">${wcTpEditor.i18n.save}</button>
				</div>
			`);
			
			$modal.append($content);
			$('body').append($modal);
			
			$modal.find('.wc-tp-modal-fee-name').focus();
			
			$modal.on('click', '.wc-tp-modal-cancel', () => {
				$modal.remove();
			});
			
			$modal.on('click', (e) => {
				if ($(e.target).hasClass('wc-tp-add-fee-modal')) {
					$modal.remove();
				}
			});
			
			$modal.on('click', '.wc-tp-modal-save', () => {
				const feeName = $modal.find('.wc-tp-modal-fee-name').val().trim();
				const feeAmount = $modal.find('.wc-tp-modal-fee-amount').val().trim();
				
				if (!feeName) {
					showToast(wcTpEditor.i18n.invalid_fee_name, 'error');
					return;
				}
				
				if (!isValidPrice(feeAmount)) {
					showToast(wcTpEditor.i18n.invalid_price, 'error');
					return;
				}
				
				this.addFee(feeName, parsePrice(feeAmount), $modal);
			});
		}

		addFee(feeName, feeAmount, $modal) {
			$.ajax({
				url: wcTpEditor.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_add_shipping_fee',
					nonce: wcTpEditor.nonce,
					fee_name: feeName,
					fee_amount: feeAmount
				},
				success: (response) => {
					if (response.success) {
						showToast(response.data.message, 'success');
						$modal.remove();
						location.reload();
					} else {
						showToast(response.data.message, 'error');
					}
				},
				error: () => {
					showToast(wcTpEditor.i18n.error, 'error');
				}
			});
		}

		handleEditClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $item = $btn.closest('.wc-tp-shipping-fee-item');
			
			if ($('.wc-tp-shipping-fee-item.editing').length > 0) {
				showToast(wcTpEditor.i18n.error, 'error');
				return;
			}

			this.enterEditMode($item);
		}

		enterEditMode($item) {
			const currentAmount = $item.find('.wc-tp-fee-amount').text().replace(/[^\d.,\-]/g, '');
			
			$item.addClass('editing');
			
			if ($item.find('.wc-tp-fee-edit-form').length === 0) {
				const $form = $('<div>', { class: 'wc-tp-fee-edit-form' });
				
				const $input = $('<input>', {
					type: 'text',
					class: 'wc-tp-fee-input',
					value: currentAmount,
					'data-original-value': currentAmount
				});
				
				const $actions = $('<div>', { class: 'wc-tp-fee-edit-actions' });
				
				const $saveBtn = $('<button>', {
					type: 'button',
					class: 'wc-tp-price-action-btn wc-tp-fee-save',
					title: wcTpEditor.i18n.save,
					html: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>'
				});
				
				const $cancelBtn = $('<button>', {
					type: 'button',
					class: 'wc-tp-price-action-btn wc-tp-fee-cancel',
					title: wcTpEditor.i18n.cancel,
					html: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'
				});
				
				$actions.append($saveBtn, $cancelBtn);
				$form.append($input, $actions);
				$item.append($form);
			}
			
			setTimeout(() => {
				$item.find('.wc-tp-fee-input').focus().select();
			}, 50);
		}

		handleSaveClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $item = $btn.closest('.wc-tp-shipping-fee-item');
			const $input = $item.find('.wc-tp-fee-input');
			
			const feeKey = $item.data('fee-key');
			const newAmount = $input.val().trim();
			
			if (!isValidPrice(newAmount)) {
				showToast(wcTpEditor.i18n.invalid_price, 'error');
				return;
			}
			
			this.updateFee($item, feeKey, parsePrice(newAmount));
		}

		handleCancelClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $btn = $(e.currentTarget);
			const $item = $btn.closest('.wc-tp-shipping-fee-item');
			
			this.exitEditMode($item);
		}

		exitEditMode($item) {
			$item.removeClass('editing');
		}

		updateFee($item, feeKey, newAmount) {
			$.ajax({
				url: wcTpEditor.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_update_shipping_fee',
					nonce: wcTpEditor.nonce,
					fee_key: feeKey,
					new_amount: newAmount
				},
				success: (response) => {
					if (response.success) {
						showToast(response.data.message, 'success');
						location.reload();
					} else {
						showToast(response.data.message, 'error');
					}
				},
				error: () => {
					showToast(wcTpEditor.i18n.error, 'error');
				}
			});
		}

		handleRemoveClick(e) {
			e.preventDefault();
			e.stopPropagation();

			if (!confirm(wcTpEditor.i18n.confirm_remove)) {
				return;
			}

			const $btn = $(e.currentTarget);
			const $item = $btn.closest('.wc-tp-shipping-fee-item');
			const feeKey = $item.data('fee-key');
			
			this.removeFee($item, feeKey);
		}

		removeFee($item, feeKey) {
			$.ajax({
				url: wcTpEditor.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_remove_shipping_fee',
					nonce: wcTpEditor.nonce,
					fee_key: feeKey
				},
				success: (response) => {
					if (response.success) {
						showToast(response.data.message, 'success');
						location.reload();
					} else {
						showToast(response.data.message, 'error');
					}
				},
				error: () => {
					showToast(wcTpEditor.i18n.error, 'error');
				}
			});
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
		new FrontendEditor();
	});

})(jQuery);
