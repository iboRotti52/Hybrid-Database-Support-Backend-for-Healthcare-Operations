<?php
/**
 * MongoDB connection helper for the support ticket system.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$mongoUri = getenv('MONGO_URI') ?: 'mongodb://mongo:27017';
$mongoDbName = getenv('MONGO_DB') ?: 'cs306_support';
$mongoCollection = getenv('MONGO_COLLECTION') ?: 'tickets';

try {
    $client = new MongoDB\Client($mongoUri);
    $db = $client->selectDatabase($mongoDbName);
    $collection = $db->selectCollection($mongoCollection);
} catch (Throwable $e) {
    http_response_code(500);
    die('MongoDB connection failed: ' . htmlspecialchars($e->getMessage()));
}
