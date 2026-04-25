<?php
/**
 * Location Formatter
 * Converts state and thana codes to readable labels
 * Supports BD Thana Add plugin and WooCommerce states
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Location_Formatter {

	private static $instance = null;
	private $thana_data = null;
	private $state_data = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_data();
		$this->init_hooks();
	}

	/**
	 * Load thana and state data
	 */
	private function load_data() {
		// Load thana data from BD Thana Add plugin
		$thana_file = WP_PLUGIN_DIR . '/bd-thana-add/data/thana.json';
		if ( file_exists( $thana_file ) ) {
			$json = file_get_contents( $thana_file );
			$this->thana_data = json_decode( $json, true );
		}

		// Load state data from WooCommerce
		$states_file = WP_PLUGIN_DIR . '/woocommerce/i18n/states.php';
		if ( file_exists( $states_file ) ) {
			$states = include $states_file;
			if ( isset( $states['BD'] ) ) {
				$this->state_data = $states['BD'];
			}
		}
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Format state in order display
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'format_order_address' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'format_order_address' ), 10, 2 );
		
		// Format state in admin order page
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'format_admin_address_fields' ) );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'format_admin_address_fields' ) );
		
		// Format in order emails
		add_filter( 'woocommerce_email_order_meta_fields', array( $this, 'format_email_fields' ), 10, 3 );
		
		// Format in My Account
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'format_my_account_address' ), 10, 3 );
	}

	/**
	 * Get state label from code
	 */
	public function get_state_label( $state_code ) {
		if ( empty( $state_code ) ) {
			return $state_code;
		}

		// If state data is loaded, return label
		if ( $this->state_data && isset( $this->state_data[ $state_code ] ) ) {
			return $this->state_data[ $state_code ];
		}

		// Return code if no label found
		return $state_code;
	}

	/**
	 * Get thana label from code
	 */
	public function get_thana_label( $thana_code ) {
		if ( empty( $thana_code ) ) {
			return $thana_code;
		}

		// If thana data is loaded, search for label
		if ( $this->thana_data && is_array( $this->thana_data ) ) {
			foreach ( $this->thana_data as $thana ) {
				if ( isset( $thana['value'] ) && $thana['value'] === $thana_code ) {
					return $thana['label'] ?? $thana_code;
				}
			}
		}

		// Return code if no label found
		return $thana_code;
	}

	/**
	 * Format complete address with state and thana labels
	 */
	public function format_address( $address ) {
		if ( ! is_array( $address ) ) {
			return $address;
		}

		// Format state
		if ( isset( $address['state'] ) && ! empty( $address['state'] ) ) {
			$address['state'] = $this->get_state_label( $address['state'] );
		}

		// Format thana (city field is often used for thana)
		if ( isset( $address['city'] ) && ! empty( $address['city'] ) ) {
			// Check if it's a thana code (format: BD-XX-XX)
			if ( preg_match( '/^BD-\d+-\d+$/', $address['city'] ) ) {
				$address['city'] = $this->get_thana_label( $address['city'] );
			}
		}

		return $address;
	}

	/**
	 * Format order address
	 */
	public function format_order_address( $address, $order ) {
		return $this->format_address( $address );
	}

	/**
	 * Format admin address fields
	 */
	public function format_admin_address_fields( $fields ) {
		if ( ! is_array( $fields ) ) {
			return $fields;
		}

		// Format state field
		if ( isset( $fields['state'] ) && isset( $fields['state']['value'] ) ) {
			$state_code = $fields['state']['value'];
			$state_label = $this->get_state_label( $state_code );
			
			// Add label as description or modify display
			if ( $state_label !== $state_code ) {
				$fields['state']['label'] = $fields['state']['label'] . ' (' . $state_label . ')';
			}
		}

		// Format city/thana field
		if ( isset( $fields['city'] ) && isset( $fields['city']['value'] ) ) {
			$city_code = $fields['city']['value'];
			if ( preg_match( '/^BD-\d+-\d+$/', $city_code ) ) {
				$city_label = $this->get_thana_label( $city_code );
				if ( $city_label !== $city_code ) {
					$fields['city']['label'] = $fields['city']['label'] . ' (' . $city_label . ')';
				}
			}
		}

		return $fields;
	}

	/**
	 * Format email fields
	 */
	public function format_email_fields( $fields, $sent_to_admin, $order ) {
		// This is for custom fields in emails
		return $fields;
	}

	/**
	 * Format My Account address
	 */
	public function format_my_account_address( $address, $customer_id, $address_type ) {
		return $this->format_address( $address );
	}

	/**
	 * Format state/thana in any text
	 * Useful for custom displays
	 */
	public function format_text( $text ) {
		if ( empty( $text ) ) {
			return $text;
		}

		// Replace state codes (BD-XX)
		$text = preg_replace_callback( '/\bBD-(\d+)\b/', function( $matches ) {
			$state_code = $matches[0];
			$state_label = $this->get_state_label( $state_code );
			return $state_label !== $state_code ? $state_label : $state_code;
		}, $text );

		// Replace thana codes (BD-XX-XX)
		$text = preg_replace_callback( '/\bBD-(\d+)-(\d+)\b/', function( $matches ) {
			$thana_code = $matches[0];
			$thana_label = $this->get_thana_label( $thana_code );
			return $thana_label !== $thana_code ? $thana_label : $thana_code;
		}, $text );

		return $text;
	}

	/**
	 * Get formatted location string
	 * Combines state and thana into readable format
	 */
	public function get_formatted_location( $state_code, $thana_code = '' ) {
		$parts = array();

		if ( ! empty( $thana_code ) ) {
			$thana_label = $this->get_thana_label( $thana_code );
			if ( $thana_label !== $thana_code ) {
				$parts[] = $thana_label;
			} else {
				$parts[] = $thana_code;
			}
		}

		if ( ! empty( $state_code ) ) {
			$state_label = $this->get_state_label( $state_code );
			if ( $state_label !== $state_code ) {
				$parts[] = $state_label;
			} else {
				$parts[] = $state_code;
			}
		}

		return implode( ', ', $parts );
	}

	/**
	 * Check if BD Thana Add plugin is active
	 */
	public function is_thana_plugin_active() {
		return $this->thana_data !== null;
	}

	/**
	 * Check if state data is loaded
	 */
	public function is_state_data_loaded() {
		return $this->state_data !== null;
	}

	/**
	 * Get all available states
	 */
	public function get_all_states() {
		return $this->state_data ?? array();
	}

	/**
	 * Get all available thanas
	 */
	public function get_all_thanas() {
		return $this->thana_data ?? array();
	}
}

// Initialize
function wc_tp_location_formatter() {
	return WC_Team_Payroll_Location_Formatter::get_instance();
}

// Helper functions for easy access
function wc_tp_get_state_label( $state_code ) {
	return wc_tp_location_formatter()->get_state_label( $state_code );
}

function wc_tp_get_thana_label( $thana_code ) {
	return wc_tp_location_formatter()->get_thana_label( $thana_code );
}

function wc_tp_format_location( $state_code, $thana_code = '' ) {
	return wc_tp_location_formatter()->get_formatted_location( $state_code, $thana_code );
}

function wc_tp_format_text( $text ) {
	return wc_tp_location_formatter()->format_text( $text );
}
