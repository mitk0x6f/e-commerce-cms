<?php
class User
{
    // Check session stored data

    public static function get($userdata)
    {
        if(isset($_SESSION[$userdata]))
        {
            return $_SESSION[$userdata];
        }

        return null;
    }

    // Store or change data in the session

    public static function set($userdata, $value): void
    {
        $_SESSION[$userdata] = $value;
    }

    // Delete data from the session

    public static function unset($userdata): void
    {
        unset($_SESSION[$userdata]);
    }

    // Update user's shopping cart
    // TODO: non-signed-in users should be able to add items to temporary shopping cart. Now probably raises error due to the null $user_id.

    public static function update_shopping_cart($user_id = null): void
    {
        // Check if user has an active order

        $pending_order = Database::run("SELECT id FROM orders WHERE user_id = ? AND status = 1", [$user_id])->fetch(PDO::FETCH_ASSOC);

        // If user has an active order

        if(!$pending_order)
        {
            self::set("cart", []);
            self::set("cart_total_qty", 0);

            return;
        }

        // Get the pending order items

        $pending_order = Database::run(
            "SELECT order_items.article_id, order_items.quantity, order_items.price, order_items.discount, articles.title
            FROM order_items
            INNER JOIN articles ON order_items.article_id = articles.id
            WHERE order_items.order_id = ? AND order_items.status = 1 AND articles.status = 1",
            [$pending_order["id"]]
        )->fetchAll(PDO::FETCH_ASSOC);

        if($pending_order)
        {
            // Add the items to the cart

            $cart = [];
            $cart_total_qty = 0;

            foreach($pending_order as $item)
            {
                $cart[] = [
                    "article_id" => $item["article_id"],
                    "title" => $item["title"],
                    "quantity" => $item["quantity"],
                    "price" => $item["price"],
                    "discount" => $item["discount"]
                ];

                $cart_total_qty += $item["quantity"];
            }

            // Store the cart items in the session

            self::set("cart", $cart);
            self::set("cart_total_qty", $cart_total_qty);
        }
    }

    // Check if email and phone numbers are available

    public static function check_available($email, $phone_number): bool
    {
        // TODO: pull table name from configuration for ALL queries in ALL files

        try
        {
            $result = Database::run("SELECT COUNT(id) FROM users WHERE email = :email OR phone_number = :phone_number LIMIT 1", [
                ":email" => $email,
                ":phone_number" => $phone_number
            ]);

            // IF $result == 0                => email and phone number are available
            //   !$result->fetchColumn()      => true
            //   $result->fetchColumn() == 0  => true
            // ELSE
            //   !$result->fetchColumn()      => false
            //   $result->fetchColumn() == 0  => false

            return $result->fetchColumn() == 0;
        }
        catch(PDOException)
        {
            return false;
        }
    }

    // Check if email and password are correct

    public static function check_user($email, #[SensitiveParam] $password): bool
    {
        // TODO: pull table name from configuration

        $result = Database::run("SELECT password FROM users WHERE email = :email LIMIT 1",
            [":email" => $email]
        );

        $result = $result->fetch(PDO::FETCH_ASSOC);

        if($result)
        {
            return password_verify($password, $result["password"]);
        }

        return false;
    }

    // User sign in

    public static function sign_in($email, #[SensitiveParam] $password, $redirect = true): void
    {
        // TODO: pull table name from configuration

        $result = Database::run("SELECT * FROM users WHERE email = :email", [
            ":email" => $email,
        ]);

        if($result)
        {
            // Get the result

            $result = $result->fetch(PDO::FETCH_ASSOC);

            // If user is found and password is correct

            if($result && password_verify($password, $result["password"]))
            {
                // Store user data

                foreach($result as $column => $value)
                {
                    // Do not save the password

                    if($column == "password")
                    {
                        continue;
                    }

                    // Save other user information

                    self::set($column, $value);
                }

                self::update_shopping_cart(self::get("id"));

                $user_reviews = Database::run("SELECT COUNT(*) FROM reviews WHERE user_id = ?", [self::get("id")]);

                if($user_reviews)
                {
                    self::set("reviews_count", $user_reviews->fetch(PDO::FETCH_NUM)[0]);
                }

                self::set("signed_in", true);

                // Redirect after successful sign in (if redirect is not set to false)

                if($redirect)
                {
                    header("Location: /home");
                    die();
                }
            }
        }
    }

    // User sign up

    public static function sign_up(#[SensitiveParam] $userinfo): bool
    {
        // TODO: pull table name from configuration

        $result = Database::run("INSERT INTO users(email, password, first_name, last_name, phone_number, birth_date, country, gender)
        VALUES(:email, :password, :first_name, :last_name, :phone_number, :birth_date, :country, :gender)", $userinfo);

        if(is_string($result))
        {
            Debug::add("An error occurred while signing up. " . $result);

            return false;
        }

        return true;
    }

    // User sign out

    public static function sign_out(): void
    {
        // Unset $_SESSION

        session_unset();
        session_destroy();

        header("Location: /home");
        die();
    }
}
?>