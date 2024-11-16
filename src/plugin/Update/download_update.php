<?php
/*
File: download_update.php
*/

// Load WordPress
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

// Validate the nonce
if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'download_update')) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// 

// Serve the plugin ZIP file
if (file_exists(PLUGIN_ZIP_PATH)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename(PLUGIN_ZIP_PATH) . '"');
    header('Content-Length: ' . filesize(PLUGIN_ZIP_PATH));
    readfile(PLUGIN_ZIP_PATH);
    exit;
} else {
    http_response_code(404);
    echo 'File not found';
    exit;
}