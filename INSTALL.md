/*
##
##	Name: apiKit
##	File: service.php
##	Description: A framework for easily creating a RESTful JSON/XML API to your data
##
##	Author: Robert Davies (admin@bobbigmac.com)
##	Website: https://github.com/bobbigmac/APIKit
##
##	Support: Email me at admin@bobbigmac.com or visit https://github.com/bobbigmac/APIKit
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
##			a JSON or XML based API to your existing infrastructure.
##		Part of this is in allowing developers and companies to work together well,
##			by providing the tools to get systems talking to each other without the need
##			to spend much money developing new systems from scratch.
##		The apiKit should be simple to add to existing systems, and present a
##			minimal impact on existing resources.
##		The underlying ideology is represented best by Tim Berners-Lee TED Talk
##			presentation at http://www.RawDataNow.com
##
##
##	INSTALLATION
##	
##	To install, copy to a directory on your php-enabled web-server.
##	
##	To configure, edit: ./config/settings.php
##	
##	1) Complete the sqlProfiles to match your database(s)
##	
##	2) Set your logSettings, loginSettings and centralRegistration preferences.
##		Detailed information for each setting is in the settings source.
##	
##	3) If you wish to access SQL Server (2005 or above) you need to add the Microsoft SQL 
##		Server (2005) driver to your php extensions and enable in php.ini
##	
##	4) The next step is to fill out your specification in ./config/specification.php
##		The specifcation schema is included in the source page.
##	
##	5) Once you have a function specified you should add it's implementation to ./config/service_funcs.php
##		For most data-related tasks you will need to validate the existence of required fields, then execute your SQL.
##		
##		The primary method for executing SQL commands (using stored procedure is recommended,
##		but not essential) is the pn_ExecuteSql() function. This accepts the following parameters:
##	
##		pn_ExecuteSql (
##			$queryString, 					The sql query string or stored procedure name.
##														if providing a query, parameters should use the paramName = ? format
##			$parameters = null, 		The values provided as an array to parameters will be escaped 
##														and sent to proc or replace ? in query in the order provided.
##			$isSproc = true, 				Specifies whether to EXEC/CALL a proc, or to parse the sql query string
##			$keepOpen = false, 		Will not close the database connection if true
##			$connConnection = null, 	Will use this connection if provided
##			$dbSource = null				Specifies the database source (if null the 'primary' entry will be used)
##		)
##	
##	Find out more or ask questions on at https://github.com/bobbigmac/APIKit
##	
*/