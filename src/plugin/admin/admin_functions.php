<?php
namespace PTA\admin;

/* Prevent direct access */
if (!defined('ABSPATH')) {
    exit;
}

/* Requires */
use PTA\DB\db_handler;
use PTA\DB\functions\db_functions;
use PTA\DB\functions\submission\submission_functions;
use PTA\DB\functions\image\image_functions;
use PTA\DB\functions\user\user_functions;
use PTA\logger\Log;

/**
 * Admin functions class for the plugin.
 */
class admin_functions{
    private $logger;
    private $submission_func;
    private $image_func;
    private $user_func;
    private $handler_instance;
    private $db_functions;

    public function __construct() {
        $this->logger = new Log('Admin Functions');
    }

    public function init(
        submission_functions $sub_functions,
        image_functions $img_functions,
        user_functions $user_functions,
        db_handler $handler_instance = null,
        db_functions $db_functions = null
    ){
        $this->logger = $this->logger->getLogger();
        //$this->logger->info('Initializing admin functions.');

        // Get the handler instance and db functions instance
        $this->handler_instance = $handler_instance ?? new db_handler();
        $this->db_functions = $db_functions ?? new db_functions();

        // if handler_instance is null or db_functions is null, set them
        if ($handler_instance == null || $db_functions == null) {

            // Set the functions instance in the handler, and initialize the functions
            $this->handler_instance->set_functions(name: 'functions', function_instance: $this->db_functions);
            $this->db_functions->init(handler_instance: $this->handler_instance);

        }

        // Set the functions instances for the submission, image, and user functions
        $this->submission_func = $sub_functions ?? new submission_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);
        $this->image_func = $img_functions ?? new image_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);
        $this->user_func = $user_functions ?? new user_functions(handler_instance: $this->handler_instance, db_functions: $this->db_functions);
    }

    /**
     * Approves a submission based on the provided submission ID.
     *
     * This function is used to mark a specific submission as approved.
     *
     * @param int $submission_id The ID of the submission to be approved.
     * @return void
     */
    public function approve_submission($submission_id){
        $this->logger->info('Approving submission with ID: ' . $submission_id);
        $updateData = [
            'state' => 'Approved'
        ];
        $this->submission_func->update_submission($submission_id, $updateData);
    }

    /**
     * Rejects a submission with a given reason.
     *
     * This function marks a submission as rejected and records the reason for the rejection.
     *
     * @param int $submission_id The ID of the submission to be rejected.
     * @param string $reason The reason for rejecting the submission.
     * @return void
     */
    public function reject_submission($submission_id, $reason){
        $this->logger->info('Rejecting submission with ID: ' . $submission_id);
        $updateData = [
            'state' => 'Rejected',
            'rejected_reason' => $reason,
            'is_rejected' => 1,
            'was_rejected' => 1
        ];
        $this->submission_func->update_submission($submission_id, $updateData);
    }

    /**
     * Unrejects a previously rejected submission.
     *
     * This function changes the status of a submission to unrejected based on the provided submission ID.
     *
     * @param int $submission_id The ID of the submission to be unrejected.
     * @return void
     */
    public function unreject_submission($submission_id){
        $this->logger->info('Unrejecting submission with ID: ' . $submission_id);
        $updateData = [
            'state' => 'In Progress',
            'is_rejected' => 0,
            'rejected_reason' => null
        ];
        $this->submission_func->update_submission($submission_id, $updateData);
    }

    
    /**
     * Deletes a submission based on the provided submission ID and reason.
     *
     * @param int $submission_id The ID of the submission to be deleted.
     * @param string $reason The reason for deleting the submission.
     * @return void
     */
    public function delete_submission($submission_id, $reason){
        $this->logger->info('Deleting submission with ID: ' . $submission_id);
        $updateData = [
            'state' => 'Removed',
            'removed_reason' => $reason,
            'is_removed' => 1
        ];
        $this->submission_func->update_submission($submission_id, $updateData);
    }

    /**
     * Undeletes a previously deleted submission.
     *
     * This function changes the status of a submission to undeleted based on the provided submission ID.
     *
     * @param int $submission_id The ID of the submission to be undeleted.
     * @return void
     */
    public function undelete_submission($submission_id){
        $this->logger->info('Undeleting submission with ID: ' . $submission_id);
        $updateData = [
            'state' => 'In Progress',
            'is_removed' => 0,
            'removed_reason' => null
        ];
        $this->submission_func->update_submission($submission_id, $updateData);
    }
}