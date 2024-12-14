<?php
namespace PTA\shortcodes;

/* Prevent direct access */
if (!defined('ABSPATH')) {
    exit;
}

/* Require Class */
use PTA\DB\db_handler;
use PTA\DB\functions\db_functions;
use PTA\DB\functions\submission\submission_functions;
use PTA\DB\functions\image\image_functions;
use PTA\DB\functions\user\user_functions;
use PTA\logger\Log;

/**
 * Class Shortcodes
 *
 * This class is the Shortcodes API for the Portals to Adventure plugin.
 *
 * @package PortalsToAdventure
 */
class Shortcodes_functions
{
    private $logger;
    private $submission_func; // $this->submission_func->
    private $image_func; // $this->image_func->
    private $user_func; // $this->user_func->
    private $handler_instance; // $this->handler_instance->
    private $db_functions; // $this->db_functions->

    public function __construct(
        submission_functions $sub_functions = null,
        image_functions $img_functions = null,
        user_functions $user_functions = null,
        db_handler $handler_instance = null,
        db_functions $db_functions = null
    ) {
        /* Logger */
        $inlog = new Log(name: 'Shortcodes');
        $this->logger = $inlog->getLogger();

        /* Get the handler instance and db functions instance */
        $this->handler_instance = $handler_instance ?? new db_handler();
        $this->db_functions = $db_functions ?? new db_functions();

        /* if handler_instance is null or db_functions is null */
        if ($handler_instance == null || $db_functions == null) {

            /* Set the functions instance in the handler, and initialize the functions */
            $this->handler_instance->set_functions(name: 'functions', function_instance: $this->db_functions);
            $this->db_functions->init(handler_instance: $this->handler_instance);

        }

        /* Get the submission functions instance */
        $this->submission_func = $sub_functions ?? new submission_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);

        /* Get the image functions instance */
        $this->image_func = $img_functions ?? new image_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);

        /* Get the user functions instance */
        $this->user_func = $user_functions ?? new user_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);
    }

    public function register_hooks()
    {
        /* Handle submission upload */
        add_action('wp', [$this, 'handle_submission_upload']);

        /* Handle edit form */
        add_action('wp', [$this, 'handle_edit_form']);
    }

    /**
     * Handles the submission upload.
     * 
     * @since 1.0.0
     */
    public function handle_submission_upload()
    {
        if (isset($_POST['submit_submission']) || isset($_POST['submission_upload_form'])) {
            if (!is_user_logged_in()) {
                return;
            }

            //error_log('POST: ' . print_r($_POST, true));

            $user_id = get_current_user_id();
            $title = sanitize_text_field($_POST['title']);
            $description = sanitize_textarea_field($_POST['description']);
            $video_link = sanitize_text_field($_POST['video_link']);
            $images_ID = [];

            //$logMain->debug('Test sanitize', array('title' => $_POST['title'], 'title_sanitized' => $title, 'description' => $_POST['description'], 'description_sanitized' => $description));

            $user = wp_get_current_user();

            if ($this->user_func->check_user_exists($user_id) == false) {
                // user is logged in but does not exist
                // create user
                $userPerms = $this->db_functions->format_permissions(1, 0, 0, 0);

                $user_id = $this->user_func->register_user(
                    $user->user_email,
                    $user->display_name,
                    $user->first_name,
                    $user->last_name,
                    1,
                    $user->ID,
                    null,
                    $userPerms,
                    null
                );

            }

            // Check if user has already submitted a door today
            $ifUserSubmitted = $this->user_func->check_user_submissions_in_time_period($user_id);

            if ($ifUserSubmitted === null) {
                // got a error
                $this->logger->error('Error checking if user has already submitted a door today', array('username' => $user->display_name));
                return;
            }

            if ($this->user_func->check_user_submissions_in_time_period($user_id) === true) {
                // user has already submitted a door today
                // redirect to the user submissions page

                // log what the instance for logger is
                //error_log('Logger instance: ' . print_r($this->logger, true));

                $this->logger->debug('User has already submitted a door today', array('username' => $user->display_name));

                wp_redirect(home_url('/my-in-progress-secret-doors/?submitted_today=true'));
                return;
            }


            // Save submission data to the database
            $submission_id = $this->submission_func->add_submission(
                user_owner_id: $user_id,
                title: $title,
                description: $description
            );

            // Handle file uploads
            if (!empty($_FILES['images']['name'][0])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                $uploaded_files = $_FILES['images'];
                $upload_overrides = array('test_form' => false);

                $imageCount = 1;
                foreach ($uploaded_files['name'] as $key => $value) {
                    if ($uploaded_files['name'][$key]) {
                        $file = array(
                            'name' => $uploaded_files['name'][$key],
                            'type' => $uploaded_files['type'][$key],
                            'tmp_name' => $uploaded_files['tmp_name'][$key],
                            'error' => $uploaded_files['error'][$key],
                            'size' => $uploaded_files['size'][$key]
                        );

                        $isMapImage = 0;
                        $isThumbnailImage = 0;

                        // check if selecedMap had filename + size as value
                        if ($_POST['selecedMap'] == ($file['name'] . '+' . $file['size'])) {
                            $isMapImage = 1;
                            //error_log('Map Image: ' . $file['name']);
                        }
                        if ($_POST['selecedThumbnail'] == ($file['name'] . '+' . $file['size'])) {
                            $isThumbnailImage = 1;
                            //error_log('Thumbnail Image: ' . $file['name']);
                        }

                        $movefile = wp_handle_upload($file, $upload_overrides);

                        if ($movefile && !isset($movefile['error'])) {
                            //$image_urls[] = $movefile['url'];

                            $movefile['url'] = str_replace('http://', 'https://', $movefile['url']);

                            // Add image to database
                            $image_id = $this->image_func->add_images($user_id, $submission_id, $movefile['url'], $isMapImage, $isThumbnailImage);
                            $images_ID[] = $image_id;

                            $imageCount++;
                        } else {
                            echo "Error uploading file: " . $movefile['error'];
                        }
                    }
                }
            }

            // Handle Map Upload
            if (!empty($_FILES['map']['name'][0])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                $uploaded_files = $_FILES['map'];
                $upload_overrides = array('test_form' => false);

                // log the file name and size
                //error_log('Map File: ' . $uploaded_files['name'][0] . ' Size: ' . $uploaded_files['size'][0]);
                //$log->info('Map File: ' . $uploaded_files['name'][0] . ' Size: ' . $uploaded_files['size'][0]);

                $imageCount = 1;
                foreach ($uploaded_files['name'] as $key => $value) {
                    if ($uploaded_files['name'][$key]) {
                        $file = array(
                            'name' => $uploaded_files['name'][$key],
                            'type' => $uploaded_files['type'][$key],
                            'tmp_name' => $uploaded_files['tmp_name'][$key],
                            'error' => $uploaded_files['error'][$key],
                            'size' => $uploaded_files['size'][$key]
                        );

                        $isMapImage = 1;
                        $isThumbnailImage = 0;

                        $movefile = wp_handle_upload($file, $upload_overrides);

                        if ($movefile && !isset($movefile['error'])) {
                            //$image_urls[] = $movefile['url'];

                            $movefile['url'] = str_replace('http://', 'https://', $movefile['url']);

                            // Add image to database
                            $image_id = $this->image_func->add_images($user_id, $submission_id, $movefile['url'], $isMapImage, $isThumbnailImage);
                            $images_ID[] = $image_id;

                            $imageCount++;
                        } else {
                            echo "Error uploading file: " . $movefile['error'];
                        }
                    }
                }
            }

            // Handle Thumbnail Upload
            if (!empty($_FILES['thumbnail']['name'][0])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                $uploaded_files = $_FILES['thumbnail'];
                $upload_overrides = array('test_form' => false);

                // log the file name and size
                //error_log('Thumbnail File: ' . $uploaded_files['name'][0] . ' Size: ' . $uploaded_files['size'][0]);

                $imageCount = 1;
                foreach ($uploaded_files['name'] as $key => $value) {
                    if ($uploaded_files['name'][$key]) {
                        $file = array(
                            'name' => $uploaded_files['name'][$key],
                            'type' => $uploaded_files['type'][$key],
                            'tmp_name' => $uploaded_files['tmp_name'][$key],
                            'error' => $uploaded_files['error'][$key],
                            'size' => $uploaded_files['size'][$key]
                        );

                        $isMapImage = 0;
                        $isThumbnailImage = 1;

                        $movefile = wp_handle_upload($file, $upload_overrides);

                        if ($movefile && !isset($movefile['error'])) {
                            //$image_urls[] = $movefile['url'];

                            $movefile['url'] = str_replace('http://', 'https://', $movefile['url']);

                            // Add image to database
                            $image_id = $this->image_func->add_images($user_id, $submission_id, $movefile['url'], $isMapImage, $isThumbnailImage);
                            $images_ID[] = $image_id;

                            $imageCount++;
                        } else {
                            echo "Error uploading file: " . $movefile['error'];
                        }
                    }
                }
            }

            // add image ids to submission
            //   $this->submission_func->update_submission($submission_id, 'image_uploads', json_encode($images_ID));
            //   $this->submission_func->update_submission($submission_id, 'video_link', $video_link);
            //   $this->submission_func->update_submission($submission_id, 'image_thumbnail_id', $images_ID[0]);

            $this->submission_func->update_submission($submission_id, [
                'image_uploads' => json_encode($images_ID),
                'video_link' => $video_link,
                'image_thumbnail_id' => $images_ID[0]
            ]);


            $this->logger->info('Submission was uploaded', array('username' => $user->display_name, 'submission_id' => $submission_id, 'title' => $title, 'description' => $description, 'video_link' => $video_link, 'images' => $images_ID));


            // Redirect to the user submissions page
            wp_redirect(home_url('/my-in-progress-secret-doors/?edit_submission_id=' . $submission_id));

        }
    }

    /**
     * Handles the submission edit.
     * 
     * @since 1.0.0
     */
    function handle_edit_form()
    {
        if (isset($_POST['update_submission']) || isset($_POST['submission_edit_form'])) {
            if (!is_user_logged_in()) {
                return;
            }

            //error_log("Handling edit form submission");
            //error_log('POST: ' . print_r($_POST, true));
            $this->logger->debug('Handling edit form submission', array('POST' => $_POST));

            $user_id = get_current_user_id();
            $submission_id = $_POST['submission_id'];

            $title = sanitize_text_field($_POST['submission_title']);
            $description = sanitize_textarea_field($_POST['submission_description']);
            $video_link = sanitize_text_field($_POST['video_link']);

            //error_log('Submission ID: ' . $submission_id);
            //error_log('Title: ' . $title);
            //error_log('Description: ' . $description);

            // Get submission data
            $submission = $this->submission_func->get_submission($submission_id)[0];

            // Check if submission is being deleted
            if ($_POST['update_submission'] == 'Delete') {
                //delete_submission($submission_id);
                //wp_redirect(home_url('/my-in-progress-secret-doors'));
                //error_log('Deleting submission: ' . $submission_id);

                $this->submission_func->remove_submission($submission_id);

                return;
            }

            // Handle setting text data (Title, Description, Video Link)
            // $this->submission_func->update_submission($submission['id'], 'title', $title);
            // $this->submission_func->update_submission($submission['id'], 'description', $description);
            // $this->submission_func->update_submission($submission['id'], 'video_link', $video_link);

            $this->submission_func->update_submission($submission['id'], [
                'title' => $title,
                'description' => $description,
                'video_link' => $video_link
            ]);

            // Handle image removals
            if (!empty($_POST['remove_image'])) {  // $_POST['remove_image'] = [\"fed0e51e-f388-4499-950a-9ebc768a7dec\"]

                // $_POST['remove_image'] to array
                // $remove_images = json_decode($_POST['remove_image'], true);
                //error_log('Remove Images: ' . $_POST['remove_image']);

                $remove_images = explode(',', $_POST['remove_image']);

                foreach ($remove_images as $image_id) {
                    //error_log('Removing image: ' . $image_id);
                    $this->submission_func->remove_image_from_submission(submission_id: $submission['id'], image_id: $image_id);
                }
            }

            // Image file-name to id
            $file_map_id = [];

            // Handle image uploads
            if (!empty($_FILES['new_images']['name'][0])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                $uploaded_files = $_FILES['new_images'];
                $upload_overrides = array('test_form' => false);

                $imageCount = 1;
                foreach ($uploaded_files['name'] as $key => $value) {
                    if ($uploaded_files['name'][$key]) {
                        $file = array(
                            'name' => $uploaded_files['name'][$key],
                            'type' => $uploaded_files['type'][$key],
                            'tmp_name' => $uploaded_files['tmp_name'][$key],
                            'error' => $uploaded_files['error'][$key],
                            'size' => $uploaded_files['size'][$key]
                        );

                        $isMapImage = 0;
                        $isThumbnailImage = 0;

                        $movefile = wp_handle_upload($file, $upload_overrides);

                        if ($movefile && !isset($movefile['error'])) {
                            //$image_urls[] = $movefile['url'];

                            // set  $movefile['url'] from http to https
                            $movefile['url'] = str_replace('http://', 'https://', $movefile['url']);

                            // Add image to database
                            $image_id = $this->image_func->add_images($user_id, $submission_id, $movefile['url'], $isMapImage, $isThumbnailImage);
                            $images_IDs[] = $image_id;

                            //error_log('Images: ' . print_r($images_IDs, true));

                            // check if selecedMap had filename + size as value
                            // if (isset($_POST['set_map'])) {

                            //     if ($_POST['set_map'] == ($file['name'] . '+' . $file['size'])) {
                            //         $_POST['set_map'] = $image_id;
                            //     }

                            // }
                            // if (isset($_POST['set_thumbnail'])) {

                            //     if ($_POST['set_thumbnail'] == ($file['name'] . '+' . $file['size'])) {
                            //         $_POST['set_thumbnail'] = $image_id;
                            //     }

                            // }

                            $file_name = $file['name'] . '+' . $file['size'];

                            $file_map_id[$file_name] = $image_id;

                            $this->submission_func->add_image_to_submission($submission_id, $image_id);

                            // $imageCount++;
                        } else {
                            echo "Error uploading file: " . $movefile['error'];
                        }
                    }
                }
                // add image ids to submission
                // $this->submission_func->update_submission($submission_id, 'image_uploads', json_encode($images_IDs));
                // $this->logger->debug('Images IDs', array('images_IDs' => $images_IDs));
                //$this->submission_func->update_submission($submission_id, ['image_uploads' => json_encode($images_IDs)]);
                // $this->submission_func->add_image_to_submission($submission_id, $images_IDs);
            }

            //$this->logger->debug('File Map ID', array('file_map_id' => $file_map_id));

            // Handle setting a new thumbnail
            if (isset($_POST['set_thumbnail'])) {
                //error_log('Setting Thumbnail: ' . $_POST["set_thumbnail"]);

                $thumbnail_id = $_POST["set_thumbnail"];

                //$this->logger->debug('Setting Thumbnail', array('thumbnail_id' => $thumbnail_id));

                // check if thumbnail id is in the file_map_id array
                if (array_key_exists($thumbnail_id, $file_map_id)) {
                    $thumbnail_id = $file_map_id[$thumbnail_id];
                }

                //$this->logger->debug('Thumbnail ID', array('thumbnail_id' => $thumbnail_id));

                //error_log('Thumbnail ID: ' . $_POST["set_thumbnail"]);
                // First reset all thumbnails
                $this->image_func->image_reset_thumbnail($submission['id']);
                // Set the selected image as thumbnail
                $this->image_func->image_set_thumbnail($thumbnail_id);
            }

            // Handle setting a new Map
            if (isset($_POST['set_map'])) {
                //error_log('Setting Map: ' . $_POST["set_map"]);

                $map_id = $_POST["set_map"];

                //$this->logger->debug('Setting Map', array('map_id' => $map_id));

                // check if map id is in the file_map_id array
                if (array_key_exists($map_id, $file_map_id)) {
                    $map_id = $file_map_id[$map_id];
                }

                //$this->logger->debug('Map ID', array('map_id' => $map_id));

                //error_log('Map ID: ' . $_POST["set_map"]);
                // First reset all thumbnails
                $this->image_func->image_reset_map($submission['id']);
                // Set the selected image as thumbnail
                $this->image_func->image_set_map($map_id);
            }

            // Handle Video Link Update
            if (isset($_POST['video_link'])) {
                // $this->submission_func->update_submission($submission['id'], 'video_link', $_POST['video_link']);
                $this->submission_func->update_submission($submission['id'], ['video_link' => $_POST['video_link']]);
            }

            // handle Save and Publish
            if ($_POST['update_submission'] == 'Save') {
                // set status to 'progress'
                //update_submission($submission['id'], 'state', 'In Progress');

                // if the submission was 'Rejected' then remove the rejected reason
                if ($submission['state'] == 'Rejected') {
                    $this->submission_func->update_submission($submission['id'], [
                        'state' => 'In Progress',
                        'rejected_reason' => null,
                        'is_rejected' => 0
                    ]);
                } else {
                    $this->submission_func->update_submission($submission['id'], ['state' => 'In Progress']);
                }

            } elseif ($_POST['update_submission'] == 'Publish') {
                // set status to 'pending'
                //update_submission($submission['id'], 'state', 'Pending Approval'); // Pending Approval

                if ($submission['state'] == 'Rejected') {
                    // $this->submission_func->set_submission_unrejeced_state($submission['id'], 'Pending Approval');
                    $this->submission_func->update_submission($submission['id'], [
                        'state' => 'Pending Approval',
                        'rejected_reason' => null,
                        'is_rejected' => 0
                    ]);
                } else {
                    // $this->submission_func->update_submission($submission['id'], 'state', 'Pending Approval');
                    $this->submission_func->update_submission($submission['id'], ['state' => 'Pending Approval']);
                }

                // redirect to view and vote page
                wp_redirect(home_url('/view-and-vote'));
            }

            $user = wp_get_current_user();

            $this->logger->info('Submission was updated', array('username' => $user->display_name, 'submission_id' => $submission_id, 'title' => $title, 'description' => $description, 'images' => $submission['image_uploads']));
        }
    }

}