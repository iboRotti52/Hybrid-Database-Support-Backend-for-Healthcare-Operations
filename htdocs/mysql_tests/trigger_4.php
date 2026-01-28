<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "db.php";

$msg = "";
$error = "";

// default encounter id (kullanıcı boş bırakırsa)
$default_encounter_id = 2001;

// formdan encounter al (GET/POST fark etmesin diye)
$encounter_id_raw = $_POST["encounter_id"] ?? $_GET["encounter_id"] ?? "";
$encounter_id = ($encounter_id_raw === "") ? $default_encounter_id : (int)$encounter_id_raw;

function get_diag_count($conn, $encounter_id) {
  $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM Encounter_Diagnosis WHERE encounter_id = ?");
  $stmt->bind_param("i", $encounter_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  return $row ? intval($row["c"]) : 0;
}

// ICD default
$default_icd = "250.00";
$icd9_input = $_POST["icd9_code"] ?? $default_icd;
$icd9 = trim($icd9_input);

// before count (sayfa ilk açılış için)
$count_before = get_diag_count($conn, $encounter_id);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  try {
    if ($encounter_id <= 0) {
      throw new Exception("Encounter ID must be a positive number.");
    }
    if ($icd9 === "") {
      throw new Exception("icd9_code cannot be empty.");
    }

    // Encounter var mı? (FK hatasını daha anlaşılır yapmak için)
    $chkE = $conn->prepare("SELECT 1 FROM Encounter WHERE encounter_id = ?");
    $chkE->bind_param("i", $encounter_id);
    $chkE->execute();
    $chkE->store_result();
    if ($chkE->num_rows === 0) {
      throw new Exception("Encounter ID $encounter_id not found in Encounter table.");
    }

    // Diagnosis var mı?
    $chkD = $conn->prepare("SELECT 1 FROM Diagnosis WHERE icd9_code = ?");
    $chkD->bind_param("s", $icd9);
    $chkD->execute();
    $chkD->store_result();
    if ($chkD->num_rows === 0) {
      throw new Exception("ICD-9 code '$icd9' not found in Diagnosis table.");
    }

    // ✅ Count'u POST anında tekrar al (en doğru)
    $current_count = get_diag_count($conn, $encounter_id);
    $pos = $current_count + 1; // next position

    // Try insert -> Trigger 4 should block when >3
    $stmt = $conn->prepare("
      INSERT INTO Encounter_Diagnosis (encounter_id, position, icd9_code)
      VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iis", $encounter_id, $pos, $icd9);
    $stmt->execute();

    $msg = "✅ Diagnosis inserted (pos=$pos, icd9=$icd9). If this was 4th+, trigger should have blocked it.";

  } catch (mysqli_sql_exception $e) {

    // Duplicate PK (same encounter_id + position)
    if ((int)$e->getCode() === 1062) {
      $error = "⚠️ Duplicate: This encounter already has a diagnosis at position $pos.";
    } else {
      // Trigger block / FK fail / other errors
      $error = "❌ DB Error ({$e->getCode()}): {$e->getMessage()}";
    }

  } catch (Exception $e) {
    $error = "❌ " . $e->getMessage();
  }
}

// after count + list
$count_after = get_diag_count($conn, $encounter_id);

// Fetch current diagnoses list
$rows = [];
$stmt = $conn->prepare("
  SELECT position, icd9_code
  FROM Encounter_Diagnosis
  WHERE encounter_id = ?
  ORDER BY position
");
$stmt->bind_param("i", $encounter_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $rows[] = $r;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Trigger 4</title>
</head>
<body>
  <h1>Trigger 4 - Limit Diagnoses to 3</h1>

  <p><b>How to test:</b> Choose an encounter_id, then click insert 3 times (should succeed). 4th time should be blocked by trigger.</p>

  <form method="post">
    <p>
      <label>encounter_id:</label>
      <input name="encounter_id" value="<?php echo htmlspecialchars((string)$encounter_id); ?>" />
    </p>

    <p>
      <label>icd9_code:</label>
      <input name="icd9_code" value="<?php echo htmlspecialchars($icd9_input); ?>" />
    </p>

    <button type="submit">Insert Diagnosis (fires trigger)</button>
  </form>

  <h3>Counts</h3>
  <ul>
    <li><b>Before count:</b> <?php echo htmlspecialchars((string)$count_before); ?></li>
    <li><b>After count:</b> <?php echo htmlspecialchars((string)$count_after); ?></li>
  </ul>

  <?php if ($msg): ?>
    <p style="color: green;"><?php echo htmlspecialchars($msg); ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <h2>Current Encounter_Diagnosis (encounter_id = <?php echo htmlspecialchars((string)$encounter_id); ?>)</h2>
  <?php if (count($rows) === 0): ?>
    <p>No diagnoses yet.</p>
  <?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0">
      <tr><th>position</th><th>icd9_code</th></tr>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r["position"]); ?></td>
          <td><?php echo htmlspecialchars($r["icd9_code"]); ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <p><a href="index.php">← Back</a></p>
</body>
</html>