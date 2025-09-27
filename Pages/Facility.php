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
    'name' => $_SESSION['name'] ?? '',
    'email' => $_SESSION['email'] ?? '',
    'userCategory' => $_SESSION['userCategory'] ?? 'Facility',
    'userGroup' => $_SESSION['userGroup'] ?? '',
    'role' => $_SESSION['role'] ?? 'Facility Manager'
];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['profile_name']);
    $new_email = trim($_POST['profile_email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $profile_update_success = false;
    $profile_error_message = '';
    
    // Validate inputs
    if (empty($new_name) || empty($new_email)) {
        $profile_error_message = "Name and email are required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $profile_error_message = "Please enter a valid email address.";
    } else {
        // Check if email is being changed and if it conflicts
        $email_conflict = false;
        
        // Only check email uniqueness if the email is actually being changed
        if (strtolower(trim($new_email)) !== strtolower(trim($user_data['email']))) {
            $stmt = mysqli_prepare($link, "SELECT id FROM requser WHERE email = ? AND id != ?");
            mysqli_stmt_bind_param($stmt, "si", $new_email, intval($user_data['id']));
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $email_conflict = true;
                $profile_error_message = "Email address is already in use by another user.";
            }
            mysqli_stmt_close($stmt);
        }
        
        // Continue only if no email conflict
        if (!$email_conflict) {
            // Check if password change is requested
            $password_change_requested = !empty($new_password) || !empty($confirm_password) || !empty($current_password);
            
            if ($password_change_requested) {
                // Validate password change requirements
                if (empty($current_password)) {
                    $profile_error_message = "Current password is required to change password.";
                } elseif (empty($new_password)) {
                    $profile_error_message = "New password is required.";
                } elseif ($new_password !== $confirm_password) {
                    $profile_error_message = "New passwords do not match.";
                } elseif (strlen($new_password) < 6) {
                    $profile_error_message = "New password must be at least 6 characters long.";
                } else {
                    // Verify current password
                    $stmt = mysqli_prepare($link, "SELECT password FROM requser WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "i", intval($user_data['id']));
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $current_user = mysqli_fetch_assoc($result);
                    
                    if (!$current_user) {
                        $profile_error_message = "User record not found.";
                    } elseif (!password_verify($current_password, $current_user['password'])) {
                        $profile_error_message = "Current password is incorrect.";
                    } else {
                        // Update with new password
                        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt_update = mysqli_prepare($link, "UPDATE requser SET name = ?, email = ?, password = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmt_update, "sssi", $new_name, $new_email, $hashed_new_password, intval($user_data['id']));
                        
                        if (mysqli_stmt_execute($stmt_update)) {
                            $profile_update_success = true;
                            $_SESSION['name'] = $new_name;
                            $_SESSION['email'] = $new_email;
                            $user_data['name'] = $new_name;
                            $user_data['email'] = $new_email;
                        } else {
                            $profile_error_message = "Error updating profile: " . mysqli_error($link);
                        }
                        mysqli_stmt_close($stmt_update);
                    }
                    mysqli_stmt_close($stmt);
                }
            } else {
                // Update without password change (name and/or email only)
                $stmt = mysqli_prepare($link, "UPDATE requser SET name = ?, email = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, $new_name, $new_email, intval($user_data['id']));
                
                if (mysqli_stmt_execute($stmt)) {
                    $affected_rows = mysqli_stmt_affected_rows($stmt);
                    if ($affected_rows > 0) {
                        $profile_update_success = true;
                        $_SESSION['name'] = $new_name;
                        $_SESSION['email'] = $new_email;
                        $user_data['name'] = $new_name;
                        $user_data['email'] = $new_email;
                    } else {
                        $profile_error_message = "No changes were made. The information may be the same as your current profile.";
                    }
                } else {
                    $profile_error_message = "Error updating profile: " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Handle form submission for requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_profile'])) {
    $documentName = trim($_POST['project-name']);
    $requestType = trim($_POST['constituency']);
    $description = trim($_POST['about-project']);
    $requestSender = $user_data['name'];

    // Validate that only Payment or Job requests are allowed for Facility users
    $allowedTypes = ['Payment', 'Job'];
    
    if (!in_array($requestType, $allowedTypes)) {
        $error_message = "Facility users can only submit Payment or Job requests.";
    } elseif ($documentName && $requestType && $description) {
        
        // Handle file upload
        $uploadedFiles = [];
        $uploadDir = '../uploads/facility_documents/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
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
        
        // Only proceed if no file upload errors
        if (!isset($error_message)) {
            // Convert uploaded files to JSON for storage
            $filesJson = !empty($uploadedFiles) ? json_encode($uploadedFiles) : null;
            
            // Insert into database
            $stmt = mysqli_prepare(
                $link,
                "INSERT INTO fileInformation (`DocumentName`, `Request Type`, `Description`, `RequestSender`, `SenderEmail`, `UploadedFiles`, `UploadDate`) VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            
            $senderName = (string) $user_data['name'];
            $senderEmail = (string) $user_data['email'];
            
            mysqli_stmt_bind_param($stmt, "ssssss", $documentName, $requestType, $description, $senderName, $senderEmail, $filesJson);

            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Request submitted successfully!";
                if (!empty($uploadedFiles)) {
                    $success_message .= " " . count($uploadedFiles) . " file(s) uploaded.";
                }
            } else {
                $error_message = "Error: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}

// Fetch user's requests and statistics (only Payment and Job types)
$user_requests = [];
$user_stats = ['job' => 0, 'payment' => 0, 'total' => 0];

if (!empty($user_data['email'])) {
    // Get user's requests (only Payment and Job types)
    $stmt = mysqli_prepare($link, "SELECT * FROM fileInformation WHERE SenderEmail = ? AND (`Request Type` = 'Payment' OR `Request Type` = 'Job') ORDER BY UploadDate DESC");
    mysqli_stmt_bind_param($stmt, "s", $user_data['email']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Decode uploaded files JSON
        if (!empty($row['UploadedFiles'])) {
            $row['files'] = json_decode($row['UploadedFiles'], true);
        } else {
            $row['files'] = [];
        }
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

// Helper function for formatting file sizes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
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
    <link rel="stylesheet" href="../Styles/facility.css">
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
                                    <?php 
                                    $previewImage = "../images/Letter.png"; // Default image
                                    $hasImage = false;
                                    
                                    // Check if there are uploaded files
                                    if (!empty($request['files'])) {
                                        foreach($request['files'] as $file) {
                                            // If it's an image file, use it as preview
                                            if (in_array(strtolower($file['file_type']), ['jpg', 'jpeg', 'png'])) {
                                                $previewImage = "../uploads/facility_documents/" . $file['stored_name'];
                                                $hasImage = true;
                                                break;
                                            }
                                        }
                                        
                                        // If no image found, show file type icon based on first file
                                        if (!$hasImage && !empty($request['files'])) {
                                            $firstFile = $request['files'][0];
                                            $fileType = strtolower($firstFile['file_type']);
                                            
                                            // Use CSS background for file type icons instead of images
                                            echo '<div class="file-type-icon file-type-' . $fileType . '">';
                                            switch($fileType) {
                                                case 'pdf':
                                                    echo '<i class="fas fa-file-pdf"></i><span>PDF</span>';
                                                    break;
                                                case 'doc':
                                                case 'docx':
                                                    echo '<i class="fas fa-file-word"></i><span>DOC</span>';
                                                    break;
                                                default:
                                                    echo '<i class="fas fa-file"></i><span>' . strtoupper($fileType) . '</span>';
                                            }
                                            echo '</div>';
                                        } else if ($hasImage) {
                                            echo '<img src="' . htmlspecialchars($previewImage) . '" alt="Document Preview" />';
                                        } else {
                                            echo '<img src="../images/Letter.png" alt="Document Preview" />';
                                        }
                                    } 
                                    
                                    if (!$hasImage && empty($request['files'])) {
                                        echo '<img src="../images/Letter.png" alt="Document Preview" />';
                                    }
                                    ?>
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
                                        <?php if (!empty($request['files'])): ?>
                                            <span class="doc-files">
                                                <i class="fas fa-paperclip"></i> <?php echo count($request['files']); ?> file(s)
                                            </span>
                                        <?php endif; ?>
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
                                <small>Support for multiple files (PDF, DOC, DOCX, JPG, PNG) - Max 5MB per file</small>
                            </div>
                            <input class="file-input" type="file" id="fileInput" name="images[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display: none;" />
                            <div class="uploaded-files-preview" id="filesPreview"></div>
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
                                    <th>Files</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($user_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($request['UploadDate']))); ?></td>
                                    <td><?php echo htmlspecialchars($request['DocumentName']); ?></td>
                                    <td>
                                        <span class="status-btn <?php echo strtolower($request['Request Type']); ?>">
                                            <?php echo htmlspecialchars($request['Request Type']); ?>
                                        </span>
                                    </td>
                                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars(substr($request['Description'], 0, 60)) . (strlen($request['Description']) > 60 ? '...' : ''); ?>
                                    </td>
                                    <td class="files-column">
                                        <?php if (!empty($request['files'])): ?>
                                            <div class="file-list">
                                                <?php foreach($request['files'] as $index => $file): ?>
                                                    <div class="file-item">
                                                        <i class="fas fa-file file-icon"></i>
                                                        <div class="file-info">
                                                            <div class="file-name"><?php echo htmlspecialchars($file['original_name']); ?></div>
                                                            <div class="file-size"><?php echo formatBytes($file['file_size']); ?></div>
                                                        </div>
                                                        <div class="file-actions">
                                                            <button class="file-btn" onclick="viewDocument('<?php echo htmlspecialchars($file['stored_name']); ?>', '<?php echo htmlspecialchars($file['original_name']); ?>', '<?php echo htmlspecialchars($file['file_type']); ?>')">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <a href="download.php?file=<?php echo urlencode($file['stored_name']); ?>&name=<?php echo urlencode($file['original_name']); ?>" class="file-btn download">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #6c757d; font-style: italic;">No files</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="status-btn pending">Pending</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Profile Section -->
            <div class="content-section" id="profile">
                <div class="profile-container">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($user_data['name'], 0, 1)); ?>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($user_data['name']); ?></h2>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_data['email']); ?></p>
                            <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($user_data['role']); ?></p>
                            <p><i class="fas fa-building"></i> Diamond Heirs Facility</p>
                        </div>
                    </div>

                    <!-- Profile Edit Form -->
                    <div class="profile-form-container">
                        <div class="profile-form-header">
                            <h3><i class="fas fa-edit"></i> Edit Profile Information</h3>
                            <p>Update your personal information and account settings</p>
                        </div>

                        <?php if (isset($profile_update_success) && $profile_update_success): ?>
                            <div class="profile-alert success">
                                <i class="fas fa-check-circle"></i> Profile updated successfully!
                            </div>
                        <?php endif; ?>

                        <?php if (isset($profile_error_message) && !empty($profile_error_message)): ?>
                            <div class="profile-alert error">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $profile_error_message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <!-- Basic Information -->
                            <div class="profile-form-grid">
                                <div class="profile-form-group">
                                    <label for="profile_name"><i class="fas fa-user"></i> Full Name *</label>
                                    <input type="text" id="profile_name" name="profile_name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                                </div>
                                
                                <div class="profile-form-group">
                                    <label for="profile_email"><i class="fas fa-envelope"></i> Email Address *</label>
                                    <input type="email" id="profile_email" name="profile_email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                </div>
                            </div>

                            <!-- Password Change Section -->
                            <div class="password-section">
                                <h4><i class="fas fa-lock"></i> Change Password</h4>
                                <p style="color: #666; margin-bottom: 20px; font-size: 0.9rem;">Leave password fields empty if you don't want to change your password</p>
                                
                                <div class="profile-form-grid">
                                    <div class="profile-form-group full-width">
                                        <label for="current_password">Current Password</label>
                                        <div class="password-input-container">
                                            <input type="password" id="current_password" name="current_password" placeholder="Enter your current password">
                                            <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="profile-form-group">
                                        <label for="new_password">New Password</label>
                                        <div class="password-input-container">
                                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password (min. 6 characters)">
                                            <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="profile-form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <div class="password-input-container">
                                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password">
                                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="profile-update-btn">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Viewer Modal -->
    <div id="documentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Document Viewer</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="documentViewer"></div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentUser = <?php echo json_encode($user_data); ?>;
        let userRequests = <?php echo json_encode($user_requests); ?>;

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
            
            const filePath = '../uploads/facility_documents/' + storedName;
            
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

        // Close modal
        function closeModal() {
            document.getElementById('documentModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('documentModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

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
                window.location.href = '../index.php';
            }
        }

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.parentNode.querySelector('.toggle-password');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Form submission handling for requests
        document.querySelector('form[enctype="multipart/form-data"]').addEventListener('submit', function(e) {
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
            }, 3000);
        });

        // Profile form validation
        document.querySelector('form[method="POST"]:not([enctype])').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;
            
            // If user wants to change password
            if (newPassword || confirmPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Current password is required to change password.');
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match.');
                    return false;
                }
                
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('New password must be at least 6 characters long.');
                    return false;
                }
            }
            
            const submitBtn = this.querySelector('.profile-update-btn');
            const originalContent = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;
            
            // Re-enable button after form processes
            setTimeout(() => {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }, 2000);
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.profile-alert, .alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>

