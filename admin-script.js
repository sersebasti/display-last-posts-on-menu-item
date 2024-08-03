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
        var menuId = $('#dlpom_menu_id').val();
        var menuItemId = $('#dlpom_menu_item_id').val();
        var numPosts = $('#dlpom_number_of_posts').val();

        if (menuItemId === '') {
            alert('Please select a menu item.');
            return;
        }

        var data = {
            'action': 'dlpom_update_json',
            'menu_id': menuId,
            'menu_item_id': menuItemId,
            'number_of_posts': numPosts
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                var jsonData = response.data.data;
                var message = response.data.message + '\n\n' +
                              'Menu Name: ' + jsonData.menu_name + '\n' +
                              'Menu Item Name: ' + jsonData.menu_item_name + '\n' +
                              'Post Count: ' + jsonData.post_count;
                alert(message);
                location.reload(); // Ricarica la pagina dopo che l'alert viene chiuso
            } else {
                alert('Error: ' + response.data);
            }
        });
    });

    $('#dlpom-update-menu').click(function() {
        var data = {
            'action': 'dlpom_update_menu'
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                $('#dlpom-menu-items').html('<h3>Menu Items:</h3>' + response.data);
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
});


