readRenviron('C:/Program Files/R/R-4.1.0/.Renviron')
options(java.parameters = c("-XX:+UseConcMarkSweepGC", "-Xmx8192m"))

#Libraries

library(DBI)
library(data.table)
library(dplyr)
library(tidyr)
library(stringr)


options(warn = - 1)  

# Connect to database
con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

#Select new field data for organization
query= "SELECT * FROM chrl.Device_Magic WHERE new='Yes'"
Field= dbGetQuery(con, query)

#Select all unique field visit dates of new events
Visit_Dates= unique(Field$date_visit)

Old_data= dbGetQuery(con, "SELECT * FROM chrl.sensors")
for (D in Visit_Dates){
  Subset= Field[which(Field$date_visit==D),]
  Date= as.Date(unique(Subset$date_visit))
  
  for (S in unique(Subset$siteid)){
    working= Subset[which(Subset$siteid==S),] 
    if (any(working$ec_sensor_change=='yes')==TRUE){
      ss= working[which(working$ec_sensor_change=='yes'),]
      
      if (nrow(ss)>1){
        next()
      }
      
      Action= unlist(strsplit(ss$action,','))
      Action= Action[Action!=" "]
      
      
      for (x  in c(1:length(Action))){
        wk_action= Action[x]
        
        if (wk_action=='Replace'){
          Removed_type=unlist(strsplit(ss$sen_r_removed_type,','))[x]
          if (Removed_type=="Other"){
            Removed_type=unlist(strsplit(ss$sen_r_removed_type_other,','))[x]
          }
          Remove_SN= unlist(strsplit(ss$sen_r_removed_sn,','))[x]
          Remove_Probe= as.numeric(unlist(strsplit(ss$sen_r_removed_probenum,','))[x])
          
          New_type=unlist(strsplit(ss$sen_r_new_type,','))[x]
          if (New_type==" "){
            New_type=unlist(strsplit(ss$sen_r_new_type_other,','))[x]
          }
          New_SN= unlist(strsplit(ss$sen_r_new_sn,','))[x]
          New_Probe= as.numeric(unlist(strsplit(ss$sen_r_new_probenum,','))[x])
          New_RL=unlist(strsplit(ss$sen_r_new_rivloc,','))[x]
          if (New_RL=='Other'){
            New_RLunlist(strsplit(ss$sen_r_new_rivloc_other,','))[x]
          }
          
          wk_old= Old_data[which(Old_data$siteid==S & Old_data$sensor_type==Removed_type & Old_data$serial_number==Remove_SN),]
          if (is.na(wk_old$deactivation_date)==FALSE){
            print('The sensor that you are trying to deactive has previously been taken out! Please review the field  notes')
            next()
          }
          
          if (New_Probe != Remove_Probe){
            print('The Probe Numbers you are replacing are different. Please check the data')
            next()
          }
          
          sensorid_old= wk_old$sensorid
          query= sprintf("UPDATE chrl.sensors SET deactivation_date='%s' WHERE sensorid=%s", Date,sensorid_old)
          query <- gsub("\\n\\s+", " ", query)
          query <- gsub('NA',"NULL", query)
          query <- gsub("'NULL'","NULL",query)
          query <- gsub('NaN',"NULL",query)
          # dbSendQuery(con,query)
          
          query= sprintf("INSERT INTO chrl.sensors (siteid, probe_number,sensor_type,serial_number,river_loc,install_date) VALUES
                         (%s,%s,'%s','%s','%s','%s')", S,New_Probe,New_type,New_SN,New_RL,Date)
          query <- gsub("\\n\\s+", " ", query)
          query <- gsub('NA',"NULL", query)
          query <- gsub("'NULL'","NULL",query)
          query <- gsub('NaN',"NULL",query)
          # dbSendQuery(con,query)
          
          ## WHAT HAPPENS WHEN THE PROBE NUMBERS SWITCH IN THE CHANGE OF PROBES? DOES THIS EVEN HAPPEN?
        } else if (wk_action=='Remove'){
          
          Remove_type= ss$sen_remove_type
          if (Remove_type=='Other'){
            Remove_type=ss$sen_remove_type_other
          }
          Remove_Probe= ss$sen_remove_probenum
          Remove_SN= ss$sen_remove_probenum
          
          old_wk= Old_data[which(Old_data$siteid==S & Old_data$probe_number==Remove_Probe & Old_data$sensor_type== Remove_type & Old_data$serial_number==Remove_SN),]
          if (nrow(old_wk)==0){
            print('The sensor you say was removed does not exist in the database. Please review the field data')
            next()
          }
          
          if(is.na(old_wk$deactivation_date)==TRUE){
            print('The sensor you are trying to remove already has been deactivated. Please review the field data')
            next()
          }
          
          SensorID= old_wk$sensorid
          query("UPDATE chrl.sensors SET deactivation_date= '%s' WHERE sensorid=%s", Date,SensorID)
          # dbSendQuery(con,query)
          
        } else if (wk_action=='Add'){
          Add_Type= ss$sen_add_type
          if(Add_Type=='Other'){
            Add_Type=ss$sen_add_type_other
          }
          Add_Probe= ss$sen_add_probenum
          Add_SN= ss$sen_add_sn
          Add_RiverLoc= ss$sen_add_riverloc
          if (Add_RiverLoc=='Other'){
            Add_RiverLoc=ss$sen_add_riverloc_other
          }
          
          Active_Stations=Old_data[which(Old_data$siteid==S & is.na(Old_data$install_date)==FALSE & is.na(Old_data$deactivation_date)==TRUE),]
          if (Add_Probe %in% Active_Stations$probe_number){
            print('There is already an active sensor with this probe number! Please review the field data.')
            next()
          } 
          
          query=sprintf("INSERT INTO chrl.sensors ((siteid, probe_number,sensor_type,serial_number,river_loc,install_date) VALUES
                         (%s,%s,'%s','%s','%s','%s')",S,Add_Probe,Add_Type,Add_SN,Add_RiverLoc,Date)
          query <- gsub("\\n\\s+", " ", query)
          query <- gsub('NA',"NULL", query)
          query <- gsub("'NULL'","NULL",query)
          query <- gsub('NaN',"NULL",query)
          # dbSendQuery(con,query)
        } else if (wk_action=="Arrange"){
          SN= ss$sen_sw_sn
          Recorded_Sensor= Old_data[which(Old_data$serial_number==SN & Old_data$siteid==S & is.na(Old_data$deactivation_date)==TRUE),]
          if (nrow(Recorded_Sensor)==0){
            print('The sensor doesnt exist in the database. Please check the field note')
          }
          sensorid= Recorded_Sensor$sensorid
          if (ss$sen_sw_action=='Position'){
            Old_positon= ss$sen_sw_position_old
            if (Old_positon=='Other'){
              Old_postion= ss$sen_sw_position_old_other
            } 
            New_position=ss$sen_sw_position_new
            if (New_position=='Other'){
              New_position=ss$sen_sw_position_new_other
            }
            
            query= sprintf("UPDATE chrl.sensors SET VALUES river_loc=%s where sensorid=%s",New_position,sensorid)
            # dbSendQuery(con,query)
          } else if (ss$sen_sw_action=='Probe'){
              Old_PN= ss$sen_sw_pn_old
              New_PN=ss$sen_sw_pn_new
              query= sprintf("UPDATE chrl.sensors SET VALUES probe_number=%s where sensorid=%s",New_PN,sensorid)
              # dbSendQuery(con,query)
          }
        }
        
      }
    }
  }
}
