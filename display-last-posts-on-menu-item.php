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

// Hook to enqueue admin styles and scripts
add_action('admin_enqueue_scripts', 'dlpom_enqueue_admin_assets');

function dlpom_enqueue_admin_assets($hook) {
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

            // Check if the menu exists
            $menu = wp_get_nav_menu_object($menu_name);
            if ($menu) {
                // Menu exists, now check if the menu item exists
                $menu_items = wp_get_nav_menu_items($menu->term_id);
                $menu_item_exists = false;

                foreach ($menu_items as $item) {
                    if ($item->title === $menu_item_name) {
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
                            'message' => 'The selected menu item and post count are valid.'
                        ];
                    } else {
                        // Invalid post count
                        $dlpom_messages[] = [
                            'type' => 'error',
                            'message' => 'The post count is invalid. It should be between 1 and ' . $total_posts . '.'
                        ];
                    }
                } else {
                    // Menu item does not exist
                    $dlpom_messages[] = [
                        'type' => 'error',
                        'message' => 'The menu item does not exist.'
                    ];
                }
            } else {
                // Menu does not exist
                $dlpom_messages[] = [
                    'type' => 'error',
                    'message' => 'The menu does not exist.'
                ];
            }
        } else {
            // Invalid JSON structure
            $dlpom_messages[] = [
                'type' => 'error',
                'message' => 'The JSON file structure is invalid.'
            ];
        }
    } else {
        // JSON file does not exist
        $dlpom_messages[] = [
            'type' => 'error',
            'message' => 'The JSON file does not exist.'
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
        <?php foreach ($dlpom_messages as $message): ?>
            <div class="dlpom-message-<?php echo esc_attr($message['type']); ?>">
                <?php echo esc_html($message['message']); ?>
            </div>
        <?php endforeach; ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('dlpom_settings_group');
            do_settings_sections('dlpom');
            submit_button('Update Configuration');
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'dlpom_register_settings');

function dlpom_register_settings() {
    register_setting('dlpom_settings_group', 'dlpom_menu_id');
    register_setting('dlpom_settings_group', 'dlpom_menu_item_id');
    register_setting('dlpom_settings_group', 'dlpom_number_of_posts');

    add_settings_section(
        'dlpom_settings_section',
        'Menu Item and Number of Posts',
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
        <option value=""><?php _e('Select a menu', 'dlpom'); ?></option>
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
        <option value=""><?php _e('Select a menu item', 'dlpom'); ?></option>
        <?php
        if ($selected_menu) {
            $menu_items = wp_get_nav_menu_items($selected_menu);
            foreach ($menu_items as $item) {
                ?>
                <option value="<?php echo esc_attr($item->ID); ?>" <?php selected($selected_menu_item, $item->ID); ?>>
                    <?php echo esc_html($item->title); ?>
                </option>
                <?php
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
            <option value="<?php echo $i; ?>" <?php selected($number_of_posts, $i); ?>>
                <?php echo $i; ?>
            </option>
        <?php endfor; ?>
    </select>
    <?php
}

// Show an alert with the selected configuration when the form is submitted
add_action('admin_footer', 'dlpom_admin_footer_script');

function dlpom_admin_footer_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#dlpom_menu_id').change(function() {
            var menuId = $(this).val();
            var data = {
                'action': 'dlpom_get_menu_items',
                'menu_id': menuId
            };

            $.post(ajaxurl, data, function(response) {
                $('#dlpom_menu_item_id').html(response);
                $('#dlpom_menu_item_id').prop('disabled', false);
            });
        });

        $('form').submit(function(e) {
            e.preventDefault();
            var menu = $('#dlpom_menu_id option:selected').text();
            var menuItem = $('#dlpom_menu_item_id option:selected').text();
            var numPosts = $('#dlpom_number_of_posts').val();
            alert('Selected Menu: ' + menu + '\nSelected Menu Item: ' + menuItem + '\nNumber of Posts: ' + numPosts);
        });
    });
    </script>
    <?php
}

add_action('wp_ajax_dlpom_get_menu_items', 'dlpom_get_menu_items');


function dlpom_get_menu_items() {
    $menu_id = intval($_POST['menu_id']);
    $menu_items = wp_get_nav_menu_items($menu_id);

    if ($menu_items) {
        $options = '<option value="">' . __('Select a menu item', 'dlpom') . '</option>';
        foreach ($menu_items as $item) {
            if ($item->menu_item_parent == 0) { // Only show top-level items
                $options .= sprintf('<option value="%s">%s</option>', esc_attr($item->ID), esc_html($item->title));
            }
        }
        echo $options;
    } else {
        echo '<option value="">' . __('No items found', 'dlpom') . '</option>';
    }

    wp_die();
}



add_action('wp_ajax_dlpom_update_json', 'dlpom_update_json');

function dlpom_update_json() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    $menu_id = intval($_POST['menu_id']);
    $menu_item_id = intval($_POST['menu_item_id']);
    $number_of_posts = intval($_POST['number_of_posts']);

    // Verifica che un elemento del menu sia stato selezionato
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

        // Verifica che la directory sia scrivibile
        if (!is_writable(dirname($json_file_path))) {
            wp_send_json_error('Directory is not writable: ' . dirname($json_file_path));
        }

        // Verifica che il file sia scrivibile o che possa essere creato
        if (file_exists($json_file_path) && !is_writable($json_file_path)) {
            wp_send_json_error('File is not writable: ' . $json_file_path);
        }

        // Scrive nel file JSON
        if (file_put_contents($json_file_path, json_encode($json_data))) {
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

    wp_die();
}



?>
