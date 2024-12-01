<?php
/*
File: update-api.php
Description: Updates for the plugin.
Author: Rowan Wachtler
version: 1.0
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Require the logger file
//require_once plugin_dir_path(__FILE__) . '../pta-logger.php';

class PTA_Update_API
{
  private $updater;
  private $baseUpdatePath = '/bitnami/wordpress/wp-content/upgrade/pta-updates/';
  private $access_token;
  private $file_URL_base = '/wp-content/upgrade/pta-updates/';

  public function __construct($updater)
  {
    $this->updater = $updater;
    $this->access_token = get_option('pta_github_fg_token');
    add_action('rest_api_init', array($this, 'register_routes'));
  }

  public function register_routes()
  {
    register_rest_route('pta/v1', '/update', array(
      'methods' => 'GET',
      'callback' => array($this, 'get_update'),
      'permission_callback' => array($this, 'pta_api_check_permissions'),
    ));
  }

  public function get_update($request)
  {
    // if param has download, download the file and return the path
    $download = $request->get_param('download');

    if ($download === 'true') {
      // return the file itself
      $zip_path = $this->download_zip();

      if ($zip_path && file_exists($zip_path)) {
        // file contents
        $file_contents = file_get_contents($zip_path);

        // Create a response
        $response = new WP_REST_Response($file_contents, 200);

        $response->header('Content-Type', 'application/zip');
        $response->header('Content-Disposition', 'attachment; filename="pta-update.zip"');
        $response->header('Content-Length', filesize($zip_path));

        return $response;
      } else {
        return new WP_REST_Response(array('error' => 'Failed to download zip'), 500);
      }

    }

    return new WP_REST_Response(array('message' => 'No download parameter provided'), 400);
  }

  private function download_zip()
  {
    global $logPTAUpdater;

    $zip_url = $this->updater->get_zip_url();


    // check if pta-updates directory exists
    if (!file_exists($this->baseUpdatePath)) {
      mkdir($this->baseUpdatePath, 0755, true);
    }


    $download_response = $this->get_github_response($zip_url);

    if (is_wp_error($download_response)) {
      $logPTAUpdater->error('Error downloading update: ' . $download_response->get_error_message());
      return;
    }

    $zip_contents = wp_remote_retrieve_body($download_response);

    $zip_path = $this->baseUpdatePath . 'pta-update.zip';

    file_put_contents($zip_path, $zip_contents);

    return $zip_path;
  }

  public function pta_api_check_permissions()
  {
    // check nonce pta_update_download, &p=nonce
    $nonce = $_GET['p'];

    if (empty($nonce)) {
      return new WP_REST_Response(array('error' => 'No nonce provided'), 400);
    }

    if (!wp_verify_nonce($nonce, 'pta_update_download')) {
      return new WP_REST_Response(array('error' => 'Invalid nonce'), 400);
    }

    return true;
  }

  public function get_github_response($URL)
  {
    global $logPTAUpdater;

    $response = wp_remote_get($URL, array(
      'headers' => array(
        'Accept' => 'application/vnd.github.v3+json',
        'Authorization' => 'token ' . $this->access_token,
        'User-Agent' => 'WordPress Plugin Updater',
      ),
    ));

    if (is_wp_error($response)) {
      $logPTAUpdater->error('Error getting GitHub response: ' . $response->get_error_message());
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data)) {
      $logPTAUpdater->error('Error decoding GitHub response: ' . $body);
      return false;
    }

    return $data;
  }
}
