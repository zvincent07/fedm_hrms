<?php
include '../config/db.php';
session_start();

// Check if user is logged in and is an admin or HR
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Hr')) {
    // Log unauthorized access attempt
    if (isset($_SESSION['user_id'])) {
        log_activity($conn, $_SESSION['user_id'], 'Security', 'unauthorized_access', 'admin_page', null, 'Unauthorized access attempt to admin page');
    }
    // Redirect to login page
    header('Location: ../index.php');
    exit();
}

// Helper function to log admin actions
function log_activity($conn, $user_id, $module, $action, $target_type = null, $target_id = null, $details = null) {
    $user_id = $user_id ? intval($user_id) : 'NULL';
    $module = mysqli_real_escape_string($conn, $module);
    $action = mysqli_real_escape_string($conn, $action);
    $target_type = $target_type ? "'" . mysqli_real_escape_string($conn, $target_type) . "'" : "NULL";
    $target_id = $target_id !== null ? intval($target_id) : "NULL";
    $details = $details ? "'" . mysqli_real_escape_string($conn, $details) . "'" : "NULL";
    $sql = "INSERT INTO activity_log (user_id, module, action, target_type, target_id, details, created_at)
            VALUES ($user_id, '$module', '$action', $target_type, $target_id, $details, NOW())";
    mysqli_query($conn, $sql);
}

// --- LOGIN ACTIVITY LOGGING PATCH ---
// If this is the first page load after login, log the login event
if (isset($_SESSION['user_id']) && empty($_SESSION['login_logged'])) {
    // Only log once per session
    $user_id = $_SESSION['user_id'];
    // Optionally, get user info for details
    $user_info = null;
    $user_res = mysqli_query($conn, "SELECT full_name, email FROM user_account WHERE user_id = '$user_id'");
    if ($user_res && mysqli_num_rows($user_res) > 0) {
        $user_info = mysqli_fetch_assoc($user_res);
        $details = "User logged in: " . $user_info['full_name'] . " (" . $user_info['email'] . ")";
    } else {
        $details = "User logged in: user_id $user_id";
    }
    $module = (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'hr') ? 'HR' : 'Admin';
    log_activity($conn, $user_id, $module, 'login', 'user_account', $user_id, $details);
    $_SESSION['login_logged'] = true;
}
// --- END LOGIN ACTIVITY LOGGING PATCH ---

$roles = [];
$sql = "SELECT role_id, name FROM role WHERE name != 'Admin' ORDER BY name ASC";
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

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    $module = (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'hr') ? 'HR' : 'Admin';
    log_activity($conn, $current_user_id, $module, 'logout', 'user_account', $current_user_id, $module . ' logged out');
    // Remove login_logged so next login is logged again
    unset($_SESSION['login_logged']);
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit();
}

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
                $new_user_id = mysqli_insert_id($conn);
                $create_account_msg = '<div class="alert alert-success">Account created successfully!</div>';
                $create_account_success = true;
                log_activity($conn, $current_user_id, 'User Management', 'create_account', 'user_account', $new_user_id, "Created user: $fullName ($email)");
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
                        log_activity($conn, $current_user_id, 'User Management', 'change_password', 'user_account', $current_user_id, "Changed own password");
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
        log_activity($conn, $current_user_id, 'User Management', 'delete_user', 'user_account', $user_id_to_delete, "Deleted user_id: $user_id_to_delete");
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

    // For activity log details, fetch old values
    $old_user = null;
    $old_user_query = "SELECT * FROM user_account WHERE user_id = '$edit_user_id'";
    $old_user_result = mysqli_query($conn, $old_user_query);
    if ($old_user_result && mysqli_num_rows($old_user_result) > 0) {
        $old_user = mysqli_fetch_assoc($old_user_result);
    }

    $update_query = "UPDATE user_account SET 
                    full_name = '$edit_full_name', 
                    email = '$edit_email', 
                    role_id = '$edit_role_id',
                    department_id = " . ($edit_department_id ? "'$edit_department_id'" : "NULL") . ",
                    job_role_id = " . ($edit_job_role_id ? "'$edit_job_role_id'" : "NULL") . ",
                    updated_at = NOW()";
    
    $password_changed = false;
    if ($edit_new_password) {
        $new_hashed_password = password_hash($edit_new_password, PASSWORD_DEFAULT);
        $update_query .= ", password = '$new_hashed_password'";
        $password_changed = true;
    }
    $update_query .= " WHERE user_id = '$edit_user_id'";

    if (mysqli_query($conn, $update_query)) {
        $user_action_msg = '<div id="userActionMsg" class="alert alert-success">User updated successfully.</div>';
        $details = "Old: " . json_encode($old_user) . "; New: " . json_encode([
            'full_name' => $edit_full_name,
            'email' => $edit_email,
            'role_id' => $edit_role_id,
            'department_id' => $edit_department_id,
            'job_role_id' => $edit_job_role_id
        ]);
        log_activity($conn, $current_user_id, 'User Management', 'edit_user', 'user_account', $edit_user_id, $details);
        if ($password_changed) {
            log_activity($conn, $current_user_id, 'User Management', 'change_user_password', 'user_account', $edit_user_id, "Changed password for user_id: $edit_user_id");
        }
    } else {
        $user_action_msg = '<div id="userActionMsg" class="alert alert-danger">Failed to update user.</div>';
    }
    echo "<script>setTimeout(() => { document.getElementById('userActionMsg').style.display = 'none'; }, 3000);</script>";
}

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
$leave_query = "SELECT lr.*, ua.full_name, d.name AS department_name 
    FROM leave_request lr 
    JOIN user_account ua ON lr.employee_id = ua.user_id 
    LEFT JOIN department d ON ua.department_id = d.department_id 
    WHERE lr.status = 'pending' 
    ORDER BY lr.requested_at DESC";
$leave_result = mysqli_query($conn, $leave_query);
// if ($leave_result) {
//     while ($row = mysqli_fetch_assoc($leave_result)) {
//         $leave_requests[$row['status']] = $row['count'];
//     }
// }

// Fetch resignations
$resignation_query = "SELECT status, COUNT(*) as count FROM resignation GROUP BY status";
$resignation_result = mysqli_query($conn, $resignation_query);
if ($resignation_result) {
    while ($row = mysqli_fetch_assoc($resignation_result)) {
        $resignations[$row['status']] = $row['count'];
    }
}

// Fetch admin notices
$notice_query = "SELECT n.title, n.content, n.type, n.created_at, ua.full_name as sender_name 
                 FROM notification n 
                 LEFT JOIN user_account ua ON n.sender_id = ua.user_id 
                 ORDER BY n.created_at DESC LIMIT 10";
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

// Fetch activity logs for table (pagination and filters)
$activity_limit = 15;
$activity_page = isset($_GET['activity_page']) ? (int)$_GET['activity_page'] : 1;
$activity_offset = ($activity_page - 1) * $activity_limit;

// Optionally, add search/filter for activity logs
$activity_search = $_GET['activity_search'] ?? '';
$activity_module = $_GET['activity_module'] ?? '';
$activity_action = $_GET['activity_action'] ?? '';

$activity_where = "1=1";
if ($activity_search !== '') {
    $activity_search_esc = mysqli_real_escape_string($conn, $activity_search);
    $activity_where .= " AND (al.module LIKE '%$activity_search_esc%' OR al.action LIKE '%$activity_search_esc%' OR al.details LIKE '%$activity_search_esc%')";
}
if ($activity_module !== '') {
    $activity_module_esc = mysqli_real_escape_string($conn, $activity_module);
    $activity_where .= " AND al.module = '$activity_module_esc'";
}
if ($activity_action !== '') {
    $activity_action_esc = mysqli_real_escape_string($conn, $activity_action);
    $activity_where .= " AND al.action = '$activity_action_esc'";
}

$activity_query = "SELECT al.*, ua.full_name AS user_name, ua.email AS user_email
                   FROM activity_log al
                   LEFT JOIN user_account ua ON al.user_id = ua.user_id
                   WHERE $activity_where
                   ORDER BY al.created_at DESC
                   LIMIT $activity_limit OFFSET $activity_offset";
$activity_result = mysqli_query($conn, $activity_query);

$activity_logs = [];
if ($activity_result) {
    while ($row = mysqli_fetch_assoc($activity_result)) {
        $activity_logs[] = $row;
    }
}

// For pagination
$activity_total_query = "SELECT COUNT(*) as total FROM activity_log al LEFT JOIN user_account ua ON al.user_id = ua.user_id WHERE $activity_where";
$activity_total_result = mysqli_query($conn, $activity_total_query);
$activity_total_row = mysqli_fetch_assoc($activity_total_result);
$activity_total_pages = ceil($activity_total_row['total'] / $activity_limit);

$show = $_GET['show'] ?? 'dashboard';

// Attendance Monitoring: all attendance for current date
$attendance_today = [];
$attendance_query = "SELECT a.*, ua.full_name, d.name AS department_name 
    FROM attendance a 
    JOIN user_account ua ON a.employee_id = ua.user_id 
    LEFT JOIN department d ON ua.department_id = d.department_id 
    WHERE a.date = CURDATE() 
    ORDER BY a.check_in ASC";
$attendance_result = mysqli_query($conn, $attendance_query);
if ($attendance_result) {
    while ($row = mysqli_fetch_assoc($attendance_result)) {
        $attendance_today[] = $row;
    }
}

// Leave Management: all pending leave requests
$pending_leaves = [];
$leave_query = "SELECT lr.*, ua.full_name, d.name AS department_name 
    FROM leave_request lr 
    JOIN user_account ua ON lr.employee_id = ua.user_id 
    LEFT JOIN department d ON ua.department_id = d.department_id 
    WHERE lr.status = 'pending' 
    ORDER BY lr.requested_at DESC";
$leave_result = mysqli_query($conn, $leave_query);
if ($leave_result) {
    while ($row = mysqli_fetch_assoc($leave_result)) {
        $pending_leaves[] = $row;
    }
}

// Resignations: all pending
$pending_resignations = [];
$resignation_query = "SELECT r.*, ua.full_name, d.name AS department_name 
    FROM resignation r 
    JOIN user_account ua ON r.employee_id = ua.user_id 
    LEFT JOIN department d ON ua.department_id = d.department_id 
    WHERE r.status = 'pending' 
    ORDER BY r.submitted_at DESC";
$resignation_result = mysqli_query($conn, $resignation_query);
if ($resignation_result) {
    while ($row = mysqli_fetch_assoc($resignation_result)) {
        $pending_resignations[] = $row;
    }
}

// Recent Notices: top 5 from notification table
$recent_notifications = [];
$notif_query = "SELECT n.title, n.content, n.type, n.created_at, ua.full_name as sender_name 
    FROM notification n 
    LEFT JOIN user_account ua ON n.sender_id = ua.user_id 
    ORDER BY n.created_at DESC LIMIT 5";
$notif_result = mysqli_query($conn, $notif_query);
if ($notif_result) {
    while ($row = mysqli_fetch_assoc($notif_result)) {
        $recent_notifications[] = $row;
    }
}

// Add this handler at the top of the attendance section:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_attendance'])) {
    $attendance_id = intval($_POST['attendance_id']);
    
    // Get attendance details
    $attendance_details_query = "SELECT a.*, ua.full_name FROM attendance a 
                               JOIN user_account ua ON a.employee_id = ua.user_id 
                               WHERE a.attendance_id = $attendance_id";
    $attendance_details_result = mysqli_query($conn, $attendance_details_query);
    $attendance_details = mysqli_fetch_assoc($attendance_details_result);
    
    // Log the activity
    $details = "Viewed attendance record for " . $attendance_details['full_name'] . 
              " on " . $attendance_details['date'];
    log_activity($conn, $_SESSION['user_id'], 'Attendance Management', 'view_record', 
                'attendance', $attendance_id, $details);
}

// Handle attendance update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $attendance_id = intval($_POST['attendance_id']);
    $time_in = $_POST['time_in'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Get current attendance record
    $current_query = "SELECT a.*, ua.full_name FROM attendance a 
                     JOIN user_account ua ON a.employee_id = ua.user_id 
                     WHERE a.attendance_id = $attendance_id";
    $current_result = mysqli_query($conn, $current_query);
    $current_record = mysqli_fetch_assoc($current_result);
    
    // Update both time in and status
    $update_query = "UPDATE attendance SET check_in = '$time_in', status = '$status' WHERE attendance_id = $attendance_id";
    if (mysqli_query($conn, $update_query)) {
        // Log the activity
        $details = "Updated attendance for " . $current_record['full_name'] . 
                  " on " . $current_record['date'] . 
                  " - Time In: " . $current_record['check_in'] . " to " . $time_in .
                  ", Status: " . $current_record['status'] . " to " . $status;
        log_activity($conn, $_SESSION['user_id'], 'Attendance Management', 'update_attendance', 
                    'attendance', $attendance_id, $details);
        
        // Redirect to refresh the page
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

// Get employee count per department
$department_colors = [
    'IT Department' => '#e48a2a',
    'Operations' => '#f6a940',
    'HR Department' => '#bfc6d1',
    'Manager' => '#f7c873'
];
$departments_bubble = [];
$sql = "SELECT d.name as department, COUNT(ua.user_id) as count
        FROM user_account ua
        JOIN department d ON ua.department_id = d.department_id
        JOIN role r ON ua.role_id = r.role_id
        WHERE r.name = 'Employee' OR r.name = 'Manager'
        GROUP BY d.name";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $color = $department_colors[$row['department']] ?? '#ccc';
        $departments_bubble[] = [
            'name' => $row['department'],
            'count' => $row['count'],
            'color' => $color
        ];
    }
}

$is_hr = (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'hr');

// Dynamic HR dashboard stats
$present_today_count = 0;
$leave_today_count = 0;
$resignation_this_month_count = 0;

// Total Employees Present Today
$present_today_sql = "SELECT COUNT(DISTINCT a.employee_id) as cnt
    FROM attendance a
    JOIN user_account ua ON a.employee_id = ua.user_id
    WHERE a.date = CURDATE() AND a.status = 'present'";
$present_today_res = mysqli_query($conn, $present_today_sql);
if ($present_today_res && $row = mysqli_fetch_assoc($present_today_res)) {
    $present_today_count = (int)$row['cnt'];
}

// Total Employees on Leave Today
$leave_today_sql = "SELECT COUNT(DISTINCT a.employee_id) as cnt
    FROM attendance a
    JOIN user_account ua ON a.employee_id = ua.user_id
    WHERE a.date = CURDATE() AND a.status = 'leave'";
$leave_today_res = mysqli_query($conn, $leave_today_sql);
if ($leave_today_res && $row = mysqli_fetch_assoc($leave_today_res)) {
    $leave_today_count = (int)$row['cnt'];
}

// Resignation this Month
$resignation_month_sql = "SELECT COUNT(*) as cnt
    FROM resignation
    WHERE MONTH(submitted_at) = MONTH(CURDATE()) AND YEAR(submitted_at) = YEAR(CURDATE())";
$resignation_month_res = mysqli_query($conn, $resignation_month_sql);
if ($resignation_month_res && $row = mysqli_fetch_assoc($resignation_month_res)) {
    $resignation_this_month_count = (int)$row['cnt'];
}
?>

<?php include 'adminHeader.php'; ?>

<?php $is_hr = (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'hr'); ?>
<div class="sidebar"
    style="display: flex; flex-direction: column; height: 100vh; min-height: 0; overflow: hidden; background: <?= $is_hr ? '#ffffff' : '#fff' ?>;">
    <!-- Profile Icon -->
    <div style="display: flex; flex-direction: column; align-items: center; margin-top: 28px; flex-shrink: 0;">
        <div style="background: <?= $is_hr ? '#f37b20' : '#b30000' ?>; border-radius: 50%; width: 104px; height: 104px; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-user<?= $is_hr ? '-group' : '' ?>" style="color: #fff; font-size: 3.6rem;"></i>
        </div>
    </div>
    <!-- Nav Section -->
    <div class="nav-section" style="flex: 1 1 auto; min-height: 0; overflow-y: auto; margin-bottom: 0;">
        <a class="nav-item<?= $is_hr ? ' hr-nav-btn' : '' ?>" id="dashboardBtn" href="?show=dashboard">
            <i class="fa-solid fa-map" style="color:<?= $is_hr ? '#a04a00' : '' ?>"></i>
            <span style="color:<?= $is_hr ? '#a04a00' : '' ?>; font-weight: bold;">Dashboard</span>
        </a>
        <div class="nav-item<?= $is_hr ? ' hr-nav-btn' : '' ?>" id="employeeBtn" style="cursor:pointer;">
            <i class="fa-solid fa-user" style="color:<?= $is_hr ? '#a04a00' : '' ?>"></i>
            <span style="color:<?= $is_hr ? '#a04a00' : '' ?>; font-weight: bold;">Employee</span>
            <i class="fa-solid fa-chevron-down" style="margin-left:auto;font-size:1rem;color:<?= $is_hr ? '#a04a00' : '' ?>"></i>
        </div>
        <div class="nav-sub" id="employeeSubMenu" style="display:none; margin-left: 32px;">
            <a class="nav-sub-item<?= $is_hr ? ' hr-nav-btn' : '' ?>" href="?show=employeeList" style="color:<?= $is_hr ? '#a04a00' : '' ?>">User Management</a>
            <a class="nav-sub-item<?= $is_hr ? ' hr-nav-btn' : '' ?>" href="?show=attendance" style="color:<?= $is_hr ? '#a04a00' : '' ?>">Attendance</a>
            <a class="nav-sub-item<?= $is_hr ? ' hr-nav-btn' : '' ?>" href="?show=leave" style="color:<?= $is_hr ? '#a04a00' : '' ?>">Leave</a>
            <a class="nav-sub-item<?= $is_hr ? ' hr-nav-btn' : '' ?>" href="?show=resignation" style="color:<?= $is_hr ? '#a04a00' : '' ?>">Resignation</a>
        </div>
        <a class="nav-item<?= $is_hr ? ' hr-nav-btn' : '' ?>" href="?show=notification">
            <i class="fa-solid fa-paper-plane" style="color:<?= $is_hr ? '#a04a00' : '' ?>"></i>
            <span style="color:<?= $is_hr ? '#a04a00' : '' ?>; font-weight: bold;">Send Notification</span>
        </a>
        <a class="nav-item<?= $is_hr ? ' hr-nav-btn' : '' ?>" href="?show=activityLogs">
            <i class="fa-regular fa-clock" style="color:<?= $is_hr ? '#a04a00' : '' ?>"></i>
            <span style="color:<?= $is_hr ? '#a04a00' : '' ?>; font-weight: bold;">Activity Logs</span>
        </a>
        <?php if (!$is_hr): ?>
        <a class="nav-item" id="createAccountBtn" href="?show=createAccount">
            <i class="fa-solid fa-user-plus"></i>
            Create Account
        </a>
        <a class="nav-item" id="changePasswordBtn" href="?show=changePassword">
            <i class="fa-solid fa-key"></i>
            Change Password
        </a>
        <?php endif; ?>
    </div>
    <div class="nav-bottom" style="flex-shrink: 0; margin-bottom: 18px;">
        <form method="POST" style="width:100%;">
            <button type="submit" name="logout" class="nav-item<?= $is_hr ? ' hr-nav-btn' : '' ?>" style="width:100%;background:none;border:none;outline:none;box-shadow:none;display:flex;align-items:center;gap:16px;padding:12px 28px;font-weight:600;font-size:1.1rem;color:<?= $is_hr ? '#a04a00' : '#b30000' ?>;transition:background 0.18s, color 0.18s;border-radius:14px;">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </button>
        </form>
    </div>
</div>
<style>
<?php if ($is_hr): ?>
    .hr-nav-btn {
        background: #fff !important;
        border-radius: 16px !important;
        margin: 18px 16px 0 16px !important;
        padding: 18px 0 18px 24px !important;
        color: #a04a00 !important;
        font-weight: bold !important;
        font-size: 1.15rem !important;
        box-shadow: none !important;
        border: none !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        transition: background 0.18s, color 0.18s;
    }
    .hr-nav-btn:hover {
        background: #ffe5c2 !important;
        color: #a04a00 !important;
    }
<?php endif; ?>
</style>

<div class="main-content" id="mainContentArea">

    <?php if ($show === 'dashboard'): ?>
        <?php
        $is_hr = (isset($_SESSION['role']) && (strtolower($_SESSION['role']) === 'hr'));
        ?>
        <?php if ($is_hr): ?>
            <!-- HR DASHBOARD (matches your image) -->
            <style>
                .hr-dashboard-bg { background: #f4f4f7; min-height: 100vh; padding: 32px 0; }
                .hr-summary-row { display: flex; gap: 24px; justify-content: center; margin-bottom: 32px; }
                .hr-summary-card {
                    border-radius: 12px;
                    padding: 24px 32px;
                    display: flex;
                    align-items: center;
                    gap: 18px;
                    min-width: 220px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
                    font-weight: 600;
                    font-size: 1.2rem;
                    background: #fff;
                    color: #222;
                }
                .hr-summary-card .icon { font-size: 2.5rem; margin-right: 10px; }
                .hr-summary-card.bg1 { background: #e9dfd3; }
                .hr-summary-card.bg2 { background: #e48a2a; color: #fff; }
                .hr-summary-card.bg3 { background: #f6a940; color: #fff; }
                .hr-main-row { display: flex; gap: 32px; justify-content: center; }
                .hr-card { background: #fff; border-radius: 12px; padding: 24px 24px 18px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); min-width: 260px; flex: 1; }
                .hr-card h4 { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; }
                .star { color: #f6a940; font-size: 1.1rem; }
                .bubble-chart { display: flex; align-items: flex-end; gap: 18px; margin-top: 18px; justify-content: center; }
                .bubble { display: flex; align-items: center; justify-content: center; border-radius: 50%; color: #fff; font-weight: bold; font-size: 1.2rem; position: relative; }
                .bubble-label { position: absolute; bottom: -22px; left: 50%; transform: translateX(-50%); font-size: 0.95rem; color: #444; font-weight: 500; white-space: nowrap; }
                .bubble-legend { display: flex; gap: 18px; margin-top: 18px; justify-content: center; }
                .bubble-legend-item { display: flex; align-items: center; gap: 6px; font-size: 0.95rem; }
                .bubble-legend-color { width: 14px; height: 14px; border-radius: 50%; display: inline-block; }
            </style>
            <div class="hr-dashboard-bg">
                <div class="hr-summary-row">
                    <div class="hr-summary-card bg1">
                        <span class="icon"><i class="fa fa-user"></i></span>
                        <div>
                            <div style="font-size:2rem; font-weight:bold;"><?= $present_today_count ?></div>
                            <div>Total Employees Present Today</div>
                        </div>
                    </div>
                    <div class="hr-summary-card bg2">
                        <span class="icon"><i class="fa fa-book"></i></span>
                        <div>
                            <div style="font-size:2rem; font-weight:bold;"><?= $leave_today_count ?></div>
                            <div>Total Employees on Leave Today</div>
                        </div>
                    </div>
                    <div class="hr-summary-card bg3">
                        <span class="icon"><i class="fa fa-money-bill"></i></span>
                        <div>
                            <div style="font-size:2rem; font-weight:bold;"><?= $resignation_this_month_count ?></div>
                            <div>Resignation this Month</div>
                        </div>
                    </div>
                </div>
                <div class="hr-main-row">
                    <div class="hr-card">
                        <h4>Top-Rated Employees</h4>
                        <ol style="padding-left: 18px;">
                            <li style="margin-bottom: 8px;">John Doe <span style="margin-left: 8px;"><?php for ($i = 0; $i < 5; $i++): ?><span class="star">&#9733;</span><?php endfor; ?> 5.0</span></li>
                            <li style="margin-bottom: 8px;">Ryan Jeremy <span style="margin-left: 8px;"><?php for ($i = 0; $i < 5; $i++): ?><span class="star">&#9733;</span><?php endfor; ?> 5.0</span></li>
                            <li style="margin-bottom: 8px;">Christine Mendoza <span style="margin-left: 8px;"><?php for ($i = 0; $i < 5; $i++): ?><span class="star">&#9733;</span><?php endfor; ?> 5.0</span></li>
                        </ol>
                    </div>
                    <div class="hr-card">
                        <h4>Total Employee per Department</h4>
                        <div class="bubble-chart" style="display: flex; align-items: flex-end; gap: 32px; justify-content: center;">
                            <?php foreach ($departments_bubble as $bubble): 
                                $size = 40 + min($bubble['count'] * 4, 80);
                            ?>
                                <div style="display: flex; flex-direction: column; align-items: center; min-width: 80px;">
                                    <div class="bubble" style="width:<?= $size ?>px;height:<?= $size ?>px;background:<?= $bubble['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:1.3em;font-weight:bold;color:#222;">
                                        <?= $bubble['count'] ?>
                                    </div>
                                    <div style="margin-top: 8px; color: #222; font-weight: 600; font-size: 1em; text-align: center;"><?= htmlspecialchars($bubble['name']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="bubble-legend" style="margin-top: 18px;">
                            <?php foreach ($departments_bubble as $bubble): ?>
                                <span class="bubble-legend-item" style="color:#222;">
                                    <span class="bubble-legend-color" style="background:<?= $bubble['color'] ?>;margin-right:4px;"></span>
                                    <?= htmlspecialchars($bubble['name']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- ORIGINAL ADMIN DASHBOARD (unchanged, all features present) -->
            <?php
            // Total Employees (role = 'Employee')
                $employee_count = 0;
            $sql = "SELECT COUNT(*) as total FROM user_account ua JOIN role r ON ua.role_id = r.role_id WHERE r.name = 'Employee'";
                $result = mysqli_query($conn, $sql);
                if ($result && $row = mysqli_fetch_assoc($result)) {
                    $employee_count = (int)$row['total'];
                }
            
            // Total Departments
            $department_count = 0;
            $sql = "SELECT COUNT(*) as total FROM department";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $department_count = (int)$row['total'];
            }

            // Resignations this month (status = 'approved')
            $resignation_count = 0;
            $sql = "SELECT COUNT(*) as total FROM resignation WHERE status = 'approved' AND MONTH(submitted_at) = MONTH(CURDATE()) AND YEAR(submitted_at) = YEAR(CURDATE())";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $resignation_count = (int)$row['total'];
            }

            // Recent Admin Notices (limit 10)
            $admin_notices = [];
            $notice_query = "SELECT an.title, an.message, an.created_at, ua.full_name as sender_name FROM admin_notice an LEFT JOIN user_account ua ON an.sender_id = ua.user_id ORDER BY an.created_at DESC LIMIT 10";
            $notice_result = mysqli_query($conn, $notice_query);
            if ($notice_result) {
                while ($row = mysqli_fetch_assoc($notice_result)) {
                    $admin_notices[] = $row;
                }
            }

            // Employees Overview by Department
            $employees_overview = [];
            $employees_query = "SELECT d.name as department, COUNT(ua.user_id) as count FROM user_account ua JOIN department d ON ua.department_id = d.department_id JOIN role r ON ua.role_id = r.role_id WHERE r.name = 'Employee' GROUP BY d.name";
            $employees_result = mysqli_query($conn, $employees_query);
            if ($employees_result) {
                while ($row = mysqli_fetch_assoc($employees_result)) {
                    $employees_overview[] = $row;
                }
            }

            // Total Employees (again, for Employees Overview)
            $total_employees = 0;
            $sql = "SELECT COUNT(*) as total FROM user_account ua JOIN role r ON ua.role_id = r.role_id WHERE r.name = 'Employee'";
            $result = mysqli_query($conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $total_employees = (int)$row['total'];
            }

            // Recent Activity Logs (limit 10)
            $activity_logs = [];
            $activity_query = "SELECT al.*, ua.full_name AS user_name FROM activity_log al LEFT JOIN user_account ua ON al.user_id = ua.user_id ORDER BY al.created_at DESC LIMIT 10";
            $activity_result = mysqli_query($conn, $activity_query);
            if ($activity_result) {
                while ($row = mysqli_fetch_assoc($activity_result)) {
                    $activity_logs[] = $row;
                }
            }
            ?>
            <div id="dashboardContent" style="display: block;">
                <h3 class="dashboard-title mb-5">Welcome to the Dashboard</h3>
                <!-- Dashboard Summary Cards (Fully Centered) -->
                <div style="display: flex; justify-content: center; align-items: center; width: 100%;">
                    <div class="d-flex mb-5" style="gap: 24px; width: 95%; justify-content: center; align-items: center; text-align: center;">
                        <!-- Employees Card -->
                        <div style="flex:1; max-width: 330px; min-width: 270px; display: flex; justify-content: center;">
                            <div style="background: #e5d6d6; border-radius: 18px; padding: 28px 0 22px 0; width: 100%; height: 170px; display: flex; align-items: center; gap: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); justify-content: center;">
                                <span style="font-size: 3rem; color: #111; margin-right: 8px;">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <div>
                                    <div style="font-size: 2.2rem; font-weight: bold; color: #111; line-height: 1;"><?= $employee_count ?></div>
                                    <div style="font-size: 1rem; color: #111; font-weight: 700; margin-top: 2px;">Total Number of<br>Employees</div>
                                </div>
                            </div>
                        </div>
                        <!-- Departments Card -->
                        <div style="flex:1; max-width: 330px; min-width: 270px; display: flex; justify-content: center;">
                            <div style="background: #8B0000; border-radius: 18px; padding: 28px 0 22px 0; width: 100%; height: 170px; display: flex; align-items: center; gap: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); justify-content: center;">
                                <span style="font-size: 3rem; color: #fff; margin-right: 8px;">
                                    <i class="fa-solid fa-building"></i>
                                </span>
                                <div>
                                    <div style="font-size: 2.2rem; font-weight: bold; color: #fff; line-height: 1;"><?= $department_count ?></div>
                                    <div style="font-size: 1rem; color: #fff; font-weight: 700; margin-top: 2px;">Departments</div>
                                </div>
                            </div>
                        </div>
                        <!-- Resignation Card -->
                        <div style="flex:1; max-width: 330px; min-width: 270px; display: flex; justify-content: center;">
                            <div style="background: #B22222; border-radius: 18px; padding: 28px 0 22px 0; width: 100%; height: 170px; display: flex; align-items: center; gap: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); justify-content: center;">
                                <span style="font-size: 3rem; color: #fff; margin-right: 8px;">
                                    <i class="fa-solid fa-user-slash"></i>
                                </span>
                                <div>
                                    <div style="font-size: 2.2rem; font-weight: bold; color: #fff; line-height: 1;"><?= $resignation_count ?></div>
                                    <div style="font-size: 1rem; color: #fff; font-weight: 700; margin-top: 2px;">Resignation<br>this Month</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <style>
                    .dashboard-card-row {
                        justify-content: center;
                    }
                    .dashboard-card-col {
                        flex: 0 0 220px;
                        max-width: 220px;
                        min-width: 220px;
                        margin-bottom: 24px;
                        display: flex;
                        align-items: stretch;
                    }
                    .dashboard-card {
                        background: #fff;
                        border-radius: 16px;
                        padding: 24px 0 18px 0;
                        text-align: center;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
                        width: 100%;
                        height: 170px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                    }
                    .dashboard-card .dashboard-card-icon {
                        font-size: 2.5rem;
                        color: #b30000;
                        margin-bottom: 6px;
                    }
                    .dashboard-card .dashboard-card-value {
                        font-size: 2.2rem;
                        font-weight: bold;
                        color: #b30000;
                    }
                    .dashboard-card .dashboard-card-label {
                        font-size: 1.1rem;
                        color: #b30000;
                        font-weight: 600;
                        margin-top: 2px;
                    }
                </style>
                <div class="container mt-4">
                    <div class="row dashboard-card-row">
                        <!-- Present Today -->
                        <div class="col-md-2 dashboard-card-col">
                            <div class="dashboard-card" style="background: #f8cccc;">
                                <div class="dashboard-card-icon">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div class="dashboard-card-value"><?= (int)($attendance_summary['present'] ?? 0) ?></div>
                                <div class="dashboard-card-label">Present Today</div>
                            </div>
                        </div>
                        <!-- Absent Today -->
                        <div class="col-md-2 dashboard-card-col">
                            <div class="dashboard-card" style="background: #f3a6a6;">
                                <div class="dashboard-card-icon">
                                    <i class="fa-solid fa-user-slash"></i>
                                </div>
                                <div class="dashboard-card-value"><?= (int)($attendance_summary['absent'] ?? 0) ?></div>
                                <div class="dashboard-card-label">Absent Today</div>
                            </div>
                        </div>
                        <!-- Late Today -->
                        <div class="col-md-2 dashboard-card-col">
                            <div class="dashboard-card" style="background: #ffe5e5;">
                                <div class="dashboard-card-icon">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div class="dashboard-card-value"><?= (int)($attendance_summary['late'] ?? 0) ?></div>
                                <div class="dashboard-card-label">Late Today</div>
                            </div>
                        </div>
                        <!-- Pending Mod. Request -->
                        <div class="col-md-2 dashboard-card-col">
                            <div class="dashboard-card" style="background: #ffeaea;">
                                <div class="dashboard-card-icon">
                                    <i class="fa-solid fa-hourglass-half"></i>
                                </div>
                                <div class="dashboard-card-value">
                                    <?php
                                        $pending_mod_query = "SELECT COUNT(*) as cnt FROM attendance_modification WHERE status = 'pending'";
                                        $pending_mod_result = mysqli_query($conn, $pending_mod_query);
                                        $pending_mod_count = 0;
                                        if ($pending_mod_result && $row = mysqli_fetch_assoc($pending_mod_result)) {
                                            $pending_mod_count = (int)$row['cnt'];
                                        }
                                        echo $pending_mod_count;
                                    ?>
                                </div>
                                <div class="dashboard-card-label">Pending Mod. Request</div>
                            </div>
                        </div>
                    </div>
                    <div class="row dashboard-card-row">
                        <!-- Leave Requests -->
                        <div class="col-md-2 dashboard-card-col">
                            <div class="dashboard-card" style="background: #ffeaea;">
                                <div class="dashboard-card-icon">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </div>
                                <div class="dashboard-card-value">
                                    <?php
                                        $leave_req_query = "SELECT COUNT(*) as cnt FROM leave_application WHERE status = 'pending'";
                                        $leave_req_result = mysqli_query($conn, $leave_req_query);
                                        $leave_req_count = 0;
                                        if ($leave_req_result && $row = mysqli_fetch_assoc($leave_req_result)) {
                                            $leave_req_count = (int)$row['cnt'];
                                        }
                                        echo $leave_req_count;
                                    ?>
                                </div>
                                <div class="dashboard-card-label">Leave Requests</div>
                            </div>
                        </div>
                        <!-- Approved Leave this Month -->
                        <div class="col-md-2 dashboard-card-col">
                            <div class="dashboard-card" style="background: #fff2d6;">
                                <div class="dashboard-card-icon">
                                    <i class="fa-solid fa-file-circle-check"></i>
                                </div>
                                <div class="dashboard-card-value">
                                    <?php
                                        $approved_leave_query = "SELECT COUNT(*) as cnt FROM leave_request WHERE status = 'approved' AND MONTH(requested_at) = MONTH(CURDATE()) AND YEAR(requested_at) = YEAR(CURDATE())";
                                        $approved_leave_result = mysqli_query($conn, $approved_leave_query);
                                        $approved_leave_count = 0;
                                        if ($approved_leave_result && $row = mysqli_fetch_assoc($approved_leave_result)) {
                                            $approved_leave_count = (int)$row['cnt'];
                                        }
                                        echo $approved_leave_count;
                                    ?>
                                </div>
                                <div class="dashboard-card-label">Approved Leave<br>this Month</div>
                            </div>
                        </div>
                        <!-- Resignation Requests -->
                        <div class="col-md-2 dashboard-card-col">
                            <div class="dashboard-card" style="background: #f3a6a6;">
                                <div class="dashboard-card-icon">
                                    <i class="fa-solid fa-folder"></i>
                                </div>
                                <div class="dashboard-card-value">
                                    <?php
                                        $resignation_req_query = "SELECT COUNT(*) as cnt FROM resignation WHERE status = 'pending'";
                                        $resignation_req_result = mysqli_query($conn, $resignation_req_query);
                                        $resignation_req_count = 0;
                                        if ($resignation_req_result && $row = mysqli_fetch_assoc($resignation_req_result)) {
                                            $resignation_req_count = (int)$row['cnt'];
                                        }
                                        echo $resignation_req_count;
                                    ?>
                                </div>
                                <div class="dashboard-card-label">Resignation Requests</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($show === 'attendance'): ?>
        <div id="attendanceContainer" style="display: block;">
            <h3>Employee's Attendance Record</h3>
            <div class="filter-section d-flex align-items-center mb-3 justify-content-between">
                <form method="get" class="d-flex align-items-center" style="gap: 8px; width:100%;">
                    <input type="hidden" name="show" value="attendance">
                    <input type="text" name="attendance_search" value="<?= htmlspecialchars($_GET['attendance_search'] ?? '') ?>" placeholder="Search by name..." class="form-control" style="max-width: 220px;">
                    <input type="date" name="attendance_date" value="<?= htmlspecialchars($_GET['attendance_date'] ?? '') ?>" class="form-control" style="max-width: 170px;">
                    <select name="attendance_status" class="form-control" style="max-width: 160px;">
                        <option value="">All Status</option>
                        <option value="present" <?= (($_GET['attendance_status'] ?? '') === 'present') ? 'selected' : '' ?>>Present</option>
                        <option value="absent" <?= (($_GET['attendance_status'] ?? '') === 'absent') ? 'selected' : '' ?>>Absent</option>
                        <option value="leave" <?= (($_GET['attendance_status'] ?? '') === 'leave') ? 'selected' : '' ?>>Leave</option>
                        <option value="late" <?= (($_GET['attendance_status'] ?? '') === 'late') ? 'selected' : '' ?>>Late</option>
                    </select>
                    <select name="attendance_department" class="form-control" style="max-width: 160px;">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= htmlspecialchars($department['department_id']) ?>" <?= (($_GET['attendance_department'] ?? '') == $department['department_id']) ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?show=attendance" class="btn btn-secondary" title="Reset"><i class="fas fa-sync-alt"></i></a>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name(s)</th>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $limit = 10;
                    $page = isset($_GET['attendance_page']) ? (int)$_GET['attendance_page'] : 1;
                    $offset = ($page - 1) * $limit;
                    $attendance_search = $_GET['attendance_search'] ?? '';
                    $attendance_status = $_GET['attendance_status'] ?? '';
                    $attendance_department = $_GET['attendance_department'] ?? '';
                    $where = "1=1";
                    if ($attendance_search !== '') {
                        $esc = mysqli_real_escape_string($conn, $attendance_search);
                        $where .= " AND (ua.full_name LIKE '%$esc%')";
                    }
                    if ($attendance_status !== '') {
                        $esc = mysqli_real_escape_string($conn, $attendance_status);
                        $where .= " AND a.status = '$esc'";
                    }
                    if ($attendance_department !== '') {
                        $esc = mysqli_real_escape_string($conn, $attendance_department);
                        $where .= " AND ua.department_id = '$esc'";
                    }
                    // Subquery to get only the latest attendance per employee
                    $attendance_query = "SELECT a.*, ua.full_name, d.name AS department_name, jr.title AS job_title, SUM(TIMESTAMPDIFF(HOUR, a.check_in, a.check_out)) as total_hours
                        FROM attendance a
                        JOIN user_account ua ON a.employee_id = ua.user_id
                        LEFT JOIN department d ON ua.department_id = d.department_id
                        LEFT JOIN job_role jr ON ua.job_role_id = jr.job_role_id
                        INNER JOIN (
                            SELECT employee_id, MAX(date) AS max_date
                            FROM attendance
                            GROUP BY employee_id
                        ) latest ON a.employee_id = latest.employee_id AND a.date = latest.max_date
                        WHERE $where
                        GROUP BY a.employee_id
                        ORDER BY a.date DESC
                        LIMIT $limit OFFSET $offset";
                    $attendance_result = mysqli_query($conn, $attendance_query);
                    if ($attendance_result && mysqli_num_rows($attendance_result) > 0) {
                        while ($row = mysqli_fetch_assoc($attendance_result)) {
                            $status = ucfirst($row['status']);
                            if ($status === 'Present' && $row['check_in'] && strtotime($row['check_in']) > strtotime('09:15:00')) {
                                $status = 'Late';
                            }
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                            echo "<td>" . ($row['check_in'] ? date('g:i A', strtotime($row['check_in'])) : '-') . "</td>";
                            echo "<td>" . ($row['check_out'] ? date('g:i A', strtotime($row['check_out'])) : '-') . "</td>";
                            echo "<td>" . htmlspecialchars($status) . "</td>";
                            echo '<td>';
                            echo '<button type="button" class="btn btn-danger btn-sm view-attendance-modal" 
                                data-employee-name="' . htmlspecialchars($row['full_name']) . '"
                                data-job-title="' . htmlspecialchars($row['job_title'] ?? '-') . '"
                                data-department="' . htmlspecialchars($row['department_name'] ?? '-') . '"
                                data-total-hours="' . htmlspecialchars($row['total_hours'] ?? '0') . '"
                                data-date="' . htmlspecialchars($row['date']) . '"
                                data-time-in="' . ($row['check_in'] ? date('g:i A', strtotime($row['check_in'])) : '-') . '"
                                data-time-out="' . ($row['check_out'] ? date('g:i A', strtotime($row['check_out'])) : '-') . '"
                                data-status="' . htmlspecialchars($status) . '">View</button>';
                            echo '</td>';
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center">No attendance records found.</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <!-- Modal for Attendance Record (Read-only, styled) -->
            <div class="modal fade" id="attendanceRecordModal" tabindex="-1" role="dialog" aria-labelledby="attendanceRecordModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content" style="border-radius:18px;background:#f6f6f6;box-shadow:0 2px 16px rgba(0,0,0,0.10);">
                        <div class="modal-header" style="border-bottom:none;">
                            <h5 class="modal-title w-100 text-center" id="attendanceRecordModalLabel" style="font-weight:bold;">Attendance Record</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position:absolute;right:18px;top:18px;font-size:1.5rem;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="padding:28px 32px 32px 32px;">
                            <div class="row mb-2">
                                <div class="col-7">
                                    <div><b>Name:</b> <span id="modalEmployeeName"></span></div>
                                    <div><b>Job Title:</b> <span id="modalJobTitle"></span></div>
                                    <div><b>Department:</b> <span id="modalDepartment"></span></div>
                                </div>
                                <div class="col-5 text-right">
                                    <div><b>Total Working Hours:</b></div>
                                    <div><span id="modalTotalHours"></span> hours</div>
                                </div>
                            </div>
                            <hr style="margin:12px 0 18px 0;">
                            <div style="background:#fff;border-radius:12px;padding:18px 18px 8px 18px;">
                                <table class="table mb-0" style="background:transparent;">
                                    <thead>
                                        <tr style="background:#f6f6f6;">
                                            <th>Date</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr style="background:#f8d7da;">
                                            <td id="modalDate"></td>
                                            <td id="modalTimeIn"></td>
                                            <td id="modalTimeOut"></td>
                                            <td id="modalStatus"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.view-attendance-modal').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        document.getElementById('modalEmployeeName').textContent = this.dataset.employeeName;
                        document.getElementById('modalJobTitle').textContent = this.dataset.jobTitle;
                        document.getElementById('modalDepartment').textContent = this.dataset.department;
                        document.getElementById('modalTotalHours').textContent = this.dataset.totalHours;
                        document.getElementById('modalDate').textContent = this.dataset.date;
                        document.getElementById('modalTimeIn').textContent = this.dataset.timeIn;
                        document.getElementById('modalTimeOut').textContent = this.dataset.timeOut;
                        document.getElementById('modalStatus').textContent = this.dataset.status;
                        $('#attendanceRecordModal').modal('show');
                    });
                });
            });
            </script>
        </div>
    <?php endif; ?>

    <?php if ($show === 'leave'): ?>
        <div id="leaveContainer" style="display: block;">
            <h3>Employee Leave Records</h3>
            <div class="filter-section d-flex align-items-center mb-3 justify-content-between">
                <form method="get" class="d-flex align-items-center" style="gap: 8px; width:100%;">
                    <input type="hidden" name="show" value="leave">
                    <input type="text" name="leave_search" value="<?= htmlspecialchars($_GET['leave_search'] ?? '') ?>" placeholder="Search by name..." class="form-control" style="max-width: 220px;">
                    <input type="date" name="leave_date" value="<?= htmlspecialchars($_GET['leave_date'] ?? '') ?>" class="form-control" style="max-width: 170px;">
                    <select name="leave_status" class="form-control" style="max-width: 160px;">
                        <option value="">All Status</option>
                        <option value="pending" <?= (($_GET['leave_status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= (($_GET['leave_status'] ?? '') === 'approved') ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= (($_GET['leave_status'] ?? '') === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                    </select>
                    <select name="leave_department" class="form-control" style="max-width: 160px;">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= htmlspecialchars($department['department_id']) ?>" <?= (($_GET['leave_department'] ?? '') == $department['department_id']) ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?show=leave" class="btn btn-secondary" title="Reset"><i class="fas fa-sync-alt"></i></a>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name(s)</th>
                            <th>Department</th>
                            <th>Job Title</th>
                            <th>Leave Date</th>
                            <th>Resumption Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $limit = 10;
                    $page = isset($_GET['leave_page']) ? (int)$_GET['leave_page'] : 1;
                    $offset = ($page - 1) * $limit;
                    $leave_search = $_GET['leave_search'] ?? '';
                    $leave_date = $_GET['leave_date'] ?? '';
                    $leave_status = $_GET['leave_status'] ?? '';
                    $leave_department = $_GET['leave_department'] ?? '';
                    $where = "1=1";
                    if ($leave_search !== '') {
                        $esc = mysqli_real_escape_string($conn, $leave_search);
                        $where .= " AND (ua.full_name LIKE '%$esc%')";
                    }
                    if ($leave_date !== '') {
                        $esc = mysqli_real_escape_string($conn, $leave_date);
                        $where .= " AND (la.start_date <= '$esc' AND la.end_date >= '$esc')";
                    }
                    if ($leave_status !== '') {
                        $esc = mysqli_real_escape_string($conn, $leave_status);
                        $where .= " AND la.status = '$esc'";
                    }
                    if ($leave_department !== '') {
                        $esc = mysqli_real_escape_string($conn, $leave_department);
                        $where .= " AND ua.department_id = '$esc'";
                    }
                    $leave_query = "SELECT la.*, ua.full_name, d.name AS department_name, jr.title AS job_title,
                        (SELECT SUM(duration) FROM leave_application WHERE employee_id = la.employee_id) as total_leaves,
                        (SELECT SUM(TIMESTAMPDIFF(HOUR, a.check_in, a.check_out)) FROM attendance a WHERE a.employee_id = la.employee_id) as total_hours
                        FROM leave_application la
                        JOIN user_account ua ON la.employee_id = ua.user_id
                        LEFT JOIN department d ON ua.department_id = d.department_id
                        LEFT JOIN job_role jr ON ua.job_role_id = jr.job_role_id
                        WHERE $where
                        ORDER BY la.applied_at DESC
                        LIMIT $limit OFFSET $offset";
                    $leave_result = mysqli_query($conn, $leave_query);
                    if ($leave_result && mysqli_num_rows($leave_result) > 0) {
                        while ($row = mysqli_fetch_assoc($leave_result)) {
                            $leave_dates = date('m/d/Y', strtotime($row['start_date'])) . ' - ' . date('m/d/Y', strtotime($row['end_date']));
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['department_name'] ?? '-') . "</td>";
                            echo "<td>" . htmlspecialchars($row['job_title'] ?? '-') . "</td>";
                            echo "<td>" . htmlspecialchars($leave_dates) . "</td>";
                            echo "<td>" . htmlspecialchars($row['resumption_date']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['leave_type']) . "</td>";
                            echo "<td>" . ucfirst($row['status']) . "</td>";
                            echo '<td>';
                            echo '<button type="button" class="btn btn-danger btn-sm view-leave-modal" 
                                data-employee-name="' . htmlspecialchars($row['full_name']) . '"
                                data-job-title="' . htmlspecialchars($row['job_title'] ?? '-') . '"
                                data-department="' . htmlspecialchars($row['department_name'] ?? '-') . '"
                                data-total-hours="' . htmlspecialchars($row['total_hours'] ?? '0') . '"
                                data-total-leaves="' . htmlspecialchars($row['total_leaves'] ?? '0') . '"
                                data-leave-dates="' . htmlspecialchars($leave_dates) . '"
                                data-resumption-date="' . htmlspecialchars($row['resumption_date']) . '"
                                data-type="' . htmlspecialchars($row['leave_type']) . '"
                                data-status="' . ucfirst($row['status']) . '">View</button>';
                            echo '</td>';
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center">No leave records found.</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <!-- Modal for Leave Record (Read-only, styled) -->
            <div class="modal fade" id="leaveRecordModal" tabindex="-1" role="dialog" aria-labelledby="leaveRecordModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content" style="border-radius:18px;background:#f6f6f6;box-shadow:0 2px 16px rgba(0,0,0,0.10);">
                        <div class="modal-header" style="border-bottom:none;">
                            <h5 class="modal-title w-100 text-center" id="leaveRecordModalLabel" style="font-weight:bold;">Leave Record</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position:absolute;right:18px;top:18px;font-size:1.5rem;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="padding:28px 32px 32px 32px;">
                            <div class="row mb-2">
                                <div class="col-7">
                                    <div><b>Name:</b> <span id="leaveModalEmployeeName"></span></div>
                                    <div><b>Job Title:</b> <span id="leaveModalJobTitle"></span></div>
                                    <div><b>Department:</b> <span id="leaveModalDepartment"></span></div>
                                </div>
                                <div class="col-5 text-right">
                                    <div><b>Total Working Hours:</b></div>
                                    <div><span id="leaveModalTotalHours"></span> hours</div>
                                    <div><b>Total Number of Leave:</b></div>
                                    <div><span id="leaveModalTotalLeaves"></span></div>
                                </div>
                            </div>
                            <hr style="margin:12px 0 18px 0;">
                            <div style="background:#fff;border-radius:12px;padding:18px 18px 8px 18px;">
                                <table class="table mb-0" style="background:transparent;">
                                    <thead>
                                        <tr style="background:#f6f6f6;">
                                            <th>Leave Date</th>
                                            <th>Resumption Date</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr style="background:#f8d7da;">
                                            <td id="leaveModalLeaveDates"></td>
                                            <td id="leaveModalResumptionDate"></td>
                                            <td id="leaveModalType"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.view-leave-modal').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        document.getElementById('leaveModalEmployeeName').textContent = this.dataset.employeeName;
                        document.getElementById('leaveModalJobTitle').textContent = this.dataset.jobTitle;
                        document.getElementById('leaveModalDepartment').textContent = this.dataset.department;
                        document.getElementById('leaveModalTotalHours').textContent = this.dataset.totalHours;
                        document.getElementById('leaveModalTotalLeaves').textContent = this.dataset.totalLeaves;
                        document.getElementById('leaveModalLeaveDates').textContent = this.dataset.leaveDates;
                        document.getElementById('leaveModalResumptionDate').textContent = this.dataset.resumptionDate;
                        document.getElementById('leaveModalType').textContent = this.dataset.type;
                        $('#leaveRecordModal').modal('show');
                    });
                });
            });
            </script>
        </div>
    <?php endif; ?>

    <?php if ($show === 'resignation'): ?>
        <div id="resignationContainer" style="display: block;">
            <h3>Employee Resignation Requests</h3>
            <?php
            // Handle resignation status update
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_resignation_status'])) {
                $resignation_id = intval($_POST['resignation_id']);
                $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
                
                // Get resignation details before update
                $resignation_details_query = "SELECT r.*, ua.full_name FROM resignation r 
                                            JOIN user_account ua ON r.employee_id = ua.user_id 
                                            WHERE r.resignation_id = $resignation_id";
                $resignation_details_result = mysqli_query($conn, $resignation_details_query);
                $resignation_details = mysqli_fetch_assoc($resignation_details_result);
                
                $update_query = "UPDATE resignation SET status = '$new_status' WHERE resignation_id = $resignation_id";
                if (mysqli_query($conn, $update_query)) {
                    // Log the activity
                    $details = "Updated resignation status for " . $resignation_details['full_name'] . 
                              " from " . $resignation_details['status'] . " to " . $new_status;
                    log_activity($conn, $_SESSION['user_id'], 'Resignation Management', 'update_status', 
                                'resignation', $resignation_id, $details);
                }
            }
            ?>
            <div class="filter-section d-flex align-items-center mb-3 justify-content-between">
                <form method="get" class="d-flex align-items-center" style="gap: 8px; width:100%;">
                    <input type="hidden" name="show" value="resignation">
                    <input type="text" name="resignation_search" value="<?= htmlspecialchars($_GET['resignation_search'] ?? '') ?>" placeholder="Search by name..." class="form-control" style="max-width: 220px;">
                    <select name="resignation_status" class="form-control" style="max-width: 160px;">
                        <option value="">All Status</option>
                        <option value="pending" <?= (($_GET['resignation_status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= (($_GET['resignation_status'] ?? '') === 'approved') ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= (($_GET['resignation_status'] ?? '') === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                    </select>
                    <select name="resignation_department" class="form-control" style="max-width: 160px;">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= htmlspecialchars($department['department_id']) ?>" <?= (($_GET['resignation_department'] ?? '') == $department['department_id']) ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?show=resignation" class="btn btn-secondary" title="Reset"><i class="fas fa-sync-alt"></i></a>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name(s)</th>
                            <th>Department</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th>Action</th>
                                                </tr>
                    </thead>
                    <tbody>
                    <?php
                    $limit = 10;
                    $page = isset($_GET['resignation_page']) ? (int)$_GET['resignation_page'] : 1;
                    $offset = ($page - 1) * $limit;
                    $resignation_search = $_GET['resignation_search'] ?? '';
                    $resignation_status = $_GET['resignation_status'] ?? '';
                    $resignation_department = $_GET['resignation_department'] ?? '';
                    $where = "1=1";
                    if ($resignation_search !== '') {
                        $esc = mysqli_real_escape_string($conn, $resignation_search);
                        $where .= " AND (ua.full_name LIKE '%$esc%')";
                    }
                    if ($resignation_status !== '') {
                        $esc = mysqli_real_escape_string($conn, $resignation_status);
                        $where .= " AND r.status = '$esc'";
                    }
                    if ($resignation_department !== '') {
                        $esc = mysqli_real_escape_string($conn, $resignation_department);
                        $where .= " AND ua.department_id = '$esc'";
                    }
                    $resignation_query = "SELECT r.*, ua.full_name, d.name AS department_name FROM resignation r JOIN user_account ua ON r.employee_id = ua.user_id LEFT JOIN department d ON ua.department_id = d.department_id WHERE $where ORDER BY r.submitted_at DESC, r.resignation_id DESC LIMIT $limit OFFSET $offset";
                    $resignation_result = mysqli_query($conn, $resignation_query);
                    if ($resignation_result && mysqli_num_rows($resignation_result) > 0) {
                        while ($row = mysqli_fetch_assoc($resignation_result)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['department_name'] ?? '-') . "</td>";
                            echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
                            echo "<td>" . ucfirst($row['status']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['submitted_at']) . "</td>";
                            echo '<td>';
                            echo '<form method="POST" style="display:inline;">';
                            echo '<input type="hidden" name="resignation_id" value="' . htmlspecialchars($row['resignation_id']) . '">';
                            echo '<select name="new_status" class="form-control form-control-sm d-inline-block" style="width:auto;display:inline-block;">';
                            foreach (["pending", "approved", "rejected"] as $statusOpt) {
                                $selected = ($row['status'] === $statusOpt) ? 'selected' : '';
                                echo '<option value="' . $statusOpt . '" ' . $selected . '>' . ucfirst($statusOpt) . '</option>';
                            }
                            echo '</select> ';
                            echo '<button type="submit" name="update_resignation_status" class="btn btn-primary btn-sm">Update</button>';
                            echo '</form>';
                            echo '</td>';
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td colspan="12" class="text-center">No resignation requests found.</td></tr>';
                    }
                    // Pagination
                    $count_query = "SELECT COUNT(*) as total FROM resignation r JOIN user_account ua ON r.employee_id = ua.user_id LEFT JOIN department d ON ua.department_id = d.department_id WHERE $where";
                    $count_result = mysqli_query($conn, $count_query);
                    $total = 0;
                    if ($count_result) {
                        $row = mysqli_fetch_assoc($count_result);
                        $total = $row['total'];
                    }
                    $totalPages = ceil($total / $limit);
                    ?>
                    </tbody>
                </table>
            </div>
            <nav aria-label="Resignation page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php if($page <= 1){ echo 'disabled'; } ?>">
                        <a class="page-link" href="<?php
                            $params = $_GET;
                            $params['resignation_page'] = $page - 1;
                            echo ($page > 1) ? '?' . http_build_query($params) : '#';
                        ?>">Previous</a>
                    </li>
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php if($page == $i){ echo 'active'; } ?>">
                            <a class="page-link" href="<?php
                                $params = $_GET;
                                $params['resignation_page'] = $i;
                                echo '?' . http_build_query($params);
                            ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php if($page >= $totalPages){ echo 'disabled'; } ?>">
                        <a class="page-link" href="<?php
                            $params = $_GET;
                            $params['resignation_page'] = $page + 1;
                            echo ($page < $totalPages) ? '?' . http_build_query($params) : '#';
                        ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>

    <?php if ($show === 'notification'): ?>
        <style>
            .notification-card {
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 2px 12px rgba(0,0,0,0.08);
                padding: 0;
                max-width: 700px;
                margin: 32px auto 0 auto;
                border: none;
            }
            .notification-card-header {
                padding: 20px 28px 12px 28px;
                border-bottom: 1px solid #eee;
                font-size: 1.5rem;
                font-weight: bold;
                color: #b30000;
                background: #fff;
                border-radius: 16px 16px 0 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .compose-btn {
                background: #b30000;
                color: #fff;
                font-weight: 600;
                border: none;
                border-radius: 12px;
                padding: 8px 24px;
                font-size: 1.1rem;
                display: flex;
                align-items: center;
                gap: 8px;
                transition: background 0.18s;
            }
            .compose-btn i {
                font-size: 1.2rem;
            }
            .notification-list {
                padding: 0 28px 18px 28px;
            }
            .notification-item {
                border-bottom: 1px solid #f0f0f0;
                padding: 16px 0 8px 0;
            }
            .notification-item:last-child {
                border-bottom: none;
            }
            .notification-title {
                font-weight: 600;
                color: #222;
            }
            .notification-meta {
                font-size: 0.95rem;
                color: #888;
            }
            .notification-content {
                color: #444;
                margin: 4px 0 0 0;
            }
            .urgent {
                color: #b30000;
                font-weight: bold;
            }
            .modal-content {
                border-radius: 18px;
            }
            .modal-header {
                border-bottom: none;
            }
            .modal-title {
                color: #b30000;
                font-weight: bold;
            }
            .notif-success-message {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
                border-radius: 6px;
                padding: 12px 18px;
                margin-bottom: 18px;
                font-size: 1rem;
                font-weight: 500;
                text-align: left;
            }
        </style>
        <?php
        // Handle notification form submission
        $notif_error = '';
        $notif_success = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
            $notif_title = trim($_POST['title'] ?? '');
            $notif_type = trim($_POST['type'] ?? '');
            $notif_content = trim($_POST['content'] ?? '');
            $notif_departments = $_POST['departments'] ?? [];
            $notif_schedule = trim($_POST['schedule'] ?? '');
            $notif_sender_id = $_SESSION['user_id'] ?? null;

            if ($notif_title === '' || $notif_type === '' || $notif_content === '' || empty($notif_departments)) {
                $notif_error = "Please fill in all required fields and select at least one department.";
            } else {
                // Prepare departments as comma-separated string
                if (in_array('all', $notif_departments)) {
                    $departments_str = 'all';
                } else {
                    $departments_str = implode(',', array_map('intval', $notif_departments));
                }
                // Schedule: if empty, use NOW()
                $created_at = ($notif_schedule !== '') ? date('Y-m-d H:i:s', strtotime($notif_schedule)) : date('Y-m-d H:i:s');
                // Insert notification
                $stmt = mysqli_prepare($conn, "INSERT INTO notification (title, type, content, sender_id, created_at) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sssis", $notif_title, $notif_type, $notif_content, $notif_sender_id, $created_at);
                    $success = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    if ($success) {
                        // Log activity for sending notification
                        if (function_exists('log_activity')) {
                            log_activity(
                                $conn,
                                $notif_sender_id,
                                'Notification',
                                'send_notification',
                                'notification',
                                mysqli_insert_id($conn),
                                "Sent notification titled: $notif_title"
                            );
                        }
                        // Instead of header redirect, set a flag to show success message
                        $notif_success = true;
                    } else {
                        $notif_error = "Failed to send notification. Please try again.";
                    }
                } else {
                    $notif_error = "Database error. Please try again.";
                }
            }
        }
        ?>
        <div class="notification-card">
            <div class="notification-card-header">
                Notifications
                <button type="button" class="compose-btn" id="composeNotifBtn">
                    <i class="fas fa-edit"></i> Compose
                </button>
            </div>
            <?php if (!empty($notif_error)): ?>
                <div class="notif-success-message" style="background:#f8d7da;color:#721c24;border-color:#f5c6cb;margin-left:18px;margin-right:18px;">
                    <?= htmlspecialchars($notif_error) ?>
                </div>
            <?php endif; ?>
            <?php if ($notif_success || (isset($_GET['notif_success']) && $_GET['notif_success'] == '1')): ?>
                <div id="notifSuccessMsg" class="notif-success-message" style="margin-left:18px;margin-right:18px;">
                    Notification sent successfully!
                </div>
            <?php endif; ?>
            <div class="notification-list">
                <?php
                // Fetch recent notifications (limit 5)
                $recent_notif_res = mysqli_query($conn, "SELECT n.*, ua.full_name as sender_name FROM notification n LEFT JOIN user_account ua ON n.sender_id = ua.user_id ORDER BY n.created_at DESC LIMIT 5");
                $recent_notifications = [];
                if ($recent_notif_res) {
                    while ($row = mysqli_fetch_assoc($recent_notif_res)) {
                        $recent_notifications[] = $row;
                    }
                }
                if (empty($recent_notifications)) {
                    echo '<div class="text-center text-muted" style="padding: 32px 0;">No notifications available</div>';
                } else {
                    foreach ($recent_notifications as $notif) {
                        echo '<div class="notification-item">';
                        if (strtolower($notif['type']) === 'urgent' || stripos($notif['title'], 'urgent') !== false) {
                            echo '<div class="notification-title urgent">' . htmlspecialchars($notif['title']) . '</div>';
                        } else {
                            echo '<div class="notification-title">' . htmlspecialchars($notif['title']) . '</div>';
                        }
                        echo '<div class="notification-meta">';
                        echo date('M d, Y h:i A', strtotime($notif['created_at'])) . ' &bull; ' . htmlspecialchars($notif['type']);
                        if (!empty($notif['sender_name'])) {
                            echo ' &bull; ' . htmlspecialchars($notif['sender_name']);
                        }
                        echo '</div>';
                        echo '<div class="notification-content">' . nl2br(htmlspecialchars($notif['content'])) . '</div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>

        <!-- Compose Notification Modal -->
        <div class="modal fade" id="composeNotifModal" tabindex="-1" role="dialog" aria-labelledby="composeNotifModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="composeNotifModalLabel"><i class="fas fa-edit" style="margin-right: 8px;"></i>Compose Notification</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" autocomplete="off" id="notificationForm" action="?show=notification">
                            <div class="form-group mb-3">
                                <label for="notifTitle" style="color:#b30000;font-weight:600;">Notification Title</label>
                                <input type="text" class="form-control" id="notifTitle" name="title" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="notifType" style="color:#b30000;font-weight:600;">Notification Type</label>
                                <select class="form-control" id="notifType" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="Company-wide Announcements">Company-wide Announcements</option>
                                    <option value="Payroll and Compensation Notifications">Payroll and Compensation Notifications</option>
                                    <option value="Training and Development Notifications">Training and Development Notifications</option>
                                    <option value="Leave Request Status">Leave Request Status</option>
                                    <option value="Employee Management Notifications">Employee Management Notifications</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label for="notifContent" style="color:#b30000;font-weight:600;">Content</label>
                                <textarea class="form-control" id="notifContent" name="content" rows="3" required></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label style="color:#b30000;font-weight:600;">Recipient</label>
                                <div style="background:#f8f8f8;border-radius:8px;padding:8px 0 8px 0;border:1.5px solid #000;">
                                    <div class="form-check" style="padding-left:1.5em;">
                                        <input class="form-check-input" type="checkbox" id="allDepartments" name="departments[]" value="all">
                                        <label class="form-check-label" for="allDepartments" style="font-weight:600;">All Departments</label>
                                    </div>
                                    <?php foreach ($departments as $dept): ?>
                                        <div class="form-check" style="padding-left:1.5em;">
                                            <input class="form-check-input department-checkbox" type="checkbox" id="dept<?= $dept['department_id'] ?>" name="departments[]" value="<?= $dept['department_id'] ?>">
                                            <label class="form-check-label" for="dept<?= $dept['department_id'] ?>"> <?= htmlspecialchars($dept['name']) ?> </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="notifSchedule" style="color:#b30000;font-weight:600;">Schedule (Optional)</label>
                                <input type="datetime-local" class="form-control" id="notifSchedule" name="schedule">
                            </div>
                            <div class="d-flex justify-content-end mt-4" style="gap:12px;">
                                <button type="submit" class="btn" name="send_notification" style="background:#b30000;color:#fff;font-weight:600;min-width:100px;border-radius:8px;font-size:1.1rem;">Send</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="min-width:100px;border-radius:8px;font-size:1.1rem;">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Compose button opens modal
            var composeBtn = document.getElementById('composeNotifBtn');
            var composeModal = document.getElementById('composeNotifModal');
            if (composeBtn && composeModal) {
                composeBtn.addEventListener('click', function() {
                    $('#composeNotifModal').modal('show');
                });
            }
            // All Departments checkbox logic (inside modal)
            var allDepartments = document.getElementById('allDepartments');
            var deptCheckboxes = document.querySelectorAll('.department-checkbox');
            if (allDepartments) {
                allDepartments.addEventListener('change', function() {
                    deptCheckboxes.forEach(function(cb) {
                        cb.checked = allDepartments.checked;
                    });
                });
                deptCheckboxes.forEach(function(cb) {
                    cb.addEventListener('change', function() {
                        if (!cb.checked) allDepartments.checked = false;
                        else {
                            var allChecked = Array.from(deptCheckboxes).every(function(box) { return box.checked; });
                            if (allChecked) allDepartments.checked = true;
                        }
                    });
                });
            }
            // Hide notification success message after 3 seconds (only if present)
            var notifSuccessMsg = document.getElementById('notifSuccessMsg');
            if (notifSuccessMsg) {
                setTimeout(function() {
                    notifSuccessMsg.parentNode.removeChild(notifSuccessMsg);
                }, 3000);
            }
        });
        </script>
    <?php endif; ?>

    <?php if ($show === 'activityLogs'): ?>
        <div id="activityLogsContainer" style="display: block;">
            <h3>Activity Logs</h3>
            <div class="filter-section d-flex align-items-center mb-3 justify-content-between">
                <form id="activityLogsFilterForm" method="get" class="d-flex align-items-center" style="gap: 8px;">
                    <input type="hidden" name="show" value="activityLogs">
                    <input type="text" name="activity_search" value="<?= htmlspecialchars($activity_search) ?>" placeholder="Search logs..." class="form-control" style="max-width: 220px;">
                    <select name="activity_module" class="form-control" style="max-width: 160px;">
                        <option value="">All Modules</option>
                        <?php
                        $modules_res = mysqli_query($conn, "SELECT DISTINCT module FROM activity_log ORDER BY module ASC");
                        while ($mod = mysqli_fetch_assoc($modules_res)) {
                            $sel = ($activity_module === $mod['module']) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($mod['module']) . "\" $sel>" . htmlspecialchars($mod['module']) . "</option>";
                        }
                        ?>
                    </select>
                    <select name="activity_action" class="form-control" style="max-width: 160px;">
                        <option value="">All Actions</option>
                        <?php
                        $actions_res = mysqli_query($conn, "SELECT DISTINCT action FROM activity_log ORDER BY action ASC");
                        while ($act = mysqli_fetch_assoc($actions_res)) {
                            $sel = ($activity_action === $act['action']) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($act['action']) . "\" $sel>" . htmlspecialchars($act['action']) . "</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?show=activityLogs" class="btn btn-secondary" title="Reset" id="activityLogsResetBtn"><i class="fas fa-sync-alt"></i></a>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Target Type</th>
                            <th>Target ID</th>
                            <th>Details</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activity_logs)): ?>
                            <tr><td colspan="7" class="text-center">No activity logs found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($activity_logs as $log): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                                        <br>
                                        <small><?= htmlspecialchars($log['user_email'] ?? '') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($log['module']) ?></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><?= htmlspecialchars($log['target_type'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($log['target_id'] ?? '') ?></td>
                                    <td style="max-width: 320px; word-break: break-all;">
                                        <?php
                                        $details = $log['details'];
                                        if (strlen($details) > 120) {
                                            echo htmlspecialchars(substr($details, 0, 120)) . '...';
                                        } else {
                                            echo htmlspecialchars($details);
                                        }
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <nav aria-label="Activity log page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php if($activity_page <= 1){ echo 'disabled'; } ?>">
                        <a class="page-link" href="<?php
                            $params = $_GET;
                            $params['activity_page'] = $activity_page - 1;
                            echo ($activity_page > 1) ? '?' . http_build_query($params) : '#';
                        ?>">Previous</a>
                    </li>
                    <?php for($i = 1; $i <= $activity_total_pages; $i++): ?>
                        <li class="page-item <?php if($activity_page == $i){ echo 'active'; } ?>">
                            <a class="page-link" href="<?php
                                $params = $_GET;
                                $params['activity_page'] = $i;
                                echo '?' . http_build_query($params);
                            ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php if($activity_page >= $activity_total_pages){ echo 'disabled'; } ?>">
                        <a class="page-link" href="<?php
                            $params = $_GET;
                            $params['activity_page'] = $activity_page + 1;
                            echo ($activity_page < $activity_total_pages) ? '?' . http_build_query($params) : '#';
                        ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>

    <?php if ($show === 'createAccount'): ?>
        <div id="createAccountFormContainer" style="display: block;">
            <form class="create-account-form" id="createAccountForm" method="POST" autocomplete="off" action="?show=createAccount">
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
                    </select>
                </div>
                <button type="submit" class="btn btn-create" name="create_account">Create Account</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($show === 'changePassword'): ?>
        <div id="changePasswordFormContainer" style="display: block;">
            <form class="change-password-form" id="changePasswordForm" method="POST" autocomplete="off" action="?show=changePassword">
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
    <?php endif; ?>

    <?php if ($show === 'employeeList'): ?>
        <div id="employeeListContainer" style="display: block;">
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
                                    <form method='POST' style='display:inline;' action='?show=employeeList'>
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
                        <a class="page-link" href="<?php 
                            $params = $_GET;
                            $params['page'] = $page - 1;
                            echo ($page > 1) ? '?' . http_build_query($params) : '#';
                        ?>" data-page="<?php echo $page - 1; ?>">Previous</a>
                    </li>
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php if($page == $i){ echo 'active'; } ?>">
                            <a class="page-link" href="<?php 
                                $params = $_GET;
                                $params['page'] = $i;
                                echo '?' . http_build_query($params);
                            ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php if($page >= $totalPages){ echo 'disabled'; } ?>">
                        <a class="page-link" href="<?php 
                            $params = $_GET;
                            $params['page'] = $page + 1;
                            echo ($page < $totalPages) ? '?' . http_build_query($params) : '#';
                        ?>" data-page="<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>

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
                    <form id="editChangePasswordForm" method="POST" autocomplete="off" action='?show=employeeList'>
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
                        <div class="form-group">
                            <label for="editStatus">Status</label>
                            <select class="form-control" id="editStatus" name="status">
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="leave">Leave</option>
                                <option value="late">Late</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" name="edit_user">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for View/Edit Attendance -->
    <div class="modal fade" id="viewAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="viewAttendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewAttendanceModalLabel">View/Edit Attendance</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editAttendanceForm" method="POST" autocomplete="off">
                        <input type="hidden" id="editAttendanceId" name="attendance_id">
                        <div class="form-group">
                            <label for="viewEmployeeName">Employee Name</label>
                            <input type="text" class="form-control" id="viewEmployeeName" readonly>
                        </div>
                        <div class="form-group">
                            <label for="viewDepartment">Department</label>
                            <input type="text" class="form-control" id="viewDepartment" readonly>
                        </div>
                        <div class="form-group">
                            <label for="viewDate">Date</label>
                            <input type="text" class="form-control" id="viewDate" readonly>
                        </div>
                        <div class="form-group">
                            <label for="editTimeIn">Time In</label>
                            <input type="time" class="form-control" id="editTimeIn" name="time_in">
                        </div>
                        <div class="form-group">
                            <label for="viewTimeOut">Time Out</label>
                            <input type="text" class="form-control" id="viewTimeOut" readonly>
                        </div>
                        <div class="form-group">
                            <label for="editStatus">Status</label>
                            <select class="form-control" id="editStatus" name="status">
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="leave">Leave</option>
                                <option value="late">Late</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" name="update_attendance">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showContent(containerId) {
        // Brute-force hide all relevant containers
        const containers = [
            'dashboardContent',
            'attendanceContainer',
            'leaveContainer',
            'resignationContainer',
            'notificationContainer',
            'activityLogsContainer',
            'createAccountFormContainer',
            'changePasswordFormContainer',
            'employeeListContainer'
        ];
        containers.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        // Show the selected container
        const showEl = document.getElementById(containerId);
        if (showEl) showEl.style.display = 'block';
    }

    // Helper to get URL parameter
    function getQueryParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }

    // On page load, show the correct container based on ?show=...
    document.addEventListener('DOMContentLoaded', function() {
        const show = getQueryParam('show');
        const containers = [
            'dashboardContent',
            'attendanceContainer',
            'leaveContainer',
            'resignationContainer',
            'notificationContainer',
            'activityLogsContainer',
            'createAccountFormContainer',
            'changePasswordFormContainer',
            'employeeListContainer'
        ];
        containers.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });

        // Map ?show= values to container IDs
        const showMap = {
            'dashboard': 'dashboardContent',
            'attendance': 'attendanceContainer',
            'leave': 'leaveContainer',
            'resignation': 'resignationContainer',
            'notification': 'notificationContainer',
            'activityLogs': 'activityLogsContainer',
            'createAccount': 'createAccountFormContainer',
            'changePassword': 'changePasswordFormContainer',
            'employeeList': 'employeeListContainer'
        };

        // Default to dashboard if no ?show= param
        const toShow = showMap[show] || 'dashboardContent';
        const showEl = document.getElementById(toShow);
        if (showEl) showEl.style.display = 'block';
    });

    // --- FIX: Actually hide User Management when filtering/resetting Activity Logs ---
    document.addEventListener('DOMContentLoaded', function() {
        // Helper to hide User Management section if present
        function hideUserManagement() {
            // Try both possible containers
            const userManagement = document.getElementById('employeeListContainer');
            if (userManagement) userManagement.style.display = 'none';
        }

        // Hide User Management if we are on Activity Logs (on page load)
        const show = (new URLSearchParams(window.location.search)).get('show');
        if (show === 'activityLogs') {
            hideUserManagement();
        }

        // Also hide User Management immediately after filter/reset in Activity Logs (before reload)
        const activityLogsFilterForm = document.getElementById('activityLogsFilterForm');
        const activityLogsResetBtn = document.getElementById('activityLogsResetBtn');
        if (activityLogsFilterForm) {
            activityLogsFilterForm.addEventListener('submit', function(e) {
                hideUserManagement();
            });
        }
        if (activityLogsResetBtn) {
            activityLogsResetBtn.addEventListener('click', function(e) {
                hideUserManagement();
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        var employeeBtn = document.getElementById('employeeBtn');
        var employeeSubMenu = document.getElementById('employeeSubMenu');
        if (employeeBtn && employeeSubMenu) {
            employeeBtn.addEventListener('click', function() {
                if (employeeSubMenu.style.display === 'none' || employeeSubMenu.style.display === '') {
                    employeeSubMenu.style.display = 'block';
                } else {
                    employeeSubMenu.style.display = 'none';
                }
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // --- CREATE ACCOUNT FORM ---
        const departmentSelect = document.getElementById('department');
        const jobRoleSelect = document.getElementById('jobRole');
        if (departmentSelect && jobRoleSelect) {
            departmentSelect.addEventListener('change', function() {
                const departmentId = this.value;
                jobRoleSelect.innerHTML = '<option value="">Select Job Title</option>';
                if (departmentId) {
                    fetch('get_job_roles.php?department_id=' + departmentId)
                        .then(response => response.json())
                        .then(data => {
                            data.forEach(function(job) {
                                const option = document.createElement('option');
                                option.value = job.job_role_id;
                                option.textContent = job.title;
                                jobRoleSelect.appendChild(option);
                            });
                        });
                }
            });
        }

        // --- EDIT MODAL ---
        const editDepartmentSelect = document.getElementById('editDepartment');
        const editJobRoleSelect = document.getElementById('editJobRole');
        // Helper to load job roles for a department and select a job role
        function loadEditJobRoles(departmentId, selectedJobRoleId) {
            editJobRoleSelect.innerHTML = '<option value="">Select Job Title</option>';
            if (departmentId) {
                fetch('get_job_roles.php?department_id=' + departmentId)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(function(job) {
                            const option = document.createElement('option');
                            option.value = job.job_role_id;
                            option.textContent = job.title;
                            if (selectedJobRoleId && String(job.job_role_id) === String(selectedJobRoleId)) {
                                option.selected = true;
                            }
                            editJobRoleSelect.appendChild(option);
                        });
                    });
            }
        }
        // When department changes in the edit modal
        if (editDepartmentSelect && editJobRoleSelect) {
            editDepartmentSelect.addEventListener('change', function() {
                loadEditJobRoles(this.value, null);
            });
        }
        // When opening the modal, set the job roles for the current department and select the current job role
        document.querySelectorAll('.edit-change-password-user').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const departmentId = this.getAttribute('data-department-id');
                const jobRoleId = this.getAttribute('data-job-role-id');
                // Set the department dropdown value
                if (editDepartmentSelect) editDepartmentSelect.value = departmentId;
                // Load job roles and select the current one
                loadEditJobRoles(departmentId, jobRoleId);
            });
        });
    });

    // Add this to your existing script section
    document.addEventListener('DOMContentLoaded', function() {
        // Handle view attendance button clicks
        document.querySelectorAll('.view-attendance').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const modal = document.getElementById('viewAttendanceModal');
                const form = document.getElementById('editAttendanceForm');
                
                // Set form values
                document.getElementById('editAttendanceId').value = this.dataset.attendanceId;
                document.getElementById('viewEmployeeName').value = this.dataset.employeeName;
                document.getElementById('viewDepartment').value = this.dataset.department;
                document.getElementById('viewDate').value = this.dataset.date;
                document.getElementById('editTimeIn').value = this.dataset.timeIn;
                document.getElementById('viewTimeOut').value = this.dataset.timeOut;
                document.getElementById('editStatus').value = this.dataset.status.toLowerCase(); // Set the status dropdown
                
                // Show modal
                $(modal).modal('show');
            });
        });
    });
</script>

<?php include 'adminFooter.php'; ?>