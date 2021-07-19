readRenviron('C:/Program Files/R/R-4.1.0/.Renviron')
setwd("C:/Users/margo.DESKTOP-T66VM01/Desktop/VIU/GitHub/R_code")
options(java.parameters = "-Xmx8g")

print("-------------------------------------------------")
print("-------------------------------------------------")
print(sprintf("Date and Time:%s", Sys.time()))

#Libraries
suppressMessages(library(DBI))
suppressMessages(library(data.table))
suppressMessages(library(dplyr))
suppressMessages(library(tidyr))
suppressMessages(source('Device_magic_functions.R'))

options(warn = - 1)  
con <<- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

query= "SELECT * FROM chrl.Device_Magic WHERE visit_added='No' OR barrel_added='No' OR sensor_added='No' "
Field= dbGetQuery(con, query)

if (nrow(Field)==0){
  print("No new field visits have occurred")
  
} else {
  Results=c()
  Visit_Dates= unique(Field$date_visit)
  
  for (D in Visit_Dates){
    Date= as.Date(D,"1970-01-01")
    Subset= Field[which(Field$date_visit==Date),]
    
    
    for (S in unique(Subset$siteid)){
      working= Subset[which(Subset$siteid==S),]
      DMI= working$dmid
      
      print(sprintf("Site visit at %s on %s", S, Date))
      
      #checks and compiles all barrel fill updates
      if (any(working$barrel_fill=='yes')==TRUE){
        Num = barrel_fill_update(working, S)
        if (as.numeric(Num[1])==1){
          print("Barrel Fill table was updated")
          for (D in DMI){
            query= sprintf("UPDATE chrl.device_magic SET barrel_added='Yes' WHERE dmid=%s",D)
            dbSendQuery(con,query)
          }
        } else {
          print(sprintf("Error in updating Barrel Fill table- %s",Num[2]))
        }
        Results=append(Results,as.numeric(Num[1]))
      } else {
          for (D in DMI){
            query= sprintf("UPDATE chrl.device_magic SET barrel_added='Yes' WHERE dmid=%s",D)
            dbSendQuery(con,query)
          }
        }
      
      # checks and complies all changes made to the sensors
      if (any(working$ec_sensor_change=='yes')==TRUE){
        Num= sensor_update(working,S)
        for (C in Num[-1]){
          print(C)
        }
        Results=append(Results,as.numeric(Num[1]))
      } else {
        for (D in DMI){
          query= sprintf("UPDATE chrl.device_magic SET sensor_added='Yes' WHERE dmid=%s",D)
          dbSendQuery(con,query)
        }
        
      }
      
      #updates field visit table 
      field_visit_update(working,S)
      print("Field_Visit table was updated")
      for (D in DMI){
        query= sprintf("UPDATE chrl.device_magic SET visit_added='Yes' WHERE dmid=%s",D)
        dbSendQuery(con,query)
      }

    }
  }
  if (grepl(0,Results)==TRUE){
    dbDisconnect(con)
    options(warn = 0)
    stop("One or more field events was not inserted into the database correctly. Please check the log and fix the error in the database")
  }
  
}

dbDisconnect(con)
options(warn = 0)
print("------------------------------------------------------------------------------------")

