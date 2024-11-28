<?php
namespace PTA\DB\functions\image;


/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Requires */
use PTA\DB\db_functions;
use PTA\DB\QueryBuilder;
use PTA\logger\Log;


/**
 * Image functions for the plugin.
 */
class image_functions extends db_functions{

  private $table_path;
  private $handler_instance;
  private $logger;

  public function __construct() {
    parent::__construct();
    $this->init();

    $this->table_path = $this->handler_instance->get_table_path('image_data');

    $this->logger = new Log('Image Functions');
    $this->logger = $this->logger->getLogger();
  }

  function get_image_data($image_id, $output_type = 'ARRAY_A')
  {
    $queryBuilder = new QueryBuilder($this->get_WPDB());
    $queryBuilder->select('*')
                 ->from($this->table_path)
                 ->where(['image_id' => $image_id]);
    
    return $this->exe_from_builder($queryBuilder, $output_type);
  }

  function add_images($user_owner_id, $submission_id, $imageURL, $is_thumbnail = 0, $is_map = 0) {
    
    $uuid = $this->generate_uuid('image_data');

    $data = [
      'image_id' => $uuid,
      'user_id' => $user_owner_id,
      'submission_id' => $submission_id,
      'image_reference' => $imageURL,
      'is_thumbnail' => $is_thumbnail,
      'is_map' => $is_map
    ];

    $this->get_WPDB()->insert($this->table_path, $data);

    return $uuid;
  }

  function update_image($image_id, $data) {
    $this->get_WPDB()->update($this->table_path, $data, ['image_id' => $image_id]);
  }

  function update_image_value($image_id, $column, $value) {
    $this->get_WPDB()->update($this->table_path, [$column => $value], ['image_id' => $image_id]);
  }

  function remove_image($image_id) {
    $this->get_WPDB()->delete($this->table_path, ['image_id' => $image_id]);
    // remove image from uploads folder
  }

  function get_image_url($image_id) {
    $queryBuilder = new QueryBuilder($this->get_WPDB());
    $queryBuilder->select('image_reference')
                 ->from($this->table_path)
                 ->where(['image_id' => $image_id]);
    
    $result = $this->exe_from_builder($queryBuilder);

    if ($result === null) {
      return null;
    }

    return $result['image_reference'];
  }

  function image_reset_thumbnail($submission_id) {
    $this->get_WPDB()->update($this->table_path, ['is_thumbnail' => 0], ['submission_id' => $submission_id]);
  }

  function image_reset_map($submission_id) {
    $this->get_WPDB()->update($this->table_path, ['is_map' => 0], ['submission_id' => $submission_id]);
  }

  function image_set_thumbnail($image_id) {
    $this->get_WPDB()->update($this->table_path, ['is_thumbnail' => 1], ['image_id' => $image_id]);
  }

  function image_set_map($image_id) {
    $this->get_WPDB()->update($this->table_path, ['is_map' => 1], ['image_id' => $image_id]);
  }

}