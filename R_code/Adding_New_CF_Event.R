##-----------------------------------------------------------------------------------------------
# Created by: Margot Vore 
# May 2021
# 
# This code reads in data from new sensor calibration field sheets in google drive and writes it to the database. 
# Before running this code, make sure all sensors and barrel fills are up to date as it will rely on this
# information to input the correct data into the database.
#
# This code enters data into the following database tables:
# Calibration Events
# Calibration Results
#
# Abbreviations:
# EC --> Electrical Conductivity
# CF  -->  Calibration Factor



##-----------------------------------------------------------------------------------------------
## ---------------------------Setting up the workspace------------------------------------------
##-----------------------------------------------------------------------------------------------
readRenviron('C:/Program Files/R/R-3.6.2/.Renviron')
options(java.parameters <- c("-XX:+UseConcMarkSweepGC", "-Xmx8192m"))

library(googledrive)
library(DBI)
library(openxlsx)
library(lubridate)
library(stringi)
library(prodlim)

con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))
drive_auth(email=Sys.getenv('email_gdrive'))

##-----------------------------------------------------------------------------------
##-------------- Finding CF field sheets that are new to the drive------------------
##----------------------------------------------------------------------------------
query <- sprintf("SELECT * FROM chrl.googledriveid")
Old_CF_Events <- dbGetQuery(con, query)

Drive_Sheets <- drive_ls("AutoSalt_Hakai_Project/CF_Measurements")
New_Events <- Drive_Sheets[!(Drive_Sheets$id %in% Old_CF_Events$driveid), ]

if (nrow(New_Events)<1){
  print('There are no new CF events to upload')
  stop()
}

##-----------------------------------------------------------------------------------------
##------------------ Transferring data to database-----------------------------------------
##-----------------------------------------------------------------------------------------
CF_Summary <- data.frame(); Sensor_Summary <- data.frame(); Events_added <- data.frame()
Num <- 1
for (x in c(1:nrow(New_Events))){
  CF_File <- 'working_directory/NewCF.xlsx'
  EA <- data.frame(name= New_Events[x,'name'],Googleid=New_Events[x,'id'], added= Sys.Date(), Num= Num)
  Events_added <- rbind(Events_added,EA)
  
  # Downloads file locally and reads it into R
  drive_download(file=sprintf("AutoSalt_Hakai_Project/CF_Measurements/%s",New_Events[x,'name']), path= CF_File, overwrite = T)
  workbook <- read.xlsx(CF_File, sheet='Calibration',skipEmptyRows = F,skipEmptyCols = F)

  SiteID <- as.integer(workbook[2,2])
  Date <- as.Date(as.integer(workbook[3,2]),origin='1899-12-30')
  PMP <- workbook[6,2]
  
  
  ##---------------------------------------------------------------------------
  ##------------------- Determining the barrel period--------------------------
  ##---------------------------------------------------------------------------
  if (PMP=='Mid'){
    query <- sprintf("SELECT * FROM chrl.barrel_periods WHERE (Starting_Date <= '%s') AND (Ending_Date >= '%s') AND (SiteID=%s)",Date,Date,SiteID)
    Periods <- dbGetQuery(con, query)
    
    # If end period is NA,  indicates the event happened in the current barrel period
    if (nrow(Periods)==0){
      query <- sprintf("SELECT * FROM chrl.barrel_periods WHERE (Starting_Date <= '%s') AND (Ending_Date IS NULL) AND (SiteID='%s')",Date,SiteID)
      Periods <- dbGetQuery(con, query)
    }
  } else if (PMP=='Pre'){
      query <- sprintf("SELECT * FROM chrl.barrel_periods WHERE (Starting_Date <= '%s') AND (Ending_Date >= '%s') AND (SiteID=%s)",Date,Date,SiteID)
      Periods <- dbGetQuery(con, query)
  } else if (PMP=='Post'){
     query <- sprintf("SELECT * FROM chrl.barrel_periods WHERE (Starting_Date <= '%s') AND (Ending_Date IS NULL) AND (SiteID='%s')",Date,SiteID)
     Periods <- dbGetQuery(con, query)
  }
  
  if (nrow(Periods)==0){
    query <- sprintf("SELECT * FROM chrl.barrel_periods WHERE (Starting_Date IS NULL) AND (Ending_Date >= '%s') AND (SiteID='%s')",Date,SiteID)
    Periods <- dbGetQuery(con, query)
  }
  
  Period_ID <- as.numeric(Periods$periodid[1])
  
  ##------------------------------------------------------------------------------------
  ##-------------------- Extracting data from the Calibration sheet---------------------
  ##------------------------------------------------------------------------------------
  Link <- drive_link(sprintf("AutoSalt_Hakai_Project/CF_Measurements/%s", New_Events[x,'name']))
  
  Location <- workbook[7,2]
  
  SensorInfo <- data.frame()
  for (Sen in c(1:4)){
    SenType <- workbook[(Sen+1),5]
    SerialNum <- workbook[(Sen+1),6]
    if (is.na(SenType)==TRUE & is.na(SerialNum)==TRUE){
      next()
    }
    if (stri_sub(SerialNum,-2,-1 )=='E9'){
      SerialNum <- as.character(as.numeric(SerialNum))
    }
    
    if (SenType=='Threcs'){
      SenType <- 'THRECS'
    }
    Loc <- workbook[(Sen+1),7]
    Notes <- workbook[(Sen+1),8]
    if (Sen==1){
      Temp <- mean(as.numeric(workbook[13:18,4]),na.rm=TRUE)
    } else if (Sen==2){
      Temp <- mean(as.numeric(workbook[22:27,4]),na.rm=TRUE)
    } else if (Sen==3){
      Temp <- mean(as.numeric(workbook[31:36,4]),na.rm=TRUE)
    } else if (Sen==4){
      Temp <- mean(as.numeric(workbook[40:45,4]),na.rm=TRUE)
    }
    
    S <- data.frame(ProbeNum=Sen, Type=SenType, SerialNum= SerialNum, StreamLoc= Loc, Notes=Notes, Temp= Temp, Num=Num)
    SensorInfo <- rbind(SensorInfo,S)
  }
  
  ##-----------------------------------------------------------------------------------------------------
  ##--------------- Extracting data from Uncertainty CF sheet-------------------------------------------
  ##----------------------------------------------------------------------------------------------------
  workbook <- read.xlsx(CF_File, sheet='Uncertainty CF',skipEmptyRows = F,skipEmptyCols = F)
  for (Sen in c(1:nrow(SensorInfo))){
    if (Sen==1){
      CF <- as.numeric(workbook[15,12])*10^6
      Err <- as.numeric(workbook[23,11])
    } else if (Sen==2){
      CF <- as.numeric(workbook[43,12])*10^6
      Err <- as.numeric(workbook[50,11])
      
    } else if (Sen==3){
      CF <- as.numeric(workbook[71,12])*10^6
      Err <- as.numeric(workbook[78,11])
      
    } else if (Sen==4){
      CF <- as.numeric(workbook[99,12])*10^6
      Err <- as.numeric(workbook[106,11])
      
    }
    
    # Determine if the CF value is too high or low to use
    SensorInfo[Sen,'CF'] <- CF
    SensorInfo[Sen,'Err'] <- Err
    if (CF <2.20){
      Flag <- 'L'
    } else if (CF > 2.9){
      Flag <- 'H'
    } else {
      Flag <- NA
    }
    SensorInfo[Sen,'Flag'] <- Flag
  }
  
  # Stores summary of each sensor and event
  C <- data.frame(SiteID= SiteID, Date=Date, PMP=PMP, Loc= Location, PeriodID= Period_ID, Num= Num, Link=Link)
  CF_Summary <- rbind(CF_Summary,C)  
  Sensor_Summary <- rbind(Sensor_Summary, SensorInfo)
  Num <- Num+1
}

##----------------------------------------------------------------------------------
##------- Enter data into the calibration events table------------------------------
##----------------------------------------------------------------------------------
# Select data needed for upload to database
CF_Summary_cut <- subset(CF_Summary,select=-c(Num,Link))

# Determine how many CF trials happened during the CF event
Uni <-  unique(CF_Summary_cut)
for (x in c(1:nrow(Uni))){
  R <- CF_Summary_cut[which(CF_Summary_cut$SiteID==Uni[x,'SiteID']& CF_Summary_cut$Date==Uni[x,'Date'] & CF_Summary_cut$PMP==Uni[x,'PMP']& CF_Summary_cut$Loc==Uni[x,'Loc'] & CF_Summary_cut$PeriodID==Uni[x,'PeriodID']),]
  Uni[x,'Trials'] <- nrow(R)
}

# Insert the data into the database
for (r in c(1:nrow(Uni))){
  query <- sprintf("INSERT INTO chrl.calibration_events (PeriodID, SiteID, Date, PMP, Trial, Location) VALUES (%s,%s,'%s','%s',%s,'%s')",
                   Uni[r,'PeriodID'],
                   Uni[r,"SiteID"],
                   Uni[r,"Date"],
                   Uni[r,"PMP"],
                   Uni[r,"Trials"],
                   Uni[r,"Loc"])
  query <- gsub("\\n\\s+", " ", query)
  dbSendQuery(con, query)
}


##----------------------------------------------------------------------------------------------
##------------------- Enter data into the calibration results table----------------------------
##---------------------------------------------------------------------------------------------

# Pull the Calibration Event ID from the newly created records
for (x in c(1:nrow(CF_Summary))){
  query <- sprintf("SELECT CalEventID from chrl.calibration_events WHERE SiteID=%s AND Date='%s' AND PMP='%s' AND Location='%s'",
                 CF_Summary[x,'SiteID'], CF_Summary[x,'Date'],CF_Summary[x,'PMP'],CF_Summary[x,'Loc'])
  CF_Summary[x,'CalEventID'] <- dbGetQuery(con, query)
}

# Extract the sensor ID for each calibrated sensor
for (R in c(1:nrow(Sensor_Summary))){
  Number <- Sensor_Summary[R,'Num']
  query <- sprintf("SELECT SensorID FROM chrl.sensors WHERE SiteID=%s AND Probe_Number=%s AND Sensor_Type='%s' AND Serial_Number='%s' and River_Loc= '%s'",
                CF_Summary[which(CF_Summary$Num==Number), 'SiteID'],
                Sensor_Summary[R,'ProbeNum'],
                Sensor_Summary[R,'Type'],
                Sensor_Summary[R,'SerialNum'],
                Sensor_Summary[R,'StreamLoc'])
  SensorID <- dbGetQuery(con, query)
  if (nrow(SensorID)==0){
    SensorID <- NA
  } else if (nrow(SensorID)> 1){
    SensorID <- NA
  }
  Sensor_Summary[R,'SensorID'] <- SensorID
}

# Update the googledriveid table that records which google drive documents have been added
for (R in c(1:nrow(Events_added))){
  Number <- Events_added[R,'Num']
  query <- sprintf("INSERT INTO chrl.googledriveid (file_name,driveid,date_added,caleventid) VALUES ('%s','%s','%s',%s)",
            Events_added[R,'name'],
            Events_added[R,'id'],
            Events_added[R,'added'],
            CF_Summary[which(CF_Summary$Num==Number),'CalEventID'])
  query <- gsub("\\n\\s+", " ", query)
  dbSendQuery(con, query)
}

# Assing a trial number to each result
Trial_assignment <- CF_Summary[,c('Num','CalEventID')]
Ca <- c(0)
for (f in c(1:nrow(Trial_assignment))){
  ID <- Trial_assignment[f,'CalEventID'] 
  assign <- sum(Ca==ID)+1
  Trial_assignment[f,'TrialNum'] <- assign
  Ca <- append(Ca,ID)
}


# Insert data into the calibration results table
for (R in c(1:nrow(Sensor_Summary))){
  Number <- Sensor_Summary[R,'Num']
  if (is.null(Sensor_Summary[R,'Flags'])==TRUE){
    Sensor_Summary[R,'Flags'] <- NA
  }
  query <- sprintf("Insert INTO chrl.calibration_results (CalEventID,SiteID,SensorID,trial_number,Temp,CF_value,Per_Err,Flags,Notes,Link)
                 VALUES (%s,%s,%s,%s,%s,%s,%s,'%s','%s','%s')",
                 CF_Summary[which(CF_Summary$Num==Number),'CalEventID'],
                 CF_Summary[which(CF_Summary$Num==Number),'SiteID'],
                 Sensor_Summary[R,'SensorID'],
                 Trial_assignment[which(Trial_assignment$Num==Number),'TrialNum'],
                 Sensor_Summary[R,'Temp'],
                 Sensor_Summary[R,'CF'],
                 Sensor_Summary[R,'Err'],
                 Sensor_Summary[R,'Flags'],
                 Sensor_Summary[R,'Notes'],
                 sprintf("<a href=%s>%s</a>",CF_Summary[which(CF_Summary$Num==Number),'Link'],Events_added[which(Events_added$Num==Number),'name'])
  )
  query <- gsub("\\n\\s+", " ", query)
  query <- gsub('NA',"NULL", query)
  query <- gsub("'NULL'","NULL",query)
  dbSendQuery(con, query)
}





