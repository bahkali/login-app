<?php 

/*   helper function   */

    function clean($str){
        return htmlentities($str);
    }

    function redirect($l){
        return header("Location: {$l}");
    }

    function set_message($m){
        if(!empty($m)){
            $_SESSION['message'] = $m;
        }else{
            $m = "";
        }
        
    }

    function display_message(){
        if(isset($_SESSION['message'])){
            echo $_SESSION['message'];
            unset($_SESSION['message']);
        }
    }

    function token_generator(){
       $token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));
        
        return $token;
        
    }

    function validation_errors($m){
        $m = <<<DELIMITER

<div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <strong>Warning!</strong> $m
</div>
DELIMITER;
       return $m; 
    }


    function email_exist($email){
        $sql = "SELECT id FROM users WHERE email = '$email'";
        $result = query($sql);
        
        if(row_count($result) == 1){
            return true;
        } else{
            return false;
        }
    }

    function username_exist($username){
        $sql = "SELECT id FROM users WHERE username = '$username'";
        $result = query($sql);
        
        if(row_count($result) == 1){
            return true;
        } else{
            return false;
        }
    }

    function send_email($email, $subject, $msg, $headers){
        
       return mail($email, $subject, $msg, $headers);
    }
/*   validation function   */

    function validate_user_registration(){
        
        $errors = [];
        
        $min = 3;
        $max= 20 ;
        
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            
            $first_name     = clean($_POST['first_name']);
            $last_name      = clean($_POST['last_name']);
            $username       = clean($_POST['username']);
            $email          = clean($_POST['email']);
            $password       = clean($_POST['password']);
            $confirm_password = clean($_POST['confirm_password']);
            
            if(strlen($first_name) < $min ){
                $errors[]=  "Your first name can't be less than {$min} characters";
            }
            if(strlen($first_name) > $max ){
                $errors[]=  "Your first name can't be more than {$max} characters";
            }       
            
            if(strlen($last_name) < $min){
                $errors[]=  "Your last name can't be less than {$min} characters";
            }
            if(strlen($last_name) > $max){
                $errors[]=  "Your last name can't be more than {$max} characters";
            }
            if(username_exist($username)){
                $errors[] ="Sorry that username already is registered";
            }
            
            if(strlen($username) < $min){
                $errors[]=  "Your Username can't be less than {$min} characters";
            }
            if(strlen($username) > $max){
                $errors[]=  "Your  Username can't be more than {$max} characters";
            }
            
            if(email_exist($email)){
                $errors[] ="Sorry that email already is registered";
            }
            
            if(strlen($email) > 50){
                $errors[]=  "Your  email can't be more than 50 characters";
            }
            
            if($password !== $confirm_password){
                
                $errors[]=  "Your password fields do not match";
            }
            
            if(!empty($errors)){
                foreach($errors as $error){
                    //error display
                   echo validation_errors($error);
                }
            }else{
                 if(register_user($first_name, $last_name, $username, $email, $password)){
                     set_message("<p class='bg-sucess text-center'>Please check your email or span folder for activation link</p>");
                     redirect("index.php");
                 } else {
                     set_message("<p class='bg-danger text-center'>Sorry we we could not register the user</p>");
                     redirect("index.php");
                 }
            }
        }
    }

    /*   register user  function   */

    function register_user($first_name, $last_name, $username, $email, $password){
        
        $first_name = escape($first_name);
        $last_name = escape($last_name);
        $username = escape($username);
        $email = escape($email);
        $password = escape($password);
        
        if(email_exist($email)){
            return false;
        }else if(username_exist($username)){
            return false;
        } else{
            
            $password = md5($password);
            
            $validation = md5($username + microtime());
            
            $sql = "INSERT INTO users(first_name, last_name, username, email, password, validation_code, active)";
            $sql .= " VALUES('$first_name','$last_name','$username', '$email', '$password','$validation','0')";
            $result= query($sql);
            confirm($result);
            
            $subject = "Activation Account";
            $msg = "Please click the link below to activate your Account
                http://localhst/login/exercise/activate.php?email=$email&code=$validation
            ";
            $headers= "From: norreply@socialnetwork.com";
            
            send_email($email, $subject, $msg, $headers);
            
            return true;
        }
        
        
    }

    /*   Activate user function   */

    function activate_user(){
        if($_SERVER['REQUEST_METHOD'] == "GET"){
            
                if(isset($_GET['email'])){
                     $email = clean($_GET['email']);
                     $validation = clean($_GET['code']);
                    
                    
                    $sql = "SELECT id FROM users WHERE email = '" . escape($_GET['email']) ."' AND validation_code = '" . escape($_GET['code'])."'" ;
                    $result = query($sql);
                    confirm($result);
                    
                        if(row_count($result) == 1){
                            $sql2 = "UPDATE users SET active = 1, validation_code = 0 WHERE email = '" . escape($_GET['email']) ."' AND validation_code = '" . escape($_GET['code'])."'";
                            
                            $result2 = query($sql2);
                            confirm($result2);
                            
                            set_message("<p class='bg-success'>Your account has been activated please login</p> ");
                            redirect("login.php");
                        } else{
                             set_message("<p class='bg-danger'>Sorry Your account could not be activated</p> ");
                            redirect("login.php");
                        }
                    
                }
        }
    }


    /*   validate user login function   */

    function validate_user_login(){
        
        $errors = [];
        
        $min = 3;
        $max = 20;
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
           
            $email = clean($_POST['email']);
            $password = clean($_POST['password']);
            $remember = isset($_POST['remember']);
            
            if(empty($email)){
                $errors[] = "Email field cannt be empty";
                
            }
            if(empty($password)){
                $errors[] = "Password field cannt be empty";
                
            }
            
            if(!empty($errors)){
                foreach($errors as $error){
                    //error display
                   echo validation_errors($error);
                }
            }else{
                
                if(login_user($email, $password, $remember)){
                    redirect("admin.php");
                }else{
                    echo validation_errors("Your credentials are not correct");
                }
            }
            
        }
    }

    /*    user login function   */

   
	function login_user($email, $password, $remember) {


		$sql = "SELECT password, id FROM users WHERE email = '". escape($email)."' AND active = 1";

		$result = query($sql);

		if(row_count($result) == 1) {

			$row = fetch_array($result);

			$db_password = $row['password'];


			if(md5($password) === $db_password) {

				if($remember == "on") {

					setcookie('email', $email, time() + 86400);

				}
                
				$_SESSION['email'] = $email;

				return true;

			} else {


				return false;
			}
			return true;

		} else {
			return false;

		}
	} // end of function

  /*     logged in function   */

    function logged_in(){
        if(isset($_SESSION['email']) || isset($_COOKIE['email'])){
            return true;
        } else{
            return false;
        }
    }

  /*     Recover Password function   */

    function recover_password(){
          
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
           
            if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token'])
            {
                $email = clean($_POST['email']);
                
               if(email_exist($email)){
                   
                   $validation_code = md5($email + microtime());
                   
                   setcookie('temp_access_code', $validation_code, time() + 900);
                   
                   $sql="UPDATE users SET validation_code= '". escape($validation_code) . "' WHERE email = '". escape($email) ."' "; 
                   $result = query($sql);
                    
                   
                   
                   $subject= "Please reset your password";
                   $message= "Here is your password reset code {$validation_code} 
                   
                   click here to reset your password http://localhost/login/exercise/code.php?email=$email&code=$validation_code
                   ";
                   
                   $header = "From: noreply@loginwebsite.com";
                   
                   if(!send_email($email, $subject, $message, $header)){
                       echo validation_errors("This emails does not exist.");
                   }
                   
                   set_message("<p class='bg-success text-center'>Please check your email or spam folder for passowrd reset code</p>");
                   
                   redirect("index.php");
                   
               } else{
                   echo validation_errors('This emails does not exist');
                   
               }//email check
            }else{
                redirect("index.php");
            }//token check
            
            if(isset($_POST['cancel-submit'])){
                redirect("login.php");
            }
        }//end post request
        
        
        
    }//end funtion


  /*     code validation function   */

    function validate_code(){
        
        if(isset($_COOKIE['temp_access_code'])){
          
                if(!isset($_GET['email']) && !isset($_GET['code'])){
                    
                    redirect("index.php");
                    
                } else if(empty($_GET['email']) || empty($_GET['code'])){
                    
                   redirect("index.php"); 
                    
                } else{
                    
                    if(isset($_POST['code'])){
                       
                        $validation_code = clean($_POST['code']);
                        $email = clean($_GET['email']);
                        
                        $sql = "SELECT id FROM users WHERE validation_code = '". escape($validation_code) . "' AND email = '". escape($email) . "'";
                        $result = query($sql);
                        
                        if(row_count($result) == 1) {

						setcookie('temp_access_code', $validation_code, time()+ 300);

						redirect("reset.php?email=$email&code=$validation_code");


                        } else {

                            echo validation_errors("Sorry wrong validation code");

                        }
                        
                    }
                }
            
        }else{
            set_message("<p class='bg-danger text-center'>Sorry your validation cookie was expire</p>");
            redirect("recover.php");
        }
    }

///*     password reset function   */
  function password_reset(){
    if(isset($_COOKIE['temp_access_code'])){ 
        
        if(isset($_GET['email']) && isset($_GET['code'])){
            
            if(isset($_SESSION['token']) && isset($_POST['token'])  && $_POST['token'] === $_SESSION['token']){
               
                if($_POST['password'] === $_POST['confirm_password']){
                
                 
                 $updated_password = md5($_POST['password']);
                 $sql= "UPDATE users SET password = '".escape($updated_password)."', validation_code = 0 WHERE email ='" .escape($_GET['email']). "'";
                 query($sql);
                    
                 set_message("<p class='bg-succes text-center'>Your password has been updated, please log in</p>");
                 redirect("login.php");
                }
             }
        }
        
    }else{
            set_message("<p class='bg-danger text-center'>Sorry your time has expire</p>");
            redirect("recover.php");
    }
 }

    
?>






























































