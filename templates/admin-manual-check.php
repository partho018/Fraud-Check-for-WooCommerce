<?php defined( 'ABSPATH' ) || exit; ?>
<div class="sfc-wrap">

    <div class="sfc-header">
        <div class="sfc-header__logo">
            <h1><?php esc_html_e( 'Fraud Intelligence', 'steadfast-fraud-check' ); ?> <span style="color:var(--sfc-primary);">Terminal</span></h1>
            <p><?php esc_html_e( 'Deep-scan customer phone numbers against the Steadfast global merchant network.', 'steadfast-fraud-check' ); ?></p>
        </div>
    </div>

    <div class="sfc-grid-check" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 32px;">
        
        <!-- Search Console -->
        <div class="sfc-card">
            <div class="sfc-card__header">
                <h2><span class="dashicons dashicons-shield-alt" style="color:var(--sfc-primary);"></span> <?php esc_html_e( 'Scan Terminal', 'steadfast-fraud-check' ); ?></h2>
                <span class="sfc-badge sfc-badge--unknown"><?php esc_html_e( 'v1.0 Ready', 'steadfast-fraud-check' ); ?></span>
            </div>
            <div class="sfc-card__body">
                <p style="margin-bottom: 24px; font-size: 14px; color: var(--sfc-text-sm); line-height: 1.5;">
                    <?php esc_html_e( 'Enter the 11-digit mobile number to retrieve real-time delivery performance and risk metrics.', 'steadfast-fraud-check' ); ?>
                </p>

                <div class="sfc-phone-terminal">
                    <span class="dashicons dashicons-phone"></span>
                    <input type="text" id="sfc-phone-input" placeholder="01XXXXXXXXX" maxlength="11" />
                    <button type="button" id="sfc-check-btn" class="sfc-btn sfc-btn--primary">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e( 'Verify', 'steadfast-fraud-check' ); ?>
                    </button>
                </div>

                <div style="margin-top: 24px; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--sfc-border); padding-top: 20px;">
                    <label class="sfc-checkbox" style="font-weight: 500; color: var(--sfc-text-sm);">
                        <input type="checkbox" id="sfc-force-refresh" />
                        <span style="margin-left:8px;"><?php esc_html_e( 'Bypass local cache', 'steadfast-fraud-check' ); ?></span>
                    </label>
                    <span style="font-size:11px; color:var(--sfc-text-xs); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">
                        <?php esc_html_e( 'Steadfast Verified API', 'steadfast-fraud-check' ); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Result Console -->
        <div id="sfc-result-panel" class="sfc-result-panel" style="display:none;">
            <!-- Content dynamically injected via admin.js -->
        </div>

    </div>

    <!-- Recent History -->
    <div style="margin-top:40px;">
        <div class="sfc-card">
            <div class="sfc-card__header">
                <h2><span class="dashicons dashicons-backup" style="color:var(--sfc-primary);"></span> <?php esc_html_e( 'Intelligence History', 'steadfast-fraud-check' ); ?></h2>
                <span style="font-size:12px; font-weight: 600; color: var(--sfc-text-xs);"><?php esc_html_e( 'Last 5 Scans', 'steadfast-fraud-check' ); ?></span>
            </div>
            <div style="padding:0;">
                <?php
                global $wpdb;
                $table = $wpdb->prefix . 'steadfast_fraud_cache';
                $recent = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY checked_at DESC LIMIT 5" );

                if ( $recent ) : ?>
                    <table class="wp-list-table widefat fixed striped" style="border:none; box-shadow: none;">
                        <thead>
                            <tr>
                                <th style="padding: 15px 24px; font-weight: 700;"><?php esc_html_e( 'Subject', 'steadfast-fraud-check' ); ?></th>
                                <th style="padding: 15px 24px; font-weight: 700;"><?php esc_html_e( 'Analysis', 'steadfast-fraud-check' ); ?></th>
                                <th style="padding: 15px 24px; font-weight: 700;"><?php esc_html_e( 'Sync Time', 'steadfast-fraud-check' ); ?></th>
                                <th style="padding: 15px 24px; font-weight: 700; text-align:right;"><?php esc_html_e( 'Action', 'steadfast-fraud-check' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $recent as $row ) : ?>
                                <tr style="transition: background 0.2s;">
                                    <td style="padding: 15px 24px; font-family: 'JetBrains Mono', 'Courier New', monospace; font-size: 15px; font-weight: 700; color: #1e293b;">
                                        <?php echo esc_html( $row->phone ); ?>
                                    </td>
                                    <td style="padding: 15px 24px;">
                                        <span class="sfc-badge sfc-badge--<?php echo esc_attr( $row->risk_level ); ?>">
                                            <?php echo esc_html( SFC_Checker::instance()->risk_label( $row->risk_level ) ); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px 24px; color: var(--sfc-text-xs); font-size: 13px; font-weight: 500;">
                                        <?php echo esc_html( human_time_diff( strtotime( $row->checked_at ), current_time( 'timestamp' ) ) . ' ago' ); ?>
                                    </td>
                                    <td style="padding: 15px 24px; text-align:right;">
                                        <button class="sfc-btn sfc-btn--xs sfc-recheck-btn" style="background: #f1f5f9; color: #475569;" data-phone="<?php echo esc_attr( $row->phone ); ?>">
                                            <span class="dashicons dashicons-update" style="font-size:14px; width:14px; height:14px;"></span>
                                            <?php esc_html_e( 'Deep Scan', 'steadfast-fraud-check' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div style="padding: 60px; text-align:center; color: var(--sfc-text-xs);">
                        <span class="dashicons dashicons-database" style="font-size:40px; width:40px; height:40px; opacity:0.3; margin-bottom:12px;"></span>
                        <p><?php esc_html_e( 'Scan database is currently empty.', 'steadfast-fraud-check' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div> <!-- .sfc-wrap -->
