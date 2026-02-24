<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class SFC_Checker
 * Orchestrates fraud checking: caching, risk scoring, and result storage.
 *
 * Risk levels:
 *   safe   – low return / cancel rate
 *   medium – moderate concern
 *   high   – significant fraud signals
 *   unknown – API not configured or error
 */
class SFC_Checker {

    private static ?SFC_Checker $instance = null;

    public static function instance(): SFC_Checker {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Update fraud data when order status changes to capture success/failure signals
        add_action( 'woocommerce_order_status_changed', [ $this, 'handle_status_change' ], 10, 4 );
    }

    /**
     * When an order status changes, we should update the fraud data for this customer
     * so that the risk level reflects their recent performance (Success/Return).
     */
    public function handle_status_change( $order_id, $from, $to, $order ): void {
        $statuses_of_interest = [ 'completed', 'processing', 'cancelled', 'failed', 'returned' ];
        
        if ( in_array( $to, $statuses_of_interest, true ) ) {
            $phone = $order->get_billing_phone();
            if ( ! empty( $phone ) ) {
                $phone_norm = SFC_API::normalize_phone( $phone );
                
                // 1. Clear cache to force a fresh look at local history
                $this->clear_phone_cache( $phone_norm );
                
                // 2. Re-calculate and update meta for THIS order
                $this->check_and_attach_order( $order_id, $phone_norm );
            }
        }
    }

    /**
     * Clear cache for a specific phone number.
     */
    public function clear_phone_cache( string $phone ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'steadfast_fraud_cache';
        $wpdb->delete( $table, [ 'phone' => $phone ], [ '%s' ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Public Methods                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * Run a fraud check on a phone number, using cache if available.
     *
     * @param  string $phone
     * @param  bool   $force_refresh  Skip cache.
     * @return array {
     *   @type string   $risk_level  'safe'|'medium'|'high'|'unknown'
     *   @type int      $score       0-100
     *   @type array    $raw         Raw API response
     *   @type string   $summary     Human-readable summary
     *   @type bool     $from_cache
     *   @type string   $error       If error occurred
     * }
     */
    public function check( string $phone, bool $force_refresh = false ): array {
        $phone_norm = SFC_API::normalize_phone( $phone );

        if ( strlen( $phone_norm ) !== 11 ) {
            return $this->error_result( sprintf( __( 'Invalid phone number (found %d digits, need 11).', 'steadfast-fraud-check' ), strlen( $phone_norm ) ) );
        }

        $api_data = null;
        $from_cache = false;

        // 1. Try cache for API history
        if ( ! $force_refresh ) {
            $cached = $this->get_cache( $phone_norm );
            if ( $cached && ! empty( $cached['raw'] ) ) {
                $api_data   = $cached['raw'];
                $from_cache = true;
                
                // If it's a cached 'unknown' (0 parcels), maybe it's worth re-checking the API 
                // if it's been a while, but for now we follow the cache hours.
            }
        }

        // 2. Fetch fresh API data if needed
        if ( null === $api_data ) {
            $api      = SFC_API::instance();
            $response = $api->fraud_check( $phone_norm );

            if ( is_wp_error( $response ) ) {
                // If API fails (rate limit etc), we don't return error immediately,
                // we proceed with 0 API stats so local history can still be shown.
                $api_data = [ 'total_parcel' => 0, 'total_delivered' => 0, 'total_returned' => 0, 'total_cancelled' => 0 ];
            } else {
                $api_data = $response;
            }
        }

        // 3. Process results with ALWAYS FRESH local history
        // This ensures returning customers are detected even if API data is cached/empty.
        $processed = $this->process_result( $api_data, $phone_norm );
        $processed['from_cache'] = $from_cache;

        // 4. Update cache with combined data
        $this->save_cache( $phone_norm, $processed );

        return $processed;
    }

    /**
     * Attach fraud check result to a WooCommerce order.
     *
     * @param  int    $order_id
     * @param  string $phone
     * @return array  The fraud check result.
     */
    public function check_and_attach_order( int $order_id, string $phone ): array {
        $result = $this->check( $phone );
        $order  = wc_get_order( $order_id );

        if ( $order ) {
            $order->update_meta_data( '_sfc_risk_level',   $result['risk_level'] );
            $order->update_meta_data( '_sfc_risk_score',   $result['score'] );
            $order->update_meta_data( '_sfc_fraud_data',   wp_json_encode( $result['raw'] ) );
            $order->update_meta_data( '_sfc_checked_at',   current_time( 'mysql' ) );
            $order->update_meta_data( '_sfc_phone_checked', $phone );
            $order->save();

            // Add private order note
            if ( 'unknown' !== $result['risk_level'] ) {
                $icon = $this->risk_icon( $result['risk_level'] );
                /* translators: 1: Icon 2: Risk level label 3: Score 4: Summary text */
                $note = sprintf(
                    __( '%1$s Steadfast Fraud Check — Risk: %2$s (Score: %3$d/100)&#10;%4$s', 'steadfast-fraud-check' ),
                    $icon,
                    strtoupper( $result['risk_level'] ),
                    $result['score'],
                    $result['summary']
                );
                $order->add_order_note( $note, false, false );
            }
        }

        return $result;
    }

    /* ------------------------------------------------------------------ */
    /*  Risk Scoring                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Calculate a risk score (0–100) from API response and local history.
     */
    private function process_result( array $raw, string $phone = '' ): array {
        $data = $raw['data'] ?? $raw;

        $total     = (int) ( $data['total_parcel']   ?? 0 );
        $delivered = (int) ( $data['total_delivered'] ?? 0 );
        $returned  = (int) ( $data['total_returned']  ?? 0 );
        $cancelled = (int) ( $data['total_cancelled'] ?? 0 );

        // Local Stats Analysis
        $local_total = 0;
        if ( ! empty( $phone ) ) {
            $local = $this->get_local_history_stats( $phone );
            $total     += $local['total'];
            $delivered += $local['success'];
            $cancelled += $local['failed'];
            $local_total = $local['total'];
        }

        $delivery_rate = $total > 0 ? round( ( $delivered / $total ) * 100, 1 ) : 0;

        $summary = sprintf(
            /* translators: 1: total 2: delivered 3: returned 4: cancelled 5: delivery rate */
            __( 'Total Orders: %1$d | Delivered: %2$d | Returned: %3$d | Cancelled: %4$d | Delivery Rate: %5$s%%', 'steadfast-fraud-check' ),
            $total,
            $delivered,
            $returned,
            $cancelled,
            $delivery_rate
        );

        if ( $local_total > 0 ) {
            $summary .= ' ' . sprintf( __( '(Includes %d local orders)', 'steadfast-fraud-check' ), $local_total );
        }

        // Score formula: (returned + cancelled) / total
        $bad_rate = $total > 0 ? ( $returned + $cancelled ) / $total : 0;
        $score    = (int) round( $bad_rate * 100 );

        // Determine Risk Level
        if ( $total === 0 ) {
            $risk_level = 'unknown';
        } else {
            $high_threshold   = (int) get_option( 'sfc_high_risk_threshold',   40 );
            $medium_threshold = (int) get_option( 'sfc_medium_risk_threshold',  20 );

            if ( $score >= $high_threshold ) {
                $risk_level = 'high';
            } elseif ( $score >= $medium_threshold ) {
                $risk_level = 'medium';
            } else {
                $risk_level = 'safe';
            }

            // Forced Safety: If they have local historical success, they are probably safe.
            if ( $score === 0 && $total > 0 ) {
                $risk_level = 'safe';
            }
        }

        return [
            'risk_level' => $risk_level,
            'score'      => $score,
            'raw'        => $raw,
            'summary'    => $summary,
            'stats'      => [
                'total'         => $total,
                'success'       => $delivered,
                'cancellation'  => ( $returned + $cancelled ),
                'delivered'     => $delivered,
                'returned'      => $returned,
                'cancelled'     => $cancelled,
                'delivery_rate' => $delivery_rate,
            ],
            'from_cache' => false,
            'error'      => '',
        ];
    }

    /**
     * Get order stats for a phone number from the current WooCommerce database.
     * Uses meta_query for maximum compatibility with both HPOS and Legacy storage.
     */
    private function get_local_history_stats( string $phone ): array {
        $phone_norm = preg_replace( '/\D/', '', $phone );
        
        // Search for variants to catch all previous orders
        $variants = [
            $phone_norm,
            '0' . ltrim( $phone_norm, '0' ),
            '880' . ltrim( $phone_norm, '0' ),
            '+880' . ltrim( $phone_norm, '0' ),
        ];
        $variants = array_unique( array_filter( $variants ) );
        
        $args = [
            'limit'      => -1,
            'status'     => 'any',
            'return'     => 'ids',
            'meta_query' => [
                [
                    'key'     => '_billing_phone',
                    'value'   => $variants,
                    'compare' => 'IN',
                ],
            ],
        ];

        $all_order_ids = wc_get_orders( $args );
        
        $success = 0;
        $failed  = 0;
        
        foreach ( $all_order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) continue;
            
            $status = $order->get_status();
            // Count successful delivery indicators
            if ( in_array( $status, [ 'completed', 'processing' ], true ) ) {
                $success++;
            } 
            // Count returns or cancels as risk signals
            elseif ( in_array( $status, [ 'cancelled', 'failed', 'returned', 'refunded' ], true ) ) {
                $failed++;
            }
        }
        
        return [
            'total'   => $success + $failed,
            'success' => $success,
            'failed'  => $failed,
        ];
    }

    private function error_result( string $message ): array {
        return [
            'risk_level' => 'unknown',
            'score'      => 0,
            'raw'        => [],
            'summary'    => $message,
            'stats'      => [],
            'from_cache' => false,
            'error'      => $message,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Cache                                                               */
    /* ------------------------------------------------------------------ */

    private function get_cache( string $phone ): ?array {
        global $wpdb;
        $table    = $wpdb->prefix . 'steadfast_fraud_cache';
        $hours    = (int) get_option( 'sfc_cache_hours', 6 );

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT result, risk_level FROM {$table} WHERE phone = %s AND checked_at > DATE_SUB(NOW(), INTERVAL %d HOUR) LIMIT 1", // phpcs:ignore
                $phone,
                $hours
            )
        );

        if ( ! $row ) return null;

        $result = json_decode( $row->result, true );
        return is_array( $result ) ? $result : null;
    }

    private function save_cache( string $phone, array $result ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'steadfast_fraud_cache';

        $wpdb->replace(
            $table,
            [
                'phone'      => $phone,
                'result'     => wp_json_encode( $result ),
                'risk_level' => $result['risk_level'],
                'checked_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s' ]
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    public function risk_icon( string $level ): string {
        $icons = [
            'safe'    => '✅',
            'medium'  => '⚠️',
            'high'    => '🚫',
            'unknown' => '❓',
        ];
        return $icons[ $level ] ?? '❓';
    }

    public function risk_label( string $level ): string {
        $labels = [
            'safe'    => __( 'Safe',    'steadfast-fraud-check' ),
            'medium'  => __( 'Medium',  'steadfast-fraud-check' ),
            'high'    => __( 'High',    'steadfast-fraud-check' ),
            'unknown' => __( 'Unknown', 'steadfast-fraud-check' ),
        ];
        return $labels[ $level ] ?? __( 'Unknown', 'steadfast-fraud-check' );
    }

    public function risk_color( string $level ): string {
        $colors = [
            'safe'    => '#10b981',
            'medium'  => '#f59e0b',
            'high'    => '#ef4444',
            'unknown' => '#6b7280',
        ];
        return $colors[ $level ] ?? '#6b7280';
    }
}
