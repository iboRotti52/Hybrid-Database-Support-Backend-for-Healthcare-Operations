<?php
require_once "db.php";

$msg = "";
$error = "";

// defaults
$encounter_id = 3001;
$patient_nbr  = 1001;
$admission_type = 1;
$discharge_id = 1;
$source_id = 1;
$payer_code = "MC";

// ✅ NEW: readmitted default
$readmitted = "NO"; // allowed: '<30', '>30', 'NO'

$d1 = "250.00";
$d2 = "401.9";
$d3 = "414.01";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $encounter_id = intval($_POST["encounter_id"] ?? 3001);
  $patient_nbr  = intval($_POST["patient_nbr"] ?? 1001);
  $admission_type = intval($_POST["admission_type"] ?? 1);
  $discharge_id = intval($_POST["discharge_id"] ?? 1);
  $source_id = intval($_POST["source_id"] ?? 1);
  $payer_code = trim($_POST["payer_code"] ?? "MC");

  // ✅ NEW: readmitted from UI, normalize
  $readmitted = strtoupper(trim($_POST["readmitted"] ?? "NO"));
  if ($readmitted !== "NO" && $readmitted !== "<30" && $readmitted !== ">30") {
    $readmitted = "NO";
  }

  $d1 = trim($_POST["d1"] ?? "250.00");
  $d2 = trim($_POST["d2"] ?? "401.9");
  $d3 = trim($_POST["d3"] ?? "414.01");

  try {
    // ✅ NEW: 10 parameters now (added readmitted)
    $stmt = $conn->prepare("CALL add_full_encounter(?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param(
      "iiiiisssss",
      $encounter_id,
      $patient_nbr,
      $admission_type,
      $discharge_id,
      $source_id,
      $payer_code,
      $readmitted,
      $d1, $d2, $d3
    );

    if ($stmt->execute()) {
      $msg = "✅ add_full_encounter executed. New encounter + 3 diagnoses inserted (readmitted=$readmitted).";
    } else {
      $error = "❌ DB error: " . $conn->error;
    }
    while ($conn->more_results() && $conn->next_result()) { /* flush */ }
  } catch (Throwable $e) {
    $error = "❌ Error: " . $e->getMessage();
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Procedure 1</title></head>
<body>
  <h1>Procedure 1 - Add Full Encounter</h1>

  <form method="post">
    <p><label>encounter_id:</label> <input name="encounter_id" value="<?php echo htmlspecialchars((string)$encounter_id); ?>"></p>
    <p><label>patient_nbr:</label> <input name="patient_nbr" value="<?php echo htmlspecialchars((string)$patient_nbr); ?>"></p>
    <p><label>admission_type_id:</label> <input name="admission_type" value="<?php echo htmlspecialchars((string)$admission_type); ?>"></p>
    <p><label>discharge_disposition_id:</label> <input name="discharge_id" value="<?php echo htmlspecialchars((string)$discharge_id); ?>"></p>
    <p><label>admission_source_id:</label> <input name="source_id" value="<?php echo htmlspecialchars((string)$source_id); ?>"></p>
    <p><label>payer_code:</label> <input name="payer_code" value="<?php echo htmlspecialchars($payer_code); ?>"></p>

    <!-- ✅ NEW: readmitted dropdown -->
    <p>
      <label>readmitted:</label>
      <select name="readmitted">
        <option value="NO"  <?php echo ($readmitted === "NO"  ? "selected" : ""); ?>>NO</option>
        <option value="<30" <?php echo ($readmitted === "<30" ? "selected" : ""); ?>>&lt;30</option>
        <option value=">30" <?php echo ($readmitted === ">30" ? "selected" : ""); ?>>&gt;30</option>
      </select>
    </p>

    <p><label>diagnosis1:</label> <input name="d1" value="<?php echo htmlspecialchars($d1); ?>"></p>
    <p><label>diagnosis2:</label> <input name="d2" value="<?php echo htmlspecialchars($d2); ?>"></p>
    <p><label>diagnosis3:</label> <input name="d3" value="<?php echo htmlspecialchars($d3); ?>"></p>

    <button type="submit">Run Procedure</button>
  </form>

  <?php if ($msg): ?><p style="color:green;"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
  <?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

  <p><b>Verify:</b> after running, check Encounter and Encounter_Diagnosis tables for encounter_id above.</p>

  <p><a href="index.php">← Back</a></p>
</body>
</html>