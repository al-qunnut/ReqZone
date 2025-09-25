<?php
// Initialize the session
session_start();
 
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'reqzone');
 
/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
 
// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
 
// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = $login_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if email is empty
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter email.";
    } else{
        $email = trim($_POST["email"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Get the submitted user type from form
    $submitted_user_type = isset($_POST["user_type"]) ? trim($_POST["user_type"]) : "";
    
    // Validate credentials
    if(empty($email_err) && empty($password_err)){
        // Prepare a select statement to get all user data
        $sql = "SELECT name, email, password, userCategory, userGroup FROM requser WHERE email = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if email exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $name, $email, $hashed_password, $userCategory, $userGroup);
                    
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            
                            // STRICT USER TYPE VALIDATION
                            // Check if the user is trying to login with the correct form
                            if($submitted_user_type == "NCC" && $userCategory != "NCC") {
                                $login_err = "This account is not registered as an NCC user. Please use the Facility login form.";
                            } elseif($submitted_user_type == "Facility" && $userCategory != "Facility") {
                                $login_err = "This account is not registered as a Facility user. Please use the NCC login form.";
                            } else {
                                // Password is correct and user type matches, start session
                                session_regenerate_id();
                                
                                // Store data in session variables
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["name"] = $name;
                                $_SESSION["email"] = $email;
                                $_SESSION["userCategory"] = $userCategory;
                                $_SESSION["userGroup"] = $userGroup;
                                
                                // Determine user role based on category and group
                                $role = "User";
                                if ($userCategory == 'NCC') {
                                    // Define hierarchy for NCC users
                                    switch($userGroup) {
                                        case 'EVC':
                                            $role = "CEO/Executive Vice Chairman";
                                            break;
                                        case 'DCSH':
                                            $role = "Director - Corporate Services HO";
                                            break;
                                        case 'ZCL':
                                            $role = "Zonal Controller";
                                            break;
                                        case 'DCSL':
                                            $role = "Director - Corporate Services LGZO";
                                            break;
                                        case 'UHCSL':
                                            $role = "Unit Head - Corporate Services LGZO";
                                            break;
                                        case 'CSL':
                                            $role = "Corporate Services Staff";
                                            break;
                                        case 'PH':
                                            $role = "Procurement Head";
                                            break;
                                        case 'FH':
                                            $role = "Finance Head";
                                            break;
                                        default:
                                            $role = "NCC Staff";
                                    }
                                } else if ($userCategory == 'Facility') {
                                    $role = "Facility Manager";
                                }
                                
                                $_SESSION["role"] = $role;
                                
                                // Redirect user to appropriate dashboard based on category
                                if ($userCategory == 'Facility') {
                                    header("location: ./Pages/Facility.php");
                                } else {
                                    header("location: ./Pages/NCC.php");
                                }
                                exit();
                            }
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else{
                    // Email doesn't exist, display a generic error message
                    $login_err = "Invalid email or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReqZone - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <link href="./index.css" rel="stylesheet" />
    <div class ="homepage-container">
         <div class="header">
            <svg class="logo" viewBox="0 0 300 80" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#ffffff"/>
                        <stop offset="100%" style="stop-color:#f0f8ff"/>
                    </linearGradient>
                </defs>
                <text x="150" y="45" font-family="Arial, sans-serif" font-size="36" font-weight="bold" text-anchor="middle" fill="url(#logoGradient)">ReqZone</text>
                <text x="150" y="65" font-family="Arial, sans-serif" font-size="12" text-anchor="middle" fill="rgba(255,255,255,0.8)">Facility Management System</text>
            </svg>
            
            <h1 class="tagline">Simplifying Approvals, Speeding Up Your Workflow</h1>
            <p class="subtitle">Streamline your facility management requests with our digital approval system</p>
        </div>
    <div>

        <!-- Role Selection Buttons -->
        <div class="login-section">
            <button class="role-btn" id="nccBtn" onclick="showLoginForm('ncc')">
                <i class="fas fa-building"></i> NCC Officer
            </button>
            <button class="role-btn" id="facilityBtn" onclick="showLoginForm('facility')">
                <i class="fas fa-industry"></i> Facility Manager
            </button>
        </div>

     <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger" style="background: rgba(255, 107, 107, 0.1); border: 1px solid #ff6b6b; color: #ff6b6b; padding: 15px; margin: 20px 0; border-radius: 8px; text-align: center;">' . $login_err . '</div>';
        }        
        ?>

     </div>
      <!-- Login Forms -->
        <div class="login-forms">
            <!-- NCC Login Form -->
        <div id="NCC">
      <form class="login-form" id="nccForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <h2 class="form-title">NCC Officer Login</h2>
                <p class="form-subtitle">Nigerian Communications Commission</p>
                
            <div>
            <div class="form-group">
              <label for='email'>Email:</label>
                <input placeholder='Enter your email...'
                       class= 'email <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>'
                       type='email'
                       name='email'
                       id='email'
                       value="<?php echo $email; ?>"
                       required/>
                       <span class="invalid-feedback" style="color: #ff6b6b; font-size: 0.9rem;"><?php echo $email_err; ?></span>
              </div>
              <div class="form-group">
                <label for='password'>Password:</label>
                  <input placeholder='Enter your password'
                     type='password'
                     class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
                     name='password'
                     id='password'
                     required/>
                     <span class="invalid-feedback" style="color: #ff6b6b; font-size: 0.9rem;"><?php echo $password_err; ?></span>
                     <span style="color: #666; font-size: 0.9rem;">Forgotten Password?</span>
              </div>

              <input type="hidden" name="user_type" value="NCC">
              <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </button> 
              </div>
              <div class="signup-link">
                    Don't have an account? <a href="./Pages/SignUp.php">Register here</a>
              </div>
       </form>
       </div>
       <div id="FACILITY">
            <!-- Facility Login Form -->
             <form class="login-form" id="facilityForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            
                <h2 class="form-title">Facility Manager Login</h2>
                <p class="form-subtitle">Diamond Heirs Facility</p>

              <div class='Facility_form'>
            <div class="form-group">
              <label for='email2'>Email:</label>
                <input placeholder='Enter your email...'
                       class= '<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>'
                       type='email'
                       name='email'
                       id='email2'
                       value="<?php echo $email; ?>"
                       required/>
                       <span class="invalid-feedback" style="color: #ff6b6b; font-size: 0.9rem;"><?php echo $email_err; ?></span>
              </div>
              <div class="form-group">
                <label for='password2'>Password:</label>
                  <input placeholder='Enter your password'
                     type='password'
                     class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
                     name='password'
                     id='password2'
                     required/>
                     <span class="invalid-feedback" style="color: #ff6b6b; font-size: 0.9rem;"><?php echo $password_err; ?></span>
                     <span class="forgot-password" style="color: #666; font-size: 0.9rem;">Forgotten Password?</span>
              </div>
              <input type="hidden" name="user_type" value="Facility">
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </button>
                
                <div class="signup-link">
                    Don't have an account? <a href="./Pages/SignUp.php">Register here</a>
                </div>
       </form>
       </div>
        </div>
    <script>
        function showLoginForm(type) {
            // Hide all forms
            document.getElementById('nccForm').classList.remove('active');
            document.getElementById('facilityForm').classList.remove('active');
            
            // Remove active class from all buttons
            document.getElementById('nccBtn').classList.remove('active');
            document.getElementById('facilityBtn').classList.remove('active');
            
            // Show selected form and activate button
            if (type === 'ncc') {
                document.getElementById('nccForm').classList.add('active');
                document.getElementById('nccBtn').classList.add('active');
            } else {
                document.getElementById('facilityForm').classList.add('active');
                document.getElementById('facilityBtn').classList.add('active');
            }
        }

        // Show facility form by default
        document.addEventListener('DOMContentLoaded', function() {
            showLoginForm('facility');
        });

        window.onload = function() {
            // Handle URL parameters for showing specific forms
            const urlParams = new URLSearchParams(window.location.search);
            const formType = urlParams.get('type');
            if (formType) {
                showLoginForm(formType);
            }
        };
    </script>
</body>
</html>