readRenviron('C:/Program Files/R/R-4.1.0/.Renviron')
options(java.parameters = c("-XX:+UseConcMarkSweepGC", "-Xmx8192m"))

setwd("Desktop")

#Libraries

library(DBI)
library(data.table)
library(XLConnect)
library(dplyr)
library(googledrive)
library(tidyr)
library(ggplot2)


options(warn = - 1)  

# Connect to database
con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

query= "SELECT * FROM chrl.Device_Magic WHERE new='Yes'"
Field= dbGetQuery(con, query)
Field[2,'siteid']=626
Field[2,'date_visit']='2021-06-24'

Visit_Dates= unique(Field$date_visit)

Old_data= dbGetQuery(con, "SELECT * FROM chrl.field_visits")
for (D in Visit_Dates){
  Subset= Field[which(Field$date_visit==D),]
  Date= as.Date(unique(Subset$date_visit))
  
  for (S in unique(Subset$siteid)){
    
    working= Subset[which(Subset$siteid==S),]
    Time= min(format(as.POSIXct(working$time_visit,"%H:%M:%S"),'%H:%M:%S'))
    Tech1= working[which(working$technician!='Other'),'technician']
    Tech2= working[which(is.na(working$technician_other)==FALSE),'technician_other']
    Technicians=paste(c(Tech1, Tech2),collapse = '; ')
    
    if (any(working$barrel_fill=='yes')==TRUE){
      Barrel_Fill='Y'
    } else {
      Barrel_Fill='N'
    }
    
    if (any(working$cf_event=='yes')==TRUE){
      CF='Y'
    } else {
      CF='N'
    }
    
    if (any(working$ec_sensor_change=='yes')==TRUE){
      SensorChange='Y'
    } else {
      SensorChange='N'
    }
    
    Weather=paste(Working[which(is.na(Working$notes_weather)==FALSE),'notes_weather'],collapse = '; ')
    Repairs= paste(Working[which(is.na(Working$notes_repairs)==FALSE),'notes_repairs'],collapse = '; ')
    ToDo=paste(Working[which(is.na(Working$notes_todo)==FALSE),'notes_todo'],collapse = '; ')
    Other=paste(Working[which(is.na(Working$notes_other)==FALSE),'notes_other'],collapse = '; ')
    
    DMI= paste(working$dmid,collapse = '; ')
    
    if (nrow(Old_data[which(Old_data$date==Date & Old_data$siteid==S),]) > 0){
      Old_wk= Old_data[which(Old_data$date==Date & Old_data$siteid==S),]
      ID= Old_wk$fid
      Time= min(format(as.POSIXct(Old_wk$time,"%H:%M:%S"),'%H:%M:%S'),Time)
      
      Technicians= unique(append(unlist(strsplit(Technicians,"; ")), unlist(strsplit(Old_wk$technicians, "; "))))
      Technicians=paste(Technicians, collapse = '; ')
      
      if (Barrel_Fill=='N' & any(Old_wk$barrel_fill=='Y')==TRUE){
        Barrel_Fill='Y'
      }
      if (CF=='N' & any(Old_wk$cf_collection=='Y')==TRUE){
        CF='Y'
      }
      if (SensorChange=='N' & any(Old_wk$sensor_change=='Y')==TRUE){
        SensorChange='Y'
      }
      
    
      Weather= paste(unique(append(unlist(Weather),unlist(Old_wk$weather))), collapse = '; ')
      Repairs= paste(unique(append(unlist(Repairs),unlist(Old_wk$repairs_adjustments))), collapse = '; ')
      ToDo= paste(unique(append(unlist(ToDo),unlist(Old_wk$todo))), collapse = '; ')
      Other=paste(unique(append(unlist(Other),unlist(Old_wk$other))), collapse = '; ')
      DMI=paste(unique(append(unlist(DMI),unlist(Old_wk$dmid))), collapse = '; ')
      
      if (Weather==""){
        Weather="NULL"
      }
      if(Repairs==""){
        Repairs="NULL"
      }
      if (ToDo==""){
        ToDo="NULL"
      }
      if (Other==""){
        Other="NULL"
      }
      
      query=sprintf("UPDATE TABLE chrl.field_visits SET
                    date='%s', 
                    siteid=%s,
                    time='%s',
                    technicians='%s',
                    barrel_fill='%s',
                    cf_collection='%s',
                    sensor_change='%s',
                    weather='%s',
                    repairs_adjustments='%s',
                    todo= '%s',
                    other='%s',
                    dmid='%s',
                    WHERE fid=%s",Date,S,Time,Technicians,Barrel_Fill,CF,SensorChange,
                    Weather, Repairs,ToDo,Other,DMI,ID)
      dbSendQuery(con,query)
                    
      
    } else {
      
      if (Weather==""){
        Weather="NULL"
      }
      if(Repairs==""){
        Repairs="NULL"
      }
      if (ToDo==""){
        ToDo="NULL"
      }
      if (Other==""){
        Other="NULL"
      }
      
      query=sprintf("INSERT INTO chrl.field_visits (date,siteid,time,technicians,barrel_fill,cf_collection,sensor_change,weather,
              repairs_adjustments,todo,other,dmid) VALUES ('%s',%s,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                    Date,S, Time, Technicians,  Barrel_Fill, CF, SensorChange,Weather, Repairs, ToDo, Other,DMI)
      dbSendQuery(con,query)
      
    }

    
    query=sprintf("SELECT fid FROM chrl.field_visits WHERE date='%s' AND SiteID=%s",Date,S)
    New_Event= dbGetQuery(con,query)
  }
}




