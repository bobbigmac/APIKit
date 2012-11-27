if(!com) var com={};
if(!com.apikit) com.apikit={};

com.apikit.serviceApi = {
	url: null,
	timeoutTimer: 30000, 
	json: null, 
	
	log: function(message)
	{
		alert('pnApi message: ' + message);
	}, 
	registerLogger: function(logFunc)
	{
		if(typeof(logFunc) == 'function')
		{
			com.apikit.serviceApi.log = logFunc;
			return true;
		}
		else
		{
			com.apikit.serviceApi.log = function(message){return null;};
			return false;
		}
	},
	init: function(apiUrl, timeout, logFunc)
	{
		if(apiUrl)
		{
			com.apikit.serviceApi.url = apiUrl;
		}
		if(timeout !== undefined && timeout !== null)
		{
			timeoutTimer = timeout;
		}
		if(logFunc)
		{
			com.apikit.serviceApi.registerLogger(logFunc);
		}
	},
	
	/* UTILITY */
	getRequestObject: function()
	{
		if(window.jXHR)
		{
			try { return new jXHR(); } catch (e0) {};
		}
		try { return new XMLHttpRequest(); } catch(e1) {}
		try { return new ActiveXObject("Msxml2.XMLHTTP"); } catch (e2) {}
		try { return new ActiveXObject("Microsoft.XMLHTTP"); } catch (e3) {}
		if(com.apikit.serviceApi.log)
		{
			com.apikit.serviceApi.log("XMLHttpRequest not supported");
		}
		return null;
	}, 
	handleError: function(msg)
	{
		if(msg && (typeof(msg) == "object") && msg.responseText)
		{
			msg = msg.responseText;
		}
		if(com.apikit.serviceApi.log)
		{
			com.apikit.serviceApi.log('Error when communicating with apikit server: ' + ((msg) ? msg : 'no server message'));
		}
	}, 
	checkStatusOk: function(resObj)
	{
		if(resObj && (!resObj.status || (resObj.status && resObj.status != "error")))
		{
			return true;
		}
		com.apikit.serviceApi.handleError((resObj.error) ? resObj.error : "Network or unknown Error, please check your network/internet connection.");
		return false;
	}, 
	parse: function(resString)
	{
		try
		{
			if(typeof(resString) == 'string')
			{
				if(!com.apikit.serviceApi.json)
				{
					com.apikit.serviceApi.json = JSON;
				}
				if(resString && (typeof(resString)) == "string" && resString.length > 0)
				{
					var resObj = ((com.apikit.serviceApi.json.decode) ? com.apikit.serviceApi.json.decode(resString) : com.apikit.serviceApi.json.parse(resString));
					if(com.apikit.serviceApi.checkStatusOk(resObj))
					{
						return resObj;
					}
				}
			}
			else if(typeof(resString) == 'object')
			{
				var resObj = resString;
				if(com.apikit.serviceApi.checkStatusOk(resObj))
				{
					return resObj;
				}
			}
		} catch(err) 
		{ 
			if(com.apikit.serviceApi.log) 
			{ 
				com.apikit.serviceApi.log('Error occurred when parsing JSON from api: ' + err);
			}
		}
		return null;
	},
	sendRequest: function(functionName, params, handler, userHandler, subApiPage) 
	{
		try
		{
			var req = this.getRequestObject();
			if(req && functionName)
			{
				var url = com.apikit.serviceApi.url;
				if(url && typeof(url) == 'string' && url.length > 0)
				{
					if(subApiPage)
					{
						url = url + '/' + subApiPage;
					}
					if(params)
					{
						params.func = functionName;
					}
					else
					{
						params = { func: functionName };
					}
					req.parameters = params;
					req.queryString = '';
					
					for(var i in req.parameters)
					{
						var thisParameter = req.parameters[i];
						if((typeof(thisParameter)) != "function")
						{
							if((typeof(thisParameter)) != "string")
							{
								if(!com.apikit.serviceApi.json)
								{
									com.apikit.serviceApi.json = JSON;
								}
								thisParameter = com.apikit.serviceApi.json.stringify(thisParameter);
							}
							if(req.queryString.length>0)
							{
								req.queryString +="&";
							}
							req.queryString += encodeURIComponent(i) + "=" + encodeURIComponent(thisParameter);
						}
					}
					
					if(req.queryString.length>0)
					{
						url += ((url.indexOf("?")>-1)?"&":"?") + req.queryString;
					}
					
					if(window.jXHR)
					{
						url += ((url.indexOf("?")>-1)?"&":"?") + 'callback=?';
					}
					
					req.handlerFunc = handler;
					req.userHandler = userHandler;
					req.onreadystatechange = function(req)
					{
						if (this.readyState === 4) 
						{
							if(window.jXHR && this.handlerFunc)
							{
								this.handlerFunc(req, this.parameters, false, this.userHandler);
							}
							else if (this.status == 200)
							{
								if(this.handlerFunc)
								{
									this.handlerFunc(this.responseText, this.parameters, false, this.userHandler);
								}
							} else {
								var statusText = null;
								try { //Catches an exception thrown when the service was unavailable.
									statusText = this.statusText;
								} catch(err) { 
									if(com.apikit.serviceApi.log) 
									{ 
										com.apikit.serviceApi.log('Error occurred in-transport. status: ' + this.status + ' error: ' + err);
									}
								}
								this.handlerFunc(statusText, this.parameters, true, this.userHandler);
							}
						}
					};
					req.open("POST", url, true); //Sends POST requests so proxies don't cache the result of previous queries
					req.send();
				}
				else
				{
					com.apikit.serviceApi.log('Error communicating with API, the API url was not set.');
				}
			}
			else
			{
				if(com.apikit.serviceApi.log)
				{
					com.apikit.serviceApi.log('Error communicating with API, a function to call was not specified.');
				}
			}
		} catch(err) 
		{
			if(com.apikit.serviceApi.log) 
			{ 
				com.apikit.serviceApi.log('Error occurred when sending request to api: ' + err);
			}
		}
	}, 
	handleGeneric: function(res, parameters, errored, userHandler)
	{
		var resObj = com.apikit.serviceApi.parse(res);
		if(resObj)
		{
			if(userHandler)
			{
				userHandler(resObj, parameters, errored);
			}
		}
	}, 
		
	//* SPECIFICATION */
	handleGetSpecification: function(res, parameters, errored, userHandler)
	{
		var resObj = com.apikit.serviceApi.parse(res);
		if(!errored && resObj)
		{
			if(userHandler)
			{
				userHandler(resObj, parameters, errored);
			}
		}
		else
		{
			if(com.apikit.serviceApi.log)
			{
				com.apikit.serviceApi.log("getSpecification failed. res: " + res + ' errored: ' + errored);
			}
			if(userHandler)
			{
				userHandler(null, parameters, true);
			}
		}
	}, 
	getSpecification: function(userHandler)
	{
		com.apikit.serviceApi.sendRequest('getSpecification', null, com.apikit.serviceApi.handleGetSpecification, userHandler);
	}
};