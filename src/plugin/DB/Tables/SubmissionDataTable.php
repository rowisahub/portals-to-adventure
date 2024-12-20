<?php
namespace PTA\DB\Tables;
/*
File: SubmissionDataTable.php
Description: Submission data table for the plugin.
Author: Rowan Wachtler
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use PTA\interfaces\DB\TableInterface;
use PTA\interfaces\DB\DBHandlerInterface;
use PTA\logger\Log;

class SubmissionDataTable implements TableInterface
{

    /**
     * Generates the schema for the ImageDataTable.
     */
    private function table_schema()
    {

        $user_info_path = $this->handler_instance->get_table_path('user_info');

        // Submission data table
        $sql_submission_data = "CREATE TABLE $this->table_path (
            id varchar(255) NOT NULL,
            user_owner_id varchar(255) NOT NULL,
            registration_method varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            image_uploads longtext DEFAULT NULL,
            video_link varchar(255) DEFAULT NULL,
            image_thumbnail_id varchar(255) DEFAULT NULL,
            views bigint(20) DEFAULT 0,
            likes_votes bigint(20) DEFAULT 0,
            state varchar(50) DEFAULT 'In Progress',
            is_rejected tinyint(1) DEFAULT 0,
            was_rejected tinyint(1) DEFAULT 0,
            rejected_reason text DEFAULT NULL,
            is_removed tinyint(1) DEFAULT 0,
            removed_reason text DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            FOREIGN KEY  (user_owner_id) REFERENCES $user_info_path(id) ON DELETE CASCADE
        ) $this->charset_collate;";

        $this->table_schema = $sql_submission_data;

    }

    private $logger;
    private $wpdb;
    private $table_name = 'submission_data';
    private $table_path;
    private $table_schema;
    private $charset_collate;
    private $handler_instance;
    private $pta_prefix;
    private $table_requirements = [
        'user_info'
    ];

    public function __construct(DBHandlerInterface $handler_instance, $wpdb)
    {
        $this->wpdb = $wpdb;

        $this->logger = new log('DB.Tables.SubmissionDataTable');
        $this->logger = $this->logger->getLogger();
        
        $this->handler_instance = $handler_instance;

        $this->pta_prefix = $handler_instance->get_pta_prefix();

        $this->table_path = $wpdb->prefix . $this->pta_prefix . $this->table_name;
        $this->charset_collate = $wpdb->get_charset_collate();

        $this->table_schema();

    }

    /**
     * Get the name of the user info table.
     *
     * @return string The name of the user info table.
     */
    public function get_table_name()
    {
        return $this->table_name;
    }

    /**
     * Retrieves the path of the user information table.
     *
     * @return string The path of the user information table.
     */
    public function get_table_path()
    {
        return $this->table_path;
    }

    /**
     * Retrieves the schema for the submission data table.
     *
     * This method returns the SQL schema definition for the user information table,
     * which includes the table structure, columns, data types, and any constraints.
     *
     * @return string The SQL schema definition for the user information table.
     */
    public function get_table_schema()
    {
        return $this->table_schema;
    }

    /**
     * Creates the user info table in the database.
     *
     * This method is responsible for creating the necessary table
     * in the database to store user information.
     *
     * @return bool True if the table was created, false otherwise.
     */
    public function create_table()
    {
        // Check if the table exists
        if ($this->wpdb->get_var("SHOW TABLES LIKE '$this->table_path'") != $this->table_path) {

            dbDelta($this->table_schema);

            $this->logger->debug('User info table created');

            return true;
        } else {
            $this->logger->debug('User info table already exists');
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
        $this->logger->info('Upgrading image data table');

        $result = dbDelta($this->table_schema);

        if (!empty($this->wpdb->last_error)) {
            $this->logger->error($this->wpdb->last_error);
            return false;
        }

        $this->logger->info('Image data table upgraded');

        return true;
    }
}