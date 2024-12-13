<?php
namespace PTA\admin;

/* Prevent direct access */
if (!defined('ABSPATH')) {
    exit;
}

/* Requires */
use PTA\logger\Log;
use PTA\DB\functions\submission\submission_functions;


/**
 * Admin functions class for the plugin.
 */
class admin_functions
{

    private $logger;
    private $submission_functions;
    private static $constructed = false;

    public function __construct(
        submission_functions $submission_functions = null
    )
    {
        if($submission_functions == null) {
            throw new \Exception('Submission functions not provided.');
        }

        if(self::$constructed){
            return;
        }

        $this->logger = new Log(name: 'Admin');
        $this->logger = $this->logger->getLogger();
        $this->submission_functions = $submission_functions;

        self::$constructed = true;
    }

    /**
     * Approves a submission based on the provided submission ID.
     *
     * This function is used to mark a specific submission as approved.
     *
     * @param int $submission_id The ID of the submission to be approved.
     * @return void
     */
    public function approve_submission($submission_id)
    {
        $this->logger->info('Approving submission with ID: ' . $submission_id);
        $updateData = [
            'state' => 'Approved'
        ];
        $this->submission_functions->update_submission($submission_id, $updateData);
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
    public function reject_submission($submission_id, $reason)
    {
        $this->logger->info('Rejecting submission with ID: ' . $submission_id);
        $updateData = [
            'state' => 'Rejected',
            'rejected_reason' => $reason,
            'is_rejected' => 1,
            'was_rejected' => 1
        ];
        $this->submission_functions->update_submission($submission_id, $updateData);
    }

    /**
     * Unrejects a previously rejected submission.
     *
     * This function changes the status of a submission to unrejected based on the provided submission ID.
     *
     * @param int $submission_id The ID of the submission to be unrejected.
     * @return void
     */
    public function unreject_submission($submission_id)
    {
        $this->logger->info('Unrejecting submission with ID: ' . $submission_id);
        $updateData = [
            'state' => 'In Progress',
            'is_rejected' => 0,
            'rejected_reason' => null
        ];
        $this->submission_functions->update_submission($submission_id, $updateData);
    }


    /**
     * Deletes a submission based on the provided submission ID and reason.
     *
     * @param int $submission_id The ID of the submission to be deleted.
     * @param string $reason The reason for deleting the submission.
     * @return void
     */
    public function delete_submission($submission_id, $reason)
    {
        $this->logger->info('Deleting submission with ID: ' . $submission_id);
        $updateData = [
            'state' => 'Removed',
            'removed_reason' => $reason,
            'is_removed' => 1
        ];
        $this->submission_functions->update_submission($submission_id, $updateData);
    }

    /**
     * Undeletes a previously deleted submission.
     *
     * This function changes the status of a submission to undeleted based on the provided submission ID.
     *
     * @param int $submission_id The ID of the submission to be undeleted.
     * @return void
     */
    public function undelete_submission($submission_id)
    {
        $this->logger->info('Undeleting submission with ID: ' . $submission_id);
        $updateData = [
            'state' => 'In Progress',
            'is_removed' => 0,
            'removed_reason' => null
        ];
        $this->submission_functions->update_submission($submission_id, $updateData);
    }
}