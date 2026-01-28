<?php
require_once "db.php";

$rows = [];
$error = "";

try {
  $res = $conn->query("CALL get_high_risk_patients()");
  if ($res) {
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $res->free();
  } else {
    $error = $conn->error;
  }
  // flush additional result sets (important after CALL)
  while ($conn->more_results() && $conn->next_result()) { /* flush */ }
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Procedure 2</title></head>
<body>
  <h1>Procedure 2 - Get High Risk Patients</h1>
  <p>Returns patients whose encounter.readmitted = &lt;30.</p>

  <?php if ($error): ?>
    <p style="color:red;">❌ <?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <?php if (count($rows) === 0): ?>
    <p>No high-risk patients found (this can be normal if no readmitted=&lt;30).</p>
  <?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0">
      <tr>
        <?php foreach (array_keys($rows[0]) as $k): ?>
          <th><?php echo htmlspecialchars($k); ?></th>
        <?php endforeach; ?>
      </tr>
      <?php foreach ($rows as $row): ?>
        <tr>
          <?php foreach ($row as $v): ?>
            <td><?php echo htmlspecialchars((string)$v); ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <p><a href="index.php">← Back</a></p>
</body>
</html>