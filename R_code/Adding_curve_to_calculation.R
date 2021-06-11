##-----------------------------------------------------------------------------------------------
# Created by: Margot Vore 
# May 2021
# 
# This code is used when a salt wave needs to be added or removed  from the average discharge calculations.
# When a new event is added using the "Add New Event" code, it chooses which sensors to use in calculating 
# discharge based on how many flag codes exist for a given wave. If 2 or more flags exist, the code will exclude that 
# salt wave from calculations. If the user, when reviewing the newly added events, wants to remove (add)
# one of the curves that was included (excluded) in calculations, this code will be used. The code will prompt you
# for the event, site, probe number, and action you would like to take, recalculates the discharge average and mixing
# percentage, and updates the database
#
# This code enters data into the following database tables:
# Autosalt_Summary
# All_Discharge_Calc
#
# Abbreviations:
# EC --> Electrical Conductivity
# CF  -->  Calibration Factor



##-----------------------------------------------------------------------------------------------
## ---------------------------Setting up the workspace------------------------------------------
##-----------------------------------------------------------------------------------------------


readRenviron('C:/Program Files/R/R-3.6.2/.Renviron')
options(java.parameters = c("-XX:+UseConcMarkSweepGC", "-Xmx8192m"))

library(DBI)
library(dplyr)
source("AutoSalt_Functions.R")

con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))
drive_auth(email=Sys.getenv('email_gdrive'))

##-------------------------------------------------------------------------------------------------------
##------------------Prompts to define what event you are altering-----------------------------------------
##---------------------------------------------------------------------------------------------------------
EventID= as.numeric(readline(prompt='EventID where start/stop times are changed: '))
SiteID= as.numeric(readline(prompt='SiteID where start/stop times are changed: '))
Change= as.character(readline(prompt='Are you adding or removing a wave:'))
Sensor= as.character(readline(prompt='What is the SensorID of the wave are you adding/removing:'))


##----------------------------------------------------------------------------------------------------------
##---------------- Extracts and adds needed data---------------------------------------------------------------------
##----------------------------------------------------------------------------------------------------------

# Pulls data for event that is being edited
Query <- sprintf("SELECT * FROM chrl.all_discharge_calcs WHERE SiteID=%i AND EventID=%i",SiteID, EventID)
Event_to_edit <- dbGetQuery(con, Query)

# Changes flagging code to Y/N depending on if a wave is being added or removed
if (Change=='adding' | Change=='Adding'){
  Event_to_edit[Event_to_edit$sensorid==Sensor,'used']='Y'
} else if (Change=='removing' | Change =='Removing'){
  Event_to_edit[Event_to_edit$sensorid==Sensor,'used']='N'
}

# Get calibration EventID for mixing percentage calcuations
for (CFID in Event_to_edit$cfid){
  Query= sprintf("SELECT * FROM chrl.calibration_results WHERE CalResultsID=%i",CFID)
  CalibrationInfo <- dbGetQuery(con, Query) 
  CalEventID= CalibrationInfo$caleventid
  Event_to_edit[Event_to_edit$cfid==CFID,'CalEventID']= CalEventID
} 

##-------------------------------------------------------------------------------------------------------------
##------------------------------------------ Redo discharge results--------------------------------------------
##-------------------------------------------------------------------------------------------------------------
# Rename column headers 
Discharge_Results= Event_to_edit 
Discharge_Results = Discharge_Results %>%
  rename(Discharge=discharge) %>%
  rename(Err=uncertainty)%>%
  rename(SensorID=sensorid)

# Calculate the error of each discharge result
Discharge_Results <- Discharge_Results[which(Discharge_Results$Discharge <100 & is.na(Discharge_Results$Discharge)==FALSE & Discharge_Results$used=="Y")  ,]
Discharge_Results$AbsErr <- Discharge_Results$Discharge*(Discharge_Results$Err/100)
Discharge_Results$QP <- Discharge_Results$Discharge+Discharge_Results$AbsErr
Discharge_Results$QM <- Discharge_Results$Discharge-Discharge_Results$AbsErr

Max_Q <- max(Discharge_Results[,'QP'],na.rm=TRUE)
Min_Q <- min(Discharge_Results[,'QM'],na.rm=TRUE)

# Get new discharge and uncertainty of the event
Average_Discharge <- mean(Discharge_Results[,'Discharge'], na.rm=TRUE)
TotalUncert <-  max(((Max_Q-Average_Discharge)/Average_Discharge*100),((Average_Discharge-Min_Q)/Average_Discharge*100))  

# Determine the mixing
Mixing <- AutoSalt_Mixing(Discharge_Results)


##-------------------------------------------------------------------------------------------------------------
##------------------------------- Update the database----------------------------------------------------------
##------------------------------------------------------------------------------------------------------------

# Update the all discharge calculation table
for (x in c(1:nrow(Discharge_Results))){
  Query= sprintf("UPDATE chrl.all_discharge_calcs SET used='%s' WHERE dischargeid=%s",
                 Discharge_Results[x,'used'],
                 Discharge_Results[x,'dischargeid'])
  Query <- gsub("\\n\\s+", " ", Query)
  Query <- gsub('NA',"NULL", Query)
  Query <- gsub("'NULL'","NULL",Query)
  dbSendQuery(con, Query)
}

# Update the autosalt summary table 
Query=sprintf("UPDATE chrl.autosalt_summary SET discharge_avg=%s, uncert=%s,mixing=%s WHERE SiteID=%s AND EventID=%s",
              Average_Discharge, TotalUncert,Mixing,SiteID,EventID)
Query <- gsub("\\n\\s+", " ", Query)
Query <- gsub('NA',"NULL", Query)
Query <- gsub("'NULL'","NULL",Query)
dbSendQuery(con, Query)

