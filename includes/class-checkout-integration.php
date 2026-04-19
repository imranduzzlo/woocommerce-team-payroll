<?php
/**
 * Checkout Field Integration
 */

class WC_Team_Payroll_Checkout_Integration {

	private $agent_field_name = 'order_agent_name';
	private $processor_field_name = '_processor_user_id';
	private $agent_user_roles = array( 'shop_employee', 'shop_manager', 'administrator' );

	public function __construct() {
		$this->load_settings();
		add_action( 'wp_footer', array( $this, 'auto_populate_agent_dropdown' ), 99 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'store_agent_processor_meta' ), 20, 2 );
	}

	private function load_settings() {
		$checkout_fields = get_option( 'wc_team_payroll_checkout_fields', array() );
		$acf_fields = get_option( 'wc_team_payroll_acf_fields', array() );

		if ( isset( $checkout_fields['agent_field_name'] ) ) {
			$this->agent_field_name = sanitize_text_field( $checkout_fields['agent_field_name'] );
		}

		if ( isset( $checkout_fields['processor_field_name'] ) ) {
			$this->processor_field_name = sanitize_text_field( $checkout_fields['processor_field_name'] );
		}

		if ( isset( $checkout_fields['agent_user_roles'] ) && is_array( $checkout_fields['agent_user_roles'] ) ) {
			$this->agent_user_roles = array_map( 'sanitize_text_field', $checkout_fields['agent_user_roles'] );
		}
	}

	public function auto_populate_agent_dropdown() {
		if ( ! is_checkout() ) {
			return;
		}

		// Get current logged-in user ID to exclude from dropdown
		$current_user_id = get_current_user_id();

		// Fetch users with specified roles
		$users = get_users( array(
			'role__in' => $this->agent_user_roles,
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		) );

		$data = array();
		foreach ( $users as $user ) {
			// Exclude current logged-in user from dropdown
			if ( $current_user_id && $user->ID === $current_user_id ) {
				continue; // Skip current user - they can't select themselves
			}

			// Check if employee is active (exclude inactive employees)
			$employee_status = get_user_meta( $user->ID, '_wc_tp_employee_status', true );
			if ( $employee_status === 'inactive' ) {
				continue; // Skip inactive employees
			}

			// Get vb_user_id from user meta
			$vb_user_id = get_user_meta( $user->ID, 'vb_user_id', true );

			$data[] = array(
				'id'         => $user->ID,
				'name'       => $user->display_name,
				'vb_user_id' => $vb_user_id ? $vb_user_id : '',
			);
		}

		?>
		<script>
			(function($) {
				window.__AGENT_USERS__ = <?php echo wp_json_encode( $data ); ?>;

				function fillAgentDropdown() {
					let $el = $('select[name="order_agent_name"]');
					if (!$el.length) return;
					if (typeof window.__AGENT_USERS__ === 'undefined') return;

					// Prevent duplicate population
					if ($el.data('agent-loaded') === true) return;

					let current = $el.val();

					// Destroy select2 safely
					if ($el.hasClass('select2-hidden-accessible')) {
						$el.select2('destroy');
					}

					// Reset field
					$el.html('<option value="">Select Agent</option>');

					// Populate users
					window.__AGENT_USERS__.forEach(user => {
						let formattedName = user.name.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
						let label = (user.vb_user_id ? user.vb_user_id + ' ' : '') + formattedName;
						$el.append('<option value="' + user.id + '">' + label + '</option>');
					});

					// Restore selection
					if (current) {
						$el.val(current);
					}

					// Re-init select2
					if ($.fn.select2) {
						$el.select2({
							width: '100%',
							placeholder: 'Select Agent'
						});
					}

					$el.data('agent-loaded', true);
				}

				function triggerFill() {
					let $el = $('select[name="order_agent_name"]');
					$el.removeData('agent-loaded');
					fillAgentDropdown();
				}

				// Initial load
				$(document).ready(function() {
					setTimeout(triggerFill, 300);
				});

				// WooCommerce refresh safe trigger
				$(document.body).on('updated_checkout updated_shipping_method wc_fragments_refreshed', function() {
					setTimeout(triggerFill, 200);
				});
			})(jQuery);
		</script>
		<?php
	}

	public function store_agent_processor_meta( $order, $data ) {
		$current_user_id = is_user_logged_in() ? get_current_user_id() : null;
		$agent_id = ! empty( $_POST['order_agent_name'] ) ? intval( $_POST['order_agent_name'] ) : null;

		// Validate agent has selected role
		if ( $agent_id ) {
			$agent = get_user_by( 'ID', $agent_id );
			if ( ! $agent ) {
				return; // Agent doesn't exist
			}

			// Check if agent has one of the selected roles
			$agent_has_role = false;
			foreach ( $this->agent_user_roles as $role ) {
				if ( in_array( $role, $agent->roles ) ) {
					$agent_has_role = true;
					break;
				}
			}

			if ( ! $agent_has_role ) {
				return; // Agent doesn't have required role
			}
		}

		// If agent selected
		if ( $agent_id ) {
			$order->update_meta_data( '_primary_agent_id', $agent_id );
			// Processor is logged-in user (if exists)
			if ( $current_user_id ) {
				$order->update_meta_data( '_processor_user_id', $current_user_id );
			}
		}
		// If NO agent selected but user logged in
		elseif ( $current_user_id ) {
			// Check if logged-in user has one of the selected roles
			$user = get_user_by( 'ID', $current_user_id );
			if ( $user ) {
				$user_has_role = false;
				foreach ( $this->agent_user_roles as $role ) {
					if ( in_array( $role, $user->roles ) ) {
						$user_has_role = true;
						break;
					}
				}

				if ( $user_has_role ) {
					// Logged-in team member is the agent (their own order)
					$order->update_meta_data( '_primary_agent_id', $current_user_id );
					// No processor (single user gets 100%)
				}
			}
		}
		// If NO agent and NO user logged in
		else {
			// No commission assignment
		}
	}
}
