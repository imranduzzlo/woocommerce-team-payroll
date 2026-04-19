<?php
/**
 * AJAX Handlers
 */

class WC_Team_Payroll_AJAX_Handlers {

	public static function init() {
		add_action( 'wp_ajax_wc_team_payroll_mark_paid', array( __CLASS__, 'mark_payroll_paid' ) );
		add_action( 'wp_ajax_wc_tp_get_employee_orders', array( __CLASS__, 'get_employee_orders' ) );
		add_action( 'wp_ajax_wc_tp_get_employee_salary', array( __CLASS__, 'get_employee_salary' ) );
		add_action( 'wp_ajax_wc_tp_get_salary_history', array( __CLASS__, 'get_salary_history' ) );
		add_action( 'wp_ajax_wc_tp_update_employee_salary', array( __CLASS__, 'update_employee_salary' ) );
		add_action( 'wp_ajax_wc_tp_get_employee_payments', array( __CLASS__, 'get_employee_payments' ) );
		add_action( 'wp_ajax_wc_tp_add_payment', array( __CLASS__, 'add_payment' ) );
		add_action( 'wp_ajax_wc_tp_delete_payment', array( __CLASS__, 'delete_payment' ) );
		add_action( 'wp_ajax_wc_tp_get_payment_methods', array( __CLASS__, 'get_payment_methods' ) );
		add_action( 'wp_ajax_wc_tp_add_payment_method', array( __CLASS__, 'add_payment_method' ) );
		add_action( 'wp_ajax_wc_tp_delete_payment_method', array( __CLASS__, 'delete_payment_method' ) );
	}

	public static function mark_payroll_paid() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$year = intval( $_POST['year'] );
		$month = intval( $_POST['month'] );
		$amount = floatval( $_POST['amount'] );

		WC_Team_Payroll_Payroll_Engine::mark_payroll_paid( $user_id, $year, $month, $amount );

		wp_send_json_success( array(
			'message' => __( 'Payroll marked as paid', 'wc-team-payroll' ),
		) );
	}

	public static function get_employee_orders() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
		$role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
		$search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

		// Get all orders
		$args = array(
			'limit'  => -1,
			'status' => 'any',
		);

		$all_orders = wc_get_orders( $args );
		$orders = array();

		foreach ( $all_orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			// Check if user is involved in this order
			$is_agent = intval( $agent_id ) === intval( $user_id );
			$is_processor = intval( $processor_id ) === intval( $user_id );

			// Determine user role (if both, show as agent)
			$user_role = null;
			$user_role_label = '';
			if ( $is_agent ) {
				$user_role = 'agent';
				$user_role_label = 'Agent';
			} elseif ( $is_processor ) {
				$user_role = 'processor';
				$user_role_label = 'Processor';
			}

			// Skip if user is not involved in this order
			if ( ! $user_role ) {
				continue;
			}

			// Get order status and check if commission applies
			$order_status = $order->get_status();
			$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
			$has_commission = $commission_data && in_array( $order_status, $commission_statuses );

			// Calculate attributed total
			$attributed_value = 0;
			if ( $has_commission && is_array( $commission_data ) ) {
				// If user is both agent and processor (owner), show full order total
				if ( $is_agent && $is_processor ) {
					$attributed_value = floatval( $order->get_total() );
				} elseif ( $user_role === 'agent' && isset( $commission_data['agent_order_value'] ) ) {
					$attributed_value = floatval( $commission_data['agent_order_value'] );
				} elseif ( $user_role === 'processor' && isset( $commission_data['processor_order_value'] ) ) {
					$attributed_value = floatval( $commission_data['processor_order_value'] );
				}
			}

			// Calculate user earnings
			$user_earnings = 0;
			$order_commission = 0;
			if ( $has_commission && is_array( $commission_data ) ) {
				// Calculate earnings based on role(s)
				if ( $is_agent && $is_processor ) {
					// Owner gets both agent and processor earnings
					$user_earnings = floatval( $commission_data['agent_earnings'] ) + floatval( $commission_data['processor_earnings'] );
				} elseif ( $user_role === 'agent' ) {
					$user_earnings = floatval( $commission_data['agent_earnings'] );
				} else {
					$user_earnings = floatval( $commission_data['processor_earnings'] );
				}
				$order_commission = floatval( $commission_data['total_commission'] );
			}

			// Get customer info
			$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$customer_email = $order->get_billing_email();
			$customer_phone = $order->get_billing_phone();

			$orders[] = array(
				'order_id' => $order->get_id(),
				'date' => $order->get_date_created()->format( 'Y-m-d' ),
				'total' => $order->get_total(),
				'commission' => $order_commission,
				'earnings' => $user_earnings,
				'user_earnings' => $user_earnings,
				'customer_name' => $customer_name,
				'customer_email' => $customer_email,
				'customer_phone' => $customer_phone,
				'status' => $order_status,
				'role' => $user_role,
				'role_label' => $user_role_label,
				'attributed_total' => $attributed_value,
				'attributed_total_formatted' => $attributed_value > 0 ? wc_price( $attributed_value ) : '—',
			);
		}

		// Filter by date range
		if ( $start_date && $end_date ) {
			$start_timestamp = strtotime( $start_date );
			$end_timestamp = strtotime( $end_date . ' 23:59:59' );
			$orders = array_filter( $orders, function( $order ) use ( $start_timestamp, $end_timestamp ) {
				$order_time = strtotime( $order['date'] ?? '' );
				return $order_time >= $start_timestamp && $order_time <= $end_timestamp;
			} );
		}

		// Filter by status
		if ( $status ) {
			$orders = array_filter( $orders, function( $order ) use ( $status ) {
				return ( $order['status'] ?? '' ) === $status;
			} );
		}

		// Filter by role (instead of flag)
		if ( $role ) {
			$orders = array_filter( $orders, function( $order ) use ( $role ) {
				return ( $order['role'] ?? '' ) === $role;
			} );
		}

		// Search
		if ( $search ) {
			$orders = array_filter( $orders, function( $order ) use ( $search ) {
				$search_lower = strtolower( $search );
				return strpos( strtolower( $order['order_id'] ?? '' ), $search_lower ) !== false ||
					   strpos( strtolower( $order['customer_name'] ?? '' ), $search_lower ) !== false ||
					   strpos( strtolower( $order['customer_email'] ?? '' ), $search_lower ) !== false ||
					   strpos( strtolower( $order['customer_phone'] ?? '' ), $search_lower ) !== false;
			} );
		}

		wp_send_json_success( array(
			'orders' => array_values( $orders ),
		) );
	}

	public static function get_employee_salary() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );

		$salary_type = get_user_meta( $user_id, '_wc_tp_salary_type', true ) ?: 'commission';
		$salary_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ?: 0;
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ?: 'monthly';

		wp_send_json_success( array(
			'type' => $salary_type,
			'amount' => $salary_amount,
			'frequency' => $salary_frequency,
		) );
	}

	public static function get_salary_history() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

		$history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Filter by date range
		if ( $start_date && $end_date ) {
			$start_timestamp = strtotime( $start_date );
			$end_timestamp = strtotime( $end_date . ' 23:59:59' );
			$history = array_filter( $history, function( $entry ) use ( $start_timestamp, $end_timestamp ) {
				$entry_time = strtotime( $entry['date'] ?? '' );
				return $entry_time >= $start_timestamp && $entry_time <= $end_timestamp;
			} );
		}

		wp_send_json_success( array(
			'history' => array_values( $history ),
		) );
	}

	public static function update_employee_salary() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$salary_type = sanitize_text_field( $_POST['salary_type'] ?? '' );
		$salary_amount = floatval( $_POST['salary_amount'] ?? 0 );
		$salary_frequency = sanitize_text_field( $_POST['salary_frequency'] ?? 'monthly' );

		// Get existing history
		$history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Add new entry
		$history[] = array(
			'date' => current_time( 'Y-m-d H:i:s' ),
			'new_type' => $salary_type,
			'new_amount' => $salary_amount,
			'new_frequency' => $salary_frequency,
		);

		update_user_meta( $user_id, '_wc_tp_salary_history', $history );
		update_user_meta( $user_id, '_wc_tp_salary_type', $salary_type );
		update_user_meta( $user_id, '_wc_tp_salary_amount', $salary_amount );
		update_user_meta( $user_id, '_wc_tp_salary_frequency', $salary_frequency );

		wp_send_json_success( array(
			'message' => __( 'Salary updated', 'wc-team-payroll' ),
		) );
	}

	public static function get_employee_payments() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		// Filter by date range
		if ( $start_date && $end_date ) {
			$start_timestamp = strtotime( $start_date );
			$end_timestamp = strtotime( $end_date . ' 23:59:59' );
			$payments = array_filter( $payments, function( $payment ) use ( $start_timestamp, $end_timestamp ) {
				$payment_time = strtotime( $payment['date'] ?? '' );
				return $payment_time >= $start_timestamp && $payment_time <= $end_timestamp;
			} );
		}

		wp_send_json_success( array(
			'payments' => array_values( $payments ),
		) );
	}

	public static function add_payment() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$amount = floatval( $_POST['amount'] ?? 0 );
		$payment_date = sanitize_text_field( $_POST['payment_date'] ?? '' );
		$payment_method_id = sanitize_text_field( $_POST['payment_method_id'] ?? '' );

		// Get payment method name
		$payment_method_name = '';
		if ( $payment_method_id ) {
			$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
			if ( is_array( $methods ) ) {
				foreach ( $methods as $method ) {
					if ( $method['id'] === $payment_method_id ) {
						$payment_method_name = $method['method_name'] . ' - ' . $method['method_details'];
						break;
					}
				}
			}
		}

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		$payments[] = array(
			'id' => uniqid(),
			'date' => $payment_date,
			'amount' => $amount,
			'payment_method' => $payment_method_name,
			'added_by' => wp_get_current_user()->display_name,
		);

		update_user_meta( $user_id, '_wc_tp_payments', $payments );

		wp_send_json_success( array(
			'message' => __( 'Payment added', 'wc-team-payroll' ),
		) );
	}

	public static function delete_payment() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$payment_id = sanitize_text_field( $_POST['payment_id'] ?? '' );

		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		$payments = array_filter( $payments, function( $payment ) use ( $payment_id ) {
			return ( $payment['id'] ?? '' ) !== $payment_id;
		} );

		update_user_meta( $user_id, '_wc_tp_payments', array_values( $payments ) );

		wp_send_json_success( array(
			'message' => __( 'Payment deleted', 'wc-team-payroll' ),
		) );
	}

	public static function get_payment_methods() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );

		$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $methods ) ) {
			$methods = array();
		}

		wp_send_json_success( array(
			'methods' => $methods,
		) );
	}

	public static function add_payment_method() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$method_name = sanitize_text_field( $_POST['method_name'] ?? '' );
		$method_details = sanitize_text_field( $_POST['method_details'] ?? '' );
		$note = sanitize_text_field( $_POST['note'] ?? '' );

		$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $methods ) ) {
			$methods = array();
		}

		$methods[] = array(
			'id' => uniqid(),
			'method_name' => $method_name,
			'method_details' => $method_details,
			'note' => $note,
			'added_date' => current_time( 'Y-m-d H:i:s' ),
		);

		update_user_meta( $user_id, '_wc_tp_payment_methods', $methods );

		wp_send_json_success( array(
			'message' => __( 'Payment method added', 'wc-team-payroll' ),
		) );
	}

	public static function delete_payment_method() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = intval( $_POST['user_id'] );
		$method_id = sanitize_text_field( $_POST['method_id'] ?? '' );

		$methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $methods ) ) {
			$methods = array();
		}

		$methods = array_filter( $methods, function( $method ) use ( $method_id ) {
			return ( $method['id'] ?? '' ) !== $method_id;
		} );

		update_user_meta( $user_id, '_wc_tp_payment_methods', array_values( $methods ) );

		wp_send_json_success( array(
			'message' => __( 'Payment method deleted', 'wc-team-payroll' ),
		) );
	}
}

// Initialize AJAX handlers
add_action( 'plugins_loaded', function() {
	WC_Team_Payroll_AJAX_Handlers::init();
} );
