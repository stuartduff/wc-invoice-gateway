<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC Invoice Gateway.
 *
 * Provides a Invoice Payment Gateway.
 *
 * @class 		WC_Gateway_Invoice
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 */

class WC_Gateway_Invoice extends WC_Payment_Gateway {

  /**
   * Constructor for the gateway.
   * @since   1.0.0
   */
  public function __construct() {
    $this->id                 = 'invoice';
    $this->icon               = apply_filters('wc_invoice_gateway_icon', '');
    $this->method_title       = __( 'Invoice Payments', 'wc-invoice-gateway' );
    $this->method_description = __( 'Allows invoice payments. Sends an order email to the store admin who\'ll have to manually create and send an invoice to the customer.', 'wc-invoice-gateway' );
    $this->has_fields 	      = false;

    // Load the settings
    $this->init_form_fields();
    $this->init_settings();

    // Define user set variables
    $this->title              = $this->get_option( 'title' );
    $this->description        = $this->get_option( 'description' );
    $this->instructions       = $this->get_option( 'instructions' );
    $this->order_status       = $this->get_option( 'order_status' );
    $this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
    $this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes' ? true : false;

    // Actions
    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    add_action( 'woocommerce_thankyou_invoice', array( $this, 'thankyou_page' ) );

    // Customer Emails
    add_action('woocommerce_email_before_order_table', array( $this, 'email_instructions'), 10, 3);

  }

  /**
   * Initialise Gateway Settings Form Fields
   * @since   1.0.0
   * @return  void
   */
  function init_form_fields() {

    $shipping_methods = array();

    if ( is_admin() ) {
      foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
        $shipping_methods[ $method->id ] = $method->get_title();
      }
    }

    $this->form_fields = array(
    'enabled' => array(
      'title'       => __( 'Enable/Disable', 'wc-invoice-gateway' ),
      'type'        => 'checkbox',
      'label'       => __( 'Enable Invoice Payment', 'wc-invoice-gateway' ),
      'default'     => 'yes'
      ),
    'title' => array(
      'title'       => __( 'Title', 'wc-invoice-gateway' ),
      'type'        => 'text',
      'description' => __( 'This controls the title which the user sees during checkout.', 'wc-invoice-gateway' ),
      'default'     => __( 'Invoice Payment', 'wc-invoice-gateway' ),
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
        'title'             => __( 'Choose and order status', 'wc-invoice-gateway' ),
        'type'              => 'select',
        'class'             => 'wc-enhanced-select',
        'css'               => 'width: 450px;',
        'default'           => 'on-hold',
        'description'       => __( 'Choose the order status that will be set after checkout', 'wc-invoice-gateway' ),
        'options'           => array(
          'on-hold'         => 'On Hold',
          'processing'      => 'Processing',
          'completed'       => 'Completed',
        ),
        'desc_tip'          => true,
        'custom_attributes' => array(
          'data-placeholder'  => __( 'Select order status', 'wc-invoice-gateway' )
        )
      ),
      'enable_for_methods' => array(
        'title'             => __( 'Enable for shipping methods', 'wc-invoice-gateway' ),
        'type'              => 'multiselect',
        'class'             => 'wc-enhanced-select',
        'css'               => 'width: 450px;',
        'default'           => '',
        'description'       => __( 'If Invoice is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'wc-invoice-gateway' ),
        'options'           => $shipping_methods,
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
   * Check If The Gateway Is Available For Use.
   * @access  public
   * @since   1.0.0
   * @return bool
   */
  public function is_available() {
    $order          = null;
    $needs_shipping = false;

    // Test if shipping is needed first
    if ( WC()->cart && WC()->cart->needs_shipping() ) {
      $needs_shipping = true;
    } elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
      $order_id = absint( get_query_var( 'order-pay' ) );
      $order    = wc_get_order( $order_id );

      // Test if order needs shipping.
      if ( 0 < sizeof( $order->get_items() ) ) {
        foreach ( $order->get_items() as $item ) {
          $_product = $order->get_product_from_item( $item );
          if ( $_product && $_product->needs_shipping() ) {
            $needs_shipping = true;
            break;
          }
        }
      }
    }

    $needs_shipping = apply_filters( 'wc_invoice_gateway_cart_needs_shipping', $needs_shipping );

    // Virtual order, with virtual disabled
    if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
      return false;
    }

    // Check methods
    if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {

      // Only apply if all packages are being shipped via chosen methods, or order is virtual
      $chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

      if ( isset( $chosen_shipping_methods_session ) ) {
        $chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
      } else {
        $chosen_shipping_methods = array();
      }

      $check_method = false;

      if ( is_object( $order ) ) {
        if ( $order->shipping_method ) {
          $check_method = $order->shipping_method;
      }

      } elseif ( empty( $chosen_shipping_methods ) || sizeof( $chosen_shipping_methods ) > 1 ) {
        $check_method = false;
      } elseif ( sizeof( $chosen_shipping_methods ) == 1 ) {
        $check_method = $chosen_shipping_methods[0];
      }

      if ( ! $check_method ) {
        return false;
      }

      $found = false;

      foreach ( $this->enable_for_methods as $method_id ) {
        if ( strpos( $check_method, $method_id ) === 0 ) {
          $found = true;
          break;
        }
      }

      if ( ! $found ) {
        return false;
      }
    }

    return parent::is_available();

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
    $order->reduce_order_stock();

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
    if ( $this->instructions && ! $sent_to_admin && 'invoice' === $order->get_payment_method() && apply_filters( 'wc_invoice_gateway_process_payment_order_status', $this->order_status ) !== $order->get_status() ) {
      echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
    }
  }

}
