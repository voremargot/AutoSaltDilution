#!/bin/bash

setsid Rscript /home/autosalt/AutoSaltDilution/R_code/Wrapper_device_magic_updates.R >> /home/autosalt/AutoSaltDilution/logs/FieldVisit_Logs/FieldEvents-`date +\%Y-\%m`.log 2>&1

CODE=$?
if [ $CODE -ne 0  ]
then
        sendmail margot.vore@viu.ca </home/autosalt/AutoSaltDilution/other/Email_Field_Visits.txt
fi

