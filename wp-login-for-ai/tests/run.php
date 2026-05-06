<?php
/**
 * Lightweight plugin behavior checks.
 *
 * These tests run through composer inside wp-env's CLI container. They stub the
 * small WordPress surface needed to verify request validation without booting
 * WordPress twice.
 */

if ( ! class_exists( 'WP_User' ) ) {
    class WP_User {
        public int $ID;
        public string $user_login;
        public string $user_email;

        public function __construct( int $id, string $login, string $email ) {
            $this->ID         = $id;
            $this->user_login = $login;
            $this->user_email = $email;
        }
    }
}

if ( ! function_exists( 'wp_get_environment_type' ) ) {
    function wp_get_environment_type(): string {
        return 'local';
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return is_string( $value ) ? stripslashes( $value ) : $value;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $value ): string {
        return trim( strip_tags( (string) $value ) );
    }
}

if ( ! function_exists( 'is_email' ) ) {
    function is_email( $value ) {
        return filter_var( $value, FILTER_VALIDATE_EMAIL ) ? $value : false;
    }
}

if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $value ): string {
        return strtolower( trim( (string) $value ) );
    }
}

if ( ! function_exists( 'sanitize_user' ) ) {
    function sanitize_user( $value, $strict = false ): string {
        $value = trim( (string) $value );

        return $strict ? preg_replace( '/[^a-zA-Z0-9_.@-]/', '', $value ) : $value;
    }
}

if ( ! function_exists( 'get_user_by' ) ) {
    function get_user_by( string $field, string $value ) {
        $users = array(
            new WP_User( 1, 'admin', 'admin@example.com' ),
            new WP_User( 2, 'editor', 'wordpress@example.com' ),
        );

        foreach ( $users as $user ) {
            if ( 'login' === $field && $user->user_login === $value ) {
                return $user;
            }

            if ( 'email' === $field && $user->user_email === strtolower( $value ) ) {
                return $user;
            }
        }

        return false;
    }
}

require_once __DIR__ . '/../src/Plugin.php';

$plugin = new WpLoginForAi\Plugin();

$assert = static function ( bool $condition, string $message ): void {
    if ( ! $condition ) {
        fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
        exit( 1 );
    }
};

$assert( $plugin->is_allowed_environment( 'local' ), 'local environment should be allowed' );
$assert( $plugin->is_allowed_environment( 'development' ), 'development environment should be allowed' );
$assert( ! $plugin->is_allowed_environment( 'production' ), 'production environment should be blocked' );
$assert( ! $plugin->is_allowed_environment( 'staging' ), 'staging environment should be blocked' );

$assert( $plugin->is_local_request_host( 'localhost:8888' ), 'localhost should be allowed' );
$assert( $plugin->is_local_request_host( '127.0.0.1:8888' ), '127.0.0.1 should be allowed' );
$assert( $plugin->is_local_request_host( '[::1]:8888' ), 'IPv6 localhost should be allowed' );
$assert( ! $plugin->is_local_request_host( 'example.com' ), 'non-local host should be blocked' );

$username_result = $plugin->process_request( 'admin', 'localhost:8888', 'local' );
$assert( ! empty( $username_result['ok'] ), 'admin username should resolve' );
$assert( $username_result['user'] instanceof WP_User && 1 === $username_result['user']->ID, 'admin user ID should match' );

$email_result = $plugin->process_request( 'wordpress@example.com', 'localhost:8888', 'local' );
$assert( ! empty( $email_result['ok'] ), 'email address should resolve' );
$assert( $email_result['user'] instanceof WP_User && 2 === $email_result['user']->ID, 'email user ID should match' );

$unknown_result = $plugin->process_request( 'definitely-not-a-user', 'localhost:8888', 'local' );
$assert( empty( $unknown_result['ok'] ) && 'unknown_user' === $unknown_result['code'], 'unknown user should fail safely' );

$unknown_email_result = $plugin->process_request( 'missing@example.com', 'localhost:8888', 'local' );
$assert( empty( $unknown_email_result['ok'] ) && 'unknown_user' === $unknown_email_result['code'], 'unknown email should fail safely' );

$empty_result = $plugin->process_request( '   ', 'localhost:8888', 'local' );
$assert( empty( $empty_result['ok'] ) && 'invalid_identifier' === $empty_result['code'], 'empty identifier should fail safely' );

$array_result = $plugin->process_request( array( 'admin' ), 'localhost:8888', 'local' );
$assert( empty( $array_result['ok'] ) && 'invalid_identifier' === $array_result['code'], 'array identifier should fail safely' );

$blocked_environment = $plugin->process_request( 'admin', 'localhost:8888', 'production' );
$assert( empty( $blocked_environment['ok'] ) && 'blocked_environment' === $blocked_environment['code'], 'production should be blocked' );

$blocked_host = $plugin->process_request( 'admin', 'example.com', 'local' );
$assert( empty( $blocked_host['ok'] ) && 'blocked_host' === $blocked_host['code'], 'non-local host should be blocked' );

echo 'PASS: plugin behavior' . PHP_EOL;
