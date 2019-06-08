# Lite-LoginPHP
This is a simple login api for php. This Login is using session and database to identify user that login or not. <br />

## Documentation
- Database Configuration
- Enviroments Configuration
- Script Implementation
	- `register($username, $password)`
	- `login($username, $password, $setSession=true, $valid_data=array())`
	- `checkLogin($hash, $valid_data=array(), $addExpired=true)`
	- `logout($hash)`
	- `changeUserInfo($id, $key, $val)`
	- `getUserInfo($id)`
	- `getUserInfoByIdentification($username)`
- Extra Script Implementation
	- `holdQuotes($str)`
	- `reholdQuotes($str)`
	- `safe_die($code, $status, $desc)`
- Modify the script
	- Identify user via email and username
	- Configuring key-name for session
	- Configuring multiple login
	- Create meta data for user
	- Create roles for user
	- Create allowed and dissalowed feature

## Database Configuration
This scripts is tested in MariaDB Server for XAMPP (Windows 10). It only support for SQL Database. <br />
For quick configuration, just create a database and import the `database_structure.sql` to the database using phpMyAdmin.

Here are the structure of database's tables :

| Table Name   | Colomn Name  | Data Type | Input Type   | Values                                         | Notes                                                                            |
|--------------|--------------|-----------|--------------|------------------------------------------------|----------------------------------------------------------------------------------|
| user_info    | user_id      | int       | number       | begin from 1                                   |                                                                                  |
|              | username     | tinytext  | string       |                                                |                                                                                  |
|              | passhash     | tinytext  | passhash     |                                                |                                                                                  |
| user_session | user_id      | int       | number       | related to user_info.user_id                   |                                                                                  |
|              | hash         | tinytext  | base64       | (string)(time_now * random1) . (string)random2 | random1 is in range 1000 to 9999, and random2 is in range 100000000 to 999999999 |
|              | created_time | timestamp | time         | time_now                                       |                                                                                  |
|              | expired_time | timestamp | time         | +1 week from update                            |                                                                                  |
|              | valid_data   | text      | <json>object |                                                | to identify the request (browser/http call) such as UserAgent or language, etc.  |

## Enviroment Configuration
After configure the database, you should configure the varibel. You need database hostname, username, password, and database name.<br />
Change it under `//enviroment configuration`
```php
<?php
	//enviroment configuration
	$db_servername = "localhost";
	$db_username = "root";
	$db_password = "";
	$db_dbname = "liteLogin";
	
	...
?>
```

## Script Implementation
### `login($username, $password, $setSession=true, $valid_data=array())`
- `$username` \<string\>
- `$password` \<string\>
- `$setSession` \<boolean\> is setting to `false` will make function return `true` while `$username` and `$password` is valid. Default: `true`.
- `$valid_data` \<objects\>
- return `false` \<boolean\> while `$username` and `$password` is invalid. <br />
	 `true` \<boolean\> while is valid and `$setSession` is setting to `false`. <br />
	 `[user_id, hash]` \<object\> while is valid and `$setSession` is setting to `true`. <br />
