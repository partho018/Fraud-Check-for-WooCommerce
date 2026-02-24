<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class SFC_API
 * Handles HTTP requests to the Steadfast Courier API.
 *
 * Base URL : https://portal.steadfast.com.bd/api/v1
 * Auth     : Headers  Api-Key: <key>   Secret-Key: <secret>
 * Fraud    : GET /fraud_check/{phone}
 */
class SFC_API {

    const BASE_URL      = 'https://portal.packzy.com/api/v1';
    const FALLBACK_URL  = 'https://portal.steadfast.com.bd/api/v1';

    private static ?SFC_API $instance = null;

    private string $api_key;
    private string $secret_key;

    public static function instance(): SFC_API {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api_key    = (string) get_option( 'sfc_api_key',    '' );
        $this->secret_key = (string) get_option( 'sfc_secret_key', '' );
    }

    private function get_headers(): array {
        return [
            'Api-Key'    => $this->api_key,
            'ApiKey'     => $this->api_key,
            'Secret-Key' => $this->secret_key,
            'Accept'     => 'application/json',
        ];
    }

    /** Check if credentials are set. */
    public function has_credentials(): bool {
        return ! empty( $this->api_key ) && ! empty( $this->secret_key );
    }

    /**
     * Reload credentials from DB.
     */
    public function reload_credentials(): void {
        $this->api_key    = trim( (string) get_option( 'sfc_api_key',    '' ) );
        $this->secret_key = trim( (string) get_option( 'sfc_secret_key', '' ) );
    }

    /**
     * Normalize Bangladeshi phone numbers.
     */
    public static function normalize_phone( string $phone ): string {
        $phone = preg_replace( '/\D/', '', $phone );
        if ( str_starts_with( $phone, '880' ) ) {
            $phone = substr( $phone, 3 );
        }
        if ( strlen( $phone ) === 10 && str_starts_with( $phone, '1' ) ) {
            $phone = '0' . $phone;
        }
        return $phone;
    }

    /**
     * Core request method using raw cURL for maximum reliability.
     */
    private function remote_request( string $url, string $method = 'GET', array $params = [] ) {
        $ch = curl_init();
        
        $headers_raw = $this->get_headers();
        $headers = [];
        foreach ( $headers_raw as $k => $v ) {
            $headers[] = "$k: $v";
        }

        if ( 'POST' === $method ) {
            $headers[] = 'Content-Type: application/json';
        }

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Steadfast-WooCommerce-Plugin/1.0',
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4, // Helps on some local environments
        ];

        if ( 'POST' === $method ) {
            $options[CURLOPT_POST] = true;
            if ( ! empty( $params ) ) {
                $options[CURLOPT_POSTFIELDS] = json_encode( $params );
            }
        }

        curl_setopt_array( $ch, $options );

        $response = curl_exec( $ch );
        $code     = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $error    = curl_error( $ch );
        curl_close( $ch );

        if ( $error ) {
            return new WP_Error( 'curl_err', $error );
        }

        return [
            'code' => $code,
            'body' => $response,
        ];
    }

    /**
     * Call GET /get_balance (Tests credentials)
     */
    public function get_balance() {
        $this->reload_credentials();
        if ( ! $this->has_credentials() ) {
            return new WP_Error( 'sfc_no_credentials', 'Credentials not set.' );
        }

        $urls = [ self::FALLBACK_URL, self::BASE_URL ];
        $last_error = null;

        foreach ( $urls as $base ) {
            $res = $this->remote_request( $base . '/get_balance' );
            if ( is_wp_error( $res ) ) {
                $last_error = $res;
                continue;
            }

            $code = (int) $res['code'];
            $body = $res['body'];
            $data = json_decode( $body, true );

            if ( 200 === $code ) {
                return $data;
            }

            $last_error = new WP_Error( 'sfc_api_err', "Status $code: $body" );
        }

        return $last_error;
    }

    /**
     * Call GET /fraud_check/{phone}
     */
    public function fraud_check( string $phone ) {
        $this->reload_credentials();

        if ( ! $this->has_credentials() ) {
            return new WP_Error( 'sfc_no_credentials', __( 'API credentials missing.', 'steadfast-fraud-check' ) );
        }

        $phone_raw = self::normalize_phone( $phone );
        
        // We will try the most standard 11-digit format first.
        // Redundant attempts (880... and 1...) often consume search limits on the API side.
        $phones = [ $phone_raw ]; 
        
        // Only try other formats if the first one fails and isn't a 429.
        if ( strlen( $phone_raw ) === 11 && str_starts_with( $phone_raw, '01' ) ) {
             // Already standard.
        } else {
             // If not standard, maybe try one more.
             $phones[] = '0' . ltrim( $phone_raw, '0' );
        }
        $phones = array_values( array_unique( array_filter( $phones ) ) );

        $urls = [ self::FALLBACK_URL, self::BASE_URL ];
        $last_error = null;

        foreach ( $urls as $base ) {
            foreach ( $phones as $p ) {
                $url = $base . '/fraud_check/' . $p;
                
                $res = $this->remote_request( $url, 'GET' );
                
                if ( is_wp_error( $res ) ) {
                    $last_error = $res;
                    continue;
                }

                $code = (int) $res['code'];
                $body = $res['body'];
                $data = json_decode( $body, true );

                // If success, return immediately.
                if ( 200 === $code && ! empty( $data ) ) {
                    return $data;
                }

                // If Rate Limited, STOP EVERYTHING. Do not try other formats or URLs.
                if ( 429 === $code ) {
                    $msg = __( 'Rate limit exceeded. Your Steadfast API plan has a daily search limit (usually 10-20 searches for standard merchants). Please check your Steadfast dashboard to increase your limit.', 'steadfast-fraud-check' );
                    return new WP_Error( 'sfc_rate_limit', $msg );
                }

                if ( 404 === $code ) {
                    $last_error = new WP_Error( 'sfc_404', 'Status 404: Not Found' );
                    continue; // Maybe try another format if we have one.
                }

                $last_error = new WP_Error( 'sfc_api_err', "Status $code: $body" );
            }
            
            // If the first URL failed with anything other than 404, we might not want to spam the second URL.
            // But for now, we continue the outer loop for the fallback URL.
        }

        // If all attempts returned 404, it means the customer has no history.
        if ( $last_error && $last_error->get_error_code() === 'sfc_404' ) {
             return [ 'total_parcel' => 0, 'total_delivered' => 0, 'total_returned' => 0, 'total_cancelled' => 0 ];
        }

        return $last_error ?: new WP_Error( 'sfc_api_err', 'No data found' );
    }
}
