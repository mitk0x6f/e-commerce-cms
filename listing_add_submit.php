<?php
// Cut off the script if the request method is not POST

if($_SERVER["REQUEST_METHOD"] !== "POST")
{
    // Show sign up page

    header("Location: /home");

    die();
}

// Add the core functionality of the application

require_once("./templates/configuration.php");

header("Content-Type: application/json");

Builder::suppress_errors();

// Default response

$response = ["success" => false, "message" => "Unexpected error."];

// Make sure the user is signed in

if(!User::get("signed_in"))
{
    $response["message"] = "User not signed in.";

    echo(json_encode($response));

    die();
}

// Check if required fields are set and not empty

$required_parameters = ["title", "description", "category_id", "price", "stock", "status"];

foreach($required_parameters as $parameter)
{
    if(!isset($_POST[$parameter]) || trim($_POST[$parameter]) === "")
    {
        $response["message"] = "Invalid request.";

        if(ECS_DEBUG)
        {
            $response["message"] .= " Missing parameter: " . $parameter;
        }

        echo(json_encode($response));

        die();
    }
}

// Category 0 is not all

if((int) $_POST["category_id"] === 0)
{
    $response["message"] = "Please select a category.";

    echo(json_encode($response));

    die();
}

// Add the order in the database as pending

try
{
    // TODO: improve security

    $shop_slug = (string) User::get("slug");
    $title = (string) $_POST["title"];
    $description = (string) $_POST["description"];
    $category_id = (int) $_POST["category_id"];
    $price = (float) $_POST["price"];
    $stock = (int) $_POST["stock"];
    $discount = (int) ($_POST["discount"] ?? 0);
    $status = (int) $_POST["status"];

    // articles.status [0=inactive;1=active;2=deleted;]

    // Add the new article

    $stmt = Database::run(
        "INSERT INTO articles (
            shop_slug,
            title,
            description,
            category,
            price,
            stock,
            discount,
            status
        ) VALUES (
            :shop_slug,
            :title,
            :description,
            :category_id,
            :price,
            :stock,
            :discount,
            :status
        )",
        [
            ":shop_slug" => $shop_slug,
            ":title" => $title,
            ":description" => $description,
            ":category_id" => $category_id,
            ":price" => $price,
            ":stock" => $stock,
            ":discount" => $discount,
            ":status" => $status
        ]
    );

    // Database query successful

    if($stmt)
    {
        // Set the AJAX response

        $response["success"] = true;
        $response["message"] = "Article added successfully.";

        // Get the last inserted article ID

        // TODO: Can be improved by setting Database::$last_insert_id in case Database::run() was INSERT
        // to minimize database queries using self::$pdo->lastInsertId()

        $response["article_id"] = Database::run("SELECT LAST_INSERT_ID() AS article_id")->fetch(PDO::FETCH_ASSOC)["article_id"];
    }
    else
    {
        // Database query failed

        $response["message"] = "Failed to add new article. Please try again later.";
    }
}
catch(Exception $e)
{
    $response["message"] = "Unexpected error. Try again later.";

    if(ECS_DEBUG)
    {
        $response["message"] = "Unexpected error. Try again later. Error: " . $e->getMessage();
    }
}

echo(json_encode($response));

die();
?>