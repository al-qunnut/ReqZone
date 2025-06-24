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
     </div>

    <div id="NCC">
   <form>
      <h1>Nigerian Communication Commission(NCC)</h1>
      <div class="NCC_form">
      <div>
        <label htmlFor='email'>Email:</label>
          <input placeholder='Enter your email...'
                 type='email'
                 name='emailNcc'
                 id='emailNcc'
                 required/>
        </div>
        <div>
          <label htmlFor='password'>Password:</label>
            <input placeholder='Enter your password'
               type='password'
               name='passwordNcc'
               id='passwordNcc'
               minLength={5}
               maxLength={15
               required/>
               <span>Forgotten Passord?</span>
        </div>
        <button type='submit'>
          Login
        </button>
        </div>
         <p>Don't have an account? 
          <span>
           <a href="./SignUp.html" class="Sign_up">Sign up</a>
          </span>
        </p>
    </form>
    </div>

    <div id="FACILITY">
       <form>
        <h1>Diamond Heirs</h1>
        <div class='Facility_form'>
      <div>
        <label htmlFor='email'>Email:</label>
          <input placeholder='Enter your email...'
                 type='email'
                 name='emailFacility'
                 id='emailFacility'
                 required/>
        </div>
        <div>
          <label htmlFor='password'>Password:</label>
            <input placeholder='Enter your password'
               type='password'
               name='passwordFacility'
               id='passwordFacility'
               minLength={5}
               maxLength={15}
               required/>
               <span>Forgotten Passord?</span>
        </div>
        <button type='submit'>
          Login
        </button>
        </div>
         <p>Don't have an account? 
          <span>
            <a href="./SignUp.html" class="Sign_up">Sign up</a>
          </span>
         </p>
    </form>
    </div>
    <script src="./script.js"></script>
</body>
</html>