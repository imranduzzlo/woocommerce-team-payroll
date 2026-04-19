<?php
/**
 * My Account Integration - Clean Implementation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_MyAccount {

	/**
	 * Custom endpoint names
	 */
	public static $endpoints = array(
		'salary-details'     => 'salary-details',
		'my-earnings'        => 'my-earnings', 
		'orders-commission'  => 'orders-commission',
		'reports'            => 'reports'
	);

	/**
	 * Initialize the class
	 */
	public static function init() {
		// Register endpoints
		add_action( 'init', array( __CLASS__, 'add_endpoints' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ), 0 );

		// Add menu items
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_items' ) );

		// Add endpoint content
		add_action( 'woocommerce_account_salary-details_endpoint', array( __CLASS__, 'salary_details_content' ) );
		add_action( 'woocommerce_account_my-earnings_endpoint', array( __CLASS__, 'my_earnings_content' ) );
		add_action( 'woocommerce_account_orders-commission_endpoint', array( __CLASS__, 'orders_commission_content' ) );
		add_action( 'woocommerce_account_reports_endpoint', array( __CLASS__, 'reports_content' ) );

		// Register AJAX handlers
		add_action( 'wp_ajax_wc_tp_get_earnings_data', array( __CLASS__, 'ajax_get_earnings_data' ) );
		add_action( 'wp_ajax_wc_tp_get_myaccount_orders', array( __CLASS__, 'ajax_get_orders' ) );
		add_action( 'wp_ajax_wc_tp_get_order_details', array( __CLASS__, 'ajax_get_order_details' ) );
		add_action( 'wp_ajax_wc_tp_get_filtered_dashboard_data', array( __CLASS__, 'ajax_get_filtered_dashboard_data' ) );
		add_action( 'wp_ajax_wc_tp_get_filtered_analytics_data', array( __CLASS__, 'ajax_get_filtered_analytics_data' ) );
		add_action( 'wp_ajax_wc_tp_get_filtered_performance_data', array( __CLASS__, 'ajax_get_filtered_performance_data' ) );
		add_action( 'wp_ajax_wc_tp_get_filtered_table_data', array( __CLASS__, 'ajax_get_filtered_table_data' ) );
		add_action( 'wp_ajax_wc_tp_get_filtered_goals_data', array( __CLASS__, 'ajax_get_filtered_goals_data' ) );
		add_action( 'wp_ajax_wc_tp_get_attributed_order_total', array( __CLASS__, 'ajax_get_attributed_order_total' ) );
		add_action( 'wp_ajax_wc_tp_export_filtered_report', array( __CLASS__, 'ajax_export_filtered_report' ) );
		// Performance Tracker AJAX is handled by WC_Team_Payroll_Performance_Tracker_AJAX class

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Register new endpoints
	 */
	public static function add_endpoints() {
		foreach ( self::$endpoints as $endpoint ) {
			add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES );
		}
	}

	/**
	 * Add query vars
	 */
	public static function add_query_vars( $vars ) {
		foreach ( self::$endpoints as $endpoint ) {
			$vars[] = $endpoint;
		}
		return $vars;
	}

	/**
	 * Add menu items
	 */
	public static function add_menu_items( $items ) {
		// Remove logout temporarily
		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );

		// Add our custom items (plain text only - icons added via JavaScript)
		$items['salary-details'] = __( 'Salary Details', 'wc-team-payroll' );
		$items['my-earnings'] = __( 'My Earnings', 'wc-team-payroll' );
		$items['orders-commission'] = __( 'My Orders (Commission)', 'wc-team-payroll' );
		$items['reports'] = __( 'Reports', 'wc-team-payroll' );

		// Add logout back
		$items['customer-logout'] = $logout;

		return $items;
	}

	/**
	 * Salary Details content
	 */
	public static function salary_details_content() {
		$user_id = get_current_user_id();
		
		// Get salary information - check for fixed/combined flags first
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		
		if ( $is_fixed_salary ) {
			$salary_type = 'fixed';
		} elseif ( $is_combined_salary ) {
			$salary_type = 'combined';
		} else {
			$salary_type = get_user_meta( $user_id, '_wc_tp_salary_type', true ) ?: 'commission';
		}
		
		$salary_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ?: 0;
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ?: 'monthly';
		
		// Get salary history
		$salary_history = get_user_meta( $user_id, '_wc_tp_salary_history', true );
		if ( ! is_array( $salary_history ) ) {
			$salary_history = array();
		}
		
		// Get payment methods
		$payment_methods = get_user_meta( $user_id, '_wc_tp_payment_methods', true );
		if ( ! is_array( $payment_methods ) ) {
			$payment_methods = array();
		}
		
		// Determine salary type labels
		$is_fixed = ( $salary_type === 'fixed' );
		$is_combined = ( $salary_type === 'combined' );
		$is_commission = ( $salary_type === 'commission' );
		
		?>
		<div class="pv-page-wrapper wc-team-payroll-salary-details">
			<!-- Employee Header -->
			<?php echo self::get_employee_header( $user_id ); ?>

			<!-- Salary Information -->
			<div class="pv-section-wrapper salary-info-section">
				<h3><?php esc_html_e( 'Salary Information', 'wc-team-payroll' ); ?></h3>
				<div class="pv-card salary-info-card">
					<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
						<div class="salary-type-badge salary-type-<?php echo esc_attr( $salary_type ); ?>">
							<?php
							if ( $is_fixed ) {
								echo '<i class="ph ph-coins"></i> ' . esc_html__( 'Fixed Salary', 'wc-team-payroll' );
							} elseif ( $is_combined ) {
								echo '<i class="ph ph-chart-line-up"></i> ' . esc_html__( 'Combined (Base + Commission)', 'wc-team-payroll' );
							} else {
								echo '<i class="ph ph-percent"></i> ' . esc_html__( 'Commission Based', 'wc-team-payroll' );
							}
							?>
						</div>
						
						<!-- Salary Display in Top Right -->
						<div style="text-align: right;">
							<?php if ( $is_fixed || $is_combined ) : ?>
								<div style="font-size: 24px; font-weight: 700; color: #28a745; margin-bottom: 5px;">
									<?php echo wp_kses_post( wc_price( $salary_amount ) ); ?>
								</div>
								<div style="font-size: 13px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">
									<?php
									$frequency_labels = array(
										'daily'   => __( 'Per Day', 'wc-team-payroll' ),
										'weekly'  => __( 'Per Week', 'wc-team-payroll' ),
										'monthly' => __( 'Per Month', 'wc-team-payroll' ),
										'yearly'  => __( 'Per Year', 'wc-team-payroll' ),
									);
									echo esc_html( $frequency_labels[ $salary_frequency ] ?? ucfirst( $salary_frequency ) );
									?>
								</div>
							<?php elseif ( $is_commission ) : ?>
								<div style="font-size: 13px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">
									<?php esc_html_e( 'Percentage/Order', 'wc-team-payroll' ); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
					
					<?php if ( $is_combined ) : ?>
						<div class="salary-type-note">
							<i class="ph ph-info"></i>
							<span><?php esc_html_e( 'You also earn commission from orders in addition to your base salary.', 'wc-team-payroll' ); ?></span>
						</div>
					<?php elseif ( $is_commission ) : ?>
						<div class="salary-type-note">
							<i class="ph ph-info"></i>
							<span><?php esc_html_e( 'Your earnings are based entirely on commission from orders you process.', 'wc-team-payroll' ); ?></span>
						</div>
					<?php elseif ( $is_fixed ) : ?>
						<div class="salary-type-note">
							<i class="ph ph-info"></i>
							<span><?php esc_html_e( 'You receive a fixed salary as shown above.', 'wc-team-payroll' ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Payment Methods -->
			<?php if ( ! empty( $payment_methods ) ) : ?>
				<div class="pv-section-wrapper payment-methods-section">
					<h3><?php esc_html_e( 'Payment Methods', 'wc-team-payroll' ); ?></h3>
					<div class="payment-methods-grid">
						<?php foreach ( $payment_methods as $method ) : ?>
							<div class="pv-card payment-method-card">
								<div class="method-header">
									<i class="ph ph-bank"></i>
									<span class="method-name"><?php echo esc_html( $method['method_name'] ); ?></span>
								</div>
								<div class="method-details">
									<?php echo esc_html( $method['method_details'] ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Salary History -->
			<?php if ( ! empty( $salary_history ) ) : ?>
				<div class="pv-section-wrapper salary-history-section">
					<h3><?php esc_html_e( 'Salary Change History', 'wc-team-payroll' ); ?></h3>
					<div class="table-wrapper">
						<div class="section-header">
							<div class="pv-table-controls table-controls">
								<div class="search-control">
									<input type="text" id="salary-history-search" placeholder="<?php esc_attr_e( 'Search history...', 'wc-team-payroll' ); ?>" />
									<i class="ph ph-magnifying-glass"></i>
								</div>
								<div class="per-page-control">
									<label for="salary-history-per-page"><?php esc_html_e( 'Show:', 'wc-team-payroll' ); ?></label>
									<select id="salary-history-per-page">
										<option value="5">5</option>
										<option value="10" selected>10</option>
										<option value="25">25</option>
										<option value="50">50</option>
									</select>
									<span><?php esc_html_e( 'per page', 'wc-team-payroll' ); ?></span>
								</div>
							</div>
						</div>
						
						<div class="table-container pv-table-container">
							<table class="pv-table woocommerce-table woocommerce-table--salary-history" id="salary-history-table">
								<thead>
									<tr>
										<th class="sortable" data-sort="date">
											<?php esc_html_e( 'Date', 'wc-team-payroll' ); ?>
											<i class="ph ph-caret-up-down sort-icon"></i>
										</th>
										<th class="sortable" data-sort="old_type">
											<?php esc_html_e( 'Previous', 'wc-team-payroll' ); ?>
											<i class="ph ph-caret-up-down sort-icon"></i>
										</th>
										<th class="sortable" data-sort="new_type">
											<?php esc_html_e( 'New', 'wc-team-payroll' ); ?>
											<i class="ph ph-caret-up-down sort-icon"></i>
										</th>
										<th class="sortable" data-sort="changed_by">
											<?php esc_html_e( 'Changed By', 'wc-team-payroll' ); ?>
											<i class="ph ph-caret-up-down sort-icon"></i>
										</th>
									</tr>
								</thead>
								<tbody id="salary-history-tbody">
									<?php foreach ( array_reverse( $salary_history ) as $index => $history ) : 
										$changed_by_id = isset( $history['changed_by'] ) ? $history['changed_by'] : 0;
										$changed_by_user = $changed_by_id ? get_user_by( 'id', $changed_by_id ) : null;
										
										// Get frequency abbreviations
										$frequency_abbr = array(
											'daily'   => 'dy',
											'weekly'  => 'wk',
											'monthly' => 'mn',
											'yearly'  => 'yr',
										);
									?>
										<tr data-index="<?php echo esc_attr( $index ); ?>">
											<td data-sort-value="<?php echo esc_attr( strtotime( $history['date'] ) ); ?>">
												<span class="date"><?php echo esc_html( date( 'M j, Y', strtotime( $history['date'] ) ) ); ?></span>
												<small><?php echo esc_html( date( 'g:i A', strtotime( $history['date'] ) ) ); ?></small>
											</td>
											<td data-sort-value="<?php echo esc_attr( $history['old_type'] ); ?>">
												<div class="salary-change">
													<span class="type"><?php echo esc_html( ucfirst( $history['old_type'] ) ); ?></span>
													<?php if ( $history['old_type'] === 'commission' ) : ?>
														<span class="amount"><?php esc_html_e( '%/order', 'wc-team-payroll' ); ?></span>
													<?php elseif ( isset( $history['old_amount'] ) && $history['old_amount'] > 0 ) : ?>
														<?php 
															$old_freq = isset( $history['old_frequency'] ) ? $history['old_frequency'] : 'monthly';
															$abbr = $frequency_abbr[ $old_freq ] ?? 'mn';
														?>
														<span class="amount"><?php echo wp_kses_post( wc_price( $history['old_amount'] ) ); ?>/<span style="font-size: 12px;"><?php echo esc_html( $abbr ); ?></span></span>
													<?php endif; ?>
												</div>
											</td>
											<td data-sort-value="<?php echo esc_attr( $history['new_type'] ); ?>">
												<div class="salary-change">
													<span class="type"><?php echo esc_html( ucfirst( $history['new_type'] ) ); ?></span>
													<?php if ( $history['new_type'] === 'commission' ) : ?>
														<span class="amount"><?php esc_html_e( '%/order', 'wc-team-payroll' ); ?></span>
													<?php elseif ( isset( $history['new_amount'] ) && $history['new_amount'] > 0 ) : ?>
														<?php 
															$new_freq = isset( $history['new_frequency'] ) ? $history['new_frequency'] : 'monthly';
															$abbr = $frequency_abbr[ $new_freq ] ?? 'mn';
														?>
														<span class="amount"><?php echo wp_kses_post( wc_price( $history['new_amount'] ) ); ?>/<span style="font-size: 12px;"><?php echo esc_html( $abbr ); ?></span></span>
													<?php endif; ?>
												</div>
											</td>
											<td data-sort-value="<?php echo esc_attr( $changed_by_user ? $changed_by_user->display_name : 'System' ); ?>">
												<?php if ( $changed_by_user ) : ?>
													<span class="changed-by"><?php echo esc_html( $changed_by_user->display_name ); ?></span>
													<small><?php echo esc_html( $changed_by_user->user_email ); ?></small>
												<?php else : ?>
													<span class="changed-by"><?php esc_html_e( 'System', 'wc-team-payroll' ); ?></span>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
					
					<!-- Pagination -->
					<div class="pagination-container" id="salary-history-pagination">
						<!-- Pagination will be inserted here by JavaScript -->
					</div>
				</div>
				
				<script>
					jQuery(document).ready(function($) {
						// Salary History Table Management
						let currentPage = 1;
						let perPage = 10;
						let currentSort = { column: 'date', direction: 'desc' };
						let searchTerm = '';
						let allRows = [];
						
						// Initialize table
						function initSalaryHistoryTable() {
							// Store all rows
							$('#salary-history-tbody tr').each(function() {
								allRows.push({
									element: $(this).clone(),
									data: {
										date: $(this).find('td').eq(0).data('sort-value'),
										old_type: $(this).find('td').eq(1).data('sort-value'),
										new_type: $(this).find('td').eq(2).data('sort-value'),
										changed_by: $(this).find('td').eq(3).data('sort-value'),
										text: $(this).text().toLowerCase()
									}
								});
							});
							
							updateTable();
						}
						
						// Update table display
						function updateTable() {
							let filteredRows = allRows.slice();
							
							// Apply search filter
							if (searchTerm) {
								filteredRows = filteredRows.filter(row => 
									row.data.text.includes(searchTerm.toLowerCase())
								);
							}
							
							// Apply sorting
							filteredRows.sort((a, b) => {
								let aVal = a.data[currentSort.column];
								let bVal = b.data[currentSort.column];
								
								if (typeof aVal === 'string') {
									aVal = aVal.toLowerCase();
									bVal = bVal.toLowerCase();
								}
								
								if (currentSort.direction === 'asc') {
									return aVal > bVal ? 1 : -1;
								} else {
									return aVal < bVal ? 1 : -1;
								}
							});
							
							// Calculate pagination
							const totalRows = filteredRows.length;
							const totalPages = Math.ceil(totalRows / perPage);
							const startIndex = (currentPage - 1) * perPage;
							const endIndex = startIndex + perPage;
							const pageRows = filteredRows.slice(startIndex, endIndex);
							
							// Update table body
							const tbody = $('#salary-history-tbody');
							tbody.empty();
							
							if (pageRows.length === 0) {
								tbody.append(`
									<tr>
										<td colspan="4" class="no-results">
											<div class="no-results-message">
												<i class="ph ph-magnifying-glass"></i>
												<p><?php esc_html_e( 'No salary history found matching your search.', 'wc-team-payroll' ); ?></p>
											</div>
										</td>
									</tr>
								`);
							} else {
								pageRows.forEach(row => {
									tbody.append(row.element);
								});
							}
							
							// Update pagination
							updatePagination(totalPages, totalRows, startIndex + 1, Math.min(endIndex, totalRows));
							
							// Update sort icons
							updateSortIcons();
						}
						
						// Update pagination
						function updatePagination(totalPages, totalRows, start, end) {
							const container = $('#salary-history-pagination');
							
							if (totalPages <= 1) {
								container.html('');
								return;
							}
							
							let paginationHTML = '<div class="pagination-wrapper">';
							paginationHTML += '<div class="pagination-info">';
							paginationHTML += `<?php esc_html_e( 'Showing', 'wc-team-payroll' ); ?> ${start} <?php esc_html_e( 'to', 'wc-team-payroll' ); ?> ${end} <?php esc_html_e( 'of', 'wc-team-payroll' ); ?> ${totalRows} <?php esc_html_e( 'entries', 'wc-team-payroll' ); ?>`;
							paginationHTML += '</div>';
							paginationHTML += '<div class="pagination-controls">';
							
							// Previous button
							if (currentPage > 1) {
								paginationHTML += `<a href="#" class="page-btn prev-btn" data-page="${currentPage - 1}"><i class="ph ph-caret-left"></i></a>`;
							}
							
							// Page numbers
							for (let i = 1; i <= totalPages; i++) {
								if (i === currentPage) {
									paginationHTML += `<a href="#" class="page-btn current-page">${i}</a>`;
								} else if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
									paginationHTML += `<a href="#" class="page-btn" data-page="${i}">${i}</a>`;
								} else if (i === currentPage - 3 || i === currentPage + 3) {
									paginationHTML += '<span class="page-ellipsis">...</span>';
								}
							}
							
							// Next button
							if (currentPage < totalPages) {
								paginationHTML += `<a href="#" class="page-btn next-btn" data-page="${currentPage + 1}"><i class="ph ph-caret-right"></i></a>`;
							}
							
							paginationHTML += '</div></div>';
							container.html(paginationHTML);
						}
						
						// Update sort icons
						function updateSortIcons() {
							$('.sortable .sort-icon').removeClass('ph-caret-up ph-caret-down').addClass('ph-caret-up-down');
							$(`.sortable[data-sort="${currentSort.column}"] .sort-icon`)
								.removeClass('ph-caret-up-down')
								.addClass(currentSort.direction === 'asc' ? 'ph-caret-up' : 'ph-caret-down');
						}
						
						// Event handlers
						$('.sortable').on('click', function() {
							const column = $(this).data('sort');
							if (currentSort.column === column) {
								currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
							} else {
								currentSort.column = column;
								currentSort.direction = 'desc';
							}
							currentPage = 1;
							updateTable();
						});
						
						$('#salary-history-search').on('input', function() {
							searchTerm = $(this).val();
							currentPage = 1;
							
							// Toggle search icon
							var $icon = $(this).siblings('i');
							if (searchTerm.length > 0) {
								$icon.removeClass('ph-magnifying-glass').addClass('ph-x');
							} else {
								$icon.removeClass('ph-x').addClass('ph-magnifying-glass');
							}
							
							updateTable();
						});
						
						// Clear search on icon click
						$(document).on('click', '.search-control i.ph-x', function() {
							$('#salary-history-search').val('').trigger('input');
						});
						
						$('#salary-history-per-page').on('change', function() {
							perPage = parseInt($(this).val());
							currentPage = 1;
							updateTable();
						});
						
						$(document).on('click', '.page-btn[data-page]', function(e) {
							e.preventDefault();
							currentPage = parseInt($(this).data('page'));
							updateTable();
							
							// Scroll to table header smoothly
							$('html, body').animate({
								scrollTop: $('#salary-history-table').offset().top - 100
							}, 300);
						});
						
						// Initialize
						initSalaryHistoryTable();
					});
				</script>
			<?php else : ?>
				<div class="no-history-message">
					<i class="ph ph-info"></i>
					<p><?php esc_html_e( 'No salary change history available.', 'wc-team-payroll' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * My Earnings content
	 */
	public static function my_earnings_content() {
		$user_id = get_current_user_id();
		
		?>
		<div class="pv-page-wrapper wc-team-payroll-earnings">
			<!-- Employee Header -->
			<?php echo self::get_employee_header( $user_id ); ?>

			<!-- Earnings Summary Section -->
			<div class="pv-section-wrapper earnings-summary-section">
				<h3><?php esc_html_e( 'Earnings Summary', 'wc-team-payroll' ); ?></h3>
				<div class="pv-summary-grid earnings-summary">
					<div class="pv-card earning-card current-month">
						<div class="card-header">
							<i class="ph ph-calendar"></i>
						</div>
						<div class="card-label-amount-wrapper">
							<div class="card-label"><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></div>
							<p class="amount" id="current-month-earnings">$0.00</p>
						</div>
						<div class="card-breakdown">
							<span class="breakdown-item"><small><?php esc_html_e( 'Salary:', 'wc-team-payroll' ); ?></small> <span id="current-month-salary">$0.00</span></span>
							<span class="breakdown-item"><small><?php esc_html_e( 'Commission:', 'wc-team-payroll' ); ?></small> <span id="current-month-commission">$0.00</span></span>
						</div>
					</div>
					
					<div class="pv-card earning-card total-earnings">
						<div class="card-header">
							<i class="ph ph-chart-line-up"></i>
						</div>
						<div class="card-label-amount-wrapper">
							<div class="card-label"><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></div>
							<p class="amount" id="total-earnings">$0.00</p>
						</div>
						<div class="card-breakdown">
							<span class="breakdown-item"><small><?php esc_html_e( 'Salary:', 'wc-team-payroll' ); ?></small> <span id="total-salary">$0.00</span></span>
							<span class="breakdown-item"><small><?php esc_html_e( 'Commission:', 'wc-team-payroll' ); ?></small> <span id="total-commission">$0.00</span></span>
						</div>
					</div>
					
					<div class="pv-card earning-card total-paid">
						<div class="card-header">
							<i class="ph ph-check-circle"></i>
						</div>
						<div class="card-label-amount-wrapper">
							<div class="card-label"><?php esc_html_e( 'Total Paid', 'wc-team-payroll' ); ?></div>
							<p class="amount paid" id="total-paid">$0.00</p>
						</div>
						<div class="card-breakdown">
							<span class="breakdown-item"><small><?php esc_html_e( 'Last Paid:', 'wc-team-payroll' ); ?></small> <span id="last-paid-info">-</span></span>
						</div>
					</div>
					
					<div class="pv-card earning-card total-due">
						<div class="card-header">
							<i class="ph ph-clock"></i>
						</div>
						<div class="card-label-amount-wrapper">
							<div class="card-label"><?php esc_html_e( 'Amount Due', 'wc-team-payroll' ); ?></div>
							<p class="amount due" id="total-due">$0.00</p>
						</div>
						<div class="card-breakdown">
							<span class="breakdown-item"><small><?php esc_html_e( 'Pending Salary:', 'wc-team-payroll' ); ?></small> <span id="pending-salary">$0.00</span></span>
						</div>
					</div>
				</div>
			</div>

			<!-- Monthly Earnings History Section -->
			<div class="pv-section-wrapper earnings-history-section">
				<h3><?php esc_html_e( 'Monthly Earnings History', 'wc-team-payroll' ); ?></h3>
				<div class="table-wrapper">
					<div class="section-header">
						<div class="pv-table-controls table-controls">
							<div class="search-control">
								<input type="text" id="earnings-search" placeholder="<?php esc_attr_e( 'Search history...', 'wc-team-payroll' ); ?>" />
								<i class="ph ph-magnifying-glass"></i>
							</div>
							<div class="view-control">
								<label for="earnings-view"><?php esc_html_e( 'View:', 'wc-team-payroll' ); ?></label>
								<select id="earnings-view">
									<option value="daily"><?php esc_html_e( 'Daily', 'wc-team-payroll' ); ?></option>
									<option value="weekly"><?php esc_html_e( 'Weekly', 'wc-team-payroll' ); ?></option>
									<option value="monthly" selected><?php esc_html_e( 'Monthly', 'wc-team-payroll' ); ?></option>
								</select>
							</div>
							<div class="per-page-control">
								<label for="earnings-per-page"><?php esc_html_e( 'Show:', 'wc-team-payroll' ); ?></label>
								<select id="earnings-per-page">
									<option value="5">5</option>
									<option value="10" selected>10</option>
									<option value="25">25</option>
									<option value="50">50</option>
								</select>
								<span><?php esc_html_e( 'per page', 'wc-team-payroll' ); ?></span>
							</div>
						</div>
					</div>
					
					<div class="table-container pv-table-container">
						<table class="pv-table woocommerce-table woocommerce-table--earnings" id="earnings-table">
							<thead>
								<tr>
									<th class="sortable" data-sort="month">
										<?php esc_html_e( 'Month', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="orders">
										<?php esc_html_e( 'Orders', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="salary">
										<?php esc_html_e( 'Salary', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="commission">
										<?php esc_html_e( 'Commission', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="earned">
										<?php esc_html_e( 'Total Earned', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="paid">
										<?php esc_html_e( 'Paid', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="due">
										<?php esc_html_e( 'Due', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="status">
										<?php esc_html_e( 'Status', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
								</tr>
							</thead>
							<tbody id="earnings-tbody">
								<tr>
									<td colspan="8" style="text-align: center; padding: 40px 20px;">
										<i class="ph ph-spinner" style="font-size: 32px; animation: spin 1s linear infinite;"></i>
										<p><?php esc_html_e( 'Loading earnings data...', 'wc-team-payroll' ); ?></p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				
				<!-- Pagination -->
				<div class="pagination-container" id="earnings-pagination">
					<!-- Pagination will be inserted here by JavaScript -->
				</div>
			</div>

			<!-- No Earnings Message -->
			<div class="no-results-message" id="no-earnings-message" style="display: none; text-align: center; padding: 40px 20px;">
				<i class="ph ph-chart-line-up" style="font-size: 48px; color: #dee2e6; margin-bottom: 15px; display: block;"></i>
				<h4 style="margin: 15px 0 10px 0; font-size: 18px; color: #495057;"><?php esc_html_e( 'No Earnings Yet', 'wc-team-payroll' ); ?></h4>
				<p style="margin: 0; font-size: 14px; color: #6c757d;"><?php esc_html_e( 'Start processing orders to see your earnings here.', 'wc-team-payroll' ); ?></p>
			</div>
		</div>

		<style>
			@keyframes spin {
				from { transform: rotate(0deg); }
				to { transform: rotate(360deg); }
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				let currentPage = 1;
				let perPage = 10;
				let currentSort = { column: 'month', direction: 'desc' };
				let searchTerm = '';
				let allRows = [];
				let currentViewType = 'monthly';

				// Load earnings data on page load
				loadEarningsData();

				function loadEarningsData() {
					$.ajax({
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: {
							action: 'wc_tp_get_earnings_data',
							nonce: '<?php echo esc_attr( wp_create_nonce( 'wc_team_payroll_nonce' ) ); ?>',
							view_type: currentViewType
						},
						success: function(response) {
							if (response.success) {
								const data = response.data;
								
								// Update summary cards
								$('#current-month-earnings').html(data.current_month_earnings);
								$('#current-month-salary').html(data.current_month_salary);
								$('#current-month-commission').html(data.current_month_commission);
								$('#total-earnings').html(data.total_earnings);
								$('#total-salary').html(data.total_salary);
								$('#total-commission').html(data.total_commission);
								$('#total-paid').html(data.total_paid);
								$('#total-due').html(data.total_due);
								$('#pending-salary').html(data.pending_salary);
								
								// Format last paid info
								let lastPaidInfo = '-';
								if (data.last_paid_amount && data.last_paid_amount !== '$0.00') {
									lastPaidInfo = data.last_paid_amount + ' at ' + data.last_paid_date;
									if (data.last_paid_method) {
										lastPaidInfo += ' with ' + data.last_paid_method;
									}
								}
								$('#last-paid-info').html(lastPaidInfo);

								// Update table header based on view type
								updateTableHeader(data.view_label);

								// Populate table rows
								allRows = [];
								if (data.history && data.history.length > 0) {
									data.history.forEach(function(item) {
										allRows.push({
											element: createTableRow(item),
											data: {
												month: item.date,
												orders: item.orders_count,
												salary: item.salary,
												commission: item.commission,
												earned: item.total,
												paid: item.paid,
												due: item.due,
												status: item.status,
												text: (item.date + ' ' + item.status).toLowerCase()
											}
										});
									});
									$('#no-earnings-message').hide();
									$('#earnings-table').show();
									$('#earnings-pagination').show();
									updateTable();
								} else {
									$('#no-earnings-message').show();
									$('#earnings-table').hide();
									$('#earnings-pagination').hide();
								}
							}
						},
						error: function() {
							$('#earnings-tbody').html('<tr><td colspan="8" style="text-align: center; padding: 20px;"><p style="color: #dc3545;"><?php esc_html_e( 'Error loading earnings data', 'wc-team-payroll' ); ?></p></td></tr>');
						}
					});
				}

				function updateTableHeader(viewLabel) {
					// Update the first column header based on view type
					$('table#earnings-table thead tr th:first-child').html(
						viewLabel + ' <i class="ph ph-caret-up-down sort-icon"></i>'
					);
				}

				function createTableRow(item) {
					const row = $('<tr></tr>');
					
					// Date/Month/Week
					const dateCell = $('<td></td>').attr('data-sort-value', item.date)
						.append($('<span class="month-name"></span>').text(item.date));
					
					// Orders
					const ordersCell = $('<td></td>').attr('data-sort-value', item.orders_count)
						.append($('<span class="orders-count"></span>').text(item.orders_count));
					
					// Salary
					const salaryCell = $('<td></td>').attr('data-sort-value', item.salary)
						.append($('<span class="amount-salary"></span>').html(item.salary_formatted));
					
					// Commission
					const commissionCell = $('<td></td>').attr('data-sort-value', item.commission)
						.append($('<span class="amount-commission"></span>').html(item.commission_formatted));
					
					// Total Earned
					const earnedCell = $('<td></td>').attr('data-sort-value', item.total)
						.append($('<span class="amount-earned"></span>').html(item.total_formatted));
					
					// Paid
					const paidCell = $('<td></td>').attr('data-sort-value', item.paid)
						.append($('<span class="amount-paid"></span>').html(item.paid_formatted));
					
					// Due
					const dueCell = $('<td></td>').attr('data-sort-value', item.due)
						.append($('<span class="amount-due"></span>').html(item.due_formatted));
					
					// Status
					let statusLabel = '<?php esc_html_e( 'Pending', 'wc-team-payroll' ); ?>';
					let statusIcon = 'ph-clock';
					
					if (item.status === 'paid') {
						statusLabel = '<?php esc_html_e( 'Paid', 'wc-team-payroll' ); ?>';
						statusIcon = 'ph-check-circle';
					} else if (item.status === 'partially_paid') {
						statusLabel = '<?php esc_html_e( 'Partially Paid', 'wc-team-payroll' ); ?>';
						statusIcon = 'ph-warning';
					}
					
					const statusCell = $('<td></td>').attr('data-sort-value', item.status)
						.append($('<span class="status-badge status-' + item.status + '"></span>')
							.append($('<i class="ph ' + statusIcon + '"></i>'))
							.append(' ' + statusLabel));

					row.append(dateCell, ordersCell, salaryCell, commissionCell, earnedCell, paidCell, dueCell, statusCell);
					return row;
				}

				function updateTable() {
					let filteredRows = allRows.slice();
					
					// Apply search filter
					if (searchTerm) {
						filteredRows = filteredRows.filter(row => 
							row.data.text.includes(searchTerm.toLowerCase())
						);
					}
					
					// Apply sorting
					filteredRows.sort((a, b) => {
						let aVal = a.data[currentSort.column];
						let bVal = b.data[currentSort.column];
						
						if (typeof aVal === 'string') {
							aVal = aVal.toLowerCase();
							bVal = bVal.toLowerCase();
						}
						
						if (currentSort.direction === 'asc') {
							return aVal > bVal ? 1 : -1;
						} else {
							return aVal < bVal ? 1 : -1;
						}
					});
					
					// Calculate pagination
					const totalRows = filteredRows.length;
					const totalPages = Math.ceil(totalRows / perPage);
					const startIndex = (currentPage - 1) * perPage;
					const endIndex = startIndex + perPage;
					const pageRows = filteredRows.slice(startIndex, endIndex);
					
					// Update table body
					const tbody = $('#earnings-tbody');
					tbody.empty();
					
					if (pageRows.length === 0) {
						tbody.append(`
							<tr>
								<td colspan="8" class="no-results">
									<div class="no-results-message">
										<i class="ph ph-magnifying-glass"></i>
										<p><?php esc_html_e( 'No earnings history found matching your search.', 'wc-team-payroll' ); ?></p>
									</div>
								</td>
							</tr>
						`);
					} else {
						pageRows.forEach(row => {
							tbody.append(row.element);
						});
					}
					
					// Update pagination
					updatePagination(totalPages, totalRows, startIndex + 1, Math.min(endIndex, totalRows));
					
					// Update sort icons
					updateSortIcons();
				}

				function updatePagination(totalPages, totalRows, start, end) {
					const container = $('#earnings-pagination');
					
					if (totalPages <= 1) {
						container.html('');
						return;
					}
					
					let paginationHTML = '<div class="pagination-wrapper">';
					paginationHTML += '<div class="pagination-info">';
					paginationHTML += `<?php esc_html_e( 'Showing', 'wc-team-payroll' ); ?> ${start} <?php esc_html_e( 'to', 'wc-team-payroll' ); ?> ${end} <?php esc_html_e( 'of', 'wc-team-payroll' ); ?> ${totalRows} <?php esc_html_e( 'entries', 'wc-team-payroll' ); ?>`;
					paginationHTML += '</div>';
					paginationHTML += '<div class="pagination-controls">';
					
					// Previous button
					if (currentPage > 1) {
						paginationHTML += `<a href="#" class="page-btn prev-btn" data-page="${currentPage - 1}"><i class="ph ph-caret-left"></i></a>`;
					}
					
					// Page numbers
					for (let i = 1; i <= totalPages; i++) {
						if (i === currentPage) {
							paginationHTML += `<a href="#" class="page-btn current-page">${i}</a>`;
						} else if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
							paginationHTML += `<a href="#" class="page-btn" data-page="${i}">${i}</a>`;
						} else if (i === currentPage - 3 || i === currentPage + 3) {
							paginationHTML += '<span class="page-ellipsis">...</span>';
						}
					}
					
					// Next button
					if (currentPage < totalPages) {
						paginationHTML += `<a href="#" class="page-btn next-btn" data-page="${currentPage + 1}"><i class="ph ph-caret-right"></i></a>`;
					}
					
					paginationHTML += '</div></div>';
					container.html(paginationHTML);
				}

				function updateSortIcons() {
					$('.sortable .sort-icon').removeClass('ph-caret-up ph-caret-down').addClass('ph-caret-up-down');
					$(`.sortable[data-sort="${currentSort.column}"] .sort-icon`)
						.removeClass('ph-caret-up-down')
						.addClass(currentSort.direction === 'asc' ? 'ph-caret-up' : 'ph-caret-down');
				}

				// Event handlers
				$('.sortable').on('click', function() {
					const column = $(this).data('sort');
					if (currentSort.column === column) {
						currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
					} else {
						currentSort.column = column;
						currentSort.direction = 'desc';
					}
					currentPage = 1;
					updateTable();
				});

				$('#earnings-search').on('input', function() {
					searchTerm = $(this).val();
					currentPage = 1;

					// Toggle search icon
					var $icon = $(this).siblings('i');
					if (searchTerm.length > 0) {
						$icon.removeClass('ph-magnifying-glass').addClass('ph-x');
					} else {
						$icon.removeClass('ph-x').addClass('ph-magnifying-glass');
					}

					updateTable();
				});

				// Clear search on icon click
				$(document).on('click', '.search-control i.ph-x', function() {
					$('#earnings-search').val('').trigger('input');
				});

				$('#earnings-per-page').on('change', function() {
					perPage = parseInt($(this).val());
					currentPage = 1;
					updateTable();
				});

				$(document).on('click', '.page-btn[data-page]', function(e) {
					e.preventDefault();
					currentPage = parseInt($(this).data('page'));
					updateTable();

					// Scroll to table header smoothly
					$('html, body').animate({
						scrollTop: $('#earnings-table').offset().top - 100
					}, 300);
				});

				// View change handler (Daily/Weekly/Monthly)
				$('#earnings-view').on('change', function() {
					currentViewType = $(this).val();
					currentPage = 1;
					currentSort = { column: 'month', direction: 'desc' };
					searchTerm = '';
					$('#earnings-search').val('');
					loadEarningsData();
				});
			});
		</script>
		<?php
	}

	/**
	 * Orders Commission content
	 */
	public static function orders_commission_content() {
		$user_id = get_current_user_id();
		
		// Get all WooCommerce order statuses
		$all_statuses = wc_get_order_statuses();
		
		// Define icons for default WooCommerce statuses
		$status_icons = array(
			'wc-completed'   => 'ph-check-circle',
			'wc-processing'  => 'ph-hourglass',
			'wc-pending'     => 'ph-clock',
			'wc-on-hold'     => 'ph-pause-circle',
			'wc-cancelled'   => 'ph-x-circle',
			'wc-refunded'    => 'ph-arrow-counter-clockwise',
		);
		
		// Default icon for custom statuses
		$default_custom_icon = 'ph-tag';
		
		// Filter out draft and failed statuses
		$filtered_statuses = array();
		foreach ( $all_statuses as $status_key => $status_label ) {
			$clean_status = str_replace( 'wc-', '', $status_key );
			if ( $clean_status !== 'draft' && $clean_status !== 'failed' ) {
				$filtered_statuses[ $status_key ] = $status_label;
			}
		}
		
		?>
		<div class="pv-page-wrapper wc-team-payroll-orders">
			<!-- Employee Header -->
			<?php echo self::get_employee_header( $user_id ); ?>

			<!-- Orders Summary Section -->
			<div class="pv-section-wrapper orders-summary-section">
				<h3><?php esc_html_e( 'Orders Summary', 'wc-team-payroll' ); ?></h3>
				<div class="pv-summary-grid orders-summary">
					<!-- Fixed Summary Cards -->
					<div class="pv-card order-card total-orders">
						<div class="card-header">
							<i class="ph ph-shopping-cart"></i>
						</div>
						<div class="card-label-amount-wrapper">
							<div class="card-label"><?php esc_html_e( 'Total Orders', 'wc-team-payroll' ); ?></div>
							<p class="amount" id="total-orders">0</p>
						</div>
					</div>
					
					<div class="pv-card order-card total-commission">
						<div class="card-header">
							<i class="ph ph-currency-dollar"></i>
						</div>
						<div class="card-label-amount-wrapper">
							<div class="card-label"><?php esc_html_e( 'Total Commission', 'wc-team-payroll' ); ?></div>
							<p class="amount" id="total-commission">$0.00</p>
						</div>
					</div>
					
					<div class="pv-card order-card my-earnings">
						<div class="card-header">
							<i class="ph ph-wallet"></i>
						</div>
						<div class="card-label-amount-wrapper">
							<div class="card-label"><?php esc_html_e( 'My Earnings', 'wc-team-payroll' ); ?></div>
							<p class="amount" id="my-earnings">$0.00</p>
						</div>
					</div>
					
					<!-- Dynamic Status Cards -->
					<?php foreach ( $filtered_statuses as $status_key => $status_label ) : 
						$clean_status = str_replace( 'wc-', '', $status_key );
						$icon_class = isset( $status_icons[ $status_key ] ) ? $status_icons[ $status_key ] : $default_custom_icon;
						$is_custom = ! isset( $status_icons[ $status_key ] );
					?>
						<div class="pv-card order-card status-<?php echo esc_attr( $clean_status ); ?><?php echo $is_custom ? ' custom-status' : ''; ?>">
							<div class="card-header">
								<i class="ph <?php echo esc_attr( $icon_class ); ?>"></i>
							</div>
							<div class="card-label-amount-wrapper">
								<div class="card-label"><?php echo esc_html( $status_label ); ?></div>
								<p class="amount" id="status-<?php echo esc_attr( $clean_status ); ?>">0</p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Orders List Section -->
			<div class="pv-section-wrapper orders-history-section">
				<h3><?php esc_html_e( 'Orders List', 'wc-team-payroll' ); ?></h3>
				<div class="table-wrapper">
					<div class="section-header">
						<div class="pv-table-controls table-controls">
							<div class="search-control">
								<input type="text" id="orders-search" placeholder="<?php esc_attr_e( 'Search orders...', 'wc-team-payroll' ); ?>" />
								<i class="ph ph-magnifying-glass"></i>
							</div>
							<div class="filter-control">
								<label for="role-filter"><?php esc_html_e( 'Role:', 'wc-team-payroll' ); ?></label>
								<select id="role-filter">
									<option value="all"><?php esc_html_e( 'All Orders', 'wc-team-payroll' ); ?></option>
									<option value="agent"><?php esc_html_e( 'As Agent', 'wc-team-payroll' ); ?></option>
									<option value="processor"><?php esc_html_e( 'As Processor', 'wc-team-payroll' ); ?></option>
								</select>
							</div>
							<div class="filter-control">
								<label for="status-filter"><?php esc_html_e( 'Status:', 'wc-team-payroll' ); ?></label>
								<select id="status-filter">
									<option value="all"><?php esc_html_e( 'All Status', 'wc-team-payroll' ); ?></option>
									<?php
									// Get all WooCommerce order statuses dynamically
									$order_statuses = wc_get_order_statuses();
									foreach ( $order_statuses as $status_key => $status_label ) {
										// Remove 'wc-' prefix from status key for cleaner values
										$clean_status = str_replace( 'wc-', '', $status_key );
										echo '<option value="' . esc_attr( $clean_status ) . '">' . esc_html( $status_label ) . '</option>';
									}
									?>
								</select>
							</div>
							<div class="filter-control pv-date-filter-wrapper">
								<label for="orders-date-preset"><?php esc_html_e( 'Date Range:', 'wc-team-payroll' ); ?></label>
								<div class="pv-date-filter-container">
									<select id="orders-date-preset">
										<option value="all-time"><?php esc_html_e( 'All Time', 'wc-team-payroll' ); ?></option>
										<option value="today"><?php esc_html_e( 'Today', 'wc-team-payroll' ); ?></option>
										<option value="this-week"><?php esc_html_e( 'This Week', 'wc-team-payroll' ); ?></option>
										<option value="this-month"><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></option>
										<option value="this-year"><?php esc_html_e( 'This Year', 'wc-team-payroll' ); ?></option>
										<option value="last-week"><?php esc_html_e( 'Last Week', 'wc-team-payroll' ); ?></option>
										<option value="last-month"><?php esc_html_e( 'Last Month', 'wc-team-payroll' ); ?></option>
										<option value="last-year"><?php esc_html_e( 'Last Year', 'wc-team-payroll' ); ?></option>
										<option value="last-6-months"><?php esc_html_e( 'Last 6 Months', 'wc-team-payroll' ); ?></option>
										<option value="custom"><?php esc_html_e( 'Custom', 'wc-team-payroll' ); ?></option>
									</select>
									<div class="pv-custom-date-inline" id="orders-custom-date-range" style="display: none;">
										<div class="pv-date-input-group">
											<label for="date-from"><?php esc_html_e( 'From:', 'wc-team-payroll' ); ?></label>
											<input type="date" id="date-from" />
										</div>
										<div class="pv-date-input-group">
											<label for="date-to"><?php esc_html_e( 'To:', 'wc-team-payroll' ); ?></label>
											<input type="date" id="date-to" />
										</div>
									</div>
								</div>
							</div>
							<div class="per-page-control">
								<label for="orders-per-page"><?php esc_html_e( 'Show:', 'wc-team-payroll' ); ?></label>
								<select id="orders-per-page">
									<option value="10">10</option>
									<option value="25" selected>25</option>
									<option value="50">50</option>
									<option value="100">100</option>
								</select>
								<span><?php esc_html_e( 'per page', 'wc-team-payroll' ); ?></span>
							</div>
							<button id="clear-filters-btn" class="btn-clear-filters" style="padding: 8px 12px; border: 1px solid; border-radius: 4px; background: transparent; cursor: pointer; font-size: 13px; font-weight: 600;">
								<i class="ph ph-x"></i> <?php esc_html_e( 'Clear', 'wc-team-payroll' ); ?>
							</button>
						</div>
					</div>
					
					<div class="table-container pv-table-container">
						<table class="pv-table woocommerce-table woocommerce-table--orders" id="orders-table">
							<thead>
								<tr>
									<th class="sortable" data-sort="order_id">
										<?php esc_html_e( 'Order ID', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="date">
										<?php esc_html_e( 'Date', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="customer">
										<?php esc_html_e( 'Customer', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="role">
										<?php esc_html_e( 'My Role', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="total">
										<?php esc_html_e( 'Order Total', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="attributed">
										<?php esc_html_e( 'Attributed Order Total', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="commission">
										<?php esc_html_e( 'Commission', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th class="sortable" data-sort="earning">
										<?php esc_html_e( 'My Earning', 'wc-team-payroll' ); ?>
										<i class="ph ph-caret-up-down sort-icon"></i>
									</th>
									<th><?php esc_html_e( 'Status', 'wc-team-payroll' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'wc-team-payroll' ); ?></th>
								</tr>
							</thead>
							<tbody id="orders-tbody">
								<tr>
									<td colspan="10" style="text-align: center; padding: 40px 20px;">
										<i class="ph ph-spinner" style="font-size: 32px; animation: spin 1s linear infinite;"></i>
										<p><?php esc_html_e( 'Loading orders...', 'wc-team-payroll' ); ?></p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				
				<!-- Pagination -->
				<div class="pagination-container" id="orders-pagination">
					<!-- Pagination will be inserted here by JavaScript -->
				</div>
			</div>

			<!-- No Orders Message -->
			<div class="no-results-message" id="no-orders-message" style="display: none; text-align: center; padding: 40px 20px;">
				<i class="ph ph-shopping-bag" style="font-size: 48px; color: #dee2e6; margin-bottom: 15px; display: block;"></i>
				<h4 style="margin: 15px 0 10px 0; font-size: 18px; color: #495057;"><?php esc_html_e( 'No Orders Found', 'wc-team-payroll' ); ?></h4>
				<p style="margin: 0; font-size: 14px; color: #6c757d;"><?php esc_html_e( 'No orders match your search criteria.', 'wc-team-payroll' ); ?></p>
			</div>
		</div>

		<style>
			@keyframes spin {
				from { transform: rotate(0deg); }
				to { transform: rotate(360deg); }
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				let currentPage = 1;
				let perPage = 25;
				let currentSort = { column: 'date', direction: 'desc' };
				let searchTerm = '';
				let roleFilter = 'all';
				let statusFilter = 'all';
				let allRows = [];
				let lastCustomDateFrom = '';
				let lastCustomDateTo = '';

				// Load orders data on page load
				loadOrdersData();

				function loadOrdersData() {
					$.ajax({
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: {
							action: 'wc_tp_get_myaccount_orders',
							nonce: '<?php echo esc_attr( wp_create_nonce( 'wc_team_payroll_nonce' ) ); ?>',
							role_filter: roleFilter,
							status_filter: statusFilter,
							date_from: $('#date-from').val(),
							date_to: $('#date-to').val()
						},
						success: function(response) {
							if (response.success) {
								const data = response.data;
								
								// Update summary cards
								$('#total-orders').html(data.summary.total_orders);
								$('#total-commission').html(data.summary.total_commission);
								$('#my-earnings').html(data.summary.my_earnings);
								
								// Update all status cards dynamically
								for (var key in data.summary) {
									if (key.startsWith('status_')) {
										var statusId = key.replace(/_/g, '-'); // Convert underscores to hyphens for ID
										$('#' + statusId).html(data.summary[key] || 0);
									}
								}

								// Populate table rows
								allRows = [];
								if (data.orders && data.orders.length > 0) {
									data.orders.forEach(function(order) {
										allRows.push({
											element: createTableRow(order),
											data: {
												order_id: order.order_id,
												date: order.date_timestamp,
												customer: order.customer_name.toLowerCase(),
												role: order.my_role.toLowerCase(),
												total: order.total_amount,
												attributed: order.attributed_amount || 0,
												commission: order.commission_amount,
												earning: order.earning_amount,
												text: (order.order_id + ' ' + order.customer_name).toLowerCase()
											}
										});
									});
									$('#no-orders-message').hide();
									$('#orders-table').show();
									$('#orders-pagination').show();
									updateTable();
								} else {
									$('#no-orders-message').show();
									$('#orders-table').hide();
									$('#orders-pagination').hide();
								}
							}
						},
						error: function() {
							$('#orders-tbody').html('<tr><td colspan="10" style="text-align: center; padding: 20px;"><p style="color: #dc3545;"><?php esc_html_e( 'Error loading orders', 'wc-team-payroll' ); ?></p></td></tr>');
						}
					});
				}

				function createTableRow(order) {
					const row = $('<tr></tr>');
					
					// Order ID
					const orderIdCell = $('<td></td>').attr('data-sort-value', order.order_id)
						.append($('<a href="#" class="order-link"></a>').text('#' + order.order_id).on('click', function(e) {
							e.preventDefault();
							showOrderDetails(order.order_id);
						}));
					
					// Date
					const dateCell = $('<td></td>').attr('data-sort-value', order.date_timestamp)
						.append($('<span class="order-date"></span>').text(order.date));
					
					// Customer
					const customerCell = $('<td></td>').attr('data-sort-value', order.customer_name.toLowerCase())
						.append($('<span class="customer-name"></span>').text(order.customer_name));
					
					// My Role
					const roleCell = $('<td></td>').attr('data-sort-value', order.my_role.toLowerCase())
						.append($('<span class="role-badge role-' + order.my_role + '"></span>')
							.append($('<i class="ph ' + (order.my_role === 'agent' ? 'ph-user-check' : 'ph-gear') + '"></i>'))
							.append(' ' + order.my_role_label));
					
					// Order Total
					const totalCell = $('<td></td>').attr('data-sort-value', order.total_amount)
						.append($('<span class="amount-total"></span>').html(order.total));
					
					// Attributed Order Total
					const attributedCell = $('<td></td>').attr('data-sort-value', order.attributed_amount || 0)
						.append($('<span class="amount-attributed"></span>').html(order.attributed || '—'));
					
					// Commission
					const commissionCell = $('<td></td>').attr('data-sort-value', order.commission_amount);
					if (order.has_commission) {
						commissionCell.append($('<span class="amount-commission"></span>').html(order.commission));
					} else {
						commissionCell.append($('<span class="amount-commission no-commission"></span>')
							.html('<?php esc_html_e( 'N/A', 'wc-team-payroll' ); ?>')
							.attr('title', '<?php esc_attr_e( 'Commission not calculated for this status', 'wc-team-payroll' ); ?>'));
					}
					
					// My Earning
					const earningCell = $('<td></td>').attr('data-sort-value', order.earning_amount);
					if (order.has_commission) {
						earningCell.append($('<span class="amount-earning"></span>').html(order.earning));
					} else {
						earningCell.append($('<span class="amount-earning no-commission"></span>')
							.html('<?php esc_html_e( 'N/A', 'wc-team-payroll' ); ?>')
							.attr('title', '<?php esc_attr_e( 'Earnings not available for this status', 'wc-team-payroll' ); ?>'));
					}
					
					// Status
					const statusCell = $('<td></td>')
						.append($('<span class="status-badge status-' + order.status + '"></span>')
							.append($('<i class="ph ' + getStatusIcon(order.status) + '"></i>'))
							.append(' ' + order.status_label));
					
					// Actions
					const actionsCell = $('<td></td>')
						.append($('<button class="btn-action btn-view"></button>')
							.append($('<i class="ph ph-eye"></i>'))
							.on('click', function() {
								showOrderDetails(order.order_id);
							}));
					
					row.append(orderIdCell, dateCell, customerCell, roleCell, totalCell, attributedCell, commissionCell, earningCell, statusCell, actionsCell);
					return row;
				}

				function getStatusIcon(status) {
					const icons = {
						'completed': 'ph-check-circle',
						'processing': 'ph-hourglass',
						'pending': 'ph-clock',
						'on-hold': 'ph-pause-circle',
						'cancelled': 'ph-x-circle',
						'refunded': 'ph-warning'
					};
					return icons[status] || 'ph-question';
				}

				function updateTable() {
					let filteredRows = allRows.slice();
					
					// Apply search filter
					if (searchTerm) {
						filteredRows = filteredRows.filter(row => 
							row.data.text.includes(searchTerm.toLowerCase())
						);
					}
					
					// Apply sorting
					filteredRows.sort((a, b) => {
						let aVal = a.data[currentSort.column];
						let bVal = b.data[currentSort.column];
						
						if (typeof aVal === 'string') {
							aVal = aVal.toLowerCase();
							bVal = bVal.toLowerCase();
						}
						
						if (currentSort.direction === 'asc') {
							return aVal > bVal ? 1 : -1;
						} else {
							return aVal < bVal ? 1 : -1;
						}
					});
					
					// Calculate pagination
					const totalRows = filteredRows.length;
					const totalPages = Math.ceil(totalRows / perPage);
					const startIndex = (currentPage - 1) * perPage;
					const endIndex = startIndex + perPage;
					const pageRows = filteredRows.slice(startIndex, endIndex);
					
					// Update table body
					const tbody = $('#orders-tbody');
					tbody.empty();
					
					if (pageRows.length === 0) {
						tbody.append(`
							<tr>
								<td colspan="9" class="no-results">
									<div class="no-results-message">
										<i class="ph ph-magnifying-glass"></i>
										<p><?php esc_html_e( 'No orders found matching your search.', 'wc-team-payroll' ); ?></p>
									</div>
								</td>
							</tr>
						`);
					} else {
						pageRows.forEach(row => {
							tbody.append(row.element);
						});
					}
					
					// Update pagination
					updatePagination(totalPages, totalRows, startIndex + 1, Math.min(endIndex, totalRows));
					
					// Update sort icons
					updateSortIcons();
				}

				function updatePagination(totalPages, totalRows, start, end) {
					const container = $('#orders-pagination');
					
					if (totalPages <= 1) {
						container.html('');
						return;
					}
					
					let paginationHTML = '<div class="pagination-wrapper">';
					paginationHTML += '<div class="pagination-info">';
					paginationHTML += `<?php esc_html_e( 'Showing', 'wc-team-payroll' ); ?> ${start} <?php esc_html_e( 'to', 'wc-team-payroll' ); ?> ${end} <?php esc_html_e( 'of', 'wc-team-payroll' ); ?> ${totalRows} <?php esc_html_e( 'entries', 'wc-team-payroll' ); ?>`;
					paginationHTML += '</div>';
					paginationHTML += '<div class="pagination-controls">';
					
					// Previous button
					if (currentPage > 1) {
						paginationHTML += `<a href="#" class="page-btn prev-btn" data-page="${currentPage - 1}"><i class="ph ph-caret-left"></i></a>`;
					}
					
					// Page numbers
					for (let i = 1; i <= totalPages; i++) {
						if (i === currentPage) {
							paginationHTML += `<a href="#" class="page-btn current-page">${i}</a>`;
						} else if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
							paginationHTML += `<a href="#" class="page-btn" data-page="${i}">${i}</a>`;
						} else if (i === currentPage - 3 || i === currentPage + 3) {
							paginationHTML += '<span class="page-ellipsis">...</span>';
						}
					}
					
					// Next button
					if (currentPage < totalPages) {
						paginationHTML += `<a href="#" class="page-btn next-btn" data-page="${currentPage + 1}"><i class="ph ph-caret-right"></i></a>`;
					}
					
					paginationHTML += '</div></div>';
					container.html(paginationHTML);
				}

				function updateSortIcons() {
					$('.sortable .sort-icon').removeClass('ph-caret-up ph-caret-down').addClass('ph-caret-up-down');
					$(`.sortable[data-sort="${currentSort.column}"] .sort-icon`)
						.removeClass('ph-caret-up-down')
						.addClass(currentSort.direction === 'asc' ? 'ph-caret-up' : 'ph-caret-down');
				}

				// Event handlers
				$('.sortable').on('click', function() {
					const column = $(this).data('sort');
					if (currentSort.column === column) {
						currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
					} else {
						currentSort.column = column;
						currentSort.direction = 'desc';
					}
					currentPage = 1;
					updateTable();
				});

				$('#orders-search').on('input', function() {
					searchTerm = $(this).val();
					currentPage = 1;

					// Toggle search icon
					var $icon = $(this).siblings('i');
					if (searchTerm.length > 0) {
						$icon.removeClass('ph-magnifying-glass').addClass('ph-x');
					} else {
						$icon.removeClass('ph-x').addClass('ph-magnifying-glass');
					}

					updateTable();
				});

				// Clear search on icon click
				$(document).on('click', '.search-control i.ph-x', function() {
					$('#orders-search').val('').trigger('input');
				});

				// Clear all filters
				$('#clear-filters-btn').on('click', function() {
					$('#orders-search').val('');
					$('#role-filter').val('all');
					$('#status-filter').val('all');
					$('#orders-date-preset').val('all-time');
					$('#orders-custom-date-range').hide();
					$('#date-from').val('');
					$('#date-to').val('');
					$('#orders-per-page').val('25');
					roleFilter = 'all';
					statusFilter = 'all';
					searchTerm = '';
					currentPage = 1;
					perPage = 25;
					updateClearButtonState();
					loadOrdersData();
				});

				// Function to update clear button state based on filter changes
				function updateClearButtonState() {
					const $clearBtn = $('#clear-filters-btn');
					const hasActiveFilters = 
						$('#orders-search').val() !== '' ||
						$('#role-filter').val() !== 'all' ||
						$('#status-filter').val() !== 'all' ||
						$('#orders-date-preset').val() !== 'all-time' ||
						$('#date-from').val() !== '' ||
						$('#date-to').val() !== '';
					
					if (hasActiveFilters) {
						$clearBtn.addClass('filters-active');
					} else {
						$clearBtn.removeClass('filters-active');
					}
				}

				// Monitor filter changes to update clear button state
				$('#orders-search, #role-filter, #status-filter, #orders-date-preset, #date-from, #date-to').on('change input', function() {
					updateClearButtonState();
				});

				// Initialize clear button state
				updateClearButtonState();

				$('#orders-per-page').on('change', function() {
					perPage = parseInt($(this).val());
					currentPage = 1;
					updateTable();
				});

				$('#role-filter').on('change', function() {
					roleFilter = $(this).val();
					currentPage = 1;
					loadOrdersData();
				});

				$('#status-filter').on('change', function() {
					statusFilter = $(this).val();
					currentPage = 1;
					loadOrdersData();
				});

				$('#date-from').on('change', function() {
					// Store the custom dates for later restoration
					lastCustomDateFrom = $(this).val();
					currentPage = 1;
					loadOrdersData();
				});

				$('#date-to').on('change', function() {
					// Store the custom dates for later restoration
					lastCustomDateTo = $(this).val();
					currentPage = 1;
					loadOrdersData();
				});

				// Date preset functionality - using click instead of change to handle repeated clicks
				$('#orders-date-preset').on('click', function() {
					const preset = $(this).val();
					const customDateInline = $('#orders-custom-date-range');
					const dateFrom = $('#date-from');
					const dateTo = $('#date-to');
					
					if (preset === 'custom') {
						// Restore previously selected custom dates if available
						if (lastCustomDateFrom) {
							dateFrom.val(lastCustomDateFrom);
						}
						if (lastCustomDateTo) {
							dateTo.val(lastCustomDateTo);
						}
						customDateInline.show();
					}
				});

				// Handle change event for all presets (including custom)
				$('#orders-date-preset').on('change', function() {
					const preset = $(this).val();
					const customDateInline = $('#orders-custom-date-range');
					const dateFrom = $('#date-from');
					const dateTo = $('#date-to');
					
					if (preset === 'custom') {
						// Restore previously selected custom dates if available
						if (lastCustomDateFrom) {
							dateFrom.val(lastCustomDateFrom);
						}
						if (lastCustomDateTo) {
							dateTo.val(lastCustomDateTo);
						}
						customDateInline.show();
					} else {
						customDateInline.hide();
						
						// Store current custom dates before switching preset
						lastCustomDateFrom = dateFrom.val();
						lastCustomDateTo = dateTo.val();
						
						// Calculate date ranges based on preset
						const today = new Date();
						let startDate, endDate;
						
						switch (preset) {
							case 'all-time':
								dateFrom.val('');
								dateTo.val('');
								break;
							case 'today':
								startDate = new Date(today);
								endDate = new Date(today);
								break;
							case 'this-week':
								startDate = new Date(today.setDate(today.getDate() - today.getDay()));
								endDate = new Date();
								break;
							case 'this-month':
								startDate = new Date(today.getFullYear(), today.getMonth(), 1);
								endDate = new Date();
								break;
							case 'this-year':
								startDate = new Date(today.getFullYear(), 0, 1);
								endDate = new Date();
								break;
							case 'last-week':
								const lastWeekEnd = new Date(today.setDate(today.getDate() - today.getDay() - 1));
								const lastWeekStart = new Date(lastWeekEnd.setDate(lastWeekEnd.getDate() - 6));
								startDate = lastWeekStart;
								endDate = new Date(today.setDate(today.getDate() - today.getDay() - 1));
								break;
							case 'last-month':
								const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
								startDate = lastMonth;
								endDate = new Date(today.getFullYear(), today.getMonth(), 0);
								break;
							case 'last-year':
								startDate = new Date(today.getFullYear() - 1, 0, 1);
								endDate = new Date(today.getFullYear() - 1, 11, 31);
								break;
							case 'last-6-months':
								startDate = new Date(today.getFullYear(), today.getMonth() - 6, 1);
								endDate = new Date();
								break;
						}
						
						if (startDate && endDate) {
							dateFrom.val(startDate.toISOString().split('T')[0]);
							dateTo.val(endDate.toISOString().split('T')[0]);
						}
						
						currentPage = 1;
						loadOrdersData();
					}
				});

				// Custom date inputs - auto-filter when changed
				$('#date-from, #date-to').on('change', function() {
					lastCustomDateFrom = $('#date-from').val();
					lastCustomDateTo = $('#date-to').val();
					currentPage = 1;
					loadOrdersData();
				});

				// Close inline date inputs when clicking outside (optional - can be removed if not needed)
				$(document).on('click', function(e) {
					if (!$(e.target).closest('.pv-date-filter-wrapper').length) {
						// Optional: could hide inline dates here, but usually they stay visible
					}
				});

				$(document).on('click', '.page-btn[data-page]', function(e) {
					e.preventDefault();
					currentPage = parseInt($(this).data('page'));
					updateTable();

					// Scroll to table header smoothly
					$('html, body').animate({
						scrollTop: $('#orders-table').offset().top - 100
					}, 300);
				});

				// Show order details
				function showOrderDetails(orderId) {
					$.ajax({
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: {
							action: 'wc_tp_get_order_details',
							order_id: orderId,
							nonce: '<?php echo esc_attr( wp_create_nonce( 'wc_team_payroll_nonce' ) ); ?>'
						},
						success: function(response) {
							if (response.success) {
								// Handle order details display
								console.log(response.data);
							}
						}
					});
				}
			});
		</script>
		<?php
	}

	/**
	 * Reports content - Enterprise Personal Performance Reports
	 */
	public static function reports_content() {
		$user_id = get_current_user_id();
		?>
		<div class="pv-page-wrapper wc-team-payroll-reports">
			<!-- Employee Header -->
			<?php echo self::get_employee_header( $user_id ); ?>

			<!-- STEP 1: MASTER FILTER SYSTEM -->
			<div class="reports-filter-section">
				<h3 class="reports-filter-title"><?php esc_html_e( 'Filter Your Reports', 'wc-team-payroll' ); ?></h3>
				
				<div class="reports-filter-controls">
					<!-- Date Range Filter -->
					<div class="reports-filter-group pv-date-filter-wrapper">
						<label for="reports-date-range"><?php esc_html_e( 'Date Range', 'wc-team-payroll' ); ?></label>
						<div class="pv-date-filter-container">
							<select id="reports-date-range">
								<option value="all-time"><?php esc_html_e( 'All Time', 'wc-team-payroll' ); ?></option>
								<option value="today"><?php esc_html_e( 'Today', 'wc-team-payroll' ); ?></option>
								<option value="this-week"><?php esc_html_e( 'This Week', 'wc-team-payroll' ); ?></option>
								<option value="this-month" selected><?php esc_html_e( 'This Month', 'wc-team-payroll' ); ?></option>
								<option value="this-year"><?php esc_html_e( 'This Year', 'wc-team-payroll' ); ?></option>
								<option value="last-week"><?php esc_html_e( 'Last Week', 'wc-team-payroll' ); ?></option>
								<option value="last-month"><?php esc_html_e( 'Last Month', 'wc-team-payroll' ); ?></option>
								<option value="last-year"><?php esc_html_e( 'Last Year', 'wc-team-payroll' ); ?></option>
								<option value="last-6-months"><?php esc_html_e( 'Last 6 Months', 'wc-team-payroll' ); ?></option>
								<option value="custom"><?php esc_html_e( 'Custom', 'wc-team-payroll' ); ?></option>
							</select>
							<div class="pv-custom-date-inline" id="reports-custom-date-range" style="display: none;">
								<div class="pv-date-input-group">
									<label for="reports-start-date"><?php esc_html_e( 'From:', 'wc-team-payroll' ); ?></label>
									<input type="date" id="reports-start-date" />
								</div>
								<div class="pv-date-input-group">
									<label for="reports-end-date"><?php esc_html_e( 'To:', 'wc-team-payroll' ); ?></label>
									<input type="date" id="reports-end-date" />
								</div>
							</div>
						</div>
					</div>

					<!-- Order Status Filter -->
					<div class="reports-filter-group">
						<label for="reports-order-status"><?php esc_html_e( 'Order Status', 'wc-team-payroll' ); ?></label>
						<select id="reports-order-status">
							<option value="all"><?php esc_html_e( 'All Statuses', 'wc-team-payroll' ); ?></option>
							<?php
							// Get only commission calculation statuses from settings
							$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
							$all_statuses = wc_get_order_statuses();
							
							foreach ( $all_statuses as $status_key => $status_label ) {
								// Normalize status key by removing 'wc-' prefix for comparison
								$normalized_status = str_replace( 'wc-', '', $status_key );
								
								// Only show statuses that are configured for commission calculation
								if ( in_array( $normalized_status, $commission_statuses ) ) {
									echo '<option value="' . esc_attr( $status_key ) . '">' . esc_html( $status_label ) . '</option>';
								}
							}
							?>
						</select>
					</div>

					<!-- Role Filter -->
					<div class="reports-filter-group">
						<label for="reports-role"><?php esc_html_e( 'My Role', 'wc-team-payroll' ); ?></label>
						<select id="reports-role">
							<option value="all"><?php esc_html_e( 'All Roles', 'wc-team-payroll' ); ?></option>
							<option value="agent"><?php esc_html_e( 'Agent Only', 'wc-team-payroll' ); ?></option>
							<option value="processor"><?php esc_html_e( 'Processor Only', 'wc-team-payroll' ); ?></option>
						</select>
					</div>
				</div>

				<!-- Filter Actions -->
				<div class="reports-filter-actions">
					<button class="reports-filter-btn" id="reports-apply-filters"><?php esc_html_e( 'Apply Filters', 'wc-team-payroll' ); ?></button>
					<button class="reports-clear-filters-btn" id="reports-clear-filters"><?php esc_html_e( 'Clear Filters', 'wc-team-payroll' ); ?></button>
				</div>

				<!-- Filter Summary -->
				<div class="reports-filter-summary" id="reports-filter-summary" style="display: none;">
					<?php esc_html_e( 'Active Filters:', 'wc-team-payroll' ); ?> <strong id="reports-active-filters-text"></strong>
				</div>
			</div>

			<!-- STEP 2: KPI DASHBOARD (Will be populated by AJAX) -->
			<div class="reports-kpi-section pv-section-wrapper">
				<div class="reports-kpi-grid" id="reports-kpi-container">
					<div class="reports-loading">
						<i class="ph ph-spinner"></i>
						<p><?php esc_html_e( 'Loading your performance metrics...', 'wc-team-payroll' ); ?></p>
					</div>
				</div>
			</div>

			<!-- STEP 3: ANALYTICS CHARTS (Will be populated by AJAX) -->
			<div class="reports-analytics-section pv-section-wrapper">
				<div class="reports-charts-grid" id="reports-charts-container">
					<div class="reports-loading">
						<i class="ph ph-spinner"></i>
						<p><?php esc_html_e( 'Loading your analytics charts...', 'wc-team-payroll' ); ?></p>
					</div>
				</div>
			</div>

			<!-- STEP 4: PERFORMANCE METRICS (Will be populated by AJAX) -->
			<div class="reports-metrics-section pv-section-wrapper">
				<h3><?php esc_html_e( 'Performance Metrics', 'wc-team-payroll' ); ?></h3>
				<div class="reports-metrics-grid" id="reports-metrics-container">
					<div class="reports-loading">
						<i class="ph ph-spinner"></i>
						<p><?php esc_html_e( 'Loading your metrics...', 'wc-team-payroll' ); ?></p>
					</div>
				</div>
			</div>

			<!-- STEP 5: DATA TABLES (Will be populated by AJAX) -->
			<div class="reports-tables-section pv-section-wrapper">
				<div id="reports-tables-container">
					<div class="reports-loading">
						<i class="ph ph-spinner"></i>
						<p><?php esc_html_e( 'Loading your data tables...', 'wc-team-payroll' ); ?></p>
					</div>
				</div>
			</div>

			<!-- STEP 6: PERFORMANCE TRACKER (Goals, Achievements, Baselines) -->
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

			<!-- STEP 7: EXPORT SECTION -->
			<div class="reports-export-section">
				<p class="reports-export-label"><?php esc_html_e( 'Export Filtered Data:', 'wc-team-payroll' ); ?></p>
				<button class="reports-export-btn" id="reports-export-csv">
					<i class="ph ph-file-csv"></i>
					<?php esc_html_e( 'CSV', 'wc-team-payroll' ); ?>
				</button>
				<button class="reports-export-btn" id="reports-export-pdf">
					<i class="ph ph-file-pdf"></i>
					<?php esc_html_e( 'PDF', 'wc-team-payroll' ); ?>
				</button>
				<button class="reports-export-btn" id="reports-print-report">
					<i class="ph ph-printer"></i>
					<?php esc_html_e( 'Print', 'wc-team-payroll' ); ?>
				</button>
			</div>
		</div>

		<style>
			@keyframes spin {
				from { transform: rotate(0deg); }
				to { transform: rotate(360deg); }
			}
		</style>
		<?php
	}

	/**
	 * Enqueue assets
	 */
	public static function enqueue_assets() {
		if ( is_account_page() ) {
			// Get styling settings
			$styling_settings = get_option( 'wc_team_payroll_styling', array() );
			
			// Set default values
			$primary_color = isset( $styling_settings['primary_color'] ) ? $styling_settings['primary_color'] : '#0073aa';
			$secondary_color = isset( $styling_settings['secondary_color'] ) ? $styling_settings['secondary_color'] : '#28a745';
			$heading_color = isset( $styling_settings['heading_color'] ) ? $styling_settings['heading_color'] : '#333333';
			$text_color = isset( $styling_settings['text_color'] ) ? $styling_settings['text_color'] : '#495057';
			$link_color = isset( $styling_settings['link_color'] ) ? $styling_settings['link_color'] : '#0073aa';
			$link_hover_color = isset( $styling_settings['link_hover_color'] ) ? $styling_settings['link_hover_color'] : '#005a87';
			$background_color = isset( $styling_settings['background_color'] ) ? $styling_settings['background_color'] : '#ffffff';
			$header_background = isset( $styling_settings['header_background'] ) ? $styling_settings['header_background'] : '#f8f9fa';
			$header_border_color = isset( $styling_settings['header_border_color'] ) ? $styling_settings['header_border_color'] : '#0073aa';
			$card_background = isset( $styling_settings['card_background'] ) ? $styling_settings['card_background'] : '#f8f9fa';
			$border_color = isset( $styling_settings['border_color'] ) ? $styling_settings['border_color'] : '#e9ecef';
			$table_header_background = isset( $styling_settings['table_header_background'] ) ? $styling_settings['table_header_background'] : '#f8f9fa';
			$table_row_hover = isset( $styling_settings['table_row_hover'] ) ? $styling_settings['table_row_hover'] : '#f5f5f5';
			$table_border_color = isset( $styling_settings['table_border_color'] ) ? $styling_settings['table_border_color'] : '#dee2e6';
			
			// Handle font family (custom or predefined)
			$font_family = 'inherit';
			if ( isset( $styling_settings['font_family'] ) ) {
				if ( $styling_settings['font_family'] === 'custom' && isset( $styling_settings['custom_font_family'] ) ) {
					$font_family = $styling_settings['custom_font_family'];
				} elseif ( $styling_settings['font_family'] !== 'inherit' ) {
					$font_family = $styling_settings['font_family'];
				}
			}
			
			// Handle base font size (px or CSS variable)
			$base_font_size_unit = isset( $styling_settings['base_font_size_unit'] ) ? $styling_settings['base_font_size_unit'] : 'px';
			$base_font_size = isset( $styling_settings['base_font_size'] ) ? $styling_settings['base_font_size'] : 14;
			if ( $base_font_size_unit === 'var' ) {
				$base_font_size_css = $base_font_size;
			} else {
				$base_font_size_css = $base_font_size . 'px';
			}
			
			// Handle heading font size (px or CSS variable)
			$heading_font_size_unit = isset( $styling_settings['heading_font_size_unit'] ) ? $styling_settings['heading_font_size_unit'] : 'px';
			$heading_font_size = isset( $styling_settings['heading_font_size'] ) ? $styling_settings['heading_font_size'] : 24;
			if ( $heading_font_size_unit === 'var' ) {
				$heading_font_size_css = $heading_font_size;
			} else {
				$heading_font_size_css = $heading_font_size . 'px';
			}
			
			$button_background = isset( $styling_settings['button_background'] ) ? $styling_settings['button_background'] : '#0073aa';
			$button_text_color = isset( $styling_settings['button_text_color'] ) ? $styling_settings['button_text_color'] : '#ffffff';
			$button_hover_background = isset( $styling_settings['button_hover_background'] ) ? $styling_settings['button_hover_background'] : '#005a87';
			$button_border_radius = isset( $styling_settings['button_border_radius'] ) ? $styling_settings['button_border_radius'] : 4;
			$card_border_radius = isset( $styling_settings['card_border_radius'] ) ? $styling_settings['card_border_radius'] : 8;
			$card_shadow = isset( $styling_settings['card_shadow'] ) ? $styling_settings['card_shadow'] : 'medium';
			$remove_debug_border = isset( $styling_settings['remove_debug_border'] ) ? $styling_settings['remove_debug_border'] : 0;
			
			// Generate shadow CSS
			$shadow_css = '';
			switch ( $card_shadow ) {
				case 'none':
					$shadow_css = 'none';
					break;
				case 'light':
					$shadow_css = '0 1px 3px rgba(0,0,0,0.1)';
					break;
				case 'medium':
					$shadow_css = '0 2px 8px rgba(0,0,0,0.1)';
					break;
				case 'heavy':
					$shadow_css = '0 4px 16px rgba(0,0,0,0.15)';
					break;
				default:
					$shadow_css = '0 2px 8px rgba(0,0,0,0.1)';
			}
			
			// Enqueue Phosphor Icons
			wp_enqueue_script(
				'phosphor-icons',
				'https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2',
				array(),
				'2.1.2',
				true
			);
			
			// Enqueue Font Awesome (keeping as fallback)
			wp_enqueue_style( 
				'font-awesome', 
				'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 
				array(), 
				'6.4.0' 
			);
			
			// Enqueue shared CSS (common components for all pages)
			wp_enqueue_style( 
				'wc-team-payroll-shared', 
				WC_TEAM_PAYROLL_URL . 'assets/css/myaccount-shared.css', 
				array(), 
				WC_TEAM_PAYROLL_VERSION . '-' . time()
			);
			
			// Detect current page and enqueue page-specific CSS
			// Enqueue ALL CSS files for all pages (ensures consistent styling)
			wp_enqueue_style( 
				'wc-team-payroll-salary-details', 
				WC_TEAM_PAYROLL_URL . 'assets/css/salary-details.css', 
				array( 'wc-team-payroll-shared' ), 
				WC_TEAM_PAYROLL_VERSION . '-' . time()
			);
			
			wp_enqueue_style( 
				'wc-team-payroll-earnings', 
				WC_TEAM_PAYROLL_URL . 'assets/css/earnings.css', 
				array( 'wc-team-payroll-shared' ), 
				WC_TEAM_PAYROLL_VERSION . '-' . time()
			);
			
			wp_enqueue_style( 
				'wc-team-payroll-orders', 
				WC_TEAM_PAYROLL_URL . 'assets/css/orders.css', 
				array( 'wc-team-payroll-shared' ), 
				WC_TEAM_PAYROLL_VERSION . '-' . time()
			);
			
			wp_enqueue_style( 
				'wc-team-payroll-reports', 
				WC_TEAM_PAYROLL_URL . 'assets/css/reports.css', 
				array( 'wc-team-payroll-shared' ), 
				WC_TEAM_PAYROLL_VERSION . '-' . time()
			);

			// Generate dynamic CSS based on settings
			$dynamic_css = "
				/* Custom CSS Variables from Settings */
				:root {
					" . ( isset( $styling_settings['css_variables'] ) && ! empty( $styling_settings['css_variables'] ) ? $styling_settings['css_variables'] : '' ) . "
				}
				
				/* Dynamic Styling from Settings */
				.pv-page-wrapper {
					padding: 20px 0 !important;
					font-family: {$font_family} !important;
					background: {$background_color} !important;
					color: {$text_color} !important;
					font-size: {$base_font_size_css} !important;
					border: none !important;
					margin: 0 !important;
				}
				
				/* Employee Header */
				.wc-tp-employee-header-new {
					background: {$header_background} !important;
					border-radius: {$card_border_radius}px !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
					box-shadow: {$shadow_css} !important;
				}
				
				.wc-tp-employee-header-new .profile-picture-container {
					border-color: {$primary_color} !important;
					background: {$background_color} !important;
				}
				
				.wc-tp-employee-header-new .profile-picture-placeholder {
					background: linear-gradient(135deg, {$primary_color} 0%, {$link_hover_color} 100%) !important;
				}
				
				.wc-tp-employee-header-new .profile-name {
					color: {$heading_color} !important;
					font-family: {$font_family} !important;
				}
				
				.wc-tp-employee-header-new .profile-role {
					background: rgba(" . implode(',', sscanf($primary_color, "#%02x%02x%02x")) . ", 0.1) !important;
					color: {$primary_color} !important;
					font-family: {$font_family} !important;
				}
				
				.wc-tp-employee-header-new .info-item {
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.wc-tp-employee-header-new .info-item i {
					color: {$primary_color} !important;
				}
				
				.wc-tp-employee-header-new .info-value:hover {
					color: {$primary_color} !important;
				}
				
				.wc-tp-employee-header-new .social-icon {
					color: {$primary_color} !important;
				}
				
				.wc-tp-employee-header-new .social-icon:hover {
					color: {$secondary_color} !important;
				}
				
				.wc-tp-employee-header-new .profile-bio {
					color: {$text_color} !important;
					font-family: {$font_family} !important;
					border-top-color: {$header_border_color} !important;
				}
				
				.wc-tp-employee-header-new .header-row-2 {
					border-top-color: {$header_border_color} !important;
					border-bottom-color: {$header_border_color} !important;
				}
				
				/* General Borders - Use border_color for all other 1px borders */
				.pv-table th {
					border-bottom-color: {$border_color} !important;
				}
				
				.pv-table td {
					border-bottom-color: {$border_color} !important;
				}
				
				.table-wrapper {
					border-color: {$border_color} !important;
				}
				
				.section-header h3, .performance-tab {
					border-bottom-color: {$border_color} !important;
				}
				
				/* Headings */
				.pv-page-wrapper h2,
				.pv-page-wrapper h3 {
					color: {$heading_color} !important;
					font-family: {$font_family} !important;
				}
				
				.pv-page-wrapper h2 {
					font-size: {$heading_font_size_css} !important;
				}
				
				/* Links */
				.pv-page-wrapper a {
					color: {$link_color} !important;
				}
				
				.pv-page-wrapper a:hover {
					color: {$link_hover_color} !important;
				}
				
				/* Phosphor icons in content */
				.pv-page-wrapper .ph {
					color: {$primary_color};
				}				
				.pv-page-wrapper button .ph {
					color: {$button_text_color} !important;
				}
				
				/* Grid layouts */
				.pv-summary-grid {
					margin-bottom: 20px !important;
				}
				
				/* Cards styling */
				.pv-card {
					background: {$card_background} !important;
					border: 1px solid {$border_color} !important;
					border-radius: {$card_border_radius}px !important;
					padding: 20px !important;
					box-shadow: {$shadow_css} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				/* Card Label and Amount Wrapper */
				.card-label-amount-wrapper {
					border-bottom-color: {$border_color} !important;
				}
				
				/* Tables */
				.table-container {
					overflow-x: auto !important;
					border: none !important;
					border-radius: 0 !important;
					background: transparent !important;
				}
				
				.pv-table {
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.pv-table th {
					background: {$table_header_background} !important;
					color: {$heading_color} !important;
					border: none !important;
					border-bottom: 1px solid {$table_border_color} !important;
					font-family: {$font_family} !important;
				}
				
				.pv-table td {
					border: none !important;
					border-bottom: 1px solid {$table_border_color} !important;
					background: transparent !important;
					font-family: {$font_family} !important;
				}
				
				.pv-table tbody tr:hover {
					background: {$table_row_hover} !important;
				}
				
				/* Search and Controls */
				.pv-table-controls .search-control input {
					border: 1px solid {$border_color} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.pv-table-controls .search-control input:focus {
					border-color: {$primary_color} !important;
					outline: none !important;
					box-shadow: 0 0 0 2px rgba(" . implode(',', sscanf($primary_color, "#%02x%02x%02x")) . ", 0.2) !important;
				}
				
				/* Filter Controls */
				.pv-table-controls .filter-control select,
				.pv-table-controls .filter-control input[type=\"date\"] {
					border: 1px solid {$border_color} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.pv-table-controls .filter-control select:focus,
				.pv-table-controls .filter-control input[type=\"date\"]:focus {
					border-color: {$primary_color} !important;
					outline: none !important;
					box-shadow: 0 0 0 2px rgba(" . implode(',', sscanf($primary_color, "#%02x%02x%02x")) . ", 0.2) !important;
				}
				
				.pv-table-controls .filter-group input,
				.pv-table-controls .filter-group select,
				.pv-filter-container .filter-group input,
				.pv-filter-container .filter-group select,
				.report-filters .filter-group input,
				.report-filters .filter-group select {
					border: 1px solid {$border_color} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.pv-table-controls .filter-group input:focus,
				.pv-table-controls .filter-group select:focus,
				.pv-filter-container .filter-group input:focus,
				.pv-filter-container .filter-group select:focus,
				.report-filters .filter-group input:focus,
				.report-filters .filter-group select:focus {
					border-color: {$primary_color} !important;
					outline: none !important;
					box-shadow: 0 0 0 2px rgba(" . implode(',', sscanf($primary_color, "#%02x%02x%02x")) . ", 0.2) !important;
				}
				
				.pv-table-controls .filter-button,
				.pv-filter-container .filter-button,
				.report-filters .filter-button {
					background: {$button_background} !important;
					color: {$button_text_color} !important;
					font-family: {$font_family} !important;
					border-radius: {$button_border_radius}px !important;
				}
				
				.pv-table-controls .filter-button:hover,
				.pv-filter-container .filter-button:hover,
				.report-filters .filter-button:hover {
					background: {$button_hover_background} !important;
				}
				
				.pv-table-controls .btn-clear-filters, .performance-tab{
					background: rgba(233, 236, 239, 0.1) !important;
					border: 1px solid rgba(233, 236, 239, 0.3) !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
					outline: none !important;
					transition: all 0.2s ease !important;
				}
				
				.pv-table-controls .btn-clear-filters .ph, .performance-tab .ph {
					color: {$primary_color} !important;
				}
				
				.pv-table-controls .btn-clear-filters:hover, .performance-tab:hover {
					background-color: rgba(" . implode(',', sscanf($border_color, "#%02x%02x%02x")) . ", 0.1) !important;
				}
				
				/* Clear button active state (when filters are changed) */
				.pv-table-controls .btn-clear-filters.filters-active, .performance-tab.active {
					background: {$button_background} !important;
					color: {$button_text_color} !important;
					border-color: {$button_background} !important;
				}
				
				.pv-table-controls .btn-clear-filters.filters-active .ph {
					color: {$button_text_color} !important;
				}
				
				.pv-table-controls .btn-clear-filters.filters-active:hover {
					background: {$button_hover_background} !important;
					border-color: {$button_hover_background} !important;
				}
				
				.pv-table-controls .btn-clear-filters:focus {
					outline: none !important;
					box-shadow: none !important;
				}
				
				.pv-filter-container,
				.report-filters {
					border: 1px solid {$border_color} !important;
					background: {$card_background} !important;
				}
				
				.pv-table-controls .per-page-control select,
				.pv-table-controls .view-control select {
					border: 1px solid {$border_color} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.pv-table-controls .pv-custom-date-range input[type=\"date\"] {
					border: 1px solid {$border_color} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.pv-table-controls .pv-custom-date-range input[type=\"date\"]:focus {
					border-color: {$primary_color} !important;
					outline: none !important;
					box-shadow: 0 0 0 2px rgba(" . implode(',', sscanf($primary_color, "#%02x%02x%02x")) . ", 0.2) !important;
				}
				
				.pv-table-controls .date-separator {
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.pv-table-controls .pv-custom-date-dropdown {
					background: {$card_background} !important;
					border: 1px solid {$border_color} !important;
				}
				
				.pv-table-controls .pv-date-input-group input[type=\"date\"] {
					border: 1px solid {$border_color} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
					background: {$background_color} !important;
				}
				
				.pv-table-controls .pv-date-input-group input[type=\"date\"]:focus {
					border-color: {$primary_color} !important;
					outline: none !important;
					box-shadow: 0 0 0 2px rgba(" . implode(',', sscanf($primary_color, "#%02x%02x%02x")) . ", 0.2) !important;
				}
				
				.pv-table-controls .pv-date-input-group label {
					color: {$text_color} !important;
					font-family: {$font_family} !important;
				}
				
				.pv-table-controls .pv-date-apply {
					background: {$button_background} !important;
					color: {$button_text_color} !important;
					font-family: {$font_family} !important;
					border-radius: {$button_border_radius}px !important;
				}
				
				.pv-table-controls .pv-date-apply:hover {
					background: {$button_hover_background} !important;
				}
				
				.pv-table-controls .pv-date-cancel {
					border: 1px solid {$border_color} !important;
					color: {$text_color} !important;
					font-family: {$font_family} !important;
					border-radius: {$button_border_radius}px !important;
				}
				
				.pv-table-controls .pv-date-cancel:hover {
					background: rgba(" . implode(',', sscanf($border_color, "#%02x%02x%02x")) . ", 0.1) !important;
				}
				
				/* Pagination */
				.page-btn {
					border: 1px solid {$button_background} !important;
					background: transparent !important;
					color: {$button_background} !important;
					font-family: {$font_family} !important;
					border-radius: {$button_border_radius}px !important;
				}
				
				.page-btn i {
					color: {$button_background} !important;
				}
				
				.page-btn:hover {
					background: rgba(" . implode(',', sscanf($button_background, "#%02x%02x%02x")) . ", 0.1) !important;
					border-color: {$button_background} !important;
				}
				
				.page-btn.current-page {
					background: {$button_background} !important;
					color: {$button_text_color} !important;
					border-color: {$button_background} !important;
				}
				
				.page-btn.current-page i {
					color: {$button_text_color} !important;
				}
				
				/* Amount styling */
				.pv-page-wrapper .amount,
				.pv-page-wrapper .amount-earned {
					color: {$secondary_color} !important;
					font-weight: 600 !important;
				}
				
				/* Buttons */
				.pv-page-wrapper button,
				.pv-page-wrapper .btn {
					background: {$button_background} !important;
					color: {$button_text_color} !important;
					border: none !important;
					border-radius: {$button_border_radius}px !important;
					font-family: {$font_family} !important;
					cursor: pointer !important;
					transition: background-color 0.2s ease !important;
				}
				
				.pv-page-wrapper button:hover,
				.pv-page-wrapper .btn:hover {
					background: {$button_hover_background} !important;
				}
				
				/* Status badges */
				.status-badge {
					border-radius: {$button_border_radius}px !important;
					font-family: {$font_family} !important;
				}
				
				/* Salary type badges */
				.salary-type-badge {
					border-radius: {$button_border_radius}px !important;
					font-family: {$font_family} !important;
				}
				
				/* Commission note */
				.commission-note {
					background: {$card_background} !important;
					border: 1px solid {$border_color} !important;
					border-radius: {$card_border_radius}px !important;
					color: {$primary_color} !important;
					font-family: {$font_family} !important;
				}
				
				/* Salary type note */
				.salary-type-note {
					background: {$card_background} !important;
					border: 1px solid {$border_color} !important;
					border-radius: {$card_border_radius}px !important;
					color: {$primary_color} !important;
					font-family: {$font_family} !important;
				}
				
				/* Section Headings - All Pages (Shared Pattern) */
				.pv-section-wrapper h3 {
					color: {$heading_color} !important;
					font-family: {$font_family} !important;
					border-bottom-color: {$border_color} !important;
				}
				
				.pv-section-wrapper h3::after {
					background: {$primary_color} !important;
				}
				
				/* Table Wrapper Card */
				.table-wrapper {
					background: {$background_color} !important;
					border: 1px solid {$border_color} !important;
					border-radius: 10px !important;
					box-shadow: {$shadow_css} !important;
					padding: 20px !important;
					font-family: {$font_family} !important;
				}
				
				/* Price amounts in salary information */
				.pv-section-wrapper .woocommerce-Price-amount {
					color: {$primary_color} !important;
				}
			";

			// Add the dynamic CSS to shared stylesheet
			wp_add_inline_style( 'wc-team-payroll-shared', $dynamic_css );

			// Add inline CSS fallback to ensure styles load
			$fallback_css = "
				/* Fallback CSS - Ensures styles load even if files are missing */
				.earnings-summary { displaywc-team-payroll-salary-details
				: grid; grid-template-columns: repeat(4, 1fr) !important; gap: 20px; }
				.earning-card { background: #fff; border: 1px solid #e9ecef; border-left: 4px solid; border-radius: 8px; padding: 20px; position: relative; }
				.earning-card .card-header { position: absolute !important; top: 0 !important; right: 0 !important; }
				.order-card .card-header { position: relative !important; top: -5px !important; right: 0 !important; }
				.earning-card .card-label-amount-wrapper { border-bottom: 1px solid #e9ecef; padding-bottom: 15px; margin-bottom: 15px; }
				.earning-card .card-label { font-size: 17px; font-weight: 600; text-transform: uppercase; }
				.earning-card .amount { font-size: 17px; font-weight: 600; }
				.order-card .card-label-amount-wrapper { border-bottom: 1px solid; padding-bottom: 15px; gap: 10px; }
				.order-card .card-label { font-size: 17px; font-weight: 600; text-transform: uppercase; margin: 0; }
				.order-card .amount { font-size: 17px; font-weight: 600; margin: 0; }
				.orders-summary { display: grid; grid-template-columns: repeat(3, 1fr) !important; gap: 20px !important; margin-bottom: 20px !important; }
				@media (max-width: 1200px) { .earnings-summary { grid-template-columns: repeat(2, 1fr) !important; } .orders-summary { grid-template-columns: repeat(2, 1fr); } }
				@media (max-width: 768px) { .earnings-summary { grid-template-columns: 1fr !important; } .orders-summary { grid-template-columns: repeat(2, 1fr); } }
				@media (max-width: 480px) { .earnings-summary { grid-template-columns: 1fr; gap: 15px; } .orders-summary { grid-template-columns: 1fr; gap: 15px; } }
			";
			wp_add_inline_style( 'wc-team-payroll-shared', $fallback_css );

			// Add custom CSS from settings
			$custom_css = get_option( 'wc_team_payroll_styling', array() );
			if ( isset( $custom_css['custom_css'] ) && ! empty( $custom_css['custom_css'] ) ) {
				wp_add_inline_style( 'wc-team-payroll-shared', $custom_css['custom_css'] );
			}

			// Enqueue jQuery for AJAX and icon injection
			wp_enqueue_script( 'jquery' );
			
			// Add inline script to inject Phosphor icons
			wp_add_inline_script( 'phosphor-icons', '
				jQuery(document).ready(function($) {
					// Function to add Phosphor icons to menu items
					function addMyAccountIcons() {
						// Remove any existing icons first
						$(".woocommerce-MyAccount-navigation a i").remove();
						
						// Add Phosphor icons to menu items
						$(".woocommerce-MyAccount-navigation a[href*=\'salary-details\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'ph ph-briefcase\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block;\'></i>");
							}
						});
						
						$(".woocommerce-MyAccount-navigation a[href*=\'my-earnings\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'ph ph-wallet\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block;\'></i>");
							}
						});
						
						$(".woocommerce-MyAccount-navigation a[href*=\'orders-commission\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'ph ph-shopping-bag\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block;\'></i>");
							}
						});
						
						$(".woocommerce-MyAccount-navigation a[href*=\'reports\']").each(function() {
							if (!$(this).find("i").length) {
								$(this).prepend("<i class=\'ph ph-chart-bar\' style=\'margin-right: 8px; font-size: 20px; width: 20px; text-align: center; display: inline-block;\'></i>");
							}
						});
					}
					
					// Add icons on page load
					addMyAccountIcons();
					
					// Re-add icons after any AJAX updates (in case menu is refreshed)
					$(document).ajaxComplete(function() {
						setTimeout(addMyAccountIcons, 100);
					});
					
					// Copy to clipboard functionality for header info
					$(document).on("click", ".wc-tp-employee-header-new [data-copy], .wc-tp-employee-header-new .info-item i[data-copy]", function(e) {
						e.preventDefault();
						var textToCopy = $(this).data("copy");
						
						if (!textToCopy || textToCopy === "---") {
							return;
						}
						
						// Copy to clipboard
						var tempInput = $("<input>");
						$("body").append(tempInput);
						tempInput.val(textToCopy).select();
						document.execCommand("copy");
						tempInput.remove();
						
						// Show notification
						var notification = $("<div class=\"copy-notification\">Copied!</div>");
						$("body").append(notification);
						
						// Auto-hide after 3 seconds
						setTimeout(function() {
							notification.fadeOut(300, function() {
								$(this).remove();
							});
						}, 3000);
					});
				});
			' );

			// Enqueue Reports JavaScript
			wp_enqueue_script(
				'wc-team-payroll-reports',
				WC_TEAM_PAYROLL_URL . 'assets/js/reports.js',
				array( 'jquery' ),
				WC_TEAM_PAYROLL_VERSION . '-' . time(),
				true
			);

			// Enqueue Performance Tracker CSS
			wp_enqueue_style(
				'wc-team-payroll-performance-tracker',
				WC_TEAM_PAYROLL_URL . 'assets/css/performance-tracker.css',
				array( 'wc-team-payroll-reports' ),
				WC_TEAM_PAYROLL_VERSION . '-' . time()
			);

			// Enqueue Performance Tracker JavaScript
			wp_enqueue_script(
				'wc-team-payroll-performance-tracker',
				WC_TEAM_PAYROLL_URL . 'assets/js/performance-tracker.js',
				array( 'jquery', 'wc-team-payroll-reports' ),
				WC_TEAM_PAYROLL_VERSION . '-' . time(),
				true
			);

			// Enqueue Chart.js for analytics charts
			wp_enqueue_script(
				'chart-js',
				'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
				array(),
				'3.9.1',
				true
			);

			// Localize reports script with AJAX data
			// Get auto-refresh interval from system config
			$system_config = get_option( 'wc_tp_system_config', array() );
			$refresh_interval = isset( $system_config['refresh_interval'] ) ? intval( $system_config['refresh_interval'] ) : 30;
			
			wp_localize_script(
				'wc-team-payroll-reports',
				'wc_tp_reports',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'wc_team_payroll_nonce' ),
					'auto_refresh_interval' => $refresh_interval, // in seconds
					'currency_symbol' => html_entity_decode( get_woocommerce_currency_symbol() ),
					'currency_pos' => get_option( 'woocommerce_currency_pos', 'left' ),
				)
			);
		}
	}

	/**
	 * Generate employee header HTML
	 */
	private static function get_employee_header( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return '';
		}

		$vb_user_id = get_user_meta( $user_id, 'vb_user_id', true ) ?: '---';
		$profile_picture_id = get_user_meta( $user_id, '_wc_tp_profile_picture', true );
		$profile_picture_url = $profile_picture_id ? wp_get_attachment_url( $profile_picture_id ) : '';
		$phone = get_user_meta( $user_id, 'billing_phone', true ) ?: '---';
		$email = $user->user_email ?: '---';
		$bio = get_user_meta( $user_id, 'description', true ) ?: '---';
		$employee_status = get_user_meta( $user_id, '_wc_tp_employee_status', true ) ?: 'active';
		
		// Get salary information - check for fixed/combined flags first
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		
		if ( $is_fixed_salary ) {
			$salary_type = 'fixed';
		} elseif ( $is_combined_salary ) {
			$salary_type = 'combined';
		} else {
			$salary_type = get_user_meta( $user_id, '_wc_tp_salary_type', true ) ?: 'commission';
		}
		
		// Get user role with proper labeling
		$user_roles = $user->roles;
		$role_label = 'Employee';
		if ( in_array( 'administrator', $user_roles ) ) {
			$role_label = 'Administrator';
		} elseif ( in_array( 'shop_manager', $user_roles ) ) {
			$role_label = 'Manager';
		} elseif ( in_array( 'shop_employee', $user_roles ) ) {
			$role_label = 'Employee';
		}
		
		// Generate initials for placeholder
		$name_parts = explode( ' ', $user->display_name );
		$initials = '';
		foreach ( $name_parts as $part ) {
			$initials .= strtoupper( substr( $part, 0, 1 ) );
		}
		$initials = substr( $initials, 0, 2 );

		// Get current page URL to check if on salary-details page
		$current_url = home_url( add_query_arg( array() ) );
		$is_salary_details_page = strpos( $current_url, 'salary-details' ) !== false;

		// Get monthly achievement for badge (Phase 1: Monthly System)
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

		// Get goal achievement count for current period
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

		ob_start();
		?>
		<div class="wc-tp-employee-header-new">
			<!-- Main Container: 2 Columns (Image + Right Content) -->
			<div class="header-main-grid">
				<!-- Left Column: Profile Picture -->
				<div class="header-left-column">
					<div class="profile-picture-container">
						<?php if ( $profile_picture_url ) : ?>
							<img src="<?php echo esc_url( $profile_picture_url ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>" class="profile-picture" />
						<?php else : ?>
							<div class="profile-picture-placeholder">
								<span class="initials"><?php echo esc_html( $initials ); ?></span>
							</div>
						<?php endif; ?>
						
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
				</div>
				
				<!-- Right Column: 3 Row Containers -->
				<div class="header-right-column">
					<!-- Row 1: Name & Role -->
					<div class="header-row-1">
						<h2 class="profile-name"><?php echo esc_html( $user->display_name ); ?></h2>
						<span class="profile-role"><?php echo esc_html( $role_label ); ?></span>
					</div>
					
					<!-- Row 2: Info Items (2 Columns) -->
					<div class="header-row-2">
						<!-- Left Column: ID, Phone, Email -->
						<div class="info-column-left">
							<!-- ID (Clickable to copy) -->
							<div class="info-item">
								<i class="ph ph-identification-badge" style="cursor: pointer;" data-copy="<?php echo esc_attr( $vb_user_id ); ?>" title="Click to copy"></i>
								<span class="info-value" data-copy="<?php echo esc_attr( $vb_user_id ); ?>" title="Click to copy"><?php echo esc_html( $vb_user_id ); ?></span>
							</div>
							<!-- Phone (Clickable to call) -->
							<div class="info-item">
								<i class="ph ph-phone"></i>
								<?php if ( $phone !== '---' ) : ?>
									<a href="tel:<?php echo esc_attr( $phone ); ?>" class="info-value" style="text-decoration: none; color: inherit;"><?php echo esc_html( $phone ); ?></a>
								<?php else : ?>
									<span class="info-value"><?php echo esc_html( $phone ); ?></span>
								<?php endif; ?>
							</div>
							<!-- Email (Clickable to email) -->
							<div class="info-item">
								<i class="ph ph-envelope"></i>
								<?php if ( $email !== '---' ) : ?>
									<a href="mailto:<?php echo esc_attr( $email ); ?>" class="info-value" style="text-decoration: none; color: inherit;"><?php echo esc_html( $email ); ?></a>
								<?php else : ?>
									<span class="info-value"><?php echo esc_html( $email ); ?></span>
								<?php endif; ?>
							</div>
						</div>
						
						<!-- Right Column: Salary Type, Status, Social Icons -->
						<div class="info-column-right">
							<!-- Salary Type (Clickable to salary-details page) -->
							<div class="info-item">
								<i class="ph ph-briefcase"></i>
								<?php
								$salary_type_labels = array(
									'fixed' => __( 'Fixed Salary', 'wc-team-payroll' ),
									'commission' => __( 'Commission Based', 'wc-team-payroll' ),
									'combined' => __( 'Combined', 'wc-team-payroll' ),
								);
								$salary_label = $salary_type_labels[ $salary_type ] ?? ucfirst( $salary_type );
								$salary_details_url = wc_get_account_endpoint_url( 'salary-details' );
								?>
								<?php if ( ! $is_salary_details_page ) : ?>
									<a href="<?php echo esc_url( $salary_details_url ); ?>" class="info-value" style="text-decoration: none; color: inherit;"><?php echo esc_html( $salary_label ); ?></a>
								<?php else : ?>
									<span class="info-value"><?php echo esc_html( $salary_label ); ?></span>
								<?php endif; ?>
							</div>
							
							<!-- Status -->
							<div class="info-item">
								<i class="ph ph-check-square-offset"></i>
								<span class="info-value status-<?php echo esc_attr( $employee_status ); ?>"><?php echo esc_html( ucfirst( $employee_status ) ); ?></span>
							</div>
							
							<!-- Social Icons -->
							<div class="social-icons-row">
								<a href="#" class="social-icon facebook" title="<?php esc_attr_e( 'Facebook', 'wc-team-payroll' ); ?>">
									<i class="ph ph-facebook-logo"></i>
								</a>
								<a href="#" class="social-icon whatsapp" title="<?php esc_attr_e( 'WhatsApp', 'wc-team-payroll' ); ?>">
									<i class="ph ph-whatsapp-logo"></i>
								</a>
								<a href="#" class="social-icon instagram" title="<?php esc_attr_e( 'Instagram', 'wc-team-payroll' ); ?>">
									<i class="ph ph-instagram-logo"></i>
								</a>
								<a href="#" class="social-icon linkedin" title="<?php esc_attr_e( 'LinkedIn', 'wc-team-payroll' ); ?>">
									<i class="ph ph-linkedin-logo"></i>
								</a>
							</div>
						</div>
					</div>
					
					<!-- Row 3: Bio -->
					<div class="header-row-3">
						<p class="profile-bio"><?php echo esc_html( $bio ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Flush rewrite rules (call this on activation)
	 */
	public static function flush_rewrite_rules() {
		self::add_endpoints();
		flush_rewrite_rules();
	}
	private static function get_user_earnings_for_period( $user_id, $start_date, $end_date ) {
		// Get salary for period
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$salary_amount = floatval( get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ?: 0 );
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ?: 'monthly';
		
		$salary = 0;
		if ( $is_fixed_salary || $is_combined_salary ) {
			$salary = self::get_user_salary_for_period( $user_id, $start_date, $end_date, $salary_amount, $salary_frequency );
		}
		
		// Get commission for period
		$commission = self::get_user_commission_for_period( $user_id, $start_date, $end_date );
		
		return $salary + $commission;
	}

	/**
	 * Helper: Get user total earnings
	 */
	private static function get_user_total_earnings( $user_id ) {
		// Get all salary transactions
		$transactions = get_user_meta( $user_id, '_wc_tp_salary_transactions', true );
		$total_salary = 0;
		if ( is_array( $transactions ) ) {
			foreach ( $transactions as $transaction ) {
				if ( isset( $transaction['type'] ) && strpos( $transaction['type'], 'salary' ) !== false ) {
					$total_salary += floatval( $transaction['amount'] ?? 0 );
				}
			}
		}

		// Get all commission earnings
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		$args = array(
			'limit'  => -1,
			'status' => $commission_statuses,
		);

		$orders = wc_get_orders( $args );
		$total_commission = 0;

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$total_commission += $commission_data['agent_earnings'];
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$total_commission += $commission_data['processor_earnings'];
			}
		}

		return $total_salary + $total_commission;
	}

	/**
	 * Helper: Get user total paid amount
	 */
	private static function get_user_total_paid( $user_id ) {
		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			return 0;
		}

		$total_paid = 0;
		foreach ( $payments as $payment ) {
			$total_paid += floatval( $payment['amount'] ?? 0 );
		}

		return $total_paid;
	}

	/**
	 * Helper: Get user monthly history
	 */
	private static function get_user_monthly_history( $user_id, $months = 12 ) {
		$history = array();
		
		// Get user's start date - check for custom employee start date first
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return $history;
		}
		
		// Check for custom employee start date meta field
		$employee_start_date = get_user_meta( $user_id, '_wc_tp_employee_start_date', true );
		
		// If no custom start date, use user_registered
		if ( ! $employee_start_date ) {
			$start_date = date( 'Y-m-d', strtotime( $user->user_registered ) );
		} else {
			$start_date = date( 'Y-m-d', strtotime( $employee_start_date ) );
		}
		
		// Get user salary info
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$salary_amount = floatval( get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ?: 0 );
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ?: 'monthly';
		
		for ( $i = 0; $i < $months; $i++ ) {
			$date = date( 'Y-m', strtotime( "-{$i} months" ) );
			$month_start = date( 'Y-m-01', strtotime( $date ) );
			$month_end = date( 'Y-m-t', strtotime( $date ) );
			
			// Skip months before employee start date
			if ( strtotime( $month_end ) < strtotime( $start_date ) ) {
				continue;
			}
			
			// Get commission earnings for this month
			$commission = self::get_user_commission_for_period( $user_id, $month_start, $month_end );
			
			// Get salary earnings for this month
			$salary = 0;
			if ( $is_fixed_salary || $is_combined_salary ) {
				$salary = self::get_user_salary_for_period( $user_id, $month_start, $month_end, $salary_amount, $salary_frequency );
			}
			
			// Get payments for this month
			$payments = self::get_user_payments_for_period( $user_id, $month_start, $month_end );
			
			// Get orders count for this month
			$orders_count = self::get_user_orders_count_for_period( $user_id, $month_start, $month_end );
			
			$total = $salary + $commission;
			$due = $total - $payments;
			
			$history[] = array(
				'date' => $date,
				'salary' => $salary,
				'commission' => $commission,
				'total' => $total,
				'paid' => $payments,
				'due' => $due,
				'orders_count' => $orders_count,
			);
		}
		return array_reverse( $history );
	}

	/**
	 * Helper: Get user commission for a period
	 */
	private static function get_user_commission_for_period( $user_id, $start_date, $end_date ) {
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		
		// Use proper date range with time to ensure full day coverage
		$date_query = $start_date . ' 00:00:00...' . $end_date . ' 23:59:59';
		
		$args = array(
			'limit'  => -1,
			'status' => $commission_statuses,
			'date_created' => $date_query,
		);

		$orders = wc_get_orders( $args );
		$total_commission = 0;

		foreach ( $orders as $order ) {

			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$total_commission += $commission_data['agent_earnings'];
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$total_commission += $commission_data['processor_earnings'];
			}
		}

		return $total_commission;
	}

	/**
	 * Helper: Get user salary for a period
	 */
	private static function get_user_salary_for_period( $user_id, $start_date, $end_date, $salary_amount, $salary_frequency ) {
		// Get salary transactions for this period
		$transactions = get_user_meta( $user_id, '_wc_tp_salary_transactions', true );
		if ( ! is_array( $transactions ) ) {
			return 0;
		}

		$total_salary = 0;
		foreach ( $transactions as $transaction ) {
			if ( ! isset( $transaction['date'] ) ) {
				continue;
			}

			$trans_date = date( 'Y-m-d', strtotime( $transaction['date'] ) );
			if ( $trans_date >= $start_date && $trans_date <= $end_date ) {
				// Check for transfer types (daily_transfer, weekly_transfer, monthly_transfer, partial_transfer)
				if ( isset( $transaction['type'] ) && strpos( $transaction['type'], 'transfer' ) !== false ) {
					$total_salary += floatval( $transaction['amount'] ?? 0 );
				}
			}
		}

		return $total_salary;
	}

	/**
	 * Helper: Get user payments for a period
	 */
	private static function get_user_payments_for_period( $user_id, $start_date, $end_date ) {
		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			return 0;
		}

		$total_paid = 0;
		foreach ( $payments as $payment ) {
			if ( ! isset( $payment['date'] ) ) {
				continue;
			}

			$payment_date = date( 'Y-m-d', strtotime( $payment['date'] ) );
			if ( $payment_date >= $start_date && $payment_date <= $end_date ) {
				$total_paid += floatval( $payment['amount'] ?? 0 );
			}
		}

		return $total_paid;
	}

	/**
	 * Helper: Get user orders count for a period
	 */
	private static function get_user_orders_count_for_period( $user_id, $start_date, $end_date ) {
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		
		// Use proper date range with time to ensure full day coverage
		$date_query = $start_date . ' 00:00:00...' . $end_date . ' 23:59:59';
		
		$args = array(
			'limit'  => -1,
			'status' => $commission_statuses,
			'date_created' => $date_query,
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
	 * Helper: Get user's last payment
	 */
	private static function get_user_last_payment( $user_id ) {
		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		
		if ( ! is_array( $payments ) || empty( $payments ) ) {
			return array(
				'amount' => 0,
				'date' => '',
				'method' => '',
			);
		}
		
		// Get the last payment (payments are typically in chronological order)
		$last_payment = end( $payments );
		
		return array(
			'amount' => floatval( $last_payment['amount'] ?? 0 ),
			'date' => isset( $last_payment['date'] ) ? date( 'd-m-Y', strtotime( $last_payment['date'] ) ) : '',
			'method' => $last_payment['method'] ?? '',
		);
	}

	/**
	 * Helper: Get user daily history from start date
	 */
	private static function get_user_daily_history( $user_id, $is_fixed_salary, $is_combined_salary, $salary_amount, $salary_frequency ) {
		$history = array();
		
		// Get user's start date - check for custom employee start date first
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return $history;
		}
		
		// Check for custom employee start date meta field
		$employee_start_date = get_user_meta( $user_id, '_wc_tp_employee_start_date', true );
		
		// If no custom start date, use user_registered
		if ( ! $employee_start_date ) {
			$start_date = date( 'Y-m-d', strtotime( $user->user_registered ) );
		} else {
			$start_date = date( 'Y-m-d', strtotime( $employee_start_date ) );
		}
		
		$today = date( 'Y-m-d' );
		
		// Generate all days from start date to today
		$current_date = new DateTime( $start_date );
		$end_date_obj = new DateTime( $today );
		
		while ( $current_date <= $end_date_obj ) {
			$date_str = $current_date->format( 'Y-m-d' );
			$next_date = clone $current_date;
			$next_date->modify( '+1 day' );
			$next_date_str = $next_date->format( 'Y-m-d' );
			
			// Get commission earnings for this day
			$commission = self::get_user_commission_for_period( $user_id, $date_str, $date_str );
			
			// Get salary earnings for this day
			$salary = 0;
			if ( $is_fixed_salary || $is_combined_salary ) {
				$salary = self::get_user_salary_for_period( $user_id, $date_str, $date_str, $salary_amount, $salary_frequency );
			}
			
			// Get payments for this day
			$payments = self::get_user_payments_for_period( $user_id, $date_str, $date_str );
			
			// Get orders count for this day
			$orders_count = self::get_user_orders_count_for_period( $user_id, $date_str, $date_str );
			
			$total = $salary + $commission;
			$due = $total - $payments;
			
			// Only add if there's activity or if it's today
			if ( $total > 0 || $payments > 0 || $date_str === $today ) {
				$history[] = array(
					'date' => date( 'F j, Y', strtotime( $date_str ) ), // Format: April 12, 2026
					'salary' => $salary,
					'commission' => $commission,
					'total' => $total,
					'paid' => $payments,
					'due' => $due,
					'orders_count' => $orders_count,
				);
			}
			
			$current_date->modify( '+1 day' );
		}
		
		return array_reverse( $history );
	}

	/**
	 * Helper: Get user weekly history from start date
	 */
	private static function get_user_weekly_history( $user_id, $is_fixed_salary, $is_combined_salary, $salary_amount, $salary_frequency ) {
		$history = array();
		
		// Get user's start date - check for custom employee start date first
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return $history;
		}
		
		// Check for custom employee start date meta field
		$employee_start_date = get_user_meta( $user_id, '_wc_tp_employee_start_date', true );
		
		// If no custom start date, use user_registered
		if ( ! $employee_start_date ) {
			$start_date = date( 'Y-m-d', strtotime( $user->user_registered ) );
		} else {
			$start_date = date( 'Y-m-d', strtotime( $employee_start_date ) );
		}
		
		$today = date( 'Y-m-d' );
		
		// Generate weeks from start date to today
		$current_date = new DateTime( $start_date );
		$end_date_obj = new DateTime( $today );
		
		// Get the week number and year of the start date
		$weeks_processed = array();
		
		while ( $current_date <= $end_date_obj ) {
			$week_num = $current_date->format( 'W' );
			$year = $current_date->format( 'Y' );
			$week_key = $year . '-W' . $week_num;
			
			// Skip if we've already processed this week
			if ( in_array( $week_key, $weeks_processed ) ) {
				$current_date->modify( '+1 day' );
				continue;
			}
			
			$weeks_processed[] = $week_key;
			
			// Get the start and end of this week (Monday to Sunday)
			$week_start = clone $current_date;
			$week_start->modify( 'Monday this week' );
			$week_start_str = $week_start->format( 'Y-m-d' );
			
			$week_end = clone $current_date;
			$week_end->modify( 'Sunday this week' );
			$week_end_str = $week_end->format( 'Y-m-d' );
			
			// Get commission earnings for this week
			$commission = self::get_user_commission_for_period( $user_id, $week_start_str, $week_end_str );
			
			// Get salary earnings for this week
			$salary = 0;
			if ( $is_fixed_salary || $is_combined_salary ) {
				$salary = self::get_user_salary_for_period( $user_id, $week_start_str, $week_end_str, $salary_amount, $salary_frequency );
			}
			
			// Get payments for this week
			$payments = self::get_user_payments_for_period( $user_id, $week_start_str, $week_end_str );
			
			// Get orders count for this week
			$orders_count = self::get_user_orders_count_for_period( $user_id, $week_start_str, $week_end_str );
			
			$total = $salary + $commission;
			$due = $total - $payments;
			
			// Only add if there's activity
			if ( $total > 0 || $payments > 0 ) {
				$history[] = array(
					'date' => 'WK-' . $week_num . ', ' . $year, // Format: WK-5, 2026
					'salary' => $salary,
					'commission' => $commission,
					'total' => $total,
					'paid' => $payments,
					'due' => $due,
					'orders_count' => $orders_count,
				);
			}
			
			$current_date->modify( '+1 week' );
		}
		
		return array_reverse( $history );
	}

	/**
	 * AJAX: Get orders data for My Account
	 */
	public static function ajax_get_orders() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get filters
		$role_filter = isset( $_POST['role_filter'] ) ? sanitize_text_field( $_POST['role_filter'] ) : 'all';
		$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : '';
		$date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : '';
		$status_filter = isset( $_POST['status_filter'] ) ? sanitize_text_field( $_POST['status_filter'] ) : 'all';
		$sort_by = isset( $_POST['sort_by'] ) ? sanitize_text_field( $_POST['sort_by'] ) : 'date-desc';
		$search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		$page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 25;

		// Get orders - include all statuses
		$args = array(
			'limit'  => -1,
			'status' => 'any', // Get orders with any status
		);

		// Apply date range filter with proper time coverage
		if ( $date_from && $date_to ) {
			// Both dates provided - use range query
			$args['date_created'] = $date_from . ' 00:00:00...' . $date_to . ' 23:59:59';
		} elseif ( $date_from ) {
			// Only start date provided
			$args['date_created'] = '>=' . $date_from . ' 00:00:00';
		} elseif ( $date_to ) {
			// Only end date provided
			$args['date_created'] = '<=' . $date_to . ' 23:59:59';
		}

		$orders = wc_get_orders( $args );
		$filtered_orders = array();
		$total_commission = 0;
		$my_total_earnings = 0;

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			// Check if user is involved in this order (even without commission data)
			$is_agent = intval( $agent_id ) === intval( $user_id );
			$is_processor = intval( $processor_id ) === intval( $user_id );
			
			// Determine user role (if both, show as agent)
			$user_role = null;
			if ( $is_agent ) {
				$user_role = 'agent';
			} elseif ( $is_processor ) {
				$user_role = 'processor';
			}

			// Skip if user is not involved in this order at all
			if ( ! $user_role ) {
				continue;
			}

			// Apply role filter
			if ( $role_filter !== 'all' && $role_filter !== $user_role ) {
				continue;
			}

			// Apply status filter
			if ( $status_filter !== 'all' && $order->get_status() !== $status_filter ) {
				continue;
			}

			// Apply search filter
			if ( $search ) {
				$search_fields = array(
					$order->get_id(),
					$order->get_billing_first_name(),
					$order->get_billing_last_name(),
					$order->get_billing_email(),
				);
				$search_match = false;
				foreach ( $search_fields as $field ) {
					if ( stripos( $field, $search ) !== false ) {
						$search_match = true;
						break;
					}
				}
				if ( ! $search_match ) {
					continue;
				}
			}

			// Handle commission data - only for configured commission statuses
			$order_status = $order->get_status();
			$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
			$has_commission = $commission_data && in_array( $order_status, $commission_statuses );
			
			// Get attributed order value
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
			
			if ( $has_commission ) {
				// Calculate earnings based on role(s)
				if ( $is_agent && $is_processor ) {
					// Owner gets both agent and processor earnings
					$my_earning = floatval( $commission_data['agent_earnings'] ) + floatval( $commission_data['processor_earnings'] );
				} elseif ( $user_role === 'agent' ) {
					$my_earning = $commission_data['agent_earnings'];
				} else {
					$my_earning = $commission_data['processor_earnings'];
				}
				$order_commission = $commission_data['total_commission'];
				$total_commission += $order_commission;
				$my_total_earnings += $my_earning;
			} else {
				// No commission for non-completed/processing orders
				$my_earning = 0;
				$order_commission = 0;
			}

			$filtered_orders[] = array(
				'order_id' => $order->get_id(),
				'date' => $order->get_date_created()->format( 'M j, Y' ),
				'time' => $order->get_date_created()->format( 'g:i A' ),
				'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'customer_email' => $order->get_billing_email(),
				'my_role' => $user_role,
				'my_role_label' => $user_role === 'agent' ? __( 'Agent', 'wc-team-payroll' ) : __( 'Processor', 'wc-team-payroll' ),
				'total' => wp_kses_post( wc_price( $order->get_total() ) ),
				'attributed' => $attributed_value > 0 ? wp_kses_post( wc_price( $attributed_value ) ) : '—',
				'commission' => wp_kses_post( wc_price( $order_commission ) ),
				'earning' => wp_kses_post( wc_price( $my_earning ) ),
				'status' => $order->get_status(),
				'status_label' => wc_get_order_status_name( $order->get_status() ),
				'edit_url' => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
				'total_raw' => $order->get_total(),
				'total_amount' => $order->get_total(),
				'attributed_amount' => $attributed_value,
				'commission_amount' => $order_commission,
				'earning_raw' => $my_earning,
				'earning_amount' => $my_earning,
				'date_raw' => $order->get_date_created()->getTimestamp(),
				'date_timestamp' => $order->get_date_created()->getTimestamp(),
				'has_commission' => $has_commission,
			);
		}

		// Sort orders
		usort( $filtered_orders, function( $a, $b ) use ( $sort_by ) {
			switch ( $sort_by ) {
				case 'date-asc':
					return $a['date_raw'] - $b['date_raw'];
				case 'date-desc':
					return $b['date_raw'] - $a['date_raw'];
				case 'total-asc':
					return $a['total_raw'] - $b['total_raw'];
				case 'total-desc':
					return $b['total_raw'] - $a['total_raw'];
				case 'earning-asc':
					return $a['earning_raw'] - $b['earning_raw'];
				case 'earning-desc':
					return $b['earning_raw'] - $a['earning_raw'];
				default:
					return $b['date_raw'] - $a['date_raw'];
			}
		} );

		// Pagination
		$total_orders = count( $filtered_orders );
		$total_pages = ceil( $total_orders / $per_page );
		$offset = ( $page - 1 ) * $per_page;
		$paged_orders = array_slice( $filtered_orders, $offset, $per_page );

		// Calculate status counts dynamically from filtered orders
		$status_counts = array();
		
		// Get all WooCommerce statuses (excluding draft and failed)
		$all_statuses = wc_get_order_statuses();
		foreach ( $all_statuses as $status_key => $status_label ) {
			$clean_status = str_replace( 'wc-', '', $status_key );
			if ( $clean_status !== 'draft' && $clean_status !== 'failed' ) {
				$status_counts[ 'status_' . $clean_status ] = 0;
			}
		}

		// Count orders by status
		foreach ( $filtered_orders as $order_data ) {
			$status_key = 'status_' . $order_data['status'];
			if ( isset( $status_counts[ $status_key ] ) ) {
				$status_counts[ $status_key ]++;
			}
		}

		// Build summary with dynamic status counts
		$summary = array(
			'total_orders' => $total_orders,
			'total_commission' => wp_kses_post( wc_price( $total_commission ) ),
			'my_earnings' => wp_kses_post( wc_price( $my_total_earnings ) ),
		);
		
		// Merge status counts into summary
		$summary = array_merge( $summary, $status_counts );

		wp_send_json_success( array(
			'orders' => $paged_orders,
			'summary' => $summary,
			'pagination' => array(
				'current_page' => $page,
				'total_pages' => $total_pages,
				'per_page' => $per_page,
				'total' => $total_orders,
				'start' => $offset + 1,
				'end' => min( $offset + $per_page, $total_orders ),
			),
		) );
	}

	/**
	 * AJAX: Get order details
	 */
	public static function ajax_get_order_details() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$order_id = intval( $_POST['order_id'] );
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found', 'wc-team-payroll' ) );
		}

		$agent_id = $order->get_meta( '_primary_agent_id' );
		$processor_id = $order->get_meta( '_processor_user_id' );
		$commission_data = $order->get_meta( '_commission_data' );

		// Check if user is involved in this order
		if ( intval( $agent_id ) !== intval( $user_id ) && intval( $processor_id ) !== intval( $user_id ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$agent = get_user_by( 'ID', $agent_id );
		$processor = get_user_by( 'ID', $processor_id );

		ob_start();
		?>
		<div class="order-details-content">
			<div class="order-header">
				<h4><?php esc_html_e( 'Order Information', 'wc-team-payroll' ); ?></h4>
				<div class="order-basic-info">
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Order ID:', 'wc-team-payroll' ); ?></span>
						<span class="value">#<?php echo esc_html( $order_id ); ?></span>
					</div>
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Date:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo esc_html( $order->get_date_created()->format( 'F j, Y g:i A' ) ); ?></span>
					</div>
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Status:', 'wc-team-payroll' ); ?></span>
						<span class="value">
							<span class="status-badge status-<?php echo esc_attr( $order->get_status() ); ?>">
								<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
							</span>
						</span>
					</div>
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Total:', 'wc-team-payroll' ); ?></span>
						<span class="value amount"><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></span>
					</div>
				</div>
			</div>

			<div class="customer-info">
				<h4><?php esc_html_e( 'Customer Information', 'wc-team-payroll' ); ?></h4>
				<div class="customer-details">
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Name:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></span>
					</div>
					<div class="info-row">
						<span class="label"><?php esc_html_e( 'Email:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo esc_html( $order->get_billing_email() ); ?></span>
					</div>
					<?php if ( $order->get_billing_phone() ) : ?>
						<div class="info-row">
							<span class="label"><?php esc_html_e( 'Phone:', 'wc-team-payroll' ); ?></span>
							<span class="value"><?php echo esc_html( $order->get_billing_phone() ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="team-assignment">
				<h4><?php esc_html_e( 'Team Assignment', 'wc-team-payroll' ); ?></h4>
				<div class="team-details">
					<div class="team-member">
						<span class="role-label"><?php esc_html_e( 'Agent:', 'wc-team-payroll' ); ?></span>
						<span class="member-info">
							<?php if ( $agent ) : ?>
								<?php echo esc_html( $agent->display_name ); ?>
								<?php if ( intval( $agent_id ) === intval( $user_id ) ) : ?>
									<span class="you-badge"><?php esc_html_e( '(You)', 'wc-team-payroll' ); ?></span>
								<?php endif; ?>
							<?php else : ?>
								<?php esc_html_e( 'Not assigned', 'wc-team-payroll' ); ?>
							<?php endif; ?>
						</span>
					</div>
					<div class="team-member">
						<span class="role-label"><?php esc_html_e( 'Processor:', 'wc-team-payroll' ); ?></span>
						<span class="member-info">
							<?php if ( $processor ) : ?>
								<?php echo esc_html( $processor->display_name ); ?>
								<?php if ( intval( $processor_id ) === intval( $user_id ) ) : ?>
									<span class="you-badge"><?php esc_html_e( '(You)', 'wc-team-payroll' ); ?></span>
								<?php endif; ?>
							<?php else : ?>
								<?php esc_html_e( 'Not assigned', 'wc-team-payroll' ); ?>
							<?php endif; ?>
						</span>
					</div>
				</div>
			</div>

			<div class="commission-breakdown">
				<h4><?php esc_html_e( 'Commission Breakdown', 'wc-team-payroll' ); ?></h4>
				<div class="commission-details">
					<div class="commission-row total">
						<span class="label"><?php esc_html_e( 'Total Commission:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo wp_kses_post( wc_price( $commission_data['total_commission'] ) ); ?></span>
					</div>
					<div class="commission-row">
						<span class="label"><?php esc_html_e( 'Agent Earnings:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo wp_kses_post( wc_price( $commission_data['agent_earnings'] ) ); ?></span>
					</div>
					<div class="commission-row">
						<span class="label"><?php esc_html_e( 'Processor Earnings:', 'wc-team-payroll' ); ?></span>
						<span class="value"><?php echo wp_kses_post( wc_price( $commission_data['processor_earnings'] ) ); ?></span>
					</div>
					<?php if ( ! empty( $commission_data['extra_earnings'] ) ) : ?>
						<div class="extra-earnings">
							<h5><?php esc_html_e( 'Extra Earnings:', 'wc-team-payroll' ); ?></h5>
							<?php foreach ( $commission_data['extra_earnings'] as $extra ) : ?>
								<div class="commission-row">
									<span class="label"><?php echo esc_html( $extra['label'] ); ?>:</span>
									<span class="value"><?php echo wp_kses_post( wc_price( $extra['amount'] ) ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Get filtered dashboard KPI data
	 * Updated: v1.0.78 - Salary from transactions filtered by date
	 */
	public static function ajax_get_filtered_dashboard_data() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get filters from request
		$filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();

		// Get date range
		$date_range = self::get_date_range_from_filter( $filters );
		$start_date = $date_range['start'];
		$end_date = $date_range['end'];

		// Get filter values
		$role_filter = isset( $filters['role'] ) ? $filters['role'] : 'all';
		$status_filter = isset( $filters['orderStatus'] ) ? $filters['orderStatus'] : 'all';

		// Get commission calculation statuses from settings
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();

		// Prepare order statuses for query based on filter
		$order_statuses = $commission_statuses; // Default: all commission statuses
		if ( $status_filter !== 'all' ) {
			// If user selected a specific status, use only that status
			$order_statuses = array( $status_filter );
		}

		// Get user earnings data with status filtering
		$engine = new WC_Team_Payroll_Core_Engine();
		$earnings_data = $engine->get_user_earnings( $user_id, $start_date, $end_date, $order_statuses );

		// Filter orders by role if needed
		$filtered_orders = $earnings_data['orders'];
		if ( $role_filter !== 'all' ) {
			$filtered_orders = array_filter( $filtered_orders, function( $order ) use ( $role_filter ) {
				return $order['role'] === $role_filter;
			});
		}

		// Calculate KPI metrics from filtered data
		$total_commission = 0; // Commission from filtered orders
		$total_orders = count( $filtered_orders );
		$total_order_value = 0;

		foreach ( $filtered_orders as $order_data ) {
			$total_commission += $order_data['earnings']; // This is the user's commission earnings
			$total_order_value += $order_data['total'];
		}

		// Calculate attributed order total using the same method as Performance Metrics
		// This correctly handles cases where user is both agent and processor
		$attributed_order_total = 0;
		
		// Determine which statuses to query for attributed total
		$statuses_for_attributed = $order_statuses;
		
		// Query args for attributed total
		$attributed_args = array(
			'limit'        => -1,
			'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
			'status'       => $statuses_for_attributed,
			'return'       => 'ids',
		);
		
		// Get orders where user is agent
		$agent_order_ids = wc_get_orders( array_merge( $attributed_args, array(
			'meta_key'   => '_primary_agent_id',
			'meta_value' => $user_id,
		) ) );
		
		// Get orders where user is processor
		$processor_order_ids = wc_get_orders( array_merge( $attributed_args, array(
			'meta_key'   => '_processor_user_id',
			'meta_value' => $user_id,
		) ) );
		
		// Apply role filter to attributed calculation
		if ( $role_filter === 'agent' ) {
			// Only count agent orders
			foreach ( $agent_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['agent_order_value'] ) ) {
					$attributed_order_total += floatval( $commission_data['agent_order_value'] );
				}
			}
		} elseif ( $role_filter === 'processor' ) {
			// Only count processor orders
			foreach ( $processor_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['processor_order_value'] ) ) {
					$attributed_order_total += floatval( $commission_data['processor_order_value'] );
				}
			}
		} else {
			// Count both agent and processor orders
			foreach ( $agent_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['agent_order_value'] ) ) {
					$attributed_order_total += floatval( $commission_data['agent_order_value'] );
				}
			}
			
			foreach ( $processor_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['processor_order_value'] ) ) {
					$attributed_order_total += floatval( $commission_data['processor_order_value'] );
				}
			}
		}

		$avg_order_value = $total_orders > 0 ? $attributed_order_total / $total_orders : 0;
		$avg_commission = $total_orders > 0 ? $total_commission / $total_orders : 0;

		// Get salary for the filtered period (same as My Earnings This Month)
		// Read directly from transactions and filter by date range
		$salary_for_period = 0;
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		
		if ( $is_fixed_salary || $is_combined_salary ) {
			$transactions = get_user_meta( $user_id, '_wc_tp_salary_transactions', true );
			if ( is_array( $transactions ) ) {
				foreach ( $transactions as $transaction ) {
					if ( ! isset( $transaction['date'] ) ) {
						continue;
					}
					
					$trans_date = date( 'Y-m-d', strtotime( $transaction['date'] ) );
					if ( $trans_date >= $start_date && $trans_date <= $end_date ) {
						// Check for transfer types (daily_transfer, weekly_transfer, monthly_transfer, partial_transfer)
						if ( isset( $transaction['type'] ) && strpos( $transaction['type'], 'transfer' ) !== false ) {
							$salary_for_period += floatval( $transaction['amount'] ?? 0 );
						}
					}
				}
			}
		}

		// Total earnings = commission from filtered orders + salary from filtered period
		$total_earnings = $total_commission + $salary_for_period;

		// Get previous period data for comparison (with same status filtering)
		$prev_date_range = self::get_previous_period_range( $date_range['start'], $date_range['end'] );
		$prev_earnings_data = $engine->get_user_earnings( $user_id, $prev_date_range['start'], $prev_date_range['end'], $order_statuses );
		
		// Apply same role filter to previous period data
		$prev_filtered_orders = $prev_earnings_data['orders'];
		if ( $role_filter !== 'all' ) {
			$prev_filtered_orders = array_filter( $prev_filtered_orders, function( $order ) use ( $role_filter ) {
				return $order['role'] === $role_filter;
			});
		}
		
		// Calculate previous period total commission (not salary)
		$prev_total_commission = 0;
		foreach ( $prev_filtered_orders as $order_data ) {
			$prev_total_commission += $order_data['earnings'];
		}

		// Calculate change percentage (commission only, since salary doesn't change with filters)
		$earnings_change = 0;
		if ( $prev_total_commission > 0 ) {
			$earnings_change = ( ( $total_commission - $prev_total_commission ) / $prev_total_commission ) * 100;
		}

		// Determine change direction
		$change_class = 'neutral';
		$change_icon = 'ph-minus';
		if ( $earnings_change > 0 ) {
			$change_class = 'positive';
			$change_icon = 'ph-trend-up';
		} elseif ( $earnings_change < 0 ) {
			$change_class = 'negative';
			$change_icon = 'ph-trend-down';
		}

		// Get salary type for display
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );

		// Generate KPI HTML
		ob_start();
		?>
		<div class="reports-kpi-card" data-card-type="my_earnings">
			<div class="reports-kpi-header">
				<div class="reports-kpi-icon">
					<i class="ph ph-wallet"></i>
				</div>
			</div>
			<p class="reports-kpi-label"><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></p>
			<p class="reports-kpi-value"><?php echo wp_kses_post( wc_price( $total_earnings ) ); ?></p>
			<div class="reports-kpi-change <?php echo esc_attr( $change_class ); ?>">
				<i class="ph <?php echo esc_attr( $change_icon ); ?>"></i>
				<?php 
					if ( $earnings_change > 0 ) {
						echo '+' . number_format( $earnings_change, 1 ) . '%';
					} elseif ( $earnings_change < 0 ) {
						echo number_format( $earnings_change, 1 ) . '%';
					} else {
						echo number_format( $total_orders, 0 ) . ' ' . esc_html__( 'orders', 'wc-team-payroll' );
					}
				?>
			</div>
		</div>

		<div class="reports-kpi-card" data-card-type="my_salary">
			<div class="reports-kpi-header">
				<div class="reports-kpi-icon">
					<i class="ph ph-briefcase"></i>
				</div>
			</div>
			<p class="reports-kpi-label"><?php esc_html_e( 'My Salary', 'wc-team-payroll' ); ?></p>
			<p class="reports-kpi-value"><?php echo wp_kses_post( wc_price( $salary_for_period ) ); ?></p>
			<div class="reports-kpi-change neutral">
				<i class="ph ph-info"></i>
				<?php 
					if ( $is_fixed_salary ) {
						esc_html_e( 'Fixed', 'wc-team-payroll' );
					} elseif ( $is_combined_salary ) {
						esc_html_e( 'Combined', 'wc-team-payroll' );
					} else {
						esc_html_e( 'Commission', 'wc-team-payroll' );
					}
				?>
			</div>
		</div>

		<div class="reports-kpi-card" data-card-type="my_commission">
			<div class="reports-kpi-header">
				<div class="reports-kpi-icon">
					<i class="ph ph-percent"></i>
				</div>
			</div>
			<p class="reports-kpi-label"><?php esc_html_e( 'My Commission', 'wc-team-payroll' ); ?></p>
			<p class="reports-kpi-value"><?php echo wp_kses_post( wc_price( $total_commission ) ); ?></p>
			<div class="reports-kpi-change neutral">
				<i class="ph ph-chart-line-up"></i>
				<?php echo esc_html( $total_orders ); ?> <?php esc_html_e( 'orders', 'wc-team-payroll' ); ?>
			</div>
		</div>

		<div class="reports-kpi-card" data-card-type="my_performance_score">
			<div class="reports-kpi-header">
				<div class="reports-kpi-icon">
					<i class="ph ph-star"></i>
				</div>
			</div>
			<p class="reports-kpi-label"><?php esc_html_e( 'Performance Score', 'wc-team-payroll' ); ?></p>
			<p class="reports-kpi-value"><?php echo esc_html( self::calculate_performance_score( $total_orders, $attributed_order_total, $avg_order_value, $user_id ) ); ?>/10</p>
			<div class="reports-kpi-change neutral">
				<i class="ph ph-smiley"></i>
				<?php esc_html_e( 'excellent', 'wc-team-payroll' ); ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Get filtered analytics data
	 */
	public static function ajax_get_filtered_analytics_data() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wc_team_payroll_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed', 'wc-team-payroll' ) );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get filters from request
		$filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();

		// Get date range
		$date_range = self::get_date_range_from_filter( $filters );
		$start_date = $date_range['start'];
		$end_date = $date_range['end'];

		// Get filter values
		$role_filter = isset( $filters['role'] ) ? $filters['role'] : 'all';
		$status_filter = isset( $filters['orderStatus'] ) ? $filters['orderStatus'] : 'all';

		// Prepare order statuses for query
		$order_statuses = null;
		if ( $status_filter !== 'all' ) {
			$order_statuses = array( $status_filter );
		}

		// Get user earnings data with status filtering
		$engine = new WC_Team_Payroll_Core_Engine();
		$earnings_data = $engine->get_user_earnings( $user_id, $start_date, $end_date, $order_statuses );

		// Filter orders by role if needed
		$filtered_orders = $earnings_data['orders'];
		if ( $role_filter !== 'all' ) {
			$filtered_orders = array_filter( $filtered_orders, function( $order ) use ( $role_filter ) {
				return $order['role'] === $role_filter;
			});
		}

		// Prepare data for charts
		$time_period = isset( $filters['timePeriod'] ) ? $filters['timePeriod'] : 'monthly';
		$chart_data = self::prepare_chart_data( $filtered_orders, $time_period, $start_date, $end_date );

		// Get styling settings for chart colors
		$styling_settings = get_option( 'wc_team_payroll_styling', array() );
		$primary_color = isset( $styling_settings['primary_color'] ) ? $styling_settings['primary_color'] : '#0073aa';
		$secondary_color = isset( $styling_settings['secondary_color'] ) ? $styling_settings['secondary_color'] : '#28a745';
		
		// Get WooCommerce currency symbol and decode HTML entities for JavaScript
		$currency_symbol = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );

		// Generate chart HTML and JavaScript
		ob_start();
		?>
		<div class="reports-chart-container">
			<h3 class="reports-chart-title"><?php esc_html_e( 'My Earnings Trend', 'wc-team-payroll' ); ?></h3>
			<div class="reports-chart-canvas">
				<canvas id="earnings-trend-chart"></canvas>
			</div>
		</div>

		<div class="reports-chart-container">
			<h3 class="reports-chart-title"><?php esc_html_e( 'My Commission Breakdown', 'wc-team-payroll' ); ?></h3>
			<div class="reports-chart-canvas">
				<canvas id="commission-breakdown-chart"></canvas>
			</div>
		</div>

		<script>
			jQuery(document).ready(function($) {
				// Earnings Trend Chart (Line Chart)
				var earningsTrendCtx = document.getElementById('earnings-trend-chart');
				if (earningsTrendCtx) {
					new Chart(earningsTrendCtx, {
						type: 'bar',
						data: {
							labels: <?php echo wp_json_encode( $chart_data['labels'] ); ?>,
							datasets: [
								{
									label: '<?php esc_html_e( 'Earnings', 'wc-team-payroll' ); ?>',
									data: <?php echo wp_json_encode( $chart_data['earnings'] ); ?>,
									backgroundColor: '<?php echo esc_attr( $primary_color ); ?>',
									borderColor: '<?php echo esc_attr( $primary_color ); ?>',
									borderWidth: 1
								},
								{
									label: '<?php esc_html_e( 'Commission', 'wc-team-payroll' ); ?>',
									data: <?php echo wp_json_encode( $chart_data['commission'] ); ?>,
									backgroundColor: '<?php echo esc_attr( $secondary_color ); ?>',
									borderColor: '<?php echo esc_attr( $secondary_color ); ?>',
									borderWidth: 1
								},
								{
									label: '<?php esc_html_e( 'Salary', 'wc-team-payroll' ); ?>',
									data: <?php echo wp_json_encode( $chart_data['salary'] ); ?>,
									backgroundColor: '#ffc107',
									borderColor: '#ffc107',
									borderWidth: 1
								}
							]
						},
						options: {
							responsive: true,
							maintainAspectRatio: true,
							plugins: {
								legend: {
									display: true,
									position: 'top',
									labels: {
										font: { size: 12, weight: '600' },
										color: '#495057',
										padding: 15,
										usePointStyle: true
									}
								},
								tooltip: {
									backgroundColor: 'rgba(0, 0, 0, 0.8)',
									padding: 12,
									titleFont: { size: 13, weight: '600' },
									bodyFont: { size: 12 },
									borderColor: '#e9ecef',
									borderWidth: 1,
									callbacks: {
										label: function(context) {
											var label = context.dataset.label || '';
											if (label) {
												label += ': ';
											}
											label += '<?php echo esc_js( $currency_symbol ); ?>' + parseFloat(context.parsed.y).toFixed(2);
											return label;
										}
									}
								}
							},
							scales: {
								y: {
									beginAtZero: true,
									ticks: {
										callback: function(value) {
											return '<?php echo esc_js( $currency_symbol ); ?>' + value.toFixed(0);
										},
										font: { size: 11 },
										color: '#6c757d'
									},
									grid: {
										color: 'rgba(0, 0, 0, 0.05)',
										drawBorder: false
									}
								},
								x: {
									ticks: {
										font: { size: 11 },
										color: '#6c757d'
									},
									grid: {
										display: false,
										drawBorder: false
									}
								}
							}
						}
					});
				}

				// Commission Breakdown Chart (Pie Chart)
				var commissionBreakdownCtx = document.getElementById('commission-breakdown-chart');
				if (commissionBreakdownCtx) {
					new Chart(commissionBreakdownCtx, {
						type: 'doughnut',
						data: {
							labels: <?php echo wp_json_encode( $chart_data['breakdown_labels'] ); ?>,
							datasets: [{
								data: <?php echo wp_json_encode( $chart_data['breakdown_data'] ); ?>,
								backgroundColor: [
													'<?php echo esc_attr( $primary_color ); ?>',
													'<?php echo esc_attr( $secondary_color ); ?>',
													'#ffc107',
													'#17a2b8',
													'#6c757d'
												],
												borderColor: '#fff',
												borderWidth: 2
											}]
										},
										options: {
											responsive: true,
											maintainAspectRatio: false,
											plugins: {
												legend: {
													display: true,
													position: 'bottom',
													labels: {
														font: { size: 12, weight: '600' },
														color: '#495057',
														padding: 15,
														usePointStyle: true
													}
												},
												tooltip: {
													backgroundColor: 'rgba(0, 0, 0, 0.8)',
													padding: 12,
													titleFont: { size: 13, weight: '600' },
													bodyFont: { size: 12 },
													borderColor: '#e9ecef',
													borderWidth: 1,
													callbacks: {
														label: function(context) {
															var label = context.label || '';
															var value = context.parsed || 0;
															var total = context.dataset.data.reduce((a, b) => a + b, 0);
															var percentage = ((value / total) * 100).toFixed(1);
															return label + ': $' + value.toFixed(2) + ' (' + percentage + '%)';
														}
													}
												}
											}
										}
									});
				}
			});
		</script>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Get filtered performance metrics
	 */
	public static function ajax_get_filtered_performance_data() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wc_team_payroll_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed', 'wc-team-payroll' ) );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get filters from request
		$filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();

		// Get date range
		$date_range = self::get_date_range_from_filter( $filters );
		$start_date = $date_range['start'];
		$end_date = $date_range['end'];

		// Get filter values
		$role_filter = isset( $filters['role'] ) ? $filters['role'] : 'all';
		$status_filter = isset( $filters['orderStatus'] ) ? $filters['orderStatus'] : 'all';

		// Prepare order statuses for query
		$order_statuses = null;
		if ( $status_filter !== 'all' ) {
			$order_statuses = array( $status_filter );
		}

		// Get user earnings data with status filtering
		$engine = new WC_Team_Payroll_Core_Engine();
		$earnings_data = $engine->get_user_earnings( $user_id, $start_date, $end_date, $order_statuses );

		// Filter by role if needed
		$filtered_orders = $earnings_data['orders'];
		if ( $role_filter !== 'all' ) {
			$filtered_orders = array_filter( $filtered_orders, function( $order ) use ( $role_filter ) {
				return $order['role'] === $role_filter;
			});
		}

		// Calculate metrics
		$total_commission = 0; // Commission only (not including salary)
		$total_order_value = 0;

		foreach ( $filtered_orders as $order_data ) {
			$total_commission += $order_data['earnings'];
			$total_order_value += $order_data['total'];
		}
		
		// Get salary for the filtered period (same as KPI cards)
		// Read directly from transactions and filter by date range
		$salary_for_period = 0;
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		
		if ( $is_fixed_salary || $is_combined_salary ) {
			$transactions = get_user_meta( $user_id, '_wc_tp_salary_transactions', true );
			if ( is_array( $transactions ) ) {
				foreach ( $transactions as $transaction ) {
					if ( ! isset( $transaction['date'] ) ) {
						continue;
					}
					
					$trans_date = date( 'Y-m-d', strtotime( $transaction['date'] ) );
					if ( $trans_date >= $start_date && $trans_date <= $end_date ) {
						// Check for transfer types (daily_transfer, weekly_transfer, monthly_transfer, partial_transfer)
						if ( isset( $transaction['type'] ) && strpos( $transaction['type'], 'transfer' ) !== false ) {
							$salary_for_period += floatval( $transaction['amount'] ?? 0 );
						}
					}
				}
			}
		}
		
		// Total earnings = commission from filtered orders + salary from filtered period
		$total_earnings = $total_commission + $salary_for_period;
		
		// Calculate attributed order total using the same method as KPI modal
		// This correctly handles cases where user is both agent and processor
		$attributed_order_total = 0;
		
		// Get commission calculation statuses
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		
		// Determine which statuses to query for attributed total
		if ( $status_filter !== 'all' ) {
			$statuses_for_attributed = in_array( $status_filter, $commission_statuses ) ? array( $status_filter ) : $commission_statuses;
		} else {
			$statuses_for_attributed = $commission_statuses;
		}
		
		// Query args for attributed total
		$attributed_args = array(
			'limit'        => -1,
			'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
			'status'       => $statuses_for_attributed,
			'return'       => 'ids',
		);
		
		// Get orders where user is agent
		$agent_order_ids = wc_get_orders( array_merge( $attributed_args, array(
			'meta_key'   => '_primary_agent_id',
			'meta_value' => $user_id,
		) ) );
		
		// Get orders where user is processor
		$processor_order_ids = wc_get_orders( array_merge( $attributed_args, array(
			'meta_key'   => '_processor_user_id',
			'meta_value' => $user_id,
		) ) );
		
		// Apply role filter to attributed calculation
		if ( $role_filter === 'agent' ) {
			// Only count agent orders
			foreach ( $agent_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['agent_order_value'] ) ) {
					$attributed_order_total += floatval( $commission_data['agent_order_value'] );
				}
			}
		} elseif ( $role_filter === 'processor' ) {
			// Only count processor orders
			foreach ( $processor_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['processor_order_value'] ) ) {
					$attributed_order_total += floatval( $commission_data['processor_order_value'] );
				}
			}
		} else {
			// Count both agent and processor orders
			foreach ( $agent_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['agent_order_value'] ) ) {
					$attributed_order_total += floatval( $commission_data['agent_order_value'] );
				}
			}
			
			foreach ( $processor_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['processor_order_value'] ) ) {
					$attributed_order_total += floatval( $commission_data['processor_order_value'] );
				}
			}
		}
		
		// Calculate highest and lowest attributed order values (respecting filters)
		$highest_order = 0;
		$lowest_order = PHP_INT_MAX;
		
		// Apply role filter to highest/lowest calculation
		if ( $role_filter === 'agent' ) {
			// Only check agent orders
			foreach ( $agent_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['agent_order_value'] ) ) {
					$attributed_value = floatval( $commission_data['agent_order_value'] );
					if ( $attributed_value > $highest_order ) {
						$highest_order = $attributed_value;
					}
					if ( $attributed_value < $lowest_order ) {
						$lowest_order = $attributed_value;
					}
				}
			}
		} elseif ( $role_filter === 'processor' ) {
			// Only check processor orders
			foreach ( $processor_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['processor_order_value'] ) ) {
					$attributed_value = floatval( $commission_data['processor_order_value'] );
					if ( $attributed_value > $highest_order ) {
						$highest_order = $attributed_value;
					}
					if ( $attributed_value < $lowest_order ) {
						$lowest_order = $attributed_value;
					}
				}
			}
		} else {
			// Check both agent and processor orders
			foreach ( $agent_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['agent_order_value'] ) ) {
					$attributed_value = floatval( $commission_data['agent_order_value'] );
					if ( $attributed_value > $highest_order ) {
						$highest_order = $attributed_value;
					}
					if ( $attributed_value < $lowest_order ) {
						$lowest_order = $attributed_value;
					}
				}
			}
			
			foreach ( $processor_order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				$commission_data = $order->get_meta( '_commission_data' );
				if ( is_array( $commission_data ) && isset( $commission_data['processor_order_value'] ) ) {
					$attributed_value = floatval( $commission_data['processor_order_value'] );
					if ( $attributed_value > $highest_order ) {
						$highest_order = $attributed_value;
					}
					if ( $attributed_value < $lowest_order ) {
						$lowest_order = $attributed_value;
					}
				}
			}
		}
		
		// Count ALL orders (excluding draft and failed) for Total Orders metric
		$all_statuses = array_keys( wc_get_order_statuses() );
		// Remove draft and failed statuses
		$all_statuses = array_diff( $all_statuses, array( 'wc-draft', 'wc-failed', 'wc-cancelled', 'wc-trash' ) );
		
		// Apply status filter if specified
		if ( $status_filter !== 'all' ) {
			$all_statuses = array( 'wc-' . $status_filter );
		}
		
		// Query for total orders count
		$total_orders_args = array(
			'limit'        => -1,
			'date_created' => $start_date . ' 00:00:00...' . $end_date . ' 23:59:59',
			'status'       => $all_statuses,
			'return'       => 'ids',
		);
		
		// Get orders where user is agent
		$agent_orders_count = count( wc_get_orders( array_merge( $total_orders_args, array(
			'meta_key'   => '_primary_agent_id',
			'meta_value' => $user_id,
		) ) ) );
		
		// Get orders where user is processor
		$processor_orders_count = count( wc_get_orders( array_merge( $total_orders_args, array(
			'meta_key'   => '_processor_user_id',
			'meta_value' => $user_id,
		) ) ) );
		
		// Apply role filter to count
		if ( $role_filter === 'agent' ) {
			$total_orders = $agent_orders_count;
		} elseif ( $role_filter === 'processor' ) {
			$total_orders = $processor_orders_count;
		} else {
			// Remove duplicates (orders where user is both agent and processor)
			$agent_order_ids = wc_get_orders( array_merge( $total_orders_args, array(
				'meta_key'   => '_primary_agent_id',
				'meta_value' => $user_id,
			) ) );
			$processor_order_ids = wc_get_orders( array_merge( $total_orders_args, array(
				'meta_key'   => '_processor_user_id',
				'meta_value' => $user_id,
			) ) );
			$unique_orders = array_unique( array_merge( $agent_order_ids, $processor_order_ids ) );
			$total_orders = count( $unique_orders );
		}

		$avg_per_order = $total_orders > 0 ? $total_earnings / $total_orders : 0;
		$avg_order_value = $total_orders > 0 ? $attributed_order_total / $total_orders : 0;
		$commission_rate = $attributed_order_total > 0 ? ( $total_commission / $attributed_order_total ) * 100 : 0;

		// Calculate performance score using attributed order total
		$performance_score = self::calculate_performance_score( $total_orders, $attributed_order_total, $avg_order_value, $user_id );

		// Get previous period for growth calculation (with same filtering)
		$prev_date_range = self::get_previous_period_range( $start_date, $end_date );
		$prev_earnings_data = $engine->get_user_earnings( $user_id, $prev_date_range['start'], $prev_date_range['end'], $order_statuses );
		
		// Apply same role filter to previous period data
		$prev_filtered_orders = $prev_earnings_data['orders'];
		if ( $role_filter !== 'all' ) {
			$prev_filtered_orders = array_filter( $prev_filtered_orders, function( $order ) use ( $role_filter ) {
				return $order['role'] === $role_filter;
			});
		}
		
		// Calculate previous period total commission
		$prev_total_commission = 0;
		foreach ( $prev_filtered_orders as $order_data ) {
			$prev_total_commission += $order_data['earnings'];
		}
		
		// Get salary for the previous period (same as current period calculation)
		$prev_salary_for_period = 0;
		if ( $is_fixed_salary || $is_combined_salary ) {
			$transactions = get_user_meta( $user_id, '_wc_tp_salary_transactions', true );
			if ( is_array( $transactions ) ) {
				foreach ( $transactions as $transaction ) {
					if ( ! isset( $transaction['date'] ) ) {
						continue;
					}
					
					$trans_date = date( 'Y-m-d', strtotime( $transaction['date'] ) );
					if ( $trans_date >= $prev_date_range['start'] && $trans_date <= $prev_date_range['end'] ) {
						// Check for transfer types (daily_transfer, weekly_transfer, monthly_transfer, partial_transfer)
						if ( isset( $transaction['type'] ) && strpos( $transaction['type'], 'transfer' ) !== false ) {
							$prev_salary_for_period += floatval( $transaction['amount'] ?? 0 );
						}
					}
				}
			}
		}
		
		// Previous period total earnings = commission + salary
		$prev_total_earnings = $prev_total_commission + $prev_salary_for_period;

		// Calculate growth rate (comparing total earnings: commission + salary)
		$growth_rate = 0;
		if ( $prev_total_earnings > 0 ) {
			$growth_rate = ( ( $total_earnings - $prev_total_earnings ) / $prev_total_earnings ) * 100;
		}

		// Generate metrics HTML
		ob_start();
		?>
		<div class="reports-metric-box">
			<p class="reports-metric-label"><?php esc_html_e( 'Total Orders', 'wc-team-payroll' ); ?></p>
			<p class="reports-metric-value"><?php echo esc_html( $total_orders ); ?></p>
			<p class="reports-metric-detail">
				<i class="ph ph-shopping-bag"></i>
				<?php esc_html_e( 'orders processed', 'wc-team-payroll' ); ?>
			</p>
		</div>

		<div class="reports-metric-box">
			<p class="reports-metric-label"><?php esc_html_e( 'Total Order Value', 'wc-team-payroll' ); ?></p>
			<p class="reports-metric-value"><?php echo wp_kses_post( wc_price( $attributed_order_total ) ); ?></p>
			<p class="reports-metric-detail">
				<i class="ph ph-wallet"></i>
				<?php esc_html_e( 'attributed value', 'wc-team-payroll' ); ?>
			</p>
		</div>

		<div class="reports-metric-box">
			<p class="reports-metric-label"><?php esc_html_e( 'Total Earnings', 'wc-team-payroll' ); ?></p>
			<p class="reports-metric-value"><?php echo wp_kses_post( wc_price( $total_earnings ) ); ?></p>
			<p class="reports-metric-detail">
				<i class="ph ph-chart-bar"></i>
				<?php esc_html_e( 'total earned', 'wc-team-payroll' ); ?>
			</p>
		</div>

		<div class="reports-metric-box">
			<p class="reports-metric-label"><?php esc_html_e( 'Avg Order Value', 'wc-team-payroll' ); ?></p>
			<p class="reports-metric-value"><?php echo wp_kses_post( wc_price( $avg_order_value ) ); ?></p>
			<p class="reports-metric-detail">
				<i class="ph ph-calculator"></i>
				<?php esc_html_e( 'average order', 'wc-team-payroll' ); ?>
			</p>
		</div>

		<div class="reports-metric-box">
			<p class="reports-metric-label"><?php esc_html_e( 'Commission Rate', 'wc-team-payroll' ); ?></p>
			<p class="reports-metric-value"><?php echo esc_html( number_format( $commission_rate, 2 ) ); ?>%</p>
			<p class="reports-metric-detail">
				<i class="ph ph-percent"></i>
				<?php esc_html_e( 'of order value', 'wc-team-payroll' ); ?>
			</p>
		</div>

		<div class="reports-metric-box">
			<p class="reports-metric-label"><?php esc_html_e( 'Performance Score', 'wc-team-payroll' ); ?></p>
			<p class="reports-metric-value"><?php echo esc_html( number_format( $performance_score, 1 ) ); ?>/10</p>
			<p class="reports-metric-detail">
				<i class="ph ph-star"></i>
				<?php 
					if ( $performance_score >= 8 ) {
						esc_html_e( 'excellent', 'wc-team-payroll' );
					} elseif ( $performance_score >= 6 ) {
						esc_html_e( 'good', 'wc-team-payroll' );
					} elseif ( $performance_score >= 4 ) {
						esc_html_e( 'average', 'wc-team-payroll' );
					} else {
						esc_html_e( 'needs improvement', 'wc-team-payroll' );
					}
				?>
			</p>
		</div>

		<div class="reports-metric-box">
			<p class="reports-metric-label"><?php esc_html_e( 'Growth Rate', 'wc-team-payroll' ); ?></p>
			<p class="reports-metric-value <?php echo $growth_rate >= 0 ? 'positive' : 'negative'; ?>">
				<?php 
					if ( $growth_rate > 0 ) {
						echo '+' . esc_html( number_format( $growth_rate, 1 ) );
					} else {
						echo esc_html( number_format( $growth_rate, 1 ) );
					}
				?>%
			</p>
			<p class="reports-metric-detail">
				<i class="ph <?php echo $growth_rate >= 0 ? 'ph-trend-up' : 'ph-trend-down'; ?>"></i>
				<?php esc_html_e( 'vs previous period', 'wc-team-payroll' ); ?>
			</p>
		</div>

		<div class="reports-metric-box">
			<p class="reports-metric-label"><?php esc_html_e( 'Highest Order', 'wc-team-payroll' ); ?></p>
			<p class="reports-metric-value"><?php echo $highest_order !== 0 ? wp_kses_post( wc_price( $highest_order ) ) : '—'; ?></p>
			<p class="reports-metric-detail">
				<i class="ph ph-arrow-up"></i>
				<?php esc_html_e( 'peak order value', 'wc-team-payroll' ); ?>
			</p>
		</div>

		<div class="reports-metric-box">
			<p class="reports-metric-label"><?php esc_html_e( 'Lowest Order', 'wc-team-payroll' ); ?></p>
			<p class="reports-metric-value"><?php echo $lowest_order !== PHP_INT_MAX ? wp_kses_post( wc_price( $lowest_order ) ) : '—'; ?></p>
			<p class="reports-metric-detail">
				<i class="ph ph-arrow-down"></i>
				<?php esc_html_e( 'minimum order value', 'wc-team-payroll' ); ?>
			</p>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Get filtered table data
	 */
	public static function ajax_get_filtered_table_data() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wc_team_payroll_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed', 'wc-team-payroll' ) );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get filters from request
		$filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();

		// Get date range
		$date_range = self::get_date_range_from_filter( $filters );
		$start_date = $date_range['start'];
		$end_date = $date_range['end'];

		// Get filter values
		$role_filter = isset( $filters['role'] ) ? $filters['role'] : 'all';
		$status_filter = isset( $filters['orderStatus'] ) ? $filters['orderStatus'] : 'all';

		// Prepare order statuses for query
		$order_statuses = null;
		if ( $status_filter !== 'all' ) {
			$order_statuses = array( $status_filter );
		}

		// Get user earnings data with status filtering
		$engine = new WC_Team_Payroll_Core_Engine();
		$earnings_data = $engine->get_user_earnings( $user_id, $start_date, $end_date, $order_statuses );

		// Filter orders by role if needed
		$filtered_orders = $earnings_data['orders'];
		if ( $role_filter !== 'all' ) {
			$filtered_orders = array_filter( $filtered_orders, function( $order ) use ( $role_filter ) {
				return $order['role'] === $role_filter;
			});
		}

		// Apply commission range filter
		$commission_range = isset( $filters['commissionRange'] ) ? $filters['commissionRange'] : 'all';
		if ( $commission_range !== 'all' ) {
			$filtered_orders = array_filter( $filtered_orders, function( $order ) use ( $commission_range ) {
				$commission = $order['earnings'];
				switch ( $commission_range ) {
					case '0-100':
						return $commission >= 0 && $commission <= 100;
					case '100-500':
						return $commission > 100 && $commission <= 500;
					case '500-1000':
						return $commission > 500 && $commission <= 1000;
					case '1000+':
						return $commission > 1000;
					default:
						return true;
				}
			});
		}

		// Re-index array after filtering
		$filtered_orders = array_values( $filtered_orders );

		// Generate table HTML
		ob_start();
		?>
		<!-- COMMISSION HISTORY TABLE -->
		<div class="reports-table-wrapper table-wrapper">
			<h3 class="reports-table-title">
				<?php esc_html_e( 'My Commission History', 'wc-team-payroll' ); ?>
			</h3>
			
			<div class="reports-table-controls section-header">
				<div class="pv-table-controls table-controls">
					<div class="reports-table-search search-control">
						<input type="text" class="table-search-input" placeholder="<?php esc_attr_e( 'Search by Order ID...', 'wc-team-payroll' ); ?>" data-table="commission-table" />
						<i class="ph ph-magnifying-glass"></i>
					</div>
					<div class="reports-table-per-page per-page-control">
						<label><?php esc_html_e( 'Show:', 'wc-team-payroll' ); ?></label>
						<select class="table-per-page-select" data-table="commission-table">
							<option value="10">10</option>
							<option value="25">25</option>
							<option value="50">50</option>
						</select>
						<span><?php esc_html_e( 'per page', 'wc-team-payroll' ); ?></span>
					</div>
				</div>
			</div>

			<div class="reports-table-container table-container pv-table-container">
				<table class="reports-table pv-table woocommerce-table woocommerce-table--commission" id="commission-table">
					<thead>
						<tr>
							<th class="sortable" data-sort="date">
								<?php esc_html_e( 'Date', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="order_id">
								<?php esc_html_e( 'Order ID', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="total">
								<?php esc_html_e( 'Amount', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="commission">
								<?php esc_html_e( 'Commission', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="earnings">
								<?php esc_html_e( 'My Earning', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="role">
								<?php esc_html_e( 'Role', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th><?php esc_html_e( 'Status', 'wc-team-payroll' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $filtered_orders ) ) : ?>
							<?php foreach ( $filtered_orders as $order ) : ?>
								<tr>
									<td data-sort-value="<?php echo esc_attr( strtotime( $order['date'] ) ); ?>">
										<?php echo esc_html( date( 'M j, Y', strtotime( $order['date'] ) ) ); ?>
									</td>
									<td data-sort-value="<?php echo esc_attr( $order['order_id'] ); ?>">
										<strong>#<?php echo esc_html( $order['order_id'] ); ?></strong>
									</td>
									<td data-sort-value="<?php echo esc_attr( $order['total'] ); ?>">
										<?php echo wp_kses_post( wc_price( $order['total'] ) ); ?>
									</td>
									<td data-sort-value="<?php echo esc_attr( $order['commission'] ); ?>">
										<?php echo wp_kses_post( wc_price( $order['commission'] ) ); ?>
									</td>
									<td data-sort-value="<?php echo esc_attr( $order['earnings'] ); ?>">
										<strong><?php echo wp_kses_post( wc_price( $order['earnings'] ) ); ?></strong>
									</td>
									<td data-sort-value="<?php echo esc_attr( strtolower( $order['role'] ) ); ?>">
										<span class="role-badge role-<?php echo esc_attr( strtolower( $order['role'] ) ); ?>">
											<i class="ph <?php echo $order['role'] === 'agent' ? 'ph-user-check' : 'ph-gear'; ?>"></i>
											<?php echo esc_html( ucfirst( $order['role'] ) ); ?>
										</span>
									</td>
									<td>
										<?php 
										$status = isset( $order['status'] ) ? $order['status'] : 'completed';
										$status_label = wc_get_order_status_name( 'wc-' . $status );
										?>
										<span class="status-badge status-<?php echo esc_attr( $status ); ?>">
											<i class="ph <?php 
												$status_icons = array(
													'completed' => 'ph-check-circle',
													'processing' => 'ph-hourglass',
													'pending' => 'ph-clock',
													'on-hold' => 'ph-pause-circle',
													'cancelled' => 'ph-x-circle',
													'refunded' => 'ph-arrow-counter-clockwise',
												);
												echo isset( $status_icons[$status] ) ? $status_icons[$status] : 'ph-tag';
											?>"></i>
											<?php echo esc_html( $status_label ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="7" class="reports-no-data">
									<i class="ph ph-inbox"></i>
									<p><?php esc_html_e( 'No commission data found for the selected filters', 'wc-team-payroll' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div class="reports-pagination pagination-container" data-table="commission-table">
				<div class="reports-pagination-info pagination-info">
					<?php esc_html_e( 'Showing', 'wc-team-payroll' ); ?> <span class="pagination-start">1</span> <?php esc_html_e( 'to', 'wc-team-payroll' ); ?> <span class="pagination-end">10</span> <?php esc_html_e( 'of', 'wc-team-payroll' ); ?> <span class="pagination-total"><?php echo esc_html( count( $filtered_orders ) ); ?></span> <?php esc_html_e( 'entries', 'wc-team-payroll' ); ?>
				</div>
				<div class="reports-pagination-controls pagination-controls">
					<!-- Pagination buttons will be generated by JavaScript -->
				</div>
			</div>
		</div>

		<!-- ORDER PROCESSING TABLE -->
		<div class="reports-table-wrapper table-wrapper">
			<h3 class="reports-table-title">
				<?php esc_html_e( 'My Order Processing', 'wc-team-payroll' ); ?>
			</h3>
			
			<div class="reports-table-controls section-header">
				<div class="pv-table-controls table-controls">
					<div class="reports-table-search search-control">
						<input type="text" class="table-search-input" placeholder="<?php esc_attr_e( 'Search orders...', 'wc-team-payroll' ); ?>" data-table="orders-table" />
						<i class="ph ph-magnifying-glass"></i>
					</div>
					<div class="reports-table-per-page per-page-control">
						<label><?php esc_html_e( 'Show:', 'wc-team-payroll' ); ?></label>
						<select class="table-per-page-select" data-table="orders-table">
							<option value="10">10</option>
							<option value="25">25</option>
							<option value="50">50</option>
						</select>
						<span><?php esc_html_e( 'per page', 'wc-team-payroll' ); ?></span>
					</div>
				</div>
			</div>

			<div class="reports-table-container table-container pv-table-container">
				<table class="reports-table pv-table woocommerce-table woocommerce-table--orders" id="orders-table">
					<thead>
						<tr>
							<th class="sortable" data-sort="order_id">
								<?php esc_html_e( 'Order ID', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="date">
								<?php esc_html_e( 'Date', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="customer">
								<?php esc_html_e( 'Customer', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="role">
								<?php esc_html_e( 'My Role', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="total">
								<?php esc_html_e( 'Order Total', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="attributed">
								<?php esc_html_e( 'Attributed Order Total', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="commission">
								<?php esc_html_e( 'Commission', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th class="sortable" data-sort="earning">
								<?php esc_html_e( 'My Earning', 'wc-team-payroll' ); ?>
								<i class="ph ph-caret-up-down sort-icon"></i>
							</th>
							<th><?php esc_html_e( 'Status', 'wc-team-payroll' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'wc-team-payroll' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $filtered_orders ) ) : ?>
							<?php foreach ( $filtered_orders as $order ) : 
								// Get order object for additional data
								$order_obj = wc_get_order( $order['order_id'] );
								$customer_name = $order_obj ? $order_obj->get_billing_first_name() . ' ' . $order_obj->get_billing_last_name() : 'N/A';
								
								// Get attributed order value
								$commission_data = $order_obj ? $order_obj->get_meta( '_commission_data' ) : null;
								$attributed_value = 0;
								if ( $commission_data && is_array( $commission_data ) ) {
									if ( $order['role'] === 'agent' && isset( $commission_data['agent_order_value'] ) ) {
										$attributed_value = floatval( $commission_data['agent_order_value'] );
									} elseif ( $order['role'] === 'processor' && isset( $commission_data['processor_order_value'] ) ) {
										$attributed_value = floatval( $commission_data['processor_order_value'] );
									}
								}
								
								// Check if this order status calculates commission
								$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
								$status = isset( $order['status'] ) ? $order['status'] : 'completed';
								$has_commission = in_array( $status, $commission_statuses, true );
							?>
								<tr>
									<td data-sort-value="<?php echo esc_attr( $order['order_id'] ); ?>">
										<strong>#<?php echo esc_html( $order['order_id'] ); ?></strong>
									</td>
									<td data-sort-value="<?php echo esc_attr( strtotime( $order['date'] ) ); ?>">
										<?php echo esc_html( date( 'M j, Y', strtotime( $order['date'] ) ) ); ?>
									</td>
									<td data-sort-value="<?php echo esc_attr( strtolower( $customer_name ) ); ?>">
										<?php echo esc_html( $customer_name ); ?>
									</td>
									<td data-sort-value="<?php echo esc_attr( strtolower( $order['role'] ) ); ?>">
										<span class="role-badge role-<?php echo esc_attr( strtolower( $order['role'] ) ); ?>">
											<i class="ph <?php echo $order['role'] === 'agent' ? 'ph-user-check' : 'ph-gear'; ?>"></i>
											<?php echo esc_html( ucfirst( $order['role'] ) ); ?>
										</span>
									</td>
									<td data-sort-value="<?php echo esc_attr( $order['total'] ); ?>">
										<?php echo wp_kses_post( wc_price( $order['total'] ) ); ?>
									</td>
									<td data-sort-value="<?php echo esc_attr( $attributed_value ); ?>">
										<?php echo $attributed_value > 0 ? wp_kses_post( wc_price( $attributed_value ) ) : '—'; ?>
									</td>
									<td data-sort-value="<?php echo esc_attr( $order['commission'] ); ?>">
										<?php 
										if ( $has_commission ) {
											echo wp_kses_post( wc_price( $order['commission'] ) );
										} else {
											echo '<span class="commission-na" title="' . esc_attr__( 'Commission not calculated for this status', 'wc-team-payroll' ) . '">N/A</span>';
										}
										?>
									</td>
									<td data-sort-value="<?php echo esc_attr( $order['earnings'] ); ?>">
										<?php 
										if ( $has_commission ) {
											echo '<strong>' . wp_kses_post( wc_price( $order['earnings'] ) ) . '</strong>';
										} else {
											echo '<span class="commission-na" title="' . esc_attr__( 'Earnings not available for this status', 'wc-team-payroll' ) . '">N/A</span>';
										}
										?>
									</td>
									<td>
										<?php 
										$status_label = wc_get_order_status_name( 'wc-' . $status );
										?>
										<span class="status-badge status-<?php echo esc_attr( $status ); ?>">
											<i class="ph <?php 
												$status_icons = array(
													'completed' => 'ph-check-circle',
													'processing' => 'ph-hourglass',
													'pending' => 'ph-clock',
													'on-hold' => 'ph-pause-circle',
													'cancelled' => 'ph-x-circle',
													'refunded' => 'ph-arrow-counter-clockwise',
												);
												echo isset( $status_icons[$status] ) ? $status_icons[$status] : 'ph-tag';
											?>"></i>
											<?php echo esc_html( $status_label ); ?>
										</span>
									</td>
									<td>
										<button class="btn-action btn-view" onclick="window.open('<?php echo esc_url( admin_url( 'post.php?post=' . $order['order_id'] . '&action=edit' ) ); ?>', '_blank')">
											<i class="ph ph-eye"></i>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="10" class="reports-no-data">
									<i class="ph ph-inbox"></i>
									<p><?php esc_html_e( 'No orders found for the selected filters', 'wc-team-payroll' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div class="reports-pagination pagination-container" data-table="orders-table">
				<div class="reports-pagination-info pagination-info">
					<?php esc_html_e( 'Showing', 'wc-team-payroll' ); ?> <span class="pagination-start">1</span> <?php esc_html_e( 'to', 'wc-team-payroll' ); ?> <span class="pagination-end">10</span> <?php esc_html_e( 'of', 'wc-team-payroll' ); ?> <span class="pagination-total"><?php echo esc_html( count( $filtered_orders ) ); ?></span> <?php esc_html_e( 'entries', 'wc-team-payroll' ); ?>
				</div>
				<div class="reports-pagination-controls pagination-controls">
					<!-- Pagination buttons will be generated by JavaScript -->
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Get filtered goal tracking data
	 */
	public static function ajax_get_filtered_goals_data() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wc_team_payroll_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed', 'wc-team-payroll' ) );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get filters from request
		$filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();

		// Get date range
		$date_range = self::get_date_range_from_filter( $filters );
		$start_date = $date_range['start'];
		$end_date = $date_range['end'];

		// Get filter values
		$role_filter = isset( $filters['role'] ) ? $filters['role'] : 'all';
		$status_filter = isset( $filters['orderStatus'] ) ? $filters['orderStatus'] : 'all';

		// Prepare order statuses for query
		$order_statuses = null;
		if ( $status_filter !== 'all' ) {
			$order_statuses = array( $status_filter );
		}

		// Get user earnings data with status filtering
		$engine = new WC_Team_Payroll_Core_Engine();
		$earnings_data = $engine->get_user_earnings( $user_id, $start_date, $end_date, $order_statuses );

		// Filter orders by role if needed
		$filtered_orders = $earnings_data['orders'];
		if ( $role_filter !== 'all' ) {
			$filtered_orders = array_filter( $filtered_orders, function( $order ) use ( $role_filter ) {
				return $order['role'] === $role_filter;
			});
		}

		// Calculate metrics
		$total_earnings = 0;
		$total_commission = 0;
		$total_orders = count( $filtered_orders );
		$total_order_value = 0;
		$attributed_order_total = 0;

		foreach ( $filtered_orders as $order_data ) {
			$total_earnings += $order_data['earnings'];
			$total_commission += $order_data['commission'];
			$total_order_value += $order_data['total'];
			
			// Add attributed order value
			if ( isset( $order_data['attributed_value'] ) ) {
				$attributed_order_total += $order_data['attributed_value'];
			}
		}

		$avg_per_order = $total_orders > 0 ? $total_earnings / $total_orders : 0;
		$avg_order_value = $total_orders > 0 ? $total_order_value / $total_orders : 0;

		// Calculate performance score using attributed order total
		$performance_score = self::calculate_performance_score( $total_orders, $attributed_order_total, $avg_order_value, $user_id );

		// Get previous period for growth calculation (with same filtering)
		$prev_date_range = self::get_previous_period_range( $start_date, $end_date );
		$prev_earnings_data = $engine->get_user_earnings( $user_id, $prev_date_range['start'], $prev_date_range['end'], $order_statuses );
		
		// Apply same role filter to previous period data
		$prev_filtered_orders = $prev_earnings_data['orders'];
		if ( $role_filter !== 'all' ) {
			$prev_filtered_orders = array_filter( $prev_filtered_orders, function( $order ) use ( $role_filter ) {
				return $order['role'] === $role_filter;
			});
		}
		
		// Calculate previous period total earnings
		$prev_total_earnings = 0;
		foreach ( $prev_filtered_orders as $order_data ) {
			$prev_total_earnings += $order_data['earnings'];
		}

		// Calculate growth rate
		$growth_rate = 0;
		if ( $prev_total_earnings > 0 ) {
			$growth_rate = ( ( $total_earnings - $prev_total_earnings ) / $prev_total_earnings ) * 100;
		}

		// Get goal targets from Performance Settings or use defaults
		$performance_config = get_option( 'wc_tp_performance_config', array() );
		$goal_targets = isset( $performance_config['goal_targets'] ) ? $performance_config['goal_targets'] : array();
		
		// Define goals (can be customized per user or globally)
		$goals = array(
			'monthly_earnings' => array(
				'label' => __( 'Monthly Earnings Target', 'wc-team-payroll' ),
				'target' => isset( $goal_targets['monthly_earnings'] ) ? floatval( $goal_targets['monthly_earnings'] ) : 5000,
				'actual' => $total_earnings,
				'icon' => 'ph-wallet',
				'color' => '#0073aa'
			),
			'orders_processed' => array(
				'label' => __( 'Orders to Process', 'wc-team-payroll' ),
				'target' => isset( $goal_targets['orders_processed'] ) ? intval( $goal_targets['orders_processed'] ) : 50,
				'actual' => $total_orders,
				'icon' => 'ph-shopping-bag',
				'color' => '#28a745'
			),
			'average_order_value' => array(
				'label' => __( 'Average Order Value', 'wc-team-payroll' ),
				'target' => isset( $goal_targets['average_order_value'] ) ? floatval( $goal_targets['average_order_value'] ) : 200,
				'actual' => $avg_order_value,
				'icon' => 'ph-chart-bar',
				'color' => '#ffc107'
			),
			'performance_score' => array(
				'label' => __( 'Performance Score', 'wc-team-payroll' ),
				'target' => isset( $goal_targets['performance_score'] ) ? floatval( $goal_targets['performance_score'] ) : 8,
				'actual' => $performance_score,
				'icon' => 'ph-star',
				'color' => '#dc3545'
			)
		);

		// Generate goals HTML
		ob_start();
		?>
		<div class="reports-goals-section">
			<h3 class="reports-section-title">
				<i class="ph ph-target"></i>
				<?php esc_html_e( 'Goals & Achievements', 'wc-team-payroll' ); ?>
			</h3>

			<!-- Goals Grid -->
			<div class="reports-goals-grid">
				<?php foreach ( $goals as $goal_key => $goal ) : ?>
					<?php 
					$percentage = $goal['target'] > 0 ? min( ( $goal['actual'] / $goal['target'] ) * 100, 100 ) : 0;
					$achieved = $goal['actual'] >= $goal['target'];
					$achievement_class = $achieved ? 'achieved' : '';
					?>
					<div class="reports-goal-card <?php echo esc_attr( $achievement_class ); ?>" data-goal-type="<?php echo esc_attr( $goal_key ); ?>">
						<div class="goal-header">
							<div class="goal-icon" style="background-color: <?php echo esc_attr( $goal['color'] ); ?>20; color: <?php echo esc_attr( $goal['color'] ); ?>;">
								<i class="ph <?php echo esc_attr( $goal['icon'] ); ?>"></i>
							</div>
							<div class="goal-title">
								<h4 class="goal-label"><?php echo esc_html( $goal['label'] ); ?></h4>
								<?php if ( $achieved ) : ?>
									<span class="achievement-badge">
										<i class="ph ph-check-circle"></i>
										<?php esc_html_e( 'Achieved!', 'wc-team-payroll' ); ?>
									</span>
								<?php endif; ?>
							</div>
						</div>

						<div class="goal-progress">
							<div class="progress-bar-container">
								<div class="progress-bar goal-progress-fill" style="width: <?php echo esc_attr( $percentage ); ?>%; background-color: <?php echo esc_attr( $goal['color'] ); ?>;"></div>
							</div>
							<div class="progress-text">
								<span class="progress-percentage"><?php echo esc_html( number_format( $percentage, 0 ) ); ?>%</span>
							</div>
						</div>

						<div class="goal-stats">
							<div class="stat-item">
								<span class="stat-label"><?php esc_html_e( 'Actual', 'wc-team-payroll' ); ?></span>
								<span class="stat-value goal-actual">
									<?php 
									if ( $goal_key === 'average_order_value' || $goal_key === 'monthly_earnings' ) {
										echo wp_kses_post( wc_price( $goal['actual'] ) );
									} else {
										echo esc_html( number_format( $goal['actual'], 1 ) );
									}
									?>
								</span>
							</div>
							<div class="stat-divider">•</div>
							<div class="stat-item">
								<span class="stat-label"><?php esc_html_e( 'Target', 'wc-team-payroll' ); ?></span>
								<span class="stat-value goal-target">
									<?php 
									if ( $goal_key === 'average_order_value' || $goal_key === 'monthly_earnings' ) {
										echo wp_kses_post( wc_price( $goal['target'] ) );
									} else {
										echo esc_html( number_format( $goal['target'], 1 ) );
									}
									?>
								</span>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Performance Summary -->
			<div class="reports-performance-summary">
				<h4><?php esc_html_e( 'Performance Summary', 'wc-team-payroll' ); ?></h4>
				
				<div class="summary-grid">
					<div class="summary-item">
						<div class="summary-label"><?php esc_html_e( 'Overall Score', 'wc-team-payroll' ); ?></div>
						<div class="summary-value">
							<span class="score-badge score-<?php echo $performance_score >= 8 ? 'excellent' : ( $performance_score >= 6 ? 'good' : ( $performance_score >= 4 ? 'average' : 'poor' ) ); ?>">
								<?php echo esc_html( number_format( $performance_score, 1 ) ); ?>/10
							</span>
						</div>
					</div>

					<div class="summary-item">
						<div class="summary-label"><?php esc_html_e( 'Growth Rate', 'wc-team-payroll' ); ?></div>
						<div class="summary-value">
							<span class="growth-badge <?php echo $growth_rate >= 0 ? 'positive' : 'negative'; ?>">
								<i class="ph <?php echo $growth_rate >= 0 ? 'ph-trend-up' : 'ph-trend-down'; ?>"></i>
								<?php 
									if ( $growth_rate > 0 ) {
										echo '+' . esc_html( number_format( $growth_rate, 1 ) );
									} else {
										echo esc_html( number_format( $growth_rate, 1 ) );
									}
								?>%
							</span>
						</div>
					</div>

					<div class="summary-item">
						<div class="summary-label"><?php esc_html_e( 'Goals Achieved', 'wc-team-payroll' ); ?></div>
						<div class="summary-value">
							<?php 
							$achieved_count = 0;
							foreach ( $goals as $goal ) {
								if ( $goal['actual'] >= $goal['target'] ) {
									$achieved_count++;
								}
							}
							?>
							<span class="achievement-count"><?php echo esc_html( $achieved_count ); ?>/<?php echo esc_html( count( $goals ) ); ?></span>
						</div>
					</div>

					<div class="summary-item">
						<div class="summary-label"><?php esc_html_e( 'Completion Rate', 'wc-team-payroll' ); ?></div>
						<div class="summary-value">
							<?php 
							$completion_rate = count( $goals ) > 0 ? ( $achieved_count / count( $goals ) ) * 100 : 0;
							?>
							<span class="completion-rate"><?php echo esc_html( number_format( $completion_rate, 0 ) ); ?>%</span>
						</div>
					</div>
				</div>
			</div>

			<!-- Achievements -->
			<div class="reports-achievements">
				<h4><?php esc_html_e( 'Recent Achievements', 'wc-team-payroll' ); ?></h4>
				
				<div class="achievements-list">
					<?php 
					$achievements = array();
					
					// Check for achievements
					if ( $total_orders >= 10 ) {
						$achievements[] = array(
							'icon' => 'ph-shopping-bag',
							'title' => __( 'Order Master', 'wc-team-payroll' ),
							'description' => __( 'Processed 10+ orders', 'wc-team-payroll' )
						);
					}
					
					if ( $total_earnings >= 1000 ) {
						$achievements[] = array(
							'icon' => 'ph-money',
							'title' => __( 'Earnings Milestone', 'wc-team-payroll' ),
							'description' => __( 'Earned $1,000+', 'wc-team-payroll' )
						);
					}
					
					if ( $performance_score >= 8 ) {
						$achievements[] = array(
							'icon' => 'ph-star',
							'title' => __( 'Top Performer', 'wc-team-payroll' ),
							'description' => __( 'Achieved excellent performance score', 'wc-team-payroll' )
						);
					}
					
					if ( $growth_rate > 20 ) {
						$achievements[] = array(
							'icon' => 'ph-rocket',
							'title' => __( 'Growth Rocket', 'wc-team-payroll' ),
							'description' => __( '20%+ growth vs previous period', 'wc-team-payroll' )
						);
					}
					
					if ( $avg_order_value > 300 ) {
						$achievements[] = array(
							'icon' => 'ph-chart-line',
							'title' => __( 'High Value Specialist', 'wc-team-payroll' ),
							'description' => __( 'Average order value $300+', 'wc-team-payroll' )
						);
					}
					
					if ( empty( $achievements ) ) {
						$achievements[] = array(
							'icon' => 'ph-target',
							'title' => __( 'Keep Going!', 'wc-team-payroll' ),
							'description' => __( 'Work towards your goals to unlock achievements', 'wc-team-payroll' )
						);
					}
					?>
					
					<?php foreach ( $achievements as $achievement ) : ?>
						<div class="achievement-item">
							<div class="achievement-icon">
								<i class="ph <?php echo esc_attr( $achievement['icon'] ); ?>"></i>
							</div>
							<div class="achievement-content">
								<h5><?php echo esc_html( $achievement['title'] ); ?></h5>
								<p><?php echo esc_html( $achievement['description'] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Get attributed order total for performance score modal
	 */
	public static function ajax_get_attributed_order_total() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get filters
		$filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();

		// Get date range
		$date_range = self::get_date_range_from_filter( $filters );
		$start_date = $date_range['start'];
		$end_date = $date_range['end'];

		// Get commission calculation statuses from settings
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();

		// Get order status filter
		$order_status_filter = isset( $filters['orderStatus'] ) && $filters['orderStatus'] !== 'all' ? $filters['orderStatus'] : '';

		// Determine which statuses to query
		if ( $order_status_filter && $order_status_filter !== 'all' ) {
			// If specific status is selected, use only that status if it's in commission statuses
			$statuses_to_query = in_array( $order_status_filter, $commission_statuses ) ? array( $order_status_filter ) : $commission_statuses;
		} else {
			// Use all commission calculation statuses
			$statuses_to_query = $commission_statuses;
		}

		// Get orders for this user
		// For date queries, append time to ensure full day coverage
		// WooCommerce expects timestamps, so we need to include the full day
		$date_query = $start_date . ' 00:00:00...' . $end_date . ' 23:59:59';
		
		$args = array(
			'limit'        => -1,
			'date_created' => $date_query,
			'status'       => $statuses_to_query,
			'return'       => 'ids',
		);

		// Get orders where user is agent or processor
		$agent_orders = wc_get_orders( array_merge( $args, array(
			'meta_key'   => '_primary_agent_id',
			'meta_value' => $user_id,
		) ) );

		$processor_orders = wc_get_orders( array_merge( $args, array(
			'meta_key'   => '_processor_user_id',
			'meta_value' => $user_id,
		) ) );

		// Calculate attributed order total
		$attributed_total = 0;

		// Process agent orders
		foreach ( $agent_orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}
			$commission_data = $order->get_meta( '_commission_data' );
			if ( is_array( $commission_data ) && isset( $commission_data['agent_order_value'] ) ) {
				$attributed_total += floatval( $commission_data['agent_order_value'] );
			}
		}

		// Process processor orders
		foreach ( $processor_orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}
			$commission_data = $order->get_meta( '_commission_data' );
			if ( is_array( $commission_data ) && isset( $commission_data['processor_order_value'] ) ) {
				$attributed_total += floatval( $commission_data['processor_order_value'] );
			}
		}

		wp_send_json_success( array(
			'attributed_total' => wc_price( $attributed_total ),
		) );
	}

	/**
	 * AJAX: Export filtered report data
	 */
	public static function ajax_export_filtered_report() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get filters and format from request
		$filters = isset( $_POST['filters'] ) ? $_POST['filters'] : array();
		$format = isset( $_POST['format'] ) ? sanitize_text_field( $_POST['format'] ) : 'csv';

		// Get date range
		$date_range = self::get_date_range_from_filter( $filters );
		$start_date = $date_range['start'];
		$end_date = $date_range['end'];

		// Get user earnings data
		$engine = new WC_Team_Payroll_Core_Engine();
		$earnings_data = $engine->get_user_earnings( $user_id, $start_date, $end_date );

		// Filter orders by status and role
		$filtered_orders = $earnings_data['orders'];
		
		// Apply order status filter
		$order_status = isset( $filters['orderStatus'] ) ? $filters['orderStatus'] : 'all';
		if ( $order_status !== 'all' ) {
			$filtered_orders = array_filter( $filtered_orders, function( $order ) use ( $order_status ) {
				return isset( $order['status'] ) && $order['status'] === $order_status;
			});
		}

		// Apply role filter
		$role_filter = isset( $filters['role'] ) ? $filters['role'] : 'all';
		if ( $role_filter !== 'all' ) {
			$filtered_orders = array_filter( $filtered_orders, function( $order ) use ( $role_filter ) {
				return $order['role'] === $role_filter;
			});
		}

		// Apply commission range filter
		$commission_range = isset( $filters['commissionRange'] ) ? $filters['commissionRange'] : 'all';
		if ( $commission_range !== 'all' ) {
			$filtered_orders = array_filter( $filtered_orders, function( $order ) use ( $commission_range ) {
				$commission = $order['earnings'];
				switch ( $commission_range ) {
					case '0-100':
						return $commission >= 0 && $commission <= 100;
					case '100-500':
						return $commission > 100 && $commission <= 500;
					case '500-1000':
						return $commission > 500 && $commission <= 1000;
					case '1000+':
						return $commission > 1000;
					default:
						return true;
				}
			});
		}

		// Get user info
		$user = get_userdata( $user_id );
		$user_name = $user->display_name;

		// Generate export based on format
		if ( $format === 'csv' ) {
			self::export_to_csv( $filtered_orders, $user_name, $start_date, $end_date );
		} elseif ( $format === 'pdf' ) {
			self::export_to_pdf( $filtered_orders, $user_name, $start_date, $end_date, $earnings_data );
		} elseif ( $format === 'excel' ) {
			self::export_to_excel( $filtered_orders, $user_name, $start_date, $end_date, $earnings_data );
		}

		wp_send_json_error( __( 'Invalid export format', 'wc-team-payroll' ) );
	}

	/**
	 * Export data to CSV
	 */
	private static function export_to_csv( $orders, $user_name, $start_date, $end_date ) {
		// Set headers for CSV download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="reports_' . sanitize_file_name( $user_name ) . '_' . date( 'Y-m-d' ) . '.csv"' );

		// Create output stream
		$output = fopen( 'php://output', 'w' );

		// Add BOM for UTF-8
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Write header row
		fputcsv( $output, array(
			__( 'Date', 'wc-team-payroll' ),
			__( 'Order ID', 'wc-team-payroll' ),
			__( 'Order Value', 'wc-team-payroll' ),
			__( 'Commission', 'wc-team-payroll' ),
			__( 'Role', 'wc-team-payroll' ),
			__( 'Status', 'wc-team-payroll' )
		) );

		// Write data rows
		foreach ( $orders as $order ) {
			$status = isset( $order['status'] ) ? $order['status'] : 'completed';
			$status_label = wc_get_order_status_name( 'wc-' . $status );

			fputcsv( $output, array(
				date( 'Y-m-d', strtotime( $order['date'] ) ),
				$order['order_id'],
				wc_format_decimal( $order['total'], 2 ),
				wc_format_decimal( $order['earnings'], 2 ),
				ucfirst( $order['role'] ),
				$status_label
			) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Export data to PDF
	 */
	private static function export_to_pdf( $orders, $user_name, $start_date, $end_date, $earnings_data ) {
		// Calculate totals
		$total_earnings = 0;
		$total_commission = 0;
		$total_orders = count( $orders );

		foreach ( $orders as $order ) {
			$total_earnings += $order['earnings'];
			$total_commission += $order['commission'];
		}

		// Generate PDF content
		$pdf_content = "
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset='UTF-8'>
			<title>Performance Report</title>
			<style>
				body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
				h1 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
				h2 { color: #495057; margin-top: 20px; font-size: 16px; }
				table { width: 100%; border-collapse: collapse; margin: 15px 0; }
				th { background: #f8f9fa; padding: 10px; text-align: left; border: 1px solid #dee2e6; font-weight: bold; }
				td { padding: 10px; border: 1px solid #dee2e6; }
				.summary { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
				.summary-item { display: inline-block; margin-right: 30px; }
				.summary-label { font-weight: bold; color: #6c757d; }
				.summary-value { font-size: 18px; color: #0073aa; font-weight: bold; }
				.footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d; }
			</style>
		</head>
		<body>
			<h1>Performance Report</h1>
			<p><strong>Employee:</strong> " . esc_html( $user_name ) . "</p>
			<p><strong>Period:</strong> " . esc_html( date( 'M d, Y', strtotime( $start_date ) ) ) . " - " . esc_html( date( 'M d, Y', strtotime( $end_date ) ) ) . "</p>
			<p><strong>Generated:</strong> " . esc_html( date( 'M d, Y H:i:s' ) ) . "</p>

			<div class='summary'>
				<div class='summary-item'>
					<div class='summary-label'>Total Orders</div>
					<div class='summary-value'>" . esc_html( $total_orders ) . "</div>
				</div>
				<div class='summary-item'>
					<div class='summary-label'>Total Earnings</div>
					<div class='summary-value'>" . wc_price( $total_earnings ) . "</div>
				</div>
				<div class='summary-item'>
					<div class='summary-label'>Total Commission</div>
					<div class='summary-value'>" . wc_price( $total_commission ) . "</div>
				</div>
			</div>

			<h2>Commission History</h2>
			<table>
				<thead>
					<tr>
						<th>Date</th>
						<th>Order ID</th>
						<th>Order Value</th>
						<th>Commission</th>
						<th>Role</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>";

		foreach ( $orders as $order ) {
			$status = isset( $order['status'] ) ? $order['status'] : 'completed';
			$status_label = wc_get_order_status_name( 'wc-' . $status );

			$pdf_content .= "
					<tr>
						<td>" . esc_html( date( 'M d, Y', strtotime( $order['date'] ) ) ) . "</td>
						<td>#" . esc_html( $order['order_id'] ) . "</td>
						<td>" . wc_price( $order['total'] ) . "</td>
						<td>" . wc_price( $order['earnings'] ) . "</td>
						<td>" . esc_html( ucfirst( $order['role'] ) ) . "</td>
						<td>" . esc_html( $status_label ) . "</td>
					</tr>";
		}

		$pdf_content .= "
				</tbody>
			</table>

			<div class='footer'>
				<p>This report was automatically generated by WooCommerce Team Payroll.</p>
				<p>For questions or discrepancies, please contact your administrator.</p>
			</div>
		</body>
		</html>";

		// Output PDF
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="reports_' . sanitize_file_name( $user_name ) . '_' . date( 'Y-m-d' ) . '.pdf"' );

		// Use simple HTML to PDF conversion (requires external library in production)
		echo wp_kses_post( $pdf_content );
		exit;
	}

	/**
	 * Export data to Excel
	 */
	private static function export_to_excel( $orders, $user_name, $start_date, $end_date, $earnings_data ) {
		// Calculate totals
		$total_earnings = 0;
		$total_commission = 0;
		$total_orders = count( $orders );

		foreach ( $orders as $order ) {
			$total_earnings += $order['earnings'];
			$total_commission += $order['commission'];
		}

		// Generate Excel XML
		$excel_content = '<?xml version="1.0" encoding="UTF-8"?>
		<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
		 xmlns:o="urn:schemas-microsoft-com:office:office"
		 xmlns:x="urn:schemas-microsoft-com:office:excel"
		 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
		 xmlns:html="http://www.w3.org/TR/REC-html40">
		 <Styles>
		  <Style ss:ID="Header">
		   <Interior ss:Color="#0073aa" ss:Pattern="Solid"/>
		   <Font ss:Bold="1" ss:Color="#FFFFFF"/>
		  </Style>
		  <Style ss:ID="Summary">
		   <Interior ss:Color="#f8f9fa" ss:Pattern="Solid"/>
		   <Font ss:Bold="1"/>
		  </Style>
		 </Styles>
		 <Worksheet ss:Name="Performance Report">
		  <Table>
		   <Row>
		    <Cell ss:StyleID="Summary"><Data ss:Type="String">Performance Report</Data></Cell>
		   </Row>
		   <Row>
		    <Cell><Data ss:Type="String">Employee:</Data></Cell>
		    <Cell><Data ss:Type="String">' . esc_html( $user_name ) . '</Data></Cell>
		   </Row>
		   <Row>
		    <Cell><Data ss:Type="String">Period:</Data></Cell>
		    <Cell><Data ss:Type="String">' . esc_html( date( 'M d, Y', strtotime( $start_date ) ) ) . ' - ' . esc_html( date( 'M d, Y', strtotime( $end_date ) ) ) . '</Data></Cell>
		   </Row>
		   <Row>
		    <Cell><Data ss:Type="String">Generated:</Data></Cell>
		    <Cell><Data ss:Type="String">' . esc_html( date( 'M d, Y H:i:s' ) ) . '</Data></Cell>
		   </Row>
		   <Row/>
		   <Row>
		    <Cell ss:StyleID="Summary"><Data ss:Type="String">Total Orders</Data></Cell>
		    <Cell ss:StyleID="Summary"><Data ss:Type="Number">' . esc_html( $total_orders ) . '</Data></Cell>
		   </Row>
		   <Row>
		    <Cell ss:StyleID="Summary"><Data ss:Type="String">Total Earnings</Data></Cell>
		    <Cell ss:StyleID="Summary"><Data ss:Type="Number">' . esc_html( wc_format_decimal( $total_earnings, 2 ) ) . '</Data></Cell>
		   </Row>
		   <Row>
		    <Cell ss:StyleID="Summary"><Data ss:Type="String">Total Commission</Data></Cell>
		    <Cell ss:StyleID="Summary"><Data ss:Type="Number">' . esc_html( wc_format_decimal( $total_commission, 2 ) ) . '</Data></Cell>
		   </Row>
		   <Row/>
		   <Row>
		    <Cell ss:StyleID="Header"><Data ss:Type="String">Date</Data></Cell>
		    <Cell ss:StyleID="Header"><Data ss:Type="String">Order ID</Data></Cell>
		    <Cell ss:StyleID="Header"><Data ss:Type="String">Order Value</Data></Cell>
		    <Cell ss:StyleID="Header"><Data ss:Type="String">Commission</Data></Cell>
		    <Cell ss:StyleID="Header"><Data ss:Type="String">Role</Data></Cell>
		    <Cell ss:StyleID="Header"><Data ss:Type="String">Status</Data></Cell>
		   </Row>';

		foreach ( $orders as $order ) {
			$status = isset( $order['status'] ) ? $order['status'] : 'completed';
			$status_label = wc_get_order_status_name( 'wc-' . $status );

			$excel_content .= '
		   <Row>
		    <Cell><Data ss:Type="String">' . esc_html( date( 'M d, Y', strtotime( $order['date'] ) ) ) . '</Data></Cell>
		    <Cell><Data ss:Type="String">#' . esc_html( $order['order_id'] ) . '</Data></Cell>
		    <Cell><Data ss:Type="Number">' . esc_html( wc_format_decimal( $order['total'], 2 ) ) . '</Data></Cell>
		    <Cell><Data ss:Type="Number">' . esc_html( wc_format_decimal( $order['earnings'], 2 ) ) . '</Data></Cell>
		    <Cell><Data ss:Type="String">' . esc_html( ucfirst( $order['role'] ) ) . '</Data></Cell>
		    <Cell><Data ss:Type="String">' . esc_html( $status_label ) . '</Data></Cell>
		   </Row>';
		}

		$excel_content .= '
		  </Table>
		 </Worksheet>
		</Workbook>';

		// Output Excel
		header( 'Content-Type: application/vnd.ms-excel; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="reports_' . sanitize_file_name( $user_name ) . '_' . date( 'Y-m-d' ) . '.xls"' );

		echo wp_kses_post( $excel_content );
		exit;
	}

	/**
	 * Helper: Get date range from filter
	 * Uses WordPress timezone for all date calculations
	 */
	private static function get_date_range_from_filter( $filters ) {
		$date_range = isset( $filters['dateRange'] ) ? $filters['dateRange'] : 'this-month';
		// Convert hyphens to underscores for consistency (JavaScript sends hyphens)
		$date_range = str_replace( '-', '_', $date_range );
		
		// Use WordPress timezone for all date calculations
		$today = current_time( 'Y-m-d' );
		$timezone = wp_timezone();

		switch ( $date_range ) {
			case 'today':
				$start = $today;
				$end = $today;
				$label = __( 'Today', 'wc-team-payroll' );
				break;
				
			case 'this_week':
				// Monday to Sunday of current week
				$now = new DateTime( 'now', $timezone );
				$start = ( clone $now )->modify( 'Monday this week' )->format( 'Y-m-d' );
				$end = ( clone $now )->modify( 'Sunday this week' )->format( 'Y-m-d' );
				$label = __( 'This Week', 'wc-team-payroll' );
				break;
				
			case 'last_week':
				// Monday to Sunday of previous week
				$now = new DateTime( 'now', $timezone );
				$start = ( clone $now )->modify( 'Monday last week' )->format( 'Y-m-d' );
				$end = ( clone $now )->modify( 'Sunday last week' )->format( 'Y-m-d' );
				$label = __( 'Last Week', 'wc-team-payroll' );
				break;
				
			case 'this_month':
				// First day to last day of current month
				$now = new DateTime( 'now', $timezone );
				$start = ( clone $now )->modify( 'first day of this month' )->format( 'Y-m-d' );
				$end = ( clone $now )->modify( 'last day of this month' )->format( 'Y-m-d' );
				$label = __( 'This Month', 'wc-team-payroll' );
				break;
				
			case 'last_month':
				// First day to last day of previous month
				$now = new DateTime( 'now', $timezone );
				$start = ( clone $now )->modify( 'first day of last month' )->format( 'Y-m-d' );
				$end = ( clone $now )->modify( 'last day of last month' )->format( 'Y-m-d' );
				$label = __( 'Last Month', 'wc-team-payroll' );
				break;
				
			case 'this_quarter':
				// First day of current quarter to last day of current quarter
				$current_month = (int) current_time( 'n' );
				$quarter = ceil( $current_month / 3 );
				$start_month = ( ( $quarter - 1 ) * 3 ) + 1;
				$end_month = $quarter * 3;
				$year = current_time( 'Y' );
				$start = date( 'Y-m-d', strtotime( "$year-$start_month-01" ) );
				$end = date( 'Y-m-t', strtotime( "$year-$end_month-01" ) );
				$label = __( 'This Quarter', 'wc-team-payroll' );
				break;
				
			case 'last_quarter':
				// First day to last day of previous quarter
				$current_month = (int) current_time( 'n' );
				$quarter = ceil( $current_month / 3 ) - 1;
				if ( $quarter < 1 ) {
					$quarter = 4;
					$year = current_time( 'Y' ) - 1;
				} else {
					$year = current_time( 'Y' );
				}
				$start_month = ( ( $quarter - 1 ) * 3 ) + 1;
				$end_month = $quarter * 3;
				$start = date( 'Y-m-d', strtotime( "$year-$start_month-01" ) );
				$end = date( 'Y-m-t', strtotime( "$year-$end_month-01" ) );
				$label = __( 'Last Quarter', 'wc-team-payroll' );
				break;
				
			case 'this_year':
				// January 1 to December 31 of current year
				$year = current_time( 'Y' );
				$start = "$year-01-01";
				$end = "$year-12-31";
				$label = __( 'This Year', 'wc-team-payroll' );
				break;
				
			case 'last_year':
				// January 1 to December 31 of previous year
				$year = current_time( 'Y' ) - 1;
				$start = "$year-01-01";
				$end = "$year-12-31";
				$label = __( 'Last Year', 'wc-team-payroll' );
				break;
				
			case 'last_6_months':
				// 6 months ago to today
				$now = new DateTime( 'now', $timezone );
				$start = ( clone $now )->modify( '-6 months' )->format( 'Y-m-d' );
				$end = $today;
				$label = __( 'Last 6 Months', 'wc-team-payroll' );
				break;
				
			case 'all_time':
				// Far back date to today
				$start = '2000-01-01';
				$end = $today;
				$label = __( 'All Time', 'wc-team-payroll' );
				break;
				
			case 'custom':
				// Custom date range from user input
				$now = new DateTime( 'now', $timezone );
				$start = isset( $filters['customStartDate'] ) ? $filters['customStartDate'] : ( clone $now )->modify( 'first day of this month' )->format( 'Y-m-d' );
				$end = isset( $filters['customEndDate'] ) ? $filters['customEndDate'] : $today;
				$label = __( 'Custom Range', 'wc-team-payroll' );
				break;
				
			default:
				// Default to this month
				$now = new DateTime( 'now', $timezone );
				$start = ( clone $now )->modify( 'first day of this month' )->format( 'Y-m-d' );
				$end = ( clone $now )->modify( 'last day of this month' )->format( 'Y-m-d' );
				$label = __( 'This Month', 'wc-team-payroll' );
		}

		return array(
			'start' => $start,
			'end' => $end,
			'label' => $label
		);
	}

	/**
	 * Helper: Get previous period date range for comparison
	 */
	private static function get_previous_period_range( $start_date, $end_date ) {
		$start_timestamp = strtotime( $start_date );
		$end_timestamp = strtotime( $end_date );
		$period_days = ( $end_timestamp - $start_timestamp ) / 86400;

		$prev_end_timestamp = $start_timestamp - 86400;
		$prev_start_timestamp = $prev_end_timestamp - ( $period_days * 86400 );

		return array(
			'start' => date( 'Y-m-d', $prev_start_timestamp ),
			'end' => date( 'Y-m-d', $prev_end_timestamp )
		);
	}

	/**
	 * Helper: Calculate performance score using role-based configuration
	 * 
	 * @param int $orders Number of orders
	 * @param float $attributed_order_total Sum of attributed order values (based on agent/processor %)
	 * @param float $avg_order_value Average order value
	 * @param int $user_id User ID
	 * @return float Performance score (0-10)
	 */
	private static function calculate_performance_score( $orders, $attributed_order_total, $avg_order_value, $user_id = null ) {
		// Get performance configuration
		$performance_config = get_option( 'wc_tp_performance_config', array() );
		
		// Get base score (default to 5 if not configured)
		$base_score = isset( $performance_config['base_score'] ) ? floatval( $performance_config['base_score'] ) : 5;
		$score = $base_score;

		// Get user's WordPress roles (only check employee roles)
		$user_role = null;
		if ( $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( $user && isset( $user->roles ) && is_array( $user->roles ) ) {
				// Get configured employee roles from WooCommerce settings
				$checkout_fields = get_option( 'wc_team_payroll_checkout_fields', array() );
				$employee_roles = isset( $checkout_fields['agent_user_roles'] ) && is_array( $checkout_fields['agent_user_roles'] ) 
					? $checkout_fields['agent_user_roles'] 
					: array( 'shop_employee', 'shop_manager', 'administrator' );
				
				// Get the first role that is both an employee role AND has performance config
				foreach ( $user->roles as $role ) {
					if ( in_array( $role, $employee_roles ) && isset( $performance_config['roles'][ $role ] ) ) {
						$user_role = $role;
						break;
					}
				}
			}
		}

		// If no role found or no user_id provided, use default calculation
		if ( ! $user_role || ! isset( $performance_config['roles'][ $user_role ] ) ) {
			// Fallback to default calculation using attributed order total
			// Orders factor (max +2)
			if ( $orders >= 50 ) {
				$score += 2;
			} elseif ( $orders >= 30 ) {
				$score += 1.5;
			} elseif ( $orders >= 10 ) {
				$score += 1;
			}

			// Attributed order total factor (max +2)
			if ( $attributed_order_total >= 5000 ) {
				$score += 2;
			} elseif ( $attributed_order_total >= 2000 ) {
				$score += 1.5;
			} elseif ( $attributed_order_total >= 500 ) {
				$score += 1;
			}

			// Average order value factor (max +1)
			if ( $avg_order_value >= 500 ) {
				$score += 1;
			} elseif ( $avg_order_value >= 200 ) {
				$score += 0.5;
			}

			// Cap at 10
			return min( $score, 10 );
		}

		// Get role-specific configuration
		$role_config = $performance_config['roles'][ $user_role ];

		// Apply earnings ranges (using attributed order total)
		if ( isset( $role_config['earnings_ranges'] ) && is_array( $role_config['earnings_ranges'] ) ) {
			foreach ( $role_config['earnings_ranges'] as $range ) {
				if ( $attributed_order_total >= $range['min'] && $attributed_order_total <= $range['max'] ) {
					$score += floatval( $range['points'] );
					break;
				}
			}
		}

		// Apply orders ranges
		if ( isset( $role_config['orders_ranges'] ) && is_array( $role_config['orders_ranges'] ) ) {
			foreach ( $role_config['orders_ranges'] as $range ) {
				if ( $orders >= $range['min'] && $orders <= $range['max'] ) {
					$score += floatval( $range['points'] );
					break;
				}
			}
		}

		// Apply average order value ranges
		if ( isset( $role_config['aov_ranges'] ) && is_array( $role_config['aov_ranges'] ) ) {
			foreach ( $role_config['aov_ranges'] as $range ) {
				if ( $avg_order_value >= $range['min'] && $avg_order_value <= $range['max'] ) {
					$score += floatval( $range['points'] );
					break;
				}
			}
		}

		// Cap at 10
		return min( $score, 10 );
	}

	/**
	 * Helper: Prepare chart data from orders
	 */
	private static function prepare_chart_data( $orders, $time_period, $start_date, $end_date ) {
		$user_id = get_current_user_id();
		
		$chart_data = array(
			'labels' => array(),
			'earnings' => array(),
			'commission' => array(),
			'salary' => array(),
			'breakdown_labels' => array(),
			'breakdown_data' => array()
		);

		// Group orders by time period
		$grouped_data = array();

		foreach ( $orders as $order ) {
			$order_date = strtotime( $order['date'] );
			$period_key = self::get_period_key( $order['date'], $time_period );

			if ( ! isset( $grouped_data[ $period_key ] ) ) {
				$grouped_data[ $period_key ] = array(
					'earnings' => 0,
					'commission' => 0,
					'salary' => 0,
					'orders' => 0,
					'date' => $order['date']
				);
			}

			$grouped_data[ $period_key ]['earnings'] += $order['earnings'];
			$grouped_data[ $period_key ]['commission'] += $order['commission'];
			$grouped_data[ $period_key ]['orders'] += 1;
		}

		// Calculate salary for each period
		$salary_type = get_user_meta( $user_id, '_wc_tp_salary_type', true );
		$salary_amount = floatval( get_user_meta( $user_id, '_wc_tp_salary_amount', true ) );
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ?: 'monthly';
		
		// Check for fixed/combined salary flags
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		
		if ( $is_fixed_salary || $is_combined_salary ) {
			// Calculate salary per period based on frequency
			foreach ( $grouped_data as $period_key => &$data ) {
				$period_salary = 0;
				
				switch ( $salary_frequency ) {
					case 'daily':
						$period_salary = $salary_amount; // Per day
						break;
					case 'weekly':
						if ( $time_period === 'daily' ) {
							$period_salary = $salary_amount / 7; // Divide weekly by 7 for daily view
						} else {
							$period_salary = $salary_amount;
						}
						break;
					case 'monthly':
						if ( $time_period === 'daily' ) {
							$period_salary = $salary_amount / 30; // Approximate daily
						} elseif ( $time_period === 'weekly' ) {
							$period_salary = $salary_amount / 4; // Approximate weekly
						} else {
							$period_salary = $salary_amount;
						}
						break;
					case 'yearly':
						if ( $time_period === 'daily' ) {
							$period_salary = $salary_amount / 365;
						} elseif ( $time_period === 'weekly' ) {
							$period_salary = $salary_amount / 52;
						} elseif ( $time_period === 'monthly' ) {
							$period_salary = $salary_amount / 12;
						} else {
							$period_salary = $salary_amount;
						}
						break;
				}
				
				$data['salary'] = $period_salary;
			}
		}

		// Sort by date
		ksort( $grouped_data );

		// Build chart labels and data
		foreach ( $grouped_data as $period_key => $data ) {
			$chart_data['labels'][] = self::format_period_label( $data['date'], $time_period );
			$chart_data['earnings'][] = round( $data['earnings'], 2 );
			$chart_data['commission'][] = round( $data['commission'], 2 );
			$chart_data['salary'][] = round( $data['salary'], 2 );
		}

		// Prepare breakdown data (by role or status)
		$agent_earnings = 0;
		$processor_earnings = 0;

		foreach ( $orders as $order ) {
			if ( $order['role'] === 'agent' ) {
				$agent_earnings += $order['earnings'];
			} else {
				$processor_earnings += $order['earnings'];
			}
		}

		$chart_data['breakdown_labels'] = array(
			__( 'Agent Earnings', 'wc-team-payroll' ),
			__( 'Processor Earnings', 'wc-team-payroll' )
		);

		$chart_data['breakdown_data'] = array(
			round( $agent_earnings, 2 ),
			round( $processor_earnings, 2 )
		);

		return $chart_data;
	}

	/**
	 * Helper: Format period label for display
	 */
	private static function format_period_label( $date, $time_period ) {
		$date_obj = new DateTime( $date );

		switch ( $time_period ) {
			case 'daily':
				return $date_obj->format( 'M j' );
			case 'weekly':
				$week = $date_obj->format( 'W' );
				return 'W' . $week;
			case 'monthly':
				return $date_obj->format( 'M Y' );
			case 'quarterly':
				$month = (int) $date_obj->format( 'm' );
				$quarter = ceil( $month / 3 );
				return 'Q' . $quarter . ' ' . $date_obj->format( 'Y' );
			case 'yearly':
				return $date_obj->format( 'Y' );
			default:
				return $date_obj->format( 'M j, Y' );
		}
	}

	/**
	 * AJAX: Get earnings data for My Earnings page
	 */
	public static function ajax_get_earnings_data() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get view type from request (daily, weekly, monthly)
		$view_type = isset( $_POST['view_type'] ) ? sanitize_text_field( $_POST['view_type'] ) : 'monthly';

		// Get user salary info
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$salary_amount = floatval( get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ?: 0 );
		$salary_frequency = get_user_meta( $user_id, '_wc_tp_salary_frequency', true ) ?: 'monthly';

		// Get current month earnings
		$current_month_start = date( 'Y-m-01' );
		$current_month_end = date( 'Y-m-t' );
		
		// Get current month salary from transactions (same approach as Total Earnings but date filtered)
		$current_month_salary = 0;
		if ( $is_fixed_salary || $is_combined_salary ) {
			$transactions = get_user_meta( $user_id, '_wc_tp_salary_transactions', true );
			if ( is_array( $transactions ) ) {
				foreach ( $transactions as $transaction ) {
					if ( ! isset( $transaction['date'] ) ) {
						continue;
					}
					
					$trans_date = date( 'Y-m-d', strtotime( $transaction['date'] ) );
					if ( $trans_date >= $current_month_start && $trans_date <= $current_month_end ) {
						// Check for transfer types (daily_transfer, weekly_transfer, monthly_transfer, partial_transfer)
						if ( isset( $transaction['type'] ) && strpos( $transaction['type'], 'transfer' ) !== false ) {
							$current_month_salary += floatval( $transaction['amount'] ?? 0 );
						}
					}
				}
			}
		}
		$current_month_commission = self::get_user_commission_for_period( $user_id, $current_month_start, $current_month_end );
		$current_month_earnings = $current_month_salary + $current_month_commission;

		// Get pending salary (accumulated but not yet transferred)
		$pending_salary = 0;
		if ( $is_fixed_salary || $is_combined_salary ) {
			$accumulation = get_user_meta( $user_id, '_wc_tp_daily_accumulation', true );
			if ( is_array( $accumulation ) && isset( $accumulation['accumulated_total'] ) ) {
				$pending_salary = floatval( $accumulation['accumulated_total'] );
			}
		}

		// Get total earnings (all time) - matches admin employee details page
		// Use _wc_tp_total_earnings for salary (accumulated base salary)
		$total_salary = get_user_meta( $user_id, '_wc_tp_total_earnings', true );
		if ( ! $total_salary ) {
			$total_salary = 0;
		}

		// Get all commission from orders
		$total_commission = 0;
		$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
		$args = array(
			'limit'  => -1,
			'status' => $commission_statuses,
		);
		$orders = wc_get_orders( $args );
		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$total_commission += $commission_data['agent_earnings'];
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$total_commission += $commission_data['processor_earnings'];
			}
		}

		$total_earnings = $total_salary + $total_commission;

		// Get total paid
		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		$total_paid = 0;
		if ( is_array( $payments ) ) {
			foreach ( $payments as $payment ) {
				$total_paid += floatval( $payment['amount'] ?? 0 );
			}
		}

		$total_due = $total_earnings - $total_paid;

		// Get last payment info
		$last_payment = self::get_user_last_payment( $user_id );

		// Get history based on view type
		$history = array();
		$view_label = 'Month';
		
		if ( $view_type === 'daily' ) {
			$history = self::get_user_daily_history( $user_id, $is_fixed_salary, $is_combined_salary, $salary_amount, $salary_frequency );
			$view_label = 'Date';
		} elseif ( $view_type === 'weekly' ) {
			$history = self::get_user_weekly_history( $user_id, $is_fixed_salary, $is_combined_salary, $salary_amount, $salary_frequency );
			$view_label = 'Week';
		} else {
			$history = self::get_user_monthly_history( $user_id );
			$view_label = 'Month';
		}
		
		// Process history with status determination
		$processed_history = array();
		foreach ( $history as $data ) {
			$due_amount = $data['total'] - $data['paid'];
			
			// Determine status based on payment records
			$status = 'pending';
			if ( $due_amount <= 0 ) {
				$status = 'paid';
			} elseif ( $data['paid'] > 0 ) {
				$status = 'partially_paid';
			}
			
			$processed_history[] = array(
				'date' => $data['date'],
				'salary' => $data['salary'],
				'salary_formatted' => wc_price( $data['salary'] ),
				'commission' => $data['commission'],
				'commission_formatted' => wc_price( $data['commission'] ),
				'orders_count' => $data['orders_count'],
				'total' => $data['total'],
				'total_formatted' => wc_price( $data['total'] ),
				'paid' => $data['paid'],
				'paid_formatted' => wc_price( $data['paid'] ),
				'due' => $due_amount,
				'due_formatted' => wc_price( $due_amount ),
				'status' => $status,
			);
		}
		
		wp_send_json_success( array(
			'current_month_earnings' => wc_price( $current_month_earnings ),
			'current_month_salary' => wc_price( $current_month_salary ),
			'current_month_commission' => wc_price( $current_month_commission ),
			'pending_salary' => wc_price( $pending_salary ),
			'total_earnings' => wc_price( $total_earnings ),
			'total_salary' => wc_price( $total_salary ),
			'total_commission' => wc_price( $total_commission ),
			'total_paid' => wc_price( $total_paid ),
			'total_due' => wc_price( $total_due ),
			'last_paid_amount' => wc_price( $last_payment['amount'] ),
			'last_paid_date' => $last_payment['date'],
			'last_paid_method' => $last_payment['method'],
			'history' => $processed_history,
			'view_type' => $view_type,
			'view_label' => $view_label,
		) );
	}

	/**
	 * AJAX: Get filtered reports data
	 */
	public static function ajax_get_filtered_reports_data() {
		check_ajax_referer( 'wc_team_payroll_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		// Get filter parameters
		$date_range = isset( $_POST['date_range'] ) ? sanitize_text_field( $_POST['date_range'] ) : 'this_month';
		$custom_start = isset( $_POST['custom_start'] ) ? sanitize_text_field( $_POST['custom_start'] ) : '';
		$custom_end = isset( $_POST['custom_end'] ) ? sanitize_text_field( $_POST['custom_end'] ) : '';
		$order_status = isset( $_POST['order_status'] ) ? sanitize_text_field( $_POST['order_status'] ) : 'all';
		$role_filter = isset( $_POST['role_filter'] ) ? sanitize_text_field( $_POST['role_filter'] ) : 'all';
		$commission_range = isset( $_POST['commission_range'] ) ? sanitize_text_field( $_POST['commission_range'] ) : 'all';
		$time_period = isset( $_POST['time_period'] ) ? sanitize_text_field( $_POST['time_period'] ) : 'monthly';
		$section = isset( $_POST['section'] ) ? sanitize_text_field( $_POST['section'] ) : 'dashboard';

		// Calculate date range
		$dates = self::calculate_date_range( $date_range, $custom_start, $custom_end );
		$start_date = $dates['start'];
		$end_date = $dates['end'];

		// Get filtered data based on section
		$data = array();

		if ( 'dashboard' === $section ) {
			$data = self::get_filtered_dashboard_data( $user_id, $start_date, $end_date, $order_status, $role_filter );
		} elseif ( 'analytics' === $section ) {
			$data = self::get_filtered_analytics_data( $user_id, $start_date, $end_date, $order_status, $role_filter, $time_period );
		} elseif ( 'metrics' === $section ) {
			$data = self::get_filtered_metrics_data( $user_id, $start_date, $end_date, $order_status, $role_filter );
		} elseif ( 'commission_table' === $section ) {
			$data = self::get_filtered_commission_table( $user_id, $start_date, $end_date, $order_status, $role_filter );
		} elseif ( 'payment_table' === $section ) {
			$data = self::get_filtered_payment_table( $user_id, $start_date, $end_date );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Calculate date range based on filter selection
	 */
	private static function calculate_date_range( $date_range, $custom_start = '', $custom_end = '' ) {
		$today = new DateTime();
		$start = clone $today;
		$end = clone $today;

		switch ( $date_range ) {
			case 'today':
				break;
			case 'this_week':
				$start->modify( 'monday this week' );
				break;
			case 'this_month':
				$start->modify( 'first day of this month' );
				break;
			case 'last_month':
				$start->modify( 'first day of last month' );
				$end->modify( 'last day of last month' );
				break;
			case 'this_quarter':
				$month = (int) $today->format( 'm' );
				$quarter_start_month = ( floor( ( $month - 1 ) / 3 ) * 3 ) + 1;
				$start->setDate( (int) $today->format( 'Y' ), $quarter_start_month, 1 );
				break;
			case 'this_year':
				$start->setDate( (int) $today->format( 'Y' ), 1, 1 );
				break;
			case 'all_time':
				$start->setDate( 2000, 1, 1 );
				break;
			case 'custom':
				if ( $custom_start ) {
					$start = DateTime::createFromFormat( 'Y-m-d', $custom_start );
				}
				if ( $custom_end ) {
					$end = DateTime::createFromFormat( 'Y-m-d', $custom_end );
				}
				break;
		}

		return array(
			'start' => $start->format( 'Y-m-d' ),
			'end'   => $end->format( 'Y-m-d' ),
		);
	}

	/**
	 * Get filtered dashboard KPI data
	 */
	private static function get_filtered_dashboard_data( $user_id, $start_date, $end_date, $order_status, $role_filter ) {
		$engine = new WC_Team_Payroll_Core_Engine();

		// Get orders in date range
		$args = array(
			'limit'  => -1,
			'status' => 'all' === $order_status ? array( 'completed', 'processing', 'refunded' ) : array( $order_status ),
			'date_query' => array(
				array(
					'after'     => $start_date,
					'before'    => $end_date,
					'inclusive' => true,
				),
			),
		);

		$orders = wc_get_orders( $args );

		$total_earnings = 0;
		$total_commission = 0;
		$total_orders = 0;
		$total_salary = 0;

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			// Check role filter
			$user_role = null;
			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$user_role = 'agent';
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$user_role = 'processor';
			}

			if ( ! $user_role ) {
				continue;
			}

			if ( 'all' !== $role_filter && $role_filter !== $user_role ) {
				continue;
			}

			$total_orders++;

			if ( 'agent' === $user_role ) {
				$total_earnings += $commission_data['agent_earnings'];
				$total_commission += $commission_data['agent_earnings'];
			} else {
				$total_earnings += $commission_data['processor_earnings'];
				$total_commission += $commission_data['processor_earnings'];
			}
		}

		// Get salary info
		$is_fixed_salary = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
		$is_combined_salary = get_user_meta( $user_id, '_wc_tp_combined_salary', true );
		$salary_amount = floatval( get_user_meta( $user_id, '_wc_tp_salary_amount', true ) ?: 0 );

		if ( $is_fixed_salary || $is_combined_salary ) {
			// Calculate salary for period
			$days_in_period = ( strtotime( $end_date ) - strtotime( $start_date ) ) / 86400 + 1;
			$total_salary = $salary_amount * $days_in_period / 30; // Approximate monthly calculation
		}

		$total_earnings += $total_salary;

		return array(
			'total_earnings'    => wc_price( $total_earnings ),
			'total_commission'  => wc_price( $total_commission ),
			'total_orders'      => $total_orders,
			'total_salary'      => wc_price( $total_salary ),
			'avg_order_value'   => $total_orders > 0 ? wc_price( $total_earnings / $total_orders ) : wc_price( 0 ),
		);
	}

	/**
	 * Get filtered analytics data for charts
	 */
	private static function get_filtered_analytics_data( $user_id, $start_date, $end_date, $order_status, $role_filter, $time_period ) {
		// Get orders in date range
		$args = array(
			'limit'  => -1,
			'status' => 'all' === $order_status ? array( 'completed', 'processing', 'refunded' ) : array( $order_status ),
			'date_query' => array(
				array(
					'after'     => $start_date,
					'before'    => $end_date,
					'inclusive' => true,
				),
			),
		);

		$orders = wc_get_orders( $args );

		$earnings_by_period = array();
		$commission_by_role = array( 'agent' => 0, 'processor' => 0 );

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			$order_date = $order->get_date_created()->format( 'Y-m-d' );
			$period_key = self::get_period_key( $order_date, $time_period );

			// Check role
			$user_role = null;
			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$user_role = 'agent';
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$user_role = 'processor';
			}

			if ( ! $user_role ) {
				continue;
			}

			if ( 'all' !== $role_filter && $role_filter !== $user_role ) {
				continue;
			}

			if ( ! isset( $earnings_by_period[ $period_key ] ) ) {
				$earnings_by_period[ $period_key ] = 0;
			}

			if ( 'agent' === $user_role ) {
				$earnings_by_period[ $period_key ] += $commission_data['agent_earnings'];
				$commission_by_role['agent'] += $commission_data['agent_earnings'];
			} else {
				$earnings_by_period[ $period_key ] += $commission_data['processor_earnings'];
				$commission_by_role['processor'] += $commission_data['processor_earnings'];
			}
		}

		return array(
			'earnings_by_period' => $earnings_by_period,
			'commission_by_role' => $commission_by_role,
		);
	}

	/**
	 * Get filtered metrics data
	 */
	private static function get_filtered_metrics_data( $user_id, $start_date, $end_date, $order_status, $role_filter ) {
		$args = array(
			'limit'  => -1,
			'status' => 'all' === $order_status ? array( 'completed', 'processing', 'refunded' ) : array( $order_status ),
			'date_query' => array(
				array(
					'after'     => $start_date,
					'before'    => $end_date,
					'inclusive' => true,
				),
			),
		);

		$orders = wc_get_orders( $args );

		$total_orders = 0;
		$total_commission = 0;
		$total_order_value = 0;

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			$user_role = null;
			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$user_role = 'agent';
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$user_role = 'processor';
			}

			if ( ! $user_role ) {
				continue;
			}

			if ( 'all' !== $role_filter && $role_filter !== $user_role ) {
				continue;
			}

			$total_orders++;
			$total_order_value += $order->get_total();

			if ( 'agent' === $user_role ) {
				$total_commission += $commission_data['agent_earnings'];
			} else {
				$total_commission += $commission_data['processor_earnings'];
			}
		}

		return array(
			'total_orders'      => $total_orders,
			'total_commission'  => wc_price( $total_commission ),
			'avg_commission'    => $total_orders > 0 ? wc_price( $total_commission / $total_orders ) : wc_price( 0 ),
			'total_order_value' => wc_price( $total_order_value ),
			'avg_order_value'   => $total_orders > 0 ? wc_price( $total_order_value / $total_orders ) : wc_price( 0 ),
		);
	}

	/**
	 * Get filtered commission table data
	 */
	private static function get_filtered_commission_table( $user_id, $start_date, $end_date, $order_status, $role_filter ) {
		$args = array(
			'limit'  => -1,
			'status' => 'all' === $order_status ? array( 'completed', 'processing', 'refunded' ) : array( $order_status ),
			'date_query' => array(
				array(
					'after'     => $start_date,
					'before'    => $end_date,
					'inclusive' => true,
				),
			),
		);

		$orders = wc_get_orders( $args );
		$rows = array();

		foreach ( $orders as $order ) {
			$agent_id = $order->get_meta( '_primary_agent_id' );
			$processor_id = $order->get_meta( '_processor_user_id' );
			$commission_data = $order->get_meta( '_commission_data' );

			if ( ! $commission_data ) {
				continue;
			}

			$user_role = null;
			$user_earnings = 0;

			if ( intval( $agent_id ) === intval( $user_id ) ) {
				$user_role = 'agent';
				$user_earnings = $commission_data['agent_earnings'];
			} elseif ( intval( $processor_id ) === intval( $user_id ) ) {
				$user_role = 'processor';
				$user_earnings = $commission_data['processor_earnings'];
			}

			if ( ! $user_role ) {
				continue;
			}

			if ( 'all' !== $role_filter && $role_filter !== $user_role ) {
				continue;
			}

			$rows[] = array(
				'order_id'    => $order->get_id(),
				'date'        => $order->get_date_created()->format( 'Y-m-d' ),
				'total'       => $order->get_total(),
				'commission'  => $commission_data['total_commission'],
				'earnings'    => $user_earnings,
				'role'        => $user_role,
				'status'      => $order->get_status(),
			);
		}

		return array( 'rows' => $rows );
	}

	/**
	 * Get filtered payment table data
	 */
	private static function get_filtered_payment_table( $user_id, $start_date, $end_date ) {
		$payments = get_user_meta( $user_id, '_wc_tp_payments', true );
		if ( ! is_array( $payments ) ) {
			$payments = array();
		}

		$rows = array();
		$start_timestamp = strtotime( $start_date );
		$end_timestamp = strtotime( $end_date ) + 86400; // Add 1 day to include end date

		foreach ( $payments as $payment ) {
			$payment_date = strtotime( $payment['date'] );

			if ( $payment_date >= $start_timestamp && $payment_date <= $end_timestamp ) {
				$rows[] = array(
					'date'    => $payment['date'],
					'amount'  => $payment['amount'],
					'method'  => isset( $payment['method'] ) ? $payment['method'] : 'Unknown',
					'status'  => isset( $payment['status'] ) ? $payment['status'] : 'Completed',
				);
			}
		}

		return array( 'rows' => $rows );
	}

	/**
	 * Get period key for grouping data
	 */
	private static function get_period_key( $date, $period ) {
		$date_obj = DateTime::createFromFormat( 'Y-m-d', $date );

		switch ( $period ) {
			case 'daily':
				return $date;
			case 'weekly':
				return $date_obj->format( 'Y-W' );
			case 'monthly':
				return $date_obj->format( 'Y-m' );
			case 'quarterly':
				$month = (int) $date_obj->format( 'm' );
				$quarter = ceil( $month / 3 );
				return $date_obj->format( 'Y' ) . '-Q' . $quarter;
			case 'yearly':
				return $date_obj->format( 'Y' );
			default:
				return $date;
		}
	}
}
