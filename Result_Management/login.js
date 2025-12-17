document.addEventListener('DOMContentLoaded', () => {
    const roleSelect = document.getElementById('role');
    const nameGroup = document.getElementById('name-group');
    const nameInput = document.getElementById('name');
    const identifierLabel = document.getElementById('identifier-label');
    const identifierInput = document.getElementById('identifier');
    const passwordGroup = document.getElementById('password-group');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    // Toggle Password Visibility
    togglePassword.addEventListener('click', () => {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        togglePassword.classList.toggle('fa-eye-slash');
    });

    // Handle Form Switching
    const updateUI = () => {
        const role = roleSelect.value;

        if (role === 'admin') {
            // Admin View: Email + Password
            nameGroup.style.display = 'none';
            nameInput.required = false;

            identifierLabel.innerText = 'Admin Email';
            identifierInput.type = 'email';
            identifierInput.placeholder = 'admin@library.com';

            passwordGroup.style.display = 'block';
            passwordInput.required = true;
        } else {
            // Student View: Name + Roll Number
            nameGroup.style.display = 'block';
            nameInput.required = true;

            identifierLabel.innerText = 'Roll Number';
            identifierInput.type = 'text';
            identifierInput.placeholder = 'e.g. 2023-CS-01';

            passwordGroup.style.display = 'none';
            passwordInput.required = false;
        }
    };

    roleSelect.addEventListener('change', updateUI);
    updateUI(); // Run on load
});