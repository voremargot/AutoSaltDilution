#!/usr/bin/Rscript
##-----------------------------------------------------------------------------------------------
# Created by: Margot Vore 
# July 2021
# 
# This code is designed to update the barrel fill,  sensor, and field visit tables in the database
# after field work has been done. Using the autosalt field visit device magic form, information about
# work at the site will be sent to the device magic table in the database. Using these data from the table,
# the code sorts it and performs checks to make sure the data aligns with what is already in the database.
# This allows the tables to be updated with little user intervention, unless there are discrepancies between 
# the field data and what is in the database. In this case a message will print the specific issue for the user
# to fix.


##-----------------------------------------------------------------------------------------------
## ---------------------------Setting up the work space------------------------------------------
##-----------------------------------------------------------------------------------------------

readRenviron('C:/Program Files/R/R-4.1.0/.Renviron')
# readRenviron('/home/autosalt/AutoSaltDilution/other/.Renviron')
setwd("C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/GitHub/R_code")
# setwd("/home/autosalt/AutoSaltDilution/R_code")


options(java.parameters = "-Xmx8g")

print("-------------------------------------------------")
print("-------------------------------------------------")
print(sprintf("Date and Time:%s", Sys.time()))

#Libraries
suppressMessages(library(DBI))
suppressMessages(library(data.table))
suppressMessages(library(dplyr))
suppressMessages(library(tidyr))
suppressMessages(library(generics))
suppressMessages(source('Device_magic_functions.R'))

# function to remove empty strings from vector
Empty_string <-  function(x){
  Vl= which(x=="" | x==" ")
  if (length(Vl)>0){
    Out= x[-Vl]
  } else {
    Out=x
  }
  return(Out)
}

options(warn = - 1)  
con <<- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

##----------------------------------------------------------------------------------------------------------------------
##-----------------------------------------------------------------------------------------------------------------------
##-----------------------------------------------------------------------------------------------------------------------

#pull any entries from device magic table where at least one of the verification columns is no
query= "SELECT * FROM chrl.Device_Magic WHERE visit_added='No' OR barrel_added='No' OR sensor_added='No'"
Field= dbGetQuery(con, query)

# check if there are any field visits that have happened
if (nrow(Field)==0){
  print("No new field visits have occurred or need to be rerun.")
  
} else {
  Results=c()
  
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
      print(sprintf("Site visit at %s on %s", S, Date))
      
      #checks and compiles all barrel fill updates
      if (any((working$barrel_fill=='yes' & working$barrel_added=='No'))==TRUE){
        # runs barrel fill update function
        Num = barrel_fill_update(working, S)
        
        # if the function was successful print that in log notes
        if (as.numeric(Num[1])==1){
          print("Barrel Fill table was updated")
          
          #updates device magic table if barrel information was successfully compiled
          for (D in DMI){
            query= sprintf("UPDATE chrl.device_magic SET barrel_added='Yes' WHERE dmid=%s",D)
            dbSendQuery(con,query)
          }
        # if there was an error in updating the barrel fill table,  print what it was
        } else {
          print(sprintf("Error in updating Barrel Fill table- %s",Num[2]))
        }
        Results=append(Results,as.numeric(Num[1]))
      } else {
        # if a barrel fill didn't happen during a field visit, update the device magic table
        # to say it has been checked
          for (D in DMI){
            query= sprintf("UPDATE chrl.device_magic SET barrel_added='Yes' WHERE dmid=%s",D)
            dbSendQuery(con,query)
          }
        }
      
      
      ##-------------------------------------------------------------------------
      ##-------------------------------------------------------------------------
      # checks and complies all changes made to the sensors
      if (any((working$ec_sensor_change=='yes'& working$sensor_added=='No'))==TRUE){
        # runs the sensor update function
        Num= sensor_update(working,S)
        
        #print all comments that occurred during the sensor update
        for (C in Num[-1]){
          print(C)
        }
        # if there are no errors, update the device magic table
        if (as.numeric(Num[1])==1){
          for (D in DMI){
            query= sprintf("UPDATE chrl.device_magic SET sensor_added='Yes' WHERE dmid=%s",D)
            dbSendQuery(con,query)
          }
        }
        Results=append(Results,as.numeric(Num[1]))
      } else {
        # if a sensor change didn't happen during a field visit, update the device magic table
        # to say it has been checked
        for (D in DMI){
          query= sprintf("UPDATE chrl.device_magic SET sensor_added='Yes' WHERE dmid=%s",D)
          dbSendQuery(con,query)
        }
      }
      

      #------------------------------------------------------------------------
      #------------------------------------------------------------------------   
      #updates field visit table 
      if (any(working$visit_added=='No')==TRUE){
        # run field visit update function
        field_visit_update(working,S)
        
        print("Field_Visit table was updated")
        Results= append(Results, 1)
        
        # update the device magic table
        for (D in DMI){
          query= sprintf("UPDATE chrl.device_magic SET visit_added='Yes' WHERE dmid=%s",D)
          dbSendQuery(con,query)
        }
      } 
    }
  }
  
  #if any of the tests were not  completed, throw an error so  we can alert the user of the issues
  if (any(grepl(0,Results)==TRUE)){
    dbDisconnect(con)
    options(warn = 0)
    stop("One or more field events was not inserted into the database correctly. Please check the log and fix the error in the database")
  }
}

#delete events from the device magic table that are older than 60 days and have been fully verified
query=sprintf("SELECT DMID FROM chrl.device_magic WHERE date_visit < '%s' AND visit_added='Yes' AND barrel_added='Yes' AND sensor_added='Yes' AND CF_added='YES'",(Sys.Date()-60))
delete_data=dbGetQuery(con,query)

if (nrow(delete_data)>0){
  for(x in delete_data){
    query= sprintf("DELETE FROM chrl.device_magic where DMID=%s",x)
    dbSendQuery(con,query)
  }
}

dbDisconnect(con)
options(warn = 0)


