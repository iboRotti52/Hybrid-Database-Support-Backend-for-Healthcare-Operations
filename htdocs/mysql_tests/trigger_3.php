<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "db.php";

$msg = "";
$error = "";

// default (form ilk açılış)
$encounter_id = intval($_POST["encounter_id"] ?? 2001);
$new_readmitted = trim($_POST["readmitted"] ?? "");

// helper: get current readmitted
function get_current_readmitted($conn, $encounter_id) {
  $stmt = $conn->prepare("SELECT readmitted FROM Encounter WHERE encounter_id = ?");
  $stmt->bind_param("i", $encounter_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  return $row ? $row["readmitted"] : null;
}

$current = get_current_readmitted($conn, $encounter_id);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  if ($encounter_id <= 0) {
    $error = "❌ Encounter ID must be a positive integer.";
  } elseif ($current === null) {
    $error = "❌ Encounter not found (invalid encounter_id).";
  } elseif ($new_readmitted === "") {
    $error = "❌ New readmitted cannot be empty.";
  } else {

    try {
      $stmt = $conn->prepare("UPDATE Encounter SET readmitted = ? WHERE encounter_id = ?");
      $stmt->bind_param("si", $new_readmitted, $encounter_id);
      $stmt->execute();

      $msg = "✅ Encounter.readmitted updated. Trigger 3 should have logged the change.";

    } catch (Throwable $e) {
      $error = "❌ DB/Trigger error: " . $e->getMessage();
    }

    // refresh current
    $current = get_current_readmitted($conn, $encounter_id);
  }
}

// fetch last 10 logs for this encounter (only if encounter exists)
$logs = [];
if ($current !== null) {
  $stmt = $conn->prepare("
    SELECT log_id, encounter_id, old_value, new_value, changed_at
    FROM Readmission_Log
    WHERE encounter_id = ?
    ORDER BY log_id DESC
    LIMIT 10
  ");
  $stmt->bind_param("i", $encounter_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $logs[] = $r;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Trigger 3</title>
</head>
<body>

<h1>Trigger 3 - Log Readmission Change</h1>

<p>
This trigger logs every change to <b>Encounter.readmitted</b> into <b>Readmission_Log</b>.
</p>

<form method="post">
  <p>
    <label>Encounter ID:</label>
    <input name="encounter_id" value="<?php echo htmlspecialchars((string)$encounter_id); ?>" />
  </p>

  <p>
    <label>New readmitted:</label>
    <input name="readmitted" value="<?php echo htmlspecialchars($new_readmitted); ?>" placeholder="NO or <30 or >30" />
  </p>

  <button type="submit">Update Readmitted (fires trigger)</button>
</form>

<hr>

<p><b>Current readmitted:</b> <?php echo htmlspecialchars($current ?? "N/A"); ?></p>
<p><b>Try values:</b> NO, &lt;30, &gt;30</p>

<?php if ($msg): ?>
  <p style="color: green;"><?php echo htmlspecialchars($msg); ?></p>
<?php endif; ?>
<?php if ($error): ?>
  <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<h2>Readmission_Log (last 10 for this encounter)</h2>
<?php if ($current === null): ?>
  <p>Enter a valid encounter_id to view logs.</p>
<?php elseif (count($logs) === 0): ?>
  <p>No logs yet for this encounter.</p>
<?php else: ?>
  <table border="1" cellpadding="6" cellspacing="0">
    <tr>
      <th>log_id</th>
      <th>old_value</th>
      <th>new_value</th>
      <th>changed_at</th>
    </tr>
    <?php foreach ($logs as $l): ?>
      <tr>
        <td><?php echo htmlspecialchars($l["log_id"]); ?></td>
        <td><?php echo htmlspecialchars($l["old_value"]); ?></td>
        <td><?php echo htmlspecialchars($l["new_value"]); ?></td>
        <td><?php echo htmlspecialchars($l["changed_at"]); ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<p><a href="index.php">← Back</a></p>

</body>
</html>