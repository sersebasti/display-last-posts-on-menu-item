(function($) {

    // Helper function to display status messages
    function displayStatusMessage(message, isError = false) {
        const color = isError ? 'red' : 'green';
        $('#dlpom-update-status').html(`<p style="color: ${color};">${message}</p>`);
    }

    $(document).ready(function() {
        // Ensure loading spinner is hidden initially
        $('#dlpom-loading').hide();

        // Check if we're on the plugin page
        if (window.location.href.indexOf('page=dlpom') !== -1) {

            // Change the menu in the configuration
            $('#dlpom_menu_id').change(function() {
                var menuId = $(this).val();
                var data = {
                    'action': 'dlpom_get_menu_items',
                    'menu_id': menuId,
                    'nonce': dlpomData.nonce
                };

                $.post(dlpomData.ajax_url, data, function(response) {
                    if (response.success && response.data && Array.isArray(response.data.menu_items)) {
                        const menuItems = response.data.menu_items;
                        $('#dlpom_menu_item_id').empty().append('<option value="">Select a menu item</option>');
                        menuItems.forEach(function(item) {
                            $('#dlpom_menu_item_id').append(
                                $('<option>', { value: item.id, text: item.title })
                            );
                        });
                        $('#dlpom_menu_item_id').prop('disabled', false);
                    } else {
                        displayStatusMessage('Error: ' + (response.data || 'Unexpected response format'), true);
                    }
                }).fail(function() {
                    displayStatusMessage('Error: Could not retrieve menu items.', true);
                });
            });

            $('#dlpom-update-config').click(function(e) {
                e.preventDefault();
                $('#dlpom-loading').show();

                var data = {
                    action: 'dlpom_update_configuration',
                    menu_id: $('#dlpom_menu_id').val(),
                    menu_item_id: $('#dlpom_menu_item_id').val(),
                    number_of_posts: $('#dlpom_number_of_posts').val(),
                    nonce: dlpomData.nonce
                };

                $.post(dlpomData.ajax_url, data, function(response) {
                    $('#dlpom-loading').hide();
                    if (response.success) {
                        displayStatusMessage(response.data);
                    } else {
                        displayStatusMessage('Error: ' + (response.data || 'Failed to update configuration.'), true);
                    }
                }).fail(function() {
                    $('#dlpom-loading').hide();
                    displayStatusMessage('Error: Could not update configuration.', true);
                });
            });

            $('#dlpom-update-menu').click(function() {
                $('#dlpom-update-status').empty();
                $('#dlpom-loading').show();

                var data = {
                    'action': 'dlpom_update_menu',
                    'nonce': dlpomData.nonce
                };

                $.post(dlpomData.ajax_url, data, function(response) {
                    $('#dlpom-loading').hide();
                    if (response.success) {
                        displayStatusMessage(response.data);
                    } else {
                        displayStatusMessage('Error: ' + (response.data || 'Failed to update menu.'), true);
                    }
                }).fail(function() {
                    $('#dlpom-loading').hide();
                    displayStatusMessage('Error: Could not update menu.', true);
                });
            });
        }
    });

})(jQuery);

