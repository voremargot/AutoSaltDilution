#!/usr/bin/Rscript
##-----------------------------------------------------------------------------------------------
# Created by: Margot Vore 
# May 2021
# 
# This code is designed to calculate the discharge values from new autosalt dump events and enter 
# the corresponding data into the database. This code looks at the autosalt event log located in Hakai's
# sensor network and picks out all events that are not already present in the database. It then 
# determines the starting and stopping point of each sensor's salt wave as well as how usable the data is. 
# It then creates a excel sheet which depicts the salt curves allowing the user to make changes as needed (this
# excel sheet is uploaded automatically to google drive). Next, we select the CF values 
# and calculate discharges and percent error of the dump event. All the results are then summarized and 
# uploaded to their respective tables within the autosalt database. 
#
# This code enters data into the following database tables:
# Autosalt_Summary
# Salt_Waves
# All_Discharge_Calc
# Autosalt_forms
#
# Abbreviations:
# EC --> Electrical Conductivity
# CF  --> Calibration Factor



##-----------------------------------------------------------------------------------------------
## ---------------------------Setting up the work space------------------------------------------
##-----------------------------------------------------------------------------------------------
cat("\n")
print("---------------------------------------------------------")
print("---------------------------------------------------------")
print(sprintf("Date and Time:%s", Sys.time()))
computer = "hakai" # or local

if (computer == "local"){
  readRenviron('C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/.Renviron')
} else if (computer == "hakai"){
  readRenviron('/home/autosalt/AutoSaltDilution/other/.Renviron')
}
options(java.parameters = "-Xmx8g")
gg=gc()


if (computer=="local"){
  setwd("C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/GitHub/R_code/")
} else if (computer == 'hakai') {
  setwd("/home/autosalt/AutoSaltDilution/R_code")
}


#Libraries
suppressMessages(library(DBI))
suppressMessages(library(data.table))
suppressMessages(library(XLConnect))
suppressMessages(library(dplyr))
suppressMessages(library(googledrive))
suppressMessages(library(tidyr))
source("AutoSalt_Functions.R")

options(warn = - 1)  

# Connect to database and google drive


if (computer=="local"){
  con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))
  drive_auth(path="C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/Oauth_try.json")
} else if (computer=="hakai"){
  con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))
  drive_auth(path="/home/autosalt/AutoSaltDilution/other/Oauth.json")
}



## ---------------------------------------------------------------------------------------------
## ------------------------------- The code-----------------------------------------------------
## ---------------------------------------------------------------------------------------------

# List of active stations
Stations= 703 #c(626,703,844,1015)
for (S in Stations){
  
  ##############################################
  # Finding new events for discharge calculation
  ###############################################
  if (computer == "hakai") {
      if (S==626){
        DumpEvent_File <- sprintf("/home/hakai/saltDose/CollatedData/Stations/SSN%i/SSN%iAS_DoseEvent.dat.csv",S,S)
      } else {
        DumpEvent_File <- sprintf("/home/hakai/saltDose/CollatedData/Stations/SSN%i/SSN%iUS_DoseEvent.dat.csv",S,S)
      }
  } else if (computer == "local"){
      if (S==626){
        DumpEvent_File <- sprintf("C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/Data_From_Goose/Data_From_Goose_11.08.2022/saltDose/CollatedData/Stations/SSN%i/SSN%iAS_DoseEvent.dat.csv",S,S)
      } else {
        DumpEvent_File <- sprintf("C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/Data_From_Goose/Data_From_Goose_11.08.2022/saltDose/CollatedData/Stations/SSN%i/SSN%iUS_DoseEvent.dat.csv",S,S)
      }
  }
  
  # Reading in newly downloaded event file
  CNames <- read.csv(DumpEvent_File, skip = 1, header = F, nrows = 1,as.is=T)
  Dump_Event <- read.csv(DumpEvent_File,skip=4, header=F,as.is=T)
  colnames(Dump_Event) <- CNames
  
  # Old Events--> those that have already be analyzed 
  query <- sprintf("SELECT EventID,SiteID FROM chrl.autosalt_summary WHERE SiteID=%i",S)
  Old_Events <- dbGetQuery(con, query)
  
  # New Events --> those that have not yet been processed
  New_Events<- Dump_Event[!(Dump_Event$DoseEventID %in% as.numeric(Old_Events$eventid)), ]
  New_Events <- New_Events[which(is.na(New_Events$DoseEventID)==FALSE),]
  
  
  #Summary data frames for the data that will be exported to CSV
  Discharge_Summary<-data.frame()
  All_Discharge <- data.frame()
  Salt_waves <-data.frame()
  Autosalt_forms <-data.frame()
  # 
  # Number= sample(1:nrow(New_Events),1)
  
  EID_Array=c(0)

  if (nrow(New_Events)==0){
	 print(sprintf("There are no new dump events to record for site %s",S))
	  next()
  }

  
  for (N in c(1:nrow(New_Events))){
    Overall_Flags <- NA
    DisSummaryComm <- NA
    
    
    ###################################  
    # Extracting metadata for the event
    ###################################
    Event_Num <- New_Events$DoseEventID[N]
    
    
    # Skip event if no event number is present 
    if (is.na(Event_Num)==TRUE){
      next()
    }
    
    # double check that there is no duplication of new events to  add
    if (Event_Num %in% EID_Array){
      next()
    }
    EID_Array=append(EID_Array, Event_Num)
    
    #general metadata
    SiteID <- S
    DateTime <- strptime(New_Events$DoseReleaseTS[N], format="%m/%d/%Y %H:%M:%S")  
    Date <- format(DateTime, format="%Y-%m-%d")
    Time  <- format(DateTime, format="%H:%M:%S")
    Temp <- New_Events$StreamTemperatureRelease[N]
    Salt_Vol <- New_Events$CalculatedSolutionVolume[N]*0.9204 #correction for salt volume
    Stage_Start <- New_Events$StreamHeightRelease[N]*100

    print(sprintf('WS%s: %s-%s',SiteID,Event_Num,Date))
    
    #########################################
    #Determine the barrel period of the event
    #########################################
    query <- sprintf("SELECT * FROM chrl.barrel_periods WHERE (Starting_Date <= '%s') AND (Ending_Date >= '%s') AND (SiteID=%s)",Date,Date,S)
    Periods <- dbGetQuery(con, query)
    
    # If end period is NA,  indicates the event happened in the current barrel period
    if (nrow(Periods)==0){
      query <- sprintf("SELECT * FROM chrl.barrel_periods WHERE (Starting_Date <= '%s') AND (Ending_Date IS NULL) AND (SiteID='%s')",Date,S)
      Periods <- dbGetQuery(con, query)
    }
    
    if (nrow(Periods)==0){
      query <- sprintf("SELECT * FROM chrl.barrel_periods WHERE (Starting_Date IS NULL) AND (Ending_Date >= '%s') AND (SiteID='%s')",Date,S)
      Periods <- dbGetQuery(con, query)
    }
    
    Period_ID <- as.numeric(Periods$periodid[1])
    
    #####################################################
    # See how many CF have happened in the barrel period
    query <- sprintf("SELECT * FROM chrl.calibration_events WHERE (PeriodID=%i)",Period_ID)
    Barrel_Period_CFs <- dbGetQuery(con, query)
    Barrel_Period_CFs=Barrel_Period_CFs[which(Barrel_Period_CFs$temp > (Temp-5) & Barrel_Period_CFs$temp <(Temp+5)),]
    
    #only evaluate the event if there are enough CF values
    Usable_trials= sum(Barrel_Period_CFs$trial)
    if (Usable_trials < 4 & is.na(Periods$ending_date)==TRUE){
      print(sprintf('Not enough valid CF measurements to evaluate Event %i at site %i:SKIPPING',Event_Num,SiteID))
      next()
    }
 
    # make note if we are doing calcualtions with CF values chosen not based on Temperature
    if (nrow(Barrel_Period_CFs)==0){
      query <- sprintf("SELECT * FROM chrl.calibration_events WHERE (PeriodID=%i)",Period_ID)
      Barrel_Period_CFs <- dbGetQuery(con, query)
      DisSummaryComm='CF Values not chosen by temperature'
    }
    
    
    ###################################
    # Downloading EC data for the event
    ###################################
             
    # Downloading raw EC Data for event from Hakai
   # EC_filename <- sprintf("working_directory/%i_ECdata_%s.csv",S,Event_Num)
   # exists <- curl_fetch_disk(
    #  sprintf("https://hecate.hakai.org/saltDose/CollatedData/Stations/SSN%i/%s.csv",S,Event_Num),EC_filename)
   if (computer == 'hakai'){
     EC_filename <- sprintf("/home/hakai/saltDose/CollatedData/Stations/SSN%i/%s.csv",S,Event_Num)
   } else if (computer == 'local'){
     EC_filename <- sprintf("C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/Data_From_Goose/Data_From_Goose_11.08.2022/saltDose/CollatedData/Stations/SSN%i/%s.csv",S,Event_Num)
   }
   
    
    
    # Determine if the EC file has data in it  
    CNames <- tryCatch({
      read.csv(EC_filename, skip = 1, header = F, nrows = 1,as.is=T)
    }, error=function(cond) {
      'EMPTY'
    })
    
    # If there is no data in the EC file, read in autodose file to see if event was captured
    if (is.data.frame(CNames)==FALSE){
      #AutoDose_filename= sprintf("working_directory/%i_ECAutoDose.csv",S)
      if (computer == 'hakai'){
        AutoDose_filename <- sprintf("/home/hakai/saltDose/CollatedData/Stations/SSN%i/SSN%iDS_AutoDoseEvent.dat.csv",S,S)
      } else if (computer=="local"){
        AutoDose_filename <- sprintf("C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/Data_From_Goose/Data_From_Goose_11.08.2022/saltDose/CollatedData/Stations/SSN%i/SSN%iDS_AutoDoseEvent.dat.csv",S,S)
      }
      CNames <- read.csv(AutoDose_filename, skip = 1, header = F, nrows = 1,as.is=T)
      EC_Dose <- read.csv(AutoDose_filename,skip=4, header=F,as.is=T)
      colnames(EC_Dose)<- CNames[,1:ncol(CNames)]
      
      EC_Dose$TIMESTAMP <- strptime(EC_Dose$TIMESTAMP, "%Y-%m-%d %H:%M:%S")
      EC<-EC_Dose[EC_Dose$TIMESTAMP> (DateTime-900) & EC_Dose$TIMESTAMP < (DateTime+3600),]
      
      # add flag to indicate autodose system used
      if (is.na(Overall_Flags)==TRUE){
        Overall_Flags='AD'
      } else {
        Overall_Flags=append(Overall_Flags,'AD')
        Overall_Flags <- paste(Overall_Flags, collapse=',')
      }
      
      
    } else {
      EC <- read.csv(EC_filename,skip=4, header=F,as.is=T)
      colnames(EC)<- CNames[,1:ncol(CNames)]
      
      # If there is less than 2min of data in the EC file check the autodose file  
      if (nrow(EC)<120){
        AutoDose_filename <-  sprintf("/home/hakai/saltDose/CollatedData/Stations/SSN%i/SSN%iDS_AutoDoseEvent.dat.csv",S,S)
        CNames <- read.csv(AutoDose_filename, skip = 1, header = F, nrows = 1,as.is=T)
        EC_Dose <- read.csv(AutoDose_filename,skip=4, header=F,as.is=T)
        colnames(EC_Dose)<- CNames[,1:ncol(CNames)]
        
        EC_Dose$TIMESTAMP <- strptime(EC_Dose$TIMESTAMP, "%Y-%m-%d %H:%M:%S")
        EC<-EC_Dose[EC_Dose$TIMESTAMP> (DateTime-900) & EC_Dose$TIMESTAMP < (DateTime+3600),]
        
        # add flag to indicate autodose system used
        if (is.na(Overall_Flags)==TRUE){
          Overall_Flags='AD'
        } else {
          Overall_Flags=append(Overall_Flags,'AD')
          Overall_Flags <- paste(Overall_Flags, collapse=',')
        }
      }
    }  
      
    # If there is still less than 2min of data, save the event in discharge summary and continue
    if (nrow(EC)< 120){
      
      #prep stage data for summary
      Stage_filename <- sprintf("/home/hakai/saltDose/CollatedData/Stations/SSN%i/SSN%iUS_FiveSecDoseStage.dat.csv",S,S)
      CNames <- read.csv(Stage_filename, skip = 1, header = F, nrows = 1,as.is=T)
      Stage <- read.csv(Stage_filename,skip=4, header=F,as.is=T)
      colnames(Stage) <- CNames
      Stage$TIMESTAMP <- strptime(Stage$TIMESTAMP, "%Y-%m-%d %H:%M:%S")
      
      Stage_Subset <- Stage[(Stage$DoseEventID==Event_Num),]
      if (nrow(Stage_Subset)==0){
        Stage_Average <- NA
        Stage_Min <- NA
        Stage_Max <- NA
        Stage_Std <- NA
      } else{
        Stage_Subset$Sec <- c(1:nrow(Stage_Subset))
        
        Stage_header <- colnames(Stage_Subset)[grep('PLS', colnames(Stage_Subset), ignore.case=T)]
        Stage_Subset$PLS_Lvl <- Stage_Subset[,Stage_header]*100
        
        #stage summary statistics
        Stage_Average <- mean(Stage_Subset$PLS_Lvl, na.rm=TRUE)
        Stage_Min <- min(Stage_Subset$PLS_Lvl,na.rm=TRUE)
        Stage_Max <- max(Stage_Subset$PLS_Lvl, na.rm=TRUE)
        Stage_Std <- sd(Stage_Subset$PLS_Lvl,na.rm=TRUE)
      }
      #Add a flag indicating no data
      if (is.na(Overall_Flags)==TRUE){
        Overall_Flags='ND'
      } else {
        Overall_Flags=append(Overall_Flags,'ND')
        Overall_Flags <- paste(Overall_Flags, collapse=',')
      }
      
      #summerize the event
      DS= data.frame(EventID=Event_Num, SiteID=S, PeriodID=Period_ID, Date= Date, Temp= Temp, Start_Time=Time, 
                     Stage_DoseRelease= Stage_Start, Stage_Average= Stage_Average, Stage_Min= Stage_Min, Stage_Max= Stage_Max, Stage_Std= Stage_Std,
                     Stage_Dir=NA, Salt_Volume= Salt_Vol, Discharge_Avg=NA, Uncert=NA, Flags=Overall_Flags, ECb=NA,
                     Mixing= NA, Notes= NA)
      Discharge_Summary= rbind(Discharge_Summary,DS)
      next()
    }
    

    ###############################
    # Download stage data for event
    ###############################
    if (computer == 'hakai'){
      Stage_filename <- sprintf("/home/hakai/saltDose/CollatedData/Stations/SSN%i/SSN%iUS_FiveSecDoseStage.dat.csv",S,S)
    } else if (computer == 'local'){
      Stage_filename <- sprintf("C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/Data_From_Goose/Data_From_Goose_11.08.2022/saltDose/CollatedData/Stations/SSN%i/SSN%iUS_FiveSecDoseStage.dat.csv",S,S)
    }
    
    CNames <- read.csv(Stage_filename, skip = 1, header = F, nrows = 1,as.is=T)
    Stage <- read.csv(Stage_filename,skip=4, header=F,as.is=T)
    colnames(Stage) <- CNames
    
    
    ################
    # Adjusting Data
    ################
    EC$TIMESTAMP <- strptime(EC$TIMESTAMP, "%Y-%m-%d %H:%M:%S")
    Stage$TIMESTAMP <- strptime(Stage$TIMESTAMP, "%Y-%m-%d %H:%M:%S")
    
    # Add a column of seconds since start of event
    
    EC$Sec <- c(1:nrow(EC))
  
  # Select only temp corrected columns of EC to analyze (ECT if possible)
    Headers= Column_Names(EC)
    EC= select(EC, c('TIMESTAMP','Sec',all_of(Headers)))
    
    if (lapply(EC,class)[Headers[1]]=="character"){
      for (H in c(1:length(Headers))){
        HD= Headers[H]
        EC <- transform(EC,TY= as.numeric(EC[,HD]))
        EC[,HD]= EC[,'TY']
      }
    }
    EC=EC[ , ! names(EC) %in% c('TY')]
  
    
  ## ----------------------------------------------------------------------------------------------------------------------------
  ## -----------------------------------------Calculating Start and Stop Times and exploring the EC data-------------------------
  ## ----------------------------------------------------------------------------------------------------------------------------
      
    #############################################  
    # Creating average EC column for each sensor
    #############################################
    for (EC_Cols in Headers){
      Smoothing_number<-15  #this value can be changed
      for (i in c(Smoothing_number:nrow(EC))){
        EC[i,sprintf('Avg_%s',EC_Cols)] <- mean(EC[(i-Smoothing_number):(i+Smoothing_number),EC_Cols],na.rm=TRUE)
      }
    }
    
      
    ##################################
    # Assessing salt waves of the event
    ##################################
      
    EC_curve_results <- data.frame()
    Probe <- 1
      
    # Calculate starting and ending time for the salt wave of each sensor
    for (EC_Cols in Headers){
      Comment <- c()
      Ecb=NA
      HED <- sprintf('Avg_%s',EC_Cols)
      
      # Find maximum EC value
      Mx <- max(EC[,HED],na.rm=TRUE)
      # Find when maximum EC value occurred
      Time_Max <-  EC[(EC[,HED]==Mx)&(is.na(EC[,HED])==FALSE),"Sec"][1]
      
      
      # Check for multiple occurrences of maximum time
      Splitting <- split(Time_Max, cumsum(c(1, diff(Time_Max) != 1)))
      L <- length(Splitting)
      if (L > 1 | length(Splitting$`1`)>10) {
        Comment <- append(Comment,'M') #Multiple maximum values
        Time_Max= round(median(Time_Max))
      }
      
      #case where all EC values are NA --> summerize and go to next sensor
      if (nrow(EC[is.na(EC[,HED])==TRUE,])== nrow(EC)){
        Re <- data.frame(SiteID=S,EventID= Event_Num,Probe=Probe,Starting_EC=NA, Starting_Time=NA, 
                         Ending_EC= NA, Ending_Time=NA,Time_Max= NA,Max_EC=NA, Duration=NA, 
                         STD=NA, Comment= 'Nd', Ecb=NA, Starting_ECb=NA, Ending_ECb=NA,stringsAsFactors = FALSE)
        EC_curve_results <- rbind(EC_curve_results,Re)
        Probe <- Probe+1
        next()
      }
      
      # Determine if there is a significant slope to the starting base EC
      lin_model <- lm(filter(EC,Sec<30)[,HED]~Sec,filter(EC,Sec<30))
      slope <- summary(lin_model)$coefficients[2]*30
    
      
      #################################
      # Find Starting Time of salt wave
      #################################
      # If there is a significant slope in ECb --> choose minimum value in first 2.5min to be start of wave
      if(abs(slope)>0.35){
        Starting_time <- EC[which(EC[,HED]==min(EC[EC$Sec<150,HED],na.rm=TRUE)& EC$Sec<150),'Sec'][1]
        Starting_EC <- EC[(EC$Sec==Starting_time),EC_Cols]
        Starting_Std_avg <- sd(EC[EC$Sec<Starting_time,EC_Cols],na.rm=TRUE)
        
        if (Time_Max > (Starting_time+ 200)){
          Starting_time= Time_Max-200
          Starting_EC <- EC[(EC$Sec==Starting_time),EC_Cols]
          Starting_Std_avg <- sd(EC[(EC$Sec<Starting_time & EC$Sec >(Starting_time-200)),EC_Cols],na.rm=TRUE)
        }
        
         
        # Check that the starting time doesn't exceed the max
        if (Starting_time > Time_Max){
          Starting_time <- EC[which(EC[,HED]==min(EC[EC$Sec<60,HED],na.rm=TRUE)& EC$Sec<60),'Sec'][1]
          Starting_EC <- EC[(EC$Sec==Starting_time),EC_Cols]
          Starting_Std_avg <- sd(EC[EC$Sec<Starting_time,EC_Cols],na.rm=TRUE)
        }
        
      # all other cases
      }else{
        Averaging_time <-30
        Starting_time <- -1000
        Loop <- 1
        # choose a starting value within 400 sec of max time
        while ((Time_Max > (Starting_time+ 400)) & (is.na(Starting_time)==FALSE)){
          Averaging_time <- Averaging_time+15
          Starting_EC <- mean(EC[(EC$Sec<Averaging_time),HED],na.rm=TRUE)
          Starting_Std_avg <- sd(EC[(EC$Sec<Averaging_time),HED],na.rm=TRUE)
          
          # Setting minimum standard deviations
          if (Starting_Std_avg<0.050){
            Starting_Std_avg <- 0.050
          } 
          
          #starting time is 3SD from the average starting EC
          Starting_time <- EC[(EC[,HED]> (Starting_EC+3*Starting_Std_avg))&(is.na(EC[,HED])==FALSE),'Sec'][1]
          
          # Break point for the while loop
          if (Loop >= 1000){
            Starting_time <-  NA
          }
          Loop <- Loop+1
        }
        
        
      }
        
      # If no starting time is determined or maximum value is insignificant
      if (is.na(Starting_time)==TRUE| Mx < (Starting_EC+3)){
        #set an arbitrary starting time
        if (is.na(Starting_time)==TRUE){
          Starting_time <- 45
        }
        
        # Determines if EC values are exceptionally low; sensor may not be submerged
        if (mean(EC[,HED],na.rm=TRUE)<5){
          Comment <- append(Comment,'L') 
        }
        
        # If there is no starting value then there is no wave
        Comment <- append(Comment,'N')
        Comment <- paste(Comment, collapse=',')
        
        # Add Event information to a data frame
        Re <- data.frame(EventID=Event_Num, SiteID=S,Probe=Probe,Starting_EC=NA,Starting_Time=NA,Ending_EC= NA, Ending_Time=NA,
                          Time_Max= NA,Max_EC= NA,Duration=NA, STD=NA, Comment= Comment, Ecb=NA,Starting_ECb=NA, Ending_ECb=NA,stringsAsFactors = FALSE)
        EC_curve_results <- rbind(EC_curve_results,Re)
        
        Probe <- Probe+1
        next()
      }
      
      #specify starting ECb level
      if (Starting_time<= 15){
        Starting_ECb=EC[(EC$Sec==Starting_time),HED]
      } else {
        Starting_ECb= median(EC[(EC$Sec<Starting_time & EC$Sec>Starting_time-30),HED], na.rm=TRUE)
      }
      

      ##########################
      # Finding End of salt wave
      #########################
      Ending_EC <-  EC[(EC$Sec>Time_Max)&(is.null(EC[,HED])==FALSE),]
      
      # Calculating ending time 
      duration <- 2000
      STD_multiplier <- 3
      Dur <- 1000
      
      # loop that determines when the EC values return to base levels
      while (duration > Dur){
        STD_multiplier <- STD_multiplier+1
        Stop_condition  <- NULL
        
        # If there are not more than 21 rows, select the last datapoint as ending time 
        if (nrow(Ending_EC)<21){
          Ending_time <- Ending_EC[nrow(Ending_EC),'Sec']
          Stop_condition <- 'STOP'
          break
        }
        
        # Break point if the standard deviation gets too large (i.e. there is no curve or extreme Ecb change)
        if (STD_multiplier >70){
          Ending_time <- Ending_EC[(Starting_time+950),'Sec']
          Stop_condition <- 'STOP'
          break
        } 
        
        if (is.null(Stop_condition)== TRUE){
          for (i in c(21:nrow(Ending_EC))){
            V <- mean(Ending_EC[((i-20):i),HED],na.rm=TRUE)
          
            # Check if the mean is within x standard deviations of the starting EC
            M=abs(Starting_EC-V)
            if (M <=(Starting_Std_avg*STD_multiplier)){
              Ending_time <- Ending_EC[i,'Sec']
              break
            } else if (i == nrow(Ending_EC)){
              Ending_time <- NULL
              break
            }
          }
        }
          
        # Check if stopping conditions are met (indicated by CO of STOP) for the end point 
        if (is.null(Stop_condition)==TRUE){
          if (is.null(Ending_time)==TRUE){
            duration <- 2000
          } else{
          duration <- Ending_time-Starting_time
          }
        } else {
          duration <- 0
          }
      }
      
      #specify the ending ECb levels
      if (Ending_time>(max(EC$Sec)-15)){
        Ending_ECb= EC[(EC$Sec==(Ending_time-15)),HED]
      } else {
        Ending_ECb= median(EC[(EC$Sec>Ending_time & EC$Sec<Ending_time+30),HED])
      }

      
      #######################
      # Look at trends in ECb
      #######################
      ECB_Subset= EC[which(EC$Sec< Starting_time | EC$Sec> (Ending_time+500)),]
      lin_model <- lm(ECB_Subset[,HED]~Sec,ECB_Subset)
      slope <- summary(lin_model)$coefficients[2]*1000
      R2 <- summary(lin_model)$r.squared
      
      # add flag if ECb is rising or falling
      if (slope > 0.25 & R2>0.75){
        Ecb <- 'R'
      } else if (slope< -0.25 & R2>0.75){
        Ecb <- 'F'
      } else {
        Ecb <- 'C'
      }
      
    #if ECb is significantly rising or falling, recalculate ending point
    if (Ecb!='C'){
        # where the ending EC levels are with a significant
        change_start= summary(lin_model)$coefficients[2]*(Ending_time)+summary(lin_model)$coefficients[1]
          
          # choosing new end point
          Stop_condition=NULL
          while (is.null(Stop_condition)== TRUE){
            for (i in c(21:nrow(Ending_EC))){
              V <- mean(Ending_EC[((i-20):i),HED],na.rm=TRUE)
              if (V<(Starting_ECb+change_start+(Starting_Std_avg*3)) & V>(Starting_ECb+change_start-(Starting_Std_avg*3))){
                Ending_time= Ending_EC[i,'Sec']
                Stop_condition='END'
              } else if (i== nrow(Ending_EC)){
                Stop_condition='END'
              }
            }
          }
        }
     
    
    
      #####################################
      # Checking for spikes in salt wave
      #####################################
      
      # flag partial waves
      if(Starting_time > Ending_time | Ending_time< Starting_time+45){
        Comment= append(Comment,'Pw') #partial wave occurring
      } else {
        EC_saltwave <- EC[(EC$Sec>Starting_time)&(EC$Sec<Ending_time),]
        
        count <- 0
        for (i in c(1:(nrow(EC_saltwave)-1))){
          # Check the difference between consecutive EC values
          diff_EC <- EC_saltwave[i+1,EC_Cols]-EC_saltwave[(i),EC_Cols]
          
          # Check to see if, after a large dip/jump in EC, it rebounds to previous levels
          if (abs(diff_EC) >3){
            for (j in c(1,2,3,4,5,6)){
              if ((i+j) > nrow(EC_saltwave)){
                break
              }
              
              #flag spike in EC wave
              if (is.na(EC_saltwave[i,EC_Cols])==TRUE | is.na(EC_saltwave[(i+j),EC_Cols])==TRUE){
                Comment <- append(Comment,'S') #Spike in the EC wave
                count <-  1
                break
              }
              
              diff_try <- EC_saltwave[(i),EC_Cols]-EC_saltwave[(i+j),EC_Cols]
              if (abs(diff_try)<3){
                Comment <- append(Comment,'S') #Spike in the EC wave
                count <-  1
                break
              }
            }
            
          }
          
          # Break point to jump out of for loop 
          if (count==1){
            break
          }
        }
      }
     
      #################################
      # Additional flags for salt waves
      #################################
      
      # Checking the noise levels in ECb
      if (Ending_time ==nrow(EC)){
        Ending_Std==0
      } else {
        Ending_Std <- sd(EC[EC$Sec>Ending_time & EC$Sec< (Ending_time+60*15),EC_Cols])
      }
      
      #flag noisy data
      Starting_Std <- sd(EC[(EC$Sec<Starting_time),EC_Cols],na.rm=TRUE)
      if(Starting_Std>0.6 | Ending_Std >0.6){
        Comment <- append(Comment, 'Sd') #Noisy with high Standard Deviation
      }
      
      # flag noisy data (specific for data that is part of the salt wave)
      if (!('Pw' %in% Comment)){
        if (!('N' %in% Comment)){
          FirstHalf <- EC[EC$Sec> Starting_time & EC$Sec <Time_Max, EC_Cols]
          SecondHalf <- EC[EC$Sec< Ending_time & EC$Sec >Time_Max, EC_Cols]
          Dif1 <- array(); Dif2 <- array()
          for(x in c(2:length(FirstHalf))){
            Dif1 <- append(Dif1, (FirstHalf[x]-FirstHalf[x-1]))
          }
          for (x in c(2:length(SecondHalf))){
            Dif2 <- append(Dif2, (SecondHalf[x]-SecondHalf[x-1]))
          }
          Dif1 <- Dif1[which(Dif1 !=0)]
          Dif2 <- Dif2[which(Dif2 !=0)]
          
          if (sd(Dif1)>2.1| sd(Dif2)>2.1){
            if (!('Sd' %in% Comment)){
              Comment <- append(Comment, 'Sd')
            }
          }
        }
        
      }

      # Checking for low EC values
      if (mean(EC[,HED],na.rm=TRUE)<5){
        Comment <- append(Comment,'L') #Low EC values, may not be submerged
      }
      
      ##################################
      # Summarize the salt wave findings
      ##################################
      
      E_EC <- EC[EC$Sec==Ending_time,EC_Cols]
      S_EC <- EC[EC$Sec==Starting_time, EC_Cols]
      Max_EC <- max(EC[,EC_Cols],na.rm=TRUE)
      
      #flag extreme values
      if (Max_EC > 130){
        Comment <- append(Comment,'E') #Extreme values present
        Max_EC <- NA
      }

      #summarize event with no wave
      if ('N' %in% Comment ){
        Comment <- paste(Comment, collapse=',')
        Re  <- data.frame(EventID= Event_Num, SiteID= S,Probe=Probe,Starting_EC=NA, Starting_Time=NA, 
                          Ending_EC= NA, Ending_Time=NA, Time_Max= NA,Max_EC= NA,
                          Duration=NA, STD=NA, Comment= Comment,Ecb=Ecb,Starting_ECb=NA, Ending_ECb=NA, stringsAsFactors = FALSE)
        EC_curve_results <- rbind(EC_curve_results,Re)
        Probe <- Probe+1
       
      # summarize all other data 
      } else{
      Comment <- paste(Comment, collapse=',')
      Re <- data.frame(SiteID=S,EventID= Event_Num,Probe=Probe,Starting_EC=S_EC, Starting_Time=Starting_time, 
                       Ending_EC= E_EC, Ending_Time=Ending_time,Time_Max= Time_Max[1],Max_EC=Max_EC, Duration=duration, 
                       STD=STD_multiplier, Comment= Comment, Ecb=Ecb,Starting_ECb=Starting_ECb, Ending_ECb=Ending_ECb, stringsAsFactors = FALSE)
      EC_curve_results <- rbind(EC_curve_results,Re)
      Probe <- Probe+1
      }
    }
    
    
    ##########################################################
    # Check for the no wave flag (N) for final database summary
    ###########################################################
    for (r in c(1:nrow(EC_curve_results))){
      C_split <- unlist(strsplit(EC_curve_results[r,'Comment'], ","))
      if (length(C_split)==0 | (length(which(C_split=='N'))==0 & length(which(C_split=='Nd'))==0)){
        NoWave_Flag <- 'No'
        break()
      }
      if (r==nrow(EC_curve_results)){
        NoWave_Flag <- 'Yes'
      }
    }
    
    #create no wave flag for discharge summary table
    if(NoWave_Flag=='Yes'){
      DS_Flag <- 'NW'
    } else {
      DS_Flag <- NA
    }
    
    # create partial wave flag for discharge summary table
    PartialWave_Flag =NA
    for (r in c(1:nrow(EC_curve_results))){
      C_split <- unlist(strsplit(EC_curve_results[r,'Comment'], ","))
      if (length(which(C_split=='Pw'))!=0 ){
        PartialWave_Flag <- 'Yes'
        break()
      }
    }
    
    if(is.na(PartialWave_Flag)==FALSE){
      DS_Flag <- 'PW'
    } 
    
      
    ##########################################
    # Summary of ECb trends for final database
    ##########################################
    
    #if there is no wave or partial wave, don't look at background EC trends
    if (is.na(DS_Flag)==FALSE){
      ECB_overall <- NA
      if (is.na(Overall_Flags)==TRUE){
        Overall_Flags=DS_Flag
      } else {
        Overall_Flags=append(Overall_Flags,DS_Flag)
        Overall_Flags <- paste(Overall_Flags, collapse=',')
      }
    # determine the background EC trend
    } else if (length(unique(EC_curve_results[which(is.na(EC_curve_results$Ecb)==FALSE),'Ecb']))==0){
      ECB_overall <- NA
    } else if (length(unique(EC_curve_results[which(is.na(EC_curve_results$Ecb)==FALSE),'Ecb']))>1){
      ECB_overall <- 'M'
    } else if (unique(EC_curve_results[which(is.na(EC_curve_results$Ecb)==FALSE),'Ecb'])=='C'){
      ECB_overall <- 'C'
    } else if (unique(EC_curve_results[which(is.na(EC_curve_results$Ecb)==FALSE),'Ecb'])=='F'){
      ECB_overall <- 'F'
    } else if (unique(EC_curve_results[which(is.na(EC_curve_results$Ecb)==FALSE),'Ecb'])=='R'){
      ECB_overall <- 'R'
    }

    
    ###############################################
    # Find what sensors are in use during the event
    ###############################################
    ActiveSensor <- data.frame()
    
    # select active sensors based on the install and deactivation dates
    for (P in c(1,2,3,4,5)){
      query <- sprintf("SELECT * FROM chrl.sensors WHERE Install_Date < '%s' AND SiteID=%s AND Deactivation_Date > '%s' AND Probe_Number=%s",Date,SiteID,Date,P)
      Act <- dbGetQuery(con, query)
      if (nrow(Act)<1){
        query  <- sprintf("SELECT * FROM chrl.sensors WHERE Install_Date < '%s' AND SiteID=%s AND Deactivation_Date IS NULL AND Probe_Number=%s",Date,SiteID,P)
        Act <- dbGetQuery(con, query)
      } 
      if (nrow(Act)<1){
        query <- sprintf("SELECT * FROM chrl.sensors WHERE Install_Date IS NULL AND SiteID=%s AND Deactivation_Date IS NULL AND Probe_Number=%s",SiteID,P)
        Act <- dbGetQuery(con, query)
      } 
      if(nrow(Act)<1){
        next()
      }
      if(nrow(Act)>1){
        Act <- Act[1,]
      }
      ActiveSensor <- rbind(ActiveSensor,Act)
    }
    
    # Assign sensor ID to salt wave data
    for (C  in c(1:nrow(EC_curve_results))){
      PN <- EC_curve_results[C,'Probe']
      EC_curve_results[C,'SensorID'] <- ActiveSensor[ActiveSensor$probe_number==PN, 'sensorid'][1]
    }
    
    ########################################
    # Formatting salt wave data for database
    ########################################
    sw <- data.frame()
    for(r  in c(1:nrow(EC_curve_results))){
      if (EC_curve_results[r,'Comment']==""){
        EC_curve_results[r,"Comment"]= NA
      }
      
      #format for waves with no starting time
      if (is.na(EC_curve_results[r,'Starting_Time'])==TRUE){
        w <- data.frame(SiteID=EC_curve_results[r,'SiteID'], EventID= EC_curve_results[r,'EventID'],
                     SensorID= EC_curve_results[r,'SensorID'],Start_ECwave= NA, End_ECwave=NA,
                     Time_MaxEC= NA, StartingEC=NA, EndingEC=NA,PeakEC=NA,
                     Flags=EC_curve_results[r,'Comment'],Date=Date)
        sw <- rbind(sw,w)
        
      # format for all other waves
      } else{
        w <- data.frame(SiteID=EC_curve_results[r,'SiteID'],
                     EventID= EC_curve_results[r,'EventID'],
                     SensorID= EC_curve_results[r,'SensorID'],
                     Start_ECwave= format(EC[EC$Sec==EC_curve_results[r,'Starting_Time'],'TIMESTAMP'],'%H:%M:%S'),
                     End_ECwave=format(EC[EC$Sec==EC_curve_results[r,'Ending_Time'],'TIMESTAMP'],'%H:%M:%S'),
                     Time_MaxEC= format(EC[EC$Sec==EC_curve_results[r,'Time_Max'],'TIMESTAMP'],'%H:%M:%S'),
                     StartingEC=EC_curve_results[r,'Starting_EC'],
                     EndingEC=EC_curve_results[r,'Ending_EC'],
                     PeakEC=EC_curve_results[r,'Max_EC'],
                     Flags=EC_curve_results[r,'Comment'],
		     Date=Date)
        sw <- rbind(sw,w)
      }
    }
    
    Salt_waves <- rbind(Salt_waves,sw)
    
    ##------------------------------------------------------------------------------------------------------------------------------------
    ##-----------------------------------------------------------------Stage Data---------------------------------------------------------
    ##------------------------------------------------------------------------------------------------------------------------------------
    
    ###########################################
    # Extract and configure stage data for event
    ############################################
    Stage_Subset <- Stage[(Stage$DoseEventID==Event_Num) & (Stage$TIMESTAMP< EC[nrow(EC),"TIMESTAMP"])&(Stage$TIMESTAMP> EC[1,"TIMESTAMP"]),]
    if(nrow(Stage_Subset)==0){
      Stage_Subset <- data.frame(TIMESTAMP=rep(NA,100),PLS_Lvl=rep(NA,100),Sec=rep(NA,100))
      
    } else{
      Diff_Time <- (EC$TIMESTAMP[1]-Stage_Subset$TIMESTAMP[1])[[1]]
      
      # Align the seconds of stage values with the seconds from EC event 
      Stage_Subset$Sec <- seq(from=abs(Diff_Time)+1,by=5,length.out=nrow(Stage_Subset))
    }
    Stage_header <- colnames(Stage_Subset)[grep('PLS', colnames(Stage_Subset), ignore.case=T)]
    Stage_Subset$PLS_Lvl <- Stage_Subset[,Stage_header]*100
    
    
    ######################
    # Summarize  stage data
    ######################
    Stage_Summary <- data.frame()
    for (R in c(1:nrow(EC_curve_results))){
      
      #summary statistics
      Stage_Average <- mean(Stage_Subset$PLS_Lvl, na.rm=TRUE)
      Stage_Min <- min(Stage_Subset$PLS_Lvl,na.rm=TRUE)
      Stage_Max <- max(Stage_Subset$PLS_Lvl, na.rm=TRUE)
      Stage_Std <- sd(Stage_Subset$PLS_Lvl,na.rm=TRUE)
      S_Start <- mean(head(Stage_Subset$PLS_Lvl,6),na.rm=TRUE)
      S_End <- mean(tail(Stage_Subset$PLS_Lvl,6),na.rm=TRUE)
    
    
      # summarize the stage data into dataframe
      if (is.nan(Stage_Average)==TRUE){
        Starting_Stage <- NA;  Ending_Stage <- NA
        Stage_Dir <- NA;  Stage_Average <- NA
        Stage_Min <- NA; Stage_Max <- NA
        Stage_Dir <- NA;  S_Start <- NA
        S_End <- NA
        
        SS <- data.frame(StageAvg= Stage_Average,StageMin=Stage_Min, StageMax=Stage_Max, StageStd=Stage_Std, Start= S_Start,End=S_End)
        Stage_Summary <- rbind(Stage_Summary,SS)
        
      } else{
        SS <- data.frame(StageAvg= Stage_Average,StageMin=Stage_Min, StageMax=Stage_Max, StageStd=Stage_Std, Start= S_Start,End=S_End)
        Stage_Summary <- rbind(Stage_Summary,SS)
      }
    }
      
    # Finding the starting and stopping sec of the stage in relation to the EC event
    if (is.nan(mean(EC_curve_results$Starting_Time,na.rm=TRUE))==FALSE){
      Starting_Stage <- Stage_Subset[which(Stage_Subset$Sec>=(mean(EC_curve_results$Starting_Time,na.rm=TRUE)-2.5) & Stage_Subset$Sec<=(mean(EC_curve_results$Starting_Time,na.rm=TRUE)+2.5)),'PLS_Lvl']
      Ending_Stage <- Stage_Subset[which(Stage_Subset$Sec>=(mean(EC_curve_results$Ending_Time,na.rm=TRUE)-2.5) & Stage_Subset$Sec<=(mean(EC_curve_results$Ending_Time,na.rm=TRUE)+2.5)),'PLS_Lvl']
    } else {
      Starting_Stage <- Stage_Subset[1,'PLS_Lvl']
      Ending_Stage <- Stage_Subset[nrow(Stage_Subset),'PLS_Lvl']
    }
    
    # Determine how the stage is changing during the dump event
    if (is.na(Stage_Average)==FALSE){
      Diff= mean(Stage_Summary$Start,na.rm=TRUE)- mean(Stage_Summary$End, na.rm=TRUE)
      
      # define flags for  stage change
      if (length(Starting_Stage)==0 | length(Ending_Stage)==0){
        Stage_Dir <- NA
      } else if(Diff< (-0.5) ){
        Stage_Dir <- 'R'
      } else if (Diff > 0.5){
        Stage_Dir <- 'F'
      } else {
        Stage_Dir <- 'C'
      }
    }

    
    
      
    ##----------------------------------------------------------------------------------------------------------------------------------------
    ##--------------------------------------- Enter Data into Workbook------------------------------------------------------------------------
    ##----------------------------------------------------------------------------------------------------------------------------------------
    
    # load the empty excel sheet from directory
    wb <- loadWorkbook("Empty_autosalt_form.xlsx")
    
    #entering data
    for (W in  c(1:length(unique(EC_curve_results$Probe)))){
      if (W==1){
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Starting_Time'],sheet= "EC salt waves",startRow = 6, startCol = 15, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Starting_EC'],sheet= "EC salt waves",startRow = 6, startCol = 14, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ending_Time'],sheet= "EC salt waves",startRow = 7, startCol = 15, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ending_EC'],sheet= "EC salt waves",startRow = 7, startCol = 14, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Time_Max'],sheet= "EC salt waves",startRow = 8, startCol = 15, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Max_EC'],sheet= "EC salt waves",startRow = 8, startCol = 14, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Comment'],sheet= "EC salt waves",startRow = 8, startCol = 22, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ecb'],sheet= "EC salt waves",startRow = 8, startCol = 24, header=F)
        writeWorksheet(wb,sprintf('Sensor %s',EC_curve_results[EC_curve_results$Probe==W,'SensorID']),sheet= "EC salt waves",startRow = 4, startCol = 3, header=F)

      } else if (W==2){
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Starting_Time'],sheet= "EC salt waves",startRow = 6, startCol = 19, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Starting_EC'],sheet= "EC salt waves",startRow = 6, startCol = 18, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ending_Time'],sheet= "EC salt waves",startRow = 7, startCol = 19, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ending_EC'],sheet= "EC salt waves",startRow = 7, startCol = 18, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Time_Max'],sheet= "EC salt waves",startRow = 8, startCol = 19, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Max_EC'],sheet= "EC salt waves",startRow = 8, startCol = 18, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Comment'],sheet= "EC salt waves",startRow = 9, startCol = 22, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ecb'],sheet= "EC salt waves",startRow = 9, startCol = 24, header=F)
        writeWorksheet(wb,sprintf('Sensor %s',EC_curve_results[EC_curve_results$Probe==W,'SensorID']),sheet= "EC salt waves",startRow = 4, startCol = 4, header=F)
        
      } else if (W==3){
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Starting_Time'],sheet= "EC salt waves",startRow =13, startCol = 15, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Starting_EC'],sheet= "EC salt waves",startRow = 13, startCol = 14, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ending_Time'],sheet= "EC salt waves",startRow =14, startCol = 15, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ending_EC'],sheet= "EC salt waves",startRow = 14, startCol = 14, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Time_Max'],sheet= "EC salt waves",startRow = 15, startCol = 15, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Max_EC'],sheet= "EC salt waves",startRow = 15, startCol = 14, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Comment'],sheet= "EC salt waves",startRow = 10, startCol = 22, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ecb'],sheet= "EC salt waves",startRow = 10, startCol = 24, header=F)
        writeWorksheet(wb,sprintf('Sensor %s',EC_curve_results[EC_curve_results$Probe==W,'SensorID']),sheet= "EC salt waves",startRow = 4, startCol = 5, header=F)
        

      } else if (W==4){
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Starting_Time'],sheet= "EC salt waves",startRow = 13, startCol = 19, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Starting_EC'],sheet= "EC salt waves",startRow = 13, startCol = 18, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ending_Time'],sheet= "EC salt waves",startRow = 14, startCol = 19, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ending_EC'],sheet= "EC salt waves",startRow = 14, startCol = 18, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Time_Max'],sheet= "EC salt waves",startRow = 15, startCol = 19, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Max_EC'],sheet= "EC salt waves",startRow = 15, startCol = 18, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Comment'],sheet= "EC salt waves",startRow = 11, startCol = 22, header=F)
        writeWorksheet(wb,EC_curve_results[EC_curve_results$Probe==W,'Ecb'],sheet= "EC salt waves",startRow = 11, startCol = 24, header=F)
        writeWorksheet(wb,sprintf('Sensor %s',EC_curve_results[EC_curve_results$Probe==W,'SensorID']),sheet= "EC salt waves",startRow = 4, startCol = 6, header=F)
        

      }
    }
   
    
    # Insert EC Data
    writeWorksheet(wb,EC$TIMESTAMP,sheet= "EC salt waves",startRow = 6,startCol = 1, header=F)
    writeWorksheet(wb,EC$Sec,sheet= "EC salt waves",startRow = 6, startCol = 2, header=F)
    L <- length(unique(EC_curve_results$Probe))+2
    writeWorksheet(wb,EC[,c(3:L)],sheet= "EC salt waves",startRow = 6, startCol = 3, header=F)
    
    # Insert Stage Data
    writeWorksheet(wb,Stage_Subset$PLS_Lvl,sheet= "Stage data",startRow = 6, startCol = 4, header=F)
    writeWorksheet(wb,Stage_Subset$TIMESTAMP,sheet= "Stage data",startRow = 6, startCol = 2, header=F)
    writeWorksheet(wb,Stage_Subset$Sec,sheet= "Stage data",startRow = 6, startCol = 3, header=F)
    
    #do recalculations
    setForceFormulaRecalculation(wb,'Stage data',TRUE)
    setForceFormulaRecalculation(wb,'EC salt waves',TRUE)
    
    saveWorkbook(wb,sprintf("working_directory/%s_%s_.xlsx",S,Event_Num))
    
    # Upload excel sheet to google drive and save 
    drive_upload(media=sprintf("working_directory/%s_%s_.xlsx",S,Event_Num),path=sprintf('AutoSalt_Hakai_Project/Discharge_Calculations/AutoSalt_Events/%s.WS%s.%s.xlsx',Event_Num,S,Date), overwrite=TRUE,verbose = FALSE)
    autosalt_file_link <- sprintf('<a href=%s>%s.WS%s.%s.xlsx</a>',drive_link(sprintf('AutoSalt_Hakai_Project/Discharge_Calculations/AutoSalt_Events/%s.WS%s.%s.xlsx',Event_Num,S,Date)),Event_Num,S,Date)

    # Save google drive info for database
    ASlink <- data.frame(EventID=Event_Num,SiteID=S,Link= autosalt_file_link,Checked='N',Date=Date)
    
    #save link to form and delete from local computer
    Autosalt_forms <- rbind(Autosalt_forms,ASlink)
    file.remove(sprintf("working_directory/%s_%s.xlsx",S,Event_Num))
    
    ##-------------------------------------------------------------------------------------------------------------------------------------
    ##----------------------------------- Choosing CF Values for analysis -----------------------------------------------------------------
    ##-------------------------------------------------------------------------------------------------------------------------------------
    
    ###############################################################
    # Extract all calibration events during the events barrel period
    ################################################################
    
    # Calculate number of days between event and CF measurement 
    Barrel_Period_CFs$Days_Diff <- abs(as.Date(Barrel_Period_CFs$date)-as.Date(Date))
    
    Barrel_Period_CFs <- arrange(Barrel_Period_CFs,Days_Diff)
    Cal_to_use <-  Barrel_Period_CFs[1:nrow(Barrel_Period_CFs),]
    Cal_to_use <- Cal_to_use[which(Cal_to_use$location=='On Site'),]
    
    
    #######################################################
    # Compile all CF values to use for discharge calculation
    ########################################################
    Mi <- data.frame()
    for (i in Cal_to_use$caleventid){
      Date_Cal <- Cal_to_use[Cal_to_use$caleventid==i,]$date[1]
      PMP <- Cal_to_use[Cal_to_use$caleventid==i,]$pmp[1]
        
      # Extract calibration results
      query <- sprintf("SELECT * FROM chrl.calibration_results WHERE caleventid=%i",i)
      Cal_Result <- dbGetQuery(con, query)
      
      # Only use cal results that don't have flags
      Cal_Result  <- Cal_Result[is.na(Cal_Result$flags)==T | is.null(Cal_Result$flags)==T,]
      
      # Get all CF records associated with a particular ID
      for (Sensor_ID in unique(Cal_Result$sensorid)){
        
        query <- sprintf("SELECT * FROM chrl.sensors WHERE (sensorid=%i)",Sensor_ID)
        Sensor_info <- dbGetQuery(con, query)
        
        Probe_Num <- Sensor_info$probe_number[1]
        
        # calibration factor values
        CF <- Cal_Result[Cal_Result$sensorid==Sensor_ID,'cf_value']
        Per_Err <- Cal_Result[Cal_Result$sensorid==Sensor_ID,'per_err']
        CFID <- Cal_Result[Cal_Result$sensorid==Sensor_ID,'calresultsid']
        
        # compile data into new datafrae
        V <- data.frame(Sensor= Sensor_ID, CFID= CFID, Date= Date_Cal, PMP=PMP, 
                      CF=CF*(10^-6), Err=Per_Err,Probe_Num=Probe_Num, CalEventID= i)
        Mi <- rbind(Mi,V)
      }
    }
    
    # If there are more than 6 CF values per sensor, subset CF values based on recency to event
    Mi$DaysSince <- abs(Mi$Date-as.Date(Date,"%Y-%m-%d"))
    Mi= Mi[which(Mi$Sensor %in% ActiveSensor$sensorid),]
    for (x in ActiveSensor$sensorid){
      
      #if there are less than 4 CF values, choose CF based on recency to dump event
      if (nrow(Mi[Mi$Sensor==x,])<4){
        
        query <- sprintf("SELECT * FROM chrl.sensors WHERE (sensorid=%i)",x)
        Sensor_info <- dbGetQuery(con, query)
        
        Probe_Num <- Sensor_info$probe_number[1]
        
        # how many more CF we need to pick
        More_we_need= 4-nrow(Mi[Mi$Sensor==x,])
	      if (More_we_need ==0){
          next
        }
        CFS_we_have= Mi[Mi$Sensor==x,"CFID"]
        
        #subset CF to those we can choose to add to the list of CF values to use
        query <- sprintf("SELECT CE.date, CE.periodid, CE.Location,CE.PMP,CR.calresultsID,CR.CalEventID, CR.SiteID, CR.SensorID, CR.CF_value,CR.Per_Err, CR.Flags
                         FROM chrl.calibration_events as CE
                         JOIN chrl.calibration_results as CR ON CE.CalEventID= CR.CalEventID WHERE PeriodID=%i AND SensorID=%i",Period_ID,x)
        query=gsub("\\n\\s+", " ", query)
        AddingCFS <- dbGetQuery(con, query)
        
        AddingCFS$Days_Diff <- abs(as.Date(AddingCFS$date)-as.Date(Date))
        AddingCFS <- arrange(AddingCFS,Days_Diff)
        AddingCFS <- AddingCFS[which(AddingCFS$location=='On Site' & is.na(AddingCFS$flags)==TRUE),]
        for (g in CFS_we_have){
          AddingCFS <- AddingCFS[!(AddingCFS$calresultsid==g),]
        }
  
        # if there are no  CFs that can be added,  next
        if (nrow(AddingCFS[which(is.na(AddingCFS$date)==FALSE),])<1){
      		next
        }
        
        # select which CFs to add
      	AddingCFS <-  AddingCFS[1:More_we_need,]

      	# add new CFS to the CF dataframe
        for  (a in c(1:nrow(AddingCFS))){
          Sensor_ID= AddingCFS[a,'sensorid']
          PMP= AddingCFS[a,'pmp']; CF= AddingCFS[a,'cf_value']; Err= AddingCFS[a,'per_err'];
          CalEventID= AddingCFS[a,'caleventid']; DaySince= AddingCFS[a,'Days_Diff']
          V=data.frame(Sensor= Sensor_ID, CFID= AddingCFS[a,'calresultsid'], Date= Date_Cal, PMP=PMP, 
                       CF=CF*(10^-6), Err=Err,Probe_Num=Probe_Num, CalEventID= CalEventID, DaysSince=DaySince)
          Mi=rbind(Mi,V)
        }
      }
      
      # if there are more than 6 CF values, choose the 6 cloest in time to event
      if (nrow(Mi[Mi$Sensor==x,]) >6){
        sub <- Mi[Mi$Sensor==x,]
        sub <- sub[order(sub$DaysSince),]
        rownames_delete <- rownames(sub[c(7:nrow(sub)),])
        Mi <- Mi[!(row.names(Mi) %in% rownames_delete),]
      }
    }
    
      
    ##---------------------------------------------------------------------------------------------------------------------------------
    ##------------------------------------------------Calculating Discharge------------------------------------------------------------
    ##---------------------------------------------------------------------------------------------------------------------------------
  
    Discharge_Results <- data.frame()
    
    # choosing the sensors headers names to use
    for (Sen in c(1,2,3,4,5)){
      if (Sen!=1){
        if (length(grep("THRECS_", Headers, ignore.case=T))>0){
          if( grep("THRECS_", Headers, ignore.case=T)==Sen){
            Header_Use <-grep("THRECS_", Headers, ignore.case=T)
          } else {
            Header_Use=NA
          }
        } else {
          Header_Use <- grep(as.character(Sen), Headers, ignore.case=T)
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
      
      if (is.na(Header_Use) ==TRUE)
        next()
      
      # start and end time of curve
      Start <- EC_curve_results[ EC_curve_results$Probe==Sen, 'Starting_Time']
      End <- EC_curve_results[ EC_curve_results$Probe==Sen, 'Ending_Time']
      SID <- ActiveSensor[ActiveSensor$probe_number==Sen,'sensorid']
      
      # If there is no Start time then we cannot calculate discharge
      if(is.na(Start)==TRUE | (Start>End)){
        DR <- data.frame(SiteID=SiteID, EventID=Event_Num, SensorID=SID, CFID=NA, Discharge=NA, Err=NA, CalEventID=NA )
        Discharge_Results <- rbind(Discharge_Results,DR)
        next()
      }
      
      
      # Values used in discharge calculation equation
      deltaT <- EC[2,'Sec']- EC[1,'Sec']
      Uncert_dump <- (0.0726/Salt_Vol)*100
      
      #starting and ending background EC levels
      ECb_start <- EC_curve_results[EC_curve_results$Probe==Sen, 'Starting_ECb']
      ECb_end <- EC_curve_results[EC_curve_results$Probe==Sen, 'Ending_ECb']
      
      # subset the EC data to values between the start and end of salt wave
      EC_cut <- EC[EC$Sec >= Start & EC$Sec<=End,Headers[Header_Use]]
      Delta_ECb <- (ECb_start-ECb_end)/(length(EC_cut)*deltaT)
      
      ######################
      # Calculating Discharge
      ######################
      Mi_use <- Mi[which(Mi$Probe_Num==Sen),]
      if (nrow(Mi_use)==0){
        DR <- data.frame(SiteID=SiteID, EventID=Event_Num, SensorID=SID, CFID=NA,Discharge=NA, Err=NA, CalEventID=NA)
        Discharge_Results <- rbind(Discharge_Results,DR)
        next()
      }
      
      for (M in c(1:nrow(Mi_use))){
        
        #identify CF measurements to use in calculation
        CF <- Mi_use[M,'CF']
        Err <- Mi_use[M,'Err']
        CalID <- Mi_use[M,'CFID']
        CalEventID <- Mi_use[M,'CalEventID']
        
        A <- array(); ER <- array()
        cou=0
        
        # the calculation
        for (E  in EC_cut){
          cou=cou+deltaT
          if (is.na(E)==TRUE){
            next()
          }
          if (E >(ECb_start-(Delta_ECb*cou))){
            C <- (E-(ECb_start-(Delta_ECb*cou)))*CF
            A <- append(A,C)
            ER <- append(ER, (((0.005/E)*100+ Err)/100*C))
          }
        }
        
        Dis <- (Salt_Vol/1000)/ (sum(A,na.rm=TRUE)*deltaT)
        DisUncer <- (sum(ER,na.rm=TRUE)/sum(A, na.rm=TRUE)*100)+Uncert_dump
        
        #summarize discharge results
        DR <- data.frame(SiteID=SiteID, EventID=Event_Num, SensorID=SID, CFID=CalID,Discharge=Dis, Err=DisUncer, CalEventID=CalEventID )
        Discharge_Results <- rbind(Discharge_Results,DR)
      }
    }
  
  Discharge_Results <- Discharge_Results[which(Discharge_Results$Discharge <100 & is.na(Discharge_Results$Discharge)==FALSE),]
  
  # If there are no discharge results, summarize event for database and move to next event
  if (nrow(Discharge_Results)==0){
    DS <- data.frame(EventID=Event_Num, SiteID=S,PeriodID=Period_ID, Date= Date,
                   Temp= Temp,  Start_Time=Time, Stage_DoseRelease= Stage_Start,
                   Stage_Average= mean(Stage_Summary$StageAvg), Stage_Min= mean(Stage_Summary$StageMin),
                   Stage_Max= mean(Stage_Summary$StageMax), Stage_Std= mean(Stage_Summary$StageStd),
                   Stage_Dir= Stage_Dir,Salt_Volume= Salt_Vol,Discharge_Avg=NA, Uncert=  NA,
                   Flags=DS_Flag,ECb=ECB_overall,Mixing=NA, Notes= DisSummaryComm)
    Discharge_Summary <- rbind(Discharge_Summary,DS)
    next()
  }
    
  ##############################################
  # Calculate the Error and average discharge
  ##############################################
  Discharge_Results$AbsErr <- Discharge_Results$Discharge*(Discharge_Results$Err/100)
  Discharge_Results$QP <- Discharge_Results$Discharge+Discharge_Results$AbsErr
  Discharge_Results$QM <- Discharge_Results$Discharge-Discharge_Results$AbsErr
  
  
  
  # Determine the number for flags that were recorded for each salt wave
  for (R in c(1:nrow(Discharge_Results))){
      SID <- Discharge_Results[R,'SensorID']
      if (length(EC_curve_results[EC_curve_results$SensorID==SID,'Comment'])==0){
        Discharge_Results[R,'Flags'] <- NA
        Discharge_Results[R,'Flag_count'] <- NA
        Discharge_Results[R,'SD'] <- 'N'
      } else {
        Discharge_Results[R,'Flags'] <- EC_curve_results[EC_curve_results$SensorID==SID,'Comment']
        FG= unlist(strsplit(EC_curve_results[EC_curve_results$SensorID==SID,'Comment'], ","))
      
        if (length(FG)==1){
          if (is.na(FG)==TRUE){
            Discharge_Results[R,'Flag_count']=0
          } else {
            Discharge_Results[R,'Flag_count']= 1
          }
        } else {
          Discharge_Results[R,'Flag_count'] <- length(FG)
        }
        
      }
  }
  
  
  ###########################################
  # Look for timing offset between salt waves
  ###########################################
  
  # subset discharge results to those whose salt waves have less than 2 flagging codes
  Probes_low_flag_count= unique(Discharge_Results[which(Discharge_Results$Flag_count <2),'SensorID'])
  
  if (length(which(is.na(EC_curve_results$Time_Max)==TRUE))< length(EC_curve_results$Time_Max) & (is.na(DS_Flag)==TRUE) & length(Probes_low_flag_count)>1){
    Combo <- combn(EC_curve_results[EC_curve_results$SensorID %in% Probes_low_flag_count,'Time_Max'],2)
    D_Array <- array()
    for (C in c(1:ncol(Combo))){
      D_Array <- append(D_Array,diff(as.numeric(Combo[,C])))
    }
    
    # finding if there is an offset between salt waves
    if (sum(is.na(D_Array))<length(D_Array)){
      if (is.na(DisSummaryComm)==TRUE){
        if (abs(max(D_Array,na.rm=TRUE))> 35){
          if (is.na(Overall_Flags)==TRUE){
            Overall_Flags='OS'
          } else {
            Overall_Flags=append(Overall_Flags,'OS')
            Overall_Flags <- paste(Overall_Flags, collapse=',')
          }
        }
      } else {
        if (abs(max(D_Array,na.rm=TRUE))> 35){
          if (is.na(Overall_Flags)==TRUE){
            Overall_Flags='OS'
          } else {
            Overall_Flags=append(Overall_Flags,'OS')
            Overall_Flags <- paste(Overall_Flags, collapse=',')
          }
        }
      }
    }
  }
  
  
  # Only summarize values if salt wave has less than 2 flags 
  Max_Q <- max(Discharge_Results[(Discharge_Results$Flag_count <2)  , 'QP'],na.rm=TRUE)
  Min_Q <- min(Discharge_Results[(Discharge_Results$Flag_count <2), 'QM'],na.rm=TRUE)

  Average_Discharge <- mean(Discharge_Results[which(Discharge_Results$Flag_count<2), 'Discharge'], na.rm=TRUE)
  TotalUncert <-  max(((Max_Q-Average_Discharge)/Average_Discharge*100),((Average_Discharge-Min_Q)/Average_Discharge*100))

  # Flag which discharge values are part of the average discharge calculation
  Discharge_Results[(Discharge_Results$Flag_count<2),'Used'] <- 'Y'
  Discharge_Results[!(Discharge_Results$Flag_count<2) ,'Used'] <- 'N'
  
  # Determine the mixing
  Mixing <- AutoSalt_Mixing(Discharge_Results[which(Discharge_Results$Used=='Y'),])

  
  ##########################################
  # Summarizing the autosalt discharge event
  ##########################################
  if (is.na(Average_Discharge)==TRUE | is.null(Average_Discharge)==TRUE){
    DS <- data.frame(EventID=Event_Num,
                  SiteID=S,
                  PeriodID=Period_ID,
                  Date= Date,
                  Temp= Temp,
                  Start_Time=Time, 
                  Stage_DoseRelease= Stage_Start,
                  Stage_Average= mean(Stage_Summary$StageAvg),
                  Stage_Min= mean(Stage_Summary$StageMin),
                  Stage_Max= mean(Stage_Summary$StageMax),
                  Stage_Std= mean(Stage_Summary$StageStd),
                  Stage_Dir= Stage_Dir,
                  Salt_Volume= Salt_Vol,
                  Discharge_Avg=NA,
                  Uncert=  NA,
                  Flags=Overall_Flags,
                  ECb=ECB_overall,
                  Mixing=NA,
                  Notes= DisSummaryComm)
    Discharge_Summary <- rbind(Discharge_Summary,DS)
    next()
  }
  
  DS <- data.frame(EventID=Event_Num,
                SiteID=S,
                PeriodID=Period_ID,
                Date= Date,
                Temp= Temp,
                Start_Time=Time, 
                Stage_DoseRelease= Stage_Start,
                Stage_Average= mean(Stage_Summary$StageAvg),
                Stage_Min= mean(Stage_Summary$StageMin),
                Stage_Max= mean(Stage_Summary$StageMax),
                Stage_Std= mean(Stage_Summary$StageStd),
                Stage_Dir= Stage_Dir,
                Salt_Volume= Salt_Vol,
                Discharge_Avg=Average_Discharge,
                Uncert=  TotalUncert,
                Flags=Overall_Flags,
                ECb=ECB_overall,
                Mixing=Mixing,
                Notes= DisSummaryComm)
  
    
    ##-----------------------------------------------------------------------------------------------------------------------------
    ##------------------------------preparing results in database format-----------------------------------------------------------
    ##------------------------------------------------------------------------------------------------------------------------------
    
  
    AD <- data.frame()
    for (r in c(1:nrow(Discharge_Results))){
      DC <- data.frame(EventID=Discharge_Results[r,'EventID'],
                      SiteID= Discharge_Results[r,'SiteID'],
                     SensorID= Discharge_Results[r,'SensorID'],
                     CFID= Discharge_Results[r,'CFID'],
                     Discharge= Discharge_Results[r,'Discharge'],
                     Uncertainty= Discharge_Results[r,'Err'],
                     Used= Discharge_Results[r,'Used']
                    )
      AD <- rbind(AD,DC)
    }
    
    Discharge_Summary <- rbind(Discharge_Summary,DS)
    All_Discharge <- rbind(All_Discharge,AD)
  
  }

  
  
  ##-----------------------------------------------------------------------------------------------------------
  ##-------------------------------------------Enter data into database------------------------------------------
  ##-----------------------------------------------------------------------------------------------------------

  if(nrow(Discharge_Summary) ==0){
    next()
  }
  for (r in c(1:nrow(Discharge_Summary))){

   Query <- sprintf("INSERT INTO chrl.autosalt_summary VALUES (%s,%s,%s,'%s',%s,'%s',%s,%s,%s,%s,%s,'%s',%s,%s,%s,'%s','%s',%s,'%s');",
           Discharge_Summary[r,"EventID"],
           Discharge_Summary[r,"SiteID"],
           Discharge_Summary[r,"PeriodID"],
           as.Date( Discharge_Summary[r,'Date']),
           Discharge_Summary[r,"Temp"],
           Discharge_Summary[r,"Start_Time"],
           Discharge_Summary[r,"Stage_DoseRelease"],
           Discharge_Summary[r,"Stage_Average"],
           Discharge_Summary[r,"Stage_Min"],
           Discharge_Summary[r,"Stage_Max"],
           Discharge_Summary[r,"Stage_Std"],
           Discharge_Summary[r,"Stage_Dir"],
           Discharge_Summary[r,"Salt_Volume"],
           Discharge_Summary[r,"Discharge_Avg"],
           Discharge_Summary[r,"Uncert"],
           Discharge_Summary[r,"Flags"],
           Discharge_Summary[r,'ECb'],
           Discharge_Summary[r,"Mixing"],
           Discharge_Summary[r,"Notes"]
           )

   Query <- gsub("\\n\\s+", " ", Query)
   Query <- gsub('NA',"NULL",Query)
   Query <- gsub('NaN',"NULL",Query)
   Query <- gsub("'NULL'","NULL",Query)
   dbSendQuery(con, Query)
 }

 if (nrow(Salt_waves)>0){
   for (r in c(1:nrow(Salt_waves))){
     Query <- sprintf("INSERT INTO chrl.salt_waves (SiteID, EventID, SensorID,Start_ECWave, End_ECWave,Time_MaxEC,StartingEC, EndingEC,PeakEC,Flags, Comments,event_date)
     VALUES (%s,%s,%s,'%s','%s','%s',%s,%s,%s,'%s',NULL,'%s')",
                    Salt_waves[r,"SiteID"],
                    Salt_waves[r,"EventID"],
                    Salt_waves[r,"SensorID"],
                    Salt_waves[r,"Start_ECwave"],
                    Salt_waves[r,"End_ECwave"],
                    Salt_waves[r,"Time_MaxEC"],
                    Salt_waves[r,"StartingEC"],
                    Salt_waves[r,"EndingEC"],
                    Salt_waves[r,"PeakEC"],
                    Salt_waves[r,"Flags"],
		    Salt_waves[r,'Date'])
     Query <- gsub("\\n\\s+", " ", Query)
     Query <- gsub('NA',"NULL", Query)
     Query <- gsub('NaN',"NULL",Query)
     Query <- gsub("'NULL'","NULL",Query)

     dbSendQuery(con, Query)
   }
 }

 if (nrow(All_Discharge)>0){
   for (r in c(1:nrow(All_Discharge))){
     Query <- sprintf("INSERT INTO chrl.all_discharge_calcs (EventID, SiteID, SensorID,CFID, Discharge, Uncertainty,Used) VALUES (%s,%s,%s,%s,%s,%s,'%s')",
                    All_Discharge[r,'EventID'],
                    All_Discharge[r,"SiteID"],
                    All_Discharge[r,"SensorID"],
                    All_Discharge[r,"CFID"],
                    All_Discharge[r,"Discharge"],
                    All_Discharge[r,"Uncertainty"],
                    All_Discharge[r,"Used"])
     Query <- gsub("\\n\\s+", " ", Query)
     Query <- gsub('NA',"NULL", Query)
     Query <- gsub("'NULL'","NULL",Query)
     dbSendQuery(con, Query)
   }
 }

 if (nrow(Autosalt_forms)>0){
   for (r in c(1:nrow(Autosalt_forms))){

     Query <- sprintf("INSERT INTO chrl.autosalt_forms (EventID, SiteID, Link, Checked, Edits_made,event_date) VALUES (%s,%s,'%s','N',NULL,'%s')",
                    Autosalt_forms[r,"EventID"],
                    Autosalt_forms[r,"SiteID"],
                    Autosalt_forms[r,'Link'],
		    Autosalt_forms[r,'Date'])
     Query <- gsub("\\n\\s+", " ", Query)
     Query <- gsub('NA',"NULL", Query)
     Query <- gsub("'NULL'","NULL",Query)
     Query <- gsub('NaN',"NULL",Query)
     dbSendQuery(con, Query)
   }
 }
}


dbDisconnect(con)
options(warn = 0)



    
