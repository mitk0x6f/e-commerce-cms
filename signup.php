<?php
// Add the core functionality of the application

require_once("./templates/configuration.php");

// Cut off the script if the request method is not POST

if($_SERVER["REQUEST_METHOD"] !== "POST")
{
    // Show sign up page

    Builder::page("signup");

    die();
}

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

// Check if every field required was submitted

$required_parameters = ["email", "password", "first_name", "last_name", "phone_number", "birth_date", "country", "gender"];

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

// Shield for possible attacks

$user_info = [
    "email" => filter_var($_POST["email"], FILTER_SANITIZE_EMAIL),
    "password" => $_POST["password"],
    "first_name" => preg_replace("/[^a-zA-Zа-яА-Я]+/", "", htmlentities($_POST["first_name"])),
    "last_name" => preg_replace("/[^a-zA-Zа-яА-Я]+/", "", htmlentities($_POST["last_name"])),
    "phone_number" => preg_replace("/[^0-9]/", "", filter_var(htmlentities($_POST["phone_number"]), FILTER_SANITIZE_NUMBER_INT)),
    "birth_date" => preg_replace("/[^0-9-]+/", "", htmlentities($_POST["birth_date"])),
    "country" => preg_replace("/(?:[^A-Z]){3}/", "", htmlentities($_POST["country"])),
    "gender" => filter_var(htmlentities($_POST["gender"]), FILTER_SANITIZE_NUMBER_INT)
];

$sign_up_errors = [];

// Check if every submitted field is also filled

if(empty($user_info["email"]) || !filter_var($user_info["email"], FILTER_VALIDATE_EMAIL))
{
    $sign_up_errors[] = "Email must be filled correctly";
}

// Check if user password includes 1 upper case letter, 1 lower case letter and 1 number (also min 8 chars)

if(empty($user_info["password"]) || !preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,256}$/", $user_info["password"]))
{
    $sign_up_errors[] = "Password must be filled correctly";
}
else
{
    // Hash the password to replace the raw one

    $user_info["password"] = password_hash($user_info["password"], PASSWORD_BCRYPT);
}

// Check if names are atleast 2 letters long each

if(empty($user_info["first_name"]) || !preg_match("/[a-zA-Zа-яА-Я]{2,}/", $user_info["first_name"]))
{
    $sign_up_errors[] = "Given name must be filled correctly";
}

if(empty($user_info["last_name"]) || !preg_match("/[a-zA-Zа-яА-Я]{2,}/", $user_info["last_name"]))
{
    $sign_up_errors[] = "Family name must be filled correctly";
}

if(empty($user_info["phone_number"]) || !preg_match("/[0-9]{2,}/", $user_info["phone_number"]))
{
    $sign_up_errors[] = "Phone number must be filled correctly";
}

if(empty($user_info["birth_date"]) || !preg_match("/(?:[0-9]{4})-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12][0-9]|3[01])/", $user_info["birth_date"]))
{
    $sign_up_errors[] = "Birth date must be filled correctly";
}

// According to ISO 3166-1 alpha-3 codes

if(empty($user_info["country"]) || !preg_match("/^(?:[A-Z]){3}$/", $user_info["country"]))
{
    $sign_up_errors[] = "Country must be selected correctly";
}

// Later add $user_info["gender"] max value
// This will be possible when all possible genders are stored in database

if(empty($user_info["gender"]) || !is_numeric($user_info["gender"]) || $user_info["gender"] < 1)
{
    $sign_up_errors[] = "Gender must be selected correctly";
}

if(!User::check_available($user_info["email"], $user_info["phone_number"]))
{
    // TODO: if email or/and phone number is/are already taken,
    // try to sign in the user with this email and the provided password

    $sign_up_errors[] = "Email or/and phone number is/are already taken";
}

if($sign_up_errors)
{
    $response["message"] = $sign_up_errors;
}
else
{
    // Successfully filled sign up form

    // Try to sign up the user

    if(User::sign_up($user_info))
    {
        $response["success"] = true;
        $response["message"] = "Successfully signed up.";
    }
    else
    {
        $response["message"] = "Failed to sign up. Please try again later.";
    }
}

echo(json_encode($response));

die();
?>