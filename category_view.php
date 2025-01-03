<?php
// Add the core functionality of the application

require_once("./templates/configuration.php");

// Attack protection

$category_name = isset($_GET["category"]) ? htmlspecialchars(trim($_GET["category"]), ENT_QUOTES, "UTF-8") : "";

// Whitelist of valid categories

// Categories hierarchy

$categories_map = json_decode(file_get_contents("./templates/categories.json"), true);

if($_SERVER["REQUEST_METHOD"] !== "GET" || strlen($category_name) === 0 || !in_array($category_name, array_column($categories_map, "name")))
{
    header("Location: /home");

    die();
}

// Generate breadcrumb
// Example: Array ( [0] => kitchen [1] => refrigerators [2] => built_in_refrigerators )

$breadcrumb = [];
$category_id_initial = null;
$last_category_name = null;

// Repeat as long as the category had a parent (Until it reaches the root category->home = null)

while($category_name !== null && isset($categories_map))
{
    // Find the current category by name

    $category_info = null;
    $category_id_current = null;

    // Loop through the categories_map to find the category info by name

    foreach($categories_map as $id => $category)
    {
        if($category["name"] === $category_name)
        {
            $category_info = $category;
            $category_id_current = $id;

            // Stop the loop once the category is found

            break;
        }
    }

    // If category is found

    if($category_info !== null)
    {
        // If this is the initial category

        if($category_id_initial === null)
        {
            // Remember the initial category id

            $category_id_initial = $category_id_current;
        }

        // Remember the last category name

        $last_category_name = $category_name;

        // Add the category name to the breadcrumb

        array_unshift($breadcrumb, $category_info);

        // Set the parent name as the new category

        $category_name = isset($category_info["parent"]) ? $categories_map[$category_info["parent"]]["name"] : null;
    }
    else
    {
        // If the category is not found, stop the loop

        break;
    }
}

Builder::add_data("breadcrumb", $breadcrumb);

// Check for sub-categories of the current category

$sub_categories = [];

foreach($categories_map as $id => $category)
{
    // If the category is a sub-category of the current category

    if($category["parent"] === $category_id_initial)
    {
        // Add the category to the sub-categories array

        $sub_categories[] = $category;
    }
}

// Add the data to the template variables

Builder::add_data("sub_categories", $sub_categories);

// Create a function to recursively fetch sub-categories

function get_sub_categories_ids($category_id, $categories_map)
{
    $sub_categories_ids = [];

    foreach($categories_map as $id => $category)
    {
        if($category["parent"] === $category_id)
        {
            $sub_categories_ids[] = $id;

            // Recursively fetch sub-categories for this category

            $sub_categories_ids = array_merge($sub_categories_ids, get_sub_categories_ids($id, $categories_map));
        }
    }

    return $sub_categories_ids;
}

try
{
    // Retrieve all sub-categories and add them to the initial category as an array for the query

    $sub_category_ids = get_sub_categories_ids($category_id_initial, $categories_map);

    array_unshift($sub_category_ids, $category_id_initial);

    // Add "?" as many times as the number of sub-categories

    $sub_categories_count = implode(", ", array_fill(0, count($sub_category_ids), "?"));

    $result = Database::run("SELECT * FROM articles WHERE category IN ($sub_categories_count) AND status = 1", $sub_category_ids);

    if($result instanceof PDOStatement)
    {
        $result = $result->fetchAll(PDO::FETCH_ASSOC);

        if(!$result)
        {
            $result = "No results found.";
        }

        // Add the data to the template variables

        Builder::add_data("results", $result);
    }
}
catch(PDOException $e)
{
    Debug::add($e->getMessage());
}

Builder::page("category_view");

Debug::show();
?>