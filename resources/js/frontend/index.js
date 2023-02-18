
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';

const settings = getSetting( 'invoice_data', {} );
const defaultLabel = __( 'Invoice Payment', 'wc-invoice-gateway' );
const label = decodeEntities( settings.title ) || defaultLabel;

/**
 * Content component
 */
const Content = () => {
	return decodeEntities( settings.description || '' );
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

/**
 * Determine whether Invoice is available for this cart/order.
 *
 * @param {Object}  props                         Incoming props for the component.
 * @param {boolean} props.cartNeedsShipping       True if the cart contains any physical/shippable products.
 * @param {boolean} props.selectedShippingMethods
 *
 * @return {boolean}  True if Invoice payment method should be displayed as a payment option.
 */
 const canMakePayment = ( { cartNeedsShipping, selectedShippingMethods } ) => {

	// Check if enabled for specific user roles
	const compareUerRoles = settings.enableForUserRoles.includes( settings.currentUserRole[0] );

	if ( settings.enableForUserRoles.length && compareUerRoles === false ) {
		return false;
	}

	if ( ! settings.enableForVirtual && ! cartNeedsShipping ) {
		// Store doesn't allow Invoice for virtual orders AND
		// order doesn't contain any shippable products.
		return false;
	}

	if ( ! settings.enableForShippingMethods.length ) {
		// Store does not limit Invoice to specific shipping methods.
		return true;
	}

	// Look for a supported shipping method in the user's selected
	// shipping methods. If one is found, then COD is allowed.
	const selectedMethods = Object.values( selectedShippingMethods );
	
	// supported shipping methods might be global (eg. "Any flat rate"), hence
	// this is doing a `String.prototype.includes` match vs a `Array.prototype.includes` match.
	const outputMethods = settings.enableForShippingMethods.some( ( shippingMethodId ) => {
		return selectedMethods.some( ( selectedMethod ) => {
			return selectedMethod.includes( shippingMethodId );
			
		} );
	} );

	if ( outputMethods !== false ) {
		return true
	}
	
};

/**
 * Invoice payment method config object.
 */
const Invoice = {
	name: "invoice",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( Invoice );
