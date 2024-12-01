<?php
namespace PTA\DB\functions\submission;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

use PTA\DB\db_handler;
use PTA\DB\functions\db_functions;
use PTA\DB\QueryBuilder;
use PTA\logger\Log;

class submission_functions {

  private $table_path;
  private $db_functions;
  private $handler_instance;
  private $logger;
  private \wpdb $wpdb;

  public function __construct(db_handler $handler_instance = null, db_functions $db_functions = null) {
    // Get the handler instance and db functions instance
    $this->handler_instance = $handler_instance ?? new db_handler();
    $this->db_functions = $db_functions ?? new db_functions();

    // if handler_instance is null or db_functions is null
    if ($handler_instance == null || $db_functions == null) {

      // Set the functions instance in the handler, and initialize the functions
      $this->handler_instance->set_functions(name: 'functions', function_instance: $this->db_functions);
      $this->db_functions->init(handler_instance: $this->handler_instance);

    }

    $this->wpdb = $this->handler_instance->get_WPDB();

    $this->table_path = $this->handler_instance->get_table_path('submission_data');

    $this->logger = new Log('Submission Functions');
    $this->logger = $this->logger->getLogger();
  }

  public function add_submission(
    $user_owner_id, 
    $title, 
    $description, 
    $registration_method = 'manual', 
    $image_uploads = null, 
    $video_link = null, 
    $image_thumbnail_id = null, 
    $views = 0, 
    $likes_votes = 0, 
    $state = 'In Progress'
  ) {
    $uuid = $this->db_functions->generate_uuid('submission_data');

    $data = [
      'id' => $uuid,
      'user_owner_id' => $user_owner_id,
      'registration_method' => $registration_method,
      'title' => $title,
      'description' => $description,
      'image_uploads' => $image_uploads,
      'video_link' => $video_link,
      'image_thumbnail_id' => $image_thumbnail_id,
      'views' => $views,
      'likes_votes' => $likes_votes,
      'state' => $state
    ];

    $this->wpdb->insert($this->table_path, $data);

    return $uuid;
  }

  public function update_submission($submission_id, $data) {
    $this->wpdb->update($this->table_path, $data, ['id' => $submission_id]);
  }

  public function get_submission($submission_id, $output_type = 'ARRAY_A') {
    $queryBuilder = new QueryBuilder($this->wpdb);
    $queryBuilder->select('*')
                 ->from($this->table_path)
                 ->where(['id' => $submission_id]);

    return $this->db_functions->exe_from_builder($queryBuilder, $output_type);
  }

  public function get_submissions_by_user($user_id, $output_type = 'ARRAY_A') {
    $queryBuilder = new QueryBuilder($this->wpdb);
    $queryBuilder->select('*')
                 ->from($this->table_path)
                 ->where(['user_owner_id' => $user_id]);

    return $this->db_functions->exe_from_builder($queryBuilder, $output_type);
  }

  public function get_submission_value($submission_id, $key) {
    $queryBuilder = new QueryBuilder($this->wpdb);
    $queryBuilder->select($key)
                 ->from($this->table_path)
                 ->where(['id' => $submission_id]);

    $result = $this->db_functions->exe_from_builder($queryBuilder);

    return $result[$key] ?? null;
  }

  public function get_submission_by_state($user_owner_id, $state, $output_type = 'ARRAY_A') {
    $queryBuilder = new QueryBuilder($this->wpdb);
    $queryBuilder->select('*')
                 ->from($this->table_path)
                 ->where(['user_owner_id' => $user_owner_id, 'state' => $state]);

    return $this->db_functions->exe_from_builder($queryBuilder, $output_type);
  }

  public function get_all_submissions_by_state($state, $numOfSubmissions = 10, $output_type = 'ARRAY_A') {
    $queryBuilder = new QueryBuilder($this->wpdb);
    $queryBuilder->select('*')
                 ->from($this->table_path)
                 ->where(['state' => $state])
                 ->limit($numOfSubmissions);

    return $this->db_functions->exe_from_builder($queryBuilder, $output_type);
  }

  public function get_all_submissions($output_type = 'ARRAY_A') {
    $queryBuilder = new QueryBuilder($this->wpdb);
    $queryBuilder->select('*')
                 ->from($this->table_path);

    return $this->db_functions->exe_from_builder($queryBuilder, $output_type);
  }

  public function remove_submission($submission_id, $message = 'Submission Removed By User') {
    $this->update_submission($submission_id, [
      'state' => 'Removed',
      'is_removed' => 1,
      'removed_reason' => $message
    ]);
  }

  public function unremove_submission($submission_id) {
    $this->update_submission($submission_id, [
      'state' => 'In Progress',
      'is_removed' => 0,
      'removed_reason' => null
    ]);
  }

  public function add_view_count($submission_id) {
    $submission = $this->get_submission($submission_id);
    $view_count = $submission['views'] + 1;
    $this->update_submission($submission_id, ['views' => $view_count]);
  }

  public function add_submission_vote($submission_id, $count = 1) {
    if (!$this->db_functions->check_id_exists('submission_data', $submission_id)) {
      return false;
    }
    $submission = $this->get_submission($submission_id);
    $votes = $submission['likes_votes'] + $count;
    $this->update_submission($submission_id, ['likes_votes' => $votes]);
    return true;
  }
}
