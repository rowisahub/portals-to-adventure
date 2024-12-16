<?php
namespace PTA\API\REST\utils;

use PTA\API\Restv2;
use PTA\API\REST\utils\Constants;

class SubmissionFormatter
{
    private Restv2 $restV2_instance;
    private Constants $constants;

    public function __construct($restV2_instance)
    {
        $this->restV2_instance = $restV2_instance;
        $this->constants = new Constants();
    }

    /**
     * Formats the submission data.
     *
     * @param array $submission The submission data to format.
     * @param \WP_User $user The user associated with the submission.
     * @return array The formatted submission data.
     */
    public function format_submission($submission, $user){
        $formated_images = $this->format_image($submission);
        $thumbnail_url = $formated_images['thumbnail_url'];
        $map_url = $formated_images['map_url'];
        $imagesShare = $formated_images['images'];
    
        // Title and description are unescaped
        $title = wp_unslash($submission['title']);
        $description = wp_unslash($submission['description']);
    
        $userpta = $this->restV2_instance->user_functions->get_user_by_id($submission['user_owner_id'])[0];
        $username = $userpta['username'];
    
        $submission_api_base = [
          'id' => $submission['id'],
          'title' => $title,
          'description' => $description,
          'video_link' => $submission['video_link'],
          'state' => $submission['state'],
          'views' => $submission['views'],
          'likes' => $submission['likes_votes'],
          'user_id' => $submission['user_owner_id'],
          'user_name' => $username,
          'created_at' => $submission['created_at'],
          'images' => $imagesShare,
          'thumbnail_url' => $thumbnail_url,
          'map_url' => $map_url
        ];
    
        $user_primary_role = $this->restV2_instance->permissionChecker->get_user_role($user);
    
        // check if user is an admin editor or the owner of the submission
        if($user_primary_role === $this->constants::ADMIN || $user_primary_role === $this->constants::EDITOR || $submission['user_owner_id'] === $user->ID){
          $submission_api_base['removed_reason'] = $submission['removed_reason'];
          $submission_api_base['is_removed'] = $submission['is_removed'] == 1;
          $submission_api_base['is_rejected'] = $submission['is_rejected'] == 1;
          $submission_api_base['rejected_reason'] = $submission['rejected_reason'];
        }
    
        // check if user is an admin or editor
        if($user_primary_role === $this->constants::ADMIN || $user_primary_role === $this->constants::EDITOR){
          $submission_api_base['was_rejected'] = $submission['was_rejected'] == 1;
        }
    
        return $submission_api_base;
      }

      /**
     * Formats the image data from the given submission.
     *
     * @param array $submission The submission data containing image information.
     * @return array The formatted image data.
     */
      protected function format_image($submission){
        $image_ids = json_decode($submission['image_uploads']);
    
        $images = [];
    
        $thumbnail_url = '';
        $map_url = '';
    
        foreach ($image_ids as $image_id) {
          $image = $this->restV2_instance->image_functions->get_image_data($image_id)[0];
    
          if(!$image){
            continue;
          }
    
          if($image['is_thumbnail'] == 1){
            $thumbnail_url = $image['image_reference'];
          }
          if($image['is_map'] == 1){
            $map_url = $image['image_reference'];
          }
    
          $images[] = [
            'id' => $image['image_id'],
            'image_url' => $image['image_reference'],
            'is_thumbnail' => $image['is_thumbnail'] == 1,
            'is_map' => $image['is_map'] == 1,
            'imageData' => $image
          ];
        }
    
        return [
          'images' => $images,
          'thumbnail_url' => $thumbnail_url,
          'map_url' => $map_url
        ];
        
      }

      /**
     * Removes duplicate submissions from the provided submissions array.
     *
     * @param array &$submissions The array of submissions to be processed. This parameter is passed by reference.
     */
    public function remove_duplicate_submissions(&$submissions)
      {
        $ids = [];
    
        foreach ($submissions as $key => $submission) {
          if (in_array($submission['id'], $ids)) {
            unset($submissions[$key]);
          } else {
            $ids[] = $submission['id'];
          }
        }
      }
}