jQuery(document).ready(function() {
    var buttons = jQuery('a.fav');

    jQuery.each(buttons, function(index, button) {
        jQuery(button).click(function(event) {
            event.preventDefault();
            jQuery.ajax({
                type: 'POST',
                url: jQuery(button).attr('href'),
                data: 'article_guid=' + jQuery(button).attr('id'),
                dataType: 'json',
                success: function(json) {
                    if (jQuery(button).hasClass('like')) {
                        jQuery(button).removeClass('like');
                        jQuery(button).addClass('dislike');
                    } else if (jQuery(button).hasClass('dislike')) {
                        jQuery(button).removeClass('dislike');
                        jQuery(button).addClass('like');
                    }
                    jQuery(button).children('span').html(json.reverse_caption);
                    jQuery(button).attr('href', json.reverse_action);
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    alert('Article favouriting failed: ' + textStatus);
                }
            });
        });
    });
});