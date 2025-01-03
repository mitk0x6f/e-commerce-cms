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

$response = ["success" => false, "message" => "Unexpected error.", "cart_total_qty" => 0];

// Make sure the user is signed in

if(!User::get("signed_in"))
{
    $response["message"] = "User not signed in.";

    echo(json_encode($response));

    die();
}

// Check if article_id and quantity are set

if(!isset($_POST["article_id"]) || !isset($_POST["quantity"]))
{
    $response["message"] = "Invalid request.";

    echo(json_encode($response));

    die();
}

// Add the order in the database as pending

try
{
    $article_id = (int) $_POST["article_id"];
    $quantity = (int) $_POST["quantity"];
    $user_id = (int) User::get("id");

    // Check if there is a pending order for the user

    $order = Database::run("SELECT id FROM orders WHERE user_id = ? AND status = 1", [$user_id])->fetch(PDO::FETCH_ASSOC);

    if($order)
    {
        $order_id = &$order["id"];
    }
    else
    {
        $stmt = Database::run("INSERT INTO orders (user_id) VALUES (?)", [$user_id]);

        if($stmt)
        {
            // Get the last inserted order ID

            $order_id = Database::run("SELECT LAST_INSERT_ID() AS order_id")->fetch(PDO::FETCH_ASSOC)["order_id"];
        }
        else
        {
            $response["message"] = "Failed to create the order. Please try again later.";

            echo(json_encode($response));

            die();
        }
    }

    // Get the article's price and discount

    $article = Database::run("SELECT title, price, discount FROM articles WHERE id = ?", [$article_id])->fetch(PDO::FETCH_ASSOC);

    // Add the article in the order

    if($article)
    {
        $existing_item = Database::run(
            "SELECT id, quantity FROM order_items WHERE order_id = :order_id AND article_id = :article_id AND status = 1",
            ["order_id" => $order_id, "article_id" => $article_id]
        )->fetch(PDO::FETCH_ASSOC);

        // Check if the user has already added the article in the order prior to this request

        if($existing_item)
        {
            // Update the quantity of the article in the order

            $quantity += $existing_item["quantity"];

            $stmt = Database::run(
                "UPDATE order_items SET quantity = :new_quantity WHERE id = :id",
                ["new_quantity" => $quantity, "id" => $existing_item["id"]]
            );
        }
        else
        {
            // Add the article in the order

            $stmt = Database::run(
                "INSERT INTO order_items (order_id, article_id, quantity, price, discount) VALUES (:order_id, :article_id, :quantity, :price, :discount)",
                [
                    "order_id" => $order_id,
                    "article_id" => $article_id,
                    "quantity" => $quantity,
                    "price" => $article["price"],
                    "discount" => $article["discount"]
                ]
            );
        }

        // Database query successful

        if($stmt)
        {
            // Check if the article is already in the cart

            $existing_index = array_search($article_id, array_column($_SESSION["cart"], "article_id"));

            if($existing_index !== false)
            {
                // Update the quantity of the article in the cart

                $_SESSION["cart"][$existing_index]["quantity"] = $quantity;
            }
            else
            {
                // Add the article in the cart

                $_SESSION["cart"][] = [
                    "order_id" => $order_id,
                    "article_id" => $article_id,
                    "title" => $article["title"],
                    "quantity" => $quantity,
                    "price" => $article["price"],
                    "discount" => $article["discount"]
                ];
            }

            // Count the total quantity

            foreach(User::get("cart") as $item)
            {
                $response["cart_total_qty"] += $item["quantity"];
            }

            // Set the cart total quantity

            User::set("cart_total_qty", $response["cart_total_qty"]);

            // Set the AJAX response

            $response["success"] = true;
            $response["message"] = "Cart updated successfully.";
        }
        else
        {
            $response["message"] = "Failed to update cart. Please try again later.";
        }
    }
    else
    {
        $response["message"] = "Trying to buy an article that doesn't exist.";
    }
}
catch(Exception $e)
{
    $response["message"] = "Unexpected error. Try again later.";
}

echo(json_encode($response));

die();
?>