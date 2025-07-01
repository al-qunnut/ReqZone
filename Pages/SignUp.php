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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter your name.";
    } elseif (!preg_match('/^[a-zA-Z ]+$/', trim($_POST["name"]))) {
        $name_err = "Name can only contain letters and spaces.";
    } else {
        $sql = "SELECT name FROM requser WHERE name = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_name);
            $param_name = trim($_POST["name"]);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $name_err = "This name is already taken.";
                } else {
                    $name = $param_name;
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter a valid email.";
    } elseif (strpos($_POST["email"], "@") === false) {
        $email_err = "Email must contain @ symbol.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate user category
    if (empty(trim($_POST["userCategory"]))) {
        $userCategory_err = "Please select a company.";
    } else {
        $userCategory = trim($_POST["userCategory"]);
    }

    // Validate user group
    if (empty(trim($_POST["userGroup"]))) {
        $userGroup_err = "Please select a department.";
    } else {
        $userGroup = trim($_POST["userGroup"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
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
    if (empty($name_err) && empty($email_err) && empty($userCategory_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "INSERT INTO requser (name, email, userCategory, userGroup, password) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $userCategory, $userGroup, $hashed_password);
            if (mysqli_stmt_execute($stmt)) {
                echo "Registration successful!";
                // header("Location: success_page.php"); // Optional redirect
            } else {
                echo "Something went wrong. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        echo "Please fix the following errors:<br>";
        echo $name_err . "<br>" . $email_err . "<br>" . $userCategory_err . "<br>" . $password_err . "<br>" . $confirm_password_err;
    }

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
    <style>
        * {
            margin: 0;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        }

        .logo {
            margin: 30px;
        }

        .SignUp {
            background-image: url(../images/SignUp.png);
            background-position: center center;
            background-size: cover;
            background-repeat: no-repeat;
            width: 100vw;
            height: 100vh;
            justify-items: center;
        }

        .Grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10;
        }

        input, select {
            margin: 5px;
            width: 38vw;
            padding: 10px;
            font-size: large;
            border: gray solid 1px;
            display: block;
        }
        
        label {
            font-size: larger;
            display: block;
            margin-top: 10px;
        }

        .btn {
            color: white;
            padding: 10px;
            padding-right: 30px;
            padding-left: 30px;
            border-radius: 20px;
            background-color: #0835C8;
            font-size: 1.5rem;
            border: none;
            margin-top: 10px;
            width: 200px;
        }
        
        .btn:hover {
            text-transform: uppercase;
            font-size: 2rem;
        }
        
        .btn:active {
            text-transform: uppercase;
            font-size: 2rem;
            opacity: 5%;
        }

        #userGrp {
            display: none; 
        }


    @media screen and (max-width: 508px ) {
        .Logo {
            width: 300px;
        }

        .Grid {
            display: block;
            margin: 10px;
        }

        input, select {
            width: 80vw;
        }
    
        label {
           padding: 5px;
           font-size: 1.2rem;
           display: block;
        }
    }

    </style>
      

      <div class="SignUp">
        <div class="">
            <img src="../images/ReqZone2.png" class="logo" alt="logo" />
        </div>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <h1>Sign Up</h1>
        <p>Already have an account? 
          <span>
            <a href="../index.php">Login</a>
          </span>
         </p>
        <div class='Grid'>
      <div>
        <label htmlFor='name'>Name:</label>
          <input placeholder='Enter your name...'
                 class = 'name <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>'
                 type='name'
                 value="<?php echo $name; ?>"
                 name='name'
                 id='name'
                 required/>
        <span class="invalid-feedback"><?php echo $name_err; ?></span>
        </div>
      <div>
        <label htmlFor='email'>Email:</label>
          <input placeholder='Enter your email...'
                 type='email'
                 name='email'
                 id='email'
                 required/>
        </div>
      <div>
        <label htmlFor='userCategory'>Company:</label>
          <select class="userCategory" name="userCategory"  id="userCategory" onchange="selectUser()" >
            <option value="">Select your company...</option>
            <option value="NCC" id="NCC">Nigerian Communication Commission(NCC)</option>
            <option value="Facility">Diamond Heirs</option>
          </select>
        </div>
      <div id="userGrp">
        <label htmlFor='userGroup'>Department:</label>
          <select class="userGroup" name="userGroup" >
            <option value="">Select your department...</option>
            <option value="CSL">Corporate Services LGZO</option>
            <option value="UHCSL">Unit Head-Corporate Serives LGZO </option>
            <option value="DCSL">Director Corporate Serives LGZO </option>
            <option value="ZCL">Zonal Controller LGZO </option>
            <option value="DCSH">Director Corporate Serives HO </option>
            <option value="EVC">CEO/EVC</option>
            <option value="PH">Procurement HO</option>
            <option value="FH">Finance HO</option>
          </select>
        </div>
        <div>
          <label htmlFor='password'>Password:</label>
            <input placeholder='Enter your password'
               class='password <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>'
               type='password'
               name='password'
               id='password'
               minLength={5}
               maxLength={15}
               required/>
        </div>
        <div>
          <label htmlFor='confirm_password'>Confirm Password:</label>
            <input placeholder='Confirm your password'
               type='password'
               name="confirm_password"
               value= '<?php echo $confirm_password; ?>'
               class ='confirmPassword <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>'
               id="confirm_password"
               minLength={5}
               maxLength={15}
               required/>
               <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
        </div>
        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary" value="Submit">
        </div>
      </div>


      <script>
       function selectUser() {
         const selectedValue = document.getElementById("userCategory").value;
         const userGrp = document.getElementById("userGrp");
       
         if (selectedValue === "NCC") {
           userGrp.style.display = "block";
         } else {
            userGrp.style.display = "none";
         }
       }
      </script>
</body>
</html>

 
 

