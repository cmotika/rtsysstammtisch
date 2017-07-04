<?php
   // call this method with will lead to failure
   if (!requireIndex()) {
	   printf(' <meta http-equiv=REFRESH content="0; URL='.$BASEURL.'">');
	   exit;
   }

 // check if we are loggedin
 if ($tmpuid <= 0) {
   //printf("<head><META HTTP-EQUIV='refresh' CONTENT='1'></head>");
   printf(' <meta http-equiv=REFRESH content="1; URL='.$BASEURL.'">');
   //printf("<head><script>location.reload(true);</script></head>");
   exit;
 }
 $uid = $tmpuid;
 updateLastSeen($uid);
 $user = getName($uid);

// printf("session: ".$s."<BR>");
// printf("user: ".$user."<BR>");
// printf("uid: ".$uid."<BR>");


 if ($cmd == "change") {
	 if ($npwd != "" || $opwd != "") {
	  	 $opwd = md5($opwd);
	     //printf("opwd:".$opwd."<br>");
	     //printf("getPwd:".getPwd($uid)."<br>");
	 	 if (($opwd != getPwd($uid)) && getPwd($uid) != "") {
			   $message = "Old password is incorrect";
			   $errormsg = true;
		 }
	     else if (strlen(trim($npwd)) < 4) {
			   $message = "Password must be at least 4 characters";
			   $errormsg = true;
		 }
	     else if ($npwd == $npwd2) {
			 updatePwd($uid, $npwd);
		     $message = "Password changed";
		 } else {
			 $message = "New passwords do not match";
			  $errormsg = true;
		 }
	 }
	 
	 if ($nemail != "") {
    	if (!emailok($nemail)) {
 			$message = "Email not valid";
	  	    $errormsg = true;
		} else if ($nemail != $nemail2) {
 			$message = "New email adresses do not match";
	  	    $errormsg = true;
		} else {
			// change email
 			$message = "Email changed to '".$nemail."'";
  		 	updateEmail($uid, $nemail);
		}
	}
	 
	 // check if new username is not taken and not empty
	 $nuser = clean($nuser);
     $nuser = str_replace ( "deleted" , "", $nuser );
	 if (strlen(trim($nuser)) < 3) {
	     $message = "Username must have at least 3 characters";
  	     $errormsg = true;
	 } else {
		 $vgluid = getUID($nuser);
		 if ($vgluid != $uid && $vgluid > 0) {
			 //printf("vgluid: ".$vgluid." of ".$nuser."<BR>");
			 $message = "Username is already taken";
  	    	 $errormsg = true;
		 } else if ($vgluid == -1) {
			 updateName($uid, $nuser);
			 $user = $nuser;
		     $message = "Username changed to '".$nuser."'";
	  	     $errormsg = false;
		 }
	 }
 } // change


 if ($cmd == "invite") {
    if (!emailok($invite)) {
 		$message = "Email not valid";
  	    $errormsg = true;
	}
    else if (getUIDByMail($invite) > 0) {
 		$message = "User already a member or invited";
  	    $errormsg = true;
	} else {
		inviteNewUser($uid, $invite, $ip, $asadmin);
	 	$message = "Invitation sent to ".$invite."";
	}
 }


 if ($cmd == "suggest") {
    $sname = clean2($sname);
	$saddress = clean2($saddress);
    if (strlen(trim($sname)) < 1) {
	   $message = "Enter a restaurant name";
	   $errormsg = true;
	} else if (strlen(trim($saddress)) < 3) {
	   $message = "Enter a restaurant address";
	   $errormsg = true;
	} else {
	   $message = "Suggestion saved. Thank you!";
	   suggest($uid, $sname, $saddress, $smodify);
	   $sname = "";
	   $saddress = "";
	}
 }

 if ($cmd == "vote") {
     vote($uid, $sid, true);
 }
 if ($cmd == "unvote") {
     vote($uid, $sid, false);
 }

 if ($cmd == "removevote") {
    removevote($uid, $sid);
 }


 if (($cmd == "cancel" || $cmd == "confirm") && !allowToConfirm($mid, $uid)) {
    // this should not happen if mid is always the current event today or in the future but
	 // we still guard against this case
     $message = "FORBIDDEN";
     $errormsg = true;
 }
 else if ($cmd == "cancel") {
//if ($cmd == "cancel") {
     $message = "We hope you make it next time!";
     $errormsg = true;
     confirm($uid, $mid, false, 0, "");
 }
 else if ($cmd == "confirm") {
     $message = "Great you are joining!";
	 if ($phone == "1") {
		$message = "Great you are joining! Thank you for reserving the table!";
	 }
     confirm($uid, $mid, true, $phone, $comment);
 }
 
 
 if ($cmd == "resetpwd") {
    if (resetPwd($uid, $nuser)) {
		$message = "Password for '".$nuser."' reset to EMPTY";
	} else {
		$message = "Error resetting password for '".$nuser."'";
		$errormsg = true;
	}
 }
 
 if ($cmd == "backupvenue") {
 	if (confirmedWithPhone($uid, $mid) && (getMeetingLocationName($mid) != "")) {
	    $newsid = getSidFromIndex($backupvenue);
		if ($newsid < 0) {
			$message = "The selected venue does not exist.";
			$errormsg = true;
		}
		else if (strlen(trim($reason)) < 5) {
			$message = "The given reason is too short!";
			$errormsg = true;
		}
		else {
			$sid = getMeetingLocationSid($mid);
			//printf("oldsid:".$sid."<br>");
			//printf("newsid:".$newsid."<br>");
			//printf("mid:".$mid."<br>");
			// change the venue
			changeVenue($mid, $sid, $newsid);
			
			// send emails to everybody
			changeVenueEmail(trim($reason));
			
			$message = "Venue change to ".getMeetingLocationName($mid);
			$errormsg = false;

			// update location strings
	    	printf(' <meta http-equiv=REFRESH content="5; URL='.$BASEURL.'">');
		}
	} else {
		$message = "You are not allowed to change the venue";
		$errormsg = true;
	}
 }
 
 
 if ($cmd == "backupdate") {
    if (!isAdmin($uid)) {
		$message = "You are not allowed to change the date";
		$errormsg = true;
	} else if (strtotime($backupdate) <= date("U")) {
		$message = "The new date must be in the future!";
		$errormsg = true;
	} else {
	   $newdate = strtotime($backupdate);
	   updateMeetingDate($mid, $newdate);
	   $message = "Meeting date changed! Consider informing the other!";
	   $errormsg = false;
	}
 }

 if($cmd == "adminchoose") {
    if (!isAdmin($uid)) {
		$message = "FORBIDDEN";
		$errormsg = true;
	} else {
 	choose(true);
	   $message = "Venue choosen!";
	   $errormsg = false;
	}
 }
 if($cmd == "admininvite") {
    if (!isAdmin($uid)) {
		$message = "FORBIDDEN";
		$errormsg = true;
	} else {
	   invite(true);
	   $message = "Invitations sent!";
	   $errormsg = false;
	}
 }
 
 if ($cmd == "admincancel") {
    if (!isAdmin($uid)) {
		$message = "FORBIDDEN";
		$errormsg = true;
	} else {
	   confirm($otheruid, $mid, false, 0, "");
	   $message = "User canceled";
	   $errormsg = false;
	}
 }



 if ($cmd == "message") {
//    if (!isAdmin($uid)) {
//		$message = "FORBIDDEN";
//		$errormsg = true;
//	} else if (strlen(trim($broadcastmessage)) < 5) {
	if (strlen(trim($broadcastmessage)) < 5) {
		$message = "Message '".$broadcastmessage."' too short";
		$errormsg = true;
	} else {
	   sendMessage($broadcastmessage, $uid);
	   $message = "Emails sent to everyone";
	   $errormsg = false;
	}
 }
 
 if ($cmd == "rate") {
    if (!allowedToRate($ratemid, $uid)) {
	    $message = "You did not attend and cannot rate";
 	    $errormsg = true;
	} else {
	    rate($ratemid, $uid, $rating);
		if ($rating > -1) {
		    $message = "Thanks for rating!";
 		    $errormsg = false;
		} else {
		    $message = "Rating removed";
 		    $errormsg = false;
		}
	}
 }
 
  if ($cmd == "photo") {
		outputPhoto($photomid);
		exit;
  }
 
 if ($cmd == "upload") {
//    printf($HTTP_POST_FILES['photofile']['tmp_name']);
//	printf($photofile_type);
//    printf($_FILES['photofile']['tmp_name']);
//	exit;

 
 	//$uploaddir = 'X:/xampp/htdocs/stammtisch/photos';
	$uploaddir = dirname(__FILE__) . "/photos";
	if (!file_exists($uploaddir)) {
		mkdir($uploaddir, 0700);
	}
	$uploadfile = $uploaddir."/".$uploadmid.".jpg";
	$filetype = $_FILES['photofile']['type'];
	
	if (file_exists($uploadfile) && !isAdmin($uid)) {
  		$message = "Photo already exists";
 	    $errormsg = true;
	}
    else if ($_FILES['photofile']['name'] == "") {
	    if (!isAdmin($uid)) {
	    	unlink($uploadfile);
    		$message = "FORBIDDEN";
	 	    $errormsg = true;
		} else {
	    	unlink($uploadfile);
    		$message = "Photo deleted";
 	    	$errormsg = false;
		}
	}
	else if (($filetype != "image/jpeg")&&($filetype != "image/pjpeg")) {
    	$message = "Photo must be a JPEG";
 	    $errormsg = true;
	}
	else if (move_uploaded_file($_FILES['photofile']['tmp_name'], $uploadfile)) {
    	$message = "Photo uploaded";
	    $errormsg = false;
	} else {
    	$message = "Photo not uploaded. Too large? (max 2MB)";
 	    $errormsg = true;
	}
 
 }
 

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="favicon.png">
    <meta http-equiv="expires" content="0">
	
     <?php 
	 if (($cmd == "login") && ($_GET['pwd'] != "") && (isset($_COOKIE[$cookie_name]))) { 
		 // If quick login (GET request) and cookies accepted, then hide the parameters
		printf(' <meta http-equiv=REFRESH content="0; URL='.$BASEURL.'">');
	 }
	 elseif (($_GET['s'] != "") && (isset($_COOKIE[$cookie_name]))) { 
		// If quick link (GET request) and cookies accepted, then hide the parameters
		printf(' <meta http-equiv=REFRESH content="0; URL='.$BASEURL.'">');
	 }
	?>

    <title>RtSys Stammtisch - Est. 2016</title>

    <!-- Bootstrap core CSS -->
    <link href="bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="bootstrap/assets/css/ie10-viewport-bug-workaround.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="jumbotron.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="bootstrap/assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="bootstrap/assets/js/ie-emulation-modes-warning.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

	<script type="text/javascript">
	function showIt(id, btn) {
    	document.getElementById(id).style.display = 'block';
	    // hide the lorem ipsum text
    	//document.getElementById(text).style.display = 'none';
	    // hide the link
    	btn.style.display = 'none';
	}

	function showMessage() {
		document.getElementById("mylogoutbutton").style.display = 'none';
		document.getElementById("title").style.display = 'none';
		document.getElementById("message").style.display = 'block';
		setTimeout(function() {
			document.getElementById("title").style.display = 'block';
			document.getElementById("message").style.display = 'none';
			document.getElementById("mylogoutbutton").style.display = 'block';
		}, 5000); // <-- time in milliseconds
	}
</script>

	<style>
	.cXontainer{
    display: flex
 	}
	</style>
	
	
	<style>
	body { padding-top: 50px; }
@media screen and (max-width: 768px) {
    body { padding-top: 50px; }
	
.flexcontainer {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  flex-shrink: 0;
  align-items: left;
  align-self: stretch;
  }
	
}

@grid-float-breakpoint:  @screen-sm-min;

	</style>

  </head>

  <body>

    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container flexcontainer">
      <!-- <div class="container flexcontainer"> -->
		  <span id="title">
	          <a class="navbar-brand" href="#"><?php printf($user);?></a>
		  </span>

		  <span id="message" style="display: none;">
	          <a class="navbar-brand" href="#" style="font-weight: bold;color:#<?php if ($errormsg) { printf("FE8787"); } else {  printf("66FF66");} ?>"><?php printf($message)?></a>
		  </span>
		
		
		   <span id="mylogoutbutton">
		<div class="navbar-right">
		
<!--		
		<div class="col-sm-5 flexcontainer">
          <form method="post">
     		<input type="hidden"  name="cmd" value="refresh">
     		<input type="hidden"  name="s" value="<?php printf($s)?>">
            <button type="submit" class="btn btn-success navbar-btn">Refresh</button>
          </form>
		 </div> 
-->					
					
			
		<div class="col-sm-3 flexcontainer">
          <form method="post">
     		<input type="hidden"  name="cmd" value="logout">
     		<input type="hidden"  name="s" value="<?php printf($s)?>">
<!--            <button type="submit" class="btn btn-danger navbar-btn">Logout</button> -->
            <button type="submit" class="btn btn-success navbar-btn">Logout</button>
          </form>
		 </div> 		
          </div>
		   </span>
		   
      </div>
    </nav>

		  <?php
		  if ($message != "") {
		  	 printf("<script> showMessage(); </script>");
		  }
		  ?>


    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotsron" >
 	  <div class="jumbxotron text-center col-md-4" style="background-color:#EEEEEE">
        <img src="clockman_m.png" alt="RtSysLogo" vspace="20" class="jumbotronwidth">
        <h1>RtSys Stammtisch</h1>
        <h4><p><?php printf( $locationText );?></p><h4>
		
					<?php
			if (isAdminView($uid)) {
				printf('
		            <form class="navbar-form" method="post">
					Reschedule Date: 
            <div class="form-group">
              <input type="text" placeholder="E.g., \'14.4.2016\'" name="backupdate" class="form-control"  value="">
            </div>
			<input type="hidden"  name="cmd" value="backupdate">
     		<input type="hidden"  name="s" value="<?php printf($s)?>">
            <button type="submit" class="btn btn-danger">Reschedule</button>
          </form>
		  <br>
          <form class="navbar-form" method="post">
			<input type="hidden"  name="cmd" value="adminchoose">
     		<input type="hidden"  name="s" value="<?php printf($s)?>">
            <button type="submit" class="btn btn-danger">Choose Venue</button>
          </form>
          <form class="navbar-form" method="post">
			<input type="hidden"  name="cmd" value="admininvite">
     		<input type="hidden"  name="s" value="<?php printf($s)?>">
            <button type="submit" class="btn btn-danger">Send Invitations</button>
          </form>
				');
			}
			?>
	    <br>

		<?php
		if ($locationName != "") {
        	printf("<p><a class='btn btn-primary btn-lg' href='http://maps.google.de/maps?q=".$locationAddress."' target='_blank' role='button'>Google Maps &raquo;</a></p>");
		}
		printf("<p><a class='btn btn-primary btn-lg' href='".$BASEURL."?cmd=ics' role='button'>Save to Calendar &raquo;</a></p><br>");

		
		?>
      </div>
    </div>

    <div class="container">
      <!-- Example row of columns -->
      <div class="row">
        <div class="col-md-4" style="margin-left: 1em;">
          <h3>Who is there <?php printf( $nextMeetingDateShort );?>?</h3>
		  <?php  whoIsThere($mid); ?>
          <p>

		  <?php
		     $cancelCmd = "confirm";
			 $cancelBtn = "Confirm";
		     if(confirmed($uid, $mid)) {
			     $cancelCmd = "cancel";
				 $cancelBtn = "Cancel";
			 }
		  ?>




	      <?php 
 			// Only show this if this is the person who will do the reservation AND
			// if a venue already has been chosen!		  
 		    if (confirmedWithPhone($uid, $mid) && (getMeetingLocationName($mid) != "")) {
					printf('<p><a href="#" onClick="showIt(\'backup\', this); return false;" "style=\"display: none;\">Need a backup venue &raquo;</a></p>');
			}
		  ?> 

		  <table><tr><td>
          <form  method="post">

          <p><a href="#" onClick="showIt('comment', this); return false;"  <?php if(confirmed($uid, $mid)) {printf("style=\"display: none;\"");} ?> >Add comment &raquo;</a></p>
		  <span id="comment" style="display: none;">
            <p><div class="form-group">
              <input type="text" placeholder="Comment, e.g., 'I might be 5 min late'" name="comment" class="form-control"  value="">
            </div></p>
            <p>
			<div class="checkbox">
				  <label><input type="checkbox" name="phone" value="1">I will reserve the table!</label>
			</div></p>
		  </span>
		  
     		<input type="hidden"  name="cmd" value="<?php printf($cancelCmd);?>">
     		<input type="hidden"  name="s" value="<?php printf($s)?>">
			<button type="submit" class="btn btn-default"><?php printf($cancelBtn);?></button>
          </form>
		  </td>
		  
		  <?php 
		  if (whoReservesTableCount($mid) < 1) {
			printf('<td>&nbsp;&nbsp;&nbsp;</td><td valign="bottom">
          <form  method="post">
     		<input type="hidden"  name="cmd" value="confirm">
     		<input type="hidden"  name="phone" value="1">
     		<input type="hidden"  name="s" value="'.$s.'">
			<button type="submit" class="btn btn-success">I\'ll reseve a table</button>
          </form></td>
			');
		  }
		  ?>
		  </tr>
		  </table>

		  </p>
          <br>
		  <span id="backup" style="display: none;">
          <form  method="post">
            <p><div class="form-group">
				<b>Change to Backup Venue</b>
			</div>
			</p>
            <p><div class="form-group">
              <input type="text" placeholder="Backup Venu Number, e.g., '1'" name="backupvenue" class="form-control"  value="">
            </div></p>
            <p><div class="form-group">
              <input type="text" placeholder="Reason why backup venue is required" name="reason" class="form-control"  value="">
            </div></p>
            <p><div class="form-group">
				If you change the venue you should have good reasons for doing so! Such are: The venue has no table, 
				the venue is closed on the desired day, or it has been shut down permanently.<br><br>
				An automatic email will be sent to anyone informing about the location change! 
				As the backup venue number you should choose 1 (the next top-voted venue).
			</div>
			</p>
     		<input type="hidden"  name="cmd" value="backupvenue">
     		<input type="hidden"  name="s" value="<?php printf($s)?>">
			<button type="submit" class="btn btn-default">Change Venue</button>
          </form>
		  <br>
		  </span>		  
		  
       </div>


        <div class="col-md-4" style="margin-left: 1em;">
          <h3>Future Venues</h3>
		  <?php buildSuggestions($uid, $s); ?>


          <p><a class="btn btn-default" href="#" role="button" onClick="showIt('suggest', this); return false;">Suggest venue &raquo;</a></p>
		  <span id="suggest" style="display: none;">
          <h4>Your Venue Suggestion</h4>
          <form  method="post">
			<?php
			if (isAdminView($uid)) {
					printf("<p><div class=\"form-group\"><input name=\"smodify\" id=\"smodify\" type=\"text\" placeholder=\"Modify ID, leave empty for a new entry\"  class=\"form-control\"></div></p>");
			}
			?>
		    <p><div class="form-group">
              <input type="text" placeholder="Name of restaurant or bar, e.g., 'El Paso'" id="sname" name="sname" class="form-control"  value="<?php printf($sname)?>">
            </div></p>
            <p><div class="form-group">
              <input type="text" placeholder="Address, e.g., 'Kleiner Kuhberg 2, 24103 Kiel'" id="saddress" name="saddress" class="form-control"  value="<?php printf($saddress)?>">
            </div></p>
     		<input type="hidden"  name="cmd" value="suggest">
     		<input type="hidden"  name="s" value="<?php printf($s)?>">
            <p><button type="submit" class="btn btn-default">Submit</button></p>
          </form>
		  </span>


        </div>
          <br>
      </div>


      <div class="row">
        <div class="col-md-4" style="margin-left: 1em;">
          <h3>Invite</h3>
          <form method="post">
		  <p><div class="form-group">
              <input type="text" placeholder="Email" name="invite" class="form-control" value="">
           </div></p>
		   
					<?php
			if (isAdminView($uid)) {
			printf('
			<p>
			<div class="checkbox">
				  <label><input type="checkbox" name="asadmin" value="1">Invite user as a new admin</label>
			</div></p> 
			');
			}
			?>	
			
					   
          <p>
		    <input type="hidden"  name="cmd" value="invite">
     		<input type="hidden"  name="s" value="<?php printf($s)?>">
            <p><button type="submit" class="btn btn-default">Send &raquo;</button></p>
          </form>
          <br>
        </div>

        <div class="col-md-3" style="margin-left: 1em;">
          <h3>Account</h3>
          <p><a class="btn btn-default" href="#" role="button" onClick="showIt('changeaccount', this); return false;">Modify &raquo;</a></p>

		  <span id="changeaccount" style="display: none;">
          <form  method="post">
		    <p><div class="form-group">
              Your email: <br><b><?php printf(getEmail($uid))?></b>
            </div></p>
		    <p><div class="form-group">
              Display/Login name:
            </div></p>
		    <p><div class="form-group">
              <input type="text" placeholder="Name" name="nuser" class="form-control" value="<?php printf($user)?>">
            </div></p>
		    <p><div class="form-group">
              Change email:
            </div></p>
		    <p><div class="form-group">
              <input type="text" placeholder="New email" name="nemail" class="form-control" value="">
            </div></p>
		    <p><div class="form-group">
              <input type="text" placeholder="Repeat email" name="nemail2" class="form-control" value="">
            </div></p>
		    <p><div class="form-group">
              Change password:
            </div></p>
            <p><div class="form-group">
              <input type="password" placeholder="Old password" name="opwd" class="form-control">
            </div>
            <p><div class="form-group">
              <input type="password" placeholder="New password" name="npwd" class="form-control">
            </div>
            <div class="form-group">
              <input type="password" placeholder="Repeat password" name="npwd2" class="form-control">
            </div></p>
     		<input type="hidden"  name="cmd" value="change">
     		<input type="hidden"  name="s" value="<?php printf($s)?>">
            <p><button type="submit" class="btn btn-default">Save</button></p>
          </form>
		  
		   <form  method="post">
			<BR><BR>		
		    <p><h4>Remove Account</h4>
			<h6>Your email address will be removed and your name will be replaced by 'deleted'. <br><b>This is permanent and
			cannot be reverted!</b><h6></p>
            <p><div class="form-group">
              <input type="password" placeholder="Type your password to confirm" name="pwd" class="form-control">
            </div>
     		<input type="hidden"  name="cmd" value="removeaccount">
     		<input type="hidden"  name="s" value="<?php printf($s)?>">
            <p><button type="submit" class="btn btn-danger">Delete Account</button></p>
          </form>

		  
		  </span>
       </div>
	   <br>
        <div class="col-md-4" style="margin-left: 1em;">
          <h3>Past Dates</h3>
          <p><?php getPastDates() ?></p>
        </div>
      </div>

<?php
  if(isAdminView($uid)) {
  printf('
	   <br>
		   <form  method="post">
        <div class="col-md-4" style="margin-left: 0em;">
          <h3>Reset Password</h3>
			<h6>Reset the password of the following user. The user should login <b>immediately</b> and set a new
			password. The default password will be EMPTY (=no password)!<h6></p>
            <p><div class="form-group">
                 <input type="text" placeholder="Username" name="nuser" class="form-control">
            </div>
     		<input type="hidden"  name="cmd" value="resetpwd">
     		<input type="hidden"  name="s" value="'.$s.'">
            <p><button type="submit" class="btn btn-danger">Reset Password</button></p>
        </div>
          </form>
  ');
  }
?>

	  <?php
//  if(isAdminView($uid)) {
	 		 printf("<span id=\"sendmessage\" style=\"display: none;\">");
 printf('
		   <form  method="post">
        <div class="col-md-4" style="margin-left: 0em;">
          <h3>Broadcast Message</h3>
			<h6>In special situations it may be required to inform all members by email. <b>Please do not use abuse this feature!</b><h6>
            <p><div class="form-group">
                 <textarea cols="40" rows="5" type="text" placeholder="Message to all members" name="broadcastmessage" class="form-control">
				 </textarea>
            </div>
     		<input type="hidden"  name="cmd" value="message">
     		<input type="hidden"  name="s" value="'.$s.'">
            <p><button type="submit" class="btn btn-danger">Send Message</button></p>
        </div>
          </form>
		  </span>
  ');
//}
	  ?>

      <hr>
	  <p><b><?php print(whoIsRegisteredCount());?> RtSys Stammtisch members</b>: <?php printf(whoIsRegistered());
	  
	  $invited = whoIsInvitedCount();
	  if ($invited > 0) {
	  	  printf("<i><font color=\"#AAAAAA\">, and ".$invited." more invited&nbsp;&nbsp;&nbsp;</font></i>");
	  }

 	   printf("<a href=\"#\" onClick=\"document.getElementById('sendmessage').style.display = 'block'; return false;\">Send message &raquo</a>");

	  ?> 
	  

	  
	  </p>
	  <br>
      <footer>
        <p>&copy; 2016 <a href="http://www.rtsys.informatik.uni-kiel.de">EmbRtSys Group</a>, Kiel University, Germany</p>
      </footer>
	  
					<?php
			if (isAdmin($uid)) {
			printf('
			<div class="col-sm-3 flexcontainer" style="margin-left: -1em;">
          <form method="post">
     		<input type="hidden"  name="adminview" value="1">
     		<input type="hidden"  name="cmd" value="refresh">
     		<input type="hidden"  name="s" value="'.$s.'">
            <button type="submit" class="btn navbar-btn btn-default">Admin</button>
          </form>			
		 </div> 
		 <br>
			');
			}
			?>
				  
    </div> <!-- /container -->

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="../../assets/js/vendor/jquery.min.js"><\/script>')</script>
    <script src="../../dist/js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="../../assets/js/ie10-viewport-bug-workaround.js"></script>





  </body>
</html>
