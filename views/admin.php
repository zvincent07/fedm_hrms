<?php
include '../config/db.php';
session_start();

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit();
}

$roles = [];
$sql = "SELECT role_id, name FROM role ORDER BY name ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $roles[] = $row;
    }
}

$departments = [];
$sql = "SELECT department_id, name FROM department ORDER BY name ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $departments[] = $row;
    }
}

$job_roles = [];
$sql = "SELECT job_role_id, title FROM job_role ORDER BY title ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $job_roles[] = $row;
    }
}

// Assume current user ID is available (replace with your session logic)
$current_user_id = $_SESSION['user_id'] ?? 1; // Example fallback to 1

// Handle form submission for Create Account
$create_account_msg = '';
$create_account_success = false;
$show_create_account_form = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account'])) {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = $_POST['role'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $job_role_id = $_POST['job_role_id'] ?? '';

    if ($fullName && $email && $password && $role_id) {
        $email_check_query = "SELECT user_id FROM user_account WHERE email = '$email'";
        $email_check_result = mysqli_query($conn, $email_check_query);
        if (mysqli_num_rows($email_check_result) > 0) {
            $create_account_msg = '<div class="alert alert-danger">Email already exists.</div>';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO user_account (email, password, full_name, role_id, department_id, job_role_id, created_at, updated_at) VALUES ('$email', '$hashed_password', '$fullName', '$role_id', '$department_id', '$job_role_id', NOW(), NOW())";
            if (mysqli_query($conn, $insert_query)) {
                $create_account_msg = '<div class="alert alert-success">Account created successfully!</div>';
                $create_account_success = true;
            } else {
                $create_account_msg = '<div class="alert alert-danger">Error creating account. Please try again.</div>';
            }
        }
    } else {
        $create_account_msg = '<div class="alert alert-danger">All fields are required.</div>';
    }
    $show_create_account_form = true;
}

// Handle form submission for Change Password
$change_password_msg = '';
$change_password_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($current_password && $new_password && $confirm_password) {
        if ($new_password !== $confirm_password) {
            $change_password_msg = '<div class="alert alert-danger">New passwords do not match.</div>';
        } else {
            $password_check_query = "SELECT password FROM user_account WHERE user_id = '$current_user_id'";
            $password_check_result = mysqli_query($conn, $password_check_query);
            if ($password_check_result && mysqli_num_rows($password_check_result) > 0) {
                $row = mysqli_fetch_assoc($password_check_result);
                if (password_verify($current_password, $row['password'])) {
                    $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE user_account SET password = '$new_hashed', updated_at = NOW() WHERE user_id = '$current_user_id'";
                    if (mysqli_query($conn, $update_query)) {
                        $change_password_msg = '<div class="alert alert-success">Password changed successfully!</div>';
                        $change_password_success = true;
                    } else {
                        $change_password_msg = '<div class="alert alert-danger">Error updating password. Please try again.</div>';
                    }
                } else {
                    $change_password_msg = '<div class="alert alert-danger">Current password is incorrect.</div>';
                }
            } else {
                $change_password_msg = '<div class="alert alert-danger">User not found.</div>';
            }
        }
    } else {
        $change_password_msg = '<div class="alert alert-danger">All fields are required.</div>';
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id_to_delete = $_POST['user_id'];
    $delete_query = "DELETE FROM user_account WHERE user_id = '$user_id_to_delete'";
    if (mysqli_query($conn, $delete_query)) {
        $user_action_msg = '<div id="userActionMsg" class="alert alert-success">User deleted successfully.</div>';
    } else {
        $user_action_msg = '<div id="userActionMsg" class="alert alert-danger">Failed to delete user.</div>';
    }
    echo "<script>setTimeout(() => { document.getElementById('userActionMsg').style.display = 'none'; }, 3000);</script>";
}

// Handle user edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $edit_user_id = $_POST['user_id'];
    $edit_full_name = trim($_POST['fullName']);
    $edit_email = trim($_POST['email']);
    $edit_role_id = $_POST['role'];
    $edit_department_id = $_POST['department_id'] ?: null;
    $edit_job_role_id = $_POST['job_role_id'] ?: null;
    $edit_new_password = $_POST['new_password'] ?? '';

    $update_query = "UPDATE user_account SET 
                    full_name = '$edit_full_name', 
                    email = '$edit_email', 
                    role_id = '$edit_role_id',
                    department_id = " . ($edit_department_id ? "'$edit_department_id'" : "NULL") . ",
                    job_role_id = " . ($edit_job_role_id ? "'$edit_job_role_id'" : "NULL") . ",
                    updated_at = NOW()";
    
    if ($edit_new_password) {
        $new_hashed_password = password_hash($edit_new_password, PASSWORD_DEFAULT);
        $update_query .= ", password = '$new_hashed_password'";
    }
    $update_query .= " WHERE user_id = '$edit_user_id'";

    if (mysqli_query($conn, $update_query)) {
        $user_action_msg = '<div id="userActionMsg" class="alert alert-success">User updated successfully.</div>';
    } else {
        $user_action_msg = '<div id="userActionMsg" class="alert alert-danger">Failed to update user.</div>';
    }
    echo "<script>setTimeout(() => { document.getElementById('userActionMsg').style.display = 'none'; }, 3000);</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        .sidebar {
            width: 260px;
            background: #fff;
            border-radius: 0 24px 24px 0;
            box-shadow: 0 8px 32px rgba(31,38,135,0.08);
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            gap: 16px;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 1.5px solid #e0e0e0;
            transition: box-shadow 0.2s;
        }
        .sidebar .nav-section {
            margin-bottom: 0;
            margin-top: 24px;
        }
        .sidebar .nav-item {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 1.15rem;
            font-weight: 600;
            color: #b30000;
            background: #f8eaea;
            border-radius: 14px;
            padding: 12px 28px;
            margin: 10px 16px 0 16px;
            cursor: pointer;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: 0 2px 8px rgba(179,0,0,0.03);
            border: none;
            outline: none;
        }
        .sidebar .nav-item.active, .sidebar .nav-item:hover {
            background: #b30000;
            color: #fff;
            box-shadow: 0 4px 16px rgba(179,0,0,0.08);
        }
        .sidebar .nav-item i {
            font-size: 1.3rem;
            min-width: 28px;
            text-align: center;
        }
        .sidebar .nav-label {
            font-size: 1.1rem;
            font-weight: bold;
            color: #900;
            margin-left: 12px;
        }
        .sidebar .nav-sub {
            margin-left: 60px;
            margin-bottom: 8px;
            display: none;
            flex-direction: column;
            gap: 4px;
        }
        .sidebar .nav-sub.show {
            display: flex;
        }
        .sidebar .nav-sub .nav-sub-item {
            font-size: 1.05rem;
            color: #b30000;
            background: #fff;
            border-radius: 8px;
            padding: 7px 16px;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar .nav-sub .nav-sub-item:hover {
            background: #f8eaea;
            color: #900;
        }
        .sidebar .nav-divider {
            border-left: 4px solid #b30000;
            height: 32px;
            margin: 0 0 16px 32px;
        }
        .sidebar .nav-bottom {
            margin-top: auto;
            padding-bottom: 24px;
        }
        .sidebar .nav-bottom .nav-item {
            background: #f8eaea;
            color: #b30000;
            border-radius: 14px;
            margin: 0 16px;
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 28px;
            transition: background 0.18s, color 0.18s;
        }
        .sidebar .nav-bottom .nav-item:hover {
            background: #b30000;
            color: #fff;
        }
        .main-content {
            margin-left: 260px;
            padding: 48px 40px;
            min-height: 100vh;
            background: #f1f1f1;
            border-radius: 0 0 24px 24px;
            transition: margin-left 0.2s;
        }
        .create-account-form, .change-password-form {
            max-width: 500px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(31,38,135,0.08);
            padding: 32px 32px 24px 32px;
        }
        .create-account-form h3, .change-password-form h3 {
            color: #b30000;
            font-weight: 700;
            margin-bottom: 24px;
        }
        .form-group label {
            font-weight: 500;
        }
        .btn-create, .btn-change-password {
            background: #b30000;
            color: #fff;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 0;
            width: 100%;
            margin-top: 18px;
        }
        .btn-create:hover, .btn-change-password:hover {
            background: #900;
        }
        .success-message {
            margin-bottom: 16px;
            font-weight: 600;
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 12px 18px;
        }
        @media (max-width: 900px) {
            .sidebar {
                position: static;
                width: 100%;
                border-radius: 0;
                left: 0;
                top: 0;
                min-height: 0;
            }
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="nav-section">
        <div class="nav-item" id="dashboardBtn">
            <i class="fa-solid fa-map"></i>
            Dashboard
        </div>
        <div class="nav-item" id="employeeBtn">
            <i class="fa-solid fa-user"></i>
            Employee
            <i class="fa-solid fa-chevron-down" style="margin-left:auto;font-size:1rem;"></i>
        </div>
        <div class="nav-sub" id="employeeSubMenu">
            <div class="nav-sub-item">Attendance</div>
            <div class="nav-sub-item">Leave</div>
            <div class="nav-sub-item">Resignation</div>
        </div>
        <div class="nav-item">
            <i class="fa-solid fa-bell"></i>
            Notification
        </div>
        <div class="nav-item">
            <i class="fa-regular fa-clock"></i>
            Activity Logs
        </div>
        <div class="nav-item" id="createAccountBtn">
            <i class="fa-solid fa-user-plus"></i>
            Create Account
        </div>
        <div class="nav-item" id="changePasswordBtn">
            <i class="fa-solid fa-key"></i>
            Change Password
        </div>
    </div>
    <div class="nav-bottom">
        <form method="POST" style="width:100%;">
            <button type="submit" name="logout" class="nav-item" style="width:100%;background:none;border:none;outline:none;box-shadow:none;display:flex;align-items:center;gap:16px;padding:12px 28px;font-weight:600;font-size:1.1rem;color:#b30000;transition:background 0.18s, color 0.18s;border-radius:14px;">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </button>
        </form>
    </div>
</div>
<div class="main-content" id="mainContentArea">

    <?php
    // Fetch data from the database
    $attendance_summary = [];
    $leave_requests = [];
    $resignations = [];
    $admin_notices = [];
    $employees_overview = [];
    $activity_logs = [];
    $new_employees_this_month = 0;
    $total_employees = 0;

    // Fetch attendance summary
    $attendance_query = "SELECT status, COUNT(*) as count FROM attendance WHERE date = CURDATE() GROUP BY status";
    $attendance_result = mysqli_query($conn, $attendance_query);
    if ($attendance_result) {
        while ($row = mysqli_fetch_assoc($attendance_result)) {
            $attendance_summary[$row['status']] = $row['count'];
        }
    }

    // Fetch leave requests
    $leave_query = "SELECT status, COUNT(*) as count FROM leave_request WHERE start_date <= CURDATE() AND end_date >= CURDATE() GROUP BY status";
    $leave_result = mysqli_query($conn, $leave_query);
    if ($leave_result) {
        while ($row = mysqli_fetch_assoc($leave_result)) {
            $leave_requests[$row['status']] = $row['count'];
        }
    }

    // Fetch resignations
    $resignation_query = "SELECT status, COUNT(*) as count FROM resignation GROUP BY status";
    $resignation_result = mysqli_query($conn, $resignation_query);
    if ($resignation_result) {
        while ($row = mysqli_fetch_assoc($resignation_result)) {
            $resignations[$row['status']] = $row['count'];
        }
    }

    // Fetch admin notices
    $notice_query = "SELECT title, message FROM admin_notice ORDER BY created_at DESC LIMIT 5";
    $notice_result = mysqli_query($conn, $notice_query);
    if ($notice_result) {
        while ($row = mysqli_fetch_assoc($notice_result)) {
            $admin_notices[] = $row;
        }
    }

    // Fetch employees overview
    $employees_query = "SELECT department.name as department, COUNT(user_account.user_id) as count FROM user_account 
                        JOIN department ON user_account.department_id = department.department_id 
                        WHERE role_id = (SELECT role_id FROM role WHERE name = 'Employee')
                        GROUP BY department.name";
    $employees_result = mysqli_query($conn, $employees_query);
    if ($employees_result) {
        while ($row = mysqli_fetch_assoc($employees_result)) {
            $employees_overview[] = $row;
        }
    }

    // Fetch new employees this month excluding Admins
    $new_employees_query = "SELECT COUNT(user_id) as count FROM user_account 
                            WHERE MONTH(created_at) = MONTH(CURDATE()) 
                            AND YEAR(created_at) = YEAR(CURDATE()) 
                            AND role_id != (SELECT role_id FROM role WHERE name = 'Admin')";
    $new_employees_result = mysqli_query($conn, $new_employees_query);
    if ($new_employees_result) {
        $row = mysqli_fetch_assoc($new_employees_result);
        $new_employees_this_month = $row['count'];
    }

    // Fetch total employees excluding Admins
    $total_employees_query = "SELECT COUNT(user_id) as count FROM user_account 
                              WHERE role_id = (SELECT role_id FROM role WHERE name = 'Employee')";
    $total_employees_result = mysqli_query($conn, $total_employees_query);
    if ($total_employees_result) {
        $row = mysqli_fetch_assoc($total_employees_result);
        $total_employees = $row['count'];
    }

    // Fetch activity logs
    $activity_query = "SELECT module, action, created_at FROM activity_log ORDER BY created_at DESC LIMIT 5";
    $activity_result = mysqli_query($conn, $activity_query);
    if ($activity_result) {
        while ($row = mysqli_fetch_assoc($activity_result)) {
            $activity_logs[] = $row;
        }
    }
    ?>

    <div id="dashboardContent" style="display:none;">
        <h3 class="dashboard-title">Welcome to the Dashboard</h3>
        <div class="container mt-4">
            <div class="row">
                <!-- Attendance Monitoring -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h4>Attendance Monitoring</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Leave Management -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h4>Leave Management</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="leaveChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <!-- Resignations -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white">
                            <h4>Resignations</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="resignationChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Notices -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h4>Admin Notices</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php if (empty($admin_notices)): ?>
                                    <li class="list-group-item">No notices available</li>
                                <?php else: ?>
                                    <?php foreach ($admin_notices as $notice): ?>
                                        <li class="list-group-item"><?= htmlspecialchars($notice['title']) ?>: <?= htmlspecialchars($notice['message']) ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <!-- Employees Overview -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h4>Employees Overview</h4>
                        </div>
                        <div class="card-body">
                            <p class="mt-3">Total Employees: <?= $total_employees ?></p>
                            <ul class="list-group mt-3">
                                <?php if (empty($employees_overview)): ?>
                                    <li class="list-group-item">No data available</li>
                                <?php else: ?>
                                    <?php foreach ($employees_overview as $overview): ?>
                                        <li class="list-group-item"><?= htmlspecialchars($overview['department']) ?>: <?= htmlspecialchars($overview['count']) ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Logs -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h4>Activity Logs</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php if (empty($activity_logs)): ?>
                                    <li class="list-group-item">No activity logs available</li>
                                <?php else: ?>
                                    <?php foreach ($activity_logs as $log): ?>
                                        <li class="list-group-item"><?= htmlspecialchars($log['module']) ?> - <?= htmlspecialchars($log['action']) ?> at <?= $log['created_at'] ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="createAccountFormContainer" style="display:none;">
        <form class="create-account-form" id="createAccountForm" method="POST" autocomplete="off">
            <h3>Create Account</h3>
            <?php if ($create_account_msg): ?>
                <div id="createAccountMsg"><?php echo $create_account_msg; ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label for="fullName">Full Name</label>
                <input type="text" class="form-control" id="fullName" name="fullName" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="">Select Role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo htmlspecialchars($role['role_id']); ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <select class="form-control" id="department" name="department_id">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo htmlspecialchars($department['department_id']); ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="jobRole">Job Title</label>
                <select class="form-control" id="jobRole" name="job_role_id">
                    <option value="">Select Job Title</option>
                    <?php foreach ($job_roles as $job_role): ?>
                        <option value="<?php echo htmlspecialchars($job_role['job_role_id']); ?>"><?php echo htmlspecialchars($job_role['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-create" name="create_account">Create Account</button>
        </form>
    </div>

    <div id="changePasswordFormContainer" style="display:none;">
        <form class="change-password-form" id="changePasswordForm" method="POST" autocomplete="off">
            <h3>Change Password</h3>
            <?php if ($change_password_msg): ?>
                <div id="changePasswordMsg"><?php echo $change_password_msg; ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-change-password" name="change_password">Change Password</button>
        </form>
    </div>

    <div id="employeeListContainer" style="display:block;">
        <h3>User Management</h3>
        <div id="userActionMsg"><?php echo $user_action_msg ?? ''; ?></div>
        <div class="filter-section d-flex align-items-center mb-3 justify-content-between">
            <div class="d-flex align-items-center">
                <input type="text" id="search" placeholder="Search by name or email..." class="form-control" style="max-width: 300px; margin-right: 0;">
                <button id="applySearch" class="btn btn-primary" style="max-width: 100px; margin-left: 0;">Search</button>
            </div>
            <div class="d-flex align-items-center">
                <select id="filterRole" class="form-control" style="max-width: 200px; margin-left: 0;">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo htmlspecialchars($role['role_id']); ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterDepartment" class="form-control" style="max-width: 200px; margin-left: 0;">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo htmlspecialchars($department['department_id']); ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterJobRole" class="form-control" style="max-width: 200px; margin-left: 0;">
                    <option value="">All Job Titles</option>
                    <?php foreach ($job_roles as $job_role): ?>
                        <option value="<?php echo htmlspecialchars($job_role['job_role_id']); ?>"><?php echo htmlspecialchars($job_role['title']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="resetFilters" class="btn btn-secondary" style="max-width: 50px; margin-left: 0;">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Job Title</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <?php
                $limit = 12;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($page - 1) * $limit;

                $search = $_GET['search'] ?? '';
                $filterRole = $_GET['filterRole'] ?? '';
                $filterDepartment = $_GET['filterDepartment'] ?? '';
                $filterJobRole = $_GET['filterJobRole'] ?? '';

                $userQuery = "SELECT ua.user_id, ua.full_name, ua.email, r.role_id, r.name AS role_name, 
                              d.department_id, d.name AS department_name, 
                              jr.job_role_id, jr.title AS job_title,
                              ua.created_at 
                              FROM user_account ua
                              JOIN role r ON ua.role_id = r.role_id 
                              LEFT JOIN department d ON ua.department_id = d.department_id
                              LEFT JOIN job_role jr ON ua.job_role_id = jr.job_role_id
                              WHERE (ua.full_name LIKE '%$search%' OR ua.email LIKE '%$search%')";

                // Ensure filters are applied correctly
                if ($filterRole !== '') {
                    $userQuery .= " AND ua.role_id = '$filterRole'";
                }
                if ($filterDepartment !== '') {
                    $userQuery .= " AND ua.department_id = '$filterDepartment'";
                }
                if ($filterJobRole !== '') {
                    $userQuery .= " AND ua.job_role_id = '$filterJobRole'";
                }

                $userQuery .= " LIMIT $limit OFFSET $offset";
                $userResult = mysqli_query($conn, $userQuery);
                if ($userResult) {
                    while ($user = mysqli_fetch_assoc($userResult)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                        echo "<td data-role-id='" . htmlspecialchars($user['role_id']) . "'>" . htmlspecialchars($user['role_name']) . "</td>";
                        echo "<td data-department-id='" . htmlspecialchars($user['department_id']) . "'>" . htmlspecialchars($user['department_name'] ?? 'N/A') . "</td>";
                        echo "<td data-job-role-id='" . htmlspecialchars($user['job_role_id']) . "'>" . htmlspecialchars($user['job_title'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
                        echo "<td>
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='user_id' value='" . htmlspecialchars($user['user_id']) . "'>
                                    <button type='submit' name='delete_user' class='btn btn-danger btn-sm'>
                                        <i class='fas fa-trash-alt'></i>
                                    </button>
                                </form>
                                <button class='btn btn-primary btn-sm edit-change-password-user' 
                                    data-user-id='" . htmlspecialchars($user['user_id']) . "' 
                                    data-full-name='" . htmlspecialchars($user['full_name']) . "' 
                                    data-email='" . htmlspecialchars($user['email']) . "' 
                                    data-role-id='" . htmlspecialchars($user['role_id']) . "'
                                    data-department-id='" . htmlspecialchars($user['department_id']) . "'
                                    data-job-role-id='" . htmlspecialchars($user['job_role_id']) . "'>
                                    <i class='fas fa-edit'></i>
                                </button>
                              </td>";
                        echo "</tr>";
                    }
                }

                $totalQuery = "SELECT COUNT(*) as total FROM user_account WHERE (full_name LIKE '%$search%' OR email LIKE '%$search%')";
                if ($filterRole !== '') {
                    $totalQuery .= " AND role_id = '$filterRole'";
                }
                if ($filterDepartment !== '') {
                    $totalQuery .= " AND department_id = '$filterDepartment'";
                }
                if ($filterJobRole !== '') {
                    $totalQuery .= " AND job_role_id = '$filterJobRole'";
                }
                $totalResult = mysqli_query($conn, $totalQuery);
                $totalRow = mysqli_fetch_assoc($totalResult);
                $totalPages = ceil($totalRow['total'] / $limit);
                ?>
            </tbody>
        </table>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php if($page <= 1){ echo 'disabled'; } ?>">
                    <a class="page-link" href="<?php if($page > 1){ echo '?page=' . ($page - 1); } else { echo '#'; } ?>" data-page="<?php echo $page - 1; ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php if($page == $i){ echo 'active'; } ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php if($page >= $totalPages){ echo 'disabled'; } ?>">
                    <a class="page-link" href="<?php if($page < $totalPages){ echo '?page=' . ($page + 1); } else { echo '#'; } ?>" data-page="<?php echo $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <script>
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                const page = this.getAttribute('data-page');
                if (page && page !== '#') {
                    employeeListContainer.style.display = 'block';
                    createAccountFormContainer.style.display = 'none';
                    changePasswordFormContainer.style.display = 'none';
                    dashboardContent.style.display = 'none';
                    window.location.href = '?page=' + page;
                }
            });
        });

        document.querySelectorAll('.edit-change-password-user').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const fullName = this.getAttribute('data-full-name');
                const email = this.getAttribute('data-email');
                const roleId = this.getAttribute('data-role-id');
                const departmentId = this.getAttribute('data-department-id');
                const jobRoleId = this.getAttribute('data-job-role-id');
                
                // Populate modal with user data
                $('#editUserId').val(userId);
                $('#editFullName').val(fullName);
                $('#editEmail').val(email);
                $('#editRole').val(roleId);
                $('#editDepartment').val(departmentId);
                $('#editJobRole').val(jobRoleId);
                
                $('#editChangePasswordModal').modal('show');
            });
        });

        // Filter and search functionality
        const searchInput = document.getElementById('search');
        const applySearchButton = document.getElementById('applySearch');
        const filterRoleSelect = document.getElementById('filterRole');
        const filterDepartmentSelect = document.getElementById('filterDepartment');
        const filterJobRoleSelect = document.getElementById('filterJobRole');
        const resetFiltersButton = document.getElementById('resetFilters');

        function applyFilters() {
            const search = searchInput.value;
            const filterRole = filterRoleSelect.value;
            const filterDepartment = filterDepartmentSelect.value;
            const filterJobRole = filterJobRoleSelect.value;

            const queryParams = new URLSearchParams(window.location.search);
            queryParams.set('search', search);
            queryParams.set('filterRole', filterRole);
            queryParams.set('filterDepartment', filterDepartment);
            queryParams.set('filterJobRole', filterJobRole);
            queryParams.set('page', 1); // Reset to first page on filter change

            window.location.search = queryParams.toString();
        }

        function resetFilters() {
            searchInput.value = '';
            filterRoleSelect.value = '';
            filterDepartmentSelect.value = '';
            filterJobRoleSelect.value = '';
            applyFilters();
        }

        applySearchButton.addEventListener('click', applyFilters);
        filterRoleSelect.addEventListener('change', applyFilters);
        filterDepartmentSelect.addEventListener('change', applyFilters);
        filterJobRoleSelect.addEventListener('change', applyFilters);
        resetFiltersButton.addEventListener('click', resetFilters);

        // Ensure only the relevant container is shown after actions
        if (<?php echo json_encode(isset($_POST['delete_user']) || isset($_POST['edit_user'])); ?>) {
            employeeListContainer.style.display = 'block';
            createAccountFormContainer.style.display = 'none';
            changePasswordFormContainer.style.display = 'none';
            dashboardContent.style.display = 'none';
        } else if (<?php echo json_encode(isset($_POST['create_account'])); ?>) {
            createAccountFormContainer.style.display = 'block';
            employeeListContainer.style.display = 'none';
            changePasswordFormContainer.style.display = 'none';
            dashboardContent.style.display = 'none';
        } else if (<?php echo json_encode(isset($_POST['change_password'])); ?>) {
            changePasswordFormContainer.style.display = 'block';
            createAccountFormContainer.style.display = 'none';
            employeeListContainer.style.display = 'none';
            dashboardContent.style.display = 'none';
        } else if (window.location.search.includes('search') || window.location.search.includes('filterRole') || window.location.search.includes('filterDepartment') || window.location.search.includes('filterJobRole')) {
            // Show employee list if any filter or search is applied
            employeeListContainer.style.display = 'block';
            createAccountFormContainer.style.display = 'none';
            changePasswordFormContainer.style.display = 'none';
            dashboardContent.style.display = 'none';
        } else {
            // Default to showing the dashboard if no specific action is detected
            dashboardContent.style.display = 'block';
            createAccountFormContainer.style.display = 'none';
            employeeListContainer.style.display = 'none';
            changePasswordFormContainer.style.display = 'none';
        }
    </script>

    <!-- Modal for Edit/Change Password -->
    <div class="modal fade" id="editChangePasswordModal" tabindex="-1" role="dialog" aria-labelledby="editChangePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editChangePasswordModalLabel">Edit User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editChangePasswordForm" method="POST" autocomplete="off">
                        <input type="hidden" id="editUserId" name="user_id">
                        <div class="form-group">
                            <label for="editFullName">Full Name</label>
                            <input type="text" class="form-control" id="editFullName" name="fullName" required>
                        </div>
                        <div class="form-group">
                            <label for="editEmail">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="editRole">Role</label>
                            <select class="form-control" id="editRole" name="role" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role['role_id']); ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editDepartment">Department</label>
                            <select class="form-control" id="editDepartment" name="department_id">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo htmlspecialchars($department['department_id']); ?>"><?php echo htmlspecialchars($department['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editJobRole">Job Title</label>
                            <select class="form-control" id="editJobRole" name="job_role_id">
                                <option value="">Select Job Title</option>
                                <?php foreach ($job_roles as $job_role): ?>
                                    <option value="<?php echo htmlspecialchars($job_role['job_role_id']); ?>"><?php echo htmlspecialchars($job_role['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password">
                        </div>
                        <div class="form-group">
                            <label for="confirmNewPassword">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmNewPassword" name="confirm_password">
                        </div>
                        <button type="submit" class="btn btn-primary" name="edit_user">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    // Collapsible Employee menu
    const employeeBtn = document.getElementById('employeeBtn');
    const employeeSubMenu = document.getElementById('employeeSubMenu');
    let isOpen = false;
    employeeBtn.addEventListener('click', function() {
        isOpen = !isOpen;
        employeeSubMenu.classList.toggle('show', isOpen);
        // Rotate chevron
        const chevron = employeeBtn.querySelector('.fa-chevron-down');
        if (isOpen) {
            chevron.style.transform = 'rotate(180deg)';
        } else {
            chevron.style.transform = 'rotate(0deg)';
        }
    });

    // Show Create Account form on sidebar click
    const createAccountBtn = document.getElementById('createAccountBtn');
    const createAccountFormContainer = document.getElementById('createAccountFormContainer');
    const employeeListContainer = document.getElementById('employeeListContainer');
    const changePasswordFormContainer = document.getElementById('changePasswordFormContainer');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const dashboardBtn = document.getElementById('dashboardBtn');
    const dashboardContent = document.getElementById('dashboardContent');
    
    createAccountBtn.addEventListener('click', function() {
        createAccountFormContainer.style.display = 'block';
        employeeListContainer.style.display = 'none';
        dashboardContent.style.display = 'none';
        changePasswordFormContainer.style.display = 'none';
    });

    changePasswordBtn.addEventListener('click', function() {
        changePasswordFormContainer.style.display = 'block';
        createAccountFormContainer.style.display = 'none';
        employeeListContainer.style.display = 'none';
        dashboardContent.style.display = 'none';
    });

    dashboardBtn.addEventListener('click', function() {
        dashboardContent.style.display = 'block';
        createAccountFormContainer.style.display = 'none';
        employeeListContainer.style.display = 'none';
        changePasswordFormContainer.style.display = 'none';
    });

    // Remove success message after 3 seconds
    function removeMessageAfterDelay(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            setTimeout(() => {
                element.style.display = 'none';
            }, 3000);
        }
    }

    if (<?php echo json_encode($create_account_success); ?>) {
        removeMessageAfterDelay('createAccountMsg');
        createAccountFormContainer.style.display = 'block';
        employeeListContainer.style.display = 'none';
    }

    if (<?php echo json_encode($change_password_success); ?>) {
        removeMessageAfterDelay('changePasswordMsg');
        changePasswordFormContainer.style.display = 'block';
        employeeListContainer.style.display = 'none';
    }

    // Show Employee List on button click
    employeeBtn.addEventListener('click', function() {
        employeeListContainer.style.display = 'block';
        createAccountFormContainer.style.display = 'none';
        changePasswordFormContainer.style.display = 'none';
        dashboardContent.style.display = 'none';
    });
</script>
</body>
</html>