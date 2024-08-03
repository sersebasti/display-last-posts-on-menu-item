<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/*
Plugin Name: Display Last Posts on Menu Item
Description: Checks for a specific menu and menu item based on a JSON file and verifies post count.
Version: 1.1
Author: Your Name
*/

// Initialize a global variable to store messages
global $dlpom_messages;
$dlpom_messages = [];

// Hook the function to WordPress admin initialization
add_action('admin_init', 'dlpom_check_menu_item');

// Function to check the JSON file and validate the menu, menu item, and post count
function dlpom_check_menu_item() {
    global $dlpom_messages;

    // Path to the JSON file
    $json_file_path = plugin_dir_path(__FILE__) . 'selected_menu_item.json';

    // Check if the JSON file exists
    if (file_exists($json_file_path)) {
        // Read the JSON file
        $json_content = file_get_contents($json_file_path);
        $menu_data = json_decode($json_content, true);

        // Check if JSON decoding was successful and required fields are present
        if ($menu_data && isset($menu_data['menu_name']) && isset($menu_data['menu_item_name']) && isset($menu_data['post_count'])) {
            // Get menu, menu item name, and post count from JSON
            $menu_name = $menu_data['menu_name'];
            $menu_item_name = $menu_data['menu_item_name'];
            $post_count = intval($menu_data['post_count']);

            // Get the total number of posts
            $total_posts = wp_count_posts()->publish;

            // Check if the menu and menu item exist in WordPress
            if (dlpom_menu_item_exists($menu_name, $menu_item_name)) {
                if ($post_count <= $total_posts) {
                    $dlpom_messages[] = "Found JSON and check OK. Number of posts in JSON: $post_count. Total posts: $total_posts.";
                } else {
                    $dlpom_messages[] = "Post count in JSON ($post_count) exceeds the total number of posts ($total_posts).";
                }
            } else {
                // Menu or menu item not found, delete the JSON file
                unlink($json_file_path);
                $dlpom_messages[] = "Menu or menu item not found.";
            }
        } else {
            // Invalid JSON structure, delete the file and store message
            unlink($json_file_path);
            $dlpom_messages[] = "Invalid JSON structure or missing fields.";
        }
    } else {
        // JSON file does not exist, store message
        $dlpom_messages[] = "JSON file not found.";
    }
}

// Function to check if a menu and menu item exist
function dlpom_menu_item_exists($menu_name, $menu_item_name) {
    // Get the menu by name
    $menu = wp_get_nav_menu_object($menu_name);
    if ($menu) {
        // Get the menu items
        $menu_items = wp_get_nav_menu_items($menu->term_id);
        if ($menu_items) {
            // Check if the specified menu item exists
            foreach ($menu_items as $item) {
                if ($item->title == $menu_item_name) {
                    return true;
                }
            }
        }
    }
    return false;
}

// Hook to add a new menu item in the admin panel
add_action('admin_menu', 'dlpom_menu');

// Function to add a menu item and the corresponding page
function dlpom_menu() {
    add_menu_page(
        'Display Last Posts on Menu Item',  // Page title
        'Last Posts Menu',                  // Menu title
        'manage_options',                   // Capability
        'dlpom',                            // Menu slug
        'dlpom_page',                       // Function to display the page content
        'dashicons-welcome-widgets-menus',  // Icon URL
        6                                   // Position
    );
}

// Function to display the plugin page content
function dlpom_page() {
    ?>
    <div class="wrap">
        <h1>Display Last Posts on Menu Item</h1>
        <div id="dlpom-output">
            <?php dlpom_display_output(); ?>
        </div>
    </div>
    <?php
}

// Function to display the messages on the plugin page
function dlpom_display_output() {
    global $dlpom_messages;
    if (!empty($dlpom_messages)) {
        echo '<table class="widefat">';
        echo '<thead><tr><th>Message</th></tr></thead>';
        echo '<tbody>';
        foreach ($dlpom_messages as $message) {
            echo '<tr><td>' . esc_html($message) . '</td></tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No messages to display.</p>';
    }
}
