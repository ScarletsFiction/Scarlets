<?php
namespace Scarlets\User;
use \Scarlets;

	$usersDatabase = 'nekonyaan'; //The table will be 'users'
class Auth{
	
	//Return true if not logged in
	function logout(){
		global $SFSessions, $sifyData;
		if(!checkLoginStatus()) $forceLogout = true;
		if(isset($_REQUEST['logout'])||$forceLogout)
		{
			$SFSessions = isset($sifyData['loggedin'])&&$sifyData['loggedin']!=''?['oldaccount'=>isset($SFSessions['loggedin'])?$SFSessions['loggedin']:""]:[];
			$sifyData = [];
			return true;
		}
		return false;
	}
	//Return true if success
	function login($userpass)
	{
		global $nekonyaanDatabase, $SFSessions, $sifyData;
		
		$data = $nekonyaanDatabase->select('users', ['user_id', 'name', 'password'], ['username'=>strtolower($userpass[0])]);
		if(count($data)==1)
		{
			$data = $data[0];
			if(password_verify($userpass[1], $data['password']))
			{
				$SFSessions['loggedin'] = strtolower($userpass[0]);
				$SFSessions['userID'] = $data['user_id'];
				$sifyData['loggedin'] = $SFSessions['loggedin'];
				$sifyData['userID'] = $SFSessions['userID'];
				saveSifyData();

				$SFSessions['name'] = $data['name'];
				return true;
			}
		}
		return false;
	}
	//Return [id, username] if logged in
	function checkLoginStatus($returnBool=false)
	{
		global $SFSessions, $sifyData;
		if(isset($SFSessions['loggedin']) && isset($sifyData['loggedin']))
		{
			if($SFSessions['loggedin'] && $sifyData['loggedin'] && $SFSessions['loggedin']!=$sifyData['loggedin']){
				destroyCookies();
				return false;
			}
			else if(!$SFSessions['loggedin'] || $SFSessions['loggedin']!=$sifyData['loggedin'])
				return false;
			
			else{
				if($returnBool) return true;
				else return [$SFSessions['userID'], $SFSessions['loggedin']];
			}
		}
		else return false;
	}
	//[email, username, name, password , |facebook, twitter, google| ]
	function signUp($data)
	{
		global $nekonyaanDatabase;

		//Validate data
		if(strlen(filter_var($data['email'],FILTER_SANITIZE_EMAIL))<strlen($data['email'])-1)
			die('Email: Not valid');
		if(strlen(preg_replace('/[^a-zA-Z0-9]/', '', $data['username']))<strlen($data['username']))die('Username: Not valid');

		$nekonyaanDatabase->insert('users', $data);
	}
	function checkUsernameExist($username)
	{
		global $nekonyaanDatabase;
		if(count($nekonyaanDatabase->select('users', ['user_id'], ['username'=>$username]))>=1)
			return true;
		return false;
	}

	function checkEmailExist($email)
	{
		global $nekonyaanDatabase;
		if(count($nekonyaanDatabase->select('users', ['user_id'], ['email[~]'=>$email]))>=1)
			return true;
		return false;
	}
}