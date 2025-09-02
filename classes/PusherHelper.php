<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Pusher\Pusher;

class PusherHelper {
    private $pusher;
    
    public function __construct() {
        $config = include __DIR__ . '/../config/pusher.php';
        
        $this->pusher = new Pusher(
            $config['key'],
            $config['secret'],
            $config['app_id'],
            [
                'cluster' => $config['cluster'],
                'useTLS' => $config['useTLS']
            ]
        );
    }
    
    public function triggerVideoProcessed($videoData) {
        try {
            $this->pusher->trigger('video-processing', 'video-processed', $videoData);
            error_log('Pusher event triggered: video-processed');
            return true;
        } catch (Exception $e) {
            error_log('Pusher error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function triggerVideoUploaded($videoData) {
        try {
            $this->pusher->trigger('video-processing', 'video-uploaded', $videoData);
            error_log('Pusher event triggered: video-uploaded');
            return true;
        } catch (Exception $e) {
            error_log('Pusher error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
