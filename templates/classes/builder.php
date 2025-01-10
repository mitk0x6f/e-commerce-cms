<?php
class Builder
{
    /*---------------------------------------------
        TODO:
            1. Create caching system
            2. Rename all classes to PascalCase
            3. Rename all functions to camelCase
            4. Rename all variables to shake_case
    ---------------------------------------------*/

    // Variable to store the template file contents

    private static string $template = "";

    // Variable to track the current decoder position

    private static int $cursor = 0;

    // Keep track of the last match

    private static array $last_match = [];

    // List, where variable data is added for the templating

    private static array $data = [];

    // Buffer for non-template code, before it gets added as a token

    private static string $non_template_code_buffer = "";

    // Add new data to the template variables list

    public static function add_data(string $key, string|array $new_data): void
    {
        self::$data[$key] = $new_data;
    }

    public static function get_data(string $key)
    {
        if(isset(self::$data[$key]))
        {
            return self::$data[$key];
        }

        return false;
    }

    // Decode .json file for translation
    // USAGE: {{ VAR translate.hello ~ }} {{ VAR translate.world ~ }}

    public static function set_lang(string $lang = "en"): void
    {
        // TODO: instead of getting the contents of one file with all lanuages,
        // Divide the languages in separate files, so less file size is loaded

        // {{ VAR USER.language ~ }} = en
        // {{ VAR site.language ~ }} = English

        self::$data["translate"] = json_decode(file_get_contents("./templates/dict.json"), true)[$lang];
        self::$data["site"]["language"] = self::$data["translate"][strtolower($lang)];
    }

    private static function match_include(): bool
    {
        $include_start = "{{ INCLUDE ";
        $pos_after_include_start = self::$cursor + strlen($include_start);

        if(substr(self::$template, self::$cursor, strlen($include_start)) === $include_start)
        {
            $include_end = " # }}";
            $include_end_pos = strpos(self::$template, $include_end, $pos_after_include_start);

            if(!empty($include_end_pos))
            {
                // Handle non-template code

                self::seal_plain_text();

                $include_content = substr(
                    self::$template,
                    $pos_after_include_start ,
                    $include_end_pos - $pos_after_include_start
                );

                self::$last_match = ["TYPE" => "INCLUDE", "FILE" => trim($include_content)];
                self::$cursor = $pos_after_include_start + strlen($include_content) + strlen($include_end);

                return true;
            }
        }

        return false;
    }

    // Match IF block

    private static function match_if(): bool
    {
        // Match the begining of IF block

        $if_start = "{{ IF ";

        // Move the cursor in the begining of the condition

        $pos_after_if_start = self::$cursor + strlen($if_start);

        if(substr(self::$template, self::$cursor, strlen($if_start)) === $if_start)
        {
            // Match the end of IF block

            $if_end = " ? }}";

            // Move the cursor in the end of the condition

            $if_end_pos = strpos(self::$template, $if_end, $pos_after_if_start);

            // Set the token for the IF block

            if(!empty($if_end_pos))
            {
                // Handle non-template code

                self::seal_plain_text();

                // Extract the condition

                $if_content = substr(self::$template, $pos_after_if_start, $if_end_pos - $pos_after_if_start);

                // Move the cursor to the end of the IF block

                self::$last_match = ["TYPE" => "IF", "CONDITION" => trim($if_content)];
                self::$cursor = $pos_after_if_start + strlen($if_content) + strlen($if_end);

                return true;
            }
        }

        return false;
    }

    private static function match_else(): bool
    {
        $else = "{{ ELSE }}";

        if(trim(substr(self::$template, self::$cursor, strlen($else))) === $else)
        {
            // Handle non-template code

            self::seal_plain_text();

            self::$last_match = ["TYPE" => "ELSE"];

            self::$cursor += strlen($else);

            return true;
        }

        return false;
    }

    private static function match_end_if(): bool
    {
        $end_if = "{{ END IF }}";

        if(trim(substr(self::$template, self::$cursor, strlen($end_if))) === $end_if)
        {
            // Handle non-template code

            self::seal_plain_text();

            self::$last_match = ["TYPE" => "END_IF"];

            self::$cursor += strlen($end_if);

            return true;
        }

        return false;
    }

    private static function match_variable(): bool
    {
        $var_start = "{{ VAR ";
        $pos_after_var_start = self::$cursor + strlen($var_start);

        if(substr(self::$template, self::$cursor, strlen($var_start)) === $var_start)
        {
            // Find the variable end position

            $var_end = " ~ }}";
            $var_end_pos = strpos(self::$template, $var_end, $pos_after_var_start);

            if(!empty($var_end_pos))
            {
                // Handle non-template code

                self::seal_plain_text();

                // Extract variable content between {{ VAR and }}

                $var_content = substr(
                    self::$template,
                    $pos_after_var_start,
                    $var_end_pos - $pos_after_var_start
                );

                self::$cursor = $var_end_pos + strlen($var_end);

                // Match any word character (letter, number, underscore)

                if(preg_match("/^[\w.]+$/", trim($var_content)))
                {
                    self::$last_match = ["TYPE" => "VARIABLE", "VARIABLE" => trim($var_content)];

                    return true;
                }
                else
                {
                    Debug::add("Trying to access unsupported template variable: " . trim($var_content));
                }
            }
        }

        return false;
    }

    private static function match_for(): bool
    {
        // Match the begining of FOR LOOP block

        $for_start = "{{ FOR ";

        // Move the cursor in the begining

        $pos_after_for_start = self::$cursor + strlen($for_start);

        if(substr(self::$template, self::$cursor, strlen($for_start)) === $for_start)
        {
            // Match the end of FOR block

            $for_end = " * }}";

            // Move the cursor in the end of the condition

            $for_end_pos = strpos(self::$template, $for_end, $pos_after_for_start);

            // Set the token for the FOR block

            if(!empty($for_end_pos))
            {
                // Handle non-template code

                self::seal_plain_text();

                // Extract the condition

                $for_condition = substr(self::$template, $pos_after_for_start, $for_end_pos - $pos_after_for_start);

                // Move the cursor to the end of the FOR block

                self::$last_match = ["TYPE" => "FOR", "CONDITION" => trim($for_condition)];
                self::$cursor = $pos_after_for_start + strlen($for_condition) + strlen($for_end);

                return true;
            }
        }

        return false;
    }

    private static function match_end_for(): bool
    {
        $end_for = "{{ END FOR }}";

        if(trim(substr(self::$template, self::$cursor, strlen($end_for))) === $end_for)
        {
            // Handle non-template code

            self::seal_plain_text();

            self::$last_match = ["TYPE" => "END_FOR"];

            self::$cursor += strlen($end_for);

            return true;
        }

        return false;
    }

    // Usage:

    // For public comment (seen in browser's code viewer) use regular <!-- comment block -->
    // For private comment (seen only in the source code) use {{ -- Comment block -- }} or {{ -- | Multi-line comment block | -- }}

    private static function match_comment(): bool
    {
        $comment_start = "{{ -- ";
        $pos_after_comment_start = self::$cursor + strlen($comment_start);

        if(substr(self::$template, self::$cursor, strlen($comment_start)) === $comment_start)
        {
            // Find the comment end position

            $comment_end = " -- }}";
            $comment_end_pos = strpos(self::$template, $comment_end, $pos_after_comment_start);

            if(!empty($comment_end_pos))
            {
                // Handle non-template code

                self::seal_plain_text();

                // Extract comment content between <!-- and -->

                $comment_content = substr(
                    self::$template,
                    $pos_after_comment_start,
                    $comment_end_pos - $pos_after_comment_start
                );

                self::$last_match = ["TYPE" => "COMMENT", "COMMENT" => trim($comment_content)];

                self::$cursor = $comment_end_pos + strlen($comment_end);

                return true;
            }
        }

        return false;
    }

    private static function match_plain_text(): bool
    {
        // Find where the next template block starts

        $next_token_start = strpos(self::$template, "{{ ", self::$cursor);

        if(empty($next_token_start))
        {
            // There are no more template blocks, so the remaining content is plain text

            $plain_text = substr(self::$template, self::$cursor);

            if(!empty(trim($plain_text)) || $plain_text === " ")
            {
                self::$last_match = ["TYPE" => "PLAIN_TEXT", "CONTENT" => $plain_text];
            }

            // Move the cursor to the end

            self::$cursor = strlen(self::$template);
        }
        else
        {
            // There's a template block ahead, create a plain text token

            $plain_text = substr(self::$template, self::$cursor, $next_token_start - self::$cursor);

            if(!empty(trim($plain_text)) || $plain_text === " ")
            {
                self::$last_match = ["TYPE" => "PLAIN_TEXT", "CONTENT" => $plain_text];
            }

            // Move the cursor to the next token start

            self::$cursor = $next_token_start;

            return true;
        }

        return false;
    }

    // Add the non-template code to the buffer

    private static function seal_plain_text(): bool
    {
        if(!empty(trim(self::$non_template_code_buffer)))
        {
            self::$last_match = ["TYPE" => "PLAIN_TEXT", "CONTENT" => trim(self::$non_template_code_buffer)];

            self::$non_template_code_buffer = "";

            return true;
        }

        return false;
    }

    // Evaluate the template IF block's condition

    private static function eval_condition($condition): bool
    {
        try
        {
            return eval("return ($condition);");
        }
        catch (Throwable $e)
        {
            Debug::add("IF condition parse error: " . $e->getMessage() . ". Condition: " . $condition);

            return false;
        }
    }

    private static function compile($tokens): void
    {
        // Output buffers for rendering of the template

        $token_stack = [];
        $current_state = "";
        $output_buffer = "";
        $final_output = "";

        foreach($tokens as $token)
        {
            if($token["TYPE"] === "INCLUDE")
            {
                // TODO: Fix a bug, when including a file, that does not exist.
                //       page structure breaks and error 404 appears.

                $output_buffer .= self::page($token["FILE"], true);
            }
            elseif($token["TYPE"] === "IF")
            {
                // Solves issue with IF block, that all code before the first IF is not rendered
                // TODO: further research is needed

                if(empty($token_stack))
                {
                    $final_output .= $output_buffer;

                    $output_buffer = "";
                }

                // Evaluate the condition and push the current state to the stack

                $parent_cond_met = empty($token_stack) || end($token_stack)["cond_met"];

                // Push the current state to the stack

                $token_stack[] = [
                    "type" => "IF",
                    "cond_met" => $parent_cond_met && self::eval_condition($token["CONDITION"]),
                    "processing" => true,
                    "output" => $output_buffer
                ];

                $output_buffer = "";
            }
            elseif($token["TYPE"] === "ELSE")
            {
                if(!empty($token_stack))
                {
                    // Set the current state to the previous state

                    $current_state = array_pop($token_stack);

                    if($current_state["type"] === "IF" && $current_state["processing"])
                    {
                        if($current_state["cond_met"])
                        {
                            // Currently processing block's condition was met, thus append the content to the output

                            $current_state["output"] .= $output_buffer;
                        }

                        // Continue without appending any output

                        $current_state["cond_met"] = !$current_state["cond_met"];
                        $current_state["processing"] = false;

                        // Reset the output buffer

                        $output_buffer = "";
                    }

                    // Add the modified state back to the stack

                    $token_stack[] = $current_state;
                }
            }
            elseif($token["TYPE"] === "END_IF")
            {
                if(!empty($token_stack))
                {
                    // Set the current state to the previous state

                    $current_state = array_pop($token_stack);

                    if($current_state["type"] === "IF")
                    {
                        if($current_state["cond_met"])
                        {
                            // Currently processing block's condition was met, thus append the content to the output

                            $current_state["output"] .= $output_buffer;
                        }

                        $output_buffer = $current_state["output"];
                    }
                    else
                    {
                        $token_stack[] = $current_state;
                    }
                }
            }
            elseif($token["TYPE"] === "VARIABLE")
            {
                // Split the variable to [0] Key and [1] Value.
                // Examples:
                // {{ VAR USER.first_name ~ }} -> string: $_SESSION["first_name"] -> "Dimitar"
                // {{ VAR author ~ }} -> string: $data["author"] -> "Dimitar Dimitrov"
                // {{ VAR site ~ }} -> array: $data["site"] -> ["title" => "E-commerce-Title", "name" => "E-commerce-Name"]
                // {{ VAR site.title ~ }} -> string: $data["site"]["title"] -> "E-commerce-Title"
                // {{ VAR translate.??? ~ }} -> string: $data["translate"]["???"] -> dict.json["??"]["???"]

                $token["VARIABLE"] = explode(".", $token["VARIABLE"]);

                // Pointers for better code readability

                $tkn_parent = &$token["VARIABLE"][0];
                $tkn_child = &$token["VARIABLE"][1] ?? null;

                if($tkn_parent === "USER")
                {
                    if(isset($_SESSION[$tkn_child]))
                    {
                        if(!is_iterable($_SESSION[$tkn_child]))
                        {
                            $output_buffer .= $_SESSION[$tkn_child];
                        }
                        else
                        {
                            Debug::add("Trying to access array (" . $tkn_child . ") as string. For loop must be used.");
                        }
                    }
                    else if(User::get("signed_in"))
                    {
                        $output_buffer .= "UNDEFINED";

                        Debug::add("Trying to access non-existing user variable: " . $tkn_child . ". Temporary using UNDEFINED as a placeholder.");
                    }
                }
                // Check if the variable is nasted
                elseif(isset(self::$data[$tkn_parent]) && is_null($tkn_child)) // Not nasted
                {
                    // Check if the variable is array or string

                    if(!is_array(self::$data[$tkn_parent])) // String
                    {
                        $output_buffer .= self::$data[$tkn_parent];
                    }
                    else // Array
                    {
                        Debug::add("Trying to access array (" . $tkn_parent . ") as string. For loop must be used.");
                    }
                }
                elseif(isset(self::$data[$tkn_parent][$tkn_child])) // Nasted
                {
                    // Examples are the {{ VAR site.??? ~ }} and {{ VAR translate.??? ~ }}

                    $output_buffer .= self::$data[$tkn_parent][$tkn_child];
                }
                else
                {
                    // TODO: log missing template variables, so below code can be removed as translation will be immediately implemented

                    if($tkn_parent === "translate")
                    {
                        // TODO: Develop a translation system.
                        // $output_buffer .= Translate::get($tkn_child);
                        // OR
                        // $output_buffer .= Builder::translate($tkn_child);

                        $output_buffer .= "?";

                        Debug::add("Missing translation for " . $tkn_child);
                    }
                    else
                    {
                        Debug::add("Trying to access non-existing template variable: " . $tkn_parent . "." . $tkn_child);
                    }

                    // Debug::add("Trying to access non-existing template variable: " . $tkn_parent . "." . $tkn_child);
                }
            }
            elseif($token["TYPE"] === "FOR")
            {
                // Usage:

                // {{ FOR item in array }}
                //     {{* key *}}
                // {{ END FOR }}

                // WIP NEW

                if(empty($token_stack) || (end($token_stack)["type"] === "IF" && end($token_stack)["cond_met"]))
                {
                    $final_output .= $output_buffer;
                }

                $output_buffer = "";

                // Make sure, the condition of the FOR LOOP is valid

                if(strpos($token["CONDITION"], " in ") !== false)
                {
                    // Split the condition to item and array

                    list($tkn_item, $tkn_array) = explode(" in ", $token["CONDITION"], 2);

                    $tkn_item = trim($tkn_item);
                    $tkn_array = trim($tkn_array);

                    $token_stack[] = [
                        "type" => "FOR",
                        "item" => $tkn_item,
                        "array" => $tkn_array
                    ];

                    // Check if the array is actually iterable
                    // TODO: fix this workaround

                    if(isset(self::$data[$tkn_array]) && !is_iterable(self::$data[$tkn_array]))
                    {
                        Debug::add("Trying to iterate on non array: " . $tkn_array);
                    }
                }
                else
                {
                    Debug::add("FOR condition (" . $token["CONDITION"] . ") parse error");
                }
            }
            elseif($token["TYPE"] === "END_FOR")
            {
                // Keep original for loop structure

                $ob_raw = $output_buffer;

                /*
                Split the condition to [0] Key and [1] Value

                Examples:

                {{ FOR item in article_info * }}
                -> array: self::$data["article_info"]
                -> ["id" => 2, "shop_slug" => "dominion", ... , "breadcrumb" => [0 => ["name" => "home", "parent" => null], 1 => ["name" => "mobile_phones", "parent" => 0]]]

                {{ FOR item in article_info.breadcrumb * }}
                -> array: self::$data["article_info"]["breadcrumb"]
                -> [0 => ["name" => "home", "parent" => null], 1 => ["name" => "mobile_phones", "parent" => 0]]

                Access by key {{* key *}}
                If translation exists {{* translate.key *}}
                */

                // Make sure the token stack is not empty as it is required for IF blocks and FOR loops to function

                if(empty($token_stack))
                {
                    Debug::add("Error ending of FOR loop. No token found.");

                    continue;
                }

                $current_state = array_pop($token_stack);

                // Current token type must be FOR

                if($current_state["type"] !== "FOR")
                {
                    // Return the token to the stack

                    $token_stack[] = $current_state;

                    continue;
                }

                if(!empty($token_stack))
                {
                    $next_state = array_pop($token_stack);

                    if($next_state["type"] === "IF" && !$next_state["cond_met"])
                    {
                        // Return the token to the stack

                        $token_stack[] = $next_state;

                        $output_buffer = "";

                        continue;
                    }

                    $token_stack[] = $next_state;
                }

                $current_state["array"] = explode(".", $current_state["array"]);

                $tkn_parent = &$current_state["array"][0];
                $tkn_child = &$current_state["array"][1] ?? null;

                if(
                    isset(self::$data[$tkn_parent]) && is_null($tkn_child) && is_iterable(self::$data[$tkn_parent]) && self::$data[$tkn_parent] !== [] // Not nasted
                    || isset(self::$data[$tkn_parent][$tkn_child]) && !is_null($tkn_child) // Nasted
                )
                {
                    $iterable = is_null($tkn_child) ? self::$data[$tkn_parent] : self::$data[$tkn_parent][$tkn_child];

                    foreach($iterable as $id => $result)
                    {
                        if(!is_iterable($result))
                        {
                            continue;
                        }

                        $replacements = [];

                        // Add all variables

                        foreach($result as $key => $value)
                        {
                            if(is_iterable($value))
                            {
                                Debug::add("Unsupported use of FOR LOOP: data->" . $tkn_parent . "->" . $key);

                                continue;
                            }

                            $replacements["{{* " . $key . " *}}"] = $value;

                            // If there is translation available, add the possibility to use it
                            // TODO: Optimize/rework below condition. Might cause major performance issues upon big FOR loops.

                            if(!is_numeric($value) && $value !== null && !strtotime($value) && isset(self::$data["translate"][$value]))
                            {
                                $replacements["{{* translate." . $key . " *}}"] = self::$data["translate"][$value];
                            }
                        }

                        // Replace all variables in the loop structure

                        $final_output .= str_replace(array_keys($replacements), array_values($replacements), $ob_raw);
                    }
                }
                // Handle website search and seller profile
                // TODO: fix this workaround
                elseif(isset(self::$data[$tkn_parent]) && (self::$data[$tkn_parent] == "No results found." || self::$data[$tkn_parent] == "No articles for sale found."))
                {
                    $final_output .= self::$data[$tkn_parent];
                }
                elseif(isset(self::$data[$tkn_parent]) && $tkn_parent === "cart" && self::$data[$tkn_parent] === []) // Handle empty shopping cart
                {
                    if(isset(self::$data["translate"]["cart_is_empty"]))
                    {
                        $final_output .= self::$data["translate"]["cart_is_empty"];
                    }
                    else
                    {
                        $final_output .= "Shopping cart is empty";

                        Debug::add("Missing translation: cart_is_empty");
                    }
                }
                elseif(isset(self::$data[$tkn_parent]) && self::$data[$tkn_parent] === [])
                {
                    if(ECS_DEBUG)
                    {
                        $final_output .= "Empty array data->" . $tkn_parent;
                    }

                    Debug::add("Trying to access empty array data->" . $tkn_parent);
                }
                elseif(!isset(self::$data[$tkn_parent]))
                {
                    Debug::add("Trying to access non-existant array data->" . $tkn_parent);
                }
                else
                {
                    Debug::add("Error in FOR loop: data->" . implode(" ", $tkn_array));
                }

                $output_buffer = "";
            }
            elseif($token["TYPE"] === "COMMENT")
            {
                // PASS - do nothing
            }
            elseif($token["TYPE"] === "PLAIN_TEXT")
            {
                $output_buffer .= $token["CONTENT"];
            }
            else
            {
                // $output_buffer .= self::$non_template_code_buffer;
                $output_buffer .= ("<pre style='padding:8px;border:3px solid pink;'>" . var_export($token) . " <|>" . self::$non_template_code_buffer . "</pre>");
            }
        }

        $final_output .= $output_buffer;

        print($final_output);
    }

    private static function tokenize(): array
    {
        // List to store the decoded tokens (template blocks)

        $tokens = [];

        // Reset decoder position

        self::$cursor = 0;

        while(self::$cursor < strlen(self::$template))
        {
            // Reset last match

            self::$last_match = [];

            // Keep track of cursor, in case we need to skip a step

            $last_cursor = self::$cursor;

            if(self::match_include())
            {
                // Handle include block
            }
            elseif(self::match_if())
            {
                // Handle if block
            }
            elseif(self::match_else())
            {
                // Handle else block
            }
            elseif(self::match_end_if())
            {
                // Handle end if block
            }
            elseif(self::match_variable())
            {
                // Handle variable block
            }
            elseif(self::match_for())
            {
                // Handle for loop
            }
            elseif(self::match_end_for())
            {
                // Handle end for loop
            }
            elseif(self::match_comment())
            {
                // Handle html comment
            }
            elseif(self::match_plain_text())
            {
                // Handle non-template code
            }
            else
            {
                // Match non-template code

                self::$non_template_code_buffer .= substr(self::$template, self::$cursor, 1);

                // Add the last non-template code to the token list (end of template file)

                if(self::$cursor == strlen(self::$template) - 1)
                {
                    self::seal_plain_text();
                }
            }

            // If there was a match above, add it to the token list

            if(!empty(self::$last_match))
            {
                $tokens[] = self::$last_match;
            }

            // Avoid stucking in infinite while loop

            if(self::$cursor === $last_cursor)
            {
                self::$cursor += 1;
            }
        }

        return $tokens;
    }

    // Load template file, set replacement data, compile and print (a.k.a. render)

    public static function page(string $page = "home", $recursive = false) // TODO: : VOID | STRING
    {
        // TODO: add absolute url instead
        // Added @ to supress risen errors, later tackled in if statement

        self::$template = @file_get_contents("./templates/default/". $page .".html");

        if(empty(self::$template))
        {
            self::$template = file_get_contents("./templates/default/404.html");
        }

        // If the function has been called recursively, use output buffering and exit it

        if($recursive)
        {
            ob_start();

            self::compile(self::tokenize());

            return ob_get_clean();
        }

        // Track the page name

        self::add_data("page_name", $page);

        // Set website display language
        // Using ISO 639-3

        self::set_lang(User::get("language"));

        // Set website display currency

        // TODO: fix below to be used like self::add_data("site.currency", User::get("currency")); or similar

        if(!empty(User::get("currency")))
        {
            self::$data["site"]["currency"] = User::get("currency");
        }

        // Add cart data from session to template

        self::add_data("cart", User::get("cart"));

        // Rendering sequence (Tokenization and rendering)

        self::compile(self::tokenize());

        // TODO: move below if later found to be bad

        Database::disconnect();
    }


    /////////////////////////////////////////
    //                 WIP                 //
    //  DO NOT SHOW ANY PHP ERRORS FOR UX  //
    /////////////////////////////////////////

    public static function suppress_errors()
    {
        $response = ["success" => false, "message" => "An error occurred. Please contact us!"];

        // Custom error handler function

        set_error_handler(function () use (&$response){
            echo(json_encode($response));

            die();
        });

        // Set exception handler (for uncaught exceptions)

        set_exception_handler(function ($exception) use (&$response){
            if(ECS_DEBUG)
            {
                $response["message"] = $exception->getMessage();
            }

            echo(json_encode($response));

            die();
        });

        // Suppress default error output

        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
    }
}
?>