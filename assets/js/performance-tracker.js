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
		achievementsEnabled: 1,
		achievementsDisplayStyle: 'badges',
		achievementsShowLocked: 1,
		achievementsNotification: 1,
		achievementsPeriod: 'monthly',
		userRole: '',
		roleAchievements: {},

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
				this.achievementsEnabled = data.achievements_enabled || 1;
				this.achievementsDisplayStyle = data.achievements_display_style || 'badges';
				this.achievementsShowLocked = data.achievements_show_locked || 1;
				this.achievementsNotification = data.achievements_notification || 1;
				this.achievementsPeriod = data.achievements_period || 'monthly';
				this.userRole = data.user_role || '';
				this.roleAchievements = data.role_achievements || {};
				
				// Update view options based on period type
				this.updateViewOptions();
				
				// Check if there's a saved tab in sessionStorage (persists during browser session only)
				const savedTab = sessionStorage.getItem('wc_tp_active_performance_tab');
				
				// Load saved tab or default to overview
				if (savedTab) {
					this.switchTab(savedTab);
				} else {
					this.loadOverview();
				}
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
			// Check if achievements are enabled before switching to achievements tab
			if ((tab === 'achievements' || tab === 'period_achievements') && this.achievementsEnabled === 0) {
				this.showError('Achievements are disabled in settings');
				return;
			}

			this.currentTab = tab;
			
			// Save current tab to sessionStorage (clears when browser/tab closes)
			sessionStorage.setItem('wc_tp_active_performance_tab', tab);

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
				case 'period_achievements':
					this.loadPeriodAchievements();
					break;
				case 'period_history':
					this.loadPeriodHistory();
					break;
				case 'bonus_achieved':
					this.loadBonusAchieved();
					break;
				case 'baselines':
					this.loadBaselines();
					break;
				case 'leaderboard':
					this.loadLeaderboard();
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
		 * Load period-based achievements data
		 */
		loadPeriodAchievements() {
			this.showLoading();
			
			this.fetchData('period_achievements', (data) => {
				this.renderPeriodAchievements(data);
				// Check and show notification for new period achievements
				this.checkAndShowPeriodNotification(data);
			});
		},

		/**
		 * Load period history data
		 */
		loadPeriodHistory() {
			this.showLoading();
			
			this.fetchData('period_achievements', (data) => {
				this.renderPeriodHistory(data);
			});
		},

		/**
		 * Load achieved bonuses data
		 */
		loadBonusAchieved() {
			this.showLoading();
			
			this.fetchData('bonus_achieved', (data) => {
				this.renderBonusAchieved(data);
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
		 * Load leaderboard data
		 */
		loadLeaderboard() {
			this.showLoading();
			
			$.ajax({
				url: wc_tp_reports.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_get_leaderboard_data',
					nonce: wc_tp_reports.nonce,
					cache_bust: Date.now()
				},
				success: (response) => {
					if (response.success) {
						this.renderLeaderboard(response.data);
					} else {
						// Check if leaderboard is disabled
						if (response.data && response.data.disabled) {
							this.showLeaderboardDisabled();
						} else {
							this.showError(response.data?.message || 'Failed to load leaderboard');
						}
					}
				},
				error: (xhr, status, error) => {
					console.error('Leaderboard AJAX Error:', error);
					this.showError('Network error. Please try again.');
				}
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
						console.error('AJAX Error for section:', section, response.data);
						this.showError(response.data?.message || 'Failed to load data');
					}
				},
				error: (xhr, status, error) => {
					console.error('Performance Tracker AJAX Error:', {
						section: section,
						status: status,
						error: error,
						response: xhr.responseText
					});
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
			
			// Update role-specific achievements if provided
			if (data.role_achievements) {
				this.roleAchievements = data.role_achievements;
			}
			if (data.user_role) {
				this.userRole = data.user_role;
			}
			if (data.achievements_config) {
				this.achievementsEnabled = data.achievements_config.enabled || 1;
				this.achievementsDisplayStyle = data.achievements_config.display_style || 'badges';
				this.achievementsShowLocked = data.achievements_config.show_locked || 1;
				this.achievementsNotification = data.achievements_config.notification || 1;
				this.achievementsPeriod = data.achievements_config.period || 'monthly';
			}

			// Filter achievements based on show_locked setting
			let achievementsToDisplay = achievements;
			if (!this.achievementsShowLocked) {
				achievementsToDisplay = Object.fromEntries(
					Object.entries(achievements).filter(([key, achievement]) => achievement.unlocked === true)
				);
			}

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
					<div class="achievements-grid achievements-display-${this.achievementsDisplayStyle}">
						${Object.entries(achievementsToDisplay).map(([key, achievement]) => 
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
			
			// Get role-specific achievement info if available
			const roleAchievementInfo = this.roleAchievements[key] || {};
			const achievementName = roleAchievementInfo.name || this.formatAchievementName(key);
			const achievementDesc = roleAchievementInfo.description || '';

			if (isUnlocked) {
				return `
					<div class="achievement-card unlocked tier-${tier}">
						<div class="achievement-badge">${tierEmoji}</div>
						<h4>${achievementName}</h4>
						${achievementDesc ? `<p class="achievement-desc">${achievementDesc}</p>` : ''}
						<p class="achievement-threshold">Threshold: ${this.formatValue(achievement.threshold, key)}</p>
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
						<h4>${achievementName}</h4>
						${achievementDesc ? `<p class="achievement-desc">${achievementDesc}</p>` : ''}
						<p class="achievement-threshold">Threshold: ${this.formatValue(achievement.threshold, key)}</p>
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
		 * Render period-based achievements section
		 */
		renderPeriodAchievements(data) {
			const periodType = data.period_type || 'monthly';
			const currentPeriodId = data.current_period_id || '';
			const periodAchievements = data.period_achievements || {};
			const periodHistory = data.period_history || {};
			const periodRange = data.period_range || {};
			const highestTier = data.highest_tier || '';

			const tierData = {
				gold: { emoji: '🥇', color: '#FFD700', label: 'Gold' },
				silver: { emoji: '🥈', color: '#C0C0C0', label: 'Silver' },
				bronze: { emoji: '🥉', color: '#CD7F32', label: 'Bronze' }
			};

			// Get period label
			const periodLabel = this.getPeriodLabel(currentPeriodId, periodType);

			if (!highestTier) {
				const html = `
					<div class="performance-period-achievements">
						<div class="period-achievements-header">
							<h3><i class="ph ph-medal"></i> Period Achievements</h3>
							<span class="period-label">${periodLabel}</span>
						</div>
						<div class="period-achievements-empty">
							<i class="ph ph-smiley-blank"></i>
							<p>No achievements earned in this period yet. Keep working towards your goals!</p>
						</div>
					</div>
				`;
				$('#performance-content').html(html);
				return;
			}

			const tier = tierData[highestTier];
			const earningsTier = periodAchievements.earnings_tier || '';
			const ordersTier = periodAchievements.orders_tier || '';
			const aovTier = periodAchievements.aov_tier || '';

			const html = `
				<div class="performance-period-achievements">
					<div class="period-achievements-header">
						<h3><i class="ph ph-medal"></i> Period Achievements</h3>
						<span class="period-label">${periodLabel}</span>
					</div>

					<!-- Current Period Badge -->
					<div class="period-badge-container">
						<div class="period-badge-display">
							<div class="period-badge-large" style="border-color: ${tier.color};">
								<svg class="badge-icon-large" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
									<defs>
										<radialGradient id="periodGradient" cx="35%" cy="35%">
											${highestTier === 'gold' ? `
												<stop offset="0%" style="stop-color:#FFFACD;stop-opacity:1"/>
												<stop offset="30%" style="stop-color:#FFD700;stop-opacity:1"/>
												<stop offset="70%" style="stop-color:#FFA500;stop-opacity:1"/>
												<stop offset="100%" style="stop-color:#8B6914;stop-opacity:1"/>
											` : highestTier === 'silver' ? `
												<stop offset="0%" style="stop-color:#FFFFFF;stop-opacity:1"/>
												<stop offset="30%" style="stop-color:#E8E8E8;stop-opacity:1"/>
												<stop offset="70%" style="stop-color:#C0C0C0;stop-opacity:1"/>
												<stop offset="100%" style="stop-color:#808080;stop-opacity:1"/>
											` : `
												<stop offset="0%" style="stop-color:#FFE4B5;stop-opacity:1"/>
												<stop offset="30%" style="stop-color:#CD7F32;stop-opacity:1"/>
												<stop offset="70%" style="stop-color:#B8860B;stop-opacity:1"/>
												<stop offset="100%" style="stop-color:#654321;stop-opacity:1"/>
											`}
										</radialGradient>
										<linearGradient id="periodShine" x1="0%" y1="0%" x2="100%" y2="100%">
											<stop offset="0%" style="stop-color:#FFFFFF;stop-opacity:0.6"/>
											<stop offset="50%" style="stop-color:#FFFFFF;stop-opacity:0"/>
											<stop offset="100%" style="stop-color:#000000;stop-opacity:0.3"/>
										</linearGradient>
										<filter id="periodShadow" x="-50%" y="-50%" width="200%" height="200%">
											<feDropShadow dx="2" dy="3" stdDeviation="2" flood-opacity="0.4"/>
										</filter>
									</defs>
									<circle cx="50" cy="50" r="46" fill="url(#periodGradient)" stroke="#000000" stroke-width="0.5" opacity="0.3"/>
									<circle cx="50" cy="50" r="45" fill="url(#periodGradient)" stroke="#000000" stroke-width="1" filter="url(#periodShadow)"/>
									<ellipse cx="40" cy="35" rx="20" ry="18" fill="url(#periodShine)" opacity="0.7"/>
									<circle cx="50" cy="50" r="38" fill="none" stroke="#000000" stroke-width="0.5" opacity="0.2"/>
									<text x="50" y="62" font-size="48" font-weight="bold" text-anchor="middle" fill="${highestTier === 'gold' ? '#8B6914' : highestTier === 'silver' ? '#606060' : '#6B3410'}" font-family="Arial, sans-serif">${highestTier.charAt(0).toUpperCase()}</text>
									<circle cx="50" cy="50" r="44" fill="none" stroke="#FFFFFF" stroke-width="1" opacity="0.3"/>
								</svg>
							</div>
							<div class="period-badge-info">
								<h4>${tier.emoji} ${tier.label} Achievement</h4>
								<p class="period-dates">${periodRange.start_date} to ${periodRange.end_date}</p>
								<div class="period-metrics">
									<div class="metric-item">
										<span class="metric-label">Earnings:</span>
										<span class="metric-value">${this.formatValue(periodAchievements.earnings, 'value')}</span>
										${earningsTier ? `<span class="metric-tier" style="color: ${tierData[earningsTier]?.color || '#999'}">${tierData[earningsTier]?.label || ''}</span>` : ''}
									</div>
									<div class="metric-item">
										<span class="metric-label">Orders:</span>
										<span class="metric-value">${periodAchievements.orders || 0}</span>
										${ordersTier ? `<span class="metric-tier" style="color: ${tierData[ordersTier]?.color || '#999'}">${tierData[ordersTier]?.label || ''}</span>` : ''}
									</div>
									<div class="metric-item">
										<span class="metric-label">AOV:</span>
										<span class="metric-value">${this.formatValue(periodAchievements.aov, 'value')}</span>
										${aovTier ? `<span class="metric-tier" style="color: ${tierData[aovTier]?.color || '#999'}">${tierData[aovTier]?.label || ''}</span>` : ''}
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Detailed Period History -->
					${Object.keys(periodHistory).length > 0 ? `
						<div class="period-history-detailed-container">
							<div class="period-history-header">
								<h4><i class="ph ph-clock-clockwise"></i> Achievement History</h4>
								<span class="history-count">${Object.keys(periodHistory).length} Period${Object.keys(periodHistory).length !== 1 ? 's' : ''}</span>
							</div>
							
							<!-- Timeline View -->
							<div class="period-history-timeline">
								${Object.entries(periodHistory).map(([periodId, periodData], index) => {
									const historyTier = periodData.highest_tier || '';
									const historyTierData = tierData[historyTier];
									const achievementsCount = (periodData.achievements_unlocked || []).length;
									const isFirst = index === 0;
									
									return `
										<div class="timeline-item ${isFirst ? 'latest' : ''}" data-period-id="${periodId}">
											<div class="timeline-marker" style="background: ${historyTierData?.color || '#999'};">
												<span class="marker-emoji">${historyTierData?.emoji || '?'}</span>
											</div>
											<div class="timeline-content">
												<div class="timeline-header">
													<h5 class="timeline-period">${this.getPeriodLabel(periodId, periodType)}</h5>
													${isFirst ? '<span class="badge-latest">Latest</span>' : ''}
												</div>
												<div class="timeline-achievement">
													<span class="achievement-tier" style="color: ${historyTierData?.color || '#999'}; border-color: ${historyTierData?.color || '#999'};">
														${historyTierData?.label || 'No Achievement'}
													</span>
													<span class="achievement-count">${achievementsCount} Achievement${achievementsCount !== 1 ? 's' : ''}</span>
												</div>
												<div class="timeline-details">
													${periodData.achievements_unlocked && periodData.achievements_unlocked.length > 0 ? `
														<div class="achievements-list">
															${periodData.achievements_unlocked.map(achievement => {
																const tierMatch = achievement.match(/(gold|silver|bronze)/);
																const metricMatch = achievement.match(/(earnings|orders|aov)/);
																const achievementTier = tierMatch ? tierMatch[1] : '';
																const achievementMetric = metricMatch ? metricMatch[1] : '';
																const achievementTierData = tierData[achievementTier];
																
																return `
																	<span class="achievement-badge" style="background: ${achievementTierData?.color || '#999'}20; color: ${achievementTierData?.color || '#999'}; border-color: ${achievementTierData?.color || '#999'};">
																		${achievementTierData?.emoji || ''} ${achievementMetric.toUpperCase()}
																	</span>
																`;
															}).join('')}
														</div>
													` : ''}
												</div>
											</div>
										</div>
									`;
								}).join('')}
							</div>

							<!-- Summary Stats -->
							<div class="period-history-summary">
								<div class="summary-stat">
									<span class="stat-label">Gold Periods:</span>
									<span class="stat-value" style="color: #FFD700;">${Object.values(periodHistory).filter(p => p.highest_tier === 'gold').length}</span>
								</div>
								<div class="summary-stat">
									<span class="stat-label">Silver Periods:</span>
									<span class="stat-value" style="color: #C0C0C0;">${Object.values(periodHistory).filter(p => p.highest_tier === 'silver').length}</span>
								</div>
								<div class="summary-stat">
									<span class="stat-label">Bronze Periods:</span>
									<span class="stat-value" style="color: #CD7F32;">${Object.values(periodHistory).filter(p => p.highest_tier === 'bronze').length}</span>
								</div>
								<div class="summary-stat">
									<span class="stat-label">Total Achievements:</span>
									<span class="stat-value">${Object.values(periodHistory).reduce((sum, p) => sum + (p.achievements_unlocked?.length || 0), 0)}</span>
								</div>
							</div>
						</div>
					` : ''}
				</div>
			`;

			$('#performance-content').html(html);

			// Bind timeline item click events for expansion
			$(document).on('click', '.timeline-item', function() {
				$(this).toggleClass('expanded');
			});
		},

		/**
		 * Render period history admin view
		 */
		renderPeriodHistory(data) {
			const periodType = data.period_type || 'monthly';
			const periodHistory = data.period_history || {};
			const currentPeriodId = data.current_period_id || '';

			const tierData = {
				gold: { emoji: '🥇', color: '#FFD700', label: 'Gold' },
				silver: { emoji: '🥈', color: '#C0C0C0', label: 'Silver' },
				bronze: { emoji: '🥉', color: '#CD7F32', label: 'Bronze' }
			};

			if (Object.keys(periodHistory).length === 0) {
				const html = `
					<div class="period-history-admin">
						<div class="period-history-header">
							<h3><i class="ph ph-clock-clockwise"></i> Period Achievement History</h3>
						</div>
						<div class="period-history-empty">
							<i class="ph ph-smiley-blank"></i>
							<p>No period history available yet.</p>
						</div>
					</div>
				`;
				$('#performance-content').html(html);
				return;
			}

			const periodEntries = Object.entries(periodHistory).sort((a, b) => {
				// Sort by period ID in descending order (newest first)
				return b[0].localeCompare(a[0]);
			});

			const html = `
				<div class="period-history-admin">
					<div class="period-history-header">
						<h3><i class="ph ph-clock-clockwise"></i> Period Achievement History</h3>
						<span class="history-count">${periodEntries.length} Period${periodEntries.length !== 1 ? 's' : ''}</span>
					</div>

					<div class="period-history-grid">
						${periodEntries.map(([periodId, periodData], index) => {
							const tier = periodData.highest_tier || '';
							const tierInfo = tierData[tier];
							const achievements = periodData.achievements_unlocked || [];
							const isCurrentPeriod = periodId === currentPeriodId;

							return `
								<div class="period-history-card ${isCurrentPeriod ? 'current' : ''}" data-period-id="${periodId}">
									<div class="card-header">
										<div class="period-label-badge">
											<span class="period-name">${this.getPeriodLabel(periodId, periodType)}</span>
											${isCurrentPeriod ? '<span class="badge-current">Current</span>' : ''}
										</div>
										<div class="period-dates">
											<i class="ph ph-calendar"></i>
											${periodData.start_date} to ${periodData.end_date}
										</div>
									</div>

									<div class="card-achievement">
										${tier ? `
											<div class="achievement-badge" style="border-color: ${tierInfo.color};">
												<svg class="badge-icon-small" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
													<defs>
														<radialGradient id="periodGradient${periodId}" cx="35%" cy="35%">
															${tier === 'gold' ? `
																<stop offset="0%" style="stop-color:#FFFACD;stop-opacity:1"/>
																<stop offset="30%" style="stop-color:#FFD700;stop-opacity:1"/>
																<stop offset="70%" style="stop-color:#FFA500;stop-opacity:1"/>
																<stop offset="100%" style="stop-color:#8B6914;stop-opacity:1"/>
															` : tier === 'silver' ? `
																<stop offset="0%" style="stop-color:#FFFFFF;stop-opacity:1"/>
																<stop offset="30%" style="stop-color:#E8E8E8;stop-opacity:1"/>
																<stop offset="70%" style="stop-color:#C0C0C0;stop-opacity:1"/>
																<stop offset="100%" style="stop-color:#808080;stop-opacity:1"/>
															` : `
																<stop offset="0%" style="stop-color:#FFE4B5;stop-opacity:1"/>
																<stop offset="30%" style="stop-color:#CD7F32;stop-opacity:1"/>
																<stop offset="70%" style="stop-color:#B8860B;stop-opacity:1"/>
																<stop offset="100%" style="stop-color:#654321;stop-opacity:1"/>
															`}
														</radialGradient>
														<linearGradient id="periodShine${periodId}" x1="0%" y1="0%" x2="100%" y2="100%">
															<stop offset="0%" style="stop-color:#FFFFFF;stop-opacity:0.6"/>
															<stop offset="50%" style="stop-color:#FFFFFF;stop-opacity:0"/>
															<stop offset="100%" style="stop-color:#000000;stop-opacity:0.3"/>
														</linearGradient>
														<filter id="periodShadow${periodId}" x="-50%" y="-50%" width="200%" height="200%">
															<feDropShadow dx="2" dy="3" stdDeviation="2" flood-opacity="0.4"/>
														</filter>
													</defs>
													<circle cx="50" cy="50" r="46" fill="url(#periodGradient${periodId})" stroke="#000000" stroke-width="0.5" opacity="0.3"/>
													<circle cx="50" cy="50" r="45" fill="url(#periodGradient${periodId})" stroke="#000000" stroke-width="1" filter="url(#periodShadow${periodId})"/>
													<ellipse cx="40" cy="35" rx="20" ry="18" fill="url(#periodShine${periodId})" opacity="0.7"/>
													<circle cx="50" cy="50" r="38" fill="none" stroke="#000000" stroke-width="0.5" opacity="0.2"/>
													<text x="50" y="62" font-size="48" font-weight="bold" text-anchor="middle" fill="${tier === 'gold' ? '#8B6914' : tier === 'silver' ? '#606060' : '#6B3410'}" font-family="Arial, sans-serif">${tier.charAt(0).toUpperCase()}</text>
													<circle cx="50" cy="50" r="44" fill="none" stroke="#FFFFFF" stroke-width="1" opacity="0.3"/>
												</svg>
											</div>
											<div class="achievement-info">
												<h4>${tierInfo.emoji} ${tierInfo.label}</h4>
												<p class="achievement-count">${achievements.length} Achievement${achievements.length !== 1 ? 's' : ''}</p>
											</div>
										` : `
											<div class="achievement-none">
												<i class="ph ph-smiley-blank"></i>
												<p>No achievements</p>
											</div>
										`}
									</div>

									<div class="card-metrics">
										<div class="metric">
											<span class="metric-label">Earnings:</span>
											<span class="metric-value">${this.formatValue(periodData.earnings, 'value')}</span>
										</div>
										<div class="metric">
											<span class="metric-label">Orders:</span>
											<span class="metric-value">${periodData.orders || 0}</span>
										</div>
										<div class="metric">
											<span class="metric-label">AOV:</span>
											<span class="metric-value">${this.formatValue(periodData.aov, 'value')}</span>
										</div>
									</div>

									${achievements.length > 0 ? `
										<div class="card-achievements-list">
											<h5>Achievements Unlocked:</h5>
											<ul>
												${achievements.map(achievement => {
													const tierMatch = achievement.match(/(gold|silver|bronze)/);
													const metricMatch = achievement.match(/(earnings|orders|aov)/);
													const achievementTier = tierMatch ? tierMatch[1] : '';
													const metric = metricMatch ? metricMatch[1] : '';
													const tierEmoji = tierData[achievementTier]?.emoji || '';
													const metricLabel = metric.charAt(0).toUpperCase() + metric.slice(1);
													return `<li>${tierEmoji} ${metricLabel} - ${achievementTier.charAt(0).toUpperCase() + achievementTier.slice(1)}</li>`;
												}).join('')}
											</ul>
										</div>
									` : ''}
								</div>
							`;
						}).join('')}
					</div>
				</div>
			`;

			$('#performance-content').html(html);

			// Check for new period achievements and show notification
			this.checkAndShowPeriodNotification(data);
		},

		/**
		 * Check for new period achievements and show notification
		 */
		checkAndShowPeriodNotification(data) {
			const currentPeriodId = data.current_period_id || '';
			const periodAchievements = data.period_achievements || {};
			const highestTier = data.highest_tier || '';

			// Check if this is a new achievement (not previously notified)
			const notificationKey = `wc_tp_period_notified_${currentPeriodId}`;
			const hasNotified = sessionStorage.getItem(notificationKey);

			if (highestTier && !hasNotified) {
				// Mark as notified
				sessionStorage.setItem(notificationKey, 'true');

				// Show notification
				this.showPeriodAchievementNotification(data);
			}
		},

		/**
		 * Show period achievement notification modal
		 */
		showPeriodAchievementNotification(data) {
			const periodType = data.period_type || 'monthly';
			const currentPeriodId = data.current_period_id || '';
			const periodAchievements = data.period_achievements || {};
			const highestTier = data.highest_tier || '';
			const periodRange = data.period_range || {};

			const tierData = {
				gold: { emoji: '🥇', color: '#FFD700', label: 'Gold', bgColor: '#FFF9C4' },
				silver: { emoji: '🥈', color: '#C0C0C0', label: 'Silver', bgColor: '#F5F5F5' },
				bronze: { emoji: '🥉', color: '#CD7F32', label: 'Bronze', bgColor: '#FFF3E0' }
			};

			const tier = tierData[highestTier];
			const periodLabel = this.getPeriodLabel(currentPeriodId, periodType);
			const achievements = periodAchievements.achievements_unlocked || [];

			const notificationHtml = `
				<div class="period-achievement-notification-overlay">
					<div class="period-achievement-notification-modal">
						<button class="notification-close" aria-label="Close notification">
							<i class="ph ph-x"></i>
						</button>

						<div class="notification-content">
							<div class="notification-header">
								<h2><i class="ph ph-confetti"></i> Congratulations!</h2>
								<p>You've earned a period achievement!</p>
							</div>

							<div class="notification-badge-container" style="background: ${tier.bgColor};">
								<div class="notification-badge-large" style="border-color: ${tier.color};">
									<svg class="badge-icon-notification" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
										<defs>
											<radialGradient id="notificationGradient" cx="35%" cy="35%">
												${highestTier === 'gold' ? `
													<stop offset="0%" style="stop-color:#FFFACD;stop-opacity:1"/>
													<stop offset="30%" style="stop-color:#FFD700;stop-opacity:1"/>
													<stop offset="70%" style="stop-color:#FFA500;stop-opacity:1"/>
													<stop offset="100%" style="stop-color:#8B6914;stop-opacity:1"/>
												` : highestTier === 'silver' ? `
													<stop offset="0%" style="stop-color:#FFFFFF;stop-opacity:1"/>
													<stop offset="30%" style="stop-color:#E8E8E8;stop-opacity:1"/>
													<stop offset="70%" style="stop-color:#C0C0C0;stop-opacity:1"/>
													<stop offset="100%" style="stop-color:#808080;stop-opacity:1"/>
												` : `
													<stop offset="0%" style="stop-color:#FFE4B5;stop-opacity:1"/>
													<stop offset="30%" style="stop-color:#CD7F32;stop-opacity:1"/>
													<stop offset="70%" style="stop-color:#B8860B;stop-opacity:1"/>
													<stop offset="100%" style="stop-color:#654321;stop-opacity:1"/>
												`}
											</radialGradient>
											<linearGradient id="notificationShine" x1="0%" y1="0%" x2="100%" y2="100%">
												<stop offset="0%" style="stop-color:#FFFFFF;stop-opacity:0.6"/>
												<stop offset="50%" style="stop-color:#FFFFFF;stop-opacity:0"/>
												<stop offset="100%" style="stop-color:#000000;stop-opacity:0.3"/>
											</linearGradient>
											<filter id="notificationShadow" x="-50%" y="-50%" width="200%" height="200%">
												<feDropShadow dx="2" dy="3" stdDeviation="2" flood-opacity="0.4"/>
											</filter>
										</defs>
										<circle cx="50" cy="50" r="46" fill="url(#notificationGradient)" stroke="#000000" stroke-width="0.5" opacity="0.3"/>
										<circle cx="50" cy="50" r="45" fill="url(#notificationGradient)" stroke="#000000" stroke-width="1" filter="url(#notificationShadow)"/>
										<ellipse cx="40" cy="35" rx="20" ry="18" fill="url(#notificationShine)" opacity="0.7"/>
										<circle cx="50" cy="50" r="38" fill="none" stroke="#000000" stroke-width="0.5" opacity="0.2"/>
										<text x="50" y="62" font-size="48" font-weight="bold" text-anchor="middle" fill="${highestTier === 'gold' ? '#8B6914' : highestTier === 'silver' ? '#606060' : '#6B3410'}" font-family="Arial, sans-serif">${highestTier.charAt(0).toUpperCase()}</text>
										<circle cx="50" cy="50" r="44" fill="none" stroke="#FFFFFF" stroke-width="1" opacity="0.3"/>
									</svg>
								</div>
							</div>

							<div class="notification-details">
								<h3>${tier.emoji} ${tier.label} Achievement</h3>
								<p class="notification-period">${periodLabel}</p>
								<p class="notification-dates">${periodRange.start_date} to ${periodRange.end_date}</p>

								<div class="notification-metrics">
									<div class="metric-item">
										<span class="metric-label">Earnings:</span>
										<span class="metric-value">${this.formatValue(periodAchievements.earnings, 'value')}</span>
									</div>
									<div class="metric-item">
										<span class="metric-label">Orders:</span>
										<span class="metric-value">${periodAchievements.orders || 0}</span>
									</div>
									<div class="metric-item">
										<span class="metric-label">AOV:</span>
										<span class="metric-value">${this.formatValue(periodAchievements.aov, 'value')}</span>
									</div>
								</div>

								${achievements.length > 0 ? `
									<div class="notification-achievements">
										<h4>Achievements Unlocked:</h4>
										<ul>
											${achievements.map(achievement => {
												const tierMatch = achievement.match(/(gold|silver|bronze)/);
												const metricMatch = achievement.match(/(earnings|orders|aov)/);
												const achievementTier = tierMatch ? tierMatch[1] : '';
												const metric = metricMatch ? metricMatch[1] : '';
												const tierEmoji = tierData[achievementTier]?.emoji || '';
												const metricLabel = metric.charAt(0).toUpperCase() + metric.slice(1);
												return `<li>${tierEmoji} ${metricLabel} - ${achievementTier.charAt(0).toUpperCase() + achievementTier.slice(1)}</li>`;
											}).join('')}
										</ul>
									</div>
								` : ''}
							</div>

							<div class="notification-actions">
								<button class="btn-notification-close">View Details</button>
							</div>
						</div>
					</div>
				</div>
			`;

			// Add notification to page
			$('body').append(notificationHtml);

			// Add animation
			setTimeout(() => {
				$('.period-achievement-notification-overlay').addClass('show');
			}, 100);

			// Close button handlers
			$(document).on('click', '.notification-close, .btn-notification-close', function() {
				$('.period-achievement-notification-overlay').removeClass('show');
				setTimeout(() => {
					$('.period-achievement-notification-overlay').remove();
				}, 300);
			});

			// Close on overlay click
			$(document).on('click', '.period-achievement-notification-overlay', function(e) {
				if ($(e.target).hasClass('period-achievement-notification-overlay')) {
					$(this).find('.notification-close').click();
				}
			});
		},

		/**
		 * Get human-readable period label
		 */
		getPeriodLabel(periodId, periodType) {
			if (!periodId) return 'Current Period';

			switch (periodType) {
				case 'daily':
					return new Date(periodId).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
				
				case 'weekly':
					// Period ID format: 2026-W16
					const weekMatch = periodId.match(/(\d{4})-W(\d+)/);
					if (weekMatch) {
						return `Week ${weekMatch[2]} of ${weekMatch[1]}`;
					}
					return periodId;
				
				case 'monthly':
					const monthDate = new Date(periodId + '-01');
					return monthDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
				
				case 'quarterly':
					// Period ID format: 2026-Q2
					const quarterMatch = periodId.match(/(\d{4})-Q(\d)/);
					if (quarterMatch) {
						return `Q${quarterMatch[2]} ${quarterMatch[1]}`;
					}
					return periodId;
				
				case 'half_yearly':
					// Period ID format: 2026-H1
					const halfMatch = periodId.match(/(\d{4})-H(\d)/);
					if (halfMatch) {
						const halfLabel = halfMatch[2] === '1' ? 'First Half' : 'Second Half';
						return `${halfLabel} ${halfMatch[1]}`;
					}
					return periodId;
				
				case 'yearly':
					return periodId;
				
				default:
					return periodId;
			}
		},

		/**
		 * Render achieved bonuses section (STEP 5)
		 */
		renderBonusAchieved(data) {
			const achievedBonuses = data.achieved_bonuses || [];

			if (achievedBonuses.length === 0) {
				const html = `
					<div class="performance-bonus-achieved">
						<div class="bonus-achieved-header">
							<h3><i class="ph ph-gift"></i> Achieved Bonuses</h3>
						</div>
						<div class="bonus-achieved-empty">
							<i class="ph ph-smiley-blank"></i>
							<p>No bonuses achieved yet. Keep working towards your goals!</p>
						</div>
					</div>
				`;
				$('#performance-content').html(html);
				return;
			}

			const tierData = {
				gold: { emoji: '🥇', color: '#FFD700', label: 'Gold' },
				silver: { emoji: '🥈', color: '#C0C0C0', label: 'Silver' },
				bronze: { emoji: '🥉', color: '#CD7F32', label: 'Bronze' }
			};

			const html = `
				<div class="performance-bonus-achieved">
					<div class="bonus-achieved-header">
						<h3><i class="ph ph-gift"></i> Achieved Bonuses</h3>
						<span class="bonus-count">${achievedBonuses.length} Bonus${achievedBonuses.length !== 1 ? 'es' : ''}</span>
					</div>

					<div class="bonus-achieved-table-container">
						<table class="bonus-achieved-table">
							<thead>
								<tr>
									<th>Tier</th>
									<th>Description</th>
									<th>Amount/Type</th>
									<th>Status</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								${achievedBonuses.map(bonus => {
									const tier = tierData[bonus.tier];
									const statusClass = bonus.status === 'claimed' ? 'claimed' : (bonus.status === 'submitted' ? 'submitted' : 'pending');
									const statusLabel = bonus.status === 'claimed' ? 'Claimed' : (bonus.status === 'submitted' ? 'Submitted' : 'Pending');
									const isMoney = bonus.bonus_type === 'money';
									const isPhysical = bonus.bonus_type !== 'money';

									return `
										<tr class="bonus-row status-${statusClass}">
											<td class="tier-cell">
												<span class="tier-badge" style="background: ${tier.color}20; color: ${tier.color};">
													${tier.emoji} ${tier.label}
												</span>
											</td>
											<td class="description-cell">
												<div class="bonus-info">
													<div class="bonus-title">${bonus.bonus_description}</div>
													<div class="bonus-meta">Achieved: ${bonus.achieved_date}</div>
												</div>
											</td>
											<td class="amount-cell">
												${isMoney ? 
													`<span class="amount-value">${this.formatValue(bonus.bonus_amount, 'value')}</span>` :
													`<span class="type-value">${bonus.bonus_type}</span>`
												}
											</td>
											<td class="status-cell">
												<span class="status-badge status-${statusClass}">
													${statusClass === 'claimed' ? '✓' : '⏳'} ${statusLabel}
												</span>
											</td>
											<td class="action-cell">
												${statusClass === 'pending' ? `
													<button class="btn-claim" data-bonus-id="${bonus.id}" data-bonus-type="${bonus.bonus_type}">
														${isMoney ? 'Claim' : 'Claim'}
													</button>
												` : (statusClass === 'submitted' && isPhysical ? `
													<button class="btn-view-code" data-bonus-id="${bonus.id}" data-secret-code="${bonus.secret_code}">
														View Code
													</button>
												` : `
													<span class="action-claimed">Claimed</span>
												`)}
											</td>
										</tr>
									`;
								}).join('')}
							</tbody>
						</table>
					</div>
				</div>
			`;

			$('#performance-content').html(html);

			// Bind claim button events
			$(document).on('click', '.btn-claim', (e) => {
				const $btn = $(e.currentTarget);
				const bonusId = $btn.data('bonus-id');
				const bonusType = $btn.data('bonus-type');
				
				if (bonusType === 'money') {
					this.claimMoneyBonus(bonusId);
				} else {
					this.claimPhysicalBonus(bonusId);
				}
			});

			// Bind view code button events
			$(document).on('click', '.btn-view-code', (e) => {
				const $btn = $(e.currentTarget);
				const secretCode = $btn.data('secret-code');
				this.showSecretCodePopup(secretCode, null, true);
			});
		},

		/**
		 * Claim money bonus
		 */
		claimMoneyBonus(bonusId) {
			if (!confirm('Are you sure you want to claim this bonus?')) {
				return;
			}

			$.ajax({
				url: wc_tp_reports.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_claim_bonus',
					nonce: wc_tp_reports.nonce,
					bonus_id: bonusId,
					bonus_type: 'money'
				},
				success: (response) => {
					if (response.success) {
						alert('Bonus claimed successfully!');
						this.loadBonusAchieved();
					} else {
						alert('Error: ' + (response.data?.message || 'Failed to claim bonus'));
					}
				},
				error: () => {
					alert('Network error. Please try again.');
				}
			});
		},

		/**
		 * Claim physical bonus
		 */
		claimPhysicalBonus(bonusId) {
			const secretCode = prompt('Enter the secret code to claim this bonus:');
			if (!secretCode) {
				return;
			}

			$.ajax({
				url: wc_tp_reports.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_claim_bonus',
					nonce: wc_tp_reports.nonce,
					bonus_id: bonusId,
					bonus_type: 'physical',
					secret_code: secretCode
				},
				success: (response) => {
					if (response.success) {
						alert('Bonus claimed successfully!');
						this.loadBonusAchieved();
					} else {
						alert('Error: ' + (response.data?.message || 'Invalid secret code'));
					}
				},
				error: () => {
					alert('Network error. Please try again.');
				}
			});
		},

		/**
		 * Submit money bonus (Admin)
		 */
		submitMoneyBonus(bonusId, userId) {
			if (!confirm('Are you sure you want to submit this bonus? It will be added to the employee\'s earnings.')) {
				return;
			}

			$.ajax({
				url: wc_tp_reports.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_submit_bonus',
					nonce: wc_tp_reports.nonce,
					bonus_id: bonusId,
					bonus_type: 'money',
					user_id: userId
				},
				success: (response) => {
					if (response.success) {
						alert('Bonus submitted successfully!');
						this.loadBonusAchieved();
					} else {
						alert('Error: ' + (response.data?.message || 'Failed to submit bonus'));
					}
				},
				error: () => {
					alert('Network error. Please try again.');
				}
			});
		},

		/**
		 * Submit physical bonus (Admin) - Show secret code
		 */
		submitPhysicalBonus(bonusId, userId) {
			$.ajax({
				url: wc_tp_reports.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_submit_bonus',
					nonce: wc_tp_reports.nonce,
					bonus_id: bonusId,
					bonus_type: 'physical',
					user_id: userId
				},
				success: (response) => {
					if (response.success) {
						const secretCode = response.data.secret_code;
						this.showSecretCodePopup(secretCode, userId);
						this.loadBonusAchieved();
					} else {
						alert('Error: ' + (response.data?.message || 'Failed to get secret code'));
					}
				},
				error: () => {
					alert('Network error. Please try again.');
				}
			});
		},

		/**
		 * Show secret code popup
		 */
		showSecretCodePopup(secretCode, userId, viewOnly = false) {
			const html = `
				<div class="secret-code-popup">
					<div class="secret-code-content">
						<h3>${viewOnly ? 'Physical Bonus Secret Code' : 'Physical Bonus Secret Code'}</h3>
						<p>${viewOnly ? 'Share this code with the employee:' : 'Share this code with the employee:'}</p>
						<div class="secret-code-display">
							<code>${secretCode}</code>
							<button class="btn-copy" data-code="${secretCode}">Copy</button>
						</div>
						${viewOnly ? `
							<p class="secret-code-note">This code was sent to the employee's email.</p>
							<button class="btn-resend" data-user-id="${userId}">Resend Email</button>
						` : `
							<p class="secret-code-note">This code has been sent to the employee's email.</p>
						`}
						<button class="btn-close">Close</button>
					</div>
				</div>
			`;

			const $popup = $(html);
			$('body').append($popup);

			$popup.find('.btn-copy').on('click', function() {
				const code = $(this).data('code');
				navigator.clipboard.writeText(code).then(() => {
					alert('Code copied to clipboard!');
				});
			});

			$popup.find('.btn-resend').on('click', (e) => {
				const userId = $(e.currentTarget).data('user-id');
				if (userId) {
					this.resendSecretCodeEmail(userId, secretCode);
					$popup.remove();
				}
			});

			$popup.find('.btn-close').on('click', function() {
				$popup.remove();
			});

			$popup.on('click', function(e) {
				if (e.target === this) {
					$popup.remove();
				}
			});
		},

		/**
		 * Resend secret code email (STEP 10)
		 */
		resendSecretCodeEmail(userId, secretCode) {
			$.ajax({
				url: wc_tp_reports.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_tp_resend_secret_code',
					nonce: wc_tp_reports.nonce,
					user_id: userId,
					secret_code: secretCode
				},
				success: (response) => {
					if (response.success) {
						alert('Secret code email resent successfully!');
					} else {
						alert('Error: ' + (response.data?.message || 'Failed to resend email'));
					}
				},
				error: () => {
					alert('Network error. Please try again.');
				}
			});
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
		 * Render leaderboard section
		 */
		renderLeaderboard(data) {
			const leaderboard = data.leaderboard || [];
			const userRank = data.user_rank;
			const config = data.config || {};
			const dateRange = data.date_range || {};
			const totalEmployees = data.total_employees || 0;

			// Get criteria label
			const criteriaLabels = {
				'total_earnings': 'Total Earnings',
				'total_orders': 'Total Orders',
				'average_order_value': 'Average Order Value',
				'commission_earnings': 'Commission Earnings',
				'total_order_value': 'Total Order Value',
				'achievement_score': 'Achievement Score',
				'goal_completion': 'Goal Completion Rate',
				'earnings_orders_50_50': 'Earnings + Orders (50/50)',
				'earnings_aov_60_40': 'Earnings + AOV (60/40)',
				'orders_aov_50_50': 'Orders + AOV (50/50)',
				'earnings_achievement_70_30': 'Earnings + Achievement (70/30)',
				'orders_achievement_60_40': 'Orders + Achievement (60/40)',
				'earnings_orders_aov_40_30_30': 'Earnings + Orders + AOV (40/30/30)',
				'earnings_orders_achievement_50_30_20': 'Earnings + Orders + Achievement (50/30/20)',
				'earnings_aov_achievement_50_25_25': 'Earnings + AOV + Achievement (50/25/25)',
				'orders_aov_achievement_40_30_30': 'Orders + AOV + Achievement (40/30/30)',
				'complete_score': 'Complete Score (All Metrics)'
			};

			const criteriaLabel = criteriaLabels[config.criteria] || config.criteria;

			const html = `
				<div class="performance-leaderboard">
					<div class="leaderboard-header">
						<h3><i class="ph ph-ranking"></i> Leaderboard</h3>
						<div class="leaderboard-meta">
							<span class="leaderboard-criteria">${criteriaLabel}</span>
							<span class="leaderboard-period">${dateRange.start_date || ''} - ${dateRange.end_date || ''}</span>
						</div>
					</div>

					${userRank ? this.renderUserRankCard(userRank, totalEmployees) : ''}

					<div class="leaderboard-list">
						<h4><i class="ph ph-trophy"></i> Top Performers</h4>
						${leaderboard.length > 0 ? leaderboard.map((entry, index) => 
							this.renderLeaderboardEntry(entry, index, config)
						).join('') : '<p class="no-data">No leaderboard data available</p>'}
					</div>

					<div class="leaderboard-footer">
						<p><i class="ph ph-info"></i> Showing top ${leaderboard.length} of ${totalEmployees} employees</p>
					</div>
				</div>
			`;

			$('#performance-content').html(html);
		},

		/**
		 * Render user rank card
		 */
		renderUserRankCard(userRank, totalEmployees) {
			// Check if user is filtered out (doesn't meet minimum requirements)
			if (userRank.filtered_out) {
				return `
					<div class="user-rank-card filtered-out">
						<div class="rank-badge-large">⚠️</div>
						<div class="rank-info">
							<h4>Not Ranked</h4>
							<p class="rank-position">${userRank.reason || 'Does not meet minimum requirements'}</p>
							<p class="rank-percentile">Complete more orders to appear on leaderboard</p>
						</div>
						<div class="rank-stats">
							${userRank.metrics && userRank.metrics.orders !== undefined ? `<div class="stat"><span>Your Orders:</span> <strong>${userRank.metrics.orders}</strong></div>` : ''}
							${userRank.metrics && userRank.metrics.earnings !== undefined ? `<div class="stat"><span>Your Earnings:</span> <strong>${this.formatValue(userRank.metrics.earnings, 'value')}</strong></div>` : ''}
						</div>
					</div>
				`;
			}
			
			// Normal ranked user
			const rankBadge = userRank.rank <= 3 ? 
				['🥇', '🥈', '🥉'][userRank.rank - 1] : 
				`#${userRank.rank}`;

			const percentile = ((totalEmployees - userRank.rank + 1) / totalEmployees * 100).toFixed(1);

			return `
				<div class="user-rank-card">
					<div class="rank-badge-large">${rankBadge}</div>
					<div class="rank-info">
						<h4>Your Rank</h4>
						<p class="rank-position">#${userRank.rank} of ${totalEmployees}</p>
						<p class="rank-percentile">Top ${percentile}%</p>
					</div>
					<div class="rank-stats">
						${userRank.score !== undefined ? `<div class="stat"><span>Score:</span> <strong>${userRank.score.toFixed(2)}</strong></div>` : ''}
						${userRank.metrics && userRank.metrics.earnings !== undefined ? `<div class="stat"><span>Earnings:</span> <strong>${this.formatValue(userRank.metrics.earnings, 'value')}</strong></div>` : ''}
						${userRank.metrics && userRank.metrics.orders !== undefined ? `<div class="stat"><span>Orders:</span> <strong>${userRank.metrics.orders}</strong></div>` : ''}
						${userRank.metrics && userRank.metrics.aov !== undefined ? `<div class="stat"><span>AOV:</span> <strong>${this.formatValue(userRank.metrics.aov, 'value')}</strong></div>` : ''}
					</div>
				</div>
			`;
		},

		/**
		 * Render leaderboard entry
		 */
		renderLeaderboardEntry(entry, index, config) {
			const rankBadge = entry.rank <= 3 ? 
				['🥇', '🥈', '🥉'][entry.rank - 1] : 
				`#${entry.rank}`;

			const showScores = config.show_scores !== 0;
			const showMetrics = config.show_metrics !== 0;

			return `
				<div class="leaderboard-entry ${entry.rank <= 3 ? 'top-three' : ''}">
					<div class="entry-rank">${rankBadge}</div>
					<div class="entry-info">
						<div class="entry-name">${entry.display_name}</div>
						${showScores && entry.score !== undefined ? `<div class="entry-score">Score: ${entry.score.toFixed(2)}</div>` : ''}
					</div>
					${showMetrics ? `
						<div class="entry-metrics">
							${entry.total_earnings !== undefined ? `<span><i class="ph ph-currency-dollar"></i> ${this.formatValue(entry.total_earnings, 'value')}</span>` : ''}
							${entry.total_orders !== undefined ? `<span><i class="ph ph-shopping-bag"></i> ${entry.total_orders}</span>` : ''}
							${entry.average_order_value !== undefined ? `<span><i class="ph ph-chart-bar"></i> ${this.formatValue(entry.average_order_value, 'value')}</span>` : ''}
						</div>
					` : ''}
				</div>
			`;
		},

		/**
		 * Show leaderboard disabled message
		 */
		showLeaderboardDisabled() {
			const html = `
				<div class="performance-leaderboard">
					<div class="leaderboard-disabled">
						<i class="ph ph-lock"></i>
						<h4>Leaderboard Disabled</h4>
						<p>The leaderboard feature is currently disabled. Please contact your administrator for more information.</p>
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
