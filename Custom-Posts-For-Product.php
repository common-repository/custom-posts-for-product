<?php
/*
  Plugin Name: Custom Posts For Product
  Description: Builds custom posts for different product with thumbnails,price,tags,featured image under different categories.
  Version: 1.0.0
  Author: Milind Deshpande
  License: GPLv2 or later
  Text Domain: cpfp-plugin
 */

/*
  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

function cpfp_my_custom_post_product() {
    $labels = array(
        'name' => _x('My Products', 'post type general name', 'cpfp'),
        'singular_name' => _x('Product', 'post type singular name', 'cpfp'),
        'add_new' => _x('Add New', 'book', 'cpfp'),
        'add_new_item' => __('Add New Product', 'cpfp'),
        'edit_item' => __('Edit Product', 'cpfp'),
        'new_item' => __('New Product', 'cpfp'),
        'all_items' => __('All Products', 'cpfp'),
        'view_item' => __('View Product', 'cpfp'),
        'search_items' => __('Search Products', 'cpfp'),
        'not_found' => __('No products found', 'cpfp'),
        'not_found_in_trash' => __('No products found in the Trash', 'cpfp'),
        'parent_item_colon' => '',
        'menu_name' => 'My Products'
    );
    $args = array(
        'labels' => $labels,
        'description' => 'Holds our products and product specific data',
        'public' => true,
        'menu_position' => 5,
        'supports' => array('title', 'editor', 'thumbnail', 'author', 'excerpt', 'comments'),
        'has_archive' => true,
        'taxonomies' => array('category', 'post_tag'),
    );
    register_post_type('product', $args);
}

add_action('init', 'cpfp_my_custom_post_product');

add_action('add_meta_boxes', 'cpfp_product_price_box');

function cpfp_product_price_box() {
    add_meta_box(
            'cpfp_product_price_box', __('Product Price', 'cpfp'), 'cpfp_product_price_box_content', 'product', 'side', 'high'
    );
}

function cpfp_product_price_box_save($post_id) {

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    if (!wp_verify_nonce($_POST['product_price_box_content_nonce'], plugin_basename(__FILE__)))
        return;

    if ('page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id))
            return;
    } else {
        if (!current_user_can('edit_post', $post_id))
            return;
    }
    $product_price = $_POST['product_price'];
    update_post_meta($post_id, 'product_price', $product_price);
}

add_action('save_post', 'cpfp_product_price_box_save');

function cpfp_product_price_box_content($post) {
    wp_nonce_field(plugin_basename(__FILE__), 'product_price_box_content_nonce');
    echo '<label for="product_price"></label>';
    echo '<input type="text" id="product_price" name="product_price" placeholder="enter a price" />';
    echo '<input type="submit">';
}

if (function_exists('add_theme_support')) {
    add_theme_support('post-thumbnails');
    set_post_thumbnail_size(270, 150, true); // default Post Thumbnail dimensions (cropped)
    // additional image sizes
    // delete the next line if you do not need additional image sizes
    add_image_size('category-thumb', 300, 9999); //300 pixels wide (and unlimited height)
}

add_image_size('admin-list-thumb', 80, 80, false);

// add featured thumbnail to admin post columns
function cpfp_add_thumbnail_columns($columns) {
    $columns = array(
        'cb' => '<input type="checkbox" />',
        'featured_thumb' => 'Thumbnail',
        'title' => 'Title',
        'author' => 'Author',
        'categories' => 'Categories',
        'tags' => 'Tags',
        'comments' => '<span class="vers"><div title="Comments" class="comment-grey-bubble"></div></span>',
        'date' => 'Date',
        'price' => 'Price'
    );
    return $columns;
}

function cpfp_add_thumbnail_columns_data($column, $post_id) {
    switch ($column) {
        case 'featured_thumb':
            echo '<a href="' . get_edit_post_link() . '">';
            echo the_post_thumbnail('admin-list-thumb');
            echo '</a>';
            break;
        case 'price':
            echo get_post_meta(get_the_ID(), 'product_price', true);
            break;
    }
}

if (function_exists('add_theme_support')) {
    add_filter('manage_posts_columns', 'cpfp_add_thumbnail_columns');
    add_action('manage_posts_custom_column', 'cpfp_add_thumbnail_columns_data', 10, 2);
    add_filter('manage_pages_columns', 'cpfp_add_thumbnail_columns');
    add_action('manage_pages_custom_column', 'cpfp_add_thumbnail_columns_data', 10, 2);
}

function cpfp_my_updated_messages($messages) {
    global $post, $post_ID;
    $messages['product'] = array(
        0 => '',
        1 => sprintf(__('Product updated. <a href="%s">View product</a>'), esc_url(get_permalink($post_ID))),
        2 => __('Custom field updated.'),
        3 => __('Custom field deleted.'),
        4 => __('Product updated.'),
        5 => isset($_GET['revision']) ? sprintf(__('Product restored to revision from %s'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
        6 => sprintf(__('Product published. <a href="%s">View product</a>'), esc_url(get_permalink($post_ID))),
        7 => __('Product saved.'),
        8 => sprintf(__('Product submitted. <a target="_blank" href="%s">Preview product</a>'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
        9 => sprintf(__('Product scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview product</a>'), date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)), esc_url(get_permalink($post_ID))),
        10 => sprintf(__('Product draft updated. <a target="_blank" href="%s">Preview product</a>'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
    );
    return $messages;
}

add_filter('post_updated_messages', 'cpfp_my_updated_messages');

function cpfp_my_contextual_help($contextual_help, $screen_id, $screen) {
    if ('product' == $screen->id) {

        $contextual_help = '<h2>Products</h2>
  <p>Products show the details of the items that we sell on the website. You can see a list of them on this page in reverse chronological order - the latest one we added is first.</p>
  <p>You can view/edit the details of each product by clicking on its name, or you can perform bulk actions using the dropdown menu and selecting multiple items.</p>';
    } elseif ('edit-product' == $screen->id) {

        $contextual_help = '<h2>Editing products</h2>
  <p>This page allows you to view/modify product details. Please make sure to fill out the available boxes with the appropriate details (product image, price, brand) and <strong>not</strong> add these details to the product description.</p>';
    }
    return $contextual_help;
}

add_action('contextual_help', 'cpfp_my_contextual_help', 10, 3);
