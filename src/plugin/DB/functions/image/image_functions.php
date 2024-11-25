<?php
namespace PTA\DB\functions\image;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

/* Requires */
use PTA\DB\db_functions;
use PTA\DB\QueryBuilder;


/**
 * Image functions for the plugin.
 */
class image_functions extends db_functions{

  function get_image_data($image_id, $output_type = 'ARRAY_A')
  {
    $queryBuilder = new QueryBuilder($this->get_WPDB());
    $queryBuilder->select('*')
                 ->from('image_data')
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

    $table_path = $this->handler_instance->get_table_path('image_data');
    
    $this->get_WPDB()->insert($table_path, $data);

    return $uuid;
  }
}