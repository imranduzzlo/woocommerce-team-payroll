<?php
/**
 * Core Commission Calculation Engine
 */

class WC_Team_Payroll_Core_Engine {

	public function __construct() {
		// Get commission calculation statuses from settings
		$commission_statuses = self::get_commission_calculation_statuses();
		
		// Add hooks for each commission status
		foreach ( $commission_statuses as $status ) {
			add_action( 'woocommerce_order_status_' . $status, array( $this, 'calculate_order_commission' ) );
		}
		
		add_action( 'woocommerce_order_item_added', array( $this, 'on_order_updated' ), 10, 3 );
		add_action( 'woocommerce_order_item_changed', array( $this, 'on_order_updated' ), 10, 3 );
		add_action( 'woocommerce_order_item_removed', array( $this, 'on_order_updated' ), 10, 2 );
		add_action( 'woocommerce_order_refunded', array( $this, 'on_order_updated' ), 10, 2 );
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'on_order_updated' ), 10, 2 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'on_order_cancelled' ) );
		add_action( 'woocommerce_order_status_failed', array( $this, 'on_order_cancelled' ) );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'on_order_cancelled' ) );
	}

	/**
	 * Calculate commission for an order
	 */
	public function calculate_order_commission( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$agent_id = $order->get_meta( '_primary_agent_id' );
		$processor_id = $order->get_meta( '_processor_user_id' );

		if ( ! $agent_id && ! $processor_id ) {
			return;
		}

		$commission_data = $this->calculate_commission( $order, $agent_id, $processor_id );
		$order->update_meta_data( '_commission_data', $commission_data );
		$order->save();

		do_action( 'wc_team_payroll_commission_calculated', $order_id, $commission_data );
	}

	/**
	 * Calculate commission breakdown for order
	 */
	public function calculate_commission( $order, $agent_id, $processor_id ) {
		$settings = get_option( 'wc_team_payroll_settings', array() );
		
		$agent_percentage = isset( $settings['agent_percentage'] ) ? floatval( $settings['agent_percentage'] ) : 70;
		$processor_percentage = isset( $settings['processor_percentage'] ) ? floatval( $settings['processor_percentage'] ) : 30;
		$commission_field_name = isset( $settings['commission_field_name'] ) ? $settings['commission_field_name'] : 'team_commission';

		// Get order total for attributed value calculation
		$order_total = floatval( $order->get_total() );

		// Calculate attributed order values based on whether there's a processor
		// If no processor or same user, owner gets 100% of order value
		// If different users, split by percentage
		$agent_order_value = 0;
		$processor_order_value = 0;
		
		if ( $agent_id === $processor_id || ! $processor_id ) {
			// Same user or no processor - owner gets full order value
			$agent_order_value = $order_total;
			$processor_order_value = 0;
		} else {
			// Different users - split by percentage
			$agent_order_value = ( $order_total * $agent_percentage ) / 100;
			$processor_order_value = ( $order_total * $processor_percentage ) / 100;
		}

		$commission_data = array(
			'order_id'       => $order->get_id(),
			'agent_id'       => $agent_id,
			'processor_id'   => $processor_id,
			'items'          => array(),
			'total_commission' => 0,
			'agent_earnings' => 0,
			'processor_earnings' => 0,
			'agent_order_value' => $agent_order_value, // Agent's attributed order value
			'processor_order_value' => $processor_order_value, // Processor's attributed order value
			'order_total'    => $order_total, // Store full order total for reference
			'calculated_at'  => current_time( 'mysql' ),
		);

		// Check if order is refunded
		$order_status = $order->get_status();
		$is_refunded = ( 'refunded' === $order_status );

		// Calculate item commissions
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$commission_rate = get_post_meta( $product_id, 'team_commission', true );

			if ( ! $commission_rate ) {
				continue;
			}

			$line_total = $item->get_total();
			$item_commission = ( $line_total * $commission_rate ) / 100;

			$commission_data['items'][] = array(
				'product_id'      => $product_id,
				'product_name'    => $item->get_name(),
				'line_total'      => $line_total,
				'commission_rate' => $commission_rate,
				'commission'      => $item_commission,
			);

			$commission_data['total_commission'] += $item_commission;
		}

		// Handle refunded orders - no commission
		if ( $is_refunded ) {
			$commission_data['total_commission'] = 0;
		}

		// Get order date for salary type checking (check salary type AT order creation time)
		$order_date = $order->get_date_created();
		$order_date_str = $order_date ? $order_date->format( 'Y-m-d H:i:s' ) : current_time( 'mysql' );

		// Check if users were commission-eligible AT THE TIME the order was created
		// Commission-eligible = commission-based OR combined salary
		// Commission-blocked = ONLY fixed salary
		$agent_is_commission_based = $this->is_user_commission_based( $agent_id, $order_date_str );
		$processor_is_commission_based = $this->is_user_commission_based( $processor_id, $order_date_str );

		// Apply split logic with salary type awareness
		if ( $agent_id === $processor_id || ! $processor_id ) {
			// Same user or no processor
			if ( $agent_is_commission_based ) {
				// Commission-eligible user gets 100%
				$commission_data['agent_earnings'] = $commission_data['total_commission'];
				$commission_data['processor_earnings'] = 0;
			} else {
				// Fixed salary user gets 0% (commission vanishes)
				$commission_data['agent_earnings'] = 0;
				$commission_data['processor_earnings'] = 0;
			}
		} else {
			// Different users - apply salary-aware split (NO REDIRECTION)
			if ( $agent_is_commission_based && $processor_is_commission_based ) {
				// Both commission-eligible - normal split
				$commission_data['agent_earnings'] = ( $commission_data['total_commission'] * $agent_percentage ) / 100;
				$commission_data['processor_earnings'] = ( $commission_data['total_commission'] * $processor_percentage ) / 100;
			} elseif ( $agent_is_commission_based && ! $processor_is_commission_based ) {
				// Agent commission-eligible, processor fixed salary
				// Agent gets their share, processor gets 0 (their share vanishes)
				$commission_data['agent_earnings'] = ( $commission_data['total_commission'] * $agent_percentage ) / 100;
				$commission_data['processor_earnings'] = 0;
			} elseif ( ! $agent_is_commission_based && $processor_is_commission_based ) {
				// Agent fixed salary, processor commission-eligible
				// Agent gets 0 (their share vanishes), processor gets their share
				$commission_data['agent_earnings'] = 0;
				$commission_data['processor_earnings'] = ( $commission_data['total_commission'] * $processor_percentage ) / 100;
			} else {
				// Both fixed salary - both shares vanish
				$commission_data['agent_earnings'] = 0;
				$commission_data['processor_earnings'] = 0;
			}
		}

		return $commission_data;
	}

	/**
	 * Check if user is commission-based at a specific date (or currently if no date provided)
	 * 
	 * Commission-eligible: commission-based OR combined salary
	 * Commission-blocked: ONLY fixed salary
	 */
	private function is_user_commission_based( $user_id, $check_date = null ) {
		if ( ! $user_id ) {
			return false;
		}

		// If no date provided, check current status
		if ( ! $check_date ) {
			$is_fixed = (bool) get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
			
			// Only fixed salary blocks commission
			// Commission-based OR combined salary = commission eligible
			return ! $is_fixed;
		}

		// Check salary type at specific date using history
		$history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Find the salary type that was active at the check_date
		$active_type = null;
		
		foreach ( $history as $entry ) {
			$entry_date = strtotime( $entry['date'] );
			$check_timestamp = strtotime( $check_date );
			
			// If this history entry is before or at the check date, it's relevant
			if ( $entry_date <= $check_timestamp ) {
				$active_type = $entry['new_type'];
			} else {
				// History entries are chronological, so we can stop here
				break;
			}
		}

		// If no history found before check_date, use CURRENT salary type
		// This handles new employees who have no salary history yet
		if ( $active_type === null ) {
			$is_fixed = (bool) get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
			$active_type = $is_fixed ? 'fixed' : 'commission';
		}

		// Only fixed salary blocks commission
		// Commission-based OR combined salary = commission eligible
		return $active_type !== 'fixed';
	}

	/**
	 * Get commission calculation statuses from settings
	 */
	public static function get_commission_calculation_statuses() {
		$acf_fields = get_option( 'wc_team_payroll_acf_fields', array() );
		$statuses = isset( $acf_fields['commission_calculation_statuses'] ) ? $acf_fields['commission_calculation_statuses'] : array( 'completed', 'processing' );
		
		// Ensure it's an array and has default values
		if ( ! is_array( $statuses ) || empty( $statuses ) ) {
			$statuses = array( 'completed', 'processing' );
		}
		
		return $statuses;
	}

	/**
	 * Handle order updates
	 */
	public function on_order_updated( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$commission_statuses = self::get_commission_calculation_statuses();
		if ( ! in_array( $order->get_status(), $commission_statuses ) ) {
			return;
		}

		$this->calculate_order_commission( $order_id );
	}

	/**
	 * Handle order cancellation
	 */
	public function on_order_cancelled( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$order->delete_meta_data( '_commission_data' );
		$order->save();
	}

	/**
	 * Get commission data for order
	 */
	public function get_order_commission( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		return $order->get_meta( '_commission_data' );
	}

	/**
	 * Get user earnings for date range - FIXED DATE QUERY
	 */
	public function get_user_earnings( $user_id, $start_date = null, $end_date = null, $order_statuses = null ) {
		if ( ! $start_date ) {
			$start_date = date( 'Y-m-01' );
		}
		if ( ! $end_date ) {
			$end_date = date( 'Y-m-t' );
		}
		
		// Default order statuses if not provided
		if ( ! $order_statuses ) {
			$order_statuses = array( 'completed', 'processing', 'refunded' );
		}

		// Use proper WooCommerce date query format with time for full day coverage
		$args = array(
			'limit'  => -1,
			'status' => $order_statuses,
			'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
		);

		$orders = wc_get_orders( $args );

		$total_earnings = 0;
		$orders_data = array();

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			$user_earnings = 0;
			$role = null;
			$attributed_value = 0;

			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$user_earnings = $commission_data['agent_earnings'];
				$role = 'agent';
				// Get agent's attributed order value (their % of order total)
				$attributed_value = isset( $commission_data['agent_order_value'] ) ? $commission_data['agent_order_value'] : 0;
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$user_earnings = $commission_data['processor_earnings'];
				$role = 'processor';
				// Get processor's attributed order value (their % of order total)
				$attributed_value = isset( $commission_data['processor_order_value'] ) ? $commission_data['processor_order_value'] : 0;
			}

			if ( $user_earnings > 0 || $attributed_value > 0 ) {
				$total_earnings += $user_earnings;
				$orders_data[] = array(
					'order_id'  => $order->get_id(),
					'date'      => $order->get_date_created()->format( 'Y-m-d' ),
					'total'     => $order->get_total(),
					'commission' => $commission_data['total_commission'],
					'earnings'  => $user_earnings,
					'role'      => $role,
					'attributed_value' => $attributed_value, // User's attributed portion of commission
				);
			}
		}

		return array(
			'total_earnings' => $total_earnings,
			'orders' => $orders_data,
		);
	}

	/**
	 * Get total orders for a user (all orders, not just commission calculation statuses)
	 */
	public function get_user_total_orders( $user_id ) {
		$args = array(
			'limit'  => -1,
			'status' => 'any', // Get all orders regardless of status
		);

		$orders = wc_get_orders( $args );
		$count = 0;

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );

			if ( intval( $agent_id ) === intval( $user_id ) || intval( $processor_id ) === intval( $user_id ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Get total earnings for a user (all time)
	 * Includes commission + base salary (if applicable)
	 * Commission only calculated for orders with commission calculation statuses
	 */
	public function get_user_total_earnings( $user_id ) {
		// Get commission earnings from orders (only for commission calculation statuses)
		$commission_earnings = $this->get_user_commission_earnings( $user_id );

		// Get base salary earnings (from automatic salary system)
		$salary_earnings = get_user_meta( $user_id, '_wc_tp_total_earnings', true );
		if ( ! $salary_earnings ) {
			$salary_earnings = 0;
		}

		// Get bonus earnings (from achievement bonuses)
		$bonus_earnings = get_user_meta( $user_id, '_wc_tp_bonus_earnings', true );
		if ( ! $bonus_earnings ) {
			$bonus_earnings = 0;
		}

		return $commission_earnings + $salary_earnings + $bonus_earnings;
	}

	/**
	 * Get commission earnings only (no base salary)
	 * Only calculates commission for orders with commission calculation statuses
	 */
	public function get_user_commission_earnings( $user_id ) {
		// Get commission calculation statuses from settings
		$commission_statuses = self::get_commission_calculation_statuses();
		
		$args = array(
			'limit'  => -1,
			'status' => $commission_statuses, // Only get orders with commission calculation statuses
		);

		$orders = wc_get_orders( $args );
		$total_earnings = 0;

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$total_earnings += $commission_data['agent_earnings'];
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$total_earnings += $commission_data['processor_earnings'];
			}
		}

		return $total_earnings;
	}

	/**
	 * Get total paid for a user (all time)
	 */
	public function get_user_total_paid( $user_id ) {
		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			return 0;
		}

		$total_paid = 0;
		foreach ( $payments as $payment ) {
			$total_paid += floatval( $payment['amount'] );
		}

		return $total_paid;
	}
}
