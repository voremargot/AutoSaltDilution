## To change the starting and ending times of an EC waved used to calculate discharge
## 1. Determine the new start and end times in seconds
## 2. update these times in the google drive excel sheet containing the salt wave information
## 3. Used the EC salt wave update code to do recalculations of discharge for the event

readRenviron('C:/Program Files/R/R-3.6.2/.Renviron')
options(java.parameters = c("-XX:+UseConcMarkSweepGC", "-Xmx8192m"))

setwd("/Users/margo.DESKTOP-T66VM01/Desktop/VIU/Salt_Dilution/")
Rcode="/Users/margo.DESKTOP-T66VM01/Desktop/VIU/GitHub/R_code/"


library(DBI)
source(sprintf("%s/AutoSalt_Functions.R",Rcode))

con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))


EventID= as.numeric(readline(prompt='EventID where start/stop times are changed: '))
SiteID= as.numeric(readline(prompt='SiteID where start/stop times are changed: '))

Query <- sprintf("SELECT * FROM chrl.autosalt_summary WHERE SiteID=%i AND EventID=%i",SiteID, EventID)
Event_to_edit <- dbGetQuery(con, Query)

Query <- sprintf("SELECT * FROM chrl.all_discharge_calcs WHERE SiteID=%i AND EventID=%i",SiteID, EventID)
All_Dis <- dbGetQuery(con, Query)
Sensors <- unique(All_Dis$sensorid)

Salt_Vol= Event_to_edit$salt_volume

##-------------------------------------------
#Downloading raw EC Data for event from Hakai
##-------------------------------------------
EC_filename <- sprintf("Trials/%i_ECdata_%s.csv",SiteID,EventID)
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
  AutoDose_filename= sprintf("Trials/%i_ECAutoDose.csv",SiteID)
  d <- curl_download(
    sprintf("https://hecate.hakai.org/saltDose/CollatedData/Stations/SSN%i/SSN%iDS_AutoDoseEvent.dat.csv",SiteID,SiteID),AutoDose_filename)
  
  CNames <- read.csv(AutoDose_filename, skip = 1, header = F, nrows = 1,as.is=T)
  EC_Dose <- read.csv(AutoDose_filename,skip=4, header=F,as.is=T)
  colnames(EC_Dose)<- CNames[,1:ncol(CNames)]
  
  EC_Dose$TIMESTAMP <- strptime(EC_Dose$TIMESTAMP, "%Y-%m-%d %H:%M:%S")
  EC<-EC_Dose[EC_Dose$TIMESTAMP> (DateTime-900) & EC_Dose$TIMESTAMP < (DateTime+3600),]
  DisSummaryComm='From Autodose event system'
  file.remove(EC_filename)
  
  
} else {
  EC <- read.csv(EC_filename,skip=4, header=F,as.is=T)
  colnames(EC)<- CNames[,1:ncol(CNames)]
  
  # If there is less than 2min of data in the EC file check the autodose file  
  if (nrow(EC)<120){
    AutoDose_filename= sprintf("Trials/%i_ECAutoDose.csv",SiteID)
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
Headers= Column_Names(EC,S)
EC= select(EC, c('TIMESTAMP','Sec',Headers))

Discharge_Results=data.frame()
for (Sen in Sensors){
  Start_time= as.numeric(readline(prompt=sprintf('New start time for sensor %s [s]: ',Sen)))
  End_time= as.numeric(readline(prompt=sprintf('New end time for sensor %s [s]: ',Sen)))
  
  Query= sprintf("SELECT * FROM chrl.sensors WHERE SensorID=%i",Sen)
  SensorInfo <- dbGetQuery(con, Query)
  ProbeNum=SensorInfo$probe_number
  
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
  
  subset= EC[which(EC$Sec> Start_time & EC$Sec<End_time),Headers[Header_Use]]
  
  ECb <- mean(subset[1],subset[length(subset)])
  deltaT <- EC[2,'Sec']- EC[1,'Sec']
  Uncert_dump <- (0.076/Salt_Vol)*100
  
  # subset the EC data to values between the start and end of saltwave
  
  CFID_subset= All_Dis[which(All_Dis$sensorid==Sen & All_Dis$used=='Y'),]
  for (CFID in CFID_subset$cfid){
    Query= sprintf("SELECT * FROM chrl.calibration_results WHERE CalResultsID=%i",CFID)
    CalibrationInfo <- dbGetQuery(con, Query) 
    CalEventID= CalibrationInfo$caleventid
    CF= CalibrationInfo$cf_value*10^-6
    Err= CalibrationInfo$per_err
    
    A <- array(); ER <- array()
    for (E  in subset){
      if (E >ECb){
        C <- (E-ECb)*CF
        A <- append(A,C)
        ER <- append(ER, (((0.005/E)*100+ Err)/100*C))
      }
      
    }
    
    Dis <- (Salt_Vol/1000)/ sum(A,na.rm=TRUE)*deltaT
    DisUncer <- (sum(ER,na.rm=TRUE)/sum(A, na.rm=TRUE)*100)+Uncert_dump
    
    DR <- data.frame(SiteID=SiteID, EventID=EventID, SensorID=Sen, CFID=CFID ,Discharge=Dis, Err=DisUncer, CalEventID=CalEventID )
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
Mixing <- AutoSalt_Mixing(Discharge_Results)

##---------------------------------------------------
##-----------Updating database-----------------------
##---------------------------------------------------





