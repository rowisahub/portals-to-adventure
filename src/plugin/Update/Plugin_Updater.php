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
//require_once plugin_dir_path(__FILE__) . 'update-api.php';
use PTA\logger\Log;
use Monolog\Logger;


// Class for handling plugin updates
class Plugin_Updater {
    private $repo_owner = 'rowisahub';
    private $repo_name = 'portals-to-adventure';
    private $plugin_file;
    private $plugin_slug;
    private $current_version;
    private $api_url;
    private $api_download_url_base;
    private $access_token;

    private Log $logger_int;
    private Logger $logger;

    public function __construct() {
      $this->logger_int = new Log('Updater');
    }

    public function init($plugin_file, $repo_owner = null, $repo_name = null){

      $this->logger = $this->logger_int->getLogger();

      // Set plugin details
      $this->plugin_file = $plugin_file;
      $this->repo_owner = $repo_owner ?? $this->repo_owner;
      $this->repo_name = $repo_name ?? $this->repo_name;
      $this->plugin_slug = plugin_basename($plugin_file);
      $this->current_version = $this->get_plugin_version();
      
      // Set GitHub API URLs
      $this->api_url = sprintf('https://api.github.com/repos/%s/%s/releases/latest', 
          $this->repo_owner, 
          $this->repo_name
      );

      $this->access_token = get_option('pta_github_fg_token');


      $this->register_hooks();

    }

    public function register_hooks(){
        // Add WordPress filters
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_filter('upgrader_pre_download', array($this, 'upgrader_pre_download'), 10, 3);
    }

    private function get_plugin_version() {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_data = get_plugin_data($this->plugin_file, true, false);
        return $plugin_data['Version'];
    }

    private function get_github_response() {
        $response = wp_remote_get($this->api_url, array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => 'token ' . $this->access_token,
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            )
        ));

        if (is_wp_error($response)) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response));
    }

    public function check_update($transient) {
        //$this->logger->debug('Checking for updates, transient');
        //$this->logger->debug(print_r($transient, true));
        if (empty($transient->checked)) {

            return $transient;
        }

        $github_response = $this->get_github_response();

        $this->logger->debug('Github responce');
        $this->logger->debug(print_r($github_response, true));

        $this->logger->debug('Current version');
        $this->logger->debug($this->current_version);

        if (!$github_response) {
            return $transient;
        }

        $latest_version = ltrim($github_response->tag_name, 'v');

        if (version_compare($this->current_version, $latest_version, '<')) {
            $plugin = array(
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_slug,
                'new_version' => $latest_version,
                'url' => $github_response->html_url,
                'package' => $github_response->zipball_url
            );

            $transient->response[$this->plugin_slug] = (object) $plugin;
        }

        return $transient;
    }

    public function plugin_info($action, $response, $false = false) {
        if ($action !== 'plugin_information') {
            return $false;
        }

        if ($response->slug !== basename($this->plugin_slug)) {
            return $false;
        }

        $github_response = $this->get_github_response();

        if (!$github_response) {
            return $false;
        }

        $plugin_info = array(
            'name' => basename($this->plugin_slug, '.php'),
            'slug' => basename($this->plugin_slug, '.php'),
            'version' => ltrim($github_response->tag_name, 'v'),
            'author' => $github_response->author->login,
            'homepage' => $github_response->html_url,
            'download_link' => $github_response->zipball_url,
            'requires' => '5.0',
            'tested' => get_bloginfo('version'),
            'last_updated' => $github_response->published_at,
            'sections' => array(
                'description' => $github_response->body,
                'changelog' => $this->get_changelog()
            )
        );

        return (object) $plugin_info;
    }

    private function get_changelog() {
        $response = wp_remote_get(sprintf(
            'https://api.github.com/repos/%s/%s/releases', 
            $this->repo_owner, 
            $this->repo_name
        ), array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => 'token ' . $this->access_token,
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            )
        ));

        if (is_wp_error($response)) {
            return 'No changelog available.';
        }

        $releases = json_decode(wp_remote_retrieve_body($response));
        $changelog = '';

        foreach ($releases as $release) {
            $changelog .= "### {$release->tag_name}\n";
            $changelog .= $release->body . "\n\n";
        }

        return $changelog;
    }

    public function upgrader_pre_download($reply, $package, $upgrader) {
        if (strpos($package, 'api.github.com') === false) {
            return $reply;
        }

        $upgrader->strings['downloading_package'] = 'Downloading package from GitHub...';

        $response = wp_remote_get($package, array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => 'token ' . $this->access_token,
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            )
        ));

        if (is_wp_error($response)) {
            return new \WP_Error(
                'download_failed',
                'Failed to download update from GitHub: ' . $response->get_error_message()
            );
        }

        $temp_file = download_url($package);
        
        if (is_wp_error($temp_file)) {
            return $temp_file;
        }

        return $temp_file;
    }
}