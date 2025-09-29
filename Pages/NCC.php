
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
$current_user_level = $current_user_permissions['level'];

// Get user name from session
if (isset($_SESSION['name'])) {
    $name = $_SESSION['name'];
}

// Handle approval/rejection actions with comments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    $comments = trim($_POST['comments']);
    
    if ($request_id && in_array($action, ['approve', 'reject']) && !empty($comments)) {
        // Get current request details
        $stmt = mysqli_prepare($link, "SELECT * FROM fileInformation WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $request_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $request = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($request && $request['current_approval_level'] == $current_user_level) {
            // Add comment to approval_comments table
            $stmt = mysqli_prepare($link, "INSERT INTO approval_comments (request_id, commenter_level, commenter_name, commenter_role, comment_text, comment_type) VALUES (?, ?, ?, ?, ?, ?)");
            $userGroup = $user_data['userGroup'];
            $final_action = ($action === 'approve') ? 'approval' : 'rejection';
            
            mysqli_stmt_bind_param(
                $stmt,
                "iissss",
                $request_id,
                $current_user_level,
                $name,
                $userGroup,
                $comments,
                $final_action
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if ($action === 'approve') {
                // Get approval route for this request type
                $stmt = mysqli_prepare($link, "SELECT route_order FROM approval_routes WHERE request_type = ? AND is_active = 1");
                mysqli_stmt_bind_param($stmt, "s", $request['Request Type']);
                mysqli_stmt_execute($stmt);
                $route_result = mysqli_stmt_get_result($stmt);
                $route = mysqli_fetch_assoc($route_result);
                mysqli_stmt_close($stmt);
                
                if ($route) {
                    $route_levels = json_decode($route['route_order'], true);
                    $current_index = array_search($current_user_level, $route_levels);
                    
                    if ($current_index !== false && $current_index < count($route_levels) - 1) {
                        // Move to next approval level
                        $next_level = $route_levels[$current_index + 1];
                        $stmt = mysqli_prepare($link, "UPDATE fileInformation SET current_approval_level = ?, status = 'in_progress' WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "ii", $next_level, $request_id);
                    } else {
                        // Final approval - mark as approved
                        $stmt = mysqli_prepare($link, "UPDATE fileInformation SET status = 'approved', current_approval_level = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "ii", $current_user_level, $request_id);
                    }
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            } else {
                // Rejection - send back to previous level or mark as rejected
                $stmt = mysqli_prepare($link, "SELECT route_order FROM approval_routes WHERE request_type = ? AND is_active = 1");
                mysqli_stmt_bind_param($stmt, "s", $request['Request Type']);
                mysqli_stmt_execute($stmt);
                $route_result = mysqli_stmt_get_result($stmt);
                $route = mysqli_fetch_assoc($route_result);
                mysqli_stmt_close($stmt);
                
                if ($route) {
                    $route_levels = json_decode($route['route_order'], true);
                    $current_index = array_search($current_user_level, $route_levels);
                    
                    if ($current_index > 0) {
                        // Send back to previous level
                        $previous_level = $route_levels[$current_index - 1];
                        $stmt = mysqli_prepare($link, "UPDATE fileInformation SET current_approval_level = ?, status = 'pending' WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "ii", $previous_level, $request_id);
                    } else {
                        // First level rejection - mark as rejected
                        $stmt = mysqli_prepare($link, "UPDATE fileInformation SET status = 'rejected' WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "i", $request_id);
                    }
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
            
            // Update approval workflow table
            $stmt = mysqli_prepare($link, "INSERT INTO approval_workflow (request_id, approver_level, approver_name, approver_role, action, comments, action_date) VALUES (?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE action = VALUES(action), comments = VALUES(comments), action_date = VALUES(action_date)");
            
            $userGroup = $user_data['userGroup'];
            $status    = ($action === 'approve') ? 'approved' : 'rejected';
            
            mysqli_stmt_bind_param(
                $stmt,
                "iissss",
                $request_id,
                $current_user_level,
                $name,
                $userGroup,
                $status,
                $comments
            );


            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle file upload
        $uploadedFiles = [];
        $uploadDir = '../uploads/ncc_documents/';
        
        
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $allowedTypes_file = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                if ($_FILES['images']['error'][$i] === 0) {
                    $fileName = $_FILES['images']['name'][$i];
                    $fileTmpName = $_FILES['images']['tmp_name'][$i];
                    $fileSize = $_FILES['images']['size'][$i];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    // Validate file
                    if (!in_array($fileExtension, $allowedTypes_file)) {
                        $error_message = "Invalid file type. Only PDF, DOC, DOCX, JPG, JPEG, PNG files are allowed.";
                        break;
                    }
                    
                    if ($fileSize > $maxFileSize) {
                        $error_message = "File size too large. Maximum size is 5MB.";
                        break;
                    }
                    
                    // Generate unique filename
                    $uniqueFileName = time() . '_' . $user_data['id'] . '_' . $i . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $uniqueFileName;
                    
                    if (move_uploaded_file($fileTmpName, $uploadPath)) {
                        $uploadedFiles[] = [
                            'original_name' => $fileName,
                            'stored_name' => $uniqueFileName,
                            'file_path' => $uploadPath,
                            'file_type' => $fileExtension,
                            'file_size' => $fileSize
                        ];
                    }
                }
            }
        }

// Initialize archive filter variables
$archive_filter_name = isset($_GET['filter_name']) ? trim($_GET['filter_name']) : '';
$archive_filter_dept = isset($_GET['filter_dept']) ? trim($_GET['filter_dept']) : '';
$archive_filter_type = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
$archive_filter_date = isset($_GET['filter_date']) ? trim($_GET['filter_date']) : '';

// Handle form submission for new requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $documentName = trim($_POST['project-name']);
    $requestType = trim($_POST['constituency']);
    $description = trim($_POST['about-project']);
    $requestSender = $name;
    $senderEmail = $user_data['email'];

    // Validate input
    if ($documentName && $requestType && $description) {
        // Get the approval route for this request type
        $stmt = mysqli_prepare($link, "SELECT start_level, end_level FROM approval_routes WHERE request_type = ? AND is_active = 1");
        mysqli_stmt_bind_param($stmt, "s", $requestType);
        mysqli_stmt_execute($stmt);
        $route_result = mysqli_stmt_get_result($stmt);
        $route = mysqli_fetch_assoc($route_result);
        mysqli_stmt_close($stmt);
        
        $start_level = $route ? $route['start_level'] : 2;
        $end_level = $route ? $route['end_level'] : 4;
        
        $stmt = mysqli_prepare(
            $link,
            "INSERT INTO fileInformation (`DocumentName`, `Request Type`, `Description`, `RequestSender`, `SenderEmail`, `UploadDate`, `status`, `current_approval_level`, `created_by_level`, `final_approver_level`) VALUES (?, ?, ?, ?, ?, NOW(), 'pending', ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "sssssiii",   // 8 total (5 strings + 3 ints)
            $documentName,
            $requestType,
            $description,
            $requestSender,
            $senderEmail,
            $start_level,
            $current_user_level,
            $end_level
        );
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Request submitted successfully and sent for approval!');</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($link) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Please fill in all fields.');</script>";
    }
}

// Function to get requests for current user level
function getRequestsForUserLevel($link, $user_level, $can_view_all) {
    if ($can_view_all) {
        // Senior roles can see all requests
        $query = "SELECT f.*, 
                         GROUP_CONCAT(DISTINCT ac.comment_text ORDER BY ac.created_at DESC SEPARATOR '|||') as all_comments,
                         GROUP_CONCAT(DISTINCT CONCAT(ac.commenter_name, ':', ac.comment_type, ':', ac.created_at) ORDER BY ac.created_at DESC SEPARATOR '|||') as comment_details
                  FROM fileInformation f 
                  LEFT JOIN approval_comments ac ON f.id = ac.request_id 
                  GROUP BY f.id 
                  ORDER BY f.UploadDate DESC";
    } else {
        // Regular users only see requests at their level or requests they created
        $query = "SELECT f.*, 
                         GROUP_CONCAT(DISTINCT ac.comment_text ORDER BY ac.created_at DESC SEPARATOR '|||') as all_comments,
                         GROUP_CONCAT(DISTINCT CONCAT(ac.commenter_name, ':', ac.comment_type, ':', ac.created_at) ORDER BY ac.created_at DESC SEPARATOR '|||') as comment_details
                  FROM fileInformation f 
                  LEFT JOIN approval_comments ac ON f.id = ac.request_id 
                  WHERE (f.current_approval_level = ? OR f.created_by_level = ?)
                  GROUP BY f.id 
                  ORDER BY f.UploadDate DESC";
    }
    
    if ($can_view_all) {
        $result = mysqli_query($link, $query);
    } else {
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, "ii", $user_level, $user_level);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
    
    $requests = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Decode uploaded files if present
            if (!empty($row['UploadedFiles'])) {
                $row['files'] = json_decode($row['UploadedFiles'], true);
            } else {
                $row['files'] = [];
            }
            
            // Process comments
            if (!empty($row['all_comments'])) {
                $comments = explode('|||', $row['all_comments']);
                $comment_details = explode('|||', $row['comment_details']);
                $row['comments'] = [];
                
                foreach ($comment_details as $i => $detail) {
                    $parts = explode(':', $detail, 3);
                    if (count($parts) >= 3) {
                        $row['comments'][] = [
                            'commenter' => $parts[0],
                            'type' => $parts[1],
                            'date' => $parts[2],
                            'text' => $comments[$i] ?? ''
                        ];
                    }
                }
            } else {
                $row['comments'] = [];
            }
            
            $requests[] = $row;
        }
        
        if (!$can_view_all) {
            mysqli_stmt_close($stmt);
        }
    }
    
    return $requests;
}

// Fetch dashboard statistics
$stats = [
    'submissions' => 0,
    'pending_my_level' => 0,
    'approved_by_me' => 0,
    'rejected_by_me' => 0
];

// Count total submissions
$result = mysqli_query($link, "SELECT COUNT(*) as count FROM fileInformation");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['submissions'] = $row['count'];
}

// Count requests pending at my level
$stmt = mysqli_prepare($link, "SELECT COUNT(*) as count FROM fileInformation WHERE current_approval_level = ? AND status IN ('pending', 'in_progress')");
mysqli_stmt_bind_param($stmt, "i", $current_user_level);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['pending_my_level'] = $row['count'];
}
mysqli_stmt_close($stmt);

// Count approved by me
$stmt = mysqli_prepare($link, "SELECT COUNT(*) as count FROM approval_workflow WHERE approver_level = ? AND action = 'approved'");
mysqli_stmt_bind_param($stmt, "i", $current_user_level);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['approved_by_me'] = $row['count'];
}
mysqli_stmt_close($stmt);

// Count rejected by me
$stmt = mysqli_prepare($link, "SELECT COUNT(*) as count FROM approval_workflow WHERE approver_level = ? AND action = 'rejected'");
mysqli_stmt_bind_param($stmt, "i", $current_user_level);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['rejected_by_me'] = $row['count'];
}
mysqli_stmt_close($stmt);

// Get all requests for this user
$allRequests = getRequestsForUserLevel($link, $current_user_level, $current_user_permissions['can_view_all']);
$recentActivity = array_slice($allRequests, 0, 10);

// Helper function for formatting file sizes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Helper function to get role name by level
function getRoleNameByLevel($level, $role_hierarchy) {
    foreach ($role_hierarchy as $role => $data) {
        if ($data['level'] == $level) {
            return $data['name'];
        }
    }
    return "Level $level User";
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
                    <span>My Queue</span>
                    <?php if ($stats['pending_my_level'] > 0): ?>
                    <div class="notification-dot"><?php echo $stats['pending_my_level']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="nav-item" onclick="showSection('memo', this)">
                    <i class="fas fa-paper-plane"></i>
                    <span>New Request</span>
                </div>
                <div class="nav-item" onclick="showSection('archive', this)">
                    <i class="fas fa-archive"></i>
                    <span>All Requests</span>
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
                            <?php if ($stats['pending_my_level'] > 0): ?>
                            <div class="notification-count"><?php echo $stats['pending_my_level']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card" onclick="showSection('requests', document.querySelector('.nav-item:nth-child(2)'))">
                        <div class="stat-card-content">
                            <div class="stat-icon submissions">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['pending_my_level']; ?></h3>
                                <p>Pending My Approval</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-content">
                            <div class="stat-icon responses">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['approved_by_me']; ?></h3>
                                <p>Approved by Me</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-content">
                            <div class="stat-icon memos">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['rejected_by_me']; ?></h3>
                                <p>Rejected by Me</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="showSection('archive', document.querySelector('.nav-item:nth-child(4)'))">
                        <div class="stat-card-content">
                            <div class="stat-icon pending">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['submissions']; ?></h3>
                                <p>Total Requests</p>
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
                                <th>Current Level</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentActivity)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.6);">
                                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        No requests found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($recentActivity as $activity): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($activity['RequestSender']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['DocumentName']); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($activity['Request Type']); ?>"><?php echo htmlspecialchars($activity['Request Type']); ?></span></td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($activity['UploadDate']))); ?></td>
                                    <td>Level <?php echo $activity['current_approval_level']; ?></td>
                                    <td><span class="status-badge <?php echo $activity['status']; ?>"><?php echo ucfirst($activity['status']); ?></span></td>
                                    <td>
                                        <button onclick="viewFullDocument(<?php echo $activity['id']; ?>)" class="action-btn view-btn" title="View Document">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($activity['current_approval_level'] == $current_user_level && $activity['status'] != 'approved' && $activity['status'] != 'rejected'): ?>
                                        <button onclick="showApprovalModal(<?php echo $activity['id']; ?>, 'approve')" class="action-btn approve-btn" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="showApprovalModal(<?php echo $activity['id']; ?>, 'reject')" class="action-btn reject-btn" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- My Queue Section (renamed from Incoming Requests) -->
            <div class="content-section" id="requests">
                <div class="section-header">
                    <h1 class="section-title">My Approval Queue</h1>
                    <div class="header-actions">
                        <button class="action-btn" onclick="refreshPage()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <div class="data-table-container">
                    <div class="table-header">
                        <h2>Requests Requiring My Approval</h2>
                        <span style="color: #4ecdc4; font-size: 0.9rem;">Level <?php echo $current_user_level; ?> - <?php echo htmlspecialchars($current_user_permissions['name']); ?></span>
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
                                <th>Comments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pendingRequests = array_filter($allRequests, function($request) use ($current_user_level) {
                                return $request['current_approval_level'] == $current_user_level && 
                                       in_array($request['status'], ['pending', 'in_progress']);
                            });
                            ?>
                            <?php if (empty($pendingRequests)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.6);">
                                        <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 10px; display: block; color: #44bd87;"></i>
                                        No requests pending your approval at this time.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($pendingRequests as $request): ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td><?php echo htmlspecialchars($request['RequestSender']); ?></td>
                                    <td><?php echo htmlspecialchars($request['DocumentName']); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($request['Request Type']); ?>"><?php echo htmlspecialchars($request['Request Type']); ?></span></td>
                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars(substr($request['Description'], 0, 50)) . (strlen($request['Description']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($request['UploadDate']))); ?></td>
                                    <td><span class="status-badge <?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                                    <td>
                                        <?php if (!empty($request['comments'])): ?>
                                            <span style="color: #4ecdc4;">
                                                <i class="fas fa-comments"></i> <?php echo count($request['comments']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: rgba(255,255,255,0.4);">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="viewFullDocument(<?php echo $request['id']; ?>)" class="action-btn view-btn" title="View Full Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="showApprovalModal(<?php echo $request['id']; ?>, 'approve')" class="action-btn approve-btn" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="showApprovalModal(<?php echo $request['id']; ?>, 'reject')" class="action-btn reject-btn" title="Reject">
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

            <!-- New Request Section -->
            <div class="content-section" id="memo">
                <div class="section-header">
                    <h1 class="section-title">Submit New Request</h1>
                </div>

                <div class="form-container">
                    <form method="POST" action="">

                      <div class="form-group full-width">
                            <label for="fileInput">Upload Documents</label>
                            <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Drag and drop your files here or click to browse</p>
                                <small>Support for multiple files (PDF, DOC, DOCX, JPG, PNG) - Max 5MB per file</small>
                            </div>
                            <input class="file-input" type="file" id="fileInput" name="images[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display: none;" />
                            <div class="uploaded-files-preview" id="filesPreview"></div>
                        </div>

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
                    <h1 class="section-title">All Requests</h1>
                    <div class="header-actions">
                        <button class="action-btn" onclick="exportData()">
                            <i class="fas fa-download"></i> Export Data
                        </button>
                        <button class="action-btn" onclick="showFilter()">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>

                <div class="data-table-container">
                    <div class="table-header">
                        <h2>All Requests Archive</h2>
                        <?php if ($current_user_permissions['can_view_all']): ?>
                            <span style="color: #4ecdc4; font-size: 0.9rem; margin-left: 15px;">
                                <i class="fas fa-eye"></i> Full Access View
                            </span>
                        <?php else: ?>
                            <span style="color: #ffeb3b; font-size: 0.9rem; margin-left: 15px;">
                                <i class="fas fa-filter"></i> My Level Only
                            </span>
                        <?php endif; ?>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Document Name</th>
                                <th>Type</th>
                                <th>Submitted By</th>
                                <th>Date</th>
                                <th>Current Level</th>
                                <th>Status</th>
                                <th>Comments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allRequests)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.6);">
                                        <i class="fas fa-archive" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        No requests found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($allRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['id']); ?></td>
                                    <td><?php echo htmlspecialchars($request['DocumentName']); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($request['Request Type']); ?>"><?php echo htmlspecialchars($request['Request Type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($request['RequestSender']); ?></td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($request['UploadDate']))); ?></td>
                                    <td>
                                        <span class="level-badge">
                                            Level <?php echo $request['current_approval_level']; ?>
                                        </span>
                                    </td>
                                    <td><span class="status-badge <?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                                    <td>
                                        <?php if (!empty($request['comments'])): ?>
                                            <span style="color: #4ecdc4;">
                                                <i class="fas fa-comments"></i> <?php echo count($request['comments']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: rgba(255,255,255,0.4);">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="viewFullDocument(<?php echo $request['id']; ?>)" class="action-btn view-btn" title="View Document">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($request['current_approval_level'] == $current_user_level && !in_array($request['status'], ['approved', 'rejected'])): ?>
                                        <button onclick="showApprovalModal(<?php echo $request['id']; ?>, 'approve')" class="action-btn approve-btn" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="showApprovalModal(<?php echo $request['id']; ?>, 'reject')" class="action-btn reject-btn" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
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
                                <input type="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="department">Department</label>
                                <input type="text" id="department" value="<?php echo htmlspecialchars($current_user_permissions['name']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label for="position">Position</label>
                                <input type="text" id="position" value="<?php echo htmlspecialchars($user_data['userGroup']); ?>" readonly>
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

    <!-- Modal for approval/rejection with comments -->
    <div id="approvalModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="approvalModalTitle">Approve Request</h2>
                <span class="close" onclick="closeApprovalModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="approvalForm" method="POST" action="">
                    <input type="hidden" id="approvalRequestId" name="request_id">
                    <input type="hidden" id="approvalAction" name="action">
                    
                    <div class="form-group">
                        <label for="approvalComments">Comments (Required)</label>
                        <textarea id="approvalComments" name="comments" rows="6" placeholder="Please provide your comments for this decision..." required style="width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; resize: vertical; font-family: inherit; font-size: 1rem;"></textarea>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <p style="color: #666; font-size: 0.9rem; margin-bottom: 20px;">
                            <i class="fas fa-info-circle"></i> 
                            Your comments will be recorded and visible to other approvers in the workflow.
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                        <button type="submit" id="approvalSubmitBtn" class="submit-btn">
                            <i class="fas fa-check"></i>
                            <span>Confirm Action</span>
                        </button>
                        <button type="button" onclick="closeApprovalModal()" class="button" style="background: rgba(255,255,255,0.2);">
                            <i class="fas fa-times"></i>
                            <span>Cancel</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentUser = {
            name: '<?php echo addslashes($name); ?>',
            role: '<?php echo addslashes($user_data['role']); ?>',
            department: '<?php echo addslashes($current_user_permissions['name']); ?>',
            level: <?php echo $current_user_level; ?>,
            canViewAll: <?php echo $current_user_permissions['can_view_all'] ? 'true' : 'false'; ?>
        };

        // Role hierarchy for reference
        let roleHierarchy = <?php echo json_encode($role_hierarchy); ?>;

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
            const pendingCount = statsData.pending_my_level;
            const notificationDots = document.querySelectorAll('.notification-dot');
            
            notificationDots.forEach(dot => {
                if (pendingCount > 0) {
                    dot.style.display = 'block';
                    dot.textContent = pendingCount;
                } else {
                    dot.style.display = 'none';
                }
            });
        }

        // Show approval modal with comments
        function showApprovalModal(requestId, action) {
            const request = requestsData.find(r => r.id == requestId);
            if (!request) return;
            
            document.getElementById('approvalRequestId').value = requestId;
            document.getElementById('approvalAction').value = action;
            
            const title = action === 'approve' ? 'Approve Request' : 'Reject Request';
            const buttonText = action === 'approve' ? 'Approve Request' : 'Reject Request';
            const buttonIcon = action === 'approve' ? 'fa-check' : 'fa-times';
            const buttonColor = action === 'approve' ? 'linear-gradient(135deg, #44bd87, #4ecdc4)' : 'linear-gradient(135deg, #ff6b6b, #ee5a24)';
            
            document.getElementById('approvalModalTitle').textContent = title;
            document.getElementById('approvalSubmitBtn').innerHTML = `<i class="fas ${buttonIcon}"></i><span>${buttonText}</span>`;
            document.getElementById('approvalSubmitBtn').style.background = buttonColor;
            
            // Clear previous comments
            document.getElementById('approvalComments').value = '';
            
            document.getElementById('approvalModal').style.display = 'block';
        }

        // Close approval modal
        function closeApprovalModal() {
            document.getElementById('approvalModal').style.display = 'none';
        }

        // View full document details with enhanced comment display
        function viewFullDocument(id) {
            const request = requestsData.find(r => r.id == id);
            if (request) {
                let filesHTML = '';
                if (request.files && request.files.length > 0) {
                    filesHTML = '<div class="detail-row"><strong>Attached Files:</strong><div class="files-list">';
                    request.files.forEach(file => {
                        filesHTML += `
                            <div class="file-item" style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 8px; margin: 5px 0; display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-file" style="color: #4ecdc4;"></i>
                                    <div>
                                        <div style="font-weight: 500;">${file.original_name}</div>
                                        <div style="font-size: 0.85rem; color: rgba(255,255,255,0.6);">${formatBytes(file.file_size)}</div>
                                    </div>
                                </div>
                                <div>
                                    <button onclick="viewFile('${file.stored_name}', '${file.file_type}')" class="action-btn view-btn" title="View File" style="margin-right: 5px;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="../uploads/facility_documents/${file.stored_name}" download="${file.original_name}" class="action-btn" title="Download" style="color: #ffeb3b; text-decoration: none;">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                    filesHTML += '</div></div>';
                }

                // Display comments with improved formatting
                let commentsHTML = '';
                if (request.comments && request.comments.length > 0) {
                    commentsHTML = '<div class="detail-row"><strong>Approval History:</strong><div class="comments-timeline">';
                    request.comments.forEach(comment => {
                        const commentDate = new Date(comment.date).toLocaleString();
                        const typeIcon = comment.type === 'approval' ? 'fa-check-circle' : comment.type === 'rejection' ? 'fa-times-circle' : 'fa-comment';
                        const typeColor = comment.type === 'approval' ? '#44bd87' : comment.type === 'rejection' ? '#ff6b6b' : '#4ecdc4';
                        
                        commentsHTML += `
                            <div class="comment-item" style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 3px solid ${typeColor};">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <i class="fas ${typeIcon}" style="color: ${typeColor};"></i>
                                    <strong style="color: white;">${comment.commenter}</strong>
                                    <span style="color: rgba(255,255,255,0.6); font-size: 0.85rem;">${commentDate}</span>
                                    <span class="comment-type-badge" style="background: ${typeColor}; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; text-transform: uppercase;">${comment.type}</span>
                                </div>
                                <div style="color: rgba(255,255,255,0.9); line-height: 1.5;">${comment.text}</div>
                            </div>
                        `;
                    });
                    commentsHTML += '</div></div>';
                }

                      // File upload handling
            document.getElementById('fileInput').addEventListener('change', function(e) {
            const files = e.target.files;
            const preview = document.getElementById('filesPreview');
            const uploadDiv = document.querySelector('.file-upload');
            
            preview.innerHTML = '';
            
            if (files.length > 0) {
                uploadDiv.classList.add('has-files');
                uploadDiv.innerHTML = `
                    <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    <p style="color: #28a745;">${files.length} file(s) selected</p>
                    <small>Click to change selection</small>
                `;
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'file-preview';
                    
                    const fileIcon = getFileIcon(file.name);
                    const fileSize = formatBytes(file.size);
                    
                    fileDiv.innerHTML = `
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center;">
                                <i class="${fileIcon}" style="margin-right: 10px; color: #007bff;"></i>
                                <div>
                                    <div style="font-weight: 500;">${file.name}</div>
                                    <div style="font-size: 0.8rem; color: #6c757d;">${fileSize}</div>
                                </div>
                            </div>
                            <button type="button" onclick="removeFile(${i})" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    
                    preview.appendChild(fileDiv);
                }
            } else {
                uploadDiv.classList.remove('has-files');
                uploadDiv.innerHTML = `
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag and drop your files here or click to browse</p>
                    <small>Support for multiple files (PDF, DOC, DOCX, JPG, PNG) - Max 5MB per file</small>
                `;
            }
        });

        // Get file icon based on extension
        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            switch(ext) {
                case 'pdf': return 'fas fa-file-pdf';
                case 'doc':
                case 'docx': return 'fas fa-file-word';
                case 'jpg':
                case 'jpeg':
                case 'png': return 'fas fa-file-image';
                default: return 'fas fa-file';
            }
        }

        // Format file size
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        // Remove file from selection
        function removeFile(index) {
            const fileInput = document.getElementById('fileInput');
            const dt = new DataTransfer();
            const files = fileInput.files;
            
            for (let i = 0; i < files.length; i++) {
                if (i !== index) {
                    dt.items.add(files[i]);
                }
            }
            
            fileInput.files = dt.files;
            fileInput.dispatchEvent(new Event('change'));
        }

        // Document viewer function
        function viewDocument(storedName, originalName, fileType) {
            const modal = document.getElementById('documentModal');
            const title = document.getElementById('modalTitle');
            const viewer = document.getElementById('documentViewer');
            
            title.textContent = originalName;
            
            const filePath = '../uploads/ncc_documents/' + storedName;
            
            if (['jpg', 'jpeg', 'png'].includes(fileType.toLowerCase())) {
                viewer.innerHTML = `<img src="${filePath}" style="max-width: 100%; height: auto;" alt="Document Image">`;
            } else if (fileType.toLowerCase() === 'pdf') {
                viewer.innerHTML = `<iframe src="${filePath}" class="document-viewer" type="application/pdf"></iframe>`;
            } else {
                viewer.innerHTML = `
                    <div style="text-align: center; padding: 50px;">
                        <i class="fas fa-file" style="font-size: 4rem; color: #6c757d; margin-bottom: 20px;"></i>
                        <h3>Document Preview Not Available</h3>
                        <p>This file type cannot be previewed in the browser.</p>
                        <a href="download.php?file=${encodeURIComponent(storedName)}&name=${encodeURIComponent(originalName)}" class="file-btn download" style="display: inline-block; margin-top: 20px; padding: 10px 20px; text-decoration: none;">
                            <i class="fas fa-download"></i> Download to View
                        </a>
                    </div>
                `;
            }
            
            modal.style.display = 'block';
        }


                const sourceLabel = request.SenderEmail ? '<span style="background: linear-gradient(135deg, #667eea, #764ba2); padding: 4px 12px; border-radius: 15px; font-size: 0.85rem;">Facility Request</span>' : '<span style="background: linear-gradient(135deg, #f093fb, #f5576c); padding: 4px 12px; border-radius: 15px; font-size: 0.85rem;">Internal NCC</span>';

                // Get role name for current approval level
                const currentLevelRole = getRoleNameByLevel(request.current_approval_level);

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
                            <strong>Source:</strong> ${sourceLabel}
                        </div>
                        <div class="detail-row">
                            <strong>Submitted By:</strong> ${request.RequestSender}
                        </div>
                        <div class="detail-row">
                            <strong>Current Status:</strong> <span class="status-badge ${request.status}">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span>
                        </div>
                        <div class="detail-row">
                            <strong>Current Approval Level:</strong> <span class="level-badge">Level ${request.current_approval_level} - ${currentLevelRole}</span>
                        </div>
                        ${request.SenderEmail ? `<div class="detail-row"><strong>Email:</strong> ${request.SenderEmail}</div>` : ''}
                        <div class="detail-row">
                            <strong>Description:</strong>
                            <div class="description-text" style="margin-top: 10px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px; line-height: 1.6;">${request.Description}</div>
                        </div>
                        ${filesHTML}
                        ${commentsHTML}
                        <div class="detail-row">
                            <strong>Date Submitted:</strong> ${new Date(request.UploadDate).toLocaleString()}
                        </div>
                    </div>
                    ${request.current_approval_level == currentUser.level && !['approved', 'rejected'].includes(request.status) ? `
                    <div class="modal-actions" style="margin-top: 25px; display: flex; gap: 10px; justify-content: center;">
                        <button onclick="showApprovalModal(${request.id}, 'approve')" class="submit-btn" style="margin: 0;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button onclick="showApprovalModal(${request.id}, 'reject')" class="submit-btn" style="background: linear-gradient(135deg, #ff6b6b, #ee5a24); margin: 0;">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>` : ''}
                `;
                document.getElementById('documentModal').style.display = 'block';
            }
        }

        // Helper function to get role name by level
        function getRoleNameByLevel(level) {
            for (const [role, data] of Object.entries(roleHierarchy)) {
                if (data.level == level) {
                    return data.name;
                }
            }
            return `Level ${level} User`;
        }

        // Format bytes helper
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // View file in new tab
        function viewFile(storedName, fileType) {
            const filePath = '../uploads/facility_documents/' + storedName;
            window.open(filePath, '_blank');
        }

        // Close modal
        function closeModal() {
            document.getElementById('documentModal').style.display = 'none';
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
            const pendingCount = statsData.pending_my_level;
            if (pendingCount > 0) {
                showNotification(`You have ${pendingCount} request${pendingCount > 1 ? 's' : ''} pending your approval.`, 'info');
            } else {
                showNotification('No requests pending your approval.', 'success');
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

        // Show/hide filter panel
        function showFilter() {
            const filterPanel = document.getElementById('filter');
            if (filterPanel && filterPanel.style.display === 'none') {
                filterPanel.style.display = 'block';
            } else if (filterPanel) {
                filterPanel.style.display = 'none';
            }
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
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            margin: 3% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
            position: relative;
        }

        .modal-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .modal-header .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            line-height: 1;
            padding: 0;
            background: none;
            border: none;
        }

        .modal-header .close:hover {
            transform: scale(1.2);
            opacity: 0.8;
        }

        .modal-body {
            padding: 30px;
            color: #333;
            max-height: 70vh;
            overflow-y: auto;
        }

        .document-details .detail-row {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .document-details .detail-row:last-child {
            border-bottom: none;
        }

        .document-details .detail-row strong {
            display: block;
            color: #667eea;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .document-details .description-text {
            line-height: 1.8;
            color: #555;
        }

        .files-list {
            margin-top: 10px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

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

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Scrollbar styling for modal */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #764ba2;
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


        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
            
            .modal-body {
                padding: 20px;
                max-height: 60vh;
            }
            
            .modal-header {
                padding: 20px;
            }
        }
    </style>
</body>
</html>