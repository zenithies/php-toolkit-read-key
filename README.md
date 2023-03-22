# Zenithies php-toolkit-read-key

Detecting key press (arrow keys) in command line (console, cli) environment in PHP under Windows and Unix

## Windows requirements

Note, at the moment it's available only on x64 version of windows and was tested on Windows 10 with PHP 8.1.x and 8.2.x TS

* PHP COM extension required: extension=php_com_dotnet
* You have to register DLL file from bin/win/ReadKey, using `regsvr32 ZenithiesCLIKeys_x64.dll` from command line with administrator permissions. 
* For your own convenience put the `ZenithiesCLIKeys_x64.dll` on your system drive.