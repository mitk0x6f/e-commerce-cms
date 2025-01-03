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
// TODO: shopping cart should also work without being signed in

if(!User::get("signed_in"))
{
    $response["message"] = "User not signed in.";

    echo(json_encode($response));

    die();
}

// Check if article_id and quantity are set

if(!isset($_POST["article_id"]) || !isset($_POST["new_qty"]))
{
    $response["message"] = "Invalid request.";

    echo(json_encode($response));

    die();
}

try
{
    $article_id = (int) $_POST["article_id"];
    $new_quantity = (int) $_POST["new_qty"];
    $user_id = (int) User::get("id");

    // Check if there is a pending order for the user

    $order = Database::run("SELECT id FROM orders WHERE user_id = ? AND status = 1", [$user_id])->fetch(PDO::FETCH_ASSOC);

    if(!$order)
    {
        $response["message"] = "There is no pending order for the user.";

        echo(json_encode($response));

        die();
    }

    // Check if user has the article in his order

    $article = Database::run(
        "SELECT 1 FROM order_items WHERE article_id = :article_id AND order_id = :order_id LIMIT 1",
        [
            ":article_id" => $article_id,
            ":order_id" => $order["id"]
        ]
    )->fetchColumn();

    // Update the quantity in the order

    if($article)
    {
        $stmt = Database::run(
            "UPDATE order_items SET quantity = :new_quantity WHERE article_id = :article_id AND order_id = :order_id",
            [
                ":new_quantity" => $new_quantity,
                ":article_id" => $article_id,
                ":order_id" => $order["id"]
            ]
        );

        if($stmt)
        {
            $existing_index = array_search($article_id, array_column($_SESSION["cart"], "article_id"));

            if($existing_index !== false)
            {
                $_SESSION["cart"][$existing_index]["quantity"] = $new_quantity;

                $total_qty = 0;

                foreach(User::get("cart") as $item)
                {
                    $total_qty += $item["quantity"];
                }

                User::set("cart_total_qty", $total_qty);

                $response["success"] = true;
                $response["message"] = "Cart updated successfully.";

                $response["total_qty"] = $total_qty;
            }
            else
            {
                $response["message"] = "Failed to update cart. Article not found in the shopping cart.";
            }
        }
        else
        {
            $response["message"] = "Failed to update cart. Please try again later.";
        }
    }
    else
    {
        $response["message"] = "Updating the shopping cart failed. Article not found in the shopping cart.";
    }
}
catch(Exception $e)
{
    $response["message"] = "Unexpected exception. Try again later.";
}

echo(json_encode($response));

die();
?>