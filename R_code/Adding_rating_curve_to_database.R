##-----------------------------------------------------------------------------------------------
# Created by: Margot Vore 
# May 2021
# 
# This code is used to record which discharge events have been included in a given rating curve. 
# After the rating curve is created and the CSV file has been updated with all the relavent information about 
# the curve, the CSV file  will be read into the database, documenting which events were included and the 
# rain event number that was assigned to the event during the RC process.
#
# This code enters data into the following database tables:
# RC autosalt
# RC Manual
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
library(curl)
library(tidyverse)

con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

##-------------------------------------------------------------------------------------------------
##--------------------- Extracting relavent data from CSV file-------------------------------------
##-------------------------------------------------------------------------------------------------
Site=as.numeric(readline(prompt='SiteID that the new rating curve is for: '))
Rating_Curve_Version=as.numeric(readline(prompt="The new rating curve's version number: "))

RC= read.csv(sprintf('working_directory/Metadata_RC_%s_V%s.csv',Site,Rating_Curve_Version))

query= sprintf("SELECT * FROM chrl.RC_summary WHERE SiteID=%s AND Version=%s",Site, (Rating_Curve_Version))
Current_RC_version= dbGetQuery(con, query)
RCID= Current_RC_version$rcid

##-----------------------------------------------------------------------------------------------
##------------------------Determining which autosalt events were included in  rating curve------
##-----------------------------------------------------------------------------------------------
RC_autosalt= RC[which(is.na(RC$EventID)==FALSE & RC$Final_rating_curve=='Y') ,]

AS_DF= data.frame()
for (r in c(1:nrow(RC_autosalt))){
  A= data.frame(SiteID= RC_autosalt[r,'SiteID'],EventID=RC_autosalt[r,'EventID'],RCID= RCID,EventNo= RC_autosalt[r,'Event_no'])
  AS_DF= rbind(AS_DF,A)
}

##-----------------------------------------------------------------------------------------------
##------------------------Determining which manual events were included in  rating curve--------
##-----------------------------------------------------------------------------------------------

RC_manual= RC[which(is.na(RC$MID)==FALSE & RC$Final_rating_curve=='Y') ,]
M_DF= data.frame()
for (r in c(1:nrow(RC_manual))){
  A= data.frame(SiteID= RC_manual[r,'SiteID'],MDisID=RC_manual[r,'MID'],RCID= RCID,EventNo= RC_manual[r,'Event_no'])
  M_DF= rbind(M_DF,A)
}


##--------------------------------------------------------------------------------------------------------
##------------- Insert data into the database--------------------------------------------------------------
##---------------------------------------------------------------------------------------------------------

for (r in c(1:nrow(AS_DF))){
  query= (sprintf('INSERT INTO chrl.rcautosalt (SiteID,EventID,RCID,EventNo) VALUES (%s,%s,%s,%s)',
                AS_DF[r,'SiteID'],AS_DF[r,'EventID'],AS_DF[r,'RCID'],AS_DF[r,'EventNo']))
  query <- gsub("\\n\\s+", " ", query)
  query <- gsub('NA',"NULL", query)
  # dbSendQuery(con, query)
}

for (r in c(1:nrow(M_DF))){
  query= (sprintf('INSERT INTO chrl.rcmanual (SiteID,MDisID,RCID,EventNo) VALUES (%s,%s,%s,%s)',
                  M_DF[r,'SiteID'],M_DF[r,'MDisID'],AS_DF[r,'RCID'],AS_DF[r,'EventNo']))
  query <- gsub("\\n\\s+", " ", query)
  query <- gsub('NA',"NULL", query)
  # dbSendQuery(con, query)
}


