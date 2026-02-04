<?php
/**
 * Plugin Name: FAQ
 * Plugin URI: https://github.com/nonatech-uk/wp-faq
 * Description: Manage and display FAQs with Meilisearch integration
 * Version: 1.0.0
 * Author: NonaTech Services Ltd
 * License: GPL v2 or later
 * Text Domain: faq
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PARISH_FAQ_VERSION', '1.2.1');
define('PARISH_FAQ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PARISH_FAQ_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once PARISH_FAQ_PLUGIN_DIR . 'includes/class-parish-faq.php';
require_once PARISH_FAQ_PLUGIN_DIR . 'includes/class-parish-faq-meilisearch.php';
require_once PARISH_FAQ_PLUGIN_DIR . 'includes/class-github-updater.php';

function parish_faq_init() {
    $plugin = new Parish_FAQ();
    $plugin->init();

    // Initialize GitHub updater
    if (is_admin()) {
        new FAQ_GitHub_Updater(
            __FILE__,
            'nonatech-uk/wp-faq',
            PARISH_FAQ_VERSION
        );
    }
}
add_action('plugins_loaded', 'parish_faq_init');

function parish_faq_activate() {
    // Flush rewrite rules for custom post type
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'parish_faq_activate');

function parish_faq_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'parish_faq_deactivate');
