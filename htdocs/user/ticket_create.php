<?php
require_once __DIR__ . "/../mongo.php";

$msg = "";
$err = "";

// Eğer support_index'ten username ile gelmek istersen (opsiyonel)
$username = trim($_GET["username"] ?? ($_POST["username"] ?? ""));
$message  = trim($_POST["message"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if ($username === "" || $message === "") {
    $err = "Username and message required.";
  } else {
    $collection->insertOne([
      "username"   => $username,
      "message"    => $message,
      "created_at" => date("Y-m-d H:i:s"),
      "status"     => true,      // true=active, false=resolved
      "comments"   => []
    ]);
    $msg = "Ticket created ✅";

    // İstersen formu temizle
    $message = "";
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Create Ticket</title>
</head>
<body>

<h1>Create Ticket</h1>

<p>
  <a href="support_index.php">← Ticket List</a>
  | <a href="index.php">← Home</a>
</p>

<?php if ($msg): ?><p style="color:green;"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
<?php if ($err): ?><p style="color:red;"><?php echo htmlspecialchars($err); ?></p><?php endif; ?>

<form method="post">
  <p>
    <label>Username:</label>
    <input name="username" value="<?php echo htmlspecialchars($username); ?>" />
  </p>

  <p>
    <label>Message:</label><br>
    <textarea name="message" rows="5" cols="60"><?php echo htmlspecialchars($message); ?></textarea>
  </p>

  <button type="submit">Create</button>
</form>

</body>
</html>