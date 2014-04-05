<?php
include_once "db_conn.php";

// TODO: Verify if user is ADMIN
header('Content-Type: application/json');

if((!isset($_SESSION)) || !($_SESSION['user_type']=="ADMIN"))
{
    echo json_encode(array("success"=>False,"message"=>"Insufficient rights"));
    exit;
}

$passed_validation = true;
$final_image_path = "";
$final_content_path = "";

$user_input_filters = array(
    "title" => array(
        "filter" => FILTER_SANITIZE_STRING
    ),
    "authors" => array(
        "filter" => FILTER_SANITIZE_STRING
    ),
    "description" => array(
        "filter" => FILTER_SANITIZE_STRING
    ),
    "price" => array(
        "filter" => FILTER_SANITIZE_NUMBER_FLOAT,
        "options" => array(
            "min_range"=>0.00,
            "max_range"=>1000.00
        )
    )
);

$validation_results = filter_input_array(INPUT_POST,$user_input_filters);

if (!$validation_results["title"])
{
    echo json_encode(array("success"=>False,"message"=>"Title has invalid characters"));
    $passed_validation = false;
}
if (!$validation_results["authors"])
{
    echo json_encode(array("success"=>False,"message"=>"Authors have invalid characters"));
    $passed_validation = false;
}
if(!$validation_results["description"])
{
    echo json_encode(array("success"=>False,"message"=>"Description has invalid characters"));
    $passed_validation = false;
}
if(!$validation_results["price"])
{
    echo json_encode(array("success"=>False,"message"=>"Price is invalid"));
    $passed_validation = false;
}

if(!isset($_FILES['content'])) echo json_encode(array("success"=>False,"message"=>"No content"));
if(!isset($_FILES['image'])) echo json_encode(array("success"=>False,"message"=>"No image"));

$content_tmp_name = $_FILES['content']['name'];
$image_tmp_name = $_FILES['image']['name'];

$target_content_path = Null;
$target_image_path = Null;

if((is_uploaded_file($image_tmp_name) && getimagesize($image_tmp_name) != false) &&
   (is_uploaded_file($content_tmp_name) && getimagesize($content_tmp_name) != false))
{
    $image_size = getimagesize($image_tmp_name);
    $image_type = $image_size['mime'];

    $content_size = getimagesize($content_tmp_name);
    $content_type = $content_size['mime'];

    if(!($image_type == 'image/jpeg') || (!($content_type == 'application/pdf')))
    {
        $passed_validation = false;
        echo json_encode(array("success"=>False,"message"=>"Image files should be in JPEG format and content in PDF format"));
    }
    else
    {
        $directory_name = make_random_string();

        $image_name = $_FILES['image']['name'];
        $content_name = $_FILES['content']['name'];

        if (!file_exists(DATA_FOLDER.$directory_name."/"))
        {
            mkdir(DATA_FOLDER.$directory_name."/");
        }
        else{
            while(true)
            {
                $directory_name = make_random_string();
                if (!file_exists(DATA_FOLDER.$directory_name."/"))
                {
                    mkdir(DATA_FOLDER.$directory_name."/");
                    break;
                }
            }
        }
        $final_image_path = $directory_name.basename($image_name);
        $final_content_path = $directory_name.basename($content_name);

        if(!move_uploaded_file($image_tmp_name,DATA_FOLDER.$final_image_path) || !move_uploaded_file($content_tmp_name,DATA_FOLDER.$final_content_path))
        {
            $passed_validation = false;
            echo json_encode(array("success"=>False,"message"=>"Failed to upload files"));
        }
    }

}
if ($passed_validation)
{
    $title = $db_pdo->quote($validation_results["title"]);
    $authors = $db_pdo->quote($validation_results["authors"]);
    $description = $db_pdo->quote($validation_results["description"]);
    $price = $db_pdo->quote($validation_results["price"]);

    // Check whether a book with an identical title and authors doesn't exist already
    $exist_query = $db_pdo->prepare('SELECT 1 FROM books WHERE title = :title AND authors = :authors');
    $exist_query->execute(array(':title' => $title,":authors" => $authors));

    $rows = $exist_query->fetchAll();
    /* Return of 0 rows implies non-existence of book with title and authors */
    if (count($rows) == 0)
    {
        /* Create & execute prepared INSERT statement */
        $insert_query = $db_pdo->prepare("INSERT INTO books (title,authors,description,price,image,content) VALUES(:title, :authors, :description, :price, :image, :content)");

        try{
            $db_pdo->beginTransaction();
            $result_code = $insert_query->execute(array(':title'=>$title,':authors'=>$authors,':description'=>$description, ':price'=>$price,':image'=>$final_image_path,':content'=>$final_content_path));
            $book_id = $db_pdo->lastInsertId(); // Retrieves ID for currently inserted book
            $db_pdo->commit();
            echo json_encode(array("success"=>$result_code,"message"=>"Book has been created", "book_id"=>"$book_id"));
        }
        catch(PDOException $e)
        {
            $db_pdo->rollBack();
            echo json_encode(array("success"=>false,"message"=>"Book could not be created", "book_id"=>""));
        }

        /* Inform REST-client about success/failure of user-creation */

    }
    else{
        echo json_encode(["success"=>False,"message"=>"Book already exists","book_id"=>"${$rows[0]['book_id']}"]);
    }
}

function make_random_string()
{
    $length = 20;
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    $size_alpha = strlen($chars);
    $str = "";
    for($i = 0; $i<$length; $i++)
    {
       $str .= $chars[rand(0,$size_alpha-1)];
    }
    return $str;
}

?>