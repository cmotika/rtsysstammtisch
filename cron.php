<?php
 require('config.php');
 require('include.php');

 $nextMeetingTS =  getNextMeetingTimeStamp();
 $nextMeetingDate = date('D, d.m.Y', $nextMeetingTS);
 $nextMeetingDateShort = date('F j', $nextMeetingTS);
 $mid = getNextMeeting();

 $locationName = getMeetingLocationName($mid);
 $locationAddress = getMeetingLocationAddress($mid);
 //$locationLabel = getMeetingLocationLabel($mid);

 $meetingWhen = " on ".$nextMeetingDate;
 if (isMeetingDay($TODAY)) {
	$nextMeetingDate = "today";
    $nextMeetingDateShort = "today";
 	$meetingWhen = " <b>TODAY</b> ";
 }
 $locationText = "The next <i>Stammtisch</i> ".$meetingWhen." is going to happen here:<BR><a href='http://www.google.de/search?q=".$locationName.", ".$locationAddress."' target='_blank'>".$locationName.", ".$locationAddress."</a>";
 if ($locationName == "") {
	 $locationText = "The next <i>Stammtisch</i> will be on ".$nextMeetingDate.".<BR>The venue has not been chosen yet.";
 }

 if ($pwd == $CRONPWD) {
 		printf($locationText."<BR>[mid=".$mid."]<BR>");
 		if (isMeetingDay($TODAY)) {
 			printf("isMeetingDay:TRUE<BR>");
 		} else {
 			printf("isMeetingDay:FALSE<BR>");
 		}
		cron();
 }
 denyService();

?>