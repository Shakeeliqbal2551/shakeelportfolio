$(document).ready(function () {
    $('#send_message').on('click', function (e) {
        e.preventDefault();
        
        const name = $('#name').val().trim();
        const email = $('#email').val().trim();
        const phone = $('#phone').val().trim();
        const message = $('#message').val().trim();
        
        // Validate - only name, email, and message are required
        if (!name || !email || !message) {
            $('#formStatus').html('<span style="color:red; font-weight: bold;">❌ Please fill all required fields</span>');
            return false;
        }
        
        $.ajax({
            url: 'contact/send-email.php',
            type: 'POST',
            dataType: 'json',
            data: {
                name: name,
                email: email,
                phone: phone,
                message: message
            },
            success: function (response) {
                if (response.status === 'success') {
                    $('#formStatus').html('<span style="color:green; font-weight: bold;">✅ ' + response.message + '</span>');
                    $('#name').val('');
                    $('#email').val('');
                    $('#phone').val('');
                    $('#message').val('');
                    setTimeout(function() {
                        $('#formStatus').html('');
                    }, 5000);
                } else {
                    $('#formStatus').html('<span style="color:red; font-weight: bold;">❌ ' + response.message + '</span>');
                }
            },
            error: function (xhr, status, error) {
                $('#formStatus').html('<span style="color:red; font-weight: bold;">❌ An error occurred. Please try again.</span>');
            }
        });
    });
});