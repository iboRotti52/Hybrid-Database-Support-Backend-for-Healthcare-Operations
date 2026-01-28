<?php
require_once "db.php";

$patient_nbr = 1001;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $patient_nbr = intval($_POST["patient_nbr"] ?? 1001);
}

$rows = [];
$error = "";

$stmt = $conn->prepare("CALL get_patient_summary(?)");
$stmt->bind_param("i", $patient_nbr);

try {
  if ($stmt->execute()) {
    $res = $stmt->get_result();
    if ($res) {
      while ($r = $res->fetch_assoc()) $rows[] = $r;
      $res->free();
    }
  } else {
    $error = $conn->error;
  }
  while ($conn->more_results() && $conn->next_result()) { /* flush */ }
} catch (Throwable $e) {
  $error = $e->getMessage();
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Procedure 4</title></head>
<body>
  <h1>Procedure 4 - Get Patient Summary</h1>

  <form method="post">
    <label>patient_nbr:</label>
    <input name="patient_nbr" value="<?php echo htmlspecialchars((string)$patient_nbr); ?>" />
    <button type="submit">Run</button>
  </form>

  <?php if ($error): ?>
    <p style="color:red;">❌ <?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <?php if (count($rows) === 0): ?>
    <p>No encounters for this patient.</p>
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