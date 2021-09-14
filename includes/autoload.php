<?php
/**
 *  Autoload classes based on a 1:1 mapping from namespace to directory structure.
 *  @author    Latitude Finance
 *  @copyright Latitude Finance
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

spl_autoload_register(function ($className) {
    # Usually I would just concatenate directly to $file variable below
    $ds = DIRECTORY_SEPARATOR;
    $dirs = [
        dirname(__FILE__) . $ds . "libs",
        dirname(__FILE__) . $ds . "gateways",
    ];

    foreach ($dirs as $_dir) {
        // replace namespace separator with directory separator (prolly not required)
        $className = str_replace('\\', $ds, $className);
        // get full name of file containing the required class
        $file = "{$_dir}{$ds}{$className}.php";
        // get file if it is readable
        if (is_readable($file)) {
            require_once $file;
        }
    }
});
