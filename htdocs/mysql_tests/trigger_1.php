<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "db.php";

$msg = "";
$error = "";

// defaults (form boş açıldığında)
$encounter_id = intval($_POST["encounter_id"] ?? 2001);
$medication_name_raw = $_POST["medication_name"] ?? "";
$value = $_POST["value"] ?? "Up";  // 'No','Steady','Up','Down'

// helper
function get_num_meds($conn, $encounter_id) {
  $stmt = $conn->prepare("SELECT num_medications FROM Encounter WHERE encounter_id = ?");
  $stmt->bind_param("i", $encounter_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  return $row ? intval($row["num_medications"]) : null;
}

$before = null;
$after  = null;

// Eğer POST gelmediyse sadece default encounter'ın değerini gösterelim
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  $before = get_num_meds($conn, $encounter_id);
  $after = $before;
} else {

  // 🔹 normalize
  $medication_name = strtolower(trim($medication_name_raw));
  $value = trim($value);

  // basic validation
  if ($encounter_id <= 0) {
    $error = "❌ Encounter ID must be a positive integer.";
  } elseif ($medication_name === "") {
    $error = "❌ Medication name cannot be empty.";
  } elseif (!in_array($value, ["No","Steady","Up","Down"], true)) {
    $error = "❌ Invalid value. Must be No / Steady / Up / Down.";
  } else {

    // encounter exists?
    $before = get_num_meds($conn, $encounter_id);
    if ($before === null) {
      $error = "❌ Encounter ID $encounter_id not found in Encounter table.";
    } else {

      // Medication exists? (case-insensitive)
      $chk = $conn->prepare("
        SELECT medication_name
        FROM Medication
        WHERE LOWER(medication_name) = ?
        LIMIT 1
      ");
      $chk->bind_param("s", $medication_name);
      $chk->execute();
      $res = $chk->get_result();
      $row = $res->fetch_assoc();

      if (!$row) {
        $error = "❌ Medication '" . htmlspecialchars($medication_name) . "' does not exist in Medication table.";
      } else {

        // DB'de gerçek ismi neyse onu kullanalım (case korunsun)
        $medication_db_name = $row["medication_name"];

        try {
          // INSERT → Trigger 1 should fire
          $stmt = $conn->prepare("
            INSERT INTO Encounter_Medication (encounter_id, medication_name, value)
            VALUES (?, ?, ?)
          ");
          $stmt->bind_param("iss", $encounter_id, $medication_db_name, $value);
          $stmt->execute();

          // after
          $after = get_num_meds($conn, $encounter_id);

          $msg = "✅ Inserted medication '" . htmlspecialchars($medication_db_name) . "' (value=$value). Trigger 1 executed.";

        } catch (mysqli_sql_exception $e) {
          // Duplicate PK: (encounter_id, medication_name)
          if ((int)$e->getCode() === 1062) {
            $after = get_num_meds($conn, $encounter_id);
            $error = "⚠️ Duplicate: This encounter already has medication '" . htmlspecialchars($medication_db_name) . "'. INSERT rejected, trigger did not run.";
          } else {
            $after = get_num_meds($conn, $encounter_id);
            $error = "❌ DB error ({$e->getCode()}): {$e->getMessage()}";
          }
        }
      }
    }
  }
}

// Eğer after hala null kaldıysa (validation fail vs) before'ı yansıt
if ($before !== null && $after === null) $after = $before;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Trigger 1</title>
</head>
<body>

<h1>Trigger 1 – Increment Medication Count</h1>

<p>
When a new record is inserted into <b>Encounter_Medication</b>, this trigger increments
<b>Encounter.num_medications</b> by 1 for that encounter.
</p>

<form method="post">
  <p>
    <label>Encounter ID:</label>
    <input name="encounter_id" value="<?php echo htmlspecialchars((string)$encounter_id); ?>" />
  </p>

  <p>
    <label>Medication name:</label>
    <input name="medication_name" value="<?php echo htmlspecialchars($medication_name_raw); ?>" placeholder="e.g. metformin" />
  </p>

  <p>
    <label>Value:</label>
    <select name="value">
      <option value="No"     <?php if ($value==="No") echo "selected"; ?>>No</option>
      <option value="Steady" <?php if ($value==="Steady") echo "selected"; ?>>Steady</option>
      <option value="Up"     <?php if ($value==="Up") echo "selected"; ?>>Up</option>
      <option value="Down"   <?php if ($value==="Down") echo "selected"; ?>>Down</option>
    </select>
  </p>

  <button type="submit">Run Trigger 1</button>
</form>

<?php if ($msg): ?>
  <p style="color:green;"><?php echo $msg; ?></p>
<?php endif; ?>

<?php if ($error): ?>
  <p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($before !== null): ?>
  <h3>Before / After</h3>
  <ul>
    <li><b>Before num_medications:</b> <?php echo htmlspecialchars((string)$before); ?></li>
    <li><b>After num_medications:</b> <?php echo htmlspecialchars((string)$after); ?></li>
  </ul>
<?php endif; ?>

<p><a href="index.php">← Back</a></p>

</body>
</html>