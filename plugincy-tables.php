<?php

/**
 * Plugin Name: Plugincy Tables
 * Description: Create custom WooCommerce product tables with shortcodes
 * Version: 1.0.0
 * Author: Plugincy
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/db.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/all_tables.php';
require_once plugin_dir_path(__FILE__) . 'includes/add_table.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings_page.php';
require_once plugin_dir_path(__FILE__) . 'includes/class_helper.php';

class PlugincyTables
{

    private $table_name;
    private $Plugincy_TablesDB;
    private $Plugincy_TablesAdmin;
    private $Plugincy_add_table;
    private $Plugincy_AllTablesAdmin;
    private $Plugincy_Tables_Helper;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'plugincy_tables';
        $this->Plugincy_TablesDB = new Plugincy_TablesDB();
        $this->Plugincy_TablesAdmin = new Plugincy_TablesAdmin();
        $this->Plugincy_add_table = new Plugincy_add_table();
        $this->Plugincy_AllTablesAdmin = new Plugincy_AllTablesAdmin();
        $this->Plugincy_Tables_Helper = new Plugincy_Tables_Helper();


        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this->Plugincy_TablesAdmin, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_init', array($this, 'handle_form_submissions'));
        add_shortcode('plugincy_table', array($this->Plugincy_Tables_Helper, 'render_table_shortcode'));

        add_action('wp_ajax_plugincy_get_preview_products', array($this->Plugincy_add_table, 'ajax_get_preview_products'));

        register_activation_hook(__FILE__, array($this->Plugincy_TablesDB, 'create_table'));
    }

    public function init()
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>Plugincy Tables requires WooCommerce to be installed and activated.</p></div>';
            });
            return;
        }
    }

    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'plugincy') !== false) {
            wp_enqueue_script('plugincy-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('plugincy-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '1.0.0');

            // Localize script to pass data to JavaScript
            wp_localize_script('plugincy-admin-js', 'plugincy_ajax ', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('plugincy_nonce'),
                'elements_json' => json_decode(file_get_contents(plugin_dir_path(__FILE__) . 'includes/elements.json'), true)
            ));
        }
    }


    public function enqueue_frontend_scripts()
    {
        wp_enqueue_style('plugincy-frontend-css', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function handle_form_submissions()
    {
        // Handle table creation/update
        if (isset($_POST['plugincy_save_table']) && wp_verify_nonce($_POST['plugincy_nonce'], 'plugincy_save_table')) {
            $this->Plugincy_add_table->save_table();
        }

        // Handle table deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete_table' && isset($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_table_' . $_GET['id'])) {
            $this->Plugincy_AllTablesAdmin->delete_table();
        }
    }
}

new PlugincyTables();
