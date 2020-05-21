<?php 

if (! function_exists('env')) {
    function env($name, $default = null)
    {
        if (isset($_ENV[$name]) && $_ENV[$name]) {
            return $_ENV[$name];
        }
        return $default;
    }
}

if (! function_exists('sort_by_name')) {
    function sort_by_name($a, $b)
    {
        return strtolower($a['name']) > strtolower($b['name']);
    }
}