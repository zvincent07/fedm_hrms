<style>
    body {
        background: #f5f5f5;
        overflow: hidden; /* Remove scrollbar */
    }
    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .login-card {
        width: 100%;
        max-width: 1100px;
        min-height: 600px;
        border: none;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        background: #fff;
        display: flex;
    }
    .login-left {
        background: url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=800&q=80') center center/cover no-repeat;
        position: relative;
        flex: 1 1 0%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .login-left::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(153, 0, 0, 0.8);
        z-index: 1;
    }
    .login-left-content {
        position: relative;
        z-index: 2;
        color: #fff;
        text-align: center;
    }
    .hrm-icon {
        margin-bottom: 30px;
    }
    .hrm-icon svg {
        width: 140px;
        height: 140px;
        display: block;
        margin: 0 auto;
    }
    .login-title {
        font-size: 1.5rem;
        font-weight: bold;
        letter-spacing: 1px;
    }
    .login-form-section {
        flex: 1 1 0%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border-radius: 0 20px 20px 0;
    }
    .login-form {
        width: 100%;
        max-width: 370px;
    }
    .form-label {
        font-weight: 500;
    }
    .form-control {
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    .btn-login {
        background: #b30000;
        color: #fff;
        border-radius: 10px;
        font-size: 1.2rem;
        font-weight: 500;
        width: 100%;
        padding: 10px 0;
        margin-top: 20px;
        transition: background 0.2s;
    }
    .btn-login:hover {
        background: #990000;
    }
    .forgot-link {
        display: block;
        text-align: right;
        font-size: 0.95rem;
        margin-top: 5px;
        color: #444;
        text-decoration: none;
    }
    .forgot-link:hover {
        text-decoration: underline;
    }
    @media (max-width: 991.98px) {
        .login-card {
            flex-direction: column;
            min-height: 0;
        }
        .login-left, .login-form-section {
            border-radius: 0;
            min-height: 300px;
        }
    }
    @media (max-width: 767.98px) {
        .login-card {
            max-width: 100vw;
            min-height: 0;
        }
        .login-left {
            display: none;
        }
        .login-form-section {
            border-radius: 20px;
        }
    }
</style>

<?php
session_start();
include __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);

    $query = "SELECT user_account.user_id, user_account.password, user_account.role_id, role.name AS role_name 
              FROM user_account 
              JOIN role ON user_account.role_id = role.role_id 
              WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role_name'];

            $roleQuery = "SELECT role_id, name FROM role";
            $roleResult = mysqli_query($conn, $roleQuery);
            $roles = [];
            while ($role = mysqli_fetch_assoc($roleResult)) {
                $roles[$role['name']] = $role['role_id'];
            }

            error_log("User Role ID: " . $user['role_id']);
            error_log("Admin Role ID: " . $roles['Admin']);
            error_log("User Role Name: " . $user['role_name']);

            if ((int)$user['role_id'] === (int)$roles['Admin']) {
                header('Location: views/admin.php');
                exit();
            } elseif ((int)$user['role_id'] === (int)$roles['Employee']) {
                header('Location: views/employee.php');
                exit();
            }
        }
    }
    $error = "Invalid credentials.";
}
?>

<div class="container-fluid login-container">
    <div class="login-card">
        <div class="login-left">
            <div class="login-left-content">
                <div class="hrm-icon">
                    <!-- HRM Icon SVG -->
                    <svg viewBox="0 0 100 100" fill="white" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="38" r="16"/>
                        <circle cx="25" cy="60" r="10"/>
                        <circle cx="75" cy="60" r="10"/>
                        <rect x="30" y="60" width="40" height="25" rx="12"/>
                        <rect x="10" y="70" width="25" height="15" rx="8"/>
                        <rect x="65" y="70" width="25" height="15" rx="8"/>
                        <polygon points="50,54 58,80 42,80" fill="#fff" stroke="#b30000" stroke-width="2"/>
                    </svg>
                </div>
                <div class="login-title">
                    HUMAN RESOURCE<br>MANAGEMENT
                </div>
            </div>
        </div>
        <div class="login-form-section">
            <form class="login-form" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your e-mail" required>
                </div>
                <div class="mb-2 position-relative">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1" style="border-radius: 0 8px 8px 0;">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off" viewBox="0 0 24 24">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-5 0-9.27-3.11-11-7.5a11.05 11.05 0 0 1 5.17-5.61"/>
                                <path d="M1 1l22 22"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <a href="#" class="forgot-link">Forgot Password?</a>
                <button type="submit" class="btn btn-login">Login</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.innerHTML = `
                <path d="M1 12C1 12 5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/>
                <circle cx="12" cy="12" r="3"/>
            `;
        } else {
            passwordInput.type = 'password';
            eyeIcon.innerHTML = `
                <path d="M1 12C1 12 5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/>
                <circle cx="12" cy="12" r="3"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
            `;
        }
    });
</script>
