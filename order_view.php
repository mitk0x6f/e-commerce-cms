<?php
// Add the core functionality of the application

require_once("./templates/configuration.php");

// Attack protection

$shop_slug = User::get("slug") ? htmlspecialchars(trim(User::get("slug"))) : "";

if(!User::get("signed_in") || !User::get("seller") || strlen($shop_slug) < 1 || strlen($shop_slug) > 51)
{
    header("Location: /home");
    die();
}

$order_id = isset($_GET["id"]) ? htmlspecialchars(trim($_GET["id"])) : "";

if(
    $_SERVER["REQUEST_METHOD"] === "GET" &&
    strlen($order_id) > 0 &&
    strlen($order_id) < 12 &&
    preg_match("/^[\d]{1,11}$/", $order_id)
)
{
    try
    {
        // Find articles by order id and viewing shop
        // Below query could be split, so no redundant information about the buyer is added to each article,
        // however a single query is the more efficient approach.

        $result = Database::run(
            "SELECT
                articles.id AS article_id,
                articles.title,
                articles.category,
                order_items.price,
                order_items.discount,
                order_items.quantity,
                orders.status,
                orders.shop_note,
                users.first_name,
                users.last_name,
                users.email, -- TODO: WIP IF USER IS SHARING THE EMAIL. Currently is shared for all.
                users.phone_number,
                users.language AS preferred_language
            FROM
                order_items
            INNER JOIN
                articles ON order_items.article_id = articles.id
            INNER JOIN
                orders ON order_items.order_id = orders.id
            INNER JOIN
                users ON orders.user_id = users.id
            WHERE
                order_items.order_id = :order_id
                AND articles.shop_slug = :shop_slug
                AND order_items.status = 1
            ORDER BY
                articles.title ASC;",
            [
                ':order_id' => $order_id,
                ':shop_slug' => $shop_slug
            ]
        );

        if($result instanceof PDOStatement)
        {
            $result = $result->fetchAll(PDO::FETCH_ASSOC);

            // If no results found make sure the result is an array

            if($result && !empty($result))
            {
                // Format results

                // Copy data from results array to be used outside of the template loop

                // TODO: WIP IF USER IS SHARING THE EMAIL. Currently is shared for all.

                $to_be_extracted = ["status", "shop_note", "first_name", "last_name", "email", "phone_number", "preferred_language"];

                $first_pass = true;

                // Use reference to avoid copying

                foreach($result as &$row)
                {
                    // Handle first pass (extract data matching $to_be_extracted)

                    if($first_pass)
                    {
                        foreach($row as $key => $value)
                        {
                            if(is_null($value))
                            {
                                // If value is null, replace it with empty string or the template will give error:
                                // Trying to access non-existing template variable: $key.

                                $value = "";
                            }

                            if(in_array($key, $to_be_extracted))
                            {
                                if($key === "status")
                                {
                                    // Status column is 0=cancelled;1=pending;2=ready_for_shipping;3=shipped;4=received;

                                    // Keep the status code

                                    Builder::add_data("status_code", $value);

                                    switch($value)
                                    {
                                        case 0:
                                            $value = "order_cancelled";
                                            break;
                                        case 1:
                                            $value = "order_pending";
                                            break;
                                        case 2:
                                            $value = "order_ready_for_shipping";
                                            break;
                                        case 3:
                                            $value = "order_shipped";
                                            break;
                                        case 4:
                                            $value = "order_received";
                                            break;
                                    }
                                }

                                Builder::add_data($key, $value);
                            }
                        }
                    }

                    // Category ID to category name mapping

                    $categories_map = json_decode(file_get_contents("./templates/categories.json"), true);

                    $row["category"] = isset($categories_map[$row["category"]]) ? $categories_map[$row["category"]]["name"] : "UNKNOWN";

                    $first_pass = false;
                }

                unset($row);

                Builder::add_data("order_id", $order_id);
            }
            else
            {
                $result = ["No results found"];
            }

            // Add the data to the template variables

            Builder::add_data("results", $result);
        }
    }
    catch(PDOException $e)
    {
        Debug::add($e->getMessage());
    }
}

Builder::page("order_view");

Debug::show();
?>