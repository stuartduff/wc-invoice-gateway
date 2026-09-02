# Invoice Payment Gateway for WooCommerce
Adds an Invoice Payment Gateway to the [WooCommerce](https://www.woocommerce.com/) plugin. This type of payment method is usually used in B2B transactions with account / invoice customers where taking instant digital payment is not an option.

Default and custom WooCommerce order statuses of like On Hold, Pending Payment, Processing or Completed etc can be chosen from the gateway settings panel. The selected order status will be applied to all orders processed via the Invoice Payment Gateway for WooCommerce plugin and the corresponding status order emails will be sent after checkout.

You can also choose to restrict the gateway to only be enabled for specific WordPress users roles.

The plugin itself does not create customer invoices for you only orders. For invoices this is something you would still have to use an accounting program like Quickbooks or similar to bill your customers with.

Supports the WooCommerce block based checkout.


![woocommerce-invoice-gateway-settings](https://user-images.githubusercontent.com/1190565/82124911-351be600-979a-11ea-893d-185e0d398a9d.png)

## Development

For pushing to GitHub and tagging a release (including the automated clean plugin zip), see [docs/releasing.md](docs/releasing.md).

## Installation

1. Download the plugin from WordPress.org [Invoice Payment Gateway for WooCommerce](https://wordpress.org/plugins/wc-invoice-gateway/).
2. Goto WordPress > Appearance > Plugins > Add New.
3. Click Upload Plugin and Choose File, then select the plugin's .zip file. Click Install Now.
4. Click Activate to use your new plugin right away.

## Minimum Requirements

For this extension to function [WooCommerce](https://www.woocommerce.com/) must be installed and activated on your [WordPress](https://wordpress.org/) site.

* [WordPress](https://wordpress.org/)
* [WooCommerce](https://www.woocommerce.com/)

## Changelog

**2.0.3 - 20/05/26**
* Fix - Corrected inverted status check in email_instructions causing payment instructions to appear in processing emails instead of only on-hold emails.

**2.0.2 - 10/03/25**
* Fix - For deprecated PHP notices
* Added - Name chage from WooCommerce Invoice Gateway to Invoice Payment Gateway for WooCommerce

**2.0.1 - 30/08/23**
* Added - HPOS compatibility.

**2.0.0 - 18/02/23**
* Added - Compatibility for the WooCommerce block checkout.

**1.0.6 - 16/05/20**
* Added - The functionality to restrict gateway access to specific users roles.
* Fix - Typo of "Choose and order status" to "Choose an order status".

**1.0.5 - 08/05/20**
* Added - The functionality to enable the order actions buttons using the `remove_wc_invoice_gateway_order_actions_buttons` filter.

**1.0.4 - 03/04/20**
* Fix - Remove Pay, Cancel order action buttons on My Account > Orders if order status is Pending Payment.

**1.0.3 - 25/02/20**
* Added - Functionality to return custom WooCommerce order statuses.

**1.0.2 - 04/11/17**
* Fix - Enable for shipping methods dropdown.
* Added - WooCommerce v3.2+ compatibility.

**1.0.1 - 27/10/17**
* Added - WooCommerce plugin version check compatibility.

**1.0.0 - 18/07/16**
* Initial Release - first version of the plugin released.
