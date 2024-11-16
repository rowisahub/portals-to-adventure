<?php
/*
File: db-functions.php
Description: Database functions for the plugin.
Authors: Rowan Wachtler, Braedon Salwoski
Created: 09-12-2024
Version: 1.0
*/

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// List of functions (Ctrl+Shift+O)
// 1. Create Plugin Tables    | In Use
// 2. Remove Custom Tables    | Not In Use
// 3. Check ID Exists
// 4. Register User
// 5. Get User Permissions
// 6. Add Submission
// 7. Add Images
// 8. Update Submission
// 9. Get Submission
// 10. Get Submission By User
// 11. Update Image
// 12. Remove Image
// 13. Remove Image From Submission
// 14. Get User By ID
// 15. Remove User
// 16. Get Submission Value
// 17. Get Submission By State
// 18. Get all submissions
// 19. Get Submission Images
// 20. Get Image Data
// 21. Get Submission URL
// 22. Get Image URL
// 23. Insert Dummy Data
// 24. Images Reset Thumbnail
// 25. Images Reset Map
// 26. Set Image Thumbnail
// 27. Set Image Map
// 28. Get Submission By State
// 29. Get All Submissions By State
// 30. Get All Submissions


// Requires
require_once plugin_dir_path(__FILE__) . '../util-functions.php';
require_once plugin_dir_path(__FILE__) . '../pta-logger.php';

// Functions

/**
 * Creates the plugin tables.
 *
 * This function is responsible for creating the necessary database tables for the plugin.
 * It should be called during the plugin activation process.
 *
 * @since 1.0.0
 * @return void
 */
function create_plugin_tables()
{
  global $logDB;

  $logDB->info('Creating tables for pta plugin');

  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();

  // wld prefix
  $wld_prefix = 'wld_pta_';

  // check if tables already exist
  $table_name_user_info = $wpdb->prefix . $wld_prefix . 'user_info';
  $table_name_submission_data = $wpdb->prefix . $wld_prefix . 'submission_data';
  $table_name_image_data = $wpdb->prefix . $wld_prefix . 'image_data';

  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_user_info'") == $table_name_user_info) {
    $logDB->debug('Table user_info already exists');
  } else {
    // create table
    $logDB->debug('Creating table user_info');

    // Table 1: User Info
    //$table_name_user_info = $wpdb->prefix . $wld_prefix . 'user_info';
    $sql_user_info = "CREATE TABLE $table_name_user_info (
          id varchar(255) NOT NULL,
          token varchar(255) NOT NULL,
          email varchar(255) NOT NULL,
          username varchar(255) NOT NULL,
          birthday date DEFAULT NULL,
          permissions varchar(4) DEFAULT '0000', -- 4-bit binary string for permissions (permissions to send email, is admin, can review submissions, is banned)
          payment_info text DEFAULT NULL,
          created_at timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (id)
      ) $charset_collate;";

    dbDelta($sql_user_info);
  }

  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_submission_data'") == $table_name_submission_data) {

    $logDB->debug('Table submission_data already exists');

  } else {
    // create table
    $logDB->debug('Creating table submission_data');

    // Table 2: Submission Data
    //$table_name_submission_data = $wpdb->prefix . $wld_prefix . 'submission_data';

    // Add Map Image ID
    $sql_submission_data = "CREATE TABLE $table_name_submission_data (
          id varchar(255) NOT NULL,
          user_owner_id varchar(255) NOT NULL,
          registration_method varchar(50) NOT NULL,
          title varchar(255) NOT NULL,
          description text NOT NULL,
          image_uploads longtext DEFAULT NULL,
          video_link varchar(255) DEFAULT NULL,
          image_thumbnail_id varchar(255) DEFAULT NULL,
          views bigint(20) DEFAULT 0,
          likes_votes bigint(20) DEFAULT 0,
          state varchar(50) DEFAULT 'In Progress',
          is_rejected tinyint(1) DEFAULT 0,
          was_rejected tinyint(1) DEFAULT 0,
          rejected_reason text DEFAULT NULL,
          is_removed tinyint(1) DEFAULT 0,
          removed_reason text DEFAULT NULL,
          created_at timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (id),
          FOREIGN KEY  (user_owner_id) REFERENCES $table_name_user_info(id) ON DELETE CASCADE
      ) $charset_collate;";

    dbDelta($sql_submission_data);
  }

  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_image_data'") == $table_name_image_data) {
    $logDB->debug('Table image_data already exists');
  } else {
    // create table
    $logDB->debug('Creating table image_data');

    // Table 3: Image Data
    //$table_name_image_data = $wpdb->prefix . $wld_prefix . 'image_data';
    $sql_image_data = "CREATE TABLE $table_name_image_data (
          image_id varchar(255) NOT NULL,
          user_id varchar(255) NOT NULL,
          submission_id varchar(255) NOT NULL,
          image_reference varchar(255) NOT NULL,
          is_thumbnail tinyint(1) DEFAULT 0,
          is_map tinyint(1) DEFAULT 0,
          created_at timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (image_id),
          FOREIGN KEY  (user_id) REFERENCES $table_name_user_info(id) ON DELETE CASCADE,
          FOREIGN KEY  (submission_id) REFERENCES $table_name_submission_data(id) ON DELETE CASCADE
      ) $charset_collate;";

    dbDelta($sql_image_data);
  }

  // debug
  $logDB->info('Tables created for pta plugin');
}
// register_activation_hook('../portals_to_adventure.php', 'create_plugin_tables');

/**
 * Removes custom tables from the database.
 *
 * This function is intended to be used for cleaning up custom tables
 * that were created by the plugin. It should be called when the plugin
 * is uninstalled or deactivated to ensure that no unnecessary data is
 * left in the database.
 *
 * @return void
 */
function remove_custom_tables()
{
  global $logDB;

  $logDB->info('Removing tables for pta plugin');

  global $wpdb;

  $wld_prefix = 'wld_pta_';

  // check if tables already exist
  $table_name_user_info = $wpdb->prefix . $wld_prefix . 'user_info';
  $table_name_submission_data = $wpdb->prefix . $wld_prefix . 'submission_data';
  $table_name_image_data = $wpdb->prefix . $wld_prefix . 'image_data';

  // set foreign key checks to OFF
  //$wpdb->query('SET FOREIGN_KEY_CHECKS=OFF');

  // SQL query to drop the table
  $sql = "DROP TABLE IF EXISTS $table_name_user_info";
  $sql2 = "DROP TABLE IF EXISTS $table_name_submission_data";
  $sql3 = "DROP TABLE IF EXISTS $table_name_image_data";

  // Execute the query
  $wpdb->query($sql3); // remove image_data table first as it has foreign keys from submission_data and user_info
  $wpdb->query($sql2); // remove submission_data table next as it has foreign keys from user_info
  $wpdb->query($sql); // remove user_info table last as it has no foreign keys

  // check if tables were removed
  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_user_info'") == $table_name_user_info) {
    $logDB->warning('Table user_info was not removed');
  } else {
    $logDB->debug('Table user_info was removed');
  }

  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_submission_data'") == $table_name_submission_data) {
    $logDB->warning('Table submission_data was not removed');
  } else {
    $logDB->debug('Table submission_data was removed');
  }

  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_image_data'") == $table_name_image_data) {
    $logDB->warning('Table image_data was not removed');
  } else {
    $logDB->debug('Table image_data was removed');
  }

  // set foreign key checks to ON
  //$wpdb->query('SET FOREIGN_KEY_CHECKS=ON');
}
// register_deactivation_hook('../portals_to_adventure.php', 'remove_custom_tables');

/**
 * Check if an ID exists in a specified table.
 *
 * @param int $id The ID to check.
 * @param string $table_name The name of the table to search in.
 * @return bool Returns true if the ID exists in the table, false otherwise.
 */
function check_id_exists($id, $table_name)
{
  global $wpdb;
  $wld_prefix = 'wld_pta_';
  $table_name_short = $table_name;
  $table_name = $wpdb->prefix . $wld_prefix . $table_name;

  // check if table is image_data
  if ($table_name_short == 'image_data') {
    // pull different ID name.
    $result = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE image_id = %s", $id));
    return count($result) > 0;
  }

  $result = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $id));
  return count($result) > 0;
}

/**
 * Registers a user.
 *
 * This function is responsible for creating a user in both WordPress and our database. It performs a check on the provided email and username before creating the user.
 *
 * @param string $email The email of the user.
 * @param string $username The username of the user.
 * @param string|null $firstName The first name of the user. (optional)
 * @param string|null $lastName The last name of the user. (optional)
 * @param int $verified_email The verification status of the email. (optional)
 * @param string $token The token for verification. (optional)
 * @param string|null $birthday The birthday of the user. (optional)
 * @param string $permissions The permissions of the user. (optional)
 * @param mixed|null $payment_info The payment information of the user. (optional)
 * @return int|null The user ID if the user is successfully registered, null otherwise.
 */
function register_user($email, $username, $firstName = null, $lastName = null, $verified_email = 0, $token = '', $birthday = null, $permissions = '', $payment_info = null)
{
  global $wpdb;
  global $logDB;

  $table_name = $wpdb->prefix . 'wld_pta_user_info';

  // also need to register user in WordPress

  // check if user already exists
  $user = get_user_by('email', $email);

  if (!$user) {
    // create user
    $user_id = wp_create_user($username, wp_generate_password(15, true, false), $email);

    if (is_wp_error($user_id)) {
      wp_send_json_error(array('message' => $user_id->get_error_message()));
    }

    // add user metadata
    if ($firstName != null) {
      update_user_meta($user_id, 'first_name', $firstName);
    }
    if ($lastName != null) {
      update_user_meta($user_id, 'last_name', $lastName);
    }

    if ($verified_email == 1) {
      add_user_meta($user_id, '_user_verified_email', 'true');
    } else {
      add_user_meta($user_id, '_user_verified_email', 'false');
    }

    $user = get_user_by('id', $user_id);
  }

  if (!$user) {
    // error creating user
    $logDB->error('Error creating user');
    return null;
  }

  $user_id = $user->ID;

  // add user to user_info table

  // check if user already exists
  if (check_id_exists($user_id, 'user_info')) {
    $logDB->debug('User already exists in user_info table');
    return $user_id;
  }

  $wpdb->insert($table_name, array(
    'id' => $user_id,
    'token' => $token,
    'email' => $email,
    'username' => $username,
    'birthday' => $birthday,
    'permissions' => $permissions,
    'payment_info' => $payment_info
  ));

  $logDB->info('User created in user_info table');

  return $user_id;
}

/**
 * Retrieves the permissions of a user.
 *
 * @param int $user_id The ID of the user.
 * @param string|null $perm The specific permission to retrieve. (optional)
 * @return int 0 | 1 The permissions of the user.
 */
function get_user_permissions($user_id, $perm = null)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_user_info';

  $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $user_id), ARRAY_A);

  if ($perm != null) {
    return $result['permissions'][$perm];
  }

  return $result['permissions'];
}

/**
 * Adds a submission to the portals_to_adventure plugin.
 *
 * @param int    $user_owner_id         The ID of the user who owns the submission.
 * @param string $title                 The title of the submission.
 * @param string $description           The description of the submission.
 * @param string $registration_method   The registration method for the submission. Default is 'manual'.
 * @param array  $image_uploads         An array of image uploads for the submission. Default is null.
 * @param string $video_link            The video link for the submission. Default is null.
 * @param int    $image_thumbnail_id    The ID of the image thumbnail for the submission. Default is null.
 * @param int    $views                 The number of views for the submission. Default is 0.
 * @param int    $likes_votes           The number of likes/votes for the submission. Default is 0.
 * @param string $state                 The state of the submission. Default is 'In Progress'.
 * @return string The ID of the submission.
 */
function add_submission($user_owner_id, $title, $description, $registration_method = 'manual', $image_uploads = null, $video_link = null, $image_thumbnail_id = null, $views = 0, $likes_votes = 0, $state = 'In Progress')
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_submission_data';
  $uuidV = gennerate_uuid('submission_data');

  //$image_uploads_json = json_encode($image_uploads);

  $wpdb->insert($table_name, array(
    'id' => $uuidV,
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
  ));

  return $uuidV;
}

/**
 * Adds an image to the specified user's submission.
 *
 * @param int $user_owner_id The ID of the user who owns the submission.
 * @param int $submission_id The ID of the submission.
 * @param string $imageURL The URL of the image to be added.
 * @param int $is_thumbnail Optional. Whether the image is a thumbnail or not. Default is 0.
 * @return string The ID of the image.
 */
function add_images($user_owner_id, $submission_id, $imageURL, $is_thumbnail = 0, $is_map = 0)
{
  // add images to image_data table
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_image_data';

  // loop through images and add to database

  // image = array('image_reference' => 'path/to/image', 'is_thumbnail' => 0)


  $uuidV = gennerate_uuid('image_data');
  $wpdb->insert($table_name, array(
    'image_id' => $uuidV,
    'user_id' => $user_owner_id,
    'submission_id' => $submission_id,
    'image_reference' => $imageURL,
    'is_thumbnail' => $is_thumbnail,
    'is_map' => $is_map
  ));

  return $uuidV;
}

/**
 * Updates a submission with a new key-value pair.
 *
 * @param int $submission_id The ID of the submission to update.
 * @param string $key The key of the value to update.
 * @param mixed $value The new value to assign to the key.
 * @return int|false The number of rows updated, or false on error.
 */
function update_submission($submission_id, $key, $value)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_submission_data';

  return $wpdb->update($table_name, array($key => $value), array('id' => $submission_id));
}

/**
 * Retrieves a submission by its ID.
 *
 * @param int $submission_id The ID of the submission to retrieve.
 * @return mixed The submission data.
 */
function get_submission($submission_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_submission_data';

  $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $submission_id), ARRAY_A);

  return $result;
}

/**
 * Retrieves submissions by user ID.
 *
 * @param int $user_id The ID of the user.
 * @return array An array of submissions made by the user.
 */
function get_submissions_by_user($user_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_submission_data';

  $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_owner_id = %s", $user_id), ARRAY_A);

  return $results;
}

/**
 * Updates the specified image with the given key-value pair.
 *
 * @param int $image_id The ID of the image to update.
 * @param string $key The key of the value to update.
 * @param mixed $value The new value to assign to the specified key.
 * @return int|false The number of rows updated, or false on error.
 */
function update_image($image_id, $key, $value)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_image_data';

  return $wpdb->update($table_name, array($key => $value), array('image_id' => $image_id));
}

/**
 * Removes an image from the database.
 *
 * @param int $image_id The ID of the image to be removed.
 * @return int|false The number of rows deleted, or false on error.
 */
function remove_image($image_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_image_data';

  return $wpdb->delete($table_name, array('image_id' => $image_id));
}

/**
 * Removes an image from a submission (WIP)
 * 
 * @warning This function is still a work in progress (WIP).
 *
 * @param int $submission_id The ID of the submission.
 * @param int $image_id The ID of the image to be removed.
 * @return void
 */
function remove_image_from_submission($submission_id, $image_id)
{
  global $wpdb;
  global $logDB;

  $table_name = $wpdb->prefix . 'wld_pta_submission_data';

  $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $submission_id), ARRAY_A);



  // error_log('remove_image_from_submission');

  //error_log('Submission: ' . print_r($submission, true));

  $images = $submission['image_uploads'];

  //error_log('Images: ' . $images);

  $image_json = json_decode($images);

  //error_log('Images Jsons: ' . print_r($image_json, true));

  //explode(',', $image_json);

  $new_image_array = [];

  // // remove image from array
  foreach ($image_json as $key => $value) {
    if ($value != $image_id) {
      $new_image_array[] = $value;
    }
  }

  //error_log('Images after: ' . print_r($new_image_array, true));

  $new_image_array_json = json_encode($new_image_array); // GOT CORRECT JSON

  //error_log('Images after: ' . print_r($new_image_array_json, true));


  // update submission
  update_submission($submission_id, 'image_uploads', $new_image_array_json);

  // error_log(message: 'Upadted submission images');

  //error_log('Submission after: ' . print_r(get_submission($submission_id), true));

  remove_image($image_id);

  // error_log(message: 'Removed image');
  $logDB->debug('Removed image from submission');

}

/**
 * Retrieves a user by their ID.
 *
 * @param int $user_id The ID of the user to retrieve.
 * @return mixed The user object if found, otherwise false.
 */
function get_user_by_id($user_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_user_info';

  $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $user_id), ARRAY_A);

  return $result;
}

/**
 * Check if a user exists in the database.
 *
 * This function checks whether a user with the given user ID exists in the database.
 *
 * @param int $user_id The ID of the user to check.
 * @return bool True if the user exists, false otherwise.
 */
function check_user_exists($user_id)
{
  $result = get_user_by_id($user_id);

  if (!$result) {
    return false;
  }

  return true;
}

/**
 * (WIP) Removes a user from the system
 * 
 * @warning This function is still a work in progress (WIP).
 *
 * @param int $user_id The ID of the user to be removed.
 * @return void
 */
function remove_user($user_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_user_info';

  // get user
  $userDB = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $user_id), ARRAY_A);
  // get user from wp
  $userWP = get_user_by('id', $user_id);

  //return $wpdb->delete($table_name, array('id' => $user = $user_id));
}

/**
 * Retrieves the value of a specific key from a submission.
 *
 * @param int $submission_id The ID of the submission.
 * @param string $key The key of the value to retrieve.
 * @return mixed The value of the specified key from the submission.
 */
function get_submission_value($submission_id, $key)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_submission_data';

  $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $submission_id), ARRAY_A);

  return $result[$key];
}

/**
 * Retrieves a submission by its owner ID and state.
 *
 * @param int $user_owner_id The ID of the submission owner.
 * @param string $state The state of the submission.
 * @return mixed The submission data if found, otherwise false.
 */
function get_submission_by_state($user_owner_id, $state)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_submission_data';

  $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_owner_id = %s AND state = %s", $user_owner_id, $state), ARRAY_A);

  return $results;
}


/**
 * Retrieves all submissions filtered by state.
 *
 * This function fetches a specified number of submissions from the database
 * that match the given state.
 *
 * @param string $state The state to filter submissions by.
 * @param int $numOfSubmissions Optional. The number of submissions to retrieve. Default is 10.
 *
 * @return array An array of submissions that match the given state.
 */
function get_all_submissions_by_state($state, $numOfSubmissions = 10)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_submission_data';

  $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE state = %s LIMIT %d", $state, $numOfSubmissions), ARRAY_A);

  return $results;
}

/**
 * Retrieve all submissions from the database.
 *
 * This function fetches all the submissions stored in the database.
 *
 * @return array An array of submissions.
 */
function get_all_submissions()
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_submission_data';

  $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

  return $results;
}

/**
 * Retrieves the images associated with a submission.
 *
 * @param int $submission_id The ID of the submission.
 * @return array An array of images associated with the submission.
 */
function get_submission_images($submission_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_submission_data';

  //error_log('Submission ID: ' . $submission_id);

  //$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $submission_id), ARRAY_A);
  $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $submission_id), ARRAY_A);

  //error_log('Results: ' . print_r($result, true));
  //error_log('Results IU: ' . print_r($result['image_uploads'], true));

  // string to array
  $image_json = json_decode($result['image_uploads']);

  //error_log('Image JSON: ' . print_r($image_json, true));


  $images = array();
  foreach ($image_json as $imResult) {
    $images[] = get_image_data($imResult);
  }

  return $images;
}

/**
 * Retrieves the image data for a given image ID.
 *
 * @param int $image_id The ID of the image.
 * @return mixed The image data.
 */
function get_image_data($image_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_image_data';

  $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE image_id = %s", $image_id), ARRAY_A);

  return $result;
}

/**
 * Retrieves the URL for a specific submission.
 *
 * @param int $submission_id The ID of the submission.
 * @return string The URL of the submission.
 */
function get_submission_url($submission_id)
{
  return add_query_arg(array('submission_id' => $submission_id), "/my-submitted-secret-doors");
}

/**
 * Retrieves the URL of an image based on its ID.
 *
 * @param int $image_id The ID of the image.
 * @return string The URL of the image.
 */
function get_image_url($image_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_image_data';

  $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE image_id = %s", $image_id), ARRAY_A);

  //error_log('Image: ' . print_r($result, true));
  //error_log('Image ID: ' . $image_id);

  if ($result === null) {
    // error_log('No image found with ID: ' . $image_id);
    return null;
  }

  return $result['image_reference'];
}

/**
 * Resets the thumbnail image for a given submission.
 *
 * This function resets the thumbnail image associated with a specific submission ID.
 *
 * @param int $submission_id The ID of the submission for which the thumbnail image should be reset.
 * 
 * @return void
 */
function images_reset_thumbnail($submission_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_image_data';

  $wpdb->update($table_name, array('is_thumbnail' => 0), array('submission_id' => $submission_id));
}

/**
 * Resets the image map for a given submission.
 *
 * This function is responsible for resetting the image map associated with a specific submission.
 *
 * @param int $submission_id The ID of the submission for which the image map should be reset.
 *
 * @return void
 */
function images_reset_map($submission_id)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_image_data';

  $wpdb->update($table_name, array('is_map' => 0), array('submission_id' => $submission_id));
}

/**
 * Sets the thumbnail status for a given image.
 *
 * This function updates the thumbnail status of an image based on the provided image ID.
 *
 * @param int $image_id The ID of the image to update.
 * @param int $value Optional. The value to set for the thumbnail status. Default is 1.
 *
 * @return void
 */
function set_image_thumbnail($image_id, $value = 1)
{
  update_image($image_id, 'is_thumbnail', $value);
}

/**
 * Sets the image map value for a given image ID.
 * 
 * This function updates the map status of an image based on the provided image ID.
 *
 * @param int $image_id The ID of the image to set the map value for.
 * @param int $value The value to set for the image map. Default is 1.
 */
function set_image_map($image_id, $value = 1)
{
  update_image($image_id, 'is_map', $value);
}

/**
 * Sets the submission status to rejected and records a rejection message.
 *
 * @param int $submission_id The ID of the submission to be rejected.
 * @param string $message The rejection message to be recorded.
 */
function set_submission_rejeced($submission_id, $message)
{
  update_submission($submission_id, 'state', "Rejected");
  update_submission($submission_id, 'rejected_reason', $message);
  update_submission($submission_id, 'is_rejected', 1);
  update_submission($submission_id, 'was_rejected', 1);
}

/**
 * Marks a submission as unrejected.
 *
 * This function updates the status of a submission to indicate that it is no longer rejected.
 *
 * @param int $submission_id The ID of the submission to be marked as unrejected.
 */
function set_submission_unrejeced($submission_id)
{
  update_submission($submission_id, 'state', 'In Progress');
  update_submission($submission_id, 'is_rejected', 0);
  update_submission($submission_id, 'rejected_reason', null);

}

/**
 * Sets the un-rejected state of a submission.
 *
 * @param int $submission_id The ID of the submission.
 * @param string $state The state to set for the submission.
 */
function set_submission_unrejeced_state($submission_id, $state)
{
  update_submission($submission_id, 'state', $state);
  update_submission($submission_id, 'is_rejected', 0);
  update_submission($submission_id, 'rejected_reason', null);
}

/**
 * Removes a submission from the database.
 *
 * @param int $submission_id The ID of the submission to be removed.
 * @param string $message Optional. The message to log for the removal. Default is 'Removed By User'.
 */
function remove_submission($submission_id, $message = 'Submission Removed By User')
{
  // Soft remove submission
  update_submission($submission_id, 'state', "Removed");
  update_submission($submission_id, "is_removed", 1);
  update_submission($submission_id, "removed_reason", $message);
}

/**
 * Unremoves a submission by its ID.
 *
 * This function restores a previously removed submission, making it active again.
 *
 * @param int $submission_id The ID of the submission to unremove.
 * @return void
 */
function unremove_submission($submission_id)
{
  update_submission($submission_id, 'state', 'In Progress');
  update_submission($submission_id, 'is_removed', 0);
  update_submission($submission_id, 'removed_reason', null);
}

/**
 * Increment the view count for a given submission.
 *
 * This function updates the view count for a specific submission identified by its ID.
 *
 * @param int $submission_id The ID of the submission whose view count is to be incremented.
 * @return void
 */
function add_view_count($submission_id)
{
  $submission = get_submission($submission_id);
  $view_count = $submission['views'];
  $view_count++;
  update_submission($submission_id, 'views', $view_count);
}

// Function for updating a database table


// function for checking how many submissions a user has made in a given time period (e.g. 24 hours)
/**
 * Check the number of submissions made by a user within a specified time period.
 *
 * @param int $user_id The ID of the user whose submissions are being checked.
 * @param string $time_period The time period within which to check for submissions. 
 *                            This should be a valid time period string (e.g., '1 hour', '1 day').
 *
 * @return int The number of submissions made by the user within the specified time period.
 */
function check_submissions_in_time_period($user_id, $time_period)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'wld_pta_submission_data';

  // filter if $time_period is in hours or days
  if (strpos($time_period, 'day') !== false || strpos($time_period, 'days') !== false) {
    $time_period = str_replace('day', '', $time_period);
    $time_period = str_replace('s', '', $time_period);
    $time_period = $time_period * 24;
  } else if (strpos($time_period, 'hour') !== false || strpos($time_period, 'hours') !== false) {
    $time_period = str_replace('hour', '', $time_period);
    $time_period = str_replace('s', '', $time_period);
  } else {
    return 0;
  }

  $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_owner_id = %s AND created_at >= NOW() - INTERVAL %s HOUR", $user_id, $time_period), ARRAY_A);

  return count($results);
}

/**
 * Checks the submission data for a given user.
 *
 * This function verifies the data submitted by a user based on their user ID.
 *
 * @param int $user_id The ID of the user whose submission data is to be checked.
 * @return bool True if the user has submitted data, false otherwise.
 */
function check_user_submission_data($user_id)
{
  // pta_number_of_submissions_per_time_period
  // pta_time_period

  $pta_number_of_submissions_per_time_period = get_option('pta_number_of_submissions_per_time_period', 1);
  $pta_time_period = get_option('pta_time_period', 'days');

  $pta_time_period_string = $pta_number_of_submissions_per_time_period . ' ' . $pta_time_period;

  $submissions = check_submissions_in_time_period($user_id, $pta_time_period_string);

  if ($submissions >= $pta_number_of_submissions_per_time_period) {
    return true;
  } else {
    return false;
  }

}

function add_submission_vote($submission_id, $count = 1)
{
  if (!check_id_exists($submission_id, 'submission_data')) {
    return false;
  }
  $submission = get_submission($submission_id);
  $votes = $submission['likes_votes'] + $count;
  update_submission($submission_id, 'likes_votes', $votes);
  return true;
}