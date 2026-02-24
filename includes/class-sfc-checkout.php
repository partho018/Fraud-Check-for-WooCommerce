<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class SFC_Checkout
 *
 * - Validates phone at checkout when "Block high risk" is enabled.
 * - Triggers fraud check when a new order is placed.
 * - Shows a notice on checkout for medium-risk customers.
 */
class SFC_Checkout {

    private static ?SFC_Checkout $instance = null;

    public static function instance(): SFC_Checkout {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( 'yes' === get_option( 'sfc_auto_check_checkout', 'yes' ) ) {
            add_action( 'woocommerce_checkout_process',          [ $this, 'validate_on_checkout' ] );
        }

        if ( 'yes' === get_option( 'sfc_auto_check_order', 'yes' ) ) {
            add_action( 'woocommerce_checkout_order_created',    [ $this, 'check_on_order_created' ] );
            add_action( 'woocommerce_new_order',                 [ $this, 'check_on_new_order' ] );
        }
    }

    /**
     * Validate the phone number before the order is placed.
     * Blocks high-risk customers if the setting is enabled.
     */
    public function validate_on_checkout(): void {
        $phone = isset( $_POST['billing_phone'] ) // phpcs:ignore
            ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) // phpcs:ignore
            : '';

        if ( empty( $phone ) ) return;

        $block_high  = 'yes' === get_option( 'sfc_block_high_risk',  'no' );
        $warn_medium = 'yes' === get_option( 'sfc_warn_medium_risk', 'yes' );

        if ( ! $block_high && ! $warn_medium ) return;

        $result = SFC_Checker::instance()->check( $phone );

        if ( $block_high && 'high' === $result['risk_level'] ) {
            wc_add_notice(
                __( 'আপনার ফোন নম্বরে অনেক রিটার্ন বা বাতিল ডেলিভারি পাওয়া গেছে। এই নম্বর থেকে অর্ডার গ্রহণ করা সম্ভব হচ্ছে না। বিস্তারিত জানতে যোগাযোগ করুন।', 'steadfast-fraud-check' ),
                'error'
            );
        }
    }

    /**
     * WooCommerce 6+ hook – fires immediately after order is created from checkout.
     *
     * @param \WC_Order $order
     */
    public function check_on_order_created( \WC_Order $order ): void {
        $phone = $order->get_billing_phone();
        if ( empty( $phone ) ) return;

        SFC_Checker::instance()->check_and_attach_order( $order->get_id(), $phone );
    }

    /**
     * Fallback for older WC – woocommerce_new_order passes the order ID.
     *
     * @param int $order_id
     */
    public function check_on_new_order( int $order_id ): void {
        // Avoid double-running if check_on_order_created already ran.
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
        if ( $order->get_meta( '_sfc_checked_at' ) ) return;

        $phone = $order->get_billing_phone();
        if ( empty( $phone ) ) return;

        SFC_Checker::instance()->check_and_attach_order( $order_id, $phone );
    }
}
