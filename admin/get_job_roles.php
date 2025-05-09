<?php
include '../config/db.php';

$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$job_roles = [];

if ($department_id) {
    $sql = "SELECT job_role_id, title FROM job_role WHERE department_id = $department_id ORDER BY title ASC";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $job_roles[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($job_roles);
