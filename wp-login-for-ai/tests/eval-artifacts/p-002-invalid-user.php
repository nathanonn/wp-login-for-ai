<?php
/**
 * Verify AC-003.3 invalid users do not change the current session state.
 */

$admin = get_user_by( 'login', 'admin' );
if ( ! $admin instanceof WP_User ) {
    echo "FAIL: admin user missing\n";
    exit( 1 );
}

wp_set_current_user( $admin->ID );
$before = get_current_user_id();

$plugin = new WpLoginForAi\Plugin();
$result = $plugin->process_request( 'definitely-not-a-user', 'localhost:8888', 'local' );
$after  = get_current_user_id();

if ( ! empty( $result['ok'] ) || 'unknown_user' !== ( $result['code'] ?? '' ) ) {
    echo "FAIL: invalid user did not return unknown_user\n";
    exit( 1 );
}

if ( $before !== $after ) {
    echo sprintf( "FAIL: current user changed from %d to %d\n", $before, $after );
    exit( 1 );
}

echo "PASS: invalid user preserves session\n";
