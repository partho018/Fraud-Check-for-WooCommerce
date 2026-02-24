<?php defined( 'ABSPATH' ) || exit;
/**
 * Variables available from SFC_Order_Column::render_order_meta_box():
 * $order, $risk, $score, $checked, $phone, $stats, $checker, $nonce
 */
?>
<div class="sfc-metabox">

    <?php if ( empty( $risk ) ) : ?>

        <div class="sfc-metabox__unchecked">
            <p><?php esc_html_e( 'Fraud check has not been run for this order yet.', 'steadfast-fraud-check' ); ?></p>
            <button type="button"
                    class="button sfc-order-check-btn"
                    data-order-id="<?php echo esc_attr( $order->get_id() ); ?>"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>">
                <span class="dashicons dashicons-shield"></span>
                <?php esc_html_e( 'Run Fraud Check Now', 'steadfast-fraud-check' ); ?>
            </button>
            <div class="sfc-order-result" style="display:none;"></div>
        </div>

    <?php else :
        $color = $checker->risk_color( $risk );
        $label = $checker->risk_label( $risk );
        $icon  = $checker->risk_icon( $risk );
    ?>

        <div class="sfc-metabox__risk-header" style="border-left: 4px solid <?php echo esc_attr( $color ); ?>">
            <div class="sfc-metabox__badge sfc-badge sfc-badge--<?php echo esc_attr( $risk ); ?>">
                <?php echo esc_html( $icon . ' ' . $label ); ?>
            </div>
            <div class="sfc-metabox__score">
                <?php
                /* translators: %d = risk score */
                printf( esc_html__( 'Score: %d/100', 'steadfast-fraud-check' ), (int) $score );
                ?>
            </div>
        </div>

        <?php if ( ! empty( $stats ) ) : ?>
        <div class="sfc-portal-style">
            <div class="sfc-portal-header">
                <span class="sfc-dot sfc-dot--<?php echo esc_attr( $risk ); ?>"></span>
                <strong><?php echo esc_html( $risk === 'safe' ? __( 'Successful', 'steadfast-fraud-check' ) : $label ); ?></strong>
            </div>

            <div class="sfc-portal-stats">
                <div class="sfc-portal-stat">
                    <span class="sfc-box sfc-box--green"></span>
                    <span class="sfc-label"><?php esc_html_e( 'Success :', 'steadfast-fraud-check' ); ?></span>
                    <span class="sfc-value"><?php echo esc_html( $stats['success'] ?? 0 ); ?></span>
                </div>
                <div class="sfc-portal-stat">
                    <span class="sfc-box sfc-box--red"></span>
                    <span class="sfc-label"><?php esc_html_e( 'Cancellation :', 'steadfast-fraud-check' ); ?></span>
                    <span class="sfc-value"><?php echo esc_html( $stats['cancellation'] ?? 0 ); ?></span>
                </div>
            </div>

            <?php if ( $total_orders = ($stats['total'] ?? 0) ) : ?>
            <div class="sfc-portal-footer">
                <small><?php printf( esc_html__( 'Total History Found: %d Orders', 'steadfast-fraud-check' ), (int) $total_orders ); ?></small>
            </div>
            <?php endif; ?>
        </div>

        <!-- Progress bar -->
        <div class="sfc-progress-bar">
            <div class="sfc-progress-bar__fill" style="width:<?php echo esc_attr( $score ); ?>%; background:<?php echo esc_attr( $color ); ?>;"></div>
        </div>
        <?php endif; ?>

        <?php if ( $checked ) : ?>
        <p class="sfc-metabox__timestamp">
            <?php
            /* translators: %s = date/time */
            printf( esc_html__( 'Checked: %s', 'steadfast-fraud-check' ), esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $checked ) ) ) );
            ?>
        </p>
        <?php endif; ?>

        <button type="button"
                class="button sfc-order-check-btn sfc-mt-sm"
                data-order-id="<?php echo esc_attr( $order->get_id() ); ?>"
                data-nonce="<?php echo esc_attr( $nonce ); ?>">
            <span class="dashicons dashicons-update"></span>
            <?php esc_html_e( 'Re-run Check', 'steadfast-fraud-check' ); ?>
        </button>
        <div class="sfc-order-result" style="display:none;"></div>

    <?php endif; ?>

</div><!-- .sfc-metabox -->
