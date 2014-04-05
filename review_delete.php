<?php
include_once "db_conn.php";
header('Content-Type: application/json');

$user_input_filters = array(
    "review_id" => array(
        "filter" => FILTER_SANITIZE_STRING));

$validation_results = filter_input_array(INPUT_GET,$user_input_filters);

if($validation_results['review_id'])
{
    $review_id = $db_pdo->quote($validation_results["review_id"]);
    $exist_query = $db_pdo->prepare('SELECT 1 FROM reviews WHERE review_id = :review_id;');
    $exist_query->execute(array(':review_id' => $review_id));
    $result = $exist_query->fetch(PDO::FETCH_ASSOC);
    if ($result)
    {
        $authorised_user = $result['user'];
        if(isset($_SESSION['user']) && ($_SESSION['type'] == "ADMIN" || $_SESSION['user'] == $authorised_user))
        {
            /* Create & execute prepared DELETE statement */
            $deletion_query = $db_pdo->prepare("DELETE FROM  reviews WHERE review_id = :review_id;");

            try{
                $db_pdo->beginTransaction();
                $result_code = $deletion_query->execute(array(':review_id'=>$review_id));
                $db_pdo->commit();
                echo json_encode(array("success"=>$result_code,"message"=>"Review has been deleted successfully "));
            }
            catch(PDOException $e)
            {
                $db_pdo->rollBack();
                echo json_encode(array("success"=>false,"message"=>"Review could not be deleted"));
            }
        }
        else
        {
            echo json_encode(array("success"=>false,"message"=>"Review could not be deleted"));
        }
    }
    else{
        echo json_encode(array("success"=>false,"message"=>"Review could not be found"));
        exit;
    }
}

?>
