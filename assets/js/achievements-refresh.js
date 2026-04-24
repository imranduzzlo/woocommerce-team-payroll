jQuery(document).ready(function($) {
	// Refresh current period achievements
	$('#wc-tp-refresh-current-period-achievements').on('click', function() {
		if (!confirm('Are you sure you want to recalculate all current period achievements with the new settings?\n\nThis will:\n• Recalculate already achieved achievements with new status settings\n• Lock the NEW values (not affected by future changes)\n• Keep previous period history unchanged\n\nThis action cannot be undone!')) {
			return;
		}

		const $button = $(this);
		const originalText = $button.html();
		$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Refreshing...');

		$.ajax({
			url: wcTpPerformance.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_tp_refresh_current_period_achievements',
				nonce: wcTpPerformance.nonce
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
				} else {
					alert('Error: ' + (response.data.message || 'Error refreshing achievements'));
				}
			},
			error: function() {
				alert('AJAX error occurred while refreshing achievements');
			},
			complete: function() {
				$button.prop('disabled', false).html(originalText);
			}
		});
	});
});
