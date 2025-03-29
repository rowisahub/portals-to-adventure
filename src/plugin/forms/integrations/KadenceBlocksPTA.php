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
    
    $contact_form_id = get_option('pta_form_contact_id');
    $pta_form_notification_id = get_option('pta_form_notification_id');

    if($post_id == $contact_form_id){
      $this->contact_form($processed_fields, $post_id);
    }
    if($post_id == $pta_form_notification_id){
      $this->notification_form($processed_fields, $post_id);
    }

  }

  private function contact_form($processed_fields, $post_id){
    $user = wp_get_current_user();
    $userid = "";
    if ( $user->exists() ) {
      // User is logged in
      $userid = $user->ID;
    } else {
      // User is not logged in
      $userid = "guest";
    }

    $name_field = "";
    $email_field = "";
    $message_field = "";

    foreach ( $processed_fields as $field ) {
      $label = isset( $field['label'] ) ? $field['label'] : '';
      $value = isset( $field['value'] ) ? $field['value'] : '';
      if($label == "Name"){
        $name_field = $value;
      }
      if($label == "Email"){
        $email_field = $value;
      }
      if($label == "Message"){
        $message_field = $value;
      }
    }

    $this->forms->contact_form_functions->add_completed_form(
      form_id: $post_id,
      user_id: $userid,
      email: $email_field,
      name: $name_field,
      message: $message_field
    );
  }

  private function notification_form($processed_fields, $post_id){
    $user = wp_get_current_user();
    $userid = "";
    if ( $user->exists() ) {
      // User is logged in
      $userid = $user->ID;
    } else {
      // User is not logged in
      $userid = "guest";
    }

    $name_field = "";
    $email_field = "";

    foreach ( $processed_fields as $field ) {
      $label = isset( $field['label'] ) ? $field['label'] : '';
      $value = isset( $field['value'] ) ? $field['value'] : '';
      if($label == "Name"){
        $name_field = $value;
      }
      if($label == "Email"){
        $email_field = $value;
      }
    }

    $this->forms->notification_form_functions->add_completed_form(
      form_id: $post_id,
      user_id: $userid,
      email: $email_field,
      name: $name_field
    );
  }

}