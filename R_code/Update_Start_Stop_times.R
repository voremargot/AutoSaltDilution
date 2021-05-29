##-----------------------------------------------------------------------------------------------
# Created by: Margot Vore 
# May 2021
# 
# This code is designed to recalculate the discharge of an event with hand-picked start and stop times 
# for an EC wave. After adding a new event, it is expected that the user will look for any major errors 
# in the salt curves and results. As there are many different types of salt curves the code that calculates
# the discharge may have picked incorrect start and stop times. The user can then use this code to do 
# recalculations with the start and stop times they have chosen by hand. This code will update the database
# with the new discharge results. 
#
# This code enters data into the following database tables:
# Autosalt_Summary
# Salt_Waves
# All_Discharge_Calc
#
#
# Abbreviations:
# EC --> Electrical Conductivity
# CF  -->  Correction Factor

##-----------------------------------------------------------------------------------------------
## ---------------------------Setting up the work space------------------------------------------
##-----------------------------------------------------------------------------------------------

readRenviron('C:/Program Files/R/R-3.6.2/.Renviron')
options(java.parameters = c("-XX:+UseConcMarkSweepGC", "-Xmx8192m"))

library(DBI)
library(curl)
library(dplyr)
source("AutoSalt_Functions.R")

con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

#Prompts to define what event you are altering
EventID= as.numeric(readline(prompt='EventID where start/stop times are changed: '))
SiteID= as.numeric(readline(prompt='SiteID where start/stop times are changed: '))

##--------------------------------------------------------------------------------------------------
##------------------------------ Extracting needed data from database and Hakai---------------------
##--------------------------------------------------------------------------------------------------

#Get info about the event
Query <- sprintf("SELECT * FROM chrl.autosalt_summary WHERE SiteID=%i AND EventID=%i",SiteID, EventID)
Event_to_edit <- dbGetQuery(con, Query)

#Get info about the sensors that are active during event
Query <- sprintf("SELECT * FROM chrl.all_discharge_calcs WHERE SiteID=%i AND EventID=%i",SiteID, EventID)
All_Dis <- dbGetQuery(con, Query)
Sensors <- unique(All_Dis$sensorid)

#extract salt volume
Salt_Vol= Event_to_edit$salt_volume

EC_filename <- sprintf("working_directory/%i_ECdata_%s.csv",SiteID,EventID)
exists <- curl_fetch_disk(
  sprintf("https://hecate.hakai.org/saltDose/CollatedData/Stations/SSN%i/%s.csv",SiteID,EventID),EC_filename)
d <- curl_download(
  sprintf("https://hecate.hakai.org/saltDose/CollatedData/Stations/SSN%i/%s.csv",SiteID,EventID),EC_filename)


# Determine if the EC file has data in it  
CNames <- tryCatch({
  read.csv(EC_filename, skip = 1, header = F, nrows = 1,as.is=T)
}, error=function(cond) {
  'EMPTY'
})

# If there is no data in the EC file, read in autodose file to see if event was captured
if (CNames=='EMPTY'){
  AutoDose_filename= sprintf("working_directory/%i_ECAutoDose.csv",SiteID)
  d <- curl_download(
    sprintf("https://hecate.hakai.org/saltDose/CollatedData/Stations/SSN%i/SSN%iDS_AutoDoseEvent.dat.csv",SiteID,SiteID),AutoDose_filename)
  
  CNames <- read.csv(AutoDose_filename, skip = 1, header = F, nrows = 1,as.is=T)
  EC_Dose <- read.csv(AutoDose_filename,skip=4, header=F,as.is=T)
  colnames(EC_Dose)<- CNames[,1:ncol(CNames)]
  
  EC_Dose$TIMESTAMP <- strptime(EC_Dose$TIMESTAMP, "%Y-%m-%d %H:%M:%S")
  DateTime <- strptime(paste(Event_to_edit$date, Event_to_edit$start_time),"%Y-%m-%d %H:%M:%S")
  EC<-EC_Dose[EC_Dose$TIMESTAMP> (DateTime-900) & EC_Dose$TIMESTAMP < (DateTime+3600),]
  DisSummaryComm='From Autodose event system'
  file.remove(EC_filename)
  
  
} else {
  EC <- read.csv(EC_filename,skip=4, header=F,as.is=T)
  colnames(EC)<- CNames[,1:ncol(CNames)]
  
  # If there is less than 2min of data in the EC file check the autodose file  
  if (nrow(EC)<120){
    AutoDose_filename= sprintf("working_directory/%i_ECAutoDose.csv",SiteID)
    d <- curl_download(
      sprintf("https://hecate.hakai.org/saltDose/CollatedData/Stations/SSN%i/SSN%iDS_AutoDoseEvent.dat.csv",SiteID,SiteID),AutoDose_filename)
    CNames <- read.csv(AutoDose_filename, skip = 1, header = F, nrows = 1,as.is=T)
    EC_Dose <- read.csv(AutoDose_filename,skip=4, header=F,as.is=T)
    colnames(EC_Dose)<- CNames[,1:ncol(CNames)]
    
    EC_Dose$TIMESTAMP <- strptime(EC_Dose$TIMESTAMP, "%Y-%m-%d %H:%M:%S")
    EC<-EC_Dose[EC_Dose$TIMESTAMP> (DateTime-900) & EC_Dose$TIMESTAMP < (DateTime+3600),]
    DisSummaryComm='From Autodose event system'
  }
}

EC$TIMESTAMP <- strptime(EC$TIMESTAMP, "%Y-%m-%d %H:%M:%S")

#Add a column of seconds since start of event
EC$Sec <- c(1:nrow(EC))

# Select only columns of EC to analyize (ECT if possible)
Headers= Column_Names(EC,SiteID)
EC= select(EC, c('TIMESTAMP','Sec',Headers))


##-------------------------------------------------------------------------
##------------------------- recalculate discharge--------------------------
##-------------------------------------------------------------------------

Salt_wave_info=data.frame()
Discharge_Results=data.frame()
for (Sen in Sensors){

  Query= sprintf("SELECT * FROM chrl.sensors WHERE SensorID=%i",Sen)
  SensorInfo <- dbGetQuery(con, Query)
  ProbeNum=SensorInfo$probe_number
  
  Start_time= as.numeric(readline(prompt=sprintf('New start time for sensor %s (Probe %s) [s]: ',Sen, ProbeNum)))
  End_time= as.numeric(readline(prompt=sprintf('New end time for sensor %s (Probe %s) [s]: ',Sen,  ProbeNum)))
  
  
  
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
  
  # subset the EC data to values between the start and end of saltwave
  
  CFID_subset= All_Dis[which(All_Dis$sensorid==Sen),]
  for (CFID in CFID_subset$cfid){
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
    
    DR <- data.frame(SiteID=SiteID, EventID=EventID, SensorID=Sen, CFID=CFID ,Discharge=Dis, Err=DisUncer, CalEventID=CalEventID,Used='Y' )
    Discharge_Results <- rbind(Discharge_Results,DR)
    
  }
}
Discharge_Results <- Discharge_Results[which(Discharge_Results$Discharge <100 & is.na(Discharge_Results$Discharge)==FALSE),]
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
Stage_filename <- sprintf("working_directory/%i_Stagedata.csv",SiteID)
d <- curl_download(
  sprintf("https://hecate.hakai.org/saltDose/CollatedData/Stations/SSN%i/SSN%iUS_FiveSecDoseStage.dat.csv",SiteID,SiteID),Stage_filename)
CNames <- read.csv(Stage_filename, skip = 1, header = F, nrows = 1,as.is=T)
Stage <- read.csv(Stage_filename,skip=4, header=F,as.is=T)
colnames(Stage) <- CNames

Stage$TIMESTAMP <- strptime(Stage$TIMESTAMP, "%Y-%m-%d %H:%M:%S")

###########################################
# Extract and configure stage data for event
############################################
Stage_Subset <- Stage[(Stage$DoseEventID==EventID) & (Stage$TIMESTAMP< EC[nrow(EC),"TIMESTAMP"])&(Stage$TIMESTAMP> EC[1,"TIMESTAMP"]),]
if(nrow(Stage_Subset)==0){
  Stage_Subset <- data.frame(TIMESTAMP=rep(NA,100),PLS_Lvl=rep(NA,100),Sec=rep(NA,100))
} else{
  Diff_Time <- (EC$TIMESTAMP[1]-Stage_Subset$TIMESTAMP[1])[[1]]
  
  #align the seconds of stage values with the seconds from EC event 
  Stage_Subset$Sec <- seq(from=abs(Diff_Time)+1,by=5,length.out=nrow(Stage_Subset))
}
Stage_header <- colnames(Stage_Subset)[grep('PLS', colnames(Stage_Subset), ignore.case=T)]
Stage_Subset$PLS_Lvl <- Stage_Subset[,Stage_header]*100


######################
# Summerize stage data
######################
Stage_Summary <- data.frame()
for (R in c(1:nrow(Salt_wave_info))){
  ST <- Salt_wave_info[R,'ST']
  ET <- Salt_wave_info[R,'ET']
  
  if(is.na(Salt_wave_info[R,'Start_time'])==TRUE ){
    Stage_Event <- Stage_Subset[Stage_Subset$Sec > 1 & Stage_Subset$Sec < 1000, ]
  } else if  (ST > ET){
    Stage_Event <- Stage_Subset[Stage_Subset$Sec > 1 & Stage_Subset$Sec < 1000, ]
  } else {
    Stage_Event <- Stage_Subset[Stage_Subset$Sec > ST & Stage_Subset$Sec < ET, ]
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


Starting_Stage <- Stage_Subset[1,'PLS_Lvl']
Ending_Stage <- Stage_Subset[nrow(Stage_Subset),'PLS_Lvl']


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

file.remove(EC_filename)
file.remove(Stage_filename)
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

