<?php
namespace PTA\interfaces\DB;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for database backup functionality.
 */
interface BackupInterface
{
    /**
     * Initialize the backup process.
     *
     * @return void
     */
    public function init();

    /**
     * Create a database backup for specific tables.
     *
     * @return string|false The SQL backup string or false on failure.
     */
    public function create_backup();

    /**
     * Save a database backup to a file.
     *
     * @param string $sql The SQL backup string.
     * @param bool $compress Whether to compress the backup file.
     * @return string|false The path to the backup file or false on failure.
     */
    public function save_backup($sql, $compress = false);

    /**
     * Perform the backup process: create and save the backup.
     *
     * @param bool $compression Whether to compress the backup file.
     * @return bool True on success, false on failure.
     */
    public function perform_backup($compression = false);
}