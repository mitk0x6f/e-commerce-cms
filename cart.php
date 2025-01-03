<?php
// Add the core functionality of the application

require_once("./templates/configuration.php");

// Attack protection

$user_id = User::get("id") ? (int) User::get("id") : false;

if(!User::get("signed_in") || User::get("seller") || !$user_id)
{
    header("Location: /home");
    die();
}

try
{
    // Find active order by user id

    // TODO: Below code is almost a duplicate of User::update_shopping_cart($user_id);
    // Merge both in a single function to avoid unnecessary redundancy

    // Check if user has an active order

    $pending_order = Database::run("SELECT id FROM orders WHERE user_id = ? AND status = 1", [$user_id])->fetch(PDO::FETCH_ASSOC);

    if(!$pending_order || empty($pending_order))
    {
        // If there is no active order for the user, redirect to home

        header("Location: /home");

        die();
    }
    else
    {
        // If user has an active order

        // Get the pending order items

        $pending_order_items = Database::run(
            "SELECT
                order_items.article_id,
                order_items.quantity,
                order_items.price,
                order_items.discount,
                articles.title,
                articles.category
            FROM
                order_items
            INNER JOIN
                articles ON order_items.article_id = articles.id
            WHERE
                order_items.order_id = ? AND order_items.status = 1 AND articles.status = 1",
            [$pending_order["id"]]
        )->fetchAll(PDO::FETCH_ASSOC);

        if($pending_order_items)
        {
            // Category ID to category name mapping

            $categories_map = json_decode(file_get_contents("./templates/categories.json"), true);

            foreach($pending_order_items as &$item)
            {
                // Get the category name from the map for translation purpose

                if(isset($item["category"]) && isset($categories_map[$item["category"]]))
                {
                    // Match category by ID

                    $item["category"] = $categories_map[$item["category"]]["name"];
                }
                else
                {
                    // Category not found

                    $item["category"] = "UNKNOWN";
                }

                // Format results

                $result[] = [
                    "article_id" => $item["article_id"],
                    "title" => $item["title"],
                    "category" => $item["category"],
                    "quantity" => $item["quantity"],
                    "price" => $item["price"],
                    "discount" => $item["discount"]
                ];
            }

            unset($item);

            // Currently the customer doesn't need to see the order id

            // Builder::add_data("order_id", $order_id);
        }
    }

    // Viewing cart when empty is not allowed

    if(empty($result))
    {
        header("Location: /home");

        die();
    }

    // Add the data to the template variables

    Builder::add_data("results", $result);
}
catch(PDOException $e)
{
    Debug::add($e->getMessage());
}

Builder::page("cart");

Debug::show();
?>