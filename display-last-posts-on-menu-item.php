<?php
/*
Plugin Name: Display Last Posts on Menu Item
Plugin URI: https://sersebasti.ddns.net/wp_plugins/display-last-posts-on-menu-item/
Description: A plugin to display the latest posts in a specific menu item, helping to dynamically update the menu with recent content.
Version: 1.0.4
Author: Sergio Sebastiani
Author URI: https://sersebasti.ddns.net/CV
Author Email: ser.sebastiani@gmail.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ...


// Register data in wp dB
add_action('admin_init', 'dlpom_register_settings');

function dlpom_register_settings() {
    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') !== false) {
        return;
    }
    register_setting('dlpom_settings_group', 'dlpom_menu_id');
    register_setting('dlpom_settings_group', 'dlpom_menu_item_id');
    register_setting('dlpom_settings_group', 'dlpom_number_of_posts');
}


// Add settings page
add_action('admin_menu', 'dlpom_add_settings_page');

function dlpom_add_settings_page() {
    add_menu_page(
        'Display Last Posts Settings',
        'Display Last Posts',
        'manage_options',
        'dlpom',
        'dlpom_render_settings_page'
    );
}

function dlpom_render_settings_page() {

    ?>
    <div class="wrap">
        <h1>Display Last Posts Settings</h1>
        <hr>
        <p>
            <label for="dlpom_menu_id">Select Menu:</label>
            <select id="dlpom_menu_id">
                <option value="" disabled <?php echo (get_option('dlpom_menu_id') === false) ? 'selected' : ''; ?>>Select Menu</option>
                <?php
                $menus = wp_get_nav_menus();
                $selected_menu_id = get_option('dlpom_menu_id');
                foreach ($menus as $menu) {
                    echo '<option value="' . esc_attr($menu->term_id) . '" ' . selected($selected_menu_id, $menu->term_id, false) . '>' . esc_html($menu->name) . '</option>';
                }
                ?>
            </select>
        </p>

        <p>
            <label for="dlpom_menu_item_id">Select Menu Item:</label>
            <select id="dlpom_menu_item_id" <?php if (!$selected_menu_id) echo 'disabled'; ?>>
                <option value="" disabled>Select Menu Item</option>
                <?php
                $selected_menu_item_id = get_option('dlpom_menu_item_id');
                if ($selected_menu_id) {
                    $menu_items = wp_get_nav_menu_items($selected_menu_id);
                    foreach ($menu_items as $item) {
                        echo '<option value="' . esc_attr($item->ID) . '" ' . selected($selected_menu_item_id, $item->ID, false) . '>' . esc_html($item->title) . '</option>';
                    }
                }
                ?>
            </select>
        </p>

        <p> 
            <label for="dlpom_number_of_posts">Number of Posts:</label>
                <select id="dlpom_number_of_posts">
                    <?php
                    $selected_posts_count = get_option('dlpom_number_of_posts', 5);
                    $total_posts = wp_count_posts()->publish; // Get the total number of posts
                    for ($i = 1; $i <= $total_posts; $i++) {
                        $is_selected = selected($selected_posts_count, $i, false);
                        echo "<option value='$i' $is_selected>$i</option>";
                    }
                    ?>
                </select>
        </p>

        <p>
            <button id="dlpom-update-config" class="button button-primary">Update Configuration</button>
            <div id="dlpom-config-status"></div>
        </p>
        
        <hr>
        
        <button id="dlpom-update-menu" class="button button-primary">Update Menu with Latest Posts</button>
        <div id="dlpom-loading" style="display: none; text-align: left; margin-top: 20px;">
            <img src="<?php echo plugins_url( 'loading.gif', __FILE__ ); ?>" alt="Loading..." style="width: 200px; height: 200px;">
            <p>Processing, please wait. This may take a few minutes depending on the number of posts.</p>
        </div>
        <div id="dlpom-update-status"></div>

    

    </div>

    <?php
}



// Hook the function to WordPress admin initialization
add_action('admin_init', 'dlpom_check_menu_item');

function dlpom_check_menu_item() {

    // Retrieve individual configuration options
    $menu_id = get_option('dlpom_menu_id');
    $menu_item_id = get_option('dlpom_menu_item_id');
    $post_count = intval(get_option('dlpom_number_of_posts'));

    // Only proceed if the configuration options are already saved in the database
    if (!$menu_id || !$menu_item_id || !$post_count) {
        $dlpom_messages[] = [
            'type' => 'error',
            'message' => esc_html('No Configuration - Update Configuration. The configuration data is missing or incomplete.', 'display-last-posts-on-menu-item')
        ];
        return;
    }

    // Get the total number of published posts
    $total_posts = wp_count_posts()->publish;

    // Check if the menu exists
    $menu = wp_get_nav_menu_object($menu_id);
    if (!$menu) {
        $dlpom_messages[] = [
            'type' => 'error',
            'message' => wp_kses_post(
                sprintf(
                    __('Configuration Error - Update Configuration. The menu does not exist. Menu ID: %1$s, Menu Item ID: %2$s, Number of Posts: %3$d', 'display-last-posts-on-menu-item'),
                    esc_html($menu_id),
                    esc_html($menu_item_id),
                    esc_html($post_count)
                )
            )
        ];
        return;
    }

    // Check if the menu item exists
    $menu_items = wp_get_nav_menu_items($menu->term_id);
    $menu_item_exists = false;
    foreach ($menu_items as $item) {
        if ($item->ID == $menu_item_id && $item->menu_item_parent == 0) { // Ensure it's a top-level item
            $menu_item_exists = true;
            break;
        }
    }

    if (!$menu_item_exists) {
        $dlpom_messages[] = [
            'type' => 'error',
            'message' => wp_kses_post(
                sprintf(
                    __('Configuration Error - Update Configuration. The menu item does not exist. Menu ID: %1$s, Menu Item ID: %2$s, Number of Posts: %3$d', 'display-last-posts-on-menu-item'),
                    esc_html($menu_id),
                    esc_html($menu_item_id),
                    esc_html($post_count)
                )
            )
        ];
        return;
    }

    // Check if the post count is valid
    if ($post_count < 1 || $post_count > $total_posts) {
        $dlpom_messages[] = [
            'type' => 'error',
            'message' => wp_kses_post(
                sprintf(
                    __('Configuration Error - Update Configuration. Invalid post count. It should be between 1 and %1$d. Menu ID: %2$s, Menu Item ID: %3$s, Number of Posts: %4$d', 'display-last-posts-on-menu-item'),
                    intval($total_posts),
                    esc_html($menu_id),
                    esc_html($menu_item_id),
                    intval($post_count)
                )
            )
        ];
        return;
    }

    // Success message if everything is valid
    $dlpom_messages[] = [
        'type' => 'success',
        'message' => wp_kses_post(
            sprintf(
                __('The selected menu item and post count are valid.<br><ul><li>Menu ID: %1$s</li><li>Menu Item ID: %2$s</li><li>Number of Posts: %3$d</li></ul>', 'display-last-posts-on-menu-item'),
                esc_html($menu_id),
                esc_html($menu_item_id),
                esc_html($post_count)
            )
        )
    ];
}


add_action('wp_ajax_dlpom_get_menu_items', 'dlpom_get_menu_items');

function dlpom_get_menu_items() {

    // Verify nonce and permissions
    my_plugin_check_permissions('dlpom_nonce_action');


    $menu_id = intval($_POST['menu_id']);
    if (!$menu_id) {
        wp_send_json_error(esc_html__('Invalid menu ID', 'display-last-posts-on-menu-item'));
    }

    $menu_items = wp_get_nav_menu_items($menu_id);

    if ($menu_items) {
        $options = [];
        foreach ($menu_items as $item) {
            if ($item->menu_item_parent == 0) { // Only show top-level items
                $options[] = [
                    'id' => $item->ID,
                    'title' => $item->title
                ];
            }
        }
        wp_send_json_success(['menu_items' => $options]);
    } else {
        wp_send_json_error(esc_html__('No items found', 'display-last-posts-on-menu-item'));
    }
}



add_action('wp_ajax_dlpom_update_configuration', 'dlpom_update_configuration');

function dlpom_update_configuration() {

    // Verify nonce and permissions
    my_plugin_check_permissions('dlpom_nonce_action'); 

    // Retrieve and validate the data
    $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
    $menu_item_id = isset($_POST['menu_item_id']) ? intval($_POST['menu_item_id']) : 0;
    $number_of_posts = isset($_POST['number_of_posts']) ? intval($_POST['number_of_posts']) : 0;

    if (!$menu_id || !$menu_item_id || !$number_of_posts) {
        wp_send_json_error(esc_html__('Invalid configuration data', 'display-last-posts-on-menu-item'));
        exit; // Stop further execution
    }

    // Here you would update your plugin's configuration using the retrieved values
    // For example, updating options:
    update_option('dlpom_menu_id', $menu_id);
    update_option('dlpom_menu_item_id', $menu_item_id);
    update_option('dlpom_number_of_posts', $number_of_posts);

    // Send a success message back to JavaScript
    wp_send_json_success(esc_html__('Configuration updated successfully', 'display-last-posts-on-menu-item'));
}


add_action('wp_ajax_dlpom_update_menu', 'dlpom_update_menu');

function dlpom_update_menu() {
    // Verify nonce and permissions
    my_plugin_check_permissions('dlpom_nonce_action');

    // Retrieve configuration from database
    $menu_id = get_option('dlpom_menu_id');
    $menu_item_id = get_option('dlpom_menu_item_id');
    $number_of_posts = intval(get_option('dlpom_number_of_posts'));

    // Verify the options are valid
    if (!$menu_id || !$menu_item_id || !$number_of_posts) {
        wp_send_json_error(esc_html__('Invalid configuration settings.', 'display-last-posts-on-menu-item'));
        exit;
    }

    // Get the most recent posts
    $recent_posts = get_posts([
        'numberposts' => $number_of_posts,
        'post_status' => 'publish',
        'orderby' => 'post_date',
        'order' => 'DESC'
    ]);

    // Prepare an array of the most recent post IDs for comparison
    $recent_post_ids = array_map(function($post) {
        return $post->ID;
    }, $recent_posts);

    // Fetch current menu items for the specified menu and menu item
    $menu_items = wp_get_nav_menu_items($menu_id);
    $current_post_ids = [];

    // Find current posts in the specified menu item
    foreach ($menu_items as $item) {
        if ($item->menu_item_parent == $menu_item_id && $item->type == 'post_type' && $item->object == 'post') {
            $current_post_ids[] = $item->object_id;
        }
    }


    // Check if the current menu item already has the recent posts in the correct order
    $current_post_ids = array_map('intval', $current_post_ids);
    $recent_post_ids = array_map('intval', $recent_post_ids);
    if ($current_post_ids === $recent_post_ids) {
        wp_send_json_success(esc_html__('The menu already contains the latest posts in the correct order.', 'display-last-posts-on-menu-item'));
        exit;
    }

    // Delete all existing items under the specified menu item
    foreach ($menu_items as $item) {
        if ($item->menu_item_parent == $menu_item_id) {
            wp_delete_post($item->ID, true); // Delete menu item
        }
    }

    // Add the most recent posts to the menu item
    foreach ($recent_posts as $post) {
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title' => $post->post_title,
            'menu-item-object' => 'post',
            'menu-item-object-id' => $post->ID,
            'menu-item-type' => 'post_type',
            'menu-item-parent-id' => $menu_item_id,
            'menu-item-status' => 'publish'
        ]);
    }

    // Send a success response back to JavaScript with a confirmation message
    wp_send_json_success(esc_html__('Menu updated successfully with the latest posts.', 'display-last-posts-on-menu-item'));
}


add_action('admin_enqueue_scripts', 'dlpom_enqueue_admin_assets');

function dlpom_enqueue_admin_assets($hook) {
    // Load assets only on the specified admin page
    if ($hook !== 'toplevel_page_dlpom') {
        return;
    }

    // Enqueue CSS and JS
    wp_enqueue_style('dlpom-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
    wp_enqueue_script('dlpom-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), null, true);

    // Localize script to pass AJAX URL and nonce to JavaScript
    wp_localize_script('dlpom-admin-script', 'dlpomData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dlpom_nonce_action')
    ]);
}


function my_plugin_check_permissions($nonce_action, $capability = 'manage_options') {
    // Check nonce validity
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $nonce_action)) {
        wp_send_json_error(esc_html__('Unauthorized access', 'display-last-posts-on-menu-item'),401);
        exit; // Stop further execution
    }

    // Check user permissions
    if (!current_user_can($capability)) {
        wp_send_json_error(esc_html__('User lacks permission', 'display-last-posts-on-menu-item'),403);
        exit; // Stop further execution
    }
}