<?php
include_once "db_conn.php";

$user_input_filters = array(
    "book_id" => array(
        "filter" => FILTER_SANITIZE_STRING));

$validation_results = filter_input_array(INPUT_GET,$user_input_filters);

if($validation_results['book_id'])
{
    $book_id = $db_pdo->quote($validation_results["book_id"]);
    $exist_query = $db_pdo->prepare('SELECT 1 FROM books WHERE book_id = :book_id');
    $exist_query->execute(array(':book_id' => $book_id));
    $result = $exist_query->fetch(PDO::FETCH_ASSOC);
    if ($result)
    {
        $image_path = DATA_FOLDER.$result['image'];
        if(file_exists($image_path))
        {
            $fp = fopen($image_path,'rb');
            $f_size = filesize($image_path);
            header("Content-type: image/jpeg");
            header("Content-length: $f_size");
            header("Cache-control: private");
            fpassthru($fp);
        }
        else{
            header("HTTP/1.0 404 Not Found");
            exit;
        }

    }
    else{
        header("HTTP/1.0 404 Not Found");
        exit;
    }
}
else{
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>