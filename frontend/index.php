<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NWR Contract Registry - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .auth-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .auth-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .auth-tabs {
            display: flex;
            margin-bottom: 2rem;
            background: #f5f5f5;
            border-radius: 6px;
            padding: 4px;
        }
        
        .auth-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            background: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .auth-tab.active {
            background: white;
            color: #007bff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input {
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .auth-btn {
            padding: 14px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 1rem;
        }
        
        .auth-btn:hover {
            background: #0056b3;
        }
        
        .auth-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .error-message, .success-message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .error-message {
            color: #721c24;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin: 1rem 0;
            color: #666;
        }
        
        .signup-form {
            display: none;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 0.9rem;
        }

        /* Hide the form initially if redirecting */
        .redirecting {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>NWR Contract Registry</h1>
            <p>Secure contract management system</p>
        </div>
        
        <div class="auth-tabs">
            <button class="auth-tab active" onclick="switchTab('login')">Login</button>
            <button class="auth-tab" onclick="switchTab('signup')">Sign Up</button>
        </div>
        
        <div id="message-area"></div>
        
        <!-- Login Form -->
        <form id="login-form" class="auth-form">
            <div class="form-group">
                <label for="login-email">Email Address</label>
                <input type="email" id="login-email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="login-password">Password</label>
                <input type="password" id="login-password" name="password" required>
            </div>
            
            <button type="submit" class="auth-btn" id="login-btn">Login</button>
        </form>
        
        <!-- Signup Form -->
        <form id="signup-form" class="auth-form signup-form">
            <div class="form-group">
                <label for="signup-username">Username</label>
                <input type="text" id="signup-username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="signup-email">Email Address</label>
                <input type="email" id="signup-email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="signup-password">Password</label>
                <input type="password" id="signup-password" name="password" required minlength="6">
                <small style="color: #666; font-size: 0.8rem; margin-top: 4px;">Minimum 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="signup-password-confirm">Confirm Password</label>
                <input type="password" id="signup-password-confirm" name="password_confirm" required minlength="6">
            </div>
            
            <button type="submit" class="auth-btn" id="signup-btn">Create Account</button>
        </form>
        
        <div class="loading" id="loading">
            <p>Please wait...</p>
        </div>
        
        <div class="auth-footer">
            <p>&copy; 2024 NWR Contract Registry. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Check if user is already logged in
        checkLoginStatus();
        
        async function checkLoginStatus() {
            try {
                const response = await fetch('/nwrcontractregistry/backend/auth_handler.php?action=check');
                const result = await response.json();
                
                if (result.loggedIn) {
                    redirectToDashboard();
                }
            } catch (error) {
                console.log('Not logged in or error checking status');
            }
        }
        
        function redirectToDashboard() {
            document.querySelector('.auth-container').classList.add('redirecting');
            showMessage('Already logged in. Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = 'pages/dashboard.html';
            }, 1000);
        }
        
        function switchTab(tab) {
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const loginTab = document.querySelector('[onclick="switchTab(\'login\')"]');
            const signupTab = document.querySelector('[onclick="switchTab(\'signup\')"]');
            
            // Clear any existing messages
            clearMessage();
            
            if (tab === 'login') {
                loginForm.style.display = 'flex';
                signupForm.style.display = 'none';
                loginTab.classList.add('active');
                signupTab.classList.remove('active');
            } else {
                loginForm.style.display = 'none';
                signupForm.style.display = 'flex';
                signupTab.classList.add('active');
                loginTab.classList.remove('active');
            }
        }
        
        function showMessage(message, type) {
            const messageArea = document.getElementById('message-area');
            const className = type === 'error' ? 'error-message' : 'success-message';
            
            messageArea.innerHTML = `<div class="${className}">${message}</div>`;
            messageArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function clearMessage() {
            document.getElementById('message-area').innerHTML = '';
        }
        
        function setLoading(isLoading) {
            const loading = document.getElementById('loading');
            const loginBtn = document.getElementById('login-btn');
            const signupBtn = document.getElementById('signup-btn');
            const forms = document.querySelectorAll('.auth-form');
            
            if (isLoading) {
                loading.style.display = 'block';
                loginBtn.disabled = true;
                signupBtn.disabled = true;
                forms.forEach(form => form.style.opacity = '0.6');
            } else {
                loading.style.display = 'none';
                loginBtn.disabled = false;
                signupBtn.disabled = false;
                forms.forEach(form => form.style.opacity = '1');
            }
        }
        
        // Login form handler
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            clearMessage();
            setLoading(true);
            
            try {
                const response = await fetch('/nwrcontractregistry/backend/auth_handler.php?action=login', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'pages/dashboard.html';
                    }, 1000);
                } else {
                    showMessage(result.error || 'Login failed', 'error');
                }
            } catch (error) {
                console.error('Login error:', error);
                showMessage('Network error. Please try again.', 'error');
            }
            
            setLoading(false);
        });
        
        // Signup form handler
        document.getElementById('signup-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = document.getElementById('signup-password').value;
            const passwordConfirm = document.getElementById('signup-password-confirm').value;
            
            if (password !== passwordConfirm) {
                showMessage('Passwords do not match', 'error');
                return;
            }
            
            const formData = new FormData(this);
            formData.delete('password_confirm'); // Remove confirmation field
            
            clearMessage();
            setLoading(true);
            
            try {
                const response = await fetch('/nwrcontractregistry/backend/auth_handler.php?action=register', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Account created successfully! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'pages/dashboard.html';
                    }, 1000);
                } else {
                    showMessage(result.error || 'Registration failed', 'error');
                }
            } catch (error) {
                console.error('Signup error:', error);
                showMessage('Network error. Please try again.', 'error');
            }
            
            setLoading(false);
        });
    </script>
</body>
</html>