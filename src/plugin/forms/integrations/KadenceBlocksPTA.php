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

    add_filter( 'kadence_blocks_advanced_form_redirect', [$this, 'modify_registration_redirect'], 10, 3 );

    $use_custom_registration = get_option('pta_form_use_custom_registration', true);
    if($use_custom_registration){
      add_action('login_init', [$this, 'redirect_registration']);
    }
  }
  public function pta_form_submission_handler($form_args, $processed_fields, $post_id){
    
    $contact_form_id = get_option('pta_form_contact_id');
    $pta_form_notification_id = get_option('pta_form_notification_id');
    $pta_form_registration_id = get_option('pta_form_signup_id');

    if($post_id == $contact_form_id){
      $this->contact_form($processed_fields, $post_id);
    }
    if($post_id == $pta_form_notification_id){
      $this->notification_form($processed_fields, $post_id);
    }
    if($post_id == $pta_form_registration_id){
      $this->register_user($processed_fields, $post_id);
    }
  }

  public function modify_registration_redirect($redirect_url, $form_args, $processed_fields) {
    $pta_form_registration_id = get_option('pta_form_signup_id');

    $this->forms->logger->info("Kadence Blocks redirect URL: " . $redirect_url);
    // $this->forms->logger->info("Kadence Blocks form args: " . json_encode($form_args));
    // $this->forms->logger->info("Kadence Blocks processed fields: " . json_encode($processed_fields));

    if (isset($_GET['redirect_to'])) {
      $this->forms->logger->info("Kadence Blocks redirect_to: " . $_GET['redirect_to']);
    }

    $this->forms->logger->info("Post: " . json_encode($_POST));

    // 
    $unprocessed_fields = $form_args['fields'];
    // check each field to look for "formID"
    foreach($unprocessed_fields as $field){
      if(isset($field['formID']) && $field['formID'] == $pta_form_registration_id){
        if(isset($_GET['redirect_to'])) {
          $redirect_url = $_GET['redirect_to'];
        } else {
          $redirect_url = home_url();
        }
        break;
      }
    }
    
    // if(isset($form_args['attributes']['_kb_adv_form_post_id']) && $form_args['attributes']['_kb_adv_form_post_id'] == $pta_form_registration_id) {
    //   // This is our registration form
    //   if (isset($_GET['redirect_to'])) {
    //     return $_GET['redirect_to'];
    //   } else {
    //     return home_url();
    //   }
    // }

    $this->forms->logger->info("Kadence Blocks redirect URL: " . $redirect_url);
    
    return $redirect_url;
  }

  public function redirect_registration(){
    add_filter('register_url', function($url) {
      // Check if the URL contains the redirect_to parameter
      if (isset($_GET['redirect_to'])) {
        $redirect_to = $_GET['redirect_to'];
        $url = add_query_arg('redirect_to', urlencode($redirect_to), '/register');
      } else {
        // If not, just return the default registration URL
        $url = '/register';
      }
      return $url;
    });
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

    /* Send email */
    // $to = 'contact@portals-to-adventure.com'; // get_option('pta_form_contact_email');
    // $subject = "New contact form submission";
    // $headers = array('Content-Type: text/html; charset=UTF-8', "Reply-To: $name_field <$email_field>");

    // $body = "<h1>New contact form submission</h1>";
    // $body .= "<p><strong>Name:</strong> $name_field</p>";
    // $body .= "<p><strong>Email:</strong> $email_field</p>";
    // $body .= "<p><strong>Message:</strong></p>";
    // $body .= "<p>$message_field</p>";

    // $mailSend = wp_mail($to, $subject, $body, $headers);

    // if($mailSend){
    //   $this->forms->logger->info("Contact form email sent successfully");
    // } else {
    //   $this->forms->logger->error("Failed to send contact form email");
    // }
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

  private function register_user($processed_fields, $post_id){

    $user = wp_get_current_user();
    if( $user->exists() ) {
      // redirect to the home page
      wp_redirect( home_url() );
      exit;
    }

    $first_name = "";
    $last_name = "";
    $email = "";
    $password = "";
    $username = "";

    foreach ( $processed_fields as $field ) {
      $name = isset( $field['name'] ) ? $field['name'] : '';
      $value = isset( $field['value'] ) ? $field['value'] : '';
      if($name == "pta_form_first_name"){
        $first_name = $value;
      }
      if($name == "pta_form_last_name"){
        $last_name = $value;
      }
      if($name == "pta_form_email"){
        $email = $value;
      }
      if($name == "pta_form_password"){
        $password = $value;
      }
      if($name == "pta_form_username"){
        $username = $value;
      }
    }

    $user_data = [
      'user_login' => $username,
      'user_email' => $email,
      'user_pass' => $password,
      'first_name' => $first_name,
      'last_name' => $last_name,
      'role' => 'subscriber',
    ];

    $user_id = wp_insert_user( $user_data );
    if ( is_wp_error( $user_id ) ) {
      // Handle error
      $this->forms->logger->error("Error creating user: " . $user_id->get_error_message());
      return;
    }
    // User created successfully
    $this->forms->logger->info("User created successfully: " . $user_id);
    
    // log in the user
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id );
    do_action( 'wp_login', $username, get_user_by( 'id', $user_id ) );

    // redirect
    // if (isset($_GET['redirect_to'])) {
    //   $redirect_to = $_GET['redirect_to'];
    //   wp_redirect( $redirect_to );
    //   exit;
    // }
  }

}