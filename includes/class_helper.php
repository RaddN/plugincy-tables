<?php

/**
 * class_helper.php
 *
 * @package Plugincy Tables
 */
if (!defined('ABSPATH')) {
    exit;
}

class Plugincy_Tables_Helper
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'plugincy_tables';
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
        global $wpdb;

        // Get table data to access query settings
        $table = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $atts['id']));

        if (!$table) {
            return array();
        }

        $table_data = json_decode($table->table_data, true);
        $query_settings = isset($table_data['query_settings']) ? $table_data['query_settings'] : array();

        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => isset($query_settings['products_per_page']) ? intval($query_settings['products_per_page']) : 10,
            'orderby' => isset($query_settings['order_by']) ? $query_settings['order_by'] : 'date',
            'order' => isset($query_settings['order']) ? $query_settings['order'] : 'DESC'
        );

        // Add excluded products - THIS IS THE NEW ADDITION
        if (!empty($query_settings['excluded_products'])) {
            $args['post__not_in'] = $query_settings['excluded_products'];
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
                    $selected_products = $query_settings['selected_products'];

                    // Remove excluded products from selected products
                    if (!empty($query_settings['excluded_products'])) {
                        $selected_products = array_diff($selected_products, $query_settings['excluded_products']);
                    }

                    if (!empty($selected_products)) {
                        $args['post__in'] = $selected_products;
                        $args['orderby'] = 'post__in';
                    }
                }
                break;

            case 'all':
            default:
                // No additional filters needed for all products
                break;
        }

        // Handle shortcode attributes for backward compatibility
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

        if (!empty($atts['limit'])) {
            $args['posts_per_page'] = intval($atts['limit']);
        }

        $query = new WP_Query($args);
        return $query->posts;
    }
    private function generate_table_html($table_data, $products)
    {

        $html = '';
        $html .= "<style>";
        foreach ($table_data['rows'][0] as $cell) {
            $cell_settings = $cell['elements'][0];
            $selector = array_keys($cell_settings['settings']);
            foreach ($selector as $key) {
                if ($key !== "content_settings") {
                    if (isset($cell_settings['settings'][$key])) {
                        $html .= $this->generate_element_styles($cell_settings['settings'][$key], $key);
                    }
                }
            }
        }
        $html .= "</style>";
        $html .= '<div class="plugincy-table-container">';
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

    // generate_element_styles
    public function generate_element_styles($settings, $selector, $class = '')
    {

        $styles = '';

        // image styles
        $styles .= $class . $selector . '{ ';
        foreach ($settings as $property => $value) {
            $styles .= esc_attr($property) . ': ' . esc_attr($value) . '; ';
        }
        $styles .= '}';

        return $styles;
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
                    $content .= '<div class="plugincy-product-title ' . $element['type'] . '">' . esc_html($wc_product->get_name()) . '</div>';
                    break;

                case 'product_title_link':
                    $content .= '<div class="plugincy-product-title-link"><a href="' . get_permalink($product->ID) . '" class="' . $element['type'] . '">' . esc_html($wc_product->get_name()) . '</a></div>';
                    break;

                case 'product_price':
                    $content .= '<div class="plugincy-product-price ' . $element['type'] . '">' . $wc_product->get_price_html() . '</div>';
                    break;

                case 'product_image':
                    $image = $wc_product->get_image('thumbnail');
                    $content .= '<div class="plugincy-product-image ' . $element['type'] . '">' . $image . '</div>';
                    break;

                case 'add_to_cart':
                    $content .= '<div class="plugincy-add-to-cart ' . $element['type'] . '">';
                    $content .= '<form class="cart" method="post" enctype="multipart/form-data">';
                    $content .= '<input type="hidden" name="add-to-cart" value="' . $product->ID . '">';
                    $content .= '<button type="submit" class="single_add_to_cart_button button">Add to Cart</button>';
                    $content .= '</form>';
                    $content .= '</div>';
                    break;

                case 'short_description':
                    $content .= '<div class="plugincy-short-description ' . $element['type'] . '">' . $wc_product->get_short_description() . '</div>';
                    break;

                case 'product_rating':
                    $rating = $wc_product->get_average_rating();
                    $content .= '<div class="plugincy-product-rating ' . $element['type'] . '">' . wc_get_rating_html($rating) . '</div>';
                    break;

                case 'product_category':
                    $categories = get_the_terms($product->ID, 'product_cat');
                    if ($categories && !is_wp_error($categories)) {
                        $cat_names = array();
                        foreach ($categories as $category) {
                            $cat_names[] = $category->name;
                        }
                        $content .= '<div class="plugincy-product-category ' . $element['type'] . '">' . implode(', ', $cat_names) . '</div>';
                    }
                    break;

                case 'product_tags':
                    $tags = get_the_terms($product->ID, 'product_tag');
                    if ($tags && !is_wp_error($tags)) {
                        $tag_names = array();
                        foreach ($tags as $tag) {
                            $tag_names[] = $tag->name;
                        }
                        $content .= '<div class="plugincy-product-tags ' . $element['type'] . '">' . implode(', ', $tag_names) . '</div>';
                    }
                    break;

                case 'stock_status':
                    $stock_status = $wc_product->get_stock_status();
                    $content .= '<div class="plugincy-stock-status ' . $element['type'] . ' plugincy-stock-' . $stock_status . '">' . ucfirst($stock_status) . '</div>';
                    break;

                case 'custom_text':
                    $content .= '<div class="plugincy-custom-text ' . $element['type'] . '">' . esc_html($element['content']) . '</div>';
                    break;
            }
        }

        return $content;
    }
}
