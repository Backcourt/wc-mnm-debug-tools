<?php
/**
 * Plugin Name: WC Mix and Match - Debug tools
 * Plugin URI: https://github.com/backcourt/wc-mnm-debug-tools
 * Description: Custom mix and match upgrade routine tools
 * Version: 1.0.0
 * Author: Backcourt Development
 * Author URI: https://backcourt.io
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * Text Domain: wc-mnm-debug-tools
 * Domain Path: /languages
 *
 * @package WC_MNM_Debug_Tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Declare WooCommerce Features compatibility.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( ! class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			return;
		}

		// HPOS (Custom Order tables) compatibility.
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', plugin_basename( __FILE__ ), true );

		// Cart/Checkout Blocks compatibility.
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', plugin_basename( __FILE__ ), true );
	}
);


/**
 * Load the plugin textdomain.
 */
add_action(
	'init',
	function () {
		load_plugin_textdomain( 'wc-mnm-debug-tools', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

/**
 * Add the debug tools to the WooCommerce System Status page.
 */
add_filter(
	'woocommerce_debug_tools',
	function ( $tools ) {
		$version                          = '2.0.0';
		$tools['wc_mnm_reset_db_version'] = array(
			'name'     => esc_html__( 'Reset Mix and Match DB version', 'wc-mnm-debug-tools' ),
			'button'   => esc_html__( 'Reset DB version', 'wc-mnm-debug-tools' ),
			'desc'     => sprintf( esc_html__( 'This will reset the Mix and Match DB version to 2.0.0.', 'wc-mnm-debug-tools' ), $version ),
			'callback' => function () use ( $version ) {
				check_ajax_referer( 'debug_action', '_wpnonce' );
				update_option( 'wc_mix_and_match_db_version', $version );
				// translators: %s: version number.
				return sprintf( esc_html__( 'Mix and Match DB version set to %s', 'wc-mnm-debug-tools' ), $version );
			},
		);

		$tools['wc_mnm_force_db_updates'] = array(
			'name'     => esc_html__( 'Force run the Mix and Match database updates', 'wc-mnm-debug-tools' ),
			'button'   => esc_html__( 'Run updates', 'wc-mnm-debug-tools' ),
			'desc'     => sprintf(
				'<strong class="red">%1$s</strong> %2$s',
				__( 'Note:', 'wc-mnm-debug-tools' ),
				__( 'This tool will update your Mix and Match Products database to the latest version. Please ensure you make sufficient backups before proceeding.', 'wc-mnm-debug-tools' )
			),
			'callback' => function () use ( $version ) {
				check_ajax_referer( 'debug_action', '_wpnonce' );
				if ( is_callable( array( 'WC_MNM_Install', 'do_update_db' ) ) ) {
					WC_MNM_Install::do_update_db();
					return esc_html__( 'Mix and Match DB updates are scheduled.', 'wc-mnm-debug-tools' );
				} else {
					return esc_html__( 'Mix and Match plugin is not activated.', 'wc-mnm-debug-tools' );
				}
			},
		);

		return $tools;
	},
	99
);

/**
 * Show the Admin notes in the System Status page.
 */
add_filter(
	'wc_mnm_system_status',
	function ( $debug_data ) {

		global $wpdb;

		// Show the admin notes in the footer.
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wc_admin_notes WHERE name=%s", 'wc-mnm-update-db-reminder' )
		);

		$debug_data['mnm_view_admin_notes'] = array(
			'name'      => _x( 'Admin notes', 'label for the system status page', 'wc-mnm-debug-tools' ),
			'mark'      => '',
			'mark_icon' => 'info',
			'note'      => json_encode( $results, JSON_PRETTY_PRINT ),
		);
		return $debug_data;
	}
);
