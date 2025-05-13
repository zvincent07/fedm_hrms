<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/manager-styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        .sidebar {
            width: 260px;
            background: #00468c;
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
            color: #fff;
            background: transparent;
            border-radius: 14px;
            padding: 12px 28px;
            margin: 10px 16px 0 16px;
            cursor: pointer;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            box-shadow: none;
            border: none;
            outline: none;
        }
        .sidebar .nav-item.active, .sidebar .nav-item:hover {
            background: #eaf1fa;
            color: #00468c;
            box-shadow: 0 4px 16px rgba(0,70,140,0.08);
        }
        .sidebar .nav-item i {
            font-size: 1.3rem;
            min-width: 28px;
            text-align: center;
        }
        .sidebar .nav-label {
            font-size: 1.1rem;
            font-weight: bold;
            color: #fff;
            margin-left: 12px;
        }
        .sidebar .nav-sub {
            margin-left: 32px;
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
            color: #00468c;
            background: #fff;
            border-radius: 8px;
            padding: 7px 16px;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar .nav-sub .nav-sub-item:hover {
            background: #eaf1fa;
            color: #003366;
        }
        .sidebar .nav-divider {
            border-left: 4px solid #00468c;
            height: 32px;
            margin: 0 0 16px 32px;
        }
        .sidebar .nav-bottom {
            margin-top: auto;
            padding-bottom: 24px;
        }
        .sidebar .nav-bottom .nav-item {
            background: transparent;
            color: #fff;
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
            background: #eaf1fa;
            color: #00468c;
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
            color: #00468c;
            font-weight: 700;
            margin-bottom: 24px;
        }
        .form-group label {
            font-weight: 500;
        }
        .btn-create, .btn-change-password {
            background: #00468c;
            color: #fff;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 0;
            width: 100%;
            margin-top: 18px;
        }
        .btn-create:hover, .btn-change-password:hover {
            background: #003366;
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
        .sidebar .nav-item{
        text-decoration: none !important;
    }

    .nav-sub {
    margin-left: 32px;
}
.nav-sub-item {
    display: block;
    padding: 8px 0 8px 24px;
    color: #fff;
    text-decoration: none;
    font-size: 1rem;
}
.nav-sub-item:hover {
    background: #00468c;
    color: #fff;
    border-radius: 8px;
    text-decoration: none !important; 
}
    </style>
</head>
<body>
    <div class="sidebar">
        <div style="display: flex; flex-direction: column; align-items: center; margin-top: 28px;">
            <div style="background: #00468c; border-radius: 50%; width: 104px; height: 104px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-user" style="color: #fff; font-size: 3.6rem;"></i>
            </div>
        </div>
        <div class="nav-section">
            <a class="nav-item" id="dashboardBtn" href="?show=dashboard">
                <i class="fa-solid fa-map"></i>
                Dashboard
            </a>
            <a class="nav-item" id="employeeBtn" href="?show=employeeList">
                <i class="fa-regular fa-user"></i>
                Employee
            </a>
            <a class="nav-item" id="attendanceBtn" href="?show=attendance">
                <i class="fa-regular fa-user"></i>
                Attendance
            </a>
            <a class="nav-item" id="leaveBtn" href="?show=leave">
                <i class="fa-regular fa-user"></i>
                Leave
            </a>
            <a class="nav-item" id="resignationBtn" href="?show=resignation">
                <i class="fa-regular fa-user"></i>
                Resignation
            </a>
            <a class="nav-item" id="notificationBtn" href="?show=notification">
                <i class="fa-regular fa-bell"></i>
                Notification
            </a>
            <a class="nav-item" id="activityLogsBtn" href="?show=activityLogs">
                <i class="fa-regular fa-clock"></i>
                Activity Logs
            </a>
            <a class="nav-item" id="changePasswordBtn" href="?show=changePassword">
                <i class="fa-solid fa-key"></i>
                Change Password
            </a>
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var employeeBtn = document.getElementById('employeeBtn');
        var employeeSubMenu = document.getElementById('employeeSubMenu');
        var chevron = employeeBtn.querySelector('.fa-chevron-down');
        if (employeeBtn && employeeSubMenu) {
            employeeBtn.addEventListener('click', function() {
                var isOpen = employeeSubMenu.classList.toggle('show');
                chevron.style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
            });
        }
    });
    </script>
</body>
</html>