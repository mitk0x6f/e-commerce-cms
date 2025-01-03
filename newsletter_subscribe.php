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

header("Content-Type: application/json");

Builder::suppress_errors();

// Default response

$response = ["success" => false, "message" => "Unexpected error."];

// Check if required field is set and not empty

if(!isset($_POST["email"]) || trim($_POST["email"]) === "")
{
    $response["message"] = "Invalid request.";

    if(ECS_DEBUG)
    {
        $response["message"] .= " Missing email.";
    }

    echo(json_encode($response));

    die();
}

try
{
    // TODO: improve security

    $name = htmlspecialchars(substr(trim((string) $_POST["name"]), 0, 50), ENT_QUOTES, "UTF-8");

    // Check if name is empty and set it to null if so

    $name = strlen($name) === 0 ? null : $name;

    $email = filter_var((string) $_POST["email"], FILTER_SANITIZE_EMAIL);

    // Check validity

    // TODO: improve code, so echo and die are not repeated for each error

    if(!is_null($name) && (strlen($name) > 0 &&strlen($name) < 2 || strlen($name) > 50))
    {
        $response["message"] = "Invalid name. Name must be between 2 and 50 characters long.";

        echo(json_encode($response));

        die();
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $response["message"] = "Invalid email address.";

        echo(json_encode($response));

        die();
    }

    // Add the new subscriber

    $stmt = Database::run(
        "INSERT INTO newsletter (
            name,
            email
        ) VALUES (
            :name,
            :email
        )
        ON DUPLICATE KEY UPDATE
            name = IFNULL(:name, name),
            subscribed = 1,
            last_updated = CURRENT_TIMESTAMP",
        [
            ":name" => $name,
            ":email" => $email
        ]
    );

    // Database query successful

    if($stmt)
    {
        // Set the AJAX response

        $response["success"] = true;
        $response["message"] = "Successfully subscribed to newsletter.";
    }
    else
    {
        // Database query failed

        $response["message"] = "Failed to subscribe to newsletter. Please try again later.";
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