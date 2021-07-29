##-----------------------------------------------------------------------------------------------
# Created by: Margot Vore 
# May 2021
# 
# This code is used to pull out the data =needed to create a rating curve. The code will create a 
# CSV file that is compatible with the html code to create rating curve. This code does not update 
# any tables.  Note that this CSV will be uploaded to the database after the rating curve has been made
# using the "adding rating curve to database" script. 
#
#
# Abbreviations:
# EC --> Electrical Conductivity
# CF  -->  Calibration Factor
# RC --> Rating Curve



##-----------------------------------------------------------------------------------------------
## ---------------------------Setting up the workspace------------------------------------------
##-----------------------------------------------------------------------------------------------
#THESE PATHS NEED TO BE UPDATED BY THE USER
readRenviron('C:/Program Files/R/R-3.6.2/.Renviron')
setwd("/Users/margo.DESKTOP-T66VM01/Desktop/VIU/GitHub/R_code/working_directory")

options(java.parameters = c("-XX:+UseConcMarkSweepGC", "-Xmx8192m"))

#load libraries
library(DBI)
library(curl)
library(tidyverse)
library(RPostgres)
 
#connect to database
con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

##---------------------------------------------------------------------------------------------
##------------- Organizing autosalt data for CSV download-------------------------------------------
##---------------------------------------------------------------------------------------------
# Prompts for user to add in needed information
Site=as.numeric(readline(prompt='SiteID that the new rating curve is for: '))
Rating_Curve_Version=as.numeric(readline(prompt="Rating Curve version number to recreate: "))

#checks that the user has  entered a valid site and rating curve number
ver=0
while (ver==0){
  # Get the RCID for the RC user in interested in
  query= sprintf("SELECT * FROM chrl.RC_summary WHERE SiteID=%s AND Version=%s",Site, (Rating_Curve_Version))
  PreviousRC= dbGetQuery(con,query)
  RCID= PreviousRC$rcid
  
  if (length(RCID)==0){
    query= sprintf("SELECT * FROM chrl.RC_summary WHERE SiteID=%s",Site)
    PreviousRC= dbGetQuery(con,query)
    Valid_versions= PreviousRC$version
    
    
    print('You have not entered a rating curve version number that exits')
    print('Valid rating curve versions are:')
    print(Valid_versions)
    Rating_Curve_Version=as.numeric(readline(prompt="Rating Curve version number to  recreate : "))
  } else {
    ver=1
  }
}

# End date of data of the rating curve
EndDate= as.Date(PreviousRC$end_date)


# Pull all autosalt events where a discharge average exists
query= sprintf("SELECT * FROM chrl.autosalt_summary WHERE SiteID=%s",Site)
autosalt_summary= dbGetQuery(con,query)
autosalt_summary= autosalt_summary[is.na(autosalt_summary$discharge_avg)==FALSE,]


# Pull all autosalt events that were included in the previous RC version
query=sprintf("SELECT * FROM chrl.rcautosalt WHERE SiteID=%s AND RCID=%s",Site,RCID)
Autosalt_included= dbGetQuery(con,query)

# Determine if an autosalt event was included in the previous RC or not
for (r in c(1:nrow(autosalt_summary))){
  EventID= autosalt_summary[r,'eventid']
  Year=as.numeric(format(as.Date(autosalt_summary[r,'date']),'%Y'))
  Month= as.numeric(format(as.Date(autosalt_summary[r,'date']),'%m'))
  
  # determine the water year of the event
  if (Month >=10){
    WY= sprintf('%s-%s',Year,(Year+1))
  } else {
    WY= sprintf('%s-%s',(Year-1),Year)
  }
  autosalt_summary[r,'WY']=WY
  
  # assign a included and event number to each event
  if (nrow(Autosalt_included[Autosalt_included$eventid==EventID,])>=1){
    autosalt_summary[r,'Included']='Y'
    autosalt_summary[r,'EventNumber']=Autosalt_included[Autosalt_included$eventid==EventID,'eventno']
  } else {
    autosalt_summary[r,'Included']='N'
    autosalt_summary[r,'EventNumber']=NA
  }
}

# determine if each data point is old  or  new since the RC of interest
autosalt_summary[autosalt_summary$date <= EndDate,'OldNew']='Old'
autosalt_summary[autosalt_summary$date > EndDate,'OldNew']='New'
autosalt_summary$Method='Autosalt'
autosalt_summary$MID=NA

Final=autosalt_summary[,c('eventid','MID','siteid','Method','date','start_time','WY','OldNew','EventNumber','Included','stage_average','stage_std','stage_dir',
                    'discharge_avg','uncert','mixing','ecb','notes')]


##----------------------------------------------------------------------------------------------------------------------
##------------------------- Organize Manual discharge events for CSV download--------------------------------------------
##-----------------------------------------------------------------------------------------------------------------------

# List which manual events were included in the previous rating curve
query=sprintf("SELECT * FROM chrl.rcmanual WHERE SiteID=%s AND RCID=%s",Site,RCID)
Manual_included= dbGetQuery(con,query)

# Pull all manual events where a discharge average exists
query= sprintf("SELECT * FROM chrl.manual_discharge WHERE SiteID=%s",Site)
manual_summary= dbGetQuery(con,query)
manual_summary= manual_summary[is.na(manual_summary$discharge)==FALSE,]

# Determine if a manual event was included in the previous rating curve or not
for (r in c(1:nrow(manual_summary))){
  Year=as.numeric(format(as.Date(manual_summary[r,'date']),'%Y'))
  Month= as.numeric(format(as.Date(manual_summary[r,'date']),'%m'))
  
  # determine the water year of each event
  if (Month >=10){
    WY= sprintf('%s-%s',Year,(Year+1))
  } else {
    WY= sprintf('%s-%s',(Year-1),Year)
  }
  manual_summary[r,'WY']=WY
  
  # assign an included and event number to each collection
  MDisID= manual_summary[r,'mdisid']
  if (nrow(Manual_included[Manual_included$mdisid==MDisID,])>=1){
    manual_summary[r,'Included']='Y'
    manual_summary[r,'EventNumber']=Manual_included[Manual_included$mdisid==MDisID,'eventno']
  } else {
    manual_summary[r,'Included']='N'
    manual_summary[r,'EventNumber']=NA
  }
}

#determine if the event is new or old in relation to the rc
manual_summary[manual_summary$date <= EndDate,'OldNew']='Old'
manual_summary[manual_summary$date > EndDate,'OldNew']='New'

# Create empty columns so we can combine manual and autosalt data
manual_summary$eventid=NA
manual_summary$stage_std=NA
manual_summary$stage_dir=NA
manual_summary$mixing= NA
manual_summary$ecb=NA

# Rename columns to match with the autosalt data
manual_summary= manual_summary %>% 
      rename(MID=  mdisid) %>%
      rename(start_time = time)  %>%
      rename(stage_average= stage)%>%
      rename(discharge_avg = discharge)%>%
      rename(notes= comment) %>%
      rename( Method= method )


##---------------------------------------------------------------------------------
##------------------------ Create CSV-------------------------------------------------
##------------------------------------------------------------------------------------
Final= rbind(Final, manual_summary[,c('eventid','MID','siteid','Method','date','start_time','WY','OldNew','EventNumber','Included',
                               'stage_average','stage_std','stage_dir','discharge_avg','uncert','mixing','ecb','notes')])

# Rename columns to match with the html code for creating rating curves
Final = Final %>%
  rename(Old_New=OldNew) %>%
  rename(Event_no = EventNumber) %>%
  rename(Date= date) %>%
  rename(Final_rating_curve= Included)%>%
  rename(Start_time= start_time ) %>%
  rename(Stage_avg= stage_average) %>%
  rename(Stage_stdv= stage_std) %>%
  rename(Stage_delta= stage_dir) %>%
  rename(Q_meas= discharge_avg) %>% 
  rename(Q_rel_unc=  uncert) %>%
  rename(Mixing= mixing) %>%
  rename(Comments= notes) %>% 
  rename(EventID= eventid) %>%
  rename(SiteID= siteid)

# write the csv file to the local computer
write.csv(Final, sprintf('Metadata_RC_%s_V%s.csv',Site,Rating_Curve_Version), row.names = FALSE)

