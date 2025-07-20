<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin functions for the plugin.
 *
 * @package Plugincy Tables
 */

class Plugincy_TablesAdmin {

    private $Plugincy_AllTablesAdmin;
    private $Plugincy_add_table;
    private $Plugincy_settings;

    public function __construct() {
        $this->Plugincy_AllTablesAdmin = new Plugincy_AllTablesAdmin();
        $this->Plugincy_add_table = new Plugincy_add_table();
        $this->Plugincy_settings = new Plugincy_settings();
        
    }

     public function add_admin_menu()
    {
        add_menu_page(
            'Plugincy Tables',
            'Plugincy Tables',
            'manage_options',
            'plugincy-tables',
            array($this->Plugincy_AllTablesAdmin, 'admin_page_all_tables'),
            'dashicons-grid-view',
            25
        );

        add_submenu_page(
            'plugincy-tables',
            'All Tables',
            'All Tables',
            'manage_options',
            'plugincy-tables',
            array($this->Plugincy_AllTablesAdmin, 'admin_page_all_tables')
        );

        add_submenu_page(
            'plugincy-tables',
            'Add New Table',
            'Add New Table',
            'manage_options',
            'plugincy-add-table',
            array($this->Plugincy_add_table, 'admin_page_add_table')
        );

        add_submenu_page(
            'plugincy-tables',
            'Settings',
            'Settings',
            'manage_options',
            'plugincy-settings',
            array($this->Plugincy_settings, 'admin_page_settings')
        );
    }
}

