(function ($) {
    'use strict';

    $(document).ready(function () {
        $('#visitorping-test-btn').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var $result = $('#visitorping-test-result');

            $btn.prop('disabled', true).text('Checking connection...');
            $result.hide().removeClass('notice-success notice-error');

            $.ajax({
                url: visitorpingAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'visitorping_test_ping',
                    nonce: visitorpingAdmin.nonce
                },
                success: function (response) {
                    $btn.prop('disabled', false).text('Verify Script Connection');
                    $result.show();

                    if (response.success) {
                        $result
                            .addClass('notice-success')
                            .html('<p><strong>✓ Connected:</strong> ' + response.data.message + '</p>');
                    } else {
                        $result
                            .addClass('notice-error')
                            .html('<p><strong>✕ Error:</strong> ' + (response.data.message || 'Verification failed.') + '</p>');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Verify Script Connection');
                    $result
                        .show()
                        .addClass('notice-error')
                        .html('<p><strong>✕ Error:</strong> Failed to communicate with server. Please try again.</p>');
                }
            });
        });
    });
})(jQuery);
