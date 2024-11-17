<?php
namespace PTA\Update;
/*
File: pta-updates2.php
Description: Updates for the plugin.
Author: Rowan Wachtler
Created: 10-31-2024
Version: 1.0
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Requires
//require_once plugin_dir_path(__FILE__) . '../pta-logger.php';
require_once plugin_dir_path(__FILE__) . 'update-api.php';
use PTA\logger\Log;


// Class for handling plugin updates
class Plugin_Updater
{
  private $repo_owner = 'rowisahub';
  private $repo_name = 'wld-pta';
  private $plugin_file;
  private $api_url;
  private $zip_url;
  private $current_version;
  private $api_instance;
  private $api_download_url_base = '/wp-json/pta/v1/update';
  private $logger;

  // Constructor to initialize the updater
  public function __construct()
  {
    $this->logger = new Log(name: 'Updater');
  }

  public function init($plugin_file, $repo_owner = null, $repo_name = null)
  {
    $this->logger = $this->logger->getLogger();

    $this->register_hooks();

    $this->repo_owner = $repo_owner ?? $this->repo_owner;
    $this->repo_name = $repo_name ?? $this->repo_name;
    $this->plugin_file = $plugin_file;
    $this->api_url = "https://api.github.com/repos/{$this->repo_owner}/{$this->repo_name}/releases/latest";
    $this->current_version = $this->get_plugin_version();

    // Init API
    //$this->api_instance = new PTA_Update_API($this);
  }

  public function register_hooks()
  {
    add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
  }

  public function check_for_updates($transient)
  {

    if (empty($transient->checked)) {
      return $transient;
    }

    $API_response = $this->api_instance->get_github_response($this->api_url);

    $this->zip_url = $API_response->assets[0]->url;

    if (!$API_response) {
      return $transient;
    }

    if (!isset($API_response->tag_name)) {
      $this->logger->warning('No tag name found in release information');
      return $transient;
    }



    // Get the latest version from the release tag
    $latest_version = ltrim($API_response->tag_name, 'v');

    if (version_compare($this->current_version, $latest_version, '<')) {
      $this->logger->info('New version available: ' . $this->current_version . ' -> ' . $latest_version);

      $plugin_slug = plugin_basename($this->plugin_file);

      $pack = $this->api_download_url_base . "?download=true&p=" . wp_create_nonce('pta_update_download');

      $plugin = array(
        'slug' => $plugin_slug,
        'new_version' => $latest_version,
        'package' => $pack
      );

      $transient->response[$plugin_slug] = (object) $plugin;
    }

    return $transient;
  }

  // Get the current version of the plugin
  private function get_plugin_version()
  {
    $plugin_data = get_plugin_data($this->plugin_file);
    return $plugin_data['Version'];
  }

  public function get_zip_url()
  {
    return $this->zip_url;
  }

}