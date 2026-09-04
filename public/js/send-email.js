$(document).ready(function () {
    $('#send_message').on('click', function (event) {
        event.preventDefault();

        const name = $('#name').val().trim();
        const email = $('#email').val().trim();
        const phone = $('#phone').val().trim();
        const message = $('#message').val().trim();

        if (!name || !email || !message) {
            $('#formStatus').html('<span style="color:red; font-weight:bold;">Please fill all required fields.</span>');
            return false;
        }

        $.ajax({
            url: $('#contactForm').data('action-url'),
            type: 'POST',
            dataType: 'json',
            data: {
                _token: $('#contactForm input[name="_token"]').val(),
                name: name,
                email: email,
                phone: phone,
                message: message
            },
            success: function (response) {
                $('#formStatus').html('<span style="color:green; font-weight:bold;">' + response.message + '</span>');
                $('#contactForm').trigger('reset');

                setTimeout(function () {
                    $('#formStatus').html('');
                }, 5000);
            },
            error: function (xhr) {
                const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'An error occurred. Please try again.';

                $('#formStatus').html('<span style="color:red; font-weight:bold;">' + errorMessage + '</span>');
            }
        });

        return false;
    });
});
