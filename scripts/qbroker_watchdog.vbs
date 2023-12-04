Option Explicit
'On Error Resume Next

Dim running, fso, strCmd, wshShell, script
script = "\\lcmchealth.org\netlogon\Scripts\Policy Scripts\Logon\_main.vbs"

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

Function findExisting
	Dim objWMIService, objProcess, colProcess
	Dim strComputer, strList
	strComputer = "."
	Set objWMIService = GetObject("winmgmts:{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
	Set colProcess = objWMIService.ExecQuery ("Select * from Win32_Process WHERE (Name = 'wscript.exe' OR Name = 'cscript.exe') AND (CommandLine LIKE '%map_printers%' OR CommandLine LIKE '%_main%')")
	'If colProcess.count > 5 Then
	'	writeOutput(colProcess.count & " duplicate processes were found running. This should never happen. Terminating the process...")
	'	logEvent 1, colProcess.count & " duplicate processes were found running. This should never happen. Terminating the process..."
	'	WScript.Quit
	'Else
		For Each objProcess in colProcess
			'only delete the matching processes that aren't the currently running process
			'WScript.Echo InStr(objProcess.CommandLine, "/id:" & scriptid)
			If InStr(objProcess.CommandLine, "/user:" & getEnvVariable("USERNAME", true)) >= 1 Then
				'writeOutput("A duplicate process was found. Killing it...")
				'objProcess.Terminate()
				findExisting = True
				Set objWMIService = Nothing
				Set colProcess = Nothing
				Exit Function
			End If
		Next
	'End If
	findExisting = False
	Set objWMIService = Nothing
	Set colProcess = Nothing
End Function

running = findExisting

If findExisting = False Then
	Set fso = CreateObject("Scripting.FileSystemObject")
	While True
		If (fso.FileExists(script)) Then
			strCmd = "WSCRIPT.EXE //B """ & script & """"
			Set wshShell = CreateObject( "WScript.Shell" )
			wshShell.Run strCmd
			WScript.Quit
		Else
			'WScript.Echo("File does not exist!")
		End If
		WScript.Sleep 300000
	Wend
End If