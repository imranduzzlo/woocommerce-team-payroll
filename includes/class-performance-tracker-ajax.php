<?php
/**
 * Performance Tracker AJAX Handlers
 * Handles frontend AJAX requests for Goals, Achievements, and Baselines
 *
 * @package WooCommerce Team Payroll
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Performance_Tracker_AJAX {

	/**
	 * Initialize AJAX handlers
	 */
	public static function init() {
		add_action( 'wp_ajax_wc_tp_get_performance_tracker_data', array( __CLASS__, 'ajax_get_performance_tracker_data' ) );
		add_action( 'wp_ajax_wc_tp_claim_bonus', array( __CLASS__, 'ajax_claim_bonus' ) );
		add_action( 'wp_ajax_wc_tp_submit_bonus', array( __CLASS__, 'ajax_submit_bonus' ) );
		add_action( 'wp_ajax_wc_tp_resend_secret_code', array( __CLASS__, 'ajax_resend_secret_code' ) );
		add_action( 'wp_ajax_wc_tp_get_leaderboard_data', array( __CLASS__, 'ajax_get_leaderboard_data' ) );
	}

	/**
	 * AJAX: Get Performance Tracker Data
	 * Handles Goals, Achievements, and Baselines
	 */
	public static function ajax_get_performance_tracker_data() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		// Check if user_id is provided (for admin viewing employee performance)
		$requested_user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		
		// If user_id is provided and current user is admin, use that user_id
		if ( $requested_user_id && current_user_can( 'manage_options' ) ) {
			$user_id = $requested_user_id;
		} else {
			// Otherwise use current logged-in user
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$section = isset( $_POST['section'] ) ? sanitize_text_field( $_POST['section'] ) : 'overview';
		$view_mode = isset( $_POST['view_mode'] ) ? sanitize_text_field( $_POST['view_mode'] ) : 'current';

		// Initialize Performance Tracker
		$tracker = new WC_Team_Payroll_Performance_Tracker();

		$data = array();

		switch ( $section ) {
			case 'config':
				// Get admin-configured settings
				$goals_config = get_option( 'wc_tp_goals_config', array() );
				$achievements_config = get_option( 'wc_tp_achievements_config', array() );
				
				// Get user's role-specific achievements
				$user = get_user_by( 'id', $user_id );
				$user_roles = $user ? $user->roles : array();
				$employee_role = '';
				
				// Find employee role
				if ( ! empty( $user_roles ) ) {
					$all_roles = $tracker->get_employee_roles();
					foreach ( $user_roles as $role ) {
						if ( isset( $all_roles[ $role ] ) ) {
							$employee_role = $role;
							break;
						}
					}
				}
				
				// Get role-specific achievements
				$role_achievements = array();
				if ( ! empty( $employee_role ) && isset( $achievements_config['roles'][ $employee_role ] ) ) {
					$role_achievements = $achievements_config['roles'][ $employee_role ];
				}
				
				$data['period_type'] = isset( $goals_config['period'] ) ? $goals_config['period'] : 'monthly';
				$data['achievements_enabled'] = isset( $achievements_config['enabled'] ) ? intval( $achievements_config['enabled'] ) : 1;
				$data['achievements_display_style'] = isset( $achievements_config['display_style'] ) ? $achievements_config['display_style'] : 'badges';
				$data['achievements_show_locked'] = isset( $achievements_config['show_locked'] ) ? intval( $achievements_config['show_locked'] ) : 1;
				$data['achievements_notification'] = isset( $achievements_config['notification'] ) ? intval( $achievements_config['notification'] ) : 1;
				$data['achievements_period'] = isset( $achievements_config['period'] ) ? $achievements_config['period'] : 'monthly';
				$data['user_role'] = $employee_role;
				$data['role_achievements'] = $role_achievements;
				break;

			case 'bonus_achieved':
				// Get achieved bonuses for current user
				$achieved_bonuses = get_user_meta( $user_id, '_wc_tp_achieved_bonuses', true );
				if ( ! is_array( $achieved_bonuses ) ) {
					$achieved_bonuses = array();
				}
				$data['achieved_bonuses'] = $achieved_bonuses;
				break;

			case 'period_achievements':
				// Get period-based achievements for current user
				$achievements_config = get_option( 'wc_tp_achievements_config', array() );
				$period_type = isset( $achievements_config['period'] ) ? $achievements_config['period'] : 'monthly';
				
				// Get current period ID
				$current_period_id = $tracker->get_current_period_id( $period_type );
				
				// Get current period achievements
				$period_achievements = get_user_meta( $user_id, '_wc_tp_period_achievements_' . $current_period_id, true );
				if ( ! is_array( $period_achievements ) ) {
					$period_achievements = array();
				}
				
				// Get period stats (includes tier_categories)
				$period_stats = get_user_meta( $user_id, '_wc_tp_period_achievements_stats_' . $current_period_id, true );
				if ( ! is_array( $period_stats ) ) {
					$period_stats = array();
				}
				
				// Get period history
				$period_history = get_user_meta( $user_id, '_wc_tp_period_achievements_history', true );
				if ( ! is_array( $period_history ) ) {
					$period_history = array();
				}
				
				// Get period date range
				$period_range = $tracker->get_period_date_range( $period_type );
				
				$data['period_type'] = $period_type;
				$data['current_period_id'] = $current_period_id;
				$data['period_achievements'] = $period_achievements;
				$data['period_stats'] = $period_stats;
				$data['period_history'] = $period_history;
				$data['period_range'] = $period_range;
				$data['highest_tier'] = isset( $period_achievements['highest_tier'] ) ? $period_achievements['highest_tier'] : '';
				$data['tier_categories'] = isset( $period_stats['tier_categories'] ) ? $period_stats['tier_categories'] : array();
				break;

			case 'overview':
				// Get overview data with view mode
				$goals = $tracker->update_goal_progress( $user_id, $view_mode );
				
				// Get current period achievements and stats
				$achievements_config = get_option( 'wc_tp_achievements_config', array() );
				$period_type = isset( $achievements_config['period'] ) ? $achievements_config['period'] : 'monthly';
				$current_period_id = $tracker->get_current_period_id( $period_type );
				$period_achievements = get_user_meta( $user_id, '_wc_tp_period_achievements_' . $current_period_id, true );
				$period_stats = get_user_meta( $user_id, '_wc_tp_period_achievements_stats_' . $current_period_id, true );
				
				$baselines = get_user_meta( $user_id, '_wc_tp_current_baselines', true );

				$data['goals_summary'] = array(
					'html' => self::render_goals_summary( $goals )
				);
				$data['achievements_summary'] = array(
					'html' => self::render_achievements_summary( $period_stats )
				);
				$data['baselines_summary'] = array(
					'html' => self::render_baselines_summary( $baselines )
				);
				$data['quick_stats'] = self::get_quick_stats( $goals, $period_stats, $baselines );
				break;

			case 'goals':
				// Get goals data with view mode
				$data['goals'] = $tracker->update_goal_progress( $user_id, $view_mode );
				$data['history'] = get_user_meta( $user_id, '_wc_tp_goal_history', true );
				break;

			case 'achievements':
				try {
					// Get achievements data
					$achievements = $tracker->update_achievements( $user_id );
					if ( ! is_array( $achievements ) ) {
						$achievements = array();
					}
					$data['achievements'] = $achievements;
					
					// Get current period achievements stats
					$achievements_config = get_option( 'wc_tp_achievements_config', array() );
					$period_type = isset( $achievements_config['period'] ) ? $achievements_config['period'] : 'monthly';
					$current_period_id = $tracker->get_current_period_id( $period_type );
					$stats = get_user_meta( $user_id, '_wc_tp_period_achievements_stats_' . $current_period_id, true );
					if ( ! is_array( $stats ) ) {
						$stats = array();
					}
					
					// Add current period metrics to stats
					$period_range = $tracker->get_period_date_range( $period_type );
					
					// Check if any orders/order_value achievements are already unlocked
					// If unlocked, use the value_at_unlock, otherwise use current count
					$has_unlocked_orders = false;
					$has_unlocked_order_value = false;
					$has_unlocked_earnings = false;
					$has_unlocked_aov = false;
					
					foreach ( $achievements as $key => $achievement ) {
						if ( isset( $achievement['unlocked'] ) && $achievement['unlocked'] === true ) {
							if ( strpos( $key, 'orders_' ) === 0 && isset( $achievement['value_at_unlock'] ) ) {
								$has_unlocked_orders = true;
								$stats['orders'] = $achievement['value_at_unlock'];
							} elseif ( strpos( $key, 'order_value_' ) === 0 && isset( $achievement['value_at_unlock'] ) ) {
								$has_unlocked_order_value = true;
								$stats['order_value'] = $achievement['value_at_unlock'];
							} elseif ( strpos( $key, 'earnings_' ) === 0 && isset( $achievement['value_at_unlock'] ) ) {
								$has_unlocked_earnings = true;
								$stats['earnings'] = $achievement['value_at_unlock'];
							} elseif ( strpos( $key, 'aov_' ) === 0 && isset( $achievement['value_at_unlock'] ) ) {
								$has_unlocked_aov = true;
								$stats['aov'] = $achievement['value_at_unlock'];
							}
						}
					}
					
					// If no achievements unlocked yet, use current metrics
					if ( ! $has_unlocked_orders ) {
						$stats['orders'] = $tracker->get_order_count( $user_id, $period_range['start_date'], $period_range['end_date'] );
					}
					if ( ! $has_unlocked_order_value ) {
						$stats['order_value'] = $tracker->get_attributed_order_total( $user_id, $period_range['start_date'], $period_range['end_date'] );
					}
					if ( ! $has_unlocked_earnings ) {
						$stats['earnings'] = $tracker->get_total_earnings( $user_id, $period_range['start_date'], $period_range['end_date'] );
					}
					if ( ! $has_unlocked_aov ) {
						$stats['aov'] = $tracker->get_average_order_value( $user_id, $period_range['start_date'], $period_range['end_date'] );
					}
					
					$data['stats'] = $stats;
					$data['tier_categories'] = isset( $stats['tier_categories'] ) ? $stats['tier_categories'] : array();
					
					// Get user's role-specific achievements
					$user = get_user_by( 'id', $user_id );
					$user_roles = $user ? $user->roles : array();
					$employee_role = '';
					
					// Find employee role
					if ( ! empty( $user_roles ) ) {
						$all_roles = $tracker->get_employee_roles();
						foreach ( $user_roles as $role ) {
							if ( isset( $all_roles[ $role ] ) ) {
								$employee_role = $role;
								break;
							}
						}
					}
					
					// Get role-specific achievements
					$role_achievements = array();
					if ( ! empty( $employee_role ) && isset( $achievements_config['roles'][ $employee_role ] ) ) {
						$role_achievements = $achievements_config['roles'][ $employee_role ];
					}
					
					// Phase 2 Part 3: Add streak and bonus data
					$streaks = get_user_meta( $user_id, '_wc_tp_badge_streaks', true );
					$bonus_history = get_user_meta( $user_id, '_wc_tp_bonus_history', true );
					$bonus_milestones = self::get_bonus_milestones( $user_id );
					
					$data['streaks'] = is_array( $streaks ) ? $streaks : array();
					$data['bonus_history'] = is_array( $bonus_history ) ? $bonus_history : array();
					$data['bonus_milestones'] = is_array( $bonus_milestones ) ? $bonus_milestones : array();
					$data['user_role'] = $employee_role;
					$data['role_achievements'] = $role_achievements;
					$data['achievements_config'] = $achievements_config;
				} catch ( Exception $e ) {
					wp_send_json_error( array( 'message' => 'Error loading achievements: ' . $e->getMessage() ) );
				}
				break;

			case 'baselines':
				// Get baselines data
				$baselines = get_user_meta( $user_id, '_wc_tp_current_baselines', true );
				
				// If no baselines exist, calculate them
				if ( empty( $baselines ) ) {
					$baselines = $tracker->update_baselines( $user_id );
				}
				
				$data['baselines'] = $baselines;
				$data['history'] = get_user_meta( $user_id, '_wc_tp_baseline_history', true );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Invalid section', 'wc-team-payroll' ) ) );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Render goals summary for overview
	 */
	private static function render_goals_summary( $goals ) {
		if ( empty( $goals ) ) {
			return '<p>No goals configured</p>';
		}

		$achieved_count = 0;
		$total_count = 0;

		foreach ( array( 'order_value', 'orders', 'aov' ) as $metric ) {
			if ( isset( $goals[ $metric ] ) ) {
				$total_count++;
				if ( $goals[ $metric ]['status'] === 'achieved' || $goals[ $metric ]['status'] === 'stretch_achieved' ) {
					$achieved_count++;
				}
			}
		}

		$percentage = $total_count > 0 ? ( $achieved_count / $total_count ) * 100 : 0;

		return sprintf(
			'<div class="summary-stat"><strong>%d/%d</strong> Goals Achieved</div><div class="summary-progress">%.0f%%</div>',
			$achieved_count,
			$total_count,
			$percentage
		);
	}

	/**
	 * Render achievements summary for overview
	 */
	private static function render_achievements_summary( $stats ) {
		if ( empty( $stats ) ) {
			return '<p>No achievements yet</p>';
		}

		return sprintf(
			'<div class="summary-stat"><strong>%d</strong> Total Unlocked</div><div class="summary-badges">🥉 %d  🥈 %d  🥇 %d</div>',
			isset( $stats['total_unlocked'] ) ? $stats['total_unlocked'] : 0,
			isset( $stats['bronze_count'] ) ? $stats['bronze_count'] : 0,
			isset( $stats['silver_count'] ) ? $stats['silver_count'] : 0,
			isset( $stats['gold_count'] ) ? $stats['gold_count'] : 0
		);
	}

	/**
	 * Render baselines summary for overview
	 */
	private static function render_baselines_summary( $baselines ) {
		if ( empty( $baselines ) || isset( $baselines['error'] ) ) {
			return '<p>Insufficient data</p>';
		}

		$trends = array();
		foreach ( array( 'order_value', 'orders', 'aov' ) as $metric ) {
			if ( isset( $baselines[ $metric ]['trend'] ) ) {
				$trends[] = $baselines[ $metric ]['trend'];
			}
		}

		$improving = count( array_filter( $trends, function( $t ) { return $t === 'improving'; } ) );
		$total = count( $trends );

		$trend_icon = $improving > $total / 2 ? '↗' : ( $improving < $total / 2 ? '↘' : '→' );
		$trend_text = $improving > $total / 2 ? 'Improving' : ( $improving < $total / 2 ? 'Declining' : 'Stable' );

		return sprintf(
			'<div class="summary-stat"><strong>%s</strong> %s</div><div class="summary-trend">%d/%d metrics improving</div>',
			$trend_icon,
			$trend_text,
			$improving,
			$total
		);
	}

	/**
	 * Get quick stats for overview
	 */
	private static function get_quick_stats( $goals, $achievements_stats, $baselines ) {
		$stats = array();

		// Order Value stat
		if ( isset( $goals['order_value'] ) ) {
			$stats[] = array(
				'label' => 'Order Value Progress',
				'value' => number_format( $goals['order_value']['percentage'], 0 ) . '%'
			);
		}

		// Orders stat
		if ( isset( $goals['orders'] ) ) {
			$stats[] = array(
				'label' => 'Orders Progress',
				'value' => number_format( $goals['orders']['percentage'], 0 ) . '%'
			);
		}

		// AOV stat
		if ( isset( $goals['aov'] ) ) {
			$stats[] = array(
				'label' => 'AOV Progress',
				'value' => number_format( $goals['aov']['percentage'], 0 ) . '%'
			);
		}

		// Next achievement
		if ( isset( $achievements_stats['next_achievement'] ) ) {
			$next = $achievements_stats['next_achievement'];
			$stats[] = array(
				'label' => 'Next Achievement',
				'value' => number_format( $next['percentage'], 0 ) . '%'
			);
		}

		return $stats;
	}

	/**
	 * Get bonus milestones for user (Phase 2 Part 3)
	 * 
	 * @param int $user_id User ID
	 * @return array Bonus milestones data
	 */
	private static function get_bonus_milestones( $user_id ) {
		// Get user's role
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array();
		}

		$user_roles = $user->roles;
		$employee_roles = get_option( 'wc_tp_employee_roles', array() );
		
		// Find first matching employee role
		$employee_role = '';
		foreach ( $user_roles as $role ) {
			if ( in_array( $role, $employee_roles ) ) {
				$employee_role = $role;
				break;
			}
		}

		if ( empty( $employee_role ) ) {
			return array();
		}

		// Get bonus configuration - always fresh from database
		$bonus_config = get_option( 'wc_tp_achievement_bonuses', array() );
		
		// Validate bonus config structure
		if ( empty( $bonus_config ) || ! isset( $bonus_config['rules'] ) || ! is_array( $bonus_config['rules'] ) ) {
			return array();
		}

		// If no rules exist, return empty
		if ( count( $bonus_config['rules'] ) === 0 ) {
			return array();
		}

		// Get current streaks
		$streaks = get_user_meta( $user_id, '_wc_tp_badge_streaks', true );
		if ( ! is_array( $streaks ) ) {
			$streaks = array(
				'bronze' => array( 'count' => 0, 'last_month' => '' ),
				'silver' => array( 'count' => 0, 'last_month' => '' ),
				'gold' => array( 'count' => 0, 'last_month' => '' ),
			);
		}

		// Get awarded bonuses (for non-repeatable tracking)
		$awarded_bonuses = get_user_meta( $user_id, '_wc_tp_awarded_bonuses', true );
		if ( ! is_array( $awarded_bonuses ) ) {
			$awarded_bonuses = array();
		}

		$milestones = array();
		$rule_count = 0;

		// Process each bonus rule
		foreach ( $bonus_config['rules'] as $index => $rule ) {
			// Validate rule structure - all required fields must exist
			if ( ! isset( $rule['tier'] ) || empty( $rule['tier'] ) || 
				 ! isset( $rule['streak_count'] ) || empty( $rule['streak_count'] ) || 
				 ! isset( $rule['bonus_description'] ) || empty( $rule['bonus_description'] ) ) {
				continue;
			}

			// Check if user's role is eligible
			// If eligible_roles is empty or not an array, skip this rule
			if ( ! isset( $rule['eligible_roles'] ) || ! is_array( $rule['eligible_roles'] ) || empty( $rule['eligible_roles'] ) ) {
				continue;
			}
			
			// Check if user's role is in the eligible roles list
			if ( ! in_array( $employee_role, $rule['eligible_roles'] ) ) {
				continue;
			}

			$tier = sanitize_text_field( $rule['tier'] );
			$required_months = intval( $rule['streak_count'] );
			$current_streak = isset( $streaks[ $tier ]['count'] ) ? intval( $streaks[ $tier ]['count'] ) : 0;
			
			// Properly handle repeatable flag - ensure it's a boolean
			$repeatable = isset( $rule['repeatable'] ) && $rule['repeatable'] ? true : false;

			// Check if already awarded (for non-repeatable)
			$bonus_key = $employee_role . '_' . $tier . '_' . $required_months;
			$already_awarded = ! $repeatable && in_array( $bonus_key, $awarded_bonuses );

			// Calculate progress
			$progress_percentage = $required_months > 0 ? min( ( $current_streak / $required_months ) * 100, 100 ) : 0;
			$months_remaining = max( 0, $required_months - $current_streak );

			$milestones[] = array(
				'tier' => $tier,
				'required_months' => $required_months,
				'current_streak' => $current_streak,
				'months_remaining' => $months_remaining,
				'progress_percentage' => $progress_percentage,
				'bonus_type' => isset( $rule['bonus_type'] ) ? sanitize_text_field( $rule['bonus_type'] ) : 'money',
				'bonus_amount' => isset( $rule['bonus_amount'] ) ? floatval( $rule['bonus_amount'] ) : 0,
				'bonus_description' => sanitize_text_field( $rule['bonus_description'] ),
				'repeatable' => $repeatable,
				'already_awarded' => $already_awarded,
				'is_active' => $current_streak > 0 && $current_streak < $required_months,
				'is_achieved' => $current_streak >= $required_months,
			);
			
			$rule_count++;
		}

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 
				'Performance Tracker: User %d (%s) - Found %d eligible bonus milestones out of %d total rules',
				$user_id,
				$employee_role,
				count( $milestones ),
				count( $bonus_config['rules'] )
			) );
		}

		// Sort by tier (gold first) and then by required months
		usort( $milestones, function( $a, $b ) {
			$tier_order = array( 'gold' => 1, 'silver' => 2, 'bronze' => 3 );
			$tier_a = isset( $tier_order[ $a['tier'] ] ) ? $tier_order[ $a['tier'] ] : 4;
			$tier_b = isset( $tier_order[ $b['tier'] ] ) ? $tier_order[ $b['tier'] ] : 4;
			
			if ( $tier_a !== $tier_b ) {
				return $tier_a - $tier_b;
			}
			
			return $a['required_months'] - $b['required_months'];
		});

		return $milestones;
	}

	/**
	 * AJAX: Claim bonus (STEP 6)
	 */
	public static function ajax_claim_bonus() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$bonus_id = isset( $_POST['bonus_id'] ) ? sanitize_text_field( $_POST['bonus_id'] ) : '';
		$bonus_type = isset( $_POST['bonus_type'] ) ? sanitize_text_field( $_POST['bonus_type'] ) : '';
		$secret_code = isset( $_POST['secret_code'] ) ? sanitize_text_field( $_POST['secret_code'] ) : '';

		if ( empty( $bonus_id ) || empty( $bonus_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid bonus data', 'wc-team-payroll' ) ) );
		}

		// Get achieved bonuses
		$achieved_bonuses = get_user_meta( $user_id, '_wc_tp_achieved_bonuses', true );
		if ( ! is_array( $achieved_bonuses ) ) {
			wp_send_json_error( array( 'message' => __( 'No bonuses found', 'wc-team-payroll' ) ) );
		}

		// Find the bonus
		$bonus_index = -1;
		$bonus = null;
		foreach ( $achieved_bonuses as $index => $b ) {
			if ( $b['id'] === $bonus_id ) {
				$bonus_index = $index;
				$bonus = $b;
				break;
			}
		}

		if ( $bonus_index === -1 || ! $bonus ) {
			wp_send_json_error( array( 'message' => __( 'Bonus not found', 'wc-team-payroll' ) ) );
		}

		// Check if already claimed
		if ( $bonus['status'] === 'claimed' ) {
			wp_send_json_error( array( 'message' => __( 'Bonus already claimed', 'wc-team-payroll' ) ) );
		}

		// Handle money bonus
		if ( $bonus_type === 'money' ) {
			// Add to earnings
			$current_earnings = get_user_meta( $user_id, '_wc_tp_total_earnings', true );
			if ( ! is_numeric( $current_earnings ) ) {
				$current_earnings = 0;
			}
			$new_earnings = floatval( $current_earnings ) + floatval( $bonus['bonus_amount'] );
			update_user_meta( $user_id, '_wc_tp_total_earnings', $new_earnings );

			// Update bonus status
			$achieved_bonuses[ $bonus_index ]['status'] = 'claimed';
			$achieved_bonuses[ $bonus_index ]['claimed_date'] = current_time( 'Y-m-d H:i:s' );
			$achieved_bonuses[ $bonus_index ]['claimed_by_user'] = true;
			update_user_meta( $user_id, '_wc_tp_achieved_bonuses', $achieved_bonuses );

			wp_send_json_success( array( 'message' => __( 'Bonus claimed successfully!', 'wc-team-payroll' ) ) );
		}
		// Handle physical bonus
		else {
			// Verify secret code
			if ( empty( $secret_code ) ) {
				wp_send_json_error( array( 'message' => __( 'Secret code required', 'wc-team-payroll' ) ) );
			}

			if ( $secret_code !== $bonus['secret_code'] ) {
				wp_send_json_error( array( 'message' => __( 'Invalid secret code', 'wc-team-payroll' ) ) );
			}

			// Update bonus status
			$achieved_bonuses[ $bonus_index ]['status'] = 'claimed';
			$achieved_bonuses[ $bonus_index ]['claimed_date'] = current_time( 'Y-m-d H:i:s' );
			$achieved_bonuses[ $bonus_index ]['claimed_by_user'] = true;
			update_user_meta( $user_id, '_wc_tp_achieved_bonuses', $achieved_bonuses );

			wp_send_json_success( array( 'message' => __( 'Bonus claimed successfully!', 'wc-team-payroll' ) ) );
		}
	}

	/**
	 * AJAX: Submit bonus (Admin) (STEP 8)
	 */
	public static function ajax_submit_bonus() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		// Check if user is admin
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$bonus_id = isset( $_POST['bonus_id'] ) ? sanitize_text_field( $_POST['bonus_id'] ) : '';
		$bonus_type = isset( $_POST['bonus_type'] ) ? sanitize_text_field( $_POST['bonus_type'] ) : '';

		if ( ! $user_id || empty( $bonus_id ) || empty( $bonus_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid bonus data', 'wc-team-payroll' ) ) );
		}

		// Get achieved bonuses
		$achieved_bonuses = get_user_meta( $user_id, '_wc_tp_achieved_bonuses', true );
		if ( ! is_array( $achieved_bonuses ) ) {
			wp_send_json_error( array( 'message' => __( 'No bonuses found', 'wc-team-payroll' ) ) );
		}

		// Find the bonus
		$bonus_index = -1;
		$bonus = null;
		foreach ( $achieved_bonuses as $index => $b ) {
			if ( $b['id'] === $bonus_id ) {
				$bonus_index = $index;
				$bonus = $b;
				break;
			}
		}

		if ( $bonus_index === -1 || ! $bonus ) {
			wp_send_json_error( array( 'message' => __( 'Bonus not found', 'wc-team-payroll' ) ) );
		}

		// Handle money bonus
		if ( $bonus_type === 'money' ) {
			// Add to earnings
			$current_earnings = get_user_meta( $user_id, '_wc_tp_total_earnings', true );
			if ( ! is_numeric( $current_earnings ) ) {
				$current_earnings = 0;
			}
			$new_earnings = floatval( $current_earnings ) + floatval( $bonus['bonus_amount'] );
			update_user_meta( $user_id, '_wc_tp_total_earnings', $new_earnings );

			// Update bonus status to submitted
			$achieved_bonuses[ $bonus_index ]['status'] = 'submitted';
			$achieved_bonuses[ $bonus_index ]['claimed_date'] = current_time( 'Y-m-d H:i:s' );
			$achieved_bonuses[ $bonus_index ]['claimed_by_user'] = false;
			update_user_meta( $user_id, '_wc_tp_achieved_bonuses', $achieved_bonuses );

			wp_send_json_success( array( 
				'message' => __( 'Bonus submitted successfully!', 'wc-team-payroll' ),
				'secret_code' => null
			) );
		}
		// Handle physical bonus
		else {
			// Send email with secret code (STEP 9)
			$tracker = new WC_Team_Payroll_Performance_Tracker();
			$tracker->send_physical_bonus_email_public( $user_id, $bonus );

			// Update bonus status to submitted
			$achieved_bonuses[ $bonus_index ]['status'] = 'submitted';
			$achieved_bonuses[ $bonus_index ]['claimed_date'] = current_time( 'Y-m-d H:i:s' );
			$achieved_bonuses[ $bonus_index ]['claimed_by_user'] = false;
			update_user_meta( $user_id, '_wc_tp_achieved_bonuses', $achieved_bonuses );

			// Return secret code for admin to copy and send
			wp_send_json_success( array( 
				'message' => __( 'Secret code generated and email sent!', 'wc-team-payroll' ),
				'secret_code' => $bonus['secret_code']
			) );
		}
	}

	/**
	 * AJAX: Resend secret code email (STEP 10)
	 */
	public static function ajax_resend_secret_code() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		// Check if user is admin
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$secret_code = isset( $_POST['secret_code'] ) ? sanitize_text_field( $_POST['secret_code'] ) : '';

		if ( ! $user_id || empty( $secret_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data', 'wc-team-payroll' ) ) );
		}

		// Get achieved bonuses
		$achieved_bonuses = get_user_meta( $user_id, '_wc_tp_achieved_bonuses', true );
		if ( ! is_array( $achieved_bonuses ) ) {
			wp_send_json_error( array( 'message' => __( 'No bonuses found', 'wc-team-payroll' ) ) );
		}

		// Find the bonus with this secret code
		$bonus = null;
		foreach ( $achieved_bonuses as $b ) {
			if ( $b['secret_code'] === $secret_code && $b['status'] === 'submitted' ) {
				$bonus = $b;
				break;
			}
		}

		if ( ! $bonus ) {
			wp_send_json_error( array( 'message' => __( 'Bonus not found', 'wc-team-payroll' ) ) );
		}

		// Send email with secret code
		$tracker = new WC_Team_Payroll_Performance_Tracker();
		$tracker->send_physical_bonus_email_public( $user_id, $bonus );

		wp_send_json_success( array( 'message' => __( 'Secret code email resent successfully!', 'wc-team-payroll' ) ) );
	}

	/**
	 * AJAX: Get Leaderboard Data
	 */
	public static function ajax_get_leaderboard_data() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		// Get view_mode from request (same as Overview/Goals tabs)
		$view_mode = isset( $_POST['view_mode'] ) ? sanitize_text_field( $_POST['view_mode'] ) : 'current';
		
		// Get leaderboard configuration
		$config = get_option( 'wc_tp_leaderboard_config', array() );
		
		// Check if configuration exists
		if ( empty( $config ) ) {
			wp_send_json_error( array( 
				'message' => __( 'Leaderboard has not been configured yet. Please contact your administrator to set up the leaderboard.', 'wc-team-payroll' ),
				'disabled' => true
			) );
		}
		
		// Check if leaderboard is enabled
		if ( ! isset( $config['enabled'] ) || ! $config['enabled'] ) {
			wp_send_json_error( array( 
				'message' => __( 'Leaderboard is currently disabled. Please contact your administrator.', 'wc-team-payroll' ),
				'disabled' => true
			) );
		}

		// Get period type from goals configuration (same as Overview/Goals)
		$goals_config = get_option( 'wc_tp_goals_config', array() );
		$period_type = isset( $goals_config['period'] ) ? $goals_config['period'] : 'monthly';
		
		// Get period dates based on view mode
		$tracker = new WC_Team_Payroll_Performance_Tracker();
		$period_dates = $tracker->get_view_mode_dates( $view_mode, $period_type );
		
		// Add period info to config for leaderboard generation
		$config['period_start'] = $period_dates['start'];
		$config['period_end'] = $period_dates['end'];
		$config['period_id'] = $period_dates['period_id'];
		$config['period_type'] = $period_type;
		$config['view_mode'] = $view_mode;

		// Initialize leaderboard engine
		try {
			$engine = new WC_Team_Payroll_Leaderboard_Engine();
		} catch ( Exception $e ) {
			wp_send_json_error( array( 
				'message' => sprintf( __( 'Error initializing leaderboard: %s', 'wc-team-payroll' ), $e->getMessage() )
			) );
		}
		
		// Try to get cached data first (cache key includes view_mode)
		$cache_key = $period_dates['period_id'] . '_' . $view_mode;
		$cached_data = $engine->get_cached_leaderboard( $cache_key );
		
		// If no cache, generate fresh data
		if ( ! $cached_data ) {
			try {
				$cached_data = $engine->generate_leaderboard( $config );
				
				if ( isset( $cached_data['error'] ) && $cached_data['error'] ) {
					wp_send_json_error( array( 'message' => $cached_data['message'] ) );
				}
			} catch ( Exception $e ) {
				wp_send_json_error( array( 
					'message' => sprintf( __( 'Error generating leaderboard: %s', 'wc-team-payroll' ), $e->getMessage() )
				) );
			}
		}

		// Get display settings
		$display_limit = isset( $config['display_limit'] ) ? intval( $config['display_limit'] ) : 10;
		$show_user_rank = isset( $config['show_user_rank'] ) ? $config['show_user_rank'] : 1;
		$anonymize = isset( $config['anonymize'] ) ? $config['anonymize'] : 0;
		$show_scores = isset( $config['show_scores'] ) ? $config['show_scores'] : 1;
		$show_metrics = isset( $config['show_metrics'] ) ? $config['show_metrics'] : 1;

		// Get leaderboard data
		$leaderboard = isset( $cached_data['leaderboard'] ) ? $cached_data['leaderboard'] : array();
		
		// Apply display limit
		$displayed_leaderboard = $leaderboard;
		if ( $display_limit > 0 && $display_limit < count( $leaderboard ) ) {
			$displayed_leaderboard = array_slice( $leaderboard, 0, $display_limit );
		}

		// Apply anonymization
		if ( $anonymize ) {
			foreach ( $displayed_leaderboard as &$entry ) {
				// Don't anonymize current user
				if ( $entry['user_id'] !== $user_id ) {
					$entry['display_name'] = self::anonymize_name( $entry['display_name'] );
				}
			}
		}

		// Find current user's rank
		$user_rank_data = null;
		if ( $show_user_rank ) {
			// First check if user is in the filtered leaderboard
			foreach ( $leaderboard as $entry ) {
				if ( $entry['user_id'] === $user_id ) {
					$user_rank_data = $entry;
					break;
				}
			}
			
			// If user not found in leaderboard (filtered out by minimum orders), 
			// check if they should still be shown
			if ( ! $user_rank_data ) {
				// Check if user is an eligible employee
				$all_employees = isset( $cached_data['all_employees'] ) ? $cached_data['all_employees'] : array();
				if ( in_array( $user_id, $all_employees ) ) {
					// User is eligible but filtered out - show them with a note
					$user = get_userdata( $user_id );
					if ( $user ) {
						// Get user's actual metrics (even if below minimum)
						$engine = new WC_Team_Payroll_Leaderboard_Engine();
						$date_range = isset( $cached_data['date_range'] ) ? $cached_data['date_range'] : array();
						
						if ( ! empty( $date_range ) ) {
							$user_metrics = $engine->calculate_employee_metrics( $user_id, $date_range['start'], $date_range['end'] );
							
							$user_rank_data = array(
								'rank' => 0, // Not ranked
								'user_id' => $user_id,
								'display_name' => $user->display_name,
								'user_email' => $user->user_email,
								'score' => 0,
								'percentile' => 0,
								'metrics' => $user_metrics,
								'filtered_out' => true, // Flag to show they don't meet minimum
								'reason' => sprintf(
									__( 'Does not meet minimum requirement (%d orders)', 'wc-team-payroll' ),
									isset( $config['minimum_orders'] ) ? intval( $config['minimum_orders'] ) : 0
								),
							);
						}
					}
				}
			}
		}

		// Prepare response
		$response = array(
			'leaderboard' => $displayed_leaderboard,
			'user_rank' => $user_rank_data,
			'config' => array(
				'criteria' => isset( $config['criteria'] ) ? $config['criteria'] : 'total_earnings',
				'period' => $period,
				'display_limit' => $display_limit,
				'show_scores' => $show_scores,
				'show_metrics' => $show_metrics,
				'anonymize' => $anonymize,
			),
			'date_range' => isset( $cached_data['date_range'] ) ? $cached_data['date_range'] : array(),
			'total_employees' => isset( $cached_data['total_employees'] ) ? $cached_data['total_employees'] : 0,
			'generated_at' => isset( $cached_data['generated_at'] ) ? $cached_data['generated_at'] : '',
		);

		wp_send_json_success( $response );
	}

	/**
	 * Anonymize name to initials
	 */
	private static function anonymize_name( $name ) {
		$parts = explode( ' ', $name );
		$initials = '';
		
		foreach ( $parts as $part ) {
			if ( ! empty( $part ) ) {
				$initials .= strtoupper( substr( $part, 0, 1 ) ) . '.';
			}
		}
		
		return $initials;
	}
}

// Initialize AJAX handlers
WC_Team_Payroll_Performance_Tracker_AJAX::init();
