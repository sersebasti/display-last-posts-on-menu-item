jQuery(document).ready(function($) {
    // Check if we're on the plugin page
    if (window.location.href.indexOf('page=dlpom') !== -1) {
        $('#dlpom_menu_id').change(function() {
            var menuId = $(this).val();
            var data = {
                'action': 'dlpom_get_menu_items',
                'menu_id': menuId
            };

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    $('#dlpom_menu_item_id').html(response.data);
                    $('#dlpom_menu_item_id').prop('disabled', false);
                } else {
                    alert('Error: ' + response.data);
                }
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

        $('#dlpom_menu_id').change(function() {
            var menuId = $(this).val();
            var data = {
                'action': 'dlpom_get_menu_items',
                'menu_id': menuId
            };

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    $('#dlpom_menu_item_id').html(response.data);
                    $('#dlpom_menu_item_id').prop('disabled', false);
                } else {
                    alert('Error: ' + response.data);
                }
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

        $('#dlpom-delete-items').click(function() {
            var data = {
                'action': 'dlpom_get_child_items'
            };
            
            

            $.post(ajaxurl, data, function(response) {

                //Create a function to update the progress bar
            
            

                var progress =  50;
                $('#dlpom-progress-bar').css('width', progress + '%').text(progress.toFixed(2) + '%');
                

                if (response.success) {
                    var childItems = JSON.parse(response.data);

                    childItems.forEach(function(itemId) {
                        $.post(ajaxurl, {
                            'action': 'dlpom_delete_single_item',
                            'item_id': itemId
                        }, function(response) {
                          
                            if (response.success) {

                                //alert("rimosso: " + itemId);              
                                $('#dlpom-menu-items').append('<p>Removed item ID: ' + itemId + '</p>');
                            } else {
                                alert("err rimosso: " + itemId);
                                //$('#dlpom-menu-items').append('<p>Error removing item ID: ' + itemId + ' - ' + response.data + '</p>');
                            }
                        });
                    });    

                    //alert(childItems[index]);

                    /*
                    $.post(ajaxurl, {
                            'action': 'dlpom_delete_single_item',
                            'item_id': itemId
                        }, function(response) {
                        if (response.success) {
                            itemsProcessed++;
                            var progress = (itemsProcessed / totalItems) * 100;
                            $('#dlpom-progress-bar').css('width', progress + '%').text(progress.toFixed(2) + '%');
                            $('#dlpom-menu-items').append('<p>Removed item ID: ' + itemId + '</p>');
                            removeItem(index + 1);
                        } else {
                            $('#dlpom-menu-items').append('<p>Error removing item ID: ' + itemId + ' - ' + response.data + '</p>');
                        }
                    });
                    

                    // Start removing items
                    removeItem(0);
                    /**/
                } else {
                    alert('Error: ' + response.data);
                }
            });
        });

    }    
});
