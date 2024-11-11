(function($) {

async function addPostToMenu(postData) {
    return new Promise((resolve, reject) => {
        $.post(ajaxurl, postData, function(response) {
            if (response.success) {
                resolve(response);
            } else {
                reject(response);
            }
        });
    });
}

async function processPosts(recentPosts, menuId, parentMenuItemId, totalItems, itemsProcessed) {

    console.log(itemsProcessed);


    for (const item of recentPosts) {
        const postData = {
            action: 'dlpom_add_post_to_menu',
            post_id: item.ID,
            menu_id: menuId,
            parent_menu_item_id: parentMenuItemId
        };

        try {
            const response = await addPostToMenu(postData);
            console.log(response);

            $('#dlpom-update-status').append('<p>Added post with ID: ' + item.ID + '</p>');
            itemsProcessed++;
            const progress = (itemsProcessed / totalItems) * 100;
            $('#dlpom-progress-bar').css('width', progress + '%').text(progress.toFixed(2) + '%');
        } catch (error) {
            console.error("Error adding post with ID: " + item.ID, error);
            alert("Error adding post with ID: " + item.ID);
        }
    }
    console.log('All posts processed.');
}

// Function to make an asynchronous Ajax call to delete a single item
async function deleteSingleItem(itemID) {
    return new Promise((resolve, reject) => {
        $.post(ajaxurl, {
                action: 'dlpom_delete_single_item',
                item_id: itemID
        }, function(response) {
        if (response.success) {
            resolve(response);
        } else {
            reject(response);
        }
        });
    });
}

async function processDeletion(currentChildItems, totalItems, callback) {
    let itemsProcessed = 0;

    // Clear the update status element
    $('#dlpom-update-status').empty();

    for (const item of currentChildItems) {
        try {
            const response = await deleteSingleItem(item.ID);
            console.log(response);

            $('#dlpom-update-status').append('<p>Removed item ID: ' + item.ID + '</p>');
            itemsProcessed++;
            const progress = (itemsProcessed / totalItems) * 100;
            $('#dlpom-progress-bar').css('width', progress + '%').text(progress.toFixed(2) + '%');

        } catch (error) {
            console.error("Error removing item with ID: " + item.ID, error);
            alert("Error removing item with ID: " + item.ID + ". Error: " + JSON.stringify(error));
        }
    }

    console.log('All items processed.');
    callback(itemsProcessed);
}

jQuery(document).ready(function($) {
    // Check if we're on the plugin page
    if (window.location.href.indexOf('page=dlpom') !== -1) {

        // Changing the menu in the configuration
        $('#dlpom_menu_id').change(function() {
            var menuId = $(this).val();
            var data = {
                'action': 'dlpom_get_menu_items',
                'menu_id': menuId
            };

            $.post(ajaxurl, data, function(response) {
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
                        // Handle unexpected response format
                        alert('Unexpected response format. Expected an object with a menu_items array.');
                        console.error('Expected an object with a menu_items array, but got:', response.data);
                    }
                } else {
                    alert('Error: ' + response.data);
                }
            });
        });

        // Handle configuration update via AJAX
        $('#dlpom-update-config').click(function(e) {
            e.preventDefault();

            var data = {
                action: 'dlpom_update_configuration',
                menu_id: $('#dlpom_menu_id').val(),
                menu_item_id: $('#dlpom_menu_item_id').val(),
                number_of_posts: $('#dlpom_number_of_posts').val(),
            };

            $.post(ajaxurl, data, function(response) {

                console.log(response);
                if (response.success) {
                    $('#dlpom-config-status').html('<p>' + response.data + '</p>');
                } else {
                    $('#dlpom-config-status').html('<p>Error: ' + response.data + '</p>');
                }
            });
        });

        $('#dlpom-update-menu').click(function() {
            $('#dlpom-update-status').empty();

            var data = {
                'action': 'dlpom_check_menu_items'
            };

            console.log(data);
        
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    console.log(response)
                    const obj = response.data;

                    console.log("Menu Data:", obj);

                    if (!compareArrays(obj.current_child_items, obj.recent_posts)) {
                        const totalItems = obj.current_child_items.length + obj.recent_posts.length;

                        processDeletion(obj.current_child_items, totalItems, function(itemsProcessed) {
                            processPosts(obj.recent_posts, obj.menu_id, obj.menu_item_id, totalItems, itemsProcessed);
                        });
                    } else {
                        $('#dlpom-update-status').append(
                            `<p>Menu Item ${obj.menu_item_name} is updated with the latest ${obj.recent_posts.length} posts</p>`
                        );
                    }
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
                var progress = 50;
                $('#dlpom-progress-bar').css('width', progress + '%').text(progress.toFixed(2) + '%');

                if (response.success) {
                    var childItems = response.data;
                    console.log("Child items:", childItems);
                } else {
                    alert('Error: ' + response.data);
                }
            });
        });
    }
});

// Helper function to compare arrays of menu items
function compareArrays(array1, array2) {
    if (array1.length !== array2.length) return false;

    return array1.every((item1, index) => {
        const item2 = array2[index];
        return item1.title === item2.post_title && item1.object === "post";
    });
}




})(jQuery); 
