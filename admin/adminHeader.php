<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
    background: #b30000;
    color: #fff;
    border-radius: 8px;
    text-decoration: none !important; 
}
    </style>
</head>
<body>