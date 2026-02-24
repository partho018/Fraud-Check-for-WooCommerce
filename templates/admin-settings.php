<?php defined( 'ABSPATH' ) || exit; ?>
<div class="sfc-wrap">

    <div class="sfc-header" style="display: flex; align-items: center; justify-content: space-between;">
        <div class="sfc-header__logo">
            <h1><?php esc_html_e( 'Global Configuration', 'steadfast-fraud-check' ); ?></h1>
            <p><?php esc_html_e( 'Manage API security, risk sensitivities, and automated shield behaviors.', 'steadfast-fraud-check' ); ?></p>
        </div>
        <div>
            <span class="sfc-badge sfc-badge--safe" style="padding: 8px 16px; font-size: 13px;">
                <span class="dashicons dashicons-shield" style="font-size:16px; margin-right:5px; vertical-align:middle;"></span>
                <?php esc_html_e( 'Deep-Scan Active', 'steadfast-fraud-check' ); ?>
            </span>
        </div>
    </div>

    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'sfc_settings_group' ); ?>

        <div class="sfc-grid-check" style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">

            <!-- Left Column -->
            <div style="display: flex; flex-direction: column; gap: 32px;">
                
                <!-- API Credentials -->
                <div class="sfc-card">
                    <div class="sfc-card__header">
                        <h2><span class="dashicons dashicons-admin-network" style="color:var(--sfc-primary);"></span> <?php esc_html_e( 'API Credentials', 'steadfast-fraud-check' ); ?></h2>
                    </div>
                    <div class="sfc-card__body">
                        <p style="font-size: 13px; color: var(--sfc-text-sm); margin-bottom: 24px;">
                            <?php esc_html_e( 'Retrieve your secure keys from the Steadfast Merchant Portal.', 'steadfast-fraud-check' ); ?>
                            <a href="https://portal.steadfast.com.bd" target="_blank" style="color:var(--sfc-primary); font-weight:700; text-decoration:none;">Visit Portal →</a>
                        </p>

                        <div class="sfc-field" style="margin-bottom: 20px;">
                            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size:13px;"><?php esc_html_e( 'Production API Key', 'steadfast-fraud-check' ); ?></label>
                            <div class="sfc-phone-terminal" style="background: #f8fafc; border: 1px solid var(--sfc-border);">
                                <span class="dashicons dashicons-key" style="margin-left:12px; color:#94a3b8;"></span>
                                <input type="text" name="sfc_api_key" value="<?php echo esc_attr( get_option( 'sfc_api_key' ) ); ?>" placeholder="Enter API Key" style="font-size:15px !important;" />
                            </div>
                        </div>

                        <div class="sfc-field" style="margin-bottom: 24px;">
                            <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 8px; font-size:13px;"><?php esc_html_e( 'Secret Encryption Key', 'steadfast-fraud-check' ); ?></label>
                            <div class="sfc-phone-terminal" style="background: #f8fafc; border: 1px solid var(--sfc-border);">
                                <span class="dashicons dashicons-lock" style="margin-left:12px; color:#94a3b8;"></span>
                                <input type="password" id="sfc_secret_key" name="sfc_secret_key" value="<?php echo esc_attr( get_option( 'sfc_secret_key' ) ); ?>" placeholder="Enter Secret Key" style="font-size:15px !important;" />
                                <button type="button" class="sfc-toggle-password" data-target="sfc_secret_key" style="margin:0 10px;">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                        </div>

                        <div style="border-top: 1px solid var(--sfc-border); padding-top: 20px; display: flex; align-items: center; justify-content: space-between;">
                            <button type="button" id="sfc-test-credentials" class="sfc-btn" style="background:#f1f5f9; color:#475569;">
                                <span class="dashicons dashicons-update"></span>
                                <?php esc_html_e( 'Test Connection', 'steadfast-fraud-check' ); ?>
                            </button>
                            <span id="sfc-test-result" class="sfc-inline-result"></span>
                        </div>
                    </div>
                </div>

                <!-- Checkout Shield -->
                <div class="sfc-card">
                    <div class="sfc-card__header">
                        <h2><span class="dashicons dashicons-cart" style="color:#10b981;"></span> <?php esc_html_e( 'Checkout Shield', 'steadfast-fraud-check' ); ?></h2>
                    </div>
                    <div class="sfc-card__body" style="display: flex; flex-direction: column; gap: 20px;">
                        
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong style="display:block; font-size:14px;"><?php esc_html_e( 'Real-time Validation', 'steadfast-fraud-check' ); ?></strong>
                                <small style="color:var(--sfc-text-sm);"><?php esc_html_e( 'Scan phone numbers during checkout.', 'steadfast-fraud-check' ); ?></small>
                            </div>
                            <label class="sfc-toggle">
                                <input type="checkbox" name="sfc_auto_check_checkout" value="yes" <?php checked( 'yes', get_option( 'sfc_auto_check_checkout', 'yes' ) ); ?> />
                                <span class="sfc-toggle__slider"></span>
                            </label>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 15px; border-top: 1px dashed var(--sfc-border);">
                            <div>
                                <strong style="display:block; font-size:14px;"><?php esc_html_e( 'Block High Risk', 'steadfast-fraud-check' ); ?></strong>
                                <small style="color:var(--sfc-text-sm);"><?php esc_html_e( 'Restrict orders from high-risk users.', 'steadfast-fraud-check' ); ?></small>
                            </div>
                            <label class="sfc-toggle">
                                <input type="checkbox" name="sfc_block_high_risk" value="yes" <?php checked( 'yes', get_option( 'sfc_block_high_risk', 'no' ) ); ?> />
                                <span class="sfc-toggle__slider sfc-toggle__slider--red"></span>
                            </label>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 15px; border-top: 1px dashed var(--sfc-border);">
                            <div>
                                <strong style="display:block; font-size:14px;"><?php esc_html_e( 'Warning Notice', 'steadfast-fraud-check' ); ?></strong>
                                <small style="color:var(--sfc-text-sm);"><?php esc_html_e( 'Prompt users with medium risk scores.', 'steadfast-fraud-check' ); ?></small>
                            </div>
                            <label class="sfc-toggle">
                                <input type="checkbox" name="sfc_warn_medium_risk" value="yes" <?php checked( 'yes', get_option( 'sfc_warn_medium_risk', 'yes' ) ); ?> />
                                <span class="sfc-toggle__slider sfc-toggle__slider--orange"></span>
                            </label>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div style="display: flex; flex-direction: column; gap: 32px;">

                <!-- Risk Calibration -->
                <div class="sfc-card">
                    <div class="sfc-card__header">
                        <h2><span class="dashicons dashicons-performance" style="color:var(--sfc-medium);"></span> <?php esc_html_e( 'Risk Calibration', 'steadfast-fraud-check' ); ?></h2>
                    </div>
                    <div class="sfc-card__body">
                        <p style="font-size: 13px; color: var(--sfc-text-sm); margin-bottom: 24px;">
                            <?php esc_html_e( 'Define the sensitivity of risk level triggers.', 'steadfast-fraud-check' ); ?>
                        </p>

                        <div style="background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid var(--sfc-border);">
                            <div class="sfc-field" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                                <span class="sfc-badge sfc-badge--high" style="font-size: 11px;"><?php esc_html_e( 'High Risk Trigger', 'steadfast-fraud-check' ); ?></span>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="number" name="sfc_high_risk_threshold" value="<?php echo esc_attr( get_option( 'sfc_high_risk_threshold', 40 ) ); ?>" min="1" max="100" style="width: 70px; text-align:center; font-weight:700; border-radius: 6px; border: 1px solid #cbd5e1;" />
                                    <span style="font-weight:700; color:#64748b;">%</span>
                                </div>
                            </div>

                            <div class="sfc-field" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                                <span class="sfc-badge sfc-badge--medium" style="font-size: 11px;"><?php esc_html_e( 'Medium Risk Trigger', 'steadfast-fraud-check' ); ?></span>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="number" name="sfc_medium_risk_threshold" value="<?php echo esc_attr( get_option( 'sfc_medium_risk_threshold', 20 ) ); ?>" min="1" max="100" style="width: 70px; text-align:center; font-weight:700; border-radius: 6px; border: 1px solid #cbd5e1;" />
                                    <span style="font-weight:700; color:#64748b;">%</span>
                                </div>
                            </div>

                            <div class="sfc-field" style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--sfc-border); padding-top: 15px;">
                                <span style="font-size: 13px; font-weight: 700; color: #475569;"><span class="dashicons dashicons-clock" style="font-size:16px; margin-right:5px;"></span> <?php esc_html_e( 'Local Cache', 'steadfast-fraud-check' ); ?></span>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="number" name="sfc_cache_hours" value="<?php echo esc_attr( get_option( 'sfc_cache_hours', 6 ) ); ?>" min="0" max="168" style="width: 70px; text-align:center; font-weight:700; border-radius: 6px; border: 1px solid #cbd5e1;" />
                                    <span style="font-size: 11px; color:#94a3b8; font-weight:600; text-transform:uppercase;">Hrs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Operations -->
                <div class="sfc-card">
                    <div class="sfc-card__header">
                        <h2><span class="dashicons dashicons-list-view" style="color:#8b5cf6;"></span> <?php esc_html_e( 'Order Operations', 'steadfast-fraud-check' ); ?></h2>
                    </div>
                    <div class="sfc-card__body" style="display: flex; flex-direction: column; gap: 20px;">
                        
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong style="display:block; font-size:14px;"><?php esc_html_e( 'Auto-Scan Orders', 'steadfast-fraud-check' ); ?></strong>
                                <small style="color:var(--sfc-text-sm);"><?php esc_html_e( 'Check fraud for every new order.', 'steadfast-fraud-check' ); ?></small>
                            </div>
                            <label class="sfc-toggle">
                                <input type="checkbox" name="sfc_auto_check_order" value="yes" <?php checked( 'yes', get_option( 'sfc_auto_check_order', 'yes' ) ); ?> />
                                <span class="sfc-toggle__slider"></span>
                            </label>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 15px; border-top: 1px dashed var(--sfc-border);">
                            <div>
                                <strong style="display:block; font-size:14px;"><?php esc_html_e( 'Status Badges', 'steadfast-fraud-check' ); ?></strong>
                                <small style="color:var(--sfc-text-sm);"><?php esc_html_e( 'Display risk levels in order list.', 'steadfast-fraud-check' ); ?></small>
                            </div>
                            <label class="sfc-toggle">
                                <input type="checkbox" name="sfc_show_risk_badge" value="yes" <?php checked( 'yes', get_option( 'sfc_show_risk_badge', 'yes' ) ); ?> />
                                <span class="sfc-toggle__slider"></span>
                            </label>
                        </div>

                    </div>
                </div>

            </div>

        </div><!-- .grid -->

        <div style="margin-top: 40px; background: #fff; border-radius: 16px; padding: 24px; border: 1px solid var(--sfc-border); display: flex; align-items: center; justify-content: space-between; box-shadow: var(--sfc-shadow);">
            <div>
                <strong style="font-size:16px; display:block;"><?php esc_html_e( 'Ready to Deploy?', 'steadfast-fraud-check' ); ?></strong>
                <p style="margin:0; font-size:13px; color:var(--sfc-text-sm);"><?php esc_html_e( 'Ensure all credentials are tested before saving changes.', 'steadfast-fraud-check' ); ?></p>
            </div>
            <?php submit_button( __( 'Deploy Settings', 'steadfast-fraud-check' ), 'sfc-btn sfc-btn--primary', 'submit', false, [ 'style' => 'padding: 12px 32px; font-size: 15px;' ] ); ?>
        </div>

    </form>
</div><!-- .sfc-wrap -->
