<?php
namespace PTA\client;

/* Prevent direct access */
if (!defined('ABSPATH')) {
    exit;
}

/* Requires */
use PTA\logger\Log;
use Monolog\Logger;
use PTA\DB\functions\submission\submission_functions;
use PTA\DB\functions\image\image_functions;
use PTA\DB\functions\user\user_functions;
use PTA\DB\db_handler;
use PTA\DB\functions\db_functions;
use PTA\admin\admin_functions;

class Client
{
    public Logger $logger;
    private $pre_log;
    private $logname;
    protected static $constructed = [];
    protected static $initialized = [];
    protected submission_functions $submission_functions;
    protected image_functions $image_functions;
    protected user_functions $user_functions;
    protected db_handler $db_handler_instance;
    protected db_functions $db_functions;
    protected admin_functions $admin_functions;
    private $callback;

    /**
     * Client constructor.
     *
     * @param string $LogName The name of the log.
     * @param callable|null $callback_function Optional. A callback function to be executed after initialization.
     */
    public function __construct($LogName, $callback_after_init = null)
    {

        $this->logname = $LogName;

        $this->pre_log = new Log(name: $LogName);
        $this->callback = $callback_after_init;
    }

    public function init(
        submission_functions $sub_functions = null,
        image_functions $img_functions = null,
        user_functions $user_functions = null,
        db_handler $handler_instance = null,
        db_functions $db_functions = null,
        admin_functions $admin_functions = null
    ) {

        $classname = static::class . $this->logname;
        if(isset(self::$initialized[$classname]) && self::$initialized[$classname]) {
            return;
        }

        $this->logger = $this->pre_log->getLogger();

        // Get the handler instance and db functions instance
        $this->db_handler_instance = ($handler_instance instanceof db_handler) ? $handler_instance : new db_handler();
        $this->db_functions = ($db_functions instanceof db_functions) ? $db_functions : new db_functions();

        // if handler_instance and db_functions are both not instances of their respective classes
        if (!($handler_instance instanceof db_handler) || !($db_functions instanceof db_functions)) {

            // Set the functions instance in the handler, and initialize the functions
            $this->db_handler_instance->set_functions(name: 'functions', function_instance: $this->db_functions);
            $this->db_functions->init(handler_instance: $this->db_handler_instance);
        }

        // Set the functions instances for the submission, image, and user functions
        $this->submission_functions = ($sub_functions instanceof submission_functions) ? $sub_functions : new submission_functions(handler_instance: $this->db_handler_instance, db_functions: $this->db_functions);
        $this->image_functions = ($img_functions instanceof image_functions) ? $img_functions : new image_functions(handler_instance: $this->db_handler_instance, db_functions: $this->db_functions);
        $this->user_functions = ($user_functions instanceof user_functions) ? $user_functions : new user_functions(handler_instance: $this->db_handler_instance, db_functions: $this->db_functions);

        $this->admin_functions = ($admin_functions instanceof admin_functions) ? $admin_functions : new admin_functions(submission_functions: $this->submission_functions);

        if ($this->callback != null && is_callable($this->callback)) {
            call_user_func($this->callback);
        }

        self::$initialized[$classname] = true;
    }

}