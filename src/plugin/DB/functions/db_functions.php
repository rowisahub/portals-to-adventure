<?php
namespace PTA\DB\functions;
/*
File: db_functions.php
Description: Database functions for the plugin.
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Requires
use PTA\DB\db_handler;
use PTA\logger\Log;
use PTA\DB\QueryBuilder;
use PTA\interfaces\DB\QueryBuilderInterface;

class db_functions
{
  private $handler_instance;
  private $logger;
  private $wpdb;
  
  public function __construct()
  {
    $this->logger = new log(name: 'DB.Functions');
  }

  public function init(db_handler $handler_instance = null, \wpdb $wpdbIn = null)
  {
    //$this->cache = [];

    $this->logger = $this->logger->getLogger();

    // log the handler instance
    //$this->logger->debug("Is handler instance null? " . ($handler_instance == null ? 'Yes' : 'No'));

    // uf handler_instance is null, set it
    if ($handler_instance == null) {
      $this->handler_instance = new db_handler();
    } else {
      $this->handler_instance = $handler_instance;
    }

    $this->wpdb = $wpdbIn ?? $this->handler_instance->get_WPDB();
    
  }

  /**
   * Retrieves data from a specified table.
   * 
   * The column is what to retrieve from the table, the where is the condition to filter the query.
   * 
   * The output type can be either ARRAY_A or ARRAY_N or OBJECT.
   * ARRAY_A is an associative array, ARRAY_N is a numeric array, and OBJECT is an object.
   *
   * @param string $table The name of the table to query.
   * @param array $columns The columns to select from the table. Defaults to ['*']. Example: ['id', 'name']
   * @param array $where An associative array of conditions to filter the query. Defaults to an empty array.
   * @param bool $use_cache (NOT IN USE) Whether to use cached results if available. Defaults to false.
   * @param string $output_type The type of output to return. Defaults to ARRAY_A. Example: OBJECT
   * @return mixed The result set from the query.
   */
  public function get_data(
    $table,
    $columns = ['*'],
    $where = [],
    /*$use_cache = false,*/
    $output_type = ARRAY_A
  ) {
    // $cache_key = md5($table . serialize($columns) . serialize($where));
    // if ($use_cache && isset($this->cache[$cache_key])) {
    //   return $this->cache[$cache_key];
    // }

    // get the table path from the handler instance
    $table_path = $this->handler_instance->get_table_path($table);

    if($table_path == null){
      $this->logger->error("Table path is null for table: " . $table);
      return null;
    }

    $columns_sql = implode(', ', $columns);
    $sql = "SELECT {$columns_sql} FROM {$table_path}";
    $values = [];

    if (!empty($where)) {
      $conditions = [];
      foreach ($where as $column => $value) {
        $conditions[] = "{$column} = %s";
        $values[] = $value;
      }
      $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    // $this->logger->debug("SQL: " . print_r($sql, true));
    // $this->logger->debug("Values: " . print_r($values, true));

    $prepared_sql = $this->wpdb->prepare($sql, $values);
    $results = $this->wpdb->get_results($prepared_sql, $output_type);

    //$this->cache[$cache_key] = $results;
    return $results;
  }

  /**
   * Retrieves the specified table from the database.
   *
   * @param string $table_name The name of the table to retrieve.
   * @return mixed The table data or false if the table does not exist.
   */
  public function get_table($table_name)
  {

    if(!$this->handler_instance){
      $this->logger->error("Handler instance is null");
      return false;
    }

    $table = $this->handler_instance->get_table($table_name);

    if(!$table){
      $this->logger->error("Table does not exist: " . $table_name);
      return false;
    }

    return $table;
  }

  /**
   * Retrieves data using the provided QueryBuilder instance.
   *
   * @param QueryBuilder $query_builder The QueryBuilder instance used to build the query.
   * @param string $output_type The type of output to return. Defaults to ARRAY_A. Example: OBJECT or ARRAY_N
   * @return mixed The data retrieved from the database.
   */
  public function exe_from_builder(QueryBuilder $query_builder, $use_cache = false, $cache_group_name = 'db', $output_type = ARRAY_A)
  {
    $queryBuilderSQL = $query_builder->get_sql();

    /* Caching */
    if($use_cache){
      $cache_key = 'qb_' . md5($queryBuilderSQL . $output_type);

      $grounp_name = "pta_" . $cache_group_name;

      $cache_result = wp_cache_get(key: $cache_key, group: $grounp_name);

      if($cache_result === false){
        
        $results = $this->wpdb->get_results($queryBuilderSQL, $output_type);

        if($results){
          wp_cache_set(key: $cache_key, data: $results, group: $grounp_name);
        }
      }

      return $cache_result;

    }

    

    //$this->logger->debug("Query Builder SQL:");
    //$this->logger->debug($queryBuilderSQL);

    //$this->logger->debug("SQL: " . $queryBuilderSQL);

    $results = $this->wpdb->get_results($queryBuilderSQL, $output_type);

    //$this->logger->debug("Results:");
    //$this->logger->debug(json_encode($results));

    return $results;

  }

  /**
   * Check if a given ID exists in a specified table.
   *
   * @param string $table The name of the table to check.
   * @param int $id The ID to check for existence.
   * @return bool True if the ID exists, false otherwise.
   */
  public function check_id_exists($table, $id)
  {
    // check if table is image_data, if so use image_id
    if ($table === 'image_data') {
      $result = $this->get_data($table, ['image_id'], ['image_id' => $id]);
    } else {
      $result = $this->get_data($table, ['id'], ['id' => $id]);
    }

    return count($result) > 0;
  }

  /**
   * Generates a universally unique identifier (UUID).
   *
   * @return string A UUID in the format of xxxxxxxx-xxxx-Mxxx-Nxxx-xxxxxxxxxxxx
   */
  private function uuid()
  {
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
  public function generate_uuid($table_name)
  {
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

  public function get_WPDB()
  {
    return $this->wpdb;
  }

  
}