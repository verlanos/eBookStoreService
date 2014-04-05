<?php
include_once "config.php";

try{
    $db_pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_DATABASE,
        DB_USERNAME,
        DB_PASSWORD);

    $users_table = "users";
    $users_columns = "username VARCHAR(255) PRIMARY KEY,
                      salt VARCHAR(255),
                      password VARCHAR(255) NOT NULL,
                      email VARCHAR(255) NOT NULL,
                      type BIT(1) NOT NULL DEFAULT 0";
    $db_pdo->exec("CREATE TABLE [IF NOT EXISTS] $users_table ($users_columns)");

    $books_table = "books";
    $books_columns = "book_id INT PRIMARY KEY,
                      title VARCHAR(255) NOT NULL,
                      authors VARCHAR(255) NOT NULL,
                      description TEXT NOT NULL,
                      price DECIMAL NOT NULL,
                      image VARCHAR(255) NOT NULL,
                      content VARCHAR(255) NOT NULL";

    $db_pdo->exec("CREATE TABLE [IF NOT EXISTS] $books_table ($books_columns)");

    $review_table = "reviews";
    $review_columns = "review_id INT PRIMARY KEY,
                       book_id INT,
                       username VARCHAR(255) NOT NULL,
                       review TEXT,
                       rating INT DEFAULT 0,
                       FOREIGN KEY(book_id) REFERENCES books (book_id),
                       FOREIGN KEY(username) REFERENCES users (username)";


}catch(PDOException $e){
    echo 'ERROR: '.$e->getMessage();
}
?>