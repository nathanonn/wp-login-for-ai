<?php
/**
 * Main plugin behavior.
 *
 * @package WpLoginForAi
 */

namespace WpLoginForAi;

use WP_User;

/**
 * Handles the local-only autologwp URL shortcut.
 */
class Plugin {
    private const QUERY_PARAM = 'autologwp';

    /**
     * Register WordPress hooks.
     */
    public function boot(): void {
        add_action( 'template_redirect', array( $this, 'handle_autologin_request' ), 0 );
    }

    /**
     * Handle the autologwp query parameter when present.
     */
    public function handle_autologin_request(): void {
        if ( ! array_key_exists( self::QUERY_PARAM, $_GET ) ) {
            return;
        }

        $result = $this->process_request(
            $_GET[ self::QUERY_PARAM ],
            $this->current_request_host(),
            wp_get_environment_type()
        );

        if ( empty( $result['ok'] ) ) {
            $this->send_json_error( $result );
        }

        $user = $result['user'] ?? null;
        if ( ! $user instanceof WP_User ) {
            $this->send_json_error(
                $this->error_result(
                    'invalid_user',
                    'The requested user could not be resolved.',
                    404
                )
            );
        }

        $this->switch_to_user( $user );

        wp_safe_redirect( admin_url() );
        exit;
    }

    /**
     * Validate a shortcut request and resolve the requested user.
     *
     * @param mixed       $raw_identifier Raw autologwp query parameter.
     * @param string|null $host           Request host, optionally with a port.
     * @param string|null $environment    WordPress environment type.
     *
     * @return array<string, mixed>
     */
    public function process_request( $raw_identifier, ?string $host = null, ?string $environment = null ): array {
        if ( ! $this->is_allowed_environment( $environment ) ) {
            return $this->error_result(
                'blocked_environment',
                'The autologwp shortcut is only available in local or development environments.',
                403
            );
        }

        if ( ! $this->is_local_request_host( $host ) ) {
            return $this->error_result(
                'blocked_host',
                'The autologwp shortcut is only available on local development hosts.',
                403
            );
        }

        $identifier = $this->sanitize_identifier( $raw_identifier );
        if ( '' === $identifier ) {
            return $this->error_result(
                'invalid_identifier',
                'The autologwp value must be a username or email address.',
                400
            );
        }

        $user = $this->find_user( $identifier );
        if ( ! $user instanceof WP_User ) {
            return $this->error_result(
                'unknown_user',
                'No WordPress user matched the autologwp value.',
                404
            );
        }

        return array(
            'ok'   => true,
            'user' => $user,
        );
    }

    /**
     * Check whether the current WordPress environment is allowed.
     */
    public function is_allowed_environment( ?string $environment = null ): bool {
        $environment = $environment ?? wp_get_environment_type();

        return in_array( $environment, array( 'local', 'development' ), true );
    }

    /**
     * Check whether a request host is local-development oriented.
     */
    public function is_local_request_host( ?string $host = null ): bool {
        $host = $host ?? $this->current_request_host();
        $host = $this->normalize_host( $host );

        return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );
    }

    /**
     * Resolve a sanitized username or email address through WordPress APIs.
     */
    public function find_user( string $identifier ): ?WP_User {
        if ( is_email( $identifier ) ) {
            $user = get_user_by( 'email', sanitize_email( $identifier ) );

            return $user instanceof WP_User ? $user : null;
        }

        $user = get_user_by( 'login', sanitize_user( $identifier, true ) );

        return $user instanceof WP_User ? $user : null;
    }

    /**
     * Replace the current auth cookies and user state with the requested user.
     */
    public function switch_to_user( WP_User $user ): void {
        wp_clear_auth_cookie();
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true, is_ssl() );

        do_action( 'wp_login', $user->user_login, $user );
    }

    /**
     * Sanitize the autologwp value before lookup.
     *
     * @param mixed $raw_identifier Raw query parameter value.
     */
    public function sanitize_identifier( $raw_identifier ): string {
        if ( is_array( $raw_identifier ) || is_object( $raw_identifier ) ) {
            return '';
        }

        $identifier = trim( sanitize_text_field( wp_unslash( (string) $raw_identifier ) ) );
        if ( '' === $identifier ) {
            return '';
        }

        if ( is_email( $identifier ) ) {
            return sanitize_email( $identifier );
        }

        return sanitize_user( $identifier, true );
    }

    /**
     * Normalize a host value for local-host comparison.
     */
    public function normalize_host( string $host ): string {
        $host = strtolower( trim( sanitize_text_field( wp_unslash( $host ) ) ) );
        if ( '' === $host ) {
            return '';
        }

        if ( str_starts_with( $host, '[' ) ) {
            $closing_bracket = strpos( $host, ']' );
            if ( false !== $closing_bracket ) {
                return substr( $host, 1, $closing_bracket - 1 );
            }
        }

        $host = preg_replace( '/:\d+$/', '', $host );

        return is_string( $host ) ? $host : '';
    }

    /**
     * Build a consistent machine-readable error result.
     *
     * @return array<string, mixed>
     */
    public function error_result( string $code, string $message, int $status ): array {
        return array(
            'ok'      => false,
            'code'    => $code,
            'message' => $message,
            'status'  => $status,
        );
    }

    /**
     * Read the request host without trusting it for anything beyond local gating.
     */
    private function current_request_host(): string {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';

        return is_string( $host ) ? $host : '';
    }

    /**
     * Emit a JSON error and stop the request.
     *
     * @param array<string, mixed> $result Error result from process_request().
     */
    private function send_json_error( array $result ): void {
        $code    = isset( $result['code'] ) ? sanitize_key( (string) $result['code'] ) : 'autologwp_error';
        $message = isset( $result['message'] ) ? esc_html( (string) $result['message'] ) : 'The autologwp request failed.';
        $status  = isset( $result['status'] ) ? absint( $result['status'] ) : 400;

        wp_send_json_error(
            array(
                'code'    => $code,
                'message' => $message,
            ),
            $status
        );
    }
}
