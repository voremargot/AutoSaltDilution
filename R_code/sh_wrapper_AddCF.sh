#!/bin/bash

setsid Rscript /home/autosalt/AutoSaltDilution/R_code/Adding_New_CF_Event.R >> /home/autosalt/AutoSaltDilution/logs/CF_Logs/CF_Events-`date +\%Y-\%m`.log 2>&1

CODE=$?
if [ $CODE -ne 0 ]
then
        sendmail margot.vore@viu.ca </home/autosalt/AutoSaltDilution/other/Email_Add_CF_Events.txt
fi


