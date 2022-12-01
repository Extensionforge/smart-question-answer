(function ($) {
    $(document).ready(function () {
        $('textarea.autogrow, textarea#post_content').autogrow({
            onInitialize: true
        });

        $('.asqa-categories-list li .asqa-icon-arrow-down').on('click', function (e) {
            e.preventDefault();
            $(this).parent().next().slideToggle(200);
        });


        $('.asqa-radio-btn').on('click', function () {
            $(this).toggleClass('active');
        });

        $('.bootstrasqa-tagsinput > input').on('keyup', function (event) {
            $(this).css(width, 'auto');
        });

        $('.asqa-label-form-item').on('click', function (e) {
            e.preventDefault();
            $(this).toggleClass('active');
            var hidden = $(this).find('input[type="hidden"]');
            hidden.val(hidden.val() == '' ? $(this).data('label') : '');
        });

    });

    $('[asqa-loadmore]').on('click', function (e) {
        e.preventDefault();
        var self = this;
        var args = JSON.parse($(this).attr('asqa-loadmore'));
        args.action = 'asqa_ajax';

        if (typeof args.asqa_ajax_action === 'undefined')
            args.asqa_ajax_action = 'bp_loadmore';

        SmartQa.showLoading(this);
        SmartQa.ajax({
            data: args,
            success: function (data) {
                SmartQa.hideLoading(self);
                console.log(data.element);
                if (data.success) {
                    $(data.element).append(data.html);
                    $(self).attr('asqa-loadmore', JSON.stringify(data.args));
                    if (!data.args.current) {
                        $(self).hide();
                    }
                }
            }
        });
    });

})(jQuery);


