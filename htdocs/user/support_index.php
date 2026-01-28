<?php
require_once __DIR__ . "/../mongo.php";

$username = trim($_GET["username"] ?? "");
$filter   = $_GET["filter"] ?? "active"; // active | resolved | all

$tickets = [];
$error = "";

// username girildiyse ticketları çek
if ($username !== "") {
  $query = ["username" => $username];

  // status: true=active, false=resolved
  if ($filter === "active") {
    $query["status"] = true;
  } elseif ($filter === "resolved") {
    $query["status"] = false;
  } elseif ($filter === "all") {
    // ekstra koşul yok
  } else {
    // invalid filter gelirse default active
    $filter = "active";
    $query["status"] = true;
  }

  try {
    $cursor = $collection->find($query, ["sort" => ["_id" => -1]]);
    foreach ($cursor as $doc) $tickets[] = $doc;
  } catch (Throwable $e) {
    $error = "MongoDB error: " . $e->getMessage();
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Support Tickets</title>
</head>
<body>

<h1>Support Tickets (User)</h1>

<!-- Username input + filter -->
<form method="get">
  <p>
    <label>Your username:</label>
    <input name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="e.g. celebrimbor fan" />
  </p>

  <p>
    <label>Show:</label>
    <select name="filter">
      <option value="active"   <?php if ($filter==="active") echo "selected"; ?>>Active</option>
      <option value="resolved" <?php if ($filter==="resolved") echo "selected"; ?>>Resolved</option>
      <option value="all"      <?php if ($filter==="all") echo "selected"; ?>>All</option>
    </select>
  </p>

  <button type="submit">View Tickets</button>
</form>

<p>
  <a href="ticket_create.php<?php echo ($username!=="" ? "?username=" . urlencode($username) : ""); ?>">
    Create new ticket
  </a>
</p>

<?php if ($error): ?>
  <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if ($username === ""): ?>
  <p>Please enter your username to view your tickets.</p>

<?php else: ?>
  <h2><?php echo htmlspecialchars(ucfirst($filter)); ?> tickets for: <?php echo htmlspecialchars($username); ?></h2>

  <?php if (count($tickets) === 0): ?>
    <p>No tickets found.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($tickets as $t): ?>
        <?php
          $id_str = isset($t->_id) ? (string)$t->_id : "";
          $msg = $t["message"] ?? "";
          $created = $t["created_at"] ?? "";
          $isActive = !empty($t["status"]);
        ?>
        <li>
          <a href="ticket_detail.php?id=<?php echo htmlspecialchars($id_str); ?><?php echo "&username=" . urlencode($username); ?>">
            <?php echo htmlspecialchars(mb_strimwidth($msg, 0, 60, "...", "UTF-8")); ?>
          </a>
          (<?php echo htmlspecialchars($created); ?>)
          — <?php echo $isActive ? "Active" : "Resolved"; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
<?php endif; ?>

<p><a href="index.php">← Back to User Home</a></p>

</body>
</html>