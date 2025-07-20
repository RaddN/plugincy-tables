<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin functions for the plugin.
 *
 * @package Plugincy Tables
 */

class Plugincy_AllTablesAdmin {

    private $table_name;

    public function __construct() {
         global $wpdb;
        $this->table_name = $wpdb->prefix . 'plugincy_tables';

    }

    public function admin_page_all_tables()
    {
        global $wpdb;

        // Display success/error messages
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';
            echo '<div class="notice notice-' . $type . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }

        $tables = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC");

?>
        <div class="wrap">
            <h1>Plugincy Tables
                <a href="<?php echo admin_url('admin.php?page=plugincy-add-table'); ?>" class="page-title-action">Add New</a>
            </h1>

            <div class="plugincy-tables-list">
                <?php if (empty($tables)): ?>
                    <div class="plugincy-no-tables">
                        <div class="plugincy-no-tables-icon">📊</div>
                        <h2>No tables found</h2>
                        <p>Create your first product table to get started.</p>
                        <a href="<?php echo admin_url('admin.php?page=plugincy-add-table'); ?>" class="button button-primary">Create Table</a>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Shortcode</th>
                                <th>Created By</th>
                                <th>Created On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $table): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($table->title); ?></strong></td>
                                    <td>
                                        <code>[plugincy_table id="<?php echo $table->id; ?>"]</code>
                                        <button type="button" class="button button-small copy-shortcode" data-shortcode="[plugincy_table id=&quot;<?php echo $table->id; ?>&quot;]">Copy</button>
                                    </td>
                                    <td><?php echo get_userdata($table->created_by)->display_name; ?></td>
                                    <td><?php echo date('M j, Y g:i a', strtotime($table->created_at)); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=plugincy-add-table&edit=' . $table->id); ?>" class="button button-small">Edit</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=plugincy-tables&action=delete_table&id=' . $table->id), 'delete_table_' . $table->id); ?>"
                                            class="button button-small button-link-delete"
                                            onclick="return confirm('Are you sure you want to delete this table?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php
    }

    public function delete_table()
    {
        global $wpdb;

        $id = intval($_GET['id']);
        $result = $wpdb->delete($this->table_name, array('id' => $id));

        if ($result) {
            $message = 'Table deleted successfully!';
            $type = 'success';
        } else {
            $message = 'Failed to delete table.';
            $type = 'error';
        }

        wp_redirect(admin_url('admin.php?page=plugincy-tables&message=' . urlencode($message) . '&type=' . $type));
        exit;
    }

}