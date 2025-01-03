<?php
if($_SERVER["REQUEST_URI"] === "/index.php")
{
    header("Location: /home");
    exit();
}

if(session_status() === PHP_SESSION_NONE)
{
    session_start();
}

// Load classes (Modules)

spl_autoload_register(
    function($class)
    {
        require_once "classes/" . $class . ".php";
    }
);

// Enable or disable (true or false) the debugger

Debug::enable(true);

// Authentication for the database

// Database::authenticate(host, user, password, name, charset);
// If charset is not specified, it will default to utf8

Database::authenticate("localhost", "root", "Password", "e_commerce", "utf8");

// Connect to database

Database::connect();

// Set some basic (default) data for the template engine

Builder::add_data("site", array(
    "title" => "E-commerce-CMS",
    "description" => "Description from configuration",
    "keywords" => "Keywords from configuration",
    "author" => "Dimitar Dimitrov",
    "absolute_link" => "http://dimitar.freesite.online"
));

Builder::add_data("author_email", "dimitrov.career@gmail.com");

// Make sure the user status is set to boolean

if(!User::get("signed_in"))
{
    User::set("signed_in", false);
}

// Check if user has set language in their sessions (even if signed out)
// If not, set it to en (english)

if(!User::get("language"))
{
    User::set("language", "en");
}

// Check if user has set currency in their sessions (even if signed out)
// If not, set it to EUR (Euro)

if(!User::get("currency"))
{
    User::set("currency", "EUR");
}

// Check if user has set cart in their sessions

if(!User::get("cart_initialized"))
{
    // If user is signed in

    if(User::get("signed_in"))
    {
        // Update user's shopping cart

        User::update_shopping_cart();
    }
    else
    {
        // Initiate the cart as an empty array

        User::set("cart", []);
        User::set("cart_total_qty", 0);
    }

    // Mark the cart as initialized

    User::set("cart_initialized", true);
}
?>