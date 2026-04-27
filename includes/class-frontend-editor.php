<?php
/**
 * Frontend Editor
 * Allows selected user roles to edit products and cart inline on the frontend
 * - Product price editing
 * - Shipping fee editing
 * - Add/Remove shipping fees
 *
 * @package WooCommerce Team Payroll
 * @since 1.0.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Frontend_Editor {

	/**
	 * Initialize the class
	 */
	public static function init() {
		$instance = new self();
		$instance->setup_hooks();
	}

	/**
	 * Setup hooks
	 */
	private function setup_hooks() {
		// Check if user has permission
		if ( ! $this->user_can_edit() ) {
			return;
		}

		// Apply custom prices from session BEFORE displaying
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_custom_prices' ), 10, 1 );

		// Cart Item Price Editing - try multiple hooks with different priorities
		add_filter( 'woocommerce_cart_item_price', array( $this, 'add_cart_price_edit_button' ), 9999, 3 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'add_cart_price_edit_button' ), 999, 3 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'add_cart_price_edit_button' ), 99, 3 );
		
		// Checkout page uses different hooks for order review
		add_filter( 'woocommerce_order_item_price', array( $this, 'add_cart_price_edit_button' ), 9999, 3 );
		add_filter( 'woocommerce_checkout_cart_item_price', array( $this, 'add_cart_price_edit_button' ), 9999, 3 );
		
		// Also try the subtotal hook as fallback
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'add_cart_subtotal_edit_button' ), 999, 3 );

		// Shipping Fee Editing
		add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'add_shipping_edit_button' ), 999, 2 );
		
		// Apply custom shipping costs from session
		add_filter( 'woocommerce_package_rates', array( $this, 'apply_custom_shipping_costs' ), 999 );

		// Clear custom prices and shipping when cart is emptied
		add_action( 'woocommerce_cart_emptied', array( $this, 'clear_all_custom_values' ) );
		
		// Clear custom prices when cart item is removed
		add_action( 'woocommerce_cart_item_removed', array( $this, 'clear_custom_price_for_item' ), 10, 2 );
		
		// Clear custom shipping when address changes (shipping recalculation)
		add_action( 'woocommerce_calculated_shipping', array( $this, 'clear_custom_shipping_on_address_change' ) );

		// Enqueue scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// AJAX handlers
		add_action( 'wp_ajax_wc_tp_update_cart_item_price', array( $this, 'ajax_update_cart_item_price' ) );
		add_action( 'wp_ajax_wc_tp_update_shipping_cost', array( $this, 'ajax_update_shipping_cost' ) );
		
		// Debug: Log that hooks are set up
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WC TP Frontend Editor: Hooks setup complete for user ' . wp_get_current_user()->user_login );
		}
	}

	/**
	 * Apply custom prices from session to cart items
	 */
	public function apply_custom_prices( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( ! WC()->session ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$custom_price = WC()->session->get( 'wc_tp_custom_price_' . $cart_item_key );
			if ( $custom_price !== null && $custom_price !== false ) {
				$cart_item['data']->set_price( floatval( $custom_price ) );
			}
		}
	}

	/**
	 * Apply custom shipping costs from session
	 */
	public function apply_custom_shipping_costs( $rates ) {
		if ( ! WC()->session ) {
			return $rates;
		}
		
		foreach ( $rates as $rate_key => $rate ) {
			$custom_cost = WC()->session->get( 'wc_tp_custom_shipping_' . $rate->id );
			if ( $custom_cost !== null && $custom_cost !== false ) {
				$rates[ $rate_key ]->cost = floatval( $custom_cost );
				// Also update taxes if needed
				$rates[ $rate_key ]->taxes = array();
			}
		}
		return $rates;
	}

	/**
	 * Clear all custom prices and shipping when cart is emptied
	 */
	public function clear_all_custom_values() {
		if ( ! WC()->session ) {
			return;
		}

		// Get all session keys
		$session_data = WC()->session->get_session_data();
		
		// Clear all custom price keys
		foreach ( $session_data as $key => $value ) {
			if ( strpos( $key, 'wc_tp_custom_price_' ) === 0 ) {
				WC()->session->set( $key, null );
			}
			if ( strpos( $key, 'wc_tp_custom_shipping_' ) === 0 ) {
				WC()->session->set( $key, null );
			}
		}
		
		// Force session save
		WC()->session->save_data();
	}

	/**
	 * Clear custom price for a specific cart item when removed
	 */
	public function clear_custom_price_for_item( $cart_item_key, $cart ) {
		if ( ! WC()->session ) {
			return;
		}

		// Clear the custom price for this item
		WC()->session->set( 'wc_tp_custom_price_' . $cart_item_key, null );
		WC()->session->save_data();
	}

	/**
	 * Clear custom shipping when address changes (shipping recalculation)
	 */
	public function clear_custom_shipping_on_address_change() {
		if ( ! WC()->session ) {
			return;
		}

		// Get all session keys
		$session_data = WC()->session->get_session_data();
		
		// Clear all custom shipping keys
		foreach ( $session_data as $key => $value ) {
			if ( strpos( $key, 'wc_tp_custom_shipping_' ) === 0 ) {
				WC()->session->set( $key, null );
			}
		}
		
		// Force session save
		WC()->session->save_data();
	}

	/**
	 * Check if current user can edit (has employee role)
	 */
	private function user_can_edit() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$current_user = wp_get_current_user();
		
		// Get configured employee roles from settings
		$checkout_fields = get_option( 'wc_team_payroll_checkout_fields', array() );
		$allowed_roles = isset( $checkout_fields['agent_user_roles'] ) && is_array( $checkout_fields['agent_user_roles'] ) 
			? $checkout_fields['agent_user_roles'] 
			: array( 'shop_employee', 'shop_manager', 'administrator' );

		// Check if user has any of the allowed roles
		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $current_user->roles ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add edit button to cart item price
	 */
	public function add_cart_price_edit_button( $price_html, $cart_item, $cart_item_key ) {
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// Get the calling hook/filter name
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );
			$hook_name = 'unknown';
			foreach ( $backtrace as $trace ) {
				if ( isset( $trace['function'] ) && $trace['function'] === 'apply_filters' ) {
					if ( isset( $trace['args'][0] ) ) {
						$hook_name = $trace['args'][0];
						break;
					}
				}
			}
			
			error_log( 'WC TP Frontend Editor: add_cart_price_edit_button called via hook: ' . $hook_name );
			error_log( 'Price HTML: ' . $price_html );
			error_log( 'Cart item key: ' . $cart_item_key );
			error_log( 'User can edit: ' . ( $this->user_can_edit() ? 'yes' : 'no' ) );
			error_log( 'Current page: ' . ( is_cart() ? 'cart' : ( is_checkout() ? 'checkout' : 'other' ) ) );
		}

		if ( ! $this->user_can_edit() ) {
			return $price_html;
		}

		// Only show edit buttons on cart and checkout pages, NOT in mini cart
		if ( ! is_cart() && ! is_checkout() ) {
			return $price_html;
		}

		// Prevent duplicate wrapping
		if ( strpos( $price_html, 'wc-tp-cart-price-wrapper' ) !== false ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WC TP Frontend Editor: Already wrapped, skipping' );
			}
			return $price_html;
		}

		$product = $cart_item['data'];
		$current_price = $product->get_price();

		$wrapper = '<span class="wc-tp-cart-price-wrapper" data-cart-key="' . esc_attr( $cart_item_key ) . '" data-current-price="' . esc_attr( $current_price ) . '">';
		$wrapper .= '<span class="wc-tp-price-display">' . $price_html . '</span>';
		$wrapper .= '<button type="button" class="wc-tp-edit-btn wc-tp-cart-price-edit" title="' . esc_attr__( 'Edit Price', 'wc-team-payroll' ) . '">';
		$wrapper .= '<i class="ph ph-pencil"></i>';
		$wrapper .= '</button>';
		$wrapper .= '</span>';

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WC TP Frontend Editor: Wrapper added' );
		}

		return $wrapper;
	}

	/**
	 * Add edit button to cart item subtotal
	 */
	public function add_cart_subtotal_edit_button( $subtotal_html, $cart_item, $cart_item_key ) {
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// Get the calling hook/filter name
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );
			$hook_name = 'unknown';
			foreach ( $backtrace as $trace ) {
				if ( isset( $trace['function'] ) && $trace['function'] === 'apply_filters' ) {
					if ( isset( $trace['args'][0] ) ) {
						$hook_name = $trace['args'][0];
						break;
					}
				}
			}
			
			error_log( 'WC TP Frontend Editor: add_cart_subtotal_edit_button called via hook: ' . $hook_name );
			error_log( 'Subtotal HTML: ' . $subtotal_html );
			error_log( 'Cart item key: ' . $cart_item_key );
			error_log( 'User can edit: ' . ( $this->user_can_edit() ? 'yes' : 'no' ) );
			error_log( 'Current page: ' . ( is_cart() ? 'cart' : ( is_checkout() ? 'checkout' : 'other' ) ) );
		}

		if ( ! $this->user_can_edit() ) {
			return $subtotal_html;
		}

		// On checkout, we want to edit the subtotal since unit price isn't shown
		// On cart, we only edit unit price, not subtotal
		if ( ! is_checkout() ) {
			return $subtotal_html;
		}

		// Prevent duplicate wrapping
		if ( strpos( $subtotal_html, 'wc-tp-cart-price-wrapper' ) !== false ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WC TP Frontend Editor: Subtotal already wrapped, skipping' );
			}
			return $subtotal_html;
		}

		$product = $cart_item['data'];
		$current_price = $product->get_price();

		$wrapper = '<span class="wc-tp-cart-price-wrapper" data-cart-key="' . esc_attr( $cart_item_key ) . '" data-current-price="' . esc_attr( $current_price ) . '">';
		$wrapper .= '<span class="wc-tp-price-display">' . $subtotal_html . '</span>';
		$wrapper .= '<button type="button" class="wc-tp-edit-btn wc-tp-cart-price-edit" title="' . esc_attr__( 'Edit Price', 'wc-team-payroll' ) . '">';
		$wrapper .= '<i class="ph ph-pencil"></i>';
		$wrapper .= '</button>';
		$wrapper .= '</span>';

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WC TP Frontend Editor: Subtotal wrapper added' );
		}

		return $wrapper;
	}

	/**
	 * Add edit button to shipping cost
	 */
	public function add_shipping_edit_button( $label, $method ) {
		if ( ! $this->user_can_edit() ) {
			return $label;
		}

		// Prevent duplicate wrapping
		if ( strpos( $label, 'wc-tp-shipping-wrapper' ) !== false ) {
			return $label;
		}

		$cost = $method->cost;
		$method_id = $method->id;

		// Only add edit button if there's a cost
		if ( $cost > 0 ) {
			$wrapper = '<span class="wc-tp-shipping-wrapper" data-method-id="' . esc_attr( $method_id ) . '" data-current-cost="' . esc_attr( $cost ) . '">';
			$wrapper .= '<span class="wc-tp-shipping-display">' . $label . '</span>';
			$wrapper .= '<button type="button" class="wc-tp-edit-btn wc-tp-shipping-edit" title="' . esc_attr__( 'Edit Shipping Cost', 'wc-team-payroll' ) . '">';
			$wrapper .= '<i class="ph ph-pencil"></i>';
			$wrapper .= '</button>';
			$wrapper .= '</span>';
			return $wrapper;
		}

		return $label;
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		// Only load on cart and checkout pages
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		if ( ! $this->user_can_edit() ) {
			return;
		}

		// Enqueue Phosphor Icons (reuse existing handle if already loaded)
		if ( ! wp_script_is( 'phosphor-icons', 'enqueued' ) && ! wp_script_is( 'phosphor-icons', 'registered' ) ) {
			wp_enqueue_script(
				'phosphor-icons',
				'https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2',
				array(),
				'2.1.2',
				false
			);
		} else {
			// Just enqueue if already registered
			wp_enqueue_script( 'phosphor-icons' );
		}

		// Enqueue CSS with cache busting
		wp_enqueue_style(
			'wc-tp-frontend-editor',
			WC_TEAM_PAYROLL_URL . 'assets/css/frontend-editor.css',
			array(),
			WC_TEAM_PAYROLL_VERSION . '-' . time()
		);

		// Enqueue JS with cache busting
		wp_enqueue_script(
			'wc-tp-frontend-editor',
			WC_TEAM_PAYROLL_URL . 'assets/js/frontend-editor.js',
			array( 'jquery', 'phosphor-icons' ),
			WC_TEAM_PAYROLL_VERSION . '-' . time(),
			true
		);

		// Get text color from WordPress settings (body text color)
		$text_color = get_theme_mod( 'text_color', '#000000' );
		if ( empty( $text_color ) || $text_color === 'blank' ) {
			$text_color = '#000000'; // Default to black
		}

		// Localize script
		wp_localize_script( 'wc-tp-frontend-editor', 'wcTpEditor', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wc_tp_frontend_editor' ),
			'currency_symbol' => get_woocommerce_currency_symbol(),
			'currency_position' => get_option( 'woocommerce_currency_pos', 'left' ),
			'decimal_separator' => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'decimals' => wc_get_price_decimals(),
			'text_color' => $text_color,
			'debug' => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'i18n' => array(
				'edit_price' => __( 'Edit Price', 'wc-team-payroll' ),
				'edit_fee' => __( 'Edit Fee', 'wc-team-payroll' ),
				'add_fee' => __( 'Add Shipping Fee', 'wc-team-payroll' ),
				'fee_name' => __( 'Fee Name', 'wc-team-payroll' ),
				'fee_amount' => __( 'Amount', 'wc-team-payroll' ),
				'save' => __( 'Save', 'wc-team-payroll' ),
				'cancel' => __( 'Cancel', 'wc-team-payroll' ),
				'remove' => __( 'Remove', 'wc-team-payroll' ),
				'updating' => __( 'Updating...', 'wc-team-payroll' ),
				'success' => __( 'Updated successfully!', 'wc-team-payroll' ),
				'error' => __( 'Failed to update. Please try again.', 'wc-team-payroll' ),
				'invalid_price' => __( 'Please enter a valid price.', 'wc-team-payroll' ),
				'invalid_fee_name' => __( 'Please enter a fee name.', 'wc-team-payroll' ),
				'confirm_remove' => __( 'Are you sure you want to remove this fee?', 'wc-team-payroll' ),
			),
		) );
	}

	/**
	 * AJAX: Update cart item price
	 */
	public function ajax_update_cart_item_price() {
		check_ajax_referer( 'wc_tp_frontend_editor', 'nonce' );

		if ( ! $this->user_can_edit() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit prices.', 'wc-team-payroll' ) ) );
		}

		$cart_key = isset( $_POST['cart_key'] ) ? sanitize_text_field( $_POST['cart_key'] ) : '';
		$new_price = isset( $_POST['new_price'] ) ? sanitize_text_field( $_POST['new_price'] ) : '';

		if ( empty( $cart_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid cart item.', 'wc-team-payroll' ) ) );
		}

		$cart = WC()->cart;
		if ( ! $cart ) {
			wp_send_json_error( array( 'message' => __( 'Cart not found.', 'wc-team-payroll' ) ) );
		}

		$cart_item = $cart->get_cart_item( $cart_key );
		if ( ! $cart_item ) {
			wp_send_json_error( array( 'message' => __( 'Cart item not found.', 'wc-team-payroll' ) ) );
		}

		$new_price = floatval( str_replace( ',', '.', $new_price ) );
		if ( $new_price < 0 ) {
			wp_send_json_error( array( 'message' => __( 'Price must be a positive number.', 'wc-team-payroll' ) ) );
		}

		// Ensure session is initialized
		if ( ! WC()->session ) {
			wp_send_json_error( array( 'message' => __( 'Session not available.', 'wc-team-payroll' ) ) );
		}

		// Store the custom price in session so it persists through AJAX updates
		WC()->session->set( 'wc_tp_custom_price_' . $cart_key, $new_price );
		
		// Force session save
		WC()->session->save_data();

		// Update the cart item price in memory
		$cart_item['data']->set_price( $new_price );
		
		// Recalculate cart totals
		$cart->calculate_totals();

		// Log the change
		$this->log_cart_price_change( $cart_item, $new_price );

		wp_send_json_success( array(
			'message' => __( 'Price updated successfully!', 'wc-team-payroll' ),
			'new_price' => $new_price,
			'new_price_html' => wc_price( $new_price ),
			'cart_totals' => array(
				'subtotal' => WC()->cart->get_cart_subtotal(),
				'total' => WC()->cart->get_total(),
			),
		) );
	}

	/**
	 * AJAX: Update shipping cost
	 */
	public function ajax_update_shipping_cost() {
		check_ajax_referer( 'wc_tp_frontend_editor', 'nonce' );

		if ( ! $this->user_can_edit() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit shipping.', 'wc-team-payroll' ) ) );
		}

		$method_id = isset( $_POST['method_id'] ) ? sanitize_text_field( $_POST['method_id'] ) : '';
		$new_cost = isset( $_POST['new_cost'] ) ? sanitize_text_field( $_POST['new_cost'] ) : '';

		if ( empty( $method_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid shipping method.', 'wc-team-payroll' ) ) );
		}

		$new_cost = floatval( str_replace( ',', '.', $new_cost ) );
		if ( $new_cost < 0 ) {
			wp_send_json_error( array( 'message' => __( 'Cost must be a positive number.', 'wc-team-payroll' ) ) );
		}

		// Ensure session is initialized
		if ( ! WC()->session ) {
			wp_send_json_error( array( 'message' => __( 'Session not available.', 'wc-team-payroll' ) ) );
		}

		// Store the custom shipping cost in session
		WC()->session->set( 'wc_tp_custom_shipping_' . $method_id, $new_cost );
		
		// Force session save
		WC()->session->save_data();

		// Clear shipping cache
		$packages = WC()->cart->get_shipping_packages();
		foreach ( $packages as $package_key => $package ) {
			WC()->session->set( 'shipping_for_package_' . $package_key, false );
		}

		// Recalculate shipping and totals
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();

		// Log the change
		$this->log_shipping_change( $method_id, $new_cost );

		wp_send_json_success( array(
			'message' => __( 'Shipping cost updated successfully!', 'wc-team-payroll' ),
			'new_cost' => $new_cost,
			'new_cost_html' => wc_price( $new_cost ),
			'reload_required' => false, // No reload needed
		) );
	}

	/**
	 * Log cart price change for audit trail
	 */
	private function log_cart_price_change( $cart_item, $new_price ) {
		$current_user = wp_get_current_user();
		$product = $cart_item['data'];
		
		$price_history = get_option( '_wc_tp_cart_price_history', array() );
		if ( ! is_array( $price_history ) ) {
			$price_history = array();
		}

		$price_history[] = array(
			'date' => current_time( 'mysql' ),
			'user_id' => $current_user->ID,
			'user_name' => $current_user->display_name,
			'product_id' => $product->get_id(),
			'product_name' => $product->get_name(),
			'old_price' => $cart_item['data']->get_regular_price(),
			'new_price' => $new_price,
			'source' => 'frontend_editor_cart',
		);

		// Keep only last 100 entries
		if ( count( $price_history ) > 100 ) {
			$price_history = array_slice( $price_history, -100 );
		}

		update_option( '_wc_tp_cart_price_history', $price_history );
	}

	/**
	 * Log shipping change for audit trail
	 */
	private function log_shipping_change( $method_id, $new_cost ) {
		$current_user = wp_get_current_user();
		
		$shipping_history = get_option( '_wc_tp_shipping_history', array() );
		if ( ! is_array( $shipping_history ) ) {
			$shipping_history = array();
		}

		$shipping_history[] = array(
			'date' => current_time( 'mysql' ),
			'user_id' => $current_user->ID,
			'user_name' => $current_user->display_name,
			'method_id' => $method_id,
			'new_cost' => $new_cost,
			'source' => 'frontend_editor',
		);

		// Keep only last 100 entries
		if ( count( $shipping_history ) > 100 ) {
			$shipping_history = array_slice( $shipping_history, -100 );
		}

		update_option( '_wc_tp_shipping_history', $shipping_history );
	}
}

// Initialize the class
WC_Team_Payroll_Frontend_Editor::init();
