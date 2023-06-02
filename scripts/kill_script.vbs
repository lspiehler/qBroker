Option Explicit
'On Error Resume Next

Function killPrintMappingScript(scriptname)
	Dim objWMIService, objProcess, colProcess
	Dim strComputer, strList
	strComputer = "."
	Set objWMIService = GetObject("winmgmts:{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
	Set colProcess = objWMIService.ExecQuery ("Select * from Win32_Process WHERE (Name = 'wscript.exe' OR Name = 'cscript.exe') AND CommandLine LIKE '%" & scriptname & "%'")
	'Set colProcess = objWMIService.ExecQuery ("Select * from Win32_Process WHERE (Name = 'wscript.exe' OR Name = 'cscript.exe')")
	'WScript.Echo colProcess.count
	For Each objProcess in colProcess
		objProcess.Terminate
		Exit Function
	Next
End Function

killPrintMappingScript "map_printers.vbs"