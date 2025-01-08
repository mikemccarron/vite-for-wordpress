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
    $isDevelopment = (WP_ENV) ? WP_ENV : false;

    function injectViteHMR() {
        if ( strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false ) {
            return false;
        }

        if (class_exists('Roots\WPConfig\Config')) {
            $wpHome = Config::get('WP_HOME');
            $cleanUrl = stripPort( $wpHome );

            $viteEntry = Config::get('VITE_ENTRY');
            $viteEntryDevPort = Config::get('VITE_ENTRY_DEVPORT');
        }
        elseif( class_exists('Dotenv\Dotenv') ){
            $wpHome = $_ENV['WP_HOME'];
            $cleanUrl = stripPort( $wpHome );

            $viteEntry = $_ENV['VITE_ENTRY'];
            $viteEntryDevPort = $_ENV['VITE_ENTRY_DEVPORT'];
        }
        else{
            echo "<!-- ViteJS: No compaitble enviroment variables found. -->";
            return false;
        }

        // This is set in lumberjack config
        if (!$wpHome || !$viteEntry) {
            echo '<!-- Missing WP_HOME or VITE_ENTRY configuration -->';
            return;
        }

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
        }
        else {
            echo '<!-- Vite development server is not running -->';
        }
    }

    if (!is_admin() && ( isset($isDevelopment) && $isDevelopment=="development") ) {
        // Call this function in the <head> section of your HTML/PHP template
        injectViteHMR();
    }
    elseif(isset($isDevelopment) && $isDevelopment=="production"){
        // Hook into wp_enqueue_scripts to properly enqueue assets.
        add_action('wp_enqueue_scripts', 'loadProductionAssets');
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

    function loadProductionAssets(){
        // Production mode: include your built assets

        // Get the current theme directory.
        $theme_dir = get_template_directory();
        $theme_uri = get_template_directory_uri();

        // Path to the Vite manifest file.
        $manifest_path = $theme_dir . '/files/.vite/manifest.json';

        // Check if the manifest file exists.
        if (file_exists($manifest_path)) {
            // Decode the manifest JSON.
            $manifest = json_decode(file_get_contents($manifest_path), true);

            // Ensure the manifest is a valid JSON object.
            if (is_array($manifest)) {
                foreach ($manifest as $entry) {
                    if (isset($entry['file']) && str_ends_with($entry['file'], '.js')) {
                        // Extract base filename without path or extension.
                        $handle = sanitize_title(pathinfo($entry['file'], PATHINFO_FILENAME));
                        wp_enqueue_script(
                            $handle, // Unique handle.
                            $theme_uri . '/files/' . $entry['file'],
                            [],
                            null,
                            true  // Load in the footer.
                        );
                    }

                    // Inject CSS files.
                    if (isset($entry['css']) && is_array($entry['css'])) {
                        foreach ($entry['css'] as $css_file) {
                            // Extract base filename without path or extension.
                            $handle = sanitize_title(pathinfo($css_file, PATHINFO_FILENAME));
                            wp_enqueue_style(
                                $handle . '-style',
                                $theme_uri . '/files/' . $css_file, // Full URL to CSS file.
                                [],
                                null
                            );
                        }
                    }
                }
            }
        }
    }

?>