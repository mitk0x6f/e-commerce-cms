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

// Check if required field are set and not empty

if(!isset($_POST["article_id"]) || trim($_POST["article_id"]) === "")
{
    $response["message"] = "Invalid request.";

    echo(json_encode($response));

    die();
}

try
{
    // TODO: improve security

    $article_id = (int) $_POST["article_id"];
    $shop_slug = (string) User::get("slug");

    // articles.status [0=inactive;1=active;2=deleted;]

    $existing_item = Database::run(
        "SELECT id FROM articles WHERE id = :article_id AND shop_slug = :shop_slug AND status != 2",
        [":article_id" => $article_id, ":shop_slug" => $shop_slug]
    )->fetch(PDO::FETCH_ASSOC);

    if($existing_item)
    {
        // Delete the article

        $stmt = Database::run(
            "UPDATE
                articles
            SET
                status = 2
            WHERE
                id = :article_id AND shop_slug = :shop_slug",
            [
                ":article_id" => $article_id,
                ":shop_slug" => $shop_slug
            ]
        );

        // Database query successful

        if($stmt)
        {
            // Set the AJAX response

            $response["success"] = true;
            $response["message"] = "Article deleted successfully.";
        }
        else
        {
            // Database query failed

            $response["message"] = "Failed to delete the article. Please try again later.";
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