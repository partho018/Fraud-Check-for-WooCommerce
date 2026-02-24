<?php
/**
 * Plugin Name:       Steadfast Fraud Check for WooCommerce
 * Plugin URI:        https://github.com/your-repo/steadfast-fraud-check
 * Description:       Automatically checks customer fraud risk using the Steadfast Courier API during WooCommerce checkout & order management. Flags high-risk orders based on COD delivery history.
 * Version:           1.0.0
 * Author:            TS Dev
 * Author URI:        #
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       steadfast-fraud-check
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * WC tested up to:   9.0
 */

defined( 'ABSPATH' ) || exit;

define( 'SFC_VERSION',     '1.0.0' );
define( 'SFC_PLUGIN_FILE', __FILE__ );
define( 'SFC_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'SFC_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

/**
 * Check if WooCommerce is active before doing anything.
 */
function sfc_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Steadfast Fraud Check:</strong> ';
            esc_html_e( 'WooCommerce must be installed and active for this plugin to work.', 'steadfast-fraud-check' );
            echo '</p></div>';
        } );
        return false;
    }
    return true;
}
add_action( 'plugins_loaded', function () {
    if ( ! sfc_check_woocommerce() ) return;

    require_once SFC_PLUGIN_DIR . 'includes/class-sfc-api.php';
    require_once SFC_PLUGIN_DIR . 'includes/class-sfc-checker.php';
    require_once SFC_PLUGIN_DIR . 'includes/class-sfc-admin.php';
    require_once SFC_PLUGIN_DIR . 'includes/class-sfc-order-column.php';
    require_once SFC_PLUGIN_DIR . 'includes/class-sfc-checkout.php';

    SFC_API::instance();
    SFC_Checker::instance();
    SFC_Admin::instance();
    SFC_Order_Column::instance();
    SFC_Checkout::instance();
} );

/**
 * Declare WooCommerce HPOS (High-Performance Order Storage) compatibility.
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SFC_PLUGIN_FILE, true );
    }
} );

/** Activation hook – create DB table & default options */
register_activation_hook( __FILE__, 'sfc_activate' );
function sfc_activate() {
    global $wpdb;
    $table      = $wpdb->prefix . 'steadfast_fraud_cache';
    $charset    = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        phone       VARCHAR(20)         NOT NULL,
        result      LONGTEXT            NOT NULL,
        risk_level  VARCHAR(20)         NOT NULL DEFAULT 'unknown',
        checked_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY  phone (phone),
        KEY         risk_level (risk_level)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    add_option( 'sfc_api_key',              '' );
    add_option( 'sfc_secret_key',           '' );
    add_option( 'sfc_cache_hours',          '6' );
    add_option( 'sfc_block_high_risk',      'no' );
    add_option( 'sfc_high_risk_threshold',  '40' );
    add_option( 'sfc_warn_medium_risk',     'yes' );
    add_option( 'sfc_medium_risk_threshold','20' );
    add_option( 'sfc_auto_check_checkout',  'yes' );
    add_option( 'sfc_auto_check_order',     'yes' );
    add_option( 'sfc_show_risk_badge',      'yes' );
}

/** Deactivation – flush scheduled events */
register_deactivation_hook( __FILE__, 'sfc_deactivate' );
function sfc_deactivate() {
    wp_clear_scheduled_hook( 'sfc_cleanup_cache' );
}

/** Schedule daily cache cleanup */
if ( ! wp_next_scheduled( 'sfc_cleanup_cache' ) ) {
    wp_schedule_event( time(), 'daily', 'sfc_cleanup_cache' );
}
add_action( 'sfc_cleanup_cache', 'sfc_do_cache_cleanup' );
function sfc_do_cache_cleanup() {
    global $wpdb;
    $table = $wpdb->prefix . 'steadfast_fraud_cache';
    $hours = (int) get_option( 'sfc_cache_hours', 6 );
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table} WHERE checked_at < DATE_SUB(NOW(), INTERVAL %d HOUR)", // phpcs:ignore
            $hours
        )
    );
}
