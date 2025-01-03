<?php
// Add the core functionality of the application

require_once("./templates/configuration.php");

// Attack protection

$article_id = isset($_GET["id"]) ? htmlspecialchars(trim($_GET["id"])) : "";

// Check if editing specific article website.com/listings/id, saving or deleting it

if(
    $_SERVER["REQUEST_METHOD"] === "GET" &&
    strlen($article_id) > 0 &&
    strlen($article_id) < 12 &&
    preg_match("/^[\d]{1,11}$/", $article_id)
)
{
    try
    {
        $result = Database::run(
            "SELECT * FROM articles WHERE id = ? AND status != 2",
            [$article_id]
        );

        if($result instanceof PDOStatement)
        {
            $result = $result->fetch(PDO::FETCH_ASSOC);

            if(!$result)
            {
                header("Location: /404");

                die();
            }

            // TODO: format the registered_on data

            // Format results

            // "active" column is 0=inactive, 1=active, 2=deleted

            // $result["active"] = ($result["active"] == 0) ? "inactive" : (($result["active"] == 1) ? "active" : "deleted");

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

            // Add the data to the template variables

            if($result)
            {
                Builder::add_data("article_info", $result);
            }
        }
    }
    catch(PDOException $e)
    {
        Debug::add($e->getMessage());
    }

    Builder::page("listing_edit");
}
else
{
    header("Location: /home");
    die();
}

Debug::show();
?>