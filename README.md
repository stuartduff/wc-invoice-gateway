# WooCommerce Invoice Payment Gateway
Adds an Invoice Payment Gateway to the [WooCommerce](https://www.woocommerce.com/) plugin. This type of payment method is usually used in B2B transactions with account customers where taking instant digital payment is not an option.

## Installation

1. Download the plugin from it's GitHub Repository Download [WooCommerce Invoice Payment Gateway](https://github.com/stuartduff/woocommerce-invoice-payment-gateway).
2. Goto WordPress > Appearance > Plugins > Add New.
3. Click Upload Plugin and Choose File, then select the plugin's .zip file. Click Install Now.
4. Click Activate to use your new plugin right away.

## Minimum Requirements

For this extension to function [WooCommerce](https://www.woocommerce.com/) must be installed and activated on your [WordPress](https://wordpress.org/) site.

* [WordPress](https://wordpress.org/) v4.5
* [WooCommerce](https://www.woocommerce.com/) v2.6

## Notes

You may want to change the order status from it's default of **on-hold** to another status like **processing** or **completed** and send the corresponding email to the customer. If so you can use this filter below by adding it to a themes functions.php file.

https://gist.github.com/stuartduff/b805d997aeea1169569a1b76ba2ea08a

## Changelog

**1.0.0 - 18/07/16**
* Initial Release - first version of the plugin released.
