<?php
require_once("./templates/configuration.php");

// Check if change_language.php was accessed via change language button or direct link

if($_SERVER["REQUEST_METHOD"] === "POST")
{
    if(isset($_POST["language"]))
    {
        // Protect from possible attacks

        $language = preg_replace("/(?:[a-z]){3}/", "", htmlentities($_POST["language"]));

        // Change website language

        // {{ VAR USER.language ~ }} = en
        // {{ VAR site.language ~ }} = English

        User::set("language", $language);

        if(User::get("signed_in"))
        {
            Database::run("UPDATE users SET `language` = :language WHERE id = :id", [
                ":language" => $language,
                ":id" => User::get("id")
            ]);
        }

        // Return to previous page

        header("Location: " . $_SERVER["HTTP_REFERER"]);
        die();
    }
    elseif(isset($_POST["currency"]))
    {
        // Protect from possible attacks

        $currency = preg_replace("/(?:[a-z]){3}/", "", htmlentities($_POST["currency"]));

        // Change website currency

        User::set("currency", $currency);

        if(User::get("signed_in"))
        {
            Database::run("UPDATE users SET `currency` = :currency WHERE id = :id", [
                ":currency" => $currency,
                ":id" => User::get("id")
            ]);
        }

        // Return to previous page

        header("Location: " . $_SERVER["HTTP_REFERER"]);
        die();
    }
}
else
{
    header("Location: /home");
    die();
}
?>