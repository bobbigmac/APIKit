<?php
/*
##	Name: apiKit
##	File: util_gen.php
##	Description: This file should contain generic utilities.
##
##	Author: Robert Davies (admin@bobbigmac.com)
##	Website: http://www.apiKit.org
##
*/

/*Removed as autoupdates should only be performed via https (which php currently does not support as shipped).
function checkForUpdates()
{
	$hashUrl = 'https://picNiche.com/api/?func=getScriptUpdateManifest&addonName=apikit_php';
	$remoteHash = file_get_contents($hashUrl);
	echo $remoteHash;
	$localVersion=md5_file(__FILE__);
	return ($localVersion == $remoteHash);
}
//checkForUpdates();*/

function pn_debug($out)
{
	if(isset($_REQUEST['debug']))
	{
		echo $out;
	}
}

function startsWith($str, $match){
    return strpos($str, $match) === 0;
}

function is_PhpAboveOrEqualVersion($desired = '5.0')
{
	if(isset($desired))
	{
		if(strnatcmp(phpversion(), $desired) >= 0)
		{
			return true;
		} 
	}
	return false;
}

function lowerRequestParameters()
{
	$_REQUEST = array_change_key_case($_REQUEST);
}

function get_StatusArray($Status, $StatusMessage)
{
	$Status = strtolower($Status);
	if(!is_null($StatusMessage))
	{
		return array('status'=>$Status, $Status=>$StatusMessage);
	}
	else
	{
		return array('status'=>$Status);
	}
}

class XMLSerializer {
    // functions adopted from http://www.sean-barton.co.uk/2009/03/turning-an-array-or-object-into-xml-using-php/
    public static function generateValidXmlFromObject(stdClass $obj, $node_block='response', $node_name='value') {
        $arr = get_object_vars($obj);
        return self::generateValidXmlFromArray($arr, $node_block, $node_name);
    }

    public static function generateValidXmlFromArray($array, $node_block='response', $node_name='value') {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";

        $xml .= '<' . $node_block . '>' . "\n";
        $xml .= self::generateXmlFromArray($array, $node_name);
        $xml .= '</' . $node_block . '>';

        return $xml;
    }

    private static function generateXmlFromArray($array, $node_name, $depth = 1) {
        $xml = '';

        if (is_array($array) || is_object($array)) {
            foreach ($array as $key=>$value) {
                if (is_numeric($key)) {
                    $key = $node_name;
                }
				$depthClose = '';
				$lineBreak = '';
				$lineDepth = str_repeat("\t", $depth);
				if(is_array($value) || is_object($value))
				{
					$lineBreak = "\n";
					$depthClose = $lineDepth;
				}
                $xml .= $lineDepth . '<' . $key . '>' . $lineBreak . self::generateXmlFromArray($value, $node_name, $depth + 1) . $depthClose . '</' . $key . '>' . "\n";
            }
        } else {
            $xml = htmlspecialchars($array, ENT_QUOTES);
        }

        return $xml;
    }
}

function pn_OutputEncoded($targetObj)
{
	if(!is_object($targetObj) && !is_array($targetObj))
	{
		return $targetObj;
	}
	
	$format = strtolower(getRequestParameter('format', 'json'));
	if($format == 'xml')
	{
		if(is_object($targetObj))
		{
			return XMLSerializer::generateValidXmlFromObject($targetObj);
		}
		else
		{
			return XMLSerializer::generateValidXmlFromArray($targetObj);
		}
	}
	
	return json_encode($targetObj);
}

function getRequestParameter($paramName, $altValue = null, $altParamName = null)
{
	$paramName = strtolower($paramName);
	$altParamName = strtolower($altParamName);
	if($paramName === 'null')
	{
		$paramName = null;
	}
	if($altParamName === 'null')
	{
		$altParamName = null;
	}
	
	if(!is_null($paramName))
	{
		if(isset($_REQUEST[$paramName]))
		{
			return $_REQUEST[$paramName];
		}
		else
		{
			if(!is_null($altParamName))
			{
				if(isset($_REQUEST[$altParamName]))
				{
					return $_REQUEST[$altParamName];
				}
			}
			
			return $altValue;
		}
	}
	return null;
}

function get_Specification($forPublic = true)
{
	global $serviceSpecification;
	if($forPublic === false)
	{
		return $serviceSpecification;
	}
	else
	{
		if(isset($serviceSpecification) && !is_null($serviceSpecification) && isset($serviceSpecification->functions) && !is_null($serviceSpecification->functions))
		{
			$outputSpecification = (object)array('validators'=>$serviceSpecification->validators,
																	'triggers'=>$serviceSpecification->triggers,
																	'functions'=>array(),
																	'notes'=>$serviceSpecification->notes,
																	'authentication'=>$serviceSpecification->authentication,
																	'links'=>$serviceSpecification->links);
			foreach($serviceSpecification->functions as $functionName => $functionSpec)
			{
				if(isset($functionSpec) && !is_null($functionSpec) && isset($functionSpec['visibility']) && $functionSpec['visibility'] != 'private')
				{
					$outputSpecification->functions[$functionName] = $functionSpec;
				}
			}
			return $outputSpecification;
		}
	}
	return false;
}

function get_OperationTime($asJson = false)
{
	global $operationStartTime;
	
	$operationEndTime = microtime(true);
	$operationTime = round(($operationEndTime - $operationStartTime) * 1000);
	
	if(!is_null($asJson) && $asJson == true)
	{
		return get_StatusArray('time', $operationTime);
	}
	else
	{
		return $operationTime;
	}
}

if(!function_exists('http_build_query'))
{
    function http_build_query($formdata, $numeric_prefix = null, $key = null)
	{
        $res = array();
        foreach ((array)$formdata as $k=>$v)
		{
            $tmp_key = urlencode(is_int($k) ? $numeric_prefix.$k : $k);
            if($key)
			{
                $tmp_key = $key.'['.$tmp_key.']';
            }
            if(is_array($v) || is_object($v))
			{
                $res[] = http_build_query($v, null, $tmp_key);
            }
			else 
			{
                $res[] = $tmp_key."=".urlencode($v);
            }
        }
        return implode("&", $res);
    }
}

function sendToRemoteService($url, $data = null, $waitResponse = false, $method = 'GET', $optionalHeaders = null)
{
	if(isset($url) && !is_null($url))
	{
		$params = array('http' => array(
			'method' => $method
		));
		
		if(strtolower($method) == 'post')
		{
			$params['content'] = $data;
		}
		else
		{
			$queryString = http_build_query($data);
			$url = $url . '?' . $queryString;
		}
		
		if ($optionalHeaders !== null)
		{
			$params['http']['header'] = $optionalHeaders;
		}
		
		//echo 'sending: ' . json_encode($data) . ' to: ' . $url . '<br/><br/>';
		$ctx = stream_context_create($params);
		$fp = @fopen($url, 'rb', false, $ctx);
		
		if (!$fp)
		{
			//echo 'returning-failed' . json_encode(error_get_last()) . '<br/><br/>';
			return false;
		}
		
		if($waitResponse)
		{
			$response = stream_get_contents($fp);
			
			//Check if content is JSON data
			$jsonResponse = json_decode($response);
			if($jsonResponse === null && (!function_exists('json_last_error') || json_last_error() !== JSON_ERROR_NONE))//PHP>=5.3 needed here
			{
				return $response;
			}
			else
			{
				return $jsonResponse;
			}
		}
		return true;
	}
	return false;
}

function trigger_RemoteService($triggerHost, $triggerFunc, $args = null, $allowedArgs = null, $waitResponse = false) //$args should contain the argument values (not just the names) ($allowedArgs contains the list of args to send, if null, all provided in $args are sent, if array of zero length, none are sent)
{
	if(isset($triggerHost) && !is_null($triggerHost) && isset($triggerFunc) && !is_null($triggerFunc) && is_PhpAboveOrEqualVersion('4.3.0'))
	{
		$spec = get_Specification(false);
		if(isset($spec->triggers) && !is_null($spec->triggers) &&
			isset($spec->triggers[$triggerHost]) && !is_null($spec->triggers[$triggerHost]) &&
			isset($spec->triggers[$triggerHost]['url']) && !is_null($spec->triggers[$triggerHost]['url']))
		{
			$url = $spec->triggers[$triggerHost]['url'];
			$funcAttr = $spec->triggers[$triggerHost]['call'];
			if(is_null($funcAttr))
			{
				$funcAttr = 'func';
			}
			
			$sendArgs = array($funcAttr=>$triggerFunc);
			if(isset($spec->triggers[$triggerHost]['defaults']) && !is_null($spec->triggers[$triggerHost]['defaults']) && count($spec->triggers[$triggerHost]['defaults']) > 0)
			{
				$sendArgs = array_merge($sendArgs, $spec->triggers[$triggerHost]['defaults']);
			}
			
			if(isset($args) && !is_null($args) && count($args) > 0)
			{
				if(is_null($allowedArgs))//Send all args in $args (if any)
				{
					$sendArgs = array_merge($sendArgs, $args);
				}
				else if(!is_null($allowedArgs) && count($allowedArgs) > 0) //Send only named args (where they exist)
				{
					$populatedArgs = array();
					foreach($allowedArgs as $argName)
					{
						if(isset($args[$argName]))
						{
							array_merge($populatedArgs, array($argName=>$args[$argName]));
						}
						else if(isset($args[strtolower($argName)]))
						{
							array_merge($populatedArgs, array($argName=>$args[strtolower($argName)]));
						}
					}
					if(count($populatedArgs) > 0)
					{
						$sendArgs = array_merge($sendArgs, $populatedArgs);
					}
				}
			}
			
			return sendToRemoteService($url, $sendArgs, $waitResponse);
		}
	}
}

function check_CentralRegistration()
{
	global $centralRegistration;
	
	if(isset($centralRegistration) && !is_null($centralRegistration) &&
		isset($centralRegistration->enabled) && ($centralRegistration->enabled == true) &&
		isset($centralRegistration->occurence) && !is_null($centralRegistration->occurence) && 
		isset($centralRegistration->info) && !is_null($centralRegistration->info) &&
		is_array($centralRegistration->info) && count($centralRegistration->info) > 0)
	{	
		$max = intval($centralRegistration->occurence);
		$fireChance = rand(0, $max);
		if($fireChance <= 1) //Should set a double-rate of registration (may be adjusted in future update)
		{
			$centralRegistration->info['func'] = 'registerApi';
			sendToRemoteService('http://apikit.org/api/', $centralRegistration->info, false);
		}
	}
}

function get_UsageLogEntry()
{
	$Func = ((isset($_REQUEST['func'])) ? strtolower($_REQUEST['func']) : null);
	$UserId = ((isset($_SESSION['UserId'])) ? $_SESSION['UserId'] : ((isset($_SESSION['WorkerId'])) ? $_SESSION['WorkerId'] : null));
	$Referrer = ((isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : null);
	$Agent = ((isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : null);
	$IpAddress = ((isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : null);
	$Duration = get_OperationTime();
	
	$usageEntry = array('Func'=>$Func, 'UserId'=>$UserId, 'Referrer'=>$Referrer, 'Agent'=>$Agent, 'IpAddress'=>$IpAddress, 'Duration'=>$Duration);
	return $usageEntry;
}

function put_UsageLogEntry($usageLogEntry = null)
{
	global $logSettings;
	if(isset($logSettings) && !is_null($logSettings) && is_object($logSettings) && isset($logSettings->enabled) && $logSettings->enabled)
	{
		if(is_null($usageLogEntry))
		{
			$usageLogEntry = get_UsageLogEntry();
		}
		if(!is_null($usageLogEntry))
		{
			pn_ExecuteSql($logSettings->procName, $usageLogEntry, true, false, null, $logSettings->sqlProfile);
		}
	}
}
?>