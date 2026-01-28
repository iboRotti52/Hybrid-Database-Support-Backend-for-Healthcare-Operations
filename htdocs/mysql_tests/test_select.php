<?php
require_once "db.php";

$res = $conn->query("SELECT admission_type_id, description FROM Admission_Type ORDER BY admission_type_id LIMIT 10");
if(!$res) die("Query error: " . $conn->error);

echo "<h2>Admission Types</h2><ul>";
while($row = $res->fetch_assoc()){
  echo "<li>{$row['admission_type_id']} - {$row['description']}</li>";
}
echo "</ul>";