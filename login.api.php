<?php
    class loginAPI{
        //custom functions
        protected function holdQuotes($str){ // \ ' " `
            $str = str_replace("\\", "\\\\", $str);
            $str = str_replace("\'", "\\\'", $str);
            $str = str_replace("\"", "\\\"", $str);
            $str = str_replace("\`", "\\\`", $str);
            return $str;
        }
        protected function reholdQuotes($str){ // \ ' " `
            $str = str_replace("\\\`", "\`", $str);
            $str = str_replace("\\\"", "\"", $str);
            $str = str_replace("\\\'", "\'", $str);
            $str = str_replace("\\\\", "\\", $str);
            return $str;
        }
        protected function safe_die($code, $status, $desc){
            die(json_encode(array("status"=>array($code, $status), "desc"=>$desc)));
        }
        
        //constructed
        protected $db_servername, $db_username, $db_password, $db_dbname, $db;

        public function __construct($db_servername, $db_username, $db_password, $db_dbname){
            $this->db_servername = $db_servername;
            $this->db_username = $db_username;
            $this->db_password = $db_password;
            $this->db_dbname = $db_dbname;

            $this->db = new mysqli($db_servername, $db_username, $db_password, $db_dbname);
        }

        //main function
        public function getUserInfo($user_id){ //to identify a user from ther id<int> that created by system
            $id = (int)$id;
            $req1 = $this->db->query("SELECT `user_id`, `username`, `passhash` FROM `user_info` WHERE `user_id`=$user_id");
            if($req1->num_rows != 1) return false;
            return $req1->fetch_assoc();
        }
        public function getUserInfoByIdentification($username){ //if you want to identify other than username, such as email. chang this var to $username_email
            $username = $this->holdQuotes($username); //change this variable too
            $req1 = $this->db->query("SELECT `user_id`, `username`, `passhash` FROM `user_info` WHERE `username`='$username'"); //And use OR : `username`='$username_email' OR `email`='$username_email' 
            if($req1->num_rows != 1) return false;
            return $req1->fetch_assoc();
        }
        public function register($username, $password){ //you could add more arguments to get more info in user_login
            $username = $this->holdQuotes($username);
            $passhash = password_hash($password, PASSWORD_DEFAULT);
            
            $req1 = $this->db->query("SELECT MAX(`user_id`) FROM `user_info`");
            $res1 = $req1->fetch_assoc()["MAX(`user_id`)"];
            $num = ($res1 != "NULL") ? 1 : ((int)$res1) + 1;
            
            $req2 = $this->getUserInfoByIdentification($this->reholdQuotes($username)); //if you use email to identify too, duplicate this line and change the var name to email var, such as $email
            if($req2 == false) return false; //add "or $req2_email == false"
            
            $req3 = $this->$db->query("INSERT INTO `user_info`(`user_id`, `username`, `passhash`) VALUES ($num, '$username', '$passhash')");
            
            return $req3 ? $num : $req3;
        }
        public function changeUserInfo($user_id, $key, $val){
            $id  = (int)$user_id;
            $key = $this->holdQuotes($key);
            $val = $this->holdQuotes($val);
            $req1 = $this->db->query("UPDATE `user_info` SET `$key`='$val' WHERE `user_id`=$id");
            
            return $req1;
        }
        public function login($username, $password, $setSession=true, $valid_data=array()){ //change $username to $username_email if you use email too
            //If you set $setSession arguments to false, it will try to login without create session for user
            $username = $this->holdQuotes($username);
            $valid_data = $this->holdQuotes(json_encode($valid_data));
            
            $res1 = $this->getUserInfoByIdentification($this->reholdQuotes($username)); //change this too
            
            $passver = password_verify($password, $res1["passhash"]);
            if($passver == false) return false;
            if($setSession == false) return $passver;
            
            $id = (int)$res1["user_id"];
            
            $hash = base64_encode(((string)(time() * rand(1000,9999))) . ((string)(rand(100000000,999999999))));
            
            $created = time();
            $expired = strtotime("+1 week");
            
            $req3 = $this->db->query("INSERT INTO `user_session`(`user_id`, `hash`, `created_time`, `expired_time`, `valid_data`) VALUES ($id, '$hash', $created, $expired, '$valid_data')");
            
            if($req3 == true) return array($id, $hash);
            return $req3;
        }
        public function checkLogin($hash, $valid_data=null, $addExpired=true){ //if you set $valid_data to null, it will return session information based on hash
            $hash = $this->holdQuotes($hash);
            
            $req1 = $this->db->query("SELECT `user_id`, `hash`, `created_time`, `expired_time`, `valid_data` FROM `user_session` WHERE `hash`='$hash'");
            if($req1->num_rows != 1) return false;
            $dataSession = $req1->fetch_assoc();
            
            if($valid_data == null) return $dataSession;
            
            if(time() > $dataSession['expired_time']){
                $hash = $this->holdQuotes($dataSession['hash']);
                $req2 = $this->logout(reholdQuotes($hash));
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
                    $hash = $this->holdQuotes($dataSession['hash']);
                    $expired = (int)strtotime("+1 week");
                    $req3 = $this->db->query("UPDATE `user_session` SET `expired_time`=$expired WHERE `hash`='$hash'");
                }
                return $req3;
            }
        }
    }
?>