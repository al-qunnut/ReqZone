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
    
    // Validate credentials
    if(empty($email_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT email, password FROM requser WHERE email = ?";
        
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
                    mysqli_stmt_bind_result($stmt, $email, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            
                            $stmt = $link->prepare("SELECT userCategory FROM requser WHERE email = ?");
                            $stmt->bind_param("s", $email);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result && $row = $result->fetch_assoc()) {
                                $category = $row['userCategory'];
                            
                                if ($category == 'Facility') {
                                    // Store data in session variables
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["email"] = $email;
                            
                                    header("location: ./Pages/Facility.php");
                                }
                            else
                            {
                                echo "you are NCC";
                            }
                            }
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else{
                    // email doesn't exist, display a generic error message
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
    <title>Document</title>
</head>

<body>
    <link href="./index.css" rel="stylesheet" />
    <div class ="Homepage">
        <div class="Heading">
            <img src="./images/ReqZone.png" alt="logo" class="Logo"/>
            <h3>Simplifying Approvals, Speeding Up Your Workflow</h3>
        </div>

    <div>
      <span onclick="displayNCC()"><button>NCC</button></span><span onclick="displayFacility()"><button>Facility</button></span>
    </div>
     <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>

     </div>

    <div id="NCC">
   <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
      <h1>Nigerian Communication Commission(NCC)</h1>
      <div class="NCC_form">
      <div>
        <label htmlFor='email'>Email:</label>
          <input placeholder='Enter your email...'
                 class= 'email <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>'
                 type='email'
                 name='email'
                 id='email'
                 required/>
                 <span class="invalid-feedback"><?php echo $email_err; ?></span>
        </div>
        <div>
          <label htmlFor='password'>Password:</label>
            <input placeholder='Enter your password'
               type='password'
               class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
               name='password'
               id='password'
               minLength={5}
               maxLength={15
               required/>
               <span class="invalid-feedback"><?php echo $password_err; ?></span>
               <span>Forgotten Passord?</span>
        </div>
        <button type='submit'>
          Login
        </button>
        </div>
         <p>Don't have an account? 
          <span>
           <a href="./Pages/SignUp.php" class="Sign_up">Sign up</a>
          </span>
        </p>
    </form>
    </div>

    <div id="FACILITY">
       <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <h1>Diamond Heirs</h1>
        <div class='Facility_form'>
      <div>
        <label htmlFor='email'>Email:</label>
          <input placeholder='Enter your email...'
                 class= '<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>'
                 type='email'
                 name='email'
                 id='email'
                 required/>
                 <span class="invalid-feedback"><?php echo $email_err; ?></span>
        </div>
        <div>
          <label htmlFor='password'>Password:</label>
            <input placeholder='Enter your password'
               type='password'
               class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
               name='password'
               id='password'
               minLength={5}
               maxLength={15}
               required/>
               <span class="invalid-feedback"><?php echo $password_err; ?></span>
               <span>Forgotten Passord?</span>
        </div>
        <button type='submit'>
          Login
        </button>
        </div>
         <p>Don't have an account? 
          <span>
            <a href="./Pages/SignUp.php" class="Sign_up">Sign up</a>
          </span>
         </p>
    </form>
    </div>
    <script src="./script.js"></script>
</body>
</html>