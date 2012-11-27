<?php
/*
##	Name: apiKit
##	File: util_sql.php
##	Description: This file should contain utilities for SQL operations.
##
##	Author: Robert Davies (admin@bobbigmac.com)
##	Website: http://www.apiKit.org
##
*/
class sqlsvr_object {
	function __construct($array_variable, $outArray)
	{
		$JavascriptDateFormat = 'D M d Y H:i:s O';
		if (!is_array($array_variable)) { return false; }

		$my_count = 0;
		foreach($array_variable as $my_key => $my_value) {
			$my_count++;
			if (!$my_key) {
				$my_key = $my_count;
			}
			if(is_object($my_value))
			{
				if(!$outArray)
				{
					$this->$my_key = $my_value->format($JavascriptDateFormat);
				}
				else
				{
					$this->outArray[$my_key] = $my_value->format($JavascriptDateFormat);
				}
			}
			else 
			{
				if(!$outArray)
				{
					$this->$my_key = $my_value; 
				}
				else
				{
					$this->outArray[$my_key] = $my_value;
				}
			}
		}
	}
}

function pn_fetch_result($stmt, $serverType = 'mysql', $returnArray = false)
{
	if(!$stmt) { return false; }
	
	if($serverType == "mssql")
	{
		$return_value = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		if (!$return_value) { return $return_value; }
		
		$filteredResults = new sqlsvr_object($return_value, $returnArray);
	}
	else if($serverType == "mysql")
	{
		$return_value = mysql_fetch_array($stmt, MYSQL_ASSOC);
		if (!$return_value) { return $return_value; }
		
		$filteredResults = new sqlsvr_object($return_value, $returnArray);
	}
	
	if($returnArray)
	{
		return $filteredResults->outArray;
	}
	else
	{
		return $filteredResults;
	}
}

function pn_OpenConnection($source = null)
{
	global $sqlProfiles;
	$conn = null;
	
	if(isset($sqlProfiles) && !is_null($sqlProfiles) && is_array($sqlProfiles))
	{
		$serverType = null;
		$serverName = null;
		$username = null;
		$password = null;
		$database = null;
		
		if(is_null($source))
		{
			$source = 'primary';
		}
		
		if(isset($sqlProfiles[$source]) && !is_null($sqlProfiles[$source]) && is_object($sqlProfiles[$source]))
		{
			$serverType = $sqlProfiles[$source]->type;
			$serverName = $sqlProfiles[$source]->path;
			$username =  $sqlProfiles[$source]->username;
			$password =  $sqlProfiles[$source]->password;
			$database =  $sqlProfiles[$source]->database;
		}
		
		if(!is_null($serverName) && !is_null($serverType) && !is_null($username) && !is_null($password) && !is_null($database))
		{
			if($serverType == 'mssql')
			{
				$connectionInfo = array( 'UID'=>$username,
										 'PWD'=>$password,
										 'Database'=>$database);
				/* Connect using SQL Server Authentication. */
				$conn = sqlsrv_connect( $serverName, $connectionInfo);
			}
			elseif($serverType == 'mysql')
			{
				$conn = mysql_connect($serverName, $username, $password, false, 65536);//CLIENT_MULTI_STATEMENTS);
				mysql_select_db($database, $conn);
			}
			
			if($conn === false)
			{
				 return null;
			}
		}
	}
	
	return $conn;
}

function pn_ExecuteSql($queryString, $parameters = null, $isSproc = true, $keepOpen = false, $connConnection = null, $dbSource = null)
{
	global $sqlProfiles;
	$serverType = null;
	if(is_null($dbSource))
	{
		$dbSource = 'primary';
	}
	
	if(isset($sqlProfiles[$dbSource]) && !is_null($sqlProfiles[$dbSource]) && is_object($sqlProfiles[$dbSource]))
	{
		$serverType = $sqlProfiles[$dbSource]->type;
		
		$allRows = null;
		if(is_null($connConnection))
		{
			$connConnection = pn_OpenConnection($dbSource);
			if(is_null($connConnection))
			{
				$errorMsg = null;
				if($serverType == "mssql")
				{
					$errorMsg = sqlsrv_errors();
				}
				else if($serverType == "mysql")
				{
					$errorMsg = mysql_error();
				}
				
				$allRows = array('status'=>'error', 
									'error'=>'Unable to connect to database.', 
									'trace'=>$errorMsg);
				return $allRows;
			}
		}
		
		//support parameters and escaping of said magical little wonders
		$paramString = '';
		if(!is_null($parameters) && count($parameters) > 0)
		{
			if($isSproc)
			{
				if($serverType == "mysql")
				{
					$paramString .= '(';
				}
				$paramCount = count($parameters);
				$paramCountTrack = 0;
				foreach($parameters as $param_key => $param_value) 
				{
					if($serverType == "mssql")
					{
						$paramString .= ((startsWith($param_key, '@')) ? ' ' : ' @') . $param_key . ' = ?';
					}
					else if($serverType == "mysql")
					{
						$paramString .= "'" . mysql_real_escape_string($param_value) . "'";
					}
					
					$paramCountTrack++;
					if($paramCountTrack != $paramCount)
					{
						$paramString .= ',';
					}
				}
				if($serverType == "mysql")
				{
					$paramString .= ')';
				}
			}
			else if($serverType == "mysql") //Escape queryString for mysql (mssql is performed by the driver)
			{
				foreach ($parameters as &$param_value)
				{
					$param_value = mysql_real_escape_string($param_value);
				}
				$queryString = vsprintf(str_replace('?',"'%s'",$queryString), $parameters);
			}
		}
		
		$stmt = null;
		if($serverType == "mssql")
		{
			if($isSproc)
			{
				$queryString = 'EXEC ' . $queryString . (((!is_null($parameters) && count($parameters) > 0)) ? ' ' . $paramString . ' ' : '');
			}
			$stmt = sqlsrv_query($connConnection, $queryString, $parameters);
		}
		else if($serverType == "mysql")
		{
			if($isSproc)
			{
				$queryString = 'CALL ' . $queryString . ((!is_null($parameters) && count($parameters) > 0 && strlen($paramString) > 0) ? ' ' . $paramString . ' ' : '');
			}
			$stmt = mysql_query($queryString, $connConnection);
		}
		
		if( $stmt === false )
		{
			$errorMsg = null;
			if($serverType == "mssql")
			{
				$errorMsg = sqlsrv_errors();
			}
			else if($serverType == "mysql")
			{
				$errorMsg = mysql_error();
			}
			$allRows = array('status'=>'error', 
								'error'=>'Error in executing query.', 
								'trace'=>$errorMsg);
			$stmt = null;
		}
		
		if($stmt !== true) //No resultset (insert/delete operation) //TODO: Could support returning status-ok
		{
			/* Retrieve the results of the query. */
			if(is_null($allRows) && !is_null($stmt))
			{
				do
				{
					$row = pn_fetch_result($stmt, $serverType);
					
					if(!is_null($row) && $row !== false)
					{
						$allRows[count($allRows)] = $row;
					}
				} while($row);
				
				if(is_null($allRows))
				{
					$allRows = (object)array();
				}
			}
			
			/* Free statement and connection resources. */
			if(!is_null($stmt))
			{
				if($serverType == "mssql")
				{
					sqlsrv_free_stmt($stmt);
				}
				else if($serverType == "mysql")
				{
					mysql_free_result($stmt);
				}
			}
		}
		
		if(!$keepOpen && !is_null($connConnection))
		{
			if($serverType == "mssql")
			{
				sqlsrv_close($connConnection);
			}
			else if($serverType == "mysql")
			{
				mysql_close($connConnection);
			}
		}
		
		return $allRows;
	}
	return array('status'=>'error', 'error'=>'Error in executing query. Configured or requested sqlProfile could not be found.');
}
?>