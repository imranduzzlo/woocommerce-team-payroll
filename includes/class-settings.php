<?php
/**
 * Settings Page with Tabs
 */

class WC_Team_Payroll_Settings {

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wc-team-payroll' ) );
		}

		if ( isset( $_POST['submit'] ) && check_admin_referer( 'wc_team_payroll_settings_nonce' ) ) {
			$this->save_settings();
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'wc-team-payroll' ) . '</p></div>';
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
		$settings = get_option( 'wc_team_payroll_settings', array() );
		$checkout_fields = get_option( 'wc_team_payroll_checkout_fields', array() );
		$acf_fields = get_option( 'wc_team_payroll_acf_fields', array() );
		$employee_roles = get_option( 'wc_tp_employee_roles', array( 'shop_employee' ) );
		$user_id_prefix = get_option( 'wc_tp_user_id_prefix', 'PVVB-EMID' );

		$this->render_tabs( $current_tab, $settings, $checkout_fields, $acf_fields, $employee_roles, $user_id_prefix );
	}

	private function render_tabs( $current_tab, $settings, $checkout_fields, $acf_fields, $employee_roles, $user_id_prefix ) {
		// Get styling settings
		$styling_settings = get_option( 'wc_team_payroll_styling', array() );
		?>
		<div class="wrap wc-tp-settings-wrap">
			<h1><?php esc_html_e( 'WooCommerce Team Payroll Settings', 'wc-team-payroll' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="?page=wc-team-payroll-settings&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">General</a>
				<a href="?page=wc-team-payroll-settings&tab=commission" class="nav-tab <?php echo $current_tab === 'commission' ? 'nav-tab-active' : ''; ?>">Commission</a>
				<a href="?page=wc-team-payroll-settings&tab=styling" class="nav-tab <?php echo $current_tab === 'styling' ? 'nav-tab-active' : ''; ?>">Frontend Styling</a>
				<a href="?page=wc-team-payroll-settings&tab=performance" class="nav-tab <?php echo $current_tab === 'performance' ? 'nav-tab-active' : ''; ?>">Reports & Performance</a>
				<a href="?page=wc-team-payroll-settings&tab=roles" class="nav-tab <?php echo $current_tab === 'roles' ? 'nav-tab-active' : ''; ?>">User Roles</a>
				<a href="?page=wc-team-payroll-settings&tab=woocommerce" class="nav-tab <?php echo $current_tab === 'woocommerce' ? 'nav-tab-active' : ''; ?>">WooCommerce</a>
				<a href="?page=wc-team-payroll-settings&tab=documentation" class="nav-tab <?php echo $current_tab === 'documentation' ? 'nav-tab-active' : ''; ?>">📖 Documentation</a>
				<a href="?page=wc-team-payroll-settings&tab=debug" class="nav-tab <?php echo $current_tab === 'debug' ? 'nav-tab-active' : ''; ?>">Debug</a>
			</nav>

			<!-- Unsaved Changes Warning -->
			<div id="wc-tp-unsaved-warning" style="display: none !important; position: sticky !important; top: 40px !important; z-index: 999 !important; background-color: #fff4e6 !important; border-left: 4px solid #ff9900 !important; border-bottom: 1px solid #ffcc99 !important; padding: 12px 15px !important; margin: 0 !important; border-radius: 0 !important; width: 100% !important; box-sizing: border-box !important; box-shadow: 0 2px 4px rgba(255, 153, 0, 0.1) !important;">
				<p style="margin: 0 !important; color: #cc7700 !important; font-weight: 600 !important; font-size: 14px !important;">
					⚠️ Settings have changed, you should save them!
				</p>
			</div>

			<form method="post" action="" id="wc-tp-settings-form">
				<?php wp_nonce_field( 'wc_team_payroll_settings_nonce' ); ?>

				<?php if ( $current_tab === 'general' ) : ?>
					<h2>General Settings</h2>
					<table class="form-table">
						<tr>
							<th><label for="wc_tp_user_id_prefix">Employee ID Prefix</label></th>
							<td>
								<input type="text" id="wc_tp_user_id_prefix" name="wc_tp_user_id_prefix" value="<?php echo esc_attr( $user_id_prefix ); ?>" />
								<p class="description">Prefix for auto-generated employee IDs (e.g., PVVB-EMID). User field name vb_user_id.</p>
							</td>
						</tr>
						<tr>
							<th><label for="enable_breakdown">Enable Breakdown Table</label></th>
							<td>
								<input type="checkbox" id="enable_breakdown" name="wc_team_payroll_settings[enable_breakdown]" value="1" <?php checked( isset( $settings['enable_breakdown'] ) ? $settings['enable_breakdown'] : 1, 1 ); ?> />
								<p class="description">Show commission breakdown table on order details</p>
							</td>
						</tr>
						<tr>
							<th><label for="enable_myaccount">Enable My Account Integration</label></th>
							<td>
								<input type="checkbox" id="enable_myaccount" name="wc_team_payroll_settings[enable_myaccount]" value="1" <?php checked( isset( $settings['enable_myaccount'] ) ? $settings['enable_myaccount'] : 1, 1 ); ?> />
								<p class="description">Add earnings tabs to My Account page</p>
							</td>
						</tr>
						<tr>
							<th><label for="enable_shortcodes">Enable Shortcodes</label></th>
							<td>
								<input type="checkbox" id="enable_shortcodes" name="wc_team_payroll_settings[enable_shortcodes]" value="1" <?php checked( isset( $settings['enable_shortcodes'] ) ? $settings['enable_shortcodes'] : 1, 1 ); ?> />
								<p class="description">Enable shortcode system for displaying earnings</p>
							</td>
						</tr>
					</table>

					<h3>Contact Information for Inactive Employees</h3>
					<p>These contact details will be shown to inactive employees when they try to login.</p>
					<table class="form-table">
						<tr>
							<th><label for="contact_whatsapp">WhatsApp Number</label></th>
							<td>
								<input type="text" id="contact_whatsapp" name="contact_whatsapp" value="<?php echo esc_attr( get_option( 'wc_team_payroll_contact_whatsapp', '' ) ); ?>" placeholder="1234567890" />
								<p class="description">WhatsApp number (without + or country code, e.g., 1234567890)</p>
							</td>
						</tr>
						<tr>
							<th><label for="contact_email">Contact Email</label></th>
							<td>
								<input type="email" id="contact_email" name="contact_email" value="<?php echo esc_attr( get_option( 'wc_team_payroll_contact_email', '' ) ); ?>" placeholder="support@example.com" />
								<p class="description">Email address for employee support</p>
							</td>
						</tr>
						<tr>
							<th><label for="contact_telegram">Telegram Username</label></th>
							<td>
								<input type="text" id="contact_telegram" name="contact_telegram" value="<?php echo esc_attr( get_option( 'wc_team_payroll_contact_telegram', '' ) ); ?>" placeholder="username" />
								<p class="description">Telegram username (without @, e.g., username)</p>
							</td>
						</tr>
					</table>

					<?php
					// Get order editor settings
					$order_editor_settings = get_option( 'wc_team_payroll_order_editor', array() );
					$editor_enabled = isset( $order_editor_settings['enabled'] ) && $order_editor_settings['enabled'] === '1';
					$editable_statuses = isset( $order_editor_settings['editable_statuses'] ) && is_array( $order_editor_settings['editable_statuses'] ) 
						? $order_editor_settings['editable_statuses'] 
						: array();
					
					// Get all order statuses
					$all_statuses = wc_get_order_statuses();
					?>

					<h3>Order Editor Settings</h3>
					<p>Enable order editing for specific order statuses. This allows you to modify order items, quantities, prices, and meta data.</p>
					<table class="form-table">
						<tr>
							<th><label for="order_editor_enabled">Enable Order Editor</label></th>
							<td>
								<input type="checkbox" id="order_editor_enabled" name="wc_team_payroll_order_editor[enabled]" value="1" <?php checked( $editor_enabled, true ); ?> />
								<p class="description">Enable advanced order editing capabilities for selected order statuses.</p>
							</td>
						</tr>
						<tr>
							<th><label>Editable Order Statuses</label></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span>Editable Order Statuses</span></legend>
									<p class="description" style="margin-bottom: 10px;">Select which order statuses should allow editing. Orders in these statuses will have edit icons and additional editing options.</p>
									<?php foreach ( $all_statuses as $status_key => $status_label ) : 
										$status_slug = str_replace( 'wc-', '', $status_key );
									?>
										<label style="display: block; margin-bottom: 8px;">
											<input type="checkbox" 
												name="wc_team_payroll_order_editor[editable_statuses][]" 
												value="<?php echo esc_attr( $status_slug ); ?>" 
												<?php checked( in_array( $status_slug, $editable_statuses ), true ); ?> />
											<span class="dashicons dashicons-edit" style="color: #2271b1; font-size: 16px; vertical-align: middle;"></span>
											<?php echo esc_html( $status_label ); ?>
										</label>
									<?php endforeach; ?>
								</fieldset>
								<p class="description" style="margin-top: 10px;">
									<strong>Note:</strong> This feature respects existing order editing plugins/themes. If conflicts are detected, the order editor will be automatically disabled.
								</p>
							</td>
						</tr>
					</table>

					<div class="wc-tp-order-editor-info" style="background: #e7f7ff; border-left: 4px solid #2271b1; padding: 15px; margin-top: 20px; border-radius: 4px;">
						<h4 style="margin-top: 0; color: #2271b1;">
							<span class="dashicons dashicons-info" style="font-size: 20px; vertical-align: middle;"></span>
							Order Editor Features
						</h4>
						<ul style="margin: 10px 0 0 20px; line-height: 1.8;">
							<li><strong>Add Products:</strong> Add new products to existing orders</li>
							<li><strong>Edit Items:</strong> Modify quantities, prices, and totals</li>
							<li><strong>Remove Items:</strong> Delete items from orders</li>
							<li><strong>Edit Order Meta:</strong> Add, update, or delete custom order meta fields</li>
							<li><strong>Recalculate Totals:</strong> Automatically recalculate order totals and commissions</li>
							<li><strong>Audit Trail:</strong> All changes are logged in order notes</li>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( $current_tab === 'commission' ) : ?>
					<h2>Commission Settings</h2>
					<table class="form-table">
						<tr>
							<th><label for="agent_percentage">Agent Commission %</label></th>
							<td>
								<input type="number" id="agent_percentage" name="wc_team_payroll_settings[agent_percentage]" value="<?php echo esc_attr( isset( $settings['agent_percentage'] ) ? $settings['agent_percentage'] : 70 ); ?>" step="0.01" min="0" max="100" />
								<p class="description">Percentage of commission for agent</p>
							</td>
						</tr>
						<tr>
							<th><label for="processor_percentage">Processor Commission %</label></th>
							<td>
								<input type="number" id="processor_percentage" name="wc_team_payroll_settings[processor_percentage]" value="<?php echo esc_attr( isset( $settings['processor_percentage'] ) ? $settings['processor_percentage'] : 30 ); ?>" step="0.01" min="0" max="100" />
								<p class="description">Percentage of commission for processor</p>
							</td>
						</tr>

					</table>
				<?php endif; ?>

				<?php if ( $current_tab === 'styling' ) : ?>
					<h2>Frontend Styling Settings</h2>
					<p>Customize the appearance of My Account pages and frontend elements. These settings will override the default styling.</p>
					
					<h3>Color Scheme</h3>
					<table class="form-table">
						<tr>
							<th><label for="primary_color">Primary Color</label></th>
							<td>
								<input type="color" id="primary_color" name="wc_team_payroll_styling[primary_color]" value="<?php echo esc_attr( isset( $styling_settings['primary_color'] ) ? $styling_settings['primary_color'] : '#0073aa' ); ?>" />
								<p class="description">Main brand color used for buttons, links, and accents.</p>
							</td>
						</tr>
						<tr>
							<th><label for="secondary_color">Secondary Color</label></th>
							<td>
								<input type="color" id="secondary_color" name="wc_team_payroll_styling[secondary_color]" value="<?php echo esc_attr( isset( $styling_settings['secondary_color'] ) ? $styling_settings['secondary_color'] : '#28a745' ); ?>" />
								<p class="description">Secondary color for success states and positive amounts</p>
							</td>
						</tr>
						<tr>
							<th><label for="heading_color">Heading Color</label></th>
							<td>
								<input type="color" id="heading_color" name="wc_team_payroll_styling[heading_color]" value="<?php echo esc_attr( isset( $styling_settings['heading_color'] ) ? $styling_settings['heading_color'] : '#333333' ); ?>" />
								<p class="description">Color for headings (h1, h2, h3, etc.)</p>
							</td>
						</tr>
						<tr>
							<th><label for="text_color">Text Color</label></th>
							<td>
								<input type="color" id="text_color" name="wc_team_payroll_styling[text_color]" value="<?php echo esc_attr( isset( $styling_settings['text_color'] ) ? $styling_settings['text_color'] : '#495057' ); ?>" />
								<p class="description">Main text color for paragraphs and content</p>
							</td>
						</tr>
						<tr>
							<th><label for="link_color">Link Color</label></th>
							<td>
								<input type="color" id="link_color" name="wc_team_payroll_styling[link_color]" value="<?php echo esc_attr( isset( $styling_settings['link_color'] ) ? $styling_settings['link_color'] : '#0073aa' ); ?>" />
								<p class="description">Color for links in normal state</p>
							</td>
						</tr>
						<tr>
							<th><label for="link_hover_color">Link Hover Color</label></th>
							<td>
								<input type="color" id="link_hover_color" name="wc_team_payroll_styling[link_hover_color]" value="<?php echo esc_attr( isset( $styling_settings['link_hover_color'] ) ? $styling_settings['link_hover_color'] : '#005a87' ); ?>" />
								<p class="description">Color for links when hovered</p>
							</td>
						</tr>
					</table>



					<h3>Background Colors</h3>
					<table class="form-table">
						<tr>
							<th><label for="background_color">Main Background</label></th>
							<td>
								<input type="color" id="background_color" name="wc_team_payroll_styling[background_color]" value="<?php echo esc_attr( isset( $styling_settings['background_color'] ) ? $styling_settings['background_color'] : '#ffffff' ); ?>" />
								<p class="description">Background color for main content areas. Use hex color (#ffffff) or CSS variable (var(--color-bg))</p>
							</td>
						</tr>
						<tr>
							<th><label for="header_background">Header Background</label></th>
							<td>
								<input type="color" id="header_background" name="wc_team_payroll_styling[header_background]" value="<?php echo esc_attr( isset( $styling_settings['header_background'] ) ? $styling_settings['header_background'] : '#f8f9fa' ); ?>" />
								<p class="description">Background color for employee header sections</p>
							</td>
						</tr>
						<tr>
							<th><label for="header_border_color">Header Border/Line Color</label></th>
							<td>
								<input type="color" id="header_border_color" name="wc_team_payroll_styling[header_border_color]" value="<?php echo esc_attr( isset( $styling_settings['header_border_color'] ) ? $styling_settings['header_border_color'] : '#0073aa' ); ?>" />
								<p class="description">Color for connecting lines and borders in employee header</p>
							</td>
						</tr>
						<tr>
							<th><label for="card_background">Card Background</label></th>
							<td>
								<input type="color" id="card_background" name="wc_team_payroll_styling[card_background]" value="<?php echo esc_attr( isset( $styling_settings['card_background'] ) ? $styling_settings['card_background'] : '#f8f9fa' ); ?>" />
								<p class="description">Background color for cards and info boxes</p>
							</td>
						</tr>
						<tr>
							<th><label for="border_color">Border Color</label></th>
							<td>
								<input type="color" id="border_color" name="wc_team_payroll_styling[border_color]" value="<?php echo esc_attr( isset( $styling_settings['border_color'] ) ? $styling_settings['border_color'] : '#e9ecef' ); ?>" />
								<p class="description">Color for borders and dividers</p>
							</td>
						</tr>
					</table>

					<h3>Table Styling</h3>
					<table class="form-table">
						<tr>
							<th><label for="table_header_background">Table Header Background</label></th>
							<td>
								<input type="color" id="table_header_background" name="wc_team_payroll_styling[table_header_background]" value="<?php echo esc_attr( isset( $styling_settings['table_header_background'] ) ? $styling_settings['table_header_background'] : '#f8f9fa' ); ?>" />
								<p class="description">Background color for table headers</p>
							</td>
						</tr>
						<tr>
							<th><label for="table_row_hover">Table Row Hover</label></th>
							<td>
								<input type="color" id="table_row_hover" name="wc_team_payroll_styling[table_row_hover]" value="<?php echo esc_attr( isset( $styling_settings['table_row_hover'] ) ? $styling_settings['table_row_hover'] : '#f5f5f5' ); ?>" />
								<p class="description">Background color for table rows on hover</p>
							</td>
						</tr>
						<tr>
							<th><label for="table_border_color">Table Border Color</label></th>
							<td>
								<input type="color" id="table_border_color" name="wc_team_payroll_styling[table_border_color]" value="<?php echo esc_attr( isset( $styling_settings['table_border_color'] ) ? $styling_settings['table_border_color'] : '#dee2e6' ); ?>" />
								<p class="description">Color for table borders and dividers</p>
							</td>
						</tr>
					</table>

					<h3>Typography</h3>
					<table class="form-table">
						<tr>
							<th><label for="font_family">Font Family</label></th>
							<td>
								<select id="font_family" name="wc_team_payroll_styling[font_family]">
									<?php
									$font_family = isset( $styling_settings['font_family'] ) ? $styling_settings['font_family'] : 'inherit';
									// Normalize the stored value for comparison
									$font_family_normalized = trim( $font_family );
									
									$font_options = array(
										'inherit' => 'Inherit from theme',
										'Arial, sans-serif' => 'Arial',
										'Helvetica, Arial, sans-serif' => 'Helvetica',
										'"Segoe UI", Tahoma, Geneva, Verdana, sans-serif' => 'Segoe UI',
										'"Roboto", sans-serif' => 'Roboto',
										'"Open Sans", sans-serif' => 'Open Sans',
										'"Lato", sans-serif' => 'Lato',
										'"Poppins", sans-serif' => 'Poppins',
										'Georgia, serif' => 'Georgia',
										'"Times New Roman", serif' => 'Times New Roman',
										'custom' => '--- Custom Font ---',
									);
									foreach ( $font_options as $value => $label ) {
										$is_selected = ( $font_family_normalized === trim( $value ) ) ? 'selected' : '';
										echo '<option value="' . esc_attr( $value ) . '"' . $is_selected . '>' . esc_html( $label ) . '</option>';
									}
									?>
								</select>
								<p class="description">Font family for all text elements. Select "Custom Font" to enter a custom font name or CSS variable.</p>
							</td>
						</tr>
						<tr id="custom_font_row" style="display: <?php echo ( $font_family_normalized === 'custom' ) ? 'table-row' : 'none'; ?>;">
							<th><label for="custom_font_family">Custom Font</label></th>
							<td>
								<input type="text" id="custom_font_family" name="wc_team_payroll_styling[custom_font_family]" value="<?php echo esc_attr( isset( $styling_settings['custom_font_family'] ) ? $styling_settings['custom_font_family'] : '' ); ?>" placeholder="e.g., 'Courier New', monospace or var(--my-font)" />
								<p class="description">Enter a font family name (e.g., 'Courier New', monospace) or CSS variable (e.g., var(--my-font) or --my-font)</p>
							</td>
						</tr>
						<tr>
							<th><label for="base_font_size">Base Font Size</label></th>
							<td style="display: flex; gap: 10px; align-items: center;">
								<input type="text" id="base_font_size" name="wc_team_payroll_styling[base_font_size]" value="<?php echo esc_attr( isset( $styling_settings['base_font_size'] ) ? $styling_settings['base_font_size'] : 14 ); ?>" placeholder="14 or var(--fs-body)" style="flex: 1; max-width: 150px;" />
								<select id="base_font_size_unit" name="wc_team_payroll_styling[base_font_size_unit]" style="max-width: 100px;">
									<?php
									$base_font_size_unit = isset( $styling_settings['base_font_size_unit'] ) ? $styling_settings['base_font_size_unit'] : 'px';
									$unit_options = array( 'px' => 'px', 'var' => 'CSS Variable' );
									foreach ( $unit_options as $value => $label ) {
										echo '<option value="' . esc_attr( $value ) . '"' . selected( $base_font_size_unit, $value, false ) . '>' . esc_html( $label ) . '</option>';
									}
									?>
								</select>
								<p class="description" style="margin: 0; font-size: 12px;">Base font size for body text. Use px (10-24) or CSS variable (e.g., var(--fs-body) or --fs-body)</p>
							</td>
						</tr>
						<tr>
							<th><label for="heading_font_size">Heading Font Size</label></th>
							<td style="display: flex; gap: 10px; align-items: center;">
								<input type="text" id="heading_font_size" name="wc_team_payroll_styling[heading_font_size]" value="<?php echo esc_attr( isset( $styling_settings['heading_font_size'] ) ? $styling_settings['heading_font_size'] : 24 ); ?>" placeholder="24 or var(--fs-heading)" style="flex: 1; max-width: 150px;" />
								<select id="heading_font_size_unit" name="wc_team_payroll_styling[heading_font_size_unit]" style="max-width: 100px;">
									<?php
									$heading_font_size_unit = isset( $styling_settings['heading_font_size_unit'] ) ? $styling_settings['heading_font_size_unit'] : 'px';
									foreach ( $unit_options as $value => $label ) {
										echo '<option value="' . esc_attr( $value ) . '"' . selected( $heading_font_size_unit, $value, false ) . '>' . esc_html( $label ) . '</option>';
									}
									?>
								</select>
								<p class="description" style="margin: 0; font-size: 12px;">Font size for main headings. Use px (16-48) or CSS variable (e.g., var(--fs-heading) or --fs-heading)</p>
							</td>
						</tr>
					</table>

					<h3>Button Styling</h3>
					<table class="form-table">
						<tr>
							<th><label for="button_background">Button Background</label></th>
							<td>
								<input type="color" id="button_background" name="wc_team_payroll_styling[button_background]" value="<?php echo esc_attr( isset( $styling_settings['button_background'] ) ? $styling_settings['button_background'] : '#0073aa' ); ?>" />
								<p class="description">Background color for primary buttons</p>
							</td>
						</tr>
						<tr>
							<th><label for="button_text_color">Button Text Color</label></th>
							<td>
								<input type="color" id="button_text_color" name="wc_team_payroll_styling[button_text_color]" value="<?php echo esc_attr( isset( $styling_settings['button_text_color'] ) ? $styling_settings['button_text_color'] : '#ffffff' ); ?>" />
								<p class="description">Text color for buttons</p>
							</td>
						</tr>
						<tr>
							<th><label for="button_hover_background">Button Hover Background</label></th>
							<td>
								<input type="color" id="button_hover_background" name="wc_team_payroll_styling[button_hover_background]" value="<?php echo esc_attr( isset( $styling_settings['button_hover_background'] ) ? $styling_settings['button_hover_background'] : '#005a87' ); ?>" />
								<p class="description">Background color for buttons when hovered</p>
							</td>
						</tr>
						<tr>
							<th><label for="button_border_radius">Button Border Radius</label></th>
							<td>
								<input type="number" id="button_border_radius" name="wc_team_payroll_styling[button_border_radius]" value="<?php echo esc_attr( isset( $styling_settings['button_border_radius'] ) ? $styling_settings['button_border_radius'] : 4 ); ?>" min="0" max="20" step="1" />
								<span>px</span>
								<p class="description">Border radius for buttons (0-20px)</p>
							</td>
						</tr>
					</table>

					<h3>Layout Settings</h3>
					<table class="form-table">
						<tr>
							<th><label for="card_border_radius">Card Border Radius</label></th>
							<td>
								<input type="number" id="card_border_radius" name="wc_team_payroll_styling[card_border_radius]" value="<?php echo esc_attr( isset( $styling_settings['card_border_radius'] ) ? $styling_settings['card_border_radius'] : 8 ); ?>" min="0" max="20" step="1" />
								<span>px</span>
								<p class="description">Border radius for cards and info boxes (0-20px)</p>
							</td>
						</tr>
						<tr>
							<th><label for="card_shadow">Card Shadow</label></th>
							<td>
								<select id="card_shadow" name="wc_team_payroll_styling[card_shadow]">
									<?php
									$card_shadow = isset( $styling_settings['card_shadow'] ) ? $styling_settings['card_shadow'] : 'medium';
									$shadow_options = array(
										'none' => 'No Shadow',
										'light' => 'Light Shadow',
										'medium' => 'Medium Shadow',
										'heavy' => 'Heavy Shadow',
									);
									foreach ( $shadow_options as $value => $label ) {
										echo '<option value="' . esc_attr( $value ) . '"' . selected( $card_shadow, $value, false ) . '>' . esc_html( $label ) . '</option>';
									}
									?>
								</select>
								<p class="description">Shadow depth for cards and elements</p>
							</td>
						</tr>
					</table>

					<h3>Custom CSS</h3>
					<p>Add custom CSS rules to further customize the frontend styling. CSS will be automatically injected into the page.</p>
					<textarea id="custom_css" name="wc_team_payroll_styling[custom_css]" rows="12" style="width: 100%; font-family: 'Courier New', monospace; font-size: 13px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;"><?php echo esc_textarea( isset( $styling_settings['custom_css'] ) ? $styling_settings['custom_css'] : '' ); ?></textarea>
					<p class="description">Example: <code>.my-class { color: #333; }</code> - Auto-closing braces will be added when needed.</p>

					<!-- Reset Styling Button -->
					<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
						<button type="button" id="wc-tp-reset-styling-btn" class="button button-secondary" style="padding: 12px 20px; font-size: 14px; border-radius: 4px;">
							<span class="dashicons dashicons-update"></span>
							Reset to Default Styling
						</button>
						<p class="description" style="margin-top: 10px;">Reset all styling options to their default values.</p>
					</div>

					<!-- Floating Preview Button (Bottom Right) -->
					<button type="button" id="wc-tp-preview-btn" class="button button-primary" style="position: fixed; bottom: 30px; right: 30px; padding: 15px 25px; font-size: 14px; border-radius: 50px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; display: none; align-items: center; gap: 8px;">
						👁️ Live Preview
					</button>

					<!-- Live Preview Modal (Only for Styling Tab) -->
					<div id="wc-tp-preview-modal" style="display: none; position: fixed; top: 0; right: 0; width: 450px; height: 100vh; background: white; z-index: 10000; overflow-y: auto; box-shadow: -2px 0 10px rgba(0,0,0,0.15); transition: transform 0.3s ease;">
						<!-- Offcanvas Header -->
						<div style="background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1001;">
							<h2 style="margin: 0; font-size: 18px; font-weight: 600;">Live Preview</h2>
							<button type="button" id="wc-tp-preview-close" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">×</button>
						</div>

						<!-- Offcanvas Content -->
						<div style="padding: 20px; background: #f9f9f9; min-height: 100vh;">
								<!-- Employee Header Preview -->
								<div id="wc-tp-preview-header" style="background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
									<div style="display: flex; align-items: center; gap: 15px;">
										<div style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: bold;">JD</div>
										<div>
											<h3 style="margin: 0 0 5px 0; font-size: 18px; font-weight: 600;">John Doe</h3>
											<p style="margin: 0; font-size: 14px; color: #666;">Employee</p>
										</div>
									</div>
								</div>

								<!-- Cards Preview -->
								<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
									<div id="wc-tp-preview-card-1" style="background: white; border-radius: 8px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
										<h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 8px;">Total Earnings</h3>
										<p style="margin: 0; font-size: 24px; font-weight: bold; color: #0073aa;">৳ 50,000</p>
									</div>
									<div id="wc-tp-preview-card-2" style="background: white; border-radius: 8px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
										<h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 8px;">This Month</h3>
										<p style="margin: 0; font-size: 24px; font-weight: bold; color: #0073aa;">৳ 5,000</p>
									</div>
									<div id="wc-tp-preview-card-3" style="background: white; border-radius: 8px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
										<h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 8px;">Last Paid</h3>
										<p style="margin: 0; font-size: 24px; font-weight: bold; color: #0073aa;">৳ 2,500</p>
									</div>
								</div>

								<!-- Table Preview -->
								<div id="wc-tp-preview-table" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
									<table style="width: 100%; border-collapse: collapse;">
										<thead>
											<tr style="background: #f5f5f5; border-bottom: 2px solid #0073aa;">
												<th style="padding: 12px; text-align: left; font-weight: 600; color: #333; font-size: 13px;">Date</th>
												<th style="padding: 12px; text-align: left; font-weight: 600; color: #333; font-size: 13px;">Amount</th>
												<th style="padding: 12px; text-align: left; font-weight: 600; color: #333; font-size: 13px;">Status</th>
											</tr>
										</thead>
										<tbody>
											<tr style="border-bottom: 1px solid #eee;">
												<td style="padding: 12px; color: #666; font-size: 13px;">2026-04-15</td>
												<td style="padding: 12px; color: #0073aa; font-weight: 600; font-size: 13px;">৳ 2,500</td>
												<td style="padding: 12px;"><span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">Paid</span></td>
											</tr>
											<tr style="border-bottom: 1px solid #eee;">
												<td style="padding: 12px; color: #666; font-size: 13px;">2026-04-08</td>
												<td style="padding: 12px; color: #0073aa; font-weight: 600; font-size: 13px;">৳ 2,500</td>
												<td style="padding: 12px;"><span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">Paid</span></td>
											</tr>
										</tbody>
									</table>
								</div>

								<p style="margin-top: 20px; font-size: 12px; color: #999; text-align: center;">Changes are reflected in real-time. No save needed to see preview.</p>
						</div>
					</div>

					<script>
						jQuery(document).ready(function($) {
							// Live Preview Functionality (Only for Styling Tab)
							const previewBtn = $('#wc-tp-preview-btn');
							const previewModal = $('#wc-tp-preview-modal');
							const previewClose = $('#wc-tp-preview-close');
							const form = $('#wc-tp-settings-form');

							// Open preview offcanvas
							previewBtn.on('click', function(e) {
								e.preventDefault();
								previewModal.css('transform', 'translateX(0)').fadeIn(300);
								updatePreview();
							});

							// Close preview offcanvas
							previewClose.on('click', function(e) {
								e.preventDefault();
								previewModal.css('transform', 'translateX(100%)').fadeOut(300);
							});

							// Update preview in real-time
							form.on('change', 'input, select, textarea', function() {
								updatePreview();
							});

							// Update preview function
							function updatePreview() {
								// Get current styling values
								const primaryColor = $('input[name="wc_team_payroll_styling[primary_color]"]').val() || '#0073aa';
								const textColor = $('input[name="wc_team_payroll_styling[text_color]"]').val() || '#333';
								const backgroundColor = $('input[name="wc_team_payroll_styling[background_color]"]').val() || '#fff';
								const cardBgColor = $('input[name="wc_team_payroll_styling[card_background_color]"]').val() || '#fff';
								const fontFamily = $('select[name="wc_team_payroll_styling[font_family]"]').val() || 'inherit';
								const baseFontSize = $('input[name="wc_team_payroll_styling[base_font_size]"]').val() || '14';
								const cardBorderRadius = $('input[name="wc_team_payroll_styling[card_border_radius]"]').val() || '8';
								const shadowIntensity = $('input[name="wc_team_payroll_styling[shadow_intensity]"]').val() || '0.1';

								// Update header
								$('#wc-tp-preview-header').css({
									'background-color': backgroundColor,
									'font-family': fontFamily === 'inherit' ? 'inherit' : fontFamily,
									'color': textColor
								});

								// Update header profile circle
								$('#wc-tp-preview-header div[style*="border-radius: 50%"]').css({
									'background': `linear-gradient(135deg, ${primaryColor} 0%, ${primaryColor}dd 100%)`
								});

								// Update cards
								$('[id^="wc-tp-preview-card-"]').css({
									'background-color': cardBgColor,
									'border-radius': cardBorderRadius + 'px',
									'font-family': fontFamily === 'inherit' ? 'inherit' : fontFamily,
									'color': textColor,
									'box-shadow': `0 1px 3px rgba(0,0,0,${shadowIntensity})`
								});

								// Update card headings
								$('[id^="wc-tp-preview-card-"] h3').css({
									'color': textColor,
									'border-bottom-color': primaryColor,
									'font-family': fontFamily === 'inherit' ? 'inherit' : fontFamily
								});

								// Update card values
								$('[id^="wc-tp-preview-card-"] p').css({
									'color': primaryColor,
									'font-family': fontFamily === 'inherit' ? 'inherit' : fontFamily,
									'font-size': (parseInt(baseFontSize) + 10) + 'px'
								});

								// Update table
								$('#wc-tp-preview-table').css({
									'background-color': cardBgColor,
									'border-radius': cardBorderRadius + 'px',
									'box-shadow': `0 1px 3px rgba(0,0,0,${shadowIntensity})`
								});

								// Update table header
								$('#wc-tp-preview-table thead tr').css({
									'background-color': backgroundColor,
									'border-bottom-color': primaryColor
								});

								$('#wc-tp-preview-table thead th').css({
									'color': textColor,
									'font-family': fontFamily === 'inherit' ? 'inherit' : fontFamily,
									'font-size': baseFontSize + 'px'
								});

								// Update table body
								$('#wc-tp-preview-table tbody td').css({
									'color': textColor,
									'font-family': fontFamily === 'inherit' ? 'inherit' : fontFamily,
									'font-size': baseFontSize + 'px'
								});

								// Update table body amount cells
								$('#wc-tp-preview-table tbody td:nth-child(2)').css({
									'color': primaryColor
								});
							}

							// Reset Styling Button Handler
							$('#wc-tp-reset-styling-btn').on('click', function(e) {
								e.preventDefault();
								
								if (!confirm('Are you sure you want to reset all styling to default values? This cannot be undone.')) {
									return;
								}

								// Default values
								const defaults = {
									'primary_color': '#0073aa',
									'secondary_color': '#28a745',
									'heading_color': '#333333',
									'text_color': '#495057',
									'link_color': '#0073aa',
									'link_hover_color': '#005a87',
									'background_color': '#ffffff',
									'header_background': '#f8f9fa',
									'header_border_color': '#0073aa',
									'card_background': '#ffffff',
									'card_border_color': '#e0e0e0',
									'card_border_radius': '8',
									'button_background': '#0073aa',
									'button_text_color': '#ffffff',
									'button_border_radius': '4',
									'font_family': 'inherit',
									'base_font_size': '14',
									'custom_css': ''
								};

								// Reset all color inputs
								for (const [key, value] of Object.entries(defaults)) {
									const selector = `input[name="wc_team_payroll_styling[${key}]"], select[name="wc_team_payroll_styling[${key}]"], textarea[name="wc_team_payroll_styling[${key}]"]`;
									$(selector).val(value).trigger('change');
								}

								alert('Styling has been reset to default values. Remember to save your changes.');
								updatePreview();
							});

							// Show preview button when on styling tab
							if ($('#wc-tp-preview-btn').length) {
								$('#wc-tp-preview-btn').show();
							}
						});
					</script>

				<?php endif; ?>

				<?php if ( $current_tab === 'roles' ) : ?>
					<h2>User Roles Management</h2>
					<p>Manage which user roles are considered employees. Default WordPress and WooCommerce roles cannot be removed but can be edited. Only custom roles created by this plugin can be removed.</p>
					<div class="wc-tp-roles-container" id="wc-tp-roles-container">
						<?php $this->render_roles_repeater( $employee_roles ); ?>
					</div>
					<button type="button" class="button button-secondary" id="wc-tp-add-role-btn">+ Add New Role</button>
				<?php endif; ?>

				<?php if ( $current_tab === 'woocommerce' ) : ?>
					<h2>WooCommerce Settings</h2>
					
					<h3>Checkout Field Mapping</h3>
					<p>Configure your checkout field names to integrate with the agent dropdown. These fields can be created using ThemeHigh Checkout Field Editor, WooCommerce Checkout Field Editor, or any other checkout field editor plugin.</p>
					<table class="form-table">
						<tr>
							<th><label for="agent_field_name">Agent Dropdown Reference Field</label></th>
							<td>
								<input type="text" id="agent_field_name" name="wc_team_payroll_checkout_fields[agent_field_name]" value="<?php echo esc_attr( isset( $checkout_fields['agent_field_name'] ) ? $checkout_fields['agent_field_name'] : 'order_other_agent_or_not' ); ?>" />
								<p class="description">The POST field name where the agent dropdown will be inserted (order_agent_name). But this is the reference field from your checkout field editor (e.g., ThemeHigh, WooCommerce Checkout Field Editor, or any custom checkout field). The dynamic agent dropdown will be placed after this field.</p>
							</td>
						</tr>
						<tr>
							<th><label for="agent_user_roles">Employee User Roles</label></th>
							<td>
								<?php
								global $wp_roles;
								$all_roles = isset( $wp_roles ) && isset( $wp_roles->roles ) ? $wp_roles->roles : array();
								$agent_user_roles = isset( $checkout_fields['agent_user_roles'] ) && is_array( $checkout_fields['agent_user_roles'] ) ? $checkout_fields['agent_user_roles'] : array( 'shop_employee', 'shop_manager', 'administrator' );
								?>
								<select id="agent_user_roles" name="wc_team_payroll_checkout_fields[agent_user_roles][]" multiple="multiple" style="width: 100%; min-height: 100px;">
									<?php 
									if ( ! empty( $all_roles ) ) {
										foreach ( $all_roles as $role_key => $role_data ) : 
											if ( is_array( $role_data ) && isset( $role_data['name'] ) ) :
									?>
										<option value="<?php echo esc_attr( $role_key ); ?>" <?php echo in_array( $role_key, $agent_user_roles ) ? 'selected' : ''; ?>>
											<?php echo esc_html( $role_data['name'] ); ?>
										</option>
									<?php 
											endif;
										endforeach;
									}
									?>
								</select>
								<p class="description">Select which user roles can be shown as agents in the checkout dropdown</p>
							</td>
						</tr>
					</table>

					<h3>Product Commission Field</h3>
					<p>Configure the field name used on products to store commission rates.</p>
					<table class="form-table">
						<tr>
							<th><label for="commission_field_name">Commission Rate Field Name</label></th>
							<td>
								<input type="text" id="commission_field_name" name="wc_team_payroll_acf_fields[commission_field_name]" value="<?php echo esc_attr( isset( $acf_fields['commission_field_name'] ) ? $acf_fields['commission_field_name'] : 'team_commission' ); ?>" />
								<p class="description">The meta field name used to store commission rates on products. This plugin will look for this field name on each product to calculate commissions. Default: 'team_commission'. You can change this if you want to use a different field name for your commission rates.</p>
							</td>
						</tr>
						<tr>
							<th><label for="commission_calculation_statuses">Commission Calculation Statuses</label></th>
							<td>
								<?php
								// Get all WooCommerce order statuses
								$all_statuses = wc_get_order_statuses();
								$saved_statuses = isset( $acf_fields['commission_calculation_statuses'] ) ? $acf_fields['commission_calculation_statuses'] : array( 'completed', 'processing' );
								
								// Ensure saved_statuses is an array
								if ( ! is_array( $saved_statuses ) ) {
									$saved_statuses = array( 'completed', 'processing' );
								}
								?>
								<div class="wc-tp-commission-statuses">
									<?php foreach ( $all_statuses as $status_key => $status_label ) : 
										// Remove 'wc-' prefix from status key for cleaner values
										$clean_status = str_replace( 'wc-', '', $status_key );
									?>
										<label class="wc-tp-status-checkbox">
											<input type="checkbox" 
												   name="wc_team_payroll_acf_fields[commission_calculation_statuses][]" 
												   value="<?php echo esc_attr( $clean_status ); ?>"
												   <?php checked( in_array( $clean_status, $saved_statuses ) ); ?> />
											<?php echo esc_html( $status_label ); ?>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="description">Select which order statuses should trigger commission calculation for employees. Only orders with these statuses will show commission and earnings data.</p>
							</td>
						</tr>
					</table>

					<h3>Order Statuses</h3>
					<p>Manage WooCommerce order statuses and configure which ones appear in bulk actions.</p>
					<div class="wc-tp-statuses-container" id="wc-tp-statuses-container">
						<?php $this->render_statuses_repeater( $checkout_fields ); ?>
					</div>
					<button type="button" class="button button-secondary" id="wc-tp-add-status-btn">+ Add New Status</button>
				<?php endif; ?>

				<?php if ( $current_tab === 'documentation' ) : ?>
					<style>
						.wc-tp-docs-container {
							max-width: 1200px;
							margin: 20px 0;
						}
						.wc-tp-docs-header {
							background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
							color: white;
							padding: 40px 30px;
							border-radius: 8px;
							margin-bottom: 30px;
							text-align: center;
						}
						.wc-tp-docs-header h2 {
							margin: 0 0 10px 0;
							font-size: 32px;
							font-weight: 700;
						}
						.wc-tp-docs-header p {
							margin: 0;
							font-size: 16px;
							opacity: 0.9;
						}
						.wc-tp-docs-grid {
							display: grid;
							grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
							gap: 20px;
							margin-bottom: 30px;
						}
						.wc-tp-docs-card {
							background: white;
							border: 1px solid #e0e0e0;
							border-radius: 8px;
							padding: 25px;
							transition: all 0.3s ease;
							cursor: pointer;
						}
						.wc-tp-docs-card:hover {
							box-shadow: 0 4px 12px rgba(0,0,0,0.1);
							transform: translateY(-2px);
							border-color: #667eea;
						}
						.wc-tp-docs-card-icon {
							font-size: 36px;
							margin-bottom: 15px;
							display: block;
						}
						.wc-tp-docs-card h3 {
							margin: 0 0 10px 0;
							font-size: 20px;
							color: #333;
						}
						.wc-tp-docs-card p {
							margin: 0;
							color: #666;
							font-size: 14px;
							line-height: 1.6;
						}
						.wc-tp-docs-section {
							background: white;
							border: 1px solid #e0e0e0;
							border-radius: 8px;
							padding: 30px;
							margin-bottom: 20px;
						}
						.wc-tp-docs-section h3 {
							margin: 0 0 20px 0;
							font-size: 24px;
							color: #333;
							border-bottom: 2px solid #667eea;
							padding-bottom: 10px;
						}
						.wc-tp-docs-section h4 {
							margin: 20px 0 10px 0;
							font-size: 18px;
							color: #444;
						}
						.wc-tp-docs-section ul {
							margin: 10px 0 10px 20px;
							line-height: 1.8;
						}
						.wc-tp-docs-section li {
							margin-bottom: 8px;
						}
						.wc-tp-docs-code {
							background: #f5f5f5;
							border: 1px solid #e0e0e0;
							border-radius: 4px;
							padding: 15px;
							font-family: 'Courier New', monospace;
							font-size: 13px;
							margin: 10px 0;
							overflow-x: auto;
						}
						.wc-tp-docs-note {
							background: #e3f2fd;
							border-left: 4px solid #2196f3;
							padding: 15px;
							margin: 15px 0;
							border-radius: 4px;
						}
						.wc-tp-docs-warning {
							background: #fff3e0;
							border-left: 4px solid #ff9800;
							padding: 15px;
							margin: 15px 0;
							border-radius: 4px;
						}
						.wc-tp-docs-success {
							background: #e8f5e9;
							border-left: 4px solid #4caf50;
							padding: 15px;
							margin: 15px 0;
							border-radius: 4px;
						}
						.wc-tp-docs-toc {
							background: #f9f9f9;
							border: 1px solid #e0e0e0;
							border-radius: 8px;
							padding: 20px;
							margin-bottom: 30px;
						}
						.wc-tp-docs-toc h3 {
							margin: 0 0 15px 0;
							font-size: 18px;
						}
						.wc-tp-docs-toc ul {
							margin: 0;
							padding: 0;
							list-style: none;
						}
						.wc-tp-docs-toc li {
							margin-bottom: 8px;
						}
						.wc-tp-docs-toc a {
							color: #667eea;
							text-decoration: none;
							font-weight: 500;
						}
						.wc-tp-docs-toc a:hover {
							text-decoration: underline;
						}
					</style>

					<div class="wc-tp-docs-container">
						<!-- Header -->
						<div class="wc-tp-docs-header">
							<h2>📖 WooCommerce Team Payroll Documentation</h2>
							<p>Complete guide to setting up and using the Team Payroll & Commission System</p>
						</div>

						<!-- Quick Start Cards -->
						<div class="wc-tp-docs-grid">
							<div class="wc-tp-docs-card" onclick="document.getElementById('getting-started').scrollIntoView({behavior: 'smooth'})">
								<span class="wc-tp-docs-card-icon">🚀</span>
								<h3>Getting Started</h3>
								<p>Learn the basics and set up your first employee with commission tracking</p>
							</div>
							<div class="wc-tp-docs-card" onclick="document.getElementById('commission-setup').scrollIntoView({behavior: 'smooth'})">
								<span class="wc-tp-docs-card-icon">💰</span>
								<h3>Commission Setup</h3>
								<p>Configure commission rates, splits, and calculation rules</p>
							</div>
							<div class="wc-tp-docs-card" onclick="document.getElementById('salary-management').scrollIntoView({behavior: 'smooth'})">
								<span class="wc-tp-docs-card-icon">💵</span>
								<h3>Salary Management</h3>
								<p>Set up fixed salaries, combined pay, and automatic transfers</p>
							</div>
							<div class="wc-tp-docs-card" onclick="document.getElementById('employee-management').scrollIntoView({behavior: 'smooth'})">
								<span class="wc-tp-docs-card-icon">👥</span>
								<h3>Employee Management</h3>
								<p>Add employees, manage payments, and track performance</p>
							</div>
							<div class="wc-tp-docs-card" onclick="document.getElementById('frontend-features').scrollIntoView({behavior: 'smooth'})">
								<span class="wc-tp-docs-card-icon">🎨</span>
								<h3>Frontend Features</h3>
								<p>Customize My Account pages and employee dashboards</p>
							</div>
							<div class="wc-tp-docs-card" onclick="document.getElementById('troubleshooting').scrollIntoView({behavior: 'smooth'})">
								<span class="wc-tp-docs-card-icon">🔧</span>
								<h3>Troubleshooting</h3>
								<p>Common issues and solutions to get you back on track</p>
							</div>
						</div>

						<!-- Table of Contents -->
						<div class="wc-tp-docs-toc">
							<h3>📑 Table of Contents</h3>
							<ul>
								<li><a href="#getting-started">1. Getting Started</a></li>
								<li><a href="#commission-setup">2. Commission Setup</a></li>
								<li><a href="#salary-management">3. Salary Management</a></li>
								<li><a href="#employee-management">4. Employee Management</a></li>
								<li><a href="#checkout-integration">5. Checkout Integration</a></li>
								<li><a href="#frontend-features">6. Frontend Features</a></li>
								<li><a href="#performance-tracking">7. Performance Tracking</a></li>
								<li><a href="#order-editor">8. Order Editor</a></li>
								<li><a href="#troubleshooting">9. Troubleshooting</a></li>
								<li><a href="#hooks-filters">10. Hooks & Filters (Developers)</a></li>
							</ul>
						</div>

						<!-- Getting Started -->
						<div class="wc-tp-docs-section" id="getting-started">
							<h3>🚀 1. Getting Started</h3>
							
							<h4>Initial Setup</h4>
							<p>After installing and activating the plugin, follow these steps:</p>
							<ol>
								<li><strong>Configure General Settings:</strong> Go to <code>Team Payroll > Settings > General</code></li>
								<li><strong>Set Employee ID Prefix:</strong> Choose a prefix for auto-generated employee IDs (e.g., PVVB-EMID)</li>
								<li><strong>Configure Commission:</strong> Go to <code>Settings > Commission</code> and set agent/processor percentages</li>
								<li><strong>Map Fields:</strong> Go to <code>Settings > WooCommerce</code> and configure checkout fields</li>
								<li><strong>Flush Permalinks:</strong> Go to <code>Settings > Permalinks</code> and click "Save Changes"</li>
							</ol>

							<div class="wc-tp-docs-success">
								<strong>✅ Quick Tip:</strong> Start with default settings and adjust as you learn the system. You can always change settings later without affecting existing data.
							</div>

							<h4>System Requirements</h4>
							<ul>
								<li>WordPress 5.0 or higher</li>
								<li>WooCommerce 5.0 or higher (tested up to 10.7.0)</li>
								<li>PHP 7.2 or higher</li>
								<li>Advanced Custom Fields (ACF) or Smart Custom Fields (optional)</li>
							</ul>
						</div>

						<!-- Commission Setup -->
						<div class="wc-tp-docs-section" id="commission-setup">
							<h3>💰 2. Commission Setup</h3>
							
							<h4>Understanding Commission Calculation</h4>
							<p>The plugin calculates commission based on:</p>
							<ul>
								<li><strong>Product Commission Rate:</strong> Set per product (e.g., 10 = 10%)</li>
								<li><strong>Agent/Processor Split:</strong> Configurable percentage split</li>
								<li><strong>Salary Type:</strong> Only commission-eligible employees receive commission</li>
							</ul>

							<h4>Commission Split Logic</h4>
							<div class="wc-tp-docs-code">
<strong>Single User (Agent = Processor):</strong>
- Gets 100% of commission
- No split applied

<strong>Two Different Users:</strong>
- Agent: 70% (configurable)
- Processor: 30% (configurable)

<strong>Salary-Aware:</strong>
- Commission-based: ✅ Receives commission
- Fixed salary: ❌ No commission
- Combined: ✅ Receives commission + base salary
							</div>

							<h4>Setting Product Commission Rates</h4>
							<ol>
								<li>Edit a product in WooCommerce</li>
								<li>Find the "Team Commission" field (or your configured field name)</li>
								<li>Enter commission percentage (e.g., 10 for 10%)</li>
								<li>Save the product</li>
							</ol>

							<div class="wc-tp-docs-note">
								<strong>💡 Note:</strong> Commission is calculated when orders reach configured statuses (default: completed, processing). You can change these in <code>Settings > WooCommerce</code>.
							</div>

							<h4>Commission Calculation Statuses</h4>
							<p>Configure which order statuses trigger commission calculation:</p>
							<ul>
								<li>Go to <code>Settings > WooCommerce</code></li>
								<li>Select statuses (e.g., completed, processing)</li>
								<li>Commission calculates automatically when orders reach these statuses</li>
							</ul>
						</div>

						<!-- Salary Management -->
						<div class="wc-tp-docs-section" id="salary-management">
							<h3>💵 3. Salary Management</h3>
							
							<h4>Three Salary Types</h4>
							<div class="wc-tp-docs-code">
<strong>1. Commission-Based:</strong>
   - Earnings from order commissions only
   - No fixed salary
   - Best for: Sales agents

<strong>2. Fixed Salary:</strong>
   - Regular salary (daily/weekly/monthly)
   - No commission from orders
   - Automatic salary transfers
   - Best for: Support staff, managers

<strong>3. Combined (Base + Commission):</strong>
   - Fixed base salary + order commissions
   - Best of both worlds
   - Best for: Senior sales staff
							</div>

							<h4>Setting Up Employee Salary</h4>
							<ol>
								<li>Go to <code>Team Payroll > Team Members</code></li>
								<li>Click "Manage" next to an employee</li>
								<li>In the "Salary Information" section:
									<ul>
										<li>Select salary type</li>
										<li>Enter amount (if fixed or combined)</li>
										<li>Choose frequency (daily/weekly/monthly)</li>
									</ul>
								</li>
								<li>Click "Update Salary"</li>
							</ol>

							<h4>Automatic Salary Transfers</h4>
							<p>For fixed and combined salary types, the system automatically:</p>
							<ul>
								<li><strong>Daily:</strong> Transfers salary amount every day</li>
								<li><strong>Weekly:</strong> Accumulates daily and transfers on week end (Saturday)</li>
								<li><strong>Monthly:</strong> Accumulates daily and transfers on month end</li>
							</ul>

							<div class="wc-tp-docs-warning">
								<strong>⚠️ Important:</strong> Automatic transfers require WordPress cron to be working. If cron is disabled, use the Salary Debug tool to manually trigger transfers.
							</div>

							<h4>Salary History</h4>
							<p>All salary changes are automatically logged with:</p>
							<ul>
								<li>Date and time of change</li>
								<li>Previous and new salary details</li>
								<li>Who made the change</li>
								<li>Visible to employees in My Account > Salary Details</li>
							</ul>
						</div>

						<!-- Employee Management -->
						<div class="wc-tp-docs-section" id="employee-management">
							<h3>👥 4. Employee Management</h3>
							
							<h4>Adding New Employees</h4>
							<ol>
								<li>Go to <code>Users > Add New</code></li>
								<li>Create user with role: Shop Employee, Shop Manager, or Administrator</li>
								<li>Employee ID is auto-generated (e.g., PVVB-EMID001)</li>
								<li>Go to <code>Team Payroll > Team Members</code></li>
								<li>Click "Manage" to configure salary and details</li>
							</ol>

							<h4>Employee Status</h4>
							<p>Employees can be Active or Inactive:</p>
							<ul>
								<li><strong>Active:</strong> Can login, process orders, earn commission</li>
								<li><strong>Inactive:</strong> Cannot login, shown contact info on login page</li>
							</ul>

							<h4>Adding Payments</h4>
							<ol>
								<li>Go to employee detail page</li>
								<li>Click "Add Payment" button</li>
								<li>Enter:
									<ul>
										<li>Amount</li>
										<li>Date and time</li>
										<li>Payment method</li>
										<li>Notes (optional)</li>
									</ul>
								</li>
								<li>Payment is tracked in employee's payment history</li>
							</ol>

							<h4>Payment Methods</h4>
							<p>Configure employee payment methods:</p>
							<ul>
								<li>Bank account details</li>
								<li>Mobile banking (bKash, Nagad, etc.)</li>
								<li>PayPal, Stripe, etc.</li>
								<li>Visible to employee in My Account > Salary Details</li>
							</ul>
						</div>

						<!-- Checkout Integration -->
						<div class="wc-tp-docs-section" id="checkout-integration">
							<h3>🛒 5. Checkout Integration</h3>
							
							<h4>Agent Dropdown at Checkout</h4>
							<p>The plugin automatically adds an agent selection dropdown at checkout:</p>
							<ul>
								<li>Shows only active employees with configured roles</li>
								<li>Excludes current logged-in user (can't select themselves)</li>
								<li>Displays employee ID + name for easy identification</li>
							</ul>

							<h4>Configuration</h4>
							<ol>
								<li>Go to <code>Settings > WooCommerce</code></li>
								<li>Configure:
									<ul>
										<li><strong>Agent Field Name:</strong> order_agent_name (default)</li>
										<li><strong>Processor Field Name:</strong> _processor_user_id (default)</li>
										<li><strong>Agent User Roles:</strong> Select which roles can be agents</li>
									</ul>
								</li>
							</ol>

							<h4>How It Works</h4>
							<div class="wc-tp-docs-code">
<strong>Scenario 1: Customer selects agent</strong>
- Agent: Selected user
- Processor: Logged-in user (if any)
- Commission split applies

<strong>Scenario 2: No agent selected, user logged in</strong>
- Agent: Logged-in user
- Processor: None
- User gets 100% commission

<strong>Scenario 3: No agent, no logged-in user</strong>
- No commission assignment
							</div>
						</div>

						<!-- Frontend Features -->
						<div class="wc-tp-docs-section" id="frontend-features">
							<h3>🎨 6. Frontend Features</h3>
							
							<h4>My Account Tabs</h4>
							<p>Employees get four new tabs in My Account:</p>
							<ul>
								<li><strong>Salary Details:</strong> View salary info, payment methods, history</li>
								<li><strong>My Earnings:</strong> Monthly earnings breakdown with filters</li>
								<li><strong>My Orders (Commission):</strong> Detailed order list with commission data</li>
								<li><strong>Reports:</strong> Personal performance analytics and charts</li>
							</ul>

							<h4>Customizing Appearance</h4>
							<ol>
								<li>Go to <code>Settings > Frontend Styling</code></li>
								<li>Customize:
									<ul>
										<li>Colors (primary, secondary, text, backgrounds)</li>
										<li>Typography (fonts, sizes)</li>
										<li>Buttons (colors, hover states, border radius)</li>
										<li>Tables (headers, rows, borders)</li>
										<li>Custom CSS</li>
									</ul>
								</li>
								<li>Use Live Preview to see changes in real-time</li>
							</ol>

							<div class="wc-tp-docs-success">
								<strong>✅ Pro Tip:</strong> Use CSS variables from your theme for consistent branding. Example: <code>var(--primary-color)</code>
							</div>
						</div>

						<!-- Performance Tracking -->
						<div class="wc-tp-docs-section" id="performance-tracking">
							<h3>📊 7. Performance Tracking</h3>
							
							<h4>Goals System</h4>
							<p>Set performance goals for employees:</p>
							<ul>
								<li><strong>Order Goals:</strong> Number of orders to complete</li>
								<li><strong>Revenue Goals:</strong> Total order value to achieve</li>
								<li><strong>Commission Goals:</strong> Commission amount to earn</li>
							</ul>

							<h4>Achievements</h4>
							<p>Reward employees for reaching milestones:</p>
							<ul>
								<li><strong>Money Rewards:</strong> Bonus amount added to earnings</li>
								<li><strong>Badge Rewards:</strong> Virtual badges for recognition</li>
								<li>Employees can claim achievements in My Account > Reports</li>
							</ul>

							<h4>Leaderboard</h4>
							<p>Real-time ranking of top performers:</p>
							<ul>
								<li>Visible in admin dashboard</li>
								<li>Can be displayed on frontend (optional)</li>
								<li>Motivates healthy competition</li>
							</ul>
						</div>

						<!-- Order Editor -->
						<div class="wc-tp-docs-section" id="order-editor">
							<h3>✏️ 8. Order Editor</h3>
							
							<h4>Enabling Order Editor</h4>
							<ol>
								<li>Go to <code>Settings > General > Order Editor Settings</code></li>
								<li>Check "Enable Order Editor"</li>
								<li>Select which order statuses allow editing</li>
								<li>Save settings</li>
							</ol>

							<h4>Features</h4>
							<ul>
								<li><strong>Add Products:</strong> Add new products to existing orders</li>
								<li><strong>Edit Items:</strong> Modify quantities, prices, and totals</li>
								<li><strong>Remove Items:</strong> Delete items from orders</li>
								<li><strong>Edit Meta:</strong> Add, update, or delete custom order meta fields</li>
								<li><strong>Recalculate:</strong> Automatically recalculate totals and commissions</li>
								<li><strong>Audit Trail:</strong> All changes logged in order notes</li>
							</ul>

							<div class="wc-tp-docs-warning">
								<strong>⚠️ Note:</strong> Order editor respects existing order editing plugins. If conflicts are detected, it will be automatically disabled.
							</div>
						</div>

						<!-- Troubleshooting -->
						<div class="wc-tp-docs-section" id="troubleshooting">
							<h3>🔧 9. Troubleshooting</h3>
							
							<h4>My Account Tabs Not Showing</h4>
							<ol>
								<li>Go to <code>Settings > Permalinks</code></li>
								<li>Click "Save Changes" (no need to modify anything)</li>
								<li>Clear browser cache</li>
								<li>Check if user has correct role (Shop Employee, Shop Manager, Administrator)</li>
							</ol>

							<h4>Commission Not Calculating</h4>
							<ol>
								<li>Check <code>Settings > Commission</code> - verify percentages are set</li>
								<li>Check <code>Settings > WooCommerce</code> - verify commission calculation statuses</li>
								<li>Ensure products have commission rates set</li>
								<li>Check employee salary type (must be commission-eligible)</li>
								<li>Verify order status is in commission calculation statuses</li>
							</ol>

							<h4>Automatic Salary Not Transferring</h4>
							<ol>
								<li>Check if WordPress cron is working (use WP Crontrol plugin)</li>
								<li>Enable Salary Debug in <code>Settings > Debug</code></li>
								<li>Go to <code>Team Payroll > Salary Debug</code></li>
								<li>Use "Test Accumulation" to manually trigger transfers</li>
								<li>Check employee salary configuration</li>
							</ol>

							<h4>Order Editor Not Appearing</h4>
							<ol>
								<li>Check <code>Settings > General > Order Editor Settings</code></li>
								<li>Ensure order status is in editable statuses list</li>
								<li>Verify no conflicting plugins (check for JavaScript errors in console)</li>
								<li>Try disabling other order editing plugins temporarily</li>
							</ol>

							<h4>Agent Dropdown Not Populating</h4>
							<ol>
								<li>Check <code>Settings > WooCommerce</code> - verify agent user roles</li>
								<li>Ensure users have correct roles assigned</li>
								<li>Check if employees are active (inactive employees are excluded)</li>
								<li>Clear browser cache and reload checkout page</li>
							</ol>
						</div>

						<!-- Hooks & Filters -->
						<div class="wc-tp-docs-section" id="hooks-filters">
							<h3>🔌 10. Hooks & Filters (For Developers)</h3>
							
							<h4>Actions</h4>
							<div class="wc-tp-docs-code">
// Commission calculated
do_action( 'wc_team_payroll_commission_calculated', $order_id, $commission_data );

// Salary changed
do_action( 'wc_tp_salary_changed', $user_id, $salary_data );

// Order edited
do_action( 'wc_team_payroll_order_edited', $order_id );

// Salary transferred
do_action( 'wc_tp_salary_transferred', $user_id, $amount, $type );
							</div>

							<h4>Filters</h4>
							<div class="wc-tp-docs-code">
// Modify commission data
$commission_data = apply_filters( 'wc_tp_commission_data', $commission_data, $order );

// Modify agent percentage
$agent_percentage = apply_filters( 'wc_tp_agent_percentage', $agent_percentage, $order_id );

// Modify processor percentage
$processor_percentage = apply_filters( 'wc_tp_processor_percentage', $processor_percentage, $order_id );

// Customize My Account menu items
$items = apply_filters( 'woocommerce_account_menu_items', $items );
							</div>

							<h4>Example: Custom Commission Logic</h4>
							<div class="wc-tp-docs-code">
add_filter( 'wc_tp_commission_data', function( $commission_data, $order ) {
    // Add custom logic here
    // Example: Add bonus for high-value orders
    if ( $order->get_total() > 1000 ) {
        $commission_data['agent_earnings'] *= 1.1; // 10% bonus
    }
    return $commission_data;
}, 10, 2 );
							</div>

							<h4>Database Schema</h4>
							<div class="wc-tp-docs-code">
<strong>User Meta:</strong>
_wc_tp_fixed_salary          // Boolean: Is fixed salary
_wc_tp_combined_salary       // Boolean: Is combined salary
_wc_tp_salary_amount         // Float: Salary amount
_wc_tp_salary_frequency      // String: daily/weekly/monthly
_wc_tp_salary_history        // Array: Salary change history
_wc_tp_payments              // Array: Payment records
_wc_tp_employee_status       // String: active/inactive
vb_user_id                   // String: Employee ID

<strong>Order Meta:</strong>
_primary_agent_id            // Int: Agent user ID
_processor_user_id           // Int: Processor user ID
_commission_data             // Array: Commission breakdown
							</div>
						</div>

						<!-- Support Section -->
						<div class="wc-tp-docs-section">
							<h3>💬 Need More Help?</h3>
							<p>If you can't find what you're looking for in this documentation:</p>
							<ul>
								<li><strong>GitHub Issues:</strong> <a href="https://github.com/imranduzzlo/woocommerce-team-payroll/issues" target="_blank">Report bugs or request features</a></li>
								<li><strong>Check README:</strong> View the complete README file on GitHub</li>
								<li><strong>Review Code:</strong> All code is well-commented for developers</li>
							</ul>
							
							<div class="wc-tp-docs-success">
								<strong>✅ Plugin Version:</strong> <?php echo esc_html( WC_TEAM_PAYROLL_VERSION ); ?><br>
								<strong>✅ Made with ❤️ by Imran</strong>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $current_tab === 'debug' ) : ?>
					<h2>Debug Tools</h2>
					<p>Configure debugging and testing tools for the plugin.</p>
					
					<h3>Salary Management Debug</h3>
					<table class="form-table">
						<tr>
							<th><label for="enable_salary_debug">Enable Salary Debug Tools</label></th>
							<td>
								<input type="hidden" name="wc_team_payroll_settings[enable_salary_debug]" value="0" />
								<input type="checkbox" id="enable_salary_debug" name="wc_team_payroll_settings[enable_salary_debug]" value="1" <?php checked( isset( $settings['enable_salary_debug'] ) ? $settings['enable_salary_debug'] : 0, 1 ); ?> />
								<p class="description">Enable advanced debugging tools for salary accumulation and testing</p>
								
								<div id="salary-debug-instructions" style="background: #e8f5e9; border: 1px solid #4caf50; border-radius: 4px; padding: 12px; margin-top: 10px; display: none;">
									<strong>✅ Salary Debug Enabled</strong>
									<p style="margin: 8px 0 0 0; font-size: 13px;">
										<strong>Access Debug Tools:</strong> Go to <strong>Team Payroll → Salary Debug</strong> in the admin menu
									</p>
									<p style="margin: 8px 0 0 0; font-size: 13px;">
										<strong>How It Works:</strong>
									</p>
									<ul style="margin: 5px 0 0 20px; font-size: 13px;">
										<li><strong>Test Accumulation:</strong> Simulate daily salary accumulation without waiting for cron jobs</li>
										<li><strong>Get Status:</strong> View current salary configuration, pending accumulation, and earnings</li>
										<li><strong>Manual Cron:</strong> Trigger salary transfer immediately for testing</li>
										<li><strong>Reset Data:</strong> Clear all salary data for fresh testing</li>
									</ul>
									<p style="margin: 8px 0 0 0; font-size: 13px;">
										<strong>Testing Scenarios:</strong>
									</p>
									<ul style="margin: 5px 0 0 20px; font-size: 13px;">
										<li><strong>Daily:</strong> 1 click = salary added immediately</li>
										<li><strong>Weekly:</strong> Clicks accumulate until week end (Saturday by default)</li>
										<li><strong>Monthly:</strong> Clicks accumulate until month end</li>
										<li><strong>Partial Periods:</strong> Debug tool calculates remaining days from today</li>
									</ul>
								</div>
								
								<div id="salary-debug-disabled" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 12px; margin-top: 10px; display: none;">
									<strong>⚠️ Salary Debug Disabled</strong>
									<p style="margin: 8px 0 0 0; font-size: 13px;">Enable this option to access advanced salary debugging and testing tools.</p>
								</div>
							</td>
						</tr>
					</table>

					<h3>Data Management</h3>
					<table class="form-table">
						<tr>
							<th><label>Clear Plugin Data</label></th>
							<td>
								<div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
									<button type="button" class="button button-secondary" id="wc-tp-clear-frontend-data-btn">
										<span class="dashicons dashicons-trash"></span>
										Clear Frontend Data Only
									</button>
									<button type="button" class="button button-secondary" id="wc-tp-clear-all-data-btn">
										<span class="dashicons dashicons-warning"></span>
										Clear All Data
									</button>
								</div>
								<p class="description">
									<strong>Frontend Data:</strong> Clears only user-related data (achievements, goals, baselines, bonuses).<br>
									<strong>All Data:</strong> Clears all configurations, settings, and user data.
								</p>
							</td>
						</tr>
						<tr>
							<th><label for="wc_tp_clear_on_uninstall">Clear Data on Uninstall</label></th>
							<td>
								<input type="hidden" name="wc_team_payroll_settings[clear_on_uninstall]" value="0" />
								<input type="checkbox" id="wc_tp_clear_on_uninstall" name="wc_team_payroll_settings[clear_on_uninstall]" value="1" <?php checked( isset( $settings['clear_on_uninstall'] ) ? $settings['clear_on_uninstall'] : 0, 1 ); ?> />
								<label for="wc_tp_clear_on_uninstall">Automatically clear all plugin data when uninstalling</label>
								<p class="description" style="color: #d63638;"><strong>⚠️ WARNING:</strong> If checked, all plugin data will be permanently deleted when the plugin is uninstalled. If unchecked, data will be preserved and restored if the plugin is reinstalled.</p>
							</td>
						</tr>
					</table>

					<script>
						jQuery(document).ready(function($) {
							const checkbox = $('#enable_salary_debug');
							const enabledDiv = $('#salary-debug-instructions');
							const disabledDiv = $('#salary-debug-disabled');

							// Show/hide instructions based on checkbox state
							function updateInstructions() {
								if (checkbox.is(':checked')) {
									enabledDiv.show();
									disabledDiv.hide();
								} else {
									enabledDiv.hide();
									disabledDiv.show();
								}
							}

							// Initial state
							updateInstructions();

							// Listen for checkbox changes
							checkbox.on('change', function() {
								updateInstructions();
							});

							// Clear Frontend Data button
							$('#wc-tp-clear-frontend-data-btn').on('click', function(e) {
								e.preventDefault();
								showClearDataDialog('frontend');
							});

							// Clear All Data button
							$('#wc-tp-clear-all-data-btn').on('click', function(e) {
								e.preventDefault();
								showClearDataDialog('all');
							});

							// Show clear data confirmation dialog
							function showClearDataDialog(type) {
								const title = type === 'frontend' ? 'Clear Frontend Data Only' : 'Clear All Data';
								const message = type === 'frontend' 
									? 'This will delete all user achievements, goals, baselines, and bonuses. Configurations will be preserved.'
									: 'This will delete ALL plugin data including configurations, settings, and user data. This action cannot be undone!';
								
								// Create custom modal HTML
								const modalHtml = `
									<div id="wc-tp-clear-modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
										<div id="wc-tp-clear-modal" style="background: white; border-radius: 8px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 5px 15px rgba(0,0,0,0.3); z-index: 10000;">
											<h2 style="margin: 0 0 15px 0; color: #333; font-size: 18px;">${title}</h2>
											<p style="margin: 0 0 15px 0; color: #666; line-height: 1.6;">${message}</p>
											<p style="margin: 15px 0; color: #d63638; font-weight: bold;">⚠️ This action cannot be undone!</p>
											<p style="margin: 15px 0 0 0;"><strong>Type "CLEAR" to confirm:</strong></p>
											<input type="text" id="wc-tp-clear-confirmation" placeholder="Type CLEAR here" style="width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" />
											<div style="display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end;">
												<button type="button" id="wc-tp-modal-cancel" class="button button-secondary" style="margin: 0;">Cancel</button>
												<button type="button" id="wc-tp-modal-clear" class="button button-primary" style="margin: 0; background-color: #d63638; border-color: #d63638;">Clear</button>
											</div>
										</div>
									</div>
								`;

								// Remove existing modal if any
								$('#wc-tp-clear-modal-overlay').remove();
								$('body').append(modalHtml);

								// Cancel button
								$('#wc-tp-modal-cancel').on('click', function() {
									$('#wc-tp-clear-modal-overlay').fadeOut(200, function() {
										$(this).remove();
									});
								});

								// Clear button
								$('#wc-tp-modal-clear').on('click', function() {
									const confirmation = $('#wc-tp-clear-confirmation').val();
									if (confirmation !== 'CLEAR') {
										alert('Please type "CLEAR" to confirm.');
										return;
									}

									// Disable button and show loading
									$(this).prop('disabled', true).text('Clearing...');

									// Send AJAX request
									$.ajax({
										url: ajaxurl,
										type: 'POST',
										data: {
											action: type === 'frontend' ? 'wc_tp_clear_frontend_data' : 'wc_tp_clear_all_data',
											nonce: '<?php echo wp_create_nonce( 'wc_team_payroll_settings_nonce' ); ?>',
											confirmation: 'CLEAR'
										},
										success: function(response) {
											if (response.success) {
												alert(response.data.message);
												location.reload();
											} else {
												alert('Error: ' + (response.data.message || 'Unknown error'));
												$('#wc-tp-clear-modal-overlay').fadeOut(200, function() {
													$(this).remove();
												});
											}
										},
										error: function(xhr, status, error) {
											console.error('AJAX error:', error);
											alert('AJAX error occurred: ' + error);
											$('#wc-tp-clear-modal-overlay').fadeOut(200, function() {
												$(this).remove();
											});
										}
									});
								});

								// Focus on input field
								$('#wc-tp-clear-confirmation').focus();
							}
						});
					</script>
				<?php endif; ?>

				<?php if ( $current_tab === 'performance' ) : ?>
					<?php
					// Render performance settings tab
					global $wc_team_payroll_performance_settings;
					if ( $wc_team_payroll_performance_settings && is_object( $wc_team_payroll_performance_settings ) ) {
						$wc_team_payroll_performance_settings->render_performance_tab();
					} else {
						echo '<div class="notice notice-error"><p>' . esc_html__( 'Performance settings class not loaded.', 'wc-team-payroll' ) . '</p></div>';
					}
					?>
				<?php endif; ?>

				<?php 
				// Hide main submit button for performance tab (it has its own save system)
				if ( $current_tab !== 'performance' ) {
					submit_button();
				}
				?>
			</form>
		</div>

		<script>
			jQuery(document).ready(function($) {
				let hasChanges = false;
				let trackChanges = false; // Flag to prevent tracking during initialization
				const form = $('#wc-tp-settings-form');
				const warningDiv = $('#wc-tp-unsaved-warning');
				const originalFormData = form.serialize();

				// Enable change tracking after page initialization (500ms delay)
				setTimeout(function() {
					trackChanges = true;
					console.log('Change tracking enabled');
				}, 500);

				// Track all form changes
				form.on('change', 'input, select, textarea', function() {
					if (trackChanges && !hasChanges) {
						hasChanges = true;
						warningDiv.fadeIn(300);
					}
				});

				// Track checkbox changes
				form.on('change', 'input[type="checkbox"]', function() {
					if (trackChanges && !hasChanges) {
						hasChanges = true;
						warningDiv.fadeIn(300);
					}
				});

				// Track color picker changes
				form.on('change', 'input[type="color"]', function() {
					if (trackChanges && !hasChanges) {
						hasChanges = true;
						warningDiv.fadeIn(300);
					}
				});

				// Reset hasChanges when form is submitted
				form.on('submit', function() {
					hasChanges = false;
					warningDiv.fadeOut(300);
				});

				// Global function to reset unsaved changes (for AJAX saves)
				window.wcTpResetUnsavedChanges = function() {
					console.log('wcTpResetUnsavedChanges called');
					hasChanges = false;
					warningDiv.fadeOut(300);
					console.log('Unsaved changes reset: hasChanges =', hasChanges);
				};

				// Global function to check unsaved changes state (for debugging)
				window.wcTpCheckUnsavedChanges = function() {
					return {
						hasChanges: hasChanges,
						warningVisible: warningDiv.is(':visible'),
						trackChanges: trackChanges
					};
				};

				// Show browser warning when leaving page with unsaved changes
				$(window).on('beforeunload', function() {
					if (hasChanges) {
						return 'You have unsaved changes. Are you sure you want to leave?';
					}
				});

				// Handle tab navigation with unsaved changes
				$('.nav-tab').on('click', function(e) {
					if (hasChanges) {
						const confirmed = confirm('You have unsaved changes. Are you sure you want to leave this tab without saving?');
						if (!confirmed) {
							e.preventDefault();
							return false;
						}
					}
				});

				// Custom CSS Auto-Closing Braces
				const customCssTextarea = $('#custom_css');
				
				customCssTextarea.on('keydown', function(e) {
					// Auto-close opening brace
					if (e.key === '{') {
						e.preventDefault();
						const textarea = this;
						const start = textarea.selectionStart;
						const end = textarea.selectionEnd;
						const text = textarea.value;
						
						// Insert { and }
						textarea.value = text.substring(0, start) + '{ ' + text.substring(end);
						
						// Move cursor inside braces
						textarea.selectionStart = textarea.selectionEnd = start + 2;
						
						// Trigger change event for unsaved changes detection
						$(this).trigger('change');
					}
					
					// Auto-indent on Enter
					if (e.key === 'Enter') {
						const textarea = this;
						const start = textarea.selectionStart;
						const text = textarea.value;
						const lineStart = text.lastIndexOf('\n', start - 1) + 1;
						const lineText = text.substring(lineStart, start);
						const indent = lineText.match(/^\s*/)[0];
						
						// Check if previous line ends with {
						const prevLineEnd = text.lastIndexOf('\n', lineStart - 2);
						const prevLine = text.substring(prevLineEnd + 1, lineStart - 1).trim();
						
						if (prevLine.endsWith('{')) {
							e.preventDefault();
							const newIndent = indent + '\t';
							textarea.value = text.substring(0, start) + '\n' + newIndent + text.substring(start);
							textarea.selectionStart = textarea.selectionEnd = start + newIndent.length + 1;
							$(this).trigger('change');
						}
					}
				});
			});
		</script>

		<script>
			jQuery(document).ready(function($) {
				// Font Family Custom Field Toggle
				const $fontFamilySelect = $('#font_family');
				const $customFontRow = $('#custom_font_row');

				$fontFamilySelect.on('change', function() {
					if ($(this).val() === 'custom') {
						$customFontRow.show();
					} else {
						$customFontRow.hide();
					}
				});
			});
		</script>

		<style>
			.wc-tp-roles-container { margin: 20px 0; }
			.wc-tp-role-item {
				background: #f9f9f9;
				border: 1px solid #ddd;
				border-radius: 4px;
				padding: 15px;
				margin-bottom: 15px;
				position: relative;
			}
			.wc-tp-role-item.wc-tp-default-role {
				background: #fff9e6;
				border-color: #ffc107;
			}
			.wc-tp-role-item-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 10px;
			}
			.wc-tp-role-name {
				font-weight: bold;
				color: #333;
				flex: 1;
			}
			.wc-tp-role-badge {
				display: inline-block;
				background: #ffc107;
				color: #333;
				padding: 3px 8px;
				border-radius: 3px;
				font-size: 11px;
				font-weight: bold;
				margin-right: 10px;
			}
			.wc-tp-role-remove {
				background: #dc3545;
				color: white;
				border: none;
				padding: 5px 10px;
				border-radius: 3px;
				cursor: pointer;
				font-size: 12px;
			}
			.wc-tp-role-remove:hover {
				background: #c82333;
			}
			.wc-tp-role-remove:disabled {
				background: #ccc;
				cursor: not-allowed;
			}
			.wc-tp-role-warning {
				background: #fff3cd;
				border: 1px solid #ffc107;
				border-radius: 3px;
				padding: 10px;
				margin-bottom: 10px;
				font-size: 12px;
				color: #856404;
			}
			
			/* Commission Statuses Checkboxes */
			.wc-tp-commission-statuses {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 10px;
				margin: 10px 0;
			}
			.wc-tp-status-checkbox {
				display: flex;
				align-items: center;
				gap: 8px;
				padding: 8px 12px;
				background: #f9f9f9;
				border: 1px solid #ddd;
				border-radius: 4px;
				cursor: pointer;
				transition: all 0.2s ease;
			}
			.wc-tp-status-checkbox:hover {
				background: #f0f0f0;
				border-color: #0073aa;
			}
			.wc-tp-status-checkbox input[type="checkbox"] {
				margin: 0;
				cursor: pointer;
			}
			.wc-tp-status-checkbox:has(input[type="checkbox"]:checked) {
				background: #e8f5e9;
				border-color: #4caf50;
				font-weight: 600;
				color: #2e7d32;
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				$('#wc-tp-add-role-btn').on('click', function() {
					const container = $('#wc-tp-roles-container');
					const timestamp = Date.now();
					const html = `
						<div class="wc-tp-role-item">
							<div class="wc-tp-role-item-header">
								<div style="flex: 1;">
									<input type="text" name="wc_tp_employee_roles[new_${timestamp}][name]" placeholder="Role name (e.g., shop_employee)" value="" style="font-weight: bold; padding: 6px; border: 1px solid #ddd; border-radius: 3px; width: 100%; max-width: 300px;" />
								</div>
								<button type="button" class="wc-tp-role-remove" style="margin-left: 10px;">Remove</button>
							</div>

							<div class="wc-tp-role-capabilities" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
								<label style="display: block; font-weight: bold; margin-bottom: 8px; font-size: 12px;">Capabilities:</label>
								<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][read]" value="1" />
										<span style="margin-left: 5px;">Read</span>
									</label>
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][edit_posts]" value="1" />
										<span style="margin-left: 5px;">Edit Posts</span>
									</label>
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][delete_posts]" value="1" />
										<span style="margin-left: 5px;">Delete Posts</span>
									</label>
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][publish_posts]" value="1" />
										<span style="margin-left: 5px;">Publish Posts</span>
									</label>
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][manage_options]" value="1" />
										<span style="margin-left: 5px;">Manage Options</span>
									</label>
									<label style="display: flex; align-items: center; font-size: 12px;">
										<input type="checkbox" name="wc_tp_employee_roles[new_${timestamp}][capabilities][manage_woocommerce]" value="1" />
										<span style="margin-left: 5px;">Manage WooCommerce</span>
									</label>
								</div>
							</div>

							<input type="hidden" name="wc_tp_employee_roles[new_${timestamp}][role_key]" value="new_${timestamp}" />
						</div>
					`;
					container.append(html);
				});

				$(document).on('click', '.wc-tp-role-remove', function(e) {
					e.preventDefault();
					if ($(this).prop('disabled')) return;
					$(this).closest('.wc-tp-role-item').remove();
				});
			});
		</script>

		<script>
			jQuery(document).ready(function($) {
				// Status Management
				$('#wc-tp-add-status-btn').on('click', function() {
					const container = $('#wc-tp-statuses-container');
					const timestamp = Date.now();
					const html = `
						<div class="wc-tp-status-item" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 15px; position: relative;">
							<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
								<div style="flex: 1;">
									<div style="margin-bottom: 12px;">
										<label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px;">Status Label</label>
										<input type="text" class="wc-tp-status-label-input" name="wc_team_payroll_checkout_fields[custom_statuses][new_${timestamp}][label]" placeholder="e.g., Custom Processing" value="" style="padding: 6px; border: 1px solid #ddd; border-radius: 3px; width: 100%; max-width: 300px;" />
										<p class="description" style="margin: 5px 0 0 0; font-size: 12px;">Display name for this status</p>
									</div>

									<div style="margin-bottom: 12px;">
										<label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px;">Status Name</label>
										<input type="text" class="wc-tp-status-name-input" name="wc_team_payroll_checkout_fields[custom_statuses][new_${timestamp}][name]" placeholder="e.g., custom_processing" value="" style="padding: 6px; border: 1px solid #ddd; border-radius: 3px; width: 100%; max-width: 300px;" />
										<p class="description" style="margin: 5px 0 0 0; font-size: 12px;">Unique identifier for this status (e.g., processing, completed)</p>
									</div>
								</div>

								<div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">
									<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; white-space: nowrap;">
										<input type="checkbox" name="wc_team_payroll_checkout_fields[custom_statuses][new_${timestamp}][in_bulk_actions]" value="1" checked />
										<span>Include in Bulk Actions</span>
									</label>
									
									<button type="button" class="wc-tp-status-remove" data-status="new_${timestamp}" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 3px; cursor: pointer; font-size: 12px; font-weight: 600;">
										Remove
									</button>
								</div>
							</div>

							<input type="hidden" name="wc_team_payroll_checkout_fields[custom_statuses][new_${timestamp}][is_default]" value="0" />
							<input type="hidden" name="wc_team_payroll_checkout_fields[custom_statuses][new_${timestamp}][status_key]" value="new_${timestamp}" />
						</div>
					`;
					container.append(html);
				});

				$(document).on('click', '.wc-tp-status-remove', function(e) {
					e.preventDefault();
					$(this).closest('.wc-tp-status-item').remove();
				});
			});
		</script>
		<?php
	}

	private function render_roles_repeater( $employee_roles ) {
		global $wp_roles;
		$all_roles = isset( $wp_roles ) && isset( $wp_roles->roles ) ? $wp_roles->roles : array();
		$all_role_keys = ! empty( $all_roles ) ? array_keys( $all_roles ) : array();

		// Define default WordPress and WooCommerce roles that cannot be removed
		$default_roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'shop_manager' );

		$detected_roles = array();
		foreach ( $all_role_keys as $role_key ) {
			if ( ! in_array( $role_key, $employee_roles ) ) {
				$detected_roles[] = $role_key;
			}
		}

		$all_employee_roles = array_unique( array_merge( $employee_roles, $detected_roles ) );

		foreach ( $all_employee_roles as $role ) :
			$role_data = isset( $all_roles[ $role ] ) ? $all_roles[ $role ] : array( 'name' => $role );
			$is_admin = $role === 'administrator';
			$is_default_role = in_array( $role, $default_roles );
			$role_obj = get_role( $role );
			$capabilities = $role_obj ? $role_obj->capabilities : array();
			?>
			<div class="wc-tp-role-item">
				<?php if ( $is_admin ) : ?>
					<div class="wc-tp-role-warning">
						⚠️ Warning: Modifying administrator role permissions can affect site security. Proceed with caution.
					</div>
				<?php endif; ?>

				<?php if ( $is_default_role ) : ?>
					<div class="wc-tp-role-info" style="background-color: #e7f3ff; border-left: 4px solid #0073aa; padding: 10px; margin-bottom: 10px; border-radius: 3px;">
						ℹ️ This is a default WordPress/WooCommerce role and cannot be removed.
					</div>
				<?php endif; ?>

				<div class="wc-tp-role-item-header">
					<div style="flex: 1;">
						<input type="text" class="wc-tp-role-name-input" name="wc_tp_employee_roles[<?php echo esc_attr( $role ); ?>][name]" value="<?php echo esc_attr( $role_data['name'] ); ?>" style="font-weight: bold; padding: 6px; border: 1px solid #ddd; border-radius: 3px; width: 100%; max-width: 300px;" />
					</div>
					<?php if ( ! $is_default_role ) : ?>
						<button type="button" class="wc-tp-role-remove" data-role="<?php echo esc_attr( $role ); ?>">
							Remove
						</button>
					<?php else : ?>
						<span style="color: #999; font-size: 12px; padding: 6px 12px;">Cannot remove</span>
					<?php endif; ?>
				</div>

				<div class="wc-tp-role-capabilities" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
					<label style="display: block; font-weight: bold; margin-bottom: 8px; font-size: 12px;">Capabilities:</label>
					<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
						<?php
						$all_capabilities = array(
							'read' => 'Read',
							'edit_posts' => 'Edit Posts',
							'delete_posts' => 'Delete Posts',
							'publish_posts' => 'Publish Posts',
							'manage_options' => 'Manage Options',
							'manage_woocommerce' => 'Manage WooCommerce',
						);

						foreach ( $all_capabilities as $cap_key => $cap_label ) :
							$is_checked = isset( $capabilities[ $cap_key ] ) && $capabilities[ $cap_key ];
							?>
							<label style="display: flex; align-items: center; font-size: 12px;">
								<input type="checkbox" name="wc_tp_employee_roles[<?php echo esc_attr( $role ); ?>][capabilities][<?php echo esc_attr( $cap_key ); ?>]" value="1" <?php checked( $is_checked, true ); ?> />
								<span style="margin-left: 5px;"><?php echo esc_html( $cap_label ); ?></span>
							</label>
							<?php
						endforeach;
						?>
					</div>
				</div>

				<input type="hidden" name="wc_tp_employee_roles[<?php echo esc_attr( $role ); ?>][role_key]" value="<?php echo esc_attr( $role ); ?>" />
			</div>
			<?php
		endforeach;
	}

	private function render_statuses_repeater( $checkout_fields ) {
		// Get WooCommerce default statuses
		$wc_statuses = array();
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$wc_statuses = wc_get_order_statuses();
		}
		
		// Get custom statuses from settings
		$custom_statuses = isset( $checkout_fields['custom_statuses'] ) && is_array( $checkout_fields['custom_statuses'] ) ? $checkout_fields['custom_statuses'] : array();
		
		// Combine default and custom statuses
		$all_statuses = array();
		
		// Add WooCommerce default statuses
		if ( ! empty( $wc_statuses ) && is_array( $wc_statuses ) ) {
			foreach ( $wc_statuses as $status_key => $status_label ) {
				// Remove 'wc-' prefix from status key
				$clean_key = str_replace( 'wc-', '', $status_key );
				$all_statuses[ $clean_key ] = array(
					'label' => $status_label,
					'name' => $clean_key,
					'is_default' => true,
					'in_bulk_actions' => 1, // Default statuses are always in bulk actions
				);
			}
		}
		
		// Add custom statuses
		if ( is_array( $custom_statuses ) && ! empty( $custom_statuses ) ) {
			foreach ( $custom_statuses as $status_key => $status_data ) {
				if ( is_array( $status_data ) && isset( $status_data['name'] ) && ! empty( $status_data['name'] ) ) {
					$all_statuses[ $status_key ] = array(
						'label' => isset( $status_data['label'] ) ? $status_data['label'] : $status_data['name'],
						'name' => $status_data['name'],
						'is_default' => false,
						'in_bulk_actions' => isset( $status_data['in_bulk_actions'] ) ? $status_data['in_bulk_actions'] : 1,
					);
				}
			}
		}
		
		foreach ( $all_statuses as $status_key => $status_data ) :
			$is_default = isset( $status_data['is_default'] ) ? $status_data['is_default'] : false;
			$in_bulk_actions = isset( $status_data['in_bulk_actions'] ) ? $status_data['in_bulk_actions'] : 0;
			?>
			<div class="wc-tp-status-item" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 15px; position: relative;">
				<?php if ( $is_default ) : ?>
					<div style="background-color: #e7f3ff; border-left: 4px solid #0073aa; padding: 10px; margin-bottom: 10px; border-radius: 3px;">
						ℹ️ This is a default WooCommerce status.
					</div>
				<?php endif; ?>

				<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
					<div style="flex: 1;">
						<div style="margin-bottom: 12px;">
							<label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px;">Status Label</label>
							<input type="text" class="wc-tp-status-label-input" name="wc_team_payroll_checkout_fields[custom_statuses][<?php echo esc_attr( $status_key ); ?>][label]" value="<?php echo esc_attr( $status_data['label'] ); ?>" style="padding: 6px; border: 1px solid #ddd; border-radius: 3px; width: 100%; max-width: 300px;" <?php echo $is_default ? 'readonly' : ''; ?> />
							<p class="description" style="margin: 5px 0 0 0; font-size: 12px;">Display name for this status</p>
						</div>

						<div style="margin-bottom: 12px;">
							<label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px;">Status Name</label>
							<input type="text" class="wc-tp-status-name-input" name="wc_team_payroll_checkout_fields[custom_statuses][<?php echo esc_attr( $status_key ); ?>][name]" value="<?php echo esc_attr( $status_data['name'] ); ?>" style="padding: 6px; border: 1px solid #ddd; border-radius: 3px; width: 100%; max-width: 300px;" <?php echo $is_default ? 'readonly' : ''; ?> />
							<p class="description" style="margin: 5px 0 0 0; font-size: 12px;">Unique identifier for this status (e.g., processing, completed)</p>
						</div>
					</div>

					<div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">
						<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; white-space: nowrap;">
							<input type="checkbox" name="wc_team_payroll_checkout_fields[custom_statuses][<?php echo esc_attr( $status_key ); ?>][in_bulk_actions]" value="1" <?php checked( $in_bulk_actions, 1 ); ?> />
							<span>Include in Bulk Actions</span>
						</label>
						
						<?php if ( ! $is_default ) : ?>
							<button type="button" class="wc-tp-status-remove" data-status="<?php echo esc_attr( $status_key ); ?>" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 3px; cursor: pointer; font-size: 12px; font-weight: 600;">
								Remove
							</button>
						<?php endif; ?>
					</div>
				</div>

				<input type="hidden" name="wc_team_payroll_checkout_fields[custom_statuses][<?php echo esc_attr( $status_key ); ?>][is_default]" value="<?php echo $is_default ? '1' : '0'; ?>" />
				<input type="hidden" name="wc_team_payroll_checkout_fields[custom_statuses][<?php echo esc_attr( $status_key ); ?>][status_key]" value="<?php echo esc_attr( $status_key ); ?>" />
			</div>
			<?php
		endforeach;
	}

	/**
	 * Normalize CSS variable values
	 * Converts --variable-name or var(--variable-name) to var(--variable-name)
	 * Leaves hex colors and other values unchanged
	 */
	private function normalize_css_value( $value ) {
		if ( empty( $value ) ) {
			return $value;
		}

		$value = trim( $value );

		// If it starts with --, convert to var(--...)
		if ( strpos( $value, '--' ) === 0 ) {
			return 'var(' . $value . ')';
		}

		// If it's already var(...), return as is
		if ( strpos( $value, 'var(' ) === 0 ) {
			return $value;
		}

		// Otherwise return as is (hex color, rgb, etc.)
		return $value;
	}

	private function save_settings() {
		// Get existing settings and merge with new ones (don't overwrite)
		$existing_settings = get_option( 'wc_team_payroll_settings', array() );
		$new_settings = isset( $_POST['wc_team_payroll_settings'] ) ? array_map( 'sanitize_text_field', $_POST['wc_team_payroll_settings'] ) : array();
		$settings = array_merge( $existing_settings, $new_settings );
		
		// Get existing checkout_fields and merge with new ones
		$existing_checkout_fields = get_option( 'wc_team_payroll_checkout_fields', array() );
		$checkout_fields = $existing_checkout_fields;
		
		if ( isset( $_POST['wc_team_payroll_checkout_fields'] ) && is_array( $_POST['wc_team_payroll_checkout_fields'] ) ) {
			foreach ( $_POST['wc_team_payroll_checkout_fields'] as $key => $value ) {
				if ( $key === 'custom_statuses' ) {
					// Handle nested custom_statuses array - merge with existing to preserve is_default flag
					$existing_custom_statuses = isset( $checkout_fields['custom_statuses'] ) && is_array( $checkout_fields['custom_statuses'] ) ? $checkout_fields['custom_statuses'] : array();
					$checkout_fields['custom_statuses'] = $existing_custom_statuses; // Start with existing
					
					if ( is_array( $value ) ) {
						foreach ( $value as $status_key => $status_data ) {
							if ( is_array( $status_data ) ) {
								$sanitized_key = sanitize_text_field( $status_key );
								
								// Preserve existing is_default flag if status already exists
								$existing_is_default = isset( $existing_custom_statuses[ $sanitized_key ]['is_default'] ) 
									? $existing_custom_statuses[ $sanitized_key ]['is_default'] 
									: 0;
								
								// Only use form data for is_default if it's a new status (starts with 'new_')
								$is_default_value = $existing_is_default;
								if ( strpos( $status_key, 'new_' ) === 0 ) {
									$is_default_value = isset( $status_data['is_default'] ) ? (int) $status_data['is_default'] : 0;
								}
								
								$checkout_fields['custom_statuses'][ $sanitized_key ] = array(
									'label' => isset( $status_data['label'] ) ? sanitize_text_field( $status_data['label'] ) : '',
									'name' => isset( $status_data['name'] ) ? sanitize_text_field( $status_data['name'] ) : '',
									'is_default' => $is_default_value,
									'in_bulk_actions' => isset( $status_data['in_bulk_actions'] ) ? 1 : 0,
								);
							}
						}
					}
				} else if ( $key === 'agent_user_roles' && is_array( $value ) ) {
					// Handle agent_user_roles array
					$checkout_fields['agent_user_roles'] = array_map( 'sanitize_text_field', $value );
				} else {
					// Handle simple fields
					$checkout_fields[ $key ] = sanitize_text_field( $value );
				}
			}
		}
		
		// Get existing custom fields settings and merge with new ones
		$existing_acf_fields = get_option( 'wc_team_payroll_acf_fields', array() );
		$new_acf_fields = array();
		
		if ( isset( $_POST['wc_team_payroll_acf_fields'] ) ) {
			foreach ( $_POST['wc_team_payroll_acf_fields'] as $key => $value ) {
				if ( is_array( $value ) ) {
					// Handle arrays (like commission_calculation_statuses checkboxes)
					$new_acf_fields[ $key ] = array_map( 'sanitize_text_field', $value );
				} else {
					// Handle single values
					$new_acf_fields[ $key ] = sanitize_text_field( $value );
				}
			}
		}
		
		$acf_fields = array_merge( $existing_acf_fields, $new_acf_fields );
		
		// Get existing styling settings and merge with new ones
		$existing_styling_settings = get_option( 'wc_team_payroll_styling', array() );
		$new_styling_settings = isset( $_POST['wc_team_payroll_styling'] ) ? array_map( 'sanitize_text_field', $_POST['wc_team_payroll_styling'] ) : array();
		$styling_settings = array_merge( $existing_styling_settings, $new_styling_settings );
		
		// Normalize CSS variable values in styling settings (convert --var to var(--var))
		$color_fields = array(
			'primary_color',
			'secondary_color',
			'text_color',
			'heading_color',
			'background_color',
			'card_background_color',
			'border_color',
			'header_background',
			'header_border_color',
			'link_color',
			'link_hover_color',
			'button_background',
			'button_text_color',
		);
		
		foreach ( $color_fields as $field ) {
			if ( isset( $styling_settings[ $field ] ) ) {
				$styling_settings[ $field ] = $this->normalize_css_value( $styling_settings[ $field ] );
			}
		}

		// Normalize font family CSS variables
		if ( isset( $styling_settings['custom_font_family'] ) ) {
			$styling_settings['custom_font_family'] = $this->normalize_css_value( $styling_settings['custom_font_family'] );
		}

		// Normalize font size CSS variables
		if ( isset( $styling_settings['base_font_size_unit'] ) && $styling_settings['base_font_size_unit'] === 'var' ) {
			if ( isset( $styling_settings['base_font_size'] ) ) {
				$styling_settings['base_font_size'] = $this->normalize_css_value( $styling_settings['base_font_size'] );
			}
		}

		if ( isset( $styling_settings['heading_font_size_unit'] ) && $styling_settings['heading_font_size_unit'] === 'var' ) {
			if ( isset( $styling_settings['heading_font_size'] ) ) {
				$styling_settings['heading_font_size'] = $this->normalize_css_value( $styling_settings['heading_font_size'] );
			}
		}

		update_option( 'wc_team_payroll_settings', $settings );
		update_option( 'wc_team_payroll_checkout_fields', $checkout_fields );
		update_option( 'wc_team_payroll_acf_fields', $acf_fields );
		update_option( 'wc_team_payroll_styling', $styling_settings );

		// Save order editor settings
		$order_editor_settings = array();
		if ( isset( $_POST['wc_team_payroll_order_editor'] ) && is_array( $_POST['wc_team_payroll_order_editor'] ) ) {
			$order_editor_settings['enabled'] = isset( $_POST['wc_team_payroll_order_editor']['enabled'] ) ? '1' : '0';
			
			if ( isset( $_POST['wc_team_payroll_order_editor']['editable_statuses'] ) && is_array( $_POST['wc_team_payroll_order_editor']['editable_statuses'] ) ) {
				$order_editor_settings['editable_statuses'] = array_map( 'sanitize_text_field', $_POST['wc_team_payroll_order_editor']['editable_statuses'] );
			} else {
				$order_editor_settings['editable_statuses'] = array();
			}
		} else {
			// If not set, disable it
			$order_editor_settings['enabled'] = '0';
			$order_editor_settings['editable_statuses'] = array();
		}
		update_option( 'wc_team_payroll_order_editor', $order_editor_settings );

		if ( isset( $_POST['wc_tp_user_id_prefix'] ) ) {
			$prefix = sanitize_text_field( $_POST['wc_tp_user_id_prefix'] );
			update_option( 'wc_tp_user_id_prefix', $prefix );
		}

		// Save contact information
		if ( isset( $_POST['contact_whatsapp'] ) ) {
			$whatsapp = sanitize_text_field( $_POST['contact_whatsapp'] );
			update_option( 'wc_team_payroll_contact_whatsapp', $whatsapp );
		}

		if ( isset( $_POST['contact_email'] ) ) {
			$email = sanitize_email( $_POST['contact_email'] );
			update_option( 'wc_team_payroll_contact_email', $email );
		}

		if ( isset( $_POST['contact_telegram'] ) ) {
			$telegram = sanitize_text_field( $_POST['contact_telegram'] );
			update_option( 'wc_team_payroll_contact_telegram', $telegram );
		}

		// Save employee roles with capabilities
		if ( isset( $_POST['wc_tp_employee_roles'] ) && is_array( $_POST['wc_tp_employee_roles'] ) ) {
			$employee_roles = array();
			
			foreach ( $_POST['wc_tp_employee_roles'] as $role_key => $role_data ) {
				$role_key = sanitize_text_field( $role_key );
				$role_name = isset( $role_data['name'] ) ? sanitize_text_field( $role_data['name'] ) : $role_key;
				$capabilities = isset( $role_data['capabilities'] ) ? array_keys( $role_data['capabilities'] ) : array();

				$employee_roles[] = $role_key;

				// Update role capabilities
				$role_obj = get_role( $role_key );
				if ( $role_obj ) {
					// Remove all capabilities first
					$all_capabilities = array( 'read', 'edit_posts', 'delete_posts', 'publish_posts', 'manage_options', 'manage_woocommerce' );
					foreach ( $all_capabilities as $cap ) {
						$role_obj->remove_cap( $cap );
					}

					// Add selected capabilities
					foreach ( $capabilities as $cap ) {
						$cap = sanitize_text_field( $cap );
						$role_obj->add_cap( $cap );
					}

					// Update role name
					if ( $role_name !== $role_key ) {
						global $wp_roles;
						$wp_roles->roles[ $role_key ]['name'] = $role_name;
						update_option( $wp_roles->role_key, $wp_roles->roles );
					}
				}
			}

			update_option( 'wc_tp_employee_roles', $employee_roles );
		}
	}
}
