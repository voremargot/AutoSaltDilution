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
Path_RCMetadata="C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/GitHub/R_code/working_directory/Metadata_RC_626_V4.csv"


options(java.parameters = "-Xmx8g")
gg=gc()

library(DBI)
library(curl)
library(tidyverse)

con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

##-------------------------------------------------------------------------------------------------
##--------------------- Extracting relavent data from CSV file-------------------------------------
##-------------------------------------------------------------------------------------------------
{Site=as.numeric(readline(prompt='SiteID that the new rating curve is for: '))}
query= sprintf("SELECT * FROM chrl.RC_summary WHERE SiteID=%s",Site)
Current_RC_version= dbGetQuery(con, query)
while (nrow(Current_RC_version)==0){
  print("The SiteID you entered was is not valid. Please enter a different value.")
  {Site=as.numeric(readline(prompt='SiteID that the new rating curve is for: '))}
  query= sprintf("SELECT * FROM chrl.RC_summary WHERE SiteID=%s",Site)
  Current_RC_version= dbGetQuery(con, query)
}

RC= read.csv(Path_RCMetadata)

Version= max(Current_RC_version$version,na.rm=TRUE)+1
Max_Date= max(as.Date(RC$Date))
Min_Date= min(as.Date(RC$Date))

Query= sprintf("INSERT INTO chrl.RC_Summary (SiteID,Version,Start_Date,End_Date) VALUES(%s,%s,'%s','%s')",
        Site,Version,Max_Date,Min_Date)
dbSendQuery(con,Query)

RCID= Current_RC_version[which(Current_RC_version$version==Version),"rcid"]

##-----------------------------------------------------------------------------------------------
##------------------------Determining which Autosalt events were included in  rating curve------
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


