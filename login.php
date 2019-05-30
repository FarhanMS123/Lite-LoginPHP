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
	$db_type="mysql";
	$conn = new mysqli($db_servername, $db_username, $db_password, $db_dbname);
		
	//Check Connection
	if($conn->connect_error){
		safe_die(500, "internal server error", "database connection error");
	}
	
	//Main functions
	function getUserInfo($id){
		global $conn;
		$id = (int)$id;
		$req1 = $conn->query("SELECT `id`, `username`, `passhash` FROM `user_login` WHERE `id`=$id");
		if($req1->num_rows != 1) return false;
		return $req1->fetch_assoc();
	}
	function getUserInfoByIdentification($username){
		global $conn;
		$username = holdQuotes($username);
		$req1 = $conn->query("SELECT `id`, `username`, `passhash` FROM `user_login` WHERE `username`='$username'");
		if($req1->num_rows != 1) return false;
		return $req1->fetch_assoc();
	}
	function register($username, $password){
		global $conn;
		
		$username = holdQuotes($username);
		$passhash = password_hash($password, PASSWORD_DEFAULT);
		
		$req1 = $conn->query("SELECT MAX(`id`) FROM `user_login`");
		$res1 = $req1->fetch_assoc()["MAX(`id`)"];
		$num = ($res1 != "NULL") ? 1 : ((int)$res1) + 1;
		
		$req2 = getUserInfoByIdentification(reholdQuotes($username));
		if($req2 == false) return false;
		
		$req3 = $conn->query("INSERT INTO `user_login`(`id`, `username`, `passhash`) VALUES ($num, '$username', '$passhash')");
		
		return $req3 ? $num : $req3;
	}
	function changeUserInfo($id, $key, $val){
		$id  = holdQuotes($id);
		$key = holdQuotes($key);
		$val = holdQuotes($val);
		$req1 = $conn->query("UPDATE `user_login` SET `$key`='$val' WHERE `id`='$id'");
		
		return $req1;
	}
	function login($username, $password, $setSession=true, $valid_data=array()){
		global $conn;
		
		$username = holdQuotes($username);
		$valid_data = holdQuotes(json_encode($valid_data));
		
		$res1 = getUserInfoByIdentification(reholdQuotes($username));
		
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
	function checkLogin($hash, $valid_data=array(), $addExpired=true){
		global $conn;
		
		$hash = holdQuotes($hash);
		
		$req1 = $conn->query("SELECT `user_id`, `hash`, `created_time`, `expired_time`, `valid_data` FROM `user_session` WHERE `hash`='$hash'");
		if($req1->num_rows != 1) return false;
		$dataSession = $req1->fetch_assoc();
		
		if($valid_data == null) return $dataSession;
		
		if(time() > $dataSession['expired_time']){
			$id = (int)$dataSession['id'];
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
			$hash = holdQuotes($dataSession['hash']);
			$expired = (int)strtotime("+1 week");
			$req3 = true;
			if($addExpired){
				$req3 = $conn->query("UPDATE `user_session` SET `expired_time`='$expired' WHERE `hash`='$hash'");
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