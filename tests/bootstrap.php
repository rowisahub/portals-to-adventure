<?php
// tests/bootstrap.php

require __DIR__ . '/../vendor/autoload.php';

// Define the wpdb class if it doesn't exist
if (!class_exists('wpdb')) {
    class wpdb {
        public $prefix = 'wp_';

        public function get_charset_collate() {
            return 'DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';
        }

        public function esc_like($string) {
            return addslashes($string);
        }

        // Add other methods as needed for your tests
    }
}