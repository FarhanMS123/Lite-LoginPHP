<?php
	//enviroment configuration
	$db_servername = "localhost";
	$db_username = "root";
	$db_password = "";
	$db_dbname = "liteLogin";
	
	//functions
	function holdQuotes($str){ // \ ' " `
		$str = str_replace("\\", "\\\\", $str);
		$str = str_replace("\'", "\\\'", $str);
		$str = str_replace("\"", "\\\"", $str);
		$str = str_replace("\`", "\\\`", $str);
		return $str;
	}
	function reholdQuotes($str){ // \ ' " `
		$str = str_replace("\\\`", "\`", $str);
		$str = str_replace("\\\"", "\"", $str);
		$str = str_replace("\\\'", "\'", $str);
		$str = str_replace("\\\\", "\\", $str);
		return $str;
	}
	function safe_die($code, $status, $desc){
		die(json_encode(array("status"=>array($code, $status), "desc"=>$desc)));
	}
	
	//connect to database
	$db_type="mariaDB";
	$conn = new mysqli($db_servername, $db_username, $db_password, $db_dbname);
		
	//check Connection
	if($conn->connect_error){
		safe_die(500, "internal server error", "database connection error");
	}
	
	//main functions
	function getUserInfo($id){
		global $conn;
		$id = (int)$id;
		$req1 = $conn->query("SELECT `user_id`, `username`, `passhash` FROM `user_info` WHERE `user_id`=$id");
		if($req1->num_rows != 1) return false;
		return $req1->fetch_assoc();
	}
	function getUserInfoByIdentification($username){ //if you want to identify other than username, such as email. chang this var to $username_email
		global $conn;
		$username = holdQuotes($username); //change this variable too
		$req1 = $conn->query("SELECT `user_id`, `username`, `passhash` FROM `user_info` WHERE `username`='$username'"); //And use OR : `username`='$username_email' OR `email`='$username_email' 
		if($req1->num_rows != 1) return false;
		return $req1->fetch_assoc();
	}
	function register($username, $password){ //you could add more arguments to get more info in user_login
		global $conn;
		
		$username = holdQuotes($username);
		$passhash = password_hash($password, PASSWORD_DEFAULT);
		
		$req1 = $conn->query("SELECT MAX(`user_id`) FROM `user_info`");
		$res1 = $req1->fetch_assoc()["MAX(`user_id`)"];
		$num = ($res1 != "NULL") ? 1 : ((int)$res1) + 1;
		
		$req2 = getUserInfoByIdentification(reholdQuotes($username)); //if you use email to identify too, duplicate this line and change the var name to email var, such as $email
		if($req2 == false) return false; //add "or $req2_email == false"
		
		$req3 = $conn->query("INSERT INTO `user_info`(`user_id`, `username`, `passhash`) VALUES ($num, '$username', '$passhash')");
		
		return $req3 ? $num : $req3;
	}
	function changeUserInfo($user_id, $key, $val){
		$id  = (int)$user_id;
		$key = holdQuotes($key);
		$val = holdQuotes($val);
		$req1 = $db->query("UPDATE `user_info` SET `$key`='$val' WHERE `user_id`=$id");
		
		return $req1;
	}
	function login($username, $password, $setSession=true, $valid_data=array()){ //change $username to $username_email if you use email too
		//If you set $setSession arguments to false, it will try to login without create session for user
		global $conn;
		
		$username = holdQuotes($username);
		$valid_data = holdQuotes(json_encode($valid_data));
		
		$res1 = getUserInfoByIdentification(reholdQuotes($username)); //change this too
		
		$passver = password_verify($password, $res1["passhash"]);
		if($passver == false) return false;
		if($setSession == false) return $passver;
		
		$id = (int)$res1["id"];
		
		$hash = base64_encode(((string)(time() * rand(1000,9999))) . ((string)(rand(100000000,999999999))));
		
		$created = time();
		$expired = strtotime("+1 week");
		
		$req3 = $conn->query("INSERT INTO `user_session`(`user_id`, `hash`, `created_time`, `expired_time`, `valid_data`) VALUES ($id, '$hash', $created, $expired, '$valid_data')");
		
		if($req3 == true) return array($id, $hash);
		return $req3;
	}
	function checkLogin($hash, $valid_data=null, $addExpired=true){ //if you set $valid_data to null, it will return session information based on hash
		global $conn;
		
		$hash = holdQuotes($hash);
		
		$req1 = $conn->query("SELECT `user_id`, `hash`, `created_time`, `expired_time`, `valid_data` FROM `user_session` WHERE `hash`='$hash'");
		if($req1->num_rows != 1) return false;
		$dataSession = $req1->fetch_assoc();
		
		if($valid_data == null) return $dataSession;
		
		if(time() > $dataSession['expired_time']){
			$hash = holdQuotes($dataSession['hash']);
			$req2 = logout(reholdQuotes($hash));
			return false;
		}else{
			$isValid = true;
			$validation_data = json_decode($dataSession["valid_data"]);
			foreach($validation_data as $key => $val){
				if($valid_data[$key] != $val){
					return false;
				}
			}
			$req3 = true;
			if($addExpired){
				$hash = holdQuotes($dataSession['hash']);
				$expired = (int)strtotime("+1 week");
				$req3 = $conn->query("UPDATE `user_session` SET `expired_time`=$expired WHERE `hash`='$hash'");
			}
			return $req3;
		}
	}
	function logout($hash){
		$req1 = checkLogin($hash, null);
		if($req1 == false) return true;
		return $conn->query("DELETE FROM `user_session` WHERE `hash`='$hash'");
	}
?>