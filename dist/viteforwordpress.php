<?php
    /**
     * Plugin Name: 1 Trick Pony - Vite for WordPress
     * Description: Handles HMR in WordPress.
     * Version: 1.0.0
     * Author: 1 Trick Pony
     * Author URI: https://1trickpony.com
     * License: MIT
     */

    // Load the Composer autoloader
    require_once __DIR__ . '/vendor/autoload.php';

    // Main class for the plugin
    use MikeMcCarron\ViteForWordPress\Plugin;

    if (class_exists('MikeMcCarron\\ViteForWordPress\\Plugin')) {
        // Initialize the plugin
        $plugin = new Plugin();
        $plugin->init();
    }
?>