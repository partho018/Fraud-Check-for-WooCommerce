<?php defined( 'ABSPATH' ) || exit; ?>
<div class="sfc-wrap">

    <div class="sfc-header">
        <div class="sfc-header__logo">
            <h1><?php esc_html_e( 'Fraud Shield', 'steadfast-fraud-check' ); ?> <span style="color:var(--sfc-primary);">Overview</span></h1>
            <p><?php esc_html_e( 'Monitor real-time security metrics and merchant network intelligence.', 'steadfast-fraud-check' ); ?></p>
        </div>
        
        <?php if ( ! $api_configured ) : ?>
            <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 15px 24px; border-radius: 12px; display: flex; align-items: center; gap: 15px; margin-top: 20px;">
                <span class="dashicons dashicons-warning" style="color: #d97706; font-size: 24px; width:24px; height:24px;"></span>
                <div style="flex: 1; color: #92400e; font-size: 14px; font-weight: 500;">
                    <?php esc_html_e( 'Steadfast API is not configured. Real-time protection is inactive.', 'steadfast-fraud-check' ); ?>
                </div>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sfc-settings' ) ); ?>" class="sfc-btn" style="background:#d97706; color:#fff;">
                    <?php esc_html_e( 'Setup API', 'steadfast-fraud-check' ); ?>
                </a>
            </div>
        <?php else : ?>
            <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
                <span style="display:inline-block; width:8px; height:8px; background:#10b981; border-radius:50%; box-shadow: 0 0 0 4px rgba(16,185,129,0.1);"></span>
                <span style="font-size: 12px; font-weight: 700; color: #10b981; text-transform: uppercase; letter-spacing: 0.05em;">
                    <?php esc_html_e( 'Merchant Network Connected', 'steadfast-fraud-check' ); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Premium Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 40px;">
        
        <!-- Total Scans -->
        <div class="sfc-card" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none;">
            <div class="sfc-card__body" style="padding: 24px; color: #fff;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-size: 13px; font-weight: 600; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'Global Scans', 'steadfast-fraud-check' ); ?></span>
                    <span class="dashicons dashicons-database" style="opacity: 0.5;"></span>
                </div>
                <div style="font-size: 32px; font-weight: 800;"><?php echo esc_html( $stats->total ?? 0 ); ?></div>
                <div style="margin-top: 8px; font-size: 12px; opacity: 0.7;"><?php esc_html_e( 'Total intelligence reports', 'steadfast-fraud-check' ); ?></div>
            </div>
        </div>

        <!-- Safe -->
        <div class="sfc-card">
            <div class="sfc-card__body" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'Safe Profiles', 'steadfast-fraud-check' ); ?></span>
                    <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                </div>
                <div style="font-size: 32px; font-weight: 800; color: #1e293b;"><?php echo esc_html( $stats->safe_count ?? 0 ); ?></div>
                <div style="margin-top: 8px; display: flex; align-items: center; gap: 5px;">
                    <span style="width: 12px; height: 4px; background: #10b981; border-radius: 2px;"></span>
                    <span style="font-size: 12px; color: #10b981; font-weight: 600;"><?php esc_html_e( 'Secure Traffic', 'steadfast-fraud-check' ); ?></span>
                </div>
            </div>
        </div>

        <!-- Medium Risk -->
        <div class="sfc-card">
            <div class="sfc-card__body" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'Medium Risk', 'steadfast-fraud-check' ); ?></span>
                    <span class="dashicons dashicons-warning" style="color: #f59e0b;"></span>
                </div>
                <div style="font-size: 32px; font-weight: 800; color: #1e293b;"><?php echo esc_html( $stats->medium_count ?? 0 ); ?></div>
                <div style="margin-top: 8px; display: flex; align-items: center; gap: 5px;">
                    <span style="width: 12px; height: 4px; background: #f59e0b; border-radius: 2px;"></span>
                    <span style="font-size: 12px; color: #f59e0b; font-weight: 600;"><?php esc_html_e( 'Requires Attention', 'steadfast-fraud-check' ); ?></span>
                </div>
            </div>
        </div>

        <!-- High Risk -->
        <div class="sfc-card">
            <div class="sfc-card__body" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'Critical Risk', 'steadfast-fraud-check' ); ?></span>
                    <span class="dashicons dashicons-dismiss" style="color: #ef4444;"></span>
                </div>
                <div style="font-size: 32px; font-weight: 800; color: #1e293b;"><?php echo esc_html( $stats->high_count ?? 0 ); ?></div>
                <div style="margin-top: 8px; display: flex; align-items: center; gap: 5px;">
                    <span style="width: 12px; height: 4px; background: #ef4444; border-radius: 2px;"></span>
                    <span style="font-size: 12px; color: #ef4444; font-weight: 600;"><?php esc_html_e( 'Threats Blocked', 'steadfast-fraud-check' ); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="sfc-grid-check" style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">

        <!-- Recent Risky Checks -->
        <div class="sfc-card">
            <div class="sfc-card__header">
                <h2><span class="dashicons dashicons-visibility" style="color:var(--sfc-primary);"></span> <?php esc_html_e( 'Recent Risk Triggers', 'steadfast-fraud-check' ); ?></h2>
            </div>
            <div style="padding:0;">
                <?php if ( empty( $recent_high ) ) : ?>
                    <div style="padding: 60px; text-align:center; color: var(--sfc-text-xs);">
                        <span class="dashicons dashicons-shield" style="font-size:40px; width:40px; height:40px; opacity:0.2; margin-bottom:12px;"></span>
                        <p><?php esc_html_e( 'Safe zone: No high risk scans detected.', 'steadfast-fraud-check' ); ?></p>
                    </div>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped" style="border:none; box-shadow: none;">
                        <thead>
                            <tr>
                                <th style="padding: 15px 24px; font-weight: 700;"><?php esc_html_e( 'Phone', 'steadfast-fraud-check' ); ?></th>
                                <th style="padding: 15px 24px; font-weight: 700;"><?php esc_html_e( 'Status', 'steadfast-fraud-check' ); ?></th>
                                <th style="padding: 15px 24px; font-weight: 700;"><?php esc_html_e( 'Analyzed', 'steadfast-fraud-check' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $checker = SFC_Checker::instance();
                        foreach ( $recent_high as $row ) :
                            $display_phone = substr( $row->phone, 0, 4 ) . '***' . substr( $row->phone, -3 );
                        ?>
                            <tr>
                                <td style="padding: 15px 24px; font-family: monospace; font-size: 14px; font-weight: 700; color: #334155;"><?php echo esc_html( $display_phone ); ?></td>
                                <td style="padding: 15px 24px;">
                                    <span class="sfc-badge sfc-badge--<?php echo esc_attr( $row->risk_level ); ?>" style="font-size: 10px;">
                                        <?php echo esc_html( $checker->risk_label( $row->risk_level ) ); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 24px; color: var(--sfc-text-xs); font-size: 12px;"><?php echo esc_html( human_time_diff( strtotime( $row->checked_at ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent High Risk Orders -->
        <div class="sfc-card">
            <div class="sfc-card__header">
                <h2><span class="dashicons dashicons-warning" style="color:#ef4444;"></span> <?php esc_html_e( 'Flagged Orders', 'steadfast-fraud-check' ); ?></h2>
            </div>
            <div style="padding:0;">
                <?php if ( empty( $high_orders ) ) : ?>
                    <div style="padding: 60px; text-align:center; color: var(--sfc-text-xs);">
                        <span class="dashicons dashicons-saved" style="font-size:40px; width:40px; height:40px; opacity:0.2; margin-bottom:12px;"></span>
                        <p><?php esc_html_e( 'All recent orders passed the risk threshold.', 'steadfast-fraud-check' ); ?></p>
                    </div>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped" style="border:none; box-shadow: none;">
                        <thead>
                            <tr>
                                <th style="padding: 15px 24px; font-weight: 700;"><?php esc_html_e( 'Order', 'steadfast-fraud-check' ); ?></th>
                                <th style="padding: 15px 24px; font-weight: 700;"><?php esc_html_e( 'Risk Level', 'steadfast-fraud-check' ); ?></th>
                                <th style="padding: 15px 24px; font-weight: 700; text-align:right;"><?php esc_html_e( 'Action', 'steadfast-fraud-check' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $high_orders as $horder ) : ?>
                            <tr>
                                <td style="padding: 15px 24px;">
                                    <a href="<?php echo esc_url( $horder->get_edit_order_url() ); ?>" style="text-decoration:none; font-weight:800; color:var(--sfc-primary);">
                                        #<?php echo esc_html( $horder->get_order_number() ); ?>
                                    </a>
                                    <div style="font-size: 11px; color:#94a3b8; margin-top:2px;"><?php echo esc_html( $horder->get_billing_first_name() . ' ' . $horder->get_billing_last_name() ); ?></div>
                                </td>
                                <td style="padding: 15px 24px;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="flex:1; height:6px; background:#f1f5f9; border-radius:10px; width:60px; overflow:hidden;">
                                            <div style="height:100%; background:#ef4444; width:<?php echo esc_attr( $horder->get_meta( '_sfc_risk_score' ) ); ?>%;"></div>
                                        </div>
                                        <span style="font-size:12px; font-weight:800; color:#ef4444;"><?php echo esc_html( $horder->get_meta( '_sfc_risk_score' ) ); ?>%</span>
                                    </div>
                                </td>
                                <td style="padding: 15px 24px; text-align:right;">
                                    <a href="<?php echo esc_url( $horder->get_edit_order_url() ); ?>" class="sfc-btn" style="background:#f1f5f9; color:#475569; padding: 6px 12px; font-size: 11px;">
                                        <?php esc_html_e( 'Examine', 'steadfast-fraud-check' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Quick Operations -->
    <div style="margin-top:40px; display: grid; grid-template-columns: 1.5fr 1fr; gap: 32px; align-items: center;">
        <div style="background: #fff; border-radius: 16px; padding: 32px; border: 1px solid var(--sfc-border); display: flex; align-items: center; gap: 32px; box-shadow: var(--sfc-shadow);">
            <div style="flex:1;">
                <h3 style="margin:0; font-size:18px; font-weight:800; color:#1e293b;"><?php esc_html_e( 'Operational Quick Actions', 'steadfast-fraud-check' ); ?></h3>
                <p style="margin:8px 0 0; font-size:13px; color:var(--sfc-text-sm);"><?php esc_html_e( 'Access core security tools and system maintenance utilities.', 'steadfast-fraud-check' ); ?></p>
            </div>
            <div style="display:flex; gap:12px;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sfc-manual-check' ) ); ?>" class="sfc-btn sfc-btn--primary" style="padding: 12px 24px;">
                    <span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Manual Terminal', 'steadfast-fraud-check' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sfc-settings' ) ); ?>" class="sfc-btn" style="background:#f1f5f9; color:#475569; padding: 12px 24px;">
                    <span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Control Panel', 'steadfast-fraud-check' ); ?>
                </a>
            </div>
        </div>
        
        <div style="text-align:right;">
            <button type="button" id="sfc-clear-cache-btn" class="sfc-btn" style="background: transparent; color: #ef4444; border: 1px solid #fee2e2; padding: 12px 20px;">
                <span class="dashicons dashicons-trash" style="font-size:16px; margin-right:5px;"></span>
                <?php esc_html_e( 'Purge Security Cache', 'steadfast-fraud-check' ); ?>
            </button>
        </div>
    </div>

</div><!-- .sfc-wrap -->
