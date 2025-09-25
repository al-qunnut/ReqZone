
<?php
session_start();

// Check if the user is logged in and is a Facility user
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["userCategory"] !== "Facility") {
    header("location: ../index.php");
    exit;
}

// Database connection
$link = mysqli_connect('localhost', 'root', '', 'reqzone');
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Initialize user data from session
$user_data = [
    'id' => $_SESSION['id'] ?? '',
    'name' => $_SESSION['name'] ?? 'Guest',
    'email' => $_SESSION['email'] ?? '',
    'userCategory' => $_SESSION['userCategory'] ?? 'Facility',
    'userGroup' => $_SESSION['userGroup'] ?? '',
    'role' => $_SESSION['role'] ?? 'Facility Manager'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentName = trim($_POST['project-name']);
    $requestType = trim($_POST['constituency']);
    $description = trim($_POST['about-project']);
    $requestSender = $user_data['name'];

    // Validate that only Payment or Job requests are allowed for Facility users
    $allowedTypes = ['Payment', 'Job'];
    
    if (!in_array($requestType, $allowedTypes)) {
        $error_message = "Facility users can only submit Payment or Job requests.";
    } elseif ($documentName && $requestType && $description) {
        $stmt = mysqli_prepare(
            $link,
            "INSERT INTO fileInformation (`DocumentName`, `Request Type`, `Description`, `RequestSender`, `SenderEmail`) VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "sssss", $documentName, $requestType, $description, $requestSender, $user_data['email']);

        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Request submitted successfully!";
        } else {
            $error_message = "Error: " . mysqli_error($link);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Please fill in all fields.";
    }
}

// Fetch user's requests and statistics (only Payment and Job types)
$user_requests = [];
$user_stats = ['job' => 0, 'payment' => 0, 'total' => 0];

if (!empty($user_data['email'])) {
    // Get user's requests (only Payment and Job types)
    $stmt = mysqli_prepare($link, "SELECT * FROM fileInformation WHERE SenderEmail = ? AND (`Request Type` = 'Payment' OR `Request Type` = 'Job') ORDER BY id DESC");
    mysqli_stmt_bind_param($stmt, "s", $user_data['email']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $user_requests[] = $row;
    }
    mysqli_stmt_close($stmt);

    // Calculate statistics
    foreach ($user_requests as $request) {
        $user_stats['total']++;
        if (strtolower($request['Request Type']) == 'job') {
            $user_stats['job']++;
        } elseif (strtolower($request['Request Type']) == 'payment') {
            $user_stats['payment']++;
        }
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Dashboard - ReqZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Inter', Arial, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0835C8 0%, #001f4d 100%);
            min-height: 100vh;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
            pointer-events: none;
        }

        .floating-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: float 25s infinite linear;
        }

        .floating-shape:nth-child(1) {
            width: 120px;
            height: 120px;
            left: 10%;
            animation-duration: 20s;
        }

        .floating-shape:nth-child(2) {
            width: 80px;
            height: 80px;
            left: 70%;
            animation-duration: 15s;
            animation-delay: -5s;
        }

        .floating-shape:nth-child(3) {
            width: 100px;
            height: 100px;
            left: 40%;
            animation-duration: 18s;
            animation-delay: -10s;
        }

        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.6; }
            90% { opacity: 0.6; }
            100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
        }

        .container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Enhanced Sidebar */
        .sidebar_box {
            background: linear-gradient(160deg, rgba(8, 53, 200, 0.95) 80%, rgba(0, 31, 77, 0.95) 100%);
            backdrop-filter: blur(20px);
            color: #fff;
            width: 280px;
            min-width: 280px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            padding: 0;
            transition: all 0.3s ease;
            z-index: 200;
            border-right: 1px solid rgba(255,255,255,0.1);
        }

        .logo-section {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .logo-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 4s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .logo {
            width: 180px;
            margin-bottom: 15px;
            filter: brightness(0) invert(1) drop-shadow(0 2px 8px rgba(255,255,255,0.3));
            transition: transform 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .logo:hover {
            transform: scale(1.05) rotate(1deg);
        }

        .user-welcome {
            margin-top: 15px;
            position: relative;
            z-index: 2;
        }

        .user-welcome h3 {
            font-size: 1.2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 8px;
        }

        .user-role {
            background: rgba(255,255,255,0.15);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 5px;
            display: inline-block;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            font-weight: 500;
        }

        .user-department {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .sidebar-nav {
            flex: 1;
            padding: 30px 0;
            width: 100%;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 18px 30px;
            color: rgba(255,255,255,0.85);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            font-size: 1.1rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            margin: 2px 0;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 100%;
            background: rgba(255,255,255,0.12);
            transition: width 0.3s ease;
            z-index: 1;
        }

        .nav-item:hover::before,
        .nav-item.active::before {
            width: 100%;
        }

        .nav-item:hover,
        .nav-item.active {
            color: white;
            transform: translateX(8px) scale(1.02);
            background: rgba(255,255,255,0.08);
            border-radius: 12px;
            box-shadow: inset 4px 0 0 #f4f6fa;
        }

        .nav-item i {
            margin-right: 16px;
            font-size: 1.4rem;
            width: 28px;
            transition: transform 0.3s ease;
            z-index: 2;
            position: relative;
        }

        .nav-item span {
            z-index: 2;
            position: relative;
        }

        .nav-item:hover i {
            transform: scale(1.15) rotate(8deg);
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            background: rgba(244, 246, 250, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px 0 0 25px;
            margin: 25px 25px 25px 0;
            overflow: hidden;
            position: relative;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .content-section {
            display: none;
            padding: 40px;
            height: calc(100vh - 50px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(8, 53, 200, 0.3) transparent;
        }

        .content-section::-webkit-scrollbar {
            width: 6px;
        }

        .content-section::-webkit-scrollbar-track {
            background: rgba(8, 53, 200, 0.05);
            border-radius: 3px;
        }

        .content-section::-webkit-scrollbar-thumb {
            background: rgba(8, 53, 200, 0.3);
            border-radius: 3px;
        }

        .content-section.active {
            display: block;
            animation: slideInRight 0.5s ease;
        }

        @keyframes slideInRight {
            0% { transform: translateX(30px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        /* Dashboard Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 2px solid rgba(8, 53, 200, 0.1);
        }

        .welcome-section h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0835C8;
            margin-bottom: 8px;
        }

        .welcome-section .user-name {
            color: #001f4d;
            font-weight: 800;
        }

        .welcome-section p {
            color: #666;
            font-size: 1.1rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-btn {
            position: relative;
            background: #0835C8;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(8, 53, 200, 0.3);
        }

        .notification-btn:hover {
            background: #001f4d;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(8, 53, 200, 0.4);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        /* Logout Button */
        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(8, 53, 200, 0.08);
            border: 1px solid rgba(8, 53, 200, 0.1);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(8, 53, 200, 0.03), transparent);
            transition: left 0.5s ease;
        }

        .stat-card:hover::before {
            left: 100%;
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 40px rgba(8, 53, 200, 0.15);
        }

        .stat-card-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-icon.job {
            background: linear-gradient(135deg, #0835C8, #001f4d);
        }

        .stat-icon.payment {
            background: linear-gradient(135deg, #4ecdc4, #44bd87);
        }

        .stat-icon.total {
            background: linear-gradient(135deg, #a55eea, #8b68ff);
        }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #001f4d;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #666;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Form Styles */
        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(8, 53, 200, 0.1);
            padding: 40px;
            border: 1px solid rgba(8, 53, 200, 0.1);
            max-width: 900px;
            margin: 0 auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: #0835C8;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            color: #0835C8;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px 20px;
            border-radius: 12px;
            border: 2px solid rgba(8, 53, 200, 0.1);
            background: #f7f7f7;
            color: #333;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #999;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0835C8;
            background: white;
            box-shadow: 0 0 0 4px rgba(8, 53, 200, 0.1);
            transform: translateY(-2px);
        }

        .file-upload {
            border: 2px dashed #0835C8;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            background: #f9f9f9;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: #001f4d;
            background: #f4f6fa;
        }

        .file-upload i {
            font-size: 3rem;
            color: #0835C8;
            margin-bottom: 15px;
        }

        .file-upload p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .file-upload small {
            color: #999;
            font-size: 0.9rem;
        }

        .file-input {
            display: none;
        }

        .submit-btn {
            background: linear-gradient(90deg, #0835C8 60%, #001f4d 100%);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 1.3rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 6px 20px rgba(8, 53, 200, 0.3);
            display: block;
            margin: 30px auto 0;
            min-width: 220px;
        }

        .submit-btn:hover {
            background: linear-gradient(90deg, #001f4d 60%, #0835C8 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(8, 53, 200, 0.4);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 10px;
            font-weight: 500;
        }

        .alert.success {
            background: rgba(68, 189, 135, 0.1);
            border: 1px solid #44bd87;
            color: #44bd87;
        }

        .alert.error {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid #ff6b6b;
            color: #ff6b6b;
        }

        /* Tables */
        .table-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(8, 53, 200, 0.1);
            overflow: hidden;
            border: 1px solid rgba(8, 53, 200, 0.1);
        }

        .table-header {
            background: linear-gradient(135deg, #0835C8, #001f4d);
            color: white;
            padding: 25px 30px;
        }

        .table-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8f9fa;
            color: #0835C8;
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 18px 15px;
            border-bottom: 1px solid rgba(8, 53, 200, 0.1);
            color: #333;
            font-weight: 500;
        }

        .data-table tr:hover td {
            background: rgba(8, 53, 200, 0.02);
        }

        .status-btn {
            border: none;
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: default;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }

        .status-btn.pending {
            background: rgba(255, 167, 38, 0.1);
            color: #ff7f00;
            border: 1px solid #ff7f00;
        }

        .section-title {
            color: #0835C8;
            font-size: 2rem;
            margin-bottom: 25px;
            font-weight: 700;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #0835C8, #001f4d);
            border-radius: 2px;
        }

        /* Recent Uploads Section */
        .recent-uploads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .upload-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(8, 53, 200, 0.08);
            display: flex;
            align-items: flex-start;
            padding: 20px;
            gap: 20px;
            transition: all 0.3s ease;
            border: 1px solid rgba(8, 53, 200, 0.1);
            position: relative;
            overflow: hidden;
        }

        .upload-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #0835C8;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .upload-card:hover::before {
            transform: scaleY(1);
        }

        .upload-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(8, 53, 200, 0.12);
        }

        .doc-preview img {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            background: #f4f6fa;
            border: 1px solid rgba(8, 53, 200, 0.1);
        }

        .upload-info {
            flex: 1;
        }

        .uploader {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .uploader-pic {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #0835C8;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .uploader-name {
            color: #0835C8;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .doc-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #001f4d;
            margin-bottom: 4px;
        }

        .doc-type {
            font-size: 1rem;
            color: #0835C8;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .doc-desc {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .doc-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: 8px;
        }

        .doc-date {
            font-size: 0.9rem;
            color: #0835C8;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: rgba(8, 53, 200, 0.3);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #0835C8;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 1.1rem;
            line-height: 1.6;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar_box {
                display: none;
            }
            
            .main-content {
                margin: 0;
                border-radius: 0;
            }
            
            .content-section {
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
    </div>

    <div class="container">
        <!-- Enhanced Sidebar -->
        <div class="sidebar_box" id="sidebar">
            <div class="logo-section">
                <svg class="logo" viewBox="0 0 180 50" xmlns="http://www.w3.org/2000/svg">
                    <text x="90" y="30" font-family="Arial, sans-serif" font-size="24" font-weight="bold" text-anchor="middle" fill="white">ReqZone</text>
                    <text x="90" y="42" font-family="Arial, sans-serif" font-size="8" text-anchor="middle" fill="rgba(255,255,255,0.8)">Facility Portal</text>
                </svg>
                <div class="user-welcome">
                    <h3>Welcome, <?php echo htmlspecialchars($user_data['name']); ?></h3>
                    <div class="user-role"><?php echo htmlspecialchars($user_data['role']); ?></div>
                    <div class="user-department">Diamond Heirs Facility</div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item active" onclick="showSection('dashboard')">
                    <i class="fas fa-house-user"></i>
                    <span>Dashboard</span>
                </div>
                <div class="nav-item" onclick="showSection('upload')">
                    <i class="fas fa-file-arrow-up"></i>
                    <span>Submit Request</span>
                </div>
                <div class="nav-item" onclick="showSection('records')">
                    <i class="fas fa-list-check"></i>
                    <span>My Records</span>
                </div>
                <div class="nav-item" onclick="showSection('profile')">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </div>
                <div class="nav-item" onclick="logout()" style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Dashboard Section -->
            <div class="content-section active" id="dashboard">
                <div class="dashboard-header">
                    <div class="welcome-section">
                        <h1>Hello <span class="user-name"><?php echo htmlspecialchars($user_data['name']); ?></span></h1>
                        <p>Welcome back to your Facility dashboard</p>
                    </div>
                    <div class="header-actions">
                        <button class="notification-btn" onclick="showNotifications()">
                            <i class="fas fa-bell"></i>
                            <?php if ($user_stats['total'] > 0): ?>
                            <span class="notification-badge"><?php echo $user_stats['total']; ?></span>
                            <?php endif; ?>
                        </button>
                        <button class="logout-btn" onclick="logout()">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="stats-grid">
                    <div class="stat-card" onclick="showSection('records')">
                        <div class="stat-card-content">
                            <div class="stat-icon job">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $user_stats['job']; ?></h3>
                                <p>Job Requests</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="showSection('records')">
                        <div class="stat-card-content">
                            <div class="stat-icon payment">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $user_stats['payment']; ?></h3>
                                <p>Payment Requests</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="showSection('records')">
                        <div class="stat-card-content">
                            <div class="stat-icon total">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $user_stats['total']; ?></h3>
                                <p>Total Requests</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="recent-uploads-section">
                    <h2 class="section-title">Recent Requests</h2>
                    
                    <?php if (empty($user_requests)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No Requests Yet</h3>
                            <p>You haven't submitted any Payment or Job requests yet. Click "Submit Request" to get started!</p>
                        </div>
                    <?php else: ?>
                        <div class="recent-uploads-grid">
                            <?php foreach(array_slice($user_requests, 0, 6) as $request): ?>
                            <div class="upload-card">
                                <div class="doc-preview">
                                    <img src="../images/Letter.png" alt="Document Preview" />
                                </div>
                                <div class="upload-info">
                                    <div class="uploader">
                                        <div class="uploader-pic"><?php echo strtoupper(substr($request['RequestSender'], 0, 1)); ?></div>
                                        <span class="uploader-name"><?php echo htmlspecialchars($request['RequestSender']); ?></span>
                                    </div>
                                    <div class="doc-title"><?php echo htmlspecialchars($request['DocumentName']); ?></div>
                                    <div class="doc-type"><?php echo htmlspecialchars($request['Request Type']); ?></div>
                                    <div class="doc-desc"><?php echo htmlspecialchars(substr($request['Description'], 0, 80)) . (strlen($request['Description']) > 80 ? '...' : ''); ?></div>
                                    <div class="doc-meta">
                                        <span class="doc-date">
                                            <i class="fa-regular fa-calendar"></i> 
                                            <?php echo isset($request['UploadDate']) ? date('M d, Y', strtotime($request['UploadDate'])) : 'Recent'; ?>
                                        </span>
                                        <button class="status-btn pending">Pending</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Submit Request Section -->
            <div class="content-section" id="upload">
                <div class="form-container">
                    <div class="form-header">
                        <h2>Submit New Request</h2>
                        <p>Submit Payment or Job requests to the NCC</p>
                    </div>

                    <?php if (isset($success_message)): ?>
                        <div class="alert success">
                            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="form-group full-width">
                            <label for="fileInput">Upload Documents</label>
                            <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Drag and drop your files here or click to browse</p>
                                <small>Support for multiple files (PDF, DOC, DOCX, JPG, PNG)</small>
                            </div>
                            <input class="file-input" type="file" id="fileInput" name="images[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display: none;" />
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="project-name">Document Name *</label>
                                <input type="text" id="project-name" name="project-name" placeholder="Enter the document name..." required />
                            </div>

                            <div class="form-group">
                                <label for="constituency">Request Type *</label>
                                <select id="constituency" name="constituency" required>
                                    <option value="">Select request type...</option>
                                    <option value="Payment">Payment Request</option>
                                    <option value="Job">Job Request</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="about-project">Description *</label>
                            <textarea id="about-project" name="about-project" rows="6" placeholder="Please provide a detailed description of your request..." required></textarea>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                    </form>
                </div>
            </div>

            <!-- Records Section -->
            <div class="content-section" id="records">
                <h2 class="section-title">My Request Records</h2>
                
                <?php if (empty($user_requests)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h3>No Records Found</h3>
                        <p>You haven't submitted any Payment or Job requests yet. Your request history will appear here once you start submitting requests.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-header">
                            <h2>Request History</h2>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Document Name</th>
                                    <th>Request Type</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($user_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['UploadDate']); ?></td>
                                    <td><?php echo htmlspecialchars($request['DocumentName']); ?></td>
                                    <td>
                                        <span class="status-btn <?php echo strtolower($request['Request Type']); ?>">
                                            <?php echo htmlspecialchars($request['Request Type']); ?>
                                        </span>
                                    </td>
                                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars(substr($request['Description'], 0, 60)) . (strlen($request['Description']) > 60 ? '...' : ''); ?>
                                    </td>
                                    <td><span class="status-btn pending">Pending</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentUser = <?php echo json_encode($user_data); ?>;
        let userRequests = <?php echo json_encode($user_requests); ?>;

        // Navigation functions
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });

            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });

            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to clicked nav item
            event.target.closest('.nav-item').classList.add('active');
        }

        // Show notifications
        function showNotifications() {
            alert(`You have ${userRequests.length} request(s) in your records.`);
        }

        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php';
            }
        }

        // Form submission handling
        document.querySelector('form').addEventListener('submit', function(e) {
            const requestType = document.getElementById('constituency').value;
            const allowedTypes = ['Payment', 'Job'];
            
            if (!allowedTypes.includes(requestType)) {
                e.preventDefault();
                alert('Facility users can only submit Payment or Job requests.');
                return false;
            }
            
            const submitBtn = this.querySelector('.submit-btn');
            const originalContent = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
            
            // Re-enable button after form processes
            setTimeout(() => {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }, 2000);
        });
    </script>
</body>
</html>