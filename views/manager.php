<?php
// --- BRUTE FORCE JSON ERROR HANDLER FOR AJAX ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    set_exception_handler(function($e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Fatal: ' . $e->getMessage()]);
        exit();
    });
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => "PHP Error: $errstr in $errfile on line $errline"]);
        exit();
    });
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Shutdown: ' . $error['message']]);
            exit();
        }
    });
}
session_start();
require_once('../config/db.php');

// Handle leave status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_leave_status'])) {
    $leave_id = intval($_POST['id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_leave_status']);
    
    $update_query = "UPDATE leave_application SET status = '$new_status' WHERE id = $leave_id";
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success_message'] = "Leave status updated successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to update leave status.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle resignation status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_resignation_status'])) {
    $resignation_id = intval($_POST['resignation_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    $update_query = "UPDATE resignation SET status = '$new_status' WHERE resignation_id = $resignation_id";
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success_message'] = "Resignation status updated successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to update resignation status.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Only allow access for Manager role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'manager') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Session expired or unauthorized. Please log in again.']);
        exit();
    } else {
        header('Location: /fedm_hrms/views/manager.php');
        exit();
    }
}

// Fetch dynamic dashboard data
$present_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT employee_id) as cnt FROM attendance WHERE date = CURDATE() AND status = 'present'"))['cnt'];
$leave_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT employee_id) as cnt FROM attendance WHERE date = CURDATE() AND status = 'leave'"))['cnt'];
$pending_resignation = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM resignation WHERE status = 'pending'"))['cnt'];
$pending_leave = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM leave_application WHERE status = 'pending'"))['cnt'];

// Top-rated employees (example: top 3 by manager_rating)
$top_employees = [];
$res = mysqli_query($conn, "SELECT full_name, manager_rating FROM user_account WHERE manager_rating IS NOT NULL ORDER BY manager_rating DESC LIMIT 3");
while ($row = mysqli_fetch_assoc($res)) {
    $top_employees[] = $row;
}

// Recent notifications (last 3)
$notifications = [];
$res = mysqli_query($conn, "SELECT title, content FROM notification ORDER BY created_at DESC LIMIT 3");
while ($row = mysqli_fetch_assoc($res)) {
    $notifications[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f3f3f3; }
        .sidebar-mgr {
            background: #f4f8fd;
            min-height: 100vh;
            border-radius: 24px 0 0 24px;
            box-shadow: 0 2px 16px #0001;
            padding: 0;
            width: 260px;
        }
        .sidebar-mgr .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 32px;
            margin-bottom: 32px;
        }
        .sidebar-mgr .logo-img {
            width: 90px;
            height: 90px;
            margin-bottom: 12px;
        }
        .sidebar-mgr .nav-mgr {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding-left: 0;
        }
        .sidebar-mgr .nav-mgr-link {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            font-weight: 600;
            color: #2956a8 !important;
            font-size: 1.18rem;
            padding: 0.7rem 2rem;
            border-radius: 16px;
            background: none;
            transition: background 0.2s, color 0.2s;
            text-decoration: none;
            position: relative;
        }
        .sidebar-mgr .nav-mgr-link.active, .sidebar-mgr .nav-mgr-link:focus {
            background: #dbeafe !important;
            color: #1d3557 !important;
        }
        .sidebar-mgr .nav-mgr-link:hover {
            background: #e3ecfa !important;
            color: #1d3557 !important;
        }
        .sidebar-mgr .nav-mgr-link i {
            font-size: 1.5rem;
        }
        .sidebar-mgr .nav-mgr-link.active::after {
            content: '';
            display: block;
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 8px;
            height: 3px;
            border-radius: 2px;
            background: #2956a8;
        }
        .dashboard-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px #0001; padding: 32px 32px 24px 32px; margin-bottom: 24px; min-height: 180px; }
        .summary-card { border-radius: 12px; color: #fff; padding: 24px 0; text-align: center; font-weight: 600; font-size: 1.2rem; }
        .summary-card.bg1 { background: #222e50; }
        .summary-card.bg2 { background: #0077b6; }
        .summary-card.bg3 { background: #adb5bd; color: #222; }
        .summary-card.bg4 { background: #03045e; }
        .star { color: #f6a940; font-size: 1.1rem; }
        .quick-action { display: flex; flex-direction: column; gap: 1rem; }
        .quick-action button, .quick-action a { width: 100%; }
    </style>
</head>
<body>
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar-mgr">
        <div class="logo">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Manager Logo" class="logo-img">
        </div>
        <nav class="nav-mgr flex-column px-2">
            <a class="nav-mgr-link active" href="#"><i class="bi bi-map"></i>Dashboard</a>
            <a class="nav-mgr-link" href="#"><i class="bi bi-person"></i>Employee</a>
            <a class="nav-mgr-link" href="#"><i class="bi bi-calendar-check"></i>Attendance</a>
            <a class="nav-mgr-link" href="#"><i class="bi bi-calendar-heart"></i>Leave</a>
            <a class="nav-mgr-link" href="#"><i class="bi bi-box-arrow-right"></i>Resignation</a>
            <a class="nav-mgr-link" href="#"><i class="bi bi-bell"></i>Notification</a>
            <a class="nav-mgr-link" href="#"><i class="bi bi-clock-history"></i>Activity Logs</a>
        </nav>
    </div>
    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
        <div id="dashboardContent">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="summary-card bg1">
                        <div style="font-size:2rem; font-weight:bold;"><?= $present_today ?></div>
                        <div>Total Employees Present Today</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card bg2">
                        <div style="font-size:2rem; font-weight:bold;"><?= $leave_today ?></div>
                        <div>Total Employees on Leave Today</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card bg3">
                        <div style="font-size:2rem; font-weight:bold;"><?= $pending_resignation ?></div>
                        <div>Pending Resignation</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card bg4">
                        <div style="font-size:2rem; font-weight:bold;"><?= $pending_leave ?></div>
                        <div>Pending Leave Request</div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h5 class="fw-bold mb-3">Top-Rated Employees</h5>
                        <ol>
                            <li>
                                John Doe
                                <span style="margin-left:8px; color: #ffb300;">
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    5.0
                                </span>
                            </li>
                            <li>
                                Ryan Jeremy
                                <span style="margin-left:8px; color: #ffb300;">
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    5.0
                                </span>
                            </li>
                            <li>
                                Christine Mendoza
                                <span style="margin-left:8px; color: #ffb300;">
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    <span class="star">&#9733;</span>
                                    5.0
                                </span>
                            </li>
                        </ol>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h5 class="fw-bold mb-3">Quick Actions</h5>
                        <div class="quick-action">
                            <a href="#" class="btn btn-outline-primary">Approve Attendance</a>
                            <a href="#" class="btn btn-outline-success">Approve Leave</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="employeeListContainer" style="display: none;">
            <h3>User Management</h3>
            <form method="get" class="d-flex align-items-center mb-3" style="gap: 12px;">
                <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Search by name or email..." class="form-control" style="max-width: 250px;">
                <button type="submit" class="btn btn-primary">Search</button>
            
                
                <select name="filterDepartment" class="form-select" style="max-width: 160px;">
                    <option value="">All Departments</option>
                    <?php
                    $deptRes = mysqli_query($conn, "SELECT department_id, name FROM department ORDER BY name ASC");
                    while ($dept = mysqli_fetch_assoc($deptRes)) {
                        $selected = (isset($_GET['filterDepartment']) && $_GET['filterDepartment'] == $dept['department_id']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($dept['department_id']) . '" ' . $selected . '>' . htmlspecialchars($dept['name']) . '</option>';
                    }
                    ?>
                </select>
                <select name="filterJobRole" class="form-select" style="max-width: 160px;">
                    <option value="">All Job Titles</option>
                    <?php
                    $jobRes = mysqli_query($conn, "SELECT job_role_id, title FROM job_role ORDER BY title ASC");
                    while ($job = mysqli_fetch_assoc($jobRes)) {
                        $selected = (isset($_GET['filterJobRole']) && $_GET['filterJobRole'] == $job['job_role_id']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($job['job_role_id']) . '" ' . $selected . '>' . htmlspecialchars($job['title']) . '</option>';
                    }
                    ?>
                </select>
                
                <a href="manager.php" class="btn btn-secondary" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
            </form>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Job Title</th>
                            <th>Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search = $_GET['search'] ?? '';
                        $filterRole = $_GET['filterRole'] ?? '';
                        $filterDepartment = $_GET['filterDepartment'] ?? '';
                        $filterJobRole = $_GET['filterJobRole'] ?? '';
                        $where = "1=1";
                        if ($search !== '') {
                            $esc = mysqli_real_escape_string($conn, $search);
                            $where .= " AND (ua.full_name LIKE '%$esc%' OR ua.email LIKE '%$esc%')";
                        }
                        // Only show users with the Employee role
                        $where .= " AND r.name = 'Employee'";
                        if ($filterRole !== '') {
                            $esc = mysqli_real_escape_string($conn, $filterRole);
                            $where .= " AND ua.role_id = '$esc'";
                        }
                        if ($filterDepartment !== '') {
                            $esc = mysqli_real_escape_string($conn, $filterDepartment);
                            $where .= " AND ua.department_id = '$esc'";
                        }
                        if ($filterJobRole !== '') {
                            $esc = mysqli_real_escape_string($conn, $filterJobRole);
                            $where .= " AND ua.job_role_id = '$esc'";
                        }
                        $userQuery = "SELECT ua.user_id, ua.full_name, ua.email, r.name AS role_name, d.name AS department_name, jr.title AS job_title, ua.manager_rating
                                      FROM user_account ua
                                      LEFT JOIN role r ON ua.role_id = r.role_id
                                      LEFT JOIN department d ON ua.department_id = d.department_id
                                      LEFT JOIN job_role jr ON ua.job_role_id = jr.job_role_id
                                      WHERE $where
                                      ORDER BY ua.full_name ASC";
                        $userResult = mysqli_query($conn, $userQuery);
                        if ($userResult) {
                            while ($user = mysqli_fetch_assoc($userResult)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($user['department_name'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($user['job_title'] ?? 'N/A') . "</td>";
                                echo '<td>';
                                echo (isset($user['manager_rating']) && $user['manager_rating'] !== null ? number_format($user['manager_rating'], 1) : '-') .
                                    ' <button class="btn btn-sm btn-outline-primary rate-btn" data-userid="' . $user['user_id'] . '" data-username="' . htmlspecialchars($user['full_name']) . '" data-currentrate="' . htmlspecialchars($user['manager_rating']) . '">Rate</button>';
                                echo '</td>';
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="attendanceListContainer" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Employee Attendance Record</h3>
                <a href="#" class="btn btn-success">View Attendance Requests</a>
            </div>
            <form method="get" class="d-flex align-items-center mb-3" style="gap: 12px;">
                <input type="text" name="attendance_search" value="<?= htmlspecialchars($_GET['attendance_search'] ?? '') ?>" placeholder="Search by name..." class="form-control" style="max-width: 220px;">
                <select name="attendance_status" class="form-select" style="max-width: 160px;">
                    <option value="">All Status</option>
                    <option value="present" <?= (($_GET['attendance_status'] ?? '') === 'present') ? 'selected' : '' ?>>Present</option>
                    <option value="late" <?= (($_GET['attendance_status'] ?? '') === 'late') ? 'selected' : '' ?>>Late</option>
                    <option value="leave" <?= (($_GET['attendance_status'] ?? '') === 'leave') ? 'selected' : '' ?>>Leave</option>
                </select>
                <select name="attendance_department" class="form-select" style="max-width: 160px;">
                    <option value="">All Departments</option>
                    <?php
                    $deptRes = mysqli_query($conn, "SELECT department_id, name FROM department ORDER BY name ASC");
                    while ($dept = mysqli_fetch_assoc($deptRes)) {
                        $selected = (isset($_GET['attendance_department']) && $_GET['attendance_department'] == $dept['department_id']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($dept['department_id']) . '" ' . $selected . '>' . htmlspecialchars($dept['name']) . '</option>';
                    }
                    ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="manager.php" class="btn btn-secondary" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
            </form>
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
                        $attendance_query = "SELECT a.*, ua.full_name, d.name AS department_name, jr.title AS job_title, TIMESTAMPDIFF(HOUR, a.check_in, a.check_out) as total_hours
                            FROM attendance a
                            JOIN user_account ua ON a.employee_id = ua.user_id
                            LEFT JOIN department d ON ua.department_id = d.department_id
                            LEFT JOIN job_role jr ON ua.job_role_id = jr.job_role_id
                            WHERE $where
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
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="position:absolute;right:18px;top:18px;font-size:1.5rem;">
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
        </div>
        <div id="attendanceRequestsPage" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-secondary" id="backToAttendanceBtn">&larr; Back to Employee's Attendance Record</button>
                <h4 class="fw-bold mb-0">Attendance Modification Requests</h4>
                <div></div>
            </div>
            <div class="table-responsive">
                <table class="table mb-0" style="background:transparent;">
                    <thead style="background:#d3d5e6;">
                        <tr>
                            <th>Name</th>
                            <th>Date</th>
                            <th>Original Time In</th>
                            <th>Original Time Out</th>
                            <th>Requested Time In</th>
                            <th>Requested Time Out</th>
                            <th>Total Hours</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $modRes = mysqli_query($conn, "SELECT am.*, ua.full_name, a.check_in AS original_time_in, a.check_out AS original_time_out FROM attendance_modification am JOIN user_account ua ON am.employee_id = ua.user_id LEFT JOIN attendance a ON am.employee_id = a.employee_id AND am.date_of_attendance = a.date ORDER BY am.status='pending' DESC, am.requested_at DESC");
                        if ($modRes && mysqli_num_rows($modRes) > 0) {
                            while ($req = mysqli_fetch_assoc($modRes)) {
                                $total_hours = '';
                                if ($req['requested_time_in'] && $req['requested_time_out']) {
                                    $in = strtotime($req['requested_time_in']);
                                    $out = strtotime($req['requested_time_out']);
                                    $total_hours = round(($out - $in) / 3600, 2);
                                }
                                echo '<tr style="background:#e6e7f2;">';
                                echo '<td>' . htmlspecialchars($req['full_name']) . '</td>';
                                echo '<td>' . htmlspecialchars(date('m/d/Y', strtotime($req['date_of_attendance']))) . '</td>';
                                echo '<td>' . ($req['original_time_in'] ? date('g:i A', strtotime($req['original_time_in'])) : '-') . '</td>';
                                echo '<td>' . ($req['original_time_out'] ? date('g:i A', strtotime($req['original_time_out'])) : '-') . '</td>';
                                echo '<td>' . ($req['requested_time_in'] ? date('g:i A', strtotime($req['requested_time_in'])) : '-') . '</td>';
                                echo '<td>' . ($req['requested_time_out'] ? date('g:i A', strtotime($req['requested_time_out'])) : '-') . '</td>';
                                echo '<td>' . ($total_hours !== '' ? $total_hours : '-') . '</td>';
                                echo '<td>' . htmlspecialchars($req['reason']) . '</td>';
                                echo '<td>' . ucfirst($req['status']) . '</td>';
                                echo '<td>' . ($req['requested_at'] ? date('m/d/Y g:i A', strtotime($req['requested_at'])) : '-') . '</td>';
                                echo '<td>';
                                echo '<form method="post" style="display:inline;">';
                                echo '<input type="hidden" name="mod_id" value="' . $req['modification_id'] . '">';
                                echo '<select name="new_mod_status" class="form-control form-control-sm d-inline-block" style="width:auto;display:inline-block;">';
                                foreach (["pending", "approved", "rejected"] as $statusOpt) {
                                    $selected = ($req['status'] === $statusOpt) ? 'selected' : '';
                                    echo '<option value="' . $statusOpt . '" ' . $selected . '>' . ucfirst($statusOpt) . '</option>';
                                }
                                echo '</select> ';
                                echo '<button type="submit" name="update_mod_status" class="btn btn-primary btn-sm">Update</button>';
                                echo '</form>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="11" class="text-center">No attendance modification requests found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="leaveListContainer" style="display: none;">
            <h3>Employee Leave Records</h3>
            <form method="get" class="d-flex align-items-center mb-3" style="gap: 12px;">
                <input type="text" name="leave_search" value="<?= htmlspecialchars($_GET['leave_search'] ?? '') ?>" placeholder="Search by name..." class="form-control" style="max-width: 220px;">
                <input type="date" name="leave_date" value="<?= htmlspecialchars($_GET['leave_date'] ?? '') ?>" class="form-control" style="max-width: 170px;">
                <select name="leave_status" class="form-select" style="max-width: 160px;">
                    <option value="">All Status</option>
                    <option value="pending" <?= (($_GET['leave_status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= (($_GET['leave_status'] ?? '') === 'approved') ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= (($_GET['leave_status'] ?? '') === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                </select>
                <select name="leave_department" class="form-select" style="max-width: 160px;">
                    <option value="">All Departments</option>
                    <?php
                    $deptRes = mysqli_query($conn, "SELECT department_id, name FROM department ORDER BY name ASC");
                    while ($dept = mysqli_fetch_assoc($deptRes)) {
                        $selected = (isset($_GET['leave_department']) && $_GET['leave_department'] == $dept['department_id']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($dept['department_id']) . '" ' . $selected . '>' . htmlspecialchars($dept['name']) . '</option>';
                    }
                    ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="manager.php" class="btn btn-secondary" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
            </form>
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
                            <th>View</th>
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
                                echo '<form method="post" class="leave-status-form" style="display:inline;">';
                                echo '<input type="hidden" name="id" value="' . htmlspecialchars($row['id']) . '">';
                                echo '<select name="new_leave_status" class="form-control form-control-sm d-inline-block" style="width:auto;display:inline-block;">';
                                foreach (["pending", "approved", "rejected"] as $statusOpt) {
                                    $selected = ($row['status'] === $statusOpt) ? 'selected' : '';
                                    echo '<option value="' . $statusOpt . '" ' . $selected . '>' . ucfirst($statusOpt) . '</option>';
                                }
                                echo '</select> ';
                                echo '<button type="submit" name="update_leave_status" value="1" class="btn btn-primary btn-sm">Update</button>';
                                echo '</form>';
                                echo '</td>';
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
                            echo '<tr><td colspan="9" class="text-center">No leave records found.</td></tr>';
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
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="position:absolute;right:18px;top:18px;font-size:1.5rem;">
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
        </div>
        <div id="resignationListContainer" style="display: none;">
            <h3>Employee Resignation Requests</h3>
            <form method="get" class="d-flex align-items-center mb-3" style="gap: 12px;">
                <input type="text" name="resignation_search" value="<?= htmlspecialchars($_GET['resignation_search'] ?? '') ?>" placeholder="Search by name..." class="form-control" style="max-width: 220px;">
                <select name="resignation_status" class="form-control" style="max-width: 160px;">
                    <option value="">All Status</option>
                    <option value="pending" <?= (($_GET['resignation_status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= (($_GET['resignation_status'] ?? '') === 'approved') ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= (($_GET['resignation_status'] ?? '') === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                </select>
                <select name="resignation_department" class="form-control" style="max-width: 160px;">
                    <option value="">All Departments</option>
                    <?php
                    $deptRes = mysqli_query($conn, "SELECT department_id, name FROM department ORDER BY name ASC");
                    while ($dept = mysqli_fetch_assoc($deptRes)) {
                        $selected = (isset($_GET['resignation_department']) && $_GET['resignation_department'] == $dept['department_id']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($dept['department_id']) . '" ' . $selected . '>' . htmlspecialchars($dept['name']) . '</option>';
                    }
                    ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="manager.php" class="btn btn-secondary" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
            </form>
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
                                echo '<form method="post" class="resignation-status-form" style="display:inline;">';
                                echo '<input type="hidden" name="resignation_id" value="' . htmlspecialchars($row['resignation_id']) . '">';
                                echo '<select name="new_status" class="form-control form-control-sm d-inline-block" style="width:auto;display:inline-block;">';
                                foreach (["pending", "approved", "rejected"] as $statusOpt) {
                                    $selected = ($row['status'] === $statusOpt) ? 'selected' : '';
                                    echo '<option value="' . $statusOpt . '" ' . $selected . '>' . ucfirst($statusOpt) . '</option>';
                                }
                                echo '</select> ';
                                echo '<button type="submit" name="update_resignation_status" value="1" class="btn btn-primary btn-sm">Update</button>';
                                echo '</form>';
                                echo '</td>';
                                echo "</tr>";
                            }
                        } else {
                            echo '<tr><td colspan="6" class="text-center">No resignation requests found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="notificationListContainer" style="display: none;">
            <div class="d-flex justify-content-center align-items-start pt-4">
                <div class="bg-white rounded-4 shadow-sm w-100" style="max-width: 950px;">
                    <div class="px-5 pt-5 pb-2">
                        <h3 class="fw-bold mb-4" style="color:#8a5a1e; font-size:2rem; text-align:center;">Notifications</h3>
                        <div class="list-group list-group-flush">
                            <?php
                            $notif_query = "SELECT n.*, COALESCE(ua.full_name, ua.email, 'Admin') as sender_name FROM notification n LEFT JOIN user_account ua ON n.sender_id = ua.user_id ORDER BY n.created_at DESC LIMIT 50";
                            $notif_result = mysqli_query($conn, $notif_query);
                            function formatNotificationTime($timestamp, $scheduled_date = null) {
                                $now = new DateTime();
                                $notification_time = new DateTime($timestamp);
                                if ($scheduled_date) {
                                    $scheduled = new DateTime($scheduled_date);
                                    if ($scheduled->format('Y-m-d') === $now->format('Y-m-d')) {
                                        return 'Posted today at ' . $scheduled->format('g:i A');
                                    } else if ($scheduled->format('Y-m-d') === $now->modify('-1 day')->format('Y-m-d')) {
                                        return 'Posted yesterday at ' . $scheduled->format('g:i A');
                                    } else if ($scheduled->format('Y') === $now->format('Y')) {
                                        return 'Posted on ' . $scheduled->format('M d') . ' at ' . $scheduled->format('g:i A');
                                    } else {
                                        return 'Posted on ' . $scheduled->format('M d, Y') . ' at ' . $scheduled->format('g:i A');
                                    }
                                }
                                if ($notification_time->format('Y-m-d') === $now->format('Y-m-d')) {
                                    return $notification_time->format('g:i A');
                                } else if ($notification_time->format('Y-m-d') === $now->modify('-1 day')->format('Y-m-d')) {
                                    return 'Yesterday ' . $notification_time->format('g:i A');
                                } else if ($notification_time->format('Y') === $now->format('Y')) {
                                    return $notification_time->format('M d, g:i A');
                                } else {
                                    return $notification_time->format('M d, Y g:i A');
                                }
                            }
                            if ($notif_result && mysqli_num_rows($notif_result) > 0) {
                                while ($notif = mysqli_fetch_assoc($notif_result)) {
                                    $time_display = formatNotificationTime($notif['created_at'], $notif['scheduled_date'] ?? null);
                                    echo '<div class="list-group-item d-flex justify-content-between align-items-start py-4 px-0 border-0 border-bottom">';
                                    echo '<div class="flex-grow-1">';
                                    echo '<div class="fw-bold" style="font-size:1.1rem;color:#111;">' . htmlspecialchars($notif['title']) . '</div>';
                                    echo '<div style="font-size:1rem;color:#111;">' . nl2br(htmlspecialchars($notif['content'])) . '</div>';
                                    if (!empty($notif['sender_name'])) {
                                        echo '<div class="mt-1" style="font-size:0.9rem;color:#111;">From: ' . htmlspecialchars($notif['sender_name']) . '</div>';
                                    }
                                    echo '</div>';
                                    echo '<div class="ms-3" style="font-size:0.9rem; min-width:110px; text-align:right;color:#111;">' . $time_display . '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="text-center text-muted" style="padding: 32px 0; color:#111;">No notifications available</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- User Info Modal -->
<div class="modal fade" id="userInfoModal" tabindex="-1" aria-labelledby="userInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius:24px;">
      <div class="modal-body p-5">
        <div class="d-flex align-items-center mb-4">
          <div style="background: #e5d6d6; border-radius: 50%; width: 104px; height: 104px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-person" style="color: #7a2320; font-size: 3.6rem;"></i>
          </div>
          <div class="ms-4 flex-grow-1">
            <div class="d-flex align-items-center">
              <h2 class="fw-bold mb-0" id="profileFullName"><?= htmlspecialchars($profile['full_name'] ?? '') ?></h2>
              <button type="button" class="btn btn-outline-secondary btn-sm ms-3" title="Edit"><i class="bi bi-pencil"></i></button>
            </div>
            <div id="profileDepartment"><?= htmlspecialchars($profile['department'] ?? 'N/A') ?></div>
            <div id="profileJobTitle"><?= htmlspecialchars($profile['job_title'] ?? 'N/A') ?></div>
            <div class="mt-1">
                <span class="text-muted">employee1@gmail.com</span>
                <span class="ms-2">
                    <i class="bi bi-star-fill text-warning"></i>
                    <i class="bi bi-star-fill text-warning"></i>
                    <i class="bi bi-star-fill text-warning"></i>
                    <i class="bi bi-star-fill text-warning"></i>
                    <i class="bi bi-star text-warning"></i>
                    <span class="ms-1">4.0</span>
                </span>
            </div>
          </div>
        </div>
        <hr>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Date of Birth</label>
            <div class="form-control bg-light" id="modalDob" readonly></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Mobile Number</label>
            <div class="form-control bg-light" id="modalMobile" readonly></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Gender</label>
            <div class="form-control bg-light" id="modalGender" readonly></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email Address</label>
            <div class="form-control bg-light" id="modalEmail" readonly></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Civil Status</label>
            <div class="form-control bg-light" id="modalCivilStatus" readonly></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Address</label>
            <div class="form-control bg-light" id="modalAddress" readonly></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nationality</label>
            <div class="form-control bg-light" id="modalNationality" readonly></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Rate Modal -->
<div class="modal fade" id="rateModal" tabindex="-1" aria-labelledby="rateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="rateForm">
        <div class="modal-header">
          <h5 class="modal-title" id="rateModalLabel">Rate Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="user_id" id="rateUserId">
          <div class="mb-3">
            <label class="form-label">Employee</label>
            <input type="text" class="form-control" id="rateUserName" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Punctuality</label>
            <select class="form-select" name="punctuality" id="ratePunctuality" required>
              <option value="">Select rating</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Work Quality</label>
            <select class="form-select" name="work_quality" id="rateWorkQuality" required>
              <option value="">Select rating</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Productivity</label>
            <select class="form-select" name="productivity" id="rateProductivity" required>
              <option value="">Select rating</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Teamwork</label>
            <select class="form-select" name="teamwork" id="rateTeamwork" required>
              <option value="">Select rating</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Professionalism</label>
            <select class="form-select" name="professionalism" id="rateProfessionalism" required>
              <option value="">Select rating</option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Rating</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Toast Container -->
<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
  <div id="statusToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="statusToastBody">
        <!-- Message goes here -->
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-mgr-link');
    const dashboardContent = document.getElementById('dashboardContent');
    const employeeList = document.getElementById('employeeListContainer');
    const attendanceNav = Array.from(document.querySelectorAll('.nav-mgr-link')).find(l => l.textContent.trim().includes('Attendance'));
    const attendanceList = document.getElementById('attendanceListContainer');
    const attendanceRequestsBtn = document.querySelector('.btn.btn-success[href="#"]');
    const attendanceRequestsPage = document.getElementById('attendanceRequestsPage');
    const backToAttendanceBtn = document.getElementById('backToAttendanceBtn');

    // Add this function to hide all main content sections
    function hideAllMainSections() {
        document.getElementById('dashboardContent').style.display = 'none';
        document.getElementById('employeeListContainer').style.display = 'none';
        document.getElementById('attendanceListContainer').style.display = 'none';
        document.getElementById('attendanceRequestsPage').style.display = 'none';
        document.getElementById('leaveListContainer').style.display = 'none';
        document.getElementById('resignationListContainer').style.display = 'none';
        document.getElementById('notificationListContainer').style.display = 'none';
    }

    // Update all nav click handlers to use this function
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            hideAllMainSections();
            if (this.textContent.trim().includes('Dashboard')) {
                document.getElementById('dashboardContent').style.display = 'block';
            } else if (this.textContent.trim().includes('Employee')) {
                document.getElementById('employeeListContainer').style.display = 'block';
            } else if (this.textContent.trim().includes('Attendance')) {
                document.getElementById('attendanceListContainer').style.display = 'block';
            } else if (this.textContent.trim().includes('Leave')) {
                document.getElementById('leaveListContainer').style.display = 'block';
            } else if (this.textContent.trim().includes('Resignation')) {
                document.getElementById('resignationListContainer').style.display = 'block';
            } else if (this.textContent.trim().includes('Notification')) {
                document.getElementById('notificationListContainer').style.display = 'block';
            }
        });
    });

    document.querySelectorAll('.view-user-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-userid');
            fetch('ajax/get_user_info.php?user_id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data && !data.error) {
                        document.getElementById('userInfoModalLabel').textContent = data.full_name;
                        document.getElementById('modalFullName').textContent = data.full_name;
                        document.getElementById('modalDepartment').textContent = data.department || '—';
                        document.getElementById('modalJobTitle').textContent = data.job_title || '';
                        document.getElementById('modalDob').textContent = data.date_of_birth || '';
                        document.getElementById('modalMobile').textContent = data.mobile_number || '';
                        document.getElementById('modalGender').textContent = data.gender || '';
                        document.getElementById('modalEmail').textContent = data.email || '';
                        document.getElementById('modalCivilStatus').textContent = data.civil_status || '';
                        document.getElementById('modalAddress').textContent = data.address || '';
                        document.getElementById('modalNationality').textContent = data.nationality || '';
                        var modal = new bootstrap.Modal(document.getElementById('userInfoModal'));
                        modal.show();
                    } else {
                        alert('User info not found.');
                    }
                });
        });
    });

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
            var modal = new bootstrap.Modal(document.getElementById('attendanceRecordModal'));
            modal.show();
        });
    });

    if (attendanceRequestsBtn && attendanceRequestsPage && attendanceList) {
        attendanceRequestsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideAllMainSections();
            attendanceRequestsPage.style.display = 'block';
        });
    }
    if (backToAttendanceBtn && attendanceRequestsPage && attendanceList) {
        backToAttendanceBtn.addEventListener('click', function() {
            hideAllMainSections();
            attendanceList.style.display = 'block';
        });
    }

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
            var modal = new bootstrap.Modal(document.getElementById('leaveRecordModal'));
            modal.show();
        });
    });

    // Add nav logic for Leave
    const leaveNav = Array.from(document.querySelectorAll('.nav-mgr-link')).find(l => l.textContent.trim().includes('Leave'));
    const leaveList = document.getElementById('leaveListContainer');
    leaveNav.addEventListener('click', function(e) {
        e.preventDefault();
        navLinks.forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        hideAllMainSections();
        leaveList.style.display = 'block';
    });

    // Add nav logic for Resignation
    const resignationNav = Array.from(document.querySelectorAll('.nav-mgr-link')).find(l => l.textContent.trim().includes('Resignation'));
    const resignationList = document.getElementById('resignationListContainer');
    resignationNav.addEventListener('click', function(e) {
        e.preventDefault();
        navLinks.forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        hideAllMainSections();
        resignationList.style.display = 'block';
    });

    const notificationNav = Array.from(document.querySelectorAll('.nav-mgr-link')).find(l => l.textContent.trim().includes('Notification'));
    const notificationList = document.getElementById('notificationListContainer');
    notificationNav.addEventListener('click', function(e) {
        e.preventDefault();
        navLinks.forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        hideAllMainSections();
        notificationList.style.display = 'block';
    });

    document.querySelectorAll('.rate-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('rateUserId').value = this.dataset.userid;
            document.getElementById('rateUserName').value = this.dataset.username;
            // Optionally clear previous values
            document.getElementById('ratePunctuality').value = '';
            document.getElementById('rateWorkQuality').value = '';
            document.getElementById('rateProductivity').value = '';
            document.getElementById('rateTeamwork').value = '';
            document.getElementById('rateProfessionalism').value = '';
            var modal = new bootstrap.Modal(document.getElementById('rateModal'));
            modal.show();
        });
    });

    document.getElementById('rateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        fetch('ajax/rate_employee.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to save rating.');
            }
        })
        .catch(() => alert('Failed to save rating.'));
    });

    function showStatusToast(message, isError = false) {
        var toastEl = document.getElementById('statusToast');
        var toastBody = document.getElementById('statusToastBody');
        toastBody.textContent = message;
        toastEl.classList.remove('text-bg-primary', 'text-bg-danger', 'text-bg-success');
        toastEl.classList.add(isError ? 'text-bg-danger' : 'text-bg-success');
        var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }

    // Handle leave status updates
    document.querySelectorAll('.leave-status-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Let the form submit normally
            return true;
        });
    });

    // Handle resignation status updates
    document.querySelectorAll('.resignation-status-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Let the form submit normally
            return true;
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 