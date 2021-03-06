barrel_fill_update <- function (working,S){
  # This function is used for updating the barrel fill periods based on the device magic form used in the field.
  
  #subset the data to entries where barrel fill occurred
  ss= working[working$barrel_fill=='yes',]
  
  #select the barrel period data already in the database
  Old_data= dbGetQuery(con, "SELECT * FROM chrl.barrel_periods")
  
  # if there are multiple rows on the same day and same site where barrel fills occurred
  # check to see if those rows hold the same data.
  if (nrow(ss)>1){
    Distin= distinct(ss[,c('siteid','barrel_fill','volume_solution','salt_added','water_added','volume_depart','salt_remaining_site')])
    if (nrow(Distin)>1){
      print(sprintf("Multiple barrel fills with differing data were recorded on %s at site %s: Please check the field records",Date,S))
      break()
    } else {
      ss=ss[1,]
    }
  }
  
  # extract variables for database
  Volume_at_start=ss$volume_solution
  Added_Salt= ss$salt_added
  Volume_at_depart= ss$volume_depart
  Salt_remaining_at_site= ss$salt_remaining_site
  Notes= ss$barrel_fill_notes
  
  # barrel period that ended with new fill event
  PeriodID= Old_data[which(is.na(Old_data$ending_date)==TRUE & Old_data$siteid==S),'periodid']
  
  #update the end date of previous barrel fill
  query=sprintf("UPDATE chrl.barrel_periods SET ending_date='%s', solution_at_end= %s WHERE periodid=%s",Date,Volume_at_start, PeriodID)
  dbSendQuery(con,query) 
  
  #insert new record containing data of new barrel period
  query=sprintf("INSERT INTO chrl.barrel_periods (SiteID,starting_date,ending_date,solution_at_start,solution_at_end,salt_added,salt_remaining_on_site,notes) VALUES (
      %s,'%s','NULL',%s,'NULL',%s,%s,'%s')",S,Date,Volume_at_depart,Added_Salt,Salt_remaining_at_site,Notes)
  query <- gsub("\\n\\s+", " ", query)
  query <- gsub('NA',"NULL", query)
  query <- gsub("'NULL'","NULL",query)
  query <- gsub('NaN',"NULL",query)
  dbSendQuery(con,query)
  
}

##---------------------------------------------------------------------------------------------------
##---------------------------------------------------------------------------------------------------
##---------------------------------------------------------------------------------------------------
field_visit_update <- funtion (working, S){
  # Updates the field visit table in the database with data collected on the 
  # device magic form. 
  
  # Get the technicians who were working on the site
  Techs=c()
  for(x in nrow(working)){
    Techs=append(Techs,unlist(strsplit(working[x,'technician'],',')))
    Techs=append(Techs,unlist(strsplit(working[x,'technician_other'],',')))
  }
  Technicians=paste(unique(Techs[Techs!='Other']),collapse = '; ')
  
  
  # did a barrel fill occur during the visit?
  if (any(working$barrel_fill=='yes')==TRUE){
    Barrel_Fill='Y'
  } else {
    Barrel_Fill='N'
  }
  
  # were CF measurements taken during the visit?
  if (any(working$cf_event=='yes')==TRUE){
    CF='Y'
  } else {
    CF='N'
  }
  
  # did any sensor changes happen during the visit?
  if (any(working$ec_sensor_change=='yes')==TRUE){
    SensorChange='Y'
  } else {
    SensorChange='N'
  }
  
  # Other variables that are copied to database
  Time= min(format(as.POSIXct(working$time_visit,"%H:%M:%S"),'%H:%M:%S'),na.rm = TRUE)
  Weather=paste(working[which(is.na(working$notes_weather)==FALSE),'notes_weather'],collapse = '; ')
  Repairs= paste(working[which(is.na(working$notes_repairs)==FALSE),'notes_repairs'],collapse = '; ')
  ToDo=paste(working[which(is.na(working$notes_todo)==FALSE),'notes_todo'],collapse = '; ')
  Other=paste(working[which(is.na(working$notes_other)==FALSE),'notes_other'],collapse = '; ')
  
  #the device magic row ID's where the data is found
  DMI= paste(working$dmid,collapse = '; ')
  
  # checking to see if there are records already in the database that match the date and 
  # site of field work
  if (nrow(Old_data[which(Old_data$date==Date & Old_data$siteid==S),]) > 0){
    Old_wk= Old_data[which(Old_data$date==Date & Old_data$siteid==S),]
    ID= Old_wk$fid
    Time= min(format(as.POSIXct(Old_wk$time,"%H:%M:%S"),'%H:%M:%S'),Time)
    
    # add in any new technicians to the record
    Technicians= unique(append(unlist(strsplit(Technicians,"; ")), unlist(strsplit(Old_wk$technicians, "; "))))
    Technicians=paste(Technicians, collapse = '; ')
    
    #check if barrel period of  previous data was yes
    if (Barrel_Fill=='N' & any(Old_wk$barrel_fill=='Y')==TRUE){
      Barrel_Fill='Y'
    }
    
    #check if CF collection of  previous data was yes
    if (CF=='N' & any(Old_wk$cf_collection=='Y')==TRUE){
      CF='Y'
    }
    
    #check if sensor change of  previous data was yes
    if (SensorChange=='N' & any(Old_wk$sensor_change=='Y')==TRUE){
      SensorChange='Y'
    }
    
    # add in new comments if old records also had values
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
    
    # replace "" with NULL for database entry
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
    
    # update field that matches date and site of new data
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
    
    # set "" to NULL for database entry
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
    
    #create new record in database for new field site visit
    query=sprintf("INSERT INTO chrl.field_visits (date,siteid,time,technicians,barrel_fill,cf_collection,sensor_change,weather,
              repairs_adjustments,todo,other,dmid) VALUES ('%s',%s,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
                  Date,S, Time, Technicians,  Barrel_Fill, CF, SensorChange,Weather, Repairs, ToDo, Other,DMI)
    query <- gsub("\\n\\s+", " ", query)
    query <- gsub('NA',"NULL", query)
    query <- gsub("'NULL'","NULL",query)
    query <- gsub('NaN',"NULL",query)
    dbSendQuery(con,query)
    
  }
  
  #get id of new field event
  query=sprintf("SELECT fid FROM chrl.field_visits WHERE date='%s' AND SiteID=%s",Date,S)
  New_Event= dbGetQuery(con,query)
  
  # select the a picture that shows upstream of the site
  UP= working[which(is.na(working$upstream_photo)==FALSE),]
  if (nrow(UP)!=0){
    if (nrow(UP)==1){
      dmid_UP= UP$dmid
      
    } else if (nrow(UP) > 1){
      sel= sample(c(1:nrow(UP)))[1]
      dmid_UP=UP[sel,'dmid']
    }
    
    #get google drive link of upstream photo
    Q= sprintf("SELECT upstream_photo FROM chrl.device_magic WHERE dmid=%s",dmid_UP)
    Link= sprintf("<a href=%s>Upstream_photo</a>",dbGetQuery(con,Q))
    
    #update database with link to upstream photo
    query= sprintf("UPDATE chrl.field_visits SET upstream_pic='%s' WHERE fid=%s",Link,New_Event)
    query <- gsub("\\n\\s+", " ", query)
    query <- gsub('NA',"NULL", query)
    query <- gsub("'NULL'","NULL",query)
    query <- gsub('NaN',"NULL",query)
    dbSendQuery(con,query)
    
  }
  
  # select the a picture that shows downstream of the site
  DOWN= working[which(is.na(working$downstream_photo)==FALSE),]
  if (nrow(DOWN)!=0){
    if (nrow(DOWN)==1){
      dmid_DOWN= DOWN$dmid
    } else if (nrow(DOWN) > 1){
      sel= sample(c(1:nrow(DOWN)))[1]
      dmid_DOWN=DOWN[sel,'dmid']
    }
    
    #get google drive link of downstream photo
    Q= sprintf("SELECT downstream_photo FROM chrl.device_magic WHERE dmid=%s",dmid_DOWN)
    Link= sprintf("<a href=%s>Downstream_photo</a>",dbGetQuery(con,Q))
    
    #update database with link to downstream photo
    query= sprintf("UPDATE chrl.field_visits SET downstream_pic= '%s' WHERE fid=%s",Link,New_Event)
    query <- gsub("\\n\\s+", " ", query)
    query <- gsub('NA',"NULL", query)
    query <- gsub("'NULL'","NULL",query)
    query <- gsub('NaN',"NULL",query)
    dbSendQuery(con,query)
  }
}

#----------------------------------------------------------------------------------
#---------------------------------------------------------------------------------
sensor_update <- function (working, S){
  # select subset of visits where sensor change happened
  ss= working[which(working$ec_sensor_change=='yes'),]
  
  # select sensor data already in the database
  Old_data= dbGetQuery(con, "SELECT * FROM chrl.sensors")
  
  Active_sensors_old=Old_data[is.na(Old_data$deactivation_date)==TRUE & is.na(Old_data$install_date)==FALSE & Old_data$siteid==S,]
  
  if (nrow(ss)>1){
    next()
  }
  
  # determine which sensor changes actions occurred
  Action= unlist(strsplit(ss$action,','))
  Action= Action[Action!=" "]
  
  # for each action the database will change in a different way
  for (x  in c(1:length(Action))){
    wk_action= Action[x]
    
#-------------------------------------------------------------------------------------
    # case where sensor is replaced in the river
    if (wk_action=='Replace'){
      
      # removed sensor type
      Removed_type=unlist(strsplit(ss$sen_r_removed_type,','))[x]
      if (Removed_type=="Other"){
        Removed_type=unlist(strsplit(ss$sen_r_removed_type_other,','))[x]
      }
      
      # removed sensor's serial number
      Remove_SN= unlist(strsplit(ss$sen_r_removed_sn,','))[x]
      
      #removed sensor's probe number
      Remove_Probe= as.numeric(unlist(strsplit(ss$sen_r_removed_probenum,','))[x])
      
      # the replacement probe type
      New_type=unlist(strsplit(ss$sen_r_new_type,','))[x]
      if (New_type==" "){
        New_type=unlist(strsplit(ss$sen_r_new_type_other,','))[x]
      }
      
      # replacement probe serial number
      New_SN= unlist(strsplit(ss$sen_r_new_sn,','))[x]
      
      # replacement probe number
      New_Probe= as.numeric(unlist(strsplit(ss$sen_r_new_probenum,','))[x])
      
      #replacement probe river location
      New_RL=unlist(strsplit(ss$sen_r_new_rivloc,','))[x]
      if (New_RL=='Other'){
        New_RLunlist(strsplit(ss$sen_r_new_rivloc_other,','))[x]
      }
      
      # make sure the probe that you are removing was recording as active in the database
      wk_old= Old_data[which(Old_data$siteid==S & Old_data$sensor_type==Removed_type & Old_data$serial_number==Remove_SN),]
      if (is.na(wk_old$deactivation_date)==FALSE){
        print('The sensor that you are trying to deactive has previously been taken out! Please review the field  notes')
        next()
      }
      
      # make sure the removed probed and replacement probe have the same probe numbers
      if (New_Probe != Remove_Probe){
        print('The Probe Numbers you are replacing are different. Please check the data')
        next()
      }
      
      # update the deactivation date of the replaced sensor
      sensorid_old= wk_old$sensorid
      query= sprintf("UPDATE chrl.sensors SET deactivation_date='%s' WHERE sensorid=%s", Date,sensorid_old)
      query <- gsub("\\n\\s+", " ", query)
      query <- gsub('NA',"NULL", query)
      query <- gsub("'NULL'","NULL",query)
      query <- gsub('NaN',"NULL",query)
      # dbSendQuery(con,query)
      
      # add a new record for the new sensor that was placed in the stream
      query= sprintf("INSERT INTO chrl.sensors (siteid, probe_number,sensor_type,serial_number,river_loc,install_date) VALUES
                         (%s,%s,'%s','%s','%s','%s')", S,New_Probe,New_type,New_SN,New_RL,Date)
      query <- gsub("\\n\\s+", " ", query)
      query <- gsub('NA',"NULL", query)
      query <- gsub("'NULL'","NULL",query)
      query <- gsub('NaN',"NULL",query)
      # dbSendQuery(con,query)
      
#----------------------------------------------------------------------------------------    
      #case where a sensor is removed but not replaced
    } else if (wk_action=='Remove'){
      
      # sensor type of probe being removed
      Remove_type= ss$sen_remove_type
      if (Remove_type=='Other'){
        Remove_type=ss$sen_remove_type_other
      }
      
      #probe number of sensor removed
      Remove_Probe= ss$sen_remove_probenum
      
      # serial number of removed sensor
      Remove_SN= ss$sen_remove_probenum
      
      # check that the sensor that removed exists in the database
      old_wk= Old_data[which(Old_data$siteid==S & Old_data$probe_number==Remove_Probe & Old_data$sensor_type== Remove_type & Old_data$serial_number==Remove_SN),]
      if (nrow(old_wk)==0){
        print('The sensor you say was removed does not exist in the database. Please review the field data')
        next()
      }
      
      # check if the sensor you removed has previously been deactivated
      if(is.na(old_wk$deactivation_date)==TRUE){
        print('The sensor you are trying to remove already has been deactivated. Please review the field data')
        next()
      }
      
      # update the deactivation date of the removed sensor in the database
      SensorID= old_wk$sensorid
      query("UPDATE chrl.sensors SET deactivation_date= '%s' WHERE sensorid=%s", Date,SensorID)
      # dbSendQuery(con,query)
      
#--------------------------------------------------------------------------------------------------------
      # case where a sensor was added to the network
    } else if (wk_action=='Add'){
      
      # Added sensor type
      Add_Type= ss$sen_add_type
      if(Add_Type=='Other'){
        Add_Type=ss$sen_add_type_other
      }
      
      # Added sensor probe number
      Add_Probe= ss$sen_add_probenum
      
      # Added sensor serial number
      Add_SN= ss$sen_add_sn
      
      # Added sensor river location
      Add_RiverLoc= ss$sen_add_riverloc
      if (Add_RiverLoc=='Other'){
        Add_RiverLoc=ss$sen_add_riverloc_other
      }
      
      # double check that this sensor is not already recorded as being active in the database
      Active_Stations=Old_data[which(Old_data$siteid==S & is.na(Old_data$install_date)==FALSE & is.na(Old_data$deactivation_date)==TRUE),]
      if (Add_Probe %in% Active_Stations$probe_number){
        print('There is already an active sensor with this probe number! Please review the field data.')
        next()
      } 
      
      # create new record for the added sensor in the database
      query=sprintf("INSERT INTO chrl.sensors ((siteid, probe_number,sensor_type,serial_number,river_loc,install_date) VALUES
                         (%s,%s,'%s','%s','%s','%s')",S,Add_Probe,Add_Type,Add_SN,Add_RiverLoc,Date)
      query <- gsub("\\n\\s+", " ", query)
      query <- gsub('NA',"NULL", query)
      query <- gsub("'NULL'","NULL",query)
      query <- gsub('NaN',"NULL",query)
      # dbSendQuery(con,query)
      
#----------------------------------------------------------------------------------------
      # case where active sensors are rearranged
    } else if (wk_action=="Arrange"){
      
      #serial_number of sensor that was rearranged
      SN= ss$sen_sw_sn
      
      # check that the sensor is active in the database
      Recorded_Sensor= Old_data[which(Old_data$serial_number==SN & Old_data$siteid==S & is.na(Old_data$deactivation_date)==TRUE),]
      if (nrow(Recorded_Sensor)==0){
        print('The sensor doesnt exist in the database. Please check the field note')
      }
      
      # select the senor id assocaited with the serial number
      sensorid= Recorded_Sensor$sensorid
      
      # case where the position of the sensor changes in the riverr
      if (ss$sen_sw_action=='Position'){
        
        # old position in river
        Old_positon= ss$sen_sw_position_old
        if (Old_positon=='Other'){
          Old_postion= ss$sen_sw_position_old_other
        } 
        
        # new position in river
        New_position=ss$sen_sw_position_new
        if (New_position=='Other'){
          New_position=ss$sen_sw_position_new_other
        }
        
        #update the sensors location in the database
        query= sprintf("UPDATE chrl.sensors SET VALUES river_loc=%s where sensorid=%s",New_position,sensorid)
        # dbSendQuery(con,query)
        
        
        # case where the probe number is changed
      } else if (ss$sen_sw_action=='Probe'){
        # old probe number
        Old_PN= ss$sen_sw_pn_old
        
        #new probe number
        New_PN=ss$sen_sw_pn_new
        
        #update the probe number in the database
        query= sprintf("UPDATE chrl.sensors SET VALUES probe_number=%s where sensorid=%s",New_PN,sensorid)
        # dbSendQuery(con,query)
      }
    }
    
  }
}
  
}
