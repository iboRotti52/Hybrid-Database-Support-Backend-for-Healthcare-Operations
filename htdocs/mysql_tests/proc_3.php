<?php
require_once "db.php";

$med = "insulin";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $med = trim($_POST["med"] ?? "insulin");
}

$result = null;
$error = "";

try {
  $stmt = $conn->prepare("CALL count_med_usage(?, @out_res)");
  $stmt->bind_param("s", $med);

  if ($stmt->execute()) {
    while ($conn->more_results() && $conn->next_result()) { /* flush */ }

    $res = $conn->query("SELECT @out_res AS usage_count");
    $row = $res->fetch_assoc();
    $result = $row ? intval($row["usage_count"]) : null;
  } else {
    $error = $conn->error;
  }
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Procedure 3</title></head>
<body>
  <h1>Procedure 3 - Count Medication Usage</h1>

  <form method="post">
    <label>medication_name:</label>
    <input name="med" value="<?php echo htmlspecialchars($med); ?>" />
    <button type="submit">Run</button>
  </form>

  <?php if ($error): ?>
    <p style="color:red;">❌ <?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <?php if ($result !== null): ?>
    <p><b>Usage count:</b> <?php echo htmlspecialchars((string)$result); ?></p>
  <?php endif; ?>

  <p><a href="index.php">← Back</a></p>
</body>
</html>