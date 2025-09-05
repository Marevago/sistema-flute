<?php
// Worker security token; change this to a long random string in production
if (!defined('FLUTE_WORKER_TOKEN')) {
    define('FLUTE_WORKER_TOKEN', 'change-me-worker-token-123');
}
