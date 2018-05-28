<?php
/*
 * This file is part of the Database package.
 *
 */

/*
 * Load and register Autoloader
 */
if (!class_exists('Database_Autoloader')) {
    require dirname(__FILE__) . '/Autoloader.php';
}
Database_Autoloader::register(true);
