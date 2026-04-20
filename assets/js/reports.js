/**
 * Enterprise Reports Page - Master Filter System
 * WooCommerce Team Payroll
 */

jQuery(document).ready(function($) {
	'use strict';

	// Master filter state
	let masterFilters = {
		dateRange: 'this-month',
		customStartDate: null,
		customEndDate: null,
		orderStatus: 'all',
		role: 'all'
	};

	// Track last custom dates for restoration
	let lastCustomDateFrom = '';
	let lastCustomDateTo = '';

	// Table state management
	let tableState = {
		'commission-table': {
			currentPage: 1,
			perPage: 10,
			sortColumn: null,
			sortOrder: 'asc',
			searchTerm: ''
		},
		'salary-table': {
			currentPage: 1,
			perPage: 10,
			sortColumn: null,
			sortOrder: 'asc',
			searchTerm: ''
		},
		'orders-table': {
			currentPage: 1,
			perPage: 10,
			sortColumn: null,
			sortOrder: 'asc',
			searchTerm: ''
		}
	};

	// Auto-refresh interval (in milliseconds)
	let autoRefreshInterval = null;
	// Get auto-refresh settings from PHP (converted from seconds to milliseconds)
	const AUTO_REFRESH_INTERVAL = (wc_tp_reports.auto_refresh_interval || 30) * 1000;
	const AUTO_REFRESH_ENABLED = AUTO_REFRESH_INTERVAL > 0; // Disabled if 0
	
	// Track if filters have changed to control animations
	let filtersChanged = false;

	// Initialize reports on page load
	initializeReports();

	/**
	 * Initialize reports
	 */
	function initializeReports() {
		// Load filters from localStorage
		loadFilterState();

		// Load initial data (no animations on first load)
		filtersChanged = false;
		loadAllReportData();

		// Bind filter events
		bindFilterEvents();

		// Setup auto-refresh
		if (AUTO_REFRESH_ENABLED) {
			setupAutoRefresh();
		}

		// Bind drill-down events
		bindDrillDownEvents();
	}

	/**
	 * Load filter state from localStorage
	 */
	function loadFilterState() {
		const savedFilters = localStorage.getItem('wc_tp_reports_filters');
		if (savedFilters) {
			try {
				const filters = JSON.parse(savedFilters);
				masterFilters = { ...masterFilters, ...filters };

				// Update form inputs with saved values
				$('#reports-date-range').val(masterFilters.dateRange);
				$('#reports-order-status').val(masterFilters.orderStatus);
				$('#reports-role').val(masterFilters.role);

				// Show custom date inputs if needed
				if (masterFilters.dateRange === 'custom') {
					$('#reports-custom-date-range').show();
					$('#reports-start-date').val(masterFilters.customStartDate);
					$('#reports-end-date').val(masterFilters.customEndDate);
					lastCustomDateFrom = masterFilters.customStartDate;
					lastCustomDateTo = masterFilters.customEndDate;
				}
			} catch (e) {
				console.log('Could not load saved filters');
			}
		}
	}

	/**
	 * Save filter state to localStorage
	 */
	function saveFilterState() {
		localStorage.setItem('wc_tp_reports_filters', JSON.stringify(masterFilters));
	}

	/**
	 * Setup auto-refresh
	 */
	function setupAutoRefresh() {
		// Refresh data at configured interval (silent refresh - no animations)
		autoRefreshInterval = setInterval(function() {
			filtersChanged = false; // Silent refresh
			loadAllReportData();
		}, AUTO_REFRESH_INTERVAL);
	}

	/**
	 * Bind drill-down events
	 */
	function bindDrillDownEvents() {
		// KPI card click for drill-down
		$(document).on('click', '.reports-kpi-card', function() {
			const cardType = $(this).data('card-type');
			showDrillDownModal(cardType);
		});

		// Goal card click for drill-down
		$(document).on('click', '.reports-goal-card', function() {
			const goalType = $(this).data('goal-type');
			showGoalDetailsModal(goalType);
		});

		// Close modal on background click
		$(document).on('click', '.reports-modal-overlay', function(e) {
			if (e.target === this) {
				closeDrillDownModal();
			}
		});

		// Close modal on close button
		$(document).on('click', '.reports-modal-close', function() {
			closeDrillDownModal();
		});
	}

	/**
	 * Show drill-down modal
	 */
	function showDrillDownModal(cardType) {
		const modal = $(`
			<div class="reports-modal-overlay">
				<div class="reports-modal">
					<div class="reports-modal-header">
						<h3>${cardType.replace(/_/g, ' ').toUpperCase()}</h3>
						<button class="reports-modal-close">
							<i class="ph ph-x"></i>
						</button>
					</div>
					<div class="reports-modal-body">
						<div class="reports-loading">
							<i class="ph ph-spinner"></i>
							<p>Loading details...</p>
						</div>
					</div>
				</div>
			</div>
		`);

		$('body').append(modal);

		// Load drill-down data
		loadDrillDownData(cardType, modal);
	}

	/**
	 * Load drill-down data
	 */
	function loadDrillDownData(cardType, modal) {
		// Add small delay to ensure KPI cards are fully loaded
		setTimeout(function() {
			// Helper function to get current filters
			function getCurrentFilters() {
				const dateRangeSelect = $('#reports-date-range').val();
				const orderStatus = $('#reports-order-status').val();
				const role = $('#reports-role').val();
				
				return {
					dateRange: dateRangeSelect ? dateRangeSelect.replace(/-/g, ' ').toUpperCase() : 'Current Period',
					orderStatus: orderStatus && orderStatus !== 'all' ? orderStatus.toUpperCase() : 'All',
					role: role && role !== 'all' ? role.toUpperCase() : 'All'
				};
			}
			
			// Fetch real data from the current dashboard data
			const filters = getCurrentFilters();
		
		// Get the current KPI values from the page using .text() to get clean text
		const kpiCards = {
			my_earnings: {
				value: $('[data-card-type="my_earnings"] .reports-kpi-value').text().trim() || '$0.00',
				commission: $('[data-card-type="my_commission"] .reports-kpi-value').text().trim() || '$0.00',
				salary: $('[data-card-type="my_salary"] .reports-kpi-value').text().trim() || '$0.00',
				change: $('[data-card-type="my_earnings"] .reports-kpi-change').text().trim() || '0 orders'
			},
			my_salary: {
				value: $('[data-card-type="my_salary"] .reports-kpi-value').text().trim() || '$0.00',
				type: $('[data-card-type="my_salary"] .reports-kpi-change').text().trim() || 'N/A'
			},
			my_commission: {
				value: $('[data-card-type="my_commission"] .reports-kpi-value').text().trim() || '$0.00',
				orders: $('[data-card-type="my_commission"] .reports-kpi-change').text().trim() || '0 orders'
			},
			my_performance_score: {
				// Extract just the numeric score, removing any "/10" suffix
				value: $('[data-card-type="my_performance_score"] .reports-kpi-value').text().trim().replace(/\/10$/, '') || '0',
				rating: $('[data-card-type="my_performance_score"] .reports-kpi-change').text().trim() || 'N/A'
			}
		};

		let content = '';

		switch (cardType) {
			case 'my_earnings':
				content = `
					<div class="drill-down-content">
						<div class="drill-down-section">
							<h4>Earnings Breakdown</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Total Earnings</span>
									<span class="breakdown-value">${kpiCards.my_earnings.value}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Commission Earned</span>
									<span class="breakdown-value">${kpiCards.my_earnings.commission}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Salary Portion</span>
									<span class="breakdown-value">${kpiCards.my_earnings.salary}</span>
								</div>
							</div>
						</div>
						<div class="drill-down-section">
							<h4>Earnings Summary</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Period</span>
									<span class="breakdown-value">${filters.dateRange || 'Current Period'}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Status Filter</span>
									<span class="breakdown-value">${filters.orderStatus || 'All'}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Role Filter</span>
									<span class="breakdown-value">${filters.role || 'All'}</span>
								</div>
							</div>
						</div>
					</div>
				`;
				break;

			case 'my_salary':
				content = `
					<div class="drill-down-content">
						<div class="drill-down-section">
							<h4>Salary Details</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Total Salary</span>
									<span class="breakdown-value">${kpiCards.my_salary.value}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Salary Type</span>
									<span class="breakdown-value">${$('[data-card-type="my_salary"] .reports-kpi-change').text() || 'N/A'}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Period</span>
									<span class="breakdown-value">${filters.dateRange || 'Current Period'}</span>
								</div>
							</div>
						</div>
						<div class="drill-down-section">
							<h4>Salary Information</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Note</span>
									<span class="breakdown-value">Salary is fixed/base compensation and is not affected by order status or role filters</span>
								</div>
							</div>
						</div>
					</div>
				`;
				break;

			case 'my_commission':
				content = `
					<div class="drill-down-content">
						<div class="drill-down-section">
							<h4>Commission Details</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Total Commission</span>
									<span class="breakdown-value">${kpiCards.my_commission.value}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Orders Counted</span>
									<span class="breakdown-value">${kpiCards.my_commission.orders}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Avg Commission per Order</span>
									<span class="breakdown-value">Calculated from total</span>
								</div>
							</div>
						</div>
						<div class="drill-down-section">
							<h4>Commission Filters Applied</h4>
							<div class="breakdown-items">
								<div class="breakdown-item">
									<span class="breakdown-label">Date Range</span>
									<span class="breakdown-value">${filters.dateRange || 'Current Period'}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Order Status</span>
									<span class="breakdown-value">${filters.orderStatus || 'All'}</span>
								</div>
								<div class="breakdown-item">
									<span class="breakdown-label">Role</span>
									<span class="breakdown-value">${filters.role || 'All'}</span>
								</div>
							</div>
						</div>
					</div>
				`;
				break;

			case 'my_performance_score':
				// Fetch attributed order total via AJAX
				$.ajax({
					url: wc_tp_reports.ajax_url,
					type: 'POST',
					data: {
						action: 'wc_tp_get_attributed_order_total',
						nonce: wc_tp_reports.nonce,
						filters: masterFilters
					},
					success: function(response) {
						let attributedTotal = '$0.00';
						if (response.success && response.data.attributed_total) {
							attributedTotal = response.data.attributed_total;
						}

						content = `
							<div class="drill-down-content">
								<div class="drill-down-section">
									<h4>Performance Score Breakdown</h4>
									<div class="breakdown-items">
										<div class="breakdown-item">
											<span class="breakdown-label">Current Score</span>
											<span class="breakdown-value">${kpiCards.my_performance_score.value}/10</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Total Orders</span>
											<span class="breakdown-value">${kpiCards.my_commission.orders}</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Order Total (Attributed)</span>
											<span class="breakdown-value">${attributedTotal}</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Total Earnings</span>
											<span class="breakdown-value">${kpiCards.my_earnings.value}</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Commission Earned</span>
											<span class="breakdown-value">${kpiCards.my_commission.value}</span>
										</div>
									</div>
								</div>
								<div class="drill-down-section">
									<h4>Performance Calculation</h4>
									<div class="breakdown-items">
										<div class="breakdown-item">
											<span class="breakdown-label">Calculation Method</span>
											<span class="breakdown-value">Role-based configuration from Performance Settings</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Based On</span>
											<span class="breakdown-value">Attributed order totals, order count, and AOV</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Filters Applied</span>
											<span class="breakdown-value">Date Range, Order Status, Role</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Period</span>
											<span class="breakdown-value">${filters.dateRange || 'Current Period'}</span>
										</div>
									</div>
								</div>
								<div class="drill-down-section">
									<h4>Calculation Details</h4>
									<div class="breakdown-items">
										<div class="breakdown-item">
											<span class="breakdown-label">How Performance Score Works</span>
											<span class="breakdown-value">
												Your performance score is calculated based on three key metrics:
												<br><strong>1. Order Count:</strong> Number of orders processed
												<br><strong>2. Total Earnings:</strong> Commission earned from orders
												<br><strong>3. Average Order Value (AOV):</strong> Average value per order
											</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Attribution System</span>
											<span class="breakdown-value">
												Orders are split between Agent and Processor based on configured percentages (typically 70% Agent / 30% Processor). Your attributed order value is your percentage share of the total order amount.
											</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Score Calculation</span>
											<span class="breakdown-value">
												Base Score (5.0) + Points from Earnings Range + Points from Orders Range + Points from AOV Range = Final Score (capped at 10.0)
											</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Example Calculation</span>
											<span class="breakdown-value">
												If you have: 15 orders, $1,500 earnings, $100 AOV
												<br>• Base Score: 5.0 points
												<br>• Earnings ($1,500): +2.5 points (from your role's earnings range)
												<br>• Orders (15): +1.5 points (from your role's orders range)
												<br>• AOV ($100): +1.0 point (from your role's AOV range)
												<br><strong>Final Score: 10.0/10</strong>
											</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Current Period Data</span>
											<span class="breakdown-value">
												Date Range: ${filters.dateRange || 'Current Period'}
												<br>Status Filter: ${filters.orderStatus || 'All'}
												<br>Role Filter: ${filters.role || 'All'}
												<br>Your score reflects only orders matching these filters.
											</span>
										</div>
									</div>
								</div>
							</div>
						`;

						// Replace loading with actual content
						modal.find('.reports-modal-body').html(content);
					},
					error: function() {
						// Fallback if AJAX fails
						content = `
							<div class="drill-down-content">
								<div class="drill-down-section">
									<h4>Performance Score Breakdown</h4>
									<div class="breakdown-items">
										<div class="breakdown-item">
											<span class="breakdown-label">Current Score</span>
											<span class="breakdown-value">${kpiCards.my_performance_score.value}/10</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Total Orders</span>
											<span class="breakdown-value">${kpiCards.my_commission.orders}</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Order Total (Attributed)</span>
											<span class="breakdown-value">$0.00</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Total Earnings</span>
											<span class="breakdown-value">${kpiCards.my_earnings.value}</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Commission Earned</span>
											<span class="breakdown-value">${kpiCards.my_commission.value}</span>
										</div>
									</div>
								</div>
								<div class="drill-down-section">
									<h4>Performance Calculation</h4>
									<div class="breakdown-items">
										<div class="breakdown-item">
											<span class="breakdown-label">Calculation Method</span>
											<span class="breakdown-value">Role-based configuration from Performance Settings</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Based On</span>
											<span class="breakdown-value">Attributed order totals, order count, and AOV</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Filters Applied</span>
											<span class="breakdown-value">Date Range, Order Status, Role</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Period</span>
											<span class="breakdown-value">${filters.dateRange || 'Current Period'}</span>
										</div>
									</div>
								</div>
								<div class="drill-down-section">
									<h4>Calculation Details</h4>
									<div class="breakdown-items">
										<div class="breakdown-item">
											<span class="breakdown-label">How Performance Score Works</span>
											<span class="breakdown-value">
												Your performance score is calculated based on three key metrics:
												<br><strong>1. Order Count:</strong> Number of orders processed
												<br><strong>2. Total Earnings:</strong> Commission earned from orders
												<br><strong>3. Average Order Value (AOV):</strong> Average value per order
											</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Attribution System</span>
											<span class="breakdown-value">
												Orders are split between Agent and Processor based on configured percentages (typically 70% Agent / 30% Processor). Your attributed order value is your percentage share of the total order amount.
											</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Score Calculation</span>
											<span class="breakdown-value">
												Base Score (5.0) + Points from Earnings Range + Points from Orders Range + Points from AOV Range = Final Score (capped at 10.0)
											</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Example Calculation</span>
											<span class="breakdown-value">
												If you have: 15 orders, $1,500 earnings, $100 AOV
												<br>• Base Score: 5.0 points
												<br>• Earnings ($1,500): +2.5 points (from your role's earnings range)
												<br>• Orders (15): +1.5 points (from your role's orders range)
												<br>• AOV ($100): +1.0 point (from your role's AOV range)
												<br><strong>Final Score: 10.0/10</strong>
											</span>
										</div>
										<div class="breakdown-item">
											<span class="breakdown-label">Current Period Data</span>
											<span class="breakdown-value">
												Date Range: ${filters.dateRange || 'Current Period'}
												<br>Status Filter: ${filters.orderStatus || 'All'}
												<br>Role Filter: ${filters.role || 'All'}
												<br>Your score reflects only orders matching these filters.
											</span>
										</div>
									</div>
								</div>
							</div>
						`;

						// Replace loading with actual content
						modal.find('.reports-modal-body').html(content);
					}
				});
				break;

			default:
				content = '<div class="drill-down-content"><p>No details available</p></div>';
		}

		// Replace loading with actual content (skip for performance score as it's handled in AJAX)
		if (cardType !== 'my_performance_score') {
			modal.find('.reports-modal-body').html(content);
		}
		}, 150); // Small delay to ensure DOM is ready
	}

	/**
	 * Show goal details modal
	 */
	function showGoalDetailsModal(goalType) {
		const modal = $(`
			<div class="reports-modal-overlay">
				<div class="reports-modal">
					<div class="reports-modal-header">
						<h3>${goalType.replace(/_/g, ' ').toUpperCase()}</h3>
						<button class="reports-modal-close">
							<i class="ph ph-x"></i>
						</button>
					</div>
					<div class="reports-modal-body">
						<div class="goal-details-content">
							<div class="goal-detail-section">
								<h4>Goal Progress</h4>
								<div class="progress-detail">
									<div class="progress-bar-large">
										<div class="progress-fill" style="width: ${Math.random() * 100}%"></div>
									</div>
									<div class="progress-info">
										<span class="progress-actual">Actual: Loading...</span>
										<span class="progress-target">Target: $5,000</span>
									</div>
								</div>
							</div>
							<div class="goal-detail-section">
								<h4>Historical Performance</h4>
								<div class="history-items">
									<div class="history-item">
										<span class="history-period">This Month</span>
										<span class="history-value">Loading...</span>
									</div>
									<div class="history-item">
										<span class="history-period">Last Month</span>
										<span class="history-value">Loading...</span>
									</div>
									<div class="history-item">
										<span class="history-period">2 Months Ago</span>
										<span class="history-value">Loading...</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		`);

		$('body').append(modal);
	}

	/**
	 * Close drill-down modal
	 */
	function closeDrillDownModal() {
		$('.reports-modal-overlay').fadeOut(200, function() {
			$(this).remove();
		});
	}

	/**
	 * Bind filter change events
	 */
	function bindFilterEvents() {
		// Unbind existing events to prevent duplicates
		$('#reports-date-range, #reports-apply-filters, #reports-clear-filters').off();
		
		// Date range click - show inline dates when custom is selected
		$('#reports-date-range').on('click', function() {
			const preset = $(this).val();
			const customDateInline = $('#reports-custom-date-range');
			const dateFrom = $('#reports-start-date');
			const dateTo = $('#reports-end-date');
			
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

		// Date range change - handle preset selection
		$('#reports-date-range').on('change', function() {
			const preset = $(this).val();
			const customDateInline = $('#reports-custom-date-range');
			const dateFrom = $('#reports-start-date');
			const dateTo = $('#reports-end-date');
			
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
				
				// Auto-apply the filter
				filtersChanged = true;
				updateMasterFilters();
				saveFilterState();
				loadAllReportData();
				updateFilterSummary();
			}
		});

		// Custom date inputs - auto-filter when changed
		$('#reports-start-date, #reports-end-date').on('change', function() {
			lastCustomDateFrom = $('#reports-start-date').val();
			lastCustomDateTo = $('#reports-end-date').val();
			filtersChanged = true;
			updateMasterFilters();
			saveFilterState();
			loadAllReportData();
			updateFilterSummary();
		});

		// Close inline date inputs when clicking outside (optional)
		$(document).on('click', function(e) {
			if (!$(e.target).closest('.pv-date-filter-wrapper').length) {
				// Optional: could hide inline dates here, but usually they stay visible
			}
		});

		// Apply filters button
		$('#reports-apply-filters').on('click', function() {
			filtersChanged = true; // Mark that filters changed
			updateMasterFilters();
			saveFilterState();
			loadAllReportData();
			updateFilterSummary();
		});

		// Clear filters button
		$('#reports-clear-filters').on('click', function() {
			filtersChanged = true; // Mark that filters changed
			resetFilters();
			saveFilterState();
			loadAllReportData();
		});
	}

	/**
	 * Update master filters from form inputs
	 */
	function updateMasterFilters() {
		const dateRange = $('#reports-date-range').val();

		if (dateRange === 'custom') {
			masterFilters.customStartDate = $('#reports-start-date').val();
			masterFilters.customEndDate = $('#reports-end-date').val();
			masterFilters.dateRange = 'custom';
		} else {
			masterFilters.dateRange = dateRange;
			masterFilters.customStartDate = null;
			masterFilters.customEndDate = null;
		}

		masterFilters.orderStatus = $('#reports-order-status').val();
		masterFilters.role = $('#reports-role').val();
	}

	/**
	 * Reset all filters to defaults
	 */
	function resetFilters() {
		masterFilters = {
			dateRange: 'this-month',
			customStartDate: null,
			customEndDate: null,
			orderStatus: 'all',
			role: 'all'
		};

		// Reset form inputs
		$('#reports-date-range').val('this-month');
		$('#reports-order-status').val('all');
		$('#reports-role').val('all');
		$('#reports-custom-date-range').hide();
		$('#reports-filter-summary').hide();

		// Update clear button state
		$('#reports-clear-filters').removeClass('active');

		// Reset table states
		tableState = {
			'commission-table': {
				currentPage: 1,
				perPage: 10,
				sortColumn: null,
				sortOrder: 'asc',
				searchTerm: ''
			},
			'orders-table': {
				currentPage: 1,
				perPage: 10,
				sortColumn: null,
				sortOrder: 'asc',
				searchTerm: ''
			}
		};
	}

	/**
	 * Update filter summary display
	 */
	function updateFilterSummary() {
		const activeFilters = [];

		if (masterFilters.dateRange !== 'this-month') {
			activeFilters.push(masterFilters.dateRange.replace(/-/g, ' '));
		}
		if (masterFilters.orderStatus !== 'all') {
			activeFilters.push('Status: ' + masterFilters.orderStatus);
		}
		if (masterFilters.role !== 'all') {
			activeFilters.push('Role: ' + masterFilters.role);
		}

		if (activeFilters.length > 0) {
			$('#reports-active-filters-text').text(activeFilters.join(', '));
			$('#reports-filter-summary').show();
			$('#reports-clear-filters').addClass('active');
		} else {
			$('#reports-filter-summary').hide();
			$('#reports-clear-filters').removeClass('active');
		}
	}

	/**
	 * Load all report data with current filters
	 */
	function loadAllReportData() {
		loadDashboardData();
		loadAnalyticsData();
		loadPerformanceMetrics();
		loadTableData();
		loadGoalsData();
	}

	/**
	 * Load KPI dashboard data
	 */
	function loadDashboardData() {
		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_filtered_dashboard_data',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters
			},
			success: function(response) {
				if (response.success) {
					if (filtersChanged) {
						// Fade out old content, then fade in new content
						$('#reports-kpi-container').fadeOut(200, function() {
							$(this).html(response.data.html).fadeIn(300);
						});
					} else {
						// Silent update (auto-refresh)
						$('#reports-kpi-container').html(response.data.html);
					}
				} else {
					console.error('Response not successful. Error:', response.data);
					$('#reports-kpi-container').html('<div class="reports-no-data"><i class="ph ph-warning"></i><p>Error: ' + (response.data || 'Unknown error') + '</p></div>');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error - Status:', status);
				console.error('AJAX Error - Error:', error);
				console.error('AJAX Error - XHR:', xhr);
				console.error('AJAX Error - Response Text:', xhr.responseText);
				$('#reports-kpi-container').html('<div class="reports-no-data"><i class="ph ph-warning"></i><p>AJAX Error: ' + error + '</p></div>');
			}
		});
	}

	/**
	 * Load analytics charts data
	 */
	function loadAnalyticsData() {
		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_filtered_analytics_data',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters
			},
			success: function(response) {
				if (response.success) {
					if (filtersChanged) {
						// Fade out old content, then fade in new content
						$('#reports-charts-container').fadeOut(200, function() {
							$(this).html(response.data.html).fadeIn(300);
						});
					} else {
						// Silent update (auto-refresh)
						$('#reports-charts-container').html(response.data.html);
					}
				}
			},
			error: function() {
				$('#reports-charts-container').html('<div class="reports-no-data"><i class="ph ph-warning"></i><p>Error loading analytics data</p></div>');
			}
		});
	}

	/**
	 * Load performance metrics
	 */
	function loadPerformanceMetrics() {
		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_filtered_performance_data',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters
			},
			success: function(response) {
				if (response.success) {
					if (filtersChanged) {
						// Fade out old content, then fade in new content
						$('#reports-metrics-container').fadeOut(200, function() {
							$(this).html(response.data.html).fadeIn(300);
						});
					} else {
						// Silent update (auto-refresh)
						$('#reports-metrics-container').html(response.data.html);
					}
				}
			},
			error: function() {
				$('#reports-metrics-container').html('<div class="reports-no-data"><i class="ph ph-warning"></i><p>Error loading metrics</p></div>');
			}
		});
	}

	/**
	 * Load data tables
	 */
	function loadTableData() {
		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_filtered_table_data',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters
			},
			success: function(response) {
				if (response.success) {
					// Reset stored rows when new data loads
					tableState['commission-table'].allRows = null;
					tableState['orders-table'].allRows = null;
					
					if (filtersChanged) {
						// Fade out old content, then fade in new content
						$('#reports-tables-container').fadeOut(200, function() {
							$(this).html(response.data.html).fadeIn(300);
							// Bind table events after content loads
							bindTableEvents();
							// Initialize pagination for all tables
							initializeTablePagination();
						});
					} else {
						// Silent update (auto-refresh)
						$('#reports-tables-container').html(response.data.html);
						// Bind table events after content loads
						bindTableEvents();
						// Initialize pagination for all tables
						initializeTablePagination();
					}
				}
			},
			error: function() {
				$('#reports-tables-container').html('<div class="reports-no-data"><i class="ph ph-warning"></i><p>Error loading tables</p></div>');
			}
		});
	}

	/**
	 * Load goals and achievements data
	 */
	function loadGoalsData() {
		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_filtered_goals_data',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters
			},
			success: function(response) {
				if (response.success) {
					if (filtersChanged) {
						// Fade out old content, then fade in new content
						$('#reports-goals-container').fadeOut(200, function() {
							$(this).html(response.data.html).fadeIn(300);
						});
					} else {
						// Silent update (auto-refresh)
						$('#reports-goals-container').html(response.data.html);
					}
				}
			},
			error: function() {
				$('#reports-goals-container').html('<div class="reports-no-data"><i class="ph ph-warning"></i><p>Error loading goals</p></div>');
			}
		});
	}

	/**
	 * Bind table events (search, sort, pagination)
	 */
	function bindTableEvents() {
		// Search functionality
		$(document).on('keyup', '.table-search-input', function() {
			const tableId = $(this).data('table');
			const searchTerm = $(this).val().toLowerCase();
			tableState[tableId].searchTerm = searchTerm;
			tableState[tableId].currentPage = 1;
			filterAndDisplayTable(tableId);
		});

		// Per-page selection
		$(document).on('change', '.table-per-page-select', function() {
			const tableId = $(this).data('table');
			tableState[tableId].perPage = parseInt($(this).val());
			tableState[tableId].currentPage = 1;
			filterAndDisplayTable(tableId);
		});

		// Column sorting
		$(document).on('click', '.reports-table th.sortable', function() {
			const tableId = $(this).closest('.reports-table').attr('id');
			const sortColumn = $(this).data('sort');
			
			console.log('Sort clicked:', {
				tableId: tableId,
				sortColumn: sortColumn,
				currentSortColumn: tableState[tableId].sortColumn,
				currentSortOrder: tableState[tableId].sortOrder
			});
			
			// Toggle sort order if clicking same column
			if (tableState[tableId].sortColumn === sortColumn) {
				tableState[tableId].sortOrder = tableState[tableId].sortOrder === 'asc' ? 'desc' : 'asc';
				console.log('Toggling sort order to:', tableState[tableId].sortOrder);
			} else {
				tableState[tableId].sortColumn = sortColumn;
				tableState[tableId].sortOrder = 'asc';
				console.log('New column, setting to asc');
			}
			
			tableState[tableId].currentPage = 1;
			filterAndDisplayTable(tableId);
		});

		// Pagination
		$(document).on('click', '.reports-page-btn', function(e) {
			e.preventDefault();
			const page = parseInt($(this).data('page'));
			const tableId = $(this).closest('.reports-pagination').data('table');
			tableState[tableId].currentPage = page;
			filterAndDisplayTable(tableId);
		});
	}

	/**
	 * Filter and display table data
	 */
	function filterAndDisplayTable(tableId) {
		const $table = $('#' + tableId);
		const $tbody = $table.find('tbody');
		
		// Store all rows with their data if not already stored
		if (!tableState[tableId].allRows) {
			tableState[tableId].allRows = [];
			$tbody.find('tr').not(':has(.reports-no-data)').each(function() {
				const $row = $(this);
				const rowData = {};
				
				// Extract data-sort-value from each cell
				$row.find('td').each(function(index) {
					const $cell = $(this);
					const $header = $table.find('th').eq(index);
					const sortKey = $header.data('sort');
					
					if (sortKey) {
						// Use data-sort-value if available, otherwise use text
						rowData[sortKey] = $cell.attr('data-sort-value') || $cell.text().trim();
					}
				});
				
				// Store full text for search
				rowData.fullText = $row.text().toLowerCase();
				
				tableState[tableId].allRows.push({
					element: $row.clone(),
					data: rowData
				});
			});
		}
		
		let filteredRows = tableState[tableId].allRows.slice();

		// Apply search filter
		if (tableState[tableId].searchTerm) {
			const searchTerm = tableState[tableId].searchTerm.toLowerCase();
			filteredRows = filteredRows.filter(function(row) {
				return row.data.fullText.includes(searchTerm);
			});
		}

		// Apply sorting
		if (tableState[tableId].sortColumn) {
			const sortColumn = tableState[tableId].sortColumn;
			const sortOrder = tableState[tableId].sortOrder;
			
			filteredRows.sort(function(a, b) {
				let aVal = a.data[sortColumn];
				let bVal = b.data[sortColumn];
				
				// Try to parse as numbers
				const aNum = parseFloat(aVal);
				const bNum = parseFloat(bVal);
				
				if (!isNaN(aNum) && !isNaN(bNum)) {
					return sortOrder === 'asc' ? aNum - bNum : bNum - aNum;
				}
				
				// String comparison
				if (typeof aVal === 'string') {
					aVal = aVal.toLowerCase();
					bVal = bVal.toLowerCase();
				}
				
				if (sortOrder === 'asc') {
					return aVal > bVal ? 1 : -1;
				} else {
					return aVal < bVal ? 1 : -1;
				}
			});
			
			// Update sort icons
			updateSortIcons(tableId);
		}

		// Calculate pagination
		const totalRows = filteredRows.length;
		const perPage = tableState[tableId].perPage;
		const totalPages = Math.ceil(totalRows / perPage);
		const currentPage = Math.min(tableState[tableId].currentPage, totalPages) || 1;
		const startIndex = (currentPage - 1) * perPage;
		const endIndex = startIndex + perPage;
		const pageRows = filteredRows.slice(startIndex, endIndex);

		// Clear tbody
		$tbody.empty();

		// Show filtered and paginated rows
		if (pageRows.length === 0) {
			// Show no results message
			const colspan = $table.find('thead th').length;
			$tbody.append(`
				<tr>
					<td colspan="${colspan}" class="reports-no-data">
						<div class="no-results-message">
							<i class="ph ph-magnifying-glass"></i>
							<p>No results found matching your search.</p>
						</div>
					</td>
				</tr>
			`);
		} else {
			pageRows.forEach(function(row) {
				$tbody.append(row.element);
			});
		}

		// Update pagination info
		const $pagination = $('[data-table="' + tableId + '"]');
		$pagination.find('.pagination-start').text(pageRows.length === 0 ? 0 : startIndex + 1);
		$pagination.find('.pagination-end').text(Math.min(endIndex, totalRows));
		$pagination.find('.pagination-total').text(totalRows);

		// Generate pagination buttons
		generatePaginationButtons(tableId, totalPages, currentPage);
	}

	/**
	 * Update sort icons to reflect current sort state
	 */
	function updateSortIcons(tableId) {
		const $table = $('#' + tableId);
		const sortColumn = tableState[tableId].sortColumn;
		const sortOrder = tableState[tableId].sortOrder;
		
		// Reset all sort icons to default
		$table.find('th.sortable .sort-icon')
			.removeClass('ph-caret-up ph-caret-down')
			.addClass('ph-caret-up-down');
		
		// Update the active sort column icon
		if (sortColumn) {
			$table.find('th.sortable[data-sort="' + sortColumn + '"] .sort-icon')
				.removeClass('ph-caret-up-down')
				.addClass(sortOrder === 'asc' ? 'ph-caret-up' : 'ph-caret-down');
		}
	}

	/**
	 * Generate pagination buttons
	 */
	function generatePaginationButtons(tableId, totalPages, currentPage) {
		const $controls = $('[data-table="' + tableId + '"] .reports-pagination-controls');
		$controls.empty();

		if (totalPages <= 1) {
			return;
		}

		// Previous button
		if (currentPage > 1) {
			$controls.append('<a href="#" class="reports-page-btn" data-page="' + (currentPage - 1) + '"><i class="ph ph-caret-left"></i></a>');
		}

		// Page numbers
		let startPage = Math.max(1, currentPage - 2);
		let endPage = Math.min(totalPages, currentPage + 2);

		if (startPage > 1) {
			$controls.append('<a href="#" class="reports-page-btn" data-page="1">1</a>');
			if (startPage > 2) {
				$controls.append('<span class="reports-page-ellipsis">...</span>');
			}
		}

		for (let i = startPage; i <= endPage; i++) {
			const activeClass = i === currentPage ? 'current' : '';
			$controls.append('<a href="#" class="reports-page-btn ' + activeClass + '" data-page="' + i + '">' + i + '</a>');
		}

		if (endPage < totalPages) {
			if (endPage < totalPages - 1) {
				$controls.append('<span class="reports-page-ellipsis">...</span>');
			}
			$controls.append('<a href="#" class="reports-page-btn" data-page="' + totalPages + '">' + totalPages + '</a>');
		}

		// Next button
		if (currentPage < totalPages) {
			$controls.append('<a href="#" class="reports-page-btn" data-page="' + (currentPage + 1) + '"><i class="ph ph-caret-right"></i></a>');
		}
	}

	/**
	 * Initialize table pagination on load
	 */
	function initializeTablePagination() {
		Object.keys(tableState).forEach(function(tableId) {
			filterAndDisplayTable(tableId);
		});
	}

	// Export functions
	$('#reports-export-csv').on('click', function() {
		exportReportData('csv');
	});

	$('#reports-export-pdf').on('click', function() {
		exportReportData('pdf');
	});

	$('#reports-print-report').on('click', function() {
		// Get current filters
		const filters = masterFilters || {};
		
		// Show loading indicator
		const btn = $(this);
		const originalText = btn.html();
		btn.html('<i class="ph ph-spinner" style="animation: spin 1s linear infinite;"></i> Preparing...').prop('disabled', true);

		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_export_filtered_report',
				nonce: wc_tp_reports.nonce,
				filters: filters,
				format: 'pdf'
			},
			success: function(response) {
				btn.html(originalText).prop('disabled', false);
				
				// Open print window with the HTML content
				const printWindow = window.open('', 'print_report', 'height=800,width=1000');
				printWindow.document.write(response);
				printWindow.document.close();
				
				// Wait for content to load, then print
				setTimeout(() => {
					printWindow.print();
				}, 500);
			},
			error: function(xhr, status, error) {
				btn.html(originalText).prop('disabled', false);
				console.error('Print error:', error);
				alert('Print failed. Please try again.');
			}
		});
	});

	/**
	 * Export report data
	 */
	function exportReportData(format) {
		// Show loading indicator
		const btn = format === 'csv' ? $('#reports-export-csv') : $('#reports-export-pdf');
		const originalText = btn.html();
		btn.html('<i class="ph ph-spinner" style="animation: spin 1s linear infinite;"></i> Exporting...').prop('disabled', true);

		$.ajax({
			url: wc_tp_reports.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_export_filtered_report',
				nonce: wc_tp_reports.nonce,
				filters: masterFilters,
				format: format
			},
			xhrFields: {
				responseType: 'blob'
			},
			success: function(blob, status, xhr) {
				btn.html(originalText).prop('disabled', false);
				
				// Get filename from header or use default
				const filename = format === 'csv' ? 
					'reports_' + new Date().toISOString().split('T')[0] + '.csv' :
					'reports_' + new Date().toISOString().split('T')[0] + '.html';
				
				// Create blob URL and trigger download
				const url = window.URL.createObjectURL(blob);
				const link = document.createElement('a');
				link.href = url;
				link.download = filename;
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
				window.URL.revokeObjectURL(url);
			},
			error: function(xhr, status, error) {
				btn.html(originalText).prop('disabled', false);
				console.error('Export error:', error, xhr);
				
				// Try to parse error message
				let errorMsg = 'Export failed. Please try again.';
				try {
					if (xhr.responseText) {
						const response = JSON.parse(xhr.responseText);
						if (response.data) {
							errorMsg = response.data;
						}
					}
				} catch (e) {
					// Ignore parse errors
				}
				
				alert(errorMsg);
			}
		});
	}
});

	/**
	 * Helper: Get current filters
	 */
	function getCurrentFilters() {
		const dateRangeSelect = $('#reports-date-range').val();
		const orderStatus = $('#reports-order-status').val();
		const role = $('#reports-role').val();
		
		return {
			dateRange: dateRangeSelect ? dateRangeSelect.replace(/-/g, ' ').toUpperCase() : 'Current Period',
			orderStatus: orderStatus && orderStatus !== 'all' ? orderStatus.toUpperCase() : 'All',
			role: role && role !== 'all' ? role.toUpperCase() : 'All'
		};
	}

