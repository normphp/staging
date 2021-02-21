::
:: @Title: {{Title}}
:: @Author: {{Author}}
:: @Date:   2020-12-12
:: @Last Modified by: {{ModifiedBy}}
:: @Last Modified time: {{ModifiedTime}}
::
@echo OFF
:: in case DelayedExpansion is on and a path contains ! 
setlocal DISABLEDELAYEDEXPANSION
::cd "%~dp0"
"{{phpPath}}" "%~dp0index_cli.php" --route /normphp-helper-tool/cli  %*
