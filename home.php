<?php
include "config.php";

// Encrypt cookie
function encryptCookie( $value ) {

   $key = hex2bin(openssl_random_pseudo_bytes(4));

   $cipher = "aes-256-cbc";
   $ivlen = openssl_cipher_iv_length($cipher);
   $iv = openssl_random_pseudo_bytes($ivlen);

   $ciphertext = openssl_encrypt($value, $cipher, $key, 0, $iv);

   return( base64_encode($ciphertext . '::' . $iv. '::' .$key) );
}

// Decrypt cookie
function decryptCookie( $ciphertext ) {

   $cipher = "aes-256-cbc";

   list($encrypted_data, $iv,$key) = explode('::', base64_decode($ciphertext));
   return openssl_decrypt($encrypted_data, $cipher, $key, 0, $iv);

}

// Check if $_SESSION or $_COOKIE already set
if( isset($_SESSION['userid']) ){
   header('Location: home.php');
   exit;
}else if( isset($_COOKIE['rememberme'] )){

   // Decrypt cookie variable value
   $userid = decryptCookie($_COOKIE['rememberme']);

   // Fetch records
   $stmt = $conn->prepare("SELECT count(*) as cntUser FROM usuario WHERE idusuario=:idusuario");
   $stmt->bindValue(':id', (int)$userid, PDO::PARAM_INT);
   $stmt->execute(); 
   $count = $stmt->fetchColumn();

   if( $count > 0 ){
      $_SESSION['userid'] = $userid; 
      header('Location: home.php');
      exit;
   }
}

// On submit
if(isset($_POST['but_submit'])){

   $username = $_POST['txt_uname'];
   $password = $_POST['txt_pwd'];

   if ($username != "" && $password != ""){

     // Fetch records
     $stmt = $conn->prepare("SELECT count(*) as cntUser,id FROM users WHERE username=:username and password=:password ");
     $stmt->bindValue(':username', $username, PDO::PARAM_STR);
     $stmt->bindValue(':password', $password, PDO::PARAM_STR);
     $stmt->execute(); 
     $record = $stmt->fetch(); 

     $count = $record['cntUser'];

     if($count > 0){
        $userid = $record['id'];

        if( isset($_POST['rememberme']) ){

           // Set cookie variables
           $days = 30;
           $value = encryptCookie($userid);

           setcookie ("rememberme",$value,time()+ ($days * 24 * 60 * 60 * 1000)); 
        }

        $_SESSION['userid'] = $userid; 
        header('Location: home.php');
        exit;
    }else{
        echo "Invalid username and password";
    }

  }

}
?>



<?php 
include "config.php";
?>
<!doctype html>
<html>
   <head>
      <title>Login page with Remember me using PDO and PHP</title>
   </head>
   <body>
     <?php
     // Check user login or not
     if(!isset($_SESSION['userid'])){ 
       header('Location: index.php');
     }

     // logout
     if(isset($_POST['but_logout'])){
       session_destroy();

       // Remove cookie variables
       $days = 30;
       setcookie ("rememberme","", time() - ($days * 24 * 60 * 60 * 1000) );

       header('Location: index.php');
     }
     ?>
     <h1>Homepage</h1>
     <form method='post' action="">
       <input type="submit" value="Logout" name="but_logout">
     </form>
   </body>
</html>
