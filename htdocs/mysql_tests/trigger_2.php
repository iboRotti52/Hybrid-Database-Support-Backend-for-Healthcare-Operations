<?php
require_once "db.php";

$msg = "";
$error = "";

// form defaults
$encounter_id = intval($_POST["encounter_id"] ?? 200000001);
$new_gender = trim($_POST["gender"] ?? "");

// helper: encounter_id -> patient_nbr
function get_patient_by_encounter($conn, $encounter_id) {
  $stmt = $conn->prepare("SELECT patient_nbr FROM Encounter WHERE encounter_id = ?");
  $stmt->bind_param("i", $encounter_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  return $row ? intval($row["patient_nbr"]) : null;
}

// helper: patient_nbr -> gender
function get_gender($conn, $patient_nbr) {
  $stmt = $conn->prepare("SELECT gender FROM Patient WHERE patient_nbr = ?");
  $stmt->bind_param("i", $patient_nbr);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  return $row ? $row["gender"] : null;
}

$patient_nbr = get_patient_by_encounter($conn, $encounter_id);
$current_gender = $patient_nbr ? get_gender($conn, $patient_nbr) : null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  if ($encounter_id <= 0) {
    $error = "❌ Encounter ID must be a positive integer.";
  } elseif ($patient_nbr === null) {
    $error = "❌ Encounter ID not found. (No such encounter in Encounter table.)";
  } elseif ($new_gender === "") {
    $error = "❌ New gender cannot be empty.";
  } else {

    $stmt = $conn->prepare("UPDATE Patient SET gender = ? WHERE patient_nbr = ?");
    $stmt->bind_param("si", $new_gender, $patient_nbr);

    try {
      $stmt->execute();
      $msg = "✅ Update OK. If gender was invalid, Trigger 2 should have blocked it.";
    } catch (Throwable $e) {
      $error = "❌ Trigger blocked update: " . $e->getMessage();
    }

    // refresh
    $current_gender = get_gender($conn, $patient_nbr);
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Trigger 2</title>
</head>
<body>

<h1>Trigger 2 - Validate Gender (via Encounter ID)</h1>

<p>
This page takes an <b>encounter_id</b>, finds its related <b>patient_nbr</b>,
and then updates <b>Patient.gender</b>. Trigger 2 validates the new gender value.
</p>

<form method="post">
  <p>
    <label>Encounter ID:</label>
    <input name="encounter_id" value="<?php echo htmlspecialchars((string)$encounter_id); ?>" />
  </p>

  <p>
    <label>New gender:</label>
    <input name="gender" value="<?php echo htmlspecialchars($new_gender); ?>" placeholder="e.g. Male or ABC" />
  </p>

  <button type="submit">Update Gender (fires trigger)</button>
</form>

<hr>

<?php if ($patient_nbr !== null): ?>
  <p><b>Patient (from encounter):</b> <?php echo htmlspecialchars((string)$patient_nbr); ?></p>
  <p><b>Current gender:</b> <?php echo htmlspecialchars($current_gender ?? "N/A"); ?></p>
<?php else: ?>
  <p><b>Patient:</b> N/A (encounter not found)</p>
<?php endif; ?>

<p><b>Allowed values:</b> Male, Female, Unknown/Invalid</p>

<?php if ($msg): ?>
  <p style="color: green;"><?php echo htmlspecialchars($msg); ?></p>
<?php endif; ?>

<?php if ($error): ?>
  <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<p><a href="index.php">← Back</a></p>

</body>
</html>