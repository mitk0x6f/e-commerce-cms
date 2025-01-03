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

Builder::suppress_errors();

// Default response

$response = ["success" => false, "message" => "Unexpected error."];

// Make sure the user is signed in
// TODO: shopping cart should also work without being signed in

if(!User::get("signed_in"))
{
    $response["message"] = "User not signed in.";

    echo(json_encode($response));

    die();
}

header("Content-Type: application/json");

// Check if article_id and quantity are set

if(!isset($_POST["article_id"]))
{
    $response["message"] = "Invalid request.";

    echo(json_encode($response));

    die();
}

try
{
    $article_id = (int) $_POST["article_id"];
    $user_id = (int) User::get("id");

    // orders.status [0=cancelled;1=pending;2=ready_for_shipping;3=shipped;4=received;]
    // order_items.status [0=removed;1=active;]

    $existing_item = Database::run(
        "SELECT order_items.id FROM order_items
        JOIN orders ON order_items.order_id = orders.id
        WHERE order_items.article_id = :article_id
            AND order_items.status = 1
            AND orders.user_id = :user_id
            AND orders.status = 1",
        [
            ":article_id" => $article_id,
            ":user_id" => $user_id
        ]
    )->fetch(PDO::FETCH_ASSOC);

    if($existing_item)
    {
        // Update the status of the article in the order

        $stmt = Database::run("UPDATE order_items SET status = 0 WHERE id = ?", [$existing_item["id"]]);

        // Database query successful

        if($stmt)
        {
            // Find and delete the article from the $_SESSION["cart"]

            $existing_index = array_search($article_id, array_column($_SESSION["cart"], "article_id"));

            if($existing_index !== false)
            {
                // Update the status of the article in the cart

                unset($_SESSION["cart"][$existing_index]);

                // Reindex the array to ensure contiguous indices

                $_SESSION["cart"] = array_values($_SESSION["cart"]);
            }

            // Update the cart total quantity

            $total_qty = 0;

            // Count the total quantity

            foreach(User::get("cart") as $item)
            {
                $total_qty += $item["quantity"];
            }

            // Set the cart total quantity

            User::set("cart_total_qty", $total_qty);

            // Set the AJAX response

            $response["success"] = true;
            $response["message"] = "Cart updated successfully.";

            $response["total_qty"] = $total_qty;
        }
        else
        {
            // Database query failed

            $response["message"] = "Failed to update cart. Please try again later.";
        }
    }
    else
    {
        // No such article

        $response["message"] = "No such article found in the shopping cart.";
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