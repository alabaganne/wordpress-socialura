<?php
/**
 * Main class for the WooCommerce Analytics package.
 * Originally ported from the Jetpack_Google_Analytics code.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic;

use Automattic\Jetpack\Connection\Manager as Jetpack_Connection;
use Automattic\Woocommerce_Analytics\My_Account;
use Automattic\Woocommerce_Analytics\Universal;

/**
 * Instantiate WooCommerce Analytics
 */
class Woocommerce_Analytics {
	/**
	 * Package version.
	 */
	const PACKAGE_VERSION = '0.4.8';

	/**
	 * Initializer.
	 * Used to configure the WooCommerce Analytics package.
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! self::should_track_store() || did_action( 'woocommerce_analytics_init' ) ) {
			return;
		}

		// loading _wca.
		add_action( 'wp_head', array( __CLASS__, 'wp_head_top' ), 1 );

		// loading s.js.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_tracking_script' ) );

		// Initialize general store tracking actions.
		add_action( 'init', array( new Universal(), 'init_hooks' ) );
		add_action( 'init', array( new My_Account(), 'init_hooks' ) );

		/**
		 * Fires after the WooCommerce Analytics package is initialized
		 *
		 * @since 0.1.5
		 */
		do_action( 'woocommerce_analytics_init' );
	}

	/**
	 * WooCommerce Analytics is only available to Jetpack connected WooCommerce stores
	 * with WooCommerce version 3.0 or higher
	 *
	 * @return bool
	 */
	public static function should_track_store() {
		// Ensure this is available, even with mu-plugins.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		/**
		 * Make sure WooCommerce is installed and active
		 *
		 * This action is documented in https://docs.woocommerce.com/document/create-a-plugin
		 */
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return false;
		}
		// Ensure the WooCommerce class exists and is a valid version.
		$minimum_woocommerce_active = class_exists( 'WooCommerce' ) && version_compare( \WC_VERSION, '3.0', '>=' );
		if ( ! $minimum_woocommerce_active ) {
			return false;
		}

		// Tracking only Site pages.
		if ( is_admin() || wp_doing_ajax() || is_login() ) {
			return false;
		}

		// Make sure the site is connected to WordPress.com.
		if ( ! ( new Jetpack_Connection() )->is_connected() ) {
			return false;
		}

		return true;
	}

	/**
	 * Make _wca available to queue events
	 */
	public static function wp_head_top() {
		if ( is_cart() || is_checkout() || is_checkout_pay_page() || is_order_received_page() || is_add_payment_method_page() ) {
			echo '<script>window._wca_prevent_referrer = true;</script>' . "\r\n";
		}
		echo '<script>window._wca = window._wca || [];</script>' . "\r\n";
	}

	/**
	 * Place script to call s.js, Store Analytics.
	 */
	public static function enqueue_tracking_script() {
		$url = sprintf(
			'https://stats.wp.com/s-%d.js',
			gmdate( 'YW' )
		);

		wp_enqueue_script(
			'woocommerce-analytics',
			$url,
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- The version is set in the URL.
			array(
				'in_footer' => false,
				'strategy'  => 'defer',
			)
		);
	}
}
