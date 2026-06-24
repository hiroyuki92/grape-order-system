<?php
/**
 * Overrides for Stripe gateway.
 *
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook\Stripe;

use function CrossPeakSoftware\WooCommerce\AddressBook\Settings\setting;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Filters the arguments used to create or update a customer.
 *
 * @param array $args The arguments used to create a customer.
 * @return array
 */
function customer_args( array $args ) {
	if ( ! setting( 'billing_enable' ) ) {
		return $args;
	}

	if ( isset( $_POST['billing_first_name'] ) && isset( $_POST['billing_last_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$billing_first_name = filter_var( wp_unslash( $_POST['billing_first_name'] ), FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$billing_last_name  = filter_var( wp_unslash( $_POST['billing_last_name'] ), FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$billing_full_name  = trim( $billing_first_name . ' ' . $billing_last_name );
		if ( ! empty( $billing_full_name ) ) {
			$args['name'] = $billing_full_name;
		}
	}

	$address_fields = array(
		'line1'       => 'billing_address_1',
		'line2'       => 'billing_address_2',
		'postal_code' => 'billing_postcode',
		'city'        => 'billing_city',
		'state'       => 'billing_state',
		'country'     => 'billing_country',
	);
	foreach ( $address_fields as $key => $field ) {
		if ( isset( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$args['address'][ $key ] = filter_var( wp_unslash( $_POST[ $field ] ), FILTER_SANITIZE_SPECIAL_CHARS ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	return $args;
}
add_filter( 'wc_stripe_create_customer_args', __NAMESPACE__ . '\customer_args' );
add_filter( 'wc_stripe_update_customer_args', __NAMESPACE__ . '\customer_args' );
