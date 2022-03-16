(function($) {
    $.entwine('ss', function($) {
        $('#Form_ItemEditForm a.no-ajax, #Form_ItemEditForm input.no-ajax').entwine({
            onclick: function(event) {
                var anchor = $(this);
                if(anchor.attr('target') === '_blank') {
                    window.open(anchor.attr('href'));
                } else {
                    window.location = anchor.attr('href');
                }
                return false;
            }
        });
    });
})(jQuery);