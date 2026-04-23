<?php
/**
 * Performance Settings Class
 * Handles Reports & Performance admin tab configuration
 *
 * @package WooCommerce Team Payroll
 * @since 1.0.52
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Performance_Settings {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add performance tab to settings
		add_filter( 'wc_team_payroll_settings_tabs', array( $this, 'add_performance_tab' ) );
		
		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		
		// AJAX handlers - Performance Scoring
		add_action( 'wp_ajax_wc_tp_save_performance_config', array( $this, 'ajax_save_performance_config' ) );
		add_action( 'wp_ajax_wc_tp_get_role_config', array( $this, 'ajax_get_role_config' ) );
		add_action( 'wp_ajax_wc_tp_clone_role_config', array( $this, 'ajax_clone_role_config' ) );
		add_action( 'wp_ajax_wc_tp_preview_calculation', array( $this, 'ajax_preview_calculation' ) );
		
		// AJAX handlers - Goals & Targets
		add_action( 'wp_ajax_wc_tp_get_role_goals', array( $this, 'ajax_get_role_goals' ) );
		add_action( 'wp_ajax_wc_tp_save_goals_config', array( $this, 'ajax_save_goals_config' ) );
		add_action( 'wp_ajax_wc_tp_clone_role_goals', array( $this, 'ajax_clone_role_goals' ) );
		
		// AJAX handlers - Achievements
		add_action( 'wp_ajax_wc_tp_get_role_achievements', array( $this, 'ajax_get_role_achievements' ) );
		add_action( 'wp_ajax_wc_tp_save_achievements_config', array( $this, 'ajax_save_achievements_config' ) );
		add_action( 'wp_ajax_wc_tp_clone_role_achievements', array( $this, 'ajax_clone_role_achievements' ) );
		
		// AJAX handlers - Baselines
		add_action( 'wp_ajax_wc_tp_save_baselines_config', array( $this, 'ajax_save_baselines_config' ) );
		add_action( 'wp_ajax_wc_tp_calculate_baseline_preview', array( $this, 'ajax_calculate_baseline_preview' ) );
		
		// AJAX handlers - Calculation Engine
		add_action( 'wp_ajax_wc_tp_save_calculation_config', array( $this, 'ajax_save_calculation_config' ) );
		add_action( 'wp_ajax_wc_tp_test_formula', array( $this, 'ajax_test_formula' ) );
		
		// AJAX handlers - System Configuration
		add_action( 'wp_ajax_wc_tp_save_system_config', array( $this, 'ajax_save_system_config' ) );
		add_action( 'wp_ajax_wc_tp_reset_all_data', array( $this, 'ajax_reset_all_data' ) );
		
		// AJAX handlers - Bonus Configuration (Phase 2 Part 2)
		add_action( 'wp_ajax_wc_tp_save_bonus_config', array( $this, 'ajax_save_bonus_config' ) );
		add_action( 'wp_ajax_wc_tp_get_bonus_config', array( $this, 'ajax_get_bonus_config' ) );
		
		// AJAX handlers - Leaderboard Configuration
		add_action( 'wp_ajax_wc_tp_save_leaderboard_config', array( $this, 'ajax_save_leaderboard_config' ) );
		add_action( 'wp_ajax_wc_tp_refresh_leaderboard', array( $this, 'ajax_refresh_leaderboard' ) );
	}

	/**
	 * Add performance tab to settings
	 */
	public function add_performance_tab( $tabs ) {
		$tabs['performance'] = __( 'Reports & Performance', 'wc-team-payroll' );
		return $tabs;
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our settings page
		if ( strpos( $hook, 'wc-team-payroll' ) === false ) {
			return;
		}

		// Check if we're on the performance tab
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
		if ( $current_tab !== 'performance' ) {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'wc-tp-performance-settings',
			WC_TEAM_PAYROLL_URL . 'assets/css/performance-settings.css',
			array(),
			WC_TEAM_PAYROLL_VERSION
		);

		// Enqueue JavaScript
		wp_enqueue_script(
			'wc-tp-performance-settings',
			WC_TEAM_PAYROLL_URL . 'assets/js/performance-settings.js',
			array( 'jquery' ),
			WC_TEAM_PAYROLL_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'wc-tp-performance-settings',
			'wcTpPerformance',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wc_tp_performance_nonce' ),
				'currency_symbol' => get_woocommerce_currency_symbol(),
				'strings'  => array(
					'save_success' => __( 'Configuration saved successfully!', 'wc-team-payroll' ),
					'save_error'   => __( 'Error saving configuration.', 'wc-team-payroll' ),
					'confirm_reset' => __( 'Are you sure you want to reset all configurations? This cannot be undone.', 'wc-team-payroll' ),
				),
			)
		);
	}

	/**
	 * Render performance settings tab content
	 */
	public function render_performance_tab() {
		?>
		<div class="wc-tp-performance-settings-wrapper">
			<h2><?php esc_html_e( 'Reports & Performance Configuration', 'wc-team-payroll' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure role-based performance scoring, goals, achievements, and baselines for your team members.', 'wc-team-payroll' ); ?>
			</p>

			<!-- Navigation Tabs -->
			<div class="wc-tp-perf-nav-tabs">
				<button type="button" class="wc-tp-perf-nav-tab active" data-section="scoring">
					<i class="dashicons dashicons-star-filled"></i>
					<?php esc_html_e( 'Performance Scoring', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="goals">
					<i class="dashicons dashicons-flag"></i>
					<?php esc_html_e( 'Goals & Targets', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="achievements">
					<i class="dashicons dashicons-awards"></i>
					<?php esc_html_e( 'Achievements', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="bonuses">
					<i class="dashicons dashicons-money-alt"></i>
					<?php esc_html_e( 'Bonus Configuration', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="leaderboard">
					<i class="dashicons dashicons-chart-bar"></i>
					<?php esc_html_e( 'Leaderboard', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="baselines">
					<i class="dashicons dashicons-chart-line"></i>
					<?php esc_html_e( 'Baselines', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="calculation">
					<i class="dashicons dashicons-calculator"></i>
					<?php esc_html_e( 'Calculation Engine', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="wc-tp-perf-nav-tab" data-section="system">
					<i class="dashicons dashicons-admin-settings"></i>
					<?php esc_html_e( 'System Config', 'wc-team-payroll' ); ?>
				</button>
			</div>

			<!-- Section: Performance Scoring -->
			<div class="wc-tp-perf-section active" id="wc-tp-perf-scoring">
				<?php $this->render_scoring_section(); ?>
			</div>

			<!-- Section: Goals & Targets -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-goals">
				<?php $this->render_goals_section(); ?>
			</div>

			<!-- Section: Achievements -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-achievements">
				<?php $this->render_achievements_section(); ?>
			</div>

			<!-- Section: Bonus Configuration -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-bonuses">
				<?php $this->render_bonuses_section(); ?>
			</div>

			<!-- Section: Leaderboard -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-leaderboard">
				<?php $this->render_leaderboard_section(); ?>
			</div>

			<!-- Section: Baselines -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-baselines">
				<?php $this->render_baselines_section(); ?>
			</div>

			<!-- Section: Calculation Engine -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-calculation">
				<?php $this->render_calculation_section(); ?>
			</div>

			<!-- Section: System Config -->
			<div class="wc-tp-perf-section" id="wc-tp-perf-system">
				<?php $this->render_system_section(); ?>
			</div>

			<!-- Save Button -->
			<div class="wc-tp-perf-save-wrapper">
				<button type="button" class="button button-primary button-large" id="wc-tp-save-performance">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Save All Configurations', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="wc-tp-export-performance">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export Settings', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="wc-tp-import-performance">
					<span class="dashicons dashicons-upload"></span>
					<?php esc_html_e( 'Import Settings', 'wc-team-payroll' ); ?>
				</button>
				<button type="button" class="button button-link-delete" id="wc-tp-reset-performance">
					<span class="dashicons dashicons-undo"></span>
					<?php esc_html_e( 'Reset All', 'wc-team-payroll' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Performance Scoring Section
	 */
	private function render_scoring_section() {
		// Get employee roles from settings (only configured employee roles)
		$all_roles = $this->get_all_roles();
		$performance_config = get_option( 'wc_tp_performance_config', array() );
		?>
		<div class="wc-tp-perf-scoring-wrapper">
			<h3><?php esc_html_e( 'Performance Scoring Matrix - Role-Based Configuration', 'wc-team-payroll' ); ?></h3>
			
			<!-- Base Score -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Base Score Configuration', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Universal starting score for all employees before applying performance factors.', 'wc-team-payroll' ); ?></p>
				<table class="form-table">
					<tr>
						<th><label for="base_score"><?php esc_html_e( 'Base Score', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" id="base_score" name="base_score" value="<?php echo esc_attr( isset( $performance_config['base_score'] ) ? $performance_config['base_score'] : 5 ); ?>" step="0.1" min="0" max="10" class="small-text" />
							<span class="description"><?php esc_html_e( 'points (0-10)', 'wc-team-payroll' ); ?></span>
						</td>
					</tr>
				</table>
			</div>

			<!-- Role Selector -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Configure Performance Factors by Role', 'wc-team-payroll' ); ?></h4>
				<p class="description">
					<?php esc_html_e( 'Configure performance scoring for each employee role. Only roles configured in Settings → WooCommerce → Employee User Roles are shown here.', 'wc-team-payroll' ); ?>
					<?php if ( empty( $all_roles ) ) : ?>
						<br><strong style="color: #d63638;"><?php esc_html_e( 'No employee roles configured. Please configure employee roles in Settings → WooCommerce → Employee User Roles first.', 'wc-team-payroll' ); ?></strong>
					<?php endif; ?>
				</p>
				<div class="wc-tp-role-selector-wrapper">
					<label for="wc-tp-role-selector"><?php esc_html_e( 'Select Employee Role to Configure:', 'wc-team-payroll' ); ?></label>
					
					<select id="wc-tp-role-selector" class="wc-tp-role-selector" <?php echo empty( $all_roles ) ? 'disabled' : ''; ?>>
						<option value=""><?php esc_html_e( '-- Select an Employee Role --', 'wc-team-payroll' ); ?></option>
						<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_name ); ?></option>
						<?php endforeach; ?>
					</select>
					
					<div class="wc-tp-role-actions">
						<button type="button" class="button" id="wc-tp-clone-role">
							<span class="dashicons dashicons-admin-page"></span>
							<?php esc_html_e( 'Clone from Another Role', 'wc-team-payroll' ); ?>
						</button>
						<button type="button" class="button" id="wc-tp-reset-role">
							<span class="dashicons dashicons-undo"></span>
							<?php esc_html_e( 'Reset to Default', 'wc-team-payroll' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Role Configuration Container (Loaded via AJAX) -->
			<div id="wc-tp-role-config-container">
				<div class="wc-tp-empty-state">
					<span class="dashicons dashicons-admin-users"></span>
					<p><?php esc_html_e( 'Select an employee role above to configure performance scoring factors.', 'wc-team-payroll' ); ?></p>
					<?php if ( empty( $all_roles ) ) : ?>
						<p><a href="?page=wc-team-payroll-settings&tab=woocommerce" class="button button-secondary"><?php esc_html_e( 'Configure Employee Roles', 'wc-team-payroll' ); ?></a></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Goals & Targets Section
	 */
	private function render_goals_section() {
		// Get employee roles from settings (only configured employee roles)
		$all_roles = $this->get_all_roles();
		$goals_config = get_option( 'wc_tp_goals_config', array() );
		?>
		<div class="wc-tp-perf-goals-wrapper">
			<h3><?php esc_html_e( 'Goals & Targets Configuration - Role-Based Matrix', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure different goal targets for each role based on their responsibilities and expectations. Set minimum, target, and stretch goals for earnings, orders, and AOV.', 'wc-team-payroll' ); ?></p>
			
			<!-- Global Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Global Goal Settings', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how goals are displayed and calculated across the system.', 'wc-team-payroll' ); ?></p>
				<table class="form-table">
					<tr>
						<th><label for="goals_period"><?php esc_html_e( 'Default Goal Period', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="goals_period" name="goals_period" class="wc-tp-goals-setting">
								<?php
								$period = isset( $goals_config['period'] ) ? $goals_config['period'] : 'monthly';
								$periods = array(
									'weekly' => __( 'Weekly', 'wc-team-payroll' ),
									'monthly' => __( 'Monthly', 'wc-team-payroll' ),
									'quarterly' => __( 'Quarterly', 'wc-team-payroll' ),
									'yearly' => __( 'Yearly', 'wc-team-payroll' ),
								);
								foreach ( $periods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $period, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Default time period for goal tracking', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="goals_display_mode"><?php esc_html_e( 'Display Mode', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="goals_display_mode" name="goals_display_mode" class="wc-tp-goals-setting">
								<?php
								$display_mode = isset( $goals_config['display_mode'] ) ? $goals_config['display_mode'] : 'percentage';
								$modes = array(
									'percentage' => __( 'Percentage Progress', 'wc-team-payroll' ),
									'absolute' => __( 'Absolute Values', 'wc-team-payroll' ),
									'both' => __( 'Both', 'wc-team-payroll' ),
								);
								foreach ( $modes as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $display_mode, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How to display goal progress on reports page', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="goals_show_stretch"><?php esc_html_e( 'Show Stretch Goals', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" id="goals_show_stretch" name="goals_show_stretch" value="1" class="wc-tp-goals-setting" <?php checked( isset( $goals_config['show_stretch'] ) ? $goals_config['show_stretch'] : 1, 1 ); ?> />
							<label for="goals_show_stretch"><?php esc_html_e( 'Display stretch goals to employees', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
				</table>
			</div>

			<!-- Role Selector -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Configure Goals by Role', 'wc-team-payroll' ); ?></h4>
				<p class="description">
					<?php esc_html_e( 'Set different goal targets for each employee role. Only roles configured in Settings → WooCommerce → Employee User Roles are shown here.', 'wc-team-payroll' ); ?>
					<?php if ( empty( $all_roles ) ) : ?>
						<br><strong style="color: #d63638;"><?php esc_html_e( 'No employee roles configured. Please configure employee roles in Settings → WooCommerce → Employee User Roles first.', 'wc-team-payroll' ); ?></strong>
					<?php endif; ?>
				</p>
				<div class="wc-tp-role-selector-wrapper">
					<label for="wc-tp-goals-role-selector"><?php esc_html_e( 'Select Employee Role to Configure:', 'wc-team-payroll' ); ?></label>
					<select id="wc-tp-goals-role-selector" class="wc-tp-role-selector" <?php echo empty( $all_roles ) ? 'disabled' : ''; ?>>
						<option value=""><?php esc_html_e( '-- Select an Employee Role --', 'wc-team-payroll' ); ?></option>
						<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_name ); ?></option>
						<?php endforeach; ?>
					</select>
					
					<div class="wc-tp-role-actions">
						<button type="button" class="button" id="wc-tp-clone-goals-role">
							<span class="dashicons dashicons-admin-page"></span>
							<?php esc_html_e( 'Clone from Another Role', 'wc-team-payroll' ); ?>
						</button>
						<button type="button" class="button" id="wc-tp-reset-goals-role">
							<span class="dashicons dashicons-undo"></span>
							<?php esc_html_e( 'Reset to Default', 'wc-team-payroll' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Role Goals Configuration Container (Loaded via AJAX) -->
			<div id="wc-tp-goals-config-container">
				<div class="wc-tp-empty-state">
					<span class="dashicons dashicons-flag"></span>
					<p><?php esc_html_e( 'Select an employee role above to configure goals and targets.', 'wc-team-payroll' ); ?></p>
					<?php if ( empty( $all_roles ) ) : ?>
						<p><a href="?page=wc-team-payroll-settings&tab=woocommerce" class="button button-secondary"><?php esc_html_e( 'Configure Employee Roles', 'wc-team-payroll' ); ?></a></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Achievements Section
	 */
	private function render_achievements_section() {
		// Get employee roles from settings (only configured employee roles)
		$all_roles = $this->get_all_roles();
		$achievements_config = get_option( 'wc_tp_achievements_config', array() );
		?>
		<div class="wc-tp-perf-achievements-wrapper">
			<h3><?php esc_html_e( 'Achievements System - Role-Aware Configuration', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Create role-specific achievements with different criteria and rewards for each role. Employees earn Bronze, Silver, and Gold badges based on their performance.', 'wc-team-payroll' ); ?></p>
			
			<!-- Global Achievement Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Global Achievement Settings', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how achievements are displayed and awarded across the system.', 'wc-team-payroll' ); ?></p>
				<table class="form-table">
					<tr>
						<th><label for="achievements_enabled"><?php esc_html_e( 'Enable Achievements', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" id="achievements_enabled" name="achievements_enabled" value="1" class="wc-tp-achievements-setting" <?php checked( isset( $achievements_config['enabled'] ) ? $achievements_config['enabled'] : 1, 1 ); ?> />
							<label for="achievements_enabled"><?php esc_html_e( 'Display achievements on employee reports page', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><label for="achievements_display_style"><?php esc_html_e( 'Display Style', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="achievements_display_style" name="achievements_display_style" class="wc-tp-achievements-setting">
								<?php
								$display_style = isset( $achievements_config['display_style'] ) ? $achievements_config['display_style'] : 'badges';
								$styles = array(
									'badges' => __( 'Badge Icons', 'wc-team-payroll' ),
									'cards' => __( 'Achievement Cards', 'wc-team-payroll' ),
									'list' => __( 'Simple List', 'wc-team-payroll' ),
								);
								foreach ( $styles as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $display_style, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How achievements are displayed to employees', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="achievements_period"><?php esc_html_e( 'Achievement Period', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="achievements_period" name="achievements_period" class="wc-tp-achievements-setting">
								<?php
								$achievement_period = isset( $achievements_config['period'] ) ? $achievements_config['period'] : 'monthly';
								$periods = array(
									'daily' => __( 'Daily (resets every day at midnight)', 'wc-team-payroll' ),
									'weekly' => __( 'Weekly (resets on WordPress start of week)', 'wc-team-payroll' ),
									'monthly' => __( 'Monthly (resets on 1st of month)', 'wc-team-payroll' ),
									'quarterly' => __( 'Quarterly (resets on 1st of Q1, Q2, Q3, Q4)', 'wc-team-payroll' ),
									'half_yearly' => __( 'Half-Yearly (resets Jan 1 & Jul 1)', 'wc-team-payroll' ),
									'yearly' => __( 'Yearly (resets on Jan 1)', 'wc-team-payroll' ),
								);
								foreach ( $periods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $achievement_period, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How often achievements reset and can be re-earned. Employees can earn badges multiple times across different periods.', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="achievements_show_locked"><?php esc_html_e( 'Show Locked Achievements', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" id="achievements_show_locked" name="achievements_show_locked" value="1" class="wc-tp-achievements-setting" <?php checked( isset( $achievements_config['show_locked'] ) ? $achievements_config['show_locked'] : 1, 1 ); ?> />
							<label for="achievements_show_locked"><?php esc_html_e( 'Show achievements that haven\'t been earned yet (grayed out)', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><label for="achievements_notification"><?php esc_html_e( 'Achievement Notifications', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" id="achievements_notification" name="achievements_notification" value="1" class="wc-tp-achievements-setting" <?php checked( isset( $achievements_config['notification'] ) ? $achievements_config['notification'] : 1, 1 ); ?> />
							<label for="achievements_notification"><?php esc_html_e( 'Show notification when employee earns a new achievement', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
				</table>
			</div>

			<!-- Role Selector -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Configure Achievements by Role', 'wc-team-payroll' ); ?></h4>
				<p class="description">
					<?php esc_html_e( 'Create role-specific achievements with different criteria for each employee role. Only roles configured in Settings → WooCommerce → Employee User Roles are shown here.', 'wc-team-payroll' ); ?>
					<?php if ( empty( $all_roles ) ) : ?>
						<br><strong style="color: #d63638;"><?php esc_html_e( 'No employee roles configured. Please configure employee roles in Settings → WooCommerce → Employee User Roles first.', 'wc-team-payroll' ); ?></strong>
					<?php endif; ?>
				</p>
				<div class="wc-tp-role-selector-wrapper">
					<label for="wc-tp-achievements-role-selector"><?php esc_html_e( 'Select Employee Role to Configure:', 'wc-team-payroll' ); ?></label>
					<select id="wc-tp-achievements-role-selector" class="wc-tp-role-selector" <?php echo empty( $all_roles ) ? 'disabled' : ''; ?>>
						<option value=""><?php esc_html_e( '-- Select an Employee Role --', 'wc-team-payroll' ); ?></option>
						<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_name ); ?></option>
						<?php endforeach; ?>
					</select>
					
					<div class="wc-tp-role-actions">
						<button type="button" class="button" id="wc-tp-clone-achievements-role">
							<span class="dashicons dashicons-admin-page"></span>
							<?php esc_html_e( 'Clone from Another Role', 'wc-team-payroll' ); ?>
						</button>
						<button type="button" class="button" id="wc-tp-reset-achievements-role">
							<span class="dashicons dashicons-undo"></span>
							<?php esc_html_e( 'Reset to Default', 'wc-team-payroll' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Role Achievements Configuration Container (Loaded via AJAX) -->
			<div id="wc-tp-achievements-config-container">
				<div class="wc-tp-empty-state">
					<span class="dashicons dashicons-awards"></span>
					<p><?php esc_html_e( 'Select an employee role above to configure achievements and badges.', 'wc-team-payroll' ); ?></p>
					<?php if ( empty( $all_roles ) ) : ?>
						<p><a href="?page=wc-team-payroll-settings&tab=woocommerce" class="button button-secondary"><?php esc_html_e( 'Configure Employee Roles', 'wc-team-payroll' ); ?></a></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Bonus Configuration Section (Phase 2 Part 2)
	 */
	private function render_bonuses_section() {
		// Get employee roles from settings
		$all_roles = $this->get_all_roles();
		$bonus_config = get_option( 'wc_tp_achievement_bonuses', array() );
		?>
		<div class="wc-tp-perf-bonuses-wrapper">
			<h3><?php esc_html_e( 'Badge Streak Bonus Configuration', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure automatic bonuses for employees who maintain badge levels for consecutive months. Bonuses can be monetary rewards, physical rewards, or other recognition.', 'wc-team-payroll' ); ?></p>
			
			<!-- Global Bonus Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Global Bonus Settings', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how bonuses are awarded and displayed across the system.', 'wc-team-payroll' ); ?></p>
				<table class="form-table">
					<tr>
						<th><label for="bonus_enabled"><?php esc_html_e( 'Enable Bonus System', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="hidden" name="bonus_enabled" value="0" />
							<input type="checkbox" id="bonus_enabled" name="bonus_enabled" value="1" class="wc-tp-bonus-setting" <?php checked( isset( $bonus_config['enabled'] ) ? $bonus_config['enabled'] : 1, 1 ); ?> />
							<label for="bonus_enabled"><?php esc_html_e( 'Automatically award bonuses when streak milestones are achieved', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><label for="bonus_notification"><?php esc_html_e( 'Email Notifications', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="hidden" name="bonus_notification" value="0" />
							<input type="checkbox" id="bonus_notification" name="bonus_notification" value="1" class="wc-tp-bonus-setting" <?php checked( isset( $bonus_config['notification'] ) ? $bonus_config['notification'] : 1, 1 ); ?> />
							<label for="bonus_notification"><?php esc_html_e( 'Send email notification when employee earns a bonus', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><label for="bonus_show_progress"><?php esc_html_e( 'Show Progress to Employees', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="hidden" name="bonus_show_progress" value="0" />
							<input type="checkbox" id="bonus_show_progress" name="bonus_show_progress" value="1" class="wc-tp-bonus-setting" <?php checked( isset( $bonus_config['show_progress'] ) ? $bonus_config['show_progress'] : 1, 1 ); ?> />
							<label for="bonus_show_progress"><?php esc_html_e( 'Display bonus milestone progress in Performance Tracker', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
				</table>
			</div>

			<!-- Bonus Rules Repeater -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Bonus Rules', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Define bonus rules for different badge tiers and streak counts. Employees will automatically receive bonuses when they achieve the specified consecutive months at a badge level.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-bonus-rules-container">
					<div id="wc-tp-bonus-rules-list">
						<?php
						if ( ! empty( $bonus_config['rules'] ) && is_array( $bonus_config['rules'] ) ) {
							foreach ( $bonus_config['rules'] as $index => $rule ) {
								$this->render_bonus_rule_row( $index, $rule, $all_roles );
							}
						} else {
							// Show one empty row by default
							$this->render_bonus_rule_row( 0, array(), $all_roles );
						}
						?>
					</div>
					
					<div class="wc-tp-bonus-rules-actions">
						<button type="button" class="button button-secondary" id="wc-tp-add-bonus-rule">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php esc_html_e( 'Add Bonus Rule', 'wc-team-payroll' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Bonus Rule Template (Hidden) -->
			<script type="text/template" id="wc-tp-bonus-rule-template">
				<?php $this->render_bonus_rule_row( '{{INDEX}}', array(), $all_roles, true ); ?>
			</script>
		</div>
		<?php
	}

	/**
	 * Render a single bonus rule row
	 */
	private function render_bonus_rule_row( $index, $rule = array(), $all_roles = array(), $is_template = false ) {
		$rule_id = isset( $rule['rule_id'] ) ? $rule['rule_id'] : '';
		$tier = isset( $rule['tier'] ) ? $rule['tier'] : '';
		$streak_count = isset( $rule['streak_count'] ) ? $rule['streak_count'] : '';
		$bonus_type = isset( $rule['bonus_type'] ) ? $rule['bonus_type'] : 'money';
		$bonus_amount = isset( $rule['bonus_amount'] ) ? $rule['bonus_amount'] : '';
		$bonus_description = isset( $rule['bonus_description'] ) ? $rule['bonus_description'] : '';
		$repeatable = isset( $rule['repeatable'] ) ? $rule['repeatable'] : 0;
		$eligible_roles = isset( $rule['eligible_roles'] ) ? $rule['eligible_roles'] : array();
		
		$index_attr = $is_template ? '{{INDEX}}' : $index;
		?>
		<div class="wc-tp-bonus-rule-row" data-index="<?php echo esc_attr( $index_attr ); ?>" data-rule-id="<?php echo esc_attr( $rule_id ); ?>">
			<div class="wc-tp-bonus-rule-header">
				<span class="wc-tp-bonus-rule-number"><?php echo esc_html( sprintf( __( 'Rule #%s', 'wc-team-payroll' ), $is_template ? '{{INDEX}}' : ( $index + 1 ) ) ); ?></span>
				<?php if ( $rule_id ) : ?>
					<span class="wc-tp-bonus-rule-id" style="color: #999; font-size: 12px; margin-left: 10px;"><?php echo esc_html( sprintf( __( 'ID: %d', 'wc-team-payroll' ), $rule_id ) ); ?></span>
				<?php endif; ?>
				<button type="button" class="button button-link-delete wc-tp-remove-bonus-rule">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Remove', 'wc-team-payroll' ); ?>
				</button>
			</div>
			
			<!-- Hidden field to store rule_id -->
			<input type="hidden" name="bonus_rules[<?php echo esc_attr( $index_attr ); ?>][rule_id]" value="<?php echo esc_attr( $rule_id ); ?>" class="wc-tp-bonus-rule-id-field" />
			
			<div class="wc-tp-bonus-rule-fields">
				<div class="wc-tp-bonus-field">
					<label><?php esc_html_e( 'Badge Tier', 'wc-team-payroll' ); ?></label>
					<select name="bonus_rules[<?php echo esc_attr( $index_attr ); ?>][tier]" class="wc-tp-bonus-tier" required>
						<option value=""><?php esc_html_e( '-- Select Tier --', 'wc-team-payroll' ); ?></option>
						<option value="bronze" <?php selected( $tier, 'bronze' ); ?>><?php esc_html_e( '🥉 Bronze', 'wc-team-payroll' ); ?></option>
						<option value="silver" <?php selected( $tier, 'silver' ); ?>><?php esc_html_e( '🥈 Silver', 'wc-team-payroll' ); ?></option>
						<option value="gold" <?php selected( $tier, 'gold' ); ?>><?php esc_html_e( '🥇 Gold', 'wc-team-payroll' ); ?></option>
					</select>
				</div>
				
				<div class="wc-tp-bonus-field">
					<label><?php esc_html_e( 'Consecutive Months', 'wc-team-payroll' ); ?></label>
					<input type="number" name="bonus_rules[<?php echo esc_attr( $index_attr ); ?>][streak_count]" value="<?php echo esc_attr( $streak_count ); ?>" min="1" max="24" step="1" class="small-text" required />
					<span class="description"><?php esc_html_e( 'months', 'wc-team-payroll' ); ?></span>
				</div>
				
				<div class="wc-tp-bonus-field">
					<label><?php esc_html_e( 'Bonus Type', 'wc-team-payroll' ); ?></label>
					<select name="bonus_rules[<?php echo esc_attr( $index_attr ); ?>][bonus_type]" class="wc-tp-bonus-type" required>
						<option value="money" <?php selected( $bonus_type, 'money' ); ?>><?php esc_html_e( 'Money (Auto-added to earnings)', 'wc-team-payroll' ); ?></option>
						<option value="reward" <?php selected( $bonus_type, 'reward' ); ?>><?php esc_html_e( 'Physical Reward (e.g., Motorcycle)', 'wc-team-payroll' ); ?></option>
						<option value="other" <?php selected( $bonus_type, 'other' ); ?>><?php esc_html_e( 'Other Recognition', 'wc-team-payroll' ); ?></option>
					</select>
				</div>
				
				<div class="wc-tp-bonus-field wc-tp-bonus-amount-field" style="<?php echo $bonus_type !== 'money' ? 'display:none;' : ''; ?>">
					<label><?php esc_html_e( 'Bonus Amount', 'wc-team-payroll' ); ?></label>
					<input type="number" name="bonus_rules[<?php echo esc_attr( $index_attr ); ?>][bonus_amount]" value="<?php echo esc_attr( $bonus_amount ); ?>" min="0" step="0.01" class="regular-text" />
					<span class="description"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
				</div>
				
				<div class="wc-tp-bonus-field wc-tp-bonus-full-width">
					<label><?php esc_html_e( 'Bonus Description', 'wc-team-payroll' ); ?></label>
					<input type="text" name="bonus_rules[<?php echo esc_attr( $index_attr ); ?>][bonus_description]" value="<?php echo esc_attr( $bonus_description ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., R15 Motorcycle, Certificate of Excellence', 'wc-team-payroll' ); ?>" required />
					<p class="description"><?php esc_html_e( 'Description shown to employees and in notifications', 'wc-team-payroll' ); ?></p>
				</div>
				
				<div class="wc-tp-bonus-field wc-tp-bonus-full-width">
					<label><?php esc_html_e( 'Eligible Roles', 'wc-team-payroll' ); ?></label>
					<div class="wc-tp-bonus-roles">
						<?php if ( ! empty( $all_roles ) ) : ?>
							<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
								<label class="wc-tp-checkbox-label">
									<input type="checkbox" name="bonus_rules[<?php echo esc_attr( $index_attr ); ?>][eligible_roles][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, (array) $eligible_roles ) ); ?> />
									<?php echo esc_html( $role_name ); ?>
								</label>
							<?php endforeach; ?>
						<?php else : ?>
							<p class="description" style="color: #d63638;"><?php esc_html_e( 'No employee roles configured. Please configure employee roles first.', 'wc-team-payroll' ); ?></p>
						<?php endif; ?>
					</div>
					<p class="description"><?php esc_html_e( 'Select which employee roles are eligible for this bonus', 'wc-team-payroll' ); ?></p>
				</div>
				
				<div class="wc-tp-bonus-field wc-tp-bonus-checkbox">
					<label>
						<input type="hidden" name="bonus_rules[<?php echo esc_attr( $index_attr ); ?>][repeatable]" value="0" />
					<input type="checkbox" name="bonus_rules[<?php echo esc_attr( $index_attr ); ?>][repeatable]" value="1" <?php checked( $repeatable, 1 ); ?> />
						<?php esc_html_e( 'Repeatable (can be earned multiple times)', 'wc-team-payroll' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'If unchecked, bonus can only be earned once per employee', 'wc-team-payroll' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Leaderboard Section
	 */
	private function render_leaderboard_section() {
		$leaderboard_config = get_option( 'wc_tp_leaderboard_config', array() );
		?>
		<div class="wc-tp-perf-leaderboard-wrapper">
			<h3><?php esc_html_e( 'Team Leaderboard Configuration', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure how employees are ranked and displayed on the team leaderboard. Choose from single metrics or composite scoring with multiple weighted criteria.', 'wc-team-payroll' ); ?></p>
			
			<!-- Enable/Disable Leaderboard -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Leaderboard Status', 'wc-team-payroll' ); ?></h4>
				<table class="form-table">
					<tr>
						<th><label for="leaderboard_enabled"><?php esc_html_e( 'Enable Leaderboard', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="hidden" name="leaderboard_enabled" value="0" />
							<input type="checkbox" 
								   id="leaderboard_enabled" 
								   name="leaderboard_enabled" 
								   value="1" 
								   class="wc-tp-leaderboard-setting" 
								   <?php checked( isset( $leaderboard_config['enabled'] ) ? $leaderboard_config['enabled'] : 0, 1 ); ?> />
							<label for="leaderboard_enabled"><?php esc_html_e( 'Display team leaderboard in Reports page', 'wc-team-payroll' ); ?></label>
							<p class="description"><?php esc_html_e( 'Shows how employees rank compared to teammates based on selected criteria', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Ranking Criteria -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Ranking Criteria', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Select how employees should be ranked. Composite criteria use percentile-based scoring with weighted metrics.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="leaderboard_criteria"><?php esc_html_e( 'Criteria Type', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="leaderboard_criteria" name="leaderboard_criteria" class="wc-tp-leaderboard-setting regular-text">
								<?php
								$current_criteria = isset( $leaderboard_config['criteria'] ) ? $leaderboard_config['criteria'] : 'total_earnings';
								
								// Single Metrics
								echo '<optgroup label="' . esc_attr__( 'Single Metrics', 'wc-team-payroll' ) . '">';
								$single_metrics = array(
									'total_earnings' => __( 'Total Earnings', 'wc-team-payroll' ),
									'total_orders' => __( 'Total Orders', 'wc-team-payroll' ),
									'average_order_value' => __( 'Average Order Value (AOV)', 'wc-team-payroll' ),
									'commission_earnings' => __( 'Commission Earnings', 'wc-team-payroll' ),
									'total_order_value' => __( 'Total Order Value', 'wc-team-payroll' ),
									'achievement_score' => __( 'Achievement Score', 'wc-team-payroll' ),
									'goal_completion_rate' => __( 'Goal Completion Rate', 'wc-team-payroll' ),
								);
								foreach ( $single_metrics as $key => $label ) {
									echo '<option value="' . esc_attr( $key ) . '"' . selected( $current_criteria, $key, false ) . '>' . esc_html( $label ) . '</option>';
								}
								echo '</optgroup>';
								
								// Dual Metrics (Composite)
								echo '<optgroup label="' . esc_attr__( 'Dual Metrics (Composite)', 'wc-team-payroll' ) . '">';
								$dual_metrics = array(
									'earnings_orders_50_50' => __( 'Earnings + Orders (50% + 50%)', 'wc-team-payroll' ),
									'earnings_aov_60_40' => __( 'Earnings + AOV (60% + 40%)', 'wc-team-payroll' ),
									'orders_aov_50_50' => __( 'Orders + AOV (50% + 50%)', 'wc-team-payroll' ),
									'earnings_achievement_70_30' => __( 'Earnings + Achievement (70% + 30%)', 'wc-team-payroll' ),
									'orders_achievement_60_40' => __( 'Orders + Achievement (60% + 40%)', 'wc-team-payroll' ),
								);
								foreach ( $dual_metrics as $key => $label ) {
									echo '<option value="' . esc_attr( $key ) . '"' . selected( $current_criteria, $key, false ) . '>' . esc_html( $label ) . '</option>';
								}
								echo '</optgroup>';
								
								// Triple Metrics (Comprehensive)
								echo '<optgroup label="' . esc_attr__( 'Triple Metrics (Comprehensive)', 'wc-team-payroll' ) . '">';
								$triple_metrics = array(
									'earnings_orders_aov_40_30_30' => __( 'Earnings + Orders + AOV (40% + 30% + 30%)', 'wc-team-payroll' ),
									'earnings_orders_achievement_50_30_20' => __( 'Earnings + Orders + Achievement (50% + 30% + 20%)', 'wc-team-payroll' ),
									'earnings_aov_achievement_50_25_25' => __( 'Earnings + AOV + Achievement (50% + 25% + 25%)', 'wc-team-payroll' ),
									'orders_aov_achievement_40_30_30' => __( 'Orders + AOV + Achievement (40% + 30% + 30%)', 'wc-team-payroll' ),
								);
								foreach ( $triple_metrics as $key => $label ) {
									echo '<option value="' . esc_attr( $key ) . '"' . selected( $current_criteria, $key, false ) . '>' . esc_html( $label ) . '</option>';
								}
								echo '</optgroup>';
								
								// Complete Score (All-Round)
								echo '<optgroup label="' . esc_attr__( 'Complete Score (All-Round)', 'wc-team-payroll' ) . '">';
								echo '<option value="complete_score"' . selected( $current_criteria, 'complete_score', false ) . '>' . esc_html__( 'Complete Score (Earnings 35% + Orders 25% + AOV 20% + Achievement 15% + Goals 5%)', 'wc-team-payroll' ) . '</option>';
								echo '</optgroup>';
								?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Composite criteria use percentile ranking: Score = Σ(weight × percentile) × 100', 'wc-team-payroll' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th><label><?php esc_html_e( 'Criteria Explanation', 'wc-team-payroll' ); ?></label></th>
						<td>
							<div class="wc-tp-criteria-explanation" style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 12px 15px; margin-top: 5px;">
								<p style="margin: 0 0 10px 0;"><strong><?php esc_html_e( 'How Composite Scoring Works:', 'wc-team-payroll' ); ?></strong></p>
								<ol style="margin: 0; padding-left: 20px;">
									<li><?php esc_html_e( 'Each employee is ranked for each metric (e.g., earnings, orders)', 'wc-team-payroll' ); ?></li>
									<li><?php esc_html_e( 'Rank is converted to percentile (rank position / total employees × 100)', 'wc-team-payroll' ); ?></li>
									<li><?php esc_html_e( 'Percentiles are multiplied by their weights and summed', 'wc-team-payroll' ); ?></li>
									<li><?php esc_html_e( 'Final score determines leaderboard position', 'wc-team-payroll' ); ?></li>
								</ol>
								<p style="margin: 10px 0 0 0;"><strong><?php esc_html_e( 'Example:', 'wc-team-payroll' ); ?></strong> <?php esc_html_e( 'For "Earnings + Orders (50% + 50%)" - Employee ranks 5th/20 in earnings (75th percentile) and 3rd/20 in orders (85th percentile). Final score = (0.50 × 75) + (0.50 × 85) = 80', 'wc-team-payroll' ); ?></p>
							</div>
						</td>
					</tr>
				</table>
			</div>

			<!-- Period Configuration -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Period Configuration', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure the time period for leaderboard calculations.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="leaderboard_period"><?php esc_html_e( 'Leaderboard Period', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="leaderboard_period" name="leaderboard_period" class="wc-tp-leaderboard-setting">
								<?php
								$current_period = isset( $leaderboard_config['period'] ) ? $leaderboard_config['period'] : 'current_month';
								$periods = array(
									'current_week' => __( 'Current Week', 'wc-team-payroll' ),
									'current_month' => __( 'Current Month', 'wc-team-payroll' ),
									'current_quarter' => __( 'Current Quarter', 'wc-team-payroll' ),
									'current_year' => __( 'Current Year', 'wc-team-payroll' ),
									'last_30_days' => __( 'Last 30 Days', 'wc-team-payroll' ),
									'last_90_days' => __( 'Last 90 Days', 'wc-team-payroll' ),
								);
								foreach ( $periods as $key => $label ) {
									echo '<option value="' . esc_attr( $key ) . '"' . selected( $current_period, $key, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Time period used for calculating rankings', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="leaderboard_update_frequency"><?php esc_html_e( 'Update Frequency', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="leaderboard_update_frequency" name="leaderboard_update_frequency" class="wc-tp-leaderboard-setting">
								<?php
								$current_frequency = isset( $leaderboard_config['update_frequency'] ) ? $leaderboard_config['update_frequency'] : 'daily';
								$frequencies = array(
									'hourly' => __( 'Hourly', 'wc-team-payroll' ),
									'daily' => __( 'Daily (Recommended)', 'wc-team-payroll' ),
									'weekly' => __( 'Weekly', 'wc-team-payroll' ),
								);
								foreach ( $frequencies as $key => $label ) {
									echo '<option value="' . esc_attr( $key ) . '"' . selected( $current_frequency, $key, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How often leaderboard rankings are recalculated automatically', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Filters & Eligibility -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Filters & Eligibility', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Set minimum requirements for employees to appear on the leaderboard.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="leaderboard_minimum_orders"><?php esc_html_e( 'Minimum Orders', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="leaderboard_minimum_orders" 
								   name="leaderboard_minimum_orders" 
								   value="<?php echo esc_attr( isset( $leaderboard_config['minimum_orders'] ) ? $leaderboard_config['minimum_orders'] : 0 ); ?>" 
								   min="0" 
								   max="1000" 
								   step="1"
								   class="wc-tp-leaderboard-setting small-text" />
							<span class="description"><?php esc_html_e( 'orders (0 = no minimum)', 'wc-team-payroll' ); ?></span>
							<p class="description"><?php esc_html_e( 'Employees must have at least this many orders in the period to appear on leaderboard', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="leaderboard_exclude_inactive"><?php esc_html_e( 'Exclude Inactive Employees', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="hidden" name="leaderboard_exclude_inactive" value="0" />
							<input type="checkbox" 
								   id="leaderboard_exclude_inactive" 
								   name="leaderboard_exclude_inactive" 
								   value="1" 
								   class="wc-tp-leaderboard-setting" 
								   <?php checked( isset( $leaderboard_config['exclude_inactive'] ) ? $leaderboard_config['exclude_inactive'] : 1, 1 ); ?> />
							<label for="leaderboard_exclude_inactive"><?php esc_html_e( 'Only show active employees on leaderboard', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
				</table>
			</div>

			<!-- Display Options -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Display Options', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how the leaderboard is displayed to employees.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="leaderboard_display_limit"><?php esc_html_e( 'Show Top N', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="leaderboard_display_limit" name="leaderboard_display_limit" class="wc-tp-leaderboard-setting">
								<?php
								$current_limit = isset( $leaderboard_config['display_limit'] ) ? $leaderboard_config['display_limit'] : 10;
								$limits = array(
									5 => __( 'Top 5', 'wc-team-payroll' ),
									10 => __( 'Top 10', 'wc-team-payroll' ),
									20 => __( 'Top 20', 'wc-team-payroll' ),
									50 => __( 'Top 50', 'wc-team-payroll' ),
									-1 => __( 'All Employees', 'wc-team-payroll' ),
								);
								foreach ( $limits as $key => $label ) {
									echo '<option value="' . esc_attr( $key ) . '"' . selected( $current_limit, $key, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Number of top-ranked employees to display', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="leaderboard_show_user_rank"><?php esc_html_e( 'Show User\'s Rank', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="hidden" name="leaderboard_show_user_rank" value="0" />
							<input type="checkbox" 
								   id="leaderboard_show_user_rank" 
								   name="leaderboard_show_user_rank" 
								   value="1" 
								   class="wc-tp-leaderboard-setting" 
								   <?php checked( isset( $leaderboard_config['show_user_rank'] ) ? $leaderboard_config['show_user_rank'] : 1, 1 ); ?> />
							<label for="leaderboard_show_user_rank"><?php esc_html_e( 'Always show current user\'s rank (even if not in top N)', 'wc-team-payroll' ); ?></label>
							<p class="description"><?php esc_html_e( 'Displays a highlighted card with the user\'s current rank below the leaderboard', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="leaderboard_anonymize"><?php esc_html_e( 'Anonymize Names', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="hidden" name="leaderboard_anonymize" value="0" />
							<input type="checkbox" 
								   id="leaderboard_anonymize" 
								   name="leaderboard_anonymize" 
								   value="1" 
								   class="wc-tp-leaderboard-setting" 
								   <?php checked( isset( $leaderboard_config['anonymize'] ) ? $leaderboard_config['anonymize'] : 0, 1 ); ?> />
							<label for="leaderboard_anonymize"><?php esc_html_e( 'Show initials instead of full names', 'wc-team-payroll' ); ?></label>
							<p class="description"><?php esc_html_e( 'Example: "John Doe" becomes "J.D." for privacy', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="leaderboard_show_scores"><?php esc_html_e( 'Show Scores', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="hidden" name="leaderboard_show_scores" value="0" />
							<input type="checkbox" 
								   id="leaderboard_show_scores" 
								   name="leaderboard_show_scores" 
								   value="1" 
								   class="wc-tp-leaderboard-setting" 
								   <?php checked( isset( $leaderboard_config['show_scores'] ) ? $leaderboard_config['show_scores'] : 1, 1 ); ?> />
							<label for="leaderboard_show_scores"><?php esc_html_e( 'Display calculated scores next to rankings', 'wc-team-payroll' ); ?></label>
							<p class="description"><?php esc_html_e( 'Shows the numerical score for composite criteria', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="leaderboard_show_metrics"><?php esc_html_e( 'Show Metric Details', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="hidden" name="leaderboard_show_metrics" value="0" />
							<input type="checkbox" 
								   id="leaderboard_show_metrics" 
								   name="leaderboard_show_metrics" 
								   value="1" 
								   class="wc-tp-leaderboard-setting" 
								   <?php checked( isset( $leaderboard_config['show_metrics'] ) ? $leaderboard_config['show_metrics'] : 1, 1 ); ?> />
							<label for="leaderboard_show_metrics"><?php esc_html_e( 'Display individual metric values (earnings, orders, etc.)', 'wc-team-payroll' ); ?></label>
							<p class="description"><?php esc_html_e( 'Shows breakdown of metrics used in ranking calculation', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Manual Refresh -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Manual Actions', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Manually trigger leaderboard recalculation.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label><?php esc_html_e( 'Recalculate Now', 'wc-team-payroll' ); ?></label></th>
						<td>
							<button type="button" class="button button-secondary" id="wc-tp-refresh-leaderboard">
								<span class="dashicons dashicons-update"></span>
								<?php esc_html_e( 'Refresh Leaderboard Rankings', 'wc-team-payroll' ); ?>
							</button>
							<p class="description"><?php esc_html_e( 'Immediately recalculate all employee rankings based on current data', 'wc-team-payroll' ); ?></p>
							<div id="wc-tp-leaderboard-refresh-status" style="margin-top: 10px;"></div>
						</td>
					</tr>
					
					<tr>
						<th><label><?php esc_html_e( 'Last Updated', 'wc-team-payroll' ); ?></label></th>
						<td>
							<?php
							$last_updated = isset( $leaderboard_config['last_updated'] ) ? $leaderboard_config['last_updated'] : '';
							if ( $last_updated ) {
								echo '<p style="margin: 0; color: #666;">' . esc_html( sprintf( __( 'Last updated: %s', 'wc-team-payroll' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_updated ) ) ) ) . '</p>';
							} else {
								echo '<p style="margin: 0; color: #999;">' . esc_html__( 'Never updated', 'wc-team-payroll' ) . '</p>';
							}
							?>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Baselines Section
	 */
	private function render_baselines_section() {
		$baselines_config = get_option( 'wc_tp_baselines_config', array() );
		?>
		<div class="wc-tp-perf-baselines-wrapper">
			<h3><?php esc_html_e( 'Performance Baselines & Benchmarks', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure how performance baselines are calculated and updated over time. Baselines help track improvement and set realistic expectations.', 'wc-team-payroll' ); ?></p>
			
			<!-- Baseline Calculation Method -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Baseline Calculation Method', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Choose how employee baselines are calculated. This affects how performance is measured and compared.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="baseline_method"><?php esc_html_e( 'Calculation Method', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="baseline_method" name="baseline_method" class="wc-tp-baseline-setting">
								<?php
								$method = isset( $baselines_config['method'] ) ? $baselines_config['method'] : 'rolling_average';
								$methods = array(
									'rolling_average' => __( 'Rolling Average (Last N periods)', 'wc-team-payroll' ),
									'historical_average' => __( 'Historical Average (All time)', 'wc-team-payroll' ),
									'best_period' => __( 'Best Period Performance', 'wc-team-payroll' ),
									'median' => __( 'Median Performance', 'wc-team-payroll' ),
									'percentile' => __( 'Percentile-Based (e.g., 75th percentile)', 'wc-team-payroll' ),
									'manual' => __( 'Manual Entry (Admin sets baseline)', 'wc-team-payroll' ),
								);
								foreach ( $methods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $method, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How baseline values are calculated from historical data', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr class="wc-tp-baseline-option" data-show-for="rolling_average">
						<th><label for="baseline_periods"><?php esc_html_e( 'Number of Periods', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="baseline_periods" 
								   name="baseline_periods" 
								   value="<?php echo esc_attr( isset( $baselines_config['periods'] ) ? $baselines_config['periods'] : 3 ); ?>" 
								   min="1" 
								   max="12" 
								   step="1"
								   class="wc-tp-baseline-setting small-text" />
							<span class="description"><?php esc_html_e( 'periods (e.g., last 3 months)', 'wc-team-payroll' ); ?></span>
						</td>
					</tr>
					
					<tr class="wc-tp-baseline-option" data-show-for="percentile">
						<th><label for="baseline_percentile"><?php esc_html_e( 'Percentile', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="baseline_percentile" 
								   name="baseline_percentile" 
								   value="<?php echo esc_attr( isset( $baselines_config['percentile'] ) ? $baselines_config['percentile'] : 75 ); ?>" 
								   min="1" 
								   max="99" 
								   step="1"
								   class="wc-tp-baseline-setting small-text" />
							<span class="description"><?php esc_html_e( 'th percentile (1-99)', 'wc-team-payroll' ); ?></span>
						</td>
					</tr>
					
					<tr>
						<th><label for="baseline_update_frequency"><?php esc_html_e( 'Update Frequency', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="baseline_update_frequency" name="baseline_update_frequency" class="wc-tp-baseline-setting">
								<?php
								$frequency = isset( $baselines_config['update_frequency'] ) ? $baselines_config['update_frequency'] : 'monthly';
								$frequencies = array(
									'daily' => __( 'Daily', 'wc-team-payroll' ),
									'weekly' => __( 'Weekly', 'wc-team-payroll' ),
									'monthly' => __( 'Monthly', 'wc-team-payroll' ),
									'quarterly' => __( 'Quarterly', 'wc-team-payroll' ),
									'manual' => __( 'Manual Only', 'wc-team-payroll' ),
								);
								foreach ( $frequencies as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $frequency, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How often baselines are recalculated', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="baseline_minimum_data"><?php esc_html_e( 'Minimum Data Points', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="baseline_minimum_data" 
								   name="baseline_minimum_data" 
								   value="<?php echo esc_attr( isset( $baselines_config['minimum_data'] ) ? $baselines_config['minimum_data'] : 5 ); ?>" 
								   min="1" 
								   max="50" 
								   step="1"
								   class="wc-tp-baseline-setting small-text" />
							<span class="description"><?php esc_html_e( 'data points required before calculating baseline', 'wc-team-payroll' ); ?></span>
							<p class="description"><?php esc_html_e( 'Prevents unreliable baselines from insufficient data', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Baseline Display Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Baseline Display Settings', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how baselines are displayed to employees on the reports page.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="baseline_show_comparison"><?php esc_html_e( 'Show Comparison', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="baseline_show_comparison" 
								   name="baseline_show_comparison" 
								   value="1" 
								   class="wc-tp-baseline-setting" 
								   <?php checked( isset( $baselines_config['show_comparison'] ) ? $baselines_config['show_comparison'] : 1, 1 ); ?> />
							<label for="baseline_show_comparison"><?php esc_html_e( 'Show current performance vs baseline comparison', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="baseline_show_trend"><?php esc_html_e( 'Show Trend', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="baseline_show_trend" 
								   name="baseline_show_trend" 
								   value="1" 
								   class="wc-tp-baseline-setting" 
								   <?php checked( isset( $baselines_config['show_trend'] ) ? $baselines_config['show_trend'] : 1, 1 ); ?> />
							<label for="baseline_show_trend"><?php esc_html_e( 'Show improvement/decline trend indicators', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="baseline_show_history"><?php esc_html_e( 'Show History', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="baseline_show_history" 
								   name="baseline_show_history" 
								   value="1" 
								   class="wc-tp-baseline-setting" 
								   <?php checked( isset( $baselines_config['show_history'] ) ? $baselines_config['show_history'] : 0, 1 ); ?> />
							<label for="baseline_show_history"><?php esc_html_e( 'Show historical baseline changes', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="baseline_comparison_format"><?php esc_html_e( 'Comparison Format', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="baseline_comparison_format" name="baseline_comparison_format" class="wc-tp-baseline-setting">
								<?php
								$format = isset( $baselines_config['comparison_format'] ) ? $baselines_config['comparison_format'] : 'percentage';
								$formats = array(
									'percentage' => __( 'Percentage (e.g., +15%)', 'wc-team-payroll' ),
									'absolute' => __( 'Absolute Value (e.g., +$500)', 'wc-team-payroll' ),
									'both' => __( 'Both (e.g., +$500 / +15%)', 'wc-team-payroll' ),
								);
								foreach ( $formats as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $format, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How to display performance vs baseline', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Baseline Preview Calculator -->
			<div class="wc-tp-perf-card wc-tp-baseline-preview-card">
				<h4><?php esc_html_e( 'Baseline Preview Calculator', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Test how baselines are calculated with sample historical data.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-baseline-preview-section">
					<div class="wc-tp-baseline-input-section">
						<h5><?php esc_html_e( 'Sample Historical Data', 'wc-team-payroll' ); ?></h5>
						<p class="description"><?php esc_html_e( 'Enter sample performance data for the last few periods:', 'wc-team-payroll' ); ?></p>
						
						<div class="wc-tp-baseline-data-inputs">
							<div class="wc-tp-baseline-data-column">
								<label><?php esc_html_e( 'Earnings History', 'wc-team-payroll' ); ?></label>
								<input type="text" class="wc-tp-baseline-sample" id="sample_earnings" placeholder="3000, 3500, 4000, 3800, 4200" />
								<span class="description"><?php esc_html_e( 'Comma-separated values', 'wc-team-payroll' ); ?></span>
							</div>
							
							<div class="wc-tp-baseline-data-column">
								<label><?php esc_html_e( 'Orders History', 'wc-team-payroll' ); ?></label>
								<input type="text" class="wc-tp-baseline-sample" id="sample_orders" placeholder="25, 30, 28, 32, 35" />
								<span class="description"><?php esc_html_e( 'Comma-separated values', 'wc-team-payroll' ); ?></span>
							</div>
							
							<div class="wc-tp-baseline-data-column">
								<label><?php esc_html_e( 'AOV History', 'wc-team-payroll' ); ?></label>
								<input type="text" class="wc-tp-baseline-sample" id="sample_aov" placeholder="200, 220, 210, 230, 240" />
								<span class="description"><?php esc_html_e( 'Comma-separated values', 'wc-team-payroll' ); ?></span>
							</div>
						</div>
						
						<button type="button" class="button button-primary" id="wc-tp-calculate-baseline">
							<span class="dashicons dashicons-calculator"></span>
							<?php esc_html_e( 'Calculate Baseline', 'wc-team-payroll' ); ?>
						</button>
					</div>
					
					<div class="wc-tp-baseline-result-section" style="display: none;">
						<h5><?php esc_html_e( 'Calculated Baselines', 'wc-team-payroll' ); ?></h5>
						
						<div class="wc-tp-baseline-results">
							<div class="wc-tp-baseline-result-item">
								<div class="wc-tp-baseline-result-icon">
									<span class="dashicons dashicons-money-alt"></span>
								</div>
								<div class="wc-tp-baseline-result-content">
									<h6><?php esc_html_e( 'Earnings Baseline', 'wc-team-payroll' ); ?></h6>
									<p class="wc-tp-baseline-value" id="baseline_earnings_value">$0</p>
									<p class="wc-tp-baseline-method" id="baseline_earnings_method"></p>
								</div>
							</div>
							
							<div class="wc-tp-baseline-result-item">
								<div class="wc-tp-baseline-result-icon">
									<span class="dashicons dashicons-cart"></span>
								</div>
								<div class="wc-tp-baseline-result-content">
									<h6><?php esc_html_e( 'Orders Baseline', 'wc-team-payroll' ); ?></h6>
									<p class="wc-tp-baseline-value" id="baseline_orders_value">0</p>
									<p class="wc-tp-baseline-method" id="baseline_orders_method"></p>
								</div>
							</div>
							
							<div class="wc-tp-baseline-result-item">
								<div class="wc-tp-baseline-result-icon">
									<span class="dashicons dashicons-chart-line"></span>
								</div>
								<div class="wc-tp-baseline-result-content">
									<h6><?php esc_html_e( 'AOV Baseline', 'wc-team-payroll' ); ?></h6>
									<p class="wc-tp-baseline-value" id="baseline_aov_value">$0</p>
									<p class="wc-tp-baseline-method" id="baseline_aov_method"></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Calculation Engine Section
	 */
	private function render_calculation_section() {
		$calculation_config = get_option( 'wc_tp_calculation_config', array() );
		?>
		<div class="wc-tp-perf-calculation-wrapper">
			<h3><?php esc_html_e( 'Performance Calculation Engine', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure how performance scores and metrics are calculated. Customize formulas, weights, and calculation rules.', 'wc-team-payroll' ); ?></p>
			
			<!-- Score Calculation Formula -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Performance Score Formula', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how the final performance score is calculated from individual factors.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="calc_score_method"><?php esc_html_e( 'Calculation Method', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="calc_score_method" name="calc_score_method" class="wc-tp-calc-setting">
								<?php
								$method = isset( $calculation_config['score_method'] ) ? $calculation_config['score_method'] : 'additive';
								$methods = array(
									'additive' => __( 'Additive (Base + Earnings + Orders + AOV)', 'wc-team-payroll' ),
									'weighted' => __( 'Weighted Average (Custom weights per factor)', 'wc-team-payroll' ),
									'multiplicative' => __( 'Multiplicative (Base × Factor multipliers)', 'wc-team-payroll' ),
									'custom' => __( 'Custom Formula (Advanced)', 'wc-team-payroll' ),
								);
								foreach ( $methods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $method, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How individual factors combine to create final score', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr class="wc-tp-calc-option" data-show-for="custom">
						<th><label for="calc_custom_formula"><?php esc_html_e( 'Custom Formula', 'wc-team-payroll' ); ?></label></th>
						<td>
							<textarea id="calc_custom_formula" 
									  name="calc_custom_formula" 
									  rows="4" 
									  class="wc-tp-calc-setting"
									  placeholder="Example: (base * 0.5) + (earnings * 0.4) + (orders * 0.08) + (aov * 0.02)"
									  style="width: 100%; font-family: monospace;"><?php echo esc_textarea( isset( $calculation_config['custom_formula'] ) ? $calculation_config['custom_formula'] : '' ); ?></textarea>
							
							<div class="wc-tp-formula-guide" style="margin-top: 12px; padding: 12px; background: #f0f6fc; border: 1px solid #c3dcf0; border-radius: 4px;">
								<strong><?php esc_html_e( '📝 Formula Guide:', 'wc-team-payroll' ); ?></strong><br><br>
								
								<strong><?php esc_html_e( 'Available Variables:', 'wc-team-payroll' ); ?></strong>
								<ul style="margin: 8px 0; padding-left: 20px;">
									<li><code>base</code> - <?php esc_html_e( 'Base score (usually 5)', 'wc-team-payroll' ); ?></li>
									<li><code>earnings</code> - <?php esc_html_e( 'Earnings performance points', 'wc-team-payroll' ); ?></li>
									<li><code>orders</code> - <?php esc_html_e( 'Orders performance points', 'wc-team-payroll' ); ?></li>
									<li><code>aov</code> - <?php esc_html_e( 'Average Order Value performance points', 'wc-team-payroll' ); ?></li>
								</ul>
								
								<strong><?php esc_html_e( 'Supported Operators:', 'wc-team-payroll' ); ?></strong>
								<ul style="margin: 8px 0; padding-left: 20px;">
									<li><code>+</code> - <?php esc_html_e( 'Addition', 'wc-team-payroll' ); ?></li>
									<li><code>-</code> - <?php esc_html_e( 'Subtraction', 'wc-team-payroll' ); ?></li>
									<li><code>*</code> - <?php esc_html_e( 'Multiplication', 'wc-team-payroll' ); ?></li>
									<li><code>/</code> - <?php esc_html_e( 'Division', 'wc-team-payroll' ); ?></li>
									<li><code>( )</code> - <?php esc_html_e( 'Parentheses for grouping', 'wc-team-payroll' ); ?></li>
								</ul>
								
								<strong><?php esc_html_e( 'Examples:', 'wc-team-payroll' ); ?></strong>
								<ul style="margin: 8px 0; padding-left: 20px;">
									<li><code>base + earnings + orders + aov</code> - <?php esc_html_e( 'Simple addition', 'wc-team-payroll' ); ?></li>
									<li><code>(earnings * 0.4) + (orders * 0.35) + (aov * 0.25)</code> - <?php esc_html_e( 'Weighted average', 'wc-team-payroll' ); ?></li>
									<li><code>base * (1 + earnings * 0.1) * (1 + orders * 0.05)</code> - <?php esc_html_e( 'Multiplicative', 'wc-team-payroll' ); ?></li>
									<li><code>(earnings * 0.6) + (orders * 0.3) + (aov * 0.1)</code> - <?php esc_html_e( 'Sales-focused', 'wc-team-payroll' ); ?></li>
								</ul>
								
								<strong style="color: #d63638;"><?php esc_html_e( '⚠️ Important:', 'wc-team-payroll' ); ?></strong>
								<ul style="margin: 8px 0; padding-left: 20px; color: #d63638;">
									<li><?php esc_html_e( 'Formula is case-sensitive (use lowercase variable names)', 'wc-team-payroll' ); ?></li>
									<li><?php esc_html_e( 'Use * for multiplication (not x)', 'wc-team-payroll' ); ?></li>
									<li><?php esc_html_e( 'Final score will be capped at the Score Cap value', 'wc-team-payroll' ); ?></li>
									<li><?php esc_html_e( 'Invalid formulas will fall back to additive method', 'wc-team-payroll' ); ?></li>
								</ul>
							</div>
						</td>
					</tr>
					
					<tr class="wc-tp-calc-option" data-show-for="weighted">
						<th><label><?php esc_html_e( 'Factor Weights', 'wc-team-payroll' ); ?></label></th>
						<td>
							<div class="wc-tp-weight-inputs">
								<div class="wc-tp-weight-item">
									<label><?php esc_html_e( 'Earnings Weight:', 'wc-team-payroll' ); ?></label>
									<input type="number" 
										   id="calc_weight_earnings" 
										   name="calc_weight_earnings" 
										   value="<?php echo esc_attr( isset( $calculation_config['weight_earnings'] ) ? $calculation_config['weight_earnings'] : 40 ); ?>" 
										   min="0" 
										   max="100" 
										   step="1"
										   class="wc-tp-calc-setting small-text" />
									<span>%</span>
								</div>
								<div class="wc-tp-weight-item">
									<label><?php esc_html_e( 'Orders Weight:', 'wc-team-payroll' ); ?></label>
									<input type="number" 
										   id="calc_weight_orders" 
										   name="calc_weight_orders" 
										   value="<?php echo esc_attr( isset( $calculation_config['weight_orders'] ) ? $calculation_config['weight_orders'] : 35 ); ?>" 
										   min="0" 
										   max="100" 
										   step="1"
										   class="wc-tp-calc-setting small-text" />
									<span>%</span>
								</div>
								<div class="wc-tp-weight-item">
									<label><?php esc_html_e( 'AOV Weight:', 'wc-team-payroll' ); ?></label>
									<input type="number" 
										   id="calc_weight_aov" 
										   name="calc_weight_aov" 
										   value="<?php echo esc_attr( isset( $calculation_config['weight_aov'] ) ? $calculation_config['weight_aov'] : 25 ); ?>" 
										   min="0" 
										   max="100" 
										   step="1"
										   class="wc-tp-calc-setting small-text" />
									<span>%</span>
								</div>
								<div class="wc-tp-weight-total">
									<strong><?php esc_html_e( 'Total:', 'wc-team-payroll' ); ?></strong>
									<span id="calc_weight_total">100</span>%
								</div>
							</div>
							<p class="description"><?php esc_html_e( 'Weights must total 100%. Adjust to emphasize different factors.', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="calc_score_cap"><?php esc_html_e( 'Score Cap', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="calc_score_cap" 
								   name="calc_score_cap" 
								   value="<?php echo esc_attr( isset( $calculation_config['score_cap'] ) ? $calculation_config['score_cap'] : 10 ); ?>" 
								   min="1" 
								   max="100" 
								   step="0.1"
								   class="wc-tp-calc-setting small-text" />
							<span class="description"><?php esc_html_e( 'Maximum possible score', 'wc-team-payroll' ); ?></span>
						</td>
					</tr>
					
					<tr>
						<th><label for="calc_rounding"><?php esc_html_e( 'Score Rounding', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="calc_rounding" name="calc_rounding" class="wc-tp-calc-setting">
								<?php
								$rounding = isset( $calculation_config['rounding'] ) ? $calculation_config['rounding'] : 'one_decimal';
								$rounding_options = array(
									'none' => __( 'No Rounding (e.g., 7.456)', 'wc-team-payroll' ),
									'one_decimal' => __( 'One Decimal (e.g., 7.5)', 'wc-team-payroll' ),
									'two_decimals' => __( 'Two Decimals (e.g., 7.46)', 'wc-team-payroll' ),
									'whole' => __( 'Whole Number (e.g., 7)', 'wc-team-payroll' ),
								);
								foreach ( $rounding_options as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $rounding, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<!-- Metric Calculation Rules -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Metric Calculation Rules', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure how individual metrics (earnings, orders, AOV) are calculated.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="calc_period_type"><?php esc_html_e( 'Calculation Period', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="calc_period_type" name="calc_period_type" class="wc-tp-calc-setting">
								<?php
								$period = isset( $calculation_config['period_type'] ) ? $calculation_config['period_type'] : 'calendar_month';
								$periods = array(
									'calendar_month' => __( 'Calendar Month (1st to last day)', 'wc-team-payroll' ),
									'rolling_30' => __( 'Rolling 30 Days', 'wc-team-payroll' ),
									'custom_range' => __( 'Custom Date Range', 'wc-team-payroll' ),
								);
								foreach ( $periods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $period, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Time period for calculating metrics', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr class="wc-tp-calc-option" data-show-for="custom_range" style="<?php echo $period === 'custom_range' ? '' : 'display: none;'; ?>">
						<th><label><?php esc_html_e( 'Custom Date Range', 'wc-team-payroll' ); ?></label></th>
						<td>
							<div class="wc-tp-date-range-inputs" style="display: flex; gap: 15px; align-items: center;">
								<div>
									<label for="calc_custom_start_date" style="display: block; margin-bottom: 5px;"><?php esc_html_e( 'Start Date:', 'wc-team-payroll' ); ?></label>
									<input type="date" 
										   id="calc_custom_start_date" 
										   name="calc_custom_start_date" 
										   value="<?php echo esc_attr( isset( $calculation_config['custom_start_date'] ) ? $calculation_config['custom_start_date'] : '' ); ?>" 
										   class="wc-tp-calc-setting" />
								</div>
								<div>
									<label for="calc_custom_end_date" style="display: block; margin-bottom: 5px;"><?php esc_html_e( 'End Date:', 'wc-team-payroll' ); ?></label>
									<input type="date" 
										   id="calc_custom_end_date" 
										   name="calc_custom_end_date" 
										   value="<?php echo esc_attr( isset( $calculation_config['custom_end_date'] ) ? $calculation_config['custom_end_date'] : '' ); ?>" 
										   class="wc-tp-calc-setting" />
								</div>
							</div>
							<p class="description"><?php esc_html_e( 'Select the start and end dates for the custom calculation period.', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="calc_revenue_attribution"><?php esc_html_e( 'Revenue Attribution Method', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="calc_revenue_attribution" name="calc_revenue_attribution" class="wc-tp-calc-setting">
								<?php
								$attribution_method = isset( $calculation_config['revenue_attribution'] ) ? $calculation_config['revenue_attribution'] : 'full';
								$attribution_methods = array(
									'full' => __( 'Full Order Value (No Split)', 'wc-team-payroll' ),
									'commission_split' => __( 'Use Commission Split Percentages', 'wc-team-payroll' ),
								);
								foreach ( $attribution_methods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $attribution_method, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How to attribute order values when multiple roles (Agent + Processor) are assigned to the same order.', 'wc-team-payroll' ); ?></p>
							
							<div class="wc-tp-attribution-explanation" style="margin-top: 10px; padding: 12px; background: #f0f6fc; border: 1px solid #c3dcf0; border-radius: 4px;">
								<div class="wc-tp-attribution-option" data-method="full">
									<strong><?php esc_html_e( '📊 Full Order Value:', 'wc-team-payroll' ); ?></strong><br>
									<span style="color: #d63638;"><?php esc_html_e( '⚠️ Warning: May cause double-counting if same order has Agent + Processor', 'wc-team-payroll' ); ?></span><br>
									<em><?php esc_html_e( 'Example: $1,000 order → Agent gets $1,000 credit + Processor gets $1,000 credit = $2,000 total (inflated)', 'wc-team-payroll' ); ?></em>
								</div>
								
								<div class="wc-tp-attribution-option" data-method="commission_split" style="display: none;">
									<strong><?php esc_html_e( '🎯 Commission Split Method:', 'wc-team-payroll' ); ?></strong><br>
									<span style="color: #00a32a;"><?php esc_html_e( '✅ Prevents double-counting by using commission percentages', 'wc-team-payroll' ); ?></span><br>
									<?php esc_html_e( 'Uses the same percentage splits configured in', 'wc-team-payroll' ); ?> 
									<a href="?page=wc-team-payroll-settings&tab=commission" target="_blank" style="text-decoration: none;">
										<strong><?php esc_html_e( 'Settings → Commission', 'wc-team-payroll' ); ?></strong>
									</a><br>
									<em><?php esc_html_e( 'Example: If Agent gets 70% commission and Processor gets 30%, then for $1,000 order → Agent gets $700 credit + Processor gets $300 credit = $1,000 total (accurate)', 'wc-team-payroll' ); ?></em>
								</div>
							</div>
						</td>
					</tr>
					
					<tr>
						<th><label><?php esc_html_e( 'Order Statuses', 'wc-team-payroll' ); ?></label></th>
						<td>
							<p class="description">
								<?php esc_html_e( 'Order statuses are configured in', 'wc-team-payroll' ); ?> 
								<a href="?page=wc-team-payroll-settings&tab=woocommerce" target="_blank">
									<?php esc_html_e( 'Settings → WooCommerce → Order Status Configuration', 'wc-team-payroll' ); ?>
								</a>
							</p>
							<p class="description">
								<?php esc_html_e( 'Only orders with the statuses selected in WooCommerce settings will count toward performance metrics.', 'wc-team-payroll' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th><label for="calc_exclude_refunds"><?php esc_html_e( 'Exclude Refunds', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="calc_exclude_refunds" 
								   name="calc_exclude_refunds" 
								   value="1" 
								   class="wc-tp-calc-setting" 
								   <?php checked( isset( $calculation_config['exclude_refunds'] ) ? $calculation_config['exclude_refunds'] : 1, 1 ); ?> />
							<label for="calc_exclude_refunds"><?php esc_html_e( 'Subtract refunded amounts from earnings', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="calc_aov_method"><?php esc_html_e( 'AOV Calculation', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="calc_aov_method" name="calc_aov_method" class="wc-tp-calc-setting">
								<?php
								$aov_method = isset( $calculation_config['aov_method'] ) ? $calculation_config['aov_method'] : 'total_divided_orders';
								$aov_methods = array(
									'total_divided_orders' => __( 'Total Earnings ÷ Total Orders', 'wc-team-payroll' ),
									'average_of_orders' => __( 'Average of Individual Order Values', 'wc-team-payroll' ),
								);
								foreach ( $aov_methods as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $aov_method, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How average order value is calculated', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Formula Tester -->
			<div class="wc-tp-perf-card wc-tp-formula-tester-card">
				<h4><?php esc_html_e( 'Formula Tester', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Test how your calculation settings affect the final performance score.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-formula-tester">
					<div class="wc-tp-formula-inputs">
						<div class="wc-tp-formula-input-group">
							<label><?php esc_html_e( 'Base Score:', 'wc-team-payroll' ); ?></label>
							<input type="number" id="test_base_score" value="5" step="0.1" min="0" max="10" />
						</div>
						<div class="wc-tp-formula-input-group">
							<label><?php esc_html_e( 'Earnings Points:', 'wc-team-payroll' ); ?></label>
							<input type="number" id="test_earnings_points" value="2.0" step="0.1" min="0" max="10" />
						</div>
						<div class="wc-tp-formula-input-group">
							<label><?php esc_html_e( 'Orders Points:', 'wc-team-payroll' ); ?></label>
							<input type="number" id="test_orders_points" value="1.5" step="0.1" min="0" max="10" />
						</div>
						<div class="wc-tp-formula-input-group">
							<label><?php esc_html_e( 'AOV Points:', 'wc-team-payroll' ); ?></label>
							<input type="number" id="test_aov_points" value="0.5" step="0.1" min="0" max="10" />
						</div>
						<button type="button" class="button button-primary" id="wc-tp-test-formula">
							<span class="dashicons dashicons-calculator"></span>
							<?php esc_html_e( 'Calculate Score', 'wc-team-payroll' ); ?>
						</button>
					</div>
					
					<div class="wc-tp-formula-result" style="display: none;">
						<div class="wc-tp-formula-breakdown">
							<h5><?php esc_html_e( 'Calculation Breakdown', 'wc-team-payroll' ); ?></h5>
							<div class="wc-tp-formula-steps" id="formula_steps"></div>
							<div class="wc-tp-formula-final">
								<strong><?php esc_html_e( 'Final Score:', 'wc-team-payroll' ); ?></strong>
								<span id="formula_final_score">0</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render System Configuration Section
	 */
	private function render_system_section() {
		$system_config = get_option( 'wc_tp_system_config', array() );
		?>
		<div class="wc-tp-perf-system-wrapper">
			<h3><?php esc_html_e( 'System Configuration & Preferences', 'wc-team-payroll' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Global settings and preferences for the performance system. These settings affect all roles and employees.', 'wc-team-payroll' ); ?></p>
			
			<!-- General System Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'General System Settings', 'wc-team-payroll' ); ?></h4>
				
				<table class="form-table">
					<tr>
						<th><label for="system_enable_performance"><?php esc_html_e( 'Enable Performance System', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_enable_performance" 
								   name="system_enable_performance" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['enable_performance'] ) ? $system_config['enable_performance'] : 1, 1 ); ?> />
							<label for="system_enable_performance"><?php esc_html_e( 'Enable the entire performance tracking system', 'wc-team-payroll' ); ?></label>
							<p class="description"><?php esc_html_e( 'Turn off to disable all performance features site-wide', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Reports Page Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Reports Page Settings', 'wc-team-payroll' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Configure what employees see on their reports page.', 'wc-team-payroll' ); ?></p>
				
				<table class="form-table">
					<tr>
						<th><label for="system_show_score"><?php esc_html_e( 'Show Performance Score', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_show_score" 
								   name="system_show_score" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['show_score'] ) ? $system_config['show_score'] : 1, 1 ); ?> />
							<label for="system_show_score"><?php esc_html_e( 'Display performance score on reports page', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_show_goals"><?php esc_html_e( 'Show Goals', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_show_goals" 
								   name="system_show_goals" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['show_goals'] ) ? $system_config['show_goals'] : 1, 1 ); ?> />
							<label for="system_show_goals"><?php esc_html_e( 'Display goals and progress', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_show_achievements"><?php esc_html_e( 'Show Achievements', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_show_achievements" 
								   name="system_show_achievements" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['show_achievements'] ) ? $system_config['show_achievements'] : 1, 1 ); ?> />
							<label for="system_show_achievements"><?php esc_html_e( 'Display earned achievements and badges', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_show_baselines"><?php esc_html_e( 'Show Baselines', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_show_baselines" 
								   name="system_show_baselines" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['show_baselines'] ) ? $system_config['show_baselines'] : 1, 1 ); ?> />
							<label for="system_show_baselines"><?php esc_html_e( 'Display baseline comparisons', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_show_leaderboard"><?php esc_html_e( 'Show Leaderboard', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_show_leaderboard" 
								   name="system_show_leaderboard" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['show_leaderboard'] ) ? $system_config['show_leaderboard'] : 0, 1 ); ?> />
							<label for="system_show_leaderboard"><?php esc_html_e( 'Display team leaderboard (rankings)', 'wc-team-payroll' ); ?></label>
							<p class="description"><?php esc_html_e( 'Shows how employees rank compared to teammates', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_refresh_interval"><?php esc_html_e( 'Auto-Refresh Interval', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="system_refresh_interval" 
								   name="system_refresh_interval" 
								   value="<?php echo esc_attr( isset( $system_config['refresh_interval'] ) ? $system_config['refresh_interval'] : 30 ); ?>" 
								   min="0" 
								   max="300" 
								   step="5"
								   class="wc-tp-system-setting small-text" />
							<span class="description"><?php esc_html_e( 'seconds (0 = disabled)', 'wc-team-payroll' ); ?></span>
							<p class="description"><?php esc_html_e( 'How often the reports page auto-refreshes data', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Data & Privacy Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Data & Privacy Settings', 'wc-team-payroll' ); ?></h4>
				
				<table class="form-table">
					<tr>
						<th><label for="system_data_retention"><?php esc_html_e( 'Data Retention Period', 'wc-team-payroll' ); ?></label></th>
						<td>
							<select id="system_data_retention" name="system_data_retention" class="wc-tp-system-setting">
								<?php
								$retention = isset( $system_config['data_retention'] ) ? $system_config['data_retention'] : 'forever';
								$retention_options = array(
									'30_days' => __( '30 Days', 'wc-team-payroll' ),
									'90_days' => __( '90 Days', 'wc-team-payroll' ),
									'6_months' => __( '6 Months', 'wc-team-payroll' ),
									'1_year' => __( '1 Year', 'wc-team-payroll' ),
									'2_years' => __( '2 Years', 'wc-team-payroll' ),
									'forever' => __( 'Forever', 'wc-team-payroll' ),
								);
								foreach ( $retention_options as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '"' . selected( $retention, $value, false ) . '>' . esc_html( $label ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'How long to keep historical performance data', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_anonymize_data"><?php esc_html_e( 'Anonymize Leaderboard', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_anonymize_data" 
								   name="system_anonymize_data" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['anonymize_data'] ) ? $system_config['anonymize_data'] : 0, 1 ); ?> />
							<label for="system_anonymize_data"><?php esc_html_e( 'Show initials instead of full names on leaderboard', 'wc-team-payroll' ); ?></label>
						</td>
					</tr>
					
					<tr>
						<th><label for="system_cache_duration"><?php esc_html_e( 'Cache Duration', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="number" 
								   id="system_cache_duration" 
								   name="system_cache_duration" 
								   value="<?php echo esc_attr( isset( $system_config['cache_duration'] ) ? $system_config['cache_duration'] : 300 ); ?>" 
								   min="0" 
								   max="3600" 
								   step="60"
								   class="wc-tp-system-setting small-text" />
							<span class="description"><?php esc_html_e( 'seconds (0 = no cache)', 'wc-team-payroll' ); ?></span>
							<p class="description"><?php esc_html_e( 'How long to cache performance calculations for better performance', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Advanced Settings -->
			<div class="wc-tp-perf-card">
				<h4><?php esc_html_e( 'Advanced Settings', 'wc-team-payroll' ); ?></h4>
				
				<table class="form-table">
					<tr>
						<th><label for="system_debug_mode"><?php esc_html_e( 'Debug Mode', 'wc-team-payroll' ); ?></label></th>
						<td>
							<input type="checkbox" 
								   id="system_debug_mode" 
								   name="system_debug_mode" 
								   value="1" 
								   class="wc-tp-system-setting" 
								   <?php checked( isset( $system_config['debug_mode'] ) ? $system_config['debug_mode'] : 0, 1 ); ?> />
							<label for="system_debug_mode"><?php esc_html_e( 'Enable debug logging for troubleshooting', 'wc-team-payroll' ); ?></label>
							<p class="description"><?php esc_html_e( 'Logs performance calculations to WordPress debug log', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th><label><?php esc_html_e( 'Reset All Data', 'wc-team-payroll' ); ?></label></th>
						<td>
							<button type="button" class="button button-secondary" id="wc-tp-reset-all-data">
								<span class="dashicons dashicons-warning"></span>
								<?php esc_html_e( 'Reset All Performance Data', 'wc-team-payroll' ); ?>
							</button>
							<p class="description" style="color: #d63638;"><?php esc_html_e( 'WARNING: This will delete all performance configurations, scores, goals, achievements, and baselines. This action cannot be undone!', 'wc-team-payroll' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<!-- System Status -->
			<div class="wc-tp-perf-card wc-tp-system-status-card">
				<h4><?php esc_html_e( 'System Status', 'wc-team-payroll' ); ?></h4>
				
				<div class="wc-tp-system-status">
					<div class="wc-tp-status-item">
						<span class="wc-tp-status-label"><?php esc_html_e( 'Performance System:', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-status-value wc-tp-status-active"><?php esc_html_e( 'Active', 'wc-team-payroll' ); ?></span>
					</div>
					<div class="wc-tp-status-item">
						<span class="wc-tp-status-label"><?php esc_html_e( 'Total Employees:', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-status-value"><?php echo esc_html( count( get_users( array( 'role__in' => array( 'shop_employee', 'shop_manager' ) ) ) ) ); ?></span>
					</div>
					<div class="wc-tp-status-item">
						<span class="wc-tp-status-label"><?php esc_html_e( 'Configured Roles:', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-status-value"><?php echo esc_html( count( get_option( 'wc_tp_performance_config', array() ) ) ); ?></span>
					</div>
					<div class="wc-tp-status-item">
						<span class="wc-tp-status-label"><?php esc_html_e( 'Last Updated:', 'wc-team-payroll' ); ?></span>
						<span class="wc-tp-status-value"><?php echo esc_html( date( 'Y-m-d H:i:s' ) ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get employee roles from settings (not all WordPress roles)
	 */
	private function get_all_roles() {
		// Get employee roles from WooCommerce settings (agent_user_roles)
		$checkout_fields = get_option( 'wc_team_payroll_checkout_fields', array() );
		$employee_roles = isset( $checkout_fields['agent_user_roles'] ) && is_array( $checkout_fields['agent_user_roles'] ) 
			? $checkout_fields['agent_user_roles'] 
			: array( 'shop_employee', 'shop_manager', 'administrator' );
		
		// Get WordPress roles for display names
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = wp_roles();
		}
		
		$all_wp_roles = $wp_roles->get_names();
		$filtered_roles = array();
		
		// Only include roles that are configured as employee roles
		foreach ( $employee_roles as $role_key ) {
			if ( isset( $all_wp_roles[ $role_key ] ) ) {
				$filtered_roles[ $role_key ] = $all_wp_roles[ $role_key ];
			} else {
				// If role doesn't exist in WordPress, use the role key as display name
				$filtered_roles[ $role_key ] = ucwords( str_replace( '_', ' ', $role_key ) );
			}
		}
		
		return $filtered_roles;
	}

	/**
	 * Clear all achievement-related caches for all users
	 * Called when achievement thresholds are changed
	 */
	private function clear_all_achievement_caches() {
		global $wpdb;

		// Get all users with shop_employee role
		$employees = get_users( array(
			'role' => 'shop_employee',
			'fields' => 'ID',
		) );

		// Also get users with other employee roles
		$all_roles = $this->get_all_roles();
		foreach ( $all_roles as $role_key => $role_name ) {
			if ( $role_key !== 'shop_employee' ) {
				$role_employees = get_users( array(
					'role' => $role_key,
					'fields' => 'ID',
				) );
				$employees = array_merge( $employees, $role_employees );
			}
		}

		// Remove duplicates
		$employees = array_unique( $employees );

		// Clear achievement meta for all employees
		foreach ( $employees as $user_id ) {
			// Delete all period achievement meta keys
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
				$user_id,
				'_wc_tp_period_achievements_%'
			) );

			// Delete all period achievement stats meta keys
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
				$user_id,
				'_wc_tp_period_achievements_stats_%'
			) );

			// Delete last achievement period tracking
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
				$user_id,
				'_wc_tp_last_achievement_period_%'
			) );
		}
	}

	/**
	 * Clear all goal-related caches for all users
	 * Called when goal thresholds are changed
	 */
	private function clear_all_goal_caches() {
		global $wpdb;

		// Get all users with shop_employee role
		$employees = get_users( array(
			'role' => 'shop_employee',
			'fields' => 'ID',
		) );

		// Also get users with other employee roles
		$all_roles = $this->get_all_roles();
		foreach ( $all_roles as $role_key => $role_name ) {
			if ( $role_key !== 'shop_employee' ) {
				$role_employees = get_users( array(
					'role' => $role_key,
					'fields' => 'ID',
				) );
				$employees = array_merge( $employees, $role_employees );
			}
		}

		// Remove duplicates
		$employees = array_unique( $employees );

		// Clear goal meta for all employees
		foreach ( $employees as $user_id ) {
			// Delete current goal progress
			delete_user_meta( $user_id, '_wc_tp_current_goal_progress' );

			// Delete goal history
			delete_user_meta( $user_id, '_wc_tp_goal_history' );

			// Delete all period goal meta keys
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
				$user_id,
				'_wc_tp_period_goals_%'
			) );
		}
	}

	/**
	 * AJAX: Save performance configuration
	 */
	public function ajax_save_performance_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
		
		// Get existing configuration
		$existing_config = get_option( 'wc_tp_performance_config', array() );
		
		// Merge with new data
		if ( isset( $config['base_score'] ) ) {
			$existing_config['base_score'] = floatval( $config['base_score'] );
		}
		
		if ( isset( $config['roles'] ) && is_array( $config['roles'] ) ) {
			if ( ! isset( $existing_config['roles'] ) ) {
				$existing_config['roles'] = array();
			}
			
			foreach ( $config['roles'] as $role => $role_config ) {
				$existing_config['roles'][ $role ] = $this->sanitize_role_config( $role_config );
			}
		}
		
		// Save configuration
		update_option( 'wc_tp_performance_config', $existing_config );

		wp_send_json_success( array( 'message' => __( 'Configuration saved successfully!', 'wc-team-payroll' ) ) );
	}

	/**
	 * Sanitize role configuration
	 */
	private function sanitize_role_config( $role_config ) {
		$sanitized = array();
		
		// Sanitize earnings ranges
		if ( isset( $role_config['earnings_ranges'] ) && is_array( $role_config['earnings_ranges'] ) ) {
			$sanitized['earnings_ranges'] = array();
			foreach ( $role_config['earnings_ranges'] as $range ) {
				if ( is_array( $range ) ) {
					$sanitized['earnings_ranges'][] = array(
						'min' => floatval( $range['min'] ),
						'max' => floatval( $range['max'] ),
						'points' => floatval( $range['points'] ),
					);
				}
			}
		}
		
		// Sanitize orders ranges
		if ( isset( $role_config['orders_ranges'] ) && is_array( $role_config['orders_ranges'] ) ) {
			$sanitized['orders_ranges'] = array();
			foreach ( $role_config['orders_ranges'] as $range ) {
				if ( is_array( $range ) ) {
					$sanitized['orders_ranges'][] = array(
						'min' => intval( $range['min'] ),
						'max' => intval( $range['max'] ),
						'points' => floatval( $range['points'] ),
					);
				}
			}
		}
		
		// Sanitize AOV ranges
		if ( isset( $role_config['aov_ranges'] ) && is_array( $role_config['aov_ranges'] ) ) {
			$sanitized['aov_ranges'] = array();
			foreach ( $role_config['aov_ranges'] as $range ) {
				if ( is_array( $range ) ) {
					$sanitized['aov_ranges'][] = array(
						'min' => floatval( $range['min'] ),
						'max' => floatval( $range['max'] ),
						'points' => floatval( $range['points'] ),
					);
				}
			}
		}
		
		return $sanitized;
	}

	/**
	 * AJAX: Get role configuration
	 */
	public function ajax_get_role_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
		
		if ( empty( $role ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid role', 'wc-team-payroll' ) ) );
		}

		$performance_config = get_option( 'wc_tp_performance_config', array() );
		$role_config = isset( $performance_config['roles'][ $role ] ) ? $performance_config['roles'][ $role ] : $this->get_default_role_config();

		ob_start();
		$this->render_role_config_form( $role, $role_config );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Get role goals configuration
	 */
	public function ajax_get_role_goals() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
		
		if ( empty( $role ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid role', 'wc-team-payroll' ) ) );
		}

		$goals_config = get_option( 'wc_tp_goals_config', array() );
		$role_goals = isset( $goals_config['roles'][ $role ] ) ? $goals_config['roles'][ $role ] : $this->get_default_role_goals();

		ob_start();
		$this->render_role_goals_form( $role, $role_goals );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Save goals configuration
	 */
	public function ajax_save_goals_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
		
		// Get existing configuration
		$existing_config = get_option( 'wc_tp_goals_config', array() );
		
		// Update global settings
		if ( isset( $config['period'] ) ) {
			$existing_config['period'] = sanitize_text_field( $config['period'] );
		}
		if ( isset( $config['display_mode'] ) ) {
			$existing_config['display_mode'] = sanitize_text_field( $config['display_mode'] );
		}
		if ( isset( $config['show_stretch'] ) ) {
			$existing_config['show_stretch'] = intval( $config['show_stretch'] );
		}
		
		// Update role-specific goals
		if ( isset( $config['roles'] ) && is_array( $config['roles'] ) ) {
			if ( ! isset( $existing_config['roles'] ) ) {
				$existing_config['roles'] = array();
			}
			
			foreach ( $config['roles'] as $role => $role_goals ) {
				$existing_config['roles'][ $role ] = $this->sanitize_role_goals( $role_goals );
			}
		}
		
		// Save configuration
		update_option( 'wc_tp_goals_config', $existing_config );

		// Clear all user goal caches to force recalculation with new thresholds
		$this->clear_all_goal_caches();

		wp_send_json_success( array( 'message' => __( 'Goals configuration saved successfully!', 'wc-team-payroll' ) ) );
	}

	/**
	 * AJAX: Clone role goals configuration
	 */
	public function ajax_clone_role_goals() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$from_role = isset( $_POST['from_role'] ) ? sanitize_text_field( $_POST['from_role'] ) : '';
		$to_role = isset( $_POST['to_role'] ) ? sanitize_text_field( $_POST['to_role'] ) : '';

		if ( empty( $from_role ) || empty( $to_role ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid roles', 'wc-team-payroll' ) ) );
		}

		$goals_config = get_option( 'wc_tp_goals_config', array() );
		
		if ( isset( $goals_config['roles'][ $from_role ] ) ) {
			$goals_config['roles'][ $to_role ] = $goals_config['roles'][ $from_role ];
			update_option( 'wc_tp_goals_config', $goals_config );
			wp_send_json_success( array( 'message' => __( 'Goals configuration cloned successfully!', 'wc-team-payroll' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Source role goals not found', 'wc-team-payroll' ) ) );
		}
	}

	/**
	 * AJAX: Get role achievements configuration
	 */
	public function ajax_get_role_achievements() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
		
		if ( empty( $role ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid role', 'wc-team-payroll' ) ) );
		}

		$achievements_config = get_option( 'wc_tp_achievements_config', array() );
		$role_achievements = isset( $achievements_config['roles'][ $role ] ) ? $achievements_config['roles'][ $role ] : $this->get_default_role_achievements();

		ob_start();
		$this->render_role_achievements_form( $role, $role_achievements );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Save achievements configuration
	 */
	public function ajax_save_achievements_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
		
		// Get existing configuration
		$existing_config = get_option( 'wc_tp_achievements_config', array() );
		
		// Update global settings
		if ( isset( $config['enabled'] ) ) {
			$existing_config['enabled'] = intval( $config['enabled'] );
		}
		if ( isset( $config['display_style'] ) ) {
			$existing_config['display_style'] = sanitize_text_field( $config['display_style'] );
		}
		if ( isset( $config['period'] ) ) {
			$existing_config['period'] = sanitize_text_field( $config['period'] );
		}
		if ( isset( $config['show_locked'] ) ) {
			$existing_config['show_locked'] = intval( $config['show_locked'] );
		}
		if ( isset( $config['notification'] ) ) {
			$existing_config['notification'] = intval( $config['notification'] );
		}
		
		// Update role-specific achievements
		if ( isset( $config['roles'] ) && is_array( $config['roles'] ) ) {
			if ( ! isset( $existing_config['roles'] ) ) {
				$existing_config['roles'] = array();
			}
			
			foreach ( $config['roles'] as $role => $role_achievements ) {
				$existing_config['roles'][ $role ] = $this->sanitize_role_achievements( $role_achievements );
			}
		}
		
		// Save configuration
		update_option( 'wc_tp_achievements_config', $existing_config );

		// Clear all user achievement caches to force recalculation with new thresholds
		$this->clear_all_achievement_caches();

		wp_send_json_success( array( 'message' => __( 'Achievements configuration saved successfully!', 'wc-team-payroll' ) ) );
	}

	/**
	 * AJAX: Clone role achievements configuration
	 */
	public function ajax_clone_role_achievements() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$from_role = isset( $_POST['from_role'] ) ? sanitize_text_field( $_POST['from_role'] ) : '';
		$to_role = isset( $_POST['to_role'] ) ? sanitize_text_field( $_POST['to_role'] ) : '';

		if ( empty( $from_role ) || empty( $to_role ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid roles', 'wc-team-payroll' ) ) );
		}

		$achievements_config = get_option( 'wc_tp_achievements_config', array() );
		
		if ( isset( $achievements_config['roles'][ $from_role ] ) ) {
			$achievements_config['roles'][ $to_role ] = $achievements_config['roles'][ $from_role ];
			update_option( 'wc_tp_achievements_config', $achievements_config );
			wp_send_json_success( array( 'message' => __( 'Achievements configuration cloned successfully!', 'wc-team-payroll' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Source role achievements not found', 'wc-team-payroll' ) ) );
		}
	}

	/**
	 * AJAX: Save baselines configuration
	 */
	public function ajax_save_baselines_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
		
		// Sanitize configuration
		$sanitized_config = array(
			'method' => isset( $config['method'] ) ? sanitize_text_field( $config['method'] ) : 'rolling_average',
			'periods' => isset( $config['periods'] ) ? intval( $config['periods'] ) : 3,
			'percentile' => isset( $config['percentile'] ) ? intval( $config['percentile'] ) : 75,
			'update_frequency' => isset( $config['update_frequency'] ) ? sanitize_text_field( $config['update_frequency'] ) : 'monthly',
			'minimum_data' => isset( $config['minimum_data'] ) ? intval( $config['minimum_data'] ) : 5,
			'show_comparison' => isset( $config['show_comparison'] ) ? intval( $config['show_comparison'] ) : 1,
			'show_trend' => isset( $config['show_trend'] ) ? intval( $config['show_trend'] ) : 1,
			'show_history' => isset( $config['show_history'] ) ? intval( $config['show_history'] ) : 0,
			'comparison_format' => isset( $config['comparison_format'] ) ? sanitize_text_field( $config['comparison_format'] ) : 'percentage',
		);
		
		// Save configuration
		update_option( 'wc_tp_baselines_config', $sanitized_config );

		wp_send_json_success( array( 'message' => __( 'Baselines configuration saved successfully!', 'wc-team-payroll' ) ) );
	}

	/**
	 * AJAX: Calculate baseline preview
	 */
	public function ajax_calculate_baseline_preview() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$method = isset( $_POST['method'] ) ? sanitize_text_field( $_POST['method'] ) : 'rolling_average';
		$periods = isset( $_POST['periods'] ) ? intval( $_POST['periods'] ) : 3;
		$percentile = isset( $_POST['percentile'] ) ? intval( $_POST['percentile'] ) : 75;
		
		$earnings_data = isset( $_POST['earnings_data'] ) ? array_map( 'floatval', $_POST['earnings_data'] ) : array();
		$orders_data = isset( $_POST['orders_data'] ) ? array_map( 'intval', $_POST['orders_data'] ) : array();
		$aov_data = isset( $_POST['aov_data'] ) ? array_map( 'floatval', $_POST['aov_data'] ) : array();

		// Calculate baselines
		$earnings_baseline = $this->calculate_baseline( $earnings_data, $method, $periods, $percentile );
		$orders_baseline = $this->calculate_baseline( $orders_data, $method, $periods, $percentile );
		$aov_baseline = $this->calculate_baseline( $aov_data, $method, $periods, $percentile );

		wp_send_json_success( array(
			'earnings' => $earnings_baseline,
			'orders' => $orders_baseline,
			'aov' => $aov_baseline,
		) );
	}

	/**
	 * Calculate baseline from data array
	 */
	private function calculate_baseline( $data, $method, $periods, $percentile ) {
		if ( empty( $data ) ) {
			return 0;
		}

		switch ( $method ) {
			case 'rolling_average':
				$slice = array_slice( $data, -$periods );
				return array_sum( $slice ) / count( $slice );

			case 'historical_average':
				return array_sum( $data ) / count( $data );

			case 'best_period':
				return max( $data );

			case 'median':
				sort( $data );
				$count = count( $data );
				$middle = floor( ( $count - 1 ) / 2 );
				if ( $count % 2 ) {
					return $data[ $middle ];
				} else {
					return ( $data[ $middle ] + $data[ $middle + 1 ] ) / 2;
				}

			case 'percentile':
				sort( $data );
				$index = ( $percentile / 100 ) * ( count( $data ) - 1 );
				$lower = floor( $index );
				$upper = ceil( $index );
				if ( $lower == $upper ) {
					return $data[ $lower ];
				} else {
					return $data[ $lower ] * ( $upper - $index ) + $data[ $upper ] * ( $index - $lower );
				}

			case 'manual':
			default:
				return array_sum( $data ) / count( $data );
		}
	}

	/**
	 * Calculate employee earnings with revenue attribution
	 * 
	 * @param int $employee_id Employee user ID
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return float Total attributed earnings
	 */
	public function calculate_employee_earnings_with_attribution( $employee_id, $start_date, $end_date ) {
		$calculation_config = get_option( 'wc_tp_calculation_config', array() );
		$revenue_attribution = isset( $calculation_config['revenue_attribution'] ) ? $calculation_config['revenue_attribution'] : 'full';
		
		if ( $revenue_attribution === 'commission_split' ) {
			return $this->calculate_earnings_with_commission_split( $employee_id, $start_date, $end_date );
		} else {
			return $this->calculate_earnings_full_value( $employee_id, $start_date, $end_date );
		}
	}

	/**
	 * Calculate earnings using commission split percentages
	 * 
	 * @param int $employee_id Employee user ID
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return float Total attributed earnings
	 */
	private function calculate_earnings_with_commission_split( $employee_id, $start_date, $end_date ) {
		// Get commission calculation statuses
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		
		// Get orders for the period with proper time coverage
		$date_query = $start_date . ' 00:00:00...' . $end_date . ' 23:59:59';
		
		$args = array(
			'limit'        => -1,
			'date_created' => $date_query,
			'status'       => $commission_statuses,
			'meta_query'   => array(
				'relation' => 'OR',
				array(
					'key'     => '_wc_tp_agent_id',
					'value'   => $employee_id,
					'compare' => '='
				),
				array(
					'key'     => '_wc_tp_processor_id',
					'value'   => $employee_id,
					'compare' => '='
				)
			)
		);
		
		$orders = wc_get_orders( $args );
		$total_attributed_earnings = 0;
		
		foreach ( $orders as $order ) {
			$order_total = $order->get_total();
			$agent_id = get_post_meta( $order->get_id(), '_wc_tp_agent_id', true );
			$processor_id = get_post_meta( $order->get_id(), '_wc_tp_processor_id', true );
			
			// Get employee's attribution percentage for this order
			$attribution_percentage = $this->get_employee_attribution_percentage( 
				$order->get_id(), 
				$employee_id, 
				$agent_id, 
				$processor_id 
			);
			
			// Apply attribution percentage to order total
			$attributed_amount = $order_total * ( $attribution_percentage / 100 );
			$total_attributed_earnings += $attributed_amount;
		}
		
		return $total_attributed_earnings;
	}

	/**
	 * Calculate earnings using full order value (original method)
	 * 
	 * @param int $employee_id Employee user ID
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return float Total earnings
	 */
	private function calculate_earnings_full_value( $employee_id, $start_date, $end_date ) {
		// Get commission calculation statuses
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		
		// Get orders for the period with proper time coverage
		$date_query = $start_date . ' 00:00:00...' . $end_date . ' 23:59:59';
		
		$args = array(
			'limit'        => -1,
			'date_created' => $date_query,
			'status'       => $commission_statuses,
			'meta_query'   => array(
				'relation' => 'OR',
				array(
					'key'     => '_wc_tp_agent_id',
					'value'   => $employee_id,
					'compare' => '='
				),
				array(
					'key'     => '_wc_tp_processor_id',
					'value'   => $employee_id,
					'compare' => '='
				)
			)
		);
		
		$orders = wc_get_orders( $args );
		$total_earnings = 0;
		
		foreach ( $orders as $order ) {
			$total_earnings += $order->get_total();
		}
		
		return $total_earnings;
	}

	/**
	 * Get employee's attribution percentage for a specific order
	 * 
	 * @param int $order_id Order ID
	 * @param int $employee_id Employee user ID
	 * @param int $agent_id Agent ID from order
	 * @param int $processor_id Processor ID from order
	 * @return float Attribution percentage (0-100)
	 */
	private function get_employee_attribution_percentage( $order_id, $employee_id, $agent_id, $processor_id ) {
		// Get commission configuration
		$commission_config = get_option( 'wc_team_payroll_commission_config', array() );
		
		// Default percentages if not configured
		$agent_percentage = isset( $commission_config['agent_percentage'] ) ? floatval( $commission_config['agent_percentage'] ) : 70;
		$processor_percentage = isset( $commission_config['processor_percentage'] ) ? floatval( $commission_config['processor_percentage'] ) : 30;
		
		// Determine employee's role and return appropriate percentage
		if ( $employee_id == $agent_id && $employee_id == $processor_id ) {
			// Same person is both agent and processor - gets 100%
			return 100;
		} elseif ( $employee_id == $agent_id ) {
			// Employee is the agent
			return $agent_percentage;
		} elseif ( $employee_id == $processor_id ) {
			// Employee is the processor
			return $processor_percentage;
		}
		
		// Employee is not assigned to this order
		return 0;
	}

	/**
	 * AJAX: Save calculation configuration
	 */
	public function ajax_save_calculation_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
		
		// Sanitize configuration
		$sanitized_config = array(
			'score_method' => isset( $config['score_method'] ) ? sanitize_text_field( $config['score_method'] ) : 'additive',
			'weight_earnings' => isset( $config['weight_earnings'] ) ? intval( $config['weight_earnings'] ) : 40,
			'weight_orders' => isset( $config['weight_orders'] ) ? intval( $config['weight_orders'] ) : 35,
			'weight_aov' => isset( $config['weight_aov'] ) ? intval( $config['weight_aov'] ) : 25,
			'score_cap' => isset( $config['score_cap'] ) ? floatval( $config['score_cap'] ) : 10,
			'rounding' => isset( $config['rounding'] ) ? sanitize_text_field( $config['rounding'] ) : 'one_decimal',
			'period_type' => isset( $config['period_type'] ) ? sanitize_text_field( $config['period_type'] ) : 'calendar_month',
			'custom_start_date' => isset( $config['custom_start_date'] ) ? sanitize_text_field( $config['custom_start_date'] ) : '',
			'custom_end_date' => isset( $config['custom_end_date'] ) ? sanitize_text_field( $config['custom_end_date'] ) : '',
			'revenue_attribution' => isset( $config['revenue_attribution'] ) ? sanitize_text_field( $config['revenue_attribution'] ) : 'full',
			'exclude_refunds' => isset( $config['exclude_refunds'] ) ? intval( $config['exclude_refunds'] ) : 1,
			'aov_method' => isset( $config['aov_method'] ) ? sanitize_text_field( $config['aov_method'] ) : 'total_divided_orders',
			'custom_formula' => isset( $config['custom_formula'] ) ? sanitize_text_field( $config['custom_formula'] ) : '',
		);
		
		// Save configuration
		update_option( 'wc_tp_calculation_config', $sanitized_config );

		wp_send_json_success( array( 'message' => __( 'Calculation configuration saved successfully!', 'wc-team-payroll' ) ) );
	}

	/**
	 * AJAX: Test formula
	 */
	public function ajax_test_formula() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$method = isset( $_POST['method'] ) ? sanitize_text_field( $_POST['method'] ) : 'additive';
		$base_score = isset( $_POST['base_score'] ) ? floatval( $_POST['base_score'] ) : 5;
		$earnings_points = isset( $_POST['earnings_points'] ) ? floatval( $_POST['earnings_points'] ) : 0;
		$orders_points = isset( $_POST['orders_points'] ) ? floatval( $_POST['orders_points'] ) : 0;
		$aov_points = isset( $_POST['aov_points'] ) ? floatval( $_POST['aov_points'] ) : 0;
		$weight_earnings = isset( $_POST['weight_earnings'] ) ? intval( $_POST['weight_earnings'] ) : 40;
		$weight_orders = isset( $_POST['weight_orders'] ) ? intval( $_POST['weight_orders'] ) : 35;
		$weight_aov = isset( $_POST['weight_aov'] ) ? intval( $_POST['weight_aov'] ) : 25;
		$score_cap = isset( $_POST['score_cap'] ) ? floatval( $_POST['score_cap'] ) : 10;
		$rounding = isset( $_POST['rounding'] ) ? sanitize_text_field( $_POST['rounding'] ) : 'one_decimal';

		$steps = array();
		$final_score = 0;

		switch ( $method ) {
			case 'additive':
				$steps[] = "Base Score: {$base_score}";
				$steps[] = "+ Earnings Points: {$earnings_points}";
				$steps[] = "+ Orders Points: {$orders_points}";
				$steps[] = "+ AOV Points: {$aov_points}";
				$final_score = $base_score + $earnings_points + $orders_points + $aov_points;
				break;

			case 'weighted':
				$steps[] = "Earnings: {$earnings_points} × {$weight_earnings}% = " . ( $earnings_points * $weight_earnings / 100 );
				$steps[] = "Orders: {$orders_points} × {$weight_orders}% = " . ( $orders_points * $weight_orders / 100 );
				$steps[] = "AOV: {$aov_points} × {$weight_aov}% = " . ( $aov_points * $weight_aov / 100 );
				$final_score = ( $earnings_points * $weight_earnings / 100 ) + ( $orders_points * $weight_orders / 100 ) + ( $aov_points * $weight_aov / 100 );
				$steps[] = "Base Score: {$base_score}";
				$final_score += $base_score;
				break;

			case 'multiplicative':
				$multiplier = 1 + ( $earnings_points / 10 ) + ( $orders_points / 10 ) + ( $aov_points / 10 );
				$steps[] = "Multiplier: 1 + ({$earnings_points}/10) + ({$orders_points}/10) + ({$aov_points}/10) = {$multiplier}";
				$steps[] = "Base Score × Multiplier: {$base_score} × {$multiplier}";
				$final_score = $base_score * $multiplier;
				break;

			case 'custom':
				$steps[] = "Custom formula not yet implemented";
				$final_score = $base_score + $earnings_points + $orders_points + $aov_points;
				break;
		}

		// Apply cap
		if ( $final_score > $score_cap ) {
			$steps[] = "Score capped at: {$score_cap}";
			$final_score = $score_cap;
		}

		// Apply rounding
		switch ( $rounding ) {
			case 'none':
				break;
			case 'one_decimal':
				$final_score = round( $final_score, 1 );
				break;
			case 'two_decimals':
				$final_score = round( $final_score, 2 );
				break;
			case 'whole':
				$final_score = round( $final_score );
				break;
		}

		wp_send_json_success( array(
			'steps' => $steps,
			'final_score' => $final_score,
		) );
	}

	/**
	 * AJAX: Save system configuration
	 */
	public function ajax_save_system_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$config = isset( $_POST['config'] ) ? $_POST['config'] : array();
		
		// Sanitize configuration
		$sanitized_config = array(
			'enable_performance' => isset( $config['enable_performance'] ) ? intval( $config['enable_performance'] ) : 1,
			'show_score' => isset( $config['show_score'] ) ? intval( $config['show_score'] ) : 1,
			'show_goals' => isset( $config['show_goals'] ) ? intval( $config['show_goals'] ) : 1,
			'show_achievements' => isset( $config['show_achievements'] ) ? intval( $config['show_achievements'] ) : 1,
			'show_baselines' => isset( $config['show_baselines'] ) ? intval( $config['show_baselines'] ) : 1,
			'show_leaderboard' => isset( $config['show_leaderboard'] ) ? intval( $config['show_leaderboard'] ) : 0,
			'refresh_interval' => isset( $config['refresh_interval'] ) ? intval( $config['refresh_interval'] ) : 30,
			'data_retention' => isset( $config['data_retention'] ) ? sanitize_text_field( $config['data_retention'] ) : 'forever',
			'anonymize_data' => isset( $config['anonymize_data'] ) ? intval( $config['anonymize_data'] ) : 0,
			'cache_duration' => isset( $config['cache_duration'] ) ? intval( $config['cache_duration'] ) : 300,
			'debug_mode' => isset( $config['debug_mode'] ) ? intval( $config['debug_mode'] ) : 0,
		);
		
		// Save configuration
		update_option( 'wc_tp_system_config', $sanitized_config );

		wp_send_json_success( array( 'message' => __( 'System configuration saved successfully!', 'wc-team-payroll' ) ) );
	}

	/**
	 * AJAX: Reset all data
	 */
	public function ajax_reset_all_data() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		// Delete all performance-related options
		delete_option( 'wc_tp_performance_config' );
		delete_option( 'wc_tp_goals_config' );
		delete_option( 'wc_tp_achievements_config' );
		delete_option( 'wc_tp_baselines_config' );
		delete_option( 'wc_tp_calculation_config' );
		delete_option( 'wc_tp_system_config' );
		delete_option( 'wc_tp_achievement_bonuses' ); // Phase 2 Part 2

		wp_send_json_success( array( 'message' => __( 'All performance data has been reset!', 'wc-team-payroll' ) ) );
	}

	/**
	 * AJAX: Clone role configuration
	 */
	public function ajax_clone_role_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$from_role = isset( $_POST['from_role'] ) ? sanitize_text_field( $_POST['from_role'] ) : '';
		$to_role = isset( $_POST['to_role'] ) ? sanitize_text_field( $_POST['to_role'] ) : '';

		if ( empty( $from_role ) || empty( $to_role ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid roles', 'wc-team-payroll' ) ) );
		}

		$performance_config = get_option( 'wc_tp_performance_config', array() );
		
		if ( isset( $performance_config['roles'][ $from_role ] ) ) {
			$performance_config['roles'][ $to_role ] = $performance_config['roles'][ $from_role ];
			update_option( 'wc_tp_performance_config', $performance_config );
			wp_send_json_success( array( 'message' => __( 'Configuration cloned successfully!', 'wc-team-payroll' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Source role configuration not found', 'wc-team-payroll' ) ) );
		}
	}

	/**
	 * AJAX: Preview calculation
	 */
	public function ajax_preview_calculation() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		// Get calculation settings
		$calculation_config = get_option( 'wc_tp_calculation_config', array() );
		
		// Get input values
		$base_score = isset( $_POST['base_score'] ) ? floatval( $_POST['base_score'] ) : 5;
		$earnings_points = isset( $_POST['earnings_points'] ) ? floatval( $_POST['earnings_points'] ) : 0;
		$orders_points = isset( $_POST['orders_points'] ) ? floatval( $_POST['orders_points'] ) : 0;
		$aov_points = isset( $_POST['aov_points'] ) ? floatval( $_POST['aov_points'] ) : 0;

		// Get calculation method
		$score_method = isset( $calculation_config['score_method'] ) ? $calculation_config['score_method'] : 'additive';
		$score_cap = isset( $calculation_config['score_cap'] ) ? floatval( $calculation_config['score_cap'] ) : 10;
		$rounding = isset( $calculation_config['rounding'] ) ? $calculation_config['rounding'] : 'one_decimal';

		// Calculate based on method
		$final_score = 0;
		$steps = array();

		switch ( $score_method ) {
			case 'additive':
				$final_score = $base_score + $earnings_points + $orders_points + $aov_points;
				$steps[] = sprintf( __( 'Base Score: %s', 'wc-team-payroll' ), number_format( $base_score, 2 ) );
				$steps[] = sprintf( __( '+ Earnings Points: %s', 'wc-team-payroll' ), number_format( $earnings_points, 2 ) );
				$steps[] = sprintf( __( '+ Orders Points: %s', 'wc-team-payroll' ), number_format( $orders_points, 2 ) );
				$steps[] = sprintf( __( '+ AOV Points: %s', 'wc-team-payroll' ), number_format( $aov_points, 2 ) );
				$steps[] = sprintf( __( '= Subtotal: %s', 'wc-team-payroll' ), number_format( $final_score, 2 ) );
				break;

			case 'weighted':
				$weight_earnings = isset( $calculation_config['weight_earnings'] ) ? floatval( $calculation_config['weight_earnings'] ) : 40;
				$weight_orders = isset( $calculation_config['weight_orders'] ) ? floatval( $calculation_config['weight_orders'] ) : 35;
				$weight_aov = isset( $calculation_config['weight_aov'] ) ? floatval( $calculation_config['weight_aov'] ) : 25;

				$weighted_score = ( $earnings_points * ( $weight_earnings / 100 ) ) +
									( $orders_points * ( $weight_orders / 100 ) ) +
									( $aov_points * ( $weight_aov / 100 ) );
				$final_score = $base_score + $weighted_score;

				$steps[] = sprintf( __( 'Base Score: %s', 'wc-team-payroll' ), number_format( $base_score, 2 ) );
				$steps[] = sprintf( __( 'Earnings: %s × %s = %s', 'wc-team-payroll' ), number_format( $earnings_points, 2 ), number_format( $weight_earnings / 100, 2 ), number_format( $earnings_points * ( $weight_earnings / 100 ), 2 ) );
				$steps[] = sprintf( __( 'Orders: %s × %s = %s', 'wc-team-payroll' ), number_format( $orders_points, 2 ), number_format( $weight_orders / 100, 2 ), number_format( $orders_points * ( $weight_orders / 100 ), 2 ) );
				$steps[] = sprintf( __( 'AOV: %s × %s = %s', 'wc-team-payroll' ), number_format( $aov_points, 2 ), number_format( $weight_aov / 100, 2 ), number_format( $aov_points * ( $weight_aov / 100 ), 2 ) );
				$steps[] = sprintf( __( '= Subtotal: %s', 'wc-team-payroll' ), number_format( $final_score, 2 ) );
				break;

			case 'multiplicative':
				$final_score = $base_score * ( 1 + ( $earnings_points * 0.1 ) ) * ( 1 + ( $orders_points * 0.1 ) ) * ( 1 + ( $aov_points * 0.1 ) );
				$steps[] = sprintf( __( 'Base Score: %s', 'wc-team-payroll' ), number_format( $base_score, 2 ) );
				$steps[] = sprintf( __( '× (1 + Earnings × 0.1): %s', 'wc-team-payroll' ), number_format( 1 + ( $earnings_points * 0.1 ), 2 ) );
				$steps[] = sprintf( __( '× (1 + Orders × 0.1): %s', 'wc-team-payroll' ), number_format( 1 + ( $orders_points * 0.1 ), 2 ) );
				$steps[] = sprintf( __( '× (1 + AOV × 0.1): %s', 'wc-team-payroll' ), number_format( 1 + ( $aov_points * 0.1 ), 2 ) );
				$steps[] = sprintf( __( '= Subtotal: %s', 'wc-team-payroll' ), number_format( $final_score, 2 ) );
				break;

			case 'custom':
				$custom_formula = isset( $calculation_config['custom_formula'] ) ? $calculation_config['custom_formula'] : '';
				if ( empty( $custom_formula ) ) {
					wp_send_json_error( array( 'message' => __( 'Custom formula not configured', 'wc-team-payroll' ) ) );
				}

				try {
					// Replace variable names with actual values
					$formula = $custom_formula;
					$formula = str_replace( 'base', $base_score, $formula );
					$formula = str_replace( 'earnings', $earnings_points, $formula );
					$formula = str_replace( 'orders', $orders_points, $formula );
					$formula = str_replace( 'aov', $aov_points, $formula );

					// Evaluate the formula (safe evaluation with limited scope)
					$final_score = eval( 'return (' . $formula . ');' );
					$steps[] = sprintf( __( 'Formula: %s', 'wc-team-payroll' ), $custom_formula );
					$steps[] = sprintf( __( '= Result: %s', 'wc-team-payroll' ), number_format( $final_score, 2 ) );
				} catch ( Exception $e ) {
					wp_send_json_error( array( 'message' => sprintf( __( 'Invalid formula: %s', 'wc-team-payroll' ), $e->getMessage() ) ) );
				}
				break;

			default:
				$final_score = $base_score + $earnings_points + $orders_points + $aov_points;
		}

		// Apply score cap
		$capped_score = min( $final_score, $score_cap );
		if ( $capped_score !== $final_score ) {
			$steps[] = sprintf( __( 'Score Cap Applied: %s', 'wc-team-payroll' ), number_format( $score_cap, 2 ) );
			$steps[] = sprintf( __( 'Capped Score: %s', 'wc-team-payroll' ), number_format( $capped_score, 2 ) );
		}

		// Apply rounding
		$rounded_score = $capped_score;
		switch ( $rounding ) {
			case 'none':
				$rounded_score = $capped_score;
				$steps[] = __( 'Rounding: None', 'wc-team-payroll' );
				break;
			case 'one_decimal':
				$rounded_score = round( $capped_score, 1 );
				$steps[] = __( 'Rounding: One Decimal', 'wc-team-payroll' );
				break;
			case 'two_decimals':
				$rounded_score = round( $capped_score, 2 );
				$steps[] = __( 'Rounding: Two Decimals', 'wc-team-payroll' );
				break;
			case 'whole':
				$rounded_score = round( $capped_score );
				$steps[] = __( 'Rounding: Whole Number', 'wc-team-payroll' );
				break;
		}

		wp_send_json_success( array(
			'final_score' => $rounded_score,
			'steps' => $steps,
			'method' => $score_method,
		) );
	}

	/**
	 * Render role configuration form
	 */
	private function render_role_config_form( $role, $config ) {
		?>
		<div class="wc-tp-role-config-form" data-role="<?php echo esc_attr( $role ); ?>">
			<h4><?php echo esc_html( sprintf( __( 'Configuring: %s', 'wc-team-payroll' ), $role ) ); ?></h4>
			
			<!-- Earnings Factor -->
			<div class="wc-tp-factor-config">
				<h5>
					<?php esc_html_e( 'Earnings Factor Configuration', 'wc-team-payroll' ); ?>
					<span class="wc-tp-factor-info"><?php esc_html_e( '(Max Points Cap: 3.0)', 'wc-team-payroll' ); ?></span>
				</h5>
				<p class="description"><?php esc_html_e( 'Define earnings ranges and corresponding points for this role. Example: $5,000-$6,999 = +2.0 points', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-ranges-container" data-factor="earnings" data-role="<?php echo esc_attr( $role ); ?>">
					<?php
					$earnings_ranges = isset( $config['earnings_ranges'] ) ? $config['earnings_ranges'] : array();
					foreach ( $earnings_ranges as $index => $range ) {
						$this->render_range_row( 'earnings', $range, $index );
					}
					?>
					<button type="button" class="button wc-tp-add-range">
						<span class="dashicons dashicons-plus-alt"></span>
						<?php esc_html_e( 'Add Earnings Range', 'wc-team-payroll' ); ?>
					</button>
				</div>
			</div>

			<!-- Orders Factor -->
			<div class="wc-tp-factor-config">
				<h5>
					<?php esc_html_e( 'Orders Factor Configuration', 'wc-team-payroll' ); ?>
					<span class="wc-tp-factor-info"><?php esc_html_e( '(Max Points Cap: 2.0)', 'wc-team-payroll' ); ?></span>
				</h5>
				<p class="description"><?php esc_html_e( 'Define order count ranges and corresponding points for this role. Example: 30-49 orders = +1.0 points', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-ranges-container" data-factor="orders" data-role="<?php echo esc_attr( $role ); ?>">
					<?php
					$orders_ranges = isset( $config['orders_ranges'] ) ? $config['orders_ranges'] : array();
					foreach ( $orders_ranges as $index => $range ) {
						$this->render_range_row( 'orders', $range, $index );
					}
					?>
					<button type="button" class="button wc-tp-add-range">
						<span class="dashicons dashicons-plus-alt"></span>
						<?php esc_html_e( 'Add Orders Range', 'wc-team-payroll' ); ?>
					</button>
				</div>
			</div>

			<!-- AOV Factor -->
			<div class="wc-tp-factor-config">
				<h5>
					<?php esc_html_e( 'Average Order Value (AOV) Factor Configuration', 'wc-team-payroll' ); ?>
					<span class="wc-tp-factor-info"><?php esc_html_e( '(Max Points Cap: 1.0)', 'wc-team-payroll' ); ?></span>
				</h5>
				<p class="description"><?php esc_html_e( 'Define AOV ranges and corresponding points for this role. Example: $200-$499 = +0.5 points', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-ranges-container" data-factor="aov" data-role="<?php echo esc_attr( $role ); ?>">
					<?php
					$aov_ranges = isset( $config['aov_ranges'] ) ? $config['aov_ranges'] : array();
					foreach ( $aov_ranges as $index => $range ) {
						$this->render_range_row( 'aov', $range, $index );
					}
					?>
					<button type="button" class="button wc-tp-add-range">
						<span class="dashicons dashicons-plus-alt"></span>
						<?php esc_html_e( 'Add AOV Range', 'wc-team-payroll' ); ?>
					</button>
				</div>
			</div>

			<!-- Preview Calculation -->
			<div class="wc-tp-factor-config wc-tp-preview-section">
				<h5><?php esc_html_e( 'Preview Calculation', 'wc-team-payroll' ); ?></h5>
				<p class="description"><?php esc_html_e( 'Test how the performance score will be calculated with sample data.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-preview-inputs">
					<div class="wc-tp-preview-input-group">
						<label><?php esc_html_e( 'Sample Earnings:', 'wc-team-payroll' ); ?></label>
						<input type="number" class="wc-tp-preview-earnings" placeholder="5000" step="0.01" />
					</div>
					<div class="wc-tp-preview-input-group">
						<label><?php esc_html_e( 'Sample Orders:', 'wc-team-payroll' ); ?></label>
						<input type="number" class="wc-tp-preview-orders" placeholder="30" step="1" />
					</div>
					<div class="wc-tp-preview-input-group">
						<label><?php esc_html_e( 'Sample AOV:', 'wc-team-payroll' ); ?></label>
						<input type="number" class="wc-tp-preview-aov" placeholder="250" step="0.01" />
					</div>
					<button type="button" class="button button-secondary wc-tp-calculate-preview">
						<span class="dashicons dashicons-calculator"></span>
						<?php esc_html_e( 'Calculate Score', 'wc-team-payroll' ); ?>
					</button>
				</div>
				
				<div class="wc-tp-preview-result" style="display: none;">
					<div class="wc-tp-preview-breakdown">
						<h6><?php esc_html_e( 'Score Breakdown:', 'wc-team-payroll' ); ?></h6>
						<div class="wc-tp-preview-item">
							<span class="label"><?php esc_html_e( 'Base Score:', 'wc-team-payroll' ); ?></span>
							<span class="value" id="preview-base-score">5.0</span>
						</div>
						<div class="wc-tp-preview-item">
							<span class="label"><?php esc_html_e( 'Earnings Points:', 'wc-team-payroll' ); ?></span>
							<span class="value" id="preview-earnings-points">+0.0</span>
						</div>
						<div class="wc-tp-preview-item">
							<span class="label"><?php esc_html_e( 'Orders Points:', 'wc-team-payroll' ); ?></span>
							<span class="value" id="preview-orders-points">+0.0</span>
						</div>
						<div class="wc-tp-preview-item">
							<span class="label"><?php esc_html_e( 'AOV Points:', 'wc-team-payroll' ); ?></span>
							<span class="value" id="preview-aov-points">+0.0</span>
						</div>
						<div class="wc-tp-preview-item wc-tp-preview-total">
							<span class="label"><?php esc_html_e( 'Final Score:', 'wc-team-payroll' ); ?></span>
							<span class="value" id="preview-final-score">5.0/10</span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single range row
	 */
	private function render_range_row( $factor, $range, $index ) {
		$min = isset( $range['min'] ) ? $range['min'] : 0;
		$max = isset( $range['max'] ) ? $range['max'] : 0;
		$points = isset( $range['points'] ) ? $range['points'] : 0;
		// Get currency symbol from WooCommerce
		$currency_symbol = ( $factor === 'earnings' || $factor === 'aov' ) ? get_woocommerce_currency_symbol() : '';
		?>
		<div class="wc-tp-range-row" data-index="<?php echo esc_attr( $index ); ?>">
			<label><?php esc_html_e( 'Range:', 'wc-team-payroll' ); ?></label>
			<span class="wc-tp-range-currency"><?php echo esc_html( $currency_symbol ); ?></span>
			<input type="number" 
				   name="<?php echo esc_attr( $factor ); ?>_min[]" 
				   value="<?php echo esc_attr( $min ); ?>" 
				   placeholder="Min" 
				   step="<?php echo $factor === 'orders' ? '1' : '0.01'; ?>" 
				   min="0"
				   class="wc-tp-range-min" />
			<span><?php esc_html_e( 'to', 'wc-team-payroll' ); ?></span>
			<span class="wc-tp-range-currency"><?php echo esc_html( $currency_symbol ); ?></span>
			<input type="number" 
				   name="<?php echo esc_attr( $factor ); ?>_max[]" 
				   value="<?php echo esc_attr( $max ); ?>" 
				   placeholder="Max" 
				   step="<?php echo $factor === 'orders' ? '1' : '0.01'; ?>" 
				   min="0"
				   class="wc-tp-range-max" />
			<span>=</span>
			<input type="number" 
				   name="<?php echo esc_attr( $factor ); ?>_points[]" 
				   value="<?php echo esc_attr( $points ); ?>" 
				   placeholder="Points" 
				   step="0.1" 
				   min="0"
				   max="10"
				   class="wc-tp-range-points" />
			<span><?php esc_html_e( 'points', 'wc-team-payroll' ); ?></span>
			<span class="dashicons dashicons-trash wc-tp-remove-range" title="<?php esc_attr_e( 'Remove this range', 'wc-team-payroll' ); ?>"></span>
		</div>
		<?php
	}

	/**
	 * Get default role configuration
	 */
	private function get_default_role_config() {
		return array(
			'earnings_ranges' => array(
				array( 'min' => 0, 'max' => 999, 'points' => 0.0 ),
				array( 'min' => 1000, 'max' => 2999, 'points' => 0.5 ),
				array( 'min' => 3000, 'max' => 4999, 'points' => 1.0 ),
				array( 'min' => 5000, 'max' => 6999, 'points' => 2.0 ),
				array( 'min' => 7000, 'max' => 9999, 'points' => 2.5 ),
				array( 'min' => 10000, 'max' => 999999, 'points' => 3.0 ),
			),
			'orders_ranges' => array(
				array( 'min' => 0, 'max' => 9, 'points' => 0.0 ),
				array( 'min' => 10, 'max' => 29, 'points' => 1.0 ),
				array( 'min' => 30, 'max' => 49, 'points' => 1.5 ),
				array( 'min' => 50, 'max' => 999, 'points' => 2.0 ),
			),
			'aov_ranges' => array(
				array( 'min' => 0, 'max' => 199, 'points' => 0.0 ),
				array( 'min' => 200, 'max' => 499, 'points' => 0.5 ),
				array( 'min' => 500, 'max' => 999999, 'points' => 1.0 ),
			),
		);
	}

	/**
	 * Get default role goals
	 */
	private function get_default_role_goals() {
		return array(
			'earnings' => array(
				'minimum' => 1000,
				'target' => 5000,
				'stretch' => 10000,
			),
			'orders' => array(
				'minimum' => 10,
				'target' => 30,
				'stretch' => 50,
			),
			'aov' => array(
				'minimum' => 100,
				'target' => 250,
				'stretch' => 500,
			),
		);
	}

	/**
	 * Get default role achievements
	 */
	private function get_default_role_achievements() {
		return array(
			'earnings_bronze' => array(
				'name' => 'Earnings Bronze',
				'description' => 'Reach $3,000 in earnings',
				'threshold' => 3000,
				'tier' => 'bronze',
				'icon' => 'money-alt',
			),
			'earnings_silver' => array(
				'name' => 'Earnings Silver',
				'description' => 'Reach $7,000 in earnings',
				'threshold' => 7000,
				'tier' => 'silver',
				'icon' => 'money-alt',
			),
			'earnings_gold' => array(
				'name' => 'Earnings Gold',
				'description' => 'Reach $15,000 in earnings',
				'threshold' => 15000,
				'tier' => 'gold',
				'icon' => 'money-alt',
			),
			'orders_bronze' => array(
				'name' => 'Orders Bronze',
				'description' => 'Complete 20 orders',
				'threshold' => 20,
				'tier' => 'bronze',
				'icon' => 'cart',
			),
			'orders_silver' => array(
				'name' => 'Orders Silver',
				'description' => 'Complete 50 orders',
				'threshold' => 50,
				'tier' => 'silver',
				'icon' => 'cart',
			),
			'orders_gold' => array(
				'name' => 'Orders Gold',
				'description' => 'Complete 100 orders',
				'threshold' => 100,
				'tier' => 'gold',
				'icon' => 'cart',
			),
			'aov_bronze' => array(
				'name' => 'AOV Bronze',
				'description' => 'Maintain $200 average order value',
				'threshold' => 200,
				'tier' => 'bronze',
				'icon' => 'chart-line',
			),
			'aov_silver' => array(
				'name' => 'AOV Silver',
				'description' => 'Maintain $400 average order value',
				'threshold' => 400,
				'tier' => 'silver',
				'icon' => 'chart-line',
			),
			'aov_gold' => array(
				'name' => 'AOV Gold',
				'description' => 'Maintain $600 average order value',
				'threshold' => 600,
				'tier' => 'gold',
				'icon' => 'chart-line',
			),
		);
	}

	/**
	 * Render role goals configuration form
	 */
	private function render_role_goals_form( $role, $goals ) {
		?>
		<div class="wc-tp-role-goals-form" data-role="<?php echo esc_attr( $role ); ?>">
			<h4><?php echo esc_html( sprintf( __( 'Configuring Goals: %s', 'wc-team-payroll' ), $role ) ); ?></h4>
			<p class="description"><?php esc_html_e( 'Set minimum, target, and stretch goals for this role. These will be displayed on the employee reports page.', 'wc-team-payroll' ); ?></p>
			
			<!-- Earnings Goals -->
			<div class="wc-tp-goal-config">
				<h5>
					<span class="dashicons dashicons-money-alt"></span>
					<?php esc_html_e( 'Earnings Goals', 'wc-team-payroll' ); ?>
				</h5>
				<p class="description"><?php esc_html_e( 'Set earnings targets for this role. Employees will see their progress toward these goals.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-goal-inputs">
					<div class="wc-tp-goal-input-group">
						<label><?php esc_html_e( 'Minimum Goal', 'wc-team-payroll' ); ?></label>
						<div class="wc-tp-input-with-icon">
							<span class="wc-tp-currency-icon"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
							<input type="number" 
								   name="earnings_minimum" 
								   value="<?php echo esc_attr( isset( $goals['earnings']['minimum'] ) ? $goals['earnings']['minimum'] : 1000 ); ?>" 
								   step="0.01" 
								   min="0"
								   class="wc-tp-goal-input" />
						</div>
						<span class="wc-tp-goal-badge wc-tp-badge-minimum"><?php esc_html_e( 'Baseline', 'wc-team-payroll' ); ?></span>
					</div>
					
					<div class="wc-tp-goal-input-group">
						<label><?php esc_html_e( 'Target Goal', 'wc-team-payroll' ); ?></label>
						<div class="wc-tp-input-with-icon">
							<span class="wc-tp-currency-icon"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
							<input type="number" 
								   name="earnings_target" 
								   value="<?php echo esc_attr( isset( $goals['earnings']['target'] ) ? $goals['earnings']['target'] : 5000 ); ?>" 
								   step="0.01" 
								   min="0"
								   class="wc-tp-goal-input" />
						</div>
						<span class="wc-tp-goal-badge wc-tp-badge-target"><?php esc_html_e( 'Expected', 'wc-team-payroll' ); ?></span>
					</div>
					
					<div class="wc-tp-goal-input-group">
						<label><?php esc_html_e( 'Stretch Goal', 'wc-team-payroll' ); ?></label>
						<div class="wc-tp-input-with-icon">
							<span class="wc-tp-currency-icon"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
							<input type="number" 
								   name="earnings_stretch" 
								   value="<?php echo esc_attr( isset( $goals['earnings']['stretch'] ) ? $goals['earnings']['stretch'] : 10000 ); ?>" 
								   step="0.01" 
								   min="0"
								   class="wc-tp-goal-input" />
						</div>
						<span class="wc-tp-goal-badge wc-tp-badge-stretch"><?php esc_html_e( 'Excellence', 'wc-team-payroll' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Orders Goals -->
			<div class="wc-tp-goal-config">
				<h5>
					<span class="dashicons dashicons-cart"></span>
					<?php esc_html_e( 'Orders Goals', 'wc-team-payroll' ); ?>
				</h5>
				<p class="description"><?php esc_html_e( 'Set order count targets for this role.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-goal-inputs">
					<div class="wc-tp-goal-input-group">
						<label><?php esc_html_e( 'Minimum Goal', 'wc-team-payroll' ); ?></label>
						<input type="number" 
							   name="orders_minimum" 
							   value="<?php echo esc_attr( isset( $goals['orders']['minimum'] ) ? $goals['orders']['minimum'] : 10 ); ?>" 
							   step="1" 
							   min="0"
							   class="wc-tp-goal-input" />
						<span class="wc-tp-goal-badge wc-tp-badge-minimum"><?php esc_html_e( 'Baseline', 'wc-team-payroll' ); ?></span>
					</div>
					
					<div class="wc-tp-goal-input-group">
						<label><?php esc_html_e( 'Target Goal', 'wc-team-payroll' ); ?></label>
						<input type="number" 
							   name="orders_target" 
							   value="<?php echo esc_attr( isset( $goals['orders']['target'] ) ? $goals['orders']['target'] : 30 ); ?>" 
							   step="1" 
							   min="0"
							   class="wc-tp-goal-input" />
						<span class="wc-tp-goal-badge wc-tp-badge-target"><?php esc_html_e( 'Expected', 'wc-team-payroll' ); ?></span>
					</div>
					
					<div class="wc-tp-goal-input-group">
						<label><?php esc_html_e( 'Stretch Goal', 'wc-team-payroll' ); ?></label>
						<input type="number" 
							   name="orders_stretch" 
							   value="<?php echo esc_attr( isset( $goals['orders']['stretch'] ) ? $goals['orders']['stretch'] : 50 ); ?>" 
							   step="1" 
							   min="0"
							   class="wc-tp-goal-input" />
						<span class="wc-tp-goal-badge wc-tp-badge-stretch"><?php esc_html_e( 'Excellence', 'wc-team-payroll' ); ?></span>
					</div>
				</div>
			</div>

			<!-- AOV Goals -->
			<div class="wc-tp-goal-config">
				<h5>
					<span class="dashicons dashicons-chart-line"></span>
					<?php esc_html_e( 'Average Order Value (AOV) Goals', 'wc-team-payroll' ); ?>
				</h5>
				<p class="description"><?php esc_html_e( 'Set average order value targets for this role.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-goal-inputs">
					<div class="wc-tp-goal-input-group">
						<label><?php esc_html_e( 'Minimum Goal', 'wc-team-payroll' ); ?></label>
						<div class="wc-tp-input-with-icon">
							<span class="wc-tp-currency-icon"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
							<input type="number" 
								   name="aov_minimum" 
								   value="<?php echo esc_attr( isset( $goals['aov']['minimum'] ) ? $goals['aov']['minimum'] : 100 ); ?>" 
								   step="0.01" 
								   min="0"
								   class="wc-tp-goal-input" />
						</div>
						<span class="wc-tp-goal-badge wc-tp-badge-minimum"><?php esc_html_e( 'Baseline', 'wc-team-payroll' ); ?></span>
					</div>
					
					<div class="wc-tp-goal-input-group">
						<label><?php esc_html_e( 'Target Goal', 'wc-team-payroll' ); ?></label>
						<div class="wc-tp-input-with-icon">
							<span class="wc-tp-currency-icon"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
							<input type="number" 
								   name="aov_target" 
								   value="<?php echo esc_attr( isset( $goals['aov']['target'] ) ? $goals['aov']['target'] : 250 ); ?>" 
								   step="0.01" 
								   min="0"
								   class="wc-tp-goal-input" />
						</div>
						<span class="wc-tp-goal-badge wc-tp-badge-target"><?php esc_html_e( 'Expected', 'wc-team-payroll' ); ?></span>
					</div>
					
					<div class="wc-tp-goal-input-group">
						<label><?php esc_html_e( 'Stretch Goal', 'wc-team-payroll' ); ?></label>
						<div class="wc-tp-input-with-icon">
							<span class="wc-tp-currency-icon"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
							<input type="number" 
								   name="aov_stretch" 
								   value="<?php echo esc_attr( isset( $goals['aov']['stretch'] ) ? $goals['aov']['stretch'] : 500 ); ?>" 
								   step="0.01" 
								   min="0"
								   class="wc-tp-goal-input" />
						</div>
						<span class="wc-tp-goal-badge wc-tp-badge-stretch"><?php esc_html_e( 'Excellence', 'wc-team-payroll' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Goal Progress Preview -->
			<div class="wc-tp-goal-config wc-tp-goal-preview-section">
				<h5>
					<span class="dashicons dashicons-visibility"></span>
					<?php esc_html_e( 'Goal Progress Preview', 'wc-team-payroll' ); ?>
				</h5>
				<p class="description"><?php esc_html_e( 'See how goal progress will be displayed to employees on the reports page.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-goal-preview-inputs">
					<div class="wc-tp-preview-input-group">
						<label><?php esc_html_e( 'Current Earnings:', 'wc-team-payroll' ); ?></label>
						<input type="number" class="wc-tp-preview-goal-earnings" placeholder="3500" step="0.01" />
					</div>
					<div class="wc-tp-preview-input-group">
						<label><?php esc_html_e( 'Current Orders:', 'wc-team-payroll' ); ?></label>
						<input type="number" class="wc-tp-preview-goal-orders" placeholder="25" step="1" />
					</div>
					<div class="wc-tp-preview-input-group">
						<label><?php esc_html_e( 'Current AOV:', 'wc-team-payroll' ); ?></label>
						<input type="number" class="wc-tp-preview-goal-aov" placeholder="200" step="0.01" />
					</div>
					<button type="button" class="button button-secondary wc-tp-preview-goals">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'Preview Progress', 'wc-team-payroll' ); ?>
					</button>
				</div>
				
				<div class="wc-tp-goal-preview-result" style="display: none;">
					<div class="wc-tp-goal-preview-item">
						<h6><?php esc_html_e( 'Earnings Progress', 'wc-team-payroll' ); ?></h6>
						<div class="wc-tp-goal-progress-bar">
							<div class="wc-tp-goal-progress-fill" id="preview-earnings-progress" style="width: 0%"></div>
						</div>
						<div class="wc-tp-goal-progress-labels">
							<span class="wc-tp-goal-current" id="preview-earnings-current">$0</span>
							<span class="wc-tp-goal-target" id="preview-earnings-target">/ $0</span>
							<span class="wc-tp-goal-percentage" id="preview-earnings-percentage">0%</span>
						</div>
					</div>
					
					<div class="wc-tp-goal-preview-item">
						<h6><?php esc_html_e( 'Orders Progress', 'wc-team-payroll' ); ?></h6>
						<div class="wc-tp-goal-progress-bar">
							<div class="wc-tp-goal-progress-fill" id="preview-orders-progress" style="width: 0%"></div>
						</div>
						<div class="wc-tp-goal-progress-labels">
							<span class="wc-tp-goal-current" id="preview-orders-current">0</span>
							<span class="wc-tp-goal-target" id="preview-orders-target">/ 0</span>
							<span class="wc-tp-goal-percentage" id="preview-orders-percentage">0%</span>
						</div>
					</div>
					
					<div class="wc-tp-goal-preview-item">
						<h6><?php esc_html_e( 'AOV Progress', 'wc-team-payroll' ); ?></h6>
						<div class="wc-tp-goal-progress-bar">
							<div class="wc-tp-goal-progress-fill" id="preview-aov-progress" style="width: 0%"></div>
						</div>
						<div class="wc-tp-goal-progress-labels">
							<span class="wc-tp-goal-current" id="preview-aov-current">$0</span>
							<span class="wc-tp-goal-target" id="preview-aov-target">/ $0</span>
							<span class="wc-tp-goal-percentage" id="preview-aov-percentage">0%</span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Sanitize role goals
	 */
	private function sanitize_role_goals( $goals ) {
		$sanitized = array();
		
		// Sanitize earnings goals
		if ( isset( $goals['earnings'] ) && is_array( $goals['earnings'] ) ) {
			$sanitized['earnings'] = array(
				'minimum' => floatval( $goals['earnings']['minimum'] ),
				'target' => floatval( $goals['earnings']['target'] ),
				'stretch' => floatval( $goals['earnings']['stretch'] ),
			);
		}
		
		// Sanitize orders goals
		if ( isset( $goals['orders'] ) && is_array( $goals['orders'] ) ) {
			$sanitized['orders'] = array(
				'minimum' => intval( $goals['orders']['minimum'] ),
				'target' => intval( $goals['orders']['target'] ),
				'stretch' => intval( $goals['orders']['stretch'] ),
			);
		}
		
		// Sanitize AOV goals
		if ( isset( $goals['aov'] ) && is_array( $goals['aov'] ) ) {
			$sanitized['aov'] = array(
				'minimum' => floatval( $goals['aov']['minimum'] ),
				'target' => floatval( $goals['aov']['target'] ),
				'stretch' => floatval( $goals['aov']['stretch'] ),
			);
		}
		
		return $sanitized;
	}

	/**
	 * Render role achievements configuration form
	 */
	private function render_role_achievements_form( $role, $achievements ) {
		?>
		<div class="wc-tp-role-achievements-form" data-role="<?php echo esc_attr( $role ); ?>">
			<h4><?php echo esc_html( sprintf( __( 'Configuring Achievements: %s', 'wc-team-payroll' ), $role ) ); ?></h4>
			<p class="description"><?php esc_html_e( 'Configure achievement badges for this role. Each achievement has three tiers: Bronze, Silver, and Gold. Employees earn badges when they reach the specified thresholds.', 'wc-team-payroll' ); ?></p>
			
			<!-- Earnings Achievements -->
			<div class="wc-tp-achievement-category">
				<h5>
					<span class="dashicons dashicons-money-alt"></span>
					<?php esc_html_e( 'Earnings Achievements', 'wc-team-payroll' ); ?>
				</h5>
				<p class="description"><?php esc_html_e( 'Set earnings thresholds for Bronze, Silver, and Gold badges.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-achievement-tiers">
					<?php
					$tiers = array( 'bronze', 'silver', 'gold' );
					foreach ( $tiers as $tier ) {
						$key = 'earnings_' . $tier;
						$achievement = isset( $achievements[ $key ] ) ? $achievements[ $key ] : array();
						$this->render_achievement_card( 'earnings', $tier, $achievement );
					}
					?>
				</div>
			</div>

			<!-- Orders Achievements -->
			<div class="wc-tp-achievement-category">
				<h5>
					<span class="dashicons dashicons-cart"></span>
					<?php esc_html_e( 'Orders Achievements', 'wc-team-payroll' ); ?>
				</h5>
				<p class="description"><?php esc_html_e( 'Set order count thresholds for Bronze, Silver, and Gold badges.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-achievement-tiers">
					<?php
					foreach ( $tiers as $tier ) {
						$key = 'orders_' . $tier;
						$achievement = isset( $achievements[ $key ] ) ? $achievements[ $key ] : array();
						$this->render_achievement_card( 'orders', $tier, $achievement );
					}
					?>
				</div>
			</div>

			<!-- AOV Achievements -->
			<div class="wc-tp-achievement-category">
				<h5>
					<span class="dashicons dashicons-chart-line"></span>
					<?php esc_html_e( 'Average Order Value (AOV) Achievements', 'wc-team-payroll' ); ?>
				</h5>
				<p class="description"><?php esc_html_e( 'Set AOV thresholds for Bronze, Silver, and Gold badges.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-achievement-tiers">
					<?php
					foreach ( $tiers as $tier ) {
						$key = 'aov_' . $tier;
						$achievement = isset( $achievements[ $key ] ) ? $achievements[ $key ] : array();
						$this->render_achievement_card( 'aov', $tier, $achievement );
					}
					?>
				</div>
			</div>

			<!-- Achievement Preview -->
			<div class="wc-tp-achievement-preview-section">
				<h5>
					<span class="dashicons dashicons-visibility"></span>
					<?php esc_html_e( 'Achievement Preview', 'wc-team-payroll' ); ?>
				</h5>
				<p class="description"><?php esc_html_e( 'See how achievements will be displayed to employees on the reports page.', 'wc-team-payroll' ); ?></p>
				
				<div class="wc-tp-achievement-preview-grid">
					<div class="wc-tp-achievement-badge wc-tp-badge-bronze wc-tp-badge-earned">
						<div class="wc-tp-badge-icon">
							<span class="dashicons dashicons-awards"></span>
						</div>
						<div class="wc-tp-badge-info">
							<h6><?php esc_html_e( 'Bronze Badge', 'wc-team-payroll' ); ?></h6>
							<p><?php esc_html_e( 'Earned', 'wc-team-payroll' ); ?></p>
						</div>
					</div>
					
					<div class="wc-tp-achievement-badge wc-tp-badge-silver wc-tp-badge-earned">
						<div class="wc-tp-badge-icon">
							<span class="dashicons dashicons-awards"></span>
						</div>
						<div class="wc-tp-badge-info">
							<h6><?php esc_html_e( 'Silver Badge', 'wc-team-payroll' ); ?></h6>
							<p><?php esc_html_e( 'Earned', 'wc-team-payroll' ); ?></p>
						</div>
					</div>
					
					<div class="wc-tp-achievement-badge wc-tp-badge-gold wc-tp-badge-locked">
						<div class="wc-tp-badge-icon">
							<span class="dashicons dashicons-lock"></span>
						</div>
						<div class="wc-tp-badge-info">
							<h6><?php esc_html_e( 'Gold Badge', 'wc-team-payroll' ); ?></h6>
							<p><?php esc_html_e( 'Locked', 'wc-team-payroll' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render achievement card
	 */
	private function render_achievement_card( $category, $tier, $achievement ) {
		$name = isset( $achievement['name'] ) ? $achievement['name'] : ucfirst( $category ) . ' ' . ucfirst( $tier );
		$description = isset( $achievement['description'] ) ? $achievement['description'] : '';
		$threshold = isset( $achievement['threshold'] ) ? $achievement['threshold'] : 0;
		$icon = isset( $achievement['icon'] ) ? $achievement['icon'] : 'awards';
		
		$tier_labels = array(
			'bronze' => __( 'Bronze Tier', 'wc-team-payroll' ),
			'silver' => __( 'Silver Tier', 'wc-team-payroll' ),
			'gold' => __( 'Gold Tier', 'wc-team-payroll' ),
		);
		
		$currency_prefix = ( $category === 'earnings' || $category === 'aov' ) ? '$' : '';
		$step = ( $category === 'orders' ) ? '1' : '0.01';
		?>
		<div class="wc-tp-achievement-card wc-tp-tier-<?php echo esc_attr( $tier ); ?>">
			<div class="wc-tp-achievement-header">
				<div class="wc-tp-achievement-badge-icon">
					<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
				</div>
				<span class="wc-tp-tier-label"><?php echo esc_html( $tier_labels[ $tier ] ); ?></span>
			</div>
			
			<div class="wc-tp-achievement-body">
				<div class="wc-tp-achievement-field">
					<label><?php esc_html_e( 'Achievement Name', 'wc-team-payroll' ); ?></label>
					<input type="text" 
						   name="achievement_<?php echo esc_attr( $category ); ?>_<?php echo esc_attr( $tier ); ?>_name" 
						   value="<?php echo esc_attr( $name ); ?>" 
						   placeholder="<?php echo esc_attr( ucfirst( $category ) . ' ' . ucfirst( $tier ) ); ?>"
						   class="wc-tp-achievement-input" />
				</div>
				
				<div class="wc-tp-achievement-field">
					<label><?php esc_html_e( 'Description', 'wc-team-payroll' ); ?></label>
					<textarea name="achievement_<?php echo esc_attr( $category ); ?>_<?php echo esc_attr( $tier ); ?>_description" 
							  rows="2" 
							  placeholder="<?php esc_attr_e( 'Describe what this achievement represents...', 'wc-team-payroll' ); ?>"
							  class="wc-tp-achievement-textarea"><?php echo esc_textarea( $description ); ?></textarea>
				</div>
				
				<div class="wc-tp-achievement-field">
					<label><?php esc_html_e( 'Threshold', 'wc-team-payroll' ); ?></label>
					<div class="wc-tp-threshold-input">
						<?php if ( $currency_prefix ) : ?>
							<span class="wc-tp-threshold-prefix"><?php echo esc_html( $currency_prefix ); ?></span>
						<?php endif; ?>
						<input type="number" 
							   name="achievement_<?php echo esc_attr( $category ); ?>_<?php echo esc_attr( $tier ); ?>_threshold" 
							   value="<?php echo esc_attr( $threshold ); ?>" 
							   step="<?php echo esc_attr( $step ); ?>"
							   min="0"
							   class="wc-tp-achievement-input" />
					</div>
					<p class="description"><?php esc_html_e( 'Value needed to earn this badge', 'wc-team-payroll' ); ?></p>
				</div>
				
				<div class="wc-tp-achievement-field">
					<label><?php esc_html_e( 'Icon', 'wc-team-payroll' ); ?></label>
					<select name="achievement_<?php echo esc_attr( $category ); ?>_<?php echo esc_attr( $tier ); ?>_icon" class="wc-tp-achievement-select">
						<?php
						$icons = array(
							'awards' => __( 'Trophy', 'wc-team-payroll' ),
							'star-filled' => __( 'Star', 'wc-team-payroll' ),
							'money-alt' => __( 'Money', 'wc-team-payroll' ),
							'cart' => __( 'Cart', 'wc-team-payroll' ),
							'chart-line' => __( 'Chart', 'wc-team-payroll' ),
							'thumbs-up' => __( 'Thumbs Up', 'wc-team-payroll' ),
							'heart' => __( 'Heart', 'wc-team-payroll' ),
							'flag' => __( 'Flag', 'wc-team-payroll' ),
						);
						foreach ( $icons as $icon_value => $icon_label ) {
							echo '<option value="' . esc_attr( $icon_value ) . '"' . selected( $icon, $icon_value, false ) . '>' . esc_html( $icon_label ) . '</option>';
						}
						?>
					</select>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Sanitize role achievements
	 */
	private function sanitize_role_achievements( $achievements ) {
		$sanitized = array();
		
		$categories = array( 'earnings', 'orders', 'aov' );
		$tiers = array( 'bronze', 'silver', 'gold' );
		
		foreach ( $categories as $category ) {
			foreach ( $tiers as $tier ) {
				$key = $category . '_' . $tier;
				
				if ( isset( $achievements[ $key ] ) && is_array( $achievements[ $key ] ) ) {
					$sanitized[ $key ] = array(
						'name' => sanitize_text_field( $achievements[ $key ]['name'] ),
						'description' => sanitize_textarea_field( $achievements[ $key ]['description'] ),
						'threshold' => ( $category === 'orders' ) ? intval( $achievements[ $key ]['threshold'] ) : floatval( $achievements[ $key ]['threshold'] ),
						'tier' => $tier,
						'icon' => sanitize_text_field( $achievements[ $key ]['icon'] ),
					);
				}
			}
		}
		
		return $sanitized;
	}

	/**
	 * AJAX: Save bonus configuration (Phase 2 Part 2)
	 */
	public function ajax_save_bonus_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$bonus_config = isset( $_POST['bonus_config'] ) ? $_POST['bonus_config'] : array();

		// Get current bonus config to preserve rule IDs
		$current_config = get_option( 'wc_tp_achievement_bonuses', array() );
		$next_rule_id = 1;
		
		// Find the highest existing rule_id to continue from
		if ( isset( $current_config['rules'] ) && is_array( $current_config['rules'] ) ) {
			foreach ( $current_config['rules'] as $rule ) {
				if ( isset( $rule['rule_id'] ) && is_numeric( $rule['rule_id'] ) ) {
					$next_rule_id = max( $next_rule_id, intval( $rule['rule_id'] ) + 1 );
				}
			}
		}

		// Sanitize bonus configuration
		$sanitized_config = array(
			'enabled' => isset( $bonus_config['enabled'] ) ? 1 : 0,
			'notification' => isset( $bonus_config['notification'] ) ? 1 : 0,
			'show_progress' => isset( $bonus_config['show_progress'] ) ? 1 : 0,
			'rules' => array(),
			'last_updated' => current_time( 'mysql' ),
			'next_rule_id' => $next_rule_id, // Track next ID to use
		);

		// Sanitize bonus rules
		if ( isset( $bonus_config['rules'] ) && is_array( $bonus_config['rules'] ) ) {
			foreach ( $bonus_config['rules'] as $rule ) {
				// Skip empty rules
				if ( empty( $rule['tier'] ) || empty( $rule['streak_count'] ) || empty( $rule['bonus_description'] ) ) {
					continue;
				}

				// Preserve existing rule_id or assign new one
				$rule_id = isset( $rule['rule_id'] ) && is_numeric( $rule['rule_id'] ) ? intval( $rule['rule_id'] ) : $next_rule_id;
				if ( ! isset( $rule['rule_id'] ) || ! is_numeric( $rule['rule_id'] ) ) {
					$next_rule_id++;
				}

				$sanitized_rule = array(
					'rule_id' => $rule_id, // Serial ID for this bonus rule
					'tier' => sanitize_text_field( $rule['tier'] ),
					'streak_count' => intval( $rule['streak_count'] ),
					'bonus_type' => sanitize_text_field( $rule['bonus_type'] ),
					'bonus_amount' => floatval( $rule['bonus_amount'] ),
					'bonus_description' => sanitize_text_field( $rule['bonus_description'] ),
					'repeatable' => isset( $rule['repeatable'] ) && $rule['repeatable'] ? 1 : 0,
					'eligible_roles' => array(),
				);

				// Sanitize eligible roles
				if ( isset( $rule['eligible_roles'] ) && is_array( $rule['eligible_roles'] ) ) {
					foreach ( $rule['eligible_roles'] as $role ) {
						$sanitized_rule['eligible_roles'][] = sanitize_text_field( $role );
					}
				}

				$sanitized_config['rules'][] = $sanitized_rule;
			}
		}

		// Update next_rule_id for next save
		$sanitized_config['next_rule_id'] = $next_rule_id;

		// Save to database
		update_option( 'wc_tp_achievement_bonuses', $sanitized_config );

		// Clear all related caches
		wp_cache_delete( 'wc_tp_achievement_bonuses', 'options' );
		delete_transient( 'wc_tp_bonus_milestones_' . get_current_user_id() );
		
		// Clear cache for all users (bonus config affects all employees)
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%wc_tp_bonus_milestones_%'" );

		wp_send_json_success( array( 
			'message' => __( 'Bonus configuration saved successfully!', 'wc-team-payroll' ),
			'config' => $sanitized_config,
			'timestamp' => current_time( 'mysql' )
		) );
	}

	/**
	 * AJAX: Get bonus configuration (Phase 2 Part 2)
	 */
	public function ajax_get_bonus_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$bonus_config = get_option( 'wc_tp_achievement_bonuses', array() );

		wp_send_json_success( array( 
			'config' => $bonus_config
		) );
	}

	/**
	 * AJAX: Save leaderboard configuration
	 */
	public function ajax_save_leaderboard_config() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		$leaderboard_config = isset( $_POST['leaderboard_config'] ) ? $_POST['leaderboard_config'] : array();

		// Sanitize leaderboard configuration
		// For checkboxes: check if key exists in the posted config array, not if it's set to 1
		$sanitized_config = array(
			'enabled' => isset( $leaderboard_config['enabled'] ) && $leaderboard_config['enabled'] == 1 ? 1 : 0,
			'criteria' => isset( $leaderboard_config['criteria'] ) ? sanitize_text_field( $leaderboard_config['criteria'] ) : 'total_earnings',
			'period' => isset( $leaderboard_config['period'] ) ? sanitize_text_field( $leaderboard_config['period'] ) : 'current_month',
			'update_frequency' => isset( $leaderboard_config['update_frequency'] ) ? sanitize_text_field( $leaderboard_config['update_frequency'] ) : 'daily',
			'minimum_orders' => isset( $leaderboard_config['minimum_orders'] ) ? intval( $leaderboard_config['minimum_orders'] ) : 0,
			'exclude_inactive' => isset( $leaderboard_config['exclude_inactive'] ) && $leaderboard_config['exclude_inactive'] == 1 ? 1 : 0,
			'display_limit' => isset( $leaderboard_config['display_limit'] ) ? intval( $leaderboard_config['display_limit'] ) : 10,
			'show_user_rank' => isset( $leaderboard_config['show_user_rank'] ) && $leaderboard_config['show_user_rank'] == 1 ? 1 : 0,
			'anonymize' => isset( $leaderboard_config['anonymize'] ) && $leaderboard_config['anonymize'] == 1 ? 1 : 0,
			'show_scores' => isset( $leaderboard_config['show_scores'] ) && $leaderboard_config['show_scores'] == 1 ? 1 : 0,
			'show_metrics' => isset( $leaderboard_config['show_metrics'] ) && $leaderboard_config['show_metrics'] == 1 ? 1 : 0,
			'last_updated' => current_time( 'mysql' ),
		);

		// Save to database
		update_option( 'wc_tp_leaderboard_config', $sanitized_config );

		// Clear leaderboard cache
		wp_cache_delete( 'wc_tp_leaderboard_config', 'options' );
		delete_transient( 'wc_tp_leaderboard_data' );

		wp_send_json_success( array( 
			'message' => __( 'Leaderboard configuration saved successfully!', 'wc-team-payroll' ),
			'config' => $sanitized_config,
			'timestamp' => current_time( 'mysql' )
		) );
	}

	/**
	 * AJAX: Refresh leaderboard rankings manually
	 */
	public function ajax_refresh_leaderboard() {
		check_ajax_referer( 'wc_tp_performance_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wc-team-payroll' ) ) );
		}

		// Get leaderboard configuration
		$config = get_option( 'wc_tp_leaderboard_config', array() );
		
		if ( empty( $config ) || ! isset( $config['enabled'] ) || ! $config['enabled'] ) {
			wp_send_json_error( array( 'message' => __( 'Leaderboard is not enabled', 'wc-team-payroll' ) ) );
		}

		// Initialize leaderboard engine
		$engine = new WC_Team_Payroll_Leaderboard_Engine();
		
		// Clear all leaderboard caches before regenerating
		$engine->clear_cache();
		
		// Generate leaderboard
		$result = $engine->generate_leaderboard( $config );
		
		if ( isset( $result['error'] ) && $result['error'] ) {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}

		// Update last updated timestamp
		$config['last_updated'] = current_time( 'mysql' );
		update_option( 'wc_tp_leaderboard_config', $config );

		// Clear cache
		wp_cache_delete( 'wc_tp_leaderboard_config', 'options' );

		wp_send_json_success( array( 
			'message' => sprintf( 
				__( 'Leaderboard refreshed successfully! %d employees ranked.', 'wc-team-payroll' ),
				isset( $result['total_employees'] ) ? $result['total_employees'] : 0
			),
			'timestamp' => current_time( 'mysql' ),
			'total_employees' => isset( $result['total_employees'] ) ? $result['total_employees'] : 0,
			'period' => isset( $result['date_range']['period_id'] ) ? $result['date_range']['period_id'] : '',
		) );
	}

}

// Note: Class is instantiated in the main plugin file
