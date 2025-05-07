<?php
namespace PTA\DB\Tables;

if (!defined('ABSPATH')) {
  exit;
}

use PTA\interfaces\DB\TableInterface;
use PTA\interfaces\DB\DBHandlerInterface;
use PTA\logger\Log;

class UserSubmissionVotesTable implements TableInterface
{

  private function table_schema()
  {
    // User submission votes table
    $sql_user_submission_votes = "CREATE TABLE $this->table_path (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id varchar(255) NOT NULL,
        submission_id varchar(255) NOT NULL,
        votes INT UNSIGNED NOT NULL DEFAULT 0,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_submission_vote (user_id, submission_id),
        KEY user_id (user_id),
        KEY submission_id (submission_id)
      ) $this->charset_collate;";

    $this->table_schema = $sql_user_submission_votes;
  }

  private $logger;
  private $wpdb;
  private $table_name = 'user_submission_votes';
  private $table_path;
  private $table_schema;
  private $charset_collate;
  private $handler_instance;
  private $pta_prefix;
  private $table_requirements;

  public function __construct(DBHandlerInterface $handler_instance, $wpdb)
  {
    $this->wpdb = $wpdb;

    $this->logger = new log('DB.Tables.UserSubmissionVotes');
    $this->logger = $this->logger->getLogger();
    
    $this->handler_instance = $handler_instance;

    $this->pta_prefix = $handler_instance->get_pta_prefix();

    $this->table_path = $wpdb->prefix . $this->pta_prefix . $this->table_name;
    $this->charset_collate = $wpdb->get_charset_collate();

    $this->table_schema();
  }

  /**
   * Get the name of the user submission votes table.
   *
   * @return string
   */
  public function get_table_name()
  {
    return $this->table_name;
  }

  /**
   * Retrieves the path of the user submission votes table.
   *
   * @return string The path of the user submission votes table.
   */
  public function get_table_path()
  {
    return $this->table_path;
  }

  /**
   * Retrieves the schema for the user submission votes table.
   *
   * @return string The SQL schema definition for the user submission votes table.
   */
  public function get_table_schema()
  {
    return $this->table_schema;
  }

  /**
   * Creates the user submission votes table in the database.
   *
   * This method is responsible for creating the necessary table
   * in the database
   *
   * @return bool True if the table was created, false otherwise.
   */
  public function create_table()
  {
    // Check if the table exists
    if ($this->wpdb->get_var("SHOW TABLES LIKE '$this->table_path'") != $this->table_path) {

      dbDelta($this->table_schema);

      $this->logger->debug('User submission votes table created');

      return true;
    } else {
      $this->logger->debug('User submission votes table already exists');
      return true;
    }

    // check for errors
    if (!empty($this->wpdb->last_error)) {
      $this->logger->error($this->wpdb->last_error);
      return false;
    }
  }

  /**
   * Upgrade the ImageDataTable to the latest schema.
   *
   * This method handles the necessary changes to update the table structure
   * to match the latest version requirements. It ensures that any new columns,
   * indexes, or other modifications are applied correctly.
   *
   * @return bool True if the table was upgraded, false otherwise.
   */
  public function upgrade_table()
  {
    $result = dbDelta($this->table_schema);

    // Check for new errors
    if ($this->wpdb->last_error) {
      $this->logger->error("Error upgrading {$this->table_name} table: " . $this->wpdb->last_error);
      return false;
    }


    // Log changes if any were made
    if (!empty($result)) {
      $this->logger->info("Changes made to {$this->table_name} table: " . print_r($result, true));
    } else {
        $this->logger->debug("No changes required for {$this->table_name} table");
    }

    //$this->logger->info('Image data table upgraded');

    return true;
  }

}
