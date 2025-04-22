document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('credentialsForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword !== confirmPassword) {
            alert('New password and confirm password do not match!');
            return;
        }
        
        // Submit the form if validation passes
        this.submit();
    });
});