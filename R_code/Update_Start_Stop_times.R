##-----------------------------------------------------------------------------------------------
# Created by: Margot Vore 
# May 2021
# 
# This code is designed to recalculate the discharge of an event with hand-picked start and stop times 
# for an EC wave. If the start and stop times of a salt wave look incorrectly placed,  the user can determine
# new start and stop times which will be entered by following the prompts of this code. The code will then recalculate
# the discharge and corresponding data and update the database and google drive document.
#
# install.packages("XLConnect") --> To install this package properly you need to gave access to the cat.exe command which I had download via github.
# The path to this cat.exe command was added as a PATH variable in my windows environment and it worked great. 
#
# This code enters data into the following database tables:
# Autosalt_Summary
# Salt_Waves
# All_Discharge_Calc
# Autosalt_Forms
#
# Abbreviations:
# EC --> Electrical Conductivity
# CF  -->  Calibration Factor

##-----------------------------------------------------------------------------------------------
## ---------------------------Setting up the workspace------------------------------------------
##-----------------------------------------------------------------------------------------------
readRenviron() ## <- SET THIS PATH TO BE THE LOCATION OF THE .Renviron DOCUMENT 
setwd() ## <-  SET THIS PATH TO THE DIRECTORY WHERE THE CODE IS


options(java.parameters = "-Xmx8g")
library(DBI)
library(dplyr)
library(googledrive)
library(XLConnect)
source("AutoSalt_Functions.R")

con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))
drive_auth(email=Sys.getenv('email_gdrive'))

# Prompts to define what event you are altering
EventID= as.numeric(readline(prompt='EventID where start/stop times are changed: '))
while (is.na(EventID)==TRUE){
  print("You didn't enter an EventID. Please try again!")
  EventID= as.numeric(readline(prompt='EventID where start/stop times are changed: '))
}
SiteID= as.numeric(readline(prompt='SiteID where start/stop times are changed: '))
while (is.na(SiteID)==TRUE){
  print("You didn't enter an SiteID. Please try again!")
  SiteID= as.numeric(readline(prompt='SiteID where start/stop times are changed: '))
}

##--------------------------------------------------------------------------------------------------
##------------------------------ Extracting data from database and Hakai---------------------------
##--------------------------------------------------------------------------------------------------

# Get info about the event
Query <- sprintf("SELECT * FROM chrl.autosalt_summary WHERE SiteID=%i AND EventID=%i",SiteID, EventID)
Event_to_edit <- tryCatch({
  dbGetQuery(con, Query)
}, error=function(cond) {
  NA
})

while (nrow(Event_to_edit)==0){
  print("The EventID/SiteID combon you entered does not exist in the database")
  print("Please recheck your values and try again ")
  
  EventID= as.numeric(readline(prompt='EventID where start/stop times are changed: '))
  while (is.na(EventID)==TRUE){
    print("You didn't enter an EventID. Please try again!")
    EventID= as.numeric(readline(prompt='EventID where start/stop times are changed: '))
  }
  SiteID= as.numeric(readline(prompt='SiteID where start/stop times are changed: '))
  while (is.na(SiteID)==TRUE){
    print("You didn't enter an SiteID. Please try again!")
    SiteID= as.numeric(readline(prompt='SiteID where start/stop times are changed: '))
  }
  
  Query <- sprintf("SELECT * FROM chrl.autosalt_summary WHERE SiteID=%i AND EventID=%i",SiteID, EventID)
  Event_to_edit <- tryCatch({
    dbGetQuery(con, Query)
  }, error=function(cond) {
    NA
  })
}


#Get info about the sensors that are active during event
Query <- sprintf("SELECT * FROM chrl.all_discharge_calcs WHERE SiteID=%i AND EventID=%i",SiteID, EventID)
All_Dis <- dbGetQuery(con, Query)
Sensors <- unique(All_Dis$sensorid)

# Extract salt volume
Salt_Vol= Event_to_edit$salt_volume

drive_download(sprintf("AutoSalt_Hakai_Project/Discharge_Calculations/AutoSalt_Events/%s.WS%s.%s.xlsx",EventID,SiteID,Event_to_edit[1,'date']),"working_directory/Event_to_fix.xlsx", overwrite = TRUE)
wb= loadWorkbook("working_directory/Event_to_fix.xlsx")

EC= readWorksheet(wb,'EC salt waves', header = FALSE, startRow = 6, startCol = 1, endCol = 6)
num_data_cols=ncol(EC)-2

names(EC)[1]= 'TIMESTAMP'
names(EC)[2]= 'Sec'
Col=2
for (p in c(1:num_data_cols)){
  names(EC)[Col+p]= sprintf("EC%s",p)
}

EC$TIMESTAMP <- strptime(EC$TIMESTAMP, "%Y-%m-%d %H:%M:%S")
Headers= c('EC1','EC2','EC3','EC4')
Headers=Headers[1:num_data_cols]

##-------------------------------------------------------------------------
##------------------------- Recalculate discharge--------------------------
##-------------------------------------------------------------------------

Salt_wave_info=data.frame()
Discharge_Results=data.frame()
for (Sen in Sensors){

  Query= sprintf("SELECT * FROM chrl.sensors WHERE SensorID=%i",Sen)
  SensorInfo <- dbGetQuery(con, Query)
  ProbeNum=SensorInfo$probe_number
  
  Start_time= as.numeric(readline(prompt=sprintf('New start time for sensor %s (Probe %s) [s]: ',Sen, ProbeNum)))
  while (is.na(Start_time)==TRUE){
    print("No valid start time was entered: Please try again!")
    Start_time= as.numeric(readline(prompt=sprintf('New start time for sensor %s (Probe %s) [s]: ',Sen, ProbeNum)))
  }
  
  End_time= as.numeric(readline(prompt=sprintf('New end time for sensor %s (Probe %s) [s]: ',Sen,  ProbeNum)))
  while (is.na(End_time)==TRUE){
    print("No valid end time was entered: Please try again!")
    End_time= as.numeric(readline(prompt=sprintf('New end time for sensor %s (Probe %s) [s]: ',Sen,  ProbeNum)))
  }
  
  
  if (ProbeNum!=1){
    if (length(grep("THRECS_", Headers, ignore.case=T))>0){
      if( grep("THRECS_", Headers, ignore.case=T)==Sen){
        Header_Use <-grep("THRECS_", Headers, ignore.case=T)
      } else {
        Header_Use=NA
      }
    } else {
      Header_Use <- grep(as.character(ProbeNum), Headers, ignore.case=T)
      if (length(Header_Use)==0){
        Header_Use =NA 
      }
    }
  } else {
    Header_Use <- grep("Probe_", Headers, ignore.case=T)
    if (length(Header_Use)==0){
      Header_Use <- 1
    }
  } 
  
  Timestamp_start= format(EC[EC$Sec==Start_time,'TIMESTAMP'],'%H:%M:%S')
  Timestamp_end= format(EC[EC$Sec==End_time,'TIMESTAMP'],'%H:%M:%S')
  SW= data.frame(Sensor= Sen, ProbeNum=ProbeNum, Start_time= Timestamp_start, End_time= Timestamp_end, StartEC=EC[EC$Sec==Start_time, Headers[Header_Use]], 
                 EndEC=EC[EC$Sec==End_time, Headers[Header_Use]], ST=Start_time, ET=End_time)
  Salt_wave_info= rbind(Salt_wave_info,SW)
  subset= EC[which(EC$Sec>= Start_time & EC$Sec<=End_time),Headers[Header_Use]]
  
  ECb_start <- median(EC[which(EC$Sec < Start_time & EC$Sec > Start_time-30),Headers[Header_Use]], na.rm=TRUE)
  ECb_end <- median(EC[which(EC$Sec > End_time & EC$Sec < End_time+30),Headers[Header_Use]], na.rm=TRUE)
  deltaT <- EC[2,'Sec']- EC[1,'Sec']
  Uncert_dump <- (0.0726/Salt_Vol)*100
  
  Delta_ECb= (ECb_start-ECb_end)/(length(subset)*deltaT)
  
  # Subset the EC data to values between the start and end of salt wave
  
  CFID_subset= All_Dis[which(All_Dis$sensorid==Sen),]
  for (CFID in CFID_subset$cfid){
    used= CFID_subset[CFID_subset$cfid==CFID,'used']
    Query= sprintf("SELECT * FROM chrl.calibration_results WHERE CalResultsID=%i",CFID)
    CalibrationInfo <- dbGetQuery(con, Query) 
    
    CalEventID= CalibrationInfo$caleventid
    CF= CalibrationInfo$cf_value*10^-6
    Err= CalibrationInfo$per_err
    
    A <- array(); ER <- array()
    cou=0
    for (E  in subset){
      cou=cou+deltaT
      if (E >(ECb_start-(Delta_ECb*cou))){
        C <- (E-(ECb_start-(Delta_ECb*cou)))*CF
        A <- append(A,C)
        ER <- append(ER, (((0.005/E)*100+ Err)/100*C))
      }
      
    }
    
    Dis <- (Salt_Vol/1000)/ sum(A,na.rm=TRUE)*deltaT
    DisUncer <- (sum(ER,na.rm=TRUE)/sum(A, na.rm=TRUE)*100)+Uncert_dump
    
    DR <- data.frame(SiteID=SiteID, EventID=EventID, SensorID=Sen, CFID=CFID ,Discharge=Dis, Err=DisUncer, CalEventID=CalEventID,Used=used )
    Discharge_Results <- rbind(Discharge_Results,DR)
    
  }
}
# Discharge_Results <- Discharge_Results[which(Discharge_Results$Discharge <100 & is.na(Discharge_Results$Discharge)==FALSE & Discharge_Results$Used=='Y'),]
Discharge_Results$AbsErr <- Discharge_Results$Discharge*(Discharge_Results$Err/100)
Discharge_Results$QP <- Discharge_Results$Discharge+Discharge_Results$AbsErr
Discharge_Results$QM <- Discharge_Results$Discharge-Discharge_Results$AbsErr

Max_Q <- max(Discharge_Results[,'QP'],na.rm=TRUE)
Min_Q <- min(Discharge_Results[,'QM'],na.rm=TRUE)

Average_Discharge <- mean(Discharge_Results[,'Discharge'], na.rm=TRUE)
TotalUncert <-  max(((Max_Q-Average_Discharge)/Average_Discharge*100),((Average_Discharge-Min_Q)/Average_Discharge*100))  

# Determine the mixing
Mixing <- AutoSalt_Mixing(Discharge_Results[which(Discharge_Results$Used=='Y'),])

##---------------------------------------------------
##-----------Updating stage results------------------
##---------------------------------------------------

###############################
# Download stage data for event
###############################
Stage= readWorksheet(wb,'Stage data',startRow = 6, startCol = 2, endCol = 4, header = FALSE)
names(Stage)[1]= 'TIMESTAMP'
names(Stage)[2]='Sec'
names(Stage)[3]= 'PLS_Lvl'


######################
# Summarize stage data
######################
Stage_Summary <- data.frame()
for (R in c(1:nrow(Salt_wave_info))){
  ST <- Salt_wave_info[R,'ST']
  ET <- Salt_wave_info[R,'ET']
  
  if(is.na(Salt_wave_info[R,'Start_time'])==TRUE ){
    Stage_Event <- Stage[Stage$Sec > 1 & Stage$Sec < 1000, ]
  } else if  (ST > ET){
    Stage_Event <- Stage[Stage$Sec > 1 & Stage$Sec < 1000, ]
  } else {
    Stage_Event <- Stage[Stage$Sec > ST & Stage$Sec < ET, ]
  }
  Stage_Average <- mean(Stage_Event$PLS_Lvl, na.rm=TRUE)
  Stage_Min <- min(Stage_Event$PLS_Lvl,na.rm=TRUE)
  Stage_Max <- max(Stage_Event$PLS_Lvl, na.rm=TRUE)
  Stage_Std <- sd(Stage_Event$PLS_Lvl,na.rm=TRUE)
  
  
  
  if (is.nan(Stage_Average)==TRUE){
    Starting_Stage <- NA
    Ending_Stage <- NA
    Stage_Dir <- NA
    Stage_Average <- NA
    Stage_Min <- NA
    Stage_Max <- NA
    Stage_Dir <- NA
    
    SS <- data.frame(StageAvg= Stage_Average,StageMin=Stage_Min, StageMax=Stage_Max, StageStd=Stage_Std)
    Stage_Summary <- rbind(Stage_Summary,SS)
    
  } else{
    SS <- data.frame(StageAvg= Stage_Average,StageMin=Stage_Min, StageMax=Stage_Max, StageStd=Stage_Std)
    Stage_Summary <- rbind(Stage_Summary,SS)
  }
}


Starting_Stage <- Stage[1,'PLS_Lvl']
Ending_Stage <- Stage[nrow(Stage),'PLS_Lvl']


# Determine how the stage is changing during the dump event
if (is.na(Stage_Average)==FALSE){
  if (length(Starting_Stage)==0 | length(Ending_Stage)==0){
    Stage_Dir <- NA
  } else if(Starting_Stage >(Ending_Stage+0.1)){
    Stage_Dir <- 'F'
  } else if (Starting_Stage < (Ending_Stage-0.1)){
    Stage_Dir <- 'R'
  } else {
    Stage_Dir <- 'C'
  }
}

##---------------------------------------------------------------------------
##-------------------Updating Database---------------------------------------
##---------------------------------------------------------------------------

Query= sprintf("UPDATE chrl.autosalt_summary SET stage_average=%s, stage_min=%s, stage_max=%s, stage_std=%s, stage_dir='%s',
               discharge_avg=%s, uncert=%s, mixing=%s WHERE eventid=%s AND siteid=%s",mean(Stage_Summary$StageAvg),mean(Stage_Summary$StageMin),
               mean(Stage_Summary$StageMax),mean(Stage_Summary$StageStd),Stage_Dir,Average_Discharge,TotalUncert,Mixing,EventID,SiteID)
Query <- gsub("\\n\\s+", " ", Query)
Query <- gsub('NA',"NULL", Query)
Query <- gsub("'NULL'","NULL",Query)
dbSendQuery(con, Query)


for (R in c(1:nrow(Discharge_Results))){
  Query= sprintf('UPDATE chrl.all_discharge_calcs SET discharge=%s, uncertainty=%s WHERE eventid=%s AND siteid=%s AND sensorid=%s AND cfid=%s',
                 Discharge_Results[R,'Discharge'],Discharge_Results[R,'Err'],EventID,SiteID,Discharge_Results[R,'SensorID'], Discharge_Results[R,'CFID'])
  Query <- gsub("\\n\\s+", " ", Query)
  Query <- gsub('NA',"NULL", Query)
  Query <- gsub("'NULL'","NULL",Query)
  dbSendQuery(con, Query)

}

for (R in c(1:nrow(Salt_wave_info))){
  Query=sprintf("UPDATE chrl.salt_waves SET start_ecwave='%s',end_ecwave='%s',startingec=%s,endingec=%s WHERE eventid=%s AND siteid=%s AND sensorid=%s",Salt_wave_info[R,'Start_time'],
                Salt_wave_info[R,'End_time'], Salt_wave_info[R,'StartEC'],Salt_wave_info[R,'EndEC'], EventID, SiteID,Salt_wave_info[R,'Sensor'])
  Query <- gsub("\\n\\s+", " ", Query)
  Query <- gsub('NA',"NULL", Query)
  Query <- gsub("'NULL'","NULL",Query)
  dbSendQuery(con, Query)
}


##-----------------------------------------------------------------------------
##------------ Update Google Drive Sheet---------------------------------------
##-----------------------------------------------------------------------------


for (Probe in c(1,2,3,4)){
  if (nrow(Salt_wave_info[Salt_wave_info$ProbeNum==Probe,])==0){
    next()
  }
  Start_time= Salt_wave_info[which(Salt_wave_info$ProbeNum==Probe),'ST']
  End_time= Salt_wave_info[which(Salt_wave_info$ProbeNum==Probe),'ET']
  StartEC=Salt_wave_info[which(Salt_wave_info$ProbeNum==Probe),'StartEC']
  EndEC=Salt_wave_info[which(Salt_wave_info$ProbeNum==Probe),'StartEC']

  if (Probe ==1) {
    writeWorksheet(wb,Start_time,sheet= "EC salt waves",startRow = 6, startCol = 15, header=F)
    writeWorksheet(wb,End_time,sheet= "EC salt waves",startRow = 7, startCol = 15, header=F)
    writeWorksheet(wb,StartEC,sheet= "EC salt waves",startRow = 6, startCol = 14, header=F)
    writeWorksheet(wb,StartEC,sheet= "EC salt waves",startRow = 7, startCol = 14, header=F)
  }  else if (Probe ==2) {
    writeWorksheet(wb,Start_time,sheet= "EC salt waves",startRow = 6, startCol = 19, header=F)
    writeWorksheet(wb,End_time,sheet= "EC salt waves",startRow = 7, startCol = 19, header=F)
    writeWorksheet(wb,StartEC,sheet= "EC salt waves",startRow = 6, startCol = 18, header=F)
    writeWorksheet(wb,StartEC,sheet= "EC salt waves",startRow = 7, startCol = 18, header=F)
  } else if (Probe ==3) {
    writeWorksheet(wb,Start_time,sheet= "EC salt waves",startRow = 13, startCol = 15, header=F)
    writeWorksheet(wb,End_time,sheet= "EC salt waves",startRow = 14, startCol = 15, header=F)
    writeWorksheet(wb,StartEC,sheet= "EC salt waves",startRow = 13, startCol = 16, header=F)
    writeWorksheet(wb,StartEC,sheet= "EC salt waves",startRow =14, startCol = 16, header=F)
  } else if (Probe ==4) {
    writeWorksheet(wb,Start_time,sheet= "EC salt waves",startRow = 13, startCol = 19, header=F)
    writeWorksheet(wb,End_time,sheet= "EC salt waves",startRow = 14, startCol = 19, header=F)
    writeWorksheet(wb,StartEC,sheet= "EC salt waves",startRow = 13, startCol = 18, header=F)
    writeWorksheet(wb,StartEC,sheet= "EC salt waves",startRow =14, startCol = 18, header=F)

  }
}

setForceFormulaRecalculation(wb,'EC salt waves',TRUE)

saveWorkbook(wb,sprintf("working_directory/%s.WS%s.%s.xlsx",EventID,SiteID,Event_to_edit$date))
drive_upload(media=sprintf("working_directory/%s.WS%s.%s.xlsx",EventID,SiteID,Event_to_edit$date),path=sprintf('AutoSalt_Hakai_Project/Discharge_Calculations/AutoSalt_Events/%s.WS%s.%s_edited.xlsx',EventID,SiteID,Event_to_edit$date), overwrite=TRUE)
file.remove(sprintf("working_directory/%s.WS%s.%s.xlsx",EventID,SiteID,Event_to_edit$date))
file.remove("working_directory/Event_to_fix.xlsx")

autosalt_file_link <- sprintf('<a href=%s>%s.WS%s.%s.xlsx</a>',drive_link(sprintf('AutoSalt_Hakai_Project/Discharge_Calculations/AutoSalt_Events/%s.WS%s.%s_edited.xlsx',EventID,SiteID,Event_to_edit$date)),EventID,SiteID,Event_to_edit$date)
Query=sprintf("UPDATE chrl.autosalt_forms SET link='%s' WHERE siteid=%s and EventID=%s",autosalt_file_link,SiteID, EventID)
dbSendQuery(con,Query)

dbDisconnect(con)




