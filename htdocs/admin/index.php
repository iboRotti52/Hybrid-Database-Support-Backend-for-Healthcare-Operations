<?php
require_once __DIR__ . "/../mongo.php";

$tickets = [];
$cursor = $collection->find(["status" => true], ["sort" => ["_id" => -1]]);
foreach ($cursor as $doc) $tickets[] = $doc;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>CS306 Phase 3 - Admin</title>
</head>
<body>

<h1>CS306 Phase 3 - Admin Panel</h1>

<h2>Active Support Tickets</h2>

<?php if (count($tickets) === 0): ?>
  <p>No active tickets.</p>
<?php else: ?>
  <ul>
    <?php foreach ($tickets as $t): ?>
      <li>
        <b><?php echo htmlspecialchars($t["username"]); ?></b> —
        <a href="ticket_detail.php?id=<?php echo htmlspecialchars((string)$t->_id); ?>">
          <?php echo htmlspecialchars(substr($t["message"], 0, 60)); ?>...
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<hr>
<p>
  <a href="../user">← Back to User Panel</a>
</p>

</body>
</html>