<?php
// Add the core functionality of the application

require_once("./templates/configuration.php");

if(!User::get("signed_in"))
{
    header("Location: /home");
    die();
}

Builder::page("settings");

Debug::show();
?>