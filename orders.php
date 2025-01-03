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

try
{
    $result = Database::run(
        "SELECT
            order_items.order_id,
            orders.user_id,
            orders.order_date,
            orders.status AS order_status,
            FORMAT(SUM(order_items.price * order_items.quantity * (100 - order_items.discount) / 100), 2) AS total_price
        FROM
            order_items
        JOIN
            orders ON order_items.order_id = orders.id
        JOIN
            articles ON order_items.article_id = articles.id
        WHERE
            articles.shop_slug = :shop_slug AND order_items.status = 1
        GROUP BY
            order_items.order_id, orders.user_id, articles.shop_slug
        ORDER BY
            orders.order_date DESC;",
        [":shop_slug" => $shop_slug]
    );

    if($result instanceof PDOStatement)
    {
        $result = $result->fetchAll(PDO::FETCH_ASSOC);

        // If no results found make sure the result is an array

        if($result)
        {
            // Format the results - combine by order_id and total price (order_items.price * order_items.quantity * (100 - order_items.discount) / 100)

            foreach($result as &$row)
            {
                // orders.order_date column is timestamp

                // Format the date to DD.MM.YYYY from ISO 8601 (YYYY-MM-DD)

                $row["order_date"] = date("d.m.Y", strtotime($row["order_date"]));

                // Status column is 0=cancelled, 1=pending, 2=ready_for_shipping, 3=shipped, 4=received

                $row["order_status_code"] = $row["order_status"];

                switch($row["order_status"])
                {
                    case 0:
                        $row["order_status"] = "order_cancelled";
                        break;
                    case 1:
                        $row["order_status"] = "order_pending";
                        break;
                    case 2:
                        $row["order_status"] = "order_ready_for_shipping";
                        break;
                    case 3:
                        $row["order_status"] = "order_shipped";
                        break;
                    case 4:
                        $row["order_status"] = "order_received";
                        break;
                    default:
                        $row["order_status"] = "other";
                        break;
                }
            }
        }
        else
        {
            $result = ["No results found"];
            print_r($result);
        }

        unset($row);

        // Add the data to the template variables

        Builder::add_data("results", $result);
    }
}
catch(PDOException $e)
{
    Debug::add($e->getMessage());
}

Builder::page("orders");

Debug::show();
?>