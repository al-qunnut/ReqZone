<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'reqzone');

// Connect to MySQL
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Initialize variables and error messages
$name = $email = $userCategory = $userGroup = $password = $confirm_password = "";
$name_err = $email_err = $userCategory_err = $userGroup_err = $password_err = $confirm_password_err = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter your name.";
    } elseif (!preg_match('/^[a-zA-Z ]+$/', trim($_POST["name"]))) {
        $name_err = "Name can only contain letters and spaces.";
    } else {
        $name = trim($_POST["name"]);
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter a valid email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $sql = "SELECT name FROM requser WHERE email = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = "This email is already registered.";
                } else {
                    $email = $param_email;
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate user category
    if (empty(trim($_POST["userCategory"]))) {
        $userCategory_err = "Please select a company.";
    } else {
        $userCategory = trim($_POST["userCategory"]);
    }

    // Validate user group - only required for NCC users
    if ($userCategory == "NCC") {
        if (empty(trim($_POST["userGroup"]))) {
            $userGroup_err = "Please select a department.";
        } else {
            $userGroup = trim($_POST["userGroup"]);
        }
    } else {
        $userGroup = ""; // Facility users don't need a userGroup
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm your password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if ($password !== $confirm_password) {
            $confirm_password_err = "Passwords do not match.";
        }
    }

    // If no errors, insert into database
    if (empty($name_err) && empty($email_err) && empty($userCategory_err) && empty($userGroup_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "INSERT INTO requser (name, email, userCategory, userGroup, password) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $userCategory, $userGroup, $hashed_password);
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Registration successful! You can now login with your credentials.";
                // Clear form data after successful registration
                $name = $email = $userCategory = $userGroup = "";
            } else {
                echo "Something went wrong. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($link);
}

// Define role hierarchy information for display
$role_info = [
    'EVC' => ['name' => 'CEO/Executive Vice Chairman', 'level' => 7, 'description' => 'Highest authority - Full system access'],
    'DCSH' => ['name' => 'Director - Corporate Services HO', 'level' => 6, 'description' => 'Head Office Director - Full approval authority'],
    'ZCL' => ['name' => 'Zonal Controller', 'level' => 5, 'description' => 'Zone Controller - Regional oversight'],
    'DCSL' => ['name' => 'Director - Corporate Services LGZO', 'level' => 4, 'description' => 'Local Director - Departmental authority'],
    'UHCSL' => ['name' => 'Unit Head - Corporate Services LGZO', 'level' => 3, 'description' => 'Unit Head - Limited approval rights'],
    'CSL' => ['name' => 'Corporate Services Staff', 'level' => 2, 'description' => 'Staff Member - Submit requests only'],
    'PH' => ['name' => 'Procurement Head', 'level' => 4, 'description' => 'Procurement Department Head'],
    'FH' => ['name' => 'Finance Head', 'level' => 4, 'description' => 'Finance Department Head']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - ReqZone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Inter', Arial, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            background: rgba(255, 255, 255, 0.1);
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

        .signup-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 900px;
            padding: 50px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .signup-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 2;
        }

        .logo {
            width: 250px;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .signup-header h1 {
            color: #667eea;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .signup-header p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .signup-header p a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-header p a:hover {
            color: #764ba2;
        }

        .form-container {
            position: relative;
            z-index: 2;
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
            color: #667eea;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 15px 20px;
            border-radius: 12px;
            border: 2px solid rgba(102, 126, 234, 0.2);
            background: #f8f9ff;
            color: #333;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-group input::placeholder {
            color: #999;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-group.is-invalid input,
        .form-group.is-invalid select {
            border-color: #ff6b6b;
            background: rgba(255, 107, 107, 0.05);
        }

        .invalid-feedback {
            color: #ff6b6b;
            font-size: 0.9rem;
            margin-top: 8px;
            display: block;
            font-weight: 500;
        }

        .role-info {
            background: rgba(102, 126, 234, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            border-left: 4px solid #667eea;
            display: none;
        }

        .role-info h4 {
            color: #667eea;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .role-info p {
            color: #666;
            font-size: 0.9rem;
            margin: 4px 0;
        }

        .role-info .level-badge {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 8px;
        }

        .department-group {
            display: none;
            animation: slideDown 0.3s ease;
        }

        .department-group.show {
            display: block;
        }

        @keyframes slideDown {
            0% { opacity: 0; transform: translateY(-20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 1.3rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .alert {
            padding: 18px 25px;
            margin: 25px 0;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInDown 0.5s ease;
        }

        .alert.success {
            background: rgba(68, 189, 135, 0.1);
            border: 2px solid #44bd87;
            color: #44bd87;
        }

        .alert.error {
            background: rgba(255, 107, 107, 0.1);
            border: 2px solid #ff6b6b;
            color: #ff6b6b;
        }

        @keyframes slideInDown {
            0% { opacity: 0; transform: translateY(-30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .hierarchy-info {
            background: rgba(102, 126, 234, 0.05);
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        .hierarchy-info h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .hierarchy-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .hierarchy-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .hierarchy-item:hover {
            transform: translateY(-2px);
        }

        .hierarchy-item .role-name {
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }

        .hierarchy-item .role-desc {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 8px;
        }

        .hierarchy-item .level-indicator {
            background: #667eea;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .signup-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .logo {
                width: 200px;
            }

            .signup-header h1 {
                font-size: 2rem;
            }

            .hierarchy-list {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .signup-container {
                padding: 25px 15px;
            }

            .logo {
                width: 180px;
            }

            .signup-header h1 {
                font-size: 1.8rem;
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

    <div class="signup-container">
        <div class="logo-section">
            <svg class="logo" viewBox="0 0 300 80" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#667eea"/>
                        <stop offset="100%" style="stop-color:#764ba2"/>
                    </linearGradient>
                </defs>
                <text x="150" y="45" font-family="Arial, sans-serif" font-size="36" font-weight="bold" text-anchor="middle" fill="url(#logoGradient)">ReqZone</text>
                <text x="150" y="65" font-family="Arial, sans-serif" font-size="12" text-anchor="middle" fill="#666">Facility Management System</text>
            </svg>
        </div>

        <div class="signup-header">
            <h1>Create Account</h1>
            <p>Already have an account? <a href="../index.php">Login here</a></p>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-grid">
                    <div class="form-group <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>">
                        <label for="name">Full Name *</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               placeholder="Enter your full name..." 
                               value="<?php echo htmlspecialchars($name); ?>" 
                               required>
                        <?php if (!empty($name_err)): ?>
                            <span class="invalid-feedback"><?php echo $name_err; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>">
                        <label for="email">Email Address *</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               placeholder="Enter your email address..." 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               required>
                        <?php if (!empty($email_err)): ?>
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group <?php echo (!empty($userCategory_err)) ? 'is-invalid' : ''; ?>">
                        <label for="userCategory">Organization *</label>
                        <select id="userCategory" name="userCategory" onchange="toggleDepartmentSelection()" required>
                            <option value="">Select your organization...</option>
                            <option value="NCC" <?php echo ($userCategory == 'NCC') ? 'selected' : ''; ?>>Nigerian Communications Commission (NCC)</option>
                            <option value="Facility" <?php echo ($userCategory == 'Facility') ? 'selected' : ''; ?>>Diamond Heirs Facility</option>
                        </select>
                        <?php if (!empty($userCategory_err)): ?>
                            <span class="invalid-feedback"><?php echo $userCategory_err; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group department-group <?php echo (!empty($userGroup_err)) ? 'is-invalid' : ''; ?>" id="departmentGroup">
                        <label for="userGroup">Department/Role *</label>
                        <select id="userGroup" name="userGroup" onchange="showRoleInfo()">
                            <option value="">Select your department...</option>
                            <option value="CSL" <?php echo ($userGroup == 'CSL') ? 'selected' : ''; ?>>Corporate Services Staff</option>
                            <option value="UHCSL" <?php echo ($userGroup == 'UHCSL') ? 'selected' : ''; ?>>Unit Head - Corporate Services LGZO</option>
                            <option value="DCSL" <?php echo ($userGroup == 'DCSL') ? 'selected' : ''; ?>>Director - Corporate Services LGZO</option>
                            <option value="ZCL" <?php echo ($userGroup == 'ZCL') ? 'selected' : ''; ?>>Zonal Controller LGZO</option>
                            <option value="DCSH" <?php echo ($userGroup == 'DCSH') ? 'selected' : ''; ?>>Director - Corporate Services HO</option>
                            <option value="EVC" <?php echo ($userGroup == 'EVC') ? 'selected' : ''; ?>>CEO/Executive Vice Chairman</option>
                            <option value="PH" <?php echo ($userGroup == 'PH') ? 'selected' : ''; ?>>Procurement Head</option>
                            <option value="FH" <?php echo ($userGroup == 'FH') ? 'selected' : ''; ?>>Finance Head</option>
                        </select>
                        <?php if (!empty($userGroup_err)): ?>
                            <span class="invalid-feedback"><?php echo $userGroup_err; ?></span>
                        <?php endif; ?>
                        <div class="role-info" id="roleInfo">
                            <!-- Role information will be displayed here -->
                        </div>
                    </div>

                    <div class="form-group <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <label for="password">Password *</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password (min. 6 characters)" 
                               minlength="6" 
                               required>
                        <?php if (!empty($password_err)): ?>
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Confirm your password" 
                               minlength="6" 
                               required>
                        <?php if (!empty($confirm_password_err)): ?>
                            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <!-- NCC Role Hierarchy Information -->
            <div class="hierarchy-info" id="hierarchyInfo" style="display: none;">
                <h3><i class="fas fa-sitemap"></i> NCC Role Hierarchy & Permissions</h3>
                <div class="hierarchy-list">
                    <?php foreach ($role_info as $code => $info): ?>
                        <div class="hierarchy-item">
                            <div class="role-name"><?php echo htmlspecialchars($info['name']); ?></div>
                            <div class="role-desc"><?php echo htmlspecialchars($info['description']); ?></div>
                            <span class="level-indicator">Level <?php echo $info['level']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Role information data
        const roleInfo = <?php echo json_encode($role_info); ?>;

        // Toggle department selection based on organization
        function toggleDepartmentSelection() {
            const userCategory = document.getElementById('userCategory').value;
            const departmentGroup = document.getElementById('departmentGroup');
            const hierarchyInfo = document.getElementById('hierarchyInfo');
            const userGroupSelect = document.getElementById('userGroup');

            if (userCategory === 'NCC') {
                departmentGroup.classList.add('show');
                departmentGroup.style.display = 'block';
                hierarchyInfo.style.display = 'block';
                userGroupSelect.required = true;
            } else {
                departmentGroup.classList.remove('show');
                departmentGroup.style.display = 'none';
                hierarchyInfo.style.display = 'none';
                userGroupSelect.required = false;
                userGroupSelect.value = '';
                document.getElementById('roleInfo').style.display = 'none';
            }
        }

        // Show role information based on selected department
        function showRoleInfo() {
            const userGroup = document.getElementById('userGroup').value;
            const roleInfoDiv = document.getElementById('roleInfo');

            if (userGroup && roleInfo[userGroup]) {
                const info = roleInfo[userGroup];
                roleInfoDiv.innerHTML = `
                    <h4><i class="fas fa-info-circle"></i> ${info.name}</h4>
                    <p><strong>Authority Level:</strong> ${info.level}/7</p>
                    <p><strong>Description:</strong> ${info.description}</p>
                    <span class="level-badge">Access Level ${info.level}</span>
                `;
                roleInfoDiv.style.display = 'block';
            } else {
                roleInfoDiv.style.display = 'none';
            }
        }

        // Form validation and submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submitBtn');

            // Check password match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please check and try again.');
                return;
            }

            // Check password strength
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return;
            }

            // Disable submit button and show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            submitBtn.disabled = true;
        });

        // Initialize form based on PHP values
        document.addEventListener('DOMContentLoaded', function() {
            toggleDepartmentSelection();
            showRoleInfo();

            // Add real-time password confirmation validation
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            function validatePasswordMatch() {
                if (confirmPassword.value && password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                    confirmPassword.parentElement.classList.add('is-invalid');
                } else {
                    confirmPassword.setCustomValidity('');
                    confirmPassword.parentElement.classList.remove('is-invalid');
                }
            }

            password.addEventListener('input', validatePasswordMatch);
            confirmPassword.addEventListener('input', validatePasswordMatch);
        });

        // Add smooth animations for form interactions
        document.querySelectorAll('input, select').forEach(element => {
            element.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            element.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>