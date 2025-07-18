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

$name = "Guest";

if (isset($_SESSION['name'])) {
    $sql = "SELECT name FROM requser WHERE name = ";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_name);
            $param_name = trim($_POST["name"]);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" /> 
</head>
<body>
    <style>
        *{
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

       .Home {
         display: grid;
         grid-template-columns: 1fr 11fr;
       }

       .sidebar_box{
        background-color: rgb(0, 0, 73);
        color: white;
        height: 100vh;
        font-size: 2.5rem;
        display: flex;
        justify-content: center;
       }

       .sidebar_icons {
        display: flex;
        flex-direction: column;
        gap: 35px;
        margin-top: 100px;
        padding: 10px 0;
       }

       #sidebar {
        display: none;
       }
        
       .logo {
        margin: 20px;
        width: 200px;
       }

       ul{
        list-style: none;
        padding: 10px;
       }

       ul li {
        display: flex;
        margin: 20px;
       }

        i {
        margin-right: 20px;
        font-size: 3rem;
       }
      
       #Dashboard_content {
        padding: 30px;
        display: none;
       }

        #Upload #Record #Profile {
          display: none;
        }

        .upload {
          justify-content: center;
          align-items: center;
          width: 80vw;
          height: 50vh;
          padding: 30px;
        }
        
        form {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 20;
        }

        .document {
          display: flex;
            justify-content: center;
            align-items: center;
            border: 2px dashed gray;
            width: 50vw;
            height: 10vh;
            padding: 20px;
            margin: 20px;
        }

        .doc {
          font-size: 1.5rem;
          border: none;
          padding: auto;
        }
         
        input, select, textarea {
            margin: 5px;
            width: 38vw;
            padding: 10px;
            font-size: 1rem;
            font-weight: bold;
            border: gray solid 1px;
            display: block;
        }
        
        label {
            font-size: large;
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
      
       .Mobile {
        display: none;
       }

       .header, .Mobile_header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.5rem;
        margin: 30px;
       }
       
       .user {
        color: rgb(0, 0, 73);
        font-size: 3rem;
       }

       .Requests_box {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        border-bottom: 2px solid black;
        padding: 20px 0; 
       }

       .recentUpload {
        padding: 20px 0; 
       }

       .boxes {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        padding: 10px 0; 
       }

       .box {
        padding: 12px;
        margin: 10px;
        cursor: pointer;
        background-color:rgb(245, 242, 242);
        display: flex;
        box-shadow: gray 2px;
       }

       .box:hover {
        background-color: rgb(0, 0, 73);
        color: white;
       }

       .icon {
        background-color: white;
        color: rgb(0, 0, 73);
        padding: 20px;
        height: 30px;
        width: 30px;
        margin-right: 10px;
        border-radius: 50%;
       }

       .box div span {
        font-size: 2rem;
        font-weight: Bolder;
       }

       @media screen and (max-width: 768px){
        #icons {
          display: none;
        }

        .Mobile {
        display: block;
       }

       .header {
        margin: 10px;
       }

        input, select, textarea {
            width: 70vw;
        }

        .doc {
          width: 50vw;
        }
        

       form {
          display: block;
        }

        .Requests_box {
        display: block;
        gap: 20px;
       }

        .boxes {
        display: block;
        gap: 20px;
       }
       }
    </style>

    <div class="Home">
        <!-- Desktop Sidebar -->
        <div class="sidebar_box">
             <span  id="icons">
              <div class="sidebar_icons">
              <i class="fa-solid fa-house-user" onclick="displaySidebar(); displayDashboard();"></i>
              <i class="fa-solid fa-file-arrow-up" onclick="displaySidebar(); displayUpload();"></i>
              <i class="fa-solid fa-list-check" onclick="displaySidebar(); displayRecords();"></i>
              <i class="fa-solid fa-user" onclick="displaySidebar(); displayProfile();"></i>
              </div>
             </span>
              <sidebar id="sidebar">
                <img src="../images/ReqZone.png" alt="logo" width="200px" class="logo"/>
                <ul>
                  <li onclick="displayDashboard()"><i class="fa-solid fa-house-user"></i> Dashboard</li>
                  <li onclick="displayUpload()"><i class="fa-solid fa-file-arrow-up"></i>Upload</li>
                  <li onclick="displayRecords()"><i class="fa-solid fa-list-check"></i>Record</li>
                  <li onclick="displayProfile()"><i class="fa-solid fa-user"></i>Profile</li>
                </ul>
              </sidebar>
        </div>
        
          <!-- Mobile Sidebar -->
           <div>
          <div class="Mobile">
            <div class="Mobile_header">
               <i class="fa-solid fa-bars" onclick="displayMobileSidebar()"></i>
               <img src="../images/ReqZone2.png" alt="logo" width="150px" class="logo"/>
            </div>
          </div>


            <!-- Dashboard Content -->
        <div id="Dashboard_content">
            <div class="header">
                <h1>Hello <span class="user"><?php echo $name; ?></span></h1>
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
                   <span>0<span>
                 </div>
                </div>
                <div class="box">
                  <div class="icon">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                  </div>
                  <div class="">
                    <h2>Payment Requests Made</h2>
                    <span>0<span>
                  </div>
                </div>
                <div class="box">
                  <div class="icon">
                    <i class="fa-solid fa-check-double"></i>
                  </div>
                  <div class="">
                    <h2>All Requests Made</h2>
                    <span>0<span>
                  </div>
                </div>
               </div>

               <div class="recentUpload">
                <h1>Recently Uploaded</h1>
                <div class="boxes">
                <div class="box">
                  <img src="" alt="" />
                  <span>Accepted</span>
                  <h2>Document Title</h2>
                  <div class="">
                    <img src='' alt='' />
                    <h3><span class="user"><?php echo $name; ?></span></h3>
                  </div>
                </div>
                <div class="box">
                  <img src="" alt="" />
                  <span>Accepted</span>
                  <h2>Document Title</h2>
                  <div class="">
                    <img src='' alt='' />
                    <h3><span class="user"><?php echo $name; ?></span></h3>
                  </div>
                </div>
                <div class="box">
                  <img src="" alt="" />
                  <span>Accepted</span>
                  <h2>Document Title</h2>
                  <div class="">
                    <img src='' alt='' />
                    <h3><span class="user"><?php echo $name; ?></span></h3>
                  </div>
                </div>
                <div class="box">
                  <img src="" alt="" />
                  <span>Accepted</span>
                  <h2>Document Title</h2>
                  <div class="">
                    <img src='' alt='' />
                    <h3><span class="user"><?php echo $name; ?></span></h3>
                  </div>
                </div>
                </div>
               </div>
              <div class="notification">

              </div>
            </div>
        </div>
         
        <!-- Upload content -->
        <div id="Upload">
          <div  class="upload">
            <div class="">
            <h1>Upload your document<h1>
              <div class="document">
                <input class='doc' type='file' value='' placeholder='Drag and drop file here or choose file' />
              </div>
             <form>
              <div class="">
                <label>Document name</label>
                <input type='text' name='docName' placeholder="Please enter the document name..." required />
              </div>
              <div class="">
                <label>Request Type</label>
                <select>
                  <option value="">Pick the request type</option>
                  <option value="Payment">Payment Request</option>
                  <option value="Job">Job Request</option>
                </select>
              </div>
              <div class="">
                <label>Description</label>
                <textarea type='text' name='description' rows='10' columns='10' placeholder="Please enter the document description..." required></textarea>
              </div>
             </form>
             <div class="">
              <button class='btn'>
                Submit
              </button>
              </div>
          </div>
          </div>
        </div>

        <div id="Record">Record</div>
        <div id="Profile">Profile</div>
        </div>
    </div>



    <script>

      window.onload = function () {
  const savedSection = localStorage.getItem('activeSection') || 'Dashboard_content';
  showSection(savedSection);
};
      function displaySidebar()
      {
          document.getElementById('sidebar').style.display = 'block';
          document.getElementById('icons').style.display = 'none';

          setTimeout(() => {
            document.getElementById('sidebar').style.display = 'none';
            document.getElementById('icons').style.display = 'flex';
          }, 2000)
      }

      function displayMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar.style.display === 'block') {
            sidebar.style.display = 'none'; 
        } else {
            sidebar.style.display = 'block'; 
        }
      }
        function toggleDisplay(elementId, displayStyle) {
          document.getElementById(elementId).style.display = displayStyle;
        }

       function showSection(sectionId) {
  const sections = ['Dashboard_content', 'Upload', 'Record', 'Profile'];
  sections.forEach(id => toggleDisplay(id, id === sectionId ? 'block' : 'none'));

  // Save the current section
  localStorage.setItem('activeSection', sectionId);
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
    
    </script>
</body>
</html>