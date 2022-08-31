#!/usr/bin/Rscript
##-----------------------------------------------------------------------------------------------
# Created by: Margot Vore 
# July 2021
# 
# This code is used to validate the CF measurements that are put into the database with
# what is entered into the device magic forms. This code can determine if the PMP values 
# differ from what was done in the field as well as make sure that there is no duplicate data.
# The user will have to fix any issues that the code identifies by hand.

##-----------------------------------------------------------------------------------------------
## ---------------------------Setting up the work space------------------------------------------
##-----------------------------------------------------------------------------------------------
# set working environment
#readRenviron('C:/Program Files/R/R-4.1.0/.Renviron')
readRenviron('/home/autosalt/AutoSaltDilution/other/.Renviron')
#setwd("C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/GitHub/R_code")
setwd("/home/autosalt/AutoSaltDilution/R_code")


options(java.parameters = "-Xmx8g")
options(warn = - 1)  



#Libraries
suppressMessages(library(DBI))
suppressMessages(library(data.table))
suppressMessages(library(dplyr))
suppressMessages(library(tidyr))
suppressMessages(library(generics))
suppressMessages(source('Device_magic_functions.R'))


Empty_string <-  function(x){
  # function to remove empty strings from vector
  Vl= which(x=="" | x==" ")
  if (length(Vl)>0){
    Out= x[-Vl]
  } else {
    Out=x
  }
  return(Out)
}

CF_event_check <-  function(working, S){
  # This function performs checks that the CF sheets entered into the database
  # match what was recorded in the device magic forms. It looks at several
  # potential error scenarios and will tell the user if any issues are found.
  
  Warning= NA
  
  # select the PMP values from the device magic form
  PMP= Empty_string(trimws(unlist(strsplit(working$time_barrel_period,','))))
  PMP=PMP[!(is.na(PMP)==TRUE)]
  
  #select the number of trials from the device magic form 
  Trials= as.numeric(Empty_string(trimws(unlist(strsplit(working$trials_cf,',')))))
  Trials= Trials[!(is.na(Trials)==TRUE)]
  
  # select calibration events from the database
  query= sprintf("Select * from chrl.calibration_events  WHERE SiteID=%s AND Date='%s'",S,Date)
  CF= dbGetQuery(con,query)
  
  # if there are no CF in the database, raise error
  if (nrow(CF)==0){
    Warning= sprintf('No CF sheets have been uploaded from the field visit at site %s on %s. Please upload all sheets.',S,Date)
    return(c(0, Warning))
  }
  
  #if there is a barrel refill,there should not be any Mid PMP recorded
  if(any(working$barrel_fill=='yes')){
    if ('Mid' %in% PMP){
      Warning= sprintf("As there was a barrel fill on %s at site %s, a Mid CF event should not have taken place. Please  review the CF sheets and device magic note",Date, S)
      return(c(0, Warning))
    } 
  }
  
  # compare the number of trials recorded from the device magic form and database
  if (setequal(PMP,CF$pmp)==TRUE & (length(PMP)==length(CF$pmp))){
    for (x in c(1:length(PMP))){
      P= PMP[x]
      Tr= Trials[x]
      if (Tr!=CF[CF$pmp==P,'trial']){
        if (Tr >CF[CF$pmp==P,'trial']){
          Warning= sprintf("Trial numbers don't match! Its likely not all CF sheets for %s test at site %s on %s have been uploaded to google drive. Please finish uploading the field sheets.",P,S,Date)
          return(c(0, Warning))
        } else {
          Warning= sprintf("Trial numbers don't match! There are more CF sheets uploaded for %s test at site %s on %s than were expected. Please review the database and CF sheets to correct the error.",P,S,Date)
          return(c(0, Warning))
        }
      }
    } 
  
    # if there is a discrepancy betweeen PMP value,  display the issue
  } else {
    Warning= paste(c("The CF table shows",CF$pmp, " measurements and the device magic forms show",PMP,". Please determine the correct CF measurements"),collapse=" ")
    return(c(0, Warning))
  }
  
  # return a 1 if the code ran successfully
  return(c(1,Warning))
}



# connect to database
con <<- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

##----------------------------------------------------------------------------------------------------------------------
##-----------------------------------------------------------------------------------------------------------------------
##-----------------------------------------------------------------------------------------------------------------------

#pull any entries from device magic table where CF  verification columns is no
query= "SELECT * FROM chrl.Device_Magic WHERE CF_added='No' "
Field= dbGetQuery(con, query)

Results=c()

# if there are no device magic entries to check
if (nrow(Field)==0){
  print("No new CF events need to be checked.")
  
} else {
  
  # pull out all field visit dates
  Visit_Dates= unique(Field$date_visit)
  
  for (D in Visit_Dates){
    Date= as.Date(D,"1970-01-01")
    
    #subset data by field visit date
    Subset= Field[which(Field$date_visit==Date),]
    
    # select all visit data from a single site on a given day
    for (S in unique(Subset$siteid)){
      working= Subset[which(Subset$siteid==S),]
      DMI= working$dmid
      
      print('******************************')
      print(sprintf("CF check at %s on %s", S, Date))
      
      # do a check of the CF event table to make sure all documents have been uploaded and filled 
      # out correctly
      if (any(working$cf_event=='yes' & working$cf_added=='No')==TRUE){
        
        #run CF event check function
        Num= CF_event_check(working, S)
        
        # prints the code ran successfully with no errors
        if (as.numeric(Num[1])==1){
          print("CF events were succesfully varified")
          
          #updates device magic table if CF information matches the device magic inputs
          for (D in DMI){
            query= sprintf("UPDATE chrl.device_magic SET cf_added='Yes' WHERE dmid=%s",D)
            dbSendQuery(con,query)
          }
        } else {
          # if the CF values in the CF event tables do not correspond with what the 
          # device magic tables says, then print the problems for the log
          print(sprintf("Error in validating CF values- %s",Num[2]))
        }
        Results=append(Results,as.numeric(Num[1]))
        
      } else {
        # if a CF event didn't occur, mark the device magic table as checked
        for (D in DMI){
          query= sprintf("UPDATE chrl.device_magic SET cf_added='Yes' WHERE dmid=%s",D)
          dbSendQuery(con,query)
        }
      }
    }
  }
}

# if there were any errors, use stop function to output the error
if (any(grepl(0,Results)==TRUE)){
  dbDisconnect(con)
  options(warn = 0)
  stop("One or more CF events were incorrect. Please check the log and fix the error in the database")
}

dbDisconnect(con)
options(warn = 0)




