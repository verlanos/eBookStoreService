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
    )
);

$validation_results = filter_input_array(INPUT_POST,$user_input_filters);

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

    // Check whether this username exist already
    $exist_query = $db_pdo->prepare('SELECT 1 FROM users WHERE username = :username');
    $exist_query->execute(array(':username' => $username));

    $rows = $exist_query->fetchAll(PDO::FETCH_ASSOC);
    /* Return of 0 rows implies non-existence of user with username */
    if (count($rows) == 1)
    {
        $hashed_stored_password = $rows[0]['password'];
        if (crypt($password, $hashed_stored_password) == $hashed_stored_password)
        {
            if (!isset($_SESSION))
            {
                session_start();
                $_SESSION['user'] = $username;
                $_SESSION['user_type'] = $rows[0]['type'] ? "ADMIN":"USER";
            }

            echo json_encode(array("success"=>True,"message"=>"Login successful"));
        }
        else{
            echo json_encode(array("success"=>False,"message"=>"Invalid username or password"));
        }
    }
    else{
        echo json_encode(array("success"=>False,"message"=>"Invalid username or password"));
    }
}
?>