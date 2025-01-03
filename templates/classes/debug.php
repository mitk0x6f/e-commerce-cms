<?php
class Debug
{
    // Set debug list name

    private static $global_name = "ECS_DEBUG_LIST";

    // Initiate the debug engine

    public static function enable($enable = false): void
    {
        define("ECS_DEBUG", $enable);

        if(ECS_DEBUG)
        {
            // TODO: add the debug messages to a log file

            $GLOBALS[self::$global_name] = (array) null;
        }
    }

    // Add new error messages to the debug list

    public static function add($error = "UNKNOWN"): void
    {
        if(ECS_DEBUG)
        {
            $GLOBALS[self::$global_name][] = $error;
        }
    }

    // Show error message / messages from the debug list
    // TODO: make debug priorities
    //          ALL - shows all messages
    //          CRITICAL - shows only critical messages
    //          WARNING - shows only warning messages

    public static function show(): void
    {
        if(ECS_DEBUG && !is_null(self::$global_name) && !empty($GLOBALS["ECS_DEBUG_LIST"]))
        {
            if(is_array($GLOBALS[self::$global_name]))
            {
                // TODO: show the debug as a nice modal instead

                print("<footer style='margin-top: 0;background-color: #F00;'><section>");
                print("Debug messages (" . count($GLOBALS[self::$global_name]) . ")<br>");

                foreach($GLOBALS[self::$global_name] as $key => $msg)
                {
                    print("[" . $key + 1 . "]: $msg<br>");
                }

                print("</section></footer>");
            }
            else
            {
                print($GLOBALS[self::$global_name]);
            }
        }
    }
}
?>