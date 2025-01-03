<?php
require_once("./templates/configuration.php");

// Check if there is actual search request

// Attack protection and replacement of _ from the url to space

$search_query = isset($_GET["search"]) ? str_replace("_", " ", htmlspecialchars(trim($_GET["search"]))) : "";

if($_SERVER["REQUEST_METHOD"] !== "GET" || strlen($search_query) < 3 || !preg_match("/^[\p{L}\d\s\-\_]{3,}$/", $search_query))
{
    header("Location: /home");
    die();
}

try
{
    $result = Database::run(
        "SELECT * FROM articles WHERE (title LIKE ? OR description LIKE ?) AND status = 1",
        ["%$search_query%", "%$search_query%"]
    );

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

Builder::page("search");

Debug::show();
?>