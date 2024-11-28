<?php
namespace PTA\DB\functions\submission;

use PTA\DB\db_functions;
use PTA\DB\QueryBuilder;
use PTA\logger\Log;

class submission_functions extends db_functions {

  private $table_path;
  private $handler_instance;
  private $logger;

  public function __construct() {
    parent::__construct();
    $this->init();

    $this->table_path = $this->handler_instance->get_table_path('submission_data');

    $this->logger = new Log('Submission Functions');
    $this->logger = $this->logger->getLogger();
  }

  function add_submission(
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
    $uuid = $this->generate_uuid('submission_data');

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

    $this->get_WPDB()->insert($this->table_path, $data);

    return $uuid;
  }

  function update_submission($submission_id, $data) {
    $this->get_WPDB()->update($this->table_path, $data, ['id' => $submission_id]);
  }

  function get_submission($submission_id, $output_type = 'ARRAY_A') {
    $queryBuilder = new QueryBuilder($this->get_WPDB());
    $queryBuilder->select('*')
                 ->from($this->table_path)
                 ->where(['id' => $submission_id]);

    return $this->exe_from_builder($queryBuilder, $output_type);
  }

  function get_submissions_by_user($user_id, $output_type = 'ARRAY_A') {
    $queryBuilder = new QueryBuilder($this->get_WPDB());
    $queryBuilder->select('*')
                 ->from($this->table_path)
                 ->where(['user_owner_id' => $user_id]);

    return $this->exe_from_builder($queryBuilder, $output_type);
  }

  function get_submission_value($submission_id, $key) {
    $queryBuilder = new QueryBuilder($this->get_WPDB());
    $queryBuilder->select($key)
                 ->from($this->table_path)
                 ->where(['id' => $submission_id]);

    $result = $this->exe_from_builder($queryBuilder);

    return $result[$key] ?? null;
  }

  function get_submission_by_state($user_owner_id, $state, $output_type = 'ARRAY_A') {
    $queryBuilder = new QueryBuilder($this->get_WPDB());
    $queryBuilder->select('*')
                 ->from($this->table_path)
                 ->where(['user_owner_id' => $user_owner_id, 'state' => $state]);

    return $this->exe_from_builder($queryBuilder, $output_type);
  }

  function get_all_submissions_by_state($state, $numOfSubmissions = 10, $output_type = 'ARRAY_A') {
    $queryBuilder = new QueryBuilder($this->get_WPDB());
    $queryBuilder->select('*')
                 ->from($this->table_path)
                 ->where(['state' => $state])
                 ->limit($numOfSubmissions);

    return $this->exe_from_builder($queryBuilder, $output_type);
  }

  function get_all_submissions($output_type = 'ARRAY_A') {
    $queryBuilder = new QueryBuilder($this->get_WPDB());
    $queryBuilder->select('*')
                 ->from($this->table_path);

    return $this->exe_from_builder($queryBuilder, $output_type);
  }

  function remove_submission($submission_id, $message = 'Submission Removed By User') {
    $this->update_submission($submission_id, [
      'state' => 'Removed',
      'is_removed' => 1,
      'removed_reason' => $message
    ]);
  }

  function unremove_submission($submission_id) {
    $this->update_submission($submission_id, [
      'state' => 'In Progress',
      'is_removed' => 0,
      'removed_reason' => null
    ]);
  }

  function add_view_count($submission_id) {
    $submission = $this->get_submission($submission_id);
    $view_count = $submission['views'] + 1;
    $this->update_submission($submission_id, ['views' => $view_count]);
  }

  function add_submission_vote($submission_id, $count = 1) {
    if (!$this->check_id_exists('submission_data', $submission_id)) {
      return false;
    }
    $submission = $this->get_submission($submission_id);
    $votes = $submission['likes_votes'] + $count;
    $this->update_submission($submission_id, ['likes_votes' => $votes]);
    return true;
  }
}
