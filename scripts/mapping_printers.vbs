Option Explicit
'On Error Resume Next

Dim wshShell, strEngine, verbose, scriptname, failure_retry_interval, failure_monitor_interval, monitor_interval, site, takeaction, removeunmanagedqueues, osname, expectedmappingcount

removeunmanagedqueues = True
takeaction = True
scriptname = Wscript.ScriptName

Set wshShell = CreateObject( "WScript.Shell" )

strEngine = UCase( Right( WScript.FullName, 12 ) )

verbose = False
failure_retry_interval = 30000
failure_monitor_interval = 300000

If strEngine = "\CSCRIPT.EXE" Then
	verbose = True
End If

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
	Dim WshShell, clientnamekey, strComputer, objRegistry, dwValue, strKeyPath

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
		writeOutput(Replace("Error: " & Err.Number & " " & Err.Description,vbLf,""))
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
	
	For i = 0 to equeues.Count - 1 Step 1
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
	
	For i = 0 to bqueues.Count - 1 Step 1
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
	Dim WshNetwork, existprinters, i, queues, splitqueue, servername, queuename

	Set WshNetwork = WScript.CreateObject("WScript.Network")

	Set existprinters = WshNetwork.EnumPrinterConnections
	
	Set queues = CreateObject( "System.Collections.ArrayList" )
	
	Set WshNetwork = Nothing
	
	'Set getExistingPrinters = queues
	'Exit Function

	For i = 0 to existprinters.Count - 1 Step 1
		If Left(ucase(existprinters.Item(i)),2) = "\\" Then
			'Set queue = CreateObject("Scripting.Dictionary")
			'splitqueue = Split(existprinters.Item(i), "\")
			'servername = splitqueue(2)
			'queuename = splitqueue(3)
			'queue.Add "server", servername
			'queue.Add "queue", queuename
			queues.Add existprinters.Item(i)
		End If
	Next
	
	Set existprinters = Nothing
	
	Set getExistingPrinters = queues
		
End Function

Function checkManagedQueue(bqueues, equeue)
	Dim i, eparsed, bparsed, eserver, eq, bserver, bq
	
	eparsed = parseQueue(equeue)
	eserver = eparsed(0)
	eq = eparsed(1)
	
	For i = 0 to bqueues.Count - 1 Step 1
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

Function checkDuplicateQueue(index, equeues)
	Dim i, qname
	
	qname = UCase(equeues(i).Item("queue"))
	
	For i = 0 to equeues.Count - 1 Step 1
		If i = index Then
			'ignore
		Else
			If qname = UCase(equeues(i)) Then
				checkDuplicateQueue = True
				Exit Function
			End If
		End If
	Next
	
	checkDuplicateQueue = False
End Function

Function removeBadQueues(onlineservers, bqueues, equeues)
	Dim i, WshNetwork, path, remaining, delete, parsed, server, queue
	
	Set WshNetwork = WScript.CreateObject("WScript.Network")
	Set remaining = CreateObject( "System.Collections.ArrayList" )

	For i = 0 to equeues.Count - 1 Step 1
		delete = False
		parsed = parseQueue(equeues(i))
		server = parsed(0)
		queue = parsed(1)
		path = "\\" & server & "\" & queue
		If checkManagedQueue(bqueues, equeues(i)) = False Then
			If removeunmanagedqueues = True Then
				writeOutput("Removing unmanaged queue " & path)
				delete = True
			Else
				writeOutput("Skipping removal of unmanaged queue " & path & " because removeunmanagedqueues = False")
			End If
		Else
			If onlineservers.Contains(UCase(server)) Then
				writeOutput(server & " is still online for " & queue)
			Else
				writeOutput(server & " was not found in the online server list for " & path)
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
			End If
		Else
			remaining.Add equeues(i)
		End If
	Next
	
	Set WshNetwork = Nothing
	
	Set removeBadQueues = remaining
End Function

Function httpRequest(url)
	Dim restReq, response, status
	Set restReq = CreateObject("Microsoft.XMLHTTP")

	On Error Resume Next
	Err.Clear
	restReq.open "GET", url, false
	'restReq.setRequestHeader "Content-Type", "application/json"
	restReq.send
	If Err Then
		writeOutput(Replace("Error: " & Err.Number & " " & Err.Description,vbLf,""))
		httpRequest = false
		Exit Function
	End If
	On Error GoTo 0
	
	writeOutput("Successful http response from " & url)
	
	status = restReq.status
	response = restReq.responseText
	
	Set restReq = Nothing

	If status <> 200 Then
		writeOutput("Received a http " & status & " from the server: " & Replace(response, vbLf, ""))
		httpRequest = false
	Else
		httpRequest = response
	End If
End Function

Function checkMappedQueues(onlineservers, equeues)
	Dim i, remaining, path, parsed, server, queue
	
	For i = 0 to equeues.Count - 1 Step 1
		parsed = parseQueue(equeues(i))
		server = parsed(0)
		queue = parsed(1)
		If onlineservers.Contains(UCase(server)) Then
			writeOutput(server & " is still online for " & queue)
		Else
			path = "\\" & server & "\" & queue
			writeOutput(server & " was not found in the online server list for " & path)
			writeOutput("Requesting queues to be remapped because at least one was found on an inactive server")
			checkMappedQueues = true
			Exit Function
		End If
	Next
	
	checkMappedQueues = false
End Function

Function mapQueues()
	Dim username, computername, success
	
	success = false
	monitor_interval = false
	
	username = getEnvVariable("USERNAME", true)
	computername = getCitrixHostname
	
	while success = false
		Dim xml, objXMLDoc, Root, mappingelem, print_mappings, mapping, mappings, prop, props, sources, active_servers, activeserver, activeservers, active_server_count, servercount, print_mapping_count, mappingcount, ep, rq, dqueue, WshNetwork, server, queue, defq, path, dq, monitor, monitor_interval
	
		xml = httpRequest("https://printermappings.lcmchealth.org/api/mappings/" & computername & "/" & username & "?format=xml")

		If xml = false Then
			writeOutput("HTTP request failure. Trying again in " & failure_retry_interval / 1000 & " seconds")
			WScript.Sleep failure_retry_interval
		Else
			'make sure workstations has .NET
			On Error Resume Next
			Err.Clear
			Set mappings = CreateObject( "System.Collections.ArrayList" )
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
					Set activeservers = CreateObject( "System.Collections.ArrayList" )
					Set active_servers = Root.getElementsByTagName("active_servers")
					'WScript.Echo len(NodeList)
					For Each activeserver In active_servers(0).childNodes
						'WScript.Echo activeserver.text
						activeservers.Add UCase(activeserver.text)
					Next
					Set print_mapping_count = Root.getElementsByTagName("print_mapping_count")
					mappingcount = Clng(print_mapping_count(0).text)
					expectedmappingcount = mappingcount
					If mappingcount < 1 Then
						writeOutput("No mappings were found using the supplied parameters")
					Else
						Set print_mappings = Root.getElementsByTagName("print_mappings")
						'WScript.Echo len(NodeList)
						defq = false
						For Each mappingelem In print_mappings(0).childNodes
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
							mappings.Add path
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
						If mappings.count > 0 Then
						
							'get existing mappings
							Set ep = getExistingPrinters
							
							Set rq = removeBadQueues(activeservers, mappings, ep)
							
							dq = mapMissingQueues(mappings, rq, dqueue)
							
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
									writeOutput(Replace("Error: " & Err.Number & " " & Err.Description,vbLf,""))
								End If
								On Error GoTo 0
								setDefaultPrinter(dq)
							End If
							
							Set ep = Nothing
							Set rq = Nothing
							Set WshNetwork = Nothing
							
							'WScript.Sleep 30000
							'WshNetwork.SetDefaultPrinter dq
							'setDefaultPrinter(dq)
						End If
					End If
				End If
				Set Root = Nothing
				Set mappings = Nothing
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
	Dim activeservers, active_servers, Root, objXMLDoc, servercount, active_server_count, activeserver, ep, remap, xml, monitor_interval, kill_active_monitors
		
	monitor_interval = failure_monitor_interval
	xml = httpRequest("https://printermappings.lcmchealth.org/api/activeservers?format=xml")
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
				Set activeservers = CreateObject( "System.Collections.ArrayList" )
				Set active_servers = Root.getElementsByTagName("active_servers")

				Set Root = Nothing

				'WScript.Echo len(NodeList)
				For Each activeserver In active_servers(0).childNodes
					'WScript.Echo activeserver.text
					activeservers.Add UCase(activeserver.text)
				Next
				
				Set active_servers = Nothing
			
				Set ep = getExistingPrinters
				
				'this may happen if a workstation is brokered a print server that is offline and all queues fail to map
				If ep.count = 0 And expectedmappingcount > 0 Then
					writeOutput("Requesting queues to be remapped because " & ep.count & " queues were found and expected mapping count is " & expectedmappingcount)
					remap = true
				Else
					remap = checkMappedQueues(activeservers, ep)
				End If
					
				Set activeservers = Nothing
				Set ep = Nothing
							
				If remap = false Then
					writeOutput("Mapped queues are all on active servers")
				Else
					mapQueues
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
		If workstationLocked Then
			writeOutput("Workstation is locked. Skipping server status check for " & monitor_interval / 1000 & " seconds")
			WScript.Sleep Clng(monitor_interval)
		Else
			monitor_interval = checkServers
			If monitor_interval = false Then
				writeOutput("kill_active_monitors is set to true, the script will now terminate")
				Exit Do
			Else
				writeOutput("Monitoring is enabled, checking active servers in " & monitor_interval / 1000 & " seconds")
				WScript.Sleep Clng(monitor_interval)
			End If
		End If
	Loop
End Function

Function checkAlreadyRunning
	Dim objWMIService, objProcess, colProcess
	Dim strComputer, strList
	strComputer = "."
	Set objWMIService = GetObject("winmgmts:{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
	Set colProcess = objWMIService.ExecQuery ("Select * from Win32_Process WHERE (Name = 'wscript.exe' OR Name = 'cscript.exe') AND CommandLine LIKE '%" & scriptname & "%'")
	'WScript.Echo colProcess.count
	If colProcess.count > 1 Then
		checkAlreadyRunning = true
		Exit Function
	End If
	checkAlreadyRunning = false
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
If InStr(UCase(osname), "SERVER") < 1 Then
	site = getADSite
	If strInArray(site, Array("TOURO", "NOEH", "UMC", "")) Then
		monitor_interval = mapQueues
		If monitor_interval = false Then
			writeOutput("Active server monitoring is disabled, the script will terminate now")
		Else
			If checkAlreadyRunning = false Then
				writeOutput("Monitoring is enabled, checking active servers in " & monitor_interval / 1000 & " seconds")
				WScript.Sleep Clng(monitor_interval)
				serverMonitor
			Else
				writeOutput("The script will terminate now because another copy of the process is already running")
			End If
		End If
	Else
		writeOutput("Terminating because site " & site & " was not found in the enabled site list")
	End If
Else
	writeOutput("Terminating because OS ("& osname &") is a server.")
End If