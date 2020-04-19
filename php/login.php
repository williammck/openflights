<?php
include 'locale.php';
include 'db_pdo.php';

$name = $_POST["name"];
// pw is hashed from lowercased username, legacypw is not
$pw = $_POST["pw"];
$legacypw = $_POST["lpw"];

// Log in user
if($name) {
  $sth = $dbh->prepare("
    SELECT uid, name, email, editor, elite, units, locale
    FROM users
    WHERE
      name = :name
      AND (:pw = password OR :legacypw = password)
  ");
  $sth->execute(compact('name', 'pw', 'legacypw'));
  if ($myrow = $sth->fetch()) {
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
  print json_encode($myrow);
}
?>


