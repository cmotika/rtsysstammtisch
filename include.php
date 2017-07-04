<?php

header('Content-Type: text/html; charset=utf-8');

  // ===========================================================================
  // ==                               G E N E R A L                           ==
  // ===========================================================================

 function requireIndex() {
   // DO NOTHING - THIS METHOD IS CALLED FROM index-login.php to assure that
   // index.php has loaded before!
   return true;
 }

 function opendb() {
     global $WEBMASTER;
     global $DBUSER;
     global $DBPWD;
     global $DBURL;
     global $MYSQLIOBJ; 
    //echo 'TYRING TO ACCESS DB '.$DBURL. " with user ".$DBUSER." and pwd ".$DBPWD;
	 if (!@mysqli_connect($DBURL, $DBUSER, $DBPWD)) {
		die(@mysqli_error());
	 }
     $dbs = @mysqli_connect($DBURL, $DBUSER, $DBPWD);
//     $dbs = mysqli_connect($DBURL);
     if(!$dbs) { echo '-CANNOT ACCESS DATABASESERVER, PLEASE CONTACT THE SYSTEM ADMINISTARTOR AT '.$WEBMASTER; exit;}
     $MYSQLIOBJ = $dbs;
     return $dbs;
 }


  function getRND($len) {
         mt_srand((double) microtime()*1000000);
         $sessionid1 = md5(str_replace(".","",$REMOTE_ADDR) + mt_rand(100000,999999));
         $sessionid2 = md5(str_replace(".","",$REMOTE_ADDR) + mt_rand(100000,999999));
         $sessionid3 = md5(str_replace(".","",$REMOTE_ADDR) + mt_rand(100000,999999));
         $backvalue = substr($sessionid1.$sessionid2.$sessionid3,0,$len);
         return $backvalue;
  }//end function


  function clean($textstring) {
    //$textstring = preg_replace('/^[a-z0-9 .\-]+$/i', '', $textstring);
	$textstring = trim(preg_replace('/[^A-Za-z0-9 .\-]/', '', $textstring));
	return $textstring;
  }
  function clean2($textstring) {
    $textstring = str_replace ( "'" , "", $textstring );
    $textstring = str_replace ( "%" , "", $textstring );
    $textstring = str_replace ( "@" , "", $textstring );
    $textstring = str_replace ( "*" , "", $textstring );
    $textstring = str_replace ( "\\" , "", $textstring );
    $textstring = str_replace ( ")" , "", $textstring );
    $textstring = str_replace ( "(" , "", $textstring );
    $textstring = str_replace ( "!" , "", $textstring );
    $textstring = str_replace ( "&" , "", $textstring );
    $textstring = str_replace ( "|" , "", $textstring );
	return $textstring;
  }

  function genNameFromEmail($email) {
	$index = strrpos($email, "@");
	return clean2(substr($email, 0,$index));
  }


  function emailok($email) {
    $email = trim($email);

    $foundillegal = false;
    $found_at = false;
    $found_point = false;
    $host = "";
    $ext  = "";
    $name = "";

    for ($i = 0;$i < strlen($email); $i++) {
      $zeichen = substr($email,$i,1);

      $ascii = ord($zeichen);
      $ok = false;
      if ($zeichen == ".") $ok = true;
      if ($zeichen == "/") $ok = true;
      if ($zeichen == "@") $ok = true;
      if (($ascii >= 48)&&($ascii <= 57)) $ok = true;   //Zahl
      if (($ascii >= 65)&&($ascii <= 90)) $ok = true;   //GROSSE buschstaben
      if (($ascii >= 97)&&($ascii <= 122)) $ok = true;  //kleine buschstaben
      if (($ascii == 95)||($ascii == 45)) $ok = true; // "_" imd "-"
      if (!$ok) $foundillegal = true;

      if ($zeichen == "@") $found_at = true;
      if (($zeichen == ".")&&($found_at)) $found_point = true;

      if (($zeichen == "@")||($zeichen == ".")) $zeichen = "";

      if (($found_at)&&($found_point)) {
       $ext .= $zeichen;
      }
      else if ($found_at) {
       $host .= $zeichen;
      }
      else {
       $name .= $zeichen;
      }
    }//next $i

    //prüfen
    $allesok = true;
    if (!(found_at)) $allesok = false;
    if (!(found_point)) $allesok = false;
    if (strlen($host)< 1) $allesok = false;
    if (strlen($name)< 1) $allesok = false;
    if (strlen($ext)< 1) $allesok = false;
    if ($foundillegal) $allesok = false;

    return $allesok;
  }

  function denyService() {
   printf("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>500 Internal Server Error</title>
</head><body>
<h1>Internal Server Error</h1>
<p>The server encountered an internal error or
misconfiguration and was unable to complete
your request.</p>
<p>Please contact the server administrator,
 [no address given] and inform them of the time the error occurred,
and anything you might have done that may have
caused the error.</p>
<p>More information about this error may be available
in the server error log.</p>
<hr>
<address>Apache/2.2.22 (Debian) Server at ".$_SERVER['HTTP_HOST']." Port 80</address>
</body></html>
");
   exit;
  }


 function outputPhoto($photomid) {
	$uploaddir = dirname(__FILE__) . "/photos";
	$uploadfile = $uploaddir."/".$photomid.".jpg";
	//printf($uploadfile);
	//exit;
	if (file_exists($uploadfile)) {
		$type = 'image/jpeg';
		header('Content-Type:'.$type);
		header('Content-Length: '.filesize($uploadfile));
		readfile($uploadfile);
		exit;
	}
 }


 // ===========================================================================
 // ==                                  C R O N                              ==
 // ===========================================================================

 // calls all cron jobs and should be called once a day
 function cron() {
    global $TODAY;
    global $nextMeetingDate;
    printf("<BR>Today is ".date('D, d.m.Y', $TODAY)."<BR>");
    printf("<BR>Next Stammtisch is ".$nextMeetingDate."<BR><BR>");
 	choose(false);
    invite(false);
    remind();
 }

 function updateMeetingDate($mid, $newdate) {
  	       // update meeting: remember that we have invited
		   global $MYSQLIOBJ;
		   $query = "UPDATE meeting SET date = '".$newdate."' WHERE mid = '".$mid."'";
		   printf($query);
   	       $result = mysqli_query($MYSQLIOBJ, $query);
 }


 function invite($override) {
 	global $TODAY;
    printf("<BR><BR>Running invite cron...");
 	if (isInvitationDay($TODAY) || $override) {
 		printf("<BR>isInvitationDay:true");
		global $MYSQLIOBJ;
 		//exit;
 		// okay we get the last meeting with the same day from DB
 		$mid = getLastMeetingWithNoInvitation($TODAY, $override);
 		printf("<BR>mid:".$mid);
 		if ($mid > 0) {
 			// Go thru all users and send them the invitation
		   $query = "SELECT uid FROM user";
   		   //printf($query."<BR>");
   	 	   $result = mysqli_query($MYSQLIOBJ, $query);
   		   if ($result) {
				while($row_sections = mysqli_fetch_array($result)) {
		    	  	$uid = $row_sections['uid'];
		    	  	printf("<BR>Sending invitation mail to uid=:".$uid);
		    	  	sendEmailInvitation($uid, $mid);
				}
	 	   }
  	       // update meeting: remember that we have invited
		   $query = "UPDATE meeting SET invited = '".time()."' WHERE mid = '".$mid."'";
		   printf($query);
   	       $result = mysqli_query($MYSQLIOBJ, $query);
 		}
 	} else {
 		printf("<BR>isInvitationDay:false");
 	}
 }

 function remind() {
 	global $TODAY;
        global $MYSQLIOBJ;

    printf("<BR><BR>Running invite cron...");
 	if (isReminderDay($TODAY)) {
 		printf("<BR>isReminderDay:true");
 		//exit;
 		// okay we get the last meeting with the same day from DB
 		$mid = getLastMeetingWithNoReminder($TODAY);
 		printf("<BR>mid:".$mid);
 		if ($mid > 0) {
 			// Go thru all users and send them the reminder
		   $query = "SELECT uid FROM user";
   		   //printf($query."<BR>");
   	 	   $result = mysqli_query($MYSQLIOBJ, $query);
   		   if ($result) {
		  	   while($row_sections = mysqli_fetch_array($result)) {
		    	  	$uid = $row_sections['uid'];
		    	  	printf("<BR>Sending reminder mail to uid=:".$uid);
		    	  	sendEmailReminder($uid, $mid);
		       }
	 	   }
  	       // update meeting: remember that we have reminded
		   $query = "UPDATE meeting SET reminded = '".time()."' WHERE mid = '".$mid."'";
		   printf($query);
   	       $result = mysqli_query($MYSQLIOBJ, $query);
 		}
 	} else {
 		printf("<BR>isReminderDay:false");
 	}
 }


 function changeVenueEmail($reason) {
 		   global $mid;
    		   global $MYSQLIOBJ;

 			// Go thru all users and send them the invitation
 		   $query = "SELECT uid FROM user";
    		   //printf($query."<BR>");
    	 	   $result = mysqli_query($MYSQLIOBJ, $query);
    		   if ($result) {
 				while($row_sections = mysqli_fetch_array($result)) {
 		    	  	$uid = $row_sections['uid'];
 		    	  	//printf("<BR>Sending changed venue mail to uid=:".$uid);
 		    	  	sendEmailChangeVenue($uid, $mid, $reason);
 				}
 	 	   }
 }



 // choose a suggested venue if
 function choose($override) {
 	global $TODAY;
     global $MYSQLIOBJ;
    printf("<BR><BR>Running choose cron...");
 	if (isChooseDay($TODAY) || $override) {
 		printf("<BR>isChooseDay:true");
 		//exit;
 		// okay we get the last meeting with the same day from DB
 		$mid = getLastMeetingWithNoLinkedSuggestion($TODAY);
 		printf("<BR>mid:".$mid);
 		if ($mid > 0	) {
 		     // now choose the most top sid
			 $sid = chooseTopSuggestion();
	   		 printf("<BR>sid:".$sid);
 		     // insert the sid
		     $query = "UPDATE meeting SET sid = '".$sid."' WHERE mid = '".$mid."'";
		     printf($query);
	   	     $result = mysqli_query($MYSQLIOBJ, $query);
 		     // tell the suggestion that it has been "consumed" now
		     $query = "UPDATE suggestion SET chosen = '".$mid."' WHERE sid = '".$sid."'";
		     printf($query);
	   	     $result = mysqli_query($MYSQLIOBJ, $query);
 		}
 	} else {
 		printf("<BR>isChooseDay:false");
 	}
 }


 function getLastMeetingWithNoLinkedSuggestion($today) {
    global $CHOOSEVENUE_DAYSBEFORE;
    global $ONEDAY;
     global $MYSQLIOBJ;

    $meetingDay = $today + ($CHOOSEVENUE_DAYSBEFORE*$ONEDAY);
    $meetingDayFrom = $meetingDay - $ONEDAY;
    $meetingDayTo = $meetingDay + $ONEDAY;
    $backvalue = -1;
    //$query = "SELECT mid FROM meeting WHERE sid < '1' and date > ".$meetingDayFrom." and date < ".$meetingDayTo;
    $query = "SELECT mid FROM meeting WHERE sid < '1' and date > ".date("U")." ORDER BY date ASC";
    //printf($query);
    $result = mysqli_query($MYSQLIOBJ, $query);
    if ($result) {
    //if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
        $e = implode(" ",$row);
        $f = explode(" ",$e);
        $backvalue = $f[0];
    }
    return $backvalue;
 }

 function getLastMeetingWithNoInvitation($today, $override) {
    global $SENDINVITATION_DAYSBEFORE;
    global $ONEDAY;
     global $MYSQLIOBJ;

    if ($SENDINVITATION_DAYSBEFORE < 0) {
    	return -1;
    }
    $meetingDay = $today + ($SENDINVITATION_DAYSBEFORE*$ONEDAY);
    $meetingDayFrom = $meetingDay - $ONEDAY;
    $meetingDayTo = $meetingDay + $ONEDAY;
    $backvalue = -1;
    $query = "SELECT mid FROM meeting WHERE invited < '1' and date > ".$meetingDayFrom." and date < ".$meetingDayTo;
    if ($override) {
	    $query = "SELECT mid FROM meeting WHERE date > ".date("U")." ORDER BY date ASC";
    }
    //printf($query);
    $result = mysqli_query($MYSQLIOBJ, $query);
    if ($result) {
    //if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
        $e = implode(" ",$row);
        $f = explode(" ",$e);
        $backvalue = $f[0];
    }
    return $backvalue;
 }

 function getLastMeetingWithNoReminder($today) {
    global $SENDREMINDER_DAYSBEFORE;
    global $ONEDAY;
     global $MYSQLIOBJ;

    if ($SENDREMINDER_DAYSBEFORE < 0) {
    	return -1;
    }
    $meetingDay = $today + ($SENDREMINDER_DAYSBEFORE*$ONEDAY);
    $meetingDayFrom = $meetingDay - $ONEDAY;
    $meetingDayTo = $meetingDay + $ONEDAY;
    $backvalue = -1;
    $query = "SELECT mid FROM meeting WHERE reminded < '1' and date > ".$meetingDayFrom." and date < ".$meetingDayTo;
    //printf($query);
    $result = mysqli_query($MYSQLIOBJ, $query);
    if ($result) {
    //if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
        $e = implode(" ",$row);
        $f = explode(" ",$e);
        $backvalue = $f[0];
    }
    return $backvalue;
 }



 // ===========================================================================
 // ==                M E E T I N G  /    C A N C E L I N G                  ==
 // ===========================================================================

function isReminderDay($timestamp) {
   global $SENDREMINDER_DAYSBEFORE;
   global $ONEDAY;
   if ($SENDREMINDER_DAYSBEFORE < 0) {
   		return false;
   }
   $diff = $ONEDAY*$SENDREMINDER_DAYSBEFORE;
   return (isMeetingDayWrapper($timestamp + $diff));
}

function isInvitationDay($timestamp) {
   global $SENDINVITATION_DAYSBEFORE;
   global $ONEDAY;
   if ($SENDINVITATION_DAYSBEFORE < 0) {
   		return false;
   }
   $diff = $ONEDAY*$SENDINVITATION_DAYSBEFORE;
   return (isMeetingDayWrapper($timestamp + $diff));
}
function isChooseDay($timestamp) {
   global $CHOOSEVENUE_DAYSBEFORE;
   global $ONEDAY;
   if ($CHOOSEVENUE_DAYSBEFORE < 0) {
   		$CHOOSEVENUE_DAYSBEFORE = 0;
   }
   $diff = $ONEDAY*$CHOOSEVENUE_DAYSBEFORE;
   $testDate = $timestamp + $diff;
   //printf("timestamp:".$timestamp."<BR>");
   //printf("TestDate:".$testDate."<BR>");
   return (isMeetingDayWrapper($testDate));
}

//$mydate = strtotime("next Wednesday");
//printf("Is Meeting Day:".isMeetingDayWrapper($mydate."<br>");
//printf("Is Choose Day:".isMeetingDayWrapper($mydate."<br>");
//printf("Is Invitation Day:".isMeetingDayWrapper($mydate."<br>");
//printf("Is Reminder Day:".isMeetingDayWrapper($mydate."<br>");


function getNextMeetingTimeStampHelper() {
   global $ONEDAY;
   global $TODAY;
   $todayplusx = $TODAY;
   $found = false;
   while (!$found) {
        $todayplusx = $todayplusx + $ONEDAY;
   		if (isMeetingDay($todayplusx)) {
   			$found = true;
   		}
   }
   return $todayplusx;
}


function getNextMeetingHelper($calledagain) {
   global $MYSQLIOBJ;
   global $ONEDAY;
   global $TODAY;
   // first search database
	$result = mysqli_query($MYSQLIOBJ,  "SELECT mid,date FROM meeting ORDER BY date DESC");
    if ($result) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $date = $f[1];
       // now check if this is in the future?
       if ($TODAY < ($date + (1*$ONEDAY))) { // ALLOW to correct the attendence list up to one day after!
       		return $f[0];
       }
    }

   if ($calledagain) {
   		// break out here, DB error possibly!
   		return -1;
   }

   // if nothing found there, then calculate the next date and save to database
   $nextmeetingtimestamp = getNextMeetingTimeStampHelper();
   $query = "INSERT INTO `meeting` (date)
                    VALUES ('".$nextmeetingtimestamp."')";
   //printf($query."<BR>");
   $result = mysqli_query($MYSQLIOBJ, $query);

   // now call this function again, it should find the DB value then
   return getNextMeetingHelper(true);
}

function getNextMeeting() {
	return getNextMeetingHelper(false);
}

function getNextMeetingTimeStamp() {
	$mid = getNextMeeting();
	return getMeetingDate($mid);
}

// This method is a wrapper for isMeetingDay and also considers
// a possible backup date from database if set for the future!
function isMeetingDayWrapper($testDay) {
    global $TODAY;
	//print("<br><br><br><br>dayNM_TS=".getNextMeetingTimeStamp()."<BR> testDay_TS=".$testDay);
	$dayNM = date("jnY", getNextMeetingTimeStamp());
	$dayToday = date("jnY", $testDay);
	//print("<br><br><br><br>".$dayNM." == ".$dayToday);
	return ($dayNM == $dayToday);
}

 // only allow if the $mid is in the past and the $uid has attended
 function allowedToRate($mid, $uid) {
   global $TODAY;
   global $ONEDAY;
   $uid = intval($uid);
   $mid = intval($mid);

   $meetingDate = getMeetingDate($mid);

   //printf("uid: ".$uid."<br>");
   //printf("mid: ".$mid."<br>");
   //printf("meetingDate: ".$meetingDate."<br>");
   //printf("TODAY: ".$TODAY."<br>");
   //printf("confirmed($uid, $mid): ".confirmed($uid, $mid)."<br>");

   if ($TODAY < $meetingDate) {
   		return false;
   }
   if (!confirmed($uid, $mid)) {
   		return false;
   }
   return true;
 }

 // only allow if the $mid is the future
 function allowToConfirm($mid, $uid) {
   global $TODAY;
   global $ONEDAY;
   $uid = intval($uid);
   $mid = intval($mid);

   if ($TODAY-$ONEDAY > getMeetingDate($mid)) {
   		return false;
   }
   return true;
 }


 function rate($mid, $uid, $rating) {
         global $MYSQLIOBJ;

    $uid = intval($uid);
    $mid = intval($mid);
    $rating = intval($rating);

    $query = "DELETE FROM `rating` WHERE uid = '$uid' and mid = '$mid'";
    //printf($query."<BR>");
    $result = mysqli_query($MYSQLIOBJ, $query);

    if ($rating > -1) {
    	$query = "INSERT INTO `rating` (mid, uid, rating, timestamp)
                 VALUES ('".$mid."','".$uid."','".$rating."','".date("U")."')";
       	//printf($query."<BR>");
    	$result = mysqli_query($MYSQLIOBJ, $query);
    }
 }

 function getOwnRating($uid, $mid) {
    global $MYSQLIOBJ;
    $query = "SELECT rating FROM `rating` WHERE mid = '".$mid."' and uid = '".$uid."'";
    //printf($query."<BR>");
    $rating = -1; // no rating
    $result = mysqli_query($MYSQLIOBJ, $query);
    $records = mysqli_num_rows($result);
    if ($records > 0) {
 	   $row_sections = mysqli_fetch_array($result);
  	   $rating = $row_sections['rating'];
 	   if ($rating == NULL) {
	 	    $rating = -1;
 	   }
    }
    return $rating;
 }

 function getRating($mid) {
    global $MYSQLIOBJ;
    $query = "SELECT AVG(rating) AS averageRating FROM `rating` WHERE mid = '".$mid."'";
    //printf($query."<BR>");
    $rating = -1; // no rating
    $result = mysqli_query($MYSQLIOBJ, $query);
    $records = mysqli_num_rows($result);
    if ($records > 0) {
 	   $row_sections = mysqli_fetch_array($result);
  	   $rating = $row_sections['averageRating'];
 	   if ($rating == NULL) {
	 	    $rating = -1;
 	   }
    }
    return $rating;
}

function getRatingCount($mid) {
   global $MYSQLIOBJ;
   $query = "SELECT COUNT(rating) AS countRating FROM `rating` WHERE mid = '".$mid."'";
    //printf($query."<BR>");
    $rating = -1; // no rating
    $result = mysqli_query($MYSQLIOBJ, $query);
    $records = mysqli_num_rows($result);
    if ($records > 0) {
 	   $row_sections = mysqli_fetch_array($result);
  	   $rating = $row_sections['countRating'];
 	   if ($rating == NULL) {
	 	    $rating = -1;
 	   }
    }
    return $rating;
}




 function confirm($uid, $mid, $confirm, $phone, $comment) {
    global $MYSQLIOBJ;
    $uid = intval($uid);
    $mid = intval($mid);
    if ($phone != 1) {
     	$phone = 0;
    }
    $comment = clean2($comment);

	if (!$confirm) {
	    $query = "DELETE FROM `confirm` WHERE uid = '$uid' and mid = '$mid'";
    	//printf($query."<BR>");
	    $result = mysqli_query($MYSQLIOBJ, $query);
	}
	if ($confirm) {
		if (confirmed($uid, $mid)) {
		    $query1 = "UPDATE `confirm` SET phone = '".$phone."' WHERE uid = '".$uid."' AND mid = '".$mid."'";
		    //$query2 = "UPDATE `confirm` SET comment = '".$comment."' WHERE uid = '".$uid."' AND mid = '".$mid."'";
	    	$result = mysqli_query($MYSQLIOBJ, $query1);
	    	//$result = mysqli_query($MYSQLIOBJ, $query2);
		} else {
	    	$query = "INSERT INTO `confirm` (mid, uid, confirmed, phone, comment)
        	         VALUES ('".$mid."','".$uid."','".date("U")."','".$phone."','".$comment."')";
    	   	//printf($query."<BR>");
	    	$result = mysqli_query($MYSQLIOBJ, $query);
		}
    }
 }


 function cleanSessions() {
      global $MYSQLIOBJ;
	global $DELETESESSION;
 	$timebarrier = date("U") - $DELETESESSION;
    $query = "DELETE FROM `session` WHERE created < '$timebarrier'";
    //printf($query."<BR>");
    $result = mysqli_query($MYSQLIOBJ, $query);
 }


 function confirmed($uid, $mid) {
        global $MYSQLIOBJ;
$uid = intval($uid);
   $mid = intval($mid);
   $result = mysqli_query($MYSQLIOBJ, "SELECT uid FROM confirm WHERE mid = '".$mid."' and uid = '".$uid."'");
   if (mysqli_num_rows($result) > 0) {
       return true;
   }
   return false;
 }


 function confirmedWithPhone($uid, $mid) {
   global $MYSQLIOBJ;
   $uid = intval($uid);
   $mid = intval($mid);
   $result = mysqli_query($MYSQLIOBJ, "SELECT uid FROM confirm WHERE mid = '".$mid."' and uid = '".$uid."' and phone = '1'");
   if (mysqli_num_rows($result) > 0) {
       return true;
   }
   return false;
 }



 function whoIsThere($mid) {
   global $uid;
   global $MYSQLIOBJ;
   $query = "SELECT uid,phone,comment FROM confirm WHERE mid = '".$mid."' ORDER BY confirmed ASC";
   //printf($query."<BR>");
   $result = mysqli_query($MYSQLIOBJ, $query);
   if ($result) {
 	 printf("<table cellspacing='0' cellpadding='0' border='0'>");
	 $i = 0;
	 while($row_sections = mysqli_fetch_array($result)) {
	    $otheruid = $row_sections['uid'];
	    $phone = $row_sections['phone'];
	    $comment = $row_sections['comment'];
	 	//if (confirm($mid, $uid)) {
	 	     $name = getName($otheruid);
		     $i = $i + 1;
		     printf("<tr valign='bottom'>");
		     printf("<td  valign='bottom'>");
	 		 printf("<h4>".$i.".</h4></td><td>&nbsp;</td><td valign='bottom'><h4>".$name."&nbsp&nbsp");
	 		 if ($phone) {
	 		  	printf("<img  title=\"I will reserve the table!\" alt=\"I will reserve the table!\" src=\"phone.png\">");
	 		 }
	 		 printf("&nbsp;&nbsp;</h4>");
	 		 if ($comment != "") {
	 		 	printf("</td></tr><tr valign='top'><td></td><td></td><td valign='top'><h6>".$comment."<br><h6>");
	 		 }

	 		 if (isAdminView($uid)) {
			     printf("</td><td valign='middle'>");
				 printf("<form  method='post'>");
			 	 printf("<input type='hidden'  name='s' value='".$s."'>");
			 	 printf("<input type='hidden'  name='otheruid' value='".$otheruid."'>");
	 		 	 printf("<input type='hidden'  name='cmd' value='admincancel'>");
		 		 printf("<button type='submit' class='btn btn-danger'>Cancel</button>");
				 printf("</form>");
	 		 }

		     printf("</td></tr>");
	 	//}
	 }
     printf("</table>");
   }
 }



 function whoIsRegistered() {
   global $MYSQLIOBJ;
   $backvalue = "";
   $query = "SELECT uid FROM user WHERE name != 'deleted' and lastseen > '10' ORDER BY name ASC";
   $result = mysqli_query($MYSQLIOBJ, $query);
   if ($result) {
	 while($row_sections = mysqli_fetch_array($result)) {
	    $uid = $row_sections['uid'];
        $name = getName($uid);
        if (strlen($backvalue) > 0) {
           $backvalue = $backvalue.", ";
        }
        $backvalue = $backvalue."$name";
	 }
   }
   return $backvalue;
 }


  function whoIsRegisteredCount() {
    global $MYSQLIOBJ;
    $query = "SELECT uid FROM user WHERE name != 'deleted' and lastseen > '10' ";
    $result = mysqli_query($MYSQLIOBJ, $query);
    $i = 0;
    if ($result) {
 	 while($row_sections = mysqli_fetch_array($result)) {
 	    $i = $i + 1;
 	 }
    }
    return $i;
  }

  function whoIsInvitedCount() {
    global $MYSQLIOBJ;
    $query = "SELECT uid FROM user WHERE name != 'deleted' and lastseen < '10' ";
    $result = mysqli_query($MYSQLIOBJ, $query);
    $i = 0;
    if ($result) {
 	 while($row_sections = mysqli_fetch_array($result)) {
 	    $i = $i + 1;
 	 }
    }
    return $i;
  }


 function whoIsThereEmail($mid) {
   global $MYSQLIOBJ;
   $backvalue = "";
   $query = "SELECT uid FROM confirm WHERE mid = '".$mid."' ORDER BY confirmed ASC";
   $result = mysqli_query($MYSQLIOBJ, $query);
   if ($result) {
	 while($row_sections = mysqli_fetch_array($result)) {
	    $uid = $row_sections['uid'];
        $name = getName($uid);
        if (strlen($backvalue) > 0) {
           $backvalue = $backvalue.", ";
        }
        $backvalue = $backvalue."$name";
	 }
   }
   return $backvalue;
 }


  function whoIsThereCount($mid) {
    global $MYSQLIOBJ; 
    $query = "SELECT uid FROM confirm WHERE mid = '".$mid."' ORDER BY confirmed ASC";
    $result = mysqli_query($MYSQLIOBJ, $query);
    $i = 0;
    if ($result) {
 	 while($row_sections = mysqli_fetch_array($result)) {
 	    $i = $i + 1;
 	 }
    }
    return $i;
  }

  function whoReservesTableCount($mid) {
    global $MYSQLIOBJ;
    $query = "SELECT uid FROM confirm WHERE mid = '".$mid."' AND phone > '0' ORDER BY confirmed ASC";
    $result = mysqli_query($MYSQLIOBJ, $query);
    $i = 0;
    if ($result) {
 	 while($row_sections = mysqli_fetch_array($result)) {
 	    $i = $i + 1;
 	 }
    }
    return $i;
  }


function getSuggestionName($sid) {
   global $MYSQLIOBJ;
   $mid = intval($mid);
   $backvalue = -1;
   $result = mysqli_query($MYSQLIOBJ, "SELECT name FROM suggestion WHERE sid = '".$sid."'");
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $backvalue = $f[0];
   }
   return $backvalue;
}
function getSuggestionAddress($sid) {
   global $MYSQLIOBJ; 
   $mid = intval($mid);
   $backvalue = -1;
   $result = mysqli_query($MYSQLIOBJ, "SELECT address FROM suggestion WHERE sid = '".$sid."'");
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $backvalue = $f[0];
   }
   return $backvalue;
}


function getMeetingLocationSid($mid) {
   global $MYSQLIOBJ;
   // if any location is yet chosen!
   $mid = intval($mid);
   $result = mysqli_query($MYSQLIOBJ, "SELECT sid FROM meeting WHERE mid = '".$mid."'");
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $sid = $f[0];

       if ($sid < 1) {
           return -1;
       }
       return $sid;
   }
   // should not be reached
   return -1;
}

function getMeetingLocationAddress($mid) {
   // if any location is yet chosen!
   $sid = getMeetingLocationSid($mid);
   if ($sid < 1) {
           return "";
   }
   return getSuggestionAddress($sid);
}

function getMeetingLocationName($mid) {
   // if any location is yet chosen!
   $sid = getMeetingLocationSid($mid);
   if ($sid < 1) {
           return "";
   }
   return getSuggestionName($sid);
}

function getMeetingLocationNameEmail($mid) {
    $locationName = getMeetingLocationName($mid);
    if ($locationName == "") {
    	return $locationName = "t.b.a.";
    }
	return $locationName;
}


function getMeetingLocationLabel($mid) {
    $locationName = getMeetingLocationName($mid);
    if ($locationName == "") {
    	return $locationName = "t.b.a.";
    }
    $locationAddress = getMeetingLocationAddress($mid);

    return "<a target='_blank' href='http://www.google.de/search?q=".$locationName." ".$locationAddress."'>".$locationName."</a>".",&nbsp<a target='_blank' href='https://www.google.de/maps?q=".$locationAddress."'>".$locationAddress."</a>";
 }



function getMeetingDate($mid) {
   global $MYSQLIOBJ; 
   $mid = intval($mid);
   $backvalue = -1;
   $result = mysqli_query($MYSQLIOBJ, "SELECT date FROM meeting WHERE mid = '".$mid."'");
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $backvalue = $f[0];
   }
   return $backvalue;
}




function getRatingHTML($mid) {
  global $s;
  global $uid;
  $rating = getRating($mid);
  $ownrating = getOwnRating($uid, $mid);
  //print($rating);
  $count = getRatingCount($mid)." people rated this venue: ".$rating;


  for($c=0;$c <4; $c++) {
     if ($rating == -1) {
         //no rating
	     printf("<img src='norating.png'  width='15' height='15' title='".$count."'>");
     }
     else if ($rating > $c) {
	     if ($rating > ($c + 0.5)) {
	        printf("<img src='star.png'  width='15' height='15' title='".$count."'>");
	     }
         else {
	        printf("<img src='halfstar.png'  width='15' height='15' title='".$count."'>");
         }
     } else {
        printf("<img src='nostar.png' width='15' height='15' title='".$count."'>");
     }
  }


  if (allowedToRate($mid, $uid)) {

  printf('
  <form method="post" name="rating'.$mid.'">
  <input type="hidden"  name="cmd" value="rate">
  <input type="hidden"  name="s" value="'.$s.'">
  <input type="hidden"  name="ratemid" value="'.$mid.'">
  &nbsp
  <select name="rating" size="1" onChange="document.forms.rating'.$mid.'.submit()"> ');
  for($c=-1;$c <=4; $c++) {
     $rateLabel = $c;
     if ($c == -1) {
     	$rateLabel = "rate";
     }

     if ($c == $ownrating) {
     	printf('<option value="'.$c.'" selected="selected">'.$rateLabel.'</option>');
     } else {
     	printf('<option value="'.$c.'">'.$rateLabel.'</option>');
     }
  }
  printf(' </select> </form>
  ');
  }//end if allowed to rate
}


function photoURL($mid, $i) {
    global $BASEURL;
    global $s;
    global $uid;
	$uploaddir = dirname(__FILE__)."/photos/";
	$uploadlink = "&nbsp;<a href=\"#\" onClick=\"document.getElementById('upload".$i."').style.display = 'block'; return false;\"><img src='attach.png' width='15' height='15'></a>";
	if (file_exists($uploaddir.$mid.".jpg")) {
	    $imagelink = "&nbsp;<a target='_blank' href='".$BASEURL."?cmd=photo&s=".$s."&photomid=".$mid."'><img src='photo.png' width='15' height='15'></a>";
	    if (isAdminView($uid)) {
			$imagelink.=$uploadlink;
		}
		return $imagelink;
	}
	else {
	  if (allowedToRate($mid, $uid)) {
	    return $uploadlink;
	  } else {
	  	return "";
	  }
	}
}


function getPastDates() {
   global $uid;
   global $MYSQLIOBJ;
   $query = "SELECT mid,date FROM meeting ORDER BY date DESC";
   //printf($query."<BR>");
   $result = mysqli_query($MYSQLIOBJ, $query);
   if ($result) {
 	 printf("<table border='0' cellspacing='100'>");
	 $i = 0;
	 while($row_sections = mysqli_fetch_array($result)) {
	    $mid = $row_sections['mid'];
	    $date = $row_sections['date'];
	    $dateString = date('d.m.Y', $date);
	    $count = whoIsThereCount($mid);
	    $persons = whoIsThereEmail($mid);
	 	//if (confirm($mid, $uid)) {
	 	     $locationLabel = getMeetingLocationLabel($mid);
		     $i = $i + 1;
		     printf("<tr>");
		     printf("<td valign='top'>");
	 		 printf("<h5>".$dateString."&nbsp;&nbsp;&nbsp;</h5></td><td><h5>".$locationLabel." (<a href=\"#\" onClick=\"document.getElementById('names".$i."').style.display = 'block'; return false;\">".$count."</a>)&nbsp;".photoURL($mid, $i));
	 		 printf("</h5>");
	 		 printf("<span id=\"names".$i."\" style=\"display: none;\">");
	 		 printf("<h5>".$persons."</h5><br>");
	 		 printf("</span>");

 if (allowedToRate($mid, $uid) || isAdminView($uid)) {

	 		 //if (isAdminView($uid)) {
	 		 //printf("<a href=\"#\" onClick=\"document.getElementById('upload".$i."').style.display = 'block'; return false;\">Attach Photo &raquo</a>");
	 		 printf("<span id=\"upload".$i."\" style=\"display: none;\">");
	 		 printf('
<form enctype="multipart/form-data" method="POST">
    <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
    <!-- Der Name des Input Felds bestimmt den Namen im $_FILES Array -->
    <div id="wrapper">
    <input name="photofile" type="file" size="100" />
    </div>
    <input type="hidden"  name="s" value="'.$s.'">
    <input type="hidden"  name="uploadmid" value="'.$mid.'">
    <input type="hidden"  name="cmd" value="upload">
    <input type="submit" value="Upload Photo" />
</form>
			');
	 		 printf("<br></span>");
			//}
 }

		     printf("</td>");
	 		 printf("<td width='80' valign='top'>&nbsp&nbsp");
	 		 getRatingHTML($mid);
		     printf("</tr>");
	 	//}
	 }
     printf("</table>");
   }
}






  // ===========================================================================
  // ==                  I C A L     E X P O R T                              ==
  // ===========================================================================

function getTimestampOfStartOfDay($timestamp) {
    $year = date("Y", $timestamp) + 1 - 1;
	$month = date("n", $timestamp) + 1 - 1;
	$day = date("j", $timestamp) + 1 - 1;
	$timestampReturn = mktime(0,0,0,$month,$day,$year);
	return $timestampReturn;
}


function dateToCal($timestamp) {
//	return date('Ymd\THis\Z', $timestamp);
	return date('Ymd\THis', $timestamp);
}
function escapeString($string) {
	return preg_replace('/([\,;])/','\\\$1', $string);
}

function exportICS($mid, $alarm) {
    global $BASEURL;
	global $MEETINGDURATION;
	global $MEETINGSTART;
	global $UTCTIMEDIFF;

	$icsfilename = "stammtisch.ics";

	$date =	getMeetingDate($mid);
	$venu = getMeetingLocationNameEmail($mid);
	$address = getMeetingLocationAddress($mid);

	$datestart = getTimestampOfStartOfDay($date) + $MEETINGSTART;// + $UTCTIMEDIFF;
	$dateend   = $datestart + $MEETINGDURATION;

    $summary = "RtSys Stammtisch @ ".$venu;

	header('Content-Type: text/Calendar; charset=utf-8');
	header('Content-Disposition: attachment; filename=' . $icsfilename);

printf('BEGIN:VCALENDAR
VERSION:2.0
X-WR-TIMEZONE:Europe/London
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTEND:'.dateToCal($dateend).'
UID:'.uniqid().'
DTSTAMP:'.dateToCal(time()).'
LOCATION:'.escapeString($address).'
DESCRIPTION:'.escapeString($venu.", ".$BASEURL).'
URL;VALUE=URI:'.escapeString($BASEURL).'
SUMMARY:'.escapeString($summary).'
DTSTART:'.dateToCal($datestart).'');

if ($alarm > -1) {
 printf('
BEGIN:VALARM
TRIGGER:-PT'.$alarm.'H
ACTION:DISPLAY
DESCRIPTION:'.$summary.", ".$BASEURL.'
END:VALARM
');
}

printf('END:VEVENT
END:VCALENDAR');
exit;
}





function exportVCS($mid) {
    global $BASEURL;
	global $MEETINGDURATION;
	global $MEETINGSTART;
	global $UTCTIMEDIFF;

	$icsfilename = "stammtisch.vcs";

	$date =	getMeetingDate($mid);
	$venu = getMeetingLocationNameEmail($mid);
	$address = getMeetingLocationAddress($mid);

	$datestart = getTimestampOfStartOfDay($date) + $MEETINGSTART;// + $UTCTIMEDIFF;
	$dateend   = $datestart + $MEETINGDURATION;

    $summary = "Stammtisch @ ".$venu;

	header('Content-Type: text/x-vCalendar');
	header('Content-Disposition: attachment; filename=' . $icsfilename);

printf('BEGIN:VCALENDAR
PRODID:-//AT Content Types//AT Event//EN
VERSION:2.0
X-WR-TIMEZONE:Europe/London
METHOD:PUBLISH
BEGIN:VEVENT
DTSTAMP:'.dateToCal(time()).'
CREATED:'.dateToCal(time()).'
LAST-MODIFIED:'.dateToCal(time()).'
SUMMARY:'.escapeString($summary).'
DTSTART:'.dateToCal($datestart).'
DTEND:'.dateToCal($dateend).'
LOCATION:'.escapeString($address).'
URL:'.escapeString($BASEURL).'
CLASS:PUBLIC
END:VEVENT
END:VCALENDAR');
exit;
}





  // ===========================================================================
  // ==                U S E R       M A N A G E M A N T                      ==
  // ===========================================================================

  function resetPwd($uid, $otheruser) {
    global $MYSQLIOBJ; 
    if (isAdmin($uid)) {
	    $otheruid = getUID($otheruser);
		if ($otheruid > 0) {
		    $query = "UPDATE user SET pwd = '' WHERE uid = '".$otheruid."'";
  		    mysqli_query($MYSQLIOBJ, $query);
  		    return true;
		}
	}
	return false;
  }



 function getUIDByMail($email) {
   global $MYSQLIOBJ;
   $backvalue = -1;
   if ($email == "email") {
   		return -1;
   }
   $result = mysqli_query($MYSQLIOBJ, "SELECT uid FROM user WHERE email LIKE  '".$email."'");
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $backvalue = $f[0];
   }
   return $backvalue;
 }

 function getUID($name) {
   global $MYSQLIOBJ; 
   $backvalue = -1;
   if ($user == "user") {
   		return -1;
   }
   $result = mysqli_query($MYSQLIOBJ, "SELECT uid FROM user WHERE name LIKE  '".$name."'");
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $backvalue = $f[0];
   }
   return $backvalue;
 }

 function getName($uid) {
   global $MYSQLIOBJ;
   $uid = intval($uid);
   $backvalue = -1;
   $result = mysqli_query($MYSQLIOBJ, "SELECT name FROM user WHERE uid = '".$uid."'");
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $backvalue = $f[0];
   }
   return $backvalue;
 }

 function getEmail($uid) {
   global $MYSQLIOBJ; 
$uid = intval($uid);
   $backvalue = -1;
   $result = mysqli_query($MYSQLIOBJ, "SELECT email FROM user WHERE uid = '".$uid."'");
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $backvalue = $f[0];
   }
   return $backvalue;
 }

  function isAdmin($uid) {
    global $MYSQLIOBJ;
    $uid = intval($uid);
    $result = mysqli_query($MYSQLIOBJ, "SELECT admin FROM user WHERE uid = '".$uid."' and admin = '1'");
    if (mysqli_num_rows($result) > 0) {
    	return true;
    }
    return false;
 }

 function isAdminView($uid) {
 	global $adminview;
    return ($adminview > 0 && isAdmin($uid));
 }

 function updateName($uid, $name) {
   global $MYSQLIOBJ; 
   $uid = intval($uid);
   if ($name == "name") {
   		return -1;
   }
   $query = "UPDATE user SET name = '".$name."' WHERE uid = '".$uid."'";
   mysqli_query($MYSQLIOBJ, $query);
 }

 function updateEmail($uid, $email) {
   global $MYSQLIOBJ;
   $uid = intval($uid);

   if ($email == "email") {
   		return -1;
   }
   $query = "UPDATE user SET email = '".$email."' WHERE uid = '".$uid."'";
   mysqli_query($MYSQLIOBJ, $query);
 }


 function getPwd($uid) {
   global $MYSQLIOBJ; 
   $uid = intval($uid);
   $backvalue = -1;
   if ($uid == "uid") {
   		return -1;
   }
   $query = "SELECT pwd FROM user WHERE uid = '".$uid."'";
   //printf("query:".$query."<BR>");
   $result = mysqli_query($MYSQLIOBJ, $query);
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $backvalue = $f[0];
   }
   return $backvalue;
 }


 function updatePwd($uid, $pwd) {
   global $MYSQLIOBJ;
   $uid = intval($uid);
   if ($pwd == "pwd") {
   		return -1;
   }
   $query = "UPDATE user SET pwd = '".md5($pwd)."' WHERE uid = '".$uid."'";
   mysqli_query($MYSQLIOBJ, $query);
 }

 function removeAccount($uid) {
   global $MYSQLIOBJ; 
   $uid = intval($uid);
   $query = "UPDATE user SET pwd = '".getRND(20)."' WHERE uid = '".$uid."'";
   mysqli_query($MYSQLIOBJ, $query);
   $query = "UPDATE user SET name = 'deleted' WHERE uid = '".$uid."'";
   mysqli_query($MYSQLIOBJ, $query);
   $query = "UPDATE user SET email = '' WHERE uid = '".$uid."'";
   mysqli_query($MYSQLIOBJ, $query);
 }

 function updateLastSeen($uid) {
        		 global $MYSQLIOBJ; 
  			// update lastseen value
	   		     $query = "UPDATE user SET lastseen = '".time()."' WHERE uid = '".$uid."'";
	   		     //printf($query);
	   	   	     $result = mysqli_query($MYSQLIOBJ, $query);
 }

 function updateIP($s) {
  	    global $IP;
  	    global $browser;
        global $MYSQLIOBJ; 
	$query = "UPDATE session SET ip = '".$IP."' WHERE sessionid = '".$s."'";
        //printf($query);
        //exit;
        $result = mysqli_query($MYSQLIOBJ, $query);
        $query = "UPDATE session SET browser = '".$browser."' WHERE sessionid = '".$s."'";
        //printf($query);
        //exit;
        $result = mysqli_query($MYSQLIOBJ, $query);
 }


 function login($uid, $pwd) {
global $MYSQLIOBJ;
   $uid = intval($uid);
   // clean old sessions
   cleanSessions();

   $result = mysqli_query($MYSQLIOBJ, "SELECT pwd FROM user WHERE uid = '".$uid."'");
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       //printf($pwd." -login2-> ".md5($pwd)." == ".$f[0]."<BR>");
       if  (($f[0] == "") || (md5($pwd) == $f[0])) {

        		 // update lastseen value
        		 updateLastSeen($uid);

       		return true;
       }
   }
   return false;
   //return true;
  }


  function logout($uid) {
global $MYSQLIOBJ;
	    $uid = intval($uid);
   	    // delete old sessions
  	    $query = "DELETE FROM `session` WHERE uid = '$uid'";
            $result = mysqli_query($MYSQLIOBJ, $query);
  }

  // used for mailing
  function updateSessionNoLogout($uid, $s) {
		global $MYSQLIOBJ;
  		if ($s != "") {
			// insert new session
	    	$query = "INSERT INTO `session` (uid, sessionid, created)
                 VALUES ('".$uid."','".$s."','".date("U")."')";
        	//printf("<BR><BR><BR>".$query."<BR>");
  	    	$result = mysqli_query($MYSQLIOBJ, $query);
	}  
}

  function updateSession($uid, $s) {
  		// disabled auto-logout, session will expire in 48hours
		//logout($uid);
		//printf("<br><br><br><br>s is =".$s);
  		updateSessionNoLogout($uid, $s);
  		updateIP($s);
  }

 //
 function getSessionUID($s) {
   global $IP;
   global $browser;
   global $MYSQLIOBJ;
   $backvalue = -1;
   $result = mysqli_query($MYSQLIOBJ, "SELECT uid, ip, browser FROM session WHERE sessionid = '".$s."'");
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $uid = $f[0];
       $sessionip = $f[1];
       $sessionbrowser = $f[2];
       //print("sessionbrowser:".$sessionbrowser."<br>");
       //print("browser:".$browser."<br>");
       if ((($sessionip != "") && ($sessionip != $IP)) || (($sessionbrowser != "")&&($sessionbrowser != $browser))) {
          // failed validation of sessionbrower / sessionip
       	  $uid = -2;
       }
   }
   return $uid;
 }


  function createRandomSession($uid) {
  	    $s = getRND(50);
  	    //printf("<br><br><br><br>random s=".$s);
  		updateSession($uid, $s);
  		return $s;
  }


  function createQuickConfirmLink($uid) {
    global $BASEURL;
  	$s = createRandomSession($uid);
  	updateSessionNoLogout($uid, $s);
  	return $BASEURL."?s=".$s."&cmd=confirm";
  }



  function inviteNewUser($uid, $invite, $ip, $asadmin) {
        global $MYSQLIOBJ;
        $pwd = getRND(4);
		$derivedName = genNameFromEmail($invite);

        //if (!isAdmin($uid)) {
        	$asadmin = 0;
        //}

		// insert new session
    	$query = "INSERT INTO `user` (name, pwd, email, registered, invited, admin)
                 VALUES ('".$derivedName."','".md5($pwd)."','".$invite."','".date("U")."','".$uid."','".$asadmin."')";
       	//printf($query."<BR>");
    	$result = mysqli_query($MYSQLIOBJ, $query);

    	if ($result) {
	    	sendEmailRegister($uid, $invite, $pwd, $ip);
    	}
  }

  // ===========================================================================
  // ==                          E M A I L S                                  ==
  // ===========================================================================


 // replaces all found seach with replace strings
 // if replace is empty it uses removebracelets and checks if it can remove
 // blocks ... NEED to cleanupbracelets after all replaces are done

 function ReplaceTextEx($inText,
						 $Search,
						 $Replace,
						 $firstonly) {
		$backText = "";
		$lock = false;

		for ($i = 0; $i < strlen($inText); $i++) {
			 $Zeichen = substr($inText,$i,1);
			 $VGLCMD  = substr($inText,$i,strlen($Search));
			 if ((!$lock)&&($VGLCMD == $Search)) {
			 		$backText .= $Replace;
					$i += strlen($Search) -1;
					if ($firstonly) {
						$lock = true;
					}
			 }
			 else
				$backText .= $Zeichen;
		}//next i

        return $backText;
 }

 //--------------------------------------------------------------

 function ReplaceText($inText,
						 $Search,
					     $Replace) {
	   return (ReplaceTextEx($inText,$Search,$Replace,false));
 }

 //--------------------------------------------------------------

 //returns a string with the bodytext of a template mail
 function readmail($filename) {
     $backText = "";

     if ((file_exists($filename))) {
	     $handle = fopen($filename,"r");
	     while(!feof($handle)) {
	        $line = rtrim(fgets($handle, 1000));
	        //$line = fgets($handle, 1000)." ";
	        //$line = "xxx";
	        //printf($lnum.":".$line."<br>");
	        $backText = $backText.$line."\n";
	     }//while
	     fclose($handle);
     }

     return $backText;
    }//end function

 //--------------------------------------------------------------

  function sendEmailRegister($uid, $invite, $pwd, $ip) {
    global $CONST_EMAILHEADER;
    global $EMAILSUBJECT_REGISTER;
    global $BASEURL;

    //$email = getEmail($uid);
    $inviter = getName($uid);
    $email = $invite;
    $username = genNameFromEmail($email);
    $backvalue = false;

    if ($email) {
	    $EMAILSUBJECT = $EMAILSUBJECT_REGISTER;
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%EMAIL",$email);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%USERNAME",$username);
	    $body = readmail("mail_register.txt");
	    $body = ReplaceText($body,"%INVITER",$inviter);
	    $body = ReplaceText($body,"%USERNAME",$username);
	    $body = ReplaceText($body,"%EMAIL",$email);
	    $body = ReplaceText($body,"%PWD",$pwd);
	    $body = ReplaceText($body,"%IPADDRESS",$ip);
	    $body = ReplaceText($body,"%BASEURL",$BASEURL);
	    //printf($body);
	    $headers .= $CONST_EMAILHEADER;
	    //error_reporting (E_ERROR);
	    error_reporting (E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
	    $backvalue =  mail($email, $EMAILSUBJECT, $body,$headers);
	    error_reporting (E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
    }
    return $backvalue;
  }


 //--------------------------------------------------------------

  function sendEmailInvitation($uid, $mid) {
    global $CONST_EMAILHEADER;
    global $EMAILSUBJECT_INVITATION;
    global $BASEURL;

    $email = getEmail($uid);
    if ($email == "") {
    		return;
    }
    $username = getName($uid);
    $venue = getMeetingLocationNameEmail($mid);
    $address = getMeetingLocationAddress($mid);
    $addressurl = urlencode($address);
    $date = date('D, d.m.Y', getMeetingDate($mid));
    $whoisthere = whoIsThereEmail($mid);
	$count = whoIsThereCount($mid);
	$confirmurl = createQuickConfirmLink($uid);

    $backvalue = false;

    if ($email) {
	    $EMAILSUBJECT = $EMAILSUBJECT_INVITATION;
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%EMAIL",$email);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%USERNAME",$username);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%VENUE",$venue);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%DATE",$date);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%COUNT",$count);
	    $body = readmail("mail_invitation.txt");
	    $body = ReplaceText($body,"%USERNAME",$username);
	    $body = ReplaceText($body,"%EMAIL",$email);
	    $body = ReplaceText($body,"%DATE",$date);
	    $body = ReplaceText($body,"%VENUE",$venue);
	    $body = ReplaceText($body,"%ADDRESSURL",$addressurl);
	    $body = ReplaceText($body,"%ADDRESS",$address);
	    $body = ReplaceText($body,"%WHOISTHERE",$whoisthere);
	    $body = ReplaceText($body,"%IPADDRESS",$ip);
	    $body = ReplaceText($body,"%BASEURL",$BASEURL);
	    $body = ReplaceText($body,"%COUNT",$count);
	    $body = ReplaceText($body,"%CONFIRMURL",$confirmurl);
	    printf($body);
	    $headers .= $CONST_EMAILHEADER;
	    //error_reporting (E_ERROR);
	    error_reporting (E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
	    $backvalue =  mail($email, $EMAILSUBJECT, $body,$headers);
	    error_reporting (E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
    }
    return $backvalue;
  }


 //--------------------------------------------------------------


  function sendEmailReminder($uid, $mid) {
    global $CONST_EMAILHEADER;
    global $EMAILSUBJECT_REMINDER;
    global $BASEURL;

    $email = getEmail($uid);
    if ($email == "") {
    		return;
    }
    $username = getName($uid);
    $venue = getMeetingLocationNameEmail($mid);
    $address = getMeetingLocationAddress($mid);
    $addressurl = urlencode($address);
    $date = date('D, d.m.Y', getMeetingDate($mid));
    $whoisthere = whoIsThereEmail($mid);
    // Is this the person who wanted to reserve the table?
	$phone = confirmedWithPhone($uid, $mid);
	$count = whoIsThereCount($mid);
	$confirmurl = createQuickConfirmLink($uid);

    $backvalue = false;

    if ($email) {
	    $EMAILSUBJECT = $EMAILSUBJECT_REMINDER;
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%EMAIL",$email);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%USERNAME",$username);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%VENUE",$venue);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%DATE",$date);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%COUNT",$count);
	    if (!$phone) {
		    $body = readmail("mail_reminder.txt");
	    } else {
		    $body = readmail("mail_reminder_phone.txt");
	    }
	    $body = ReplaceText($body,"%USERNAME",$username);
	    $body = ReplaceText($body,"%EMAIL",$email);
	    $body = ReplaceText($body,"%DATE",$date);
	    $body = ReplaceText($body,"%VENUE",$venue);
	    $body = ReplaceText($body,"%ADDRESSURL",$addressurl);
	    $body = ReplaceText($body,"%ADDRESS",$address);
	    $body = ReplaceText($body,"%WHOISTHERE",$whoisthere);
	    $body = ReplaceText($body,"%IPADDRESS",$ip);
	    $body = ReplaceText($body,"%BASEURL",$BASEURL);
	    $body = ReplaceText($body,"%COUNT",$count);
	    $body = ReplaceText($body,"%CONFIRMURL",$confirmurl);
	    printf($body);
	    $headers .= $CONST_EMAILHEADER;
	    //error_reporting (E_ERROR);
	    error_reporting (E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
	    $backvalue =  mail($email, $EMAILSUBJECT, $body,$headers);
	    error_reporting (E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
    }
    return $backvalue;
  }


 //--------------------------------------------------------------

  function sendEmailChangeVenue($uid, $mid, $reason) {
    global $CONST_EMAILHEADER;
    global $EMAILSUBJECT_CHANGEDVENUE;
    global $BASEURL;

    $email = getEmail($uid);
    //$email = "delphino@gmx.de"; // REMOVETHIS
    if ($email == "") {
    		return;
    }
    $username = getName($uid);
    $venue = getMeetingLocationNameEmail($mid);
    $address = getMeetingLocationAddress($mid);
    $addressurl = urlencode($address);
    //$addressurl = htmlentities($address);
    $date = date('D, d.m.Y', getMeetingDate($mid));
    $whoisthere = whoIsThereEmail($mid);
	$count = whoIsThereCount($mid);
	$confirmurl = createQuickConfirmLink($uid);

    $backvalue = false;

    if ($email) {
	    $EMAILSUBJECT = $EMAILSUBJECT_CHANGEDVENUE;
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%EMAIL",$email);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%USERNAME",$username);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%VENUE",$venue);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%DATE",$date);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%COUNT",$count);
	    $body = readmail("mail_changedvenue.txt");
	    $body = ReplaceText($body,"%USERNAME",$username);
	    $body = ReplaceText($body,"%EMAIL",$email);
	    $body = ReplaceText($body,"%DATE",$date);
	    $body = ReplaceText($body,"%VENUE",$venue);
	    $body = ReplaceText($body,"%ADDRESSURL",$addressurl);
	    $body = ReplaceText($body,"%ADDRESS",$address);
	    $body = ReplaceText($body,"%WHOISTHERE",$whoisthere);
	    $body = ReplaceText($body,"%IPADDRESS",$ip);
	    $body = ReplaceText($body,"%BASEURL",$BASEURL);
	    $body = ReplaceText($body,"%COUNT",$count);
	    $body = ReplaceText($body,"%REASON",$reason);
	    $body = ReplaceText($body,"%CONFIRMURL",$confirmurl);
	    //printf("<BR><BR>".$body."<BR>");
	    $headers .= $CONST_EMAILHEADER;
	    //error_reporting (E_ERROR);
	    error_reporting (E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
	    $backvalue = mail($email, $EMAILSUBJECT, $body,$headers);
	    error_reporting (E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
    }
    return $backvalue;
  }


 //--------------------------------------------------------------

 function sendMessage($broadcastmessage, $senderuid) {
 		   global $mid;
		   global $MYSQLIOBJ;
 			// Go thru all users and send them the invitation
 		   $query = "SELECT uid FROM user";
    		   //printf($query."<BR>");
    	 	   $result = mysqli_query($MYSQLIOBJ, $query);
    		   if ($result) {
 				while($row_sections = mysqli_fetch_array($result)) {
 		    	  	$uid = $row_sections['uid'];
 		    	  	//printf("<BR>Sending changed venue mail to uid=:".$uid);
 		    	  	sendMessageMail($uid, $mid, $broadcastmessage, $senderuid);
 		    	  	//return; // REMOVETHIS
 				}
 	 	   }
 }


  function sendMessageMail($uid, $mid, $broadcastmessage, $senderuid) {
    global $CONST_EMAILHEADER;
    global $EMAILSUBJECT_MESSAGE;
    global $BASEURL;

    $email = getEmail($uid);
    //$email = "delphino@gmx.de"; // REMOVETHIS
    if ($email == "") {
    		return;
    }
    $username = getName($uid);
    $sender = getName($senderuid);
    $venue = getMeetingLocationNameEmail($mid);
    $address = getMeetingLocationAddress($mid);
    $addressurl = urlencode($address);
    //$addressurl = htmlentities($address);
    $date = date('D, d.m.Y', getMeetingDate($mid));
    $whoisthere = whoIsThereEmail($mid);
	$count = whoIsThereCount($mid);
	$confirmurl = createQuickConfirmLink($uid);

    $backvalue = false;

    if ($email) {
	    $EMAILSUBJECT = $EMAILSUBJECT_MESSAGE;
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%EMAIL",$email);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%USERNAME",$username);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%SENDER",$sender);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%VENUE",$venue);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%DATE",$date);
	    $EMAILSUBJECT = ReplaceText($EMAILSUBJECT,"%COUNT",$count);
	    $body = readmail("mail_message.txt");
	    $body = ReplaceText($body,"%USERNAME",$username);
	    $body = ReplaceText($body,"%SENDER",$sender);
	    $body = ReplaceText($body,"%MESSAGE",$broadcastmessage);
	    $body = ReplaceText($body,"%EMAIL",$email);
	    $body = ReplaceText($body,"%DATE",$date);
	    $body = ReplaceText($body,"%VENUE",$venue);
	    $body = ReplaceText($body,"%ADDRESSURL",$addressurl);
	    $body = ReplaceText($body,"%ADDRESS",$address);
	    $body = ReplaceText($body,"%WHOISTHERE",$whoisthere);
	    $body = ReplaceText($body,"%IPADDRESS",$ip);
	    $body = ReplaceText($body,"%BASEURL",$BASEURL);
	    $body = ReplaceText($body,"%COUNT",$count);
	    $body = ReplaceText($body,"%REASON",$reason);
	    $body = ReplaceText($body,"%CONFIRMURL",$confirmurl);
	    //printf("<BR><BR>".$body."<BR>");
	    $headers .= $CONST_EMAILHEADER;
	    //error_reporting (E_ERROR);
	    error_reporting (E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
	    $backvalue = mail($email, $EMAILSUBJECT, $body,$headers);
	    error_reporting (E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
    }
    return $backvalue;
  }



 // ===========================================================================
 // ==                        S U G G E S T I O N S                          ==
 // ===========================================================================

 function suggest($uid, $sname, $saddress, $smodify) {
 	    global $MYSQLIOBJ; 
		if ($smodify != "" && isAdmin($uid)) {
		   $query = "UPDATE suggestion SET name = '".$sname."' WHERE sid = '".$smodify."'";
		   //printf($query);
   	       $result = mysqli_query($MYSQLIOBJ, $query);
		   $query = "UPDATE suggestion SET address = '".$saddress."' WHERE sid = '".$smodify."'";
		   //printf($query);
   	       $result = mysqli_query($MYSQLIOBJ, $query);
 	    } else {
	    	$query = "INSERT INTO `suggestion` (name, address, uid, suggested)
	                 VALUES ('".$sname."','".$saddress."','".$uid."','".date("U")."')";
	       	//printf($query."<BR>");
	    	$result = mysqli_query($MYSQLIOBJ, $query);
 	    }
 }


 function removevote($uid, $sid) {
    global $MYSQLIOBJ; 
    if (isAdmin($uid)) {
	    $query = "DELETE FROM `suggestion` WHERE sid = '$sid'";
	    //printf($query."<BR>");
	    $result = mysqli_query($MYSQLIOBJ, $query);

	    $query = "DELETE FROM `vote` WHERE sid = '$sid'";
	    //printf($query."<BR>");
	    $result = mysqli_query($MYSQLIOBJ, $query);
    }
 }


 function vote($uid, $sid, $vote) {
    global $MYSQLIOBJ; 
    $uid = intval($uid);
    $sid = intval($sid);

    $query = "DELETE FROM `vote` WHERE uid = '$uid' and sid = '$sid'";
    //printf($query."<BR>");
    $result = mysqli_query($MYSQLIOBJ, $query);

    if ($vote) {
    	$query = "INSERT INTO `vote` (sid, uid, voted)
                 VALUES ('".$sid."','".$uid."','".date("U")."')";
       	//printf($query."<BR>");
    	$result = mysqli_query($MYSQLIOBJ, $query);
    }
 }

 function voted($uid, $sid) {
   global $MYSQLIOBJ; 
   $uid = intval($uid);
   $sid = intval($sid);
   $result = mysqli_query($MYSQLIOBJ, "SELECT uid FROM vote WHERE sid = '".$sid."' and uid = '".$uid."'");
   if (mysqli_num_rows($result) > 0) {
       return true;
   }
   return false;
 }


 function chooseTopSuggestion() {
   global $MYSQLIOBJ; 
   $query = "SELECT suggestion.sid, COUNT(vote.sid) AS votes FROM suggestion LEFT JOIN vote ON suggestion.sid = vote.sid WHERE chosen < '1' GROUP BY suggestion.sid ORDER BY votes DESC, suggested ASC ";
   //printf($query."<BR>");
   $result = mysqli_query($MYSQLIOBJ, $query);
   if (mysqli_num_rows($result) > 0) {
       $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
       $e = implode(" ",$row);
       $f = explode(" ",$e);
       $backvalue = $f[0];
   }
   return $backvalue;
 }


 // Required to get backup venue suggestion id
 function getSidFromIndex($index) {
   global $MYSQLIOBJ; 
   $query = "SELECT suggestion.sid, COUNT(vote.sid) AS votes FROM suggestion LEFT JOIN vote ON suggestion.sid = vote.sid WHERE chosen < '1' GROUP BY suggestion.sid ORDER BY votes DESC, suggested ASC ";
   //printf($query."<BR>");
   $sid = "-1";
   $result = mysqli_query($MYSQLIOBJ, $query);
   $records = mysqli_num_rows($result);
   if ($records > 0) {
	 while($row_sections = mysqli_fetch_array($result)) {
	     $index = $index - 1;
	     if ($index <= 0) {
			$sid = $row_sections['sid'];
	     	return $sid;
	     }
	 }
   }
   return $sid;
 }


 function changeVenue($mid, $oldsid, $newsid) {
 	  global $MYSQLIOBJ; 
		// revert the old suggestion to that it has not been consumed
	  $query = "UPDATE suggestion SET chosen = '' WHERE sid = '".$oldsid."'";
		     //printf($query);
	   	     $result = mysqli_query($MYSQLIOBJ, $query);
 		     // insert the sid
		     $query = "UPDATE meeting SET sid = '".$newsid."' WHERE mid = '".$mid."'";
		     //printf($query);
	   	     $result = mysqli_query($MYSQLIOBJ, $query);
 		     // tell the suggestion that it has been "consumed" now
		     $query = "UPDATE suggestion SET chosen = '".$mid."' WHERE sid = '".$newsid."'";
		     //printf($query);
	   	     $result = mysqli_query($MYSQLIOBJ, $query);
 }



 function buildSuggestions($uid, $s) {
   global $MYSQLIOBJ;
   $query = "SELECT suggestion.sid, name, address, COUNT(vote.sid) AS votes FROM suggestion LEFT JOIN vote ON suggestion.sid = vote.sid WHERE chosen < '1' GROUP BY suggestion.sid ORDER BY votes DESC, suggested ASC ";
   //printf($query."<BR>");


   $result = mysqli_query($MYSQLIOBJ, $query);
   $records = mysqli_num_rows($result);
   if ($records > 0) {
 	 printf("<table border='0' cellspacing='100'>");
	 $i = 0;
	 while($row_sections = mysqli_fetch_array($result)) {
	     $i = $i + 1;
	     $sid = $row_sections['sid'];
	     $sname = $row_sections['name'];
	     $saddress = $row_sections['address'];
	     $votes = $row_sections['votes'];
	     $voted = voted($uid, $sid);


	     printf("<tr>");
	     printf("<td valign='top'>");
 		 printf("<h4><a href='http://www.google.de/search?q=".$sname." ".$saddress."' target='_blank'>".$i.".&nbsp;".$sname." (".$votes.")"."</a>&nbsp;&nbsp;</h4>");
	     printf("</td><td>&nbsp;&nbsp;&nbsp;</td><td>");

		 printf("<form  method='post'>");
		 	 printf("<input type='hidden'  name='s' value='".$s."'>");
		 	 printf("<input type='hidden'  name='sid' value='".$sid."'>");
	     if ($voted) {
 		 	 printf("<input type='hidden'  name='cmd' value='unvote'>");
	 		 printf("<button type='submit' class='btn btn-default'>Unvote</button>");
	     } else {
 		 	 printf("<input type='hidden'  name='cmd' value='vote'>");
	 		 printf("<button type='submit' class='btn btn-default'>Vote</button>");
	     }
		 printf("</form>");

	     if (isAdminView($uid)) {
		     printf("</td><td>&nbsp;&nbsp;</td><td>");
			 printf("<form  method='post'>");
		 	 printf("<input type='hidden'  name='s' value='".$s."'>");
		 	 printf("<input type='hidden'  name='sid' value='".$sid."'>");
 		 	 printf("<input type='hidden'  name='cmd' value='removevote'>");
	 		 printf("<button type='submit' class='btn btn-danger'>Remove</button>");
			 printf("</form>");
		     printf("</td><td>&nbsp;&nbsp;</td><td>");
	 		 printf("<button type='submit' class='btn btn-danger'
	 		 onClick=\"document.getElementById('smodify').value='".$sid."';
	 		 	document.getElementById('sname').value='".$sname."';
	 		 	document.getElementById('saddress').value='".$saddress."';
	 		 	showIt('suggest', null); return false;\">Edit</button>");
	     }

	     printf("</td>");
	     printf("</tr>");
	 }
     printf("</table><BR>");
   }

 }


 // ===========================================================================



 $connection = opendb();
 $db = 0;
 if ($connection)  {
    $db =  mysqli_select_db($connection, $DBNAME);
 } else {
   	  echo '-CANNOT ACCESS DATABASE (1), PLEASE CONTACT THE SYSTEM ADMINISTARTOR AT '.$WEBMASTER;
   	  exit;
 }
 if(!$db) {
   	  echo '-CANNOT ACCESS DATABASE (2), PLEASE CONTACT THE SYSTEM ADMINISTARTOR AT '.$WEBMASTER;
   	  exit;
 }

  $IP = $_SERVER['REMOTE_ADDR'];
  $browser = $_SERVER['HTTP_USER_AGENT'];
  //$IP = "123.123.123.2";
  //$browser = "1234";

  //print($IP);
  //print("<BR>".$browser."<br>");

  $cmd = $_GET['cmd'];
  $postCmd = $_POST['cmd'];
  if ($postCmd != "") {
   	  $cmd = $postCmd;
  }
  $pwd = $_GET['pwd'];
  $postPwd = $_POST['pwd'];
  if ($postPwd != "") {
   	  $pwd = $postPwd;
  }


  $opwd = $_GET['opwd'];
  $postOPwd = $_POST['opwd'];
  if ($postOPwd != "") {
   	  $opwd = $postOPwd;
  }

  $npwd = $_GET['npwd'];
  $postNPwd = $_POST['npwd'];
  if ($postNPwd != "") {
   	  $npwd = $postNPwd;
  }

  $npwd2 = $_GET['npwd2'];
  $postNPwd2 = $_POST['npwd2'];
  if ($postNPwd2 != "") {
   	  $npwd2 = $postNPwd2;
  }

  $nemail = $_GET['nemail'];
  $postNEmail = $_POST['nemail'];
  if ($postNEmail != "") {
   	  $nemail = $postNEmail;
  }

  $nemail2 = $_GET['nemail2'];
  $postNEmail2 = $_POST['nemail2'];
  if ($postNEmail2 != "") {
   	  $nemail2 = $postNEmail2;
  }


  $user = $_GET['user'];
  $postUser = $_POST['user'];
  if ($postUser != "") {
   	  $user = $postUser;
  }

  $nuser = $_GET['nuser'];
  $postNUser = $_POST['nuser'];
  if ($postNUser != "") {
   	  $nuser = $postNUser;
  }

  $invite = $_GET['invite'];
  $postInvite = $_POST['invite'];
  if ($postInvite != "") {
   	  $invite = $postInvite;
  }

  $email = $_GET['email'];
  $val = $_GET['val'];
  $postVal = $_POST['val'];
  if ($postVal != "") {
   	  $val = $postVal;
  }
  $host = $_GET['host'];
  $postHost = $_POST['host'];
  if ($postHost != "") {
   	  $host = $postHost;
  }
  $session = $_GET['session'];
  $postSession = $_POST['session'];
  if ($postSession != "") {
   	  $session = $postSession;
  }
  $s = $_GET['s'];
  $postS = $_POST['s'];
  if ($postS != "") {
   	  $s = $postS;
  }

  $sname = $_GET['sname'];
  $postSname = $_POST['sname'];
  if ($postSname != "") {
   	  $sname = $postSname;
  }

  $saddress = $_GET['saddress'];
  $postAddress = $_POST['saddress'];
  if ($postAddress != "") {
   	  $saddress = $postAddress;
  }

  $smodify = $_GET['smodify'];
  $postSmodify = $_POST['smodify'];
  if ($postSmodify != "") {
   	  $smodify = $postSmodify;
  }


  $sid = $_GET['sid'];
  $postSid = $_POST['sid'];
  if ($postSid != "") {
   	  $sid = $postSid;
  }

  $phone = $_GET['phone'];
  $postPhone = $_POST['phone'];
  if ($postPhone != "") {
   	  $phone = $postPhone;
  }

  $comment = $_GET['comment'];
  $postComment = $_POST['comment'];
  if ($postComment != "") {
   	  $comment = $postComment;
  }

  $adminview = $_GET['adminview'];
  $postAdminview = $_POST['adminview'];
  if ($postAdminview != "") {
   	  $adminview = $postAdminview;
  }

  $asadmin = $_GET['asadmin'];
  $postAsadmin = $_POST['asadmin'];
  if ($postAsadmin != "") {
   	  $asadmin = $postAsadmin;
  }


  $reason = $_GET['reason'];
  $postReason = $_POST['reason'];
  if ($postReason != "") {
   	  $reason = $postReason;
  }

  $backupvenue = $_GET['backupvenue'];
  $postBackupvenue = $_POST['backupvenue'];
  if ($postBackupvenue != "") {
   	  $backupvenue = $postBackupvenue;
  }

  $backupvenunumber = $_GET['backupvenunumber'];
  $postBackupvenunumber = $_POST['backupvenunumber'];
  if ($postBackupvenunumber != "") {
   	  $backupvenunumber = $postBackupvenunumber;
  }

  $backupdate = $_GET['backupdate'];
  $postBackupdate = $_POST['backupdate'];
  if ($postBackupdate != "") {
   	  $backupdate = $postBackupdate;
  }


  $photomid = $_GET['photomid'];

  $uploadmid = $_POST['uploadmid'];

  $rating = $_POST['rating'];
  $ratemid = $_POST['ratemid'];

  $photofile = $_POST['photofile'];

  $adminchoose = $_POST['adminchoose'];
  $admininvite = $_POST['admininvite'];

  $otheruid = $_POST['otheruid'];
  $broadcastmessage = $_POST['broadcastmessage'];

?>
