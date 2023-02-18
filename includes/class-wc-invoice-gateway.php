<?php
/**
 * WC_Invoice_Gateway class
 *
 * @author   Stuart Duff
 * @package  WooCommerce Invoice Gateway
 * @since    1.0.0
 */

use Automattic\Jetpack\Constants;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Invoice Gateway.
 *
 * @class    WC_Gateway_Invoice
 * @version  1.0.3
 */
class WC_Gateway_Invoice extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		
		// Setup general properties.
		$this->setup_properties();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define settings.
		$this->title       		  = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->instructions 	  = $this->get_option( 'instructions', $this->description );
		$this->order_status       = $this->get_option( 'order_status' );
		$this->user_roles         = $this->get_option( 'user_roles' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
		$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_invoice', array( $this, 'thankyou_page' ) );

		// Restrict payment gateway to user roles.
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'wc_invoice_restrict_gatway_user_roles' ) );

		// Customer Emails
		add_action('woocommerce_email_before_order_table', array( $this, 'email_instructions'), 10, 3 );		

	}


/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = 'invoice';
		$this->icon               = apply_filters( 'wc_invoice_gateway_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = _x( 'Invoice Payment', 'Invoice payment method', 'wc-invoice-gateway' );
		$this->method_description = __( 'Allows invoice payments. Sends an order email to the store admin who\'ll have to manually create and send an invoice to the customer.', 'wc-invoice-gateway' );
		$this->supports           = array(
			'products'
		);
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'wc-invoice-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Invoice Payment', 'wc-invoice-gateway' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'wc-invoice-gateway' ),
				'type'        => 'safe_text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-invoice-gatewayy' ),
				'default'     => _x( 'Invoice Payment', 'Invoice payment method', 'wc-invoice-gateway' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'wc-invoice-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description which the user sees during checkout.', 'wc-invoice-gateway' ),
				'default'     => __( 'Thank you for your order. You\'ll be invoiced soon.', 'wc-invoice-gateway' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'wc-invoice-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page after checkout and included within the new order email.', 'wc-invoice-gateway' ),
				'default'     => __( 'If you\'re an account customer you\'ll be invoiced soon with regards to payment for your order.', 'wc-invoice-gateway' ),
				'desc_tip'    => true,
			),
     	'order_status' => array(
      	'title'             => __( 'Choose an order status', 'wc-invoice-gateway' ),
        'type'              => 'select',
        'class'             => 'wc-enhanced-select',
       	'css'               => 'width: 450px;',
        'default'           => 'on-hold',
       	'description'       => __( 'Choose the order status that will be set after checkout', 'wc-invoice-gateway' ),
       	'options'           => $this->get_available_order_statuses(),
       	'desc_tip'          => true,
       	'custom_attributes' => array(
          'data-placeholder'  => __( 'Select order status', 'wc-invoice-gateway' )
        )
      ),
			'user_roles' => array(
				'title'             => __( 'Restrict to specific user roles', 'wc-invoice-gateway' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'description'       => __( 'Choose specific user roles the gateway will display for. If no user roles are chosen the gateway will display for all users', 'wc-invoice-gateway' ),
				'options'           => $this->get_available_user_roles(),
				'desc_tip'          => true,
				'custom_attributes' => array(
				  'data-placeholder'  => __( 'Select user roles', 'wc-invoice-gateway' )
				)
			),
			'enable_for_methods' => array(
				'title'             => __( 'Enable for shipping methods', 'wc-invoice-gateway' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'description'       => __( 'If Invoice is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'wc-invoice-gateway' ),
				'options'           => $this->load_shipping_method_options(),
				'desc_tip'          => true,
				'custom_attributes' => array(
				  'data-placeholder'  => __( 'Select shipping methods', 'wc-invoice-gateway' )
				)
			),
			'enable_for_virtual' => array(
				'title'             => __( 'Accept for virtual orders', 'wc-invoice-gateway' ),
				'label'             => __( 'Accept Invoice if the order is virtual', 'wc-invoice-gateway' ),
				'type'              => 'checkbox',
				'default'           => 'yes'
			),
		);
	}

	/**
	 * Get all order statuses available within WooCommerce
	 * @access  protected
	 * @since   1.0.3
	 * @return array
	 */
	protected function get_available_order_statuses() {
		$order_statuses = wc_get_order_statuses();

		$keys = array_map( function( $key ) {
		return str_replace('wc-', '', $key ); // Remove prefix
		}, array_keys( $order_statuses ) );

		$returned_statuses = array_combine( $keys, $order_statuses );

		// Remove the statuses of cancelled, refunded, failed and draft from returning.
		unset( $returned_statuses['cancelled'] );
		unset( $returned_statuses['refunded'] );
		unset( $returned_statuses['failed'] );
		unset( $returned_statuses['checkout-draft'] );

		return $returned_statuses;

	}

	/**
	 * Get all user roles available within WordPress
	 * @access  protected
	 * @since   1.0.6
	 * @return array
	 */
	protected function get_available_user_roles() {
		global $wp_roles;

		$roles = $wp_roles->get_names();

		return $roles;
	}

	/**
	 * Restrict invoice gateway access selected user roles
	 * @access  public
	 * @since   1.0.6
	 */
	public function wc_invoice_restrict_gatway_user_roles( $available_gateways ) {

		$user = wp_get_current_user();
		$enabled_roles = $this->user_roles;

		if ( ! empty( $enabled_roles ) && array_diff( $enabled_roles, (array) $user->roles ) === $enabled_roles ) {
			unset( $available_gateways['invoice'] );
		}

		return $available_gateways;

	}

	/**
	 * Check If The Gateway Is Available For Use.
	 * @access  public
	 * @since   1.0.0
	 * @return bool
	 */
	public function is_available() {
		$order          = null;
		$needs_shipping = false;

		// Test if shipping is needed first.
		if ( WC()->cart && WC()->cart->needs_shipping() ) {
			$needs_shipping = true;
		} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			// Test if order needs shipping.
			if ( $order && 0 < count( $order->get_items() ) ) {
				foreach ( $order->get_items() as $item ) {
					$_product = $item->get_product();
					if ( $_product && $_product->needs_shipping() ) {
						$needs_shipping = true;
						break;
					}
				}
			}
		}

		$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

		// Virtual order, with virtual disabled
		if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
			return false;
		}

		// Only apply if all packages are being shipped via chosen method, or order is virtual.
		if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
			$order_shipping_items            = is_object( $order ) ? $order->get_shipping_methods() : false;
			$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

			if ( $order_shipping_items ) {
				$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids( $order_shipping_items );
			} else {
				$canonical_rate_ids = $this->get_canonical_package_rate_ids( $chosen_shipping_methods_session );
			}

			if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
				return false;
			}
		}

		return parent::is_available();

	}

	/**
	 * Checks to see whether or not the admin settings are being accessed by the current request.
	 *
	 * @return bool
	 */
	private function is_accessing_settings() {
		if ( is_admin() ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['section'] ) || 'invoice' !== $_REQUEST['section'] ) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			return true;
		}

		if ( Constants::is_true( 'REST_REQUEST' ) ) {
			global $wp;
			if ( isset( $wp->query_vars['rest_route'] ) && false !== strpos( $wp->query_vars['rest_route'], '/payment_gateways' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Loads all of the shipping method options for the enable_for_methods field.
	 *
	 * @return array
	 */
	private function load_shipping_method_options() {
		// Since this is expensive, we only want to do it if we're actually on the settings page.
		if ( ! $this->is_accessing_settings() ) {
			return array();
		}

		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();

		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new WC_Shipping_Zone( $raw_zone );
		}

		$zones[] = new WC_Shipping_Zone( 0 );

		$options = array();
		foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

			$options[ $method->get_method_title() ] = array();

			// Translators: %1$s shipping method name.
			$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'wc-invoice-gateway' ), $method->get_method_title() );

			foreach ( $zones as $zone ) {

				$shipping_method_instances = $zone->get_shipping_methods();

				foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

					if ( $shipping_method_instance->id !== $method->id ) {
						continue;
					}

					$option_id = $shipping_method_instance->get_rate_id();

					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'wc-invoice-gateway' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf( __( '%1$s &ndash; %2$s', 'wc-invoice-gateway' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'wc-invoice-gateway' ), $option_instance_title );

					$options[ $method->get_method_title() ][ $option_id ] = $option_title;
				}
			}
		}

		return $options;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @since  3.4.0
	 *
	 * @param  array $order_shipping_items  Array of WC_Order_Item_Shipping objects.
	 * @return array $canonical_rate_ids    Rate IDs in a canonical format.
	 */
	private function get_canonical_order_shipping_item_rate_ids( $order_shipping_items ) {

		$canonical_rate_ids = array();

		foreach ( $order_shipping_items as $order_shipping_item ) {
			$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
		}

		return $canonical_rate_ids;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @since  3.4.0
	 *
	 * @param  array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
	 * @return array $canonical_rate_ids  Rate IDs in a canonical format.
	 */
	private function get_canonical_package_rate_ids( $chosen_package_rate_ids ) {

		$shipping_packages  = WC()->shipping()->get_packages();
		$canonical_rate_ids = array();

		if ( ! empty( $chosen_package_rate_ids ) && is_array( $chosen_package_rate_ids ) ) {
			foreach ( $chosen_package_rate_ids as $package_key => $chosen_package_rate_id ) {
				if ( ! empty( $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ] ) ) {
					$chosen_rate          = $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ];
					$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
				}
			}
		}

		return $canonical_rate_ids;
	}

	/**
	 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
	 *
	 * @since  3.4.0
	 *
	 * @param array $rate_ids Rate ids to check.
	 * @return boolean
	 */
	private function get_matching_rates( $rate_ids ) {
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
	}

	/**
	 * Process the payment and return the result.
	 * @access  public
	 * @since   1.0.0
	 * @param int $order_id
	 * @return array
	 */
	function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		// Mark as on-hold (we're awaiting the invoice)
		$order->update_status( apply_filters( 'wc_invoice_gateway_process_payment_order_status', $this->order_status ), __('Awaiting invoice payment', 'wc-invoice-gateway' ) );

		// Reduce stock levels
		wc_reduce_stock_levels( $order_id );

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' 	  => 'success',
			'redirect'	=> $this->get_return_url( $order )
		);

	}

	/**
   * Output for the order received page.
   * @access  public
   * @since   1.0.0
   * @return  void
   */
  public function thankyou_page() {
    if ( $this->instructions ) {
      echo wpautop( wptexturize( $this->instructions ) );
    }
  }

  /**
   * Add content to the WC emails.
   * @access  public
   * @since   1.0.0
   * @param WC_Order $order
   * @param bool $sent_to_admin
   * @param bool $plain_text
   */
  public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
    if ( $this->instructions && ! $sent_to_admin && 'invoice' === $order->get_payment_method() && apply_filters( 'wc_invoice_gateway_process_payment_order_status', $this->order_status ) !== 'wc-' . $order->get_status() ) {
      echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
    }
  }

}
