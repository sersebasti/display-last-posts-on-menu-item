<?php
/*
Plugin Name: Display Last Posts on Menu Item
Plugin URI: https://sersebasti.ddns.net/wp_plugins/display-last-posts-on-menu-item/
Description: A plugin to display the latest posts in a specific menu item, helping to dynamically update the menu with recent content.
Version: 1.0.0
Author: Sergio Sebastiani
Author URI: https://sersebasti.ddns.net/CV
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


// Initialize a global variable to store messages
global $dlpom_messages;
$dlpom_messages = [];

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

// Hook the function to WordPress admin initialization
add_action('admin_init', 'dlpom_check_menu_item');

// Function to check the JSON file and validate the menu, menu item, and post count
function dlpom_check_menu_item() {
    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') !== false) {
        return;
    }
    
    global $dlpom_messages;

    // Path to the JSON file
    $json_file_path = plugin_dir_path(__FILE__) . 'selected_menu_item.json';

    // Check if the JSON file exists
    if (file_exists($json_file_path)) {
        // Read the JSON file
        $json_content = dlpom_read_json_file($json_file_path);
        $menu_data = json_decode($json_content, true);


    

        // Check if JSON decoding was successful and required fields are present
        if ($menu_data && isset($menu_data['menu_name']) && isset($menu_data['menu_item_name']) && isset($menu_data['post_count'])) {
            // Get menu, menu item name, and post count from JSON
            $menu_name = $menu_data['menu_name'];
            $menu_item_name = $menu_data['menu_item_name'];
            $post_count = intval($menu_data['post_count']);

            // Get the total number of posts
            $total_posts = wp_count_posts()->publish;

            // Check if the menu exists
            $menu = wp_get_nav_menu_object($menu_name);
            if ($menu) {
                // Menu exists, now check if the menu item exists
                $menu_items = wp_get_nav_menu_items($menu->term_id);
                $menu_item_exists = false;

                foreach ($menu_items as $item) {
                    if ($item->title === $menu_item_name && $item->menu_item_parent == 0) { // Ensure it's a top-level item
                        $menu_item_exists = true;
                        break;
                    }
                }

                if ($menu_item_exists) {
                    // Menu item exists, now check the post count
                    if ($post_count > 0 && $post_count <= $total_posts) {
                        // Everything is correct
                        $dlpom_messages[] = [
                            'type' => 'success',
                            'message' => wp_kses_post(
                                'The selected menu item and post count are valid.<br>' .
                                '<ul>' .
                                '<li>Menu: ' . esc_html($menu_name) . '</li>' .
                                '<li>Menu Item: ' . esc_html($menu_item_name) . '</li>' .
                                '<li>Number of Posts: ' . esc_html($post_count) . '</li>' .
                                '</ul>'
                            )
                        ];
                    } else {
                        // Invalid post count
                        $dlpom_messages[] = [
                            'type' => 'error',
                            'message' => 'Configuration Error - Update Configuration. ' .
                                         'Invalid post count. It should be between 1 and ' . $total_posts . '. ' .
                                         'Menu: ' . esc_html($menu_name) . ', ' .
                                         'Menu Item: ' . esc_html($menu_item_name) . ', ' .
                                         'Number of Posts: ' . esc_html($post_count)
                        ];
                    }
                } else {
                    // Menu item does not exist
                    $dlpom_messages[] = [
                        'type' => 'error',
                        'message' => 'Configuration Error - Update Configuration. ' .
                                     'The menu item does not exist. ' .
                                     'Menu: ' . esc_html($menu_name) . ', ' .
                                     'Menu Item: ' . esc_html($menu_item_name) . ', ' .
                                     'Number of Posts: ' . esc_html($post_count)
                    ];
                }
            } else {
                // Menu does not exist
                $dlpom_messages[] = [
                    'type' => 'error',
                    'message' => 'Configuration Error - Update Configuration. ' .
                                 'The menu does not exist. ' .
                                 'Menu: ' . esc_html($menu_name) . ', ' .
                                 'Menu Item: ' . esc_html($menu_item_name) . ', ' .
                                 'Number of Posts: ' . esc_html($post_count)
                ];
            }
        } else {
            // Invalid JSON structure
            $dlpom_messages[] = [
                'type' => 'error',
                'message' => 'Configuration Error - Update Configuration. ' .
                             'The JSON file structure is invalid.'
            ];
        }
    } else {
        // JSON file does not exist
        $dlpom_messages[] = [
            'type' => 'error',
            'message' => 'No Configuration - Update Configuration.'
        ];
    }
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
    global $dlpom_messages;
    ?>
    <div class="wrap">
        <h1>Display Last Posts Settings</h1>
        <hr>
        <form method="post" action="options.php">
            <?php
            settings_fields('dlpom_settings_group');
            do_settings_sections('dlpom');
            submit_button('Update Configuration');
            ?>
        </form>
        <?php foreach ($dlpom_messages as $message): ?>
            <div class="dlpom-message-<?php echo esc_attr($message['type']); ?>">
                <?php echo wp_kses_post($message['message']); ?>
            </div>
        <?php endforeach; ?>
        <hr><br><br>
        <button id="dlpom-update-menu" class="button button-primary">Update Menu with Latest Posts</button>
        <div id="dlpom-update-status"></div>

        <div id="dlpom-progress-container">
            <div id="dlpom-progress-bar" style="width: 0; background-color: #4caf50; color: white; text-align: center;">100%</div>
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

    add_settings_section(
        'dlpom_settings_section',
        'Configuration',
        'dlpom_settings_section_callback',
        'dlpom'
    );

    add_settings_field(
        'dlpom_menu_id',
        'Select Menu',
        'dlpom_menu_id_callback',
        'dlpom',
        'dlpom_settings_section'
    );

    add_settings_field(
        'dlpom_menu_item_id',
        'Select Menu Item',
        'dlpom_menu_item_id_callback',
        'dlpom',
        'dlpom_settings_section'
    );

    add_settings_field(
        'dlpom_number_of_posts',
        'Number of Posts',
        'dlpom_number_of_posts_callback',
        'dlpom',
        'dlpom_settings_section'
    );
}

function dlpom_settings_section_callback() {
    echo 'Select the menu and menu item, and specify the number of posts to display.';
}

function dlpom_menu_id_callback() {
    $menus = wp_get_nav_menus();
    $selected_menu = get_option('dlpom_menu_id');
    ?>
    <select id="dlpom_menu_id" name="dlpom_menu_id">
        <option value=""><?php esc_html_e('Select a menu', 'dlpom'); ?></option>
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
        <option value=""><?php esc_html_e('Select a menu item', 'dlpom'); ?></option>
        <?php
        if ($selected_menu) {
            $menu_items = wp_get_nav_menu_items($selected_menu);
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
        ?>
    </select>
    <?php
}

function dlpom_number_of_posts_callback() {
    $number_of_posts = get_option('dlpom_number_of_posts', 5);
    $total_posts = wp_count_posts()->publish;
    ?>
    <select id="dlpom_number_of_posts" name="dlpom_number_of_posts">
        <?php for ($i = 1; $i <= $total_posts; $i++): ?>
            <option value="<?php echo esc_attr($i); ?>" <?php selected($number_of_posts, $i); ?>>
                <?php echo esc_html($i); ?>
            </option>
        <?php endfor; ?>
    </select>
    <?php
}


add_action('wp_ajax_dlpom_get_menu_items', 'dlpom_get_menu_items');

function dlpom_get_menu_items() {
    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') !== false) {
        return;
    }
 
    $menu_id = intval($_POST['menu_id']);
    $menu_items = wp_get_nav_menu_items($menu_id);

    if ($menu_items) {
        $options = '<option value="">' . __('Select a menu item', 'dlpom') . '</option>';
        foreach ($menu_items as $item) {
            if ($item->menu_item_parent == 0) { // Only show top-level items
                $options .= sprintf('<option value="%s">%s</option>', esc_attr($item->ID), esc_html($item->title));
            }
        }
        wp_send_json_success($options);
    } else {
        wp_send_json_error('No items found');
    }
      
}

add_action('wp_ajax_dlpom_update_json', 'dlpom_update_json');

function dlpom_update_json() {
    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') !== false) {
        return;
    }

    $filesystem_initialized = dlpom_initialize_filesystem();
    if ( is_wp_error($filesystem_initialized) ) {
        return false;
    }

    global $wp_filesystem;

    $filesystem_initialized = dlpom_initialize_filesystem();
    if ( is_wp_error($filesystem_initialized) ) {
        return false;
    }

    global $wp_filesystem;

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    $menu_id = intval($_POST['menu_id']);
    $menu_item_id = intval($_POST['menu_item_id']);
    $number_of_posts = intval($_POST['number_of_posts']);

    if ($menu_item_id == 0) {
        wp_send_json_error('No menu item selected.');
    }

    $menu = wp_get_nav_menu_object($menu_id);
    $menu_item = wp_get_nav_menu_items($menu_id, ['p' => $menu_item_id])[0];

    if ($menu && $menu_item && $number_of_posts > 0) {
        $json_data = [
            'menu_name' => $menu->name,
            'menu_item_name' => $menu_item->title,
            'post_count' => $number_of_posts
        ];

        $json_file_path = plugin_dir_path(__FILE__) . 'selected_menu_item.json';

        if (! $wp_filesystem->is_writable(dirname($json_file_path))) {
            wp_send_json_error('Directory is not writable: ' . dirname($json_file_path));
        }

        if (file_exists($json_file_path) && ! $wp_filesystem->is_writable($json_file_path)) {
            wp_send_json_error('File is not writable: ' . $json_file_path);
        }

        // Scrive nel file JSON
        if ($wp_filesystem->put_contents($json_file_path, wp_json_encode($json_data))) {
            dlpom_check_menu_item(); // Re-run the JSON check function
            wp_send_json_success([
                'message' => 'Configuration updated successfully.',
                'data' => $json_data
            ]);
        } else {
            wp_send_json_error('Failed to update JSON file.');
        }
    } else {
        wp_send_json_error('Invalid data provided.');
    }
}

add_action('wp_ajax_dlpom_check_menu_items', 'dlpom_check_menu_items');

function dlpom_check_menu_items() {
    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') !== false) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    // Path to the JSON file
    $json_file_path = plugin_dir_path(__FILE__) . 'selected_menu_item.json';

    // Check if the JSON file exists and read the content
    if (!file_exists($json_file_path)) {
        wp_send_json_error('No configuration file found.');
    }

    $json_content = dlpom_read_json_file($json_file_path);
    $menu_data = json_decode($json_content, true);

    // Check if JSON decoding was successful and required fields are present
    if (!$menu_data || !isset($menu_data['menu_name']) || !isset($menu_data['menu_item_name'])) {
        wp_send_json_error('Invalid JSON structure.');
    }

    // Get menu and menu item name from JSON
    $menu_name = $menu_data['menu_name'];
    $menu_item_name = $menu_data['menu_item_name'];

    // Get the menu object
    $menu = wp_get_nav_menu_object($menu_name);
    if (!$menu) {
        wp_send_json_error('Menu not found.');
    }

    // Get the menu item
    $menu_items = wp_get_nav_menu_items($menu->term_id);
    $menu_item_id = 0;
    $child_items = [];

    foreach ($menu_items as $item) {
        if ($item->title === $menu_item_name && $item->menu_item_parent == 0) { // Ensure it's a top-level item
            $menu_item_id = $item->ID;
        } elseif ($item->menu_item_parent == $menu_item_id) {
            $child_items[] = $item->title;
        }
    }

    if ($menu_item_id == 0) {
        wp_send_json_error('Menu item not found.');
    }

    if (empty($child_items)) {
        wp_send_json_success('No child items found.');
    } else {
        $child_items_list = '<ul>';
        foreach ($child_items as $child) {
            $child_items_list .= '<li>' . esc_html($child) . '</li>';
        }
        $child_items_list .= '</ul>';
        wp_send_json_success($child_items_list);
    }
}

add_action('wp_ajax_dlpom_check', 'dlpom_check');

function dlpom_check() {
    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') !== false) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    // Path to the JSON file
    $json_file_path = plugin_dir_path(__FILE__) . 'selected_menu_item.json';

    // Check if the JSON file exists and read the content
    if (!file_exists($json_file_path)) {
        wp_send_json_error('No configuration file found.');
    }

    $json_content = dlpom_read_json_file($json_file_path);
    $menu_data = json_decode($json_content, true);

    // Check if JSON decoding was successful and required fields are present
    if (!$menu_data || !isset($menu_data['menu_name']) || !isset($menu_data['menu_item_name']) || !isset($menu_data['post_count'])) {
        wp_send_json_error('Invalid JSON structure.');
    }
    

    // Get menu, menu item name, and post count from JSON
    $menu_name = $menu_data['menu_name'];
    $menu_item_name = $menu_data['menu_item_name'];
    $number_of_posts = intval($menu_data['post_count']);

    // Get the menu object
    $menu = wp_get_nav_menu_object($menu_name);
    if (!$menu) {
        wp_send_json_error('Menu not found.');
    }

    // Get the menu item
    $menu_items = wp_get_nav_menu_items($menu->term_id);
    $menu_item_id = 0;
    $current_child_items = [];

    foreach ($menu_items as $item) {
        if ($item->title === $menu_item_name && $item->menu_item_parent == 0) { // Ensure it's a top-level item
            $menu_item_id = $item->ID;
        }
    }

    if ($menu_item_id == 0) {
        wp_send_json_error('Menu item not found.');
    }

    // Retrieve the child items of the selected menu item
    foreach ($menu_items as $item) {
        if ($item->menu_item_parent == $menu_item_id) {
            
            $current_child_items[] = [
                'title' => $item->title,
                'type' => $item->type,
                'object' => $item->object,
                'ID' => $item->ID
            ];


        }
    }

    

    // Get the latest posts
    $recent_posts = wp_get_recent_posts([
        'numberposts' => $number_of_posts,
        'post_status' => 'publish',
        'orderby' => 'post_date',
        'order' => 'DESC'
    ]);
    
    $R['menu_name'] = $menu_name;
    $R['menu_id'] = $menu->term_id;
    $R['menu_item_name'] = $menu_item_name;
    $R['menu_item_id'] = $menu_item_id;
    $R['current_child_items'] = $current_child_items;
    $R['recent_posts'] = $recent_posts;

    wp_send_json_success(wp_json_encode($R));
}


add_action('wp_ajax_dlpom_get_child_items', 'dlpom_get_child_items');

function dlpom_get_child_items() {
    if (strpos($_SERVER['PHP_SELF'], 'display-last-posts-on-menu-item.php') !== false) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    // Path to the JSON file
    $json_file_path = plugin_dir_path(__FILE__) . 'selected_menu_item.json';

    // Check if the JSON file exists and read the content
    if (!file_exists($json_file_path)) {
        wp_send_json_error('No configuration file found.');
    }

    $json_content = dlpom_read_json_file($json_file_path);
    $menu_data = json_decode($json_content, true);

    // Check if JSON decoding was successful and required fields are present
    if (!$menu_data || !isset($menu_data['menu_name']) || !isset($menu_data['menu_item_name']) || !isset($menu_data['post_count'])) {
        wp_send_json_error('Invalid JSON structure.');
    }

    // Get menu, menu item name, and post count from JSON
    $menu_name = $menu_data['menu_name'];
    $menu_item_name = $menu_data['menu_item_name'];
    $number_of_posts = intval($menu_data['post_count']);

    // Get the menu object
    $menu = wp_get_nav_menu_object($menu_name);
    if (!$menu) {
        wp_send_json_error('Menu not found.');
    }

    // Get the menu item
    $menu_items = wp_get_nav_menu_items($menu->term_id);
    $menu_item_id = 0;
    $current_child_items = [];

    foreach ($menu_items as $item) {
        if ($item->title === $menu_item_name && $item->menu_item_parent == 0) { // Ensure it's a top-level item
            $menu_item_id = $item->ID;
        }
    }


    if ($menu_item_id == 0) {
        wp_send_json_error('Menu item not found.');
    }


    // Retrieve the child items of the selected menu item
    foreach ($menu_items as $item) {
        if ($item->menu_item_parent == $menu_item_id) {
            $current_child_items[] = $item->ID; // Store the IDs of the child items
        }
    }
    
    wp_send_json_success(wp_json_encode($current_child_items));

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

// Function to read JSON file content
function dlpom_read_json_file($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }

    $filesystem_initialized = dlpom_initialize_filesystem();
    if ( is_wp_error($filesystem_initialized) ) {
        return false;
    }

    global $wp_filesystem;

    $content = $wp_filesystem->get_contents($file_path);
    if ($content === false) {
        return false;
    }

    return $content;
}


function dlpom_initialize_filesystem() {
    if ( ! function_exists('WP_Filesystem') ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    global $wp_filesystem;

    if ( ! WP_Filesystem() ) {
        return new WP_Error('filesystem_not_initialized', __('Filesystem initialization failed.'));
    }

    return true;
}