<?php
namespace PTA\API;

/* Prevent direct access */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SSE
 *
 * This class is the Server-Sent Events API for the Portals to Adventure plugin.
 *
 * @package PortalsToAdventure
 */
class SSE {
    private static $instance = null;

    public function __construct()
    {
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
        try {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); 

            // Send initial connection event
            echo "event: connection\n";
            echo "data: " . json_encode(['status' => 'connected']) . "\n\n";
            ob_end_flush();
            flush();

            while (true) {
                if (connection_aborted()) break;
        
                $data = [
                    'timestamp' => time(),
                    'type' => 'heartbeat'
                ];
        
                echo "data: " . json_encode($data) . "\n\n";
                flush();
                sleep(1);
            }
            exit();
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