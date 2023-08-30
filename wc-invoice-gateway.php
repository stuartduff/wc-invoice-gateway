<?php
/**
 * Plugin Name: WooCommerce Invoice Gateway
 * Plugin URI: https://wordpress.org/plugins/wc-invoice-gateway/
 * Description: Adds Invoice payment gateway functionality to your WooCommerce store. This type of payment method is usually used in B2B transactions with account customers where taking instant digital payment is not an option.
 * Version: 2.0.1
 *
 * Author: Stuart Duff
 * Author URI: http://stuartduff.com
 *
 * Text Domain: wc-invoice-gateway
 * Domain Path: /languages/
 *
 * Requires at least: 6.1
 * Tested up to: 6.3
 * 
 * WC requires at least: 8.0
 * WC tested up to: 8.1
 *
 * Copyright: © 2009-2017 Emmanouil Psychogyiopoulos.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Invoice_Gateway plugin class.
 *
 * @class WC_Invoice_Gateway
 */
class WC_Invoice_Gateway {

	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {

		// Invoice Payments setup
		add_action( 'init', array( __CLASS__, 'plugin_setup' ) );

		// Invoice Payments gateway class.
		add_action( 'plugins_loaded', array( __CLASS__, 'includes' ), 0 );

		// Invoice Payments text domain
    add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );

		// Make the Invoice Payments gateway available to WC.
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );

		// Registers WooCommerce Blocks integration.
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'wc_invoice_gateway_block_support' ) );

		// Remove order actions for pending payment status.
    add_filter( 'woocommerce_my_account_my_orders_actions', array( __CLASS__, 'remove_wc_invoice_gateway_order_actions_buttons' ), 10, 2 );

		// Declare HPOS compaibility.
		add_action( 'before_woocommerce_init', array( __CLASS__, 'wc_declare_hpos_compatibility' ) );

	}

  /**
   * Setup all the things.
   * Only executes if WooCommerce core plugin is active.
   * If WooCommerce is not installed or inactive an admin notice is displayed.
   * @return void
   */
  public static function plugin_setup() {
    if ( class_exists( 'WooCommerce' ) ) {
      add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'plugin_action_links' ) );
    } else {
      add_action( 'admin_notices', array( __CLASS__, 'install_woocommerce_core_notice' ) );
    }
  }

  /**
   * Load the localisation file.
   * @access  public
   * @since   1.0.0
   * @return  void
   */
  public static function load_plugin_textdomain() {
    load_plugin_textdomain( 'wc-invoice-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
  }

	/**
	 * Add the Invoice Payment gateway to the list of available gateways.
	 *
	 * @param array
	 */
	public static function add_gateway( $gateways ) {
		$gateways[] = 'WC_Gateway_Invoice';
		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes() {

		// Make the WC_Invoice_Gateway class available.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once 'includes/class-wc-invoice-gateway.php';
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 *
	 */
	public static function wc_invoice_gateway_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once 'includes/blocks/class-wc-invoice-payments-blocks.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Invoice_Gateway_Blocks_Support );
				}
			);
		}
	}

	/**
   * Show action links on the plugin screen.
   * @access  public
   * @since   1.0.0
   * @param	mixed $links Plugin Action links
   * @return	array
   */
  public static function plugin_action_links( $links ) {
    $action_links = array(
      'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=invoice' ) . '" title="' . esc_attr( __( 'View WooCommerce Settings', 'wc-invoice-gateway' ) ) . '">' . __( 'Settings', 'wc-invoice-gateway' ) . '</a>',
    );

    return array_merge( $action_links, $links );
  }

  /**
   * WooCommerce Invoice Gateway plugin install notice.
   * If the user activates this plugin while not having the WooCommerce Dynamic Pricing plugin installed or activated, prompt them to install WooCommerce Dynamic Pricing.
   * @since   1.0.0
   * @return  void
   */
  public static function install_woocommerce_core_notice() {
    echo '<div class="notice notice-error is-dismissible">
      <p>' . __( 'The WooCommerce Invoice Gateway extension requires that you have the WooCommerce core plugin installed and activated.', 'wc-invoice-gateway' ) . ' <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">' . __( 'Install WooCommerce', 'wc-invoice-gateway' ) . '</a></p>
    </div>';
  }

  /**
   * Remove Pay, Cancel order action buttons on My Account > Orders if order status is Pending Payment.
   * @since   1.0.4
   * @return  $actions
   */
  public static function remove_wc_invoice_gateway_order_actions_buttons( $actions, $order ) {

    if ( $order->has_status( 'pending' ) && 'invoice' === $order->get_payment_method() ) {
      unset( $actions['pay'] );
      unset( $actions['cancel'] );
    }

    return $actions;

  }

	/**
	 * Declare HPOS compatibility.
	 * @since   2.0.1
	 * @return  void
	 */
	public static function wc_declare_hpos_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

}

WC_Invoice_Gateway::init();
