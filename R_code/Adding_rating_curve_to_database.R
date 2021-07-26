##-----------------------------------------------------------------------------------------------
# Created by: Margot Vore 
# May 2021
# 
# This code is used to record which discharge events have been included in a given rating curve. 
# After the rating curve is created and the CSV file has been updated with all the relevant information about 
# the curve, the CSV file  will be read into the database, documenting which events were included and the 
# rain event number assigned to the each event during the RC process.
#
# This code enters data into the following database tables:
# RC autosalt
# RC Manual
# RC Summary
#
# Abbreviations:
# EC --> Electrical Conductivity
# CF  -->  Calibration Factor



##-----------------------------------------------------------------------------------------------
## ---------------------------Setting up the work space------------------------------------------
##-----------------------------------------------------------------------------------------------

#THESE TWO LINES NEED TO BE UPDATED BY THE USER
readRenviron('C:/Program Files/R/R-4.1.0/.Renviron')
Path_RCMetadata="C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/GitHub/R_code/working_directory/Metadata_RC_626_V4_updated.csv"

options(java.parameters = "-Xmx8g")
options(warn = - 1) 

#load libraries
library(DBI)
library(curl)
library(tidyverse)
library(googledrive)

#connect to database and google drive
con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))
drive_auth(email=Sys.getenv('email_gdrive'))


##-------------------------------------------------------------------------------------------------
##--------------------- Extracting relevant data from CSV file-------------------------------------
##-------------------------------------------------------------------------------------------------
#user prompt to ensure they have everything on google drive to properly run the code
print("Before running this code, upload your final RC documents to the appropriate google drive folder.")
print("Make sure the site number is in the name of the RC documents you have uploaded to google drive.")
ready = readline(prompt="Are your final documents in google drive (y/n)? ")
if (ready='n' |ready='N' |ready='No'|ready='no'){
  print("Please upload your documents correctly to google drive and then run the code again")
  stop()
}

# User prompt to enter te site they have made a new rating curve for
Site=as.numeric(readline(prompt='SiteID that the new rating curve is for: '))
while (is.na(Site)==TRUE){
  print("You didn't enter a site! Please try again")
  Site=as.numeric(readline(prompt='SiteID that the new rating curve is for: '))
}

# Select the current information from the RC_summary table
query= sprintf("SELECT * FROM chrl.RC_summary WHERE SiteID=%s",Site)
Current_RC_version= dbGetQuery(con, query)

#check that a valid SiteID was given so the code can run
while (nrow(Current_RC_version)==0){
  print("The SiteID you entered was is not valid. Please enter a different value.")
  Site=as.numeric(readline(prompt='SiteID that the new rating curve is for: '))
  query= sprintf("SELECT * FROM chrl.RC_summary WHERE SiteID=%s",Site)
  Current_RC_version= dbGetQuery(con, query)
}

# Prompts the user to tell it if a shift in the rating curve happened 
Shift= readline(prompt='Was the a shift in the curve from the previous RC version (y/n):')
if (Shift=='n' | Shift=='No' | Shift=='no'){
  Shift='N'
}
if (Shift=='y' | Shift=='Yes' | Shift=='yes'){
  Shift='Y'
}

#read in the metadata
RC= read.csv(Path_RCMetadata)

# assign a version number to the curve
Version= max(Current_RC_version$version,na.rm=TRUE)+1

#find the max and min dates of data for the RC summary table
Max_Date= max(as.Date(RC$Date,"%m/%d/%Y"))
Min_Date= min(as.Date(RC$Date,"%m/%d/%Y"))


# look for relevant rating curve documents in Google drive 
drive= drive_ls(path=sprintf("AutoSalt_Hakai_Project/Rating_Curve/Plotting_and_metadata/Version %s rating curves",Version))
if (nrow(drive)==0){
  print(sprintf("There are no rating curve documents in version %s folder! Please upload the new rating curve documents to google drive and rerun the code",Version))
  stop()
}
RC_doc= drive[grepl(as.character(Site),drive$name),]
if (nrow(RC_doc)==0){
  print(sprintf('There are no files in Version %s rating curve folder with %s in the file  name.',Version, Site))
  print(sprintf("Please either rename the file so it has the site name in it OR upload the files associated with site %s",Site))   
  stop()
}

# Extract links for the RC summary documents in Google drive
Links= drive_link(RC_doc)
Link1=sprintf("<a href= %s>%s</a>",Links[1],RC_doc[1,'name'])
if (length(Links)>1){
  Link2=sprintf("<a href= %s>%s</a>",Links[2],RC_doc[2,'name'])
} else {
  Link2= NA
}


#update the RC summary table in the database with new information
Query= sprintf("INSERT INTO chrl.rc_summary (SiteID,Version,Start_Date,End_Date,Shift, Link1,Link2) VALUES(%s,%s,'%s','%s','%s','%s','%s')",
        Site,Version,Min_Date,Max_Date,Shift,Link1,Link2)
Query <- gsub("\\n\\s+", " ", Query)
Query <- gsub('NA',"NULL", Query)
Query <- gsub("'NULL'","NULL",Query)
dbSendQuery(con,Query)

#Get the RCID value associated with the new rating curve
query= sprintf("SELECT rcid FROM chrl.RC_summary WHERE SiteID=%s AND version=%s",Site,Version)
RCID= dbGetQuery(con, query)


##-----------------------------------------------------------------------------------------------
##------------------------Determining which Autosalt events were included in  rating curve------
##-----------------------------------------------------------------------------------------------
# select autosalt events that were included in the rating curve
RC_autosalt= RC[which(is.na(RC$EventID)==FALSE & RC$Final_rating_curve=='Y') ,]

# extract all valid autosalt data for events included in rating curve
AS_DF= data.frame()
for (r in c(1:nrow(RC_autosalt))){
  A= data.frame(SiteID= RC_autosalt[r,'SiteID'],EventID=RC_autosalt[r,'EventID'],RCID= RCID,EventNo= RC_autosalt[r,'Event_no'])
  AS_DF= rbind(AS_DF,A)
}

#insert data into the RCAutosalt Table for the new rating curve
for (r in c(1:nrow(AS_DF))){
  query= (sprintf('INSERT INTO chrl.rcautosalt (SiteID,EventID,RCID,EventNo) VALUES (%s,%s,%s,%s)',
                  AS_DF[r,'SiteID'],AS_DF[r,'EventID'],AS_DF[r,'RCID'],AS_DF[r,'EventNo']))
  query <- gsub("\\n\\s+", " ", query)
  query <- gsub('NA',"NULL", query)
  dbSendQuery(con, query)
}

##-----------------------------------------------------------------------------------------------
##------------------------Determining which manual events were included in  rating curve--------
##-----------------------------------------------------------------------------------------------
# select  manual events that were included in the rating curve
RC_manual= RC[which(is.na(RC$MID)==FALSE & RC$Final_rating_curve=='Y') ,]

# extract all valid data for events included in rating curve
M_DF= data.frame()
for (r in c(1:nrow(RC_manual))){
  A= data.frame(SiteID= RC_manual[r,'SiteID'],MDisID=RC_manual[r,'MID'],RCID= RCID,EventNo= RC_manual[r,'Event_no'])
  M_DF= rbind(M_DF,A)
}

#Insert data into the RCManual table for the new rating curve
for (r in c(1:nrow(M_DF))){
  query= (sprintf('INSERT INTO chrl.rcmanual (SiteID,MDisID,RCID,EventNo) VALUES (%s,%s,%s,%s)',
                  M_DF[r,'SiteID'],M_DF[r,'MDisID'],M_DF[r,'RCID'],M_DF[r,'EventNo']))
  query <- gsub("\\n\\s+", " ", query)
  query <- gsub('NA',"NULL", query)
  dbSendQuery(con, query)
}


dbDisconnect(con)
options(warn = 0)


