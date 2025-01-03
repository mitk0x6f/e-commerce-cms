<?php
// Cut off the script if the request method is not POST

if($_SERVER["REQUEST_METHOD"] !== "POST")
{
    // Show sign up page

    header("Location: /home");

    die();
}

// Add the core functionality of the application

require_once("./templates/configuration.php");

// If user is already signed in, redirect to homepage

if(User::get("signed_in"))
{
    header("Location: /home");

    die();
}

header("Content-Type: application/json");

Builder::suppress_errors();

// Default response

$response = ["success" => false, "message" => "Unexpected error."];

// Check if required fields are set and not empty

$required_parameters = ["email", "password"];

foreach($required_parameters as $parameter)
{
    if(!isset($_POST[$parameter]) || trim($_POST[$parameter]) === "")
    {
        $response["message"] = "Invalid request.";

        if(ECS_DEBUG)
        {
            $response["message"] .= " Missing " . $parameter;
        }

        echo(json_encode($response));

        die();
    }
}

try
{
    // TODO: improve security

    $email = filter_var((string) $_POST["email"], FILTER_SANITIZE_EMAIL);
    $password = (string) $_POST["password"];

    // Check validity

    // TODO: improve code, so echo and die are not repeated for each error

    if(!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $response["message"] = "Invalid email address.";

        echo(json_encode($response));

        die();
    }

    // Check if user password contain at least one lowercase letter, one uppercase letter, one number, one symbol, and be at least 8 characters long. [can include symbols]

    if(!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,256}$/", $password))
    {
        $response["message"] = "Invalid password. Password must contain at least one lowercase letter, one uppercase letter, one number, one symbol, and be at least 8 characters long.";

        echo(json_encode($response));

        die();
    }

    if(User::check_user($email, $password))
    {
        // User::sign_in($email, $password, $automatic_redirect[default = true]);

        User::sign_in($email, $password, false);

        $response["success"] = true;

        echo(json_encode($response));

        die();
    }
    else
    {
        $response["message"] = "Invalid email or password.";
    }
}
catch(Exception $e)
{
    $response["message"] = "Unexpected error. Try again later.";

    if(ECS_DEBUG)
    {
        $response["message"] = "Unexpected error. Try again later. Error: " . $e->getMessage();
    }
}

echo(json_encode($response));

die();
?>