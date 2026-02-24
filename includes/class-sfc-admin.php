<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class SFC_Admin
 * Handles the WordPress admin settings page and AJAX manual check.
 */
class SFC_Admin {

    private static ?SFC_Admin $instance = null;

    public static function instance(): SFC_Admin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu',             [ $this, 'register_menu' ] );
        add_action( 'admin_init',             [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts',  [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_sfc_manual_check',        [ $this, 'ajax_manual_check' ] );
        add_action( 'wp_ajax_sfc_order_check',         [ $this, 'ajax_order_check' ] );
        add_action( 'wp_ajax_sfc_delete_cache',        [ $this, 'ajax_delete_cache' ] );
        add_action( 'wp_ajax_sfc_test_credentials',    [ $this, 'ajax_test_credentials' ] );
        add_action( 'wp_ajax_sfc_get_order_fraud_data', [ $this, 'ajax_get_order_fraud_data' ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Menu & Page                                                         */
    /* ------------------------------------------------------------------ */

    public function register_menu(): void {
        add_menu_page(
            __( 'Fraud Check', 'steadfast-fraud-check' ),
            __( 'Fraud Check', 'steadfast-fraud-check' ),
            'manage_woocommerce',
            'sfc-fraud-check',
            [ $this, 'render_page' ],
            'dashicons-shield-alt',
            56
        );

        add_submenu_page(
            'sfc-fraud-check',
            __( 'Dashboard', 'steadfast-fraud-check' ),
            __( 'Dashboard', 'steadfast-fraud-check' ),
            'manage_woocommerce',
            'sfc-fraud-check',
            [ $this, 'render_page' ]
        );

        add_submenu_page(
            'sfc-fraud-check',
            __( 'Settings', 'steadfast-fraud-check' ),
            __( 'Settings', 'steadfast-fraud-check' ),
            'manage_options',
            'sfc-settings',
            [ $this, 'render_settings_page' ]
        );

        add_submenu_page(
            'sfc-fraud-check',
            __( 'Manual Check', 'steadfast-fraud-check' ),
            __( 'Manual Check', 'steadfast-fraud-check' ),
            'manage_woocommerce',
            'sfc-manual-check',
            [ $this, 'render_manual_check_page' ]
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Settings Registration                                               */
    /* ------------------------------------------------------------------ */

    public function register_settings(): void {
        $options = [
            'sfc_api_key'              => 'sanitize_text_field',
            'sfc_secret_key'           => 'sanitize_text_field',
            'sfc_cache_hours'          => 'absint',
            'sfc_block_high_risk'      => 'sanitize_text_field',
            'sfc_high_risk_threshold'  => 'absint',
            'sfc_warn_medium_risk'     => 'sanitize_text_field',
            'sfc_medium_risk_threshold'=> 'absint',
            'sfc_auto_check_checkout'  => 'sanitize_text_field',
            'sfc_auto_check_order'     => 'sanitize_text_field',
            'sfc_show_risk_badge'      => 'sanitize_text_field',
        ];

        foreach ( $options as $key => $sanitize ) {
            register_setting( 'sfc_settings_group', $key, [ 'sanitize_callback' => $sanitize ] );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Asset Enqueue                                                       */
    /* ------------------------------------------------------------------ */

    public function enqueue_assets( string $hook ): void {
        $pages = [
            'toplevel_page_sfc-fraud-check',
            'fraud-check_page_sfc-settings',
            'fraud-check_page_sfc-manual-check',
        ];

        if ( ! in_array( $hook, $pages, true ) ) return;

        wp_enqueue_style(
            'sfc-admin',
            SFC_PLUGIN_URL . 'assets/css/admin.css',
            [],
            SFC_VERSION
        );

        wp_enqueue_script(
            'sfc-admin',
            SFC_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            SFC_VERSION,
            true
        );

        wp_localize_script( 'sfc-admin', 'SFC', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'sfc_nonce' ),
            'i18n'    => [
                'checking'     => __( 'Checking…', 'steadfast-fraud-check' ),
                'error'        => __( 'Error occurred. Please try again.', 'steadfast-fraud-check' ),
                'confirm_del'  => __( 'Clear all cached fraud check results?', 'steadfast-fraud-check' ),
                'testing'      => __( 'Testing connection…', 'steadfast-fraud-check' ),
            ],
        ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Dashboard Page                                                      */
    /* ------------------------------------------------------------------ */

    public function render_page(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'steadfast_fraud_cache';

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) as total,
                SUM(risk_level = 'high')    as high_count,
                SUM(risk_level = 'medium')  as medium_count,
                SUM(risk_level = 'safe')    as safe_count,
                SUM(risk_level = 'unknown') as unknown_count
            FROM {$table}"  // phpcs:ignore
        );

        // Recent high-risk cache entries
        $recent_high = $wpdb->get_results(
            "SELECT phone, risk_level, checked_at FROM {$table} WHERE risk_level IN ('high','medium') ORDER BY checked_at DESC LIMIT 10" // phpcs:ignore
        );

        // Orders with risk meta
        $high_orders = wc_get_orders( [
            'limit'      => 10,
            'meta_key'   => '_sfc_risk_level',   // phpcs:ignore
            'meta_value' => 'high',               // phpcs:ignore
            'orderby'    => 'date',
            'order'      => 'DESC',
        ] );

        $api_configured = SFC_API::instance()->has_credentials();

        include SFC_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    /* ------------------------------------------------------------------ */
    /*  Settings Page                                                       */
    /* ------------------------------------------------------------------ */

    public function render_settings_page(): void {
        include SFC_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /* ------------------------------------------------------------------ */
    /*  Manual Check Page                                                   */
    /* ------------------------------------------------------------------ */

    public function render_manual_check_page(): void {
        include SFC_PLUGIN_DIR . 'templates/admin-manual-check.php';
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX Handlers                                                       */
    /* ------------------------------------------------------------------ */

    public function ajax_manual_check(): void {
        check_ajax_referer( 'sfc_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'steadfast-fraud-check' ) ] );
        }

        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $force = isset( $_POST['force'] ) && 'true' === $_POST['force'];

        if ( empty( $phone ) ) {
            wp_send_json_error( [ 'message' => __( 'Phone number is required.', 'steadfast-fraud-check' ) ] );
        }

        $result = SFC_Checker::instance()->check( $phone, $force );
        wp_send_json_success( $result );
    }

    public function ajax_order_check(): void {
        check_ajax_referer( 'sfc_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'steadfast-fraud-check' ) ] );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        if ( ! $order_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid order ID.', 'steadfast-fraud-check' ) ] );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( [ 'message' => __( 'Order not found.', 'steadfast-fraud-check' ) ] );
        }

        $phone = $order->get_billing_phone();
        if ( empty( $phone ) ) {
            wp_send_json_error( [ 'message' => __( 'No phone number on this order.', 'steadfast-fraud-check' ) ] );
        }

        $result = SFC_Checker::instance()->check_and_attach_order( $order_id, $phone );
        wp_send_json_success( $result );
    }

    public function ajax_delete_cache(): void {
        check_ajax_referer( 'sfc_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'steadfast-fraud-check' ) ] );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'steadfast_fraud_cache';
        $wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore

        wp_send_json_success( [ 'message' => __( 'Cache cleared successfully.', 'steadfast-fraud-check' ) ] );
    }

    public function ajax_test_credentials(): void {
        check_ajax_referer( 'sfc_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'steadfast-fraud-check' ) ] );
        }

        // Accept credentials from the form (so the user can test before saving).
        $posted_key    = isset( $_POST['api_key'] )    ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) )    : '';
        $posted_secret = isset( $_POST['secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['secret_key'] ) ) : '';

        // If credentials were provided in the request, temporarily store them
        // so reload_credentials() picks them up, then restore the originals.
        $original_key    = get_option( 'sfc_api_key',    '' );
        $original_secret = get_option( 'sfc_secret_key', '' );

        $using_posted = ! empty( $posted_key ) && ! empty( $posted_secret );
        if ( $using_posted ) {
            update_option( 'sfc_api_key',    $posted_key );
            update_option( 'sfc_secret_key', $posted_secret );
        }

        $result = SFC_API::instance()->get_balance();

        // Restore original values if we temporarily changed them.
        if ( $using_posted ) {
            update_option( 'sfc_api_key',    $original_key );
            update_option( 'sfc_secret_key', $original_secret );
        }

        if ( is_wp_error( $result ) ) {
            $msg = $result->get_error_message();

            // 500 from Steadfast usually means wrong/invalid credentials.
            if ( str_contains( $msg, '500' ) ) {
                $msg = __( 'Status 500: Invalid API credentials. Please double-check your API Key and Secret Key from your Steadfast Merchant Dashboard.', 'steadfast-fraud-check' );
            }

            wp_send_json_error( [ 'message' => $msg ] );
        }

        wp_send_json_success( [
            'message' => __( 'Connection successful!', 'steadfast-fraud-check' ),
            'balance' => $result,
        ] );
    }

    public function ajax_get_order_fraud_data(): void {
        check_ajax_referer( 'sfc_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'steadfast-fraud-check' ) ] );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        if ( ! $order_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid order ID.', 'steadfast-fraud-check' ) ] );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( [ 'message' => __( 'Order not found.', 'steadfast-fraud-check' ) ] );
        }

        $risk      = $order->get_meta( '_sfc_risk_level' );
        $score     = $order->get_meta( '_sfc_risk_score' );
        $raw_json  = $order->get_meta( '_sfc_fraud_data' ) ?: '{}';
        $raw       = json_decode( $raw_json, true ) ?: [];
        $checked   = $order->get_meta( '_sfc_checked_at' );
        $force     = isset( $_POST['force'] ) && 'true' === $_POST['force'];

        // If data is empty, 'unknown', or if force refresh is requested
        if ( empty( $risk ) || 'unknown' === $risk || $force ) {
            $phone = $order->get_billing_phone();
            if ( ! empty( $phone ) ) {
                // This will use the table cache (from manual check) if available, 
                // otherwise it hits the API (if not force).
                $result = SFC_Checker::instance()->check_and_attach_order( $order_id, $phone, $force );
                
                if ( ! is_wp_error( $result ) ) {
                    wp_send_json_success( $result );
                    return;
                }
            }
        }

        wp_send_json_success( [
            'risk_level' => $risk,
            'score'      => $score,
            'stats'      => $raw['stats'] ?? [],
            'summary'    => $raw['summary'] ?? '',
            'checked_at' => $checked,
        ] );
    }
}
