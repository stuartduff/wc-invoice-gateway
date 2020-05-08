<?php
/**
 * Plugin Name:       WooCommerce Invoice Gateway
 * Plugin URI:        https://wordpress.org/plugins/wc-invoice-gateway/
 * Description:       Adds Invoice payment gateway functionality to your WooCommerce store. This type of payment method is usually used in B2B transactions with account customers where taking instant digital payment is not an option.
 * Version:           1.0.5
 * Author:            Stuart Duff
 * Author URI:        http://stuartduff.com
 * Requires at least: 5.4
 * Tested up to:      5.4
 * Text Domain: wc-invoice-gateway
 * Domain Path: /languages/
 * WC requires at least: 4.0
 * WC tested up to: 4.1
 *
 * @package WC_Invoice_Gateway
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Returns the main instance of WC_Invoice_Gateway to prevent the need to use globals.
 *
 * @since   1.0.0
 * @return  object WC_Invoice_Gateway
 */
function WC_Invoice_Gateway() {
  return WC_Invoice_Gateway::instance();
} // End WC_Invoice_Gateway()
WC_Invoice_Gateway();

/**
 * Main WC_Invoice_Gateway Class
 *
 * @class WC_Invoice_Gateway
 * @version   1.0.0
 * @since     1.0.0
 * @package   WC_Invoice_Gateway
 */
final class WC_Invoice_Gateway {

  /**
   * WC_Invoice_Gateway The single instance of WC_Invoice_Gateway.
   * @var     object
   * @access  private
   * @since   1.0.0
   */
  private static $_instance = null;

  /**
   * The token.
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $token;

  /**
   * The version number.
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $version;

  /**
   * Constructor function.
   * @access  public
   * @since   1.0.0
   * @return  void
   */
  public function __construct() {
    $this->token            = 'wc-invoice-gateway';
    $this->plugin_url       = plugin_dir_url( __FILE__ );
    $this->plugin_path      = plugin_dir_path( __FILE__ );
    $this->plugin_basename  = plugin_basename( __FILE__ );
    $this->version          = '1.0.0';

    register_activation_hook( __FILE__, array( $this, 'install' ) );

    add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

    add_action( 'init', array( $this, 'plugin_setup' ) );

    // Remove order actions for pending payment status.
    add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'remove_wc_invoice_gateway_order_actions_buttons' ), 10, 2 );

  }

  /**
   * Main WC_Invoice_Gateway Instance
   *
   * Ensures only one instance of WC_Invoice_Gateway is loaded or can be loaded.
   *
   * @since   1.0.0
   * @static
   * @see     WC_Invoice_Gateway()
   * @return  Main WC_Invoice_Gateway instance
   */
  public static function instance() {
    if ( is_null( self::$_instance ) )
      self::$_instance = new self();
    return self::$_instance;
  } // End instance()

  /**
   * Load the localisation file.
   * @access  public
   * @since   1.0.0
   * @return  void
   */
  public function load_plugin_textdomain() {
    load_plugin_textdomain( 'wc-invoice-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
  }

  /**
   * Installation.
   * Runs on activation. Logs the version number.
   * @access  public
   * @since   1.0.0
   * @return  void
   */
  public function install() {
    $this->_log_plugin_version_number();
  }

  /**
   * Log the plugin version number.
   * @access  private
   * @since   1.0.0
   * @return  void
   */
  private function _log_plugin_version_number() {
    // Log the version number.
    update_option( $this->token . '-version', $this->version );
  }

  /**
   * Setup all the things.
   * Only executes if WooCommerce core plugin is active.
   * If WooCommerce is not installed or inactive an admin notice is displayed.
   * @return void
   */
  public function plugin_setup() {
    if ( class_exists( 'WooCommerce' ) ) {
      require $this->plugin_path . '/classes/class-wc-invoice-gateway.php';
      add_filter( 'woocommerce_payment_gateways',  array( $this, 'add_wc_invoice_gateway' ) );
      add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'plugin_action_links' ) );
    } else {
      add_action( 'admin_notices', array( $this, 'install_woocommerce_core_notice' ) );
    }
  }

  /**
   * WooCommerce Invoice Gateway plugin install notice.
   * If the user activates this plugin while not having the WooCommerce Dynamic Pricing plugin installed or activated, prompt them to install WooCommerce Dynamic Pricing.
   * @since   1.0.0
   * @return  void
   */
  public function install_woocommerce_core_notice() {
    echo '<div class="notice is-dismissible updated">
      <p>' . __( 'The WooCommerce Invoice Gateway extension requires that you have the WooCommerce core plugin installed and activated.', 'wc-invoice-gateway' ) . ' <a href="https://woocommerce.com/download/" target="_blank">' . __( 'Install WooCommerce', 'wc-invoice-gateway' ) . '</a></p>
    </div>';
  }

  /**
   * Add the gateway to WooCommerce
   * @access  public
   * @since   1.0.0
   * @return $methods
   */
  public function add_wc_invoice_gateway( $methods ) {
    $methods[] = 'WC_Gateway_Invoice';
    return $methods;
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

} // End Class
