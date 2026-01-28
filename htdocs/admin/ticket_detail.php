<?php
require_once __DIR__ . "/../mongo.php";
use MongoDB\BSON\ObjectId;

$id = $_GET["id"] ?? "";
if ($id === "") die("Missing id");

$ticket = $collection->findOne(["_id" => new ObjectId($id)]);
if (!$ticket) die("Ticket not found");

$msg=""; $err="";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (isset($_POST["resolve"])) {
    $collection->updateOne(
      ["_id" => new ObjectId($id)],
      ['$set' => ["status" => false]]
    );
    $msg = "Ticket resolved ✅";
  } else {
    $comment = trim($_POST["comment"] ?? "");
    if ($comment === "") $err = "Comment cannot be empty.";
    else {
      $collection->updateOne(
        ["_id" => new ObjectId($id)],
        ['$push' => ["comments" => ("admin: " . $comment)]]
      );
      $msg = "Admin comment added ✅";
    }
  }
  $ticket = $collection->findOne(["_id" => new ObjectId($id)]);
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Admin Ticket Detail</title></head>
<body>
<h1>Admin Ticket Detail</h1>
<p><a href="index.php">← Back</a></p>

<p><b>Username:</b> <?php echo htmlspecialchars($ticket["username"]); ?></p>
<p><b>Created at:</b> <?php echo htmlspecialchars($ticket["created_at"]); ?></p>
<p><b>Status:</b> <?php echo $ticket["status"] ? "active" : "resolved"; ?></p>

<h3>Message</h3>
<p><?php echo nl2br(htmlspecialchars($ticket["message"])); ?></p>

<h3>Comments</h3>
<?php if (count($ticket["comments"]) === 0): ?>
  <p>No comments.</p>
<?php else: ?>
  <ul>
    <?php foreach ($ticket["comments"] as $c): ?>
      <li><?php echo htmlspecialchars($c); ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php if ($msg): ?><p style="color:green;"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
<?php if ($err): ?><p style="color:red;"><?php echo htmlspecialchars($err); ?></p><?php endif; ?>

<?php if ($ticket["status"]): ?>
  <form method="post">
    <p>
      <label>Admin comment:</label><br>
      <input name="comment" size="60" />
      <button type="submit">Add Comment</button>
    </p>
    <button type="submit" name="resolve" value="1">Mark Resolved</button>
  </form>
<?php else: ?>
  <p>This ticket is resolved.</p>
<?php endif; ?>
</body></html>