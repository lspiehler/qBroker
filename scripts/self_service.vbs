Option Explicit
'On Error Resume Next

Dim hostname, objShell, username

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

		getCitrixHostname = UCase(dwValue)

	End If

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

hostname = getCitrixHostname
username = getEnvVariable("USERNAME", true)

Set objShell = CreateObject("WScript.Shell")
objShell.Run "https://printermappings.lcmchealth.org/selfservice?computername=" & hostname