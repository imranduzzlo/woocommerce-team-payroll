<?php
/**
 * Employee Detail Page
 */

class WC_Team_Payroll_Employee_Detail {

	public function render_employee_detail() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$user_id = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_die( esc_html__( 'Invalid user ID', 'wc-team-payroll' ) );
		}

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			wp_die( esc_html__( 'User not found', 'wc-team-payroll' ) );
		}

		// Get user meta
		$profile_picture_id = get_user_meta( $user_id, '_wc_tp_profile_picture', true );
		$profile_picture = '';
		if ( $profile_picture_id ) {
			$profile_picture = wp_get_attachment_url( $profile_picture_id );
		}
		$phone = get_user_meta( $user_id, 'billing_phone', true );
		$bio = get_user_meta( $user_id, 'description', true );
		$vb_user_id = get_user_meta( $user_id, 'vb_user_id', true );
		$employee_status = get_user_meta( $user_id, '_wc_tp_employee_status', true );
		if ( ! $employee_status ) {
			$employee_status = 'active'; // Default to active
		}

		// Get stats
		$core_engine = new WC_Team_Payroll_Core_Engine();
		$total_orders = $core_engine->get_user_total_orders( $user_id );
		$total_earnings = $core_engine->get_user_total_earnings( $user_id );
		$total_paid = $core_engine->get_user_total_paid( $user_id );
		$total_due = $total_earnings - $total_paid;

		// Add styles
		$this->add_styles();
		// Add scripts
		$this->add_scripts();

		?>
		<div class="wrap wc-tp-employee-detail">
			<div class="wc-tp-page-header">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-team-payroll-employees' ) ); ?>" class="wc-tp-back-button">
					<span class="dashicons dashicons-arrow-left"></span>
					<?php esc_html_e( 'Back to Employees', 'wc-team-payroll' ); ?>
				</a>
				<h1><?php esc_html_e( 'Employee Details', 'wc-team-payroll' ); ?></h1>
			</div>

			<!-- Profile Section -->
			<div class="wc-tp-profile-section">
				<div class="wc-tp-profile-header">
					<div class="wc-tp-profile-left">
						<div class="wc-tp-profile-picture">
							<?php if ( $profile_picture ) : ?>
								<img src="<?php echo esc_url( $profile_picture ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>" />
							<?php else : ?>
								<div class="wc-tp-profile-placeholder">
									<span class="dashicons dashicons-admin-users"></span>
								</div>
							<?php endif; ?>
							
							<?php
							// Get monthly achievement for badge
							$monthly_achievements = get_user_meta( $user_id, '_wc_tp_monthly_achievements', true );
							$highest_tier = '';
							
							if ( ! empty( $monthly_achievements ) && isset( $monthly_achievements['highest_tier'] ) ) {
								$highest_tier = $monthly_achievements['highest_tier'];
							} else {
								// Fallback to old system if monthly not available yet
								$achievement_stats = get_user_meta( $user_id, '_wc_tp_achievement_stats', true );
								if ( ! empty( $achievement_stats ) ) {
									$gold_count = isset( $achievement_stats['gold_count'] ) ? intval( $achievement_stats['gold_count'] ) : 0;
									$silver_count = isset( $achievement_stats['silver_count'] ) ? intval( $achievement_stats['silver_count'] ) : 0;
									$bronze_count = isset( $achievement_stats['bronze_count'] ) ? intval( $achievement_stats['bronze_count'] ) : 0;
									
									if ( $gold_count > 0 ) {
										$highest_tier = 'gold';
									} elseif ( $silver_count > 0 ) {
										$highest_tier = 'silver';
									} elseif ( $bronze_count > 0 ) {
										$highest_tier = 'bronze';
									}
								}
							}
							
							// Get goal achievement count
							$goal_progress = get_user_meta( $user_id, '_wc_tp_current_goal_progress', true );
							$goals_achieved = 0;
							$total_goals = 0;
							
							if ( ! empty( $goal_progress ) ) {
								$metrics = array( 'order_value', 'orders', 'aov' );
								foreach ( $metrics as $metric ) {
									if ( isset( $goal_progress[ $metric ] ) ) {
										$total_goals++;
										$status = isset( $goal_progress[ $metric ]['status'] ) ? $goal_progress[ $metric ]['status'] : '';
										if ( in_array( $status, array( 'achieved', 'stretch_achieved' ) ) ) {
											$goals_achieved++;
										}
									}
								}
							}
							?>
							
							<?php if ( ! empty( $highest_tier ) ) : ?>
								<div class="profile-achievement-badge profile-achievement-badge-<?php echo esc_attr( $highest_tier ); ?>">
									<svg class="badge-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
										<defs>
											<!-- Gold Gradients -->
											<radialGradient id="goldGradient" cx="50%" cy="50%">
												<stop offset="0%" style="stop-color:#FFF9E6"/>
												<stop offset="40%" style="stop-color:#FFD700"/>
												<stop offset="100%" style="stop-color:#DAA520"/>
											</radialGradient>
											
											<!-- Silver Gradients -->
											<radialGradient id="silverGradient" cx="50%" cy="50%">
												<stop offset="0%" style="stop-color:#FFFFFF"/>
												<stop offset="40%" style="stop-color:#E0E0E0"/>
												<stop offset="100%" style="stop-color:#B0B0B0"/>
											</radialGradient>
											
											<!-- Bronze Gradients -->
											<radialGradient id="bronzeGradient" cx="50%" cy="50%">
												<stop offset="0%" style="stop-color:#FFE4C4"/>
												<stop offset="40%" style="stop-color:#CD7F32"/>
												<stop offset="100%" style="stop-color:#8B4513"/>
											</radialGradient>
										</defs>
										
										<!-- Main Circle with Gradient -->
										<circle cx="50" cy="50" r="45" class="badge-circle"/>
										
										<!-- Inner Ring for Depth -->
										<circle cx="50" cy="50" r="35" class="badge-inner-ring"/>
										
										<!-- Crown Icon (Outline Only) -->
										<g class="badge-icon-outline">
											<?php if ( $highest_tier === 'gold' ) : ?>
												<!-- King Crown (Outline) -->
												<path d="M 30 55 L 33 42 L 40 48 L 50 38 L 60 48 L 67 42 L 70 55 Z" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
												<rect x="30" y="55" width="40" height="8" rx="2" fill="none" stroke-width="3"/>
												<circle cx="40" cy="42" r="3" fill="none" stroke-width="2"/>
												<circle cx="50" cy="38" r="3" fill="none" stroke-width="2"/>
												<circle cx="60" cy="42" r="3" fill="none" stroke-width="2"/>
											<?php elseif ( $highest_tier === 'silver' ) : ?>
												<!-- Medal Star (Outline) -->
												<path d="M 50 35 L 53 45 L 63 46 L 56 53 L 58 63 L 50 58 L 42 63 L 44 53 L 37 46 L 47 45 Z" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
												<circle cx="50" cy="50" r="4" fill="none" stroke-width="2"/>
											<?php else : ?>
												<!-- Trophy (Outline) -->
												<path d="M 40 42 L 40 52 L 44 58 L 56 58 L 60 52 L 60 42 Z" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
												<path d="M 35 42 L 35 47 C 35 50 37 52 40 52" fill="none" stroke-width="2.5" stroke-linecap="round"/>
												<path d="M 65 42 L 65 47 C 65 50 63 52 60 52" fill="none" stroke-width="2.5" stroke-linecap="round"/>
												<rect x="45" y="58" width="10" height="5" fill="none" stroke-width="2.5"/>
												<rect x="40" y="63" width="20" height="4" rx="2" fill="none" stroke-width="2.5"/>
											<?php endif; ?>
										</g>
									</svg>
								</div>
							<?php else : ?>
								<!-- Locked Badge -->
								<div class="profile-achievement-badge profile-achievement-badge-locked">
									<svg class="badge-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
										<defs>
											<radialGradient id="lockedGradient" cx="50%" cy="50%">
												<stop offset="0%" style="stop-color:#F0F0F0"/>
												<stop offset="40%" style="stop-color:#D0D0D0"/>
												<stop offset="100%" style="stop-color:#A0A0A0"/>
											</radialGradient>
										</defs>
										
										<circle cx="50" cy="50" r="45" class="badge-circle"/>
										<circle cx="50" cy="50" r="35" class="badge-inner-ring"/>
										
										<!-- Lock Icon (Outline) -->
										<g class="badge-icon-outline">
											<rect x="42" y="50" width="16" height="14" rx="2" fill="none" stroke-width="3"/>
											<path d="M 44 50 L 44 44 C 44 40 46.7 37 50 37 C 53.3 37 56 40 56 44 L 56 50" fill="none" stroke-width="3" stroke-linecap="round"/>
											<circle cx="50" cy="57" r="2.5" fill="none" stroke-width="2"/>
										</g>
									</svg>
								</div>
							<?php endif; ?>
							
							<?php if ( $total_goals > 0 ) : ?>
								<div class="profile-goal-counter">
									<svg class="goal-star-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
										<path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
									</svg>
									<span class="goal-count"><?php echo esc_html( $goals_achieved . '/' . $total_goals ); ?></span>
								</div>
							<?php endif; ?>
						</div>
						<div class="wc-tp-profile-info">
							<h2><?php echo esc_html( $user->display_name ); ?></h2>
							<?php if ( $vb_user_id ) : ?>
								<p class="wc-tp-user-id"><strong><?php esc_html_e( 'ID:', 'wc-team-payroll' ); ?></strong> <?php echo esc_html( $vb_user_id ); ?></p>
							<?php endif; ?>
							<p class="wc-tp-email"><strong><?php esc_html_e( 'Email:', 'wc-team-payroll' ); ?></strong> <?php echo esc_html( $user->user_email ); ?></p>
							<?php if ( $phone ) : ?>
								<p class="wc-tp-phone"><strong><?php esc_html_e( 'Phone:', 'wc-team-payroll' ); ?></strong> <?php echo esc_html( $phone ); ?></p>
							<?php endif; ?>
							<?php if ( $bio ) : ?>
								<p class="wc-tp-bio"><strong><?php esc_html_e( 'Bio:', 'wc-team-payroll' ); ?></strong> <?php echo esc_html( $bio ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<div class="wc-tp-profile-right">
						<div class="wc-tp-profile-actions">
							<div class="wc-tp-status-control">
								<label for="wc-tp-employee-status"><?php esc_html_e( 'Status:', 'wc-team-payroll' ); ?></label>
								<select id="wc-tp-employee-status" data-user-id="<?php echo esc_attr( $user_id ); ?>">
									<option value="active" <?php selected( $employee_status, 'active' ); ?>><?php esc_html_e( 'Active', 'wc-team-payroll' ); ?></option>
									<option value="inactive" <?php selected( $employee_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wc-team-payroll' ); ?></option>
								</select>
							</div>
							<a href="<?php echo esc_url( get_edit_user_link( $user_id ) ); ?>" class="button button-primary">
								<span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit Profile', 'wc-team-payroll' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Stats Cards -->
			<div class="wc-tp-stats-grid">
				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">📦</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value"><?php echo esc_html( $total_orders ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Orders', 'wc-team-payroll' ); ?></div>
					</div>
				</div>
				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">💰</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value"><?php echo wp_kses_post( wc_price( $total_earnings ) ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></div>
					</div>
				</div>
				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">✅</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value"><?php echo wp_kses_post( wc_price( $total_paid ) ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Paid', 'wc-team-payroll' ); ?></div>
					</div>
				</div>
				<div class="wc-tp-stat-card">
					<div class="wc-tp-stat-icon">⏳</div>
					<div class="wc-tp-stat-content">
						<div class="wc-tp-stat-value"><?php echo wp_kses_post( wc_price( $total_due ) ); ?></div>
						<div class="wc-tp-stat-label"><?php esc_html_e( 'Total Due', 'wc-team-payroll' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Tabs Navigation -->
			<nav class="nav-tab-wrapper wc-tp-tabs">
				<a href="?page=wc-team-payroll-employee-detail&user_id=<?php echo esc_attr( $user_id ); ?>&tab=orders" class="nav-tab <?php echo ( ! isset( $_GET['tab'] ) || $_GET['tab'] === 'orders' ) ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Orders', 'wc-team-payroll' ); ?>
				</a>
				<a href="?page=wc-team-payroll-employee-detail&user_id=<?php echo esc_attr( $user_id ); ?>&tab=payments" class="nav-tab <?php echo ( isset( $_GET['tab'] ) && $_GET['tab'] === 'payments' ) ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Payments', 'wc-team-payroll' ); ?>
				</a>
				<a href="?page=wc-team-payroll-employee-detail&user_id=<?php echo esc_attr( $user_id ); ?>&tab=salary" class="nav-tab <?php echo ( isset( $_GET['tab'] ) && $_GET['tab'] === 'salary' ) ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Salary Management', 'wc-team-payroll' ); ?>
				</a>
				<a href="?page=wc-team-payroll-employee-detail&user_id=<?php echo esc_attr( $user_id ); ?>&tab=performance" class="nav-tab <?php echo ( isset( $_GET['tab'] ) && $_GET['tab'] === 'performance' ) ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Performance', 'wc-team-payroll' ); ?>
				</a>
			</nav>

			<!-- Tab Content -->
			<div class="wc-tp-tab-content">
				<?php
				$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'orders';

				if ( $current_tab === 'orders' ) {
					$this->render_orders_tab( $user_id );
				} elseif ( $current_tab === 'payments' ) {
					$this->render_payments_tab( $user_id );
				} elseif ( $current_tab === 'salary' ) {
					$this->render_salary_tab( $user_id );
				} elseif ( $current_tab === 'performance' ) {
					$this->render_performance_tab( $user_id );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Orders Tab
	 */
	private function render_orders_tab( $user_id ) {
		?>
		<div class="wc-tp-orders-tab">
			<!-- Unified Filter Section -->
			<div class="wc-tp-unified-filter">
				<div class="wc-tp-filter-row">
					<!-- Date Range Preset -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-orders-date-preset">
							<option value="this-month"><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></option>
							<option value="all-time"><?php esc_html_e( 'All Time', 'wc-team-payroll' ); ?></option>
							<option value="today"><?php esc_html_e( 'Today', 'wc-team-payroll' ); ?></option>
							<option value="this-week"><?php esc_html_e( 'This Week', 'wc-team-payroll' ); ?></option>
							<option value="this-year"><?php esc_html_e( 'This Year', 'wc-team-payroll' ); ?></option>
							<option value="last-week"><?php esc_html_e( 'Last Week', 'wc-team-payroll' ); ?></option>
							<option value="last-month"><?php esc_html_e( 'Last Month', 'wc-team-payroll' ); ?></option>
							<option value="last-year"><?php esc_html_e( 'Last Year', 'wc-team-payroll' ); ?></option>
							<option value="last-6-months"><?php esc_html_e( 'Last 6 Months', 'wc-team-payroll' ); ?></option>
							<option value="custom"><?php esc_html_e( 'Custom', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Custom Date Range (Hidden by default) -->
					<div class="wc-tp-filter-group wc-tp-custom-date-range" id="wc-tp-orders-custom-date-range" style="display: none;">
						<input type="date" id="wc-tp-orders-start-date" />
						<span class="wc-tp-date-separator">to</span>
						<input type="date" id="wc-tp-orders-end-date" />
					</div>

					<!-- Status Filter -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Status:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-orders-status-filter">
							<option value=""><?php esc_html_e( 'All Statuses', 'wc-team-payroll' ); ?></option>
							<option value="completed"><?php esc_html_e( 'Completed', 'wc-team-payroll' ); ?></option>
							<option value="processing"><?php esc_html_e( 'Processing', 'wc-team-payroll' ); ?></option>
							<option value="pending"><?php esc_html_e( 'Pending', 'wc-team-payroll' ); ?></option>
							<option value="cancelled"><?php esc_html_e( 'Cancelled', 'wc-team-payroll' ); ?></option>
							<option value="refunded"><?php esc_html_e( 'Refunded', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Employee Role Filter -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Employee Role:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-orders-role-filter">
							<option value=""><?php esc_html_e( 'All Roles', 'wc-team-payroll' ); ?></option>
							<option value="agent"><?php esc_html_e( 'Agent', 'wc-team-payroll' ); ?></option>
							<option value="processor"><?php esc_html_e( 'Processor', 'wc-team-payroll' ); ?></option>
						</select>
					</div>

					<!-- Search -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Search:', 'wc-team-payroll' ); ?></label>
						<input type="text" id="wc-tp-orders-search" placeholder="<?php esc_attr_e( 'Order ID, Customer...', 'wc-team-payroll' ); ?>" />
					</div>

					<!-- Filter Button -->
					<div class="wc-tp-filter-group">
						<button type="button" class="button button-primary" id="wc-tp-orders-filter-btn"><?php esc_html_e( 'Filter', 'wc-team-payroll' ); ?></button>
					</div>

					<!-- Screen Options -->
					<div class="wc-tp-filter-group">
						<label><?php esc_html_e( 'Per Page:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-orders-per-page">
							<option value="5">5</option>
							<option value="10" selected>10</option>
							<option value="25">25</option>
							<option value="50">50</option>
							<option value="100">100</option>
						</select>
					</div>
				</div>
			</div>

			<!-- Orders Table -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Order History', 'wc-team-payroll' ); ?></h2>
				<div id="wc-tp-orders-table-container">
					<!-- Content will be loaded via AJAX -->
				</div>
			</div>
		</div>

		<input type="hidden" id="wc-tp-current-user-id" value="<?php echo esc_attr( $user_id ); ?>" />
		<?php
		wp_nonce_field( 'wc_team_payroll_nonce', 'wc_team_payroll_nonce' );
	}

	/**
	 * Render Payments Tab
	 */
	private function render_payments_tab( $user_id ) {
		?>
		<div class="wc-tp-payments-tab">
			<!-- Add Payment Form -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></h2>
				<form id="wc-tp-add-payment-form" class="wc-tp-form">
					<div class="wc-tp-form-row">
						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-amount"><?php esc_html_e( 'Amount', 'wc-team-payroll' ); ?></label>
							<input type="number" id="wc-tp-payment-amount" step="0.01" min="0" required />
						</div>
						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-date"><?php esc_html_e( 'Date', 'wc-team-payroll' ); ?></label>
							<input type="datetime-local" id="wc-tp-payment-date" value="<?php echo esc_attr( date( 'Y-m-d\TH:i' ) ); ?>" required />
						</div>
						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-method"><?php esc_html_e( 'Payment Method', 'wc-team-payroll' ); ?></label>
							<select id="wc-tp-payment-method">
								<option value=""><?php esc_html_e( 'Select Method', 'wc-team-payroll' ); ?></option>
								<!-- Will be populated via AJAX -->
							</select>
						</div>
						<div class="wc-tp-form-group">
							<label for="wc-tp-payment-note"><?php esc_html_e( 'Note (Optional)', 'wc-team-payroll' ); ?></label>
							<input type="text" id="wc-tp-payment-note" />
						</div>
						<div class="wc-tp-form-group">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Payment', 'wc-team-payroll' ); ?></button>
						</div>
					</div>
				</form>
			</div>

			<!-- Payment History -->
			<div class="wc-tp-table-section">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
					<h2 style="margin: 0;"><?php esc_html_e( 'Payment History', 'wc-team-payroll' ); ?></h2>
					<div class="wc-tp-filter-group" style="margin: 0; min-width: auto;">
						<label style="margin-right: 8px;"><?php esc_html_e( 'Per Page:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payment-history-per-page" style="min-width: 80px;">
							<option value="5">5</option>
							<option value="10" selected>10</option>
							<option value="25">25</option>
							<option value="50">50</option>
							<option value="100">100</option>
						</select>
					</div>
				</div>
				<div id="wc-tp-payment-history-container">
					<!-- Content will be loaded via AJAX -->
				</div>
			</div>

			<!-- Add Payment Method Form -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Add Payment Method', 'wc-team-payroll' ); ?></h2>
				<form id="wc-tp-add-payment-method-form" class="wc-tp-form">
					<div class="wc-tp-form-row">
						<div class="wc-tp-form-group">
							<label for="wc-tp-method-name"><?php esc_html_e( 'Payment Method', 'wc-team-payroll' ); ?></label>
							<input type="text" id="wc-tp-method-name" placeholder="<?php esc_attr_e( 'e.g., bKash Personal', 'wc-team-payroll' ); ?>" required />
						</div>
						<div class="wc-tp-form-group">
							<label for="wc-tp-method-account"><?php esc_html_e( 'Account/Details', 'wc-team-payroll' ); ?></label>
							<input type="text" id="wc-tp-method-account" placeholder="<?php esc_attr_e( 'e.g., 01712345678', 'wc-team-payroll' ); ?>" required />
						</div>
						<div class="wc-tp-form-group">
							<label for="wc-tp-method-note"><?php esc_html_e( 'Note (Optional)', 'wc-team-payroll' ); ?></label>
							<input type="text" id="wc-tp-method-note" />
						</div>
						<div class="wc-tp-form-group">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Method', 'wc-team-payroll' ); ?></button>
						</div>
					</div>
				</form>
			</div>

			<!-- Payment Methods List -->
			<div class="wc-tp-table-section">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
					<h2 style="margin: 0;"><?php esc_html_e( 'Payment Methods', 'wc-team-payroll' ); ?></h2>
					<div class="wc-tp-filter-group" style="margin: 0; min-width: auto;">
						<label style="margin-right: 8px;"><?php esc_html_e( 'Per Page:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-payment-methods-per-page" style="min-width: 80px;">
							<option value="5">5</option>
							<option value="10" selected>10</option>
							<option value="25">25</option>
							<option value="50">50</option>
							<option value="100">100</option>
						</select>
					</div>
				</div>
				<div id="wc-tp-payment-methods-container">
					<!-- Content will be loaded via AJAX -->
				</div>
			</div>
		</div>

		<input type="hidden" id="wc-tp-current-user-id" value="<?php echo esc_attr( $user_id ); ?>" />
		<?php
		wp_nonce_field( 'wc_team_payroll_nonce', 'wc_team_payroll_nonce' );
	}

	/**
	 * Render Salary Tab
	 */
	private function render_salary_tab( $user_id ) {
		$is_fixed = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$salary_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true );
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true );

		$salary_type = 'commission';
		if ( $is_fixed ) {
			$salary_type = 'fixed';
		} elseif ( $is_combined ) {
			$salary_type = 'combined';
		}
		?>
		<div class="wc-tp-salary-tab">
			<!-- Salary Management Form -->
			<div class="wc-tp-table-section">
				<h2><?php esc_html_e( 'Salary Management', 'wc-team-payroll' ); ?></h2>
				<form id="wc-tp-salary-form" class="wc-tp-form">
					<div class="wc-tp-form-row">
						<div class="wc-tp-form-group">
							<label for="wc-tp-salary-type"><?php esc_html_e( 'Salary Type', 'wc-team-payroll' ); ?></label>
							<select id="wc-tp-salary-type">
								<option value="commission" <?php selected( $salary_type, 'commission' ); ?>><?php esc_html_e( 'Commission Based', 'wc-team-payroll' ); ?></option>
								<option value="fixed" <?php selected( $salary_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed Salary', 'wc-team-payroll' ); ?></option>
								<option value="combined" <?php selected( $salary_type, 'combined' ); ?>><?php esc_html_e( 'Combined (Base + Commission)', 'wc-team-payroll' ); ?></option>
							</select>
						</div>
						<div class="wc-tp-form-group wc-tp-salary-amount-group" style="<?php echo ( $salary_type === 'commission' ) ? 'display: none;' : ''; ?>">
							<label for="wc-tp-salary-amount"><?php esc_html_e( 'Salary Amount', 'wc-team-payroll' ); ?></label>
							<input type="number" id="wc-tp-salary-amount" step="0.01" min="0" value="<?php echo esc_attr( $salary_amount ); ?>" />
						</div>
						<div class="wc-tp-form-group wc-tp-salary-frequency-group" style="<?php echo ( $salary_type === 'commission' ) ? 'display: none;' : ''; ?>">
							<label for="wc-tp-salary-frequency"><?php esc_html_e( 'Frequency', 'wc-team-payroll' ); ?></label>
							<select id="wc-tp-salary-frequency">
								<option value="monthly" <?php selected( $salary_frequency, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'wc-team-payroll' ); ?></option>
								<option value="weekly" <?php selected( $salary_frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'wc-team-payroll' ); ?></option>
								<option value="daily" <?php selected( $salary_frequency, 'daily' ); ?>><?php esc_html_e( 'Daily', 'wc-team-payroll' ); ?></option>
							</select>
						</div>
						<div class="wc-tp-form-group">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Update Salary', 'wc-team-payroll' ); ?></button>
						</div>
					</div>
				</form>
			</div>

			<!-- Salary History -->
			<div class="wc-tp-table-section">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
					<h2 style="margin: 0;"><?php esc_html_e( 'Salary History', 'wc-team-payroll' ); ?></h2>
					<div class="wc-tp-filter-group" style="margin: 0; min-width: auto;">
						<label style="margin-right: 8px;"><?php esc_html_e( 'Per Page:', 'wc-team-payroll' ); ?></label>
						<select id="wc-tp-salary-history-per-page" style="min-width: 80px;">
							<option value="5">5</option>
							<option value="10" selected>10</option>
							<option value="25">25</option>
							<option value="50">50</option>
							<option value="100">100</option>
						</select>
					</div>
				</div>
				<div id="wc-tp-salary-history-container">
					<!-- Content will be loaded via AJAX -->
				</div>
			</div>
		</div>

		<input type="hidden" id="wc-tp-current-user-id" value="<?php echo esc_attr( $user_id ); ?>" />
		<?php
		wp_nonce_field( 'wc_team_payroll_nonce', 'wc_team_payroll_nonce' );
	}

	/**
	 * Add inline styles
	 */
	public function add_styles() {
		?>
		<style>
			:root {
				--color-primary: #FF9900;
				--color-primary-hover: #E68A00;
				--color-primary-subtle: #FFF4E5;
				--color-secondary: #212B36;
				--color-site-bg: #FDFBF8;
				--color-card-bg: #FFFFFF;
				--color-border-light: #E5EAF0;
				--color-accent-alert: #FF5500;
				--color-accent-link: #0077EE;
				--color-accent-success: #388E3C;
				--color-accent-muted: #F4F4F4;
				--text-main: #212B36;
				--text-body: #454F5B;
				--text-muted: #919EAB;
				--font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
				--fs-h1: 2rem;
				--fs-h2: 1.5rem;
				--fs-body: 1rem;
				--fs-meta: 0.875rem;
				--fs-small: 0.75rem;
				--fw-bold: 700;
				--fw-semibold: 600;
				--fw-medium: 500;
				--lh-body: 1.5;
			}

			.wc-tp-employee-detail {
				background: var(--color-site-bg);
				padding: 24px;
				font-family: var(--font-family);
				color: var(--text-main);
			}

			.wc-tp-employee-detail h1 {
				font-size: var(--fs-h1);
				font-weight: var(--fw-bold);
				color: var(--text-main);
				margin-bottom: 24px;
				margin-top: 0;
			}

			.wc-tp-page-header {
				display: flex;
				align-items: center;
				gap: 16px;
				margin-bottom: 24px;
			}

			.wc-tp-back-button {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				padding: 10px 16px;
				background: var(--color-card-bg);
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				color: var(--color-primary);
				text-decoration: none;
				font-weight: var(--fw-semibold);
				font-size: var(--fs-body);
				transition: all 0.2s ease;
			}

			.wc-tp-back-button:hover {
				background: var(--color-primary-subtle);
				border-color: var(--color-primary);
				color: var(--color-primary-hover);
			}

			.wc-tp-back-button .dashicons {
				font-size: 18px;
				width: 18px;
				height: 18px;
			}

			/* Profile Section */
			.wc-tp-profile-section {
				background: var(--color-card-bg);
				border: 1px solid var(--color-border-light);
				border-radius: 8px;
				padding: 24px;
				margin-bottom: 24px;
			}

			.wc-tp-profile-header {
				display: flex;
				justify-content: space-between;
				align-items: flex-start;
				gap: 20px;
			}

			.wc-tp-profile-left {
				display: flex;
				gap: 20px;
				flex: 1;
			}

			.wc-tp-profile-picture {
				width: 120px;
				height: 120px;
				border-radius: 50%;
				overflow: visible;
				border: 3px solid #ff9900;
				flex-shrink: 0;
				position: relative;
				background: #fff;
				display: flex;
				align-items: center;
				justify-content: center;
			}

			.wc-tp-profile-picture img {
				width: 100%;
				height: 100%;
				object-fit: cover;
				border-radius: 50%;
			}

			.wc-tp-profile-placeholder {
				width: 100%;
				height: 100%;
				background: linear-gradient(135deg, #ff9900 0%, #e68a00 100%);
				display: flex;
				align-items: center;
				justify-content: center;
				border-radius: 50%;
			}

			.wc-tp-profile-placeholder .dashicons {
				font-size: 60px;
				width: 60px;
				height: 60px;
				color: #fff;
			}

			/* Profile Achievement Badge - Top Right */
			.profile-achievement-badge {
				position: absolute;
				top: -8px;
				right: -8px;
				width: 44px;
				height: 44px;
				z-index: 10;
				filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.3));
				transition: all 0.3s ease;
			}

			.profile-achievement-badge:hover {
				filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.4));
				transform: scale(1.08);
			}

			.badge-icon {
				width: 100%;
				height: 100%;
			}

			/* Gold Badge */
			.profile-achievement-badge-gold .badge-circle {
				fill: url(#goldGradient);
				stroke: #B8860B;
				stroke-width: 2;
			}

			.profile-achievement-badge-gold .badge-inner-ring {
				fill: none;
				stroke: #DAA520;
				stroke-width: 1.5;
				opacity: 0.5;
			}

			.profile-achievement-badge-gold .badge-icon-outline {
				stroke: #8B6914;
				fill: none;
			}

			/* Silver Badge */
			.profile-achievement-badge-silver .badge-circle {
				fill: url(#silverGradient);
				stroke: #909090;
				stroke-width: 2;
			}

			.profile-achievement-badge-silver .badge-inner-ring {
				fill: none;
				stroke: #B0B0B0;
				stroke-width: 1.5;
				opacity: 0.5;
			}

			.profile-achievement-badge-silver .badge-icon-outline {
				stroke: #606060;
				fill: none;
			}

			/* Bronze Badge */
			.profile-achievement-badge-bronze .badge-circle {
				fill: url(#bronzeGradient);
				stroke: #8B4513;
				stroke-width: 2;
			}

			.profile-achievement-badge-bronze .badge-inner-ring {
				fill: none;
				stroke: #CD7F32;
				stroke-width: 1.5;
				opacity: 0.5;
			}

			.profile-achievement-badge-bronze .badge-icon-outline {
				stroke: #6B3410;
				fill: none;
			}

			/* Locked Badge */
			.profile-achievement-badge-locked {
				opacity: 0.65;
			}

			.profile-achievement-badge-locked .badge-circle {
				fill: url(#lockedGradient);
				stroke: #909090;
				stroke-width: 2;
			}

			.profile-achievement-badge-locked .badge-inner-ring {
				fill: none;
				stroke: #B0B0B0;
				stroke-width: 1.5;
				opacity: 0.5;
			}

			.profile-achievement-badge-locked .badge-icon-outline {
				stroke: #707070;
				fill: none;
			}

			/* Profile Goal Counter - Bottom Center */
			.profile-goal-counter {
				position: absolute;
				bottom: -8px;
				left: 50%;
				transform: translateX(-50%);
				display: flex;
				align-items: center;
				gap: 4px;
				background: #fff;
				padding: 4px 10px;
				border-radius: 20px;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
				z-index: 10;
				border: 2px solid #ff9900;
				transition: all 0.3s ease;
			}

			.profile-goal-counter:hover {
				box-shadow: 0 4px 12px rgba(255, 153, 0, 0.3);
				transform: translateX(-50%) scale(1.05);
			}

			.goal-star-icon {
				width: 16px;
				height: 16px;
				color: #FFC107;
				flex-shrink: 0;
			}

			.goal-count {
				font-size: 12px;
				font-weight: 700;
				color: #ff9900;
				line-height: 1;
				white-space: nowrap;
			}

			.wc-tp-profile-info {
				flex: 1;
			}

			.wc-tp-profile-info h2 {
				margin: 0 0 12px 0;
				font-size: 1.75rem;
				font-weight: var(--fw-bold);
				color: var(--text-main);
			}

			.wc-tp-profile-info p {
				margin: 8px 0;
				font-size: var(--fs-body);
				color: var(--text-body);
				line-height: var(--lh-body);
			}

			.wc-tp-profile-info p strong {
				color: var(--text-main);
				font-weight: var(--fw-semibold);
			}

			.wc-tp-user-id {
				color: var(--color-primary);
				font-weight: var(--fw-semibold);
			}

			.wc-tp-profile-right {
				flex-shrink: 0;
			}

			.wc-tp-profile-actions {
				display: flex;
				flex-direction: column;
				gap: 16px;
				align-items: flex-end;
			}

			.wc-tp-status-control {
				display: flex;
				flex-direction: column;
				gap: 6px;
				align-items: flex-end;
			}

			.wc-tp-status-control label {
				font-size: var(--fs-meta);
				font-weight: var(--fw-semibold);
				color: var(--text-main);
			}

			.wc-tp-status-control select {
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
				background: var(--color-card-bg);
				min-width: 120px;
				cursor: pointer;
			}

			.wc-tp-status-control select:focus {
				outline: none;
				border-color: var(--color-primary);
				box-shadow: 0 0 0 3px var(--color-primary-subtle);
			}

			.wc-tp-status-control select[value="active"] {
				border-color: var(--color-accent-success);
				color: var(--color-accent-success);
			}

			.wc-tp-status-control select[value="inactive"] {
				border-color: var(--color-accent-alert);
				color: var(--color-accent-alert);
			}

			.wc-tp-profile-right .button-primary {
				display: inline-flex;
				align-items: center;
				gap: 8px;
				background: var(--color-primary);
				border-color: var(--color-primary);
				color: white;
				font-weight: var(--fw-semibold);
				border-radius: 6px;
				padding: 10px 20px;
				font-size: var(--fs-body);
				transition: all 0.2s ease;
			}

			.wc-tp-profile-right .button-primary:hover {
				background: var(--color-primary-hover);
				border-color: var(--color-primary-hover);
			}

			.wc-tp-profile-right .button-primary .dashicons {
				font-size: 18px;
				width: 18px;
				height: 18px;
			}

			/* Stats Cards */
			.wc-tp-stats-grid {
				display: grid;
				grid-template-columns: repeat(4, 1fr);
				gap: 16px;
				margin-bottom: 24px;
			}

			.wc-tp-stat-card {
				background: var(--color-card-bg);
				padding: 20px;
				border-radius: 8px;
				border: 1px solid var(--color-border-light);
				display: flex;
				align-items: center;
				gap: 16px;
				transition: all 0.3s ease;
			}

			.wc-tp-stat-card:hover {
				border-color: var(--color-primary);
				box-shadow: 0 4px 12px rgba(255, 153, 0, 0.1);
				transform: translateY(-2px);
			}

			.wc-tp-stat-icon {
				font-size: 32px;
				min-width: 50px;
				text-align: center;
				flex-shrink: 0;
			}

			.wc-tp-stat-content {
				flex: 1;
			}

			.wc-tp-stat-value {
				font-size: 1.5rem;
				font-weight: var(--fw-bold);
				color: var(--color-primary);
				margin-bottom: 4px;
				line-height: 1.3;
			}

			.wc-tp-stat-label {
				font-size: var(--fs-meta);
				color: var(--text-muted);
				text-transform: uppercase;
				letter-spacing: 0.5px;
				font-weight: var(--fw-medium);
			}

			/* Tabs */
			.wc-tp-tabs {
				margin-bottom: 0;
				border-bottom: 1px solid var(--color-border-light);
			}

			.wc-tp-tabs .nav-tab {
				font-size: var(--fs-body);
				font-weight: var(--fw-semibold);
				color: var(--text-body);
				border: none;
				border-bottom: 3px solid transparent;
				background: transparent;
				padding: 12px 24px;
				margin: 0;
				transition: all 0.2s ease;
			}

			.wc-tp-tabs .nav-tab:hover {
				color: var(--color-primary);
				background: var(--color-primary-subtle);
			}

			.wc-tp-tabs .nav-tab-active {
				color: var(--color-primary);
				border-bottom-color: var(--color-primary);
				background: transparent;
			}

			/* Tab Content */
			.wc-tp-tab-content {
				background: var(--color-card-bg);
				border: 1px solid var(--color-border-light);
				border-top: none;
				border-radius: 0 0 8px 8px;
				padding: 24px;
			}

			/* Unified Filter */
			.wc-tp-unified-filter {
				background: var(--color-accent-muted);
				padding: 16px;
				border-radius: 8px;
				margin-bottom: 24px;
				border: 1px solid var(--color-border-light);
			}

			.wc-tp-filter-row {
				display: flex;
				gap: 12px;
				align-items: flex-end;
				flex-wrap: wrap;
			}

			.wc-tp-filter-group {
				display: flex;
				flex-direction: column;
				gap: 6px;
			}

			.wc-tp-filter-group label {
				font-weight: var(--fw-semibold);
				color: var(--text-main);
				font-size: var(--fs-meta);
			}

			.wc-tp-filter-group select,
			.wc-tp-filter-group input[type="date"],
			.wc-tp-filter-group input[type="text"] {
				padding: 8px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
				background: var(--color-card-bg);
				min-width: 150px;
			}

			.wc-tp-custom-date-range {
				display: flex;
				gap: 8px;
				align-items: center;
				flex-wrap: wrap;
			}

			.wc-tp-custom-date-range input[type="date"] {
				flex: 1;
				min-width: 150px;
			}

			.wc-tp-date-separator {
				color: var(--text-muted);
				font-weight: var(--fw-medium);
				font-size: var(--fs-meta);
			}

			/* Table Section */
			.wc-tp-table-section {
				background: var(--color-card-bg);
				padding: 20px;
				border-radius: 8px;
				border: 1px solid var(--color-border-light);
				margin-bottom: 20px;
			}

			.wc-tp-table-section h2 {
				margin-top: 0;
				margin-bottom: 20px;
				color: var(--text-main);
				border-left: 4px solid var(--color-primary);
				padding-left: 12px;
				font-size: var(--fs-h2);
				font-weight: var(--fw-bold);
			}

			/* Table Wrapper for Horizontal Scroll */
			.wc-tp-table-wrapper {
				overflow-x: auto;
				-webkit-overflow-scrolling: touch;
				margin-bottom: 20px;
			}

			.wc-tp-data-table {
				width: 100%;
				border-collapse: collapse;
				display: table !important;
				min-width: 1000px; /* Ensure table doesn't shrink too much */
			}

			.wc-tp-data-table thead {
				background: var(--color-accent-muted);
				display: table-header-group !important;
			}

			.wc-tp-data-table tbody {
				display: table-row-group !important;
			}

			.wc-tp-data-table tr {
				display: table-row !important;
			}

			.wc-tp-data-table th,
			.wc-tp-data-table td {
				display: table-cell !important;
			}

			.wc-tp-data-table th {
				padding: 14px 12px;
				text-align: left;
				font-weight: var(--fw-semibold);
				color: var(--text-main);
				font-size: var(--fs-meta);
				border-bottom: 1px solid var(--color-border-light);
				white-space: nowrap; /* Prevent header text from wrapping */
			}

			.wc-tp-data-table th .th-content {
				display: inline-block;
				white-space: nowrap;
			}

			.wc-tp-sortable-header {
				cursor: pointer;
				user-select: none;
				transition: all 0.2s ease;
			}

			.wc-tp-sortable-header:hover {
				background: var(--color-primary-subtle);
				color: var(--color-primary);
			}

			.wc-tp-data-table td {
				padding: 12px;
				border-bottom: 1px solid var(--color-border-light);
				font-size: var(--fs-body);
				color: var(--text-body);
				white-space: nowrap; /* Prevent cell content from wrapping */
			}

			.wc-tp-data-table tbody tr:hover {
				background: var(--color-primary-subtle);
			}

			/* Pagination */
			.wc-tp-pagination {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-top: 20px;
				padding-top: 20px;
				border-top: 1px solid var(--color-border-light);
				flex-wrap: wrap;
				gap: 16px;
			}

			.wc-tp-pagination-info {
				font-size: var(--fs-meta);
				color: var(--text-muted);
				font-weight: var(--fw-medium);
			}

			.wc-tp-pagination-controls {
				display: flex;
				gap: 8px;
				align-items: center;
				flex-wrap: wrap;
			}

			.wc-tp-pagination-btn {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-width: 36px;
				height: 36px;
				padding: 0 8px;
				border: 1px solid var(--color-border-light);
				background: var(--color-card-bg);
				color: var(--text-body);
				border-radius: 4px;
				cursor: pointer;
				font-size: var(--fs-meta);
				font-weight: var(--fw-semibold);
				transition: all 0.2s ease;
			}

			.wc-tp-pagination-btn:hover:not(:disabled) {
				background: var(--color-primary-subtle);
				border-color: var(--color-primary);
				color: var(--color-primary);
			}

			.wc-tp-pagination-btn.active {
				background: var(--color-primary);
				border-color: var(--color-primary);
				color: white;
			}

			.wc-tp-pagination-btn:disabled {
				opacity: 0.5;
				cursor: not-allowed;
			}

			.wc-tp-pagination-ellipsis {
				color: var(--text-muted);
				padding: 0 4px;
			}

			.wc-tp-empty-state {
				text-align: center;
				padding: 40px 20px;
				color: var(--text-muted);
			}

			.wc-tp-empty-icon {
				font-size: 48px;
				margin-bottom: 15px;
				display: block;
				opacity: 0.5;
			}

			.wc-tp-empty-state p {
				margin: 0;
				font-size: var(--fs-body);
				color: var(--text-muted);
			}

			/* Forms */
			.wc-tp-form {
				background: var(--color-accent-muted);
				padding: 20px;
				border-radius: 8px;
			}

			.wc-tp-form-row {
				display: flex;
				gap: 16px;
				align-items: flex-end;
				flex-wrap: wrap;
			}

			.wc-tp-form-group {
				display: flex;
				flex-direction: column;
				gap: 6px;
				flex: 1;
				min-width: 200px;
			}

			.wc-tp-form-group label {
				font-weight: var(--fw-semibold);
				color: var(--text-main);
				font-size: var(--fs-meta);
			}

			.wc-tp-form-group input[type="text"],
			.wc-tp-form-group input[type="number"],
			.wc-tp-form-group input[type="datetime-local"],
			.wc-tp-form-group select {
				padding: 10px 12px;
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
				font-size: var(--fs-body);
				font-family: var(--font-family);
				color: var(--text-main);
				background: var(--color-card-bg);
			}

			.wc-tp-form-group input:focus,
			.wc-tp-form-group select:focus {
				outline: none;
				border-color: var(--color-primary);
				box-shadow: 0 0 0 3px var(--color-primary-subtle);
			}

			/* Buttons */
			.button-primary {
				background: var(--color-primary);
				border-color: var(--color-primary);
				color: white;
				font-weight: var(--fw-semibold);
				border-radius: 6px;
				padding: 10px 20px;
				font-size: var(--fs-body);
				transition: all 0.2s ease;
				cursor: pointer;
			}

			.button-primary:hover {
				background: var(--color-primary-hover);
				border-color: var(--color-primary-hover);
			}

			.button-secondary {
				background: var(--color-accent-muted);
				border-color: var(--color-border-light);
				color: var(--text-main);
				font-weight: var(--fw-semibold);
				border-radius: 6px;
				padding: 8px 16px;
				font-size: var(--fs-meta);
				transition: all 0.2s ease;
			}

			.button-secondary:hover {
				background: var(--color-border-light);
				border-color: var(--color-border-light);
			}

			.button-small {
				padding: 6px 12px;
				font-size: var(--fs-small);
			}

			/* Action Icons */
			.wc-tp-action-icons {
				display: flex;
				gap: 8px;
			}

			.wc-tp-action-icon {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 32px;
				height: 32px;
				border-radius: 4px;
				border: 1px solid var(--color-border-light);
				background: var(--color-card-bg);
				color: var(--text-body);
				transition: all 0.2s ease;
				text-decoration: none;
			}

			.wc-tp-action-icon:hover {
				background: var(--color-primary);
				border-color: var(--color-primary);
				color: white;
			}

			.wc-tp-action-icon .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
			}

			/* Badges */
			.wc-tp-badge {
				display: inline-block;
				padding: 4px 8px;
				border-radius: 4px;
				font-size: var(--fs-small);
				font-weight: var(--fw-semibold);
			}

			.wc-tp-badge-owner {
				background: #E3F2FD;
				color: #1976D2;
			}

			.wc-tp-badge-affiliate-to {
				background: #FFF3E0;
				color: #F57C00;
			}

			.wc-tp-badge-affiliate-from {
				background: #F3E5F5;
				color: #7B1FA2;
			}

			.wc-tp-status-completed {
				background: #D4EDDA;
				color: #155724;
			}

			.wc-tp-status-processing {
				background: #D1ECF1;
				color: #0C5460;
			}

			.wc-tp-status-pending {
				background: #FFF3CD;
				color: #856404;
			}

			.wc-tp-status-cancelled {
				background: #F8D7DA;
				color: #721C24;
			}

			.wc-tp-status-refunded {
				background: #E2E3E5;
				color: #383D41;
			}

			/* Payment Methods List */
			.wc-tp-payment-methods-list {
				display: grid;
				gap: 12px;
			}

			.wc-tp-payment-method-item {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 16px;
				background: var(--color-accent-muted);
				border: 1px solid var(--color-border-light);
				border-radius: 6px;
			}

			.wc-tp-payment-method-info {
				flex: 1;
			}

			.wc-tp-payment-method-name {
				font-weight: var(--fw-semibold);
				color: var(--text-main);
				margin-bottom: 4px;
			}

			.wc-tp-payment-method-details {
				font-size: var(--fs-meta);
				color: var(--text-body);
			}

			.wc-tp-payment-method-actions {
				display: flex;
				gap: 8px;
			}

			.wc-tp-delete-btn {
				background: #dc3545;
				border-color: #dc3545;
				color: white;
				padding: 6px 12px;
				border-radius: 4px;
				font-size: var(--fs-small);
				cursor: pointer;
				transition: all 0.2s ease;
			}

			.wc-tp-delete-btn:hover {
				background: #c82333;
				border-color: #c82333;
			}

			/* Responsive Design */
			@media (max-width: 1024px) {
				.wc-tp-stats-grid {
					grid-template-columns: repeat(2, 1fr);
				}

				.wc-tp-filter-row {
					flex-direction: column;
					align-items: stretch;
				}

				.wc-tp-filter-group {
					width: 100%;
				}
			}

			@media (max-width: 768px) {
				.wc-tp-employee-detail {
					padding: 12px;
				}

				.wc-tp-profile-header {
					flex-direction: column;
				}

				.wc-tp-profile-left {
					flex-direction: column;
					align-items: center;
					text-align: center;
				}

				.wc-tp-profile-right {
					width: 100%;
				}

				.wc-tp-profile-actions {
					align-items: stretch;
				}

				.wc-tp-status-control {
					align-items: stretch;
				}

				.wc-tp-status-control select {
					width: 100%;
				}

				.wc-tp-profile-right .button-primary {
					width: 100%;
					justify-content: center;
				}

				.wc-tp-stats-grid {
					grid-template-columns: 1fr;
				}

				.wc-tp-stat-card {
					flex-direction: column;
					text-align: center;
				}

				.wc-tp-form-row {
					flex-direction: column;
				}

				.wc-tp-form-group {
					width: 100%;
					min-width: unset;
				}

				.wc-tp-data-table {
					font-size: var(--fs-small);
				}

				.wc-tp-data-table th,
				.wc-tp-data-table td {
					padding: 8px 6px;
				}
			}
		</style>
		<?php
	}

	/**
	 * Add inline scripts
	 */
	public function add_scripts() {
		?>
		<script>
			jQuery(document).ready(function($) {
				const userId = $('#wc-tp-current-user-id').val();
				const nonce = $('#wc_team_payroll_nonce').val();

				// Employee Status Change Handler
				$('#wc-tp-employee-status').on('change', function() {
					const newStatus = $(this).val();
					const userId = $(this).data('user-id');
					const $select = $(this);
					
					// Show confirmation for status change
					const statusText = newStatus === 'active' ? 'Active' : 'Inactive';
					const message = 'Are you sure you want to change this employee status to ' + statusText + '?';
					
					if (newStatus === 'inactive') {
						const warningMessage = 'Warning: Setting employee to Inactive will:\n\n' +
							'• Hide them from checkout agent dropdown\n' +
							'• Block their WordPress login access\n' +
							'• Show warning message on login attempts\n\n' +
							'Are you sure you want to continue?';
						
						if (!confirm(warningMessage)) {
							// Revert selection
							$select.val($select.data('previous-value') || 'active');
							return;
						}
					} else {
						if (!confirm(message)) {
							// Revert selection
							$select.val($select.data('previous-value') || 'inactive');
							return;
						}
					}
					
					// Store previous value
					$select.data('previous-value', newStatus);
					
					// Disable select during update
					$select.prop('disabled', true);
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wc_tp_update_employee_status',
							user_id: userId,
							status: newStatus,
							nonce: nonce
						},
						success: function(response) {
							if (response.success) {
								// Update select styling based on status
								$select.removeClass('status-active status-inactive').addClass('status-' + newStatus);
								
								// Show success message
								if (typeof wcTPToast === 'function') {
									wcTPToast('Employee status updated successfully', 'success');
								} else {
									alert('Employee status updated successfully');
								}
							} else {
								// Revert on error
								const previousValue = newStatus === 'active' ? 'inactive' : 'active';
								$select.val(previousValue);
								
								if (typeof wcTPToast === 'function') {
									wcTPToast('Failed to update employee status: ' + (response.data || 'Unknown error'), 'error');
								} else {
									alert('Failed to update employee status: ' + (response.data || 'Unknown error'));
								}
							}
						},
						error: function() {
							// Revert on error
							const previousValue = newStatus === 'active' ? 'inactive' : 'active';
							$select.val(previousValue);
							
							if (typeof wcTPToast === 'function') {
								wcTPToast('Error updating employee status', 'error');
							} else {
								alert('Error updating employee status');
							}
						},
						complete: function() {
							$select.prop('disabled', false);
						}
					});
				});

				// Store initial value
				$('#wc-tp-employee-status').data('previous-value', $('#wc-tp-employee-status').val());

				// Add inline edit form styles
				if (!$('#wc-tp-inline-edit-styles').length) {
					$('head').append(`
						<style id="wc-tp-inline-edit-styles">
							.wc-tp-inline-edit-form {
								padding: 16px;
								background: #f9f9f9;
								border-radius: 6px;
							}

							.wc-tp-inline-edit-form .wc-tp-form-row {
								display: flex;
								gap: 12px;
								align-items: flex-end;
								flex-wrap: wrap;
							}

							.wc-tp-inline-edit-form .wc-tp-form-group {
								display: flex;
								flex-direction: column;
								gap: 6px;
								flex: 1;
								min-width: 150px;
							}

							.wc-tp-inline-edit-form .wc-tp-form-group label {
								font-weight: 600;
								font-size: 12px;
								color: #212B36;
							}

							.wc-tp-inline-edit-form .wc-tp-form-group input {
								padding: 8px 12px;
								border: 1px solid #E5EAF0;
								border-radius: 6px;
								font-size: 14px;
							}
						</style>
					`);
				}

				// ============================================================================
				// ORDERS TAB
				// ============================================================================
				if ($('.wc-tp-orders-tab').length) {
					let currentStartDate = '';
					let currentEndDate = '';
					let currentSortColumn = 'date';
					let currentSortDirection = 'desc';
					let currentPage = 1;
					let itemsPerPage = 10;
					let allOrders = [];

					// Load saved items per page from localStorage
					const savedOrdersPerPage = localStorage.getItem('wc_tp_orders_per_page');
					if (savedOrdersPerPage) {
						itemsPerPage = parseInt(savedOrdersPerPage);
						$('#wc-tp-orders-per-page').val(itemsPerPage);
					}

					// Initialize with default preset (This Month)
					updateDateRangeFromPreset('this-month');
					loadOrdersData();

					// Screen options for items per page
					$('#wc-tp-orders-per-page').on('change', function() {
						itemsPerPage = parseInt($(this).val());
						localStorage.setItem('wc_tp_orders_per_page', itemsPerPage);
						currentPage = 1;
						renderOrdersTable(allOrders);
					});

					// Date preset change
					$('#wc-tp-orders-date-preset').on('change', function() {
						const preset = $(this).val();
						
						if (preset === 'custom') {
							$('#wc-tp-orders-custom-date-range').slideDown(200);
						} else {
							$('#wc-tp-orders-custom-date-range').slideUp(200);
							updateDateRangeFromPreset(preset);
						}
					});

					// Filter button click
					$('#wc-tp-orders-filter-btn').on('click', function() {
						loadOrdersData();
					});

					// Search on enter key
					$('#wc-tp-orders-search').on('keypress', function(e) {
						if (e.which === 13) {
							loadOrdersData();
						}
					});

					function getDateRangeFromPreset(preset) {
						const today = new Date();
						const year = today.getFullYear();
						const month = String(today.getMonth() + 1).padStart(2, '0');
						const date = String(today.getDate()).padStart(2, '0');
						const todayStr = `${year}-${month}-${date}`;

						let startDate, endDate;

						switch (preset) {
							case 'today':
								startDate = todayStr;
								endDate = todayStr;
								break;
							case 'this-week':
								const firstDay = new Date(today);
								firstDay.setDate(today.getDate() - today.getDay());
								startDate = formatDateForInput(firstDay);
								endDate = todayStr;
								break;
							case 'this-month':
								startDate = `${year}-${month}-01`;
								endDate = todayStr;
								break;
							case 'this-year':
								startDate = `${year}-01-01`;
								endDate = todayStr;
								break;
							case 'last-week':
								const lastWeekEnd = new Date(today);
								lastWeekEnd.setDate(today.getDate() - today.getDay() - 1);
								const lastWeekStart = new Date(lastWeekEnd);
								lastWeekStart.setDate(lastWeekEnd.getDate() - 6);
								startDate = formatDateForInput(lastWeekStart);
								endDate = formatDateForInput(lastWeekEnd);
								break;
							case 'last-month':
								const lastMonthDate = new Date(year, parseInt(month) - 2, 1);
								const lastMonthYear = lastMonthDate.getFullYear();
								const lastMonthMonth = String(lastMonthDate.getMonth() + 1).padStart(2, '0');
								startDate = `${lastMonthYear}-${lastMonthMonth}-01`;
								const lastMonthLastDay = new Date(lastMonthYear, parseInt(lastMonthMonth), 0);
								endDate = `${lastMonthYear}-${lastMonthMonth}-${String(lastMonthLastDay.getDate()).padStart(2, '0')}`;
								break;
							case 'last-year':
								const lastYear = year - 1;
								startDate = `${lastYear}-01-01`;
								endDate = `${lastYear}-12-31`;
								break;
							case 'last-6-months':
								const sixMonthsAgo = new Date(today);
								sixMonthsAgo.setMonth(today.getMonth() - 6);
								startDate = formatDateForInput(sixMonthsAgo);
								endDate = todayStr;
								break;
							case 'all-time':
								startDate = '2000-01-01';
								endDate = todayStr;
								break;
							default:
								startDate = `${year}-${month}-01`;
								endDate = todayStr;
						}

						return { start: startDate, end: endDate };
					}

					function formatDateForInput(date) {
						const year = date.getFullYear();
						const month = String(date.getMonth() + 1).padStart(2, '0');
						const day = String(date.getDate()).padStart(2, '0');
						return `${year}-${month}-${day}`;
					}

					function updateDateRangeFromPreset(preset) {
						const range = getDateRangeFromPreset(preset);
						currentStartDate = range.start;
						currentEndDate = range.end;
						$('#wc-tp-orders-start-date').val(range.start);
						$('#wc-tp-orders-end-date').val(range.end);
					}

					function loadOrdersData() {
						const preset = $('#wc-tp-orders-date-preset').val();
						
						if (preset === 'custom') {
							currentStartDate = $('#wc-tp-orders-start-date').val();
							currentEndDate = $('#wc-tp-orders-end-date').val();
						}

						const status = $('#wc-tp-orders-status-filter').val();
						const role = $('#wc-tp-orders-role-filter').val();
						const search = $('#wc-tp-orders-search').val();

						if (!currentStartDate || !currentEndDate) {
							return;
						}

						currentPage = 1;
						$('#wc-tp-orders-filter-btn').prop('disabled', true).text('Loading...');

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_get_employee_orders',
								user_id: userId,
								start_date: currentStartDate,
								end_date: currentEndDate,
								status: status,
								role: role,
								search: search,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									allOrders = response.data.orders;
									renderOrdersTable(allOrders);
								} else {
									$('#wc-tp-orders-table-container').html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📦</div><p>Failed to load orders</p></div>');
								}
							},
							error: function() {
								$('#wc-tp-orders-table-container').html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">❌</div><p>Error loading orders</p></div>');
							},
							complete: function() {
								$('#wc-tp-orders-filter-btn').prop('disabled', false).text('Filter');
							}
						});
					}

					function renderOrdersTable(orders) {
						const container = $('#wc-tp-orders-table-container');
						
						if (!orders || orders.length === 0) {
							container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📦</div><p>No orders found</p></div>');
							return;
						}

						// Sort orders based on current sort settings
						orders = sortOrders(orders, currentSortColumn, currentSortDirection);

						// Calculate pagination
						const totalPages = Math.ceil(orders.length / itemsPerPage);
						const startIndex = (currentPage - 1) * itemsPerPage;
						const endIndex = startIndex + itemsPerPage;
						const paginatedOrders = orders.slice(startIndex, endIndex);

						let html = '<div class="wc-tp-table-wrapper"><table class="wc-tp-data-table"><thead><tr>';
						html += '<th class="wc-tp-sortable-header" data-column="order_id">';
						html += '<span class="th-content">Order&nbsp;ID</span>' + getSortIcon('order_id');
						html += '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="customer_name">';
						html += '<span class="th-content">Customer</span>' + getSortIcon('customer_name');
						html += '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="total">';
						html += '<span class="th-content">Total</span>' + getSortIcon('total');
						html += '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="attributed_total">';
						html += '<span class="th-content">Attributed&nbsp;Total</span>' + getSortIcon('attributed_total');
						html += '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="status">';
						html += '<span class="th-content">Status</span>' + getSortIcon('status');
						html += '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="commission">';
						html += '<span class="th-content">Commission</span>' + getSortIcon('commission');
						html += '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="user_earnings">';
						html += '<span class="th-content">Your&nbsp;Earnings</span>' + getSortIcon('user_earnings');
						html += '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="role">';
						html += '<span class="th-content">Employee&nbsp;Role</span>' + getSortIcon('role');
						html += '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="date">';
						html += '<span class="th-content">Date</span>' + getSortIcon('date');
						html += '</th>';
						html += '<th><span class="th-content">Actions</span></th>';
						html += '</tr></thead><tbody>';

						$.each(paginatedOrders, function(i, order) {
							const statusClass = 'wc-tp-status-' + order.status.toLowerCase();
							const roleClass = 'wc-tp-badge-' + order.role;
							const viewUrl = '<?php echo admin_url("post.php"); ?>?post=' + order.order_id + '&action=edit';
							const editUrl = viewUrl;

							html += '<tr>';
							html += '<td><strong>#' + order.order_id + '</strong></td>';
							html += '<td>' + order.customer_name + '</td>';
							html += '<td>' + formatCurrency(order.total) + '</td>';
							html += '<td>' + (order.attributed_total_formatted || '—') + '</td>';
							html += '<td><span class="wc-tp-badge ' + statusClass + '">' + order.status + '</span></td>';
							html += '<td>' + formatCurrency(order.commission) + '</td>';
							html += '<td><strong>' + formatCurrency(order.user_earnings) + '</strong></td>';
							html += '<td><span class="wc-tp-badge ' + roleClass + '">' + order.role_label + '</span></td>';
							html += '<td>' + order.date + '</td>';
							html += '<td><div class="wc-tp-action-icons">';
							html += '<a href="' + viewUrl + '" class="wc-tp-action-icon" title="View Order"><span class="dashicons dashicons-visibility"></span></a>';
							html += '<a href="' + editUrl + '" class="wc-tp-action-icon" title="Edit Order"><span class="dashicons dashicons-edit"></span></a>';
							html += '</div></td>';
							html += '</tr>';
						});

						html += '</tbody></table></div>';

						// Add pagination controls
						html += '<div class="wc-tp-pagination">';
						html += '<div class="wc-tp-pagination-info">';
						html += 'Showing ' + (startIndex + 1) + ' to ' + Math.min(endIndex, orders.length) + ' of ' + orders.length + ' orders';
						html += '</div>';
						html += '<div class="wc-tp-pagination-controls">';
						
						if (currentPage > 1) {
							html += '<button class="wc-tp-pagination-btn wc-tp-prev-page" data-page="' + (currentPage - 1) + '"><span class="dashicons dashicons-arrow-left"></span></button>';
						}
						
						for (let i = 1; i <= totalPages; i++) {
							if (i === currentPage) {
								html += '<button class="wc-tp-pagination-btn wc-tp-page-btn active" data-page="' + i + '">' + i + '</button>';
							} else if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
								html += '<button class="wc-tp-pagination-btn wc-tp-page-btn" data-page="' + i + '">' + i + '</button>';
							} else if (i === 2 || i === totalPages - 1) {
								html += '<span class="wc-tp-pagination-ellipsis">...</span>';
							}
						}
						
						if (currentPage < totalPages) {
							html += '<button class="wc-tp-pagination-btn wc-tp-next-page" data-page="' + (currentPage + 1) + '"><span class="dashicons dashicons-arrow-right"></span></button>';
						}
						
						html += '</div>';
						html += '</div>';

						container.html(html);

						// Attach click handlers to sortable headers
						$('.wc-tp-sortable-header').on('click', function() {
							const column = $(this).data('column');
							
							if (currentSortColumn === column) {
								// Toggle direction if same column
								currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
							} else {
								// New column, default to desc
								currentSortColumn = column;
								currentSortDirection = 'desc';
							}
							
							currentPage = 1;
							renderOrdersTable(allOrders);
						});

						// Attach click handlers to pagination buttons
						$('.wc-tp-page-btn, .wc-tp-prev-page, .wc-tp-next-page').on('click', function() {
							currentPage = parseInt($(this).data('page'));
							renderOrdersTable(allOrders);
						});
					}

					function getSortIcon(column) {
						if (currentSortColumn !== column) {
							return '';
						}
						
						const icon = currentSortDirection === 'asc' ? 'arrow-up' : 'arrow-down';
						return ' <span class="dashicons dashicons-' + icon + '" style="font-size: 14px; margin-left: 4px;"></span>';
					}

					function sortOrders(orders, column, direction) {
						const sorted = [...orders].sort((a, b) => {
							let aVal = a[column];
							let bVal = b[column];
							
							// Handle numeric values
							if (typeof aVal === 'string' && !isNaN(aVal)) {
								aVal = parseFloat(aVal);
								bVal = parseFloat(bVal);
							}
							
							if (aVal < bVal) return direction === 'asc' ? -1 : 1;
							if (aVal > bVal) return direction === 'asc' ? 1 : -1;
							return 0;
						});
						
						return sorted;
					}

					function formatCurrency(value) {
						return '<?php echo get_woocommerce_currency_symbol(); ?>' + parseFloat(value).toFixed(2);
					}
				}

				// ============================================================================
				// PAYMENTS TAB
				// ============================================================================
				if ($('.wc-tp-payments-tab').length) {
					// Payment History Pagination
					let paymentHistorySortColumn = 'date';
					let paymentHistorySortDirection = 'desc';
					let paymentHistoryPage = 1;
					let paymentHistoryPerPage = 10;
					let allPayments = [];

					// Payment Methods Pagination
					let paymentMethodsSortColumn = 'method_name';
					let paymentMethodsSortDirection = 'asc';
					let paymentMethodsPage = 1;
					let paymentMethodsPerPage = 10;
					let allPaymentMethods = [];

					// Load saved items per page from localStorage
					const savedPaymentHistoryPerPage = localStorage.getItem('wc_tp_payment_history_per_page');
					if (savedPaymentHistoryPerPage) {
						paymentHistoryPerPage = parseInt(savedPaymentHistoryPerPage);
						$('#wc-tp-payment-history-per-page').val(paymentHistoryPerPage);
					}

					const savedPaymentMethodsPerPage = localStorage.getItem('wc_tp_payment_methods_per_page');
					if (savedPaymentMethodsPerPage) {
						paymentMethodsPerPage = parseInt(savedPaymentMethodsPerPage);
						$('#wc-tp-payment-methods-per-page').val(paymentMethodsPerPage);
					}

					loadPaymentMethods();
					loadPaymentHistory();

					// Payment History Screen Options
					$('#wc-tp-payment-history-per-page').on('change', function() {
						paymentHistoryPerPage = parseInt($(this).val());
						localStorage.setItem('wc_tp_payment_history_per_page', paymentHistoryPerPage);
						paymentHistoryPage = 1;
						renderPaymentHistory(allPayments);
					});

					// Payment Methods Screen Options
					$('#wc-tp-payment-methods-per-page').on('change', function() {
						paymentMethodsPerPage = parseInt($(this).val());
						localStorage.setItem('wc_tp_payment_methods_per_page', paymentMethodsPerPage);
						paymentMethodsPage = 1;
						renderPaymentMethods(allPaymentMethods);
					});

					// Add Payment Form Submit
					$('#wc-tp-add-payment-form').on('submit', function(e) {
						e.preventDefault();

						const amount = $('#wc-tp-payment-amount').val();
						const date = $('#wc-tp-payment-date').val();
						const method = $('#wc-tp-payment-method').val();
						const note = $('#wc-tp-payment-note').val();

						if (!amount || !date) {
							wcTPToast('Please fill in all required fields', 'error');
							return;
						}

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_add_payment',
								user_id: userId,
								amount: amount,
								payment_date: date,
								payment_method: method || '',
								note: note,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									wcTPToast('Payment added successfully');
									$('#wc-tp-add-payment-form')[0].reset();
									$('#wc-tp-payment-date').val('<?php echo date("Y-m-d\TH:i"); ?>');
									loadPaymentHistory();
									updateStatsCards();
								} else {
									wcTPToast('Failed to add payment: ' + response.data, 'error');
								}
							},
							error: function() {
								wcTPToast('Error adding payment', 'error');
							}
						});
					});

					// Add Payment Method Form Submit
					$('#wc-tp-add-payment-method-form').on('submit', function(e) {
						e.preventDefault();

						const methodName = $('#wc-tp-method-name').val();
						const methodAccount = $('#wc-tp-method-account').val();
						const methodNote = $('#wc-tp-method-note').val();

						if (!methodName || !methodAccount) {
							wcTPToast('Please fill in all required fields', 'error');
							return;
						}

						const methodDetails = methodAccount + (methodNote ? ' - ' + methodNote : '');

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_add_payment_method',
								user_id: userId,
								method_name: methodName,
								method_details: methodDetails,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									wcTPToast('Payment method added successfully');
									$('#wc-tp-add-payment-method-form')[0].reset();
									loadPaymentMethods();
								} else {
									wcTPToast('Failed to add payment method: ' + response.data, 'error');
								}
							},
							error: function() {
								wcTPToast('Error adding payment method', 'error');
							}
						});
					});

					// Edit Payment Method
					$(document).on('click', '.wc-tp-edit-method', function() {
						// Remove any existing edit forms first
						$('.wc-tp-edit-method-row').remove();
						$('tr').show();

						const row = $(this).closest('tr');
						const methodId = $(this).data('method-id');
						const methodName = row.find('td:eq(1)').text();
						const methodDetails = row.find('td:eq(2)').text();

						// Show edit form
						const editForm = $(`
							<tr class="wc-tp-edit-method-row">
								<td colspan="4">
									<div class="wc-tp-inline-edit-form">
										<div class="wc-tp-form-row">
											<div class="wc-tp-form-group">
												<label>Payment Method</label>
												<input type="text" class="wc-tp-edit-method-name" value="${methodName}" />
											</div>
											<div class="wc-tp-form-group">
												<label>Account/Details</label>
												<input type="text" class="wc-tp-edit-method-details" value="${methodDetails}" />
											</div>
											<div class="wc-tp-form-group">
												<button type="button" class="button button-primary wc-tp-save-method-edit" data-method-id="${methodId}">Save</button>
												<button type="button" class="button wc-tp-cancel-method-edit">Cancel</button>
											</div>
										</div>
									</div>
								</td>
							</tr>
						`);

						row.hide();
						row.after(editForm);
					});

					// Save Payment Method Edit
					$(document).on('click', '.wc-tp-save-method-edit', function() {
						const methodId = $(this).data('method-id');
						const methodName = $('.wc-tp-edit-method-name').val();
						const methodDetails = $('.wc-tp-edit-method-details').val();

						if (!methodName || !methodDetails) {
							wcTPToast('Please fill in all fields', 'error');
							return;
						}

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_update_payment_method',
								user_id: userId,
								method_id: methodId,
								method_name: methodName,
								method_details: methodDetails,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									wcTPToast('Payment method updated successfully');
									loadPaymentMethods();
								} else {
									wcTPToast('Failed to update payment method: ' + response.data, 'error');
								}
							},
							error: function() {
								wcTPToast('Error updating payment method', 'error');
							}
						});
					});

					// Cancel Payment Method Edit
					$(document).on('click', '.wc-tp-cancel-method-edit', function() {
						$('.wc-tp-edit-method-row').remove();
						$('tr').show();
					});

					// Delete Payment Method (Single)
					$(document).on('click', '.wc-tp-delete-method', function() {
						const methodId = $(this).data('method-id');

						wcTPDeleteModal({
							message: 'This will permanently delete this payment method. This action cannot be undone.',
							onConfirm: function() {
								$.ajax({
									url: ajaxurl,
									type: 'POST',
									data: {
										action: 'wc_tp_delete_payment_method',
										user_id: userId,
										method_id: methodId,
										nonce: nonce
									},
									success: function(response) {
										if (response.success) {
											wcTPToast('Payment method deleted successfully');
											loadPaymentMethods();
										} else {
											wcTPToast('Failed to delete payment method: ' + response.data, 'error');
										}
									},
									error: function() {
										wcTPToast('Error deleting payment method', 'error');
									}
								});
							}
						});
					});

					// Select All Payment Methods
					$(document).on('change', '.wc-tp-select-all-methods', function() {
						const isChecked = $(this).prop('checked');
						$('.wc-tp-method-checkbox').prop('checked', isChecked);
						toggleBulkDeleteMethodsButton();
					});

					// Individual Method Checkbox
					$(document).on('change', '.wc-tp-method-checkbox', function() {
						const totalCheckboxes = $('.wc-tp-method-checkbox').length;
						const checkedCheckboxes = $('.wc-tp-method-checkbox:checked').length;
						$('.wc-tp-select-all-methods').prop('checked', totalCheckboxes === checkedCheckboxes);
						toggleBulkDeleteMethodsButton();
					});

					// Bulk Delete Payment Methods
					$(document).on('click', '.wc-tp-bulk-delete-methods', function() {
						const selectedIds = [];
						$('.wc-tp-method-checkbox:checked').each(function() {
							const methodId = $(this).data('method-id');
							if (methodId) {
								selectedIds.push(methodId);
							}
						});

						if (selectedIds.length === 0) {
							wcTPToast('Please select at least one payment method to delete', 'warning');
							return;
						}

						const count = selectedIds.length;
						const message = count === 1 
							? 'This will permanently delete 1 payment method. This action cannot be undone.'
							: 'This will permanently delete ' + count + ' payment methods. This action cannot be undone.';

						wcTPDeleteModal({
							message: message,
							onConfirm: function() {
								// Delete each method
								let completed = 0;
								let failed = 0;
								const totalToDelete = selectedIds.length;

								selectedIds.forEach(function(methodId) {
									$.ajax({
										url: ajaxurl,
										type: 'POST',
										data: {
											action: 'wc_tp_delete_payment_method',
											user_id: userId,
											method_id: methodId,
											nonce: nonce
										},
										success: function(response) {
											if (response.success) {
												completed++;
											} else {
												failed++;
											}
											checkCompletion();
										},
										error: function() {
											failed++;
											checkCompletion();
										}
									});
								});

								function checkCompletion() {
									if (completed + failed === totalToDelete) {
										if (failed === 0) {
											wcTPToast(completed + ' payment method(s) deleted successfully');
										} else {
											wcTPToast(completed + ' deleted, ' + failed + ' failed', 'warning');
										}
										loadPaymentMethods();
									}
								}
							}
						});
					});

					function toggleBulkDeleteMethodsButton() {
						const checkedCount = $('.wc-tp-method-checkbox:checked').length;
						if (checkedCount > 0) {
							$('.wc-tp-bulk-delete-methods').show().text('Delete Selected (' + checkedCount + ')');
						} else {
							$('.wc-tp-bulk-delete-methods').hide();
						}
					}

					// Delete Payment (Single)
					$(document).on('click', '.wc-tp-delete-payment', function() {
						const paymentId = $(this).data('payment-id');

						wcTPDeleteModal({
							message: 'This will permanently delete this payment. This action cannot be undone.',
							onConfirm: function() {
								$.ajax({
									url: ajaxurl,
									type: 'POST',
									data: {
										action: 'wc_tp_delete_payment',
										user_id: userId,
										payment_id: paymentId,
										nonce: nonce
									},
									success: function(response) {
										if (response.success) {
											wcTPToast('Payment deleted successfully');
											loadPaymentHistory();
											updateStatsCards();
										} else {
											wcTPToast('Failed to delete payment: ' + response.data, 'error');
										}
									},
									error: function() {
										wcTPToast('Error deleting payment', 'error');
									}
								});
							}
						});
					});

					// Select All Payments
					$(document).on('change', '.wc-tp-select-all-payments', function() {
						const isChecked = $(this).prop('checked');
						$('.wc-tp-payment-checkbox').prop('checked', isChecked);
						toggleBulkDeleteButton();
					});

					// Individual Payment Checkbox
					$(document).on('change', '.wc-tp-payment-checkbox', function() {
						const totalCheckboxes = $('.wc-tp-payment-checkbox').length;
						const checkedCheckboxes = $('.wc-tp-payment-checkbox:checked').length;
						$('.wc-tp-select-all-payments').prop('checked', totalCheckboxes === checkedCheckboxes);
						toggleBulkDeleteButton();
					});

					// Bulk Delete Payments
					$(document).on('click', '.wc-tp-bulk-delete-payments', function() {
						const selectedIds = [];
						$('.wc-tp-payment-checkbox:checked').each(function() {
							const paymentId = $(this).data('payment-id');
							if (paymentId) {
								selectedIds.push(paymentId);
							}
						});

						if (selectedIds.length === 0) {
							wcTPToast('Please select at least one payment to delete', 'warning');
							return;
						}

						const count = selectedIds.length;
						const message = count === 1 
							? 'This will permanently delete 1 payment. This action cannot be undone.'
							: 'This will permanently delete ' + count + ' payments. This action cannot be undone.';

						wcTPDeleteModal({
							message: message,
							onConfirm: function() {
								// Delete each payment
								let completed = 0;
								let failed = 0;
								const totalToDelete = selectedIds.length;

								selectedIds.forEach(function(paymentId) {
									$.ajax({
										url: ajaxurl,
										type: 'POST',
										data: {
											action: 'wc_tp_delete_payment',
											user_id: userId,
											payment_id: paymentId,
											nonce: nonce
										},
										success: function(response) {
											if (response.success) {
												completed++;
											} else {
												failed++;
											}
											checkCompletion();
										},
										error: function() {
											failed++;
											checkCompletion();
										}
									});
								});

								function checkCompletion() {
									if (completed + failed === totalToDelete) {
										if (failed === 0) {
											wcTPToast(completed + ' payment(s) deleted successfully');
										} else {
											wcTPToast(completed + ' deleted, ' + failed + ' failed', 'warning');
										}
										loadPaymentHistory();
										updateStatsCards();
									}
								}
							}
						});
					});

					function toggleBulkDeleteButton() {
						const checkedCount = $('.wc-tp-payment-checkbox:checked').length;
						if (checkedCount > 0) {
							$('.wc-tp-bulk-delete-payments').show().text('Delete Selected (' + checkedCount + ')');
						} else {
							$('.wc-tp-bulk-delete-payments').hide();
						}
					}

					// Edit Payment
					$(document).on('click', '.wc-tp-edit-payment', function() {
						// Remove any existing edit forms first
						$('.wc-tp-edit-payment-row').remove();
						$('tr').show();

						const row = $(this).closest('tr');
						const paymentId = $(this).data('payment-id');
						const amount = row.find('.wc-tp-payment-amount').data('amount');
						const date = row.find('.wc-tp-payment-date').data('date');
						const method = row.find('.wc-tp-payment-method').data('method') || '';
						const note = row.find('.wc-tp-payment-note').data('note') || '';

						// Convert date format for datetime-local input
						const dateObj = new Date(date.replace(' ', 'T'));
						const formattedDate = dateObj.toISOString().slice(0, 16);

						// Get payment methods for dropdown
						const methodOptions = $('#wc-tp-payment-method option').clone();

						// Show edit form
						const editForm = $(`
							<tr class="wc-tp-edit-payment-row">
								<td colspan="6">
									<div class="wc-tp-inline-edit-form">
										<div class="wc-tp-form-row">
											<div class="wc-tp-form-group">
												<label>Amount</label>
												<input type="number" class="wc-tp-edit-amount" step="0.01" min="0" value="${amount}" />
											</div>
											<div class="wc-tp-form-group">
												<label>Date</label>
												<input type="datetime-local" class="wc-tp-edit-date" value="${formattedDate}" />
											</div>
											<div class="wc-tp-form-group">
												<label>Method</label>
												<select class="wc-tp-edit-payment-method"></select>
											</div>
											<div class="wc-tp-form-group">
												<label>Note</label>
												<input type="text" class="wc-tp-edit-note" value="${note}" />
											</div>
											<div class="wc-tp-form-group">
												<button type="button" class="button button-primary wc-tp-save-payment-edit" data-payment-id="${paymentId}">Save</button>
												<button type="button" class="button wc-tp-cancel-payment-edit">Cancel</button>
											</div>
										</div>
									</div>
								</td>
							</tr>
						`);

						// Populate method dropdown
						editForm.find('.wc-tp-edit-payment-method').html(methodOptions);
						editForm.find('.wc-tp-edit-payment-method').val(method);

						row.hide();
						row.after(editForm);
					});

					// Save Payment Edit
					$(document).on('click', '.wc-tp-save-payment-edit', function() {
						const paymentId = $(this).data('payment-id');
						const amount = $('.wc-tp-edit-amount').val();
						const date = $('.wc-tp-edit-date').val();
						const method = $('.wc-tp-edit-payment-method').val();
						const note = $('.wc-tp-edit-note').val();

						if (!amount || !date) {
							wcTPToast('Please fill in all fields', 'error');
							return;
						}

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_update_payment',
								user_id: userId,
								payment_id: paymentId,
								amount: amount,
								date: date,
								payment_method: method || '',
								note: note,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									wcTPToast('Payment updated successfully');
									loadPaymentHistory();
									updateStatsCards();
								} else {
									wcTPToast('Failed to update payment: ' + response.data, 'error');
								}
							},
							error: function() {
								wcTPToast('Error updating payment', 'error');
							}
						});
					});

					// Cancel Payment Edit
					$(document).on('click', '.wc-tp-cancel-payment-edit', function() {
						$('.wc-tp-edit-payment-row').remove();
						$('tr').show();
					});

					function loadPaymentMethods() {
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_get_payment_methods',
								user_id: userId,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									allPaymentMethods = response.data.methods;
									paymentMethodsPage = 1;
									renderPaymentMethods(allPaymentMethods);
									populatePaymentMethodDropdown(allPaymentMethods);
								}
							}
						});
					}

					function renderPaymentMethods(methods) {
						const container = $('#wc-tp-payment-methods-container');
						
						if (!methods || methods.length === 0) {
							container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💳</div><p>No payment methods added yet</p></div>');
							return;
						}

						// Sort methods
						methods = sortPaymentMethods(methods, paymentMethodsSortColumn, paymentMethodsSortDirection);

						// Calculate pagination
						const totalPages = Math.ceil(methods.length / paymentMethodsPerPage);
						const startIndex = (paymentMethodsPage - 1) * paymentMethodsPerPage;
						const endIndex = startIndex + paymentMethodsPerPage;
						const paginatedMethods = methods.slice(startIndex, endIndex);

						// Add bulk delete button
						let html = '<div style="margin-bottom: 12px;"><button type="button" class="button wc-tp-bulk-delete-methods" style="display: none;"><span class="dashicons dashicons-trash" style="margin-top: 3px;"></span> Delete Selected</button></div>';
						
						html += '<table class="wc-tp-data-table"><thead><tr>';
						html += '<th style="width: 40px;"><input type="checkbox" class="wc-tp-select-all-methods" title="Select All" /></th>';
						html += '<th class="wc-tp-sortable-header" data-column="method_name" data-table="payment-methods">Method Name' + getMethodSortIcon('method_name') + '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="method_details" data-table="payment-methods">Details' + getMethodSortIcon('method_details') + '</th>';
						html += '<th>Actions</th>';
						html += '</tr></thead><tbody>';
						
						$.each(paginatedMethods, function(i, method) {
							html += '<tr>';
							html += '<td><input type="checkbox" class="wc-tp-method-checkbox" data-method-id="' + method.id + '" /></td>';
							html += '<td><strong>' + method.method_name + '</strong></td>';
							html += '<td>' + method.method_details + '</td>';
							html += '<td><div class="wc-tp-action-icons">';
							html += '<button class="wc-tp-action-icon wc-tp-edit-method" data-method-id="' + method.id + '" title="Edit Method"><span class="dashicons dashicons-edit"></span></button>';
							html += '<button class="wc-tp-action-icon wc-tp-delete-btn wc-tp-delete-method" data-method-id="' + method.id + '" title="Delete Method"><span class="dashicons dashicons-trash"></span></button>';
							html += '</div></td>';
							html += '</tr>';
						});

						html += '</tbody></table>';

						// Add pagination controls
						html += '<div class="wc-tp-pagination">';
						html += '<div class="wc-tp-pagination-info">';
						html += 'Showing ' + (startIndex + 1) + ' to ' + Math.min(endIndex, methods.length) + ' of ' + methods.length + ' methods';
						html += '</div>';
						html += '<div class="wc-tp-pagination-controls">';
						
						if (paymentMethodsPage > 1) {
							html += '<button class="wc-tp-pagination-btn wc-tp-prev-page-method" data-page="' + (paymentMethodsPage - 1) + '"><span class="dashicons dashicons-arrow-left"></span></button>';
						}
						
						for (let i = 1; i <= totalPages; i++) {
							if (i === paymentMethodsPage) {
								html += '<button class="wc-tp-pagination-btn wc-tp-page-btn-method active" data-page="' + i + '">' + i + '</button>';
							} else if (i === 1 || i === totalPages || (i >= paymentMethodsPage - 1 && i <= paymentMethodsPage + 1)) {
								html += '<button class="wc-tp-pagination-btn wc-tp-page-btn-method" data-page="' + i + '">' + i + '</button>';
							} else if (i === 2 || i === totalPages - 1) {
								html += '<span class="wc-tp-pagination-ellipsis">...</span>';
							}
						}
						
						if (paymentMethodsPage < totalPages) {
							html += '<button class="wc-tp-pagination-btn wc-tp-next-page-method" data-page="' + (paymentMethodsPage + 1) + '"><span class="dashicons dashicons-arrow-right"></span></button>';
						}
						
						html += '</div>';
						html += '</div>';

						container.html(html);

						// Reset select all checkbox and hide bulk delete button
						$('.wc-tp-select-all-methods').prop('checked', false);
						$('.wc-tp-bulk-delete-methods').hide();

						// Attach click handlers to sortable headers
						$('.wc-tp-sortable-header[data-table="payment-methods"]').on('click', function() {
							const column = $(this).data('column');
							
							if (paymentMethodsSortColumn === column) {
								paymentMethodsSortDirection = paymentMethodsSortDirection === 'asc' ? 'desc' : 'asc';
							} else {
								paymentMethodsSortColumn = column;
								paymentMethodsSortDirection = 'asc';
							}
							
							paymentMethodsPage = 1;
							renderPaymentMethods(allPaymentMethods);
						});

						// Attach click handlers to pagination buttons
						$('.wc-tp-page-btn-method, .wc-tp-prev-page-method, .wc-tp-next-page-method').on('click', function() {
							paymentMethodsPage = parseInt($(this).data('page'));
							renderPaymentMethods(allPaymentMethods);
						});
					}

					function getMethodSortIcon(column) {
						if (paymentMethodsSortColumn !== column) {
							return '';
						}
						
						const icon = paymentMethodsSortDirection === 'asc' ? 'arrow-up' : 'arrow-down';
						return ' <span class="dashicons dashicons-' + icon + '" style="font-size: 14px; margin-left: 4px;"></span>';
					}

					function sortPaymentMethods(methods, column, direction) {
						const sorted = [...methods].sort((a, b) => {
							let aVal = a[column];
							let bVal = b[column];
							
							if (aVal < bVal) return direction === 'asc' ? -1 : 1;
							if (aVal > bVal) return direction === 'asc' ? 1 : -1;
							return 0;
						});
						
						return sorted;
					}

					function populatePaymentMethodDropdown(methods) {
						const dropdown = $('#wc-tp-payment-method');
						dropdown.find('option:not(:first)').remove();

						if (methods && methods.length > 0) {
							$.each(methods, function(i, method) {
								dropdown.append('<option value="' + method.method_name + '">' + method.method_name + '</option>');
							});
						}
					}

					function loadPaymentHistory() {
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_get_employee_payments',
								user_id: userId,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									allPayments = response.data.payments;
									paymentHistoryPage = 1;
									renderPaymentHistory(allPayments);
								}
							}
						});
					}

					function renderPaymentHistory(payments) {
						const container = $('#wc-tp-payment-history-container');
						
						if (!payments || payments.length === 0) {
							container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">💰</div><p>No payments yet</p></div>');
							return;
						}

						// Sort payments
						payments = sortPayments(payments, paymentHistorySortColumn, paymentHistorySortDirection);

						// Calculate pagination
						const totalPages = Math.ceil(payments.length / paymentHistoryPerPage);
						const startIndex = (paymentHistoryPage - 1) * paymentHistoryPerPage;
						const endIndex = startIndex + paymentHistoryPerPage;
						const paginatedPayments = payments.slice(startIndex, endIndex);

						// Add bulk delete button
						let html = '<div style="margin-bottom: 12px;"><button type="button" class="button wc-tp-bulk-delete-payments" style="display: none;"><span class="dashicons dashicons-trash" style="margin-top: 3px;"></span> Delete Selected</button></div>';
						
						html += '<table class="wc-tp-data-table"><thead><tr>';
						html += '<th style="width: 40px;"><input type="checkbox" class="wc-tp-select-all-payments" title="Select All" /></th>';
						html += '<th class="wc-tp-sortable-header" data-column="amount" data-table="payment-history">Amount' + getPaymentSortIcon('amount') + '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="date" data-table="payment-history">Date' + getPaymentSortIcon('date') + '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="payment_method" data-table="payment-history">Method' + getPaymentSortIcon('payment_method') + '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="note" data-table="payment-history">Note' + getPaymentSortIcon('note') + '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="added_by_name" data-table="payment-history">Added By' + getPaymentSortIcon('added_by_name') + '</th>';
						html += '<th>Actions</th>';
						html += '</tr></thead><tbody>';

						$.each(paginatedPayments, function(i, payment) {
							// Format date properly (replace T with space)
							const formattedDate = payment.date ? payment.date.replace('T', ' ') : '-';
							const rawDate = payment.date || '';
							
							// Format user info with link and tooltip
							let userHtml = 'System';
							if (payment.added_by_id && payment.added_by_name) {
								const tooltip = 'Email: ' + (payment.added_by_email || 'N/A') + '\nRole: ' + (payment.added_by_role || 'N/A');
								const userEditUrl = '<?php echo admin_url("user-edit.php"); ?>?user_id=' + payment.added_by_id;
								userHtml = '<a href="' + userEditUrl + '" title="' + tooltip + '" style="text-decoration: none; color: #0073aa;">' + payment.added_by_name + '</a>';
							}

							html += '<tr>';
							html += '<td><input type="checkbox" class="wc-tp-payment-checkbox" data-payment-id="' + payment.id + '" /></td>';
							html += '<td class="wc-tp-payment-amount" data-amount="' + payment.amount + '"><strong>' + formatCurrency(payment.amount) + '</strong></td>';
							html += '<td class="wc-tp-payment-date" data-date="' + rawDate + '">' + formattedDate + '</td>';
							html += '<td class="wc-tp-payment-method" data-method="' + (payment.payment_method || '') + '">' + (payment.payment_method || '-') + '</td>';
							html += '<td class="wc-tp-payment-note" data-note="' + (payment.note || '') + '">' + (payment.note || '-') + '</td>';
							html += '<td>' + userHtml + '</td>';
							html += '<td><div class="wc-tp-action-icons">';
							html += '<button class="wc-tp-action-icon wc-tp-edit-payment" data-payment-id="' + payment.id + '" title="Edit Payment"><span class="dashicons dashicons-edit"></span></button>';
							html += '<button class="wc-tp-action-icon wc-tp-delete-payment" data-payment-id="' + payment.id + '" title="Delete Payment"><span class="dashicons dashicons-trash"></span></button>';
							html += '</div></td>';
							html += '</tr>';
						});

						html += '</tbody></table>';

						// Add pagination controls
						html += '<div class="wc-tp-pagination">';
						html += '<div class="wc-tp-pagination-info">';
						html += 'Showing ' + (startIndex + 1) + ' to ' + Math.min(endIndex, payments.length) + ' of ' + payments.length + ' payments';
						html += '</div>';
						html += '<div class="wc-tp-pagination-controls">';
						
						if (paymentHistoryPage > 1) {
							html += '<button class="wc-tp-pagination-btn wc-tp-prev-page-payment" data-page="' + (paymentHistoryPage - 1) + '"><span class="dashicons dashicons-arrow-left"></span></button>';
						}
						
						for (let i = 1; i <= totalPages; i++) {
							if (i === paymentHistoryPage) {
								html += '<button class="wc-tp-pagination-btn wc-tp-page-btn-payment active" data-page="' + i + '">' + i + '</button>';
							} else if (i === 1 || i === totalPages || (i >= paymentHistoryPage - 1 && i <= paymentHistoryPage + 1)) {
								html += '<button class="wc-tp-pagination-btn wc-tp-page-btn-payment" data-page="' + i + '">' + i + '</button>';
							} else if (i === 2 || i === totalPages - 1) {
								html += '<span class="wc-tp-pagination-ellipsis">...</span>';
							}
						}
						
						if (paymentHistoryPage < totalPages) {
							html += '<button class="wc-tp-pagination-btn wc-tp-next-page-payment" data-page="' + (paymentHistoryPage + 1) + '"><span class="dashicons dashicons-arrow-right"></span></button>';
						}
						
						html += '</div>';
						html += '</div>';

						container.html(html);

						// Reset select all checkbox and hide bulk delete button
						$('.wc-tp-select-all-payments').prop('checked', false);
						$('.wc-tp-bulk-delete-payments').hide();

						// Attach click handlers to sortable headers
						$('.wc-tp-sortable-header[data-table="payment-history"]').on('click', function() {
							const column = $(this).data('column');
							
							if (paymentHistorySortColumn === column) {
								paymentHistorySortDirection = paymentHistorySortDirection === 'asc' ? 'desc' : 'asc';
							} else {
								paymentHistorySortColumn = column;
								paymentHistorySortDirection = 'desc';
							}
							
							paymentHistoryPage = 1;
							renderPaymentHistory(allPayments);
						});

						// Attach click handlers to pagination buttons
						$('.wc-tp-page-btn-payment, .wc-tp-prev-page-payment, .wc-tp-next-page-payment').on('click', function() {
							paymentHistoryPage = parseInt($(this).data('page'));
							renderPaymentHistory(allPayments);
						});
					}

					function getPaymentSortIcon(column) {
						if (paymentHistorySortColumn !== column) {
							return '';
						}
						
						const icon = paymentHistorySortDirection === 'asc' ? 'arrow-up' : 'arrow-down';
						return ' <span class="dashicons dashicons-' + icon + '" style="font-size: 14px; margin-left: 4px;"></span>';
					}

					function sortPayments(payments, column, direction) {
						const sorted = [...payments].sort((a, b) => {
							let aVal = a[column];
							let bVal = b[column];
							
							// Handle numeric values
							if (typeof aVal === 'string' && !isNaN(aVal)) {
								aVal = parseFloat(aVal);
								bVal = parseFloat(bVal);
							}
							
							if (aVal < bVal) return direction === 'asc' ? -1 : 1;
							if (aVal > bVal) return direction === 'asc' ? 1 : -1;
							return 0;
						});
						
						return sorted;
					}

					function updateStatsCards() {
						// Reload stats from server
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_get_employee_stats',
								user_id: userId,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									const stats = response.data;
									$('.wc-tp-stat-card').eq(1).find('.wc-tp-stat-value').html(formatCurrency(stats.total_earnings));
									$('.wc-tp-stat-card').eq(2).find('.wc-tp-stat-value').html(formatCurrency(stats.total_paid));
									$('.wc-tp-stat-card').eq(3).find('.wc-tp-stat-value').html(formatCurrency(stats.total_due));
								}
							}
						});
					}

					function formatCurrency(value) {
						return '<?php echo get_woocommerce_currency_symbol(); ?>' + parseFloat(value).toFixed(2);
					}
				}

				// ============================================================================
				// SALARY TAB
				// ============================================================================
				if ($('.wc-tp-salary-tab').length) {
					// Salary History Pagination
					let salarySortColumn = 'date';
					let salarySortDirection = 'desc';
					let salaryPage = 1;
					let salaryPerPage = 10;
					let allSalaryHistory = [];

					// Store original values for change detection
					let originalSalaryType = $('#wc-tp-salary-type').val();
					let originalSalaryAmount = $('#wc-tp-salary-amount').val();
					let originalSalaryFrequency = $('#wc-tp-salary-frequency').val();

					loadSalaryHistory();

					// Function to check if form has changes
					function hasFormChanged() {
						const currentType = $('#wc-tp-salary-type').val();
						const currentAmount = $('#wc-tp-salary-amount').val();
						const currentFrequency = $('#wc-tp-salary-frequency').val();

						return (
							currentType !== originalSalaryType ||
							currentAmount !== originalSalaryAmount ||
							currentFrequency !== originalSalaryFrequency
						);
					}

					// Function to update button state
					function updateButtonState() {
						const $button = $('#wc-tp-salary-form button[type="submit"]');
						const hasChanged = hasFormChanged();

						if (hasChanged) {
							$button.prop('disabled', false).removeClass('wc-tp-button-disabled');
						} else {
							$button.prop('disabled', true).addClass('wc-tp-button-disabled');
						}
					}

					// Initialize button state
					updateButtonState();

					// Salary History Screen Options
					$('#wc-tp-salary-history-per-page').on('change', function() {
						salaryPerPage = parseInt($(this).val());
						salaryPage = 1;
						renderSalaryHistory(allSalaryHistory);
					});

					// Salary Type Change - Show/Hide Amount and Frequency
					$('#wc-tp-salary-type').on('change', function() {
						const salaryType = $(this).val();
						
						if (salaryType === 'commission') {
							$('.wc-tp-salary-amount-group, .wc-tp-salary-frequency-group').slideUp(200);
							$('#wc-tp-salary-amount').val('');
						} else {
							$('.wc-tp-salary-amount-group, .wc-tp-salary-frequency-group').slideDown(200);
							$('#wc-tp-salary-amount').val('');
						}

						// Update button state on type change
						updateButtonState();
					});

					// Update button state on amount change
					$('#wc-tp-salary-amount').on('change input', function() {
						updateButtonState();
					});

					// Update button state on frequency change
					$('#wc-tp-salary-frequency').on('change', function() {
						updateButtonState();
					});

					// Salary Form Submit
					$('#wc-tp-salary-form').on('submit', function(e) {
						e.preventDefault();

						const salaryType = $('#wc-tp-salary-type').val();
						const salaryAmount = $('#wc-tp-salary-amount').val();
						const salaryFrequency = $('#wc-tp-salary-frequency').val();

						if (salaryType !== 'commission' && (!salaryAmount || salaryAmount <= 0)) {
							wcTPToast('Please enter a valid salary amount', 'error');
							return;
						}

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_update_employee_salary',
								user_id: userId,
								salary_type: salaryType,
								salary_amount: salaryAmount,
								salary_frequency: salaryFrequency,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									wcTPToast('Salary updated successfully');
									
									// Update original values after successful save
									originalSalaryType = salaryType;
									originalSalaryAmount = salaryAmount;
									originalSalaryFrequency = salaryFrequency;
									
									// Reset button state
									updateButtonState();
									
									loadSalaryHistory();
								} else {
									wcTPToast('Failed to update salary: ' + response.data, 'error');
								}
							},
							error: function() {
								wcTPToast('Error updating salary', 'error');
							}
						});
					});

					function loadSalaryHistory() {
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'wc_tp_get_salary_history',
								user_id: userId,
								nonce: nonce
							},
							success: function(response) {
								if (response.success) {
									allSalaryHistory = response.data.history;
									salaryPage = 1;
									renderSalaryHistory(allSalaryHistory);
								}
							}
						});
					}

					function renderSalaryHistory(history) {
						const container = $('#wc-tp-salary-history-container');
						
						if (!history || history.length === 0) {
							container.html('<div class="wc-tp-empty-state"><div class="wc-tp-empty-icon">📊</div><p>No salary history yet</p></div>');
							return;
						}

						// Sort history
						history = sortSalaryHistory(history, salarySortColumn, salarySortDirection);

						// Calculate pagination
						const totalPages = Math.ceil(history.length / salaryPerPage);
						const startIndex = (salaryPage - 1) * salaryPerPage;
						const endIndex = startIndex + salaryPerPage;
						const paginatedHistory = history.slice(startIndex, endIndex);

						let html = '<table class="wc-tp-data-table"><thead><tr>';
						html += '<th class="wc-tp-sortable-header" data-column="date" data-table="salary-history">Date' + getSalarySortIcon('date') + '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="old_type" data-table="salary-history">Old Type' + getSalarySortIcon('old_type') + '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="new_type" data-table="salary-history">New Type' + getSalarySortIcon('new_type') + '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="old_amount" data-table="salary-history">Old Amount' + getSalarySortIcon('old_amount') + '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="new_amount" data-table="salary-history">New Amount' + getSalarySortIcon('new_amount') + '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="new_frequency" data-table="salary-history">Frequency' + getSalarySortIcon('new_frequency') + '</th>';
						html += '<th class="wc-tp-sortable-header" data-column="changed_by_name" data-table="salary-history">Changed By' + getSalarySortIcon('changed_by_name') + '</th>';
						html += '</tr></thead><tbody>';

						$.each(paginatedHistory, function(i, entry) {
							// Format date properly (replace T with space)
							const formattedDate = entry.date ? entry.date.replace('T', ' ') : '-';
							
							// Format user info with link and tooltip
							let userHtml = 'System';
							if (entry.changed_by_id && entry.changed_by_name) {
								const tooltip = 'Email: ' + (entry.changed_by_email || 'N/A') + '\nRole: ' + (entry.changed_by_role || 'N/A');
								const userEditUrl = '<?php echo admin_url("user-edit.php"); ?>?user_id=' + entry.changed_by_id;
								userHtml = '<a href="' + userEditUrl + '" title="' + tooltip + '" style="text-decoration: none; color: #0073aa;">' + entry.changed_by_name + '</a>';
							}
							
							html += '<tr>';
							html += '<td>' + formattedDate + '</td>';
							html += '<td>' + formatSalaryType(entry.old_type) + '</td>';
							html += '<td><strong>' + formatSalaryType(entry.new_type) + '</strong></td>';
							html += '<td>' + (entry.old_amount ? formatCurrency(entry.old_amount) : '-') + '</td>';
							html += '<td><strong>' + (entry.new_amount ? formatCurrency(entry.new_amount) : '-') + '</strong></td>';
							html += '<td>' + (entry.new_frequency ? entry.new_frequency : '-') + '</td>';
							html += '<td>' + userHtml + '</td>';
							html += '</tr>';
						});

						html += '</tbody></table>';

						// Add pagination controls
						html += '<div class="wc-tp-pagination">';
						html += '<div class="wc-tp-pagination-info">';
						html += 'Showing ' + (startIndex + 1) + ' to ' + Math.min(endIndex, history.length) + ' of ' + history.length + ' entries';
						html += '</div>';
						html += '<div class="wc-tp-pagination-controls">';
						
						if (salaryPage > 1) {
							html += '<button class="wc-tp-pagination-btn wc-tp-prev-page-salary" data-page="' + (salaryPage - 1) + '"><span class="dashicons dashicons-arrow-left"></span></button>';
						}
						
						for (let i = 1; i <= totalPages; i++) {
							if (i === salaryPage) {
								html += '<button class="wc-tp-pagination-btn wc-tp-page-btn-salary active" data-page="' + i + '">' + i + '</button>';
							} else if (i === 1 || i === totalPages || (i >= salaryPage - 1 && i <= salaryPage + 1)) {
								html += '<button class="wc-tp-pagination-btn wc-tp-page-btn-salary" data-page="' + i + '">' + i + '</button>';
							} else if (i === 2 || i === totalPages - 1) {
								html += '<span class="wc-tp-pagination-ellipsis">...</span>';
							}
						}
						
						if (salaryPage < totalPages) {
							html += '<button class="wc-tp-pagination-btn wc-tp-next-page-salary" data-page="' + (salaryPage + 1) + '"><span class="dashicons dashicons-arrow-right"></span></button>';
						}
						
						html += '</div>';
						html += '</div>';

						container.html(html);

						// Attach click handlers to sortable headers
						$('.wc-tp-sortable-header[data-table="salary-history"]').on('click', function() {
							const column = $(this).data('column');
							
							if (salarySortColumn === column) {
								salarySortDirection = salarySortDirection === 'asc' ? 'desc' : 'asc';
							} else {
								salarySortColumn = column;
								salarySortDirection = 'desc';
							}
							
							salaryPage = 1;
							renderSalaryHistory(allSalaryHistory);
						});

						// Attach click handlers to pagination buttons
						$('.wc-tp-page-btn-salary, .wc-tp-prev-page-salary, .wc-tp-next-page-salary').on('click', function() {
							salaryPage = parseInt($(this).data('page'));
							renderSalaryHistory(allSalaryHistory);
						});
					}

					function getSalarySortIcon(column) {
						if (salarySortColumn !== column) {
							return '';
						}
						
						const icon = salarySortDirection === 'asc' ? 'arrow-up' : 'arrow-down';
						return ' <span class="dashicons dashicons-' + icon + '" style="font-size: 14px; margin-left: 4px;"></span>';
					}

					function sortSalaryHistory(history, column, direction) {
						const sorted = [...history].sort((a, b) => {
							let aVal = a[column];
							let bVal = b[column];
							
							// Handle numeric values
							if (typeof aVal === 'string' && !isNaN(aVal)) {
								aVal = parseFloat(aVal);
								bVal = parseFloat(bVal);
							}
							
							if (aVal < bVal) return direction === 'asc' ? -1 : 1;
							if (aVal > bVal) return direction === 'asc' ? 1 : -1;
							return 0;
						});
						
						return sorted;
					}

					function formatSalaryType(type) {
						const types = {
							'commission': 'Commission Based',
							'fixed': 'Fixed Salary',
							'combined': 'Combined (Base + Commission)'
						};
						return types[type] || type;
					}

					function formatCurrency(value) {
						return '<?php echo get_woocommerce_currency_symbol(); ?>' + parseFloat(value).toFixed(2);
					}
				}
			});
		</script>
		<?php
	}

	/**
	 * Render Performance Tab
	 */
	private function render_performance_tab( $user_id ) {
		// Enqueue Phosphor Icons
		wp_enqueue_script(
			'phosphor-icons',
			'https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2',
			array(),
			'2.1.2',
			true
		);

		// Enqueue shared CSS (for common components)
		wp_enqueue_style(
			'wc-team-payroll-shared',
			WC_TEAM_PAYROLL_URL . 'assets/css/myaccount-shared.css',
			array(),
			WC_TEAM_PAYROLL_VERSION
		);

		// Enqueue Performance Tracker CSS
		wp_enqueue_style(
			'wc-team-payroll-performance-tracker',
			WC_TEAM_PAYROLL_URL . 'assets/css/performance-tracker.css',
			array( 'wc-team-payroll-shared' ),
			WC_TEAM_PAYROLL_VERSION
		);

		// Enqueue Performance Tracker JavaScript
		wp_enqueue_script(
			'wc-team-payroll-performance-tracker',
			WC_TEAM_PAYROLL_URL . 'assets/js/performance-tracker.js',
			array( 'jquery' ),
			WC_TEAM_PAYROLL_VERSION,
			true
		);

		// Localize script with necessary data for AJAX calls
		wp_localize_script(
			'wc-team-payroll-performance-tracker',
			'wc_tp_reports',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'wc_team_payroll_nonce' ),
				'currency_symbol' => get_woocommerce_currency_symbol(),
				'currency_pos'    => get_option( 'woocommerce_currency_pos', 'left' ),
			)
		);

		?>
		<div class="wc-tp-performance-tab">
			<!-- Performance Tracker Section -->
			<div class="performance-tracker-wrapper pv-section-wrapper" id="performance-tracker-container">
				<!-- Performance Header -->
				<div class="performance-header">
					<h3><?php esc_html_e( 'Performance Tracker', 'wc-team-payroll' ); ?></h3>
					<select id="performance-view-selector">
						<option value="current"><?php esc_html_e( 'Current Period', 'wc-team-payroll' ); ?></option>
					</select>
				</div>

				<!-- Performance Tabs -->
				<div class="performance-tabs">
					<button class="performance-tab active" data-tab="overview">
						<i class="ph ph-chart-line"></i> <?php esc_html_e( 'Overview', 'wc-team-payroll' ); ?>
					</button>
					<button class="performance-tab" data-tab="goals">
						<i class="ph ph-target"></i> <?php esc_html_e( 'Goals', 'wc-team-payroll' ); ?>
					</button>
					<button class="performance-tab" data-tab="achievements">
						<i class="ph ph-trophy"></i> <?php esc_html_e( 'Achievements', 'wc-team-payroll' ); ?>
					</button>
					<button class="performance-tab" data-tab="baselines">
						<i class="ph ph-chart-line-up"></i> <?php esc_html_e( 'Baselines', 'wc-team-payroll' ); ?>
					</button>
				</div>

				<!-- Performance Content -->
				<div id="performance-content">
					<div class="performance-loading">
						<i class="ph ph-spinner ph-spin"></i>
						<p><?php esc_html_e( 'Loading performance data...', 'wc-team-payroll' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<input type="hidden" id="wc-tp-current-user-id" value="<?php echo esc_attr( $user_id ); ?>" />
		<?php
		wp_nonce_field( 'wc_team_payroll_nonce', 'wc_team_payroll_nonce' );
	}
}
