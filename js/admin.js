var br_saved_timeout;
var br_savin_ajax = false;
(function ($){
    $(document).ready( function () {
        $('.force_sell_submit_form').submit( function(event) {
            event.preventDefault();
            $('.cat_suggest_position').each(function( i, o ) {
                $(o).val(i);
            });
            $('.product_suggest_position').each(function( i, o ) {
                $(o).val(i);
            });
            $('.br_add_suggestion_to_specific_product table tbody tr').each(function( i, o ) {
                $(o).find('input').each(function ( ii, oo ) {
                    var name = $(oo).data('name');
                    if( name != undefined ) {
                        name = name.replace('%position%', i);
                        $(oo).attr('name', name);
                    }
                });
            });
            event.preventDefault();
            if( !br_savin_ajax ) {
                br_savin_ajax = true;
                var form_data = $(this).serialize();
                form_data = 'action=br_force_sell_settings_save&'+form_data;
                var url = ajaxurl;
                clearTimeout(br_saved_timeout);
                destroy_br_saved();
                $('body').append('<span class="br_saved br_saving"><i class="fa fa-refresh fa-spin"></i></span>');
                $.post(url, form_data, function (data) {
                    if($('.br_saved').length > 0) {
                        $('.br_saved').removeClass('br_saving').find('.fa').removeClass('fa-spin').removeClass('fa-refresh').addClass('fa-check');
                    } else {
                        $('body').append('<span class="br_saved"><i class="fa fa-check"></i></span>');
                    }
                    br_saved_timeout = setTimeout( function(){destroy_br_saved();}, 5000 );
                    br_savin_ajax = false;
                }, 'json').fail(function() {
                    if($('.br_saved').length > 0) {
                        $('.br_saved').removeClass('br_saving').addClass('br_not_saved').find('.fa').removeClass('fa-spin').removeClass('fa-refresh').addClass('fa-times');
                    } else {
                        $('body').append('<span class="br_saved br_not_saved"><i class="fa fa-times"></i></span>');
                    }
                    br_saved_timeout = setTimeout( function(){destroy_br_saved();}, 5000 );
                    $('.br_save_error').html(data.responseText);
                    br_savin_ajax = false;
                });
            }
        });
        function destroy_br_saved() {
            $('.br_saved').addClass('br_saved_remove');
            var $get = $('.br_saved');
            setTimeout( function(){$get.remove();}, 200 );
        }
        $('.br_settings .nav-tab').click(function(event) {
            event.preventDefault();
            $('.nav-tab-active').removeClass('nav-tab-active');
            $('.nav-block-active').removeClass('nav-block-active');
            $(this).addClass('nav-tab-active');
            $('.'+$(this).data('block')+'-block').addClass('nav-block-active');
        });
        $(window).on('keydown', function(event) {
            if (event.ctrlKey || event.metaKey) {
                switch (String.fromCharCode(event.which).toLowerCase()) {
                case 's':
                    event.preventDefault();
                    $('.force_sell_submit_form').submit();
                    break;
                }
            }
        });
        $(document).on('click', '.add_category_linked', function(event) {
            var cat_text = $('.category_suggest').find(':selected').text();
            var cat_val = $('.category_suggest').val();
            var cat_val_next = cat_val;
            if( $('.cat_exist_'+cat_val).length <= 1 ) {
                if($('.cat_exist_'+cat_val).length == 1) {
                    cat_val_next = cat_val+'_2';
                }
                var html = '<tr class="cat_exist_id cat_exist_'+cat_val+'"><td class="move_suggestions"><i class="fa fa-th"></i></td><td><input class="cat_suggest_position" type="hidden" value="" name="br-force_sell-options[category_linked]['+cat_val_next+'][position]"><input type="hidden" value="'+cat_val_next+'" name="br-force_sell-options[category_linked]['+cat_val_next+'][category]">'+cat_text+'</td><td>';
                html += category_product_search.replace('%cat_id%', cat_val_next);
                html += '</td>';
                html += '<td><input type="checkbox" name="br-force_sell-options[category_linked]['+cat_val_next+'][linked]"></td>';
                html += '<td class="cat_suggest_remove"><button type="button" class="cat_linked_remove_button">Remove</button></td></tr>';
                $('.br_add_suggestion_to_specific_category table tbody').append($(html));
            }
            reload_sortable();
        });
        $(document).on('click', '.add_product_linked', function(event) {
            var html = '<tr class="cat_exist_id"><td class="move_suggestions"><i class="fa fa-th"></i></td><td><input class="product_suggest_position" type="hidden" value="" data-name="br-force_sell-options[product_linked][%position%][position]" name="br-force_sell-options[product_linked][%position%][position]">';
            html += product_product_search;
            html += '</td><td>';
            html += product_product_search_2;
            html += '</td>';
            html += '<td><input type="checkbox" name="br-force_sell-options[product_linked][%position%][linked]" data-name="br-force_sell-options[product_linked][%position%][linked]" value="1"></td>';
            html += '<td class="cat_suggest_remove"><button type="button" class="cat_linked_remove_button">Remove</button></td></tr>';
            $('.br_add_suggestion_to_specific_product table tbody').append($(html));
            reload_sortable();
        });
        $(document).on('click', '.cat_linked_remove_button', function(event) {
            $(this).parents('.cat_exist_id').remove();
        });
    });
})(jQuery);