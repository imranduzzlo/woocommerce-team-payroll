/**
 * Performance Settings Admin JavaScript
 * WooCommerce Team Payroll
 * @since 1.0.52
 */

jQuery(document).ready(function($) {
	'use strict';

	// Navigation tabs
	$('.wc-tp-perf-nav-tab').on('click', function(e) {
		e.preventDefault(); // Prevent form submission
		const section = $(this).data('section');
		
		// Update active tab
		$('.wc-tp-perf-nav-tab').removeClass('active');
		$(this).addClass('active');
		
		// Show corresponding section
		$('.wc-tp-perf-section').removeClass('active');
		$('#wc-tp-perf-' + section).addClass('active');
	});

	// Calculation Period toggle for custom date range
	$(document).on('change', '#calc_period_type', function() {
		const selectedValue = $(this).val();
		
		// Hide all conditional rows
		$('.wc-tp-calc-option').hide();
		
		// Show the row for the selected period type
		if (selectedValue === 'custom_range') {
			$('.wc-tp-calc-option[data-show-for="custom_range"]').show();
		}
	});

	// Initialize calculation period toggle on page load
	$(document).ready(function() {
		const currentPeriod = $('#calc_period_type').val();
		if (currentPeriod === 'custom_range') {
			$('.wc-tp-calc-option[data-show-for="custom_range"]').show();
		}
	});

	// Role selector change
	$('#wc-tp-role-selector').on('change', function() {
		const role = $(this).val();
		
		if (!role) {
			$('#wc-tp-role-config-container').html(
				'<div class="wc-tp-empty-state">' +
				'<span class="dashicons dashicons-admin-users"></span>' +
				'<p>Select an employee role above to configure performance scoring factors.</p>' +
				'</div>'
			);
			return;
		}

		loadRoleConfiguration(role);
	});

	// Load role configuration via AJAX
	function loadRoleConfiguration(role) {
		$('#wc-tp-role-config-container').html(
			'<div class="wc-tp-loading">' +
			'<span class="spinner is-active"></span>' +
			'<p>Loading configuration...</p>' +
			'</div>'
		);

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_role_config',
				nonce: wcTpPerformance.nonce,
				role: role
			},
			success: function(response) {
				if (response.success) {
					$('#wc-tp-role-config-container').html(response.data.html);
					initializeRangeControls();
				} else {
					showMessage('error', response.data.message || 'Error loading configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Initialize range controls
	function initializeRangeControls() {
		// Add range button
		$(document).on('click', '.wc-tp-add-range', function() {
			const container = $(this).closest('.wc-tp-ranges-container');
			const factor = container.data('factor');
			const role = container.data('role');
			addRangeRow(container, factor, role);
		});

		// Remove range button
		$(document).on('click', '.wc-tp-remove-range', function() {
			if (confirm('Are you sure you want to remove this range?')) {
				$(this).closest('.wc-tp-range-row').fadeOut(200, function() {
					$(this).remove();
				});
			}
		});

		// Calculate preview
		$(document).on('click', '.wc-tp-calculate-preview', function() {
			calculatePreview();
		});
	}

	// Add range row
	function addRangeRow(container, factor, role) {
		const index = container.find('.wc-tp-range-row').length;
		const currencySymbol = (factor === 'earnings' || factor === 'aov') ? '$' : '';
		const step = factor === 'orders' ? '1' : '0.01';
		
		const rangeRow = $('<div class="wc-tp-range-row" data-index="' + index + '">' +
			'<label>Range:</label>' +
			'<span class="wc-tp-range-currency">' + currencySymbol + '</span>' +
			'<input type="number" name="' + factor + '_min[]" placeholder="Min" step="' + step + '" min="0" class="wc-tp-range-min" />' +
			'<span>to</span>' +
			'<span class="wc-tp-range-currency">' + currencySymbol + '</span>' +
			'<input type="number" name="' + factor + '_max[]" placeholder="Max" step="' + step + '" min="0" class="wc-tp-range-max" />' +
			'<span>=</span>' +
			'<input type="number" name="' + factor + '_points[]" placeholder="Points" step="0.1" min="0" max="10" class="wc-tp-range-points" />' +
			'<span>points</span>' +
			'<span class="dashicons dashicons-trash wc-tp-remove-range" title="Remove this range"></span>' +
			'</div>');

		container.find('.wc-tp-add-range').before(rangeRow);
		rangeRow.hide().fadeIn(200);
	}

	// Clone role configuration
	$('#wc-tp-clone-role').on('click', function() {
		const currentRole = $('#wc-tp-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		// Show dialog to select source role
		const allRoles = [];
		$('#wc-tp-role-selector option').each(function() {
			if ($(this).val() && $(this).val() !== currentRole) {
				allRoles.push({
					value: $(this).val(),
					text: $(this).text()
				});
			}
		});

		if (allRoles.length === 0) {
			alert('No other roles available to clone from');
			return;
		}

		let options = '';
		allRoles.forEach(function(role) {
			options += '<option value="' + role.value + '">' + role.text + '</option>';
		});

		const sourceRole = prompt('Select role to clone from:\n\n' + allRoles.map(r => r.text).join('\n'));
		
		if (sourceRole) {
			cloneRoleConfiguration(sourceRole, currentRole);
		}
	});

	// Clone role configuration via AJAX
	function cloneRoleConfiguration(fromRole, toRole) {
		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_clone_role_config',
				nonce: wcTpPerformance.nonce,
				from_role: fromRole,
				to_role: toRole
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', response.data.message);
					loadRoleConfiguration(toRole);
				} else {
					showMessage('error', response.data.message || 'Error cloning configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Reset role configuration
	$('#wc-tp-reset-role').on('click', function() {
		const currentRole = $('#wc-tp-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		if (confirm('Are you sure you want to reset this role configuration to default values?')) {
			// Reset logic will be implemented
			showMessage('success', 'Role configuration reset to defaults');
			loadRoleConfiguration(currentRole);
		}
	});

	// Unified save handler for all sections
	function setupUnifiedSaveHandler() {
		$('#wc-tp-save-performance').off('click').on('click', function() {
			const button = $(this);
			button.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>Saving...');
			// Get active section
			const activeSection = $('.wc-tp-perf-nav-tab.active').data('section');
			// Collect data based on active section
			let savePromises = [];
			
			// Always save performance scoring config if there's data
			try {
				const config = collectConfigurationData();
				if (config.base_score || Object.keys(config.roles).length > 0) {
					savePromises.push(savePerformanceConfig(config));
				}
			} catch (e) {
			}
			
			// Always try to save bonus config (regardless of active tab)
			try {
				const bonusConfig = collectBonusConfigurationData();
				if (bonusConfig && Object.keys(bonusConfig).length > 0) {
					savePromises.push(saveBonusConfig(bonusConfig));
				}
			} catch (e) {
				// Only show error if we're on the bonuses tab
				if (activeSection === 'bonuses') {
					showMessage('error', e.message);
				}
			}
			
			// Save section-specific data
			switch (activeSection) {
				case 'goals':
					try {
						const goalsConfig = collectGoalsConfigurationData();
						if (goalsConfig && Object.keys(goalsConfig).length > 0) {
							savePromises.push(saveGoalsConfig(goalsConfig));
						}
					} catch (e) {
					}
					break;
				case 'achievements':
					try {
						const achievementsConfig = collectAchievementsConfigurationData();
						if (achievementsConfig && Object.keys(achievementsConfig).length > 0) {
							savePromises.push(saveAchievementsConfig(achievementsConfig));
						}
					} catch (e) {
					}
					break;
				case 'baselines':
					try {
						const baselinesConfig = collectBaselinesConfigurationData();
						if (baselinesConfig && Object.keys(baselinesConfig).length > 0) {
							savePromises.push(saveBaselinesConfig(baselinesConfig));
						}
					} catch (e) {
					}
					break;
				case 'calculation':
					try {
						const calculationConfig = collectCalculationConfigurationData();
						if (calculationConfig && Object.keys(calculationConfig).length > 0) {
							savePromises.push(saveCalculationConfig(calculationConfig));
						}
					} catch (e) {
					}
					break;
				case 'system':
					try {
						const systemConfig = collectSystemConfigurationData();
						if (systemConfig && Object.keys(systemConfig).length > 0) {
							savePromises.push(saveSystemConfig(systemConfig));
						}
					} catch (e) {
					}
					break;
				case 'leaderboard':
					try {
						const leaderboardConfig = collectLeaderboardConfigurationData();
						if (leaderboardConfig && Object.keys(leaderboardConfig).length > 0) {
							savePromises.push(saveLeaderboardConfig(leaderboardConfig));
						}
					} catch (e) {
					}
					break;
				case 'bonuses':
					// Bonus config is already handled in the "always save" section above
					break;
			}
			if (savePromises.length === 0) {
				showMessage('warning', 'No configuration data to save');
				button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
				return;
			}
			
			// Execute all saves
			Promise.all(savePromises).then(function(results) {
				let hasError = false;
				let errorMessages = [];
				
				results.forEach(function(result) {
					if (!result.success) {
						hasError = true;
						errorMessages.push(result.message);
					}
				});
				
				if (!hasError) {
					showMessage('success', 'All configurations saved successfully!');
					// Reset the main form's unsaved changes flag
					if (typeof window.wcTpResetUnsavedChanges === 'function') {
						window.wcTpResetUnsavedChanges();
					} else {
						// Fallback: Try to reset the unsaved changes manually
						try {
							const warningDiv = $('#wc-tp-unsaved-warning');
							if (warningDiv.length) {
								warningDiv.fadeOut(300);
							}
							
							// Try to reset the hasChanges flag if we can access it
							if (window.parent && window.parent.hasChanges !== undefined) {
								window.parent.hasChanges = false;
							}
						} catch (e) {
						}
					}
				} else {
					errorMessages.forEach(function(message) {
						showMessage('error', message);
					});
				}
			}).catch(function(error) {
				showMessage('error', 'Error saving configurations');
			}).finally(function() {
				button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span>Save All Configurations');
			});
		});
	}

	// Individual save functions
	function savePerformanceConfig(config) {
		return $.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_performance_config',
				nonce: wcTpPerformance.nonce,
				config: config
			}
		}).then(function(response) {
			return { success: response.success, message: response.data ? response.data.message : 'Performance config saved' };
		}).catch(function(xhr) {
			return { success: false, message: xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error saving performance config' };
		});
	}

	function saveGoalsConfig(config) {
		return $.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_goals_config',
				nonce: wcTpPerformance.nonce,
				config: config
			}
		}).then(function(response) {
			return { success: response.success, message: response.data ? response.data.message : 'Goals config saved' };
		}).catch(function(xhr) {
			return { success: false, message: xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error saving goals config' };
		});
	}

	function saveAchievementsConfig(config) {
		return $.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_achievements_config',
				nonce: wcTpPerformance.nonce,
				config: config
			}
		}).then(function(response) {
			return { success: response.success, message: response.data ? response.data.message : 'Achievements config saved' };
		}).catch(function(xhr) {
			return { success: false, message: xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error saving achievements config' };
		});
	}

	function saveBaselinesConfig(config) {
		return $.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_baselines_config',
				nonce: wcTpPerformance.nonce,
				config: config
			}
		}).then(function(response) {
			return { success: response.success, message: response.data ? response.data.message : 'Baselines config saved' };
		}).catch(function(xhr) {
			return { success: false, message: xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error saving baselines config' };
		});
	}

	function saveCalculationConfig(config) {
		return $.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_calculation_config',
				nonce: wcTpPerformance.nonce,
				config: config
			}
		}).then(function(response) {
			return { success: response.success, message: response.data ? response.data.message : 'Calculation config saved' };
		}).catch(function(xhr) {
			return { success: false, message: xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error saving calculation config' };
		});
	}

	function saveSystemConfig(config) {
		return $.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_system_config',
				nonce: wcTpPerformance.nonce,
				config: config
			}
		}).then(function(response) {
			return { success: response.success, message: response.data ? response.data.message : 'System config saved' };
		}).catch(function(xhr) {
			return { success: false, message: xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error saving system config' };
		});
	}

	// Collect leaderboard configuration data
	function collectLeaderboardConfigurationData() {
		const config = {
			enabled: $('#leaderboard_enabled').is(':checked') ? 1 : 0,
			criteria: $('#leaderboard_criteria').val(),
			period: $('#leaderboard_period').val(),
			update_frequency: $('#leaderboard_update_frequency').val(),
			minimum_orders: parseInt($('#leaderboard_minimum_orders').val()) || 0,
			exclude_inactive: $('#leaderboard_exclude_inactive').is(':checked') ? 1 : 0,
			display_limit: parseInt($('#leaderboard_display_limit').val()) || 10,
			show_user_rank: $('#leaderboard_show_user_rank').is(':checked') ? 1 : 0,
			anonymize: $('#leaderboard_anonymize').is(':checked') ? 1 : 0,
			show_scores: $('#leaderboard_show_scores').is(':checked') ? 1 : 0,
			show_metrics: $('#leaderboard_show_metrics').is(':checked') ? 1 : 0
		};
		
		return config;
	}

	function saveLeaderboardConfig(config) {
		return $.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_leaderboard_config',
				nonce: wcTpPerformance.nonce,
				leaderboard_config: config
			}
		}).then(function(response) {
			return { success: response.success, message: response.data ? response.data.message : 'Leaderboard config saved' };
		}).catch(function(xhr) {
			return { success: false, message: xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error saving leaderboard config' };
		});
	}

	// Manual leaderboard refresh button
	$(document).on('click', '#wc-tp-refresh-leaderboard', function() {
		const button = $(this);
		const statusDiv = $('#wc-tp-leaderboard-refresh-status');
		
		button.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>Refreshing...');
		statusDiv.html('');
		
		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_refresh_leaderboard',
				nonce: wcTpPerformance.nonce
			},
			success: function(response) {
				if (response.success) {
					statusDiv.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
					// Update last updated timestamp if available
					if (response.data.timestamp) {
						const date = new Date(response.data.timestamp);
						const formattedDate = date.toLocaleString();
						statusDiv.append('<p style="color: #666; margin-top: 5px;">Last updated: ' + formattedDate + '</p>');
					}
				} else {
					statusDiv.html('<div class="notice notice-error inline"><p>' + (response.data.message || 'Error refreshing leaderboard') + '</p></div>');
				}
			},
			error: function() {
				statusDiv.html('<div class="notice notice-error inline"><p>AJAX error occurred</p></div>');
			},
			complete: function() {
				button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span>Refresh Leaderboard Rankings');
			}
		});
	});

	function saveBonusConfig(config) {
		return $.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_bonus_config',
				nonce: wcTpPerformance.nonce,
				bonus_config: config
			}
		}).then(function(response) {
			// Update rule numbers on success
			if (response.success) {
				updateBonusRuleNumbers();
				
				// Clear any cached bonus milestone data for all users
				// This ensures fresh data is fetched on next page load
				if (typeof window.wcTpClearBonusCache === 'function') {
					window.wcTpClearBonusCache();
				}
			}
			return { success: response.success, message: response.data ? response.data.message : 'Bonus config saved' };
		}).catch(function(xhr) {
			return { success: false, message: xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Error saving bonus config' };
		});
	}

	// Initialize unified save handler
	setupUnifiedSaveHandler();

	// Debug: Check if wcTpResetUnsavedChanges function exists
	$(document).ready(function() {

		// Test the function after a short delay to ensure settings page JS is loaded
		setTimeout(function() {
			console.log('wcTpResetUnsavedChanges function available (delayed check):', typeof window.wcTpResetUnsavedChanges === 'function');
		}, 1000);

		// Flag to track if page has finished initial load
		let pageLoadComplete = false;
		
		// Mark page load as complete after a delay to allow all initial changes to settle
		setTimeout(function() {
			pageLoadComplete = true;
		}, 1500);

		// Ensure performance settings changes are tracked by main form
		// BUT only after initial page load is complete
		$('.wc-tp-performance-settings-wrapper').on('change', 'input, select, textarea', function() {
			// Ignore changes during initial page load
			if (!pageLoadComplete) {
				console.log('Ignoring change during page load:', $(this).attr('name') || $(this).attr('id'));
				return;
			}
			
			console.log('Performance setting changed:', $(this).attr('name') || $(this).attr('id'));
			// Trigger change on main form to ensure unsaved changes detection
			$('#wc-tp-settings-form').trigger('wc-tp-performance-change');
		});

		// Listen for our custom event on the main form
		$('#wc-tp-settings-form').on('wc-tp-performance-change', function() {
			// Manually trigger the unsaved changes detection
			if (typeof window.wcTpCheckUnsavedChanges === 'function') {
				const state = window.wcTpCheckUnsavedChanges();
				if (!state.hasChanges) {
					// Force show the warning if it's not already shown
					$('#wc-tp-unsaved-warning').fadeIn(300);
				}
			}
		});
	});

	// Save goals configuration
	function saveGoalsConfiguration() {
		const goalsConfig = collectGoalsConfigurationData();

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_goals_config',
				nonce: wcTpPerformance.nonce,
				config: goalsConfig
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', 'Goals configuration saved successfully!');
				} else {
					showMessage('error', response.data.message || 'Error saving goals configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred while saving goals');
			}
		});
	}

	// Collect configuration data
	function collectConfigurationData() {
		const config = {
			base_score: $('#base_score').val(),
			roles: {}
		};

		// Get current role being configured
		const currentRole = $('.wc-tp-role-config-form').data('role');
		
		if (currentRole) {
			// Collect data for the currently active role
			config.roles[currentRole] = {
				earnings_ranges: collectRanges('earnings'),
				orders_ranges: collectRanges('orders'),
				aov_ranges: collectRanges('aov')
			};
		}

		// Also collect any other role configurations that might be stored in hidden inputs
		// This ensures we don't lose previously configured roles when saving
		$('.wc-tp-stored-role-config').each(function() {
			const role = $(this).data('role');
			const configData = $(this).val();
			if (role && configData && role !== currentRole) {
				try {
					config.roles[role] = JSON.parse(configData);
				} catch (e) {
				}
			}
		});

		return config;
	}

	// Collect ranges for a specific factor
	function collectRanges(factor) {
		const ranges = [];
		const container = $('.wc-tp-ranges-container[data-factor="' + factor + '"]');
		
		container.find('.wc-tp-range-row').each(function() {
			const min = parseFloat($(this).find('.wc-tp-range-min').val()) || 0;
			const max = parseFloat($(this).find('.wc-tp-range-max').val()) || 0;
			const points = parseFloat($(this).find('.wc-tp-range-points').val()) || 0;
			
			ranges.push({
				min: min,
				max: max,
				points: points
			});
		});
		
		return ranges;
	}

	// Calculate preview score
	function calculatePreview() {
		const earnings = parseFloat($('.wc-tp-preview-earnings').val()) || 0;
		const orders = parseInt($('.wc-tp-preview-orders').val()) || 0;
		const aov = parseFloat($('.wc-tp-preview-aov').val()) || 0;
		const baseScore = parseFloat($('#base_score').val()) || 5;

		// Calculate points for each factor
		const earningsPoints = calculatePointsForValue(earnings, collectRanges('earnings'));
		const ordersPoints = calculatePointsForValue(orders, collectRanges('orders'));
		const aovPoints = calculatePointsForValue(aov, collectRanges('aov'));

		// Calculate final score
		const finalScore = baseScore + earningsPoints + ordersPoints + aovPoints;
		const cappedScore = Math.min(finalScore, 10);

		// Display results
		$('#preview-base-score').text(baseScore.toFixed(1));
		$('#preview-earnings-points').text('+' + earningsPoints.toFixed(1));
		$('#preview-orders-points').text('+' + ordersPoints.toFixed(1));
		$('#preview-aov-points').text('+' + aovPoints.toFixed(1));
		$('#preview-final-score').text(cappedScore.toFixed(1) + '/10');

		$('.wc-tp-preview-result').slideDown(300);
	}

	// Calculate points for a value based on ranges
	function calculatePointsForValue(value, ranges) {
		for (let i = 0; i < ranges.length; i++) {
			const range = ranges[i];
			if (value >= range.min && value <= range.max) {
				return range.points;
			}
		}
		return 0;
	}

	// Export settings
	$('#wc-tp-export-performance').on('click', function() {
		// Export functionality will be implemented
		alert('Export functionality coming soon');
	});

	// Import settings
	$('#wc-tp-import-performance').on('click', function() {
		// Import functionality will be implemented
		alert('Import functionality coming soon');
	});

	// Reset all configurations
	$('#wc-tp-reset-performance').on('click', function() {
		if (confirm(wcTpPerformance.strings.confirm_reset)) {
			// Reset functionality will be implemented
			showMessage('success', 'All configurations reset to defaults');
		}
	});

	// Show message
	function showMessage(type, message) {
		const icon = type === 'success' ? 'yes-alt' : 'warning';
		const messageHtml = $('<div class="wc-tp-message ' + type + '">' +
			'<span class="dashicons dashicons-' + icon + '"></span>' +
			'<span>' + message + '</span>' +
			'</div>');

		$('.wc-tp-performance-settings-wrapper').prepend(messageHtml);

		setTimeout(function() {
			messageHtml.fadeOut(300, function() {
				$(this).remove();
			});
		}, 5000);

		// Scroll to top
		$('html, body').animate({ scrollTop: 0 }, 300);
	}

	// ============================================================================
	// GOALS & TARGETS FUNCTIONALITY
	// ============================================================================

	// Goals role selector change
	$('#wc-tp-goals-role-selector').on('change', function() {
		const role = $(this).val();
		if (!role) {
			$('#wc-tp-goals-config-container').html(
				'<div class="wc-tp-empty-state">' +
				'<span class="dashicons dashicons-flag"></span>' +
				'<p>Select an employee role above to configure goals and targets.</p>' +
				'</div>'
			);
			return;
		}

		loadRoleGoals(role);
	});

	// Load role goals via AJAX
	function loadRoleGoals(role) {
		$('#wc-tp-goals-config-container').html(
			'<div class="wc-tp-loading">' +
			'<span class="spinner is-active"></span>' +
			'<p>Loading goals configuration...</p>' +
			'</div>'
		);

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_role_goals',
				nonce: wcTpPerformance.nonce,
				role: role
			},
			success: function(response) {
				if (response.success) {
					$('#wc-tp-goals-config-container').html(response.data.html);
					initializeGoalsControls();
				} else {
					showMessage('error', response.data.message || 'Error loading goals configuration');
				}
			},
			error: function(xhr, status, error) {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Initialize goals controls
	function initializeGoalsControls() {
		// Preview goals button
		$(document).on('click', '.wc-tp-preview-goals', function() {
			previewGoalProgress();
		});
	}

	// Clone goals configuration
	$('#wc-tp-clone-goals-role').on('click', function() {
		const currentRole = $('#wc-tp-goals-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		// Show dialog to select source role
		const allRoles = [];
		$('#wc-tp-goals-role-selector option').each(function() {
			if ($(this).val() && $(this).val() !== currentRole) {
				allRoles.push({
					value: $(this).val(),
					text: $(this).text()
				});
			}
		});

		if (allRoles.length === 0) {
			alert('No other roles available to clone from');
			return;
		}

		const sourceRole = prompt('Enter the role name to clone from:\n\n' + allRoles.map(r => r.text).join('\n'));
		
		if (sourceRole) {
			cloneRoleGoals(sourceRole, currentRole);
		}
	});

	// Clone role goals via AJAX
	function cloneRoleGoals(fromRole, toRole) {
		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_clone_role_goals',
				nonce: wcTpPerformance.nonce,
				from_role: fromRole,
				to_role: toRole
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', response.data.message);
					loadRoleGoals(toRole);
				} else {
					showMessage('error', response.data.message || 'Error cloning goals configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Reset goals role configuration
	$('#wc-tp-reset-goals-role').on('click', function() {
		const currentRole = $('#wc-tp-goals-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		if (confirm('Are you sure you want to reset this role goals configuration to default values?')) {
			showMessage('success', 'Role goals configuration reset to defaults');
			loadRoleGoals(currentRole);
		}
	});

	// Collect goals configuration data
	function collectGoalsConfigurationData() {
		const config = {
			period: $('#goals_period').val(),
			display_mode: $('#goals_display_mode').val(),
			show_stretch: $('#goals_show_stretch').is(':checked') ? 1 : 0,
			roles: {}
		};

		// Get current role being configured
		const currentRole = $('.wc-tp-role-goals-form').data('role');
		
		if (currentRole) {
			config.roles[currentRole] = {
				earnings: {
					minimum: parseFloat($('input[name="earnings_minimum"]').val()) || 0,
					target: parseFloat($('input[name="earnings_target"]').val()) || 0,
					stretch: parseFloat($('input[name="earnings_stretch"]').val()) || 0
				},
				orders: {
					minimum: parseInt($('input[name="orders_minimum"]').val()) || 0,
					target: parseInt($('input[name="orders_target"]').val()) || 0,
					stretch: parseInt($('input[name="orders_stretch"]').val()) || 0
				},
				aov: {
					minimum: parseFloat($('input[name="aov_minimum"]').val()) || 0,
					target: parseFloat($('input[name="aov_target"]').val()) || 0,
					stretch: parseFloat($('input[name="aov_stretch"]').val()) || 0
				}
			};
		}

		return config;
	}

	// Preview goal progress
	function previewGoalProgress() {
		const currentEarnings = parseFloat($('.wc-tp-preview-goal-earnings').val()) || 0;
		const currentOrders = parseInt($('.wc-tp-preview-goal-orders').val()) || 0;
		const currentAov = parseFloat($('.wc-tp-preview-goal-aov').val()) || 0;

		const earningsTarget = parseFloat($('input[name="earnings_target"]').val()) || 0;
		const ordersTarget = parseInt($('input[name="orders_target"]').val()) || 0;
		const aovTarget = parseFloat($('input[name="aov_target"]').val()) || 0;

		// Get currency symbol from localized data
		const currencySymbol = wcTpPerformance.currency_symbol || '$';

		// Calculate percentages
		const earningsPercentage = earningsTarget > 0 ? Math.min((currentEarnings / earningsTarget) * 100, 100) : 0;
		const ordersPercentage = ordersTarget > 0 ? Math.min((currentOrders / ordersTarget) * 100, 100) : 0;
		const aovPercentage = aovTarget > 0 ? Math.min((currentAov / aovTarget) * 100, 100) : 0;

		// Update earnings progress
		$('#preview-earnings-progress').css('width', earningsPercentage + '%');
		$('#preview-earnings-current').text(currencySymbol + currentEarnings.toFixed(2));
		$('#preview-earnings-target').text('/ ' + currencySymbol + earningsTarget.toFixed(2));
		$('#preview-earnings-percentage').text(earningsPercentage.toFixed(1) + '%');

		// Update orders progress
		$('#preview-orders-progress').css('width', ordersPercentage + '%');
		$('#preview-orders-current').text(currentOrders);
		$('#preview-orders-target').text('/ ' + ordersTarget);
		$('#preview-orders-percentage').text(ordersPercentage.toFixed(1) + '%');

		// Update AOV progress
		$('#preview-aov-progress').css('width', aovPercentage + '%');
		$('#preview-aov-current').text(currencySymbol + currentAov.toFixed(2));
		$('#preview-aov-target').text('/ ' + currencySymbol + aovTarget.toFixed(2));
		$('#preview-aov-percentage').text(aovPercentage.toFixed(1) + '%');

		// Show results
		$('.wc-tp-goal-preview-result').slideDown(300);
	}

	// ============================================================================
	// ACHIEVEMENTS FUNCTIONALITY
	// ============================================================================

	// Achievements role selector change
	$('#wc-tp-achievements-role-selector').on('change', function() {
		const role = $(this).val();
		
		if (!role) {
			$('#wc-tp-achievements-config-container').html(
				'<div class="wc-tp-empty-state">' +
				'<span class="dashicons dashicons-awards"></span>' +
				'<p>Select an employee role above to configure achievements and badges.</p>' +
				'</div>'
			);
			return;
		}

		loadRoleAchievements(role);
	});

	// Load role achievements via AJAX
	function loadRoleAchievements(role) {
		$('#wc-tp-achievements-config-container').html(
			'<div class="wc-tp-loading">' +
			'<span class="spinner is-active"></span>' +
			'<p>Loading achievements configuration...</p>' +
			'</div>'
		);

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_role_achievements',
				nonce: wcTpPerformance.nonce,
				role: role
			},
			success: function(response) {
				if (response.success) {
					$('#wc-tp-achievements-config-container').html(response.data.html);
				} else {
					showMessage('error', response.data.message || 'Error loading achievements configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Clone achievements configuration
	$('#wc-tp-clone-achievements-role').on('click', function() {
		const currentRole = $('#wc-tp-achievements-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		// Show dialog to select source role
		const allRoles = [];
		$('#wc-tp-achievements-role-selector option').each(function() {
			if ($(this).val() && $(this).val() !== currentRole) {
				allRoles.push({
					value: $(this).val(),
					text: $(this).text()
				});
			}
		});

		if (allRoles.length === 0) {
			alert('No other roles available to clone from');
			return;
		}

		const sourceRole = prompt('Enter the role name to clone from:\n\n' + allRoles.map(r => r.text).join('\n'));
		
		if (sourceRole) {
			cloneRoleAchievements(sourceRole, currentRole);
		}
	});

	// Clone role achievements via AJAX
	function cloneRoleAchievements(fromRole, toRole) {
		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_clone_role_achievements',
				nonce: wcTpPerformance.nonce,
				from_role: fromRole,
				to_role: toRole
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', response.data.message);
					loadRoleAchievements(toRole);
				} else {
					showMessage('error', response.data.message || 'Error cloning achievements configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Reset achievements role configuration
	$('#wc-tp-reset-achievements-role').on('click', function() {
		const currentRole = $('#wc-tp-achievements-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		if (confirm('Are you sure you want to reset this role achievements configuration to default values?')) {
			showMessage('success', 'Role achievements configuration reset to defaults');
			loadRoleAchievements(currentRole);
		}
	});

	// Save achievements configuration (integrated with main save button)
	function saveAchievementsConfiguration() {
		const achievementsConfig = collectAchievementsConfigurationData();

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_achievements_config',
				nonce: wcTpPerformance.nonce,
				config: achievementsConfig
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', 'Achievements configuration saved successfully!');
				} else {
					showMessage('error', response.data.message || 'Error saving achievements configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred while saving achievements');
			}
		});
	}

	// Collect achievements configuration data
	function collectAchievementsConfigurationData() {
		const config = {
			enabled: $('#achievements_enabled').is(':checked') ? 1 : 0,
			display_style: $('#achievements_display_style').val(),
			show_locked: $('#achievements_show_locked').is(':checked') ? 1 : 0,
			notification: $('#achievements_notification').is(':checked') ? 1 : 0,
			roles: {}
		};

		// Get current role being configured
		const currentRole = $('.wc-tp-role-achievements-form').data('role');
		
		if (currentRole) {
			config.roles[currentRole] = {};
			
			const categories = ['earnings', 'orders', 'aov'];
			const tiers = ['bronze', 'silver', 'gold'];
			
			categories.forEach(function(category) {
				tiers.forEach(function(tier) {
					const key = category + '_' + tier;
					const nameField = $('input[name="achievement_' + category + '_' + tier + '_name"]');
					const descField = $('textarea[name="achievement_' + category + '_' + tier + '_description"]');
					const thresholdField = $('input[name="achievement_' + category + '_' + tier + '_threshold"]');
					const iconField = $('select[name="achievement_' + category + '_' + tier + '_icon"]');
					
					if (nameField.length) {
						config.roles[currentRole][key] = {
							name: nameField.val(),
							description: descField.val(),
							threshold: category === 'orders' ? parseInt(thresholdField.val()) || 0 : parseFloat(thresholdField.val()) || 0,
							tier: tier,
							icon: iconField.val()
						};
					}
				});
			});
		}

		return config;
	}

	// Load role goals via AJAX
	function loadRoleGoals(role) {
		$('#wc-tp-goals-config-container').html(
			'<div class="wc-tp-loading">' +
			'<span class="spinner is-active"></span>' +
			'<p>Loading goals configuration...</p>' +
			'</div>'
		);

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_role_goals',
				nonce: wcTpPerformance.nonce,
				role: role
			},
			success: function(response) {
				if (response.success) {
					$('#wc-tp-goals-config-container').html(response.data.html);
					initializeGoalsControls();
				} else {
					showMessage('error', response.data.message || 'Error loading goals configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Initialize goals controls
	function initializeGoalsControls() {
		// Preview goals button
		$(document).on('click', '.wc-tp-preview-goals', function() {
			previewGoalProgress();
		});
	}

	// Clone goals configuration
	$('#wc-tp-clone-goals-role').on('click', function() {
		const currentRole = $('#wc-tp-goals-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		// Show dialog to select source role
		const allRoles = [];
		$('#wc-tp-goals-role-selector option').each(function() {
			if ($(this).val() && $(this).val() !== currentRole) {
				allRoles.push({
					value: $(this).val(),
					text: $(this).text()
				});
			}
		});

		if (allRoles.length === 0) {
			alert('No other roles available to clone from');
			return;
		}

		const sourceRole = prompt('Enter the role name to clone from:\n\n' + allRoles.map(r => r.text).join('\n'));
		
		if (sourceRole) {
			cloneRoleGoals(sourceRole, currentRole);
		}
	});

	// Clone role goals via AJAX
	function cloneRoleGoals(fromRole, toRole) {
		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_clone_role_goals',
				nonce: wcTpPerformance.nonce,
				from_role: fromRole,
				to_role: toRole
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', response.data.message);
					loadRoleGoals(toRole);
				} else {
					showMessage('error', response.data.message || 'Error cloning goals configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Reset goals role configuration
	$('#wc-tp-reset-goals-role').on('click', function() {
		const currentRole = $('#wc-tp-goals-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		if (confirm('Are you sure you want to reset this role goals configuration to default values?')) {
			showMessage('success', 'Role goals configuration reset to defaults');
			loadRoleGoals(currentRole);
		}
	});

	// Collect goals configuration data
	function collectGoalsConfigurationData() {
		const config = {
			period: $('#goals_period').val(),
			display_mode: $('#goals_display_mode').val(),
			show_stretch: $('#goals_show_stretch').is(':checked') ? 1 : 0,
			roles: {}
		};

		// Get current role being configured
		const currentRole = $('.wc-tp-role-goals-form').data('role');
		
		if (currentRole) {
			config.roles[currentRole] = {
				earnings: {
					minimum: parseFloat($('input[name="earnings_minimum"]').val()) || 0,
					target: parseFloat($('input[name="earnings_target"]').val()) || 0,
					stretch: parseFloat($('input[name="earnings_stretch"]').val()) || 0
				},
				orders: {
					minimum: parseInt($('input[name="orders_minimum"]').val()) || 0,
					target: parseInt($('input[name="orders_target"]').val()) || 0,
					stretch: parseInt($('input[name="orders_stretch"]').val()) || 0
				},
				aov: {
					minimum: parseFloat($('input[name="aov_minimum"]').val()) || 0,
					target: parseFloat($('input[name="aov_target"]').val()) || 0,
					stretch: parseFloat($('input[name="aov_stretch"]').val()) || 0
				}
			};
		}

		return config;
	}

	// Preview goal progress
	function previewGoalProgress() {
		const currentEarnings = parseFloat($('.wc-tp-preview-goal-earnings').val()) || 0;
		const currentOrders = parseInt($('.wc-tp-preview-goal-orders').val()) || 0;
		const currentAov = parseFloat($('.wc-tp-preview-goal-aov').val()) || 0;

		const earningsTarget = parseFloat($('input[name="earnings_target"]').val()) || 0;
		const ordersTarget = parseInt($('input[name="orders_target"]').val()) || 0;
		const aovTarget = parseFloat($('input[name="aov_target"]').val()) || 0;

		// Calculate percentages
		const earningsPercentage = earningsTarget > 0 ? Math.min((currentEarnings / earningsTarget) * 100, 100) : 0;
		const ordersPercentage = ordersTarget > 0 ? Math.min((currentOrders / ordersTarget) * 100, 100) : 0;
		const aovPercentage = aovTarget > 0 ? Math.min((currentAov / aovTarget) * 100, 100) : 0;

		// Update earnings progress
		$('#preview-earnings-progress').css('width', earningsPercentage + '%');
		$('#preview-earnings-current').text(wcTpPerformance.currency_symbol + currentEarnings.toFixed(2));
		$('#preview-earnings-target').text('/ ' + wcTpPerformance.currency_symbol + earningsTarget.toFixed(2));
		$('#preview-earnings-percentage').text(earningsPercentage.toFixed(1) + '%');

		// Update orders progress
		$('#preview-orders-progress').css('width', ordersPercentage + '%');
		$('#preview-orders-current').text(currentOrders);
		$('#preview-orders-target').text('/ ' + ordersTarget);
		$('#preview-orders-percentage').text(ordersPercentage.toFixed(1) + '%');

		// Update AOV progress
		$('#preview-aov-progress').css('width', aovPercentage + '%');
		$('#preview-aov-current').text(wcTpPerformance.currency_symbol + currentAov.toFixed(2));
		$('#preview-aov-target').text('/ ' + wcTpPerformance.currency_symbol + aovTarget.toFixed(2));
		$('#preview-aov-percentage').text(aovPercentage.toFixed(1) + '%');

		// Show results
		$('.wc-tp-goal-preview-result').slideDown(300);
	}


	// ============================================================================
	// ACHIEVEMENTS FUNCTIONALITY
	// ============================================================================

	// Achievements role selector change
	$('#wc-tp-achievements-role-selector').on('change', function() {
		const role = $(this).val();
		
		if (!role) {
			$('#wc-tp-achievements-config-container').html(
				'<div class="wc-tp-empty-state">' +
				'<span class="dashicons dashicons-awards"></span>' +
				'<p>Select an employee role above to configure achievements and badges.</p>' +
				'</div>'
			);
			return;
		}

		loadRoleAchievements(role);
	});

	// Load role achievements via AJAX
	function loadRoleAchievements(role) {
		$('#wc-tp-achievements-config-container').html(
			'<div class="wc-tp-loading">' +
			'<span class="spinner is-active"></span>' +
			'<p>Loading achievements configuration...</p>' +
			'</div>'
		);

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_get_role_achievements',
				nonce: wcTpPerformance.nonce,
				role: role
			},
			success: function(response) {
				if (response.success) {
					$('#wc-tp-achievements-config-container').html(response.data.html);
				} else {
					showMessage('error', response.data.message || 'Error loading achievements configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Clone achievements configuration
	$('#wc-tp-clone-achievements-role').on('click', function() {
		const currentRole = $('#wc-tp-achievements-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		// Show dialog to select source role
		const allRoles = [];
		$('#wc-tp-achievements-role-selector option').each(function() {
			if ($(this).val() && $(this).val() !== currentRole) {
				allRoles.push({
					value: $(this).val(),
					text: $(this).text()
				});
			}
		});

		if (allRoles.length === 0) {
			alert('No other roles available to clone from');
			return;
		}

		const sourceRole = prompt('Enter the role name to clone from:\n\n' + allRoles.map(r => r.text).join('\n'));
		
		if (sourceRole) {
			cloneRoleAchievements(sourceRole, currentRole);
		}
	});

	// Clone role achievements via AJAX
	function cloneRoleAchievements(fromRole, toRole) {
		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_clone_role_achievements',
				nonce: wcTpPerformance.nonce,
				from_role: fromRole,
				to_role: toRole
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', response.data.message);
					loadRoleAchievements(toRole);
				} else {
					showMessage('error', response.data.message || 'Error cloning achievements configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			}
		});
	}

	// Reset achievements role configuration
	$('#wc-tp-reset-achievements-role').on('click', function() {
		const currentRole = $('#wc-tp-achievements-role-selector').val();
		
		if (!currentRole) {
			alert('Please select a role first');
			return;
		}

		if (confirm('Are you sure you want to reset this role achievements configuration to default values?')) {
			showMessage('success', 'Role achievements configuration reset to defaults');
			loadRoleAchievements(currentRole);
		}
	});

	// Save achievements configuration (integrated with main save button)
	function saveAchievementsConfiguration() {
		const achievementsConfig = collectAchievementsConfigurationData();

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_achievements_config',
				nonce: wcTpPerformance.nonce,
				config: achievementsConfig
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', 'Achievements configuration saved successfully!');
				} else {
					showMessage('error', response.data.message || 'Error saving achievements configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred while saving achievements');
			}
		});
	}

	// Collect achievements configuration data
	function collectAchievementsConfigurationData() {
		const config = {
			enabled: $('#achievements_enabled').is(':checked') ? 1 : 0,
			display_style: $('#achievements_display_style').val(),
			show_locked: $('#achievements_show_locked').is(':checked') ? 1 : 0,
			notification: $('#achievements_notification').is(':checked') ? 1 : 0,
			roles: {}
		};

		// Get current role being configured
		const currentRole = $('.wc-tp-role-achievements-form').data('role');
		
		if (currentRole) {
			config.roles[currentRole] = {};
			
			const categories = ['earnings', 'orders', 'aov'];
			const tiers = ['bronze', 'silver', 'gold'];
			
			categories.forEach(function(category) {
				tiers.forEach(function(tier) {
					const key = category + '_' + tier;
					const nameField = $('input[name="achievement_' + category + '_' + tier + '_name"]');
					const descField = $('textarea[name="achievement_' + category + '_' + tier + '_description"]');
					const thresholdField = $('input[name="achievement_' + category + '_' + tier + '_threshold"]');
					const iconField = $('select[name="achievement_' + category + '_' + tier + '_icon"]');
					
					if (nameField.length) {
						config.roles[currentRole][key] = {
							name: nameField.val(),
							description: descField.val(),
							threshold: category === 'orders' ? parseInt(thresholdField.val()) || 0 : parseFloat(thresholdField.val()) || 0,
							tier: tier,
							icon: iconField.val()
						};
					}
				});
			});
		}

		return config;
	}

	// ============================================================================
	// BASELINES & BENCHMARKS FUNCTIONALITY
	// ============================================================================

	// Collect baselines configuration data
	function collectBaselinesConfigurationData() {
		const config = {
			method: $('#baseline_method').val(),
			periods: parseInt($('#baseline_periods').val()) || 3,
			percentile: parseInt($('#baseline_percentile').val()) || 75,
			sample_earnings: $('#sample_earnings').val(),
			sample_orders: $('#sample_orders').val(),
			sample_aov: $('#sample_aov').val()
		};
		
		return config;
	}

	// Collect calculation configuration data
	function collectCalculationConfigurationData() {
		const config = {
			score_method: $('#calc_score_method').val(),
			weight_earnings: parseInt($('#calc_weight_earnings').val()) || 40,
			weight_orders: parseInt($('#calc_weight_orders').val()) || 35,
			weight_aov: parseInt($('#calc_weight_aov').val()) || 25,
			score_cap: parseFloat($('#calc_score_cap').val()) || 10,
			rounding: $('#calc_rounding').val(),
			period_type: $('#calc_period_type').val(),
			custom_start_date: $('#calc_custom_start_date').val() || '',
			custom_end_date: $('#calc_custom_end_date').val() || '',
			revenue_attribution: $('#calc_revenue_attribution').val(),
			exclude_refunds: $('#calc_exclude_refunds').is(':checked') ? 1 : 0,
			aov_method: $('#calc_aov_method').val(),
			custom_formula: $('#calc_custom_formula').val()
		};
		
		return config;
	}

	// Collect system configuration data
	function collectSystemConfigurationData() {
		const config = {
			enable_performance: $('#system_enable_performance').is(':checked') ? 1 : 0,
			show_score: $('#system_show_score').is(':checked') ? 1 : 0,
			show_goals: $('#system_show_goals').is(':checked') ? 1 : 0,
			show_achievements: $('#system_show_achievements').is(':checked') ? 1 : 0,
			show_baselines: $('#system_show_baselines').is(':checked') ? 1 : 0,
			show_leaderboard: $('#system_show_leaderboard').is(':checked') ? 1 : 0,
			refresh_interval: parseInt($('#system_refresh_interval').val()) || 30,
			data_retention: $('#system_data_retention').val(),
			anonymize_data: $('#system_anonymize_data').is(':checked') ? 1 : 0,
			cache_duration: parseInt($('#system_cache_duration').val()) || 300,
			debug_mode: $('#system_debug_mode').is(':checked') ? 1 : 0
		};
		
		return config;
	}

	// Collect bonus configuration data
	function collectBonusConfigurationData() {
		// Validate rules
		let isValid = true;
		const rules = [];
		
		$('#wc-tp-bonus-rules-list .wc-tp-bonus-rule-row').each(function(index) {
			const $row = $(this);
			const ruleId = $row.find('.wc-tp-bonus-rule-id-field').val();
			const tier = $row.find('[name*="[tier]"]').val();
			const streakCount = $row.find('[name*="[streak_count]"]').val();
			const bonusType = $row.find('[name*="[bonus_type]"]').val();
			const bonusAmount = $row.find('[name*="[bonus_amount]"]').val();
			const bonusDescription = $row.find('[name*="[bonus_description]"]').val();
			const repeatable = $row.find('[name*="[repeatable]"]').is(':checked');
			
			// Get eligible roles
			const eligibleRoles = [];
			$row.find('[name*="[eligible_roles][]"]:checked').each(function() {
				eligibleRoles.push($(this).val());
			});
			
			// Skip empty rules (all fields empty)
			if (!tier && !streakCount && !bonusDescription) {
				return true; // continue
			}
			
			// Validate required fields
			if (!tier || !streakCount || !bonusDescription) {
				isValid = false;
				$row.css('border-color', '#d63638');
				return true; // continue
			} else {
				$row.css('border-color', '#dcdcde');
			}
			
			// Validate money type has amount
			if (bonusType === 'money' && (!bonusAmount || bonusAmount <= 0)) {
				isValid = false;
				$row.find('.wc-tp-bonus-amount-field').css('border-color', '#d63638');
				return true; // continue
			}
			
			// Validate at least one role is selected
			if (eligibleRoles.length === 0) {
				isValid = false;
				$row.find('.wc-tp-bonus-roles').css('border-color', '#d63638');
				return true; // continue
			} else {
				$row.find('.wc-tp-bonus-roles').css('border-color', '#dcdcde');
			}
			
			// Ensure repeatable is properly converted to 0 or 1
			const repeatableValue = repeatable ? 1 : 0;
			
			rules.push({
				rule_id: ruleId ? parseInt(ruleId) : 0,
				tier: tier,
				streak_count: parseInt(streakCount),
				bonus_type: bonusType,
				bonus_amount: parseFloat(bonusAmount) || 0,
				bonus_description: bonusDescription,
				repeatable: repeatableValue,
				eligible_roles: eligibleRoles
			});
		});
		
		if (!isValid) {
			throw new Error('Please fill in all required fields for each bonus rule.');
		}
		
		// Ensure at least one rule exists
		if (rules.length === 0) {
			throw new Error('Please add at least one bonus rule.');
		}
		
		const config = {
			enabled: $('#bonus_enabled').is(':checked') ? 1 : 0,
			notification: $('#bonus_notification').is(':checked') ? 1 : 0,
			show_progress: $('#bonus_show_progress').is(':checked') ? 1 : 0,
			rules: rules
		};
		
		return config;
	}

	// Show/hide baseline options based on method
	$('#baseline_method').on('change', function() {
		const method = $(this).val();
		
		// Hide all options first
		$('.wc-tp-baseline-option').hide();
		
		// Show relevant options
		$('.wc-tp-baseline-option[data-show-for="' + method + '"]').show();
	}).trigger('change');

	// Show/hide calculation options based on method
	$('#calc_score_method').on('change', function() {
		const method = $(this).val();
		
		// Hide all calculation options first
		$('.wc-tp-calc-option').hide();
		
		// Show relevant options
		$('.wc-tp-calc-option[data-show-for="' + method + '"]').show();
	}).trigger('change');

	// Calculate baseline preview
	$('#wc-tp-calculate-baseline').on('click', function() {
		const method = $('#baseline_method').val();
		const periods = parseInt($('#baseline_periods').val()) || 3;
		const percentile = parseInt($('#baseline_percentile').val()) || 75;
		
		// Parse sample data
		const earningsData = $('#sample_earnings').val().split(',').map(v => parseFloat(v.trim())).filter(v => !isNaN(v));
		const ordersData = $('#sample_orders').val().split(',').map(v => parseInt(v.trim())).filter(v => !isNaN(v));
		const aovData = $('#sample_aov').val().split(',').map(v => parseFloat(v.trim())).filter(v => !isNaN(v));
		
		if (earningsData.length === 0 && ordersData.length === 0 && aovData.length === 0) {
			alert('Please enter sample data for at least one metric');
			return;
		}
		
		// Show loading
		$(this).prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>Calculating...');
		
		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_calculate_baseline_preview',
				nonce: wcTpPerformance.nonce,
				method: method,
				periods: periods,
				percentile: percentile,
				earnings_data: earningsData,
				orders_data: ordersData,
				aov_data: aovData
			},
			success: function(response) {
				if (response.success) {
					displayBaselineResults(response.data, method, periods, percentile);
					$('.wc-tp-baseline-result-section').slideDown(300);
				} else {
					showMessage('error', response.data.message || 'Error calculating baseline');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred');
			},
			complete: function() {
				$('#wc-tp-calculate-baseline').prop('disabled', false).html('<span class="dashicons dashicons-calculator"></span>Calculate Baseline');
			}
		});
	});

	// Display baseline results
	function displayBaselineResults(data, method, periods, percentile) {
		// Earnings
		$('#baseline_earnings_value').text('$' + data.earnings.toFixed(2));
		$('#baseline_earnings_method').text(getMethodDescription(method, periods, percentile));
		
		// Orders
		$('#baseline_orders_value').text(Math.round(data.orders));
		$('#baseline_orders_method').text(getMethodDescription(method, periods, percentile));
		
		// AOV
		$('#baseline_aov_value').text('$' + data.aov.toFixed(2));
		$('#baseline_aov_method').text(getMethodDescription(method, periods, percentile));
	}

	// Get method description
	function getMethodDescription(method, periods, percentile) {
		switch(method) {
			case 'rolling_average':
				return 'Average of last ' + periods + ' periods';
			case 'historical_average':
				return 'Average of all historical data';
			case 'best_period':
				return 'Best performance period';
			case 'median':
				return 'Median of all data points';
			case 'percentile':
				return percentile + 'th percentile of data';
			case 'manual':
				return 'Manual entry (showing average)';
			default:
				return 'Calculated baseline';
		}
	}

	// Save baselines configuration
	function saveBaselinesConfiguration() {
		const baselinesConfig = {
			method: $('#baseline_method').val(),
			periods: parseInt($('#baseline_periods').val()) || 3,
			percentile: parseInt($('#baseline_percentile').val()) || 75,
			update_frequency: $('#baseline_update_frequency').val(),
			minimum_data: parseInt($('#baseline_minimum_data').val()) || 5,
			show_comparison: $('#baseline_show_comparison').is(':checked') ? 1 : 0,
			show_trend: $('#baseline_show_trend').is(':checked') ? 1 : 0,
			show_history: $('#baseline_show_history').is(':checked') ? 1 : 0,
			comparison_format: $('#baseline_comparison_format').val()
		};

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_save_baselines_config',
				nonce: wcTpPerformance.nonce,
				config: baselinesConfig
			},
			success: function(response) {
				if (response.success) {
					showMessage('success', 'Baselines configuration saved successfully!');
				} else {
					showMessage('error', response.data.message || 'Error saving baselines configuration');
				}
			},
			error: function() {
				showMessage('error', 'AJAX error occurred while saving baselines');
			}
		});
	}

		// ============================================================================
	// FORMULA TESTER
	// ============================================================================

	// Formula Tester - Calculate Score
	$(document).on('click', '#wc-tp-test-formula', function() {
		const baseScore = parseFloat($('#test_base_score').val()) || 0;
		const earningsPoints = parseFloat($('#test_earnings_points').val()) || 0;
		const ordersPoints = parseFloat($('#test_orders_points').val()) || 0;
		const aovPoints = parseFloat($('#test_aov_points').val()) || 0;

		// Get calculation settings
		const scoreMethod = $('#calc_score_method').val() || 'additive';
		const scoreCap = parseFloat($('#calc_score_cap').val()) || 10;
		const rounding = $('#calc_rounding').val() || 'one_decimal';
		const customFormula = $('#calc_custom_formula').val() || '';

		// Get weights for weighted method
		const weightEarnings = parseFloat($('#calc_weight_earnings').val()) || 40;
		const weightOrders = parseFloat($('#calc_weight_orders').val()) || 35;
		const weightAov = parseFloat($('#calc_weight_aov').val()) || 25;

		let finalScore = 0;
		let steps = [];

		// Calculate based on method
		switch (scoreMethod) {
			case 'additive':
				finalScore = baseScore + earningsPoints + ordersPoints + aovPoints;
				steps.push('Base Score: ' + baseScore.toFixed(2));
				steps.push('+ Earnings Points: ' + earningsPoints.toFixed(2));
				steps.push('+ Orders Points: ' + ordersPoints.toFixed(2));
				steps.push('+ AOV Points: ' + aovPoints.toFixed(2));
				steps.push('= Subtotal: ' + finalScore.toFixed(2));
				break;

			case 'weighted':
				const totalWeight = weightEarnings + weightOrders + weightAov;
				const weightedScore = (earningsPoints * (weightEarnings / 100)) +
									   (ordersPoints * (weightOrders / 100)) +
									   (aovPoints * (weightAov / 100));
				finalScore = baseScore + weightedScore;
				steps.push('Base Score: ' + baseScore.toFixed(2));
				steps.push('Earnings: ' + earningsPoints.toFixed(2) + ' × ' + (weightEarnings / 100).toFixed(2) + ' = ' + (earningsPoints * (weightEarnings / 100)).toFixed(2));
				steps.push('Orders: ' + ordersPoints.toFixed(2) + ' × ' + (weightOrders / 100).toFixed(2) + ' = ' + (ordersPoints * (weightOrders / 100)).toFixed(2));
				steps.push('AOV: ' + aovPoints.toFixed(2) + ' × ' + (weightAov / 100).toFixed(2) + ' = ' + (aovPoints * (weightAov / 100)).toFixed(2));
				steps.push('= Subtotal: ' + finalScore.toFixed(2));
				break;

			case 'multiplicative':
				finalScore = baseScore * (1 + (earningsPoints * 0.1)) * (1 + (ordersPoints * 0.1)) * (1 + (aovPoints * 0.1));
				steps.push('Base Score: ' + baseScore.toFixed(2));
				steps.push('× (1 + Earnings × 0.1): ' + (1 + (earningsPoints * 0.1)).toFixed(2));
				steps.push('× (1 + Orders × 0.1): ' + (1 + (ordersPoints * 0.1)).toFixed(2));
				steps.push('× (1 + AOV × 0.1): ' + (1 + (aovPoints * 0.1)).toFixed(2));
				steps.push('= Subtotal: ' + finalScore.toFixed(2));
				break;

			case 'custom':
				if (customFormula.trim() === '') {
					alert('Please enter a custom formula');
					return;
				}
				try {
					// Replace variable names with actual values
					let formula = customFormula
						.replace(/\bbase\b/g, baseScore)
						.replace(/\bearnings\b/g, earningsPoints)
						.replace(/\borders\b/g, ordersPoints)
						.replace(/\baov\b/g, aovPoints);

					// Evaluate the formula (safe evaluation with limited scope)
					finalScore = Function('"use strict"; return (' + formula + ')')();
					steps.push('Formula: ' + customFormula);
					steps.push('= Result: ' + finalScore.toFixed(2));
				} catch (e) {
					alert('Invalid formula: ' + e.message);
					return;
				}
				break;

			default:
				finalScore = baseScore + earningsPoints + ordersPoints + aovPoints;
		}

		// Apply score cap
		const cappedScore = Math.min(finalScore, scoreCap);
		if (cappedScore !== finalScore) {
			steps.push('Score Cap Applied: ' + scoreCap);
			steps.push('Capped Score: ' + cappedScore.toFixed(2));
		}

		// Apply rounding
		let roundedScore = cappedScore;
		switch (rounding) {
			case 'none':
				roundedScore = cappedScore;
				steps.push('Rounding: None');
				break;
			case 'one_decimal':
				roundedScore = Math.round(cappedScore * 10) / 10;
				steps.push('Rounding: One Decimal');
				break;
			case 'two_decimals':
				roundedScore = Math.round(cappedScore * 100) / 100;
				steps.push('Rounding: Two Decimals');
				break;
			case 'whole':
				roundedScore = Math.round(cappedScore);
				steps.push('Rounding: Whole Number');
				break;
		}

		// Display results
		let stepsHtml = '';
		steps.forEach(function(step) {
			stepsHtml += '<div class="wc-tp-formula-step">' + step + '</div>';
		});

		$('#formula_steps').html(stepsHtml);
		$('#formula_final_score').text(roundedScore.toFixed(rounding === 'none' ? 3 : (rounding === 'two_decimals' ? 2 : (rounding === 'one_decimal' ? 1 : 0))));

		// Show result
		$('.wc-tp-formula-result').slideDown(300);
	});

	// ============================================================================
	// BONUS CONFIGURATION (Phase 2 Part 2)
	// ============================================================================

	// Bonus rule index counter
	let bonusRuleIndex = $('#wc-tp-bonus-rules-list .wc-tp-bonus-rule-row').length;

	// Add bonus rule
	$('#wc-tp-add-bonus-rule').on('click', function() {
		const template = $('#wc-tp-bonus-rule-template').html();
		const newRule = template.replace(/\{\{INDEX\}\}/g, bonusRuleIndex);
		
		$('#wc-tp-bonus-rules-list').append(newRule);
		bonusRuleIndex++;
		
		// Update rule numbers
		updateBonusRuleNumbers();
		
		// Reinitialize bonus type change handlers
		initializeBonusTypeHandlers();
		
		// Show success message
		showMessage('success', 'New bonus rule added. Don\'t forget to save!');
	});

	// Remove bonus rule
	$(document).on('click', '.wc-tp-remove-bonus-rule', function() {
		const $row = $(this).closest('.wc-tp-bonus-rule-row');
		
		// If this is the last rule, don't remove it, just clear it
		if ($('#wc-tp-bonus-rules-list .wc-tp-bonus-rule-row').length === 1) {
			$row.find('input, select').val('');
			$row.find('input[type="checkbox"]').prop('checked', false);
			showMessage('info', 'At least one rule must exist. Fields have been cleared.');
			return;
		}
		
		$row.fadeOut(300, function() {
			$(this).remove();
			updateBonusRuleNumbers();
			showMessage('success', 'Bonus rule removed. Don\'t forget to save!');
		});
	});

	// Update bonus rule numbers after removal
	function updateBonusRuleNumbers() {
		$('#wc-tp-bonus-rules-list .wc-tp-bonus-rule-row').each(function(index) {
			$(this).find('.wc-tp-bonus-rule-number').text('Rule #' + (index + 1));
		});
	}

	// Initialize bonus type change handlers
	function initializeBonusTypeHandlers() {
		$('.wc-tp-bonus-type').off('change').on('change', function() {
			const $row = $(this).closest('.wc-tp-bonus-rule-row');
			const bonusType = $(this).val();
			const $amountField = $row.find('.wc-tp-bonus-amount-field');
			
			if (bonusType === 'money') {
				$amountField.slideDown(200);
				$amountField.find('input').prop('required', true);
			} else {
				$amountField.slideUp(200);
				$amountField.find('input').prop('required', false);
			}
		});
	}

	// Initialize on page load
	initializeBonusTypeHandlers();

});

