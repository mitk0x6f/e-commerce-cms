<?php
// Cut off the script if the request method is not POST

if($_SERVER["REQUEST_METHOD"] !== "POST")
{
    header("Location: /home");

    die();
}

require_once("./templates/configuration.php");

if(User::get("signed_in"))
{
    header("Location: /home");
    die();
}

if(isset($_POST["email"]) && isset($_POST["phone_number"]))
{
    $user_info = [
        "email" => filter_var($_POST["email"], FILTER_SANITIZE_EMAIL),
        "phone_number" => preg_replace("/[^0-9]/", "", filter_var(htmlentities($_POST["phone_number"]), FILTER_SANITIZE_NUMBER_INT))
    ];

    if(User::check_available($user_info["email"], $user_info["phone_number"]))
    {
        echo "available";
    }
    else
    {
        echo "exists";
    }
}
else
{
    echo "invalid request";
}
?>