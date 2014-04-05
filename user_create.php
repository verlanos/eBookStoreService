<?php
include_once "db_conn.php";
header('Content-Type: application/json');
$passed_validation = true;

$user_input_filters = array(
  "username" => array(
      "filter" => FILTER_SANITIZE_STRING
  ),
  "password" => array(
      "filter" => FILTER_SANITIZE_STRING
  ),
  "email" => FILTER_VALIDATE_EMAIL
);

$validation_results = filter_input_array(INPUT_POST,$user_input_filters);

/* Validate e-mail address */
if (!$validation_results["email"])
{
    echo json_encode(array("success"=>False,"message"=>"Invalid e-mail address"));
    $passed_validation = false;
}
if (!$validation_results["username"])
{
    echo json_encode(array("success"=>False,"message"=>"Username has special characters in it"));
    $passed_validation = false;
}
if (!$validation_results["password"])
{
    echo json_encode(array("success"=>False,"message"=>"Password has special characters in it"));
    $passed_validation = false;
}

if($passed_validation)
{
    $username = $db_pdo->quote($validation_results["username"]);
    $password = $db_pdo->quote($validation_results["password"]);
    $email = $db_pdo->quote($validation_results["email"]);

    // Check whether a username with an identical username doesn't exist already
    $exist_query = $db_pdo->prepare('SELECT 1 FROM users WHERE username = :username');
    $exist_query->execute(array(':username' => $username));

    /* Return of 0 rows implies non-existence of user with username */
    if (count($exist_query->fetchAll()) == 0)
    {
        /* Create salt */
        $salt = bin2hex(openssl_random_pseudo_bytes(255));
        $password_hashed = crypt($password,'$2a$11$'.$salt);

        /* Create & execute prepared INSERT statement with hashed password instead of clear text password*/
        $insert_query = $db_pdo->prepare("INSERT INTO users (username,password,salt,email) VALUES(:username, :password, :salt, :email)");

        try{
            $db_pdo->beginTransaction();
            $result_code = $insert_query->execute(array(':username'=>$username,':password'=>$password_hashed,':email'=>$email, ':salt'=>$salt));
            $db_pdo->commit();
            /* Inform REST-client about success/failure of user-creation */
            echo json_encode(array("success"=>$result_code,"message"=>"User has been created"));
        }
        catch(PDOException $e)
        {
            echo json_encode(array("success"=>false,"message"=>"User could NOT be created"));
        }
    }
    else{
        echo json_encode(array("success"=>False,"message"=>"Username has been taken"));
    }
}
?>