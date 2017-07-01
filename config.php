<?php
//================================================================================================
//===        D E L P H I N O      S T A M M T I S C H      C O N F I G U R A T I O N           ===
//================================================================================================

$TODAY = strtotime('today midnight'); //time();
//$TODAY = strtotime("12 Mar 2016");

$MINUTES = 60;
$HOURS = 60 * $MINUTES;

$UTCTIMEDIFF = -1*$HOURS;

$MEETINGSTART = 19 * $HOURS + 30 * $MINUTES; // 19:30h
$MEETINGDURATION = 2 * $HOURS;


//$BASEURL = "http://www.cryptsecure.org/";
$BASEURL = "http://localhost/stammtisch/";
$WEBMASTER = "webaster@localhost";
$DBUSER = "dummy";
$DBPWD = "password";
$DBURL = "localhost";
$DBNAME = "stammtisch";

$DELETESESSION = 48*$HOURS;
$EXPIRECOOKIE = 1*$HOURS;

$CRONPWD = "ABCDE";
// Cronurl: $BASEURL + "cron.php?pwd=" + $CRONPWD
// E.g. http://www.mystammtisch.com/cron.php?pwd=password
// Should be called at least 1x per day

$CONST_EMAILHEADER  = "From: RtSys Stammtisch <noreply@localhost>\n";
$CONST_EMAILHEADER .= "Content-Type: text/plain; charset=us-ascii\n";
$CONST_EMAILHEADER .= "Content-transfer-encoding: quoted-printable";
$CONST_EMAILHEADER .= "MIME-Version: 1.0\n";

$CHOOSEVENUE_DAYSBEFORE = 6;
$SENDINVITATION_DAYSBEFORE = 4; // set -1 to not send invitations
$SENDREMINDER_DAYSBEFORE = 1;   // set -1 to not send reminders

$EMAILSUBJECT_REGISTER = "RtSys Stammtisch Invitation, YOU were selected! :)";
$EMAILSUBJECT_INVITATION = "RtSys Stammtisch at %VENUE!";
$EMAILSUBJECT_REMINDER = "RtSys Stammtisch happens *TOMORROW*!";
$EMAILSUBJECT_CHANGEDVENUE = "Attention! RtSys Stammtisch *changed* venue to %VENUE!";
$EMAILSUBJECT_MESSAGE = "Important message from %SENDER";

//$welcomemessage = "Welcome to Delphino CryptSecure!@@@NEWLINE@@@@@@NEWLINE@@@Your account has just been activated. You can now exchange securely encrypted messages with other people who also value uncompromised privacy.@@@NEWLINE@@@@@@NEWLINE@@@Give your UID \'".$uid."\' to anyone who should be able to add you. Ask friends about their UIDs in order to add them. To add UIDs, go to the main window and select \'Add User\' from the context menu.@@@NEWLINE@@@@@@NEWLINE@@@Find more information here: http://www.cryptsecure.org@@@NEWLINE@@@@@@NEWLINE@@@Tell a good friend about CryptSecure if you like it!";

$ipaddress = $_SERVER['REMOTE_ADDR'];
//printf($ipaddress);

//================================================================================================
//================================================================================================

$ONEDAY = 24 * 60 * 60; // time stamp diff in seconds
$SEVENDAYS = 7 * $ONEDAY; // time stamp diff in seconds

$MEETDAY = 4;  // 4=THURSDAY (0=Sunday, 6=Saturday)
$MEETWEEK = 2; // Second Thursday of a Month

// You can fully program your own isMeetingDay function here
// or use the above constants to modify your monthly meeting
function isMeetingDay($timestamp) {
   global $MEETDAY;
   global $MEETWEEK;
   global $SEVENDAYS;

   // first test if this is the correct day of week
   $dw = date( "w", $timestamp);
   if ($dw != $MEETDAY)  {
    	// if NOT we can simply return here already
    	return 0;
   }
   // if it is the correct day of week
   // then we need to test if this is the correct one of this month
   // so we count backwards in 7day-steps until we reach another month
   $curtimestamp = $timestamp;
   $month = date( "n", $curtimestamp);
   //printf("starting with month of ".date('D, d.m.Y', $curtimestamp)."->".$month."<br>");

   $counter = 0;
   $comparemonth = $month;
   while ($comparemonth == $month) {
   		$counter = $counter + 1;
      	$curtimestamp = $curtimestamp - $SEVENDAYS;
   		$comparemonth = date("n", $curtimestamp);
   		//printf("month of ".date('D, d.m.Y', $curtimestamp)."->".$comparemonth."<br>");
   }

   // now we know the number of MEETDAYs in the currently searched month,
   // so we can look up the $counter value and compare!
   if ($counter == $MEETWEEK) {
   		return 1;
   }
   return 0;
}


//ini_set('display_errors', 0);
//ini_set('log_errors', 0);
error_reporting(E_ERROR | E_WARNING | E_PARSE );

?>