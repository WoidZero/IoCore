<?php
	include "../config/name.php";
	include "../include/lib/connection.php";

	require "../include/lib/XORCipher.php";
	require_once "../include/lib/mainLib.php";

	$gs = new mainLib();
	$xc = new XORCipher();

	function chkarray($source){
		if ($source == "") {
			$target = "0";
		} else {
			$target = $source;
		}
		return $target;
	}

	if (!empty($_POST["levelid"])) {
		$levelID = $_POST["levelid"];
		$levelID = preg_replace("/[^0-9]/", '', $levelID);
		$url = $_POST["server"];
		$post = ['gameVersion' => '21', 'binaryVersion' => '33', 'gdw' => '0', 'levelID' => $levelID, 'secret' => 'Wmfd2893gb7', 'inc' => '1', 'extras' => '0'];
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$result = curl_exec($ch);
		curl_close($ch);

		if ($result == "" OR $result == "-1" OR $result == "No no no") {
			if ($result=="") {
				$log =  "An error has occured while connecting to the server.";
			} elseif($result=="-1") {
				$log =  "This level doesn't exist.";
			} else {
				$log =  "RobTop doesn't like you or something...";
			}
			$log =  "<br>Error code: $result";
		}else{
			$level = explode('#', $result)[0];
			$resultarray = explode(':', $level);
			$levelarray = array();
			$x = 1;
			foreach($resultarray as &$value){
				if ($x % 2 == 0) {
					$levelarray["a$arname"] = $value;
				}else{
					$arname = $value;
				}
				$x++;
			}
			//echo $result;
			if($_POST["debug"] == 1){
				echo $result;
				var_dump($levelarray);
			}
			if($levelarray["a4"] == ""){
				$log = "Error: ".htmlspecialchars($result,ENT_QUOTES);
			}
			$uploadDate = time();
			//old levelString
			$levelString = chkarray($levelarray["a4"]);
			$gameVersion = chkarray($levelarray["a13"]);
			if(substr($levelString,0,2) == 'eJ'){
				$levelString = str_replace("_","/",$levelString);
				$levelString = str_replace("-","+",$levelString);
				$levelString = gzuncompress(base64_decode($levelString));
				if($gameVersion > 18){
					$gameVersion = 18;
				}
			}
			//check if exists
			$query = $db->prepare("SELECT count(*) FROM levels WHERE originalReup = :lvl OR original = :lvl");
			$query->execute([':lvl' => $levelarray["a1"]]);
			if($query->fetchColumn() == 0){
				$parsedurl = parse_url($url);
				if($parsedurl["host"] == $_SERVER['SERVER_NAME']){
					$log = "You're attempting to reupload from the this server";
					exit();
				}
				$hostname = $gs->getIP();
				//values
				$twoPlayer = chkarray($levelarray["a31"]);
				$songID = chkarray($levelarray["a35"]);
				$coins = chkarray($levelarray["a37"]);
				$reqstar = chkarray($levelarray["a39"]);
				$extraString = chkarray($levelarray["a36"]);
				$starStars = chkarray($levelarray["a18"]);
				$isLDM = chkarray($levelarray["a40"]);
				$password = chkarray($levelarray["a27"]);
				if($password != "0"){
					$password = $xc->cipher(base64_decode($password),26364);
				}
				$starCoins = 0;
				$starDiff = 0;
				$starDemon = 0;
				$starAuto = 0;

				if ($parsedurl["host"] == "www.boomlings.com") {
					if ($starStars != 0) {
						$starCoins = chkarray($levelarray["a38"]);
						$starDiff = chkarray($levelarray["a9"]);
						$starDemon = chkarray($levelarray["a17"]);
						$starAuto = chkarray($levelarray["a25"]);
					}
				} else {
					$starStars = 0;
				}
				$targetUserID = chkarray($levelarray["a6"]);
				//linkacc
				$query = $db->prepare("SELECT accountID, userID FROM links WHERE targetUserID=:target AND server=:url");
				$query->execute([':target' => $targetUserID, ':url' => $parsedurl["host"]]);
				if($query->rowCount() == 0){
					$userID = 71;
					$extID = 71;
				}else{
					$userInfo = $query->fetchAll()[0];
					$userID = $userInfo["userID"];
					$extID = $userInfo["accountID"];
				}
				//query
				$query = $db->prepare("INSERT INTO levels (levelName, gameVersion, binaryVersion, userName, levelDesc, levelVersion, levelLength, audioTrack, auto, password, original, twoPlayer, songID, objects, coins, requestedStars, extraString, levelString, levelInfo, secret, uploadDate, updateDate, originalReup, userID, extID, unlisted, hostname, starStars, starCoins, starDifficulty, starDemon, starAuto, isLDM) VALUES (:name ,:gameVersion, '27', 'Reupload', :desc, :version, :length, :audiotrack, '0', :password, :originalReup, :twoPlayer, :songID, '0', :coins, :reqstar, :extraString, :levelString, '0', '0', '$uploadDate', '$uploadDate', :originalReup, :userID, :extID, '0', :hostname, :starStars, :starCoins, :starDifficulty, :starDemon, :starAuto, :isLDM)");
				$query->execute([':password' => $password, ':starDemon' => $starDemon, ':starAuto' => $starAuto, ':gameVersion' => $gameVersion, ':name' => $levelarray["a2"], ':desc' => $levelarray["a3"], ':version' => $levelarray["a5"], ':length' => $levelarray["a15"], ':audiotrack' => $levelarray["a12"], ':twoPlayer' => $twoPlayer, ':songID' => $songID, ':coins' => $coins, ':reqstar' => $reqstar, ':extraString' => $extraString, ':levelString' => "", ':originalReup' => $levelarray["a1"], ':hostname' => $hostname, ':starStars' => $starStars, ':starCoins' => $starCoins, ':starDifficulty' => $starDiff, ':userID' => $userID, ':extID' => $extID, ':isLDM' => $isLDM]);
				$levelID = $db->lastInsertId();
				file_put_contents("../data/levels/$levelID",$levelString);
				$log = "Level reuploaded, <b> $levelID </b>";
			}else{
				$log = "This level has been already reuploaded";
			}
		}
	}
?>
<html>
	<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, maximum-scale=1.0, user-scalable=no">
	<link href="../include/components/css/styles.css" rel="stylesheet">
	<link href="../include/components/images/tools_favicon.png" rel="shortcut icon">
	<title>Level Reupload</title>
	</head>

	<body>
		<main id="tools">
			<h1>Level Reupload</h1>
			<section id="toolbox" style="height: 30rem; width: 40rem;">
				<form class="form" method="post" action="levelReupload.php">
					<h3 style="padding: 15px;">Reupload from server</h3>
					ID: <input type="text" name="levelid"><br>
					URL: <input type="text" name="server" value="http://www.boomlings.com/database/downloadGJLevel22.php"><br>
					Unlisted (0=false, 1=true): <input type="text" name="debug" value="0"><br>
					<input type="submit" value="Reupload">
				</form>

				<div id="toolbox__log">
					<p><?php echo $log ?></p>
				</div>
			</section>
		</main>
		<footer>Provided by <span><a href="https://github.com/WoidZero/IoCore">IoCore</a></span> / Developed by <a href="https://github.com/WoidZero">WoidZero</a></footer>
	</body>
</html>
