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

		// Product Price Editing
		add_filter( 'woocommerce_get_price_html', array( $this, 'add_price_edit_button' ), 999, 2 );

		// Cart Shipping Fee Editing
		add_action( 'woocommerce_cart_totals_after_shipping', array( $this, 'add_shipping_editor' ) );
		add_action( 'woocommerce_review_order_after_shipping', array( $this, 'add_shipping_editor' ) );

		// Enqueue scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// AJAX handlers
		add_action( 'wp_ajax_wc_tp_update_product_price', array( $this, 'ajax_update_product_price' ) );
		add_action( 'wp_ajax_wc_tp_add_shipping_fee', array( $this, 'ajax_add_shipping_fee' ) );
		add_action( 'wp_ajax_wc_tp_update_shipping_fee', array( $this, 'ajax_update_shipping_fee' ) );
		add_action( 'wp_ajax_wc_tp_remove_shipping_fee', array( $this, 'ajax_remove_shipping_fee' ) );
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
	 * Add edit button to product price
	 */
	public function add_price_edit_button( $price_html, $product ) {
		if ( ! $product || ! $this->user_can_edit() ) {
			return $price_html;
		}

		// Get product ID
		$product_id = $product->get_id();
		
		// Get current price
		$current_price = $product->get_regular_price();
		if ( empty( $current_price ) ) {
			$current_price = $product->get_price();
		}

		// Wrap price with edit functionality
		$wrapper = '<span class="wc-tp-price-wrapper" data-product-id="' . esc_attr( $product_id ) . '" data-current-price="' . esc_attr( $current_price ) . '">';
		$wrapper .= '<span class="wc-tp-price-display">' . $price_html . '</span>';
		$wrapper .= '<button type="button" class="wc-tp-edit-btn wc-tp-price-edit-btn" title="' . esc_attr__( 'Edit Price', 'wc-team-payroll' ) . '">';
		$wrapper .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>';
		$wrapper .= '</button>';
		$wrapper .= '</span>';

		return $wrapper;
	}

	/**
	 * Add shipping fee editor to cart/checkout
	 */
	public function add_shipping_editor() {
		if ( ! $this->user_can_edit() ) {
			return;
		}

		?>
		<tr class="wc-tp-shipping-editor-row">
			<td colspan="2">
				<div class="wc-tp-shipping-editor">
					<h4><?php esc_html_e( 'Manage Shipping Fees', 'wc-team-payroll' ); ?></h4>
					
					<div class="wc-tp-shipping-fees-list">
						<?php $this->render_shipping_fees(); ?>
					</div>
					
					<button type="button" class="button wc-tp-add-shipping-btn">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
						<?php esc_html_e( 'Add Shipping Fee', 'wc-team-payroll' ); ?>
					</button>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render existing shipping fees
	 */
	private function render_shipping_fees() {
		$cart = WC()->cart;
		if ( ! $cart ) {
			return;
		}

		$fees = $cart->get_fees();
		
		if ( empty( $fees ) ) {
			echo '<p class="wc-tp-no-fees">' . esc_html__( 'No shipping fees added yet.', 'wc-team-payroll' ) . '</p>';
			return;
		}

		foreach ( $fees as $fee_key => $fee ) {
			$fee_name = $fee->name;
			$fee_amount = $fee->amount;
			$fee_id = sanitize_title( $fee_name );
			
			?>
			<div class="wc-tp-shipping-fee-item" data-fee-id="<?php echo esc_attr( $fee_id ); ?>" data-fee-key="<?php echo esc_attr( $fee_key ); ?>">
				<div class="wc-tp-fee-display">
					<span class="wc-tp-fee-name"><?php echo esc_html( $fee_name ); ?></span>
					<span class="wc-tp-fee-amount"><?php echo wc_price( $fee_amount ); ?></span>
					<div class="wc-tp-fee-actions">
						<button type="button" class="wc-tp-edit-btn wc-tp-fee-edit-btn" title="<?php esc_attr_e( 'Edit Fee', 'wc-team-payroll' ); ?>">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</button>
						<button type="button" class="wc-tp-remove-btn wc-tp-fee-remove-btn" title="<?php esc_attr_e( 'Remove Fee', 'wc-team-payroll' ); ?>">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
						</button>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		if ( ! $this->user_can_edit() ) {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'wc-tp-frontend-editor',
			WC_TEAM_PAYROLL_URL . 'assets/css/frontend-editor.css',
			array(),
			WC_TEAM_PAYROLL_VERSION
		);

		// Enqueue JS
		wp_enqueue_script(
			'wc-tp-frontend-editor',
			WC_TEAM_PAYROLL_URL . 'assets/js/frontend-editor.js',
			array( 'jquery' ),
			WC_TEAM_PAYROLL_VERSION,
			true
		);

		// Localize script
		wp_localize_script( 'wc-tp-frontend-editor', 'wcTpEditor', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wc_tp_frontend_editor' ),
			'currency_symbol' => get_woocommerce_currency_symbol(),
			'currency_position' => get_option( 'woocommerce_currency_pos', 'left' ),
			'decimal_separator' => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'decimals' => wc_get_price_decimals(),
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
	 * AJAX: Update product price
	 */
	public function ajax_update_product_price() {
		check_ajax_referer( 'wc_tp_frontend_editor', 'nonce' );

		if ( ! $this->user_can_edit() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit prices.', 'wc-team-payroll' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		$new_price = isset( $_POST['new_price'] ) ? sanitize_text_field( $_POST['new_price'] ) : '';

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'wc-team-payroll' ) ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'wc-team-payroll' ) ) );
		}

		$new_price = floatval( str_replace( ',', '.', $new_price ) );
		if ( $new_price < 0 ) {
			wp_send_json_error( array( 'message' => __( 'Price must be a positive number.', 'wc-team-payroll' ) ) );
		}

		$old_price = $product->get_regular_price();
		$product->set_regular_price( $new_price );
		$product->set_price( $new_price );
		$product->save();

		wc_delete_product_transients( $product_id );

		// Log the change
		$this->log_price_change( $product_id, $old_price, $new_price );

		wp_send_json_success( array(
			'message' => __( 'Price updated successfully!', 'wc-team-payroll' ),
			'new_price' => $new_price,
			'new_price_html' => wc_price( $new_price ),
		) );
	}

	/**
	 * AJAX: Add shipping fee
	 */
	public function ajax_add_shipping_fee() {
		check_ajax_referer( 'wc_tp_frontend_editor', 'nonce' );

		if ( ! $this->user_can_edit() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to add fees.', 'wc-team-payroll' ) ) );
		}

		$fee_name = isset( $_POST['fee_name'] ) ? sanitize_text_field( $_POST['fee_name'] ) : '';
		$fee_amount = isset( $_POST['fee_amount'] ) ? sanitize_text_field( $_POST['fee_amount'] ) : '';

		if ( empty( $fee_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Fee name is required.', 'wc-team-payroll' ) ) );
		}

		$fee_amount = floatval( str_replace( ',', '.', $fee_amount ) );

		$cart = WC()->cart;
		if ( ! $cart ) {
			wp_send_json_error( array( 'message' => __( 'Cart not found.', 'wc-team-payroll' ) ) );
		}

		// Add fee to cart
		$cart->add_fee( $fee_name, $fee_amount, true );

		// Log the change
		$this->log_fee_change( 'add', $fee_name, $fee_amount );

		wp_send_json_success( array(
			'message' => __( 'Shipping fee added successfully!', 'wc-team-payroll' ),
			'fee_name' => $fee_name,
			'fee_amount' => $fee_amount,
			'fee_html' => wc_price( $fee_amount ),
		) );
	}

	/**
	 * AJAX: Update shipping fee
	 */
	public function ajax_update_shipping_fee() {
		check_ajax_referer( 'wc_tp_frontend_editor', 'nonce' );

		if ( ! $this->user_can_edit() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit fees.', 'wc-team-payroll' ) ) );
		}

		$fee_key = isset( $_POST['fee_key'] ) ? sanitize_text_field( $_POST['fee_key'] ) : '';
		$new_amount = isset( $_POST['new_amount'] ) ? sanitize_text_field( $_POST['new_amount'] ) : '';

		$new_amount = floatval( str_replace( ',', '.', $new_amount ) );

		$cart = WC()->cart;
		if ( ! $cart ) {
			wp_send_json_error( array( 'message' => __( 'Cart not found.', 'wc-team-payroll' ) ) );
		}

		$fees = $cart->get_fees();
		if ( ! isset( $fees[ $fee_key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Fee not found.', 'wc-team-payroll' ) ) );
		}

		$fee = $fees[ $fee_key ];
		$old_amount = $fee->amount;
		$fee->amount = $new_amount;
		$fee->total = $new_amount;

		// Recalculate totals
		$cart->calculate_totals();

		// Log the change
		$this->log_fee_change( 'update', $fee->name, $new_amount, $old_amount );

		wp_send_json_success( array(
			'message' => __( 'Shipping fee updated successfully!', 'wc-team-payroll' ),
			'new_amount' => $new_amount,
			'new_amount_html' => wc_price( $new_amount ),
		) );
	}

	/**
	 * AJAX: Remove shipping fee
	 */
	public function ajax_remove_shipping_fee() {
		check_ajax_referer( 'wc_tp_frontend_editor', 'nonce' );

		if ( ! $this->user_can_edit() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to remove fees.', 'wc-team-payroll' ) ) );
		}

		$fee_key = isset( $_POST['fee_key'] ) ? sanitize_text_field( $_POST['fee_key'] ) : '';

		$cart = WC()->cart;
		if ( ! $cart ) {
			wp_send_json_error( array( 'message' => __( 'Cart not found.', 'wc-team-payroll' ) ) );
		}

		$fees = $cart->get_fees();
		if ( ! isset( $fees[ $fee_key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Fee not found.', 'wc-team-payroll' ) ) );
		}

		$fee_name = $fees[ $fee_key ]->name;
		$fee_amount = $fees[ $fee_key ]->amount;

		// Remove fee
		unset( $fees[ $fee_key ] );
		$cart->fees_api()->set_fees( $fees );
		$cart->calculate_totals();

		// Log the change
		$this->log_fee_change( 'remove', $fee_name, $fee_amount );

		wp_send_json_success( array(
			'message' => __( 'Shipping fee removed successfully!', 'wc-team-payroll' ),
		) );
	}

	/**
	 * Log price change for audit trail
	 */
	private function log_price_change( $product_id, $old_price, $new_price ) {
		$current_user = wp_get_current_user();
		
		$price_history = get_post_meta( $product_id, '_wc_tp_price_history', true );
		if ( ! is_array( $price_history ) ) {
			$price_history = array();
		}

		$price_history[] = array(
			'date' => current_time( 'mysql' ),
			'user_id' => $current_user->ID,
			'user_name' => $current_user->display_name,
			'old_price' => $old_price,
			'new_price' => $new_price,
			'source' => 'frontend_editor',
		);

		// Keep only last 50 entries
		if ( count( $price_history ) > 50 ) {
			$price_history = array_slice( $price_history, -50 );
		}

		update_post_meta( $product_id, '_wc_tp_price_history', $price_history );
	}

	/**
	 * Log fee change for audit trail
	 */
	private function log_fee_change( $action, $fee_name, $new_amount, $old_amount = null ) {
		$current_user = wp_get_current_user();
		
		$fee_history = get_option( '_wc_tp_fee_history', array() );
		if ( ! is_array( $fee_history ) ) {
			$fee_history = array();
		}

		$fee_history[] = array(
			'date' => current_time( 'mysql' ),
			'user_id' => $current_user->ID,
			'user_name' => $current_user->display_name,
			'action' => $action,
			'fee_name' => $fee_name,
			'old_amount' => $old_amount,
			'new_amount' => $new_amount,
			'source' => 'frontend_editor',
		);

		// Keep only last 100 entries
		if ( count( $fee_history ) > 100 ) {
			$fee_history = array_slice( $fee_history, -100 );
		}

		update_option( '_wc_tp_fee_history', $fee_history );
	}
}

// Initialize the class
WC_Team_Payroll_Frontend_Editor::init();
