<?php
//   /home/nuov2ituldev/public_html/autoloader.php

// let's be honest, I didn't write this.  This autoloader was pulled directly from php.net manual in the section on
// namespacing.

function loadClass($className)
{
    $fileName  = '';
    $namespace = '';

    // Sets the include path as the same directory as this file.
    $includePath = dirname(__FILE__).DIRECTORY_SEPARATOR;

    if (false !== ($lastNsPos = strripos($className, '\\'))) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    $fullFileName = $includePath . DIRECTORY_SEPARATOR . $fileName;

    if (file_exists($fullFileName)) {
        require_once $fullFileName;
    } else {
        echo "Class $className does not exist.\n\n";
        echo "looking at $fullFileName\n++++++\n";
    }
}
spl_autoload_register('loadClass'); // Registers the autoloader