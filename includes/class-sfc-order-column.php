<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class SFC_Order_Column
 * Adds a "Fraud Risk" column to the WooCommerce orders list
 * and shows the risk badge inside the single order page.
 */
class SFC_Order_Column {

    private static ?SFC_Order_Column $instance = null;

    public static function instance(): SFC_Order_Column {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Orders list table (WC 7+ uses the HPOS page, older uses edit.php?post_type=shop_order)
        add_filter( 'manage_woocommerce_page_wc-orders_columns',  [ $this, 'add_column' ], 20 );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'render_column' ], 20, 2 );

        // Legacy post-table support
        add_filter( 'manage_edit-shop_order_columns',          [ $this, 'add_column' ], 20 );
        add_action( 'manage_shop_order_posts_custom_column',   [ $this, 'render_column_legacy' ], 20, 2 );

        // Single order page meta box
        add_action( 'add_meta_boxes', [ $this, 'add_order_meta_box' ] );

        // Enqueue styles for order edit pages
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_order_assets' ] );
    }

    /* ------------------------------------------------------------------ */
    /*  HPOS Column                                                         */
    /* ------------------------------------------------------------------ */

    public function add_column( array $columns ): array {
        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'order_status' === $key ) {
                $new['sfc_fraud_risk'] = '<span class="dashicons dashicons-shield-alt" title="' . esc_attr__( 'Fraud Risk', 'steadfast-fraud-check' ) . '"></span> ' . esc_html__( 'Fraud Risk', 'steadfast-fraud-check' );
            }
        }
        return $new;
    }

    public function render_column( string $column, \WC_Order $order ): void {
        if ( 'sfc_fraud_risk' !== $column ) return;
        $this->output_risk_badge( $order );
    }

    public function render_column_legacy( string $column, int $post_id ): void {
        if ( 'sfc_fraud_risk' !== $column ) return;
        $order = wc_get_order( $post_id );
        if ( $order ) {
            $this->output_risk_badge( $order );
        }
    }

    private function output_risk_badge( \WC_Order $order ): void {
        if ( 'yes' !== get_option( 'sfc_show_risk_badge', 'yes' ) ) return;

        $risk    = $order->get_meta( '_sfc_risk_level' );
        $score   = $order->get_meta( '_sfc_risk_score' );
        $order_id = $order->get_id();

        $checker = SFC_Checker::instance();
        
        echo '<div class="sfc-column-risk" id="sfc-risk-container-' . esc_attr( $order_id ) . '">';

        if ( empty( $risk ) ) {
            echo '<span class="sfc-badge sfc-badge--unknown" title="' . esc_attr__( 'Not checked yet', 'steadfast-fraud-check' ) . '">';
            echo esc_html( $checker->risk_icon( 'unknown' ) . ' ' . $checker->risk_label( 'unknown' ) );
            echo '</span>';
        } else {
            $label = $checker->risk_label( $risk );
            $icon  = $checker->risk_icon( $risk );
            echo '<span class="sfc-badge sfc-badge--' . esc_attr( $risk ) . '" title="' . esc_attr( $label . ' (' . $score . '/100)' ) . '">';
            echo esc_html( $icon . ' ' . $label );
            echo '</span>';
        }
        
        echo '<div class="sfc-column-actions">';
        echo '<button type="button" class="sfc-order-refresh-btn" data-order-id="' . esc_attr( $order_id ) . '" title="' . esc_attr__( 'Sync / Refresh Risk', 'steadfast-fraud-check' ) . '">';
        echo '<span class="dashicons dashicons-update"></span>';
        echo '</button>';
        
        echo '<button type="button" class="sfc-view-fraud-details" data-order-id="' . esc_attr( $order_id ) . '" title="' . esc_attr__( 'View Intel Report', 'steadfast-fraud-check' ) . '">';
        echo '<span class="dashicons dashicons-external"></span>';
        echo '</button>';
        echo '</div>'; // .sfc-column-actions
        
        echo '</div>'; // .sfc-column-risk
    }

    /* ------------------------------------------------------------------ */
    /*  Single Order Meta Box                                               */
    /* ------------------------------------------------------------------ */

    public function add_order_meta_box(): void {
        $screens = [ 'woocommerce_page_wc-orders', 'shop_order' ];
        foreach ( $screens as $screen ) {
            add_meta_box(
                'sfc_fraud_metabox',
                '<span class="dashicons dashicons-shield-alt"></span> ' . __( 'Fraud Check — Steadfast', 'steadfast-fraud-check' ),
                [ $this, 'render_order_meta_box' ],
                $screen,
                'side',
                'high'
            );
        }
    }

    public function render_order_meta_box( $post_or_order ): void {
        $order = $post_or_order instanceof \WC_Order
            ? $post_or_order
            : wc_get_order( $post_or_order->ID );

        if ( ! $order ) return;

        $risk      = $order->get_meta( '_sfc_risk_level' )   ?: '';
        $score     = $order->get_meta( '_sfc_risk_score' )    ?: 0;
        $checked   = $order->get_meta( '_sfc_checked_at' )    ?: '';
        $phone     = $order->get_meta( '_sfc_phone_checked' ) ?: $order->get_billing_phone();
        $raw_json  = $order->get_meta( '_sfc_fraud_data' )    ?: '{}';
        $raw       = json_decode( $raw_json, true ) ?: [];
        $stats     = $raw['stats'] ?? [];

        $checker = SFC_Checker::instance();
        $nonce   = wp_create_nonce( 'sfc_nonce' );

        include SFC_PLUGIN_DIR . 'templates/order-meta-box.php';
    }

    /* ------------------------------------------------------------------ */
    /*  Assets on Order Edit Page                                           */
    /* ------------------------------------------------------------------ */

    public function enqueue_order_assets( string $hook ): void {
        $order_pages = [ 'post.php', 'post-new.php', 'woocommerce_page_wc-orders' ];
        if ( ! in_array( $hook, $order_pages, true ) ) return;

        wp_enqueue_style(
            'sfc-order',
            SFC_PLUGIN_URL . 'assets/css/admin.css',
            [],
            SFC_VERSION
        );

        wp_enqueue_script(
            'sfc-order',
            SFC_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            SFC_VERSION,
            true
        );

        wp_localize_script( 'sfc-order', 'SFC', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'sfc_nonce' ),
            'i18n'    => [
                'checking' => __( 'Checking…', 'steadfast-fraud-check' ),
                'error'    => __( 'Error occurred. Please try again.', 'steadfast-fraud-check' ),
            ],
        ] );
    }
}
