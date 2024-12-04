<?php
namespace PTA\client;

/* Prevent direct access */
if (!defined('ABSPATH')) {
    exit;
}

/* Requires */
use PTA\logger\Log;
use PTA\DB\functions\submission\submission_functions;
use PTA\DB\functions\image\image_functions;
use PTA\DB\functions\user\user_functions;
use PTA\DB\db_handler;
use PTA\DB\functions\db_functions;
use PTA\admin\admin_functions;

class Client
{
    public $logger;
    public submission_functions $submission_functions;
    public image_functions $image_functions;
    public user_functions $user_functions;
    public db_handler $db_handler_instance;
    public db_functions $db_functions;
    public admin_functions $admin_functions;

    public function __construct($LogName)
    {
        $this->logger = new Log(name: $LogName);
    }

    public function init(
        submission_functions $sub_functions = null,
        image_functions $img_functions = null,
        user_functions $user_functions = null,
        db_handler $handler_instance = null,
        db_functions $db_functions = null,
        admin_functions $admin_functions = null
    ) {
        $this->logger = $this->logger->getLogger();

        // Get the handler instance and db functions instance
        $this->db_handler_instance = $handler_instance ?? new db_handler();
        $this->db_functions = $db_functions ?? new db_functions();

        // if handler_instance is null or db_functions is null, set them
        if ($handler_instance == null || $db_functions == null) {

            // Set the functions instance in the handler, and initialize the functions
            $this->db_handler_instance->set_functions(name: 'functions', function_instance: $this->db_functions);
            $this->db_functions->init(handler_instance: $this->db_handler_instance);

        }

        // Set the functions instances for the submission, image, and user functions
        $this->submission_functions = $sub_functions ?? new submission_functions(handler_instance: $this->db_handler_instance, db_functions: $this->db_functions);
        $this->image_functions = $img_functions ?? new image_functions(handler_instance: $this->db_handler_instance, db_functions: $this->db_functions);
        $this->user_functions = $user_functions ?? new user_functions(handler_instance: $this->db_handler_instance, db_functions: $this->db_functions);

        $this->admin_functions = $admin_functions ?? new admin_functions();

        if ($admin_functions == null) {

            $this->admin_functions->init(
                sub_functions: $this->submission_functions,
                img_functions: $this->image_functions,
                user_functions: $this->user_functions,
                handler_instance: $this->db_handler_instance,
                db_functions: $this->db_functions
            );

        }
    }

}