<?php
/*
##	Name: apiKit
##	File: settings.php
##	Description: This file should contain your configured settings.
##
##	Author: Robert Davies (admin@bobbigmac.com)
##	Website: http://www.apiKit.org
##
##	During an update/version upgrade, this file should NOT be replaced except as specified in the update notes.
##
*/

/*
##	SQL PROFILES
##	
##	sqlProfiles allow the config of multiple databases.
##	Calls to pn_ExecuteSql (from any 'service_funcs.inc' function) should be 
##		provided with the profile name specifying which database to use for the call.
##	The 'primary' database will be used if no dbSource parameter is passed.
##	
##	Current supported database types are:
##	mysql: MySQL
##	mssql: Microsoft SQL Server 2005 (Should support 2008 too)
##		mssql 2005 in most setups requires the use of the Microsoft SQL Server Driver for PHP 
##		installed and configured in your php.ini:
##		http://www.microsoft.com/sqlserver/2005/en/us/php-driver.aspx
##	
##	If you modify the util_sql.inc source to add support for other data stores, 
##	please send the code so it can be incorporated into the root build. Thanks :)
##
*/
$sqlProfiles = array(
	'primary'=>(object)array( //Default sql execution behaviour is to check for the 'primary' record.
		'type'=>'mssql or mysql', //mssql or mysql
		'path'=>'examplehostname',
		'username'=>'exampleuser',
		'password'=>'examplepass',
		'database'=>'exampledatabase'
	),
	'secondary'=>(object)array(
		'type'=>'',
		'path'=>'',
		'username'=>'',
		'password'=>'',
		'database'=>''
	)
);

/*
##	LOG SETTINGS
##	
##	This is a utility function to allow for usage-tracking and performance info.
##		It executes the specified stored procedure name on the specified sqlProfile 
##		(it must exist in the sqlProfiles config above).
##
##	The Usage-Log provides the following parameters to the stored procedure:
##	Func: the requested function
##	UserId: the userId of the currently authed user/worker if set.
##	Referrer: The referrer of the call (clients/consumers may set this when they make the call)
##	Agent: The user-agent of the calling browser
##	IpAddress: The IpAddress of the call originator
##	Duration: The duration in milliseconds taken to complete the operation. 
##		(It does not include the time taken to write the usage log entry)
##
*/
$logSettings = (object)array(
	'enabled'=>true,
	'procName'=>'put_UsageLog',
	'sqlProfile'=>'primary'
);

/*
##	USER/STAFF LOGIN
##	
##	This is a utility function to allow for user and/or staff login
##		It executes the specified stored procedure name on the specified sqlProfile 
##		(it must exist in the sqlProfiles config above).
##
##	The forceSecure option prevents plain-text authentication when the service is run
##		from http protocol (must be a secure transaction, ie: https).
##	When forceSecure is enabled, the saltedPassword must be used (password will be ignored)
##		This prevents your site from leaking secure data via man-in-the-middle attacks.
##	The stateless parameter if enabled, causes the the apiKit to NOT start a php session.
##	
##	The saltFormat should match the format you use in your database to store user/staff
##		credentials, or you should adapt your database to stores them in this format for comparison.
##	Salting prevents third-parties from guessing simple passwords by 
##		running tests against known hash-manifests.
##	If saltedPassword is provided on the request, the password parameter will be ignored, 
##		regardless of the setting of forceSecure.
##	The value of saltFormat should be human-readable as a guide to the format 
##		credentials should be sent to the API by the client-developer. Developers may 
##		choose to parse and automate this via getSpecification so try to be predictable.
##		As a guide, specify function call targets using brackets and fixed strings inside double-quotes.
##		Values: username and password should be defined as such.
##			eg.	md5(lower(username)password)
##					sha1(upper(usernamepassword))
##	
##	The login method provides the following parameters to the named stored procedure:
##		@Username: the supplied username from REQUEST('username')
##		@Password: the supplied password from REQUEST('password') OR
##			REQUEST('saltedPassword') based on call and configuration as described.
##	The login stored procedure should return:
##		for Users the UserId (GUID or Integer)
##		for Staff a record of (WorkerId, isAdmin)
##
##	You can easily override the default authentication by implementing auth_User and/or 
##		auth_Worker functions in your service_funcs.inc file.
##	
##	Username, password, and saltedPassword do not need to be included in the arguments list
##		for a function call. Authentication can be made when executing any function.
##		To pass the active UserId to a function, include an argument in that function specification
##		such as the following: 'internal:#'=>'$UserId'
##
*/
$loginSettings = (object)array(
	'forceSecure'=>true,
	'sqlProfile'=>'primary',
	'stateless'=>false,
	'staffProcName'=>'check_WorkerLogin',
	'userProcName'=>'check_UserLogin',
	'saltFormat'=>'md5(lower(username)password)',
	'saltRegex'=>'^\{?[a-fA-F0-9]{32}\}?$'
);

/*
##	CENTRAL REGISTRATION
##	
##	This has the api occasionally register with the central apiKit api
##		The purpose of such registration is to help with the discovery
##		and circulation of various apis using the kit.
##	It also helps gather statistics on kit usability, and provides a method
##		for alerting hosts of the kit to new updates, issues, or security concerns.
##
##	If you're using the kit primarily for internal or private functions, you should 
##		disable the registration, or set a high occurence variable.
##	The occurence allows for databaseless control of how often re-register:
##		for example, if your api gets ~1000 calls a day, and you want to re-register 
##		twice a week, set your occurence around 3500. (It generates a random 
##		number (max occurence) on every call, and if it matches, then it registers.
##		The chance of registration is random, so it may or may not register during this time).
##
*/
$centralRegistration = (object)array(
	'enabled'=>true,
	'occurence'=>'500',
	'info'=>array(
		'apiUrl'=>('http' . ($_SERVER['HTTPS'] == 'on' ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']),
		'apiSpecificationFunction'=>'getSpecification',
		'apiName'=>'',
		'apiDescription'=>'',
		'apiNotes'=>'',
		'apiDocsUrl'=>'',
		'parentUrl'=>'', //homepage
		'contactEmail'=>''
	)
);

?>