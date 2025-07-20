<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * add_table.php for the plugin.
 *
 * @package Plugincy Tables
 */

class Plugincy_add_table
{

    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'plugincy_tables';
    }

    public function admin_page_add_table()
    {
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $table_data = null;

        if ($edit_id) {
            global $wpdb;
            $table_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $edit_id));
        }

        // Get WooCommerce product categories
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        // Get WooCommerce product tags
        $tags = get_terms(array(
            'taxonomy' => 'product_tag',
            'hide_empty' => false,
        ));

        // Get all products for product selection
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        // Check if WooCommerce has products
        $has_products = !empty($products);

        // Parse existing table data
        $existing_data = $table_data ? json_decode($table_data->table_data, true) : null;
        $query_settings = $existing_data['query_settings'] ?? array();

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

                    <!-- Shortcode Display Section -->
                    <div class="plugincy-shortcode-section" <?php echo !$edit_id ? 'style="display:none;"' : ''; ?>>
                        <h3>Shortcode</h3>
                        <div class="plugincy-shortcode-display">
                            <code id="table-shortcode">[plugincy_table id="<?php echo $edit_id; ?>"]</code>
                            <button type="button" class="button button-small copy-shortcode" data-shortcode="[plugincy_table id=&quot;<?php echo $edit_id; ?>&quot;]">Copy</button>
                        </div>
                        <p class="description">Use this shortcode to display the table on any page or post.</p>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="plugincy-tabs">
                        <nav class="nav-tab-wrapper">
                            <a href="#" class="nav-tab nav-tab-active" data-tab="columns">Columns</a>
                            <a href="#" class="nav-tab" data-tab="query">Query</a>
                            <a href="#" class="nav-tab" data-tab="design">Design</a>
                            <a href="#" class="nav-tab" data-tab="options">Options</a>
                            <a href="#" class="nav-tab" data-tab="search-filter">Search & Filter</a>
                            <a href="#" class="nav-tab" data-tab="settings">Settings</a>
                        </nav>

                        <!-- Columns Tab -->
                        <div class="tab-content" id="tab-columns">
                            <div class="plugincy-table-editor">
                                <h3>Table Structure</h3>
                                <p class="description">Design your table layout. The first row defines what product information will be displayed for each product.</p>

                                <div class="plugincy-table-container">
                                    <div class="plugincy-table-controls">
                                        <button type="button" class="button" id="add-column">Add Column</button>
                                        <button type="button" class="button" id="add-row" style="display:none;">Add Row</button>
                                        <div class="plugincy-row-info" id="row-info-message" style="display:none;">
                                            <p><strong>Note:</strong> Additional rows will be automatically generated based on your product query settings.</p>
                                        </div>
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
                                                        <button type="button" class="button button-small delete-row" style="display:none;">Delete</button>
                                                    </td>
                                                </tr>
                                                <tr class="plugincy-preview-loading-row">
                                                    <td colspan="6" class="plugincy-preview-loading" style="text-align: center;">
                                                        <p>Configure your query settings to see product preview.</p>
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
                                    <div class="plugincy-preview-controls">
                                        <button type="button" class="button" id="refresh-preview">Refresh Preview</button>
                                        <button type="button" class="button" id="clear-excluded">Clear All Exclusions</button>
                                        <span class="plugincy-excluded-count" id="excluded-count" style="margin-left: 15px;"></span>
                                    </div>
                                    <?php if (!$has_products): ?>
                                        <div class="plugincy-no-products-notice">
                                            <div class="plugincy-no-products-icon">📦</div>
                                            <h3>No Products Found</h3>
                                            <p><strong>WooCommerce products are required to create product tables.</strong></p>
                                            <p>Please add products to your WooCommerce store first.</p>
                                            <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button button-primary">Add Your First Product</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Query Tab -->
                        <div class="tab-content" id="tab-query" style="display:none;">
                            <h3>Product Query Settings</h3>
                            <p class="description">Configure which products should be displayed in your table.</p>

                            <div class="plugincy-query-settings">
                                <div class="plugincy-form-group">
                                    <label for="query-type">Query Type</label>
                                    <select id="query-type" name="query_type">
                                        <option value="all" <?php selected($query_settings['query_type'] ?? 'all', 'all'); ?>>All Products</option>
                                        <option value="category" <?php selected($query_settings['query_type'] ?? '', 'category'); ?>>By Category</option>
                                        <option value="tags" <?php selected($query_settings['query_type'] ?? '', 'tags'); ?>>By Tags</option>
                                        <option value="products" <?php selected($query_settings['query_type'] ?? '', 'products'); ?>>Specific Products</option>
                                    </select>
                                    <p class="description">Choose how to filter the products for your table.</p>
                                </div>

                                <!-- Category Selection -->
                                <div class="plugincy-form-group" id="category-selection" style="display:none;">
                                    <label for="selected-categories">Select Categories</label>
                                    <select id="selected-categories" name="selected_categories[]" multiple style="height: 120px;">
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo esc_attr($category->slug); ?>"
                                                <?php echo in_array($category->slug, $query_settings['selected_categories'] ?? []) ? 'selected' : ''; ?>>
                                                <?php echo esc_html($category->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">Hold Ctrl (or Cmd) to select multiple categories.</p>
                                </div>

                                <!-- Tags Selection -->
                                <div class="plugincy-form-group" id="tags-selection" style="display:none;">
                                    <label for="selected-tags">Select Tags</label>
                                    <select id="selected-tags" name="selected_tags[]" multiple style="height: 120px;">
                                        <?php foreach ($tags as $tag): ?>
                                            <option value="<?php echo esc_attr($tag->slug); ?>"
                                                <?php echo in_array($tag->slug, $query_settings['selected_tags'] ?? []) ? 'selected' : ''; ?>>
                                                <?php echo esc_html($tag->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">Hold Ctrl (or Cmd) to select multiple tags.</p>
                                </div>

                                <!-- Product Selection -->
                                <div class="plugincy-form-group" id="products-selection" style="display:none;">
                                    <label for="selected-products">Select Products</label>
                                    <select id="selected-products" name="selected_products[]" multiple style="height: 200px;">
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product->ID; ?>"
                                                <?php echo in_array($product->ID, $query_settings['selected_products'] ?? []) ? 'selected' : ''; ?>>
                                                <?php echo esc_html($product->post_title); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">Hold Ctrl (or Cmd) to select multiple products.</p>

                                    <div class="plugincy-product-rows-info" id="product-rows-section">
                                        <div class="plugincy-info-box">
                                            <h4>🔄 Dynamic Row Management</h4>
                                            <p>When you select specific products, you can add multiple rows to customize the table layout. Each product will populate these rows based on your design.</p>
                                            <ul>
                                                <li>✓ The first row defines the column structure and elements</li>
                                                <li>✓ Additional rows provide alternative layouts for variety</li>
                                                <li>✓ Products will cycle through your defined row patterns</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                // Parse existing excluded products if editing
                                $excluded_products = isset($existing_data['query_settings']['excluded_products']) ? $existing_data['query_settings']['excluded_products'] : array();
                                ?>
                                <input type="hidden" name="excluded_products" id="excluded-products-input" value="<?php echo esc_attr($excluded_products); ?>">

                                <!-- Products Per Page -->
                                <div class="plugincy-form-group">
                                    <label for="products-per-page">Products Per Page</label>
                                    <input type="number" id="products-per-page" name="products_per_page"
                                        value="<?php echo $query_settings['products_per_page'] ?? 10; ?>" min="1" max="100">
                                    <p class="description">Maximum number of products to display in the table (1-100).</p>
                                </div>

                                <!-- Order Settings -->
                                <div class="plugincy-form-group">
                                    <label for="order-by">Order By</label>
                                    <select id="order-by" name="order_by">
                                        <option value="date" <?php selected($query_settings['order_by'] ?? 'date', 'date'); ?>>Date Created</option>
                                        <option value="title" <?php selected($query_settings['order_by'] ?? '', 'title'); ?>>Product Title</option>
                                        <option value="menu_order" <?php selected($query_settings['order_by'] ?? '', 'menu_order'); ?>>Menu Order</option>
                                        <option value="rand" <?php selected($query_settings['order_by'] ?? '', 'rand'); ?>>Random</option>
                                        <option value="price" <?php selected($query_settings['order_by'] ?? '', 'price'); ?>>Price</option>
                                        <option value="popularity" <?php selected($query_settings['order_by'] ?? '', 'popularity'); ?>>Popularity</option>
                                        <option value="rating" <?php selected($query_settings['order_by'] ?? '', 'rating'); ?>>Average Rating</option>
                                    </select>
                                </div>

                                <div class="plugincy-form-group">
                                    <label for="order">Order Direction</label>
                                    <select id="order" name="order">
                                        <option value="DESC" <?php selected($query_settings['order'] ?? 'DESC', 'DESC'); ?>>Descending</option>
                                        <option value="ASC" <?php selected($query_settings['order'] ?? '', 'ASC'); ?>>Ascending</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Other tabs remain the same -->
                        <!-- Design Tab -->
                        <div class="tab-content" id="tab-design" style="display:none;">
                            <h3>Design Settings</h3>
                            <p>Design options will be implemented in future updates.</p>
                        </div>

                        <!-- Options Tab -->
                        <div class="tab-content" id="tab-options" style="display:none;">
                            <div class="plugincy-table-settings">
                                <h3>Table Options</h3>
                                <div class="plugincy-settings-row">
                                    <label>
                                        <input type="checkbox" id="show-header" name="show_header" value="1"
                                            <?php checked($existing_data['show_header'] ?? true); ?>> Show Header
                                    </label>
                                    <label>
                                        <input type="checkbox" id="show-footer" name="show_footer" value="1"
                                            <?php checked($existing_data['show_footer'] ?? false); ?>> Show Footer
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Search & Filter Tab -->
                        <div class="tab-content" id="tab-search-filter" style="display:none;">
                            <h3>Search & Filter</h3>
                            <p>Search and filter options will be implemented in future updates.</p>
                        </div>

                        <!-- Settings Tab -->
                        <div class="tab-content" id="tab-settings" style="display:none;">
                            <h3>Additional Settings</h3>
                            <p>Additional settings will be implemented in future updates.</p>
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

        <!-- Element Selection Modal -->
        <div id="plugincy-element-modal" class="plugincy-modal">
            <div class="plugincy-modal-content">
                <div class="plugincy-modal-header">
                    <h3>Add Element</h3>
                    <span class="plugincy-close">&times;</span>
                </div>
                <div class="plugincy-modal-body">
                    <div class="plugincy-element-options">
                        <div class="plugincy-element-option" data-type="product_title">
                            <strong>Product Title</strong>
                            <p>Display the product name</p>
                        </div>
                        <div class="plugincy-element-option" data-type="product_title_link">
                            <strong>Product Title with Link</strong>
                            <p>Product name linked to product page</p>
                        </div>
                        <div class="plugincy-element-option" data-type="product_price">
                            <strong>Product Price</strong>
                            <p>Display product pricing</p>
                        </div>
                        <div class="plugincy-element-option" data-type="product_image">
                            <strong>Product Image</strong>
                            <p>Product featured image</p>
                        </div>
                        <div class="plugincy-element-option" data-type="add_to_cart">
                            <strong>Add to Cart Button</strong>
                            <p>Interactive add to cart button</p>
                        </div>
                        <div class="plugincy-element-option" data-type="short_description">
                            <strong>Short Description</strong>
                            <p>Product short description</p>
                        </div>
                        <div class="plugincy-element-option" data-type="product_rating">
                            <strong>Product Rating</strong>
                            <p>Star rating display</p>
                        </div>
                        <div class="plugincy-element-option" data-type="product_category">
                            <strong>Product Category</strong>
                            <p>Display product categories</p>
                        </div>
                        <div class="plugincy-element-option" data-type="product_tags">
                            <strong>Product Tags</strong>
                            <p>Display product tags</p>
                        </div>
                        <div class="plugincy-element-option" data-type="stock_status">
                            <strong>Stock Status</strong>
                            <p>In stock/out of stock indicator</p>
                        </div>
                        <div class="plugincy-element-option" data-type="custom_text">
                            <strong>Custom Text</strong>
                            <p>Add your own custom text</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($table_data): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    function waitForPlugincy() {
                        if (typeof window.plugincyLoadTableData === 'function') {
                            const tableData = <?php echo $table_data->table_data; ?>;
                            console.log('Loading existing table data:', tableData);
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


    public function get_preview_products($query_settings, $excluded_products = array())
    {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => isset($query_settings['products_per_page']) ? intval($query_settings['products_per_page']) : 10,
            'orderby' => isset($query_settings['order_by']) ? $query_settings['order_by'] : 'date',
            'order' => isset($query_settings['order']) ? $query_settings['order'] : 'DESC'
        );

        // Exclude products
        if (!empty($excluded_products)) {
            $args['post__not_in'] = $excluded_products;
        }

        // Handle different query types
        $query_type = isset($query_settings['query_type']) ? $query_settings['query_type'] : 'all';

        switch ($query_type) {
            case 'category':
                if (!empty($query_settings['selected_categories'])) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'slug',
                            'terms' => $query_settings['selected_categories'],
                            'operator' => 'IN'
                        )
                    );
                }
                break;

            case 'tags':
                if (!empty($query_settings['selected_tags'])) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'product_tag',
                            'field' => 'slug',
                            'terms' => $query_settings['selected_tags'],
                            'operator' => 'IN'
                        )
                    );
                }
                break;

            case 'products':
                if (!empty($query_settings['selected_products'])) {
                    $args['post__in'] = $query_settings['selected_products'];
                    $args['orderby'] = 'post__in';
                }
                break;
        }

        $query = new WP_Query($args);
        return $query->posts;
    }

    // Add this AJAX handler method to the Plugincy_add_table class

    public function ajax_get_preview_products()
    {
        check_ajax_referer('plugincy_nonce', 'nonce');

        $query_settings = json_decode(stripslashes($_POST['query_settings']), true);
        $excluded_products = isset($_POST['excluded_products']) ? array_map('intval', $_POST['excluded_products']) : array();
        $table_data = isset($_POST['table_data']) ? json_decode(stripslashes($_POST['table_data']), true) : null;

        $products = $this->get_preview_products($query_settings, $excluded_products);

        $html = '';
        $product_index = 0;

        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            if ($wc_product) {
                if ($table_data && isset($table_data['rows'])) {
                    $row_template_index = $product_index % count($table_data['rows']);
                    $current_row_template = $table_data['rows'][$row_template_index];

                    $html .= '<tr class="plugincy-product-preview-row" data-product-id="' . $product->ID . '" data-row-template="' . $row_template_index . '">';

                    foreach ($current_row_template as $cell) {
                        $html .= '<td>';
                        if (isset($cell['elements']) && !empty($cell['elements'])) {
                            foreach ($cell['elements'] as $element) {
                                $html .= $this->render_element($element, $wc_product, $product_index);
                            }
                        }
                        $html .= '</td>';
                    }

                    $html .= '<td><button type="button" class="button button-small remove-product" data-product-id="' . $product->ID . '">Remove</button></td>';
                } else {
                    // Fallback structure
                    $image = $wc_product->get_image('thumbnail');
                    $html .= '<tr class="plugincy-product-preview-row" data-product-id="' . $product->ID . '">';
                    $html .= '<td>' . $image . '</td>';
                    $html .= '<td>' . esc_html($wc_product->get_name()) . '</td>';
                    $html .= '<td>' . $wc_product->get_price_html() . '</td>';
                    $html .= '<td>' . esc_html($wc_product->get_stock_status()) . '</td>';
                    $html .= '<td><button type="button" class="button button-small remove-product" data-product-id="' . $product->ID . '">Remove</button></td>';
                }
                $html .= '</tr>';
                $product_index++;
            }
        }

        wp_send_json_success($html);
    }

    private function render_element($element, $wc_product, $row_index = 0)
    {
        $output = '';

        switch ($element['type']) {
            case 'product_title':
                $output = '<span class="plugincy-element-preview">' . esc_html($wc_product->get_name()) . '</span>';
                break;

            case 'product_title_link':
                $output = '<a href="' . get_permalink($wc_product->get_id()) . '" class="plugincy-element-preview">' . esc_html($wc_product->get_name()) . '</a>';
                break;

            case 'product_price':
                $output = '<span class="plugincy-element-preview">' . $wc_product->get_price_html() . '</span>';
                break;

            case 'product_image':
                $output = '<div class="plugincy-element-preview">' . $wc_product->get_image('thumbnail') . '</div>';
                break;

            case 'add_to_cart':
                $output = '<button class="button plugincy-element-preview">Add to Cart</button>';
                break;

            case 'short_description':
                $output = '<div class="plugincy-element-preview">' . wp_trim_words($wc_product->get_short_description(), 10) . '</div>';
                break;

            case 'product_rating':
                $rating = $wc_product->get_average_rating();
                $output = '<div class="plugincy-element-preview">★' . number_format($rating, 1) . '</div>';
                break;

            case 'product_category':
                $categories = wp_get_post_terms($wc_product->get_id(), 'product_cat', array('fields' => 'names'));
                $output = '<span class="plugincy-element-preview">' . implode(', ', $categories) . '</span>';
                break;

            case 'product_tags':
                $tags = wp_get_post_terms($wc_product->get_id(), 'product_tag', array('fields' => 'names'));
                $output = '<span class="plugincy-element-preview">' . implode(', ', $tags) . '</span>';
                break;

            case 'stock_status':
                $status = $wc_product->get_stock_status();
                $output = '<span class="plugincy-element-preview plugincy-stock-' . $status . '">' . ucfirst($status) . '</span>';
                break;

            case 'custom_text':
                $output = '<span class="plugincy-element-preview">' . esc_html($element['content'] ?? 'Custom Text') . '</span>';
                break;

            default:
                $output = '<span class="plugincy-element-preview">[' . esc_html($element['type']) . ']</span>';
        }

        return $output;
    }




    public function save_table()
    {
        global $wpdb;

        $title = sanitize_text_field($_POST['table_title']);
        $table_data = stripslashes($_POST['table_data']);
        $edit_id = intval($_POST['edit_id']);

        // Save query settings
        $query_settings = array(
            'query_type' => sanitize_text_field($_POST['query_type']),
            'selected_categories' => isset($_POST['selected_categories']) ? array_map('sanitize_text_field', $_POST['selected_categories']) : array(),
            'selected_tags' => isset($_POST['selected_tags']) ? array_map('sanitize_text_field', $_POST['selected_tags']) : array(),
            'selected_products' => isset($_POST['selected_products']) ? array_map('intval', $_POST['selected_products']) : array(),
            'excluded_products' => isset($_POST['excluded_products'])
                ? array_map('intval', is_array($_POST['excluded_products'])
                    ? $_POST['excluded_products']
                    : array_filter(array_map('trim', explode(',', $_POST['excluded_products']))))
                : array(),
            'products_per_page' => intval($_POST['products_per_page']),
            'order_by' => sanitize_text_field($_POST['order_by']),
            'order' => sanitize_text_field($_POST['order'])
        );

        // Combine table data with query settings
        $table_data_array = json_decode($table_data, true);
        if (!$table_data_array) {
            $table_data_array = array();
        }
        $table_data_array['query_settings'] = $query_settings;
        $table_data = json_encode($table_data_array);

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
            $table_id = $edit_id > 0 ? $edit_id : $wpdb->insert_id;
            $message = $edit_id > 0 ? 'Table updated successfully!' : 'Table created successfully!';
            wp_redirect(admin_url('admin.php?page=plugincy-add-table&edit=' . $table_id . '&message=' . urlencode($message) . '&type=success'));
            exit;
        } else {
            $message = 'Failed to save table.';
            wp_redirect(admin_url('admin.php?page=plugincy-add-table&message=' . urlencode($message) . '&type=error'));
            exit;
        }
    }
}
