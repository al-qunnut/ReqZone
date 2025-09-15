<?php

session_start(); // Start session

// Database connection
$link = mysqli_connect('localhost', 'root', '', 'reqzone');

if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

$name = "Guest";

// Get user name from session
if (isset($_SESSION['name'])) {
    $name = $_SESSION['name'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentName = trim($_POST['project-name']);
    $requestType = trim($_POST['constituency']);
    $description = trim($_POST['about-project']);
    $requestSender = $name; // Using the logged in user name

    // Validate input
    if ($documentName && $requestType && $description) {
        // Use prepared statements to prevent SQL injection
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

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" /> 
    <style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
}

body {
  background: #f4f6fa;
}

.Home {
  display: flex;
  min-height: 100vh;
  background: #f4f6fa;
}

.sidebar_box {
  background: linear-gradient(160deg, #0835C8 80%, #001f4d 100%);
  color: #fff;
  width: 20vw;
  min-width: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-shadow: 2px 0 10px rgba(0,0,0,0.08);
  padding: 0;
  transition: width 0.3s;
  z-index: 200;
}

.logo {
  margin: 30px 0 10px 0;
  width: 160px;
  filter: drop-shadow(0 2px 8px #0003);
}

ul {
  list-style: none;
  padding: 0;
  margin-top: 30px;
  width: 100%;
}
ul li {
  display: flex;
  align-items: center;
  padding: 12px 30px;
  font-size: 1.1rem;
  cursor: pointer;
  border-radius: 8px;
  transition: background 0.2s, color 0.2s;
}
ul li:hover {
  background: #fff;
  color: #0835C8;
}

i {
  margin-right: 18px;
  font-size: 2rem;
  cursor: pointer;
}

#Dashboard_content, #Upload, #Record, #Profile {
  flex: 1;
  padding: 40px 5vw;
  background: #f4f6fa;
  border-radius: 18px;
  margin: 30px 0;
  box-shadow: 0 4px 24px #0001;
}

.dashboard {
  width: 70vw;
}

.header, .Mobile_header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 1.5rem;
  margin: 30px 0 40px 0;
}

.user {
  color: #0835C8;
  font-size: 2.2rem;
  font-weight: 700;
}

.Requests_box {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30px;
  border-bottom: 2px solid #e0e0e0;
  padding: 20px 0 30px 0;
}

.box {
  padding: 24px 18px;
  margin: 0;
  cursor: pointer;
  background: #fff;
  border-radius: 16px;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 12px #0001;
  transition: background 0.2s, color 0.2s, transform 0.2s;
  border: 1px solid #e0e0e0;
}
.box:hover {
  background: #0835C8;
  color: #fff;
  transform: translateY(-4px) scale(1.03);
}

.icon {
  background: #e3eaff;
  color: #0835C8;
  padding: 18px;
  height: 48px;
  width: 48px;
  margin-right: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}

.box div span {
  font-size: 2rem;
  font-weight: bold;
}

.recentUpload {
  padding: 30px 0 0 0;
}

.boxes {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  padding: 10px 0;
}

.box img {
  height: 40px;
  background: #e0e0e0;
  border-radius: 8px;
  margin-right: 10px;
}

.box span {
  display: inline-block;
  background: #0835C8;
  color: #fff;
  font-size: 0.9rem;
  padding: 2px 12px;
  border-radius: 12px;
  margin-bottom: 8px;
}

.recent-upload-section {
  margin: 40px auto 0 auto;
  max-width: 900px;
  padding: 0 10px;
}
.recent-upload-section h2 {
  color: #0835C8;
  font-size: 1.6rem;
  margin-bottom: 22px;
  font-weight: 700;
  letter-spacing: 0.5px;
}
.recent-upload-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 28px;
}
.recent-upload-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 2px 12px #0835c81a;
  display: flex;
  align-items: flex-start;
  padding: 18px 16px;
  gap: 18px;
  transition: box-shadow 0.2s, transform 0.2s;
  border: 1.5px solid #e3eaff;
  min-width: 0;
}
.recent-upload-card:hover {
  box-shadow: 0 4px 24px #0835c822;
  transform: translateY(-2px) scale(1.01);
}
.doc-preview img {
  width: 80px;
  height: 100px;
  object-fit: cover;
  border-radius: 8px;
  background: #e3eaff;
  border: 1px solid #e3eaff;
}
.recent-upload-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.uploader {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 2px;
}
.uploader-pic {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #0835C8;
  background: #f4f6fa;
}
.uploader-name {
  color: #0835C8;
  font-weight: 600;
  font-size: 1.08rem;
}
.doc-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.doc-title {
  font-size: 1.13rem;
  font-weight: 700;
  color: #001f4d;
  margin-bottom: 2px;
}
.doc-type {
  font-size: 0.98rem;
  color: #0835C8;
  font-weight: 500;
  margin-bottom: 2px;
}
.doc-desc {
  font-size: 0.97rem;
  color: #444;
  margin-bottom: 4px;
}
.doc-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 4px;
}
.doc-date {
  font-size: 0.95rem;
  color: #0835C8;
  display: flex;
  align-items: center;
  gap: 4px;
}
.status-btn {
  border: none;
  border-radius: 14px;
  padding: 4px 16px;
  font-size: 0.98rem;
  font-weight: 600;
  cursor: default;
  box-shadow: 0 1px 4px #0835c822;
  transition: background 0.2s, color 0.2s;
}
.status-btn.accepted {
  background: #e3eaff;
  color: #0835C8;
  border: 1.5px solid #0835C8;
}
.status-btn.rejected {
  background: #ffeaea;
  color: #d32f2f;
  border: 1.5px solid #d32f2f;
}
.status-btn.inprogress {
  background: #fffbe6;
  color: #bfa100;
  border: 1.5px solid #bfa100;
}


.upload {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 2px 16px #0001;
  width: 80vw;
  max-width: 900px;
  margin: 30px auto;
  padding: 40px 30px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

form {
  margin: 20px 0 0 0;
  width: 100%;
}

.form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-top: 20px;
}

.document {
  display: flex;
  justify-content: center;
  align-items: center;
  border: 2px dashed #0835C8;
  width: 100%;
  height: 12vh;
  padding: 20px;
  margin: 20px 0;
  background: #f9f9f9;
  border-radius: 12px;
  transition: border 0.2s;
}

.document:hover {
  border: 2px solid #001f4d;
}

.doc {
  font-size: 1.2rem;
  border: none;
  background: none;
  width: 100%;
}

input, select, textarea {
  margin: 5px 0 15px 0;
  width: 95%;
  padding: 12px;
  font-size: 1rem;
  font-weight: 500;
  border: 1px solid #bdbdbd;
  border-radius: 8px;
  background: #f7f7f7;
  transition: border 0.2s;
}
input:focus, select:focus, textarea:focus {
  border: 1.5px solid #0835C8;
  outline: none;
  background: #fff;
}

label {
  font-size: 1.1rem;
  font-weight: 600;
  display: block;
  margin-top: 10px;
  margin-bottom: 5px;
  color: #0835C8;
}

.btn {
  color: #fff;
  padding: 12px 0;
  border-radius: 24px;
  background: linear-gradient(90deg, #0835C8 60%, #001f4d 100%);
  font-size: 1.3rem;
  border: none;
  margin-top: 20px;
  width: 220px;
  cursor: pointer;
  font-weight: 600;
  letter-spacing: 1px;
  box-shadow: 0 2px 8px #0002;
  transition: background 0.2s, font-size 0.2s;
}

.btn:hover {
  text-transform: uppercase;
  font-size: 1.5rem;
  background: linear-gradient(90deg, #001f4d 60%, #0835C8 100%);
}

.btn:active {
  opacity: 0.7;
}

.Mobile {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  z-index: 300;
  background: linear-gradient(160deg, #0835C8 80%, #001f4d 100%);
}

.Mobile_header {
  display: flex;
  align-items: center;
  height: 70px;
  justify-content: space-between;
  padding: 0 16px;
}

.mobile_nav_menu {
  display: none;
  position: absolute;
  top: 70px;
  left: 0;
  width: 100vw;
  background: #fff;
  color: #0835C8;
  box-shadow: 0 2px 16px #001f4d22;
  z-index: 301;
}
.mobile_nav_menu ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.mobile_nav_menu ul li {
  padding: 18px 24px;
  font-size: 1.2rem;
  border-bottom: 1px solid #e0e0e0;
  cursor: pointer;
  display: flex;
  align-items: center;
}
.mobile_nav_menu ul li i {
  margin-right: 18px;
  font-size: 1.5rem;
}
.mobile_nav_menu ul li:hover {
  background: #e3eaff;
  color: #001f4d;
}

.blue-table {
  width: 60vw;
  max-width: 100vw;
  margin: 0 auto 30px auto;
  border-collapse: collapse;
  background: #fff;
  box-shadow: 0 2px 12px #0835c81a;
  border-radius: 12px;
  overflow: hidden;
  font-size: 1rem;
}
.blue-table thead {
  background: #0835C8;
  color: #fff;
}
.blue-table th, .blue-table td {
  padding: 16px 12px;
  text-align: left;
}
.blue-table th {
  font-size: 1.08rem;
  letter-spacing: 0.5px;
  font-weight: 600;
}
.blue-table tbody tr {
  border-bottom: 1px solid #e0e0e0;
  transition: background 0.2s;
}
.blue-table tbody tr:nth-child(even) {
  background: #f4f6fa;
}
.blue-table tbody tr:hover {
  background: #e3eaff;
}
.preview-btn {
  background: #0835C8;
  color: #fff;
  padding: 6px 18px;
  border-radius: 16px;
  text-decoration: none;
  font-size: 0.98rem;
  transition: background 0.2s;
  display: inline-block;
  box-shadow: 0 1px 4px #0835c822;
}
.preview-btn:hover {
  background: #001f4d;
}


<!-- ...existing code... -->
<style>
/* ...existing code... */

/* Profile Section (already present, but improved for media friendliness) */
.profile-container {
  max-width: 420px;
  margin: 100px auto;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 2px 16px #0835c81a;
  padding: 32px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.profile-form {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.profile-image-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 24px;
}
.profile-image {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #0835C8;
  margin-bottom: 10px;
  background: #f4f6fa;
}
.edit-img-btn {
  background: #0835C8;
  color: #fff;
  border: none;
  border-radius: 16px;
  padding: 6px 18px;
  font-size: 1rem;
  cursor: pointer;
  margin-top: 6px;
  transition: background 0.2s;
}
.edit-img-btn:hover {
  background: #001f4d;
}
.profile-fields {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.profile-fields label {
  color: #0835C8;
  font-weight: 600;
  margin-bottom: 2px;
}
.profile-fields input {
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #bdbdbd;
  background: #f7f7f7;
  font-size: 1rem;
  margin-bottom: 8px;
  transition: border 0.2s;
}
.profile-fields input:focus {
  border: 1.5px solid #0835C8;
  background: #fff;
  outline: none;
}
.btn-profile{
  color: #fff;
  padding: 12px 0;
  border-radius: 24px;
  background: linear-gradient(90deg, #0835C8 60%, #001f4d 100%);
  font-size: 1.15rem;
  border: none;
  margin-top: 18px;
  width: 100%;
  cursor: pointer;
  font-weight: 600;
  letter-spacing: 1px;
  box-shadow: 0 2px 8px #0002;
  transition: background 0.2s, font-size 0.2s;
}
.btn-profile:hover {
  background: linear-gradient(90deg, #001f4d 60%, #0835C8 100%);
  font-size: 1.18rem;
}

/* Responsive Design */
@media (max-width: 900px) {
  .profile-container {
    max-width: 98vw;
    padding: 18px 5px;
    margin: 20px auto;
  }
  .profile-image {
    width: 80px;
    height: 80px;
  }
  .profile-fields input, .btn-profile {
    font-size: 1rem;
  }
  .profile-fields label {
    font-size: 1rem;
  }
}
@media (max-width: 500px) {
  .profile-container {
    padding: 10px 2vw;
    margin: 10px auto;
  }
  .profile-image {
    width: 60px;
    height: 60px;
  }
  .profile-fields input, .btn-profile {
    font-size: 0.95rem;
    padding: 8px;
  }
}

/* Responsive Design */

@media (max-width: 1200px) {
  .sidebar_box {
    width: 20vw;
    padding: 18px 0;
  }
  .boxes {
    grid-template-columns: repeat(2, 1fr);
  }
  .form {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 900px) {
  #Dashboard_content, #Upload, #Record, #Profile {
    padding: 24px 2vw 16px 2vw;
  }
  .Requests_box {
    grid-template-columns: 1fr;
    gap: 18px;
  }
  .boxes {
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

    .profile-container {
    max-width: 98vw;
    padding: 18px 5px;
    margin: 20px auto;
  }
  .profile-image {
    width: 80px;
    height: 80px;
  }
  .profile-fields input, .btn-profile {
    font-size: 1rem;
  }
  .profile-fields label {
    font-size: 1rem;
  }

}

/* Hide desktop sidebar and show mobile nav on small screens */
@media (max-width: 700px) {
  .Home {
    flex-direction: column;
  }
  .sidebar_box {
    display: none;
  }
  .Mobile {
    display: block;
  }
  #Dashboard_content, #Upload, #Record, #Profile {
    padding: 10px 2vw;
    margin: 80px 0 10px 0;
  }
  .upload {
    padding: 10px 5px;
    margin: 10px auto;
    width: 98vw;
  }
  .boxes {
    grid-template-columns: 1fr;
  }
  .form {
    grid-template-columns: 1fr;
  }
  .dashboard {
    width: 95vw;
  }
  .header {
    margin: 10px 0 20px 0;
  }

  .recent-upload-list {
    grid-template-columns: 1fr;
    gap: 18px;
  }
  .recent-upload-card {
    flex-direction: column;
    align-items: stretch;
    padding: 14px 8px;
    gap: 10px;
  }
  .doc-preview img {
    width: 100%;
    max-width: 100px;
    height: 70px;
    margin: 0 auto 8px auto;
    display: block;
  }
  .uploader {
    gap: 8px;
  }
  .uploader-pic {
    width: 30px;
    height: 30px;
  }
  input, select, textarea {
    width: 100%;
  }
  .doc {
    width: 100%;
  }
  .blue-table, .blue-table thead, .blue-table tbody, .blue-table th, .blue-table td, .blue-table tr {
    display: block;
    width: 100%;
  }
  .blue-table thead {
    display: none;
  }
  .blue-table tr {
    margin-bottom: 18px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 8px #0835c81a;
    padding: 10px 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
  }
  .blue-table td {
    padding: 12px 16px;
    text-align: left;
    border: none;
    position: relative;
    font-size: 1rem;
    background: none;
    border-bottom: none;
    box-shadow: none;
    width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
  }
  .blue-table td:before {
    content: attr(data-label);
    font-weight: 700;
    color: #0835C8;
    display: block;
    margin-bottom: 4px;
    font-size: 0.98rem;
    letter-spacing: 0.2px;
  }
  
  .blue-table td[data-label="Description"],
  .blue-table td[data-label="Preview"] {
    grid-column: 1 / -1;
  }
  .preview-btn {
    width: 100%;
    text-align: center;
    margin-top: 8px;
  }
  .profile-container {
        max-width: 98vw;
        padding: 18px 5px;
      }
      .profile-image {
        width: 80px;
        height: 80px;
      }
}
    </style>
</head>
<body>
    <div class="Home">
        <!-- Desktop Sidebar -->
        <div class="sidebar_box">
            <div id="sidebar">
                <img src="../images/ReqZone.png" alt="logo" width="200px" class="logo"/>
                <ul>
                  <li onclick="displayDashboard()"><i class="fa-solid fa-house-user"></i> Dashboard</li>
                  <li onclick="displayUpload()"><i class="fa-solid fa-file-arrow-up"></i>Upload</li>
                  <li onclick="displayRecords()"><i class="fa-solid fa-list-check"></i>Record</li>
                  <li onclick="displayProfile()"><i class="fa-solid fa-user"></i>Profile</li>
                </ul>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div class="Mobile">
            <div class="Mobile_header">
                <img src="../images/ReqZone2.png" alt="logo" width="150px" class="logo"/>
                <i class="fa-solid fa-bars" id="mobileMenuBtn"></i>
            </div>
            <div class="mobile_nav_menu" id="mobileNavMenu">
                <ul>
                  <li onclick="displayDashboard();toggleMobileMenu();"><i class="fa-solid fa-house-user"></i> Dashboard</li>
                  <li onclick="displayUpload();toggleMobileMenu();"><i class="fa-solid fa-file-arrow-up"></i>Upload</li>
                  <li onclick="displayRecords();toggleMobileMenu();"><i class="fa-solid fa-list-check"></i>Record</li>
                  <li onclick="displayProfile();toggleMobileMenu();"><i class="fa-solid fa-user"></i>Profile</li>
                </ul>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div id="Dashboard_content">
          <div class="dashboard">
            <div class="header">
                <h1>Hello <span class="user"><?php echo htmlspecialchars($name); ?></span></h1>
                <i class="fa-solid fa-bell"></i>
            </div>
            <div class="">
               <div class="Requests_box">
                <div class="box">
                  <div class="icon">
                    <i class="fa-solid fa-briefcase"></i>
                  </div>
                 <div class="">
                   <h2>Job Requests Made</h2>
                   <span>0</span>
                 </div>
                </div>
                <div class="box">
                  <div class="icon">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                  </div>
                  <div class="">
                    <h2>Payment Requests Made</h2>
                    <span>0</span>
                  </div>
                </div>
                <div class="box">
                  <div class="icon">
                    <i class="fa-solid fa-check-double"></i>
                  </div>
                  <div class="">
                    <h2>All Requests Made</h2>
                    <span>0</span>
                  </div>
                </div>
               </div>

               <div class="recent-upload-section">
                <h2>Recently Uploaded</h2>
                <div class="recent-upload-list">
                  <!-- Example Card 1 -->
                  <div class="recent-upload-card accepted">
                    <div class="doc-preview">
                      <img src="../images/Letter.png" alt="Document Preview" />
                    </div>
                    <div class="recent-upload-info">
                      <div class="uploader">
                        <img src="../images/default-user.png" alt="Uploader" class="uploader-pic" />
                        <span class="uploader-name">John Doe</span>
                      </div>
                      <div class="doc-details">
                        <div class="doc-title">Invoice_123.pdf</div>
                        <div class="doc-type">Payment</div>
                        <div class="doc-desc">Payment for August</div>
                        <div class="doc-meta">
                          <span class="doc-date"><i class="fa-regular fa-calendar"></i> 2025-09-06</span>
                          <button class="status-btn accepted">Accepted</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Example Card 2 -->
                  <div class="recent-upload-card rejected">
                    <div class="doc-preview">
                      <img src="../images/Letter.png" alt="Document Preview" />
                    </div>
                    <div class="recent-upload-info">
                      <div class="uploader">
                        <img src="../images/default-user.png" alt="Uploader" class="uploader-pic" />
                        <span class="uploader-name">Jane Smith</span>
                      </div>
                      <div class="doc-details">
                        <div class="doc-title">Job_Offer.docx</div>
                        <div class="doc-type">Job</div>
                        <div class="doc-desc">Offer letter for new hire</div>
                        <div class="doc-meta">
                          <span class="doc-date"><i class="fa-regular fa-calendar"></i> 2025-09-05</span>
                          <button class="status-btn rejected">Rejected</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Example Card 3 -->
                  <div class="recent-upload-card inprogress">
                    <div class="doc-preview">
                      <img src="../images/Letter.png" alt="Document Preview" />
                    </div>
                    <div class="recent-upload-info">
                      <div class="uploader">
                        <img src="../images/default-user.png" alt="Uploader" class="uploader-pic" />
                        <span class="uploader-name">Alex Green</span>
                      </div>
                      <div class="doc-details">
                        <div class="doc-title">Report2025.pdf</div>
                        <div class="doc-type">Job</div>
                        <div class="doc-desc">Monthly performance report</div>
                        <div class="doc-meta">
                          <span class="doc-date"><i class="fa-regular fa-calendar"></i> 2025-09-04</span>
                          <button class="status-btn inprogress">In Progress</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Add more cards as needed -->
                </div>
              </div>
              
              
            </div>
          </div>
        </div>
         
        <!-- Upload content -->
        <div class="upload" id='Upload'>
            <h1>Upload your document</h1>
              <form action="" method="post" enctype="multipart/form-data">
              <div class="document">
                <input class='doc' type='file' name="images[]" multiple required placeholder='Drag and drop file here or choose file' />
              </div>
             <div class="form">
              <div class="">
                <label>Document name</label>
                <input type='text'  name="project-name" placeholder="Please enter the document name..." required />
              </div>
              <div class="">
                <label>Request Type</label>
                <select name="constituency" required>
                  <option value="">Pick the request type</option>
                  <option value="Payment">Payment Request</option>
                  <option value="Job">Job Request</option>
                </select>
              </div>
              <div class="">
                <label>Description</label>
                <textarea name="about-project" rows='10' placeholder="Please enter the document description..." required></textarea>
              </div>
              </div>
              <button type="submit" class='btn'>Submit</button>
             </form>
          </div>

        <div id="Record">
            <h1>Records</h1>
            <p>Your records will show here</p>

            <table class="blue-table">
             <thead>
               <tr>
                 <th>Date</th>
                 <th>Time</th>
                 <th>Name</th>
                 <th>Request Type</th>
                 <th>Document Name</th>
                 <th>Description</th>
                 <th>Preview</th>
               </tr>
             </thead>
             <tbody>
               <tr>
                 <td data-label="Date">2025-09-06</td>
                 <td data-label="Time">10:30 AM</td>
                 <td data-label="Name">John Doe</td>
                 <td data-label="Request Type">Payment</td>
                 <td data-label="Document Name">Invoice_123.pdf</td>
                 <td data-label="Description">Payment for August</td>
                 <td data-label="Preview"><a href="#" class="preview-btn">View</a></td>
               </tr>
               <tr>
                 <td data-label="Date">2025-09-05</td>
                 <td data-label="Time">02:15 PM</td>
                 <td data-label="Name">Jane Smith</td>
                 <td data-label="Request Type">Job</td>
                 <td data-label="Document Name">Job_Offer.docx</td>
                 <td data-label="Description">Offer letter for new hire</td>
                 <td data-label="Preview"><a href="#" class="preview-btn">View</a></td>
               </tr>
             </tbody>
           </table>
                  
            
        </div>
        
        <div id="Profile">
            <h1>Profile</h1>
            <p>Your profile information will show here</p>
            
              <div class="profile-container">
                <form class="profile-form" id="profileForm">
                  <div class="profile-image-section">
                    <img id="profileImage" src="../images/default-user.png" alt="Profile Image" class="profile-image"/>
                    <input type="file" id="profilePicInput" accept="image/*" style="display:none;">
                    <button type="button" class="edit-img-btn" onclick="document.getElementById('profilePicInput').click();">
                      Change Photo
                    </button>
                  </div>
                  <div class="profile-fields">
                    <label for="profileName">Name</label>
                    <input type="text" id="profileName" value="John Doe" required>
                    <label for="profileEmail">Email</label>
                    <input type="email" id="profileEmail" value="user@email.com" required>
                    <label for="profilePassword">Password</label>
                    <input type="password" id="profilePassword" value="password" required>
                    <label for="profilePhone">Phone Number</label>
                    <input type="tel" id="profilePhone" value="+1234567890" required>
                    <label for="profilePosition">Position</label>
                    <input type="text" id="profilePosition" value="Staff" required>
                    <button type="submit" class="btn-profile">Save Changes</button>
                  </div>
                </form>
              </div>
        </div>
    </div>

    <script>
      let currentSection = 'Dashboard_content';

      function showSection(sectionId) {
        // Hide all sections
        document.getElementById('Dashboard_content').style.display = 'none';
        document.getElementById('Upload').style.display = 'none';
        document.getElementById('Record').style.display = 'none';
        document.getElementById('Profile').style.display = 'none';
        
        // Show selected section
        document.getElementById(sectionId).style.display = 'block';
      }
    
      function displayDashboard() {
        showSection('Dashboard_content');
      }
    
      function displayUpload() {
        showSection('Upload');
      }
    
      function displayRecords() {
        showSection('Record');
      }
    
      function displayProfile() {
        showSection('Profile');
      }

      // Mobile nav menu toggle
      function toggleMobileMenu() {
        const menu = document.getElementById('mobileNavMenu');
        if (menu.style.display === 'block') {
          menu.style.display = 'none';
        } else {
          menu.style.display = 'block';
        }
      }

      // Open mobile menu on hamburger click
      document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('mobileMenuBtn');
        if (btn) {
          btn.addEventListener('click', toggleMobileMenu);
        }
        // Hide menu when clicking outside
        document.addEventListener('click', function(e) {
          const menu = document.getElementById('mobileNavMenu');
          const isMenu = menu.contains(e.target);
          const isBtn = btn.contains(e.target);
          if (!isMenu && !isBtn) {
            menu.style.display = 'none';
          }
        });
      });

      document.getElementById('profilePicInput').addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(ev) {
          document.getElementById('profileImage').src = ev.target.result;
        }
        reader.readAsDataURL(e.target.files[0]);
      }
    });
    // Demo form submit
    document.getElementById('profileForm').addEventListener('submit', function(e) {
      e.preventDefault();
      alert('Profile changes saved (demo only)');
    });
    </script>
</body>
</html>