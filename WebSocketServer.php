<?php
require_once 'Frame.php';
require_once 'Event.php';
require_once 'WebSocketManager.php';
require_once 'WebSocket.php';

$wsManager = new WebSocketManager('localhost', 4000);
$wsManager->run();

