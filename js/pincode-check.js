jQuery(document).ready(function ($) {
    $('#check-pincode').on('click', function () {
        var pincode = $('#pincode').val().trim();
        var resultDiv = $('#pincode-result');
        resultDiv.removeClass('error success').hide();

        // Validate input
        if (pincode === '') {
            resultDiv.html('Please enter a pincode.').addClass('error').fadeIn();
            return;
        }

        if (!/^\d{6}$/.test(pincode)) {
            resultDiv.html('Invalid pincode. Must be 6 digits.').addClass('error').fadeIn();
            return;
        }

        // AJAX call
        $.ajax({
            type: 'POST',
            url: edpc_ajax.ajax_url, 
            data: {
                action: 'check_edpc_pincode',
                nonce: edpc_ajax.nonce,
                pincode: pincode
            },
            success: function (response) {
                if (response.success) {
                    // Determine response type based on returned keys
                    if (response.data.estimated_delivery_days !== undefined) {
                        resultDiv.html(
                            'Est. Delivery : <strong>' + response.data.estimated_delivery_days + '</strong> days ( <strong>' + response.data.estimated_delivery_date + '</strong>)'
                        ).addClass('success').fadeIn();
                    } else if (response.data.city) {
                        resultDiv.html(
                            'Delivered to - <strong>' + response.data.city + '</strong>'
                        ).addClass('success').fadeIn();
                    } else {
                        resultDiv.html('Service available.').addClass('success').fadeIn();
                    }
                } else {
                    resultDiv.html(response.data.message || 'Not available for this pincode.').addClass('error').fadeIn();
                }
            },
            error: function () {
                resultDiv.html('Network error. Please try again.').addClass('error').fadeIn();
            }
        });
    });
});
