<?php
// Add the core functionality of the application

require_once("./templates/configuration.php");

// Attack protection

$article_id = isset($_GET["id"]) ? htmlspecialchars(trim($_GET["id"])) : "";

// Check if editing specific article website.com/listings/id, saving or deleting it

if(
    $_SERVER["REQUEST_METHOD"] !== "GET" ||
    strlen($article_id) == 0 ||
    strlen($article_id) > 12 ||
    !preg_match("/^[\d]{1,11}$/", $article_id)
)
{
    header("Location: /home");
    die();
}

try
{
    $result = Database::run(
        "SELECT
            articles.*, users.shop_name, GROUP_CONCAT(categories.category_id SEPARATOR ',') AS categories
        FROM
            articles
        INNER JOIN
            users ON articles.shop_slug = users.slug
        LEFT JOIN
            categories ON articles.id = categories.article_id
        WHERE
            articles.id = ? AND articles.status = 1",
        [$article_id]
    );

    if($result instanceof PDOStatement)
    {
        $result = $result->fetch(PDO::FETCH_ASSOC);

        if(!$result || empty($result["id"]))
        {
            header("Location: /404");

            die();
        }

        // TODO: format the registered_on data

        // Format the results

        // "active" column from articles table is 0=inactive, 1=active, 2=deleted
        // "category" column from articles table is the primary category
        // "categories" column from categories table is a list of all secondary categories

        // $result["active"] = ($result["active"] == 0) ? "inactive" : (($result["active"] == 1) ? "active" : "deleted");

        // Status

        switch($result["status"])
        {
            case 0:
                $result["status"] = "inactive";
                break;
            case 1:
                $result["status"] = "active";
                break;
            case 2:
                $result["status"] = "deleted";
                break;
        }

        // Categories hierarchy

        $categories_map = json_decode(file_get_contents("./templates/categories.json"), true);

        $categories = explode(",", $result["categories"]);

        $result["categories"] = [];

        foreach($categories as $category)
        {
            if(isset($categories_map[$category]))
            {
                $result["categories"][] = $categories_map[$category]["name"];
            }
            else
            {
                $result["categories"][] = "UNKNOWN";
            }
        }

        $result["categories"] = implode(",", $result["categories"]);

        // Primary category

        $result["primary_category"] = isset($result["category"]) && isset($categories_map[$result["category"]]) ? $categories_map[$result["category"]]["name"] : "UNKNOWN";
        $result["primary_category_id"] = $result["category"];

        // Generate breadcrumb

        $result["breadcrumb"] = [];

        // Repeat as long as the category had a parent (Until it reaches the root category->home = null)

        while($result["category"] !== null && isset($categories_map[$result["category"]]))
        {
            // Add the category to the breadcrumb

            array_unshift($result["breadcrumb"], $categories_map[$result["category"]]);

            // Set the parent as the new category

            $result["category"] = $categories_map[$result["category"]]["parent"];
        }

        // Add the data to the template variables

        Builder::add_data("article_info", $result);
    }
}
catch(PDOException $e)
{
    Debug::add($e->getMessage());
}

Builder::page("listing_view");

Debug::show();
?>