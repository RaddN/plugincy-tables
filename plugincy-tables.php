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

class PlugincyTables
{

    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'plugincy_tables';

        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_init', array($this, 'handle_form_submissions'));
        add_shortcode('plugincy_table', array($this, 'render_table_shortcode'));

        register_activation_hook(__FILE__, array($this, 'create_table'));
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

    public function create_table()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            table_data longtext NOT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Plugincy Tables',
            'Plugincy Tables',
            'manage_options',
            'plugincy-tables',
            array($this, 'admin_page_all_tables'),
            'dashicons-grid-view',
            25
        );

        add_submenu_page(
            'plugincy-tables',
            'All Tables',
            'All Tables',
            'manage_options',
            'plugincy-tables',
            array($this, 'admin_page_all_tables')
        );

        add_submenu_page(
            'plugincy-tables',
            'Add New Table',
            'Add New Table',
            'manage_options',
            'plugincy-add-table',
            array($this, 'admin_page_add_table')
        );

        add_submenu_page(
            'plugincy-tables',
            'Settings',
            'Settings',
            'manage_options',
            'plugincy-settings',
            array($this, 'admin_page_settings')
        );
    }

    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'plugincy') !== false) {
            wp_enqueue_script('plugincy-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('plugincy-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '1.0.0');
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
            $this->save_table();
        }

        // Handle table deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete_table' && isset($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_table_' . $_GET['id'])) {
            $this->delete_table();
        }
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

    public function admin_page_add_table()
    {
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $table_data = null;

        if ($edit_id) {
            global $wpdb;
            $table_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $edit_id));
        }

    ?>
        <div class="wrap">
            <h1><?php echo $edit_id ? 'Edit Table' : 'Add New Table'; ?></h1>

            <div class="plugincy-table-builder">
                <form method="post" action="" id="plugincy-table-form">
                    <?php wp_nonce_field('plugincy_save_table', 'plugincy_nonce'); ?>

                    <div class="plugincy-form-group">
                        <label for="table-title">Table Title</label>
                        <input type="text" id="table-title" name="table_title" value="<?php echo $table_data ? esc_attr($table_data->title) : ''; ?>" required>
                    </div>

                    <div class="plugincy-table-settings">
                        <h3>Table Settings</h3>
                        <div class="plugincy-settings-row">
                            <label>
                                <input type="checkbox" id="show-header" name="show_header" value="1" checked> Show Header
                            </label>
                            <label>
                                <input type="checkbox" id="show-footer" name="show_footer" value="1"> Show Footer
                            </label>
                        </div>
                    </div>

                    <div class="plugincy-table-editor">
                        <h3>Table Structure</h3>

                        <div class="plugincy-table-container">
                            <div class="plugincy-table-controls">
                                <button type="button" class="button" id="add-column">Add Column</button>
                                <button type="button" class="button" id="add-row">Add Row</button>
                            </div>

                            <div class="plugincy-table-wrapper">
                                <table id="plugincy-editable-table" class="plugincy-editable-table">
                                    <thead id="table-header">
                                        <tr>
                                            <th contenteditable="true" class="plugincy-editable-header">Column 1</th>
                                            <th contenteditable="true" class="plugincy-editable-header">Column 2</th>
                                            <th contenteditable="true" class="plugincy-editable-header">Column 3</th>
                                            <th contenteditable="true" class="plugincy-editable-header">Column 4</th>
                                            <th contenteditable="true" class="plugincy-editable-header">Column 5</th>
                                            <th class="plugincy-action-column">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        <tr>
                                            <td class="plugincy-editable-cell">
                                                <div class="plugincy-cell-content">
                                                    <div class="plugincy-add-element">+</div>
                                                </div>
                                            </td>
                                            <td class="plugincy-editable-cell">
                                                <div class="plugincy-cell-content">
                                                    <div class="plugincy-add-element">+</div>
                                                </div>
                                            </td>
                                            <td class="plugincy-editable-cell">
                                                <div class="plugincy-cell-content">
                                                    <div class="plugincy-add-element">+</div>
                                                </div>
                                            </td>
                                            <td class="plugincy-editable-cell">
                                                <div class="plugincy-cell-content">
                                                    <div class="plugincy-add-element">+</div>
                                                </div>
                                            </td>
                                            <td class="plugincy-editable-cell">
                                                <div class="plugincy-cell-content">
                                                    <div class="plugincy-add-element">+</div>
                                                </div>
                                            </td>
                                            <td class="plugincy-action-column">
                                                <button type="button" class="button button-small delete-row">Delete</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot id="table-footer" style="display: none;">
                                        <tr>
                                            <td contenteditable="true" class="plugincy-editable-footer">Footer 1</td>
                                            <td contenteditable="true" class="plugincy-editable-footer">Footer 2</td>
                                            <td contenteditable="true" class="plugincy-editable-footer">Footer 3</td>
                                            <td contenteditable="true" class="plugincy-editable-footer">Footer 4</td>
                                            <td contenteditable="true" class="plugincy-editable-footer">Footer 5</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="plugincy-form-actions">
                        <button type="submit" name="plugincy_save_table" class="button button-primary">
                            <?php echo $edit_id ? 'Update Table' : 'Create Table'; ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=plugincy-tables'); ?>" class="button">Cancel</a>
                    </div>

                    <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                    <input type="hidden" name="table_data" id="table-data-input" value="">
                </form>
            </div>
        </div>

        <div id="plugincy-element-modal" class="plugincy-modal">
            <div class="plugincy-modal-content">
                <div class="plugincy-modal-header">
                    <h3>Add Element</h3>
                    <span class="plugincy-close">&times;</span>
                </div>
                <div class="plugincy-modal-body">
                    <div class="plugincy-element-options">
                        <div class="plugincy-element-option" data-type="product_title">Product Title</div>
                        <div class="plugincy-element-option" data-type="product_title_link">Product Title with Link</div>
                        <div class="plugincy-element-option" data-type="product_price">Product Price</div>
                        <div class="plugincy-element-option" data-type="product_image">Product Image</div>
                        <div class="plugincy-element-option" data-type="add_to_cart">Add to Cart Button</div>
                        <div class="plugincy-element-option" data-type="short_description">Short Description</div>
                        <div class="plugincy-element-option" data-type="product_rating">Product Rating</div>
                        <div class="plugincy-element-option" data-type="product_category">Product Category</div>
                        <div class="plugincy-element-option" data-type="product_tags">Product Tags</div>
                        <div class="plugincy-element-option" data-type="stock_status">Stock Status</div>
                        <div class="plugincy-element-option" data-type="custom_text">Custom Text</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($table_data): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Wait for the external script to load
                    function waitForPlugincy() {
                        if (typeof window.plugincyLoadTableData === 'function') {
                            const tableData = <?php echo $table_data->table_data; ?>;
                            console.log(tableData);
                            window.plugincyLoadTableData(tableData);
                        } else {
                            setTimeout(waitForPlugincy, 100);
                        }
                    }
                    waitForPlugincy();
                });
            </script>
        <?php endif; ?>
    <?php
    }

    public function admin_page_settings()
    {
    ?>
        <div class="wrap">
            <h1>Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('plugincy_settings');
                do_settings_sections('plugincy_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Default Table Style</th>
                        <td>
                            <select name="plugincy_default_style">
                                <option value="default">Default</option>
                                <option value="modern">Modern</option>
                                <option value="minimal">Minimal</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Products Per Page</th>
                        <td>
                            <input type="number" name="plugincy_products_per_page" value="10" min="1" max="100">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
<?php
    }

    public function save_table()
    {
        global $wpdb;

        $title = sanitize_text_field($_POST['table_title']);
        $table_data = stripslashes($_POST['table_data']);
        $edit_id = intval($_POST['edit_id']);

        if ($edit_id > 0) {
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'title' => $title,
                    'table_data' => $table_data,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $edit_id)
            );
        } else {
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'title' => $title,
                    'table_data' => $table_data,
                    'created_by' => get_current_user_id(),
                    'created_at' => current_time('mysql')
                )
            );
        }

        if ($result !== false) {
            $message = $edit_id > 0 ? 'Table updated successfully!' : 'Table created successfully!';
            wp_redirect(admin_url('admin.php?page=plugincy-tables&message=' . urlencode($message) . '&type=success'));
            exit;
        } else {
            $message = 'Failed to save table.';
            wp_redirect(admin_url('admin.php?page=plugincy-add-table&message=' . urlencode($message) . '&type=error'));
            exit;
        }
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

    public function render_table_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => 0,
            'products' => '',
            'category' => '',
            'limit' => 10
        ), $atts);

        if (!$atts['id']) {
            return '<p>Please provide a table ID.</p>';
        }

        global $wpdb;
        $table = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $atts['id']));

        if (!$table) {
            return '<p>Table not found.</p>';
        }

        $table_data = json_decode($table->table_data, true);

        $products = $this->get_products_for_table($atts);

        if (empty($products)) {
            return '<p>No products found.</p>';
        }

        return $this->generate_table_html($table_data, $products);
    }

    private function get_products_for_table($atts)
    {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish'
        );

        if (!empty($atts['products'])) {
            $product_ids = array_map('intval', explode(',', $atts['products']));
            $args['post__in'] = $product_ids;
        }

        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }

        $query = new WP_Query($args);
        return $query->posts;
    }

    private function generate_table_html($table_data, $products)
    {
        $html = '<div class="plugincy-table-container">';
        $html .= '<table class="plugincy-product-table">';

        if ($table_data['show_header']) {
            $html .= '<thead><tr>';
            foreach ($table_data['headers'] as $header) {
                $html .= '<th>' . esc_html($header) . '</th>';
            }
            $html .= '</tr></thead>';
        }

        $html .= '<tbody>';
        foreach ($products as $product) {
            $html .= '<tr>';
            foreach ($table_data['rows'][0] as $cell) {
                $html .= '<td>' . $this->render_cell_content($cell, $product) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';

        if ($table_data['show_footer']) {
            $html .= '<tfoot><tr>';
            foreach ($table_data['footers'] as $footer) {
                $html .= '<td>' . esc_html($footer) . '</td>';
            }
            $html .= '</tr></tfoot>';
        }

        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    private function render_cell_content($cell, $product)
    {
        $wc_product = wc_get_product($product->ID);

        if (!$wc_product) {
            return '';
        }

        $content = '';

        foreach ($cell['elements'] as $element) {
            switch ($element['type']) {
                case 'product_title':
                    $content .= '<div class="plugincy-product-title">' . esc_html($wc_product->get_name()) . '</div>';
                    break;

                case 'product_title_link':
                    $content .= '<div class="plugincy-product-title-link"><a href="' . get_permalink($product->ID) . '">' . esc_html($wc_product->get_name()) . '</a></div>';
                    break;

                case 'product_price':
                    $content .= '<div class="plugincy-product-price">' . $wc_product->get_price_html() . '</div>';
                    break;

                case 'product_image':
                    $image = $wc_product->get_image('thumbnail');
                    $content .= '<div class="plugincy-product-image">' . $image . '</div>';
                    break;

                case 'add_to_cart':
                    $content .= '<div class="plugincy-add-to-cart">';
                    $content .= '<form class="cart" method="post" enctype="multipart/form-data">';
                    $content .= '<input type="hidden" name="add-to-cart" value="' . $product->ID . '">';
                    $content .= '<button type="submit" class="single_add_to_cart_button button">Add to Cart</button>';
                    $content .= '</form>';
                    $content .= '</div>';
                    break;

                case 'short_description':
                    $content .= '<div class="plugincy-short-description">' . $wc_product->get_short_description() . '</div>';
                    break;

                case 'product_rating':
                    $rating = $wc_product->get_average_rating();
                    $content .= '<div class="plugincy-product-rating">' . wc_get_rating_html($rating) . '</div>';
                    break;

                case 'product_category':
                    $categories = get_the_terms($product->ID, 'product_cat');
                    if ($categories && !is_wp_error($categories)) {
                        $cat_names = array();
                        foreach ($categories as $category) {
                            $cat_names[] = $category->name;
                        }
                        $content .= '<div class="plugincy-product-category">' . implode(', ', $cat_names) . '</div>';
                    }
                    break;

                case 'product_tags':
                    $tags = get_the_terms($product->ID, 'product_tag');
                    if ($tags && !is_wp_error($tags)) {
                        $tag_names = array();
                        foreach ($tags as $tag) {
                            $tag_names[] = $tag->name;
                        }
                        $content .= '<div class="plugincy-product-tags">' . implode(', ', $tag_names) . '</div>';
                    }
                    break;

                case 'stock_status':
                    $stock_status = $wc_product->get_stock_status();
                    $content .= '<div class="plugincy-stock-status plugincy-stock-' . $stock_status . '">' . ucfirst($stock_status) . '</div>';
                    break;

                case 'custom_text':
                    $content .= '<div class="plugincy-custom-text">' . esc_html($element['content']) . '</div>';
                    break;
            }
        }

        return $content;
    }
}

new PlugincyTables();

?>