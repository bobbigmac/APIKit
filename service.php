<?php
$operationStartTime = microtime(true);
/*
##	Name: apiKit
##	File: service.php
##	Description: A framework for easily creating a RESTful JSON API to your data
##
##	Author: Robert Davies (admin@bobbigmac.com)
##	Website: https://github.com/bobbigmac/APIKit
##
##	Support: Email me directly at admin@bobbigmac.com
##	License: CC-BY-SA 3.0
##					Creative Commons Attribution-Share Alike 3.0
##					http://creativecommons.org/licenses/by-sa/3.0/
##		Attribution required via link to http://www.apikit.org on docs page.
##		Sharing changes is required (as per the license), but it would also be helpful 
##			if you let me know what changes you have made, and where the source can 
##			be downloaded so the main build can be improved for everyone.
##		Source additions which integrate directly with your existing infrastructure, or
##			changes which may reveal secured or proprietary aspects of your 
##			existing systems are subject to your own licensing terms and are not 
##			considered as a part of the changes required to be shared.
##		No warranty express or implied is provided with this software.
##
##	Compatibility:
##		Should be entirely compatible with PHP 5.2 and above
##		If required to run on PHP 4 it is compatible at 4.3.0 or above for everything
##			except JSON support (json_encode, json_decode).
##		To enable JSON support for lower versions of PHP I suggest adding the following:
##			http://php.net/manual/en/function.json-decode.php#80606
##		This can be safely added to your service_funcs.inc file.
##
##	Project Goals:
##		The apiKit framework aims to be a lightweight, and quick platform to add
##			a JSON-based API to your existing infrastructure.
##		Part of this is in allowing developers and companies to work together well,
##			by providing the tools to get systems talking to each other without the need
##			to spend much money developing new systems from scratch.
##		The apiKit should be simple to add to existing systems, and present a
##			minimal impact on existing resources.
##		The underlying ideology is represented best by Tim Berners-Lee TED Talk
##			presentation at http://www.RawDataNow.com
##
*/
include_once('./config/settings.php');
if(!$loginSettings->stateless)
{
	session_start();
}
include_once('./util/util_gen.php');
include_once('./util/util_sql.php');
include_once('./config/specification.php');
include_once('./config/service_funcs.php');

function get_TestResponse()
{
	return get_StatusArray('ok', null);
}

function logout_User($UserId)
{
	if(isset($_SESSION['UserId']))
	{
		$_SESSION['UserId'] = null;
		unset($_SESSION['UserId']);
	}
	return get_StatusArray('loggedout', null);
}

function logout_Worker($WorkerId)
{
	if(isset($_SESSION['WorkerId']))
	{
		$_SESSION['WorkerId'] = null;
		unset($_SESSION['WorkerId']);
	}
	if(isset($_SESSION['isAdmin']))
	{
		$_SESSION['isAdmin'] = null;
		unset($_SESSION['isAdmin']);
	}
	return get_StatusArray('loggedout', null);
}

function logout($UserId, $WorkerId)
{
	if(!is_null($UserId))
	{
		logout_User($UserId);
	}
	if(!is_null($WorkerId))
	{
		logout_Worker($WorkerId);
	}
	return get_StatusArray('loggedout', null);
}

function get_LoginStatus($Username, $Password)
{
	//Provided login details were verified when determining whether execution of this method was allowed.
	return get_StatusArray('loggedin', null);
}

if(!function_exists('auth_User'))
{
	function auth_User($username, $password, $saltedPassword)
	{
		global $loginSettings;
		$userId = null;
		if(!is_null($username) && (!is_null($password) || !is_null($saltedPassword)))
		{
			$secured = (($_SERVER['HTTPS'] == 'on') ? true : false);
			if($loginSettings->forceSecure && !$secured)
			{
				$password = null;
				if(preg_match('/' . $loginSettings->saltRegex . '/', $saltedPassword) === 1)
				{
					$password = $saltedPassword;
				}
			}
		
			if(strlen($username) > 0 && strlen($password) > 0)
			{
				$params = array('Username'=>$username,'Password'=>$password);
				$userIdRow = pn_ExecuteSql($loginSettings->userProcName, $params);
				
				if(!is_null($userIdRow))
				{
					if(count($userIdRow) == 1 || (count($userIdRow) == 2 && $userIdRow['rows'] == 1))
					{
						$userId = $userIdRow[0]->UserId;
					}
				}
			}
		}
		
		if(!is_null($userId))
		{
			if(!$loginSettings->stateless)
			{
				$_SESSION['UserId'] = $userId;
			}
			return $userId;
		}
		
		return null;
	}
}

if(!function_exists('auth_Worker'))
{
	function auth_Worker($username, $password, $saltedPassword)
	{
		global $loginSettings;
		$workerId = null;
		$isAdmin = null;
		if(!is_null($username) && (!is_null($password) || !is_null($saltedPassword)))
		{
			$secured = (($_SERVER['HTTPS'] == 'on') ? true : false);
			if($loginSettings->forceSecure && !$secured)
			{
				$password = null;
				if(preg_match('/' . $loginSettings->saltRegex . '/', $saltedPassword) === 1)
				{
					$password = $saltedPassword;
				}
			}
			
			if(strlen($username) > 0 && strlen($password) > 0)
			{
				$params = array('Username'=>$username,'Password'=>$password);
				$workerIdRow = pn_ExecuteSql($loginSettings->staffProcName, $params);
				
				if(!is_null($workerIdRow))
				{
					if(count($workerIdRow) == 1 || (count($workerIdRow) == 2 && $workerIdRow['rows'] == 1))
					{
						if(isset($workerIdRow[0]->WorkerId) && !is_null($workerIdRow[0]->WorkerId))
						{
							$workerId = $workerIdRow[0]->WorkerId;
						}
						
						if(isset($workerIdRow[0]->isAdmin) && !is_null($workerIdRow[0]->isAdmin))
						{
							$isAdmin = $workerIdRow[0]->isAdmin;
						}
					}
				}
			}
		}
		
		if(!is_null($workerId))
		{
			if(!$loginSettings->stateless)
			{
				$_SESSION['WorkerId'] = $workerId;
				if(!is_null($isAdmin) && $isAdmin)
				{
					$_SESSION['isAdmin'] = $isAdmin;
				}
			}
			$workerObj = (object)array('WorkerId'=>$WorkerId);
			if(!is_null($isAdmin) && $isAdmin)
			{
				$workerObj->isAdmin = $isAdmin;
			}
			return $workerObj;
		}
		
		return null;
	}
}

function Service_Load()
{
	global $serviceSpecification;
	$jsonpCallback = null;
	lowerRequestParameters();
	
	if(isset($_REQUEST['callback']))
	{
		$jsonpCallback = $_REQUEST['callback'];
	}
	
	if(isset($_REQUEST['func']) && $serviceSpecification)
	{
		$ret = array();
		$authedAsWorker = false;
		$WorkerRecord = null;
		$UserId = null;
		$WorkerId = null;
		$functionName = strtolower($_REQUEST['func']);
		
		if($functionName != 'signup')
		{
			//if(!isset($_REQUEST['isworker']) || (isset($_REQUEST['isworker']) && ($_REQUEST['isworker'] == false || $_REQUEST['isworker'] == 0 || $_REQUEST['isworker'] == 'false' || $_REQUEST['isworker'] == '0')))
			//{
				$UserId = auth_User(getRequestParameter('username'), getRequestParameter('password'), getRequestParameter('saltedpassword'));
			//}
			//else
			//{
				$WorkerRecord = auth_Worker(getRequestParameter('username'), getRequestParameter('password'), getRequestParameter('saltedpassword'));
				if(!is_null($WorkerRecord))
				{
					$WorkerId = $WorkerRecord->WorkerId;
					$authedAsWorker = true;
				}
			//}
		}
		
		if(isset($serviceSpecification->functions[$functionName]) && !is_null($serviceSpecification->functions[$functionName]))
		{
			$activeFunction = $serviceSpecification->functions[$functionName];
			//echo json_encode($activeFunction);
			foreach($activeFunction as $destFuncName => $activeFunctionSpec)
			{
				if(is_object($activeFunctionSpec) || is_array($activeFunctionSpec))
				{
					$funcAllowed = false;
					$grant = $activeFunctionSpec->grant;
					
					//CHECK PERMISSIONS
					if(!is_null($grant))
					{
						if(array_search('public', $grant) !== false)
						{
							$funcAllowed = true;
						}
						else if(!is_null($UserId) && array_search('user', $grant) !== false)
						{
							$funcAllowed = true;
						}
						else if($authedAsWorker && !is_null($WorkerRecord))
						{
							if(array_search('admin', $grant) !== false && isset($WorkerRecord->isAdmin) && !is_null($WorkerRecord->isAdmin) && ($WorkerRecord->isAdmin === 1 || $WorkerRecord->isAdmin === true))
							{
								$funcAllowed = true;
							}
							else if(array_search('staff', $grant) !== false)
							{
								$funcAllowed = true;
							}
						}
					}
					
					if($funcAllowed)
					{
						$args = $activeFunctionSpec->args;
						$funcArgs = array();
						$namedArgs = array();
						$invalidArguments = array();
						
						if(!is_null($args) && count($args) > 0)
						{
							foreach($args as $argName => $argValidator)
							{
								if(strpos($argName, 'internal:') === 0)
								{
									if(strpos($argValidator, '$') === 0)
									{
										//echo 'adding: ' . substr($argValidator, 1, strlen($argValidator) - 1) . ' value: ' . ${substr($argValidator, 1, strlen($argValidator) - 1)} . '<br><br>';
										array_push($funcArgs, ${substr($argValidator, 1, strlen($argValidator) - 1)});
									}
									else
									{
										array_push($funcArgs, $argValidator);
									}
								}
								else
								{
									$paramArguments = explode(',', $argName);
									$currentArgValue = call_user_func_array('getRequestParameter', $paramArguments);
									
									if(startsWith($argValidator, 'json'))
									{
										$jsonData = @json_decode($currentArgValue);
										if($jsonData === null && json_last_error() !== JSON_ERROR_NONE)
										{
											array_push($invalidArguments, $argName);
										}
										else
										{
											array_push($funcArgs, $jsonData);
											$namedArgs = array_merge($namedArgs, array($argName=>$jsonData));
										}
									}
									else
									{
										$argRegex = ((isset($serviceSpecification->validators[$argValidator])) ? $serviceSpecification->validators[$argValidator] : null);
									
										if(is_null($currentArgValue) || is_null($argRegex) || preg_match('/' . $argRegex . '/', $currentArgValue) === 1)
										{
											array_push($funcArgs, $currentArgValue);
											$namedArgs = array_merge($namedArgs, array($argName=>$currentArgValue));
										}
										else
										{
											array_push($invalidArguments, $argName);
										}
									}
								}
							}
						}
						
						if(count($invalidArguments) > 0)
						{
							$ret[$destFuncName] = get_StatusArray('error', 'Parameter(s) failed validation: ' . implode(',', $invalidArguments));
						}
						else
						{
							$ret[$destFuncName] = call_user_func_array($destFuncName, $funcArgs);
							
							//check spec for trigger(s), if exists, pass to trigger with wait param and $namedArgs
							if(isset($activeFunctionSpec->triggers) && !is_null($activeFunctionSpec->triggers) && count($activeFunctionSpec->triggers) > 0)
							{
								foreach($activeFunctionSpec->triggers as $triggerHost => $triggerSpec)
								{
									$triggerFunc = $triggerSpec['function'];
									$waitResponse = ((isset($triggerSpec['wait'])) ? $triggerSpec['wait'] : false);
																		
									//To await a response, the result of the internal function must exist and be either an array or object
									if($waitResponse == true && isset($ret[$destFuncName]) && !is_null($ret[$destFuncName]))
									{
										$retIsObject = is_object($ret[$destFuncName]);
										$retIsArray = is_array($ret[$destFuncName]);
										
										$triggerResponse = trigger_RemoteService($triggerHost, $triggerFunc, $namedArgs, $triggerSpec['args'], true);
										
										if($retIsArray)
										{
											$ret[$destFuncName] = (object)$ret[$destFuncName];
											$retIsObject = is_object($ret[$destFuncName]);
										}
										
										if($retIsObject)
										{
											//Attach response structure and response
											if(!isset($ret[$destFuncName]->triggerResponse) || (isset($ret[$destFuncName]->triggerResponse) && !is_array($ret[$destFuncName]->triggerResponse)))
											{
												$ret[$destFuncName]->triggerResponse = array();
											}
											if(!isset($ret[$destFuncName]->triggerResponse[$triggerHost]) || (isset($ret[$destFuncName]->triggerResponse[$triggerHost]) && !is_array($ret[$destFuncName]->triggerResponse[$triggerHost])))
											{
												$ret[$destFuncName]->triggerResponse[$triggerHost] = array();
											}
											
											$ret[$destFuncName]->triggerResponse[$triggerHost][$triggerFunc] = $triggerResponse;
										}
									}
									else
									{
										trigger_RemoteService($triggerHost, $triggerFunc, $namedArgs, $triggerSpec['args']);
									}
								}
							}
						}
					}
					else
					{
						$ret[$destFuncName] = get_StatusArray('error', 'Permission denied to call function.');
					}
				}
			}

			if(!is_null($ret) && count($ret) > 0)
			{
				//OUTPUT RESULT OF FUNCTION
				if(count($ret) == 1)
				{
					$retArrayKeys = array_keys($ret);
					$ret = $ret[$retArrayKeys[0]];
				}
				
				if(!is_null($ret))
				{
					echo ((!is_null($jsonpCallback)) ? $jsonpCallback . '(': '') . pn_OutputEncoded($ret) . ((!is_null($jsonpCallback)) ? ');': '');
				}
			}
		}
		else
		{
			$noFunc = get_StatusArray('error', 'Requested function does not exist.');
			echo ((!is_null($jsonpCallback)) ? $jsonpCallback . '(': '') . pn_OutputEncoded($noFunc) . ((!is_null($jsonpCallback)) ? ');': '');
		}
	}
	else
	{
		$noFunc = get_StatusArray('error', 'No functionality specified.');
		echo ((!is_null($jsonpCallback)) ? $jsonpCallback . '(': '') . pn_OutputEncoded($noFunc) . ((!is_null($jsonpCallback)) ? ');': '');
	}
	check_CentralRegistration();
	put_UsageLogEntry();
}
Service_Load();

?>