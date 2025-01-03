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

$required_parameters = ["article_id", "title", "description", "category_id", "price", "stock", "status"];

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

try
{
    // TODO: improve security

    $shop_slug = (string) User::get("slug");
    $article_id = (int) $_POST["article_id"];
    $title = (string) $_POST["title"];
    $description = (string) $_POST["description"];
    $category_id = (int) $_POST["category_id"];
    $price = (float) $_POST["price"];
    $stock = (int) $_POST["stock"];
    $discount = (int) ($_POST["discount"] ?? 0);
    $status = (int) $_POST["status"];

    // articles.status [0=inactive;1=active;2=deleted;]

    $existing_item = Database::run(
        "SELECT id FROM articles WHERE id = :article_id AND shop_slug = :shop_slug AND status != 2",
        [":article_id" => $article_id, ":shop_slug" => $shop_slug]
    )->fetch(PDO::FETCH_ASSOC);

    if($existing_item)
    {
        // Update the article

        $stmt = Database::run(
            "UPDATE
                articles
            SET
                title = :title,
                description = :description,
                category = :category_id,
                price = :price,
                stock = :stock,
                discount = :discount,
                status = :status
            WHERE
                id = :article_id",
            [
                ":title" => $title,
                ":description" => $description,
                ":category_id" => $category_id,
                ":price" => $price,
                ":stock" => $stock,
                ":discount" => $discount,
                ":status" => $status,
                ":article_id" => $article_id
            ]
        );

        // Database query successful

        if($stmt)
        {
            // Set the AJAX response

            $response["success"] = true;
            $response["message"] = "Article updated successfully.";
        }
        else
        {
            // Database query failed

            $response["message"] = "Failed to update article. Please try again later.";
        }
    }
    else
    {
        // No such article

        $response["message"] = "No such article found.";
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