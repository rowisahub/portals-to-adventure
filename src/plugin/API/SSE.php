<?php
namespace PTA\API;

/* Prevent direct access */
if (!defined('ABSPATH')) {
    exit;
}

// Requires
use PTA\logger\Log;

/**
 * Class SSE
 *
 * This class is the Server-Sent Events API for the Portals to Adventure plugin.
 *
 * @package PortalsToAdventure
 */
class SSE {
    private static $instance = null;
    private $logger;

    public function __construct()
    {

        $log = new Log(name: "SSE");
        $this->logger = $log->getLogger();

        if (self::$instance == null) {
            self::$instance = $this;
        }
    }

    public function register_hooks()
    {
        add_action('wp_ajax_nopriv_wldpta_sse', array($this, 'wldpta_sse'));
        add_action('wp_ajax_wldpta_sse', array($this, 'wldpta_sse'));
    }

    public function wldpta_sse() {
        $this->logger->debug('SSE request received');

        try {

            // Close session early to prevent blocking
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            // Set execution parameters safely
            ignore_user_abort(true);
            set_time_limit(0);

            // Force PHP to send headers immediately
            // ini_set('implicit_flush', 1);
            // ini_set('output_buffering', 'Off');

            nocache_headers(); // WordPress function for cache control
            header('X-Accel-Buffering: no'); 
            header('Content-Type: text/event-stream');
            //header('Cache-Control: no-cache');
            header('Connection: keep-alive');

            // header('Pragma: no-cache');
            // header('Expires: 0');
            
            // Turn off output buffering completely
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            
            // Send initial connection event
            echo "event: connection\n";
            echo "data: " . json_encode(['status' => 'connected']) . "\n\n";
            ob_flush();
            flush();

            $counter = 0;
            $lastPingTime = time();
            // Keep connection alive
            while (true) {
                if (connection_aborted()) {
                    $this->logger->debug('Connection aborted');
                    
                    break;
                }

                // Send heartbeat every 5 seconds
                if ((time() - $lastPingTime) >= 5) {
                    $this->logger->debug('Sending heartbeat');
                    $lastPingTime = time();

                    echo "event: heartbeat\n";
                    echo "data: " . json_encode(['time' => time()]) . "\n\n";
                    ob_flush();
                    flush();
                    

                    if($counter == 3) {
                        $this->logger->debug('Ending connection');
                        echo "event: close\n";
                        echo "data: " . json_encode(['status' => 'disconnected']) . "\n\n";
                        ob_flush();
                        flush();
                        
                        break;
                    }

                    $counter++;
                }

                // Small sleep to prevent CPU overload
                

                // if($counter == 10) {
                //     $this->logger->debug('Ending connection');
                //     echo "event: close\n";
                //     echo "data: " . json_encode(['status' => 'disconnected']) . "\n\n";
                //     flush();
                // }
            
                //usleep(100000); // 100ms delay
                sleep(1);
            }
        } catch (\Exception $e) {
            error_log('SSE Error: ' . $e->getMessage());
            http_response_code(500);
            echo "data: {\"error\": \"Internal Server Error\"}\n\n";
            ob_flush();
            flush();
        }
    }

    public static function get_instance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function send_message($message, $event = 'message')
    {
        $data = json_encode([
            'message' => $message,
            'timestamp' => date('r')
        ]);
    
        echo "event: " . $event . "\n";
        echo "data: " . $data . "\n\n";
        
        ob_flush();
        flush();


    }
}

// $sse = new SSE();
// $sse->listen();