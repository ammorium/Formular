(function ($) {
    $(function () {
        $('.formular').on('submit', function (e) {
            e.preventDefault();
            self = $(this);
            if (self.data('sending')) return false;
            $.ajax(self.attr('action'), {
                type: (self.attr('method')) ? self.attr('method') : 'get',
                data: self.serialize(),
                beforeSend: function () {
                    self.data('sending', true);
                    self.find('.ajax-loader')
                        .animate({'opacity': 1}, 'fast');
                    self.removeClass('invalid');
                    var output = self.find('.wpcf7-response-output');
                    output
                        .slideUp('fast')
                        .addClass('wpcf7-display-none')
                        .removeClass('wpcf7-validation-errors')
                        .html('');
                },
                complete: function (data) {
                    var response = data.responseJSON;
                    var output = self.find('.wpcf7-response-output');
                    output
                        .html(response.data)
                        .slideDown('fast')
                        .removeClass('wpcf7-display-none');
                    if (response.success) {
                        output.addClass('wpcf7-mail-sent-ok');
                        self.trigger('reset');
                        window.setTimeout(function() {
                            output
                                .slideUp('fast')
                                .addClass('wpcf7-display-none')
                                .removeClass('wpcf7-validation-errors')
                                .html('');
                        }, 5000);
                    } else {
                        self.addClass('invalid');
                        output.addClass('wpcf7-validation-errors');
                    }
                    self.find('.ajax-loader')
                        .animate({'opacity': 0}, 'fast');
                    self.data('sending', false);
                }
            });
        });
    });
})(jQuery);