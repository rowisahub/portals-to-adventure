<?php
namespace PTA\DB\functions\user;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

use PTA\DB\db_handler;
use PTA\DB\functions\db_functions;
use PTA\DB\QueryBuilder;
use PTA\logger\Log;

class user_submission_functions
{
  private $table_path;
  private $handler_instance;
  private $db_functions;
  private $logger;
  private \wpdb $wpdb;

  public function __construct(db_handler $handler_instance = null, db_functions $db_functions = null)
  {
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

    $this->table_path = $this->handler_instance->get_table_path('user_submission_votes');

    $this->logger = new Log('User Submission Functions');
    $this->logger = $this->logger->getLogger();
  }

  private function update_user_vote($user_id, $submission_id, $votes)
  {
    $table_data = [
      'votes' => $votes
    ];

    $this->wpdb->update($this->table_path, $table_data, ['user_id' => $user_id, 'submission_id' => $submission_id]);
    $this->logger->debug('User vote updated', $table_data);
    $this->logger->info('User vote updated', [
      'user_id' => $user_id,
      'submission_id' => $submission_id,
      'votes' => $votes
    ]);
  }

  /**
   * Retrieves the number of votes for a specific user's submission.
   *
   * This method fetches the vote count associated with the given submission
   * for a particular user.
   *
   * @param int $user_id The unique identifier of the user.
   * @param int $submission_id The unique identifier of the submission.
   * @return int The number of votes for the user's submission.
   */
  public function get_user_submission_votes($user_id, $submission_id)
  {
    $query = new QueryBuilder($this->wpdb);
    $query->select('votes')
      ->from($this->table_path)
      ->where(['user_id' => $user_id])
      ->where(['submission_id' => $submission_id]);

    $result = $this->db_functions->exe_from_builder(query_builder: $query, output_type: 'ARRAY_A');
    $result = $result[0]['votes'] ?? 0;

    return $result;
  }

  /**
   * Retrieve the total number of votes for a given submission.
   *
   * This method queries the database to count all votes associated with the specified submission.
   *
   * @param int $submission_id The unique identifier of the submission.
   * @return int The total vote count for the submission.
   */
  public function get_total_votes_for_submission($submission_id)
  {
    $query = new QueryBuilder($this->wpdb);
    $query->select('SUM(votes) as total_votes')
      ->from($this->table_path)
      ->where(['submission_id' => $submission_id]);

    $result = $this->db_functions->exe_from_builder(query_builder: $query, output_type: 'ARRAY_A');
    $result = $result[0]['total_votes'] ?? 0;

    return (int) $result;
  }

  /**
   * Check if a user has already voted for a specific submission.
   *
   * This method checks the database to determine if the user has cast a vote
   * for the specified submission.
   *
   * @param int $user_id The unique identifier of the user.
   * @param int $submission_id The unique identifier of the submission.
   * @return bool True if the user has voted, false otherwise.
   */
  public function has_user_voted($user_id, $submission_id)
  {
    $query = new QueryBuilder($this->wpdb);
    $query->select('votes')
      ->from($this->table_path)
      ->where(['user_id' => $user_id])
      ->where(['submission_id' => $submission_id]);

    $result = $this->db_functions->exe_from_builder($query);

    return !empty($result);
  }

  private function create_user_vote($user_id, $submission_id, $votes = 0)
  {
    $table_data = [
      'user_id' => $user_id,
      'submission_id' => $submission_id,
      'votes' => $votes
    ];

    $this->wpdb->insert($this->table_path, $table_data);
    $this->logger->debug('User vote created', $table_data);
  }

  /**
   * Records a user vote on a submission.
   *
   * This function logs the vote count for the specified user and submission.
   *
   * @param int $user_id       The unique identifier of the user casting the vote.
   * @param int $submission_id The unique identifier of the submission receiving the vote.
   * @param int $votes         The number of votes to add for the submission.
   */
  public function add_user_vote($user_id, $submission_id, $votes)
  {
    if(!$this->has_user_voted($user_id, $submission_id)){

      $this->create_user_vote($user_id, $submission_id, $votes);
      $this->logger->debug('User vote created', [
        'user_id' => $user_id,
        'submission_id' => $submission_id,
        'votes' => $votes
      ]);

    } else {

      $result = $this->get_user_submission_votes($user_id, $submission_id);

      if ($result) {
        $votes += $result;
      }

      $this->update_user_vote($user_id, $submission_id, $votes);

      $this->logger->debug('User vote updated', [
        'user_id' => $user_id,
        'submission_id' => $submission_id,
        'votes' => $votes
      ]);
    } 
  }

  /**
   * Removes a specified number of votes for a given user's submission.
   *
   * This method subtracts the specified number of votes from the submission
   * associated with the provided submission ID for the given user.
   *
   * @param int $user_id        The unique identifier of the user whose vote will be removed.
   * @param int $submission_id  The unique identifier of the submission from which the vote is to be removed.
   * @param int $votes          The number of votes to remove. Defaults to 1.
   */
  public function remove_user_vote($user_id, $submission_id, $votes = 1)
  {
    $this->logger->debug('Attempting to remove user vote', [
      'user_id' => $user_id,
      'submission_id' => $submission_id,
      'votes' => $votes
    ]);

    if ($this->has_user_voted($user_id, $submission_id)) {
      $current_votes = $this->get_user_submission_votes($user_id, $submission_id);
      $new_votes = max(0, $current_votes - $votes);

      $this->logger->debug('Removing user vote', [
        'user_id' => $user_id,
        'submission_id' => $submission_id,
        'votes' => $new_votes,
        'current_votes' => $current_votes,
        'votes_to_remove' => $votes
      ]);

      $this->update_user_vote($user_id, $submission_id, $new_votes);
    } else {
      $this->logger->debug('User has not voted on this submission', [
        'user_id' => $user_id,
        'submission_id' => $submission_id
      ]);
    }
  }

  private function view_table()
  {
    $query = new QueryBuilder($this->wpdb);
    $query->select('*')
      ->from($this->table_path);

    $result = $this->db_functions->exe_from_builder($query);

    return $result;
  }

  public function get_all_votes(){
    return $this->view_table();
  }

  public function has_user_voted_limit($user_id, $submission_id)
  {
    // $query = new QueryBuilder($this->wpdb);
    // $query->select('votes')
    //   ->from($this->table_path)
    //   ->where(['user_id' => $user_id])
    //   ->where(['submission_id' => $submission_id]);

    // $result = $this->db_functions->exe_from_builder($query);

    // return !empty($result);
  }

}