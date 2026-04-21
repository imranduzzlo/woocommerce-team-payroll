<?php
/**
 * Leaderboard Engine Class
 * Handles leaderboard calculations, rankings, and scoring
 *
 * @package WooCommerce Team Payroll
 * @since 1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Leaderboard_Engine {

	/**
	 * Performance Tracker instance
	 */
	private $performance_tracker;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize performance tracker for metric calculations
		$this->performance_tracker = new WC_Team_Payroll_Performance_Tracker();
	}

	/**
	 * Static method to initialize the class
	 */
	public static function init() {
		return new self();
	}

	// ============================================================================
	// MAIN LEADERBOARD GENERATION
	// ============================================================================

	/**
	 * Generate complete leaderboard data
	 *
	 * @param array $config Leaderboard configuration
	 * @return array Leaderboard data with rankings
	 */
	public function generate_leaderboard( $config = array() ) {
		// Get configuration
		if ( empty( $config ) ) {
			$config = get_option( 'wc_tp_leaderboard_config', array() );
		}

		// Check if leaderboard is enabled
		if ( ! isset( $config['enabled'] ) || ! $config['enabled'] ) {
			return array(
				'error' => true,
				'message' => __( 'Leaderboard is not enabled', 'wc-team-payroll' ),
			);
		}

		// Get configuration values
		$criteria = isset( $config['criteria'] ) ? $config['criteria'] : 'total_earnings';
		$period = isset( $config['period'] ) ? $config['period'] : 'current_month';
		$minimum_orders = isset( $config['minimum_orders'] ) ? intval( $config['minimum_orders'] ) : 0;
		$exclude_inactive = isset( $config['exclude_inactive'] ) ? $config['exclude_inactive'] : 1;

		// Get date range for period
		$date_range = $this->get_period_date_range( $period );

		// Get all eligible employees
		$employees = $this->get_eligible_employees( $exclude_inactive );

		if ( empty( $employees ) ) {
			return array(
				'error' => true,
				'message' => __( 'No eligible employees found', 'wc-team-payroll' ),
			);
		}

		// Calculate metrics for all employees
		$employee_data = array();
		foreach ( $employees as $employee_id ) {
			$metrics = $this->calculate_employee_metrics( $employee_id, $date_range['start'], $date_range['end'] );
			
			// Apply minimum orders filter
			if ( $minimum_orders > 0 && $metrics['orders'] < $minimum_orders ) {
				continue;
			}

			$employee_data[ $employee_id ] = $metrics;
		}

		if ( empty( $employee_data ) ) {
			return array(
				'error' => true,
				'message' => __( 'No employees meet the minimum requirements', 'wc-team-payroll' ),
			);
		}

		// Calculate scores based on criteria
		$scored_employees = $this->calculate_scores( $employee_data, $criteria );

		// Sort by score (descending)
		uasort( $scored_employees, function( $a, $b ) {
			return $b['score'] <=> $a['score'];
		});

		// Assign ranks
		$rank = 1;
		$leaderboard = array();
		foreach ( $scored_employees as $employee_id => $data ) {
			$user = get_userdata( $employee_id );
			if ( ! $user ) {
				continue;
			}

			$leaderboard[] = array(
				'rank' => $rank,
				'user_id' => $employee_id,
				'display_name' => $user->display_name,
				'user_email' => $user->user_email,
				'score' => $data['score'],
				'percentile' => $data['percentile'],
				'metrics' => $data['metrics'],
			);

			// Save rank to user meta
			update_user_meta( $employee_id, '_wc_tp_leaderboard_rank_' . $date_range['period_id'], $rank );
			update_user_meta( $employee_id, '_wc_tp_leaderboard_score_' . $date_range['period_id'], $data['score'] );
			update_user_meta( $employee_id, '_wc_tp_current_leaderboard_rank', $rank );
			update_user_meta( $employee_id, '_wc_tp_current_leaderboard_score', $data['score'] );

			$rank++;
		}

		// Cache leaderboard data
		$cache_data = array(
			'leaderboard' => $leaderboard,
			'config' => $config,
			'date_range' => $date_range,
			'total_employees' => count( $leaderboard ),
			'generated_at' => current_time( 'mysql' ),
		);

		set_transient( 'wc_tp_leaderboard_data_' . $date_range['period_id'], $cache_data, HOUR_IN_SECONDS );

		return $cache_data;
	}

	// ============================================================================
	// EMPLOYEE METRICS CALCULATION
	// ============================================================================

	/**
	 * Calculate all metrics for an employee
	 *
	 * @param int $employee_id Employee user ID
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return array Metrics data
	 */
	private function calculate_employee_metrics( $employee_id, $start_date, $end_date ) {
		// Get basic metrics using performance tracker methods
		$earnings = $this->get_total_earnings( $employee_id, $start_date, $end_date );
		$orders = $this->get_order_count( $employee_id, $start_date, $end_date );
		$order_value = $this->get_attributed_order_total( $employee_id, $start_date, $end_date );
		$aov = $orders > 0 ? ( $order_value / $orders ) : 0;
		$commission = $this->get_commission_earnings( $employee_id, $start_date, $end_date );
		
		// Get achievement score
		$achievement_score = $this->get_achievement_score( $employee_id );
		
		// Get goal completion rate
		$goal_completion = $this->get_goal_completion_rate( $employee_id );

		return array(
			'earnings' => $earnings,
			'orders' => $orders,
			'order_value' => $order_value,
			'aov' => $aov,
			'commission' => $commission,
			'achievement_score' => $achievement_score,
			'goal_completion' => $goal_completion,
		);
	}

	/**
	 * Get total earnings for employee
	 */
	private function get_total_earnings( $employee_id, $start_date, $end_date ) {
		// Use performance settings calculation method
		$settings = new WC_Team_Payroll_Performance_Settings();
		return $settings->calculate_earnings( $employee_id, $start_date, $end_date );
	}

	/**
	 * Get order count for employee
	 */
	private function get_order_count( $employee_id, $start_date, $end_date ) {
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		$statuses_to_query = array();
		foreach ( $commission_statuses as $status ) {
			$statuses_to_query[] = 'wc-' . $status;
		}

		$order_ids = array();

		// Agent orders
		$agent_args = array(
			'limit'        => -1,
			'meta_key'     => '_primary_agent_id',
			'meta_value'   => $employee_id,
			'status'       => $statuses_to_query,
			'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
			'return'       => 'ids',
		);
		$agent_orders = wc_get_orders( $agent_args );
		$order_ids = array_merge( $order_ids, $agent_orders );

		// Processor orders
		$processor_args = array(
			'limit'        => -1,
			'meta_key'     => '_processor_user_id',
			'meta_value'   => $employee_id,
			'status'       => $statuses_to_query,
			'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
			'return'       => 'ids',
		);
		$processor_orders = wc_get_orders( $processor_args );
		$order_ids = array_merge( $order_ids, $processor_orders );

		return count( array_unique( $order_ids ) );
	}

	/**
	 * Get attributed order total for employee
	 */
	private function get_attributed_order_total( $employee_id, $start_date, $end_date ) {
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		$statuses_to_query = array();
		foreach ( $commission_statuses as $status ) {
			$statuses_to_query[] = 'wc-' . $status;
		}

		$attributed_total = 0;

		// Agent orders
		$agent_args = array(
			'limit'        => -1,
			'meta_key'     => '_primary_agent_id',
			'meta_value'   => $employee_id,
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

		// Processor orders
		$processor_args = array(
			'limit'        => -1,
			'meta_key'     => '_processor_user_id',
			'meta_value'   => $employee_id,
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

		return $attributed_total;
	}

	/**
	 * Get commission earnings for employee
	 */
	private function get_commission_earnings( $employee_id, $start_date, $end_date ) {
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		$statuses_to_query = array();
		foreach ( $commission_statuses as $status ) {
			$statuses_to_query[] = 'wc-' . $status;
		}

		$total_commission = 0;

		// Agent commission
		$agent_args = array(
			'limit'        => -1,
			'meta_key'     => '_primary_agent_id',
			'meta_value'   => $employee_id,
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
			if ( $commission_data && isset( $commission_data['agent_commission'] ) ) {
				$total_commission += floatval( $commission_data['agent_commission'] );
			}
		}

		// Processor commission
		$processor_args = array(
			'limit'        => -1,
			'meta_key'     => '_processor_user_id',
			'meta_value'   => $employee_id,
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
			if ( $commission_data && isset( $commission_data['processor_commission'] ) ) {
				$total_commission += floatval( $commission_data['processor_commission'] );
			}
		}

		return $total_commission;
	}

	/**
	 * Get achievement score for employee
	 */
	private function get_achievement_score( $employee_id ) {
		// Get current period achievements
		$achievements_config = get_option( 'wc_tp_achievements_config', array() );
		$period_type = isset( $achievements_config['period'] ) ? $achievements_config['period'] : 'monthly';
		
		$tracker = new WC_Team_Payroll_Performance_Tracker();
		$current_period_id = $tracker->get_current_period_id( $period_type );
		
		$period_stats = get_user_meta( $employee_id, '_wc_tp_period_achievements_stats_' . $current_period_id, true );
		
		if ( ! is_array( $period_stats ) || ! isset( $period_stats['total_unlocked'] ) ) {
			return 0;
		}

		// Calculate score: Bronze = 1, Silver = 2, Gold = 3
		$bronze = isset( $period_stats['bronze_count'] ) ? intval( $period_stats['bronze_count'] ) : 0;
		$silver = isset( $period_stats['silver_count'] ) ? intval( $period_stats['silver_count'] ) : 0;
		$gold = isset( $period_stats['gold_count'] ) ? intval( $period_stats['gold_count'] ) : 0;

		return ( $bronze * 1 ) + ( $silver * 2 ) + ( $gold * 3 );
	}

	/**
	 * Get goal completion rate for employee
	 */
	private function get_goal_completion_rate( $employee_id ) {
		$current_progress = get_user_meta( $employee_id, '_wc_tp_current_goal_progress', true );
		
		if ( ! is_array( $current_progress ) ) {
			return 0;
		}

		$total_goals = 0;
		$achieved_goals = 0;

		foreach ( array( 'order_value', 'orders', 'aov' ) as $metric ) {
			if ( isset( $current_progress[ $metric ] ) ) {
				$total_goals++;
				$status = isset( $current_progress[ $metric ]['status'] ) ? $current_progress[ $metric ]['status'] : '';
				if ( $status === 'achieved' || $status === 'stretch_achieved' ) {
					$achieved_goals++;
				}
			}
		}

		return $total_goals > 0 ? ( $achieved_goals / $total_goals ) * 100 : 0;
	}

	// ============================================================================
	// SCORING & RANKING
	// ============================================================================

	/**
	 * Calculate scores for all employees based on criteria
	 *
	 * @param array $employee_data Employee metrics data
	 * @param string $criteria Scoring criteria
	 * @return array Scored employee data
	 */
	private function calculate_scores( $employee_data, $criteria ) {
		// Check if single metric or composite
		if ( $this->is_single_metric( $criteria ) ) {
			return $this->calculate_single_metric_scores( $employee_data, $criteria );
		} else {
			return $this->calculate_composite_scores( $employee_data, $criteria );
		}
	}

	/**
	 * Check if criteria is a single metric
	 */
	private function is_single_metric( $criteria ) {
		$single_metrics = array(
			'total_earnings',
			'total_orders',
			'average_order_value',
			'commission_earnings',
			'total_order_value',
			'achievement_score',
			'goal_completion_rate',
		);

		return in_array( $criteria, $single_metrics );
	}

	/**
	 * Calculate scores for single metric criteria
	 */
	private function calculate_single_metric_scores( $employee_data, $criteria ) {
		$metric_map = array(
			'total_earnings' => 'earnings',
			'total_orders' => 'orders',
			'average_order_value' => 'aov',
			'commission_earnings' => 'commission',
			'total_order_value' => 'order_value',
			'achievement_score' => 'achievement_score',
			'goal_completion_rate' => 'goal_completion',
		);

		$metric_key = isset( $metric_map[ $criteria ] ) ? $metric_map[ $criteria ] : 'earnings';

		$scored_data = array();
		foreach ( $employee_data as $employee_id => $metrics ) {
			$score = isset( $metrics[ $metric_key ] ) ? floatval( $metrics[ $metric_key ] ) : 0;
			
			$scored_data[ $employee_id ] = array(
				'score' => $score,
				'percentile' => 0, // Will be calculated after sorting
				'metrics' => $metrics,
			);
		}

		return $scored_data;
	}

	/**
	 * Calculate composite scores with weighted percentiles
	 */
	private function calculate_composite_scores( $employee_data, $criteria ) {
		// Get weights for criteria
		$weights = $this->get_criteria_weights( $criteria );
		
		if ( empty( $weights ) ) {
			// Fallback to total earnings
			return $this->calculate_single_metric_scores( $employee_data, 'total_earnings' );
		}

		// Calculate percentile for each metric
		$percentiles = array();
		foreach ( $weights as $metric => $weight ) {
			$percentiles[ $metric ] = $this->calculate_percentiles( $employee_data, $metric );
		}

		// Calculate weighted composite score
		$scored_data = array();
		foreach ( $employee_data as $employee_id => $metrics ) {
			$composite_score = 0;

			foreach ( $weights as $metric => $weight ) {
				$percentile = isset( $percentiles[ $metric ][ $employee_id ] ) ? $percentiles[ $metric ][ $employee_id ] : 0;
				$composite_score += ( $weight / 100 ) * $percentile;
			}

			$scored_data[ $employee_id ] = array(
				'score' => $composite_score,
				'percentile' => $composite_score, // For composite, score IS the percentile
				'metrics' => $metrics,
			);
		}

		return $scored_data;
	}

	/**
	 * Get weights for composite criteria
	 */
	private function get_criteria_weights( $criteria ) {
		$weights_map = array(
			// Dual metrics
			'earnings_orders_50_50' => array( 'earnings' => 50, 'orders' => 50 ),
			'earnings_aov_60_40' => array( 'earnings' => 60, 'aov' => 40 ),
			'orders_aov_50_50' => array( 'orders' => 50, 'aov' => 50 ),
			'earnings_achievement_70_30' => array( 'earnings' => 70, 'achievement_score' => 30 ),
			'orders_achievement_60_40' => array( 'orders' => 60, 'achievement_score' => 40 ),
			
			// Triple metrics
			'earnings_orders_aov_40_30_30' => array( 'earnings' => 40, 'orders' => 30, 'aov' => 30 ),
			'earnings_orders_achievement_50_30_20' => array( 'earnings' => 50, 'orders' => 30, 'achievement_score' => 20 ),
			'earnings_aov_achievement_50_25_25' => array( 'earnings' => 50, 'aov' => 25, 'achievement_score' => 25 ),
			'orders_aov_achievement_40_30_30' => array( 'orders' => 40, 'aov' => 30, 'achievement_score' => 30 ),
			
			// Complete score
			'complete_score' => array( 'earnings' => 35, 'orders' => 25, 'aov' => 20, 'achievement_score' => 15, 'goal_completion' => 5 ),
		);

		return isset( $weights_map[ $criteria ] ) ? $weights_map[ $criteria ] : array();
	}

	/**
	 * Calculate percentiles for a specific metric
	 *
	 * @param array $employee_data All employee data
	 * @param string $metric Metric key
	 * @return array Employee ID => percentile mapping
	 */
	private function calculate_percentiles( $employee_data, $metric ) {
		// Extract metric values
		$values = array();
		foreach ( $employee_data as $employee_id => $metrics ) {
			$values[ $employee_id ] = isset( $metrics[ $metric ] ) ? floatval( $metrics[ $metric ] ) : 0;
		}

		// Sort by value (ascending)
		asort( $values );

		// Calculate percentile for each employee
		$total_employees = count( $values );
		$percentiles = array();
		$rank = 1;

		foreach ( $values as $employee_id => $value ) {
			// Percentile = (rank / total) * 100
			$percentiles[ $employee_id ] = ( $rank / $total_employees ) * 100;
			$rank++;
		}

		return $percentiles;
	}

	// ============================================================================
	// HELPER FUNCTIONS
	// ============================================================================

	/**
	 * Get all eligible employees
	 *
	 * @param bool $exclude_inactive Exclude inactive employees
	 * @return array Employee user IDs
	 */
	private function get_eligible_employees( $exclude_inactive = true ) {
		$args = array(
			'role__in' => array( 'shop_employee', 'shop_manager', 'administrator' ),
			'fields' => 'ID',
		);

		if ( $exclude_inactive ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key' => '_wc_tp_employee_status',
					'value' => 'active',
					'compare' => '=',
				),
				array(
					'key' => '_wc_tp_employee_status',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		$users = get_users( $args );
		return $users;
	}

	/**
	 * Get date range for period
	 *
	 * @param string $period Period identifier
	 * @return array Start and end dates
	 */
	private function get_period_date_range( $period ) {
		$timezone = wp_timezone();
		$now = new DateTime( 'now', $timezone );

		switch ( $period ) {
			case 'current_week':
				$start = clone $now;
				$start->modify( 'monday this week' );
				$end = clone $start;
				$end->modify( '+6 days' );
				$period_id = $start->format( 'Y-W' );
				break;

			case 'current_month':
				$start = new DateTime( $now->format( 'Y-m-01' ), $timezone );
				$end = clone $start;
				$end->modify( 'last day of this month' );
				$period_id = $start->format( 'Y-m' );
				break;

			case 'current_quarter':
				$month = (int) $now->format( 'n' );
				$quarter_start_month = ( ceil( $month / 3 ) - 1 ) * 3 + 1;
				$start = new DateTime( $now->format( 'Y' ) . '-' . str_pad( $quarter_start_month, 2, '0', STR_PAD_LEFT ) . '-01', $timezone );
				$end = clone $start;
				$end->modify( '+2 months' );
				$end->modify( 'last day of this month' );
				$period_id = $start->format( 'Y-Q' ) . ceil( $month / 3 );
				break;

			case 'current_year':
				$start = new DateTime( $now->format( 'Y' ) . '-01-01', $timezone );
				$end = new DateTime( $now->format( 'Y' ) . '-12-31', $timezone );
				$period_id = $start->format( 'Y' );
				break;

			case 'last_30_days':
				$end = clone $now;
				$start = clone $now;
				$start->modify( '-30 days' );
				$period_id = 'last_30_days_' . $now->format( 'Y-m-d' );
				break;

			case 'last_90_days':
				$end = clone $now;
				$start = clone $now;
				$start->modify( '-90 days' );
				$period_id = 'last_90_days_' . $now->format( 'Y-m-d' );
				break;

			default:
				// Default to current month
				$start = new DateTime( $now->format( 'Y-m-01' ), $timezone );
				$end = clone $start;
				$end->modify( 'last day of this month' );
				$period_id = $start->format( 'Y-m' );
		}

		return array(
			'start' => $start->format( 'Y-m-d' ),
			'end' => $end->format( 'Y-m-d' ),
			'period_id' => $period_id,
		);
	}

	/**
	 * Get user's current rank
	 *
	 * @param int $user_id User ID
	 * @return int Rank (0 if not ranked)
	 */
	public function get_user_rank( $user_id ) {
		$rank = get_user_meta( $user_id, '_wc_tp_current_leaderboard_rank', true );
		return $rank ? intval( $rank ) : 0;
	}

	/**
	 * Get user's current score
	 *
	 * @param int $user_id User ID
	 * @return float Score (0 if not scored)
	 */
	public function get_user_score( $user_id ) {
		$score = get_user_meta( $user_id, '_wc_tp_current_leaderboard_score', true );
		return $score ? floatval( $score ) : 0;
	}

	/**
	 * Get cached leaderboard data
	 *
	 * @param string $period_id Period identifier
	 * @return array|false Leaderboard data or false if not cached
	 */
	public function get_cached_leaderboard( $period_id = null ) {
		if ( ! $period_id ) {
			$config = get_option( 'wc_tp_leaderboard_config', array() );
			$period = isset( $config['period'] ) ? $config['period'] : 'current_month';
			$date_range = $this->get_period_date_range( $period );
			$period_id = $date_range['period_id'];
		}

		return get_transient( 'wc_tp_leaderboard_data_' . $period_id );
	}

	/**
	 * Clear leaderboard cache
	 */
	public function clear_cache() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wc_tp_leaderboard_data_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wc_tp_leaderboard_data_%'" );
	}
}

