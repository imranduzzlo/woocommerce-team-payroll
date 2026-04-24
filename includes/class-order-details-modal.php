<?php
/**
 * Order Details Modal Handler
 *
 * @package WooCommerce Team Payroll
 * @since 1.7.26
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_TP_Order_Details_Modal {

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
		if ( ! $agent_id ) {
			$agent_id = $order->get_meta( '_wc_tp_agent_id' );
		}
		
		$processor_id = $order->get_meta( '_processor_user_id' );
		if ( ! $processor_id ) {
			$processor_id = $order->get_meta( '_wc_tp_processor_id' );
		}
		
		$commission_data = $order->get_meta( '_commission_data' );

		// Check if user is involved in this order
		if ( intval( $agent_id ) !== intval( $user_id ) && intval( $processor_id ) !== intval( $user_id ) ) {
			wp_send_json_error( __( 'Unauthorized', 'wc-team-payroll' ) );
		}

		$agent = get_user_by( 'ID', $agent_id );
		$processor = get_user_by( 'ID', $processor_id );

		ob_start();
		?>
		<div class="order-details-wrapper">
			<!-- Tab Navigation -->
			<div class="order-detail-tabs">
				<button class="tab-button active" data-tab="order-info-tab">
					<i class="ph ph-info"></i>
					<?php esc_html_e( 'Order Information', 'wc-team-payroll' ); ?>
				</button>
				<button class="tab-button" data-tab="order-changelog-tab">
					<i class="ph ph-clock-clockwise"></i>
					<?php esc_html_e( 'Order Changelog', 'wc-team-payroll' ); ?>
				</button>
			</div>

			<!-- Tab Content: Order Information -->
			<div id="order-info-tab" class="order-tab-content active">
				<div class="order-details-content">
					
					<!-- Order Header Section -->
					<div class="order-section order-header-section">
						<div class="info-grid">
							<div class="info-item">
								<span class="label"><i class="ph ph-hash"></i> <?php esc_html_e( 'Order ID', 'wc-team-payroll' ); ?></span>
								<span class="value">#<?php echo esc_html( $order_id ); ?></span>
							</div>
							<div class="info-item">
								<span class="label"><i class="ph ph-calendar"></i> <?php esc_html_e( 'Date Created', 'wc-team-payroll' ); ?></span>
								<span class="value"><?php echo esc_html( $order->get_date_created()->format( 'F j, Y g:i A' ) ); ?></span>
							</div>
							<div class="info-item">
								<span class="label"><i class="ph ph-tag"></i> <?php esc_html_e( 'Status', 'wc-team-payroll' ); ?></span>
								<span class="value">
									<span class="status-badge status-<?php echo esc_attr( $order->get_status() ); ?>">
										<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
									</span>
								</span>
							</div>
							<div class="info-item">
								<span class="label"><i class="ph ph-currency-circle-dollar"></i> <?php esc_html_e( 'Order Total', 'wc-team-payroll' ); ?></span>
								<span class="value amount"><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></span>
							</div>
						</div>
					</div>

					<!-- Customer Information -->
					<div class="order-section">
						<h4><i class="ph ph-user"></i> <?php esc_html_e( 'Customer Information', 'wc-team-payroll' ); ?></h4>
						<div class="section-content">
							<div class="info-grid">
								<div class="info-item">
									<span class="label"><?php esc_html_e( 'Full Name', 'wc-team-payroll' ); ?></span>
									<span class="value"><?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></span>
								</div>
								<div class="info-item">
									<span class="label"><?php esc_html_e( 'Email Address', 'wc-team-payroll' ); ?></span>
									<span class="value"><?php echo esc_html( $order->get_billing_email() ); ?></span>
								</div>
								<?php if ( $order->get_billing_phone() ) : ?>
									<div class="info-item">
										<span class="label"><?php esc_html_e( 'Phone Number', 'wc-team-payroll' ); ?></span>
										<span class="value"><?php echo esc_html( $order->get_billing_phone() ); ?></span>
									</div>
								<?php endif; ?>
								<?php if ( $order->get_billing_address_1() ) : ?>
									<div class="info-item" style="grid-column: 1 / -1;">
										<span class="label"><?php esc_html_e( 'Billing Address', 'wc-team-payroll' ); ?></span>
										<div class="address-block">
											<?php echo esc_html( $order->get_billing_address_1() ); ?><br>
											<?php if ( $order->get_billing_address_2() ) echo esc_html( $order->get_billing_address_2() ) . '<br>'; ?>
											<?php echo esc_html( $order->get_billing_city() ); ?>, <?php echo esc_html( $order->get_billing_state() ); ?> <?php echo esc_html( $order->get_billing_postcode() ); ?><br>
											<?php echo esc_html( WC()->countries->countries[ $order->get_billing_country() ] ?? $order->get_billing_country() ); ?>
										</div>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- Order Items -->
					<div class="order-section">
						<h4><i class="ph ph-shopping-cart"></i> <?php esc_html_e( 'Order Items', 'wc-team-payroll' ); ?></h4>
						<div class="section-content">
							<table class="order-items-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Product', 'wc-team-payroll' ); ?></th>
										<th><?php esc_html_e( 'Quantity', 'wc-team-payroll' ); ?></th>
										<th><?php esc_html_e( 'Price', 'wc-team-payroll' ); ?></th>
										<th><?php esc_html_e( 'Total', 'wc-team-payroll' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
										<tr>
											<td>
												<strong><?php echo esc_html( $item->get_name() ); ?></strong>
												<?php
												$metadata = $item->get_formatted_meta_data();
												if ( ! empty( $metadata ) ) :
													?>
													<div class="item-meta">
														<?php foreach ( $metadata as $meta ) : ?>
															<div><?php echo wp_kses_post( $meta->display_key ); ?>: <?php echo wp_kses_post( $meta->display_value ); ?></div>
														<?php endforeach; ?>
													</div>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( $item->get_quantity() ); ?></td>
											<td><?php echo wp_kses_post( wc_price( $item->get_subtotal() / $item->get_quantity() ) ); ?></td>
											<td><?php echo wp_kses_post( wc_price( $item->get_total() ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
								<tfoot>
									<tr>
										<th colspan="3"><?php esc_html_e( 'Subtotal', 'wc-team-payroll' ); ?></th>
										<td><?php echo wp_kses_post( wc_price( $order->get_subtotal() ) ); ?></td>
									</tr>
									<?php if ( $order->get_total_discount() > 0 ) : ?>
										<tr>
											<th colspan="3"><?php esc_html_e( 'Discount', 'wc-team-payroll' ); ?></th>
											<td>-<?php echo wp_kses_post( wc_price( $order->get_total_discount() ) ); ?></td>
										</tr>
									<?php endif; ?>
									<?php if ( $order->get_shipping_total() > 0 ) : ?>
										<tr>
											<th colspan="3"><?php esc_html_e( 'Shipping', 'wc-team-payroll' ); ?></th>
											<td><?php echo wp_kses_post( wc_price( $order->get_shipping_total() ) ); ?></td>
										</tr>
									<?php endif; ?>
									<?php if ( $order->get_total_tax() > 0 ) : ?>
										<tr>
											<th colspan="3"><?php esc_html_e( 'Tax', 'wc-team-payroll' ); ?></th>
											<td><?php echo wp_kses_post( wc_price( $order->get_total_tax() ) ); ?></td>
										</tr>
									<?php endif; ?>
									<tr class="order-total-row">
										<th colspan="3"><?php esc_html_e( 'Order Total', 'wc-team-payroll' ); ?></th>
										<td><?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?></td>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>

					<!-- Team Assignment -->
					<div class="order-section">
						<h4><i class="ph ph-users-three"></i> <?php esc_html_e( 'Team Assignment', 'wc-team-payroll' ); ?></h4>
						<div class="section-content">
							<div class="team-grid">
								<div class="team-member">
									<span class="role-label"><?php esc_html_e( 'Agent', 'wc-team-payroll' ); ?></span>
									<span class="member-info">
										<?php if ( $agent ) : ?>
											<?php echo esc_html( $agent->display_name ); ?>
											<?php if ( intval( $agent_id ) === intval( $user_id ) ) : ?>
												<span class="you-badge"><?php esc_html_e( 'You', 'wc-team-payroll' ); ?></span>
											<?php endif; ?>
										<?php else : ?>
											<span style="color: #919EAB;"><?php esc_html_e( 'Not assigned', 'wc-team-payroll' ); ?></span>
										<?php endif; ?>
									</span>
								</div>
								<div class="team-member">
									<span class="role-label"><?php esc_html_e( 'Processor', 'wc-team-payroll' ); ?></span>
									<span class="member-info">
										<?php if ( $processor ) : ?>
											<?php echo esc_html( $processor->display_name ); ?>
											<?php if ( intval( $processor_id ) === intval( $user_id ) ) : ?>
												<span class="you-badge"><?php esc_html_e( 'You', 'wc-team-payroll' ); ?></span>
											<?php endif; ?>
										<?php else : ?>
											<span style="color: #919EAB;"><?php esc_html_e( 'Not assigned', 'wc-team-payroll' ); ?></span>
										<?php endif; ?>
									</span>
								</div>
							</div>
						</div>
					</div>

					<!-- Commission Breakdown -->
					<?php if ( ! empty( $commission_data ) ) : ?>
						<div class="order-section">
							<h4><i class="ph ph-coins"></i> <?php esc_html_e( 'Commission Breakdown', 'wc-team-payroll' ); ?></h4>
							<div class="section-content">
								<div class="commission-grid">
									<div class="commission-item total">
										<span class="label"><?php esc_html_e( 'Total Commission', 'wc-team-payroll' ); ?></span>
										<span class="value"><?php echo wp_kses_post( wc_price( $commission_data['total_commission'] ) ); ?></span>
									</div>
									<div class="commission-item">
										<span class="label"><?php esc_html_e( 'Agent Earnings', 'wc-team-payroll' ); ?></span>
										<span class="value"><?php echo wp_kses_post( wc_price( $commission_data['agent_earnings'] ) ); ?></span>
									</div>
									<div class="commission-item">
										<span class="label"><?php esc_html_e( 'Processor Earnings', 'wc-team-payroll' ); ?></span>
										<span class="value"><?php echo wp_kses_post( wc_price( $commission_data['processor_earnings'] ) ); ?></span>
									</div>
									<?php if ( ! empty( $commission_data['extra_earnings'] ) ) : ?>
										<div class="commission-item extra-earnings-section">
											<span class="label"><?php esc_html_e( 'Extra Earnings', 'wc-team-payroll' ); ?></span>
											<div class="extra-earnings-list">
												<?php foreach ( $commission_data['extra_earnings'] as $extra ) : ?>
													<div class="extra-earning-item">
														<span><?php echo esc_html( $extra['label'] ); ?></span>
														<span><?php echo wp_kses_post( wc_price( $extra['amount'] ) ); ?></span>
													</div>
												<?php endforeach; ?>
											</div>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endif; ?>

				</div>
			</div>

			<!-- Tab Content: Order Changelog -->
			<div id="order-changelog-tab" class="order-tab-content">
				<div class="order-changelog-content">
					<div class="changelog-header">
						<p class="changelog-description">
							<i class="ph ph-info"></i>
							<?php esc_html_e( 'Complete history of all changes made to this order including status updates, item modifications, shipping changes, and notes.', 'wc-team-payroll' ); ?>
						</p>
					</div>
					
					<div class="changelog-timeline">
						<?php
						// Get comprehensive order changelog
						$changelog = self::get_order_comprehensive_changelog( $order );
						
						if ( ! empty( $changelog ) ) :
							foreach ( $changelog as $entry ) :
								?>
								<div class="changelog-entry <?php echo esc_attr( $entry['type'] ); ?>-entry">
									<div class="changelog-icon">
										<i class="ph <?php echo esc_attr( $entry['icon'] ); ?>"></i>
									</div>
									<div class="changelog-details">
										<div class="changelog-meta">
											<span class="changelog-date"><?php echo esc_html( $entry['date'] ); ?></span>
											<span class="changelog-type-badge <?php echo esc_attr( $entry['type'] ); ?>">
												<?php echo esc_html( $entry['type_label'] ); ?>
											</span>
										</div>
										<div class="changelog-content">
											<?php echo wp_kses_post( $entry['content'] ); ?>
										</div>
										<?php if ( ! empty( $entry['author'] ) ) : ?>
											<div class="changelog-author">
												<i class="ph ph-user"></i> <?php echo esc_html( $entry['author'] ); ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="no-changelog">
								<i class="ph ph-clock-clockwise"></i>
								<p><?php esc_html_e( 'No changelog entries found for this order.', 'wc-team-payroll' ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Get comprehensive order changelog including all changes
	 */
	private static function get_order_comprehensive_changelog( $order ) {
		$changelog = array();
		$order_id = $order->get_id();

		// Get order notes
		$notes = wc_get_order_notes( array( 'order_id' => $order_id ) );
		
		foreach ( $notes as $note ) {
			$content = $note->content;
			$type = 'system';
			$icon = 'ph-gear';
			$type_label = __( 'System Update', 'wc-team-payroll' );
			
			// Determine entry type based on content
			if ( $note->customer_note ) {
				$type = 'customer';
				$icon = 'ph-chat-circle-text';
				$type_label = __( 'Customer Note', 'wc-team-payroll' );
			} elseif ( strpos( $content, 'status changed' ) !== false || strpos( $content, 'Order status' ) !== false ) {
				$type = 'status';
				$icon = 'ph-arrow-circle-right';
				$type_label = __( 'Status Change', 'wc-team-payroll' );
			} elseif ( strpos( $content, 'Item' ) !== false || strpos( $content, 'product' ) !== false || strpos( $content, 'quantity' ) !== false ) {
				$type = 'item';
				$icon = 'ph-shopping-cart';
				$type_label = __( 'Item Update', 'wc-team-payroll' );
			} elseif ( strpos( $content, 'shipping' ) !== false || strpos( $content, 'Shipping' ) !== false ) {
				$type = 'shipping';
				$icon = 'ph-truck';
				$type_label = __( 'Shipping Update', 'wc-team-payroll' );
			} elseif ( strpos( $content, 'payment' ) !== false || strpos( $content, 'Payment' ) !== false || strpos( $content, 'paid' ) !== false ) {
				$type = 'payment';
				$icon = 'ph-credit-card';
				$type_label = __( 'Payment Update', 'wc-team-payroll' );
			} elseif ( strpos( $content, 'refund' ) !== false || strpos( $content, 'Refund' ) !== false ) {
				$type = 'refund';
				$icon = 'ph-arrow-counter-clockwise';
				$type_label = __( 'Refund', 'wc-team-payroll' );
			}
			
			$author_name = '';
			if ( $note->added_by ) {
				$author = get_user_by( 'login', $note->added_by );
				if ( $author ) {
					$author_name = sprintf( __( 'By: %s', 'wc-team-payroll' ), $author->display_name );
				}
			}
			
			$changelog[] = array(
				'date' => date_i18n( 'F j, Y g:i A', strtotime( $note->date_created ) ),
				'type' => $type,
				'icon' => $icon,
				'type_label' => $type_label,
				'content' => wpautop( $content ),
				'author' => $author_name,
				'timestamp' => strtotime( $note->date_created ),
			);
		}
		
		// Sort by timestamp (newest first)
		usort( $changelog, function( $a, $b ) {
			return $b['timestamp'] - $a['timestamp'];
		} );
		
		return $changelog;
	}
}
