<?php

return [
	/*
	---------------------------------------------------------------------------
	| Database for registered users
	---------------------------------------------------------------------------
	|
	| When you're using internal build library for managing user
	| You would need to specify the database and table location
	| The minimal column are:
	| 'user_id'(int), 'username'(text), 'password'(text), 'email'(text)
	|
	| Make sure you use 'user_id' when selecting rows to avoid performance
	| problem
	|
	*/
	'users' => [
		'database' => 'nekonyaan',
		'table' => 'users',
	],

	/*
	---------------------------------------------------------------------------
	| Access token
	---------------------------------------------------------------------------
	|
	| This will be used by internal library to manage access token for
	| your users. 
	| The minimal column for 'token_table':
	| token_id(int), app_id(int), user_id(int), expiration(int),
	| permissions(text)
	
	| The minimal column for 'app_table':
	| app_id(int), app_secret(text)
	|
	*/
	'access_token' => [
		'driver' => 'database', # Supported: redis, database
		'database' => 'scarletsfiction', # redis-> cache.php, database-> database.php

		# User token table
		'token_table' => 'access_token',

		# Application that have registered into your application 
		'app_table' => 'external_apps',

		# Available permission for your users
		'permissions' => [
			0 => 'public info',
			1 => 'read bookmark',
			2 => 'write bookmark',
			3 => 'read playlist',
			4 => 'write playlist',
			5 => 'like/unlike',
			6 => 'read activity',
			7 => 'write activity'
		]
	]
];