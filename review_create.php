<?php
include_once "db_conn.php";
header('Content-Type: application/json');
$passed_validation = true;

if(!isset($_SESSION) || !isset($_SESSION['user']))
{
    echo json_encode(array("success"=>False,"message"=>"Not logged in","review_id"=>""));
    exit;
}

$user_input_filters = array(
    "book_id" => array(
        "filter" => FILTER_SANITIZE_STRING
    ),
    "user" => array(
        "filter" => FILTER_SANITIZE_STRING
    ),
    "review" => array(
        "filter" => FILTER_SANITIZE_STRING
    ),
    "rating" => array(
        "filter" => FILTER_SANITIZE_NUMBER_INT
    )
);

$validation_results = filter_input_array(INPUT_POST,$user_input_filters);

if (!$validation_results["book_id"])
{
    echo json_encode(array("success"=>False,"message"=>"Book id has special characters in it"));
    $passed_validation = false;
}
if (!$validation_results["user"])
{
    echo json_encode(array("success"=>False,"message"=>"Username has special characters in it"));
    $passed_validation = false;
}
if (!$validation_results["review"])
{
    echo json_encode(array("success"=>False,"message"=>"Review has special characters in it"));
    $passed_validation = false;
}
if (!$validation_results["rating"])
{
    echo json_encode(array("success"=>False,"message"=>"Rating should be a number"));
    $passed_validation = false;
}

if($passed_validation)
{
    $book_id = $db_pdo->quote($validation_results["book_id"]);
    $username = $db_pdo->quote($validation_results["user"]);
    $review = $db_pdo->quote($validation_results["review"]);
    $rating = $db_pdo->quote($validation_results["rating"]);

    // Check whether a book with an identical title and authors doesn't exist already
    $exist_query = $db_pdo->prepare('SELECT 1 FROM reviews WHERE book_id = :book_id AND username = :username');
    $exist_query->execute(array(':book_id' => $book_id,":username" => $username));

    $rows = $exist_query->fetchAll();
    /* Return of 0 rows implies non-existence of book with title and authors */
    if (count($rows) == 0)
    {
        /* Create & execute prepared INSERT statement */
        $insert_query = $db_pdo->prepare("INSERT INTO reviews (book_id,username,review,rating) VALUES(:book_id, :username, :review, :rating)");
        try{
            $db_pdo->beginTransaction();
            $result_code = $insert_query->execute(array(':book_id'=>$book_id,':username'=>$username,':review'=>$review, ':rating'=>$rating));
            $review_id = $db_pdo->lastInsertId(); // Retrieves ID for currently inserted review
            $db_pdo->commit();
            echo json_encode(array("success"=>$result_code,"message"=>"Review has been created", "review_id"=>"$review_id"));
        }
        catch(PDOException $e)
        {
            $db_pdo->rollBack();
            echo json_encode(array("success"=>false,"message"=>"Review could not be created", "review_id"=>""));
        }

        /* Inform REST-client about success/failure of user-creation */
    }
    else
    {
        echo json_encode(array("success"=>false,"message"=>"Review already exists", "review_id"=>"".${$rows[0]['book_id']}));
    }
}
?>