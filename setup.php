<?
 //error_reporting(-1);
 //ini_set('display_errors',1);
 //ini_set('display_startup_errors',1);
 error_reporting (E_ERROR | E_WARNING | E_PARSE); // This will NOT report uninitialized variables
 // Turnoff all error reporting
 //error_reporting(0);
 //ini_set('display_errors', 0);
 //ini_set('log_errors', 0);

 require('config.php');

 printf("<BR><BR>=== STAMMTISCH SETUP ====<BR><BR>");


 function opendb() {
     global $WEBMASTER;
     global $DBUSER;
     global $DBPWD;
     global $DBURL;
     $dbs = @mysql_connect($DBURL,$DBUSER,$DBPWD);
     if(!$dbs) { echo '-CANNOT ACCESS DATABASESERVER, PLEASE CONTACT THE SYSTEM ADMINISTARTOR AT '.$WEBMASTER; exit;}
     return $dbs;
 }

 $connection = opendb();
 $db = 0;
 if ($connection) $db =  mysql_select_db($DBNAME,$connection);
 if($db) {
 	  echo '-SETUP HAS ALREADY RUN, PLEASE CONTACT THE SYSTEM ADMINISTARTOR AT '.$WEBMASTER;
 	  exit;
 }

 // Create database
 $sql = "CREATE DATABASE ".$DBNAME;
 echo "Creating Database... ";
 if (mysql_query($sql)) {echo "OK<BR>";} else {echo "FAILED, DO MANUALLY:<BR>".htmlentities($sql)."<BR>";}
 //$success = mysql_query($sql);


 if ($connection) $db =  mysql_select_db($DBNAME,$connection);
 if(!$db) {
     echo "Error creating database. Is the db already created? Has user '".$DBUSER."' enough rights to create a DB? Is the password correct?";
 	  exit;
 } else {
     echo "Database created successfully.<BR>";
 }


     echo "<BR><BR>Now creating tables...<BR><BR>";





$sql = 'CREATE TABLE IF NOT EXISTS `confirm` ('
        . ' `uid` INT NOT NULL, '
        . ' `mid` INT NOT NULL, '
        . ' `confirmed` VARCHAR(50) NOT NULL, '
        . ' `phone` INT NOT NULL, '
        . ' `comment` VARCHAR(200) NOT NULL '
        . ' )';

 echo "confirm... ";
 if (mysql_query($sql)) {echo "OK<BR>";} else {echo "FAILED, DO MANUALLY:<BR>".htmlentities($sql)."<BR>";}



$sql = 'CREATE TABLE IF NOT EXISTS `rating` ('
        . ' `mid` INT NOT NULL, '
        . ' `uid` INT NOT NULL, '
        . ' `rating` INT NOT NULL, '
        . ' `timestamp` VARCHAR(50) NOT NULL '
        . ' )';

 echo "rating... ";
 if (mysql_query($sql)) {echo "OK<BR>";} else {echo "FAILED, DO MANUALLY:<BR>".htmlentities($sql)."<BR>";}



        $sql = 'CREATE TABLE IF NOT EXISTS  `meeting` ('
        . ' `mid` INT NOT NULL AUTO_INCREMENT, '
        . ' `date` VARCHAR(50) NOT NULL, '
        . ' `sid` INT NOT NULL, '
        . ' `invited` VARCHAR(50) NOT NULL, '
        . ' `reminded` VARCHAR(50) NOT NULL, '
        . ' PRIMARY KEY (`mid`)'
        . ' )';

 echo "meeting... ";
 if (mysql_query($sql)) {echo "OK<BR>";} else {echo "FAILED, DO MANUALLY:<BR>".htmlentities($sql)."<BR>";}



$sql = 'CREATE TABLE  IF NOT EXISTS  `session` ('
        . ' `uid` INT NOT NULL, '
        . ' `sessionid` VARCHAR(200) NOT NULL, '
        . ' `created` VARCHAR(50) NOT NULL, '
        . ' `ip` VARCHAR(20) NOT NULL, '
        . ' `browser` VARCHAR(200) NOT NULL '
        . ' )';

 echo "session... ";
 if (mysql_query($sql)) {echo "OK<BR>";} else {echo "FAILED, DO MANUALLY:<BR>".htmlentities($sql)."<BR>";}








$sql = 'CREATE TABLE IF NOT EXISTS  `suggestion` ('
        . ' `sid` INT NOT NULL AUTO_INCREMENT, '
        . ' `name` VARCHAR(50) NOT NULL, '
        . ' `address` VARCHAR(200) NOT NULL, '
        . ' `uid` INT NOT NULL, '
        . ' `suggested` VARCHAR(50) NOT NULL, '
        . ' `chosen` VARCHAR(50) NOT NULL, '
        . ' PRIMARY KEY (`sid`)'
        . ' )';

 echo "suggestion... ";
 if (mysql_query($sql)) {echo "OK<BR>";} else {echo "FAILED, DO MANUALLY:<BR>".htmlentities($sql)."<BR>";}






        $sql = 'CREATE TABLE IF NOT EXISTS `user` ('
        . ' `uid` INT NOT NULL AUTO_INCREMENT, '
        . ' `name` VARCHAR(50) NOT NULL, '
        . ' `pwd` VARCHAR(100) NOT NULL, '
        . ' `email` VARCHAR(50) NOT NULL, '
        . ' `registered` VARCHAR(50) NOT NULL, '
        . ' `lastseen` VARCHAR(50) NOT NULL, '
        . ' `invited` INT NOT NULL, '
        . ' `admin` INT NOT NULL, '
        . ' PRIMARY KEY (`uid`)'
        . ' )';

 echo "user... ";
 if (mysql_query($sql)) {echo "OK<BR>";} else {echo "FAILED, DO MANUALLY:<BR>".htmlentities($sql)."<BR>";}

        $sql = 'CREATE TABLE IF NOT EXISTS  `knowndevices` ('
		        . ' `created` VARCHAR(20) NOT NULL, '
		        . ' `uid` INT NOT NULL, '
		        . ' `device` VARCHAR(4) NOT NULL'
        . ' )';






$sql = 'CREATE TABLE  IF NOT EXISTS  `vote` ('
        . ' `sid` INT NOT NULL, '
        . ' `uid` INT NOT NULL, '
        . ' `voted` VARCHAR(50) NOT NULL '
        . ' )';

 echo "vote... ";
 if (mysql_query($sql)) {echo "OK<BR>";} else {echo "FAILED, DO MANUALLY:<BR>".htmlentities($sql)."<BR>";}





 $sql = "GRANT SELECT , INSERT , UPDATE , DELETE , CREATE , DROP , INDEX , ALTER  ON `".$DBNAME."` . * TO '".$DBUSER."'@'".$DBURL."';";
 echo "<BR>Granting permissions... ";
 if (mysql_query($sql)) {echo "OK<BR>";} else {echo "FAILED<BR>".htmlentities($sql)."<BR>";}



 printf("<BR><BR>Setup completed. You should delete the file 'setup.php' immediately.");


?>