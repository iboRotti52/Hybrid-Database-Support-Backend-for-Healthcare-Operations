<?php
require_once __DIR__ . '/../mongo.php';
use MongoDB\BSON\ObjectId;

$id = $_GET['id'] ?? '';
$username = trim($_GET['username'] ?? '');

if ($id === '') die('Missing id');

$ticket = $collection->findOne(['_id' => new ObjectId($id)]);
if (!$ticket) die('Ticket not found');

if ($username !== '' && isset($ticket['username']) && $ticket['username'] !== $username) {
  http_response_code(403);
  die('Forbidden: username mismatch.');
}

$comments = $ticket['comments'] ?? [];
if (!is_array($comments)) $comments = [];
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Ticket Detail</title></head>
<body>
<h1>Ticket Detail</h1>
<p><a href="support_index.php?username=<?php echo urlencode($ticket['username'] ?? $username); ?>">← Back to list</a></p>

<p><b>Username:</b> <?php echo htmlspecialchars($ticket['username'] ?? ''); ?></p>
<p><b>Created at:</b> <?php echo htmlspecialchars($ticket['created_at'] ?? ''); ?></p>
<p><b>Status:</b> <?php echo !empty($ticket['status']) ? 'active' : 'resolved'; ?></p>

<h3>Message</h3>
<p><?php echo nl2br(htmlspecialchars($ticket['message'] ?? '')); ?></p>

<h3>Comments</h3>
<?php if (count($comments) === 0): ?>
  <p>No comments.</p>
<?php else: ?>
  <ul>
    <?php foreach ($comments as $c): ?>
      <li><?php echo htmlspecialchars((string)$c); ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

</body>
</html>
