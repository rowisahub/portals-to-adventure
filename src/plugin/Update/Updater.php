<?php
namespace PTA\Update;
/*
File: pta-updates.php
Description: Updates for the plugin.
Author: Rowan Wachtler
Created: 10-31-2024
Version: 1.0
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Require the logger file
//require_once plugin_dir_path(__FILE__) . '../pta-logger.php';

// Class for handling plugin updates
class Updater
{
  private $repo_owner = 'rowisahub';
  private $repo_name = 'wld-pta';
  private $plugin_file;
  private $api_url;
  private $zip_url;
  private $current_version;
  private $access_token;

  // Constructor to initialize the updater
  public function __construct($plugin_file, $repo_owner = null, $repo_name = null)
  {
    global $logPTAUpdater;

    // Check the environment setting
    $env = get_option('pta_environment');

    // If environment is production, do nothing
    if ($env == 'production') {
      return;
    }

    // Set repository owner and name
    $this->repo_owner = $repo_owner ?? $this->repo_owner;
    $this->repo_name = $repo_name ?? $this->repo_name;
    $this->plugin_file = $plugin_file;
    $this->api_url = "https://api.github.com/repos/{$this->repo_owner}/{$this->repo_name}/releases/latest";
    $this->current_version = $this->get_plugin_version();
    $this->access_token = $this->get_access_token();

    // Log a warning if access token is not set
    if (!$this->access_token) {
      $logPTAUpdater->warning('Access token not set for GitHub API');
      return;
    }

    // If environment is development, do nothing
    if ($env == 'development') {
      return;
    }

    // Hook into WordPress update system
    add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
    add_filter('plugins_api', array($this, 'plugins_api'), 10, 3);
  }

  // Get the current version of the plugin
  private function get_plugin_version()
  {
    $plugin_data = get_plugin_data($this->plugin_file);
    return $plugin_data['Version'];
  }

  // Get the GitHub access token from options
  private function get_access_token()
  {
    return get_option('pta_github_fg_token');
  }

  // Check for updates from GitHub
  public function check_for_update($transient)
  {
    global $logPTAUpdater;

    // If no checked plugins, return the transient
    if (empty($transient->checked)) {
      return $transient;
    }

    // Fetch the latest release information from GitHub
    $response = wp_remote_get($this->api_url, array(
      'headers' => array(
        'Accept' => 'application/vnd.github.v3+json',
        'Authorization' => 'token ' . $this->access_token,
        'User-Agent' => 'WordPress Plugin Updater',
      ),
    ));

    // Log an error if the request failed
    if (is_wp_error($response)) {
      $logPTAUpdater->error('Error fetching update information: ' . $response->get_error_message());
      return $transient;
    }

    // Decode the response body
    $release = json_decode(wp_remote_retrieve_body($response));

    // Log a warning if no tag name is found
    if (!isset($release->tag_name)) {
      $logPTAUpdater->warning('No tag name found in release information');
      return $transient;
    }

    // Get the latest version from the release tag
    $latest_version = ltrim($release->tag_name, 'v');

    // If a new version is available, update the transient
    if (version_compare($this->current_version, $latest_version, '<')) {
      $plugin_slug = plugin_basename($this->plugin_file);

      $logPTAUpdater->info('Update available: ' . $this->current_version . ' -> ' . $latest_version);

      $transient->response[$plugin_slug] = (object) array(
        'slug' => $plugin_slug,
        'new_version' => $latest_version,
        'package' => $release->zipball_url,
        'url' => $release->html_url,
      );

      // Perform the update
      $this->perform_update($release->zipball_url);
    }

    return $transient;
  }

  // get zip url
  public function get_zip_url()
  {
    return $this->zip_url;
  }

  // Perform the update by downloading and extracting the zip file
  private function perform_update($zip_url)
  {
    global $logPTAUpdater;

    // Download the update zip file
    $download_response = wp_remote_get($zip_url, array(
      'headers' => array(
        'Accept' => 'application/vnd.github.v3+json',
        'Authorization' => 'token ' . $this->access_token,
        'User-Agent' => 'WordPress Plugin Updater',
      ),
    ));

    // Log an error if the download failed
    if (is_wp_error($download_response)) {
      $logPTAUpdater->error('Error downloading update: ' . $download_response->get_error_message());
      return;
    }

    // Create a temporary file for the download
    $temp_file = wp_tempnam($zip_url);
    if (!$temp_file) {
      $logPTAUpdater->error('Error creating temporary file for download');
      return;
    }

    // Write the downloaded data to the temporary file
    $file_data = wp_remote_retrieve_body($download_response);
    if (file_put_contents($temp_file, $file_data) === false) {
      $logPTAUpdater->error('Error writing to temporary file');
      return;
    }

    $logPTAUpdater->info('Update downloaded to temporary file: ' . $temp_file);

    // Extract the zip file to the plugin directory
    $result = unzip_file($temp_file, WP_PLUGIN_DIR . '/' . dirname(plugin_basename($this->plugin_file)));

    // Log an error if extraction failed
    if (is_wp_error($result)) {
      $logPTAUpdater->error('Error extracting update: ' . $result->get_error_message());
      return;
    }

    $logPTAUpdater->info('Plugin updated successfully');

    // Clean up the temporary file
    unlink($temp_file);
  }

  // Handle the plugins API request for plugin information
  public function plugins_api($false, $action, $args)
  {
    global $logPTAUpdater;

    // If the action is not 'plugin_information', return false
    if ($action !== 'plugin_information') {
      return $false;
    }

    // If the plugin slug does not match, return false
    if ($args->slug !== plugin_basename($this->plugin_file)) {
      return $false;
    }

    // Fetch the latest release information from GitHub
    $response = wp_remote_get($this->api_url, array(
      'headers' => array(
        'Accept' => 'application/vnd.github.v3+json',
        'Authorization' => 'token ' . $this->access_token,
        'User-Agent' => 'WordPress Plugin Updater',
      ),
    ));

    // Return false if the request failed
    if (is_wp_error($response)) {
      return $false;
    }

    // Decode the response body
    $release = json_decode(wp_remote_retrieve_body($response));

    // Return false if no tag name is found
    if (!isset($release->tag_name)) {
      return $false;
    }

    // Get the plugin data
    $plugin_data = get_plugin_data($this->plugin_file);

    // Prepare the plugin information
    $plugin_info = array(
      'name' => $plugin_data['Plugin Name'],
      'slug' => plugin_basename($this->plugin_file),
      'version' => ltrim($release->tag_name, 'v'),
      'author' => $plugin_data['Author'],
      'download_link' => $release->zipball_url,
      'sections' => array(
        'description' => $plugin_data['Description'],
      ),
    );

    $logPTAUpdater->debug('Plugin info: ' . print_r($plugin_info, true));

    // Return false for testing
    return $false;

    // Uncomment the following line to return the plugin information
    // return (object) $plugin_info;
  }

  // Simulate the update download process (for development purposes)
  private function simulate_update_download()
  {
    global $logPTAUpdater;

    // Fetch the latest release information from GitHub
    $response = wp_remote_get($this->api_url, array(
      'headers' => array(
        'Accept' => 'application/vnd.github.v3+json',
        'Authorization' => 'token ' . $this->access_token,
        'User-Agent' => 'WordPress Plugin Updater',
      ),
    ));

    // Log an error if the request failed
    if (is_wp_error($response)) {
      $logPTAUpdater->error('Error fetching update information: ' . $response->get_error_message());
      return;
    }

    // Decode the response body
    $release = json_decode(wp_remote_retrieve_body($response));

    // Log a warning if no tag name is found
    if (!isset($release->tag_name)) {
      $logPTAUpdater->warning('No tag name found in release information');
      return;
    }

    // Get the latest version from the release tag
    $latest_version = ltrim($release->tag_name, 'v');

    // If a new version is available, simulate the update download
    if (version_compare($this->current_version, $latest_version, '<')) {
      $logPTAUpdater->info('Simulating update download: ' . $this->current_version . ' -> ' . $latest_version);

      $download_response = wp_remote_get($release->zipball_url, array(
        'headers' => array(
          'Accept' => 'application/vnd.github.v3+json',
          'Authorization' => 'token ' . $this->access_token,
          'User-Agent' => 'WordPress Plugin Updater',
        ),
      ));

      // Log an error if the download failed
      if (is_wp_error($download_response)) {
        $logPTAUpdater->error('Error downloading update: ' . $download_response->get_error_message());
        return;
      }

      // Create a temporary file for the download
      $temp_file = wp_tempnam($release->zipball_url);
      if (!$temp_file) {
        $logPTAUpdater->error('Error creating temporary file for download');
        return;
      }

      // Write the downloaded data to the temporary file
      $file_data = wp_remote_retrieve_body($download_response);
      if (file_put_contents($temp_file, $file_data) === false) {
        $logPTAUpdater->error('Error writing to temporary file');
        return;
      }

      $logPTAUpdater->info('Update downloaded to temporary file: ' . $temp_file);

      // Extract the zip file to the plugin directory
      $result = unzip_file($temp_file, WP_PLUGIN_DIR . '/' . dirname(plugin_basename($this->plugin_file)));

      // Log an error if extraction failed
      if (is_wp_error($result)) {
        $logPTAUpdater->error('Error extracting update: ' . $result->get_error_message());
        return;
      }

      $logPTAUpdater->info('Plugin updated successfully');

      // Clean up the temporary file
      unlink($temp_file);
    } else {
      $logPTAUpdater->info('No update needed. Current version is up to date.');
    }
  }
}