<?php
namespace PTA\DB;
/*
File: db_functions.php
Description: Database functions for the plugin.
Authors: Rowan Wachtler, Braedon Salwoski
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

class db_functions {
    private $handler_instance;
    private $logger;
    private $wpdb;
    private $cache;
    
    public function __construct($handler_instance) {
      global $wpdb;
      $this->handler_instance = $handler_instance;
      $this->logger = createLogger('DB.Functions');
      $this->wpdb = $wpdb;
    }
    
    /**
     * Retrieves data from a specified table.
     *
     * @param string $table The name of the table to query.
     * @param array $columns The columns to select from the table. Defaults to ['*'].
     * @param array $where An associative array of conditions to filter the query. Defaults to an empty array.
     * @param bool $use_cache Whether to use cached results if available. Defaults to false.
     * @return mixed The result set from the query.
     */
    public function get_data($table, $columns = ['*'], $where = [], $use_cache = false) {
      $cache_key = md5($table . serialize($columns) . serialize($where));
      if ($use_cache && isset($this->cache[$cache_key])) {
        return $this->cache[$cache_key];
      }

      $columns_sql = implode(', ', $columns);
      $sql = "SELECT {$columns_sql} FROM {$table}";
      $values = [];

      if (!empty($where)) {
          $conditions = [];
          foreach ($where as $column => $value) {
              $conditions[] = "{$column} = %s";
              $values[] = $value;
          }
          $sql .= " WHERE " . implode(' AND ', $conditions);
      }

      $prepared_sql = $this->wpdb->prepare($sql, $values);
      $results = $this->wpdb->get_results($prepared_sql, ARRAY_A);

      $this->cache[$cache_key] = $results;
      return $results;
    }

    /**
     * Check if a given ID exists in a specified table.
     *
     * @param string $table The name of the table to check.
     * @param int $id The ID to check for existence.
     * @return bool True if the ID exists, false otherwise.
     */
    public function check_id_exists($table, $id) {

      $result = $this->get_data($table, ['id'], ['id' => $id]);

      return count($result) > 0;
    }
    
    /**
     * Generates a universally unique identifier (UUID).
     *
     * @return string A UUID in the format of xxxxxxxx-xxxx-Mxxx-Nxxx-xxxxxxxxxxxx
     */
    private function uuid() {
      $data = random_bytes(16);

      $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
      $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

      return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Generates a UUID for a given table name.
     *
     * @param string $table_name The name of the table for which the UUID is to be generated.
     * @return string The generated UUID.
     */
    public function generate_uuid($table_name) {
      do {
        $uuid = $this->uuid();
      } while ($this->check_id_exists($table_name, $uuid));
      return $uuid;
    }

    /**
     * Formats the permissions for a user.
     *
     * @param bool|null $PermToSendEmail Whether the user has permission to send emails.
     * @param bool|null $PermIsAdmin Whether the user has admin permissions.
     * @param bool|null $PermCanReviewSubmissions Whether the user can review submissions.
     * @param bool|null $PermIsBanned Whether the user is banned.
     * @return string The formatted permissions string.
     */
    public function format_permissions($PermToSendEmail = null, $PermIsAdmin = null, $PermCanReviewSubmissions = null, $PermIsBanned = null)
    {
      // 4-bit binary string for permissions (permissions to send email, is admin, can review submissions, is banned)
      $permString = '';
      $permString .= $PermToSendEmail ? '1' : '0';
      $permString .= $PermIsAdmin ? '1' : '0';
      $permString .= $PermCanReviewSubmissions ? '1' : '0';
      $permString .= $PermIsBanned ? '1' : '0';

      return $permString;
    }
}