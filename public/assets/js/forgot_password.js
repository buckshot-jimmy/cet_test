$(document).ready(function () {
    $("#trimite_email").validate({
        rules: {
            reset_password_request_form_email_forgot_password: {
                email: true,
                required: true
            }
        }
    });

    $("#reset_password_form").validate({
        rules: {
            "change_password_form[new_password]": {
                required: true,
                minlength: 6,
                equalTo: "#change_password_form_confirm_new_password",
                strength: true
            },
            "change_password_form[confirm_new_password]": {
                required: true,
                minlength: 6,
                equalTo: "#change_password_form_new_password",
                strength: true
            }
        }
    });
});