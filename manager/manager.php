<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../config/db.php';
if (!$conn) { die('<div style="color:red;font-weight:bold;">Database connection failed: ' . mysqli_connect_error() . '</div>'); }

$roles = [];
$sql = "SELECT role_id, name FROM role ORDER BY name ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $roles[] = $row;
    }
}

// DEBUG HELPER
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

// Fetch departments
$departments = [];
$sql = "SELECT department_id, name FROM department ORDER BY name ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $departments[] = $row;
    }
    if ($debug) echo '<div style="color:blue;">[DEBUG] Departments: ' . count($departments) . ' rows</div>';
} else if ($debug) {
    echo '<div style="color:red;">[DEBUG] Department SQL error: ' . mysqli_error($conn) . '</div>';
}

// Fetch job roles
$job_roles = [];
$sql = "SELECT job_role_id, title FROM job_role ORDER BY title ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $job_roles[] = $row;
    }
    if ($debug) echo '<div style="color:blue;">[DEBUG] Job Roles: ' . count($job_roles) . ' rows</div>';
} else if ($debug) {
    echo '<div style="color:red;">[DEBUG] Job Role SQL error: ' . mysqli_error($conn) . '</div>';
}

// Assume current user ID is available (replace with your session logic)
$current_user_id = $_SESSION['user_id'] ?? 1; // Example fallback to 1

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
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

// Handle employee rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_employee_rating'])) {
    $employee_id = intval($_POST['rate_employee_id']);
    $manager_id = $current_user_id; // Make sure this is set to the logged-in manager's user_id
    $rating = intval($_POST['employee_rating']);
    if ($employee_id && $manager_id && $rating >= 1 && $rating <= 5) {
        // Check if a rating already exists
        $check_query = "SELECT id FROM employee_rating WHERE employee_id = $employee_id AND manager_id = $manager_id";
        $check_result = mysqli_query($conn, $check_query);
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            // Update existing rating
            $update_query = "UPDATE employee_rating SET rating = $rating, created_at = NOW() WHERE employee_id = $employee_id AND manager_id = $manager_id";
            mysqli_query($conn, $update_query);
        } else {
            // Insert new rating
            $insert_query = "INSERT INTO employee_rating (employee_id, manager_id, rating, created_at) VALUES ($employee_id, $manager_id, $rating, NOW())";
            mysqli_query($conn, $insert_query);
        }
        // Optionally, show a success message
        echo '<script>setTimeout(function() { $("#rateEmployeeModal").modal("hide"); }, 500);</script>';
    }
}

// Fetch top-rated employees (top 3 by average rating)
$top_rated_employees = [];
$top_rated_query = "SELECT ua.full_name, AVG(er.rating) as avg_rating
    FROM employee_rating er
    JOIN user_account ua ON er.employee_id = ua.user_id
    WHERE ua.role_id = (SELECT role_id FROM role WHERE name = 'Employee')
    GROUP BY er.employee_id
    ORDER BY avg_rating DESC, ua.full_name ASC
    LIMIT 3";
$top_rated_result = mysqli_query($conn, $top_rated_query);
if ($top_rated_result) {
    while ($row = mysqli_fetch_assoc($top_rated_result)) {
        $top_rated_employees[] = $row;
    }
}
?>
<?php include 'managerHeader.php'; ?>

<div class="main-content" id="mainContentArea">
    <?php if ($show === 'dashboard'): ?>
        <div id="dashboardContent" style="display: block;">
            <div class="manager-dashboard-cards">
                <div class="manager-dashboard-card blue">
                    <span class="icon"><i class="fa-solid fa-user"></i></span>
                    <div>
                        <div class="stat"><?= isset($attendance_summary['present']) ? $attendance_summary['present'] : 0 ?></div>
                        <div class="desc">Total Employees Present Today</div>
                    </div>
                </div>
                <div class="manager-dashboard-card gray">
                    <span class="icon"><i class="fa-solid fa-book"></i></span>
                    <div>
                        <div class="stat"><?= isset($leave_requests['approved']) ? $leave_requests['approved'] : 0 ?></div>
                        <div class="desc">Total Employees on Leave Today</div>
                    </div>
                </div>
                <div class="manager-dashboard-card gray">
                    <span class="icon"><i class="fa-solid fa-dice"></i></span>
                    <div>
                        <div class="stat"><?= isset($resignations['pending']) ? $resignations['pending'] : 0 ?></div>
                        <div class="desc">Pending Resignation</div>
                    </div>
                </div>
                <div class="manager-dashboard-card blue">
                    <span class="icon"><i class="fa-solid fa-dice"></i></span>
                    <div>
                        <div class="stat"><?= isset($leave_requests['pending']) ? $leave_requests['pending'] : 0 ?></div>
                        <div class="desc">Pending Leave Request</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="manager-white-card">
                        <h5><i class="fa-solid fa-star"></i> Top-Rated Employees</h5>
                        <?php if (!empty($top_rated_employees)): ?>
                            <?php foreach ($top_rated_employees as $idx => $emp): ?>
                                <div><?= ($idx+1) . '. ' . htmlspecialchars($emp['full_name']) ?>
                                    <span class="star">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <?= ($i < round($emp['avg_rating'])) ? '&#9733;' : '&#9734;' ?>
                                        <?php endfor; ?>
                                    </span>
                                    <?= number_format($emp['avg_rating'], 1) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div>No ratings yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="manager-white-card">
                        <h5><i class="fa-solid fa-bell"></i> Recent Notifications</h5>
                        <?php if (!empty($admin_notices)): ?>
                            <?php foreach (array_slice($admin_notices, 0, 5) as $notice): ?>
                                <div style="margin-bottom:8px; background:#eaf1fa; border-radius:8px; padding:6px 12px;">
                                    <?= htmlspecialchars($notice['title']) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="margin-bottom:8px; background:#eaf1fa; border-radius:8px; padding:6px 12px;">No notifications</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="manager-white-card">
                        <h5><i class="fa-solid fa-bolt"></i> Quick Actions</h5>
                        <div class="quick-action selected"><i class="fa-solid fa-plus"></i> <a href="?show=createAccount" style="color:inherit;text-decoration:none;">Add Employee</a></div>
                        <div class="quick-action"><i class="fa-solid fa-check"></i> <a href="?show=leave" style="color:inherit;text-decoration:none;">Approve Leave</a></div>
                        <div class="quick-action"><i class="fa-solid fa-eye"></i> <a href="?show=activityLogs" style="color:inherit;text-decoration:none;">View Logs</a></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($show === 'attendance'): ?>
        <style>
            .attendance-card {
                background: #fff;
                border-radius: 24px;
                box-shadow: 0 4px 32px rgba(0,0,0,0.10);
                padding: 48px 40px 40px 40px;
                max-width: 1100px;
                margin: 40px auto 0 auto;
            }
            .attendance-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 28px;
            }
            .attendance-title {
                font-size: 2rem;
                font-weight: bold;
                color: #111;
            }
            .attendance-actions {
                display: flex;
                align-items: center;
                gap: 16px;
            }
            .attendance-btn {
                background: #3d4ed7;
                color: #fff;
                border: none;
                border-radius: 14px;
                padding: 12px 32px;
                font-size: 1.1rem;
                font-weight: 500;
                box-shadow: 0 1px 4px rgba(61,78,215,0.08);
                transition: background 0.18s;
            }
            .attendance-btn.secondary {
                background: #222;
                color: #fff;
                font-size: 1rem;
                padding: 12px 24px;
            }
            .attendance-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 12px;
            }
            .attendance-table th {
                background: #e7eaf6;
                color: #222;
                font-weight: 600;
                padding: 14px 16px;
                border-radius: 10px 10px 0 0;
                text-align: left;
                font-size: 1.1rem;
            }
            .attendance-table td {
                background: #f4f5fb;
                color: #222;
                padding: 16px 16px;
                border-radius: 10px;
                font-size: 1.1rem;
            }
            .attendance-table tr {
                margin-bottom: 10px;
            }
            .attendance-table tr:last-child td {
                border-radius: 0 0 10px 10px;
            }
            .attendance-placeholder {
                background: #e7eaf6;
                border-radius: 10px;
                height: 48px;
                margin: 16px 0;
            }
            .attendance-filter-icon {
                font-size: 1.3rem;
                color: #222;
                margin-right: 12px;
                cursor: pointer;
                background: none;
                border: none;
            }
            .attendance-filter-form {
                display: flex;
                gap: 16px;
                margin-bottom: 24px;
                align-items: center;
                flex-wrap: wrap;
            }
            .attendance-filter-form input,
            .attendance-filter-form select {
                border-radius: 8px;
                border: 1.5px solid #e7eaf6;
                padding: 10px 14px;
                font-size: 1rem;
                background: #f8f9fa;
                min-width: 160px;
                max-width: 220px;
            }
            .attendance-filter-form button[type="submit"],
            .attendance-filter-form a {
                background: #3d4ed7;
                color: #fff;
                border: none;
                border-radius: 8px;
                padding: 10px 22px;
                font-size: 1rem;
                font-weight: 500;
                margin-left: 0;
                text-decoration: none;
                display: inline-block;
            }
            .attendance-filter-form a {
                background: #888;
            }
            .pagination {
                display: flex;
                justify-content: center;
                gap: 8px;
                margin-top: 28px;
            }
            .pagination .page-item {
                display: inline-block;
            }
            .pagination .page-link {
                background: #e7eaf6;
                color: #222;
                border: none;
                border-radius: 7px;
                padding: 8px 18px;
                font-size: 1rem;
                font-weight: 500;
                margin: 0 2px;
                transition: background 0.15s;
            }
            .pagination .page-item.active .page-link {
                background: #3d4ed7;
                color: #fff;
            }
            .pagination .page-item.disabled .page-link {
                background: #f4f5fb;
                color: #aaa;
            }
        </style>
        <div class="attendance-card">
            <div class="attendance-header">
                <span class="attendance-title">Attendance Record</span>
                <div class="attendance-actions">
                    <button class="attendance-filter-icon" title="Filter"><i class="fas fa-filter"></i></button>
                    <button class="attendance-btn">Export</button>
                    <button class="attendance-btn secondary" id="viewAttendanceRequestsBtn" type="button">View Attendance Requests</button>
                </div>
            </div>
            <form method="get" class="attendance-filter-form" id="attendanceFilterForm" style="display: flex;">
                <input type="hidden" name="show" value="attendance">
                <input type="text" name="attendance_search" value="<?= htmlspecialchars($_GET['attendance_search'] ?? '') ?>" placeholder="Search by name...">
                <input type="date" name="attendance_date" value="<?= htmlspecialchars($_GET['attendance_date'] ?? '') ?>">
                <select name="attendance_status">
                    <option value="">All Status</option>
                    <option value="present" <?= (($_GET['attendance_status'] ?? '') === 'present') ? 'selected' : '' ?>>Present</option>
                    <option value="absent" <?= (($_GET['attendance_status'] ?? '') === 'absent') ? 'selected' : '' ?>>Absent</option>
                    <option value="leave" <?= (($_GET['attendance_status'] ?? '') === 'leave') ? 'selected' : '' ?>>Leave</option>
                    <option value="late" <?= (($_GET['attendance_status'] ?? '') === 'late') ? 'selected' : '' ?>>Late</option>
                </select>
                <select name="attendance_department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= htmlspecialchars($department['department_id']) ?>" <?= (($_GET['attendance_department'] ?? '') == $department['department_id']) ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filter</button>
                <a href="?show=attendance" title="Reset"><i class="fas fa-sync-alt"></i></a>
            </form>
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $limit = 10;
                $page = isset($_GET['attendance_page']) ? (int)$_GET['attendance_page'] : 1;
                $offset = ($page - 1) * $limit;
                $attendance_search = $_GET['attendance_search'] ?? '';
                $attendance_date = $_GET['attendance_date'] ?? '';
                $attendance_status = $_GET['attendance_status'] ?? '';
                $attendance_department = $_GET['attendance_department'] ?? '';
                $where = "1=1";
                if ($attendance_search !== '') {
                    $esc = mysqli_real_escape_string($conn, $attendance_search);
                    $where .= " AND (ua.full_name LIKE '%$esc%')";
                }
                if ($attendance_date !== '') {
                    $esc = mysqli_real_escape_string($conn, $attendance_date);
                    $where .= " AND a.date = '$esc'";
                }
                if ($attendance_status !== '') {
                    $esc = mysqli_real_escape_string($conn, $attendance_status);
                    $where .= " AND a.status = '$esc'";
                }
                if ($attendance_department !== '') {
                    $esc = mysqli_real_escape_string($conn, $attendance_department);
                    $where .= " AND ua.department_id = '$esc'";
                }
                $attendance_query = "SELECT a.*, ua.full_name, d.name AS department_name FROM attendance a JOIN user_account ua ON a.employee_id = ua.user_id LEFT JOIN department d ON ua.department_id = d.department_id WHERE $where ORDER BY a.date DESC, a.attendance_id DESC LIMIT $limit OFFSET $offset";
                $attendance_result = mysqli_query($conn, $attendance_query);
                $attendance_rows = [];
                if ($attendance_result && mysqli_num_rows($attendance_result) > 0) {
                    while ($row = mysqli_fetch_assoc($attendance_result)) {
                        $attendance_rows[] = $row;
                        $status = ucfirst($row['status']);
                        if ($status === 'Present' && $row['check_in'] && strtotime($row['check_in']) > strtotime('09:15:00')) {
                            $status = 'Late';
                        }
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['department_name'] ?? '-') . "</td>";
                        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                        echo "<td>" . ($row['check_in'] ? date('g:i A', strtotime($row['check_in'])) : '-') . "</td>";
                        echo "<td>" . ($row['check_out'] ? date('g:i A', strtotime($row['check_out'])) : '-') . "</td>";
                        echo "<td>" . htmlspecialchars($status) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    // Show two placeholders if no data
                    echo '<tr><td colspan="6"><div class="attendance-placeholder"></div></td></tr>';
                    echo '<tr><td colspan="6"><div class="attendance-placeholder"></div></td></tr>';
                }
                // Pagination
                $count_query = "SELECT COUNT(*) as total FROM attendance a JOIN user_account ua ON a.employee_id = ua.user_id LEFT JOIN department d ON ua.department_id = d.department_id WHERE $where";
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
            <nav aria-label="Attendance page navigation">
                <ul class="pagination">
                    <li class="page-item <?php if($page <= 1){ echo 'disabled'; } ?>">
                        <a class="page-link" href="<?php
                            $params = $_GET;
                            $params['attendance_page'] = $page - 1;
                            echo ($page > 1) ? '?' . http_build_query($params) : '#';
                        ?>">Previous</a>
                    </li>
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php if($page == $i){ echo 'active'; } ?>">
                            <a class="page-link" href="<?php
                                $params = $_GET;
                                $params['attendance_page'] = $i;
                                echo '?' . http_build_query($params);
                            ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php if($page >= $totalPages){ echo 'disabled'; } ?>">
                        <a class="page-link" href="<?php
                            $params = $_GET;
                            $params['attendance_page'] = $page + 1;
                            echo ($page < $totalPages) ? '?' . http_build_query($params) : '#';
                        ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <script>
            // Toggle filter form (for demonstration, always visible here)
            document.querySelector('.attendance-filter-icon').addEventListener('click', function() {
                var form = document.getElementById('attendanceFilterForm');
                if (form.style.display === 'none') {
                    form.style.display = 'flex';
                } else {
                    form.style.display = 'none';
                }
            });
            // Export to CSV functionality
            document.querySelector('.attendance-btn:not(.secondary)').addEventListener('click', function(e) {
                e.preventDefault();
                var csv = 'Name,Department,Date,Time In,Time Out,Status\n';
                <?php if (!empty($attendance_rows)): ?>
                var rows = <?php echo json_encode($attendance_rows); ?>;
                rows.forEach(function(row) {
                    var status = row.status.charAt(0).toUpperCase() + row.status.slice(1);
                    if (status === 'Present' && row.check_in && new Date('1970-01-01T' + row.check_in) > new Date('1970-01-01T09:15:00')) {
                        status = 'Late';
                    }
                    csv += '"' + row.full_name + '",';
                    csv += '"' + (row.department_name || '-') + '",';
                    csv += '"' + row.date + '",';
                    csv += '"' + (row.check_in ? new Date('1970-01-01T' + row.check_in).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '-') + '",';
                    csv += '"' + (row.check_out ? new Date('1970-01-01T' + row.check_out).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '-') + '",';
                    csv += '"' + status + '"\n';
                });
                var blob = new Blob([csv], { type: 'text/csv' });
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = 'attendance_records.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                <?php else: ?>
                alert('No attendance data to export.');
                <?php endif; ?>
            });
            // View Attendance Requests button
           </script> 
            <!-- ...modal and more JS as in your original code... -->
        </div>
    <?php endif; ?>

    <?php if ($show === 'leave'): ?>
        <style>
            .leave-mgmt-outer {
                background: #f4f4f6;
                min-height: 100vh;
                padding: 0;
            }
            .leave-mgmt-card {
                background: #fff;
                border-radius: 20px;
                box-shadow: 0 4px 24px rgba(0,0,0,0.10);
                padding: 32px 32px 32px 32px;
                margin: 40px auto 0 auto;
                max-width: 1200px;
            }
            .leave-mgmt-header-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 24px;
            }
            .leave-mgmt-header-bar .filter-group {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .leave-mgmt-header-bar select {
                border-radius: 20px;
                border: none;
                background: #2d3a7b;
                color: #fff;
                font-weight: 500;
                padding: 8px 22px;
                font-size: 1rem;
                box-shadow: 0 2px 8px rgba(44,62,80,0.08);
                outline: none;
            }
            .leave-mgmt-header-bar .search-wrapper {
                position: relative;
                display: flex;
                align-items: center;
            }
            .leave-mgmt-header-bar .search-wrapper input[type="text"] {
                border-radius: 20px;
                border: none;
                background: #f4f4f6;
                padding: 8px 38px 8px 16px;
                font-size: 1rem;
                min-width: 180px;
                outline: none;
            }
            .leave-mgmt-header-bar .search-wrapper .fa-search {
                position: absolute;
                right: 14px;
                color: #b0b0b0;
                font-size: 1.1rem;
            }
            .leave-mgmt-header-bar .filter-icon {
                font-size: 1.3rem;
                color: #2d3a7b;
                margin-left: 10px;
                cursor: pointer;
            }
            .leave-mgmt-header-bar button.export-btn {
                background: #2d3a7b;
                color: #fff;
                border: none;
                border-radius: 20px;
                padding: 10px 32px;
                font-size: 1rem;
                font-weight: 500;
                box-shadow: 0 2px 8px rgba(44,62,80,0.12);
                cursor: pointer;
                transition: background 0.18s;
            }
            .leave-mgmt-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 14px;
                font-size: 1rem;
            }
            .leave-mgmt-table th {
                background: #e7eaf6;
                color: #222;
                font-weight: 600;
                padding: 14px 10px;
                border-radius: 10px 10px 0 0;
                text-align: left;
            }
            .leave-mgmt-table td {
                background: #f8f9fa;
                color: #222;
                padding: 14px 10px;
                border-radius: 10px;
            }
            .leave-mgmt-table tr:nth-child(even) td {
                background: #e9eaf3;
            }
            .leave-mgmt-table tr {
                margin-bottom: 10px;
            }
            .leave-mgmt-table tr:last-child td {
                border-radius: 0 0 10px 10px;
            }
            .leave-mgmt-action-btn {
                border: none;
                background: none;
                font-size: 1.2rem;
                cursor: pointer;
                margin: 0 4px;
                border-radius: 50%;
                width: 32px;
                height: 32px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: background 0.15s;
            }
            .leave-mgmt-action-btn.approve {
                color: #fff;
                background: #2ecc40;
            }
            .leave-mgmt-action-btn.reject {
                color: #fff;
                background: #e74c3c;
            }
            .leave-mgmt-action-btn.approve:hover {
                background: #27ae60;
            }
            .leave-mgmt-action-btn.reject:hover {
                background: #c0392b;
            }
        </style>
        <?php
        // Handle approve/reject actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_action']) && isset($_POST['leave_id'])) {
            $leave_id = intval($_POST['leave_id']);
            $new_status = $_POST['leave_action'] === 'approve' ? 'approved' : 'rejected';
            $update_query = "UPDATE leave_request SET status = '$new_status' WHERE leave_id = $leave_id";
            mysqli_query($conn, $update_query);
            echo '<script>window.location.href = window.location.href;</script>';
            exit();
        }
        ?>
        <div class="leave-mgmt-outer">
            <div class="leave-mgmt-card">
                <div class="leave-mgmt-header-bar">
                    <div class="filter-group">
                        <select id="leaveStatusFilter">
                            <option value="">All Candidates</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <div class="search-wrapper">
                            <input type="text" id="leaveSearchInput" placeholder="Search..." />
                            <i class="fa fa-search"></i>
                        </div>
                        <i class="fa fa-filter filter-icon"></i>
                    </div>
                    <div>
                        <button class="export-btn" id="leaveExportBtn">Export</button>
                    </div>
                </div>
                <table class="leave-mgmt-table" id="leaveTable">
                    <thead>
                        <tr>
                            <th>Name(s)</th>
                            <th>End Date</th>
                            <th>Start Date</th>
                            <th>Duration(s)</th>
                            <th>Resumption Date</th>
                            <th>Type</th>
                            <th>Reason(s)</th>
                            <th>Hand Over Document</th>
                            <th>Relief Officer</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $leave_query = "SELECT lr.*, ua.full_name, d.name AS department FROM leave_request lr
                        LEFT JOIN user_account ua ON lr.employee_id = ua.user_id
                        LEFT JOIN department d ON ua.department_id = d.department_id
                        ORDER BY lr.start_date DESC";
                    $leave_result = mysqli_query($conn, $leave_query);
                    if ($leave_result && mysqli_num_rows($leave_result) > 0) {
                        while ($row = mysqli_fetch_assoc($leave_result)) {
                            $duration = '-';
                            if (!empty($row['start_date']) && !empty($row['end_date'])) {
                                $start = new DateTime($row['start_date']);
                                $end = new DateTime($row['end_date']);
                                $duration = $start->diff($end)->days + 1;
                            }
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['full_name'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($row['end_date'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($row['start_date'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($duration) . '</td>';
                            echo '<td>' . htmlspecialchars($row['resumption_date'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($row['type'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($row['reason'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($row['hand_over_document'] ?? '-') . '</td>';
                            echo '<td>' . htmlspecialchars($row['relief_officer'] ?? '-') . '</td>';
                            echo '<td class="leave-status">' . htmlspecialchars(ucfirst($row['status'])) . '</td>';
                            echo '<td>';
                            if ($row['status'] === 'pending') {
                                echo '<form method="post" style="display:inline;">';
                                echo '<input type="hidden" name="leave_id" value="' . $row['leave_id'] . '">';
                                echo '<button type="submit" name="leave_action" value="approve" class="leave-mgmt-action-btn approve" title="Approve"><i class="fa fa-check"></i></button>';
                                echo '<button type="submit" name="leave_action" value="reject" class="leave-mgmt-action-btn reject" title="Reject"><i class="fa fa-times"></i></button>';
                                echo '</form>';
                            } else if ($row['status'] === 'approved') {
                                echo '<span class="leave-mgmt-action-btn approve"><i class="fa fa-check"></i></span>';
                            } else if ($row['status'] === 'rejected') {
                                echo '<span class="leave-mgmt-action-btn reject"><i class="fa fa-times"></i></span>';
                            } else {
                                echo '-';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="11" class="text-center">No leave requests found.</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script>
        // Live search and filter
        const searchInput = document.getElementById('leaveSearchInput');
        const statusFilter = document.getElementById('leaveStatusFilter');
        const table = document.getElementById('leaveTable');
        searchInput.addEventListener('input', filterTable);
        statusFilter.addEventListener('change', filterTable);
        function filterTable() {
            const search = searchInput.value.toLowerCase();
            const status = statusFilter.value;
            for (const row of table.tBodies[0].rows) {
                let text = row.innerText.toLowerCase();
                let rowStatus = row.querySelector('.leave-status')?.innerText.toLowerCase() || '';
                let show = (!search || text.includes(search)) && (!status || rowStatus === status);
                row.style.display = show ? '' : 'none';
            }
        }
        // Export to CSV
        document.getElementById('leaveExportBtn').addEventListener('click', function() {
            let csv = '';
            const rows = table.querySelectorAll('tr');
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll('th, td');
                for (let j = 0; j < cols.length; j++) {
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                }
                csv += row.join(',') + '\n';
            }
            let blob = new Blob([csv], { type: 'text/csv' });
            let link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = 'leave_requests.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
        </script>
    <?php endif; ?>

    <?php if ($show === 'resignation'): ?>
        <style>
            .resignation-mgmt-card {
                background: #fff;
                border-radius: 18px;
                box-shadow: 0 2px 16px rgba(0,0,0,0.07);
                padding: 32px 32px 32px 32px;
                margin: 40px auto 0 auto;
                max-width: 900px;
            }
            .resignation-title {
                font-size: 1.7rem;
                font-weight: bold;
                color: #222;
                margin-bottom: 24px;
            }
            .resignation-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 10px;
                font-size: 1rem;
            }
            .resignation-table th {
                background: #e7eaf6;
                color: #222;
                font-weight: 600;
                padding: 12px 10px;
                border-radius: 10px 10px 0 0;
                text-align: left;
            }
            .resignation-table td {
                background: #f8f9fa;
                color: #222;
                padding: 12px 10px;
                border-radius: 10px;
            }
            .resignation-table tr:nth-child(even) td {
                background: #e9eaf3;
            }
            .resignation-table tr {
                margin-bottom: 10px;
            }
            .resignation-table tr:last-child td {
                border-radius: 0 0 10px 10px;
            }
            .resignation-action-btn {
                border: none;
                background: none;
                font-size: 1.2rem;
                cursor: pointer;
                margin: 0 4px;
            }
            .resignation-action-btn.accept { color: #2ecc40; }
            .resignation-action-btn.reject { color: #e74c3c; }
        </style>
        <div class="resignation-mgmt-card">
            <div class="resignation-title"><i class="fa-solid fa-user-slash"></i> Resignation Requests</div>
            <table class="resignation-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Date Filed</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $resignation_query = "SELECT r.*, ua.full_name, d.name AS department FROM resignation r
                    LEFT JOIN user_account ua ON r.employee_id = ua.user_id
                    LEFT JOIN department d ON ua.department_id = d.department_id
                    ORDER BY r.date_filed DESC";
                $resignation_result = mysqli_query($conn, $resignation_query);
                if ($resignation_result && mysqli_num_rows($resignation_result) > 0) {
                    while ($row = mysqli_fetch_assoc($resignation_result)) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['full_name'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($row['department'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($row['date_filed'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($row['reason'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars(ucfirst($row['status'])) . '</td>';
                        echo '<td>';
                        if ($row['status'] === 'pending') {
                            echo '<form method="post" style="display:inline;">';
                            echo '<input type="hidden" name="resignation_id" value="' . $row['resignation_id'] . '">';
                            echo '<button type="submit" name="resignation_action" value="accept" class="resignation-action-btn accept" title="Accept"><i class="fa fa-check"></i></button>';
                            echo '<button type="submit" name="resignation_action" value="reject" class="resignation-action-btn reject" title="Reject"><i class="fa fa-times"></i></button>';
                            echo '</form>';
                        } else if ($row['status'] === 'accepted') {
                            echo '<i class="fa fa-check" style="color:#2ecc40;"></i>';
                        } else if ($row['status'] === 'rejected') {
                            echo '<i class="fa fa-times" style="color:#e74c3c;"></i>';
                        } else {
                            echo '-';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6" class="text-center">No resignation requests found.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($show === 'notification'): ?>
        <style>
            .notif-card {
                background: #fff;
                border-radius: 18px;
                box-shadow: 0 2px 16px rgba(0,0,0,0.07);
                padding: 32px 32px 32px 32px;
                margin: 40px auto 0 auto;
                max-width: 800px;
            }
            .notif-title {
                font-size: 1.7rem;
                font-weight: bold;
                color: #222;
                margin-bottom: 24px;
            }
            .notif-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 10px;
                font-size: 1rem;
            }
            .notif-table th {
                background: #e7eaf6;
                color: #222;
                font-weight: 600;
                padding: 12px 10px;
                border-radius: 10px 10px 0 0;
                text-align: left;
            }
            .notif-table td {
                background: #f8f9fa;
                color: #222;
                padding: 12px 10px;
                border-radius: 10px;
            }
            .notif-table tr:nth-child(even) td {
                background: #e9eaf3;
            }
            .notif-table tr {
                margin-bottom: 10px;
            }
            .notif-table tr:last-child td {
                border-radius: 0 0 10px 10px;
            }
        </style>
        <div class="notif-card">
            <div class="notif-title"><i class="fa-solid fa-bell"></i> Notifications</div>
            <table class="notif-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Type</th>
                        <th>Sender</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $notice_query = "SELECT n.title, n.content, n.type, n.created_at, ua.full_name as sender_name 
                    FROM notification n 
                    LEFT JOIN user_account ua ON n.sender_id = ua.user_id 
                    ORDER BY n.created_at DESC LIMIT 20";
                $notice_result = mysqli_query($conn, $notice_query);
                if ($notice_result && mysqli_num_rows($notice_result) > 0) {
                    while ($row = mysqli_fetch_assoc($notice_result)) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['title']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['content']) . '</td>';
                        echo '<td>' . htmlspecialchars(ucfirst($row['type'])) . '</td>';
                        echo '<td>' . htmlspecialchars($row['sender_name'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" class="text-center">No notifications found.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($show === 'activityLogs'): ?>
        <style>
            .activity-logs-card {
                background: #fff;
                border-radius: 18px;
                box-shadow: 0 2px 16px rgba(0,0,0,0.07);
                padding: 32px 32px 32px 32px;
                margin: 40px auto 0 auto;
                max-width: 1100px;
            }
            .activity-logs-title {
                font-size: 1.7rem;
                font-weight: bold;
                color: #222;
                margin-bottom: 24px;
            }
            .activity-logs-header-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 18px;
            }
            .activity-logs-header-bar .search-wrapper {
                position: relative;
                display: flex;
                align-items: center;
            }
            .activity-logs-header-bar .search-wrapper input[type="text"] {
                border-radius: 20px;
                border: none;
                background: #f4f4f6;
                padding: 8px 38px 8px 16px;
                font-size: 1rem;
                min-width: 220px;
                outline: none;
            }
            .activity-logs-header-bar .search-wrapper .fa-search {
                position: absolute;
                right: 14px;
                color: #b0b0b0;
                font-size: 1.1rem;
            }
            .activity-logs-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 10px;
                font-size: 1rem;
            }
            .activity-logs-table th {
                background: #e7eaf6;
                color: #222;
                font-weight: 600;
                padding: 12px 10px;
                border-radius: 10px 10px 0 0;
                text-align: left;
            }
            .activity-logs-table td {
                background: #f8f9fa;
                color: #222;
                padding: 12px 10px;
                border-radius: 10px;
            }
            .activity-logs-table tr:nth-child(even) td {
                background: #e9eaf3;
            }
            .activity-logs-table tr {
                margin-bottom: 10px;
            }
            .activity-logs-table tr:last-child td {
                border-radius: 0 0 10px 10px;
            }
            .pagination {
                display: flex;
                justify-content: center;
                gap: 8px;
                margin-top: 28px;
            }
            .pagination .page-item {
                display: inline-block;
            }
            .pagination .page-link {
                background: #e7eaf6;
                color: #222;
                border: none;
                border-radius: 7px;
                padding: 8px 18px;
                font-size: 1rem;
                font-weight: 500;
                margin: 0 2px;
                transition: background 0.15s;
            }
            .pagination .page-item.active .page-link {
                background: #3d4ed7;
                color: #fff;
            }
            .pagination .page-item.disabled .page-link {
                background: #f4f5fb;
                color: #aaa;
            }
        </style>
        <div class="activity-logs-card">
            <div class="activity-logs-title"><i class="fa-solid fa-clock"></i> Activity Logs</div>
            <div class="activity-logs-header-bar">
                <div class="search-wrapper">
                    <input type="text" id="activityLogsSearchInput" placeholder="Search logs..." />
                    <i class="fa fa-search"></i>
                </div>
            </div>
            <table class="activity-logs-table" id="activityLogsTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Date/Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $activity_limit = 15;
                $activity_page = isset($_GET['activity_page']) ? (int)$_GET['activity_page'] : 1;
                $activity_offset = ($activity_page - 1) * $activity_limit;
                $activity_search = $_GET['activity_search'] ?? '';
                $activity_where = "1=1";
                if ($activity_search !== '') {
                    $activity_search_esc = mysqli_real_escape_string($conn, $activity_search);
                    $activity_where .= " AND (al.module LIKE '%$activity_search_esc%' OR al.action LIKE '%$activity_search_esc%' OR al.details LIKE '%$activity_search_esc%' OR ua.full_name LIKE '%$activity_search_esc%')";
                }
                $activity_query = "SELECT al.*, ua.full_name AS user_name FROM activity_log al
                    LEFT JOIN user_account ua ON al.user_id = ua.user_id
                    WHERE $activity_where
                    ORDER BY al.created_at DESC
                    LIMIT $activity_limit OFFSET $activity_offset";
                $activity_result = mysqli_query($conn, $activity_query);
                if ($activity_result && mysqli_num_rows($activity_result) > 0) {
                    while ($row = mysqli_fetch_assoc($activity_result)) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['user_name'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($row['module'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($row['action'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($row['details'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" class="text-center">No activity logs found.</td></tr>';
                }
                ?>
                </tbody>
            </table>
            <?php
            // Pagination
            $activity_total_query = "SELECT COUNT(*) as total FROM activity_log al LEFT JOIN user_account ua ON al.user_id = ua.user_id WHERE $activity_where";
            $activity_total_result = mysqli_query($conn, $activity_total_query);
            $activity_total_row = mysqli_fetch_assoc($activity_total_result);
            $activity_total_pages = ceil($activity_total_row['total'] / $activity_limit);
            ?>
            <nav aria-label="Activity logs page navigation">
                <ul class="pagination">
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
        <script>
        // Live search for activity logs
        const activitySearchInput = document.getElementById('activityLogsSearchInput');
        const activityTable = document.getElementById('activityLogsTable');
        activitySearchInput.addEventListener('input', function() {
            const search = activitySearchInput.value.toLowerCase();
            for (const row of activityTable.tBodies[0].rows) {
                let text = row.innerText.toLowerCase();
                row.style.display = (!search || text.includes(search)) ? '' : 'none';
            }
        });
        </script>
    <?php endif; ?>

    <?php if ($show === 'employeeList'): ?>
        <div class="manager-white-card" id="employeeListContainer" style="max-width:1100px;margin:40px auto 0 auto;">
            <h3 style="margin-bottom:24px;"><i class="fa-solid fa-users"></i> Employee List</h3>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Job Title</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $employee_query = "SELECT ua.user_id, ua.full_name, ua.email, r.name AS role, d.name AS department, jr.title AS job_title
                    FROM user_account ua
                    LEFT JOIN role r ON ua.role_id = r.role_id
                    LEFT JOIN department d ON ua.department_id = d.department_id
                    LEFT JOIN job_role jr ON ua.job_role_id = jr.job_role_id
                    WHERE ua.role_id = 2
                    ORDER BY ua.full_name ASC";
                $employee_result = mysqli_query($conn, $employee_query);
                if ($employee_result && mysqli_num_rows($employee_result) > 0) {
                    while ($row = mysqli_fetch_assoc($employee_result)) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['role']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['department'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($row['job_title'] ?? '-') . '</td>';
                        echo '<td>';
                        echo '<form method="post" style="display:inline;">';
                        echo '<input type="hidden" name="user_id" value="' . $row['user_id'] . '">';
                        echo '<button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this user?\')">Delete</button>';
                        echo '</form>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6" class="text-center">No employees found.</td></tr>';
                }
                ?>
                </tbody>
            </table>
            <?php if (!empty($user_action_msg)) echo $user_action_msg; ?>
        </div>
    <?php endif; ?>

    <?php if ($show === 'changePassword'): ?>
        <div id="changePasswordFormContainer" class="change-password-form">
            <h3>Change Password</h3>
            <form method="post">
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
                <button type="submit" name="change_password" class="btn btn-change-password">Change Password</button>
            </form>
            <?php if (!empty($change_password_msg)) echo $change_password_msg; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'managerFooter.php'; ?>