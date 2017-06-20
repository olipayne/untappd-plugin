<?php
/*
Plugin Name: Untappd
Plugin URI:  https://github.com/olipayne/Untappd-Shortcode-Plugin
Description: Plugin to show Untappd info
Version:     0.1
Author:      Oliver Payne
Author URI:  https://github.com/olipayne/
*/

/*
 * This plugin was built on top of WordPress-Plugin-Skeleton by Ian Dunn.
 * See https://github.com/iandunn/WordPress-Plugin-Skeleton for details.
 */

if (! defined('ABSPATH')) {
    die('Access denied.');
}

define('Untappd_NAME', 'Untappd');
define('Untappd_REQUIRED_PHP_VERSION', '5.3');                          // because of get_called_class()
define('Untappd_REQUIRED_WP_VERSION', '3.1');                          // because of esc_textarea()

/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 */
function untappd_requirements_met()
{
    global $wp_version;

    if (version_compare(PHP_VERSION, Untappd_REQUIRED_PHP_VERSION, '<')) {
        return false;
    }

    if (version_compare($wp_version, Untappd_REQUIRED_WP_VERSION, '<')) {
        return false;
    }

    return true;
}

/**
 * Prints an error that the system requirements weren't met.
 */
function untappd_requirements_error()
{
    global $wp_version;

    require_once(dirname(__FILE__) . '/views/requirements-error.php');
}

/*
 * Check requirements and load main class
 * The main program needs to be in a separate file that only gets loaded if the plugin requirements are met. Otherwise older PHP installations could crash when trying to parse it.
 */
if (untappd_requirements_met()) {
    require_once(__DIR__ . '/vendor/autoload.php');
    require_once(__DIR__ . '/classes/untappd-module.php');
    require_once(__DIR__ . '/classes/untappd-plugin.php');
    require_once(__DIR__ . '/includes/admin-notice-helper/admin-notice-helper.php');
    require_once(__DIR__ . '/classes/untappd-settings.php');
    require_once(__DIR__ . '/classes/untappd-cron.php');
    require_once(__DIR__ . '/classes/untappd-instance-class.php');

    if (class_exists('Untappd_Plugin')) {
        $GLOBALS['untappd'] = Untappd_Plugin::get_instance();
        register_activation_hook(__FILE__, array( $GLOBALS['untappd'], 'activate' ));
        register_deactivation_hook(__FILE__, array( $GLOBALS['untappd'], 'deactivate' ));
    }
} else {
    add_action('admin_notices', 'untappd_requirements_error');
}
