Option Explicit
'On Error Resume Next

Dim disablevdi, disableserveros, qbrokerserver, NamedArgs, Arg, scriptid, scriptuser, delay, delaycalculated, wshShell, strEngine, verbose, scriptname, locked_workstation_interval, failure_retry_interval, failure_monitor_interval, monitor_interval, site, takeaction, removeunmanagedqueues, osname, expectedmappingcount, returnedmappingcount, computername

Set NamedArgs = WScript.Arguments.Named
disableserveros = True
disablevdi = True

If NamedArgs.Exists("qbrokerserver") Then
	qbrokerserver = NamedArgs.Item("qbrokerserver")
Else
	qbrokerserver = "qbroker.lcmchealth.org"
End If

If NamedArgs.Exists("disableserveros") Then
	If UCase(NamedArgs.Item("disableserveros")) = "FALSE" Then
		disableserveros = False
	End If
End If

If NamedArgs.Exists("disablevdi") Then
	If UCase(NamedArgs.Item("disablevdi")) = "FALSE" Then
		disablevdi = False
	End If
End If

'For Each Arg In NamedArgs
'	WScript.Echo Arg & " = " & NamedArgs.Item(Arg)
'Next

removeunmanagedqueues = True
takeaction = True
scriptname = Wscript.ScriptName

Set wshShell = CreateObject( "WScript.Shell" )

strEngine = UCase( Right( WScript.FullName, 11 ) )

verbose = False
failure_retry_interval = 30000
failure_monitor_interval = 300000
locked_workstation_interval = 60000

If NamedArgs.Exists("delay") Then
	delay = CInt(NamedArgs.Item("delay"))
Else
	delay = 10
End If

delaycalculated = delay * 1000

If NamedArgs.Exists("id") And NamedArgs.Exists("user") Then
	'WScript.Echo "ID exists"
Else
	Dim strArgs, strCmd
	For Each Arg In NamedArgs
		strArgs = strArgs & " /" & Arg & ":" & NamedArgs.Item(Arg)
	Next
	If strEngine = "CSCRIPT.EXE" Then
		strCmd = "CSCRIPT.EXE //NoLogo """ & WScript.ScriptFullName & """ /id:" & RandomString(20) & " /user:" & getEnvVariable("USERNAME", true) & " /delay:" & CStr(delay) & " /qbrokerserver:" & qbrokerserver & " /disableserveros:" & CStr(disableserveros) & " /disablevdi:" & CStr(disablevdi)
	Else
		strCmd = "WSCRIPT.EXE """ & WScript.ScriptFullName & """ /id:" & RandomString(20) & " /user:" & getEnvVariable("USERNAME", true) & " /delay:" & CStr(delay) & " /qbrokerserver:" & qbrokerserver & " /disableserveros:" & CStr(disableserveros) & " /disablevdi:" & CStr(disablevdi)
	End If
	wshShell.Run strCmd
	'WScript.Echo strCmd
	'WScript.Echo strEngine
	'WScript.Echo strArgs
	'WScript.Echo RandomString(20)
	WScript.Quit
End If

scriptid = NamedArgs.Item("id")
scriptuser = NamedArgs.Item("user")

Set NamedArgs = Nothing

If strEngine = "CSCRIPT.EXE" Then
	verbose = True
End If

Function RandomString( ByVal strLen )
    Dim str, min, max, i

    Const LETTERS = "abcdefghijklmnopqrstuvwxyz0123456789"
    min = 1
    max = Len(LETTERS)

    Randomize
    For i = 1 to strLen
        str = str & Mid( LETTERS, Int((max-min+1)*Rnd+min), 1 )
    Next
    RandomString = str
End Function

Function getADSite()
	Dim objADSysInfo
	
	Set objADSysInfo = CreateObject("ADSystemInfo")

	getADSite = objADSysInfo.SiteName
End Function

Function getEnvVariable(envvar, wait)
	Dim objShell, wshSystemEnv, value, loopcount
	
	value=""
	loopcount=0

	Do while value = "" AND loopcount < 45
		Set objShell = WScript.CreateObject("WScript.Shell")
		Set wshSystemEnv = objShell.Environment( "PROCESS" ) 'PROCESS,SYSTEM,USER, OR VOLATILE
		value = wshSystemEnv( envvar )
		loopcount = loopcount+1
		If value = "" Then
			WScript.Sleep(1000)
		End If
		If Not wait Then
			exit do
		End If
	Loop
	
	Set objShell = Nothing
	Set wshSystemEnv = Nothing

	'getEnvVariable = vbTab & """" + envvar + """:""" + value + """"
	getEnvVariable = UCase(value)
End Function

Function getCitrixHostname()
	Dim clientnamekey, strComputer, objRegistry, dwValue, strKeyPath

	Const HKEY_LOCAL_MACHINE = &H80000002

	strComputer = "."

	Set objRegistry = GetObject("winmgmts:\\" & strComputer & "\root\default:StdRegProv")

	strKeyPath = "SOFTWARE\Citrix\ICA\Session\"

	objRegistry.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,"ClientName",dwValue
	
	Set objRegistry = Nothing

	If IsNull(dwValue) Then

		'Wscript.Echo "The registry key does not exist."
		'getCitrixHostname = False
		getCitrixHostname = getEnvVariable("COMPUTERNAME", true)

	Else
		If disablevdi = True Then
			writeOutput("Terminating because disablevdi is true and this is a VDI desktop")
			WshShell.LogEvent 2, "Terminating because disablevdi is true and this is a VDI desktop"
			WScript.Quit
		End If
		getCitrixHostname = UCase(dwValue)

	End If

End Function

Function setDefaultPrinter(printer)
	Dim strComputer, objWMIService, printerquery, colInstalledPrinters, objPrinter

	strComputer = "." 
	Set objWMIService = GetObject("winmgmts:" _ 
	& "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")

	printerquery = "Select * from Win32_Printer Where Name = '" & Replace(printer,"\","\\") & "'"
 
	Set colInstalledPrinters =  objWMIService.ExecQuery(printerquery) 
 
	On Error Resume Next
	Err.Clear
	For Each objPrinter in colInstalledPrinters
		'WScript.Echo objPrinter.Name 
    		objPrinter.SetDefaultPrinter() 
	Next
	If Err Then
		writeOutput(Replace("Error: " & Err.Number & " Failed to set default printer because " & LCase(Err.Description),vbLf,""))
		WshShell.LogEvent 1, Replace("Error: " & Err.Number & " Failed to set default printer because " & LCase(Err.Description),vbLf,"")
	End If
	On Error GoTo 0
	
	Set objWMIService = Nothing
	Set colInstalledPrinters = Nothing
	
End Function

Function getOSName()
	Dim strComputer, objWMIService, colItems, objItem, osname

	strComputer = "." 
	Set objWMIService = GetObject("winmgmts:" _ 
	& "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
 
	Set colItems =  objWMIService.ExecQuery("Select * from Win32_OperatingSystem",,48) 
 
	'Msgbox colItems.ItemIndex(0).Caption
	For Each objItem in colItems
		osname = objItem.Caption
		exit for
	Next
	
	Set objWMIService = Nothing
	Set colItems = Nothing
	
	getOSName = osname
	
End Function

Function queueExists(bqueue, equeues)
	Dim i, eqname, parsed, server, queue, bparsed, bserver, bq
	
	'bqname = UCase(bqueue.Item("queue"))
	
	bparsed = parseQueue(bqueue)
	bserver = bparsed(0)
	bq = bparsed(1)
	
	For i = 0 to UBound(equeues) Step 1
		parsed = parseQueue(equeues(i))
		server = parsed(0)
		queue = parsed(1)
		eqname = UCase(queue)
		If LCase(bq) = LCase(eqname) Then
			queueExists = "\\" & server & "\" & queue
			Exit Function
		End If
	Next
	
	queueExists = False

End Function

Function mapMissingQueues(bqueues, equeues, dqueue)
	Dim WshNetwork, i, dq, path, exists, bparsed, bserver, bq
	
	Set WshNetwork = WScript.CreateObject("WScript.Network")
	
	For i = 0 to UBound(bqueues) Step 1
		bparsed = parseQueue(bqueues(i))
		bserver = bparsed(0)
		bq = bparsed(1)
		path = "\\" & bserver & "\" & bq
		exists = queueExists(bqueues(i), equeues)
		If exists = False Then
			writeOutput("Mapping brokered queue " & path)
			If takeaction = True Then
				On Error Resume Next
				Err.Clear
				WshNetwork.AddWindowsPrinterConnection path
				If Err Then
					writeOutput(Replace("Error: " & Err.Number & " " & Err.Description,vbLf,""))
					WshShell.LogEvent 1, Replace("Error: " & Err.Number & " " & Err.Description & "Failed to map " & path,vbLf,"")
					writeOutput("Decreasing expected mapping count by 1 because the queue failed to map")
					expectedmappingcount = expectedmappingcount - 1
					'WScript.Echo "-2147023095"
					'WScript.Echo "0" & CStr(Err.Number) & "0"
					'If Str("" & Err.Number & "") = Str("-2147023095") Then
					'	writeOutput("Decreasing expectedmappingcount by 1 because error number " + Err.Number + " usually means the queue name does not exist")
					'	expectedmappingcount = expectedmappingcount - 1
					'End If
				End If
				On Error GoTo 0
			End If
			If LCase(bq) = LCase(dqueue) Then
				dq = path
			End If
		Else
			' queue already exists
			If LCase(bq) = LCase(dqueue) Then
				dq = exists
			End If
		End If
	Next
	
	Set WshNetwork = Nothing
	
	mapMissingQueues = dq

End Function

Function getExistingPrinters()
	Dim WshNetwork, existprinters, i, queues, splitqueue, servername, queuename, j
	ReDim queues(-1)

	Set WshNetwork = WScript.CreateObject("WScript.Network")

	Set existprinters = WshNetwork.EnumPrinterConnections
	
	'Set queues = CreateObject( "System.Collections.ArrayList" )
	
	Set WshNetwork = Nothing
	
	'Set getExistingPrinters = queues
	'Exit Function
	j = 0
	For i = 0 to existprinters.Count - 1 Step 1
		If Left(ucase(existprinters.Item(i)),2) = "\\" Then
			'Set queue = CreateObject("Scripting.Dictionary")
			'splitqueue = Split(existprinters.Item(i), "\")
			'servername = splitqueue(2)
			'queuename = splitqueue(3)
			'queue.Add "server", servername
			'queue.Add "queue", queuename
			ReDim Preserve queues(UBound(queues) + 1)
			queues(j) = existprinters.Item(i)
			j = j + 1
			'queues.Add existprinters.Item(i)
		End If
	Next
	
	Set existprinters = Nothing
	
	getExistingPrinters = queues
		
End Function

Function checkManagedQueue(bqueues, equeue)
	Dim i, eparsed, bparsed, eserver, eq, bserver, bq
	
	eparsed = parseQueue(equeue)
	eserver = eparsed(0)
	eq = eparsed(1)
	
	For i = 0 to UBound(bqueues) Step 1
		bparsed = parseQueue(bqueues(i))
		bserver = bparsed(0)
		bq = bparsed(1)
		If LCase(bq) = LCase(eq) Then
			checkManagedQueue = True
			Exit Function
		Else
			
		End If
	Next
	checkManagedQueue = False
End Function

Function checkDuplicateQueue(index, qname, equeues)
	Dim i, parsed, server, queue
	
	qname = UCase(qname)
	
	For i = 0 to UBound(equeues) Step 1
		If i = index Then
			'ignore
		Else
			parsed = parseQueue(equeues(i))
			server = parsed(0)
			queue = parsed(1)
			If qname = UCase(queue) Then
				checkDuplicateQueue = True
				Exit Function
			End If
		End If
	Next
	
	checkDuplicateQueue = False
End Function

Function removeIndexFromArray(index, array)
	Dim newarray, i, j

	ReDim newarray(-1)
	'Set remaining = CreateObject( "System.Collections.ArrayList" )
	
	j = 0
	For i = 0 to UBound(array) Step 1
		If i <> index Then
			ReDim Preserve newarray(UBound(newarray) + 1)
			newarray(j) = array(i)
			j = j + 1
		End If
	Next
	
	removeIndexFromArray = newarray
	
End Function

Function removeDuplicateQueues(equeues)
	Dim i, j, WshNetwork, path, parsed, server, queue, remaining, updatedarray
	
	Set WshNetwork = WScript.CreateObject("WScript.Network")

	i = 0
	While i < UBound(equeues) + 1
		parsed = parseQueue(equeues(i))
		server = parsed(0)
		queue = parsed(1)
		path = "\\" & server & "\" & queue
		If checkDuplicateQueue(i, queue, equeues) = True Then
			writeOutput(path & " will be removed because duplicate detection found another queue mapped with the same name")
			WshShell.LogEvent 2, path & " will be removed because duplicate detection found another queue mapped with the same name"
			If takeaction = True Then
				WSHNetwork.RemovePrinterConnection path, true, true
				'equeues.RemoveAt(i)
				'WScript.Echo Join(equeues, ",")
				updatedarray = removeIndexFromArray(i, equeues)
				ReDim equeues(UBound(updatedarray))
				For j = 0 to UBound(updatedarray) Step 1
					equeues(j) = updatedarray(j)
				Next
				Erase updatedarray
				'WScript.Echo Join(equeues, ",")
			End If
		Else
			i = i + 1
			'WScript.Echo queue & " is not a duplicate"
		End If
	Wend
	
	Set WshNetwork = Nothing
	
	removeDuplicateQueues = equeues
End Function

Function removeBadQueues(onlineservers, bqueues, equeues)
	Dim i, j, WshNetwork, path, remaining, delete, parsed, server, queue
	
	Set WshNetwork = WScript.CreateObject("WScript.Network")
	ReDim remaining(-1)
	'Set remaining = CreateObject( "System.Collections.ArrayList" )

	j = 0
	For i = 0 to UBound(equeues) Step 1
		delete = False
		parsed = parseQueue(equeues(i))
		server = parsed(0)
		queue = parsed(1)
		path = "\\" & server & "\" & queue
		If checkManagedQueue(bqueues, equeues(i)) = False Then
			If removeunmanagedqueues = True Then
				writeOutput("Removing unmanaged queue " & path)
				WshShell.LogEvent 2, path & " will be removed because no mapping was found for it"
				delete = True
			Else
				writeOutput("Skipping removal of unmanaged queue " & path & " because removeunmanagedqueues = False")
			End If
		Else
			If strInArray(UCase(server), onlineservers) Then
				writeOutput(server & " is still online for " & queue)
			Else
				writeOutput(server & " was not found in the online server list for " & path)
				WshShell.LogEvent 2, path & " will be removed because " & server & " was not found in the online server list"
				delete = True
			End If
		End If
		
		If delete = True Then
			If takeaction = True Then
				On Error Resume Next
				Err.Clear
				WSHNetwork.RemovePrinterConnection path, true, true
				If Err Then
					'if removal fails, printer might be stuck, try adding again and then removing by uncommenting below
					writeOutput(Replace("Error: " & Err.Number & " " & Err.Description,vbLf,""))
					'WSHNetwork.AddWindowsPrinterConnection path
					'WSHNetwork.RemovePrinterConnection path, true, true
				End If
				On Error GoTo 0
				'wait one second after each queue removal, attempting to prevent failed removals
				WScript.Sleep 1000
			End If
		Else
			ReDim Preserve remaining(UBound(remaining) + 1)
			remaining(j) = equeues(i)
			j = j + 1
			'remaining.Add equeues(i)
		End If
	Next
	
	Set WshNetwork = Nothing
	
	removeBadQueues = remaining
End Function

Function httpRequest(url)
	Dim restReq, response, status
	Set restReq = CreateObject("Microsoft.XMLHTTP")

	On Error Resume Next
	Err.Clear
	restReq.open "GET", url, false
	restReq.setRequestHeader "Accept", "application/xml"
	restReq.setRequestHeader "Connection", "close"
	restReq.send
	If Err Then
		writeOutput(Replace("Error: " & Err.Number & " " & Err.Description,vbLf,""))
		WshShell.LogEvent 1, "Failed to make a connection to the qbroker server " & qbrokerserver & ":" & vbCrlf & vbCrlf & Replace(Err.Description, vbLf, "")
		httpRequest = false
		Exit Function
	End If
	On Error GoTo 0
	
	writeOutput("Successful http response from " & url)
	
	status = restReq.status
	response = restReq.responseText
	
	Set restReq = Nothing

	If status <> 200 Then
		writeOutput("Received a http " & status & " from the qbroker server " & qbrokerserver & ": " & Replace(response, vbLf, ""))
		WshShell.LogEvent 1, "Received a http " & status & " from the qbroker server " & qbrokerserver & ":" & vbCrlf & vbCrlf & Replace(response, vbLf, "")
		httpRequest = false
	Else
		httpRequest = response
	End If
End Function

Function checkMappedQueues(onlineservers, equeues)
	Dim i, remaining, path, parsed, server, queue
	
	For i = 0 to UBound(equeues) Step 1
		parsed = parseQueue(equeues(i))
		server = parsed(0)
		queue = parsed(1)
		If strInArray(UCase(server), onlineservers) = True Then
			writeOutput(server & " is still online for " & queue)
		Else
			path = "\\" & server & "\" & queue
			writeOutput(server & " was not found in the online server list for " & path)
			writeOutput("Requesting queues to be remapped because at least one was found on an inactive server")
			WshShell.LogEvent 2, server & " was not found in the online server list for " & path & ". Requesting queues to be remapped because at least one was found on an inactive server."
			checkMappedQueues = true
			Exit Function
		End If
	Next
	
	checkMappedQueues = false
End Function

Function mapQueues(computername)
	Dim username, success
	
	success = false
	monitor_interval = false
	
	username = getEnvVariable("USERNAME", true)
	
	while success = false
		Dim url, xml, objXMLDoc, Root, mappingelem, print_mappings, mapping, mappings, prop, props, sources, active_servers, activeserver, activeservers, active_server_count, servercount, print_mapping_count, mappingcount, ep, rbq, rq, dqueue, WshNetwork, server, queue, defq, path, dq, monitor, monitor_interval, i
	
		ReDim mappings(-1)
		ReDim activeservers(-1)
	
		url = "https://" & qbrokerserver & "/api/mappings/" & computername & "/" & username
	
		xml = httpRequest(url)

		If xml = false Then
			writeOutput("HTTP request failure. Trying again in " & failure_retry_interval / 1000 & " seconds")
			WScript.Sleep failure_retry_interval
		Else
			'make sure workstations has .NET
			On Error Resume Next
			Err.Clear
			'Set mappings = CreateObject( "System.Collections.ArrayList" )
			If Err Then
				writeOutput("Workstation does not have .NET installed. Quitting...")
				WScript.Quit
			End If
			On Error GoTo 0
			Set objXMLDoc = CreateObject("Microsoft.XMLDOM")
			'objXMLDoc.async = False 
			objXMLDoc.loadXML(xml)

			Set Root = objXMLDoc.documentElement
			
			Set objXMLDoc = Nothing
			
			On Error Resume Next
			Err.Clear
			Set active_server_count = Root.getElementsByTagName("active_server_count")
			If Err Then
				Root = Nothing
				On Error GoTo 0
				writeOutput("XML could not be parsed from http response. Trying again in " & failure_retry_interval / 1000 & " seconds")
				WScript.Sleep failure_retry_interval
			Else
				On Error GoTo 0
				success = true
				servercount = Clng(active_server_count(0).text)
				
				Set active_server_count = Nothing
				
				If servercount < 1 Then
					writeOutput("No active print servers were found")
				Else
					monitor = getXMLTextValue(Root, "monitor")
					'Set monitor = Root.getElementsByTagName("monitor")
					If monitor = "1" Then
						monitor_interval = getXMLTextValue(Root, "monitor_interval")
					Else
						monitor_interval = false
					End If
					'Set activeservers = CreateObject( "System.Collections.ArrayList" )
					Set active_servers = Root.getElementsByTagName("active_servers")
					'WScript.Echo len(NodeList)
					i = 0
					For Each activeserver In active_servers(0).childNodes
						'WScript.Echo activeserver.text
						ReDim Preserve activeservers(UBound(activeservers) + 1)
						activeservers(i) = UCase(activeserver.text)
						i = i + 1
						'activeservers.Add UCase(activeserver.text)
					Next
					Set print_mapping_count = Root.getElementsByTagName("print_mapping_count")
					mappingcount = Clng(print_mapping_count(0).text)
					expectedmappingcount = mappingcount
					returnedmappingcount = mappingcount
					If mappingcount < 1 Then
						WshShell.LogEvent 2, "No mappings were found using the supplied parameters in request to " & url
						writeOutput("No mappings were found using the supplied parameters")
					Else
						'WScript.Echo UBound(mappings)
						Set print_mappings = Root.getElementsByTagName("print_mappings")
						'WScript.Echo len(NodeList)
						defq = false
						i = 0
						For Each mappingelem In print_mappings(0).childNodes
							ReDim Preserve mappings(UBound(mappings) + 1)
							'Set sources = CreateObject( "System.Collections.ArrayList" )
							'Set mapping = CreateObject("Scripting.Dictionary")
							'mapping.Add "source",sources
							'WScript.Echo "Port " & port & " has IP address of " & mappingelem.text
							'WScript.Echo mappingelem.nodeName
							For Each prop In mappingelem.childNodes
								'If mapping.Exists(prop.nodeName) Then
								'	mapping.Item(prop.nodeName) = mapping.Item(prop.nodeName) & "," & prop.text
								'Else
								'	mapping.Add prop.nodeName,prop.text
								'End If
								If prop.nodeName = "server" Then
									server = prop.text
								ElseIf prop.nodeName = "queue" Then
									queue = prop.text
								ElseIf prop.nodeName = "default" Then
									If prop.text = "1" Then
										defq = true
									Else
										defq = false
									End If
								Else
									'unnecessary property
								End If
							Next
							path = "\\" & LCase(server) & "\" & LCase(queue)
							'WScript.Echo i
							mappings(i) = path
							i = i + 1
							If defq = true Then
								dqueue = queue
							End If
							'Set mapping = Nothing
						Next
						
						'Dim map
						
						'For Each map in mappings
						'	WScript.Echo map
						'Next
						
						'WScript.Echo dqueue
						'if we have mappings
						'WScript.Echo UBound(mappings)
						'WScript.Echo Join(mappings, ",")
						If UBound(mappings) >= 0 Then
						
							'get existing mappings
							ep = getExistingPrinters
							'WScript.Echo UBound(ep)
							
							rbq = removeBadQueues(activeservers, mappings, ep)
							
							rq = removeDuplicateQueues(rbq)
							
							Erase rbq
							
							WshShell.LogEvent 4, "The following printers will be mapped per the response from " & url & ": " & vbCrlf & vbCrlf & Join(mappings, vbCrlf)
							
							dq = mapMissingQueues(mappings, rq, dqueue)
							
							Erase ep
							Erase rq
							
							If IsEmpty(dq) Then
								writeOutput("No default printer was specified")
								WshShell.LogEvent 2, "No default printer was specified."
							Else
								writeOutput("Setting default printer to " & dq)						
								'For Each mapping In mappings
									'WScript.Echo "\\" & mapping.Item("server") & "\" & mapping.Item("queue") & "\" & mapping.Item("default")
								'Next
								
								Set WshNetwork = WScript.CreateObject("WScript.Network")
								
								WScript.Sleep 3000
								If takeaction = True Then
									On Error Resume Next
									Err.Clear
									WshNetwork.SetDefaultPrinter dq
									If Err Then
										writeOutput(Replace("Error: " & Err.Number & " Failed to set default printer because " & LCase(Err.Description),vbLf,""))
										WshShell.LogEvent 1, Replace("Error: " & Err.Number & " Failed to set default printer because " & LCase(Err.Description),vbLf,"")
									End If
									On Error GoTo 0
									setDefaultPrinter(dq)
									
									WScript.Sleep 10000
									On Error Resume Next
									Err.Clear
									WshNetwork.SetDefaultPrinter dq
									If Err Then
										writeOutput(Replace("Error: " & Err.Number & " Failed to set default printer because " & LCase(Err.Description),vbLf,""))
										WshShell.LogEvent 1, Replace("Error: " & Err.Number & " Failed to set default printer because " & LCase(Err.Description),vbLf,"")
									End If
									On Error GoTo 0
									setDefaultPrinter(dq)
								End If
								
								Set WshNetwork = Nothing
							End If
						End If
					End If
				End If
				Set Root = Nothing
				Erase mappings
			End If
		End If
	Wend
	
	mapQueues = monitor_interval
End Function

Function parseQueue(path)
	Dim splitqueue, servername, queuename, parsed

	splitqueue = Split(path, "\")
	servername = splitqueue(2)
	queuename = splitqueue(3)
	
	parsed = Array(servername, queuename)
	
	parseQueue = parsed

End Function

Function writeOutput(message)
	If verbose Then
		WScript.Echo FormatDateTime(Now) & " " & message
	End If
End Function

Function getXMLTextValue(xmlroot, tagname)
	Dim elem, value
	
	'WScript.Echo tagname
	
	Set elem = xmlroot.getElementsByTagName(tagname)
	
	value = elem(0).text
	
	Set elem = Nothing
	
	getXMLTextValue = value
	
End Function

Function checkServers
	Dim activeservers, active_servers, Root, objXMLDoc, servercount, active_server_count, activeserver, ep, remap, xml, monitor_interval, kill_active_monitors, i
	
	ReDim activeservers(-1)
	
	monitor_interval = failure_monitor_interval
	xml = httpRequest("https://" & qbrokerserver & "/api/activeservers")
	If xml = false Then
		writeOutput("http request failure")
	Else
		Set objXMLDoc = CreateObject("Microsoft.XMLDOM")
		'objXMLDoc.async = False 
		objXMLDoc.loadXML(xml)
		'WScript.Echo xml

		Set Root = objXMLDoc.documentElement
		
		Set objXMLDoc = Nothing
		
		On Error Resume Next
		Err.Clear
		Set active_server_count = Root.getElementsByTagName("active_server_count")
		If Err Then
			writeOutput("XML could not be parsed from http response.")
		Else
			On Error GoTo 0
			servercount = Clng(active_server_count(0).text)
			
			Set active_server_count = Nothing

			If servercount < 1 Then
				writeOutput("No active print servers were found")
			Else
				kill_active_monitors = getXMLTextValue(Root, "kill_active_monitors")
				'Set kill_active_monitors = Root.getElementsByTagName("kill_active_monitors")
				If kill_active_monitors = "1" Then
					monitor_interval = false
				Else
					monitor_interval = getXMLTextValue(Root, "monitor_interval")
				End If
				'Set activeservers = CreateObject( "System.Collections.ArrayList" )
				Set active_servers = Root.getElementsByTagName("active_servers")

				Set Root = Nothing

				'WScript.Echo len(NodeList)
				i = 0
				For Each activeserver In active_servers(0).childNodes
					'WScript.Echo activeserver.text
					'activeservers.Add UCase(activeserver.text)
					ReDim Preserve activeservers(UBound(activeservers) + 1)
					activeservers(i) = UCase(activeserver.text)
					i = i + 1
				Next
				
				Set active_servers = Nothing
			
				ep = getExistingPrinters
				
				'this may happen if a workstation is brokered a print server that is offline and all queues fail to map or a queue was manually deleted
				'WScript.Echo UBound(ep)
				'If UBound(ep) + 1 = 0 Then
				If UBound(ep) + 1 = 0 And returnedmappingcount > 0 Then
				'If expectedmappingcount = 0 or ep.count <> expectedmappingcount Then
					writeOutput("Requesting queues to be remapped because " & UBound(ep) + 1 & " queues were found and returned mapping count is " & returnedmappingcount)
					remap = true
				Else
					remap = checkMappedQueues(activeservers, ep)
				End If
					
				Erase activeservers
				Erase ep
							
				If remap = false Then
					writeOutput("Mapped queues are all on active servers")
				Else
					mapQueues(computername)
				End If	
			End If
		End If
	End If
	
	checkServers = monitor_interval

End Function

Function serverMonitor
	Dim monitor_interval
	
	monitor_interval = true

	Do While true
		'If workstationLocked Then
		'	writeOutput("Workstation is locked. Skipping server status check for " & locked_workstation_interval / 1000 & " seconds")
		'	WScript.Sleep Clng(locked_workstation_interval)
		'Else
			monitor_interval = checkServers
			If monitor_interval = false Then
				writeOutput("kill_active_monitors is set to true, the script will now terminate")
				WshShell.LogEvent 2, "kill_active_monitors is set to true, the script will now terminate"
				Exit Do
			Else
				writeOutput("Monitoring is enabled, checking active servers in " & monitor_interval / 1000 & " seconds")
				WScript.Sleep Clng(monitor_interval)
			End If
		'End If
	Loop
End Function

Function checkAlreadyRunning
	Dim objWMIService, colProcess
	Dim strComputer, strList
	strComputer = "."
	Set objWMIService = GetObject("winmgmts:{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
	Set colProcess = objWMIService.ExecQuery ("Select * from Win32_Process WHERE (Name = 'wscript.exe' OR Name = 'cscript.exe') AND CommandLine LIKE '%" & scriptname & "%'")
	'WScript.Echo colProcess.count
	If colProcess.count > 1 Then
		checkAlreadyRunning = true
		Exit Function
	End If
	
	Set objWMIService = Nothing
	Set colProcess = Nothing
	
	checkAlreadyRunning = false
End Function

Function killExisting
	Dim objWMIService, objProcess, colProcess
	Dim strComputer, strList
	strComputer = "."
	Set objWMIService = GetObject("winmgmts:{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
	Set colProcess = objWMIService.ExecQuery ("Select * from Win32_Process WHERE (Name = 'wscript.exe' OR Name = 'cscript.exe') AND CommandLine LIKE '%" & scriptname & "%'")
	If colProcess.count > 5 Then
		writeOutput(colProcess.count & " duplicate processes were found running. This should never happen. Terminating the process...")
		WshShell.LogEvent 1, colProcess.count & " duplicate processes were found running. This should never happen. Terminating the process..."
		WScript.Quit
	Else
		For Each objProcess in colProcess
			'only delete the matching processes that aren't the currently running process
			'WScript.Echo InStr(objProcess.CommandLine, "/id:" & scriptid)
			If InStr(objProcess.CommandLine, "/id:" & scriptid) < 1 Then
				If InStr(objProcess.CommandLine, "/user:" & scriptuser) >= 1 Then
					writeOutput("A duplicate process was found. Killing it...")
					objProcess.Terminate()
				End If
				'Exit Function
			End If
		Next
	End If
	
	Set objWMIService = Nothing
	Set colProcess = Nothing
End Function

Function strInArray(str, arr)
	Dim arrstr

	For Each arrstr In arr
		If UCase(str) = UCase(arrstr) Then
			strInArray = true
			Exit Function
		End If
	Next
	strInArray = false
End Function

Function workstationLocked()
	Dim strComputer, objWMIService, logonScreenCount

	strComputer = "." 
	
    Set objWMIService = GetObject("winmgmts://" & strComputer & "/root/cimv2")
    logonScreenCount = objWMIService.ExecQuery ("SELECT * FROM Win32_Process WHERE Name = 'LogonUI.exe'").Count
 
	Set objWMIService = Nothing
 
    workstationLocked = (logonScreenCount > 0)
End Function

osname = getOSName
computername = getCitrixHostname
If InStr(UCase(osname), "SERVER") >= 1 and disableserveros = True Then
	writeOutput("Terminating because OS ("& osname &") is a server.")
	WshShell.LogEvent 2, "Terminating because OS ("& osname &") is a server."
Else
	'site = getADSite
	'If strInArray(site, Array("TOURO", "NOEH", "UMC", "", "EPIC")) Then
		If delay <> 0 Then
			writeOutput("Delaying " & delay & " second(s) before starting...")
			WScript.Sleep delaycalculated
		End If
		killExisting
		monitor_interval = mapQueues(computername)
		If monitor_interval = false Then
			writeOutput("Active server monitoring is disabled, the script will terminate now")
			WshShell.LogEvent 2, "Active server monitoring is disabled, the script will terminate now"
		Else
			'If checkAlreadyRunning = false Then
				writeOutput("Monitoring is enabled, checking active servers in " & monitor_interval / 1000 & " seconds")
				WshShell.LogEvent 4, "Monitoring is enabled, checking active servers in " & monitor_interval / 1000 & " seconds"
				WScript.Sleep Clng(monitor_interval)
				serverMonitor
			'Else
			'	writeOutput("The script will terminate now because another copy of the process is already running")
			'End If
		End If
	'Else
	'	writeOutput("Terminating because site " & site & " was not found in the enabled site list")
	'End If
End If