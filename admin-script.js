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

        $('#dlpom-update-menu').click(function() {
            $('#dlpom-update-status').empty();

            var data = {
                'action': 'dlpom_check'
            };

            $.post(ajaxurl, data, function(response) {
                
                if (response.success) {

                    const obj = JSON.parse(response.data);

                    console.log(obj.menu_name);
                    console.log(obj.menu_id);
                    console.log(obj.menu_item_name);
                    console.log(obj.menu_item_id);
                    console.log(obj.current_child_items);
                    console.log(obj.recent_posts);
                    
                    if(!compareArrays(obj.current_child_items,obj.recent_posts)){
                        
                        const totalItems = obj.current_child_items.length + obj.recent_posts.length;

                        processDeletion(obj.current_child_items, totalItems, function(itemsProcessed) {
                            // This code will run after processDeletion is complete
                            processPosts(obj.recent_posts, obj.menu_id, obj.menu_item_id, totalItems, itemsProcessed);
                        });

                    }
                    else{
                        $('#dlpom-update-status').append('<p>Menu Item ' + obj.menu_item_name + ' is updated with last (' + obj.recent_posts.length + ') posts</p>');
                    }



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
                    console.log(childItems);
                    /*
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
                    /**/     

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
                            $('#
                        
                            ').append('<p>Removed item ID: ' + itemId + '</p>');
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

function compareArrays(array1, array2) {
    // Check if the number of elements in both arrays is equal
    if (array1.length !== array2.length) {
        return false;
    }

    // Iterate over the arrays and compare each element
    for (let i = 0; i < array1.length; i++) {
        const item1 = array1[i];
        const item2 = array2[i];

        // Check if the titles match and the object is "post"
        if (item1.title !== item2.post_title || item1.object !== "post") {
            return false;
        }
    }

    return true;
}

})(jQuery); 
