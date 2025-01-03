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

// Check if order_id and shop_note are set

if(!isset($_POST["order_id"]) || !isset($_POST["shop_note"]))
{
    $response["message"] = "Invalid request.";

    echo(json_encode($response));

    die();
}

try
{
    // Sanitize the data

    $order_id = htmlspecialchars(trim((int) $_POST["order_id"]), ENT_QUOTES, "UTF-8");
    $shop_slug = htmlspecialchars(trim((string) User::get("slug")), ENT_QUOTES, "UTF-8");

    // Select the order

    $existing_item = Database::run(
        "SELECT
            orders.id as order_id
        FROM
            orders
        INNER JOIN
            order_items ON orders.id = order_items.order_id
        INNER JOIN
            articles ON order_items.article_id = articles.id
        WHERE
            orders.id = :order_id
            AND articles.shop_slug = :shop_slug
        LIMIT 1",
        [
            ":order_id" => $order_id,
            ":shop_slug" => $shop_slug
        ]
    )->fetch(PDO::FETCH_ASSOC);

    if($existing_item)
    {
        // Max length of the note is 255 characters. Trim the note.

        $shop_note = htmlspecialchars(substr(trim((string) $_POST["shop_note"]), 0, 255), ENT_QUOTES, "UTF-8");

        // Update the note of the order

        if($shop_note === "")
        {
            $shop_note = NULL;
        }

        $stmt = Database::run(
            "UPDATE
                orders
            SET
                shop_note = :shop_note
            WHERE
                id = :order_id",
            [
                ":order_id" => $existing_item["order_id"],
                ":shop_note" => $shop_note
            ]
        );

        // Database query successful

        if($stmt)
        {
            // Set the AJAX response

            $response["success"] = true;
            $response["message"] = "Note updated successfully.";
        }
        else
        {
            // Database query failed

            $response["message"] = "Failed to update order note. Please try again later.";
        }
    }
    else
    {
        // No such order

        $response["message"] = "No such order found or you are not the owner.";
    }
}
catch(Exception $e)
{
    $response["message"] = "Unexpected error. Try again later.";

    if(ECS_DEBUG)
    {
        $response["message"] += " " . $e->getMessage();
    }
}

echo(json_encode($response));

die();
?>