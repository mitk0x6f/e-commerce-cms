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
    // Find articles from the viewing seller, which are not deleted

    $result = Database::run(
        "SELECT * FROM articles WHERE shop_slug = ? AND status != 2",
        [$shop_slug]
    );

    if($result instanceof PDOStatement)
    {
        $result = $result->fetchAll(PDO::FETCH_ASSOC);

        // If no results found make sure the result is an array

        if($result)
        {
            // Format results


            // Use reference to avoid copying

            foreach($result as &$row)
            {

                // Created_on column is timestamp

                // Format the date to DD.MM.YYYY from ISO 8601 (YYYY-MM-DD)

                $row["created_on"] = date("d.m.Y", strtotime($row["created_on"]));

                // Category ID to category name mapping

                $categories_map = json_decode(file_get_contents("./templates/categories.json"), true);

                $row["category"] = isset($categories_map[$row["category"]]) ? $categories_map[$row["category"]]["name"] : "UNKNOWN";

                // Status column is 0=inactive, 1=active, 2=deleted

                // $row["active"] = ($row["active"] == 0) ? "inactive" : (($row["active"] == 1) ? "active" : "deleted");

                // Keep the status code

                $row["status_code"] = $row["status"];

                switch($row["status"])
                {
                    case 0:
                        $row["status"] = "inactive";
                        break;
                    case 1:
                        $row["status"] = "active";
                        break;
                    case 2:
                        $row["status"] = "deleted";
                        break;
                }
            }

            unset($row);
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

Builder::page("listings");

Debug::show();
?>