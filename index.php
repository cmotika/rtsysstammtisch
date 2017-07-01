<?php

 require('config.php');
 require('include.php');



// if ($cmd != "") {
// 	print("s:".$s);
// 	print("<br>cmd:".$cmd);
// 	print("<br>user:".$user);
// 	print("<br>pwd:".$pwd);
//	print("<br>adminview:".$adminview);
//	exit;
// }



 if ($cmd == "test") {
	sendEmailChangeVenue(1, 1, "der grund");
// changevenue(trim($reason));
 
//	sendEmailInvitation(1, 1);
//	sendEmailReminder(1, 1);
 }


 $nextMeetingTS =  getNextMeetingTimeStamp();
 $nextMeetingDate = date('D, d.m.Y', $nextMeetingTS);
 $nextMeetingDateShort = date('F j', $nextMeetingTS);
 $mid = getNextMeeting();

 //printf("<br><br><br><br><br><br><br><br>Next Meeting MID: ". getNextMeeting());


 $locationName = getMeetingLocationName($mid);
 $locationAddress = getMeetingLocationAddress($mid);
 $locationText = "The next <i>Stammtisch</i> <b>".$nextMeetingDate."</b> is going to happen <br>at <a href='http://www.google.de/search?q=".$locationName.", ".$locationAddress."' target='_blank'>".$locationName.", ".$locationAddress."</a>";
 if (isMeetingDayWrapper($TODAY)) {
	 $nextMeetingDate = "today";
     $nextMeetingDateShort = "today";
	 $locationText = "The next <i>Stammtisch</i> happens <b>TODAY</b> <br>at <a href='http://www.google.de/search?q=".$locationName.", ".    $locationAddress."' target='_blank'>".$locationName.", ".$locationAddress."</a>";
 }
 if ($locationName == "") {
	 $locationText = "The next <i>Stammtisch</i> will be <b>".$nextMeetingDate.".</b><BR>The venue has not been chosen yet - you can still vote for one!";
	  if (isMeetingDayWrapper($TODAY)) {
			 $locationText = "The next <i>Stammtisch</i> happens <b>TODAY</b>".$nextMeetingDate.".<BR>The venue has not been chosen yet - you can still vote for one!";
	  }
 }

 $locationText = $locationText."<BR>@ ".date("h:i A",$MEETINGSTART+$UTCTIMEDIFF)."";

 $message = "";
 $errormsg = false;
 $tmpuid = "";
 
 // cookie control I of II
 $cookie_name = "s";
 
 if ($cmd == "ics") {
		exportICS($mid, "2"); // alarm 2 hours before
 }
 
 if ($cmd == "vcs") {
 		exportVCS($mid);
 }

 if ($cmd == "login") {
    $uid = getUID($user);
	if ($uid < 0) {
	   // fall back, allow login with email too
	   $uid = getUIDbyMail($user);
	}
    if (login($uid, $pwd)) {
	    $s = createRandomSession($uid);
		$message = "Welcome ".getName($uid)."!";
		
		//printf("<br><br><br><br><br>Session/UID".$s." / ".$tmpuid);

	    // cookie control II of II
	    // cookie is valid for (1 day cookie)
	    $cookie_value = $s;
		setcookie($cookie_name, $cookie_value, time() + $EXPIRECOOKIE, "/"); 
		
		$tmpuid = $uid;
    	require('index-login.php');
		exit;
        //printf("logged in:".$s);
	} else {
	   if ($user != "") {
	     // delete cookie
		 setcookie($cookie_name, "invalid", time() + $EXPIRECOOKIE, "/"); 
 		 $message = "Authentication failed";
		 $errormsg = true;
	   }
	}
 }
 
 if ($cmd == "removeaccount") {
     $tmpuid = getSessionUID($s);
	 $vglpwd = getPwd($tmpuid);
	 //$message = $vglpwd ." == ".$tmpuid;
	 if ($vglpwd == md5($pwd)) {
	    removeAccount($tmpuid);
	  	$message = "Account removed, we are sad that you leave! :-(";
		$errormsg = false;
	    logout($uid);
		$uid = "";
		$s = "";
	 } else if ($pwd != "") {
	  	$message = "Wrong password. Account NOT removed.";
		$errormsg = true;
	 }
 }


 // this is the currently logged in user
 $tmpuid = getSessionUID($s);
 // test if we need a cookie and if there is one that helps
 if ($tmpuid <= 0 && ($errormsg == "")) {
   if (isset($_COOKIE[$cookie_name])) {
		$s = $_COOKIE[$cookie_name];
		$tmpuid = getSessionUID($s);
   }
 }
 
 //printf("<br><br><br><br><br>Session/UID".$s." / ".$tmpuid);

 if ($tmpuid > 0) {
    $uid = $tmpuid;
    if ($cmd == "logout") {
	    logout($uid);
		$uid = "";
		$s = "";
		$message = "Good Bye!";
        header("Location: ".$BASEURL."?cmd=logout");
        exit;
	} else {
	    // cookie control II of II
	    // cookie is valid for (1 day cookie)
	    $cookie_value = $s;
		setcookie($cookie_name, $cookie_value, time() + $EXPIRECOOKIE, "/"); 
	
    	require('index-login.php');
		exit;
    }
 }
 if ($cmd == "logout") {
		$uid = "";
		$s = "";
		$message = "Good Bye!";
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
	
	<?php 
	if (($cmd == "logout")||(($cmd == "login") && $errormsg != "")) {
		printf(' <meta http-equiv=REFRESH content="2; URL='.$BASEURL.'">');
	}
	?>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

	<script>
	function showMessage() {
		document.getElementById("title").style.display = 'none';
		document.getElementById("message").style.display = 'block';
		setTimeout(function() {
			document.getElementById("message").style.display = 'none';
			document.getElementById("title").style.display = 'block';
		}, 5000); // <-- time in milliseconds
	}
	</script>

	<style>
	.floatcontainer{
    display: flex
 	}
	</style>

  </head>


  <?php if($cmd == "location") {printf('<body>'.$locationText.'</body>'); exit;} ?>

  <body>

    <?php if($cmd == "minimal") {printf('<span id="message" style="display: none;">');} ?>
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
		  <span id="title">
	          <a class="navbar-brand" href="#">RtSys Stammtisch</a>
		  </span>

		  <span id="message" style="display: none;">
	          <a class="navbar-brand" href="<?php print($BASEURL)?>" style="font-weight: bold;color:#<?php if ($errormsg) { printf("FE8787"); } else {  printf("66FF66");} ?>"><?php printf($message)?></a>
		  </span>
		  <?php
		  if ($message != "") {
		  	 printf("<script> showMessage(); </script>");
		  }
		  ?>

        </div>
		
        <div id="navbar" class="navbar-collapse collapse">
          <form class="navbar-form navbar-right" method="post" action="<?php print($BASEURL)?>">
            <div class="form-group">
              <input type="text" placeholder="Name or Email" name="user" class="form-control">
            </div>
            <div class="form-group">
              <input type="password" placeholder="Password" name="pwd" class="form-control">
            </div>
			<input type="hidden"  name="cmd" value="login">
            <button type="submit" class="btn btn-success">Sign in</button>
          </form>
        </div><!--/.navbar-collapse -->
      </div>
    </nav>
    <?php if($cmd == "minimal") {printf('</span>');} ?>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
      <div class="container">
	    <?php if($cmd == "minimal") {printf('<span id="message" style="display: none;">');} ?>
	    <div class="col-md-3">
        <img src="clockman_m.png" alt="" vspace="20" class="jumbotronwidth">
		</div>
	    <?php if($cmd == "minimal") {printf('</span>');} ?>
	    <div class="col-md-6">
    <?php if($cmd == "minimal") {printf('<span id="message" style="display: none;">');} ?>
        <h1>RtSys Stammtisch</h1>
    <?php if($cmd == "minimal") {printf('</span>');} ?>
        <p><?php printf( $locationText );?></p>
		<div class="form-group floatcontainer">
		<?php
		if ($locationName != "") {
        	printf("<p></p><div class='scol-md-5' style='margin-left: -0em;'><a class='btn btn-primary btn-lg' href='http://maps.google.de/maps?q=".$locationAddress."' target='_blank' role='button'>Google Maps &raquo;</a></div> &nbsp;&nbsp;");
		} else {
			printf("<br>");
		}
  	    printf("<div class='scol-md-3' style='margin-left: -0em;'><a class='btn btn-primary btn-lg' href='".$BASEURL."?cmd=ics' role='button'>To Calendar &raquo;</a></div>");
		?>
		</div>

		</div>
      </div>
    </div>

    <div class="container">
      <!-- Example row of columns -->
      <div class="row">
        <div class="col-md-4">
        </div>
        <div class="col-md-4">
          <form method="post" action="<?php print($BASEURL)?>">
            <div class="form-group">
              <input type="text" placeholder="Name or Email" name="user" class="form-control">
            </div>
            <div class="form-group">
              <input type="password" placeholder="Password" name="pwd" class="form-control">
            </div>
          <p>
  		  <input type="hidden"  name="cmd" value="login">
		  <button type="submit" class="btn btn-default">Sign in</button>
          </p>
          </form>
       </div>
        <div class="col-md-4">
        </div>
      </div>



	  <?php if($cmd == "minimal") {printf('<span id="message" style="display: none;">');} ?>
      <hr>
      <footer>
        <p>&copy; 2016 <a href="http://www.rtsys.informatik.uni-kiel.de">EmbRtSys Group</a>, Kiel University, Germany</p>
      </footer>
   	  <?php if($cmd == "minimal") {printf('</span>');} ?>
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
