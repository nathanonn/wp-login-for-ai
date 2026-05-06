<?php
/**
 * Plugin Name: WP Login for AI
 * Description: Local development helper that lets AI agents switch WordPress users through a URL.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Requires at least: 6.0
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_LOGIN_FOR_AI_VERSION', '0.1.0' );
define( 'WP_LOGIN_FOR_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_LOGIN_FOR_AI_URL', plugin_dir_url( __FILE__ ) );

$autoload = WP_LOGIN_FOR_AI_PATH . 'vendor/autoload.php';
if ( file_exists( $autoload ) ) {
    require_once $autoload;
}

$plugin_class = WP_LOGIN_FOR_AI_PATH . 'src/Plugin.php';
if ( ! class_exists( 'WpLoginForAi\\Plugin' ) && file_exists( $plugin_class ) ) {
    require_once $plugin_class;
}

register_activation_hook(
    __FILE__,
    function () {
        // No setup is required for the initial scaffold.
    }
);

register_deactivation_hook(
    __FILE__,
    function () {
        // No cleanup is required for the initial scaffold.
    }
);

add_action(
    'plugins_loaded',
    function () {
        if ( class_exists( 'WpLoginForAi\\Plugin' ) ) {
            ( new WpLoginForAi\Plugin() )->boot();
        }
    }
);
