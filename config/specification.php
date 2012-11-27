<?php
/*
##	Name: apiKit
##	File: specification.php
##	Description: This file should contain your specification manifest.
##
##	Author: Robert Davies (admin@bobbigmac.com)
##	Website: http://www.apiKit.org
##
##	During an update/version upgrade, this file should NOT be replaced except as specified in the update notes.
##	
##	The specification manifest primarily declares the list of executable function calls,
##		mapped to the internal function name to be executed, and permissions
##		along with the accepted arguments for each call, the function visibility
##	Function implementations should be added to the service_funcs codefile.
##
##	The permission model allows for public, user, staff and staff-admin.
##		public allows any caller to execute, staff and user may be both declared, 
##		but admin applies only to staff accounts (and therefore requires staff-auth)
##	
##	Validators take the form of regular expressions. Each accepted argument may 
##		specify a validator, where, if the value is provided on the query string, it must
##		match this validator for the function call to execute correctly.
##	The called function is responsible for enforcing required-ness of attributes to allow 
##		for overloading.
##	
##	Attributes may be provided by the specification through use of the 'internal:#'=>'value'
##		construct. In this way the same internal function can be used for different 
##		external calls.
##	$UserId and $WorkerId may be used to send the respective values for authenticated calls.
##
##############################################################
##
##	Specification schema is as follows:
##	
##	$serviceSpecification=>(object)array(
##		'validators'=>array(
##			'validatorName'=>'regularExpression'
##		),
##		'triggers'=>array(
##			'triggerName'=>array(
##				'url'=>'domain/path to called external api', 
##				'call'=>'parameter name of function attribute',
##				'defaults'=>array('default'=>'value', 'parameter'=>'value', 'names'=>'value')
##			)
##		),
##		'functions'=>array(
##			'functionname'=>array(
##				'internalFunctionName'=>(object)array(
##					'grant'=>array('public', 'staff', 'user', 'admin'),
##					'description'=>'Human readable description of function call',
##					'args'=>array(
##						'argumentName'=>'validatorName',
##						'internal:1'=>value,
##						'internal:2'=>'$UserId'
##					)
##					'triggers'=>array(
##						'triggerName'=>array(
##							'function'=>'remoteFunctionName', 
##							'wait'=>true|false, 
##							'args'=>null|array('list', 'of', 'passable', 'argument', 'names')
##				))),
##				'visibility'=>'public'|'private'
##			),
##			'otherfunctionname'=>array([as above])
##		),
##		'authentication'=>array(
##			'plaintextonlyhttps'=>'loaded from $loginSettings',
##			'saltFormat'=>'loaded from $loginSettings',
##			'saltRegex'=>'loaded from $loginSettings'
##		),
##		'notes'=>array('array of plain text notes', 'and descriptions', 'to help with development'),
##		'links'=>array(
##			'linkName'=>'linkUrl', 
##		)
##	);
##	
##############################################################
##	
*/

$serviceSpecification = (object)array(
	'validators'=>array( //These validators may not be bulletproof, check if your data must be clean.
		'emailFormat'=>'^([a-zA-Z0-9]+([\.+_-][a-zA-Z0-9]+)*)@(([a-zA-Z0-9]+((\.|[-]{1,2})[a-zA-Z0-9]+)*)\.[a-zA-Z]{2,6})$',
		'miniIdentFormat'=>'^[a-zA-Z0-9_]{2,5}$',
		'titleFormat'=>'^[ a-zA-Z0-9_\-]{3,50}$',
		'usernameFormat'=>'^[a-zA-Z0-9_]{3,16}$',
		'passwordFormat'=>'^[a-zA-Z0-9_]{3,16}$|^\{?[a-fA-F0-9]{32}\}?$',
		'alphaText2000'=>'^[ .,!;a-zA-Z0-9:@\/\[\]\-_]{1,2000}$',
		'alphaText255'=>'^[ .,!;a-zA-Z0-9:_\-@\/\[\]_]{1,255}$',
		'alphaText150'=>'^[ .a-zA-Z0-9:\-@_]{1,150}$',
		'alphaText50'=>'^[ .a-zA-Z0-9:\-@_]{1,50}$',
		'freeText'=>'^[\s\S]*$',
		'md5Format'=>'^\{?[a-fA-F0-9]{32}\}?$',
		'intFormat'=>'^[0-9]*$',
		'regexFormat1000'=>'^[\s\S]{1,1000}$',
		'boolFormat'=>'^[Ff](alse)?|[Tt](rue)?|0|[-+]?1$',
		'urlFormat'=>'^(chrome:\/\/|http:\/\/|https:\/\/)([a-zA-Z0-9]+\.[a-zA-Z0-9\-]+|[a-zA-Z0-9\-]+)(\.[a-zA-Z\.]{2,6}){0,7}([\/a-zA-Z0-9\.?=\/#%&\+-_]+|\/|)$',
		'guidFormat'=>'[({]?(0x)?[0-9a-fA-F]{8}([-,]?(0x)?[0-9a-fA-F]{4}){2}((-?[0-9a-fA-F]{4}-?[0-9a-fA-F]{12})|(,\{0x[0-9a-fA-F]{2}(,0x[0-9a-fA-F]{2}){7}\}))[)}]?',
	),
	'triggers'=>array(
		'apiKit'=>array('url'=>'http://apikit.org/api/', 'call'=>'func', 'defaults'=>array('source'=>$centralRegistration->info['apiName'], 'event'=>'trigger')),
		'picNiche'=>array('url'=>'http://picNiche.com/api/', 'call'=>'func', 'defaults'=>array('source'=>$centralRegistration->info['apiName'], 'event'=>'trigger')),
	),
	'functions'=>array(
		//UTILITIES
		'getoperationtime'=>array('get_OperationTime'=>(object)array('grant'=>array('public'),
																				'description'=>'Returns the time required in milliseconds to prepare and execute the operation. Used as a simple server performance measure.',
																				'args'=>array('internal:1'=>true)),
																			'visibility'=>'private'),
		'getusagelogentry'=>array('get_UsageLogEntry'=>(object)array('grant'=>array('public'),
																				'description'=>'Returns the usage log entry for this call as recorded to the system activity log.',
																				'args'=>null),
																			'visibility'=>'private'),
		'testmulti'=>array('get_UsageLogEntry'=>(object)array('grant'=>array('public'),
																				'description'=>'Returns the usage log entry for this call as recorded to the system activity log.',
																				'args'=>null),
									'get_OperationTime'=>(object)array('grant'=>array('public'),
																				'description'=>'Returns the time required in milliseconds to prepare and execute the operation. Used as a simple server performance measure.',
																				'args'=>array('internal:1'=>true)),
																			'visibility'=>'public'),
		'testtrigger'=>array('get_OperationTime'=>(object)array('grant'=>array('public'),
																				'description'=>'Returns the time required in milliseconds to prepare and execute the operation. Used as a simple server performance measure. Also triggers getusagelogentry on picNiche',
																				'args'=>array('internal:1'=>true),
																				'triggers'=>array('picNiche'=>array('function'=>'getusagelogentry', 'wait'=>true, 'args'=>null))),
																			'visibility'=>'public'),
																			
		'test'=>array('get_TestResponse'=>(object)array('grant'=>array('public'),
																				'description'=>'Returns a simple status-ok response.',
																				'args'=>null),
																			'visibility'=>'public'),
		
		//USER
		'getuser'=>array('get_User'=>(object)array('grant'=>array('user'), 
																				'description'=>'Gets details for the currently authenticated user.',
																				'args'=>array('internal:1'=>'$UserId')), //Note: do not evaluate $UserId at this point (as this array is built literally)
																			'visibility'=>'public'),
		'getworker'=>array('get_Worker'=>(object)array('grant'=>array('staff'), 
																				'description'=>'Gets details for the currently authenticated staff member.',
																				'args'=>array('internal:1'=>'$WorkerId')), //Note: do not evaluate $WorkerId at this point (as this array is built literally)
																			'visibility'=>'public'),
		
		//You should keep this declaration for the getSpecification call to run against you API (for docs, dynamic ui, etc).
		'getspecification'=>array('get_Specification'=>(object)array('grant'=>array('public'),
																				'description'=>'Returns the API specification in JSON format.',
																				'args'=>null),
																			'visibility'=>'public'),
	),
	'authentication'=>array(
		'plaintextonlyhttps'=>$loginSettings->forceSecure,
		'saltFormat'=>$loginSettings->saltFormat,
		'saltRegex'=>$loginSettings->saltRegex
	),
	'notes'=>array(
		'Call the function name you require as the parameter \'func\' with it\'s arguments as additional query string parameters in the same request, ie: apiUrl?func=getUser',
		'Parameters marked as internal should not be provided',
		'Any call may also be accompanied by username and password parameters to login for that call',
		'The account password may be transmitted as an MD5 hash of (lowercaseusername + password) for additional security',
		'Providing the isWorker parameter (set to true) will attempt to authenticate the provided username and password parameters against the staff list',
		'Parameters may be attached to the request in any order',
		'To provide a null value, do NOT supply that parameter',
		'All parameter names are converted to lowercase internally before any operations are performed',
		'Providing a zero-length-string (or whitespace only) as a value for a parameter will be interpreted as a literal empty string',
		'Functions marked as (Tentative) are still subject to significant change and should not be used in production systems',
		'Most requests can be performed on-session (after a login, before a logout), however for increased security, some functions explicitly require the username and password to verify the request',
		'To avoid proxy-caching most requests should be made via POST where possible. Where only a GET request is possible, it\'s suggested to amend a randomly named/valued parameter to your request (such as a timestamp or large random number)',
		'Where a parameter specification defines a comma-seperated list, the first is the parameter name to read from the query string, the second is the default value used when this parameter is not provided',
		'Providing parameter format=xml will result in xml formatted response. Any other value (or if not supplied) will result in json data.',
	),
	'links'=>array(
		'homepage'=>$centralRegistration->info['parentUrl'],
		'bugs'=>'',
		'provider'=>'http://www.apiKit.org',
		'docs'=>$centralRegistration->info['apiDocsUrl'],
		'email'=>$centralRegistration->info['contactEmail']
	)
);


?>