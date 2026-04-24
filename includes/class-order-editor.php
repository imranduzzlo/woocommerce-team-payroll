<?php
/**
 * Order Editor Class
 * Allows editing orders in specific statuses with conflict prevention
 *
 * @package WooCommerce Team Payroll
 * @since 1.7.61
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Order_Editor {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Check if order editing is enabled
		if ( ! $this->is_order_editing_enabled() ) {
			return;
		}

		// Check for conflicts with other plugins/themes
		if ( $this->has_editing_conflict() ) {
			return;
		}

		// Initialize hooks
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Add edit buttons to order actions
		add_filter( 'woocommerce_order_actions', array( $this, 'add_edit_order_action' ), 10, 2 );
		
		// Add edit icons to order items
		add_action( 'woocommerce_before_order_itemmeta', array( $this, 'add_edit_icons_to_items' ), 10, 3 );
		
		// Make order items editable based on status
		add_filter( 'wc_order_is_editable', array( $this, 'make_order_editable' ), 10, 2 );
		
		// Add custom meta box for order editing
		add_action( 'add_meta_boxes', array( $this, 'add_order_editor_meta_box' ) );
		
		// Make custom meta fields editable
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'make_custom_fields_editable' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'make_billing_custom_fields_editable' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'make_shipping_custom_fields_editable' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_wc_tp_update_order_item', array( $this, 'ajax_update_order_item' ) );
		add_action( 'wp_ajax_wc_tp_add_order_item', array( $this, 'ajax_add_order_item' ) );
		add_action( 'wp_ajax_wc_tp_remove_order_item', array( $this, 'ajax_remove_order_item' ) );
		add_action( 'wp_ajax_wc_tp_update_order_meta', array( $this, 'ajax_update_order_meta' ) );
		add_action( 'wp_ajax_wc_tp_get_order_edit_data', array( $this, 'ajax_get_order_edit_data' ) );
		add_action( 'wp_ajax_wc_tp_add_shipping', array( $this, 'ajax_add_shipping' ) );
		add_action( 'wp_ajax_wc_tp_update_shipping', array( $this, 'ajax_update_shipping' ) );
		add_action( 'wp_ajax_wc_tp_remove_shipping', array( $this, 'ajax_remove_shipping' ) );
		add_action( 'wp_ajax_wc_tp_add_fee', array( $this, 'ajax_add_fee' ) );
		add_action( 'wp_ajax_wc_tp_update_fee', array( $this, 'ajax_update_fee' ) );
		add_action( 'wp_ajax_wc_tp_remove_fee', array( $this, 'ajax_remove_fee' ) );
		add_action( 'wp_ajax_wc_tp_add_coupon', array( $this, 'ajax_add_coupon' ) );
		add_action( 'wp_ajax_wc_tp_remove_coupon', array( $this, 'ajax_remove_coupon' ) );
		add_action( 'wp_ajax_wc_tp_update_addresses', array( $this, 'ajax_update_addresses' ) );
		add_action( 'wp_ajax_wc_tp_update_order_status', array( $this, 'ajax_update_order_status' ) );
		add_action( 'wp_ajax_wc_tp_get_all_custom_fields', array( $this, 'ajax_get_all_custom_fields' ) );
		add_action( 'wp_ajax_wc_tp_save_custom_fields', array( $this, 'ajax_save_custom_fields' ) );
		
		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Add notice about editing capabilities
		add_action( 'admin_notices', array( $this, 'show_editing_notice' ) );
	}

	/**
	 * Static method to initialize the class
	 */
	public static function init() {
		return new self();
	}

	/**
	 * Check if order editing is enabled in settings
	 */
	private function is_order_editing_enabled() {
		$settings = get_option( 'wc_team_payroll_order_editor', array() );
		return isset( $settings['enabled'] ) && $settings['enabled'] === '1';
	}

	/**
	 * Get editable order statuses from settings
	 */
	private function get_editable_statuses() {
		$settings = get_option( 'wc_team_payroll_order_editor', array() );
		$statuses = isset( $settings['editable_statuses'] ) && is_array( $settings['editable_statuses'] ) 
			? $settings['editable_statuses'] 
			: array();
		
		return $statuses;
	}

	/**
	 * Check if order is editable based on status
	 */
	public function is_order_editable_by_status( $order ) {
		if ( ! $order ) {
			return false;
		}

		$order_status = $order->get_status();
		$editable_statuses = $this->get_editable_statuses();

		return in_array( $order_status, $editable_statuses );
	}

	/**
	 * Check for conflicts with other plugins/themes
	 */
	private function has_editing_conflict() {
		// Check for common order editing plugins
		$conflicting_plugins = array(
			'woocommerce-order-editor/woocommerce-order-editor.php',
			'advanced-order-export-for-woocommerce/advanced-order-export-for-woocommerce.php',
			'woo-order-export-lite/woo-order-export-lite.php',
		);

		foreach ( $conflicting_plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				return true;
			}
		}

		// Check if theme has custom order editing (common theme function names)
		if ( function_exists( 'theme_custom_order_editor' ) || 
		     function_exists( 'custom_wc_order_editor' ) ||
		     has_filter( 'wc_order_is_editable' ) ) {
			
			// Check if our filter priority is lower than existing ones
			global $wp_filter;
			if ( isset( $wp_filter['wc_order_is_editable'] ) ) {
				// If there are already filters, we might have a conflict
				$existing_filters = $wp_filter['wc_order_is_editable']->callbacks;
				if ( ! empty( $existing_filters ) ) {
					// Check if any filter is not from our plugin
					foreach ( $existing_filters as $priority => $callbacks ) {
						foreach ( $callbacks as $callback ) {
							if ( isset( $callback['function'] ) ) {
								$function = $callback['function'];
								// Check if it's not our callback
								if ( is_array( $function ) && isset( $function[0] ) ) {
									$class_name = is_object( $function[0] ) ? get_class( $function[0] ) : $function[0];
									if ( strpos( $class_name, 'WC_Team_Payroll' ) === false ) {
										// Found external filter, potential conflict
										return true;
									}
								}
							}
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Make order editable based on status
	 */
	public function make_order_editable( $editable, $order ) {
		if ( ! $order ) {
			return $editable;
		}

		// If already editable, keep it
		if ( $editable ) {
			return $editable;
		}

		// Check if order status is in editable list
		return $this->is_order_editable_by_status( $order );
	}

	/**
	 * Add edit order action
	 */
	public function add_edit_order_action( $actions, $order ) {
		if ( ! $this->is_order_editable_by_status( $order ) ) {
			return $actions;
		}

		$actions['wc_tp_edit_order'] = __( 'Edit Order Details', 'wc-team-payroll' );

		return $actions;
	}

	/**
	 * Add edit icons to order items
	 */
	public function add_edit_icons_to_items( $item_id, $item, $order ) {
		if ( ! $this->is_order_editable_by_status( $order ) ) {
			return;
		}

		?>
		<div class="wc-tp-order-item-actions" style="float: right; margin-left: 10px;">
			<a href="#" class="wc-tp-edit-item" data-item-id="<?php echo esc_attr( $item_id ); ?>" title="<?php esc_attr_e( 'Edit Item', 'wc-team-payroll' ); ?>">
				<span class="dashicons dashicons-edit" style="color: #2271b1;"></span>
			</a>
			<a href="#" class="wc-tp-remove-item" data-item-id="<?php echo esc_attr( $item_id ); ?>" title="<?php esc_attr_e( 'Remove Item', 'wc-team-payroll' ); ?>">
				<span class="dashicons dashicons-trash" style="color: #d63638;"></span>
			</a>
		</div>
		<?php
	}

	/**
	 * Add order editor meta box
	 */
	public function add_order_editor_meta_box() {
		global $post;
		
		if ( ! $post ) {
			return;
		}

		$order = wc_get_order( $post->ID );
		
		if ( ! $order || ! $this->is_order_editable_by_status( $order ) ) {
			return;
		}

		add_meta_box(
			'wc_tp_order_editor',
			__( 'Order Editor', 'wc-team-payroll' ),
			array( $this, 'render_order_editor_meta_box' ),
			'shop_order',
			'normal',
			'high'
		);
	}

	/**
	 * Render order editor meta box
	 */
	public function render_order_editor_meta_box( $post ) {
		$order = wc_get_order( $post->ID );
		
		if ( ! $order ) {
			return;
		}

		?>
		<div class="wc-tp-order-editor-wrapper">
			<div class="wc-tp-editor-notice" style="background: #e7f7ff; border-left: 4px solid #2271b1; padding: 12px; margin-bottom: 15px;">
				<p style="margin: 0;">
					<span class="dashicons dashicons-info" style="color: #2271b1;"></span>
					<strong><?php _e( 'Order Editing Enabled', 'wc-team-payroll' ); ?></strong><br>
					<?php _e( 'This order is in an editable status. You can modify items, quantities, prices, and order meta.', 'wc-team-payroll' ); ?>
				</p>
			</div>

			<div class="wc-tp-editor-actions">
				<button type="button" class="button button-primary wc-tp-add-product" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
					<span class="dashicons dashicons-plus-alt"></span> <?php _e( 'Add Product', 'wc-team-payroll' ); ?>
				</button>
				
				<button type="button" class="button wc-tp-add-shipping" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
					<span class="dashicons dashicons-admin-site"></span> <?php _e( 'Add/Edit Shipping', 'wc-team-payroll' ); ?>
				</button>
				
				<button type="button" class="button wc-tp-add-fee" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
					<span class="dashicons dashicons-money-alt"></span> <?php _e( 'Add Fee', 'wc-team-payroll' ); ?>
				</button>
				
				<button type="button" class="button wc-tp-add-coupon" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
					<span class="dashicons dashicons-tag"></span> <?php _e( 'Add Coupon', 'wc-team-payroll' ); ?>
				</button>
				
				<button type="button" class="button wc-tp-edit-addresses" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
					<span class="dashicons dashicons-location"></span> <?php _e( 'Edit Addresses', 'wc-team-payroll' ); ?>
				</button>
				
				<button type="button" class="button wc-tp-edit-order-meta" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
					<span class="dashicons dashicons-admin-generic"></span> <?php _e( 'Edit Order Meta', 'wc-team-payroll' ); ?>
				</button>
				
				<button type="button" class="button wc-tp-recalculate-order" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
					<span class="dashicons dashicons-update"></span> <?php _e( 'Recalculate Totals', 'wc-team-payroll' ); ?>
				</button>
			</div>

			<div class="wc-tp-editor-info" style="margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
				<h4 style="margin-top: 0;"><?php _e( 'Quick Stats', 'wc-team-payroll' ); ?></h4>
				<ul style="margin: 0; padding-left: 20px;">
					<li><?php printf( __( 'Total Items: %d', 'wc-team-payroll' ), count( $order->get_items() ) ); ?></li>
					<li><?php printf( __( 'Shipping Methods: %d', 'wc-team-payroll' ), count( $order->get_items( 'shipping' ) ) ); ?></li>
					<li><?php printf( __( 'Fees: %d', 'wc-team-payroll' ), count( $order->get_items( 'fee' ) ) ); ?></li>
					<li><?php printf( __( 'Coupons: %d', 'wc-team-payroll' ), count( $order->get_items( 'coupon' ) ) ); ?></li>
					<li><?php printf( __( 'Order Total: %s', 'wc-team-payroll' ), wc_price( $order->get_total() ) ); ?></li>
					<li><?php printf( __( 'Order Status: %s', 'wc-team-payroll' ), wc_get_order_status_name( $order->get_status() ) ); ?></li>
					<li><?php printf( __( 'Last Modified: %s', 'wc-team-payroll' ), $order->get_date_modified() ? $order->get_date_modified()->date_i18n( 'Y-m-d H:i:s' ) : __( 'Never', 'wc-team-payroll' ) ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: Get order edit data
	 */
	public function ajax_get_order_edit_data() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		// Get order items
		$items = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			$items[] = array(
				'item_id' => $item_id,
				'product_id' => $item->get_product_id(),
				'variation_id' => $item->get_variation_id(),
				'name' => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'subtotal' => $item->get_subtotal(),
				'total' => $item->get_total(),
				'tax' => $item->get_total_tax(),
				'sku' => $product ? $product->get_sku() : '',
			);
		}

		// Get ALL order meta dynamically
		$meta_data = array();
		$all_meta = $order->get_meta_data();
		
		foreach ( $all_meta as $meta ) {
			$key = $meta->key;
			$value = $meta->value;
			
			// Auto-detect field type
			$field_type = $this->detect_field_type( $value );
			$field_options = $this->get_field_options( $key, $value );
			$field_label = $this->format_label( $key );
			
			$meta_data[] = array(
				'id' => $meta->id,
				'key' => $key,
				'value' => $value,
				'type' => $field_type,
				'label' => $field_label,
				'options' => $field_options,
				'is_internal' => strpos( $key, '_' ) === 0,
			);
		}

		// Get shipping methods
		$shipping_items = array();
		foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
			$shipping_items[] = array(
				'item_id' => $item_id,
				'method_title' => $item->get_method_title(),
				'method_id' => $item->get_method_id(),
				'total' => $item->get_total(),
			);
		}

		// Get fees
		$fee_items = array();
		foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {
			$fee_items[] = array(
				'item_id' => $item_id,
				'name' => $item->get_name(),
				'total' => $item->get_total(),
				'tax_status' => $item->get_tax_status(),
			);
		}

		// Get coupons
		$coupon_items = array();
		foreach ( $order->get_items( 'coupon' ) as $item_id => $item ) {
			$coupon_items[] = array(
				'item_id' => $item_id,
				'code' => $item->get_code(),
				'discount' => $item->get_discount(),
			);
		}

		// Get addresses
		$billing_address = array(
			'first_name' => $order->get_billing_first_name(),
			'last_name' => $order->get_billing_last_name(),
			'company' => $order->get_billing_company(),
			'address_1' => $order->get_billing_address_1(),
			'address_2' => $order->get_billing_address_2(),
			'city' => $order->get_billing_city(),
			'state' => $order->get_billing_state(),
			'postcode' => $order->get_billing_postcode(),
			'country' => $order->get_billing_country(),
			'email' => $order->get_billing_email(),
			'phone' => $order->get_billing_phone(),
		);

		$shipping_address = array(
			'first_name' => $order->get_shipping_first_name(),
			'last_name' => $order->get_shipping_last_name(),
			'company' => $order->get_shipping_company(),
			'address_1' => $order->get_shipping_address_1(),
			'address_2' => $order->get_shipping_address_2(),
			'city' => $order->get_shipping_city(),
			'state' => $order->get_shipping_state(),
			'postcode' => $order->get_shipping_postcode(),
			'country' => $order->get_shipping_country(),
		);

		wp_send_json_success( array(
			'items' => $items,
			'meta' => $meta_data,
			'shipping' => $shipping_items,
			'fees' => $fee_items,
			'coupons' => $coupon_items,
			'billing_address' => $billing_address,
			'shipping_address' => $shipping_address,
			'order_total' => $order->get_total(),
			'order_status' => $order->get_status(),
		) );
	}

	/**
	 * AJAX: Update order item
	 */
	public function ajax_update_order_item() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$item_id = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;
		$quantity = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 1;
		$subtotal = isset( $_POST['subtotal'] ) ? floatval( $_POST['subtotal'] ) : 0;
		$total = isset( $_POST['total'] ) ? floatval( $_POST['total'] ) : 0;

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		$item = $order->get_item( $item_id );

		if ( ! $item ) {
			wp_send_json_error( array( 'message' => __( 'Item not found', 'wc-team-payroll' ) ) );
		}

		// Update item
		$item->set_quantity( $quantity );
		$item->set_subtotal( $subtotal );
		$item->set_total( $total );
		$item->save();

		// Recalculate order totals
		$order->calculate_totals();
		$order->save();

		// Log the change
		$order->add_order_note( sprintf(
			__( 'Order item #%d updated: Quantity=%d, Total=%s (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			$item_id,
			$quantity,
			wc_price( $total )
		) );

		// Recalculate commission
		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Item updated successfully', 'wc-team-payroll' ),
			'order_total' => $order->get_total(),
		) );
	}

	/**
	 * AJAX: Add order item
	 */
	public function ajax_add_order_item() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		$quantity = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 1;

		$order = wc_get_order( $order_id );
		$product = wc_get_product( $product_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		// Add item to order
		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( $quantity );
		$item->set_subtotal( $product->get_price() * $quantity );
		$item->set_total( $product->get_price() * $quantity );
		
		$order->add_item( $item );
		$order->calculate_totals();
		$order->save();

		// Log the change
		$order->add_order_note( sprintf(
			__( 'Product "%s" (ID: %d) added: Quantity=%d (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			$product->get_name(),
			$product_id,
			$quantity
		) );

		// Recalculate commission
		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Product added successfully', 'wc-team-payroll' ),
			'order_total' => $order->get_total(),
			'item_id' => $item->get_id(),
		) );
	}

	/**
	 * AJAX: Remove order item
	 */
	public function ajax_remove_order_item() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$item_id = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		$item = $order->get_item( $item_id );

		if ( ! $item ) {
			wp_send_json_error( array( 'message' => __( 'Item not found', 'wc-team-payroll' ) ) );
		}

		$item_name = $item->get_name();

		// Remove item
		$order->remove_item( $item_id );
		$order->calculate_totals();
		$order->save();

		// Log the change
		$order->add_order_note( sprintf(
			__( 'Product "%s" (Item ID: %d) removed (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			$item_name,
			$item_id
		) );

		// Recalculate commission
		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Item removed successfully', 'wc-team-payroll' ),
			'order_total' => $order->get_total(),
		) );
	}

	/**
	 * AJAX: Update order meta
	 */
	public function ajax_update_order_meta() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$meta_key = isset( $_POST['meta_key'] ) ? sanitize_text_field( $_POST['meta_key'] ) : '';
		$meta_value = isset( $_POST['meta_value'] ) ? sanitize_text_field( $_POST['meta_value'] ) : '';
		$action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : 'update';

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		if ( empty( $meta_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Meta key is required', 'wc-team-payroll' ) ) );
		}

		// Perform action
		switch ( $action_type ) {
			case 'add':
			case 'update':
				$order->update_meta_data( $meta_key, $meta_value );
				$order->save();
				$message = __( 'Order meta updated successfully', 'wc-team-payroll' );
				
				// Log the change
				$order->add_order_note( sprintf(
					__( 'Order meta updated: %s = %s (via Team Payroll Order Editor)', 'wc-team-payroll' ),
					$meta_key,
					$meta_value
				) );
				break;

			case 'delete':
				$order->delete_meta_data( $meta_key );
				$order->save();
				$message = __( 'Order meta deleted successfully', 'wc-team-payroll' );
				
				// Log the change
				$order->add_order_note( sprintf(
					__( 'Order meta deleted: %s (via Team Payroll Order Editor)', 'wc-team-payroll' ),
					$meta_key
				) );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Invalid action type', 'wc-team-payroll' ) ) );
		}

		// Recalculate commission if agent/processor meta changed
		if ( in_array( $meta_key, array( '_primary_agent_id', '_processor_user_id' ) ) ) {
			do_action( 'wc_team_payroll_order_edited', $order_id );
		}

		wp_send_json_success( array(
			'message' => $message,
		) );
	}

	/**
	 * AJAX: Save custom fields
	 */
	public function ajax_save_custom_fields() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$fields = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : array();

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		if ( empty( $fields ) || ! is_array( $fields ) ) {
			wp_send_json_error( array( 'message' => __( 'No fields to save', 'wc-team-payroll' ) ) );
		}

		// Save each field
		foreach ( $fields as $meta_key => $meta_value ) {
			$meta_key = sanitize_text_field( $meta_key );
			$meta_value = sanitize_text_field( $meta_value );

			$order->update_meta_data( $meta_key, $meta_value );
		}

		$order->save();

		// Log the change
		$order->add_order_note( sprintf(
			__( 'Custom fields updated via Team Payroll Order Editor', 'wc-team-payroll' )
		) );

		// Recalculate commission if needed
		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Custom fields saved successfully', 'wc-team-payroll' ),
		) );
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on order edit page
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		global $post;
		
		if ( ! $post || $post->post_type !== 'shop_order' ) {
			return;
		}

		$order = wc_get_order( $post->ID );
		
		if ( ! $order || ! $this->is_order_editable_by_status( $order ) ) {
			return;
		}

		wp_enqueue_script(
			'wc-tp-order-editor',
			WC_TEAM_PAYROLL_URL . 'assets/js/order-editor.js',
			array( 'jquery', 'wc-enhanced-select' ),
			WC_TEAM_PAYROLL_VERSION,
			true
		);

		wp_localize_script( 'wc-tp-order-editor', 'wcTpOrderEditor', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wc-tp-order-editor' ),
			'order_id' => $order->get_id(),
			'i18n' => array(
				'confirm_remove' => __( 'Are you sure you want to remove this item?', 'wc-team-payroll' ),
				'confirm_delete_meta' => __( 'Are you sure you want to delete this meta field?', 'wc-team-payroll' ),
				'select_product' => __( 'Select a product', 'wc-team-payroll' ),
				'enter_quantity' => __( 'Enter quantity', 'wc-team-payroll' ),
				'error' => __( 'Error', 'wc-team-payroll' ),
				'success' => __( 'Success', 'wc-team-payroll' ),
			),
		) );

		wp_enqueue_style(
			'wc-tp-order-editor',
			WC_TEAM_PAYROLL_URL . 'assets/css/order-editor.css',
			array(),
			WC_TEAM_PAYROLL_VERSION
		);
	}

	/**
	 * Show editing notice
	 */
	public function show_editing_notice() {
		$screen = get_current_screen();
		
		if ( ! $screen || $screen->id !== 'shop_order' ) {
			return;
		}

		global $post;
		
		if ( ! $post ) {
			return;
		}

		$order = wc_get_order( $post->ID );
		
		if ( ! $order ) {
			return;
		}

		if ( $this->is_order_editable_by_status( $order ) ) {
			?>
			<div class="notice notice-info">
				<p>
					<span class="dashicons dashicons-edit"></span>
					<strong><?php _e( 'Order Editing Enabled', 'wc-team-payroll' ); ?></strong> - 
					<?php _e( 'This order is in an editable status. You can modify items and order details using the Order Editor below.', 'wc-team-payroll' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Make custom fields in "Additional Information" section editable
	 */
	public function make_custom_fields_editable( $order ) {
		if ( ! $this->is_order_editable_by_status( $order ) ) {
			return;
		}

		// Standard WooCommerce fields that should not be made editable
		$standard_fields = array(
			'Email', 'Phone', 'Payment method', 'First name', 'Last name', 
			'Company', 'Address', 'City', 'Postcode', 'Country', 'State',
			'Date created', 'Status', 'Customer'
		);

		// Get all order meta
		$all_meta = $order->get_meta_data();
		$custom_fields = array();

		foreach ( $all_meta as $meta ) {
			$key = $meta->key;
			$value = $meta->value;

			// Skip internal WooCommerce meta (starts with _)
			if ( strpos( $key, '_' ) === 0 ) {
				continue;
			}

			// Skip if value is empty or array
			if ( empty( $value ) || is_array( $value ) ) {
				continue;
			}

			$custom_fields[ $key ] = $value;
		}

		// If no custom fields, don't render anything
		if ( empty( $custom_fields ) ) {
			return;
		}

		?>
		<div class="wc-tp-custom-fields-section">
			<?php
			foreach ( $custom_fields as $meta_key => $meta_value ) {
				// Format label from meta key
				$label = $this->format_label( $meta_key );

				// Skip standard fields
				if ( in_array( $label, $standard_fields ) ) {
					continue;
				}

				// Detect field type
				$field_type = $this->detect_field_type( $meta_value );

				// Display readonly field with edit button
				?>
				<p class="form-field form-field-wide wc-tp-custom-field-row" data-meta-key="<?php echo esc_attr( $meta_key ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>">
					<label><?php echo esc_html( $label ); ?>:</label>
					<span class="wc-tp-field-display"><?php echo esc_html( $meta_value ); ?></span>
					<button type="button" class="button wc-tp-edit-custom-field-btn" data-meta-key="<?php echo esc_attr( $meta_key ); ?>" data-label="<?php echo esc_attr( $label ); ?>" data-value="<?php echo esc_attr( $meta_value ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" style="margin-left: 10px;">
						<span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px; margin-right: 5px;"></span>Edit
					</button>
				</p>
				<?php
			}
			?>
		</div>

		<?php
		// Add JavaScript to handle edit button clicks
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(document).on('click', '.wc-tp-edit-custom-field-btn', function(e) {
				e.preventDefault();
				
				var $btn = $(this);
				var metaKey = $btn.data('meta-key');
				var label = $btn.data('label');
				var value = $btn.data('value');
				var fieldType = $btn.data('field-type');
				var orderId = <?php echo $order->get_id(); ?>;
				
				// Create modal with proper field type
				var fieldHtml = '';
				
				if (fieldType === 'checkbox') {
					var checked = (value === '1' || value.toLowerCase() === 'yes') ? 'checked' : '';
					fieldHtml = '<label style="display: flex; align-items: center; gap: 8px;"><input type="checkbox" class="wc-tp-field-value" ' + checked + ' style="margin: 0;"> <span>Yes</span></label>';
				} else if (fieldType === 'email') {
					fieldHtml = '<input type="email" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'url') {
					fieldHtml = '<input type="url" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'date') {
					fieldHtml = '<input type="date" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'number') {
					fieldHtml = '<input type="number" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'textarea') {
					fieldHtml = '<textarea class="wc-tp-field-value" rows="4" style="width: 100%;">' + value + '</textarea>';
				} else {
					fieldHtml = '<input type="text" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				}
				
				var modalHtml = '<div class="wc-tp-modal-overlay">' +
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
					var $saveBtn = $(this);
					$saveBtn.prop('disabled', true).text('Saving...');
					
					var newValue = $('.wc-tp-field-value').is(':checkbox') ? 
						($('.wc-tp-field-value').is(':checked') ? '1' : '0') : 
						$('.wc-tp-field-value').val();
					var key = $('.wc-tp-field-key').val();
					
					$.ajax({
						url: wcTpOrderEditor.ajax_url,
						type: 'POST',
						data: {
							action: 'wc_tp_save_custom_fields',
							nonce: wcTpOrderEditor.nonce,
							order_id: orderId,
							fields: {
								[key]: newValue
							}
						},
						success: function(response) {
							if (response.success) {
								$('.wc-tp-modal-overlay').remove();
								location.reload();
							} else {
								alert(response.data.message || 'Error saving field');
								$saveBtn.prop('disabled', false).text('Save Changes');
							}
						},
						error: function() {
							alert('Error saving field');
							$saveBtn.prop('disabled', false).text('Save Changes');
						}
					});
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Make billing custom fields editable
	 */
	public function make_billing_custom_fields_editable( $order ) {
		if ( ! $this->is_order_editable_by_status( $order ) ) {
			return;
		}

		// Standard WooCommerce billing fields
		$standard_fields = array(
			'Email', 'Phone', 'Payment method', 'First name', 'Last name', 
			'Company', 'Address', 'City', 'Postcode', 'Country', 'State'
		);

		// Get billing custom meta fields
		$billing_custom_fields = array();
		$all_meta = $order->get_meta_data();

		foreach ( $all_meta as $meta ) {
			$key = $meta->key;
			$value = $meta->value;

			// Only get billing custom fields (start with _billing_ but not standard)
			if ( strpos( $key, '_billing_' ) === 0 ) {
				$label = $this->format_label( str_replace( '_billing_', '', $key ) );
				
				// Skip standard fields
				if ( in_array( $label, $standard_fields ) ) {
					continue;
				}

				// Skip if value is empty or array
				if ( empty( $value ) || is_array( $value ) ) {
					continue;
				}

				$billing_custom_fields[ $key ] = $value;
			}
		}

		// If no custom fields, don't render anything
		if ( empty( $billing_custom_fields ) ) {
			return;
		}

		?>
		<div class="wc-tp-billing-custom-fields-section">
			<?php
			foreach ( $billing_custom_fields as $meta_key => $meta_value ) {
				$label = $this->format_label( str_replace( '_billing_', '', $meta_key ) );
				$field_type = $this->detect_field_type( $meta_value );

				// Display readonly field with edit button
				?>
				<p class="form-field form-field-wide wc-tp-custom-field-row" data-meta-key="<?php echo esc_attr( $meta_key ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>">
					<label><?php echo esc_html( $label ); ?>:</label>
					<span class="wc-tp-field-display"><?php echo esc_html( $meta_value ); ?></span>
					<button type="button" class="button wc-tp-edit-custom-field-btn" data-meta-key="<?php echo esc_attr( $meta_key ); ?>" data-label="<?php echo esc_attr( $label ); ?>" data-value="<?php echo esc_attr( $meta_value ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" style="margin-left: 10px;">
						<span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px; margin-right: 5px;"></span>Edit
					</button>
				</p>
				<?php
			}
			?>
		</div>

		<?php
		// Add JavaScript to handle edit button clicks
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(document).on('click', '.wc-tp-edit-custom-field-btn', function(e) {
				e.preventDefault();
				
				var $btn = $(this);
				var metaKey = $btn.data('meta-key');
				var label = $btn.data('label');
				var value = $btn.data('value');
				var fieldType = $btn.data('field-type');
				var orderId = <?php echo $order->get_id(); ?>;
				
				// Create modal with proper field type
				var fieldHtml = '';
				
				if (fieldType === 'checkbox') {
					var checked = (value === '1' || value.toLowerCase() === 'yes') ? 'checked' : '';
					fieldHtml = '<label style="display: flex; align-items: center; gap: 8px;"><input type="checkbox" class="wc-tp-field-value" ' + checked + ' style="margin: 0;"> <span>Yes</span></label>';
				} else if (fieldType === 'email') {
					fieldHtml = '<input type="email" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'url') {
					fieldHtml = '<input type="url" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'date') {
					fieldHtml = '<input type="date" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'number') {
					fieldHtml = '<input type="number" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'textarea') {
					fieldHtml = '<textarea class="wc-tp-field-value" rows="4" style="width: 100%;">' + value + '</textarea>';
				} else {
					fieldHtml = '<input type="text" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				}
				
				var modalHtml = '<div class="wc-tp-modal-overlay">' +
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
					var $saveBtn = $(this);
					$saveBtn.prop('disabled', true).text('Saving...');
					
					var newValue = $('.wc-tp-field-value').is(':checkbox') ? 
						($('.wc-tp-field-value').is(':checked') ? '1' : '0') : 
						$('.wc-tp-field-value').val();
					var key = $('.wc-tp-field-key').val();
					
					$.ajax({
						url: wcTpOrderEditor.ajax_url,
						type: 'POST',
						data: {
							action: 'wc_tp_save_custom_fields',
							nonce: wcTpOrderEditor.nonce,
							order_id: orderId,
							fields: {
								[key]: newValue
							}
						},
						success: function(response) {
							if (response.success) {
								$('.wc-tp-modal-overlay').remove();
								location.reload();
							} else {
								alert(response.data.message || 'Error saving field');
								$saveBtn.prop('disabled', false).text('Save Changes');
							}
						},
						error: function() {
							alert('Error saving field');
							$saveBtn.prop('disabled', false).text('Save Changes');
						}
					});
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Make shipping custom fields editable
	 */
	public function make_shipping_custom_fields_editable( $order ) {
		if ( ! $this->is_order_editable_by_status( $order ) ) {
			return;
		}

		// Standard WooCommerce shipping fields
		$standard_fields = array(
			'First name', 'Last name', 'Company', 'Address', 'City', 
			'Postcode', 'Country', 'State'
		);

		// Get shipping custom meta fields
		$shipping_custom_fields = array();
		$all_meta = $order->get_meta_data();

		foreach ( $all_meta as $meta ) {
			$key = $meta->key;
			$value = $meta->value;

			// Only get shipping custom fields (start with _shipping_ but not standard)
			if ( strpos( $key, '_shipping_' ) === 0 ) {
				$label = $this->format_label( str_replace( '_shipping_', '', $key ) );
				
				// Skip standard fields
				if ( in_array( $label, $standard_fields ) ) {
					continue;
				}

				// Skip if value is empty or array
				if ( empty( $value ) || is_array( $value ) ) {
					continue;
				}

				$shipping_custom_fields[ $key ] = $value;
			}
		}

		// If no custom fields, don't render anything
		if ( empty( $shipping_custom_fields ) ) {
			return;
		}

		?>
		<div class="wc-tp-shipping-custom-fields-section">
			<?php
			foreach ( $shipping_custom_fields as $meta_key => $meta_value ) {
				$label = $this->format_label( str_replace( '_shipping_', '', $meta_key ) );
				$field_type = $this->detect_field_type( $meta_value );

				// Display readonly field with edit button
				?>
				<p class="form-field form-field-wide wc-tp-custom-field-row" data-meta-key="<?php echo esc_attr( $meta_key ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>">
					<label><?php echo esc_html( $label ); ?>:</label>
					<span class="wc-tp-field-display"><?php echo esc_html( $meta_value ); ?></span>
					<button type="button" class="button wc-tp-edit-custom-field-btn" data-meta-key="<?php echo esc_attr( $meta_key ); ?>" data-label="<?php echo esc_attr( $label ); ?>" data-value="<?php echo esc_attr( $meta_value ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" style="margin-left: 10px;">
						<span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px; margin-right: 5px;"></span>Edit
					</button>
				</p>
				<?php
			}
			?>
		</div>

		<?php
		// Add JavaScript to handle edit button clicks
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(document).on('click', '.wc-tp-edit-custom-field-btn', function(e) {
				e.preventDefault();
				
				var $btn = $(this);
				var metaKey = $btn.data('meta-key');
				var label = $btn.data('label');
				var value = $btn.data('value');
				var fieldType = $btn.data('field-type');
				var orderId = <?php echo $order->get_id(); ?>;
				
				// Create modal with proper field type
				var fieldHtml = '';
				
				if (fieldType === 'checkbox') {
					var checked = (value === '1' || value.toLowerCase() === 'yes') ? 'checked' : '';
					fieldHtml = '<label style="display: flex; align-items: center; gap: 8px;"><input type="checkbox" class="wc-tp-field-value" ' + checked + ' style="margin: 0;"> <span>Yes</span></label>';
				} else if (fieldType === 'email') {
					fieldHtml = '<input type="email" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'url') {
					fieldHtml = '<input type="url" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'date') {
					fieldHtml = '<input type="date" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'number') {
					fieldHtml = '<input type="number" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				} else if (fieldType === 'textarea') {
					fieldHtml = '<textarea class="wc-tp-field-value" rows="4" style="width: 100%;">' + value + '</textarea>';
				} else {
					fieldHtml = '<input type="text" class="wc-tp-field-value" value="' + value.replace(/"/g, '&quot;') + '" style="width: 100%;">';
				}
				
				var modalHtml = '<div class="wc-tp-modal-overlay">' +
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
					var $saveBtn = $(this);
					$saveBtn.prop('disabled', true).text('Saving...');
					
					var newValue = $('.wc-tp-field-value').is(':checkbox') ? 
						($('.wc-tp-field-value').is(':checked') ? '1' : '0') : 
						$('.wc-tp-field-value').val();
					var key = $('.wc-tp-field-key').val();
					
					$.ajax({
						url: wcTpOrderEditor.ajax_url,
						type: 'POST',
						data: {
							action: 'wc_tp_save_custom_fields',
							nonce: wcTpOrderEditor.nonce,
							order_id: orderId,
							fields: {
								[key]: newValue
							}
						},
						success: function(response) {
							if (response.success) {
								$('.wc-tp-modal-overlay').remove();
								location.reload();
							} else {
								alert(response.data.message || 'Error saving field');
								$saveBtn.prop('disabled', false).text('Save Changes');
							}
						},
						error: function() {
							alert('Error saving field');
							$saveBtn.prop('disabled', false).text('Save Changes');
						}
					});
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX: Get all custom fields dynamically
	 */
	public function ajax_get_all_custom_fields() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		// Get ALL meta data
		$all_meta = $order->get_meta_data();
		$custom_fields = array();

		foreach ( $all_meta as $meta ) {
			$key = $meta->key;
			$value = $meta->value;

			// Auto-detect field type
			$field_type = $this->detect_field_type( $value );
			$field_options = $this->get_field_options( $key, $value );

			$custom_fields[] = array(
				'key' => $key,
				'value' => $value,
				'type' => $field_type,
				'label' => $this->format_label( $key ),
				'options' => $field_options,
			);
		}

		wp_send_json_success( array(
			'fields' => $custom_fields,
		) );
	}

	/**
	 * Auto-detect field type from value
	 */
	private function detect_field_type( $value ) {
		if ( is_array( $value ) ) {
			return 'select'; // or multiselect
		}

		if ( is_bool( $value ) || $value === '1' || $value === '0' ) {
			return 'checkbox';
		}

		if ( is_numeric( $value ) && strlen( $value ) < 10 ) {
			return 'number';
		}

		if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
			return 'email';
		}

		if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
			return 'url';
		}

		if ( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $value ) || preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return 'date';
		}

		if ( strlen( $value ) > 100 || strpos( $value, "\n" ) !== false ) {
			return 'textarea';
		}

		return 'text';
	}

	/**
	 * Get field options for select fields
	 */
	private function get_field_options( $key, $value ) {
		// Check if this is a known select field
		$select_fields = array(
			'_order_source' => array( 'Facebook', 'WhatsApp', 'Website', 'Phone', 'Instagram', 'Other' ),
			'_order_priority' => array( 'Low', 'Medium', 'High', 'Urgent' ),
			'_payment_method' => array( 'bKash', 'Nagad', 'Rocket', 'Cash', 'Bank Transfer' ),
		);

		if ( isset( $select_fields[ $key ] ) ) {
			return array_combine( $select_fields[ $key ], $select_fields[ $key ] );
		}

		// Check if it's an agent/user field
		if ( strpos( $key, 'agent' ) !== false || strpos( $key, 'user' ) !== false ) {
			return $this->get_agent_options();
		}

		return array();
	}

	/**
	 * Format meta key to readable label
	 */
	private function format_label( $key ) {
		// Remove leading underscore
		$label = ltrim( $key, '_' );
		
		// Replace underscores with spaces
		$label = str_replace( '_', ' ', $label );
		
		// Capitalize words
		$label = ucwords( $label );
		
		return $label;
	}

	/**
	 * Get agent options for dropdown
	 */
	private function get_agent_options() {
		$agents = get_users( array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'orderby' => 'display_name',
			'order' => 'ASC',
		) );

		$options = array( '' => '-- Select Agent --' );
		foreach ( $agents as $agent ) {
			$options[ $agent->ID ] = $agent->display_name . ' (#' . $agent->ID . ')';
		}

		return $options;
	}

	/**
	 * AJAX: Add shipping method
	 */
	public function ajax_add_shipping() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$method_title = isset( $_POST['method_title'] ) ? sanitize_text_field( $_POST['method_title'] ) : '';
		$method_id = isset( $_POST['method_id'] ) ? sanitize_text_field( $_POST['method_id'] ) : 'flat_rate';
		$cost = isset( $_POST['cost'] ) ? floatval( $_POST['cost'] ) : 0;

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		// Create shipping item
		$item = new WC_Order_Item_Shipping();
		$item->set_method_title( $method_title );
		$item->set_method_id( $method_id );
		$item->set_total( $cost );
		
		$order->add_item( $item );
		$order->calculate_totals();
		$order->save();

		// Log the change
		$order->add_order_note( sprintf(
			__( 'Shipping method "%s" added: Cost=%s (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			$method_title,
			wc_price( $cost )
		) );

		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Shipping method added successfully', 'wc-team-payroll' ),
			'order_total' => $order->get_total(),
		) );
	}

	/**
	 * AJAX: Update shipping method
	 */
	public function ajax_update_shipping() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$item_id = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;
		$method_title = isset( $_POST['method_title'] ) ? sanitize_text_field( $_POST['method_title'] ) : '';
		$cost = isset( $_POST['cost'] ) ? floatval( $_POST['cost'] ) : 0;

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		$item = $order->get_item( $item_id );

		if ( ! $item || $item->get_type() !== 'shipping' ) {
			wp_send_json_error( array( 'message' => __( 'Shipping item not found', 'wc-team-payroll' ) ) );
		}

		$item->set_method_title( $method_title );
		$item->set_total( $cost );
		$item->save();

		$order->calculate_totals();
		$order->save();

		$order->add_order_note( sprintf(
			__( 'Shipping method updated: %s = %s (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			$method_title,
			wc_price( $cost )
		) );

		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Shipping method updated successfully', 'wc-team-payroll' ),
			'order_total' => $order->get_total(),
		) );
	}

	/**
	 * AJAX: Remove shipping method
	 */
	public function ajax_remove_shipping() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$item_id = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		$item = $order->get_item( $item_id );

		if ( ! $item || $item->get_type() !== 'shipping' ) {
			wp_send_json_error( array( 'message' => __( 'Shipping item not found', 'wc-team-payroll' ) ) );
		}

		$method_title = $item->get_method_title();

		$order->remove_item( $item_id );
		$order->calculate_totals();
		$order->save();

		$order->add_order_note( sprintf(
			__( 'Shipping method "%s" removed (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			$method_title
		) );

		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Shipping method removed successfully', 'wc-team-payroll' ),
			'order_total' => $order->get_total(),
		) );
	}

	/**
	 * AJAX: Add fee
	 */
	public function ajax_add_fee() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$fee_name = isset( $_POST['fee_name'] ) ? sanitize_text_field( $_POST['fee_name'] ) : '';
		$fee_amount = isset( $_POST['fee_amount'] ) ? floatval( $_POST['fee_amount'] ) : 0;
		$taxable = isset( $_POST['taxable'] ) ? (bool) $_POST['taxable'] : false;

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		// Create fee item
		$item = new WC_Order_Item_Fee();
		$item->set_name( $fee_name );
		$item->set_total( $fee_amount );
		$item->set_tax_status( $taxable ? 'taxable' : 'none' );
		
		$order->add_item( $item );
		$order->calculate_totals();
		$order->save();

		$order->add_order_note( sprintf(
			__( 'Fee "%s" added: Amount=%s (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			$fee_name,
			wc_price( $fee_amount )
		) );

		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Fee added successfully', 'wc-team-payroll' ),
			'order_total' => $order->get_total(),
		) );
	}

	/**
	 * AJAX: Update fee
	 */
	public function ajax_update_fee() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$item_id = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;
		$fee_name = isset( $_POST['fee_name'] ) ? sanitize_text_field( $_POST['fee_name'] ) : '';
		$fee_amount = isset( $_POST['fee_amount'] ) ? floatval( $_POST['fee_amount'] ) : 0;

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		$item = $order->get_item( $item_id );

		if ( ! $item || $item->get_type() !== 'fee' ) {
			wp_send_json_error( array( 'message' => __( 'Fee not found', 'wc-team-payroll' ) ) );
		}

		$item->set_name( $fee_name );
		$item->set_total( $fee_amount );
		$item->save();

		$order->calculate_totals();
		$order->save();

		$order->add_order_note( sprintf(
			__( 'Fee updated: %s = %s (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			$fee_name,
			wc_price( $fee_amount )
		) );

		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Fee updated successfully', 'wc-team-payroll' ),
			'order_total' => $order->get_total(),
		) );
	}

	/**
	 * AJAX: Remove fee
	 */
	public function ajax_remove_fee() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$item_id = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		$item = $order->get_item( $item_id );

		if ( ! $item || $item->get_type() !== 'fee' ) {
			wp_send_json_error( array( 'message' => __( 'Fee not found', 'wc-team-payroll' ) ) );
		}

		$fee_name = $item->get_name();

		$order->remove_item( $item_id );
		$order->calculate_totals();
		$order->save();

		$order->add_order_note( sprintf(
			__( 'Fee "%s" removed (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			$fee_name
		) );

		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Fee removed successfully', 'wc-team-payroll' ),
			'order_total' => $order->get_total(),
		) );
	}

	/**
	 * AJAX: Add coupon
	 */
	public function ajax_add_coupon() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( $_POST['coupon_code'] ) : '';

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		// Check if coupon exists
		$coupon = new WC_Coupon( $coupon_code );
		
		if ( ! $coupon->get_id() ) {
			wp_send_json_error( array( 'message' => __( 'Coupon not found', 'wc-team-payroll' ) ) );
		}

		// Apply coupon
		$result = $order->apply_coupon( $coupon_code );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$order->calculate_totals();
		$order->save();

		$order->add_order_note( sprintf(
			__( 'Coupon "%s" applied (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			$coupon_code
		) );

		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Coupon applied successfully', 'wc-team-payroll' ),
			'order_total' => $order->get_total(),
		) );
	}

	/**
	 * AJAX: Remove coupon
	 */
	public function ajax_remove_coupon() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( $_POST['coupon_code'] ) : '';

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		// Remove coupon
		$order->remove_coupon( $coupon_code );
		$order->calculate_totals();
		$order->save();

		$order->add_order_note( sprintf(
			__( 'Coupon "%s" removed (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			$coupon_code
		) );

		do_action( 'wc_team_payroll_order_edited', $order_id );

		wp_send_json_success( array(
			'message' => __( 'Coupon removed successfully', 'wc-team-payroll' ),
			'order_total' => $order->get_total(),
		) );
	}

	/**
	 * AJAX: Update addresses
	 */
	public function ajax_update_addresses() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$address_type = isset( $_POST['address_type'] ) ? sanitize_text_field( $_POST['address_type'] ) : 'billing';
		$address_data = isset( $_POST['address_data'] ) ? $_POST['address_data'] : array();

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		// Sanitize address data
		$sanitized_address = array();
		$address_fields = array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'email', 'phone' );
		
		foreach ( $address_fields as $field ) {
			if ( isset( $address_data[ $field ] ) ) {
				$sanitized_address[ $field ] = sanitize_text_field( $address_data[ $field ] );
			}
		}

		// Update address
		if ( $address_type === 'billing' ) {
			$order->set_billing_first_name( $sanitized_address['first_name'] ?? '' );
			$order->set_billing_last_name( $sanitized_address['last_name'] ?? '' );
			$order->set_billing_company( $sanitized_address['company'] ?? '' );
			$order->set_billing_address_1( $sanitized_address['address_1'] ?? '' );
			$order->set_billing_address_2( $sanitized_address['address_2'] ?? '' );
			$order->set_billing_city( $sanitized_address['city'] ?? '' );
			$order->set_billing_state( $sanitized_address['state'] ?? '' );
			$order->set_billing_postcode( $sanitized_address['postcode'] ?? '' );
			$order->set_billing_country( $sanitized_address['country'] ?? '' );
			$order->set_billing_email( $sanitized_address['email'] ?? '' );
			$order->set_billing_phone( $sanitized_address['phone'] ?? '' );
		} else {
			$order->set_shipping_first_name( $sanitized_address['first_name'] ?? '' );
			$order->set_shipping_last_name( $sanitized_address['last_name'] ?? '' );
			$order->set_shipping_company( $sanitized_address['company'] ?? '' );
			$order->set_shipping_address_1( $sanitized_address['address_1'] ?? '' );
			$order->set_shipping_address_2( $sanitized_address['address_2'] ?? '' );
			$order->set_shipping_city( $sanitized_address['city'] ?? '' );
			$order->set_shipping_state( $sanitized_address['state'] ?? '' );
			$order->set_shipping_postcode( $sanitized_address['postcode'] ?? '' );
			$order->set_shipping_country( $sanitized_address['country'] ?? '' );
		}

		$order->save();

		$order->add_order_note( sprintf(
			__( '%s address updated (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			ucfirst( $address_type )
		) );

		wp_send_json_success( array(
			'message' => __( 'Address updated successfully', 'wc-team-payroll' ),
		) );
	}

	/**
	 * AJAX: Update order status
	 */
	public function ajax_update_order_status() {
		check_ajax_referer( 'wc-tp-order-editor', 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		$new_status = isset( $_POST['new_status'] ) ? sanitize_text_field( $_POST['new_status'] ) : '';

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'wc-team-payroll' ) ) );
		}

		if ( ! $this->is_order_editable_by_status( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Order is not editable in current status', 'wc-team-payroll' ) ) );
		}

		$old_status = $order->get_status();
		$order->set_status( $new_status );
		$order->save();

		$order->add_order_note( sprintf(
			__( 'Order status changed from %s to %s (via Team Payroll Order Editor)', 'wc-team-payroll' ),
			wc_get_order_status_name( $old_status ),
			wc_get_order_status_name( $new_status )
		) );

		wp_send_json_success( array(
			'message' => __( 'Order status updated successfully', 'wc-team-payroll' ),
		) );
	}
}
