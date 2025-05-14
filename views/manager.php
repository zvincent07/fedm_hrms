<?php
session_start();
require_once('../config/db.php');

// Only allow access for Manager role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'manager') {
    header('Location: /fedm_hrms/views/manager.php');
    exit();
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
                        <?php foreach ($top_employees as $emp): ?>
                            <li><?= htmlspecialchars($emp['full_name']) ?>
                                <span style="margin-left:8px;">
                                    <?php for ($i = 0; $i < 5; $i++): ?><span class="star">&#9733;</span><?php endfor; ?>
                                    <?= number_format($emp['manager_rating'], 1) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h5 class="fw-bold mb-3">Recent Notifications</h5>
                        <ul class="list-group">
                        <?php foreach ($notifications as $notif): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($notif['title']) ?></strong><br>
                                <span class="text-muted small"><?= htmlspecialchars($notif['content']) ?></span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h5 class="fw-bold mb-3">Quick Actions</h5>
                        <div class="quick-action">
                            <a href="#" class="btn btn-outline-primary">Add Employee</a>
                            <a href="#" class="btn btn-outline-success">Approve Leave</a>
                            <a href="#" class="btn btn-outline-secondary">View Logs</a>
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
                            <th>Role</th>
                            <th>Department</th>
                            <th>Job Title</th>
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
                        $userQuery = "SELECT ua.user_id, ua.full_name, ua.email, r.name AS role_name, d.name AS department_name, jr.title AS job_title
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
                                echo "<td>" . htmlspecialchars($user['role_name'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($user['department_name'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($user['job_title'] ?? 'N/A') . "</td>";
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
                                $isPending = strtolower($req['status']) === 'pending';
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
                                if ($isPending) {
                                    echo '<form method="post" style="display:inline;">';
                                    echo '<input type="hidden" name="mod_id" value="' . $req['modification_id'] . '">';
                                    echo '<button type="submit" name="approve_mod" class="btn btn-success btn-sm me-1" title="Approve"><i class="bi bi-check-circle"></i></button>';
                                    echo '<button type="submit" name="reject_mod" class="btn btn-danger btn-sm" title="Reject"><i class="bi bi-x-circle"></i></button>';
                                    echo '</form>';
                                } else {
                                    echo '<span style="font-size:1.5rem;">&mdash;</span>';
                                }
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
              <h2 class="fw-bold mb-0" id="modalFullName"></h2>
              <button type="button" class="btn btn-outline-secondary btn-sm ms-3" title="Edit"><i class="bi bi-pencil"></i></button>
            </div>
            <div id="modalDepartment" class="text-muted" style="font-size:1.1rem;"></div>
            <div id="modalJobTitle" class="fw-semibold" style="font-size:1.1rem;"></div>
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
          <div class="col-md-6 d-flex align-items-end">
            <a href="#" class="text-primary ms-3 mb-2" style="font-size:1rem; font-weight:500; white-space:nowrap;">Change password</a>
          </div>
        </div>
      </div>
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

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            if (this.textContent.trim().includes('Employee')) {
                dashboardContent.style.display = 'none';
                employeeList.style.display = 'block';
            } else if (this.textContent.trim().includes('Dashboard')) {
                dashboardContent.style.display = 'block';
                employeeList.style.display = 'none';
            } else if (this.textContent.trim().includes('Attendance')) {
                dashboardContent.style.display = 'none';
                employeeList.style.display = 'none';
                attendanceList.style.display = 'block';
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
            attendanceList.style.display = 'none';
            attendanceRequestsPage.style.display = 'block';
        });
    }
    if (backToAttendanceBtn && attendanceRequestsPage && attendanceList) {
        backToAttendanceBtn.addEventListener('click', function() {
            attendanceRequestsPage.style.display = 'none';
            attendanceList.style.display = 'block';
        });
    }
});
</script>
<?php
// Handle approve/reject POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mod_id'])) {
  $mod_id = intval($_POST['mod_id']);
  $new_status = isset($_POST['approve_mod']) ? 'approved' : (isset($_POST['reject_mod']) ? 'rejected' : '');
  if ($new_status) {
    mysqli_query($conn, "UPDATE attendance_modification SET status = '$new_status' WHERE modification_id = $mod_id");
    echo "<script>location.reload();</script>";
    exit();
  }
}
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 