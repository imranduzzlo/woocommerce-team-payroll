/**
 * Performance Tracker Frontend
 * Handles Goals, Achievements, and Baselines display
 * 
 * @package WooCommerce Team Payroll
 * @since 1.3.0
 */

(function($) {
	'use strict';

	const PerformanceTracker = {
		// Configuration
		periodType: 'monthly',
		currentView: 'current',
		currentTab: 'overview',
		currencySymbol: '$',
		currencyPosition: 'left',

		/**
		 * Initialize the Performance Tracker
		 */
		init() {
			// Get currency settings from WooCommerce
			this.currencySymbol = wc_tp_reports.currency_symbol || '$';
			this.currencyPosition = wc_tp_reports.currency_pos || 'left';
			this.loadConfiguration();
			this.bindEvents();
		},

		/**
		 * Load admin configuration (period type, etc.)
		 */
		loadConfiguration() {
			this.fetchData('config', (data) => {
				this.periodType = data.period_type || 'monthly';
				
				// Update view options based on period type
				this.updateViewOptions();
				
				// Load initial data
				this.loadOverview();
			});
		},

		/**
		 * Bind event handlers
		 */
		bindEvents() {
			// View selector change
			$(document).on('change', '#performance-view-selector', (e) => {
				this.currentView = $(e.target).val();
				this.refreshCurrentTab();
			});

			// Tab switching
			$(document).on('click', '.performance-tab', (e) => {
				e.preventDefault();
				const tab = $(e.currentTarget).data('tab');
				this.switchTab(tab);
			});

			// Refresh button
			$(document).on('click', '#performance-refresh-btn', () => {
				this.refreshCurrentTab();
			});
		},

		/**
		 * Update view options based on period type
		 */
		updateViewOptions() {
			const options = this.getViewOptions(this.periodType);
			const $selector = $('#performance-view-selector');
			
			if ($selector.length) {
				$selector.empty();
				options.forEach(opt => {
					$selector.append(`<option value="${opt.value}">${opt.label}</option>`);
				});
			}
		},

		/**
		 * Get view options based on period type
		 */
		getViewOptions(periodType) {
			const optionsMap = {
				'weekly': [
					{ value: 'current', label: 'Current Week' },
					{ value: 'last', label: 'Last Week' },
					{ value: 'last_4', label: 'Last 4 Weeks' },
					{ value: 'last_12', label: 'Last 12 Weeks' },
					{ value: 'ytd', label: 'Year to Date' }
				],
				'monthly': [
					{ value: 'current', label: 'Current Month' },
					{ value: 'last', label: 'Last Month' },
					{ value: 'last_3', label: 'Last 3 Months' },
					{ value: 'last_6', label: 'Last 6 Months' },
					{ value: 'last_12', label: 'Last 12 Months' },
					{ value: 'ytd', label: 'Year to Date' }
				],
				'quarterly': [
					{ value: 'current', label: 'Current Quarter' },
					{ value: 'last', label: 'Last Quarter' },
					{ value: 'last_4', label: 'Last 4 Quarters' },
					{ value: 'ytd', label: 'Year to Date' }
				],
				'yearly': [
					{ value: 'current', label: 'Current Year' },
					{ value: 'last', label: 'Last Year' },
					{ value: 'last_3', label: 'Last 3 Years' }
				]
			};

			return optionsMap[periodType] || optionsMap['monthly'];
		},

		/**
		 * Switch between tabs
		 */
		switchTab(tab) {
			this.currentTab = tab;

			// Update tab UI
			$('.performance-tab').removeClass('active');
			$(`.performance-tab[data-tab="${tab}"]`).addClass('active');

			// Load tab content
			switch(tab) {
				case 'overview':
					this.loadOverview();
					break;
				case 'goals':
					this.loadGoals();
					break;
				case 'achievements':
					this.loadAchievements();
					break;
				case 'baselines':
					this.loadBaselines();
					break;
			}
		},

		/**
		 * Refresh current tab
		 */
		refreshCurrentTab() {
			this.switchTab(this.currentTab);
		},

		/**
		 * Load overview data
		 */
		loadOverview() {
			this.showLoading();
			
			this.fetchData('overview', (data) => {
				this.renderOverview(data);
			}, { view_mode: this.currentView });
		},

		/**
		 * Load goals data
		 */
		loadGoals() {
			this.showLoading();
			
			this.fetchData('goals', (data) => {
				this.renderGoals(data);
			}, { view_mode: this.currentView });
		},

		/**
		 * Load achievements data
		 */
		loadAchievements() {
			this.showLoading();
			
			this.fetchData('achievements', (data) => {
				this.renderAchievements(data);
			});
		},

		/**
		 * Load baselines data
		 */
		loadBaselines() {
			this.showLoading();
			
			this.fetchData('baselines', (data) => {
				this.renderBaselines(data);
			});
		},

		/**
		 * Fetch data from server
		 */
		fetchData(section, callback, extraData = {}) {
			// Get user_id if available (for admin viewing employee performance)
			const userId = $('#wc-tp-current-user-id').val();
			const ajaxData = {
				action: 'wc_tp_get_performance_tracker_data',
				nonce: wc_tp_reports.nonce,
				section: section,
				cache_bust: Date.now(), // Add timestamp to prevent caching
				...extraData
			};
			
			// Add user_id if available (admin context)
			if (userId) {
				ajaxData.user_id = userId;
			}
			
			$.ajax({
				url: wc_tp_reports.ajax_url,
				type: 'POST',
				data: ajaxData,
				cache: false, // Disable jQuery AJAX caching
				success: (response) => {
					if (response.success) {
						callback(response.data);
					} else {
						this.showError(response.data?.message || 'Failed to load data');
					}
				},
				error: (xhr, status, error) => {
					console.error('Performance Tracker Error:', error);
					this.showError('Network error. Please try again.');
				}
			});
		},

		/**
		 * Render overview section
		 */
		renderOverview(data) {
			// Check if all sections are achieved
			const goalsAchieved = data.goals_summary?.html && (data.goals_summary.html.includes('100%') || data.goals_summary.html.includes('Goals Achieved'));
			const achievementsUnlocked = data.achievements_summary?.html && data.achievements_summary.html.match(/<strong>(\d+)<\/strong>\s*Total Unlocked/) && parseInt(data.achievements_summary.html.match(/<strong>(\d+)<\/strong>/)[1]) > 0;
			const baselinesAchieved = data.baselines_summary?.html && !data.baselines_summary.html.includes('Insufficient data') && !data.baselines_summary.html.includes('No data');
			
			const allAchieved = goalsAchieved && achievementsUnlocked && baselinesAchieved;

			const html = `
				<div class="performance-overview">
					<div class="overview-header">
						<h3><i class="ph ph-chart-line"></i> Performance Overview</h3>
						<span class="period-label">${this.getPeriodLabel()}</span>
					</div>

					${allAchieved ? `
						<div class="congratulations-banner">
							<i class="ph ph-confetti"></i>
							<div class="congratulations-content">
								<h4>🎉 Outstanding Performance!</h4>
								<p>Congratulations! You've achieved all your goals and unlocked achievements for this period. Keep up the excellent work!</p>
							</div>
						</div>
					` : ''}

					<div class="overview-grid">
						${this.renderOverviewCard('Goals', data.goals_summary)}
						${this.renderOverviewCard('Achievements', data.achievements_summary)}
						${this.renderOverviewCard('Baselines', data.baselines_summary)}
					</div>

					<div class="overview-quick-stats">
						<h4>Quick Stats</h4>
						${this.renderQuickStats(data)}
					</div>
				</div>
			`;

			$('#performance-content').html(html);
		},

		/**
		 * Render overview card
		 */
		renderOverviewCard(title, data) {
			if (!data) return '';

			// Determine if card should be marked as achieved
			let achievedClass = '';
			
			if (title === 'Goals' && data.html) {
				// Check if goals are achieved (look for "100%" or "Goals Achieved" in the HTML)
				if (data.html.includes('100%') || data.html.includes('Goals Achieved')) {
					achievedClass = 'achieved';
				}
			} else if (title === 'Achievements' && data.html) {
				// Check if achievements are unlocked (look for "Total Unlocked" with a number > 0)
				const match = data.html.match(/<strong>(\d+)<\/strong>\s*Total Unlocked/);
				if (match && parseInt(match[1]) > 0) {
					achievedClass = 'achieved';
				}
			}

			return `
				<div class="overview-card ${achievedClass}">
					<h4>${title}</h4>
					<div class="overview-card-content">
						${data.html || '<p>No data available</p>'}
					</div>
				</div>
			`;
		},

		/**
		 * Render quick stats
		 */
		renderQuickStats(data) {
			if (!data.quick_stats) return '<p>No stats available</p>';

			const stats = data.quick_stats;
			return `
				<div class="quick-stats-grid">
					${stats.map(stat => `
						<div class="stat-item">
							<span class="stat-label">${stat.label}</span>
							<span class="stat-value">${stat.value}</span>
						</div>
					`).join('')}
				</div>
			`;
		},

		/**
		 * Render goals section
		 */
		renderGoals(data) {
			if (!data.goals) {
				this.showError('No goals configured');
				return;
			}

			const goals = data.goals;
			const html = `
				<div class="performance-goals">
					<div class="goals-header">
						<h3><i class="ph ph-target"></i> Goals & Progress</h3>
						<span class="period-label">${goals.period_start} - ${goals.period_end}</span>
					</div>

					<div class="goals-grid">
						${this.renderGoalCard('Order Value', goals.order_value, 'ph-wallet', '#0073aa')}
						${this.renderGoalCard('Orders Count', goals.orders, 'ph-shopping-bag', '#28a745')}
						${this.renderGoalCard('Average Order Value', goals.aov, 'ph-chart-bar', '#ffc107')}
					</div>

					${data.history ? this.renderGoalHistory(data.history) : ''}
				</div>
			`;

			$('#performance-content').html(html);
		},

		/**
		 * Render single goal card
		 */
		renderGoalCard(label, goal, icon, color) {
			if (!goal) return '';

			const percentage = goal.percentage || 0;
			const status = goal.status || 'not_started';
			const achievedClass = status === 'achieved' || status === 'stretch_achieved' ? 'achieved' : '';

			return `
				<div class="goal-card ${achievedClass}" data-status="${status}">
					<div class="goal-header">
						<div class="goal-icon" style="background-color: ${color}20; color: ${color};">
							<i class="ph ${icon}"></i>
						</div>
						<div class="goal-title">
							<h4>${label}</h4>
							${achievedClass ? '<span class="achievement-badge"><i class="ph ph-check-circle"></i> Achieved!</span>' : ''}
						</div>
					</div>

					<div class="goal-progress">
						<div class="progress-bar-container">
							<div class="progress-bar" style="width: ${percentage}%; background-color: ${color};"></div>
						</div>
						<div class="progress-text">
							<span class="progress-percentage">${percentage.toFixed(0)}%</span>
						</div>
					</div>

					<div class="goal-stats">
						<div class="stat-row">
							<span class="stat-label">Current:</span>
							<span class="stat-value">${this.formatValue(goal.current, label)}</span>
						</div>
						<div class="stat-row">
							<span class="stat-label">Target:</span>
							<span class="stat-value">${this.formatValue(goal.target, label)}</span>
						</div>
						${goal.stretch ? `
							<div class="stat-row">
								<span class="stat-label">Stretch:</span>
								<span class="stat-value">${this.formatValue(goal.stretch, label)}</span>
							</div>
						` : ''}
					</div>

					<div class="goal-status-badge status-${status}">
						${this.getStatusLabel(status)}
					</div>
				</div>
			`;
		},

		/**
		 * Render goal history
		 */
		renderGoalHistory(history) {
			if (!history || history.length === 0) return '';

			return `
				<div class="goal-history">
					<h4><i class="ph ph-clock-clockwise"></i> Goal History</h4>
					<div class="history-list">
						${history.slice(0, 6).map(period => `
							<div class="history-item">
								<span class="history-period">${period.period}</span>
								<span class="history-status status-${period.order_value?.status || 'not_started'}">
									${this.getStatusIcon(period.order_value?.status)}
								</span>
								<span class="history-value">${this.formatValue(period.order_value?.current, 'Order Value')}</span>
							</div>
						`).join('')}
					</div>
				</div>
			`;
		},

		/**
		 * Render achievements section
		 */
		renderAchievements(data) {
			if (!data.achievements) {
				this.showError('No achievements configured');
				return;
			}

			const achievements = data.achievements;
			const stats = data.stats || {};
			const streaks = data.streaks || {};
			const bonusHistory = data.bonus_history || [];
			const bonusMilestones = data.bonus_milestones || [];

			const html = `
				<div class="performance-achievements">
					<div class="achievements-header">
						<h3><i class="ph ph-trophy"></i> Achievements & Streaks</h3>
						<div class="achievements-stats">
							<span class="stat-badge">Total: ${stats.total_unlocked || 0}</span>
							<span class="stat-badge bronze">🥉 ${stats.bronze_count || 0}</span>
							<span class="stat-badge silver">🥈 ${stats.silver_count || 0}</span>
							<span class="stat-badge gold">🥇 ${stats.gold_count || 0}</span>
						</div>
					</div>

					${this.renderStreakIndicators(streaks)}
					${this.renderBonusMilestones(bonusMilestones)}

					<h4 style="margin-top: 32px; margin-bottom: 16px;"><i class="ph ph-medal"></i> Achievement Badges</h4>
					<div class="achievements-grid">
						${Object.entries(achievements).map(([key, achievement]) => 
							this.renderAchievementCard(key, achievement)
						).join('')}
					</div>

					${bonusHistory.length > 0 ? this.renderBonusHistory(bonusHistory) : ''}
				</div>
			`;

			$('#performance-content').html(html);
		},

		/**
		 * Render single achievement card
		 */
		renderAchievementCard(key, achievement) {
			const isUnlocked = achievement.unlocked === true;
			const tier = achievement.tier || 'bronze';
			const tierEmoji = { bronze: '🥉', silver: '🥈', gold: '🥇' }[tier];

			if (isUnlocked) {
				return `
					<div class="achievement-card unlocked tier-${tier}">
						<div class="achievement-badge">${tierEmoji}</div>
						<h4>${this.formatAchievementName(key)}</h4>
						<p class="achievement-desc">Threshold: ${this.formatValue(achievement.threshold, key)}</p>
						<div class="achievement-meta">
							<span class="unlock-date">Unlocked: ${achievement.unlocked_date}</span>
							<span class="unlock-value">Value: ${this.formatValue(achievement.value_at_unlock, key)}</span>
						</div>
					</div>
				`;
			} else {
				const percentage = achievement.percentage || 0;
				return `
					<div class="achievement-card locked tier-${tier}">
						<div class="achievement-badge locked-badge">🔒</div>
						<h4>${this.formatAchievementName(key)}</h4>
						<p class="achievement-desc">Threshold: ${this.formatValue(achievement.threshold, key)}</p>
						<div class="achievement-progress">
							<div class="progress-bar-container">
								<div class="progress-bar" style="width: ${percentage}%;"></div>
							</div>
							<span class="progress-text">${percentage.toFixed(0)}%</span>
						</div>
						<div class="achievement-meta">
							<span>Current: ${this.formatValue(achievement.current_progress, key)}</span>
							<span>Remaining: ${this.formatValue(achievement.threshold - achievement.current_progress, key)}</span>
						</div>
					</div>
				`;
			}
		},

		/**
		 * Render streak indicators (Phase 2 Part 3)
		 */
		renderStreakIndicators(streaks) {
			if (!streaks || Object.keys(streaks).length === 0) {
				return '';
			}

			const tierData = {
				gold: { emoji: '🥇', color: '#FFD700', label: 'Gold' },
				silver: { emoji: '🥈', color: '#C0C0C0', label: 'Silver' },
				bronze: { emoji: '🥉', color: '#CD7F32', label: 'Bronze' }
			};

			// Find active streak
			let activeStreak = null;
			let maxStreak = 0;
			
			for (const [tier, data] of Object.entries(streaks)) {
				if (data.count > 0 && data.count > maxStreak) {
					maxStreak = data.count;
					activeStreak = { tier, ...data, ...tierData[tier] };
				}
			}

			if (!activeStreak) {
				return `
					<div class="streak-indicator-container">
						<div class="streak-indicator no-streak">
							<i class="ph ph-fire"></i>
							<div class="streak-content">
								<h4>No Active Streak</h4>
								<p>Earn a badge this month to start your streak!</p>
							</div>
						</div>
					</div>
				`;
			}

			return `
				<div class="streak-indicator-container">
					<div class="streak-indicator active-streak" style="border-color: ${activeStreak.color};">
						<div class="streak-flame" style="color: ${activeStreak.color};">
							<i class="ph ph-fire-fill"></i>
						</div>
						<div class="streak-content">
							<h4>
								<span class="streak-badge" style="background: ${activeStreak.color}20; color: ${activeStreak.color};">
									${activeStreak.emoji} ${activeStreak.label}
								</span>
								Streak Active!
							</h4>
							<div class="streak-count">
								<span class="count-number">${activeStreak.count}</span>
								<span class="count-label">Consecutive Month${activeStreak.count !== 1 ? 's' : ''}</span>
							</div>
							<p class="streak-message">Keep earning ${activeStreak.label} badges to maintain your streak!</p>
						</div>
					</div>
				</div>
			`;
		},

		/**
		 * Render bonus milestones (Phase 2 Part 3)
		 */
		renderBonusMilestones(milestones) {
			if (!milestones || milestones.length === 0) {
				return '';
			}

			const tierData = {
				gold: { emoji: '🥇', color: '#FFD700', label: 'Gold' },
				silver: { emoji: '🥈', color: '#C0C0C0', label: 'Silver' },
				bronze: { emoji: '🥉', color: '#CD7F32', label: 'Bronze' }
			};

			return `
				<div class="bonus-milestones-container">
					<h4><i class="ph ph-gift"></i> Bonus Milestones</h4>
					<div class="bonus-milestones-grid">
						${milestones.map(milestone => {
							const tier = tierData[milestone.tier];
							const isAchieved = milestone.is_achieved;
							const isActive = milestone.is_active;
							const alreadyAwarded = milestone.already_awarded;
							
							let statusClass = '';
							let statusText = '';
							
							if (alreadyAwarded) {
								statusClass = 'awarded';
								statusText = '✓ Awarded';
							} else if (isAchieved) {
								statusClass = 'achieved';
								statusText = '🎉 Eligible!';
							} else if (isActive) {
								statusClass = 'active';
								statusText = `${milestone.months_remaining} month${milestone.months_remaining !== 1 ? 's' : ''} to go`;
							} else {
								statusClass = 'inactive';
								statusText = 'Not started';
							}

							return `
								<div class="bonus-milestone-card ${statusClass}" style="border-color: ${tier.color};">
									<div class="milestone-header" style="background: ${tier.color}20;">
										<span class="milestone-badge" style="color: ${tier.color};">
											${tier.emoji} ${tier.label}
										</span>
										<span class="milestone-months">${milestone.required_months} Month${milestone.required_months !== 1 ? 's' : ''}</span>
									</div>
									
									<div class="milestone-body">
										<div class="milestone-reward">
											${milestone.bonus_type === 'money' ? `
												<div class="reward-amount">${this.formatValue(milestone.bonus_amount, 'value')}</div>
												<div class="reward-type">Cash Bonus</div>
											` : `
												<div class="reward-description">${milestone.bonus_description}</div>
												<div class="reward-type">${milestone.bonus_type === 'reward' ? 'Physical Reward' : 'Recognition'}</div>
											`}
										</div>

										<div class="milestone-progress">
											<div class="progress-bar-container">
												<div class="progress-bar" style="width: ${milestone.progress_percentage}%; background: ${tier.color};"></div>
											</div>
											<div class="progress-info">
												<span class="progress-current">${milestone.current_streak}/${milestone.required_months}</span>
												<span class="progress-status ${statusClass}">${statusText}</span>
											</div>
										</div>

										<div class="milestone-repeatable">
											<i class="ph ${milestone.repeatable ? 'ph-repeat' : 'ph-check-circle'}"></i> 
											${milestone.repeatable ? 'Repeatable' : 'One Time'}
										</div>
									</div>
								</div>
							`;
						}).join('')}
					</div>
				</div>
			`;
		},

		/**
		 * Render bonus history (Phase 2 Part 3)
		 */
		renderBonusHistory(history) {
			if (!history || history.length === 0) {
				return '';
			}

			const tierData = {
				gold: { emoji: '🥇', color: '#FFD700', label: 'Gold' },
				silver: { emoji: '🥈', color: '#C0C0C0', label: 'Silver' },
				bronze: { emoji: '🥉', color: '#CD7F32', label: 'Bronze' }
			};

			return `
				<div class="bonus-history-container">
					<h4><i class="ph ph-clock-clockwise"></i> Bonus History</h4>
					<div class="bonus-history-list">
						${history.slice(0, 10).map(bonus => {
							const tier = tierData[bonus.tier];
							return `
								<div class="bonus-history-item">
									<div class="bonus-icon" style="background: ${tier.color}20; color: ${tier.color};">
										${tier.emoji}
									</div>
									<div class="bonus-details">
										<div class="bonus-title">
											${tier.label} Badge Streak (${bonus.streak_count} month${bonus.streak_count !== 1 ? 's' : ''})
										</div>
										<div class="bonus-description">
											${bonus.bonus_type === 'money' ? 
												this.formatValue(bonus.bonus_amount, 'value') : 
												bonus.bonus_description
											}
										</div>
									</div>
									<div class="bonus-date">
										${bonus.awarded_date}
									</div>
								</div>
							`;
						}).join('')}
					</div>
				</div>
			`;
		},

		/**
		 * Render baselines section
		 */
		renderBaselines(data) {
			if (!data.baselines) {
				this.showError('No baselines calculated yet');
				return;
			}

			const baselines = data.baselines;

			// Handle undefined method and date with fallbacks
			const method = baselines.method || 'Not Set';
			const calculatedDate = baselines.calculated_date || 'Not Calculated';

			const html = `
				<div class="performance-baselines">
					<div class="baselines-header">
						<h3><i class="ph ph-chart-line-up"></i> Performance Baselines</h3>
						<div class="baselines-meta">
							<span>Method: ${method}</span>
							<span>Updated: ${calculatedDate}</span>
						</div>
					</div>

					<div class="baselines-grid">
						${this.renderBaselineCard('Order Value', baselines.order_value)}
						${this.renderBaselineCard('Orders Count', baselines.orders)}
						${this.renderBaselineCard('Average Order Value', baselines.aov)}
					</div>
				</div>
			`;

			$('#performance-content').html(html);
		},

		/**
		 * Render single baseline card
		 */
		renderBaselineCard(label, baseline) {
			if (!baseline) return '';

			const trend = baseline.trend || 'stable';
			const trendIcon = {
				'improving': 'ph-trend-up',
				'declining': 'ph-trend-down',
				'stable': 'ph-minus'
			}[trend];
			const trendColor = {
				'improving': '#28a745',
				'declining': '#dc3545',
				'stable': '#6c757d'
			}[trend];

			return `
				<div class="baseline-card trend-${trend}">
					<h4>${label}</h4>
					
					<div class="baseline-comparison">
						<div class="baseline-item">
							<span class="label">Current</span>
							<span class="value">${this.formatValue(baseline.current, label)}</span>
						</div>
						<div class="baseline-divider">vs</div>
						<div class="baseline-item">
							<span class="label">Baseline</span>
							<span class="value">${this.formatValue(baseline.baseline, label)}</span>
						</div>
					</div>

					<div class="baseline-difference" style="color: ${trendColor};">
						<i class="ph ${trendIcon}"></i>
						<span>${baseline.difference > 0 ? '+' : ''}${this.formatValue(baseline.difference, label)}</span>
						<span>(${baseline.percentage > 0 ? '+' : ''}${baseline.percentage.toFixed(1)}%)</span>
					</div>

					<div class="baseline-trend">
						<span class="trend-label">Trend:</span>
						<span class="trend-value" style="color: ${trendColor};">${trend.charAt(0).toUpperCase() + trend.slice(1)}</span>
					</div>
				</div>
			`;
		},

		/**
		 * Helper: Format value based on metric type
		 */
		formatValue(value, label) {
			if (value === null || value === undefined) return 'N/A';

			if (label.toLowerCase().includes('value') || label.toLowerCase().includes('earnings')) {
				const formatted = parseFloat(value).toFixed(2);
				// Use WooCommerce currency formatting
				if (this.currencyPosition === 'left') {
					return this.currencySymbol + formatted;
				} else if (this.currencyPosition === 'left_space') {
					return this.currencySymbol + ' ' + formatted;
				} else if (this.currencyPosition === 'right') {
					return formatted + this.currencySymbol;
				} else if (this.currencyPosition === 'right_space') {
					return formatted + ' ' + this.currencySymbol;
				}
				return this.currencySymbol + formatted;
			}

			return parseFloat(value).toFixed(label.toLowerCase().includes('average') ? 2 : 0);
		},

		/**
		 * Helper: Format achievement name
		 */
		formatAchievementName(key) {
			return key.split('_').map(word => 
				word.charAt(0).toUpperCase() + word.slice(1)
			).join(' ');
		},

		/**
		 * Helper: Get status label
		 */
		getStatusLabel(status) {
			const labels = {
				'not_started': 'Not Started',
				'in_progress': 'In Progress',
				'achieved': 'Achieved',
				'stretch_achieved': 'Stretch Achieved'
			};
			return labels[status] || status;
		},

		/**
		 * Helper: Get status icon
		 */
		getStatusIcon(status) {
			const icons = {
				'not_started': '○',
				'in_progress': '◐',
				'achieved': '✓',
				'stretch_achieved': '★'
			};
			return icons[status] || '○';
		},

		/**
		 * Helper: Get period label
		 */
		getPeriodLabel() {
			const viewLabels = {
				'current': 'Current Period',
				'last': 'Last Period',
				'last_3': 'Last 3 Periods',
				'last_6': 'Last 6 Periods',
				'last_12': 'Last 12 Periods',
				'ytd': 'Year to Date'
			};
			return viewLabels[this.currentView] || 'Current Period';
		},

		/**
		 * Show loading state
		 */
		showLoading() {
			$('#performance-content').html(`
				<div class="performance-loading">
					<i class="ph ph-spinner ph-spin"></i>
					<p>Loading performance data...</p>
				</div>
			`);
		},

		/**
		 * Show error message
		 */
		showError(message) {
			$('#performance-content').html(`
				<div class="performance-error">
					<i class="ph ph-warning-circle"></i>
					<p>${message}</p>
				</div>
			`);
		}
	};

	// Initialize when document is ready
	$(document).ready(function() {
		// Only initialize if performance tracker container exists
		if ($('#performance-tracker-container').length) {
			PerformanceTracker.init();
		}
	});

	// Expose to global scope for debugging
	window.PerformanceTracker = PerformanceTracker;

})(jQuery);
