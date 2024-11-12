(function($) {


jQuery(document).ready(function($) {
    // Check if we're on the plugin page
    if (window.location.href.indexOf('page=dlpom') !== -1) {

        // Changing the menu in the configuration
        $('#dlpom_menu_id').change(function() {
            var menuId = $(this).val();
            var data = {
                'action': 'dlpom_get_menu_items',  // Specify the action name
                'menu_id': menuId,
                'nonce': dlpomData.nonce           // Include the nonce for verification
            };

            $.post(dlpomData.ajax_url, data, function(response) {
                if (response.success) {

                    // Check if response.data is an object and contains the menu_items array
                    if (response.data && Array.isArray(response.data.menu_items)) {
                        const menuItems = response.data.menu_items;

                        // Clear existing options and add a placeholder option
                        $('#dlpom_menu_item_id').empty();
                        $('#dlpom_menu_item_id').append('<option value="">Select a menu item</option>');

                        // Populate the dropdown with menu items
                        menuItems.forEach(function(item) {
                            $('#dlpom_menu_item_id').append(
                                $('<option>', {
                                    value: item.id,
                                    text: item.title
                                })
                            );
                        });

                        // Enable the dropdown
                        $('#dlpom_menu_item_id').prop('disabled', false);
                    } else {
                        $('#dlpom-update-status').html('<p> error: ' + response.data + '</p>'); 
                    }
                } else {
                    $('#dlpom-update-status').html('<p> error: ' + response.data + '</p>'); 
                }
            });
        });

        $('#dlpom-update-config').click(function(e) {
            // Show the loading spinner
            $('#dlpom-loading').show()

            e.preventDefault();
        
            var data = {
                action: 'dlpom_update_configuration',
                menu_id: $('#dlpom_menu_id').val(),
                menu_item_id: $('#dlpom_menu_item_id').val(),
                number_of_posts: $('#dlpom_number_of_posts').val(),
                nonce: dlpomData.nonce // Add nonce for security
            };
        
            // Use the localized AJAX URL from dlpomData
            $.post(dlpomData.ajax_url, data, function(response) {

                // Hide the loading spinner once the AJAX call is complete
                $('#dlpom-loading').hide();

                if (response.success) {
                    $('#dlpom-update-status').html('<p>' + response.data + '</p>');
                } else {
                    $('#dlpom-update-status').html('<p>Error: ' + response.data + '</p>');
                }
            });
        });

        $('#dlpom-update-menu').click(function() {
            $('#dlpom-update-status').empty();
            
            // Show the loading spinner
            $('#dlpom-loading').show();
            
            // Data to be sent in the AJAX request
            var data = {
                'action': 'dlpom_update_menu',
                'nonce': dlpomData.nonce // Include the nonce for security
            };
            
            // Send AJAX request using the localized ajax URL
            $.post(dlpomData.ajax_url, data, function(response) {
                // Hide the loading spinner once the AJAX call is complete
                $('#dlpom-loading').hide();
        
                if (response.success) {
                    $('#dlpom-update-status').html('<p>' + response.data + '</p>');
                } else {
                    $('#dlpom-update-status').html('<p>Error: ' + response.data + '</p>');
                }
            });
        });
        
    }
});


})(jQuery); 
