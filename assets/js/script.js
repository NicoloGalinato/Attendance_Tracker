$(document).ready(function() {
    // Loading animation for forms
    $('form').on('submit', function() {
        $('#loadingModal').modal('show');
    });
    
    // Close modal when navigating away
    $(window).on('beforeunload', function() {
        $('#loadingModal').modal('hide');
    });
    
    // Password match validation for registration
    $('#registerForm').on('submit', function(e) {
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match');
            $('#loadingModal').modal('hide');
        }
    });
});