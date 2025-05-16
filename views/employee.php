<?php
// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Manila'); // Set to your local timezone
// Include database connection
require_once('../config/db.php');

// Example dynamic data (replace with your actual data source)
$attendance = ['checked_in' => false];
$leaves = [
    'Annual Leave' => 2,
    'Sick Leave' => 0,
    'Compassionate Leave' => 5
];
$leave_max = [
    'Annual Leave' => 10,
    'Sick Leave' => 10,
    'Compassionate Leave' => 10
];
$todos = [
    'Complete Onboarding Document Upload',
    'Follow up on clients on documents',
    'Design wireframes for LMS',
    'Create case study for next IT project',
    'Follow up on clients on documents'
];

// --- Attendance Logic ---
session_start();

// Get user information
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
$user_id = $_SESSION['user_id'];

// Get today's attendance record
$today = date('Y-m-d');
$attendance_query = "SELECT * FROM attendance WHERE employee_id = $user_id AND date = '$today'";
$attendance_result = mysqli_query($conn, $attendance_query);
$attendance_log = mysqli_fetch_assoc($attendance_result);

if (!$attendance_log) {
    $attendance_log = [
        'check_in' => null,
        'check_out' => null,
        'late' => null,
        'undertime' => null,
        'overtime' => null,
        'work_hours' => null,
        'status' => null
    ];
}

$work_start = strtotime(date('Y-m-d') . ' 08:00:00');
$work_end = strtotime(date('Y-m-d') . ' 16:00:00');
$lunch_break = 3600; // 1 hour in seconds

// Handle Time In
if (isset($_POST['time_in'])) {
    $now = time();
    $current_hour = (int)date('H', $now);
    $current_minute = (int)date('i', $now);
    
    // Check if current time is between 4 PM and midnight
    if ($current_hour >= 16 && $current_hour < 24) {
        $_SESSION['error_message'] = "Cannot time in between 4:00 PM and midnight. Please wait until tomorrow's working hours.";
    } else {
        // Check if already timed in today
        if ($attendance_log && $attendance_log['check_in']) {
            $_SESSION['error_message'] = "You have already timed in today.";
        } else {
            $time_in = date('Y-m-d H:i:s', $now);
            $status = ($now > $work_start) ? 'late' : 'present';
            
            // Insert new attendance record
            $insert_query = "INSERT INTO attendance (employee_id, date, check_in, status) 
                           VALUES ($user_id, '$today', '$time_in', '$status')";
            
            if (mysqli_query($conn, $insert_query)) {
                // Refresh the page to show updated status
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['error_message'] = "Error recording time in. Please try again.";
            }
        }
    }
}

// Handle Time Out
if (isset($_POST['time_out']) && $attendance_log && $attendance_log['check_in']) {
    $now = time();
    $time_in = strtotime($attendance_log['check_in']);
    $hours_passed = ($now - $time_in) / 3600;

    // Check if at least 4 hours have passed since time in
    if ($hours_passed < 4) {
        $_SESSION['error_message'] = "Cannot time out. You must work at least 4 hours from your time in.";
    } else {
        $time_out = date('Y-m-d H:i:s', $now);
        
        // Calculate work hours and status
        $work_seconds = $now - $time_in - $lunch_break;
        $work_hours = round($work_seconds / 3600, 2);
        
        if ($work_seconds < 8 * 3600) {
            $status = 'undertime';
        } elseif ($work_seconds == 8 * 3600) {
            $status = ($attendance_log['status'] === 'late') ? 'late' : 'present';
        } else {
            $status = 'overtime';
        }
        
        // Update attendance record
        $update_query = "UPDATE attendance 
                        SET check_out = '$time_out', 
                            status = '$status',
                            work_hours = $work_hours 
                        WHERE employee_id = $user_id 
                        AND date = '$today'";
        
        if (mysqli_query($conn, $update_query)) {
            // Refresh the page to show updated status
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error_message'] = "Error recording time out. Please try again.";
        }
    }
}

// --- To-do Logic ---
if (!isset($_SESSION['todos'])) {
    $_SESSION['todos'] = [];
}
if (!isset($_SESSION['todos'][$user_id])) {
    $_SESSION['todos'][$user_id] = [];
}
$todos = &$_SESSION['todos'][$user_id];

// Add todo
if (isset($_POST['add_todo'])) {
    $task = trim($_POST['task']);
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    
    if (!empty($task)) {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("User not logged in");
            }
            
            $stmt = $conn->prepare("INSERT INTO todos (employee_id, task, due_date) VALUES (?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            $stmt->bind_param("iss", $_SESSION['user_id'], $task, $due_date);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }
            
            $_SESSION['success_message'] = "Task added successfully!";
            $stmt->close();
            
            // Redirect to refresh the page
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error adding task: " . $e->getMessage();
        }
    }
}

// Fetch todos for the current user
$todos = [];
try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not logged in");
    }
    
    $stmt = $conn->prepare("SELECT * FROM todos WHERE employee_id = ? ORDER BY due_date ASC, created_at DESC");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $todos[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching todos: " . $e->getMessage();
    // Initialize empty todos array to prevent undefined variable errors
    $todos = [];
}

// Delete to-do
if (isset($_POST['delete_todo']) && isset($_POST['todo_index'])) {
    $idx = (int)$_POST['todo_index'];
    if (isset($todos[$idx])) unset($todos[$idx]);
    $todos = array_values($todos); // reindex
}
// Start editing to-do
if (isset($_POST['edit_todo']) && isset($_POST['todo_index'])) {
    $idx = (int)$_POST['todo_index'];
    foreach ($todos as &$todo) $todo['editing'] = false;
    if (isset($todos[$idx])) $todos[$idx]['editing'] = true;
}
// Save edited to-do
if (isset($_POST['save_todo']) && isset($_POST['todo_index']) && isset($_POST['edit_text'])) {
    $idx = (int)$_POST['todo_index'];
    if (isset($todos[$idx])) {
        $todos[$idx]['text'] = trim($_POST['edit_text']);
        $todos[$idx]['editing'] = false;
    }
}
// Cancel editing
if (isset($_POST['cancel_edit']) && isset($_POST['todo_index'])) {
    $idx = (int)$_POST['todo_index'];
    if (isset($todos[$idx])) $todos[$idx]['editing'] = false;
}
// Update status
if (isset($_POST['update_status']) && isset($_POST['todo_id']) && isset($_POST['status'])) {
    $todo_id = $_POST['todo_id'];
    $new_status = $_POST['status'];
    $employee_id = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("UPDATE todos SET status = ? WHERE todo_id = ? AND employee_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("sii", $new_status, $todo_id, $employee_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update status: " . $stmt->error);
        }
        
        $_SESSION['success_message'] = "Task status updated successfully!";
        $stmt->close();
        
        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating task status: " . $e->getMessage();
    }
}

// Toggle todo status
if (isset($_POST['toggle_todo'])) {
    $todo_id = $_POST['todo_id'];
    $employee_id = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("UPDATE todos SET status = CASE WHEN status = 'completed' THEN 'pending' ELSE 'completed' END WHERE todo_id = ? AND employee_id = ?");
        $stmt->bind_param("ii", $todo_id, $employee_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Task status updated!";
        } else {
            $_SESSION['error_message'] = "Error updating task status: " . $stmt->error;
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating task status: " . $e->getMessage();
    }
}

// Delete todo
if (isset($_POST['delete_todo'])) {
    $todo_id = $_POST['todo_id'];
    $employee_id = $_SESSION['user_id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM todos WHERE todo_id = ? AND employee_id = ?");
        $stmt->bind_param("ii", $todo_id, $employee_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Task deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting task: " . $stmt->error;
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error deleting task: " . $e->getMessage();
    }
}

// --- Attendance Modification Request Logic ---
if (isset($_POST['submit_mod_request'])) {
    try {
        // Get form data
        $employee_id = $_SESSION['user_id'];
        $date_of_attendance = $_POST['date_of_attendance'];
        $original_time_in = !empty($_POST['original_time_in']) ? $_POST['original_time_in'] : null;
        $original_time_out = !empty($_POST['original_time_out']) ? $_POST['original_time_out'] : null;
        $requested_time_in = $_POST['requested_time_in'] ?? null;
        $requested_time_out = $_POST['requested_time_out'] ?? null;
        $reason = $_POST['reason'] ?? '';
        if (empty($reason)) {
            $_SESSION['error_message'] = "Reason is required for attendance modification.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        $remarks = $_POST['remarks'] ?? '';
        
        // Calculate total_hours in PHP if both requested times are provided
        if (!empty($requested_time_in) && !empty($requested_time_out)) {
            $in_parts = explode(':', $requested_time_in);
            $out_parts = explode(':', $requested_time_out);
            $in_seconds = ($in_parts[0] * 3600) + ($in_parts[1] * 60);
            $out_seconds = ($out_parts[0] * 3600) + ($out_parts[1] * 60);
            $diff = $out_seconds - $in_seconds;
            if ($diff < 0) $diff += 24 * 3600; // handle overnight
            $total_hours = round($diff / 3600, 2);
        } else {
            $total_hours = null;
        }
        
        // Handle file upload if present
        $evidence_file = null;
        if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/attendance_evidence/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION);
            $evidence_file = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $evidence_file;
            
            if (move_uploaded_file($_FILES['evidence']['tmp_name'], $upload_path)) {
                $evidence_file = 'uploads/attendance_evidence/' . $evidence_file;
            }
        }

        // Insert into database
        error_log('Reason from POST: ' . $_POST['reason']);
        error_log('Reason variable: ' . $reason);
        $stmt = $conn->prepare("INSERT INTO attendance_modification (
            employee_id, 
            date_of_attendance,
            original_time_in,
            original_time_out,
            requested_time_in,
            requested_time_out,
            total_hours,
            remarks,
            reason,
            evidence_file,
            status,
            requested_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        $stmt->bind_param(
            "issssssdss",
            $employee_id,
            $date_of_attendance,
            $original_time_in,
            $original_time_out,
            $requested_time_in,
            $requested_time_out,
            $total_hours,
            $remarks,
            $reason,
            $evidence_file
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to submit request: " . $stmt->error);
        }

        $_SESSION['success_message'] = "Modification request submitted successfully!";
        $stmt->close();
        
        // Redirect to refresh the page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error submitting request: " . $e->getMessage();
    }
}

// Fetch modification requests for the current user
$mod_requests = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            am.*,
            u.full_name as employee_name,
            d.name as department_name,
            j.title as position
        FROM attendance_modification am
        LEFT JOIN user_account u ON am.employee_id = u.user_id
        LEFT JOIN department d ON u.department_id = d.department_id
        LEFT JOIN job_role j ON u.job_role_id = j.job_role_id
        WHERE am.employee_id = ?
        ORDER BY am.requested_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception("Failed to fetch requests: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $mod_requests[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching modification requests: " . $e->getMessage();
}

// Get user information for the modification form
$user_info = null;
try {
    $stmt = $conn->prepare("
        SELECT 
            u.full_name,
            d.name as department_name,
            j.title as position
        FROM user_account u
        LEFT JOIN department d ON u.department_id = d.department_id
        LEFT JOIN job_role j ON u.job_role_id = j.job_role_id
        WHERE u.user_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception("Failed to fetch user info: " . $stmt->error);
    }
    
    $user_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching user information: " . $e->getMessage();
}

// Helper function for status color
function getStatusColor($status) {
    switch ($status) {
        case 'Done':
            return 'background:#198754;color:#fff;'; // Bootstrap green
        case 'Pending':
            return 'background:#ffc107;color:#212529;'; // Bootstrap yellow
        default:
            return 'background:#e9ecef;color:#6c757d;'; // Gray
    }
}

// Display error message if any
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger mb-3">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']);
}

// Display success message if any
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success mb-3">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
    unset($_SESSION['success_message']);
}

// Fetch all attendance records for the current user (attendance history)
$attendance_history = [];
try {
    $stmt = $conn->prepare("SELECT date, check_in, check_out, status FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 30");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $attendance_history[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    // Optionally handle error
}

// Handle cancellation of modification request
if (isset($_POST['cancel_mod_request']) && isset($_POST['modification_id'])) {
    $modification_id = (int)$_POST['modification_id'];
    $employee_id = $_SESSION['user_id'];
    try {
        $stmt = $conn->prepare("UPDATE attendance_modification SET status = 'cancelled' WHERE modification_id = ? AND employee_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $modification_id, $employee_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Modification request cancelled.";
        } else {
            $_SESSION['error_message'] = "Error cancelling request: " . $stmt->error;
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error cancelling request: " . $e->getMessage();
    }
}

// Fetch existing resignation for this user
$user_resignation = null;
try {
    $stmt = $conn->prepare("SELECT * FROM resignation WHERE employee_id = ? ORDER BY submitted_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $user_resignation = $result->fetch_assoc();
    }
    $stmt->close();
} catch (Exception $e) {
    // Optionally handle error
}
// Handle edit resignation
if (isset($_POST['edit_resignation'])) {
    $_SESSION['edit_resignation'] = true;
}
// Handle cancel resignation
if (isset($_POST['cancel_resignation']) && $user_resignation && $user_resignation['status'] === 'pending') {
    try {
        $stmt = $conn->prepare("UPDATE resignation SET status = 'cancelled' WHERE resignation_id = ? AND employee_id = ?");
        $stmt->bind_param("ii", $user_resignation['resignation_id'], $user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success_message'] = "Resignation cancelled.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error cancelling resignation: " . $e->getMessage();
    }
}

// Handle resignation submission (new or edit)
if (isset($_POST['submit_resignation'])) {
    try {
        $employee_id = $_SESSION['user_id'];
        $reason = $_POST['resignation_reason'];
        $comments = $_POST['resignation_comments'] ?? '';
        $is_edit = isset($_SESSION['edit_resignation']) && $user_resignation && $user_resignation['status'] === 'pending';
        $is_reapply = $user_resignation && $user_resignation['status'] === 'cancelled';
        // Handle file upload if present
        $resignation_letter = ($user_resignation && !$is_reapply) ? $user_resignation['resignation_letter'] : null;
        if (isset($_FILES['resignation_letter']) && $_FILES['resignation_letter']['error'] === UPLOAD_ERR_OK) {
            $base_dir = '/Applications/XAMPP/xamppfiles/htdocs/fedm_hrms/';
            $upload_dir = $base_dir . 'uploads/resignation_letters/';
            if (!is_dir($upload_dir)) throw new Exception("Upload directory does not exist at: " . $upload_dir);
            if (!is_writable($upload_dir)) throw new Exception("Upload directory is not writable at: " . $upload_dir);
            $file_extension = strtolower(pathinfo($_FILES['resignation_letter']['name'], PATHINFO_EXTENSION));
            if ($file_extension !== 'pdf') throw new Exception("Only PDF files are allowed for resignation letters.");
            $resignation_letter = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $resignation_letter;
            if (!move_uploaded_file($_FILES['resignation_letter']['tmp_name'], $upload_path)) {
                $upload_error = error_get_last();
                throw new Exception("Failed to upload resignation letter. Error: " . ($upload_error ? $upload_error['message'] : 'Unknown error'));
            }
            $resignation_letter = 'uploads/resignation_letters/' . $resignation_letter;
        }
        if ($is_edit) {
            // Update existing resignation (pending)
            $stmt = $conn->prepare("UPDATE resignation SET reason = ?, comments = ?, resignation_letter = ? WHERE resignation_id = ? AND employee_id = ? AND status = 'pending'");
            $stmt->bind_param("sssii", $reason, $comments, $resignation_letter, $user_resignation['resignation_id'], $employee_id);
            $stmt->execute();
            $stmt->close();
            unset($_SESSION['edit_resignation']);
            $_SESSION['success_message'] = "Resignation updated successfully.";
        } else if ($is_reapply) {
            // Reapply after cancellation: update the cancelled record
            $stmt = $conn->prepare("UPDATE resignation SET reason = ?, comments = ?, resignation_letter = ?, status = 'pending', submitted_at = NOW(), processed_at = NULL, processed_by = NULL, process_remarks = NULL WHERE resignation_id = ? AND employee_id = ? AND status = 'cancelled'");
            $stmt->bind_param("sssii", $reason, $comments, $resignation_letter, $user_resignation['resignation_id'], $employee_id);
            if (!$stmt->execute()) throw new Exception("Failed to reapply resignation: " . $stmt->error);
            $stmt->close();
            $_SESSION['success_message'] = "Resignation submitted successfully. HR will review your request.";
        } else {
            // Insert new resignation (first time)
            $stmt = $conn->prepare("INSERT INTO resignation (employee_id, reason, comments, resignation_letter, status, submitted_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("isss", $employee_id, $reason, $comments, $resignation_letter);
            if (!$stmt->execute()) throw new Exception("Failed to submit resignation: " . $stmt->error);
            $stmt->close();
            $_SESSION['success_message'] = "Resignation submitted successfully. HR will review your request.";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error submitting resignation: " . $e->getMessage();
    }
}

// After the PHP logic for handling edit_resignation
if (isset($_SESSION['edit_resignation']) && $_SESSION['edit_resignation']) {
    echo '<script>window.addEventListener("DOMContentLoaded", function() { var modal = new bootstrap.Modal(document.getElementById("resignationModal")); modal.show(); });</script>';
    unset($_SESSION['edit_resignation']);
}

// LEAVE APPLICATION LOGIC
if (isset($_POST['submit_leave'])) {
    // Assume $_POST['leave_type'], $_POST['start_date'], $_POST['end_date'], $_SESSION['user_id']
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $advance_days = 3;

    // Set leave limits
    $leave_limits = [
        'Vacation Leave' => 15,
        'Sick Leave' => 15,
        'Emergency Leave' => 5,
        'Maternity Leave' => 105,
        'Paternity Leave' => 7,
    ];

    // Date validation
    if ($leave_type !== 'Sick Leave') {
        $min_date = date('Y-m-d', strtotime("+$advance_days days"));
        if ($start_date < $min_date) {
            die('Leave must be filed at least 3 days in advance.');
        }
        if ($start_date < $today) {
            die('Past dates are not allowed for this leave type.');
        }
    }

    // Yearly leave limit validation
    $year = date('Y');
    $stmt = $conn->prepare('SELECT SUM(duration) FROM leave_application WHERE employee_id = ? AND leave_type = ? AND YEAR(start_date) = ? AND status IN ("approved", "pending")');
    $stmt->bind_param('iss', $user_id, $leave_type, $year);
    $stmt->execute();
    $stmt->bind_result($used_days);
    $stmt->fetch();
    $stmt->close();

    $used_days = $used_days ?: 0;
    $new_days = (strtotime($end_date) - strtotime($start_date)) / (60*60*24) + 1;
    if ($used_days + $new_days > $leave_limits[$leave_type]) {
        die('You have exceeded the annual limit for this leave type.');
    }

    $employee_id = $_SESSION['user_id'];
    $duration = $_POST['duration'];
    $resumption_date = $_POST['resumption_date'];
    $reason = $_POST['reason'];
    $status = 'pending';
    $applied_at = date('Y-m-d H:i:s');
    $handover_doc = null;

    // Handle file upload
    if (isset($_FILES['handover_doc']) && $_FILES['handover_doc']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/leave_handover/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['handover_doc']['name'], PATHINFO_EXTENSION);
        $handover_doc = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $handover_doc;
        move_uploaded_file($_FILES['handover_doc']['tmp_name'], $upload_path);
        $handover_doc = 'uploads/leave_handover/' . $handover_doc;
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO leave_application (employee_id, leave_type, start_date, end_date, duration, resumption_date, reason, handover_doc, status, applied_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssisssss", $employee_id, $leave_type, $start_date, $end_date, $duration, $resumption_date, $reason, $handover_doc, $status, $applied_at);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Leave application submitted successfully!';
    } else {
        $_SESSION['error_message'] = 'Error submitting leave application: ' . $stmt->error;
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Dashboard leave types and limits
$leave_types = [
    'Vacation Leave' => 15,
    'Sick Leave' => 15,
    'Emergency Leave' => 5
];

$leave_used = [];
$year = date('Y');
foreach ($leave_types as $type => $max) {
    $stmt = $conn->prepare("SELECT SUM(duration) as used FROM leave_application WHERE employee_id = ? AND leave_type = ? AND YEAR(start_date) = ? AND status = 'approved'");
    $stmt->bind_param("isi", $user_id, $type, $year);
    $stmt->execute();
    $stmt->bind_result($used);
    $stmt->fetch();
    $stmt->close();
    $leave_used[$type] = $used ?: 0;
}

// --- PROFILE LOGIC ---
// Fetch user profile data
$profile = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            u.full_name, 
            u.email, 
            u.date_of_birth, 
            u.mobile_number, 
            u.gender, 
            u.civil_status, 
            u.address, 
            u.nationality, 
            u.manager_rating, 
            d.name as department, 
            j.title as job_title, 
            u.employment_type 
        FROM user_account u 
        LEFT JOIN department d ON u.department_id = d.department_id 
        LEFT JOIN job_role j ON u.job_role_id = j.job_role_id 
        WHERE u.user_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching profile: " . $e->getMessage();
    $profile = [];
}
// Check for incomplete personal information
$incomplete_profile = false;
$required_profile_fields = ['date_of_birth', 'mobile_number', 'gender', 'civil_status', 'address', 'nationality'];
foreach ($required_profile_fields as $field) {
    if (empty($profile[$field])) {
        $incomplete_profile = true;
        break;
    }
}
// Fetch roles and departments for dropdowns
$role_options = [];
$department_options = [];
$job_role_options = [];
try {
    $res = $conn->query("SELECT role_id, name FROM role ORDER BY name ASC");
    while ($row = $res->fetch_assoc()) $role_options[] = $row;
    $res = $conn->query("SELECT department_id, name FROM department ORDER BY name ASC");
    while ($row = $res->fetch_assoc()) $department_options[] = $row;
    $res = $conn->query("SELECT job_role_id, title FROM job_role ORDER BY title ASC");
    while ($row = $res->fetch_assoc()) $job_role_options[] = $row;
} catch (Exception $e) {}
// Handle profile update (now includes name, role, department, job title, employment type)
if (isset($_POST['save_profile'])) {
    try {
        $update_password = !empty($_POST['password']);
        if ($update_password) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                UPDATE user_account 
                SET 
                    date_of_birth = ?,
                    mobile_number = ?,
                    gender = ?,
                    civil_status = ?,
                    address = ?,
                    nationality = ?,
                    password = ?
                WHERE user_id = ?
            ");
            if (!$stmt) {
                throw new Exception("Failed to prepare update statement: " . $conn->error);
            }
            $stmt->bind_param(
                "sssssssi",
                $_POST['date_of_birth'],
                $_POST['mobile_number'],
                $_POST['gender'],
                $_POST['civil_status'],
                $_POST['address'],
                $_POST['nationality'],
                $hashed_password,
                $user_id
            );
        } else {
            $stmt = $conn->prepare("
                UPDATE user_account 
                SET 
                    date_of_birth = ?,
                    mobile_number = ?,
                    gender = ?,
                    civil_status = ?,
                    address = ?,
                    nationality = ?
                WHERE user_id = ?
            ");
            if (!$stmt) {
                throw new Exception("Failed to prepare update statement: " . $conn->error);
            }
            $stmt->bind_param(
                "ssssssi",
                $_POST['date_of_birth'],
                $_POST['mobile_number'],
                $_POST['gender'],
                $_POST['civil_status'],
                $_POST['address'],
                $_POST['nationality'],
                $user_id
            );
        }
        if (!$stmt->execute()) {
            throw new Exception("Failed to update profile: " . $stmt->error);
        }
        $_SESSION['success_message'] = "Profile updated successfully!";
        $stmt->close();
        // Refresh the page to show updated data
        header("Location: " . $_SERVER['PHP_SELF'] . "?show=profile");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating profile: " . $e->getMessage();
    }
}

// --- Change Password Logic ---
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $user_id = $_SESSION['user_id'];
    // Fetch current password hash from DB
    $stmt = $conn->prepare("SELECT password FROM user_account WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($password_hash);
    $stmt->fetch();
    $stmt->close();
    // Validate current password
    if (!password_verify($current_password, $password_hash)) {
        $_SESSION['change_password_error'] = 'Current password is incorrect.';
        header("Location: " . $_SERVER['PHP_SELF'] . "?show=profile");
        exit();
    }
    // Validate new password strength
    if (strlen($new_password) < 8 ||
        !preg_match('/[A-Za-z]/', $new_password) ||
        !preg_match('/[0-9]/', $new_password) ||
        !preg_match('/[^A-Za-z0-9]/', $new_password)) {
        $_SESSION['change_password_error'] = 'New password must be at least 8 characters long and include a combination of letters, numbers, and symbols.';
        header("Location: " . $_SERVER['PHP_SELF'] . "?show=profile");
        exit();
    }
    // Validate new password and confirmation match
    if ($new_password !== $confirm_new_password) {
        $_SESSION['change_password_error'] = 'New password and confirmation do not match.';
        header("Location: " . $_SERVER['PHP_SELF'] . "?show=profile");
        exit();
    }
    // All checks passed, update password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE user_account SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_password_hash, $user_id);
    if ($stmt->execute()) {
        $_SESSION['change_password_success'] = 'Password updated successfully!';
    } else {
        $_SESSION['change_password_error'] = 'Error updating password: ' . $stmt->error;
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?show=profile");
    exit();
}
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f3f3f3; }
        .dashboard-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px #0001; padding: 32px 32px 24px 32px; margin-bottom: 24px; min-height: 280px; }
        .btn-main { background: #a12a22; color: #fff; font-weight: bold; }
        .btn-main:disabled, .btn-disabled { background: #e5cfcf !important; color: #fff !important; border: none; }
        .progress-bar { background: #a12a22; }
        .edit-icon {
            font-size: 1.2rem;
            cursor: pointer;
        }
        .file-resignation-card {
            padding: 24px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0001;
            margin-bottom: 24px;
        }
        .todo-status-select {
            min-width: 110px;
            width: 140px;
        }
        /* Horizontal navbar styles */
        .custom-navbar {
            background: #fbeaea; /* light red */
            border-radius: 0 0 24px 24px;
            box-shadow: 0 4px 16px #0001;
            padding: 1.5rem 0 1rem 0;
            margin-bottom: 2rem;
        }
        .custom-nav {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 3rem;
            width: 100%;
        }
        .custom-nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #7a2320 !important;
            font-size: 1.25rem;
            padding: 0.5rem 2rem;
            border-radius: 16px;
            position: relative;
            background: none;
            transition: background 0.2s;
            text-decoration: none;
        }
        .custom-nav-link:active,
        .custom-nav-link:focus,
        .custom-nav-link:hover {
            text-decoration: none;
            color: #7a2320 !important;
            background: none !important;
        }
        .custom-nav-link.active {
            background: none !important;
            color: #7a2320 !important;
        }
        .custom-nav-link.active::after {
            content: '';
            display: block;
            position: absolute;
            left: 12px;
            right: 12px;
            bottom: 6px;
            height: 3px;
            border-radius: 2px;
            background: #a12a22;
        }
        .custom-nav-link i {
            font-size: 1.5rem;
        }
        .attendance-label, .leave-label { font-size: 1.15rem; font-weight: 500; }
        .attendance-time, .leave-count { font-size: 1.25rem; font-weight: 600; }
        .attendance-status-badge { font-size: 1.1rem !important; padding: 0.5em 1.2em; }
    </style>
</head>
<body>
    <!-- Horizontal Navbar -->
    <nav class="custom-navbar mb-4">
        <div class="container">
            <div class="custom-nav mx-auto">
                <a class="custom-nav-link active" href="#" data-page="dashboard">
                    <i class="bi bi-map"></i>
                    Dashboard
                </a>
                <a class="custom-nav-link" href="#" data-page="attendance">
                    <i class="bi bi-calendar-check"></i>
                    Attendance
                </a>
                <a class="custom-nav-link" href="#" data-page="leave">
                    <i class="bi bi-calendar-heart"></i>
                    Leave
                </a>
                <a class="custom-nav-link" href="#" data-page="notification">
                    <i class="bi bi-bell"></i>
                    Notification
                </a>
                <a class="custom-nav-link" href="#" data-page="profile">
                    <i class="bi bi-person"></i>
                    Profile
                </a>
                <form method="post" style="display:inline;">
    <button type="submit" name="logout" class="custom-nav-link" id="logout-btn" style="background:none; border:none; padding:0;">
        <i class="bi bi-box-arrow-right"></i>
        Logout
    </button>
</form>
            </div>
        </div>
    </nav>
    <div class="container">
        <!-- Dashboard Page -->
        <div id="dashboard-page" class="page-content">
            <div class="row g-4 align-items-stretch">
                <div class="col-md-6 h-100">
                    <div class="dashboard-card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Attendance</h5>
                            <div class="px-3 py-1 rounded-pill" style="background:#e5cfcf; color:#7a2320; font-weight:600;">
                                <?= date('l, F j, Y'); ?>
                            </div>
                        </div>
                        <?php
                        // Attendance UI
                        $log = $attendance_log;
                        $can_time_out = false;
                        if ($log['check_in']) {
                            $now = date('Y-m-d H:i');
                            $current_hour = (int)date('H');
                            if ($current_hour >= 16) {
                                $can_time_out = true;
                            }
                        }

                        // Check if current time is between 4 PM and midnight
                        $current_hour = (int)date('H');
                        $is_prohibited_hours = ($current_hour >= 16 && $current_hour < 24);
                        ?>
                        <form method="post" action="">
                            <?php if (!$log['check_in']): ?>
                                <button type="submit" name="time_in" class="btn btn-main btn-lg w-100 mb-2" <?= $is_prohibited_hours ? 'disabled' : '' ?>>
                                    Time In
                                </button>
                                <?php if ($is_prohibited_hours): ?>
                                    <div class="alert alert-warning mb-3">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        Time in is not allowed between this time. Please wait until tomorrow's working hours.
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <span class="attendance-label">Time In</span>
                                        <span class="attendance-time ms-2"><?= date('g:i A', strtotime($log['check_in'])) ?></span>
                                    </div>
                                    <span class="badge attendance-status-badge ms-2" style="background:
                                        <?php 
                                        switch($log['status']) {
                                            case 'present':
                                                echo '#198754';
                                                break;
                                            case 'late':
                                                echo '#ffc107;color:#212529;';
                                                break;
                                            case 'overtime':
                                                echo '#a12a22';
                                                break;
                                            case 'undertime':
                                                echo '#dc3545';
                                                break;
                                            default:
                                                echo '#e9ecef;color:#6c757d;';
                                        }
                                        ?>
                                    ">
                                        <?= ucfirst($log['status']) ?>
                                    </span>
                                </div>
                                <?php if (!$log['check_out']): ?>
                                    <div class="d-flex justify-content-between align-items-between mb-3">
                                        <span class="attendance-label">Expected Out</span>
                                        <span class="attendance-time"><?= date('g:i A', strtotime($log['check_in'] . ' +8 hours')) ?></span>
                                    </div>
                                    <?php
                                    $current_time = time();
                                    $time_in = strtotime($log['check_in']);
                                    $hours_passed = ($current_time - $time_in) / 3600;
                                    $can_time_out = ($hours_passed >= 4); // Can time out after 4 hours
                                    ?>
                                    <button type="submit" name="time_out" class="btn btn-main btn-lg w-100 mb-2" <?= $can_time_out ? '' : 'disabled' ?>>Time Out</button>
                                    <?php if (!$can_time_out): ?>
                                        <div class="text-danger small text-center">
                                            You can only time out after working for at least 4 hours from your time in.
                                            <?php
                                            $remaining_hours = 4 - $hours_passed;
                                            if ($remaining_hours > 0) {
                                                echo sprintf(" (%.1f hours remaining)", $remaining_hours);
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <span class="attendance-label">Time Out</span>
                                            <span class="attendance-time ms-2"><?= date('g:i A', strtotime($log['check_out'])) ?></span>
                                        </div>
                                        <?php if (isset($log['overtime']) && $log['overtime']): ?>
                                            <span class="badge attendance-status-badge ms-2" style="background:#a12a22;">Overtime</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </form>
                        <div class="d-flex justify-content-end mt-2">
                            <a href="#" class="fw-bold" data-bs-toggle="modal" data-bs-target="#modRequestModal" style="color: #a12a22;">
                                Attendance Modification Request
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 h-100">
                    <div class="dashboard-card h-100">
                        <h5 class="fw-bold mb-4">Number of Leave</h5>
                        <?php foreach ($leave_types as $type => $max): ?>
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <span class="leave-label"><?= htmlspecialchars($type) ?></span>
                                <span class="leave-count"><?= $leave_used[$type] ?>/<?= $max ?></span>
                            </div>
                            <div class="progress mb-4" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: <?= ($leave_used[$type]/$max)*100 ?>%"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <!-- To-dos -->
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">To-dos</h5>
                </div>
                
                <!-- Add new to-do form -->
                <form method="post" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-8">
                            <input type="text" name="task" class="form-control" placeholder="Add new task..." required>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="due_date" class="form-control" placeholder="Due date (optional)">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="add_todo" class="btn btn-main w-100">Add</button>
                        </div>
                    </div>
                </form>

                <?php if (empty($todos)): ?>
                    <p class="text-muted mb-0">No tasks yet. Add one to get started!</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($todos as $todo): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 <?php echo $todo['status'] === 'completed' ? 'text-decoration-line-through text-muted' : ''; ?>">
                                            <?php echo htmlspecialchars($todo['task']); ?>
                                        </h6>
                                        <?php if (!empty($todo['due_date'])): ?>
                                            <small class="text-muted">Due: <?php echo date('M d, Y', strtotime($todo['due_date'])); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $todo['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="completed" <?php echo $todo['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">
                                            <button type="submit" name="edit_todo" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">
                                            <button type="submit" name="delete_todo" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this task?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- File resignation -->
            <div class="file-resignation-card mb-4" style="background: #fff; border-radius: 16px; padding: 32px 32px 32px 32px; display: flex; align-items: center; justify-content: space-between;">
                <span class="fw-bold" style="font-size: 1.25rem; color: #555;">File resignation</span>
                <div class="d-flex align-items-center gap-2 ms-auto" style="height:48px;">
                <?php if (!$user_resignation || $user_resignation['status'] === 'cancelled'): ?>
                    <button class="btn btn-main" style="font-size: 1.3rem; border-radius: 16px; width: 120px; height: 56px;" data-bs-toggle="modal" data-bs-target="#resignationModal">Apply</button>
                <?php elseif ($user_resignation['status'] === 'approved'): ?>
                    <?php
                        $last_working_day = date('F d, Y', strtotime($user_resignation['processed_at'] . ' +30 days'));
                    ?>
                    <span style="font-size:1.1rem; font-weight:500; min-width:220px; text-align:center; display:flex; align-items:center; gap:0.5em;">
                        Last working Day on:
                        <span class="badge bg-success text-white" style="font-size:1.1rem; font-weight:600; border-radius:8px; padding:8px 16px;">
                            <?= htmlspecialchars($last_working_day) ?>
                        </span>
                    </span>
                <?php else: ?>
                    <span class="badge bg-light text-dark border px-3 py-2 d-flex align-items-center" style="font-size:1.1rem; font-weight:500; border-radius:8px; min-width:100px; height:44px; text-align:center;">
                        <?= htmlspecialchars(ucfirst($user_resignation['status'])) ?>
                    </span>
                    <?php if ($user_resignation['status'] === 'pending'): ?>
                        <form method="post" class="d-inline" id="cancel-resignation-form">
                            <input type="hidden" name="cancel_resignation" value="1">
                            <button type="button" class="btn btn-outline-danger px-4 py-2 align-middle" id="showCancelModalBtn" style="height:44px; display:flex; align-items:center;">Cancel</button>
                        </form>
                        <!-- Cancel Confirmation Modal -->
                        <div class="modal fade" id="cancelResignationModal" tabindex="-1" aria-labelledby="cancelResignationModalLabel" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="cancelResignationModalLabel">Cancel Resignation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                Are you sure you want to cancel your resignation request?
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                                <button type="button" class="btn btn-danger" id="confirmCancelResignationBtn">Yes, Cancel</button>
                              </div>
                            </div>
                          </div>
                        </div>
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var showBtn = document.getElementById('showCancelModalBtn');
                            var confirmBtn = document.getElementById('confirmCancelResignationBtn');
                            var form = document.getElementById('cancel-resignation-form');
                            var modal = new bootstrap.Modal(document.getElementById('cancelResignationModal'));
                            if (showBtn) {
                                showBtn.addEventListener('click', function() {
                                    modal.show();
                                });
                            }
                            if (confirmBtn) {
                                confirmBtn.addEventListener('click', function() {
                                    form.submit();
                                });
                            }
                        });
                        </script>
                    <?php endif; ?>
                <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Attendance Page -->
        <div id="attendance-page" class="page-content" style="display: none;">
            <div class="dashboard-card mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-journal-text me-2"></i>Modification Request</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Original Time In</th>
                                <th>Original Time Out</th>
                                <th>Requested Time In</th>
                                <th>Requested Time Out</th>
                                <th>Total Hours</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Requested At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mod_requests as $req): ?>
                            <tr>
                                <td><?= htmlspecialchars($req['date_of_attendance']) ?></td>
                                <td><?= $req['original_time_in'] ? date('g:i A', strtotime($req['original_time_in'])) : 'NA' ?></td>
                                <td><?= $req['original_time_out'] ? date('g:i A', strtotime($req['original_time_out'])) : 'NA' ?></td>
                                <td><?= $req['requested_time_in'] ? date('g:i A', strtotime($req['requested_time_in'])) : 'NA' ?></td>
                                <td><?= $req['requested_time_out'] ? date('g:i A', strtotime($req['requested_time_out'])) : 'NA' ?></td>
                                <td><?= htmlspecialchars($req['total_hours'] ?? 'NA') ?: 'NA' ?></td>
                                <td><?= htmlspecialchars($req['reason'] ?? 'NA') ?: 'NA' ?></td>
                                <td>
                                    <?php if ($req['status'] === 'pending'): ?>
                                        <div class="dropdown d-inline">
                                            <button class="badge bg-warning text-dark dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:1em; font-weight:600; cursor:pointer; box-shadow:none;">
                                                Pending
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm p-0" style="min-width: 120px;">
                                                <li>
                                                    <form method="post" onsubmit="return confirm('Are you sure you want to cancel this request?');" class="m-0">
                                                        <input type="hidden" name="modification_id" value="<?= $req['modification_id'] ?>">
                                                        <button type="submit" name="cancel_mod_request" class="dropdown-item text-danger py-2" style="font-weight:500;">Cancel</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge <?= $req['status'] === 'approved' ? 'bg-success' : ($req['status'] === 'cancelled' ? 'bg-secondary' : 'bg-danger') ?>" style="font-size:1em; font-weight:600;">
                                            <?= ucfirst($req['status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y h:i A', strtotime($req['requested_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="dashboard-card">
                <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Attendance Record</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attendance_history)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No attendance records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($attendance_history as $row): ?>
                                    <tr>
                                        <td><?= date('m/d/Y', strtotime($row['date'])) ?></td>
                                        <td><?= $row['check_in'] ? date('g:i A', strtotime($row['check_in'])) : 'NA' ?></td>
                                        <td><?= $row['check_out'] ? date('g:i A', strtotime($row['check_out'])) : 'NA' ?></td>
                                        <td><?= ucfirst($row['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Leave Page -->
        <div id="leave-page" class="page-content" style="display: none; background: #ececec; min-height: 100vh; padding-bottom: 40px;">
            <div class="mb-4">
                <h5 class="fw-bold mb-3" style="font-size:1.25rem;"><i class="bi bi-journal-bookmark me-2"></i>Leave Application</h5>
                <div class="d-flex align-items-center justify-content-between bg-white rounded-3 p-4 mb-4" style="box-shadow:0 1px 4px #0001;">
                    <span class="fw-semibold" style="font-size:1.15rem; color:#444;">File Leave here</span>
                    <button class="btn btn-main px-5 py-2" style="font-size:1.1rem; font-weight:600; border-radius:8px;"
                            data-bs-toggle="modal" data-bs-target="#leaveModal">
                      Apply
                    </button>
                </div>
            </div>
            <div>
                <h5 class="fw-bold mb-3" style="font-size:1.25rem;"><i class="bi bi-clock-history me-2"></i>Leave Record</h5>
                <div class="bg-white rounded-3 p-4" style="box-shadow:0 1px 4px #0001;">
                    <table class="table mb-0" style="border-collapse:separate; border-spacing:0 12px;">
                        <thead>
                            <tr style="background:#e9bcbc;">
                                <th>Duration(s)</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Type</th>
                                <th>Reason(s)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Example: Fetch leave history for the current user
                        $leave_history = [];
                        try {
                            $stmt = $conn->prepare("SELECT l.*, u.full_name FROM leave_application l LEFT JOIN user_account u ON l.employee_id = u.user_id WHERE l.employee_id = ? ORDER BY l.start_date DESC");
                            $stmt->bind_param("i", $user_id);
                            if ($stmt->execute()) {
                                $result = $stmt->get_result();
                                while ($row = $result->fetch_assoc()) {
                                    $leave_history[] = $row;
                                }
                            }
                            $stmt->close();
                        } catch (Exception $e) {}
                        if (empty($leave_history)) {
                            echo '<tr><td colspan="6" class="text-center text-muted">No leave records found.</td></tr>';
                        } else {
                            foreach ($leave_history as $i => $leave) {
                                echo '<tr style="background:'.($i%2==0?'#fbeaea':'#fff').';">';
                                echo '<td>'.htmlspecialchars($leave['duration']).'</td>';
                                echo '<td>'.date('d/m/Y', strtotime($leave['start_date'])).'</td>';
                                echo '<td>'.date('d/m/Y', strtotime($leave['end_date'])).'</td>';
                                echo '<td>'.htmlspecialchars($leave['leave_type'] ?? '').'</td>';
                                echo '<td>'.htmlspecialchars($leave['reason']).'</td>';
                                // Status column with dropdown for pending
                                echo '<td>';
                                if ($leave['status'] === 'pending') {
                                    echo '<div class="dropdown d-inline">';
                                    echo '<button class="badge bg-warning text-dark dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:1em; font-weight:600; cursor:pointer; box-shadow:none;">Pending</button>';
                                    echo '<ul class="dropdown-menu dropdown-menu-end shadow-sm p-0" style="min-width: 120px;">';
                                    echo '<li>';
                                    echo '<button type="button" class="dropdown-item text-danger py-2" style="font-weight:500;" data-leave-id="'.htmlspecialchars($leave['id']).'" onclick="showCancelLeaveModal(this)">Cancel</button>';
                                    echo '</li>';
                                    echo '</ul>';
                                    echo '</div>';
                                } else {
                                    echo '<span class="badge ';
                                    if ($leave['status'] === 'approved') echo 'bg-success';
                                    elseif ($leave['status'] === 'cancelled') echo 'bg-secondary';
                                    else echo 'bg-danger';
                                    echo '">'.ucfirst($leave['status']).'</span>';
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notification Page -->
        <div id="notification-page" class="page-content" style="display: none; min-height: 100vh; padding-bottom: 40px;">
            <div class="d-flex justify-content-center align-items-start pt-4">
                <div class="bg-white rounded-4 shadow-sm w-100" style="max-width: 950px;">
                    <div class="px-5 pt-5 pb-2">
                        <h3 class="fw-bold mb-4" style="color:#8a5a1e; font-size:2rem;">Notifications</h3>
                        <div class="list-group list-group-flush">
                            <?php
                            // Fetch notifications for the current user
                            $notifications = [];
                            try {
                                $stmt = $conn->prepare("
                                    SELECT n.*, ua.full_name as sender_name 
                                    FROM notification n 
                                    LEFT JOIN user_account ua ON n.sender_id = ua.user_id 
                                    WHERE n.scheduled_date IS NULL 
                                       OR (n.scheduled_date IS NOT NULL AND n.scheduled_date <= NOW())
                                    ORDER BY 
                                        CASE 
                                            WHEN n.scheduled_date IS NOT NULL THEN n.scheduled_date 
                                            ELSE n.created_at 
                                        END DESC
                                    LIMIT 10
                                ");
                                
                                if (!$stmt) {
                                    throw new Exception("Failed to prepare statement: " . $conn->error);
                                }
                                
                                if (!$stmt->execute()) {
                                    throw new Exception("Failed to fetch notifications: " . $stmt->error);
                                }
                                
                                $result = $stmt->get_result();
                                while ($row = $result->fetch_assoc()) {
                                    $notifications[] = $row;
                                }
                                $stmt->close();
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">Error loading notifications: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }

                            function formatNotificationTime($timestamp, $scheduled_date = null) {
                                $now = new DateTime();
                                $notification_time = new DateTime($timestamp);
                                
                                // If there's a scheduled date, use that for display
                                if ($scheduled_date) {
                                    $scheduled = new DateTime($scheduled_date);
                                    // If the scheduled date is today
                                    if ($scheduled->format('Y-m-d') === $now->format('Y-m-d')) {
                                        return 'Posted today at ' . $scheduled->format('g:i A');
                                    }
                                    // If the scheduled date is yesterday
                                    else if ($scheduled->format('Y-m-d') === $now->modify('-1 day')->format('Y-m-d')) {
                                        return 'Posted yesterday at ' . $scheduled->format('g:i A');
                                    }
                                    // If the scheduled date is this year
                                    else if ($scheduled->format('Y') === $now->format('Y')) {
                                        return 'Posted on ' . $scheduled->format('M d') . ' at ' . $scheduled->format('g:i A');
                                    }
                                    // If the scheduled date is from previous years
                                    else {
                                        return 'Posted on ' . $scheduled->format('M d, Y') . ' at ' . $scheduled->format('g:i A');
                                    }
                                }
                                
                                // For non-scheduled notifications, use the created_at time
                                if ($notification_time->format('Y-m-d') === $now->format('Y-m-d')) {
                                    return $notification_time->format('g:i A');
                                }
                                else if ($notification_time->format('Y-m-d') === $now->modify('-1 day')->format('Y-m-d')) {
                                    return 'Yesterday ' . $notification_time->format('g:i A');
                                }
                                else if ($notification_time->format('Y') === $now->format('Y')) {
                                    return $notification_time->format('M d, g:i A');
                                }
                                else {
                                    return $notification_time->format('M d, Y g:i A');
                                }
                            }

                            if (empty($notifications)) {
                                echo '<div class="text-center text-muted" style="padding: 32px 0;">No notifications available</div>';
                            } else {
                                foreach ($notifications as $notif) {
                                    $time_display = formatNotificationTime($notif['created_at'], $notif['scheduled_date']);
                                    echo '<div class="list-group-item d-flex justify-content-between align-items-start py-4 px-0 border-0 border-bottom">';
                                    echo '<div class="flex-grow-1">';
                                    echo '<div class="fw-bold" style="font-size:1.1rem;">' . htmlspecialchars($notif['title']) . '</div>';
                                    echo '<div class="text-muted" style="font-size:1rem;">' . nl2br(htmlspecialchars($notif['content'])) . '</div>';
                                    if (!empty($notif['sender_name'])) {
                                        echo '<div class="text-muted mt-1" style="font-size:0.9rem;">From: ' . htmlspecialchars($notif['sender_name']) . '</div>';
                                    }
                                    echo '</div>';
                                    echo '<div class="text-muted ms-3" style="font-size:0.9rem; min-width:110px; text-align:right;">' . $time_display . '</div>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for Attendance Modification Request -->
    <div class="modal fade" id="modRequestModal" tabindex="-1" aria-labelledby="modRequestModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="post" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title" id="modRequestModalLabel">Attendance Modification Request</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3"><strong>Employee Information</strong></div>
              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label">Full Name</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_info['full_name']); ?>" readonly>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Department</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_info['department_name']); ?>" readonly>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Position</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_info['position']); ?>" readonly>
                </div>
              </div>
              <div class="mb-3"><strong>Date & Time to Modify</strong></div>
              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label">Date of Attendance</label>
                  <input type="date" name="date_of_attendance" class="form-control" required max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Original Time In</label>
                  <input type="time" name="original_time_in" class="form-control" id="original_time_in" value="<?= $log['check_in'] ? date('H:i', strtotime($log['check_in'])) : '' ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Original Time Out</label>
                  <input type="time" name="original_time_out" class="form-control" id="original_time_out" value="<?= $log['check_out'] ? date('H:i', strtotime($log['check_out'])) : '' ?>">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Requested Time In</label>
                  <input type="time" name="requested_time_in" class="form-control" id="requested_time_in" value="">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Requested Time Out</label>
                  <input type="time" name="requested_time_out" class="form-control" id="requested_time_out" value="">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Total Hours</label>
                  <input type="text" name="total_hours" class="form-control" id="total_hours" readonly>
                </div>
              </div>
              <div class="mb-3"><strong>Reason for Modification</strong></div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Reason</label>
                  <select name="reason" class="form-select" required>
                    <option value="">Select reason</option>
                    <option>Forgot to time in/out</option>
                    <option>System error</option>
                    <option>Device not working</option>
                    <option>Work-related off-site activity</option>
                    <option>Medical appointment</option>
                    <option>Other</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Remarks (Optional)</label>
                  <input type="text" name="remarks" class="form-control">
                </div>
              </div>
              <div class="mb-3"><strong>Supporting Evidence (optional but encouraged)</strong></div>
              <div class="mb-3">
                <input type="file" name="evidence" class="form-control">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" name="submit_mod_request" class="btn btn-main">Submit Request</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Resignation Modal -->
    <div class="modal fade" id="resignationModal" tabindex="-1" aria-labelledby="resignationModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="post" enctype="multipart/form-data">
            <div class="modal-header border-0 pb-0">
              <h3 class="modal-title w-100 text-center fw-bold" id="resignationModalLabel" style="font-size:2rem;"><i class="bi bi-box-arrow-right me-2"></i><?= isset($_SESSION['edit_resignation']) ? 'Edit resignation' : 'Submit resignation' ?></h3>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
              <p class="text-center mb-4" style="color:#444;">We're sorry to see you go. To ensure a smooth transition, kindly Provide the necessary information below. Your feedback will be kept confidential</p>
              <div class="mb-3">
                <label class="form-label fw-semibold">Reason for Resignation</label>
                <select name="resignation_reason" class="form-select" required>
                  <option value="">Choose an Option</option>
                  <option <?= (isset($_SESSION['edit_resignation']) && $user_resignation && $user_resignation['reason'] == 'Personal reasons') ? 'selected' : '' ?>>Personal reasons</option>
                  <option <?= (isset($_SESSION['edit_resignation']) && $user_resignation && $user_resignation['reason'] == 'Career growth') ? 'selected' : '' ?>>Career growth</option>
                  <option <?= (isset($_SESSION['edit_resignation']) && $user_resignation && $user_resignation['reason'] == 'Health reasons') ? 'selected' : '' ?>>Health reasons</option>
                  <option <?= (isset($_SESSION['edit_resignation']) && $user_resignation && $user_resignation['reason'] == 'Relocation') ? 'selected' : '' ?>>Relocation</option>
                  <option <?= (isset($_SESSION['edit_resignation']) && $user_resignation && $user_resignation['reason'] == 'Work environment') ? 'selected' : '' ?>>Work environment</option>
                  <option <?= (isset($_SESSION['edit_resignation']) && $user_resignation && $user_resignation['reason'] == 'Better opportunity') ? 'selected' : '' ?>>Better opportunity</option>
                  <option <?= (isset($_SESSION['edit_resignation']) && $user_resignation && $user_resignation['reason'] == 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Additional Comments</label>
                <textarea name="resignation_comments" class="form-control" rows="3" placeholder=""><?php if (isset($_SESSION['edit_resignation']) && $user_resignation) echo htmlspecialchars($user_resignation['comments'] ?? ''); ?></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Attach Resignation Letter (Pdf)</label>
                <input type="file" name="resignation_letter" class="form-control" accept="application/pdf">
                <?php if (isset($_SESSION['edit_resignation']) && $user_resignation && $user_resignation['resignation_letter']): ?>
                    <div class="mt-2"><a href="/fedm_hrms/<?= htmlspecialchars($user_resignation['resignation_letter']) ?>" target="_blank">View current letter</a></div>
                <?php endif; ?>
              </div>
              <div class="mb-3">
                <div class="bg-info bg-opacity-10 border border-info rounded p-3 text-center" style="font-size:1.05rem; color:#055160;">
                    Once submitted, HR will review your request and contact you for further steps including clearance and exit interviews.
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 d-flex justify-content-between">
              <button type="button" class="btn btn-outline-danger px-5 py-2" data-bs-dismiss="modal" style="font-weight:600;">Cancel</button>
              <button type="submit" name="submit_resignation" class="btn btn-main px-5 py-2" style="font-weight:600;">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="modal fade" id="leaveModal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="post" enctype="multipart/form-data">
            <div class="modal-header border-0 pb-0">
              <h3 class="modal-title w-100 text-center fw-bold" id="leaveModalLabel" style="font-size:2rem;">
                <i class="bi bi-journal-bookmark me-2"></i>Leave Application
              </h3>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
              <p class="text-center mb-4" style="color:#444;">Fill the required fields below to apply for leave.</p>
              <div class="mb-3">
                <label class="form-label fw-semibold">Leave Type</label>
                <select name="leave_type" id="leave_type" class="form-select" required>
                  <option value="">Select Leave Type</option>
                  <option value="Vacation Leave">Vacation Leave</option>
                  <option value="Sick Leave">Sick Leave</option>
                  <option value="Emergency Leave">Emergency Leave</option>
                  <option value="Maternity Leave">Maternity Leave</option>
                  <option value="Paternity Leave">Paternity Leave</option>
                </select>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Start Date</label>
                  <input type="date" name="start_date" id="start_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">End Date</label>
                  <input type="date" name="end_date" id="end_date" class="form-control" required>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Duration</label>
                  <input type="number" name="duration" id="duration" class="form-control" min="1" value="1" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Resumption Date</label>
                  <input type="date" name="resumption_date" id="resumption_date" class="form-control" required>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Reason for leave</label>
                <textarea name="reason" class="form-control" rows="2" required></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Attach handover document (pdf, jpg, docx or any other format)</label>
                <input type="file" name="handover_doc" class="form-control">
              </div>
            </div>
            <div class="modal-footer border-0 d-flex justify-content-between">
              <button type="button" class="btn btn-outline-danger px-5 py-2" data-bs-dismiss="modal" style="font-weight:600;">Cancel</button>
              <button type="submit" name="submit_leave" class="btn btn-main px-5 py-2" style="font-weight:600;">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Cancel Leave Confirmation Modal -->
    <div class="modal fade" id="cancelLeaveModal" tabindex="-1" aria-labelledby="cancelLeaveModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="cancelLeaveModalLabel">Cancel Leave Application</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to cancel your leave application?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
            <form method="post" id="cancelLeaveForm" class="d-inline">
              <input type="hidden" name="leave_id" id="cancelLeaveId" value="">
              <button type="submit" name="cancel_leave" class="btn btn-danger">Yes, Cancel</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- Profile Page -->
    <div id="profile-page" class="page-content" style="display: none;">
        <div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="card shadow p-4" style="width: 700px; border-radius: 24px;">
                <?php if ($incomplete_profile): ?>
                    <div class="alert alert-warning mb-4" style="font-weight:600; font-size:1.1rem;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Please complete your personal information. All fields are required.
                    </div>
                <?php endif; ?>
                <div class="d-flex align-items-center mb-4">
                    <div style="background: #e5d6d6; border-radius: 50%; width: 104px; height: 104px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-person" style="color: #7a2320; font-size: 3.6rem;"></i>
                    </div>
                    <div class="ms-4 flex-grow-1">
                        <h2 class="fw-bold mb-0" id="profileFullName"><?= htmlspecialchars($profile['full_name'] ?? '') ?></h2>
                        <div id="profileDepartment"><?= htmlspecialchars($profile['department'] ?? 'N/A') ?></div>
                        <div id="profileJobTitle"><?= htmlspecialchars($profile['job_title'] ?? 'N/A') ?></div>
                        <div class="mt-1">
                            <?php
                            // Get the manager rating from the profile data
                            $rating = isset($profile['manager_rating']) ? (int)$profile['manager_rating'] : 0;
                            
                            // Display filled stars based on rating
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="bi bi-star-fill text-warning"></i>';
                                } else {
                                    echo '<i class="bi bi-star text-warning"></i>';
                                }
                            }
                            
                            // Display the numerical rating if available
                            if ($rating > 0) {
                                echo '<span class="ms-2 text-muted">(' . $rating . '/5)</span>';
                            }
                            ?>
                        </div>
                        <!-- Removed redundant Change password link here -->
                    </div>
                    <button class="btn btn-outline-dark ms-auto" id="editProfileBtn" title="Edit Profile"><i class="bi bi-pencil-square"></i></button>
                </div>
                <hr>
                <form id="profileForm" method="POST" action="">
                    <!-- Only keep editable fields: Date of Birth, Mobile Number, Gender, Civil Status, Address, Nationality, Email (readonly) -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" id="profileDob" value="<?= htmlspecialchars($profile['date_of_birth'] ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" name="mobile_number" id="profileMobile" value="<?= htmlspecialchars($profile['mobile_number'] ?? '') ?>" disabled>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select class="form-control" name="gender" id="profileGender" disabled>
                                <option value="">Select Gender</option>
                                <option value="Male" <?= (isset($profile['gender']) && $profile['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= (isset($profile['gender']) && $profile['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= (isset($profile['gender']) && $profile['gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" id="profileEmail" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" disabled readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Civil Status</label>
                            <select class="form-control" name="civil_status" id="profileCivilStatus" disabled>
                                <option value="">Select Status</option>
                                <option value="Single" <?= (isset($profile['civil_status']) && $profile['civil_status'] === 'Single') ? 'selected' : '' ?>>Single</option>
                                <option value="Married" <?= (isset($profile['civil_status']) && $profile['civil_status'] === 'Married') ? 'selected' : '' ?>>Married</option>
                                <option value="Widowed" <?= (isset($profile['civil_status']) && $profile['civil_status'] === 'Widowed') ? 'selected' : '' ?>>Widowed</option>
                                <option value="Divorced" <?= (isset($profile['civil_status']) && $profile['civil_status'] === 'Divorced') ? 'selected' : '' ?>>Divorced</option>
                                <option value="Separated" <?= (isset($profile['civil_status']) && $profile['civil_status'] === 'Separated') ? 'selected' : '' ?>>Separated</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" id="profileAddress" value="<?= htmlspecialchars($profile['address'] ?? '') ?>" disabled>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nationality</label>
                            <input type="text" class="form-control" name="nationality" id="profileNationality" value="<?= htmlspecialchars($profile['nationality'] ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                           
                            <a href="#" class="text-primary ms-3 mb-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal" style="font-size:1rem; font-weight:500; white-space:nowrap;">Change password</a>
                        </div>
                    </div>
                    <!-- Employment Type field already removed -->
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" id="saveProfileBtn" name="save_profile" style="display:none;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" id="changePasswordForm">
            <div class="modal-header">
              <h5 class="modal-title w-100 text-center fw-bold" id="changePasswordModalLabel">Change Password</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p class="mb-3 text-center">Please enter your current password and choose a new password. Make sure your new password is secure and easy for you to remember.</p>
              <?php if (isset($_SESSION['change_password_error'])): ?>
                <div class="alert alert-danger" id="changePasswordError"><?= htmlspecialchars($_SESSION['change_password_error']) ?></div>
                <?php unset($_SESSION['change_password_error']); ?>
              <?php endif; ?>
              <?php if (isset($_SESSION['change_password_success'])): ?>
                <div class="alert alert-success" id="changePasswordSuccess"><?= htmlspecialchars($_SESSION['change_password_success']) ?></div>
                <?php unset($_SESSION['change_password_success']); ?>
              <?php endif; ?>
              <div class="mb-3">
                <label for="currentPassword" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="currentPassword" name="current_password" required autocomplete="current-password">
              </div>
              <div class="mb-3">
                <label for="newPassword" class="form-label">New Password</label>
                <input type="password" class="form-control" id="newPassword" name="new_password" required autocomplete="new-password">
              </div>
              <div class="mb-3">
                <label for="confirmNewPassword" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirmNewPassword" name="confirm_new_password" required autocomplete="new-password">
              </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" name="change_password">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-calculate total hours in modal
    document.addEventListener('DOMContentLoaded', function() {
        function calcTotalHours() {
            var inTime = document.getElementById('requested_time_in').value;
            var outTime = document.getElementById('requested_time_out').value;
            if (inTime && outTime) {
                var inParts = inTime.split(':'), outParts = outTime.split(':');
                var inDate = new Date(0,0,0,parseInt(inParts[0]),parseInt(inParts[1]));
                var outDate = new Date(0,0,0,parseInt(outParts[0]),parseInt(outParts[1]));
                var diff = (outDate - inDate) / 1000 / 60 / 60;
                if (diff < 0) diff += 24; // handle overnight
                document.getElementById('total_hours').value = diff.toFixed(2);
            } else {
                document.getElementById('total_hours').value = '';
            }
        }
        var inInput = document.getElementById('requested_time_in');
        var outInput = document.getElementById('requested_time_out');
        if (inInput && outInput) {
            inInput.addEventListener('input', calcTotalHours);
            outInput.addEventListener('input', calcTotalHours);
        }

        // Auto-hide alerts after 3 seconds
        // const alerts = document.querySelectorAll('.alert');
        // alerts.forEach(function(alert) {
        //     setTimeout(function() {
        //         alert.style.transition = 'opacity 0.5s ease-in-out';
        //         alert.style.opacity = '0';
        //         setTimeout(function() {
        //             alert.remove();
        //         }, 500);
        //     }, 3000);
        // });
    });

    // Add tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.custom-nav-link');
        const pages = document.querySelectorAll('.page-content');

        navLinks.forEach(link => {
            // Skip logout button
            if (link.id === 'logout-btn') return;
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Hide all pages
                pages.forEach(page => page.style.display = 'none');
                
                // Show selected page
                const pageId = this.getAttribute('data-page') + '-page';
                const selectedPage = document.getElementById(pageId);
                if (selectedPage) {
                    selectedPage.style.display = 'block';
                }
            });
        });
    });

    // Disable requested time in/out if original is empty
    function updateRequestedFields() {
        var origIn = document.getElementById('original_time_in');
        var reqIn = document.getElementById('requested_time_in');
        var origOut = document.getElementById('original_time_out');
        var reqOut = document.getElementById('requested_time_out');
        if (origIn && reqIn) reqIn.disabled = !origIn.value;
        if (origOut && reqOut) reqOut.disabled = !origOut.value;
    }
    document.addEventListener('DOMContentLoaded', function() {
        // ... existing code ...
        updateRequestedFields();
        var origIn = document.getElementById('original_time_in');
        var origOut = document.getElementById('original_time_out');
        if (origIn) origIn.addEventListener('input', updateRequestedFields);
        if (origOut) origOut.addEventListener('input', updateRequestedFields);
    });

    document.addEventListener('DOMContentLoaded', function() {
        var modRequestModal = document.getElementById('modRequestModal');
        if (modRequestModal) {
            modRequestModal.addEventListener('show.bs.modal', function () {
                document.getElementById('requested_time_in').value = '';
                document.getElementById('requested_time_out').value = '';
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const leaveType = document.getElementById('leave_type');
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const today = new Date().toISOString().split('T')[0];

        function updateDatePickers() {
            if (leaveType.value === 'Sick Leave') {
                // Allow both past and future dates for sick leave
                startDate.removeAttribute('min');
                endDate.removeAttribute('min');
                startDate.removeAttribute('max');
                endDate.removeAttribute('max');
            } else {
                const minDate = new Date();
                minDate.setDate(minDate.getDate() + 3);
                const minDateStr = minDate.toISOString().split('T')[0];
                startDate.setAttribute('min', minDateStr);
                endDate.setAttribute('min', minDateStr);
                startDate.removeAttribute('max');
                endDate.removeAttribute('max');
            }
        }

        leaveType.addEventListener('change', updateDatePickers);
        updateDatePickers();
    });

    document.addEventListener('DOMContentLoaded', function() {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const duration = document.getElementById('duration');
        const resumptionDate = document.getElementById('resumption_date');

        function updateDurationAndResumption() {
            if (startDate.value && endDate.value) {
                const start = new Date(startDate.value);
                const end = new Date(endDate.value);
                if (end >= start) {
                    // Calculate days inclusive
                    const days = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
                    duration.value = days;
                    // Set resumption date min to the day after end date
                    const resumptionMin = new Date(end);
                    resumptionMin.setDate(resumptionMin.getDate() + 1);
                    resumptionDate.min = resumptionMin.toISOString().split('T')[0];
                } else {
                    duration.value = '';
                    resumptionDate.min = '';
                }
            } else {
                duration.value = '';
                resumptionDate.min = '';
            }
            // Always set resumption date min to today or later
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            if (!resumptionDate.min || resumptionDate.min < todayStr) {
                resumptionDate.min = todayStr;
            }
        }

        startDate.addEventListener('change', updateDurationAndResumption);
        endDate.addEventListener('change', updateDurationAndResumption);

        // Prevent manual entry of past dates for resumption
        resumptionDate.addEventListener('input', function() {
            if (resumptionDate.value && resumptionDate.value < resumptionDate.min) {
                resumptionDate.value = resumptionDate.min;
            }
        });
    });

    function showCancelLeaveModal(btn) {
        var leaveId = btn.getAttribute('data-leave-id');
        document.getElementById('cancelLeaveId').value = leaveId;
        var modal = new bootstrap.Modal(document.getElementById('cancelLeaveModal'));
        modal.show();
    }
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const editBtn = document.getElementById('editProfileBtn');
        const saveBtn = document.getElementById('saveProfileBtn');
        const form = document.getElementById('profileForm');
        const fields = [
            'profileDob',
            'profileMobile',
            'profileGender',
            'profileCivilStatus',
            'profileAddress',
            'profileNationality'
        ];
        editBtn.addEventListener('click', function() {
            fields.forEach(id => document.getElementById(id).disabled = false);
            saveBtn.style.display = 'inline-block';
        });
    });
    </script>
    <script>
    // ... existing code ...
        document.addEventListener('DOMContentLoaded', function() {
            // ... existing code ...
            // Password show/hide toggle
            const passwordInput = document.getElementById('profilePassword');
            const togglePasswordBtn = document.getElementById('togglePassword');
            const togglePasswordIcon = document.getElementById('togglePasswordIcon');
            if (togglePasswordBtn && passwordInput && togglePasswordIcon) {
                togglePasswordBtn.addEventListener('click', function() {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        togglePasswordIcon.classList.remove('bi-eye');
                        togglePasswordIcon.classList.add('bi-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        togglePasswordIcon.classList.remove('bi-eye-slash');
                        togglePasswordIcon.classList.add('bi-eye');
                    }
                });
            }
        });
    // ... existing code ...
    </script>
</body>
</html>