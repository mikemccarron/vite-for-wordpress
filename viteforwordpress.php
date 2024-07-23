<?php
    /**
     * Plugin Name: Vite for WordPress
     * Description: Required for use with ViteJS MHR and it's compiled manifest.json
     * Version: 1.0.0
     * Author: 1 Trick Pony
     * Author URI: https://1trickpony.com
     * License: MIT
     */
    use Roots\WPConfig\Config;

    function injectViteHMR() {
        $wpHome = Config::get('WP_HOME');
        $viteEntry = Config::get('VITE_ENTRY');
        $viteEntryDevPort = Config::get('VITE_ENTRY_DEVPORT');

        $isDevelopment = WP_ENV; // This is set in lumberjack config

         if (!$wpHome || !$viteEntry) {
            echo '<!-- Missing WP_HOME or VITE_ENTRY configuration -->';
            return;
        }

        $cleanUrl = stripPort( $wpHome );

        if ($isDevelopment=="development") {

            if (!$viteEntryDevPort) {
                echo '<!-- VITE_ENTRY_DEVPORT is not set -->';
                return;
            }

            $viteServer = @fsockopen('localhost', $viteEntryDevPort);

            if ($viteServer) {
                if ($cleanUrl && $viteEntry) {
                    $root = $cleanUrl . ':' . $viteEntryDevPort;
                    $entry = $root . '/' . $viteEntry;
                }

                fclose($viteServer);
                // Inject Vite HMR scripts
                echo '<script type="module" src="'. $root .'/@vite/client"></script>';

                if ($entry) {
                    echo '<script type="module" src="'. $entry .'"></script>';
                }
            } else {
                echo '<!-- Vite development server is not running -->';
            }
        }
        elseif($isDevelopment=="production") {
            // Production mode: include your built assets
            echo '<link rel="stylesheet" href="/path/to/your/compiled-css-file.css">';
            echo '<script type="module" src="/path/to/your/compiled-js-file.js"></script>';
        }
        else{
            echo '<!-- Vite: An invalid development value provided. -->';
        }
    }

    function stripPort($url) {
        if (is_null($url) || $url === '') {
            return null;
        }

        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return null;
        }

        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        if (isset($parsedUrl['path'])) {
            $baseUrl .= $parsedUrl['path'];
        }
        if (isset($parsedUrl['query'])) {
            $baseUrl .= '?' . $parsedUrl['query'];
        }
        if (isset($parsedUrl['fragment'])) {
            $baseUrl .= '#' . $parsedUrl['fragment'];
        }

        return $baseUrl;
    }


    // Call this function in the <head> section of your HTML/PHP template
    injectViteHMR();

?>