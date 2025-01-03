<?php
// Cut off the script if the request method is not POST

if($_SERVER["REQUEST_METHOD"] !== "POST")
{
    // Show sign up page

    header("Location: /settings");

    die();
}

// Add the core functionality of the application

require_once("./templates/configuration.php");

header("Content-Type: application/json");

Builder::suppress_errors();

// Default response

$response = ["success" => false, "field" => false, "by_you" => false, "message" => "Unexpected error.", "requires_reload" => false];

// Make sure the user is signed in

if(!User::get("signed_in"))
{
    $response["message"] = "User not signed in.";

    echo(json_encode($response));

    die();
}

// Strip the domain from the slug if submitting user is seller
// TODO: make it more *intelligent*

if(isset($_POST["save-profile"]) && isset($_POST["slug"]))
{
    $_POST["slug"] = substr($_POST["slug"], strlen("http://website.com/profile/"));
}

// Strip the + from the phone_number
// TODO: make it more *intelligent*

if(isset($_POST["save-account"]))
{
    $_POST["phone_number"] = substr($_POST["phone_number"], strlen("+"));
}

// Check if none actual settings page has been submitted

if(!isset($_POST["save-profile"]) && !isset($_POST["save-account"]))
{
    $response["message"] = "Invalid request.";

    echo(json_encode($response));

    die();
}

// List of allowed settings from POST

$allowed = [
    "first_name", "last_name", "slug", "shop_name",
    "email", "phone_number", "birth_date", "country", "gender", "language"
];

$uniques = ["slug", "shop_name", "email", "phone_number"];

// Current approach using foreach and individual column update queries is not efficient!
// TODO: add all, that must be changed to an array and then post a single query

foreach($_POST as $key => $value)
{
    // Sanitize the data

    $key = htmlspecialchars(trim((string) $key), ENT_QUOTES, "UTF-8");
    $value = htmlspecialchars(trim((string) $value), ENT_QUOTES, "UTF-8");

    // Prepare for further responses

    $response["field"] = $key;

    // Skip if the setting value is not allowed to be changed, is invalid or is not changed.

    if(!in_array($key, $allowed) || strlen($value) < 1)
    {
        continue;
    }

    // Check if user is already using the same data

    if(in_array($key, $uniques) && User::get($key) && $value == User::get($key))
    {
        // Add fields to the response

        $response["by_you"] = true;
        $response["message"] = "The " . $key . " is already taken by you.";

        // Cut off the script

        echo(json_encode($response));

        die();
    }

    try
    {
        // Set variable, so we don't fetch it multiple times

        $user_id = htmlspecialchars(trim((int) User::get("id")), ENT_QUOTES, "UTF-8");

        // Check if there is already user with same data

        if(in_array($key, $uniques))
        {
            $exists_by_user = Database::run(
                "SELECT
                    id
                FROM
                    users
                WHERE
                    {$key} = :value
                    AND id != :id",
                [
                    ":value" => $value,
                    ":id" => $user_id
                ]
            )->fetchColumn();

            // Check if value is already taken by another user.

            if($exists_by_user)
            {
                // Add field to the response

                $response["message"] = "The " . $key . " is already taken.";

                // Cut off the script

                echo(json_encode($response));

                die();
            }
        }

        // Update the database

        $update = Database::run(
            "UPDATE
                users
            SET
                $key = :value
            WHERE
                id = :id",
            [
                ":value" => $value,
                ":id" => $user_id
            ]
        );

        // Update the session information if the database update was successful

        if($update)
        {
            User::set($key, $value);

            $response["success"] = true;
            $response["message"] = "Settings updated successfully.";
            $response["requires_reload"] = true;
        }
    }
    catch(Exception $e)
    {
        $response["message"] = "Unexpected error. Try again later.";

        if(ECS_DEBUG)
        {
            $response["message"] += " " . $e->getMessage();
        }
    }
}

echo(json_encode($response));

die();