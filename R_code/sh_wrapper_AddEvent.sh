#!/bin/bash

setsid Rscript /home/autosalt/AutoSaltDilution/R_code/Add_New_Events.R >> /home/autosalt/AutoSaltDilution/logs/DumpEvent_Logs/NewDumpEvents-`date +\%Y-\%m`.log 2>&1

CODE=$?
if [ $CODE -ne 0  ]
then
	/usr/sbin/sendmail autosalt.alerts@hakai.org </home/autosalt/AutoSaltDilution/other/Email_Add_New_Event.txt
fi 

