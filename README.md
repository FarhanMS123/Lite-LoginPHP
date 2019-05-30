# Lite-LoginPHP
This is a simple login api for php. This Login is using session and database to identify user that login or not.

## Documentation
- Database Configuration
- Script Implementation
	- Setting Enviroments
	- `register`
	- `login`
	- `checkLogin`
	- `logout`
	- `changeUserInfo`
	- `getUserInfo`
	- `getUserInfoByIdentification`
- Modify the script
	- Identify user via email and username
	- Configuring key-name for session
	- Configuring multiple login
	- Create meta data for user
	- Create roles for user
	- Create allowed and dissalowed feature

## Database Configuration
For quick configuration, just create a database and import the `database_structure.sql` to the database using phpMyAdmin.

Here are the structure of database's tables :

| Table Name   | Colomn Name  | Data Type | Input Type   | Values                                         | Notes                                                                            |
==============================================================================================================================================================================================
| user_info    | user_id      | int       | number       | begin from 1                                   |                                                                                  |
|              | username     | tinytext  | string       |                                                |                                                                                  |
|              | passhash     | tinytext  | passhash     |                                                |                                                                                  |
| user_session | user_id      | int       | number       | related to user_info.user_id                   |                                                                                  |
|              | hash         | tinytext  | base64       | (string)(time_now * random1) . (string)random2 | random1 is in range 1000 to 9999, and random2 is in range 100000000 to 999999999 |
|              | created_time | timestamp | time         | time_now                                       |                                                                                  |
|              | expired_time | timestamp | time         | +1 week from update                            |                                                                                  |
|              | valid_data   | text      | <json>object |                                                | to identify the request (browser/http call) such as UserAgent or language, etc.  |
