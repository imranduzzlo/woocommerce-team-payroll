<?php
/**
 * Performance Tracker Class
 * Handles Goals, Achievements, and Baselines tracking for employees
 *
 * @package WooCommerce Team Payroll
 * @since 1.2.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Performance_Tracker {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize hooks
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// AJAX handlers
		add_action( 'wp_ajax_wc_tp_get_user_goal_progress', array( $this, 'ajax_get_user_goal_progress' ) );
		add_action( 'wp_ajax_wc_tp_get_user_achievements', array( $this, 'ajax_get_user_achievements' ) );
		add_action( 'wp_ajax_wc_tp_get_user_baselines', array( $this, 'ajax_get_user_baselines' ) );
		add_action( 'wp_ajax_wc_tp_recalculate_performance', array( $this, 'ajax_recalculate_performance' ) );

		// Cron jobs
		add_action( 'wc_tp_daily_baseline_update', array( $this, 'cron_update_baselines' ) );
		add_action( 'wc_tp_check_achievements', array( $this, 'cron_check_achievements' ) );
		add_action( 'wc_tp_finalize_period_goals', array( $this, 'cron_finalize_period_goals' ) );

		// Schedule cron jobs if not scheduled
		if ( ! wp_next_scheduled( 'wc_tp_daily_baseline_update' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_tp_daily_baseline_update' );
		}
		if ( ! wp_next_scheduled( 'wc_tp_check_achievements' ) ) {
			wp_schedule_event( time(), 'hourly', 'wc_tp_check_achievements' );
		}
		if ( ! wp_next_scheduled( 'wc_tp_finalize_period_goals' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_tp_finalize_period_goals' );
		}
	}

	/**
	 * Static method to initialize the class
	 */
	public static function init() {
		return new self();
	}

	// ============================================================================
	// HELPER FUNCTIONS - Data Calculation
	// ============================================================================

	/**
	 * Get attributed order total for user in a date range
	 * Uses same logic as Performance Metrics "Total Order Value"
	 *
	 * @param int $user_id User ID
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param string $role_filter Role filter (agent, processor, all)
	 * @param string $status_filter Status filter
	 * @return float Attributed order total
	 */
	private function get_attributed_order_total( $user_id, $start_date, $end_date, $role_filter = 'all', $status_filter = 'all' ) {
		// Get commission calculation statuses from settings using the correct method
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();

		// Prepare statuses to query
		$statuses_to_query = array();
		foreach ( $commission_statuses as $status ) {
			$statuses_to_query[] = 'wc-' . $status;
		}

		$attributed_total = 0;

		// Query orders where user is agent
		if ( $role_filter === 'all' || $role_filter === 'agent' ) {
			$agent_args = array(
				'limit'        => -1,
				'meta_key'     => '_primary_agent_id',
				'meta_value'   => $user_id,
				'status'       => $statuses_to_query,
				'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
				'return'       => 'ids',
			);

			$agent_orders = wc_get_orders( $agent_args );

			foreach ( $agent_orders as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}

				$commission_data = $order->get_meta( '_commission_data' );
				if ( $commission_data && isset( $commission_data['agent_order_value'] ) ) {
					$attributed_total += floatval( $commission_data['agent_order_value'] );
				}
			}
		}

		// Query orders where user is processor
		if ( $role_filter === 'all' || $role_filter === 'processor' ) {
			$processor_args = array(
				'limit'        => -1,
				'meta_key'     => '_processor_user_id',
				'meta_value'   => $user_id,
				'status'       => $statuses_to_query,
				'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
				'return'       => 'ids',
			);

			$processor_orders = wc_get_orders( $processor_args );

			foreach ( $processor_orders as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}

				$commission_data = $order->get_meta( '_commission_data' );
				if ( $commission_data && isset( $commission_data['processor_order_value'] ) ) {
					$attributed_total += floatval( $commission_data['processor_order_value'] );
				}
			}
		}

		return $attributed_total;
	}

	/**
	 * Get order count for user in a date range
	 *
	 * @param int $user_id User ID
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param string $role_filter Role filter (agent, processor, all)
	 * @return int Order count
	 */
	private function get_order_count( $user_id, $start_date, $end_date, $role_filter = 'all' ) {
		// Get commission calculation statuses from settings using the correct method
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();

		// Prepare statuses to query
		$statuses_to_query = array();
		foreach ( $commission_statuses as $status ) {
			$statuses_to_query[] = 'wc-' . $status;
		}

		$order_ids = array();

		// Query orders where user is agent
		if ( $role_filter === 'all' || $role_filter === 'agent' ) {
			$agent_args = array(
				'limit'        => -1,
				'meta_key'     => '_primary_agent_id',
				'meta_value'   => $user_id,
				'status'       => $statuses_to_query,
				'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
				'return'       => 'ids',
			);

			$agent_orders = wc_get_orders( $agent_args );
			$order_ids = array_merge( $order_ids, $agent_orders );
		}

		// Query orders where user is processor
		if ( $role_filter === 'all' || $role_filter === 'processor' ) {
			$processor_args = array(
				'limit'        => -1,
				'meta_key'     => '_processor_user_id',
				'meta_value'   => $user_id,
				'status'       => $statuses_to_query,
				'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
				'return'       => 'ids',
			);

			$processor_orders = wc_get_orders( $processor_args );
			$order_ids = array_merge( $order_ids, $processor_orders );
		}

		// Remove duplicates (if user is both agent and processor on same order)
		$order_ids = array_unique( $order_ids );

		return count( $order_ids );
	}

	/**
	 * Get average order value for user in a date range
	 *
	 * @param int $user_id User ID
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param string $role_filter Role filter (agent, processor, all)
	 * @return float Average order value
	 */
	private function get_average_order_value( $user_id, $start_date, $end_date, $role_filter = 'all' ) {
		$attributed_total = $this->get_attributed_order_total( $user_id, $start_date, $end_date, $role_filter );
		$order_count = $this->get_order_count( $user_id, $start_date, $end_date, $role_filter );

		if ( $order_count === 0 ) {
			return 0;
		}

		return $attributed_total / $order_count;
	}

	/**
	 * Get date range based on view mode
	 *
	 * @param string $view_mode View mode (current, last, last_3, last_6, last_12, ytd)
	 * @param string $period_type Period type (weekly, monthly, quarterly, yearly)
	 * @return array Array with 'start' and 'end' dates
	 */
	private function get_view_mode_dates( $view_mode, $period_type ) {
		$timezone = wp_timezone();
		$now = new DateTime( 'now', $timezone );

		switch ( $view_mode ) {
			case 'current':
				// Current period
				return $this->get_period_dates( $period_type );

			case 'last':
				// Last period
				if ( $period_type === 'weekly' ) {
					$start = clone $now;
					$start->modify( 'monday last week' );
					$end = clone $start;
					$end->modify( '+6 days' );
				} elseif ( $period_type === 'monthly' ) {
					$start = clone $now;
					$start->modify( 'first day of last month' );
					$end = clone $start;
					$end->modify( 'last day of this month' );
				} elseif ( $period_type === 'quarterly' ) {
					$month = (int) $now->format( 'n' );
					$current_quarter_start = ( ceil( $month / 3 ) - 1 ) * 3 + 1;
					$last_quarter_start = $current_quarter_start - 3;
					if ( $last_quarter_start < 1 ) {
						$last_quarter_start += 12;
						$year = (int) $now->format( 'Y' ) - 1;
					} else {
						$year = (int) $now->format( 'Y' );
					}
					$start = new DateTime( $year . '-' . str_pad( $last_quarter_start, 2, '0', STR_PAD_LEFT ) . '-01', $timezone );
					$end = clone $start;
					$end->modify( '+2 months' );
					$end->modify( 'last day of this month' );
				} else { // yearly
					$start = new DateTime( ( (int) $now->format( 'Y' ) - 1 ) . '-01-01', $timezone );
					$end = new DateTime( ( (int) $now->format( 'Y' ) - 1 ) . '-12-31', $timezone );
				}
				break;

			case 'last_3':
				// Last 3 periods
				if ( $period_type === 'weekly' ) {
					$start = clone $now;
					$start->modify( '-3 weeks monday' );
					$end = clone $now;
					$end->modify( 'sunday this week' );
				} elseif ( $period_type === 'monthly' ) {
					$start = clone $now;
					$start->modify( '-2 months first day of this month' );
					$end = clone $now;
					$end->modify( 'last day of this month' );
				} elseif ( $period_type === 'quarterly' ) {
					$start = clone $now;
					$start->modify( '-9 months first day of this month' );
					$end = clone $now;
					$end->modify( 'last day of this month' );
				} else { // yearly
					$start = new DateTime( ( (int) $now->format( 'Y' ) - 2 ) . '-01-01', $timezone );
					$end = new DateTime( $now->format( 'Y' ) . '-12-31', $timezone );
				}
				break;

			case 'last_6':
				// Last 6 periods
				if ( $period_type === 'weekly' ) {
					$start = clone $now;
					$start->modify( '-6 weeks monday' );
					$end = clone $now;
					$end->modify( 'sunday this week' );
				} elseif ( $period_type === 'monthly' ) {
					$start = clone $now;
					$start->modify( '-5 months first day of this month' );
					$end = clone $now;
					$end->modify( 'last day of this month' );
				} elseif ( $period_type === 'quarterly' ) {
					$start = clone $now;
					$start->modify( '-18 months first day of this month' );
					$end = clone $now;
					$end->modify( 'last day of this month' );
				} else { // yearly
					$start = new DateTime( ( (int) $now->format( 'Y' ) - 5 ) . '-01-01', $timezone );
					$end = new DateTime( $now->format( 'Y' ) . '-12-31', $timezone );
				}
				break;

			case 'last_12':
				// Last 12 periods
				if ( $period_type === 'weekly' ) {
					$start = clone $now;
					$start->modify( '-12 weeks monday' );
					$end = clone $now;
					$end->modify( 'sunday this week' );
				} elseif ( $period_type === 'monthly' ) {
					$start = clone $now;
					$start->modify( '-11 months first day of this month' );
					$end = clone $now;
					$end->modify( 'last day of this month' );
				} elseif ( $period_type === 'quarterly' ) {
					$start = clone $now;
					$start->modify( '-36 months first day of this month' );
					$end = clone $now;
					$end->modify( 'last day of this month' );
				} else { // yearly
					$start = new DateTime( ( (int) $now->format( 'Y' ) - 11 ) . '-01-01', $timezone );
					$end = new DateTime( $now->format( 'Y' ) . '-12-31', $timezone );
				}
				break;

			case 'ytd':
				// Year to date
				$start = new DateTime( $now->format( 'Y' ) . '-01-01', $timezone );
				$end = clone $now;
				break;

			default:
				// Default to current period
				return $this->get_period_dates( $period_type );
		}

		return array(
			'start' => $start->format( 'Y-m-d' ),
			'end'   => $end->format( 'Y-m-d' ),
			'period_id' => $start->format( 'Y-m' ),
		);
	}

	/**
	 * Get period dates based on period type
	 *
	 * @param string $period_type Period type (weekly, monthly, quarterly, yearly)
	 * @param string $date Optional date to calculate period for (default: today)
	 * @return array Array with 'start' and 'end' dates
	 */
	private function get_period_dates( $period_type, $date = null ) {
		$timezone = wp_timezone();
		$now = $date ? new DateTime( $date, $timezone ) : new DateTime( 'now', $timezone );

		switch ( $period_type ) {
			case 'weekly':
				$start = clone $now;
				$start->modify( 'monday this week' );
				$end = clone $start;
				$end->modify( '+6 days' );
				break;

			case 'monthly':
				$start = new DateTime( $now->format( 'Y-m-01' ), $timezone );
				$end = clone $start;
				$end->modify( 'last day of this month' );
				break;

			case 'quarterly':
				$month = (int) $now->format( 'n' );
				$quarter_start_month = ( ceil( $month / 3 ) - 1 ) * 3 + 1;
				$start = new DateTime( $now->format( 'Y' ) . '-' . str_pad( $quarter_start_month, 2, '0', STR_PAD_LEFT ) . '-01', $timezone );
				$end = clone $start;
				$end->modify( '+2 months' );
				$end->modify( 'last day of this month' );
				break;

			case 'yearly':
				$start = new DateTime( $now->format( 'Y' ) . '-01-01', $timezone );
				$end = new DateTime( $now->format( 'Y' ) . '-12-31', $timezone );
				break;

			default:
				$start = new DateTime( $now->format( 'Y-m-01' ), $timezone );
				$end = clone $start;
				$end->modify( 'last day of this month' );
		}

		return array(
			'start' => $start->format( 'Y-m-d' ),
			'end'   => $end->format( 'Y-m-d' ),
			'period_id' => $start->format( 'Y-m' ),
		);
	}

	// ============================================================================
	// GOALS TRACKING
	// ============================================================================

	/**
	 * Calculate and update current goal progress for a user
	 *
	 * @param int $user_id User ID
	 * @param string $view_mode View mode (current, last, last_3, last_6, last_12, ytd)
	 * @return array Goal progress data
	 */
	public function update_goal_progress( $user_id, $view_mode = 'current' ) {
		// Get user role
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return array();
		}

		$user_roles = $user->roles;
		$employee_role = '';

		// Find employee role (agent, processor, etc.)
		$all_roles = $this->get_employee_roles();
		
		foreach ( $user_roles as $role ) {
			if ( isset( $all_roles[ $role ] ) ) {
				$employee_role = $role;
				break;
			}
		}

		if ( empty( $employee_role ) ) {
			return array();
		}

		// Get goals configuration
		$goals_config = get_option( 'wc_tp_goals_config', array() );
		
		$period_type = isset( $goals_config['period'] ) ? $goals_config['period'] : 'monthly';
		$role_goals = isset( $goals_config['roles'][ $employee_role ] ) ? $goals_config['roles'][ $employee_role ] : array();

		if ( empty( $role_goals ) ) {
			return array();
		}

		// Get period dates based on view mode
		$period_dates = $this->get_view_mode_dates( $view_mode, $period_type );

		// Calculate current values
		$attributed_total = $this->get_attributed_order_total( $user_id, $period_dates['start'], $period_dates['end'] );
		$order_count = $this->get_order_count( $user_id, $period_dates['start'], $period_dates['end'] );
		$aov = $this->get_average_order_value( $user_id, $period_dates['start'], $period_dates['end'] );

		// Build progress data
		$progress_data = array(
			'period' => $period_dates['period_id'],
			'period_type' => $period_type,
			'period_start' => $period_dates['start'],
			'period_end' => $period_dates['end'],
			'view_mode' => $view_mode,
			'order_value' => $this->calculate_goal_status( $attributed_total, $role_goals['earnings'] ?? array() ),
			'orders' => $this->calculate_goal_status( $order_count, $role_goals['orders'] ?? array() ),
			'aov' => $this->calculate_goal_status( $aov, $role_goals['aov'] ?? array() ),
		);

		// Save to user meta only if current view
		if ( $view_mode === 'current' ) {
			update_user_meta( $user_id, '_wc_tp_current_goal_progress', $progress_data );
		}

		return $progress_data;
	}

	/**
	 * Calculate goal status for a metric
	 *
	 * @param float $current Current value
	 * @param array $goals Goal thresholds (minimum, target, stretch)
	 * @return array Status data
	 */
	private function calculate_goal_status( $current, $goals ) {
		$minimum = isset( $goals['minimum'] ) ? floatval( $goals['minimum'] ) : 0;
		$target = isset( $goals['target'] ) ? floatval( $goals['target'] ) : 0;
		$stretch = isset( $goals['stretch'] ) ? floatval( $goals['stretch'] ) : 0;

		$percentage = $target > 0 ? ( $current / $target ) * 100 : 0;

		// Determine status
		$status = 'not_started';
		if ( $current >= $stretch ) {
			$status = 'stretch_achieved';
		} elseif ( $current >= $target ) {
			$status = 'achieved';
		} elseif ( $current > 0 ) {
			$status = 'in_progress';
		}

		return array(
			'current' => $current,
			'minimum' => $minimum,
			'target' => $target,
			'stretch' => $stretch,
			'percentage' => round( $percentage, 2 ),
			'status' => $status,
		);
	}

	/**
	 * Finalize period goals and save to history
	 *
	 * @param int $user_id User ID
	 * @return bool Success
	 */
	public function finalize_period_goals( $user_id ) {
		// Get current progress
		$current_progress = get_user_meta( $user_id, '_wc_tp_current_goal_progress', true );

		if ( empty( $current_progress ) ) {
			return false;
		}

		// Get goal history
		$goal_history = get_user_meta( $user_id, '_wc_tp_goal_history', true );
		if ( ! is_array( $goal_history ) ) {
			$goal_history = array();
		}

		// Add achieved date for achieved goals
		$finalized_data = $current_progress;
		$finalized_data['finalized_date'] = current_time( 'Y-m-d H:i:s' );

		// Add to history
		array_unshift( $goal_history, $finalized_data );

		// Keep only last 12 periods
		$goal_history = array_slice( $goal_history, 0, 12 );

		// Save history
		update_user_meta( $user_id, '_wc_tp_goal_history', $goal_history );

		// Check if all performance metrics are achieved and send congratulations email
		$this->check_and_send_congratulations_email( $user_id );

		return true;
	}

	/**
	 * Check if all performance metrics are achieved and send congratulations email
	 *
	 * @param int $user_id User ID
	 * @return bool Success
	 */
	private function check_and_send_congratulations_email( $user_id ) {
		// Check if email was already sent for this period
		$last_email_sent = get_user_meta( $user_id, '_wc_tp_last_congratulations_email', true );
		$current_progress = get_user_meta( $user_id, '_wc_tp_current_goal_progress', true );
		
		if ( empty( $current_progress ) ) {
			return false;
		}

		$current_period = isset( $current_progress['period'] ) ? $current_progress['period'] : '';
		
		// Don't send if already sent for this period
		if ( $last_email_sent === $current_period ) {
			return false;
		}

		// Check if all goals are achieved
		$goals_achieved = false;
		if ( isset( $current_progress['order_value']['status'] ) && 
		     isset( $current_progress['orders']['status'] ) && 
		     isset( $current_progress['aov']['status'] ) ) {
			
			$order_value_achieved = in_array( $current_progress['order_value']['status'], array( 'achieved', 'stretch_achieved' ) );
			$orders_achieved = in_array( $current_progress['orders']['status'], array( 'achieved', 'stretch_achieved' ) );
			$aov_achieved = in_array( $current_progress['aov']['status'], array( 'achieved', 'stretch_achieved' ) );
			
			$goals_achieved = $order_value_achieved && $orders_achieved && $aov_achieved;
		}

		// Check if achievements are unlocked
		$achievement_stats = get_user_meta( $user_id, '_wc_tp_achievement_stats', true );
		$achievements_unlocked = isset( $achievement_stats['total_unlocked'] ) && $achievement_stats['total_unlocked'] > 0;

		// Check if baselines have sufficient data
		$baselines = get_user_meta( $user_id, '_wc_tp_current_baselines', true );
		$baselines_sufficient = ! empty( $baselines ) && isset( $baselines['order_value'] );

		// All three must be achieved
		if ( $goals_achieved && $achievements_unlocked && $baselines_sufficient ) {
			// Send congratulations email
			$this->send_congratulations_email( $user_id, $current_progress, $achievement_stats, $baselines );
			
			// Mark email as sent for this period
			update_user_meta( $user_id, '_wc_tp_last_congratulations_email', $current_period );
			
			return true;
		}

		return false;
	}

	/**
	 * Send congratulations email to employee
	 *
	 * @param int $user_id User ID
	 * @param array $goal_progress Goal progress data
	 * @param array $achievement_stats Achievement statistics
	 * @param array $baselines Baseline data
	 * @return bool Success
	 */
	private function send_congratulations_email( $user_id, $goal_progress, $achievement_stats, $baselines ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return false;
		}

		$to = $user->user_email;
		$subject = '🎉 Outstanding Performance Achievement - Congratulations!';

		// Get period information
		$period_type = isset( $goal_progress['period_type'] ) ? $goal_progress['period_type'] : 'monthly';
		$period_start = isset( $goal_progress['period_start'] ) ? date( 'F j, Y', strtotime( $goal_progress['period_start'] ) ) : '';
		$period_end = isset( $goal_progress['period_end'] ) ? date( 'F j, Y', strtotime( $goal_progress['period_end'] ) ) : '';

		// Get currency symbol
		$currency_symbol = html_entity_decode( get_woocommerce_currency_symbol() );

		// Build email content
		$message = $this->get_congratulations_email_template( 
			$user->display_name, 
			$period_type, 
			$period_start, 
			$period_end,
			$goal_progress,
			$achievement_stats,
			$baselines,
			$currency_symbol
		);

		// Email headers
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Povaly Group <noreply@povalygroup.com>',
		);

		// Send email
		$sent = wp_mail( $to, $subject, $message, $headers );

		return $sent;
	}

	/**
	 * Get congratulations email HTML template
	 *
	 * @param string $name Employee name
	 * @param string $period_type Period type
	 * @param string $period_start Period start date
	 * @param string $period_end Period end date
	 * @param array $goal_progress Goal progress data
	 * @param array $achievement_stats Achievement statistics
	 * @param array $baselines Baseline data
	 * @param string $currency_symbol Currency symbol
	 * @return string HTML email content
	 */
	private function get_congratulations_email_template( $name, $period_type, $period_start, $period_end, $goal_progress, $achievement_stats, $baselines, $currency_symbol ) {
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Outstanding Performance Achievement</title>
		</head>
		<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8;">
			<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f6f8; padding: 40px 20px;">
				<tr>
					<td align="center">
						<!-- Main Container -->
						<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); overflow: hidden;">
							
							<!-- Header with Povaly Group Branding -->
							<tr>
								<td style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 40px 30px; text-align: center;">
									<h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
										🎉 Outstanding Performance!
									</h1>
									<p style="margin: 10px 0 0 0; color: #ffffff; font-size: 16px; opacity: 0.95;">
										Congratulations on Your Achievement
									</p>
								</td>
							</tr>

							<!-- Greeting -->
							<tr>
								<td style="padding: 40px 30px 20px 30px;">
									<p style="margin: 0; font-size: 18px; color: #212B36; line-height: 1.6;">
										Dear <strong><?php echo esc_html( $name ); ?></strong>,
									</p>
								</td>
							</tr>

							<!-- Main Message -->
							<tr>
								<td style="padding: 0 30px 30px 30px;">
									<p style="margin: 0 0 20px 0; font-size: 16px; color: #637381; line-height: 1.8;">
										We are thrilled to inform you that you have achieved <strong style="color: #28a745;">exceptional performance</strong> for the period from <strong><?php echo esc_html( $period_start ); ?></strong> to <strong><?php echo esc_html( $period_end ); ?></strong>!
									</p>
									<p style="margin: 0; font-size: 16px; color: #637381; line-height: 1.8;">
										You have successfully completed <strong>all your goals</strong>, unlocked <strong>achievements</strong>, and maintained <strong>excellent performance baselines</strong>. This is a testament to your dedication, hard work, and commitment to excellence.
									</p>
								</td>
							</tr>

							<!-- Performance Summary -->
							<tr>
								<td style="padding: 0 30px 30px 30px;">
									<table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%); border: 2px solid #28a745; border-radius: 8px; padding: 20px;">
										<tr>
											<td>
												<h2 style="margin: 0 0 20px 0; font-size: 20px; color: #28a745; font-weight: 700;">
													📊 Your Performance Summary
												</h2>

												<!-- Goals Achieved -->
												<div style="margin-bottom: 20px;">
													<h3 style="margin: 0 0 10px 0; font-size: 16px; color: #212B36; font-weight: 600;">
														🎯 Goals Achieved (100%)
													</h3>
													<table width="100%" cellpadding="8" cellspacing="0" style="font-size: 14px; color: #637381;">
														<tr>
															<td style="padding: 8px 0;">Order Value:</td>
															<td align="right" style="padding: 8px 0; font-weight: 600; color: #28a745;">
																<?php echo esc_html( $currency_symbol . number_format( $goal_progress['order_value']['current'], 2 ) ); ?> / <?php echo esc_html( $currency_symbol . number_format( $goal_progress['order_value']['target'], 2 ) ); ?>
															</td>
														</tr>
														<tr>
															<td style="padding: 8px 0;">Orders Count:</td>
															<td align="right" style="padding: 8px 0; font-weight: 600; color: #28a745;">
																<?php echo esc_html( number_format( $goal_progress['orders']['current'], 0 ) ); ?> / <?php echo esc_html( number_format( $goal_progress['orders']['target'], 0 ) ); ?>
															</td>
														</tr>
														<tr>
															<td style="padding: 8px 0;">Avg Order Value:</td>
															<td align="right" style="padding: 8px 0; font-weight: 600; color: #28a745;">
																<?php echo esc_html( $currency_symbol . number_format( $goal_progress['aov']['current'], 2 ) ); ?> / <?php echo esc_html( $currency_symbol . number_format( $goal_progress['aov']['target'], 2 ) ); ?>
															</td>
														</tr>
													</table>
												</div>

												<!-- Achievements -->
												<div style="margin-bottom: 20px;">
													<h3 style="margin: 0 0 10px 0; font-size: 16px; color: #212B36; font-weight: 600;">
														🏆 Achievements Unlocked
													</h3>
													<p style="margin: 0; font-size: 14px; color: #637381;">
														<strong style="color: #28a745;"><?php echo esc_html( $achievement_stats['total_unlocked'] ); ?></strong> Total Achievements
														<span style="margin-left: 10px;">
															🥉 <?php echo esc_html( $achievement_stats['bronze_count'] ); ?>
															🥈 <?php echo esc_html( $achievement_stats['silver_count'] ); ?>
															🥇 <?php echo esc_html( $achievement_stats['gold_count'] ); ?>
														</span>
													</p>
												</div>

												<!-- Baselines -->
												<div>
													<h3 style="margin: 0 0 10px 0; font-size: 16px; color: #212B36; font-weight: 600;">
														📈 Performance Baselines
													</h3>
													<p style="margin: 0; font-size: 14px; color: #637381;">
														Your performance is 
														<strong style="color: #28a745; text-transform: capitalize;">
															<?php echo esc_html( $baselines['order_value']['trend'] ); ?>
														</strong>
														compared to your baseline metrics.
													</p>
												</div>
											</td>
										</tr>
									</table>
								</td>
							</tr>

							<!-- Closing Message -->
							<tr>
								<td style="padding: 0 30px 30px 30px;">
									<p style="margin: 0 0 20px 0; font-size: 16px; color: #637381; line-height: 1.8;">
										Your outstanding performance reflects the values and excellence that <strong>Povaly Group</strong> stands for. As part of the <strong>Vorosa Bajar</strong> family, you continue to set the standard for success.
									</p>
									<p style="margin: 0; font-size: 16px; color: #637381; line-height: 1.8;">
										Keep up the excellent work, and we look forward to celebrating more of your achievements in the future!
									</p>
								</td>
							</tr>

							<!-- Call to Action -->
							<tr>
								<td style="padding: 0 30px 40px 30px;" align="center">
									<a href="<?php echo esc_url( home_url( '/my-account/reports/' ) ); ?>" style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);">
										View Your Performance Dashboard
									</a>
								</td>
							</tr>

							<!-- Footer -->
							<tr>
								<td style="background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5eaf0;">
									<p style="margin: 0 0 10px 0; font-size: 16px; color: #212B36; font-weight: 600;">
										Best Regards,
									</p>
									<p style="margin: 0 0 5px 0; font-size: 18px; color: #28a745; font-weight: 700;">
										Povaly Group
									</p>
									<p style="margin: 0 0 20px 0; font-size: 14px; color: #637381;">
										<em>Vorosa Bajar - A Povaly Group Product</em>
									</p>
									<p style="margin: 0; font-size: 12px; color: #919EAB; line-height: 1.6;">
										This is an automated message from your performance tracking system.<br>
										Please do not reply to this email.
									</p>
								</td>
							</tr>

						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get employee roles from settings
	 *
	 * @return array Employee roles (role_key => role_name)
	 */
	private function get_employee_roles() {
		// Get employee roles from settings (simple array of role keys)
		$employee_role_keys = get_option( 'wc_tp_employee_roles', array( 'shop_employee' ) );
		
		if ( ! is_array( $employee_role_keys ) ) {
			$employee_role_keys = array( 'shop_employee' );
		}

		// Get WordPress roles to get display names
		global $wp_roles;
		$all_roles = isset( $wp_roles ) && isset( $wp_roles->roles ) ? $wp_roles->roles : array();

		$roles = array();
		foreach ( $employee_role_keys as $role_key ) {
			$role_name = isset( $all_roles[ $role_key ]['name'] ) ? $all_roles[ $role_key ]['name'] : ucfirst( str_replace( '_', ' ', $role_key ) );
			$roles[ $role_key ] = $role_name;
		}

		return $roles;
	}

	// ============================================================================
	// ACHIEVEMENTS TRACKING
	// ============================================================================

	/**
	 * Check and update achievements for a user
	 *
	 * @param int $user_id User ID
	 * @return array Updated achievements data
	 */
	public function update_achievements( $user_id ) {
		// Get user role
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return array();
		}

		$user_roles = $user->roles;
		$employee_role = '';

		// Find employee role
		$all_roles = $this->get_employee_roles();
		foreach ( $user_roles as $role ) {
			if ( isset( $all_roles[ $role ] ) ) {
				$employee_role = $role;
				break;
			}
		}

		if ( empty( $employee_role ) ) {
			return array();
		}

		// Get achievements configuration
		$achievements_config = get_option( 'wc_tp_achievements_config', array() );
		
		$role_achievements = isset( $achievements_config['roles'][ $employee_role ] ) ? $achievements_config['roles'][ $employee_role ] : array();

		if ( empty( $role_achievements ) ) {
			return array();
		}

		// Get current unlocked achievements
		$unlocked_achievements = get_user_meta( $user_id, '_wc_tp_unlocked_achievements', true );
		if ( ! is_array( $unlocked_achievements ) ) {
			$unlocked_achievements = array();
		}

		// Calculate all-time totals
		$all_time_order_value = $this->get_attributed_order_total( $user_id, '2000-01-01', date( 'Y-m-d' ) );
		$all_time_orders = $this->get_order_count( $user_id, '2000-01-01', date( 'Y-m-d' ) );
		$all_time_aov = $this->get_average_order_value( $user_id, '2000-01-01', date( 'Y-m-d' ) );

		$newly_unlocked = array();

		// Check each achievement
		foreach ( $role_achievements as $achievement_key => $achievement_data ) {
			$threshold = isset( $achievement_data['threshold'] ) ? floatval( $achievement_data['threshold'] ) : 0;
			$tier = isset( $achievement_data['tier'] ) ? $achievement_data['tier'] : 'bronze';

			// Determine which metric to check
			$current_value = 0;
			if ( strpos( $achievement_key, 'earnings' ) !== false || strpos( $achievement_key, 'order_value' ) !== false ) {
				$current_value = $all_time_order_value;
			} elseif ( strpos( $achievement_key, 'orders' ) !== false ) {
				$current_value = $all_time_orders;
			} elseif ( strpos( $achievement_key, 'aov' ) !== false ) {
				$current_value = $all_time_aov;
			}

			// Check if already unlocked
			$is_unlocked = isset( $unlocked_achievements[ $achievement_key ] ) && $unlocked_achievements[ $achievement_key ]['unlocked'] === true;

			if ( ! $is_unlocked && $current_value >= $threshold ) {
				// Achievement unlocked!
				$unlocked_achievements[ $achievement_key ] = array(
					'unlocked' => true,
					'unlocked_date' => current_time( 'Y-m-d H:i:s' ),
					'value_at_unlock' => $current_value,
					'threshold' => $threshold,
					'tier' => $tier,
				);

				$newly_unlocked[] = $achievement_key;
			} elseif ( ! $is_unlocked ) {
				// Not yet unlocked, track progress
				$unlocked_achievements[ $achievement_key ] = array(
					'unlocked' => false,
					'current_progress' => $current_value,
					'threshold' => $threshold,
					'percentage' => $threshold > 0 ? round( ( $current_value / $threshold ) * 100, 2 ) : 0,
					'tier' => $tier,
				);
			}
		}

		// Save updated achievements
		update_user_meta( $user_id, '_wc_tp_unlocked_achievements', $unlocked_achievements );

		// Update achievement statistics
		$this->update_achievement_stats( $user_id, $unlocked_achievements );

		// Send notifications for newly unlocked achievements
		if ( ! empty( $newly_unlocked ) && isset( $achievements_config['notification'] ) && $achievements_config['notification'] ) {
			$this->send_achievement_notifications( $user_id, $newly_unlocked, $role_achievements );
		}

		return $unlocked_achievements;
	}

	/**
	 * Update achievement statistics
	 *
	 * @param int $user_id User ID
	 * @param array $unlocked_achievements Unlocked achievements data
	 */
	private function update_achievement_stats( $user_id, $unlocked_achievements ) {
		$bronze_count = 0;
		$silver_count = 0;
		$gold_count = 0;
		$last_unlocked = null;
		$next_achievement = null;
		$min_percentage = 100;

		foreach ( $unlocked_achievements as $key => $data ) {
			if ( isset( $data['unlocked'] ) && $data['unlocked'] === true ) {
				// Count by tier
				if ( isset( $data['tier'] ) ) {
					switch ( $data['tier'] ) {
						case 'bronze':
							$bronze_count++;
							break;
						case 'silver':
							$silver_count++;
							break;
						case 'gold':
							$gold_count++;
							break;
					}
				}

				// Track last unlocked
				if ( ! $last_unlocked || ( isset( $data['unlocked_date'] ) && $data['unlocked_date'] > $last_unlocked['date'] ) ) {
					$last_unlocked = array(
						'achievement' => $key,
						'date' => $data['unlocked_date'] ?? '',
						'value' => $data['value_at_unlock'] ?? 0,
					);
				}
			} else {
				// Track next achievement (closest to completion)
				$percentage = isset( $data['percentage'] ) ? $data['percentage'] : 0;
				if ( $percentage < 100 && $percentage > 0 && ( ! $next_achievement || $percentage > $min_percentage ) ) {
					$next_achievement = array(
						'achievement' => $key,
						'threshold' => $data['threshold'] ?? 0,
						'current' => $data['current_progress'] ?? 0,
						'remaining' => ( $data['threshold'] ?? 0 ) - ( $data['current_progress'] ?? 0 ),
						'percentage' => $percentage,
					);
					$min_percentage = $percentage;
				}
			}
		}

		$stats = array(
			'total_unlocked' => $bronze_count + $silver_count + $gold_count,
			'bronze_count' => $bronze_count,
			'silver_count' => $silver_count,
			'gold_count' => $gold_count,
			'last_unlocked' => $last_unlocked,
			'next_achievement' => $next_achievement,
		);

		update_user_meta( $user_id, '_wc_tp_achievement_stats', $stats );
	}

	// ============================================================================
	// MONTHLY ACHIEVEMENT SYSTEM (Phase 1)
	// ============================================================================

	/**
	 * Update monthly achievements for a user
	 * This calculates achievements based on CURRENT MONTH performance only
	 *
	 * @param int $user_id User ID
	 * @return array Monthly achievement data
	 */
	public function update_monthly_achievements( $user_id ) {
		// Get user role
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return array();
		}

		$user_roles = $user->roles;
		$employee_role = '';

		// Find employee role
		$all_roles = $this->get_employee_roles();
		foreach ( $user_roles as $role ) {
			if ( isset( $all_roles[ $role ] ) ) {
				$employee_role = $role;
				break;
			}
		}

		if ( empty( $employee_role ) ) {
			return array();
		}

		// Get achievements configuration
		$achievements_config = get_option( 'wc_tp_achievements_config', array() );
		$role_achievements = isset( $achievements_config['roles'][ $employee_role ] ) ? $achievements_config['roles'][ $employee_role ] : array();

		if ( empty( $role_achievements ) ) {
			return array();
		}

		// Get current month date range
		$timezone = wp_timezone();
		$now = new DateTime( 'now', $timezone );
		$month_start = $now->format( 'Y-m-01' );
		$month_end = $now->format( 'Y-m-t' );
		$period_id = $now->format( 'Y-m' );

		// Calculate current month totals
		$month_order_value = $this->get_attributed_order_total( $user_id, $month_start, $month_end );
		$month_orders = $this->get_order_count( $user_id, $month_start, $month_end );
		$month_aov = $this->get_average_order_value( $user_id, $month_start, $month_end );

		// Determine achievements for each category
		$earnings_tier = $this->determine_achievement_tier( $month_order_value, $role_achievements, 'earnings' );
		$orders_tier = $this->determine_achievement_tier( $month_orders, $role_achievements, 'orders' );
		$aov_tier = $this->determine_achievement_tier( $month_aov, $role_achievements, 'aov' );

		// Count achievements by tier
		$tier_counts = array( 'bronze' => 0, 'silver' => 0, 'gold' => 0 );
		foreach ( array( $earnings_tier, $orders_tier, $aov_tier ) as $tier ) {
			if ( ! empty( $tier ) ) {
				$tier_counts[ $tier ]++;
			}
		}

		// Determine highest tier achieved this month
		$highest_tier = '';
		if ( $tier_counts['gold'] > 0 ) {
			$highest_tier = 'gold';
		} elseif ( $tier_counts['silver'] > 0 ) {
			$highest_tier = 'silver';
		} elseif ( $tier_counts['bronze'] > 0 ) {
			$highest_tier = 'bronze';
		}

		// Build monthly achievement data
		$monthly_data = array(
			'period' => $period_id,
			'month_start' => $month_start,
			'month_end' => $month_end,
			'earnings' => $month_order_value,
			'orders' => $month_orders,
			'aov' => $month_aov,
			'earnings_tier' => $earnings_tier,
			'orders_tier' => $orders_tier,
			'aov_tier' => $aov_tier,
			'bronze_count' => $tier_counts['bronze'],
			'silver_count' => $tier_counts['silver'],
			'gold_count' => $tier_counts['gold'],
			'highest_tier' => $highest_tier,
			'updated_at' => current_time( 'Y-m-d H:i:s' ),
		);

		// Save current month data
		update_user_meta( $user_id, '_wc_tp_monthly_achievements', $monthly_data );

		// Update achievement stats for compatibility with existing code
		$stats = array(
			'total_unlocked' => $tier_counts['bronze'] + $tier_counts['silver'] + $tier_counts['gold'],
			'bronze_count' => $tier_counts['bronze'],
			'silver_count' => $tier_counts['silver'],
			'gold_count' => $tier_counts['gold'],
			'last_unlocked' => null,
			'next_achievement' => null,
		);
		update_user_meta( $user_id, '_wc_tp_achievement_stats', $stats );

		// Phase 2: Update badge streaks
		$this->update_badge_streaks( $user_id, $highest_tier, $employee_role );

		return $monthly_data;
	}

	/**
	 * Determine achievement tier for a metric
	 *
	 * @param float $value Current value
	 * @param array $role_achievements Role achievements configuration
	 * @param string $category Category (earnings, orders, aov)
	 * @return string Tier (bronze, silver, gold) or empty string
	 */
	private function determine_achievement_tier( $value, $role_achievements, $category ) {
		$tiers = array( 'gold', 'silver', 'bronze' ); // Check from highest to lowest
		
		foreach ( $tiers as $tier ) {
			$key = $category . '_' . $tier;
			if ( isset( $role_achievements[ $key ] ) ) {
				$threshold = floatval( $role_achievements[ $key ]['threshold'] ?? 0 );
				if ( $value >= $threshold ) {
					return $tier;
				}
			}
		}
		
		return ''; // No achievement
	}

	/**
	 * Finalize monthly achievements and save to history
	 * Called at the end of each month
	 *
	 * @param int $user_id User ID
	 * @return bool Success
	 */
	public function finalize_monthly_achievements( $user_id ) {
		// Get current month data
		$monthly_data = get_user_meta( $user_id, '_wc_tp_monthly_achievements', true );
		
		if ( empty( $monthly_data ) ) {
			return false;
		}

		// Get achievement history
		$history = get_user_meta( $user_id, '_wc_tp_achievement_history', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		// Add finalized date
		$monthly_data['finalized_at'] = current_time( 'Y-m-d H:i:s' );

		// Add to history (most recent first)
		array_unshift( $history, $monthly_data );

		// Keep only last 24 months
		$history = array_slice( $history, 0, 24 );

		// Save history
		update_user_meta( $user_id, '_wc_tp_achievement_history', $history );

		return true;
	}

	/**
	 * Get monthly achievement history for a user
	 *
	 * @param int $user_id User ID
	 * @param int $months Number of months to retrieve (default: 12)
	 * @return array Achievement history
	 */
	public function get_monthly_achievement_history( $user_id, $months = 12 ) {
		$history = get_user_meta( $user_id, '_wc_tp_achievement_history', true );
		
		if ( ! is_array( $history ) ) {
			return array();
		}

		// Return requested number of months
		return array_slice( $history, 0, $months );
	}

	// ============================================================================
	// BADGE STREAK SYSTEM (Phase 2)
	// ============================================================================

	/**
	 * Update badge streaks for a user
	 * Tracks consecutive months at same badge level
	 *
	 * @param int $user_id User ID
	 * @param string $current_tier Current month's highest tier
	 * @param string $employee_role Employee role
	 */
	private function update_badge_streaks( $user_id, $current_tier, $employee_role ) {
		// Get current streaks
		$streaks = get_user_meta( $user_id, '_wc_tp_badge_streaks', true );
		if ( ! is_array( $streaks ) ) {
			$streaks = array(
				'bronze' => array( 'count' => 0, 'last_month' => '' ),
				'silver' => array( 'count' => 0, 'last_month' => '' ),
				'gold' => array( 'count' => 0, 'last_month' => '' ),
			);
		}

		$current_period = date( 'Y-m' );

		// Update streak for current tier
		if ( ! empty( $current_tier ) ) {
			// Check if this is a continuation of the streak
			$last_month = isset( $streaks[ $current_tier ]['last_month'] ) ? $streaks[ $current_tier ]['last_month'] : '';
			
			if ( $last_month === $this->get_previous_month( $current_period ) ) {
				// Continuing streak
				$streaks[ $current_tier ]['count']++;
			} else {
				// New streak
				$streaks[ $current_tier ]['count'] = 1;
			}
			
			$streaks[ $current_tier ]['last_month'] = $current_period;

			// Reset other tiers
			foreach ( array( 'bronze', 'silver', 'gold' ) as $tier ) {
				if ( $tier !== $current_tier ) {
					$streaks[ $tier ]['count'] = 0;
					$streaks[ $tier ]['last_month'] = '';
				}
			}

			// Check for bonus eligibility
			$this->check_streak_bonus_eligibility( $user_id, $current_tier, $streaks[ $current_tier ]['count'], $employee_role );
		} else {
			// No achievement this month, reset all streaks
			foreach ( array( 'bronze', 'silver', 'gold' ) as $tier ) {
				$streaks[ $tier ]['count'] = 0;
				$streaks[ $tier ]['last_month'] = '';
			}
		}

		// Save updated streaks
		update_user_meta( $user_id, '_wc_tp_badge_streaks', $streaks );
	}

	/**
	 * Get previous month in Y-m format
	 *
	 * @param string $period Current period (Y-m)
	 * @return string Previous month (Y-m)
	 */
	private function get_previous_month( $period ) {
		$date = DateTime::createFromFormat( 'Y-m', $period );
		if ( ! $date ) {
			return '';
		}
		$date->modify( '-1 month' );
		return $date->format( 'Y-m' );
	}

	/**
	 * Check if user is eligible for streak bonus
	 *
	 * @param int $user_id User ID
	 * @param string $tier Badge tier
	 * @param int $streak_count Current streak count
	 * @param string $employee_role Employee role
	 */
	private function check_streak_bonus_eligibility( $user_id, $tier, $streak_count, $employee_role ) {
		// Get bonus configuration
		$bonus_config = get_option( 'wc_tp_achievement_bonuses', array() );
		
		if ( empty( $bonus_config ) || ! isset( $bonus_config[ $employee_role ] ) ) {
			return;
		}

		$role_bonuses = $bonus_config[ $employee_role ];

		// Check each bonus rule
		foreach ( $role_bonuses as $index => $bonus_rule ) {
			if ( empty( $bonus_rule ) ) {
				continue;
			}

			$rule_tier = isset( $bonus_rule['tier'] ) ? $bonus_rule['tier'] : '';
			$required_months = isset( $bonus_rule['months'] ) ? intval( $bonus_rule['months'] ) : 0;
			$repeatable = isset( $bonus_rule['repeatable'] ) && $bonus_rule['repeatable'];

			// Check if this rule matches
			if ( $rule_tier === $tier && $streak_count === $required_months ) {
				// Check if already awarded (for non-repeatable bonuses)
				if ( ! $repeatable ) {
					$awarded_bonuses = get_user_meta( $user_id, '_wc_tp_awarded_bonuses', true );
					if ( ! is_array( $awarded_bonuses ) ) {
						$awarded_bonuses = array();
					}

					$bonus_key = $employee_role . '_' . $tier . '_' . $required_months;
					if ( in_array( $bonus_key, $awarded_bonuses ) ) {
						continue; // Already awarded, skip
					}
				}

				// Award the bonus!
				$this->award_streak_bonus( $user_id, $bonus_rule, $tier, $streak_count, $employee_role );
			}
		}
	}

	/**
	 * Award streak bonus to user
	 *
	 * @param int $user_id User ID
	 * @param array $bonus_rule Bonus rule configuration
	 * @param string $tier Badge tier
	 * @param int $streak_count Streak count
	 * @param string $employee_role Employee role
	 */
	private function award_streak_bonus( $user_id, $bonus_rule, $tier, $streak_count, $employee_role ) {
		$rule_id = isset( $bonus_rule['rule_id'] ) ? intval( $bonus_rule['rule_id'] ) : 0;
		$bonus_type = isset( $bonus_rule['bonus_type'] ) ? $bonus_rule['bonus_type'] : 'money';
		$bonus_amount = isset( $bonus_rule['bonus_amount'] ) ? floatval( $bonus_rule['bonus_amount'] ) : 0;
		$bonus_description = isset( $bonus_rule['bonus_description'] ) ? $bonus_rule['bonus_description'] : '';
		$repeatable = isset( $bonus_rule['repeatable'] ) && $bonus_rule['repeatable'];

		// Generate secret code for physical/other bonuses
		$secret_code = '';
		if ( $bonus_type !== 'money' ) {
			$secret_code = strtoupper( substr( md5( $user_id . $rule_id . time() . wp_rand() ), 0, 8 ) );
		}

		// Create achieved bonus record (lifetime storage)
		$achieved_bonus = array(
			'id' => time() . '_' . $user_id . '_' . $rule_id,
			'rule_id' => $rule_id,
			'tier' => $tier,
			'streak_count' => $streak_count,
			'bonus_type' => $bonus_type,
			'bonus_amount' => $bonus_amount,
			'bonus_description' => $bonus_description,
			'achieved_date' => current_time( 'Y-m-d H:i:s' ),
			'status' => 'pending', // pending, claimed
			'secret_code' => $secret_code, // For physical bonuses
			'claimed_date' => null,
			'claimed_by_user' => false,
		);

		// Save to achieved bonuses (lifetime storage)
		$achieved_bonuses = get_user_meta( $user_id, '_wc_tp_achieved_bonuses', true );
		if ( ! is_array( $achieved_bonuses ) ) {
			$achieved_bonuses = array();
		}
		array_unshift( $achieved_bonuses, $achieved_bonus );
		update_user_meta( $user_id, '_wc_tp_achieved_bonuses', $achieved_bonuses );

		// Create bonus history record (for display purposes)
		$bonus_history = get_user_meta( $user_id, '_wc_tp_bonus_history', true );
		if ( ! is_array( $bonus_history ) ) {
			$bonus_history = array();
		}
		
		$history_record = array(
			'user_id' => $user_id,
			'tier' => $tier,
			'streak_count' => $streak_count,
			'bonus_type' => $bonus_type,
			'bonus_amount' => $bonus_amount,
			'bonus_description' => $bonus_description,
			'awarded_date' => current_time( 'Y-m-d H:i:s' ),
			'period' => date( 'Y-m' ),
			'repeatable' => $repeatable,
		);
		
		array_unshift( $bonus_history, $history_record );
		$bonus_history = array_slice( $bonus_history, 0, 50 ); // Keep last 50
		update_user_meta( $user_id, '_wc_tp_bonus_history', $bonus_history );

		// Mark as awarded (for non-repeatable)
		if ( ! $repeatable ) {
			$awarded_bonuses = get_user_meta( $user_id, '_wc_tp_awarded_bonuses', true );
			if ( ! is_array( $awarded_bonuses ) ) {
				$awarded_bonuses = array();
			}
			$bonus_key = $employee_role . '_' . $tier . '_' . $streak_count . '_' . $rule_id;
			$awarded_bonuses[] = $bonus_key;
			update_user_meta( $user_id, '_wc_tp_awarded_bonuses', $awarded_bonuses );
		}

		// Send notification email
		$this->send_bonus_notification_email( $user_id, $history_record );
	}

	/**
	 * Add money bonus to user's earnings
	 *
	 * @param int $user_id User ID
	 * @param float $amount Bonus amount
	 * @param string $tier Badge tier
	 * @param int $streak_count Streak count
	 */
	private function add_bonus_to_earnings( $user_id, $amount, $tier, $streak_count ) {
		// Get current bonus earnings
		$bonus_earnings = get_user_meta( $user_id, '_wc_tp_bonus_earnings', true );
		if ( ! $bonus_earnings ) {
			$bonus_earnings = 0;
		}
		$bonus_earnings += $amount;
		update_user_meta( $user_id, '_wc_tp_bonus_earnings', $bonus_earnings );

		// Add to payment history as bonus
		$payment_history = get_user_meta( $user_id, '_wc_tp_payment_history', true );
		if ( ! is_array( $payment_history ) ) {
			$payment_history = array();
		}

		$payment_record = array(
			'amount' => $amount,
			'date' => current_time( 'Y-m-d H:i:s' ),
			'method' => 'Achievement Bonus',
			'note' => sprintf( 
				__( '%s Badge Streak Bonus (%d months)', 'wc-team-payroll' ),
				ucfirst( $tier ),
				$streak_count
			),
			'type' => 'bonus',
			'added_by' => 0, // System
		);

		array_unshift( $payment_history, $payment_record );
		update_user_meta( $user_id, '_wc_tp_payment_history', $payment_history );
	}

	/**
	 * Send bonus notification email
	 *
	 * @param int $user_id User ID
	 * @param array $bonus_record Bonus record
	 */
	private function send_bonus_notification_email( $user_id, $bonus_record ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		$tier = ucfirst( $bonus_record['tier'] );
		$streak_count = $bonus_record['streak_count'];
		$bonus_type = $bonus_record['bonus_type'];
		$bonus_amount = $bonus_record['bonus_amount'];
		$bonus_description = $bonus_record['bonus_description'];

		$tier_emojis = array(
			'gold' => '🥇',
			'silver' => '🥈',
			'bronze' => '🥉',
		);
		$emoji = isset( $tier_emojis[ $bonus_record['tier'] ] ) ? $tier_emojis[ $bonus_record['tier'] ] : '🏆';

		$subject = sprintf(
			__( '%s Congratulations! Streak Bonus Unlocked!', 'wc-team-payroll' ),
			$emoji
		);

		$currency_symbol = get_woocommerce_currency_symbol();

		$message = $this->get_bonus_email_template(
			$user->display_name,
			$tier,
			$streak_count,
			$bonus_type,
			$bonus_amount,
			$bonus_description,
			$currency_symbol,
			$emoji
		);

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $user->user_email, $subject, $message, $headers );
	}

	/**
	 * Send physical bonus secret code email (STEP 9)
	 *
	 * @param int $user_id User ID
	 * @param array $bonus Achieved bonus record
	 */
	private function send_physical_bonus_email( $user_id, $bonus ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		$tier = ucfirst( $bonus['tier'] );
		$secret_code = $bonus['secret_code'];
		$bonus_description = $bonus['bonus_description'];

		$tier_emojis = array(
			'gold' => '🥇',
			'silver' => '🥈',
			'bronze' => '🥉',
		);
		$emoji = isset( $tier_emojis[ $bonus['tier'] ] ) ? $tier_emojis[ $bonus['tier'] ] : '🏆';

		$subject = sprintf(
			__( '%s Your Physical Bonus - Secret Code Inside', 'wc-team-payroll' ),
			$emoji
		);

		$message = $this->get_physical_bonus_email_template(
			$user->display_name,
			$tier,
			$bonus_description,
			$secret_code,
			$emoji
		);

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $user->user_email, $subject, $message, $headers );

		// Also send to admin
		$admin_email = get_option( 'admin_email' );
		if ( $admin_email ) {
			$admin_subject = sprintf(
				__( 'Physical Bonus Submitted to %s - Secret Code: %s', 'wc-team-payroll' ),
				$user->display_name,
				$secret_code
			);

			$admin_message = $this->get_admin_physical_bonus_email_template(
				$user->display_name,
				$tier,
				$bonus_description,
				$secret_code,
				$emoji
			);

			wp_mail( $admin_email, $admin_subject, $admin_message, $headers );
		}
	}

	/**
	 * Public wrapper for sending physical bonus email (for AJAX calls)
	 *
	 * @param int $user_id User ID
	 * @param array $bonus Achieved bonus record
	 */
	public function send_physical_bonus_email_public( $user_id, $bonus ) {
		$this->send_physical_bonus_email( $user_id, $bonus );
	}

	/**
	 * Get bonus notification email template
	 *
	 * @param string $name User name
	 * @param string $tier Badge tier
	 * @param int $streak_count Streak count
	 * @param string $bonus_type Bonus type
	 * @param float $bonus_amount Bonus amount
	 * @param string $bonus_description Bonus description
	 * @param string $currency_symbol Currency symbol
	 * @param string $emoji Tier emoji
	 * @return string HTML email content
	 */
	private function get_bonus_email_template( $name, $tier, $streak_count, $bonus_type, $bonus_amount, $bonus_description, $currency_symbol, $emoji ) {
		$tier_colors = array(
			'Gold' => '#FFD700',
			'Silver' => '#C0C0C0',
			'Bronze' => '#CD7F32',
		);
		$color = isset( $tier_colors[ $tier ] ) ? $tier_colors[ $tier ] : '#FFD700';

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
		</head>
		<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
			<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
				<tr>
					<td align="center">
						<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
							<!-- Header -->
							<tr>
								<td style="background: linear-gradient(135deg, <?php echo esc_attr( $color ); ?> 0%, <?php echo esc_attr( $color ); ?>CC 100%); padding: 40px 20px; text-align: center;">
									<h1 style="margin: 0; color: #ffffff; font-size: 36px; font-weight: bold;">
										<?php echo esc_html( $emoji ); ?> BONUS UNLOCKED!
									</h1>
									<p style="margin: 10px 0 0 0; color: #ffffff; font-size: 20px; font-weight: bold;">
										<?php echo esc_html( $tier ); ?> Badge Streak Bonus
									</p>
								</td>
							</tr>
							
							<!-- Content -->
							<tr>
								<td style="padding: 40px 30px;">
									<p style="margin: 0 0 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
										Dear <strong><?php echo esc_html( $name ); ?></strong>,
									</p>
									<p style="margin: 0 0 30px 0; font-size: 18px; color: #333333; line-height: 1.6;">
										🎉 <strong>Congratulations!</strong> You've maintained your <strong><?php echo esc_html( $tier ); ?></strong> badge for <strong><?php echo esc_html( $streak_count ); ?> consecutive months</strong>!
									</p>
									
									<!-- Bonus Details -->
									<table width="100%" cellpadding="20" cellspacing="0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px; margin: 30px 0; border: 2px solid <?php echo esc_attr( $color ); ?>;">
										<tr>
											<td align="center">
												<h2 style="margin: 0 0 15px 0; color: <?php echo esc_attr( $color ); ?>; font-size: 24px;">
													Your Bonus Reward
												</h2>
												<?php if ( $bonus_type === 'money' ) : ?>
													<p style="margin: 0; font-size: 48px; font-weight: bold; color: #28a745;">
														<?php echo esc_html( $currency_symbol . number_format( $bonus_amount, 2 ) ); ?>
													</p>
													<p style="margin: 10px 0 0 0; font-size: 14px; color: #6c757d;">
														This bonus has been added to your earnings automatically.
													</p>
												<?php elseif ( $bonus_type === 'reward' ) : ?>
													<p style="margin: 0; font-size: 28px; font-weight: bold; color: #007bff;">
														<?php echo esc_html( $bonus_description ); ?>
													</p>
													<p style="margin: 10px 0 0 0; font-size: 14px; color: #6c757d;">
														Please contact management to claim your reward.
													</p>
												<?php else : ?>
													<p style="margin: 0; font-size: 20px; font-weight: bold; color: #6f42c1;">
														<?php echo esc_html( $bonus_description ); ?>
													</p>
												<?php endif; ?>
											</td>
										</tr>
									</table>
									
									<p style="margin: 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
										Your consistent performance is truly outstanding! Keep up the excellent work to earn more bonuses.
									</p>
									
									<p style="margin: 20px 0 0 0; font-size: 14px; color: #6c757d; line-height: 1.6;">
										Best regards,<br>
										<strong>Povaly Group Team</strong>
									</p>
								</td>
							</tr>
							
							<!-- Footer -->
							<tr>
								<td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6;">
									<p style="margin: 0; font-size: 12px; color: #6c757d;">
										This is an automated bonus notification from your performance tracking system.
									</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get physical bonus email template for employee (STEP 9)
	 *
	 * @param string $name Employee name
	 * @param string $tier Bonus tier
	 * @param string $bonus_description Bonus description
	 * @param string $secret_code Secret code
	 * @param string $emoji Tier emoji
	 * @return string HTML email template
	 */
	private function get_physical_bonus_email_template( $name, $tier, $bonus_description, $secret_code, $emoji ) {
		$tier_colors = array(
			'Gold' => '#FFD700',
			'Silver' => '#C0C0C0',
			'Bronze' => '#CD7F32',
		);
		$color = isset( $tier_colors[ $tier ] ) ? $tier_colors[ $tier ] : '#FFD700';

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
		</head>
		<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
			<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
				<tr>
					<td align="center">
						<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
							<!-- Header -->
							<tr>
								<td style="background: linear-gradient(135deg, <?php echo esc_attr( $color ); ?> 0%, <?php echo esc_attr( $color ); ?>CC 100%); padding: 40px 20px; text-align: center;">
									<h1 style="margin: 0; color: #ffffff; font-size: 36px; font-weight: bold;">
										<?php echo esc_html( $emoji ); ?> BONUS AWARDED!
									</h1>
									<p style="margin: 10px 0 0 0; color: #ffffff; font-size: 20px; font-weight: bold;">
										<?php echo esc_html( $tier ); ?> Badge Physical Bonus
									</p>
								</td>
							</tr>
							
							<!-- Content -->
							<tr>
								<td style="padding: 40px 30px;">
									<p style="margin: 0 0 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
										Dear <strong><?php echo esc_html( $name ); ?></strong>,
									</p>
									<p style="margin: 0 0 30px 0; font-size: 18px; color: #333333; line-height: 1.6;">
										🎉 <strong>Congratulations!</strong> Your physical bonus has been approved and is ready to claim!
									</p>
									
									<!-- Bonus Details -->
									<table width="100%" cellpadding="20" cellspacing="0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px; margin: 30px 0; border: 2px solid <?php echo esc_attr( $color ); ?>;">
										<tr>
											<td align="center">
												<h2 style="margin: 0 0 15px 0; color: <?php echo esc_attr( $color ); ?>; font-size: 24px;">
													Your Bonus Reward
												</h2>
												<p style="margin: 0 0 20px 0; font-size: 18px; font-weight: bold; color: #333333;">
													<?php echo esc_html( $bonus_description ); ?>
												</p>
												
												<h3 style="margin: 20px 0 10px 0; color: #333333; font-size: 16px;">
													Secret Code to Claim:
												</h3>
												<div style="background: #ffffff; border: 3px dashed <?php echo esc_attr( $color ); ?>; border-radius: 8px; padding: 20px; margin: 15px 0;">
													<p style="margin: 0; font-size: 32px; font-weight: bold; letter-spacing: 3px; color: <?php echo esc_attr( $color ); ?>; font-family: 'Courier New', monospace;">
														<?php echo esc_html( $secret_code ); ?>
													</p>
												</div>
												
												<p style="margin: 15px 0 0 0; font-size: 13px; color: #6c757d;">
													Use this code in your Performance Tracker to claim your bonus.
												</p>
											</td>
										</tr>
									</table>
									
									<div style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 15px; border-radius: 4px; margin: 20px 0;">
										<p style="margin: 0; font-size: 14px; color: #0073aa; line-height: 1.6;">
											<strong>📝 How to Claim:</strong><br>
											1. Log in to your account<br>
											2. Go to Performance Tracker → Bonus Achieved<br>
											3. Click "Claim" on this bonus<br>
											4. Enter the secret code above<br>
											5. Your bonus will be marked as claimed!
										</p>
									</div>
									
									<p style="margin: 20px 0 0 0; font-size: 14px; color: #6c757d; line-height: 1.6;">
										Best regards,<br>
										<strong>Povaly Group Team</strong>
									</p>
								</td>
							</tr>
							
							<!-- Footer -->
							<tr>
								<td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6;">
									<p style="margin: 0; font-size: 12px; color: #6c757d;">
										This is an automated bonus notification from your performance tracking system.
									</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get physical bonus email template for admin (STEP 9)
	 *
	 * @param string $employee_name Employee name
	 * @param string $tier Bonus tier
	 * @param string $bonus_description Bonus description
	 * @param string $secret_code Secret code
	 * @param string $emoji Tier emoji
	 * @return string HTML email template
	 */
	private function get_admin_physical_bonus_email_template( $employee_name, $tier, $bonus_description, $secret_code, $emoji ) {
		$tier_colors = array(
			'Gold' => '#FFD700',
			'Silver' => '#C0C0C0',
			'Bronze' => '#CD7F32',
		);
		$color = isset( $tier_colors[ $tier ] ) ? $tier_colors[ $tier ] : '#FFD700';

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
		</head>
		<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
			<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
				<tr>
					<td align="center">
						<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
							<!-- Header -->
							<tr>
								<td style="background: linear-gradient(135deg, <?php echo esc_attr( $color ); ?> 0%, <?php echo esc_attr( $color ); ?>CC 100%); padding: 40px 20px; text-align: center;">
									<h1 style="margin: 0; color: #ffffff; font-size: 36px; font-weight: bold;">
										<?php echo esc_html( $emoji ); ?> BONUS SUBMITTED
									</h1>
									<p style="margin: 10px 0 0 0; color: #ffffff; font-size: 20px; font-weight: bold;">
										Physical Bonus - Admin Notification
									</p>
								</td>
							</tr>
							
							<!-- Content -->
							<tr>
								<td style="padding: 40px 30px;">
									<p style="margin: 0 0 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
										<strong>Admin Notification:</strong>
									</p>
									<p style="margin: 0 0 30px 0; font-size: 16px; color: #333333; line-height: 1.6;">
										A physical bonus has been submitted to <strong><?php echo esc_html( $employee_name ); ?></strong>.
									</p>
									
									<!-- Bonus Details -->
									<table width="100%" cellpadding="20" cellspacing="0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px; margin: 30px 0; border: 2px solid <?php echo esc_attr( $color ); ?>;">
										<tr>
											<td>
												<p style="margin: 0 0 10px 0; font-size: 14px; color: #6c757d;">
													<strong>Employee:</strong> <?php echo esc_html( $employee_name ); ?>
												</p>
												<p style="margin: 0 0 10px 0; font-size: 14px; color: #6c757d;">
													<strong>Tier:</strong> <?php echo esc_html( $tier ); ?> <?php echo esc_html( $emoji ); ?>
												</p>
												<p style="margin: 0 0 10px 0; font-size: 14px; color: #6c757d;">
													<strong>Bonus:</strong> <?php echo esc_html( $bonus_description ); ?>
												</p>
												<p style="margin: 0; font-size: 14px; color: #6c757d;">
													<strong>Submitted:</strong> <?php echo esc_html( current_time( 'Y-m-d H:i:s' ) ); ?>
												</p>
											</td>
										</tr>
									</table>
									
									<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px; margin: 20px 0;">
										<p style="margin: 0; font-size: 14px; color: #856404; line-height: 1.6;">
											<strong>Secret Code:</strong><br>
											<code style="font-size: 18px; font-weight: bold; letter-spacing: 2px; font-family: 'Courier New', monospace;">
												<?php echo esc_html( $secret_code ); ?>
											</code><br><br>
											This code has been sent to the employee. They will use it to claim the bonus in their Performance Tracker.
										</p>
									</div>
									
									<p style="margin: 20px 0 0 0; font-size: 14px; color: #6c757d; line-height: 1.6;">
										Best regards,<br>
										<strong>Performance Tracking System</strong>
									</p>
								</td>
							</tr>
							
							<!-- Footer -->
							<tr>
								<td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6;">
									<p style="margin: 0; font-size: 12px; color: #6c757d;">
										This is an automated notification from your performance tracking system.
									</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if it's a new month and finalize previous month
	 * Called by daily cron
	 */
	public function check_and_finalize_monthly_achievements() {
		// Get all employees
		$employees = $this->get_all_employees();

		foreach ( $employees as $employee ) {
			$user_id = $employee->ID;

			// Get current month data
			$monthly_data = get_user_meta( $user_id, '_wc_tp_monthly_achievements', true );

			if ( empty( $monthly_data ) ) {
				// First time, just update current month
				$this->update_monthly_achievements( $user_id );
				continue;
			}

			// Check if we're in a new month
			$current_period = date( 'Y-m' );
			$saved_period = isset( $monthly_data['period'] ) ? $monthly_data['period'] : '';

			if ( $current_period !== $saved_period && ! empty( $saved_period ) ) {
				// New month! Finalize previous month
				$this->finalize_monthly_achievements( $user_id );

				// Send monthly summary email
				$this->send_monthly_achievement_email( $user_id, $monthly_data );

				// Start tracking new month
				$this->update_monthly_achievements( $user_id );
			} else {
				// Same month, just update progress
				$this->update_monthly_achievements( $user_id );
			}
		}
	}

	/**
	 * Send monthly achievement summary email
	 *
	 * @param int $user_id User ID
	 * @param array $monthly_data Monthly achievement data
	 */
	private function send_monthly_achievement_email( $user_id, $monthly_data ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		// Check if already sent for this period
		$last_email_period = get_user_meta( $user_id, '_wc_tp_last_monthly_email', true );
		if ( $last_email_period === $monthly_data['period'] ) {
			return; // Already sent
		}

		$highest_tier = isset( $monthly_data['highest_tier'] ) ? $monthly_data['highest_tier'] : '';
		
		if ( empty( $highest_tier ) ) {
			return; // No achievements, no email
		}

		// Prepare email
		$to = $user->user_email;
		$subject = sprintf( 
			__( '🎉 Your %s Achievement for %s!', 'wc-team-payroll' ),
			ucfirst( $highest_tier ),
			date( 'F Y', strtotime( $monthly_data['period'] . '-01' ) )
		);

		$currency_symbol = get_woocommerce_currency_symbol();
		
		$message = $this->get_monthly_achievement_email_template( 
			$user->display_name,
			$monthly_data,
			$currency_symbol
		);

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		// Send email
		wp_mail( $to, $subject, $message, $headers );

		// Mark as sent
		update_user_meta( $user_id, '_wc_tp_last_monthly_email', $monthly_data['period'] );
	}

	/**
	 * Get monthly achievement email template
	 *
	 * @param string $name User name
	 * @param array $monthly_data Monthly achievement data
	 * @param string $currency_symbol Currency symbol
	 * @return string HTML email content
	 */
	private function get_monthly_achievement_email_template( $name, $monthly_data, $currency_symbol ) {
		$highest_tier = $monthly_data['highest_tier'];
		$period = date( 'F Y', strtotime( $monthly_data['period'] . '-01' ) );
		
		// Tier colors and emojis
		$tier_config = array(
			'gold' => array( 'color' => '#FFD700', 'emoji' => '🥇', 'label' => 'Gold' ),
			'silver' => array( 'color' => '#C0C0C0', 'emoji' => '🥈', 'label' => 'Silver' ),
			'bronze' => array( 'color' => '#CD7F32', 'emoji' => '🥉', 'label' => 'Bronze' ),
		);
		
		$config = $tier_config[ $highest_tier ];
		
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
		</head>
		<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
			<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
				<tr>
					<td align="center">
						<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
							<!-- Header -->
							<tr>
								<td style="background: linear-gradient(135deg, <?php echo esc_attr( $config['color'] ); ?> 0%, <?php echo esc_attr( $config['color'] ); ?>CC 100%); padding: 40px 20px; text-align: center;">
									<h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: bold;">
										<?php echo esc_html( $config['emoji'] ); ?> Congratulations!
									</h1>
									<p style="margin: 10px 0 0 0; color: #ffffff; font-size: 18px;">
										<?php echo esc_html( $config['label'] ); ?> Achievement Unlocked
									</p>
								</td>
							</tr>
							
							<!-- Content -->
							<tr>
								<td style="padding: 40px 30px;">
									<p style="margin: 0 0 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
										Dear <strong><?php echo esc_html( $name ); ?></strong>,
									</p>
									<p style="margin: 0 0 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
										Congratulations on achieving <strong><?php echo esc_html( $config['label'] ); ?></strong> status for <strong><?php echo esc_html( $period ); ?></strong>!
									</p>
									
									<!-- Performance Summary -->
									<table width="100%" cellpadding="15" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin: 20px 0;">
										<tr>
											<td style="border-bottom: 1px solid #dee2e6;">
												<strong style="color: #495057;">Total Earnings:</strong>
											</td>
											<td align="right" style="border-bottom: 1px solid #dee2e6;">
												<strong style="color: #28a745; font-size: 18px;">
													<?php echo esc_html( $currency_symbol . number_format( $monthly_data['earnings'], 2 ) ); ?>
												</strong>
												<?php if ( ! empty( $monthly_data['earnings_tier'] ) ) : ?>
													<span style="margin-left: 10px;"><?php echo esc_html( $tier_config[ $monthly_data['earnings_tier'] ]['emoji'] ); ?></span>
												<?php endif; ?>
											</td>
										</tr>
										<tr>
											<td style="border-bottom: 1px solid #dee2e6;">
												<strong style="color: #495057;">Orders Processed:</strong>
											</td>
											<td align="right" style="border-bottom: 1px solid #dee2e6;">
												<strong style="color: #007bff; font-size: 18px;">
													<?php echo esc_html( number_format( $monthly_data['orders'] ) ); ?>
												</strong>
												<?php if ( ! empty( $monthly_data['orders_tier'] ) ) : ?>
													<span style="margin-left: 10px;"><?php echo esc_html( $tier_config[ $monthly_data['orders_tier'] ]['emoji'] ); ?></span>
												<?php endif; ?>
											</td>
										</tr>
										<tr>
											<td>
												<strong style="color: #495057;">Average Order Value:</strong>
											</td>
											<td align="right">
												<strong style="color: #6f42c1; font-size: 18px;">
													<?php echo esc_html( $currency_symbol . number_format( $monthly_data['aov'], 2 ) ); ?>
												</strong>
												<?php if ( ! empty( $monthly_data['aov_tier'] ) ) : ?>
													<span style="margin-left: 10px;"><?php echo esc_html( $tier_config[ $monthly_data['aov_tier'] ]['emoji'] ); ?></span>
												<?php endif; ?>
											</td>
										</tr>
									</table>
									
									<p style="margin: 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
										Keep up the excellent work! Your dedication and performance are truly appreciated.
									</p>
									
									<p style="margin: 20px 0 0 0; font-size: 14px; color: #6c757d; line-height: 1.6;">
										Best regards,<br>
										<strong>Povaly Group Team</strong>
									</p>
								</td>
							</tr>
							
							<!-- Footer -->
							<tr>
								<td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6;">
									<p style="margin: 0; font-size: 12px; color: #6c757d;">
										This is an automated message from your performance tracking system.
									</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Send achievement unlock notifications
	 *
	 * @param int $user_id User ID
	 * @param array $newly_unlocked Newly unlocked achievement keys
	 * @param array $role_achievements Role achievements configuration
	 */
	private function send_achievement_notifications( $user_id, $newly_unlocked, $role_achievements ) {
		// This is a placeholder for notification system
		// Can be extended to send emails, push notifications, etc.
		
		// For now, just save a transient that frontend can check
		foreach ( $newly_unlocked as $achievement_key ) {
			$achievement_data = isset( $role_achievements[ $achievement_key ] ) ? $role_achievements[ $achievement_key ] : array();
			$notification_data = array(
				'user_id' => $user_id,
				'achievement_key' => $achievement_key,
				'achievement_name' => $achievement_data['name'] ?? '',
				'achievement_description' => $achievement_data['description'] ?? '',
				'tier' => $achievement_data['tier'] ?? 'bronze',
				'timestamp' => current_time( 'timestamp' ),
			);

			set_transient( 'wc_tp_achievement_notification_' . $user_id . '_' . $achievement_key, $notification_data, DAY_IN_SECONDS );
		}
	}


	// ============================================================================
	// BASELINES CALCULATION
	// ============================================================================

	/**
	 * Calculate and update baselines for a user
	 *
	 * @param int $user_id User ID
	 * @return array Baseline data
	 */
	public function update_baselines( $user_id ) {
		// Get baselines configuration
		$baselines_config = get_option( 'wc_tp_baselines_config', array() );
		$method = isset( $baselines_config['method'] ) ? $baselines_config['method'] : 'rolling_average';
		$periods = isset( $baselines_config['periods'] ) ? intval( $baselines_config['periods'] ) : 3;
		$minimum_data = isset( $baselines_config['minimum_data'] ) ? intval( $baselines_config['minimum_data'] ) : 5;

		// Get goals configuration for period type
		$goals_config = get_option( 'wc_tp_goals_config', array() );
		$period_type = isset( $goals_config['period'] ) ? $goals_config['period'] : 'monthly';

		// Get historical data
		$historical_data = $this->get_historical_performance_data( $user_id, $period_type, $periods + 5 ); // Get extra periods for calculation

		// Check if we have enough data
		if ( count( $historical_data ) < $minimum_data ) {
			return array(
				'error' => 'insufficient_data',
				'message' => sprintf( __( 'Need at least %d data points to calculate baseline', 'wc-team-payroll' ), $minimum_data ),
				'current_data_points' => count( $historical_data ),
			);
		}

		// Calculate baselines based on method
		$baseline_data = array(
			'calculated_date' => current_time( 'Y-m-d H:i:s' ),
			'method' => $method,
			'periods_used' => min( $periods, count( $historical_data ) ),
			'order_value' => $this->calculate_baseline_for_metric( $historical_data, 'order_value', $method, $periods ),
			'orders' => $this->calculate_baseline_for_metric( $historical_data, 'orders', $method, $periods ),
			'aov' => $this->calculate_baseline_for_metric( $historical_data, 'aov', $method, $periods ),
		);

		// Save to user meta
		update_user_meta( $user_id, '_wc_tp_current_baselines', $baseline_data );

		// Update baseline history
		$this->update_baseline_history( $user_id, $baseline_data );

		return $baseline_data;
	}

	/**
	 * Get historical performance data for a user
	 *
	 * @param int $user_id User ID
	 * @param string $period_type Period type (weekly, monthly, quarterly, yearly)
	 * @param int $num_periods Number of periods to retrieve
	 * @return array Historical data
	 */
	private function get_historical_performance_data( $user_id, $period_type, $num_periods ) {
		$historical_data = array();
		$timezone = wp_timezone();
		$now = new DateTime( 'now', $timezone );

		for ( $i = 0; $i < $num_periods; $i++ ) {
			// Calculate period dates
			$period_date = clone $now;
			
			switch ( $period_type ) {
				case 'weekly':
					$period_date->modify( '-' . $i . ' weeks' );
					break;
				case 'monthly':
					$period_date->modify( '-' . $i . ' months' );
					break;
				case 'quarterly':
					$period_date->modify( '-' . ( $i * 3 ) . ' months' );
					break;
				case 'yearly':
					$period_date->modify( '-' . $i . ' years' );
					break;
			}

			$period_dates = $this->get_period_dates( $period_type, $period_date->format( 'Y-m-d' ) );

			// Get data for this period
			$order_value = $this->get_attributed_order_total( $user_id, $period_dates['start'], $period_dates['end'] );
			$orders = $this->get_order_count( $user_id, $period_dates['start'], $period_dates['end'] );
			$aov = $this->get_average_order_value( $user_id, $period_dates['start'], $period_dates['end'] );

			// Only include periods with data
			if ( $order_value > 0 || $orders > 0 ) {
				$historical_data[] = array(
					'period' => $period_dates['period_id'],
					'start_date' => $period_dates['start'],
					'end_date' => $period_dates['end'],
					'order_value' => $order_value,
					'orders' => $orders,
					'aov' => $aov,
				);
			}
		}

		return $historical_data;
	}

	/**
	 * Calculate baseline for a specific metric
	 *
	 * @param array $historical_data Historical performance data
	 * @param string $metric Metric name (order_value, orders, aov)
	 * @param string $method Calculation method
	 * @param int $periods Number of periods to use
	 * @return array Baseline data for metric
	 */
	private function calculate_baseline_for_metric( $historical_data, $metric, $method, $periods ) {
		// Extract values for this metric
		$values = array();
		$data_points = array();
		
		$limited_data = array_slice( $historical_data, 0, $periods );
		
		foreach ( $limited_data as $period_data ) {
			if ( isset( $period_data[ $metric ] ) ) {
				$values[] = floatval( $period_data[ $metric ] );
				$data_points[] = floatval( $period_data[ $metric ] );
			}
		}

		if ( empty( $values ) ) {
			return array(
				'baseline' => 0,
				'current' => 0,
				'difference' => 0,
				'percentage' => 0,
				'trend' => 'stable',
				'data_points' => array(),
			);
		}

		// Calculate baseline based on method
		$baseline = 0;
		switch ( $method ) {
			case 'rolling_average':
				$baseline = array_sum( $values ) / count( $values );
				break;

			case 'historical_average':
				$baseline = array_sum( $values ) / count( $values );
				break;

			case 'best_period':
				$baseline = max( $values );
				break;

			case 'median':
				sort( $values );
				$count = count( $values );
				$middle = floor( $count / 2 );
				if ( $count % 2 == 0 ) {
					$baseline = ( $values[ $middle - 1 ] + $values[ $middle ] ) / 2;
				} else {
					$baseline = $values[ $middle ];
				}
				break;

			case 'percentile':
				$baselines_config = get_option( 'wc_tp_baselines_config', array() );
				$percentile = isset( $baselines_config['percentile'] ) ? intval( $baselines_config['percentile'] ) : 75;
				sort( $values );
				$index = ceil( ( $percentile / 100 ) * count( $values ) ) - 1;
				$baseline = $values[ max( 0, $index ) ];
				break;

			default:
				$baseline = array_sum( $values ) / count( $values );
		}

		// Get current value (most recent period)
		$current = isset( $historical_data[0][ $metric ] ) ? floatval( $historical_data[0][ $metric ] ) : 0;

		// Calculate difference and percentage
		$difference = $current - $baseline;
		$percentage = $baseline > 0 ? round( ( $difference / $baseline ) * 100, 2 ) : 0;

		// Determine trend
		$trend = 'stable';
		if ( $percentage > 5 ) {
			$trend = 'improving';
		} elseif ( $percentage < -5 ) {
			$trend = 'declining';
		}

		return array(
			'baseline' => round( $baseline, 2 ),
			'current' => round( $current, 2 ),
			'difference' => round( $difference, 2 ),
			'percentage' => $percentage,
			'trend' => $trend,
			'data_points' => $data_points,
		);
	}

	/**
	 * Update baseline history
	 *
	 * @param int $user_id User ID
	 * @param array $baseline_data Current baseline data
	 */
	private function update_baseline_history( $user_id, $baseline_data ) {
		$baseline_history = get_user_meta( $user_id, '_wc_tp_baseline_history', true );
		if ( ! is_array( $baseline_history ) ) {
			$baseline_history = array();
		}

		// Add current baseline to history
		$history_entry = array(
			'date' => $baseline_data['calculated_date'],
			'order_value_baseline' => $baseline_data['order_value']['baseline'] ?? 0,
			'orders_baseline' => $baseline_data['orders']['baseline'] ?? 0,
			'aov_baseline' => $baseline_data['aov']['baseline'] ?? 0,
			'method' => $baseline_data['method'],
			'periods' => $baseline_data['periods_used'],
		);

		array_unshift( $baseline_history, $history_entry );

		// Keep only last 12 entries
		$baseline_history = array_slice( $baseline_history, 0, 12 );

		update_user_meta( $user_id, '_wc_tp_baseline_history', $baseline_history );
	}


	// ============================================================================
	// AJAX HANDLERS
	// ============================================================================

	/**
	 * AJAX: Get user goal progress
	 */
	public function ajax_get_user_goal_progress() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'wc-team-payroll' ) ) );
		}

		// Check permissions
		if ( $user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		// Update and get goal progress
		$progress = $this->update_goal_progress( $user_id );

		if ( empty( $progress ) ) {
			wp_send_json_error( array( 'message' => __( 'No goals configured for this user', 'wc-team-payroll' ) ) );
		}

		wp_send_json_success( array( 'progress' => $progress ) );
	}

	/**
	 * AJAX: Get user achievements
	 */
	public function ajax_get_user_achievements() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'wc-team-payroll' ) ) );
		}

		// Check permissions
		if ( $user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		// Update and get achievements
		$achievements = $this->update_achievements( $user_id );
		$stats = get_user_meta( $user_id, '_wc_tp_achievement_stats', true );

		if ( empty( $achievements ) ) {
			wp_send_json_error( array( 'message' => __( 'No achievements configured for this user', 'wc-team-payroll' ) ) );
		}

		wp_send_json_success( array(
			'achievements' => $achievements,
			'stats' => $stats,
		) );
	}

	/**
	 * AJAX: Get user baselines
	 */
	public function ajax_get_user_baselines() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'wc-team-payroll' ) ) );
		}

		// Check permissions
		if ( $user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		// Get current baselines (don't recalculate, just return stored data)
		$baselines = get_user_meta( $user_id, '_wc_tp_current_baselines', true );
		$history = get_user_meta( $user_id, '_wc_tp_baseline_history', true );

		if ( empty( $baselines ) ) {
			// No baselines yet, calculate them
			$baselines = $this->update_baselines( $user_id );
		}

		wp_send_json_success( array(
			'baselines' => $baselines,
			'history' => $history,
		) );
	}

	/**
	 * AJAX: Recalculate all performance data for a user
	 */
	public function ajax_recalculate_performance() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'wc-team-payroll' ) ) );
		}

		// Recalculate everything
		$goals = $this->update_goal_progress( $user_id );
		$achievements = $this->update_achievements( $user_id );
		$baselines = $this->update_baselines( $user_id );

		wp_send_json_success( array(
			'message' => __( 'Performance data recalculated successfully', 'wc-team-payroll' ),
			'goals' => $goals,
			'achievements' => $achievements,
			'baselines' => $baselines,
		) );
	}


	// ============================================================================
	// CRON JOBS
	// ============================================================================

	/**
	 * Cron: Update baselines for all employees
	 */
	public function cron_update_baselines() {
		// Get baselines configuration
		$baselines_config = get_option( 'wc_tp_baselines_config', array() );
		$update_frequency = isset( $baselines_config['update_frequency'] ) ? $baselines_config['update_frequency'] : 'monthly';

		// Check if it's time to update based on frequency
		$last_update = get_option( 'wc_tp_last_baseline_update', 0 );
		$current_time = current_time( 'timestamp' );

		$should_update = false;
		switch ( $update_frequency ) {
			case 'daily':
				$should_update = ( $current_time - $last_update ) >= DAY_IN_SECONDS;
				break;
			case 'weekly':
				$should_update = ( $current_time - $last_update ) >= ( 7 * DAY_IN_SECONDS );
				break;
			case 'monthly':
				$should_update = ( $current_time - $last_update ) >= ( 30 * DAY_IN_SECONDS );
				break;
			case 'quarterly':
				$should_update = ( $current_time - $last_update ) >= ( 90 * DAY_IN_SECONDS );
				break;
			case 'manual':
				$should_update = false;
				break;
		}

		if ( ! $should_update ) {
			return;
		}

		// Get all employees
		$employees = $this->get_all_employees();

		foreach ( $employees as $employee_id ) {
			$this->update_baselines( $employee_id );
		}

		// Update last update timestamp
		update_option( 'wc_tp_last_baseline_update', $current_time );
	}

	/**
	 * Cron: Check and update achievements for all employees
	 */
	public function cron_check_achievements() {
		// Use new monthly achievement system
		$this->check_and_finalize_monthly_achievements();
		
		// Also update old system for backward compatibility
		$employees = $this->get_all_employees();
		foreach ( $employees as $employee_id ) {
			$this->update_achievements( $employee_id );
		}
	}

	/**
	 * Cron: Finalize period goals for all employees
	 */
	public function cron_finalize_period_goals() {
		// Get goals configuration
		$goals_config = get_option( 'wc_tp_goals_config', array() );
		$period_type = isset( $goals_config['period'] ) ? $goals_config['period'] : 'monthly';

		// Get current period dates
		$period_dates = $this->get_period_dates( $period_type );
		$today = current_time( 'Y-m-d' );

		// Check if we're at the end of the period
		if ( $today !== $period_dates['end'] ) {
			return; // Not end of period yet
		}

		// Get all employees
		$employees = $this->get_all_employees();

		foreach ( $employees as $employee_id ) {
			// Update current progress one last time
			$this->update_goal_progress( $employee_id );
			
			// Finalize and save to history
			$this->finalize_period_goals( $employee_id );
		}
	}

	/**
	 * Get all employee user IDs
	 *
	 * @return array Employee user IDs
	 */
	private function get_all_employees() {
		$employee_roles = array_keys( $this->get_employee_roles() );

		if ( empty( $employee_roles ) ) {
			return array();
		}

		$args = array(
			'role__in' => $employee_roles,
			'fields' => 'ID',
			'meta_query' => array(
				array(
					'key' => '_wc_tp_employee_status',
					'value' => 'active',
					'compare' => '=',
				),
			),
		);

		$users = get_users( $args );

		return $users;
	}
}
