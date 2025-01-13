<?php
/**
 * Plugin Name: WC Mix and Match - Debug tools
 * Plugin URI: https://github.com/backcourt/wc-mnm-debug-tools
 * Description: Custom mix and match upgrade routine tools
 * Version: 1.1.0
 * Author: Backcourt Development
 * Author URI: https://backcourt.io
 * Requires at least: 6.6
 * Requires PHP: 7.4
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

		global $wpdb;
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

		$tools['wc_mnm_repair_db_constraints'] = array(
			'name'     => esc_html__( 'Repair database foreign key constraints', 'wc-mnm-debug-tools' ),
			'button'   => esc_html__( 'Repair keys', 'wc-mnm-debug-tools' ),
			'desc'     => sprintf(
				'<strong class="red">%1$s</strong> %2$s',
				__( 'Note:', 'wc-mnm-debug-tools' ),
				__( 'This tool will update your Mix and Match Products database constraints to your curent `wp_posts` table.', 'wc-mnm-debug-tools' )
			),
			'callback' => function () use ( $wpdb ) {
				check_ajax_referer( 'debug_action', '_wpnonce' );

				try {

					$wpdb->hide_errors();

					$foreign_key_names = array(
						"fk_{$wpdb->prefix}wc_mnm_child_items_container_id",
						"fk_{$wpdb->prefix}wc_mnm_child_items_product_id",
					);

					foreach ( $foreign_key_names as $foreign_key_name ) {

						$foreign_key_name = sanitize_key( $foreign_key_name );

						$fk_exists = $wpdb->get_var(
							$wpdb->prepare(
								'
								SELECT COUNT(*)
								FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
								WHERE TABLE_NAME = %s
									AND TABLE_SCHEMA = %s
									AND CONSTRAINT_NAME = %s
								',
								"{$wpdb->prefix}wc_mnm_child_items", // Table name.
								DB_NAME,                             // Database name.
								$foreign_key_name                    // Foreign key name.
							)
						);

						if ( $fk_exists ) {
							// Remove foreign keys, so we can regenerate them.
							$sql = sprintf( "ALTER TABLE {$wpdb->prefix}wc_mnm_child_items DROP FOREIGN KEY %s", $foreign_key_name );

							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $wpdb->prepare() does not correctly handle keys or table names.
							$result = $wpdb->query( $sql );

							if ( false === $result ) {
								// translators: %1$s: foreign key name. %2$s: error message.
								return sprintf( esc_html__( 'Mix and Match DB %1$s key could not be removed because %2$s.', 'wc-mnm-debug-tools' ), $foreign_key_name, $wpdb->last_error );
							}

							$column_name = sanitize_key( str_replace( "fk_{$wpdb->prefix}wc_mnm_child_items_", '', $foreign_key_name ) );

							// Add the foreign keys.

							$sql = sprintf(
								"
									ALTER TABLE {$wpdb->prefix}wc_mnm_child_items
									ADD CONSTRAINT %s
									FOREIGN KEY (%s)
									REFERENCES {$wpdb->prefix}posts(ID)
									ON DELETE CASCADE
								",
								$foreign_key_name,
								$column_name
							);

							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $wpdb->prepare() does not correctly handle keys or table names.
							$result = $wpdb->query( $sql );

							if ( false === $result ) {
								// translators: %1$s: foreign key name. %2$s: error message.
								return sprintf( esc_html__( 'Mix and Match DB %1$s foreign key could not be added because %2$s.', 'wc-mnm-debug-tools' ), $foreign_key_name, $wpdb->last_error );
							}
						}
					}

					return esc_html__( 'Mix and Match DB foreign keys are repaired.', 'wc-mnm-debug-tools' );

				} catch ( Exception $e ) {
					return esc_html__( 'Mix and Match DB could not be regenerated.', 'wc-mnm-debug-tools' );
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
