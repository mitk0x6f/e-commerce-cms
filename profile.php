<?php
// Add the core functionality of the application

require_once("./templates/configuration.php");

// Check if viewing specific profile www.url/profile/slug
// Or signed in user is shop

if((isset($_GET["shop_id"]) && preg_match('/^[a-zA-Z0-9]+$/', $_GET["shop_id"])) || User::get("seller"))
{
    // Sanitize the $_GET and set it to $_GET["shop_id"] if viewing specific profile
    // Else set it to signed in seller's slug

    $shop_slug = isset($_GET["shop_id"]) ? htmlspecialchars(trim($_GET["shop_id"]), ENT_QUOTES, "UTF-8") : User::get("slug");

    // Fetch the shop information

    try
    {
        $result = Database::run(
            "SELECT slug, shop_name, profile_picture, registered_on, seller FROM users WHERE slug = ?",
            [$shop_slug]
        );

        if($result instanceof PDOStatement)
        {
            $result = $result->fetch(PDO::FETCH_ASSOC);

            // TODO: format the registered_on data

            // Add the data to the template variables

            if($result)
            {
                Builder::add_data("shop_info", $result);
            }
        }
    }
    catch(PDOException $e)
    {
        Debug::add($e->getMessage());
    }

    // Fetch the shop's active items

    try
    {
        $result = Database::run(
            "SELECT * FROM articles WHERE shop_slug = ? AND status = 1",
            [$shop_slug]
        );

        if($result instanceof PDOStatement)
        {
            $result = $result->fetchAll(PDO::FETCH_ASSOC);

            if(!$result)
            {
                $result = "No articles for sale found.";
            }

            // Add the data to the template variables

            Builder::add_data("results", $result);
        }
    }
    catch(PDOException $e)
    {
        Debug::add($e->getMessage());
    }

    Builder::page("profile_shop");
}
else // Viewing own profile www.url/profile
{
    if(!User::get("signed_in"))
    {
        header("Location: /home");
        die();
    }

    // Format the registered_on data

    if(User::get("registered_on"))
    {
        // Couldn't manage to install below
        // required extension on xampp for macOS
        // Builder::add_data("registered_on", (new IntlDateFormatter("bg_BG", IntlDateFormatter::LONG, IntlDateFormatter::NONE))->format(strtotime(User::get("registered_on"))));
        // Builder::add_data("registered_on", datefmt_format(datefmt_create("bg_BG", IntlDateFormatter::RELATIVE_MEDIUM, IntlDateFormatter::NONE), strtotime(User::get("registered_on"))));

        // TODO: fix above issue and make the below code better

        Builder::add_data("registered_on", date_format(date_create(User::get("registered_on")), "d.m.Y"));
    }

    // Not in use yet

    // if(User::get("reviews_count"))
    // {
    //     Builder::add_data("reviews", [1]);
    // }
    // else
    // {
    //     Builder::add_data("reviews", "No reviews yet");
    // }

    Builder::page("profile_user");
}

// Display debug messages at the end.
// TODO: display it in modal maybe ?

Debug::show();
?>