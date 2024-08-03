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
