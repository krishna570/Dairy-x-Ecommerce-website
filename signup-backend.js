// Signup Backend Integration Script

const API_BASE = 'api';

function handleSignup(event) {
    event.preventDefault();
    
    const fullname = document.getElementById('fullname').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // Reset error messages
    document.getElementById('passwordError').style.display = 'none';
    document.getElementById('confirmError').style.display = 'none';

    // Validate password length
    if (password.length < 6) {
        document.getElementById('passwordError').style.display = 'block';
        return;
    }

    // Validate password match
    if (password !== confirmPassword) {
        document.getElementById('confirmError').style.display = 'block';
        return;
    }

    // Send registration request to backend
    registerUser(fullname, email, phone, password);
}

async function registerUser(fullname, email, phone, password) {
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                fullname: fullname,
                email: email,
                phone: phone,
                password: password
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Registration successful! Welcome to Dairy-X, ' + fullname);
            // Redirect to home page
            window.location.href = 'index.php';
        } else {
            alert('Registration failed: ' + data.message);
        }
    } catch (error) {
        console.error('Registration error:', error);
        alert('Registration failed. Please try again.');
    }
}
