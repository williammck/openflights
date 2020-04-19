<?php
include 'locale.php';
include 'db_pdo.php';
include 'helper.php';

$type = $_POST["type"];
$name = $_POST["name"];
$pw = $_POST["pw"];
$oldpw = $_POST["oldpw"];
$email = $_POST["email"];
$privacy = $_POST["privacy"];
$editor = $_POST["editor"];
$units = $_POST["units"];
$guestpw = $_POST["guestpw"];
$startpane = $_POST["startpane"];
$locale = $_POST["locale"]; // override any value in URL/session

// 0 error
// 1 new
// 2 edited
// 10 reset

// Create new user
switch($type) {
 case "NEW":
   $sth = $dbh->prepare("SELECT * FROM users WHERE name = ?");
   $sth->execute([$name]);
   if ($sth->fetch()) {
     die("0;" . _("Sorry, that name is already taken, please try another."));
   }
   break;
   
 case "EDIT":
 case "RESET":
  $uid = $_SESSION["uid"];
  $name = $_SESSION["name"];
  if(!$uid or empty($uid)) {
    die("0;" . _("Your session has timed out, please log in again."));
  }

  if($type == "RESET") {
    $sth = $dbh->prepare("DELETE FROM flights WHERE uid = ?");
    $sth->execute([$uid]);
    printf("10;" . _("Account reset, %s flights deleted."), $sth->rowCount());
    exit;
  }

  // EDIT
  if($oldpw && $oldpw != "") {
    $sth = $dbh->prepare("SELECT password FROM users WHERE name = ?");
    $sth->execute([$name]);
    $passwordHash = $sth->fetchColumn();
    if(!isPasswordCorrect($name, $oldpw, $passwordHash, false)) {
      die("0;" . _("Sorry, current password is not correct."));
    }
  }
  break;

 default:
   die("0;Unknown action $type");
}

$newPasswordHash = password_hash($pw, PASSWORD_BCRYPT);
if($type == "NEW") {
  $sth = $dbh->prepare("INSERT INTO users (name, password, email, public, editor, locale, units) VALUES (?, ?, ?, ?, ?, ?, ?)");
  $success = $sth->execute([$name, $newPasswordHash, $email, $privacy, $editor, $locale, $units]);
} else {
  if(! $guestpw) $guestpw = null;
  $params = compact('email', 'privacy', 'editor', 'guestpw', 'startpane', 'locale', 'units', 'uid');

  // If the old password was given...
  if($oldpw && $oldpw != "") {
    $pwsql = "password = :password, ";
    if ($pw && $pw != "") {
      // Change if we got a new one
      $params['password'] = $newPasswordHash;
    } else if (isLegacyHash($name, $oldpw, $passwordHash)) {
      // Otherwise, while we're at it, let's normalize the password if it's a legacy format
      $params['password'] = password_hash($oldpw, PASSWORD_BCRYPT);
    }
  } else {
    $pwsql = "";
  }
  $sth = $dbh->prepare("
    UPDATE users
    SET $pwsql
        email = :email, public = :privacy, editor = :editor, guestpw = :guestpw,
        startpane = :startpane, locale = :locale, units = :units
    WHERE uid = :uid
  ");
  $success = $sth->execute($params);
}
if (!$success) {
    die("0;Operation on user $name failed.");
}

// In all cases change locale and units to user selection
$_SESSION['locale'] = $locale;
$_SESSION['units'] = $units;

if($type == "NEW") {
  printf("1;" . _("Successfully signed up, now logging in..."));

  // Log in the user
  $uid = $dbh->lastInsertId();
  $_SESSION['uid'] = $uid;
  $_SESSION['name'] = $name;
  $_SESSION['editor'] = $editor;
  $_SESSION['elite'] = $elite;
  $_SESSION['units'] = $units;
} else {
  printf("2;" . _("Settings changed successfully, returning..."));
}
?>
