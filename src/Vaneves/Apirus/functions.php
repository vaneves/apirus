<?php 

if (! function_exists('env')) {
    function env($name, $default = null)
    {
        if (isset($_ENV[$name])) {
            return $_ENV[$name];
        }
        return $default;
    }
}