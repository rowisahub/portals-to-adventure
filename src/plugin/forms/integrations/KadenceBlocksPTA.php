<?php
namespace PTA\forms\integrations;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

use PTA\forms\Forms;

class KadenceBlocksPTA
{
  private $isActive = true;
  private Forms $forms;
  public function __construct(forms $forms)
  {
    $this->forms = $forms;
    if(!class_exists('Kadence_Blocks')) {
      $this->isActive = false;
    }
  }
  
  public function register_routes(){
    add_action( 'kadence_blocks_advanced_form_submission', [$this, 'pta_form_submission_handler'], 10, 3 );
  }
  public function pta_form_submission_handler($form_args, $processed_fields, $post_id){
    // Log the form post ID.
    error_log( 'Form Post ID: ' . $post_id );

    // Log overall form attributes/settings.
    //error_log( "Form Attributes:\n" . print_r( $form_args['attributes'], true ) );

    // User
    $user = wp_get_current_user();
    $user_id = "";
    if ( $user->exists() ) {
      // User is logged in
      error_log( 'User ID: ' . $user->ID );
      $user_id = $user->ID;
    } else {
      // User is not logged in
      error_log( 'User is not logged in.' );
      $user_id = "guest";
    }

    // Time and date
    $current_time = current_time( 'Y-m-d H:i:s' );
    error_log( 'Current Time: ' . $current_time );

    // Log each submitted field.
    error_log( "Processed Fields:\n" . print_r( $processed_fields, true ) );

    // Example: Loop through each field and do custom processing.
    foreach ( $processed_fields as $field ) {
      $name  = isset( $field['name'] ) ? $field['name'] : '';
      $label = isset( $field['label'] ) ? $field['label'] : '';
      $value = isset( $field['value'] ) ? $field['value'] : '';

      // Do something with each field (for example, save custom data or perform an API call)
      error_log( "Field: $label (name: $name) has value: $value" );
    }
  }
}