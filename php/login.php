<?php
include 'locale.php';
include 'db_pdo.php';
include 'helper.php';

$name = $_POST["name"];
$pw = $_POST["pw"];

// Log in user
if($name) {
  $sth = $dbh->prepare("
    SELECT uid, name, password, email, editor, elite, units, locale
    FROM users
    WHERE name = ?
  ");
  $sth->execute([$name]);
  $myrow = $sth->fetch(PDO::FETCH_ASSOC);
  if ($myrow && isPasswordCorrect($name, $pw, $myrow['password'])) {
    $uid = $myrow["uid"];
    $_SESSION['uid'] = $uid;
    $_SESSION['name'] = $myrow["name"];
    $_SESSION['email'] = $myrow["email"];
    $_SESSION['editor'] = $myrow["editor"];
    $_SESSION['elite'] = $myrow["elite"];
    $_SESSION['units'] = $myrow["units"];
    if($myrow["locale"] != "en_US" && $_SESSION['locale'] != $myrow["locale"]) {
      $myrow['status'] = 2; // force reload, so UI is changed into user's language
    } else {
      $myrow['status'] = 1;
    }
    $_SESSION['locale'] = $myrow["locale"];
  } else {
    $message = sprintf(_("Login failed. <%s>Create account</a> or <%s>reset password</a>?"), "a href='/html/settings?new=yes'", "a href='#' onclick='JavaScript:help(\"resetpw\")'");
    $myrow = array("status" => 0, "message" => $message);
  }
  unset($myrow['password']);
  print json_encode($myrow);
}
?>


