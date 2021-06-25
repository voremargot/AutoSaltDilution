readRenviron('C:/Program Files/R/R-4.1.0/.Renviron')
options(java.parameters = c("-XX:+UseConcMarkSweepGC", "-Xmx8192m"))

#Libraries

library(DBI)
library(data.table)
library(dplyr)
library(tidyr)



options(warn = - 1)  

# Connect to database
con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

query= "SELECT * FROM chrl.Device_Magic WHERE new='Yes'"
Field= dbGetQuery(con, query)

Visit_Dates= unique(Field$date_visit)

Old_data= dbGetQuery(con, "SELECT * FROM chrl.field_visits")
for (D in Visit_Dates){
  Subset= Field[which(Field$date_visit==D),]
  Date= as.Date(unique(Subset$date_visit))
  
  for (S in unique(Subset$siteid)){
    
    working= Subset[which(Subset$siteid==S),]
    Time= min(format(as.POSIXct(working$time_visit,"%H:%M:%S"),'%H:%M:%S'),na.rm = TRUE)
    Techs=c()
    for(x in nrow(working)){
      Techs=append(Techs,unlist(strsplit(working[x,'technician'],',')))
      Techs=append(Techs,unlist(strsplit(working[x,'technician_other'],',')))
    }
    Technicians=paste(unique(Techs[Techs!='Other']),collapse = '; ')
    
    
    # if(any(grepl(', ', working$technician)==TRUE)){
    #   entry= grep(', ', working$technician)
    #   Ls= unlist(strsplit(working[entry,'technician'],','))
    #   LS=c(Ls,unlist(working[!(entry),'technician']),unlist(strsplit(working[which(is.na(working$technician_other)==FALSE),'technician_other'],'; ')))
    #   
    # } else {
    #   Tech1= unlist(strsplit(working[which(working$technician!='Other'),'technician'],'; '))
    #   Tech2= unlist(strsplit(working[which(is.na(working$technician_other)==FALSE),'technician_other'],'; '))
    # }

    
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
    
    Weather=paste(working[which(is.na(working$notes_weather)==FALSE),'notes_weather'],collapse = '; ')
    Repairs= paste(working[which(is.na(working$notes_repairs)==FALSE),'notes_repairs'],collapse = '; ')
    ToDo=paste(working[which(is.na(working$notes_todo)==FALSE),'notes_todo'],collapse = '; ')
    Other=paste(working[which(is.na(working$notes_other)==FALSE),'notes_other'],collapse = '; ')
    
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
      
      if (is.null(Old_wk$weather)==FALSE){
        Weather= paste(unique(append(unlist(strsplit(Weather, "; ")),unlist(strsplit(Old_wk$weather, "; ")))), collapse = '; ')
      }
      if (is.null(Old_wk$repairs_adjustments)==FALSE){
        Repairs= paste(unique(append(unlist(strsplit(Repairs, "; ")),unlist(strsplit(Old_wk$repairs_adjustments, "; ")))), collapse = '; ')
      }
      if (is.null(Old_wk$todo)==FALSE){
        ToDo= paste(unique(append(strsplit(unlist(ToDo, "; ")),unlist(strsplit(Old_wk$todo, "; ")))), collapse = '; ')
      }
      if (is.null(Old_wk$other)==FALSE){
        Other=paste(unique(append(unlist(strsplit(Other, "; ")),unlist(strsplit(Old_wk$other, "; ")))), collapse = '; ')
      }
      
      DMI=paste(unique(append(unlist(strsplit(DMI,'; ')),unlist(strsplit(Old_wk$dmid,'; ')))), collapse = '; ')
      
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
      
      query=sprintf("UPDATE chrl.field_visits SET
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
                    dmid='%s'
                    WHERE fid=%s",Date,S,Time,Technicians,Barrel_Fill,CF,SensorChange,
                    Weather, Repairs,ToDo,Other,DMI,ID)
      query <- gsub("\\n\\s+", " ", query)
      query <- gsub('NA',"NULL", query)
      query <- gsub("'NULL'","NULL",query)
      query <- gsub('NaN',"NULL",query)
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
      query <- gsub("\\n\\s+", " ", query)
      query <- gsub('NA',"NULL", query)
      query <- gsub("'NULL'","NULL",query)
      query <- gsub('NaN',"NULL",query)
      dbSendQuery(con,query)
      
    }
    
    query=sprintf("SELECT fid FROM chrl.field_visits WHERE date='%s' AND SiteID=%s",Date,S)
    New_Event= dbGetQuery(con,query)
    
    UP= working[which(is.na(working$upstream_photo)==FALSE),]
    if (nrow(UP)!=0){
      if (nrow(UP)==1){
        dmid_UP= UP$dmid

      } else if (nrow(UP) > 1){
        sel= sample(c(1:nrow(UP)))[1]
        dmid_UP=UP[sel,'dmid']
      }

      Q= sprintf("SELECT upstream_photo FROM chrl.device_magic WHERE dmid=%s",dmid_UP)
      Link= sprintf("<a href=%s>Upstream_photo</a>",dbGetQuery(con,Q))
      

      query= sprintf("UPDATE chrl.field_visits SET upstream_pic='%s' WHERE fid=%s",Link,New_Event)
      query <- gsub("\\n\\s+", " ", query)
      query <- gsub('NA',"NULL", query)
      query <- gsub("'NULL'","NULL",query)
      query <- gsub('NaN',"NULL",query)
      dbSendQuery(con,query)

    }


    DOWN= working[which(is.na(working$downstream_photo)==FALSE),]
    if (nrow(DOWN)!=0){
      if (nrow(DOWN)==1){
        dmid_DOWN= DOWN$dmid
      } else if (nrow(DOWN) > 1){
        sel= sample(c(1:nrow(DOWN)))[1]
        dmid_DOWN=DOWN[sel,'dmid']
      }
      
      Q= sprintf("SELECT downstream_photo FROM chrl.device_magic WHERE dmid=%s",dmid_DOWN)
      Link= sprintf("<a href=%s>Downstream_photo</a>",dbGetQuery(con,Q))
      
      query= sprintf("UPDATE chrl.field_visits SET downstream_pic= '%s' WHERE fid=%s",Link,New_Event)
      query <- gsub("\\n\\s+", " ", query)
      query <- gsub('NA',"NULL", query)
      query <- gsub("'NULL'","NULL",query)
      query <- gsub('NaN',"NULL",query)
      dbSendQuery(con,query)
    }
  }
}




