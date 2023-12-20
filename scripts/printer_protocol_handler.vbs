Option Explicit
'On Error Resume Next

Dim objShellApp, uri, fso, dir, strEngine, queues, i, splitqueue, formatqueue, qbrokerserver, forceremap, wshShell, strCmd

qbrokerserver = "qbroker.lcmchealth.org"

uri = Split(WScript.Arguments(0), "/")

'WScript.Echo uri(0)

If uri(0) = "printer:" Then
	If uri(2) = "update" Then
		Set fso = CreateObject("Scripting.FileSystemObject")
		dir = fso.GetParentFolderName(WScript.ScriptFullName)
		'WScript.Echo dir
		forceremap = False
		Set wshShell = CreateObject( "WScript.Shell" )
		strEngine = UCase( Right( WScript.FullName, 11 ) )
		If strEngine = "CSCRIPT.EXE" Then
			strCmd = "CSCRIPT.EXE //NoLogo """ & dir & "\adhoc_interactive_map_printers.vbs" & """ /delay:0 /qbrokerserver:" & qbrokerserver & " /forceremap:" & CStr(forceremap)
		Else
			strCmd = "WSCRIPT.EXE """ & dir & "\adhoc_interactive_map_printers.vbs" & """ /delay:0 /qbrokerserver:" & qbrokerserver & " /forceremap:" & CStr(forceremap)
		End If
		wshShell.Run strCmd
		'WScript.Echo "update"
	Else
		Set objShellApp = CreateObject("Shell.Application")
		queues = Split(uri(2),";")
		For i = 0 to UBound(queues) Step 1
			'WScript.Echo queues(i)
			splitqueue = Split(queues(i), "@")
			'WScript.Echo uri(2)

			formatqueue = "\\" & splitqueue(1) & "\" & splitqueue(0)
			'WScript.Echo formatqueue
			objShellApp.ShellExecute "rundll32", "printui.dll,PrintUIEntry /in /n" & formatqueue

			'WScript.Echo "printui.dll,PrintUIEntry " & formatqueue
		Next
	End If
Else

End If