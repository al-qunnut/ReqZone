<?php
session_start();

// Check if the user is logged in and is an NCC user
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["userCategory"] !== "NCC") {
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
    'userCategory' => $_SESSION['userCategory'] ?? 'NCC',
    'userGroup' => $_SESSION['userGroup'] ?? '',
    'role' => $_SESSION['role'] ?? 'NCC Staff'
];

// Define role hierarchy and permissions
$role_hierarchy = [
    'EVC' => ['level' => 7, 'name' => 'CEO/Executive Vice Chairman', 'can_approve' => true, 'can_view_all' => true],
    'DCSH' => ['level' => 6, 'name' => 'Director - Corporate Services HO', 'can_approve' => true, 'can_view_all' => true],
    'ZCL' => ['level' => 5, 'name' => 'Zonal Controller', 'can_approve' => true, 'can_view_all' => true],
    'DCSL' => ['level' => 4, 'name' => 'Director - Corporate Services LGZO', 'can_approve' => true, 'can_view_all' => true],
    'UHCSL' => ['level' => 3, 'name' => 'Unit Head - Corporate Services LGZO', 'can_approve' => true, 'can_view_all' => false],
    'CSL' => ['level' => 2, 'name' => 'Corporate Services Staff', 'can_approve' => false, 'can_view_all' => false],
    'PH' => ['level' => 4, 'name' => 'Procurement Head', 'can_approve' => true, 'can_view_all' => false],
    'FH' => ['level' => 4, 'name' => 'Finance Head', 'can_approve' => true, 'can_view_all' => false]
];

$current_user_permissions = $role_hierarchy[$user_data['userGroup']] ?? ['level' => 1, 'name' => 'Staff', 'can_approve' => false, 'can_view_all' => false];

// Get user name from session
if (isset($_SESSION['name'])) {
    $name = $_SESSION['name'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentName = trim($_POST['project-name']);
    $requestType = trim($_POST['constituency']);
    $description = trim($_POST['about-project']);
    $requestSender = $name;

    // Validate input
    if ($documentName && $requestType && $description) {
        $stmt = mysqli_prepare(
            $link,
            "INSERT INTO fileInformation (`DocumentName`, `Request Type`, `Description`, `RequestSender`) VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssss", $documentName, $requestType, $description, $requestSender);

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Data submitted successfully!');</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($link) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Please fill in all fields.');</script>";
    }
}

// Fetch dashboard statistics
$stats = [
    'submissions' => 0,
    'responses' => 0,
    'memos' => 0,
    'pending' => 0
];

// Count total submissions
$result = mysqli_query($link, "SELECT COUNT(*) as count FROM fileInformation");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['submissions'] = $row['count'];
}

// Count responses (assuming you have a status field or responses table)
$result = mysqli_query($link, "SELECT COUNT(*) as count FROM fileInformation WHERE `Request Type` = 'Response'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['responses'] = $row['count'];
}

// Count memos
$result = mysqli_query($link, "SELECT COUNT(*) as count FROM fileInformation WHERE `Request Type` = 'Memo'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['memos'] = $row['count'];
}

// Count pending requests (assuming new requests are pending)
$result = mysqli_query($link, "SELECT COUNT(*) as count FROM fileInformation WHERE `Request Type` IN ('Payment', 'Job')");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['pending'] = $row['count'];
}

// Fetch recent activity
$recentActivity = [];
$result = mysqli_query($link, "SELECT * FROM fileInformation");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recentActivity[] = $row;
    }
}

// Fetch all requests for the requests section
$allRequests = [];
$result = mysqli_query($link, "SELECT * FROM fileInformation ");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $allRequests[] = $row;
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NCC Dashboard - ReqZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/ncc.css">
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
    </div>

    <div class="dashboard-container">
        <!-- Mobile Navigation -->
        <div class="mobile-nav">
            <h3>NCC Dashboard</h3>
            <button class="mobile-menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <svg class="logo" viewBox="0 0 180 50" xmlns="http://www.w3.org/2000/svg">
                        <text x="90" y="30" font-family="Arial, sans-serif" font-size="24" font-weight="bold" text-anchor="middle" fill="white">ReqZone</text>
                        <text x="90" y="42" font-family="Arial, sans-serif" font-size="8" text-anchor="middle" fill="rgba(255,255,255,0.8)">NCC Portal</text>
                    </svg>
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($user_data['name']); ?></h3>
                        <div class="role-badge"><?php echo htmlspecialchars($user_data['role']); ?></div>
                        <div class="department-badge"><?php echo htmlspecialchars($user_data['userGroup']); ?> - Level <?php echo $current_user_permissions['level']; ?></div>

                    </div>
                </div>
            </div>


            <nav class="sidebar-nav">
                <div class="nav-item active" onclick="showSection('dashboard', this)">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </div>
                <div class="nav-item" onclick="showSection('requests', this)">
                    <i class="fas fa-inbox"></i>
                    <span>Incoming Requests</span>
                    <?php if ($stats['pending'] > 0): ?>
                    <div class="notification-dot"></div>
                    <?php endif; ?>
                </div>
                <div class="nav-item" onclick="showSection('memo', this)">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send Memo</span>
                </div>
                <div class="nav-item" onclick="showSection('archive', this)">
                    <i class="fas fa-archive"></i>
                    <span>Archive</span>
                </div>
                <div class="nav-item" onclick="showSection('profile', this)">
                    <i class="fas fa-user-shield"></i>
                    <span>Profile</span>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Dashboard Section -->
            <div class="content-section active" id="dashboard">
                <div class="section-header">
                    <h1 class="section-title">Dashboard Overview</h1>
                    <div class="header-actions">
                        <div class="notification-bell" onclick="showNotifications()">
                            <i class="fas fa-bell"></i>
                            <?php if ($stats['pending'] > 0): ?>
                            <div class="notification-count"><?php echo $stats['pending']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card" onclick="showSection('requests', document.querySelector('.nav-item:nth-child(2)'))">
                        <div class="stat-card-content">
                            <div class="stat-icon submissions">
                                <i class="fas fa-envelope-open-text"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['submissions']; ?></h3>
                                <p>Total Submissions</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-content">
                            <div class="stat-icon responses">
                                <i class="fas fa-reply-all"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['responses']; ?></h3>
                                <p>Responses Sent</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="showSection('memo', document.querySelector('.nav-item:nth-child(3)'))">
                        <div class="stat-card-content">
                            <div class="stat-icon memos">
                                <i class="fas fa-memo-circle-info"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['memos']; ?></h3>
                                <p>Internal Memos</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-content">
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['pending']; ?></h3>
                                <p>Pending Actions</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="data-table-container">
                    <div class="table-header">
                        <h2>Recent Activity</h2>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentActivity)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.6);">
                                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        No requests found. Submit your first request below!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach(array_slice($recentActivity, 0, 5) as $activity): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($activity['RequestSender']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['DocumentName']); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($activity['Request Type']); ?>"><?php echo htmlspecialchars($activity['Request Type']); ?></span></td>
                                    <td><?php echo  htmlspecialchars($activity['UploadDate']); ?></td>
                                    <td><span class="status-badge new">New</span></td>
                                    <td>
                                        <button onclick="viewDocument(<?php echo $activity['id']; ?>)" class="action-btn view-btn" title="View Document">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="approveRequest(<?php echo $activity['id']; ?>)" class="action-btn approve-btn" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Incoming Requests Section -->
            <div class="content-section" id="requests">
                <div class="section-header">
                    <h1 class="section-title">Incoming Requests</h1>
                    <div class="header-actions">
                        <button class="action-btn" onclick="refreshPage()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <div class="data-table-container">
                    <div class="table-header">
                        <h2>All Requests</h2>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>From</th>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allRequests)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.6);">
                                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        No requests submitted yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($allRequests as $request): ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td><?php echo htmlspecialchars($request['RequestSender']); ?></td>
                                    <td><?php echo htmlspecialchars($request['DocumentName']); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($request['Request Type']); ?>"><?php echo htmlspecialchars($request['Request Type']); ?></span></td>
                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars(substr($request['Description'], 0, 50)) . (strlen($request['Description']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['UploadDate']) ; ?></td>
                                    <td><span class="status-badge new">Pending</span></td>
                                    <td>
                                        <button onclick="viewFullDocument(<?php echo $request['id']; ?>)" class="action-btn view-btn" title="View Full Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="approveRequest(<?php echo $request['id']; ?>)" class="action-btn approve-btn" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="rejectRequest(<?php echo $request['id']; ?>)" class="action-btn reject-btn" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Send Memo Section -->
            <div class="content-section" id="memo">
                <div class="section-header">
                    <h1 class="section-title">Submit New Request</h1>
                </div>

                <div class="form-container">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="project-name">Document/Project Name</label>
                            <input type="text" id="project-name" name="project-name" placeholder="Enter document or project name..." required>
                        </div>

                        <div class="form-group">
                            <label for="constituency">Request Type</label>
                            <select id="constituency" name="constituency" required>
                                <option value="">Select request type...</option>
                                <option value="Payment">Payment Request</option>
                                <option value="Job">Job Request</option>
                                <option value="Memo">Internal Memo</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="IT Support">IT Support</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="about-project">Description</label>
                            <textarea id="about-project" name="about-project" rows="8" placeholder="Provide detailed description of your request..." required></textarea>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i>
                            <span>Submit Request</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Archive Section -->
            <div class="content-section" id="archive">
                <div class="section-header">
                    <h1 class="section-title">Document Archive</h1>
                    <div class="header-actions">
                        <button class="action-btn" onclick="exportData()">
                            <i class="fas fa-download"></i> Export Data
                        </button>
                        <button class="action-btn" onclick="showFilter()">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>

                 <!-- Advanced Filters -->
                <div class="filters-panel" id="filter" style="display: none; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 25px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.2);">
                    <h3 style="color: white; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-filter"></i> Advanced Search Filters
                    </h3>
                    
                    <form method="GET" action="#" class="filter-form">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="color: white; display: block; margin-bottom: 8px; font-weight: 600;">
                                    <i class="fas fa-user"></i> Name/Document
                                </label>
                                <input type="text" name="filter_name" placeholder="Search by name or document..." 
                                       value="<?php echo htmlspecialchars($archive_filter_name); ?>" 
                                       style="width: 100%; padding: 12px 15px; border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; background: rgba(255,255,255,0.1); color: white; font-size: 1rem;">
                            </div>
                            
                            <div>
                                <label style="color: white; display: block; margin-bottom: 8px; font-weight: 600;">
                                    <i class="fas fa-building"></i> Department/Source
                                </label>
                                <select name="filter_dept" style="width: 100%; padding: 12px 15px; border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; background: rgba(255,255,255,0.1); color: white; font-size: 1rem;">
                                    <option value="">All Departments</option>
                                    <option value="facility" <?php echo ($archive_filter_dept == 'facility') ? 'selected' : ''; ?>>Facility Requests</option>
                                    <option value="ncc" <?php echo ($archive_filter_dept == 'ncc') ? 'selected' : ''; ?>>Internal NCC</option>
                                </select>
                            </div>
                            
                            <div>
                                <label style="color: white; display: block; margin-bottom: 8px; font-weight: 600;">
                                    <i class="fas fa-tag"></i> Request Type
                                </label>
                                <select name="filter_type" style="width: 100%; padding: 12px 15px; border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; background: rgba(255,255,255,0.1); color: white; font-size: 1rem;">
                                    <option value="">All Types</option>
                                    <option value="Payment" <?php echo ($archive_filter_type == 'Payment') ? 'selected' : ''; ?>>Payment</option>
                                    <option value="Job" <?php echo ($archive_filter_type == 'Job') ? 'selected' : ''; ?>>Job</option>
                                    <option value="Memo" <?php echo ($archive_filter_type == 'Memo') ? 'selected' : ''; ?>>Memo</option>
                                    <option value="Maintenance" <?php echo ($archive_filter_type == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="IT Support" <?php echo ($archive_filter_type == 'IT Support') ? 'selected' : ''; ?>>IT Support</option>
                                    <option value="Policy" <?php echo ($archive_filter_type == 'Policy') ? 'selected' : ''; ?>>Policy</option>
                                </select>
                            </div>
                            
                            <div>
                                <label style="color: white; display: block; margin-bottom: 8px; font-weight: 600;">
                                    <i class="fas fa-calendar"></i> Date Submitted
                                </label>
                                <input type="date" name="filter_date" 
                                       value="<?php echo htmlspecialchars($archive_filter_date); ?>" 
                                       style="width: 100%; padding: 12px 15px; border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; background: rgba(255,255,255,0.1); color: white; font-size: 1rem;">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button type="submit" style="background: linear-gradient(135deg, #4ecdc4, #44bd87); color: white; border: none; padding: 12px 25px; border-radius: 25px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <a href="?#archive" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 12px 25px; border-radius: 25px; font-weight: 600; text-decoration: none; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        </div>
                    </form>
                </div>


                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-content">
                            <div class="stat-icon submissions">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['submissions']; ?></h3>
                                <p>Total Documents</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-content">
                            <div class="stat-icon responses">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['submissions'] > 0 ? round(($stats['responses'] / $stats['submissions']) * 100) : 0; ?>%</h3>
                                <p>Processing Rate</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-content">
                            <div class="stat-icon memos">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo count($recentActivity); ?></h3>
                                <p>Recent Activity</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="data-table-container">
                    <div class="table-header">
                        <h2>All Documents Archive</h2>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Document Name</th>
                                <th>Type</th>
                                <th>Submitted By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allRequests)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.6);">
                                        <i class="fas fa-archive" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        No archived documents yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($allRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['id']); ?></td>
                                    <td><?php echo htmlspecialchars($request['DocumentName']); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($request['Request Type']); ?>"><?php echo htmlspecialchars($request['Request Type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($request['RequestSender']); ?></td>
                                    <td><?php echo  htmlspecialchars($request['UploadDate']);  ; ?></td>
                                    <td><span class="status-badge new">Archived</span></td>
                                    <td>
                                        <button onclick="viewFullDocument(<?php echo $request['id']; ?>)" class="action-btn view-btn" title="View Document">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="downloadDocument(<?php echo $request['id']; ?>)" class="action-btn" title="Download" style="color: #ffeb3b;">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Profile Section -->
            <div class="content-section" id="profile">
                <div class="section-header">
                    <h1 class="section-title">Profile Settings</h1>
                </div>

                <div class="form-container">
                    <div class="profile-section">
                        <div class="profile-avatar">
                            <div class="avatar-container">
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='60' fill='%23667eea'/%3E%3Ccircle cx='60' cy='45' r='20' fill='%23fff'/%3E%3Cpath d='M20 100 Q20 75 60 75 Q100 75 100 100 Z' fill='%23fff'/%3E%3C/svg%3E" 
                                     alt="Profile Picture" id="profileImage">
                            </div>
                        </div>

                        <form id="profileForm" onsubmit="updateProfile(event)">
                            <div class="form-group">
                                <label for="fullName">Full Name</label>
                                <input type="text" id="fullName" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" value="<?php echo strtolower(str_replace(' ', '.', $name)); ?>@ncc.gov.ng" required>
                            </div>

                            <div class="form-group">
                                <label for="department">Department</label>
                                <input type="text" id="department" value="Corporate Services LGZO" readonly>
                            </div>

                            <div class="form-group">
                                <label for="position">Position</label>
                                <input type="text" id="position" value="Senior Administrator" readonly>
                            </div>

                            <button type="submit" class="submit-btn">
                                <i class="fas fa-save"></i>
                                <span>Save Changes</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for viewing full document details -->
    <div id="documentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Document Details</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Dynamic content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentUser = {
            name: '<?php echo addslashes($name); ?>',
            role: 'Senior Administrator',
            department: 'Corporate Services LGZO'
        };

        // Database requests data from PHP
        let requestsData = <?php echo json_encode($allRequests); ?>;
        let statsData = <?php echo json_encode($stats); ?>;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboard();
            updateNotificationDot();
        });

        // Navigation functions
        function showSection(sectionId, navElement) {
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
            if (navElement) {
                navElement.classList.add('active');
            }

            // Close mobile sidebar
            document.getElementById('sidebar').classList.remove('open');
        }

        // Mobile sidebar toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        // Initialize dashboard
        function initializeDashboard() {
            updateDateTime();
            setInterval(updateDateTime, 60000); // Update every minute
        }

        // Update date and time
        function updateDateTime() {
            const now = new Date();
            console.log('Dashboard updated at:', now.toLocaleString());
        }

        // Update notification dot based on pending requests
        function updateNotificationDot() {
            const pendingCount = statsData.pending;
            const notificationDots = document.querySelectorAll('.notification-dot');
            
            notificationDots.forEach(dot => {
                if (pendingCount > 0) {
                    dot.style.display = 'block';
                } else {
                    dot.style.display = 'none';
                }
            });
        }

        // View full document details
        function viewFullDocument(id) {
            const request = requestsData.find(r => r.id == id);
            if (request) {
                document.getElementById('modalBody').innerHTML = `
                    <div class="document-details">
                        <div class="detail-row">
                            <strong>Document ID:</strong> #${request.id}
                        </div>
                        <div class="detail-row">
                            <strong>Document Name:</strong> ${request.DocumentName}
                        </div>
                        <div class="detail-row">
                            <strong>Request Type:</strong> <span class="status-badge ${request['Request Type'].toLowerCase()}">${request['Request Type']}</span>
                        </div>
                        <div class="detail-row">
                            <strong>Submitted By:</strong> ${request.RequestSender}
                        </div>
                        <div class="detail-row">
                            <strong>Description:</strong>
                            <div class="description-text">${request.Description}</div>
                        </div>
                        <div class="detail-row">
                            <strong>Date Submitted:</strong> ${request.created_at ? new Date(request.created_at).toLocaleString() : 'Recent'}
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button onclick="approveRequest(${request.id})" class="submit-btn" style="margin-right: 10px;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button onclick="rejectRequest(${request.id})" class="submit-btn" style="background: linear-gradient(135deg, #ff6b6b, #ee5a24);">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                `;
                document.getElementById('documentModal').style.display = 'block';
            }
        }

        // View document (simplified version)
        function viewDocument(id) {
            viewFullDocument(id);
        }

        // Close modal
        function closeModal() {
            document.getElementById('documentModal').style.display = 'none';
        }

        // Approve request
        function approveRequest(id) {
            if (confirm('Are you sure you want to approve this request?')) {
                showNotification(`Request #${id} has been approved successfully!`, 'success');
                closeModal();
            }
        }

        // Reject request
        function rejectRequest(id) {
            if (confirm('Are you sure you want to reject this request?')) {
                showNotification(`Request #${id} has been rejected.`, 'info');
                closeModal();
            }
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Show notifications
        function showNotifications() {
            const pendingCount = statsData.pending;
            if (pendingCount > 0) {
                showNotification(`You have ${pendingCount} pending request${pendingCount > 1 ? 's' : ''} requiring attention.`, 'info');
            } else {
                showNotification('No new notifications.', 'info');
            }
        }

        // Refresh page
        function refreshPage() {
            location.reload();
        }

        // Export data
        function exportData() {
            showNotification('Preparing data export...', 'info');
            setTimeout(() => {
                showNotification('Data export completed successfully!', 'success');
            }, 2000);
        }

        function showFilter() {
            document.getElementById('filter').style.display = 'block'
        }

        // Download document
        function downloadDocument(id) {
            showNotification(`Preparing download for document #${id}...`, 'info');
        }

        // Update profile
        function updateProfile(event) {
            event.preventDefault();
            const submitBtn = event.target.querySelector('.submit-btn');
            const originalContent = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                showNotification('Profile updated successfully!', 'success');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }, 1500);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('documentModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !mobileMenuBtn.contains(e.target) && 
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
    </script>


    <style>
        /* Additional styles for new functionality */
        .action-btn {
            background: none;
            border: none;
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
            cursor: pointer;
            padding: 8px;
            margin: 0 2px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: scale(1.1);
        }

        .view-btn:hover { color: #4ecdc4; }
        .approve-btn:hover { color: #44bd87; }
        .reject-btn:hover { color: #ff6b6b; }

        .filters-panel, .search-panel {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .filter-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: white;
            min-width: 150px;
        }

        .filter-btn, .refresh-btn, .search-btn, .export-btn {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .filter-btn:hover, .refresh-btn:hover, .search-btn:hover, .export-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .archive-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .archive-stat {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .archive-stat h3 {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 10px;
        }

        .archive-stat p {
            color: rgba(255,255,255,0.8);
            font-weight: 500;
        }

        .archive-message {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.8);
        }

        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-avatar {
            margin-bottom: 40px;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
        }

        .avatar-container img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
        }

        .edit-avatar-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #4ecdc4;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .edit-avatar-btn:hover {
            transform: scale(1.1);
            background: #44bd87;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-radius: 10px;
            border-left: 4px solid #4ecdc4;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 2000;
            animation: slideInRight 0.3s ease;
            color: #333;
            max-width: 350px;
        }

        .notification-success {
            border-left-color: #44bd87;
        }

        .notification button {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            margin-left: auto;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }

        .chart-container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .chart-container h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .chart-container canvas {
            width: 100%;
            height: 200px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-group {
                flex-direction: column;
            }
            
            .header-actions {
                flex-wrap: wrap;
                gap: 10px;
            }
        }
    </style>
</body>
</html>