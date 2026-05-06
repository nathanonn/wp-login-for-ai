<?php
/**
 * Verify AC-003.1 environment gating.
 */

$plugin = new WpLoginForAi\Plugin();

$cases = array(
    'local'       => true,
    'development' => true,
    'production'  => false,
    'staging'     => false,
);

foreach ( $cases as $environment => $expected ) {
    $actual = $plugin->is_allowed_environment( $environment );
    if ( $actual !== $expected ) {
        echo sprintf(
            "FAIL: environment gate %s expected %s got %s\n",
            $environment,
            $expected ? 'allowed' : 'blocked',
            $actual ? 'allowed' : 'blocked'
        );
        exit( 1 );
    }
}

echo "PASS: environment gate\n";
