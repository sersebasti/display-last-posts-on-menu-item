<?php
/*
Plugin Name: Display Last Posts on Menu Item
Plugin URI: https://sersebasti.ddns.net/wp_plugins/display-last-posts-on-menu-item/
Description: A plugin to display the latest posts in a specific menu item, helping to dynamically update the menu with recent content.
Version: 1.0.3
Author: Sergio Sebastiani
Author URI: https://sersebasti.ddns.net/CV
Author Email: ser.sebastiani@gmail.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ...

// Initialize a global variable to store messages
global $dlpom_messages;
$dlpom_messages = [];

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
    global $dlpom_messages;
    ?>
    <div class="wrap">
        <h1>Display Last Posts Settings</h1>
        <hr>

        <!-- Configuration Fields -->
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
                // If a menu_id is saved, load the corresponding menu items
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
            <!-- Dropdown for the number of posts -->
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
        <div id="dlpom-update-status"></div>

        <div id="dlpom-progress-container">
            <div id="dlpom-progress-bar" style="width: 0; background-color: #4caf50; color: white; text-align: center;">0%</div>
        </div>
    </div>

    <?php
}


add_action('admin_init', 'dlpom_register_settings');

function dlpom_register_settings() {
    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') !== false) {
        return;
    }
    
    register_setting('dlpom_settings_group', 'dlpom_menu_id');
    register_setting('dlpom_settings_group', 'dlpom_menu_item_id');
    register_setting('dlpom_settings_group', 'dlpom_number_of_posts');
}




// Hook the function to WordPress admin initialization
add_action('admin_init', 'dlpom_check_menu_item');

function dlpom_check_menu_item() {
    global $dlpom_messages;

    // Retrieve individual configuration options
    $menu_id = get_option('dlpom_menu_id');
    $menu_item_id = get_option('dlpom_menu_item_id');
    $post_count = intval(get_option('dlpom_number_of_posts'));

    // Only proceed if the configuration options are already saved in the database
    if (!$menu_id || !$menu_item_id || !$post_count) {
        $dlpom_messages[] = [
            'type' => 'error',
            'message' => __('No Configuration - Update Configuration. The configuration data is missing or incomplete.', 'display-last-posts-on-menu-item')
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

add_action('wp_ajax_dlpom_update_configuration', 'dlpom_update_configuration');

function dlpom_update_configuration() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    // Sanitize and validate input
    $menu_id = isset($_POST['menu_id']) ? sanitize_text_field($_POST['menu_id']) : '';
    $menu_item_id = isset($_POST['menu_item_id']) ? sanitize_text_field($_POST['menu_item_id']) : '';
    $number_of_posts = isset($_POST['number_of_posts']) ? intval($_POST['number_of_posts']) : 0;

    if (!$menu_id || !$menu_item_id || !$number_of_posts) {
        wp_send_json_error('Missing or invalid fields');
    }

    // Update options
    update_option('dlpom_menu_id', $menu_id);
    update_option('dlpom_menu_item_id', $menu_item_id);
    update_option('dlpom_number_of_posts', $number_of_posts);

    wp_send_json_success('Configuration updated successfully');
}


function dlpom_settings_section_callback() {
    echo 'Select the menu and menu item, and specify the number of posts to display.';
}

function dlpom_menu_id_callback() {
    $menus = wp_get_nav_menus();
    $selected_menu = get_option('dlpom_menu_id');
    ?>
    <select id="dlpom_menu_id" name="dlpom_menu_id">
        <option value=""><?php esc_html_e('Select a menu', 'display-last-posts-on-menu-item'); ?></option>
        <?php foreach ($menus as $menu): ?>
            <option value="<?php echo esc_attr($menu->term_id); ?>" <?php selected($selected_menu, $menu->term_id); ?>>
                <?php echo esc_html($menu->name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}



function dlpom_menu_item_id_callback() {
    $selected_menu = get_option('dlpom_menu_id');
    $selected_menu_item = get_option('dlpom_menu_item_id');
    ?>
    <select id="dlpom_menu_item_id" name="dlpom_menu_item_id" <?php if (!$selected_menu) echo 'disabled'; ?>>
        <option value=""><?php esc_html_e('Select a menu item', 'display-last-posts-on-menu-item'); ?></option>
        <?php
        if ($selected_menu) {
            $menu_items = wp_get_nav_menu_items($selected_menu);
            if ($menu_items) {
                foreach ($menu_items as $item) {
                    if ($item->menu_item_parent == 0) { // Only show top-level items
                        ?>
                        <option value="<?php echo esc_attr($item->ID); ?>" <?php selected($selected_menu_item, $item->ID); ?>>
                            <?php echo esc_html($item->title); ?>
                        </option>
                        <?php
                    }
                }
            }
        }
        ?>
    </select>
    <?php
}

function dlpom_number_of_posts_callback() {
    $number_of_posts = get_option('dlpom_number_of_posts', 5);
    $total_posts = wp_count_posts()->publish;
    ?>
    <select id="dlpom_number_of_posts" name="dlpom_number_of_posts">
        <?php for ($i = 1; $i <= intval($total_posts); $i++): ?>
            <option value="<?php echo esc_attr($i); ?>" <?php selected($number_of_posts, $i); ?>>
                <?php echo esc_html($i); ?>
            </option>
        <?php endfor; ?>
    </select>
    <?php
}

add_action('wp_ajax_dlpom_get_menu_items', 'dlpom_get_menu_items');

function dlpom_get_menu_items() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(esc_html__('Unauthorized user', 'display-last-posts-on-menu-item'));
    }

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



add_action('wp_ajax_dlpom_check_menu_items', 'dlpom_check_menu_items');

function dlpom_check_menu_items() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(esc_html__('Unauthorized user', 'display-last-posts-on-menu-item'));
        wp_die();
    }

    // Retrieve each configuration option separately
    $menu_id = get_option('dlpom_menu_id');
    $menu_item_name = get_option('dlpom_menu_item_id'); // Assuming this is a menu item ID, not the name

    // Check if configuration data is valid
    if (!$menu_id || !$menu_item_name) {
        wp_send_json_error(esc_html__('Configuration data is invalid or incomplete.', 'display-last-posts-on-menu-item'));
        wp_die();
    }

    // Get the menu object by ID
    $menu = wp_get_nav_menu_object($menu_id);
    if (!$menu) {
        wp_send_json_error(esc_html__('Menu not found', 'display-last-posts-on-menu-item'));
        wp_die();
    }

    // Get the menu items for the selected menu
    $menu_items = wp_get_nav_menu_items($menu->term_id);
    $menu_item_id = 0;
    $child_items = [];

    foreach ($menu_items as $item) {
        if ($item->ID == $menu_item_name && $item->menu_item_parent == 0) { // Ensure it's a top-level item
            $menu_item_id = $item->ID;
        } elseif ($item->menu_item_parent == $menu_item_id) {
            $child_items[] = $item->title;
        }
    }

    if ($menu_item_id == 0) {
        wp_send_json_error(esc_html__('Menu item not found', 'display-last-posts-on-menu-item'));
        wp_die();
    }

    if (empty($child_items)) {
        wp_send_json_success(['message' => esc_html__('No child items found', 'display-last-posts-on-menu-item')]);
    } else {
        wp_send_json_success(['child_items' => $child_items]);
    }

    wp_die();
}



add_action('wp_ajax_dlpom_get_child_items', 'dlpom_get_child_items');

function dlpom_get_child_items() {
    // Exit early if this is not the right page
    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') === false) {
        return;
    }

    // Check for proper permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
        wp_die();
    }

    // Retrieve each configuration option separately
    $menu_id = get_option('dlpom_menu_id');
    $menu_item_id = get_option('dlpom_menu_item_id');
    $number_of_posts = intval(get_option('dlpom_number_of_posts'));

    // Check if the required configuration options are present and valid
    if (!$menu_id || !$menu_item_id || !$number_of_posts) {
        wp_send_json_error('Configuration data is invalid or incomplete.');
        wp_die();
    }

    // Get the menu object by ID
    $menu = wp_get_nav_menu_object($menu_id);
    if (!$menu) {
        wp_send_json_error('Menu not found.');
        wp_die();
    }

    // Get the menu items for the selected menu
    $menu_items = wp_get_nav_menu_items($menu->term_id);
    $current_child_items = [];

    // Find child items of the specified menu item
    foreach ($menu_items as $item) {
        if ($item->menu_item_parent == $menu_item_id) {
            $current_child_items[] = $item->ID; // Store the IDs of the child items
        }
    }

    // Send child items as JSON response
    if (!empty($current_child_items)) {
        wp_send_json_success($current_child_items);
    } else {
        wp_send_json_success(['message' => 'No child items found.']);
    }

    wp_die();
}


add_action('wp_ajax_dlpom_delete_single_item', 'dlpom_delete_single_item');

function dlpom_delete_single_item() {
    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') !== false) {
        return;
    }    
    
    if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized user');
    }


    $item_id = intval($_POST['item_id']);
    if ($item_id > 0) {
        // Recursively delete the item and its sub-items
        dlpom_delete_item_and_subitems($item_id);
        wp_send_json_success('Item and its sub-items removed successfully.');
    } else {
        wp_send_json_error('Invalid item ID.');
    }
    
}

function dlpom_delete_item_and_subitems($item_id) {

    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') !== false) {
        return;
    }  
    // Get sub-items
    $sub_items = get_posts(array(
        'post_type' => 'nav_menu_item',
        'meta_key' => '_menu_item_menu_item_parent',
        'meta_value' => $item_id,
        'numberposts' => -1
    ));

    // Recursively delete each sub-item
    foreach ($sub_items as $sub_item) {
        dlpom_delete_item_and_subitems($sub_item->ID);
    }

    // Delete the item itself
    wp_delete_post($item_id, true); // True ensures the item is permanently deleted
}

add_action('wp_ajax_dlpom_add_post_to_menu', 'dlpom_add_post_to_menu');

function dlpom_add_post_to_menu() {
    // Check user permissions
    if (!current_user_can('edit_theme_options')) {
        wp_send_json_error('You do not have permission to edit menus.');
    }



    // Validate and sanitize input
    $post_id = intval($_POST['post_id']);
    $menu_id = sanitize_text_field($_POST['menu_id']);
    $parent_menu_item_id = intval($_POST['parent_menu_item_id']);
    

    // Validate post ID and menu ID
    if (!$post_id || !$menu_id || !$parent_menu_item_id) {
        wp_send_json_error('Invalid post ID or menu ID.');
    }

    // Get post details
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error('Invalid post ID.');
    }

    // Add the post to the menu
    $menu_item_data = array(
        'menu-item-object-id' => $post_id,
        'menu-item-object' => 'post',
        'menu-item-type' => 'post_type',
        'menu-item-status' => 'publish',
        'menu-item-title' => $post->post_title,
        'menu-item-url' => get_permalink($post_id),
        'menu-item-parent-id' => $parent_menu_item_id
    );

    $new_menu_item_id = wp_update_nav_menu_item($menu_id, 0, $menu_item_data);

    if (is_wp_error($new_menu_item_id)) {
        wp_send_json_error('Failed to add post to menu.');
    }

    wp_send_json_success('Post added to menu successfully.');
}

// Hook to enqueue admin styles and scripts
add_action('admin_enqueue_scripts', 'dlpom_enqueue_admin_assets');

function dlpom_enqueue_admin_assets($hook) {
    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') !== false) {
        return;
    }

    if ($hook !== 'toplevel_page_dlpom') {
        return;
    }
    wp_enqueue_style('dlpom-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
    wp_enqueue_script('dlpom-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), null, true);
}