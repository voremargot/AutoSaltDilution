barrel_fill_update <- function (working,S){
  ### This function is used for updating the barrel fill periods based on the device magic form used in the field.
  ### The function tests for inconsistencies in the data and database and will output a warning specifying the issue. 
  ### If an issue is found the code will return a zero and the warning message but if the code successfully runs it will
  ### output a 1 with no warnings. 
  
  # function that rounds values down to the nearest 25L
  roundDown <- function(x,to=25) {
    to*(x%/%to)
  }
  
  Warning=NA
  Duplicate= NA
  #subset the data to entries where barrel fill occurred
  ss= working[working$barrel_fill=='yes',]
  
  #select the barrel period data already in the database
  Old_data= dbGetQuery(con, "SELECT * FROM chrl.barrel_periods")
  
  Date= as.Date(ss$date_visit)
  
  # check if there is already barrel period data in the database
  # Used as check to make sure the new data does not differ from previous entries
  if( nrow(Old_data[which(Old_data$siteid==S & (Old_data$starting_date== Date | Old_data$ending_date== Date)),])>0){
    Old_Matching_events=Old_data[which(Old_data$siteid==S & (Old_data$starting_date== Date | Old_data$ending_date== Date)),]
    New_Barrel_data= distinct(ss[,c('siteid','barrel_fill','volume_solution','salt_added','water_added','volume_depart','salt_remaining_site')])
    
    # checks if the new barrel data is differs from that already in the database and produces a warning if it does. 
    for (x in c(1:nrow(New_Barrel_data))){
      if (Old_Matching_events[Old_Matching_events$ending_date==Date,'solution_at_end']!= roundDown(New_Barrel_data[x,'volume_solution'])){
        Warning= sprintf("Multiple barrel fills with differing data were recorded on %s at site %s. Please check the field records",Date,S)
        return(c(0, Warning))
      } else if (Old_Matching_events[Old_Matching_events$starting_date==Date,'solution_at_start']!= roundDown(New_Barrel_data[x,'volume_depart'])){
        Warning= sprintf("Multiple barrel fills with differing data were recorded on %s at site %s. Please check the field records",Date,S)
        return(c(0, Warning))
      } else if (Old_Matching_events[Old_Matching_events$starting_date==Date,'salt_added']!=New_Barrel_data[x,'salt_added']){
        Warning= sprintf("Multiple barrel fills with differing data were recorded on %s at site %s. Please check the field records",Date,S)
        return(c(0, Warning))
      } else if (Old_Matching_events[Old_Matching_events$starting_date==Date,'salt_remaining_on_site']!=New_Barrel_data[x,'salt_remaining_site']){
        Warning= sprintf("Multiple barrel fills with differing data were recorded on %s at site %s. Please check the field records",Date,S)
        return(c(0,Warning))
      } 
    }
    Duplicate='Yes'
  } 
  
  if (is.na(Duplicate)==FALSE){
    return(c(1,warning))
  }
  
  # if there are multiple rows on the same day and same site where barrel fills occurred
  # check to see if those rows hold the same data.
  if (nrow(ss)>1){
    Distin= distinct(ss[,c('siteid','barrel_fill','volume_solution','salt_added','water_added','volume_depart','salt_remaining_site')])
    
    # if there are multiple rows with different data throw warning
    if (nrow(Distin)>1){
      Warning= sprintf("Multiple barrel fills with differing data were recorded on %s at site %s. Please check the field records",Date,S)
      return (c(0,Warning))
    } else {
      ss=ss[1,]
    }
  }
  
  
  # extract variables for database
  Volume_at_start=roundDown(ss$volume_solution)
  Added_Salt= ss$salt_added
  Volume_at_depart= roundDown(ss$volume_depart)
  Salt_remaining_at_site= ss$salt_remaining_site
  Notes= ss$barrel_fill_notes
  
  if (grepl("'", Notes)==TRUE){
    Notes=gsub("'","",Notes)
  }
  
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
  
# returns a 1 if the function ran successfully  
return(c(1,Warning))
}

sensor_update <- function (working, S){
  
  # function that is used if a sensor was replaced
  Replace <- function (ss, Old_data) {
    ### This function selects the old and new sensors from the device magic form
    ### and does checks that the data entered into the device magic form accurately corresponds
    ### to the data in the database. If the data in the device magic form does not match with that in 
    ### the database, the function will return a 1 and a detailed warning of the problem, otherwise the
    ### code produces a 1 and no warning.
    
    Warning=NA
    
    # subset data
    data= ss[ss$action=='Replace',]
    
    #define dmid 
    D= data$dmid
    Date= as.Date(data$date_visit)
    
    # removed sensor type
    Remove_type=Empty_string(unlist(strsplit(data$sen_r_removed_type,',')))
    if (Remove_type=="Other"){
      Remove_type=Empty_string(unlist(strsplit(data$sen_r_removed_type_other,',')))
    }
    
    # removed sensor's serial number
    Remove_SN=toupper(Empty_string( unlist(strsplit(data$sen_r_removed_sn,','))))
    
    #removed sensor's probe number
    Remove_Probe= as.numeric(Empty_string(unlist(strsplit(data$sen_r_removed_probenum,','))))
    
    # the replacement probe type
    New_type=Empty_string(unlist(strsplit(data$sen_r_new_type,',')))
    if (New_type=="Other"){
      New_type=Empty_string(unlist(strsplit(data$sen_r_new_type_other,',')))
    }
    
    # replacement probe serial number
    New_SN= toupper(Empty_string(unlist(strsplit(data$sen_r_new_sn,','))))
    
    # replacement probe number
    New_Probe= as.numeric(Empty_string(unlist(strsplit(data$sen_r_new_probenum,','))))
    
    #replacement probe river location
    New_RL=Empty_string(unlist(strsplit(data$sen_r_new_rivloc,',')))
    if (New_RL=='Other'){
      New_RL=Empty_string(unlist(strsplit(data$sen_r_new_rivloc_other,',')))
    }
    
    Duplicate_deca= Old_data[which(Old_data$siteid==S & Old_data$deactivation_date==Date),]
    Duplicate_act= Old_data[which(Old_data$siteid==S & Old_data$install_date==Date),]
    G= Duplicate_deca[which(Duplicate_deca$probe_number==Remove_Probe & Duplicate_deca$sensor_type==Remove_type & Duplicate_deca$serial_number==Remove_SN),]
    H= Duplicate_act[which(Duplicate_act$probe_number==New_Probe & Duplicate_act$sensor_type==New_type & Duplicate_act$serial_number==New_SN),]
    if (nrow(G)==1 & nrow(H)==1){
      return(c(1,Warning))
    } 
    
    # check the probe that you are removing exits at that site
    wk_old= Old_data[which(Old_data$siteid==S & Old_data$sensor_type==Remove_type & Old_data$serial_number==Remove_SN),]
    if (nrow(wk_old)==0){
      Warning= sprintf('Sensor %s that you are trying to deactivate doesnt exist in the stream at this site (DMID=%s)! Please review the field notes.',Remove_SN,D)
      return (c(0,Warning))
    }
    
    # check the probe that you are removing has not been previously deactivated
    if (is.na(wk_old$deactivation_date)==FALSE){
      Warning= sprintf('Sensor %s that you are trying to deactivate has previously been taken out (DMID=%s)! Please review the field notes.',Remove_SN,D)
      return (c(0,Warning))
    }
    
    # make sure the removed probed and replacement probe have the same probe numbers
    if (New_Probe != Remove_Probe){
      Warning=sprintf('The probe numbers you are replacing are different (DMID=%s) (New Probe -> %s, Old Probe -> %s. Please check the data.',D,New_Probe,Remove_Probe)
      return(c(0,Warning))
    }
    
    # update the deactivation date of the replaced sensor
    sensorid_old= wk_old$sensorid
    query= sprintf("UPDATE chrl.sensors SET deactivation_date='%s' WHERE sensorid=%s", Date,sensorid_old)
    query <- gsub("\\n\\s+", " ", query)
    query <- gsub('NA',"NULL", query)
    query <- gsub("'NULL'","NULL",query)
    query <- gsub('NaN',"NULL",query)
    dbSendQuery(con,query)
    
    # add a new record for the new sensor that was placed in the stream
    query= sprintf("INSERT INTO chrl.sensors (siteid, probe_number,sensor_type,serial_number,river_loc,install_date) VALUES
                         (%s,%s,'%s','%s','%s','%s')", S,New_Probe,New_type,New_SN,New_RL,Date)
    query <- gsub("\\n\\s+", " ", query)
    query <- gsub('NA',"NULL", query)
    query <- gsub("'NULL'","NULL",query)
    query <- gsub('NaN',"NULL",query)
    dbSendQuery(con,query)
    
    return (c(1, Warning))
  }
  
  # function that is used if a sensor is removed without replacement
  Remove <- function(ss,Old_data){
    ### This function selects the sensor that was removed from the device magic form 
    ### and does checks that the data entered into the device magic form accurately corresponds
    ### to the data in the database. If the data in the device magic form does not match with that in 
    ### the database, the function will return a 1 and a detailed warning of the problem, otherwise the
    ### code produces a 1 and no warning
    
    Warning=NA
    
    # subset the data to events where sensors have been removed
    data=ss[ss$action=='Remove',]
    
    #specify the dmid for warning messages
    D=data$dmid
    Date= as.Date(data$date_visit)
    
    # Sensor type that was removed
    Remove_type= Empty_string(trimws(unlist(strsplit(data$sen_remove_type, ", "))))
    if (Remove_type=='Other'){
      Remove_type=Empty_string(trimws(unlist(strsplit(data$sen_remove_type_other,", "))))
    }
    
    #probe number of sensor removed
    Remove_Probe= as.numeric(Empty_string(unlist(strsplit(data$sen_remove_probenum,", "))))
    
    # serial number of removed sensor
    Remove_SN= toupper(trimws(Empty_string(unlist(strsplit(data$sen_remove_sn,", ")))))
    
    Duplicate= Old_data[which(Old_data$siteid==S & Old_data$deactivation_date==Date),]
    G= Duplicate[which(Duplicate$probe_number==Remove_Probe & Duplicate$sensor_type==Remove_type & Duplicate$serial_number==Remove_SN),]
    if (nrow(G)==1){
      return(c(1,Warning))
    } 
    
    # check that the sensor that removed exists at the site
    old_wk= Old_data[which(Old_data$siteid==S & Old_data$probe_number==Remove_Probe & Old_data$sensor_type== Remove_type & Old_data$serial_number==Remove_SN),]
    if (nrow(old_wk)==0){
      Warning=sprintf('The sensor you say was removed (SN=%s) does not exist at site %s (DMID=%s). Please review the field data',Remove_SN,S,D)
      return(c(0,Warning))
    }
    
    Duplicate= Old_data[which(Old_data$siteid==S & Old_data$deactivation_date==Date),]
    G= Duplicate[which(Duplicate$probe_number==Remove_Probe & Duplicate$sensor_type==Remove_type & Duplicate$serial_number==Remove_SN),]
    if (nrow(G)==1){
      return(c(1,Warning))
    } 
    
    # check if the sensor removed has previously been deactivated
    if(is.na(old_wk$deactivation_date)==FALSE){
      Warning= sprintf('The sensor you are trying to remove (SN=%s) already has been deactivated (DMID=%s). Please review the field data',Remove_SN,D)
      return(c(0,Warning))
    }
    
    # update the deactivation date of the removed sensor in the database
    SensorID= old_wk$sensorid
    query=sprintf("UPDATE chrl.sensors SET deactivation_date= '%s' WHERE sensorid=%s", Date,SensorID)
    dbSendQuery(con,query) 
    
    return(c(1,Warning))
  }
  
  # function that is used if a sensor is added to the network
  Add  <- function(ss, Old_data){
    ### This function selects the added  sensor(s) from the device magic form
    ### and does checks that the data entered into the device magic form accurately corresponds
    ### to the data in the database. If the data in the device magic form does not match with that in 
    ### the database, the function will return a 1 and a detailed warning of the problem, otherwise the
    ### code produces a 1 and no warning.
    
    Warning=NA
    
    # Subset the data to events where sensors were added
    data=ss[ss$action=='Add',]
    
    #specify the dmid for warning messages
    D=data$dmid
    Date= as.Date(data$date_visit)
    
    # define the sensor type of the new sensor
    Add_Type=Empty_string(trimws(unlist(strsplit(data$sen_add_type,', '))))
    if(Add_Type=='Other'){
      Add_Type=Empty_string(trimws(unlist(strsplit(data$sen_add_type_other,', '))))
    }
    
    # Added sensor probe number
    Add_Probe= as.numeric(Empty_string(unlist(strsplit(data$sen_add_probenum,', '))))
    
    # Added sensor serial number
    Add_SN= toupper(Empty_string(trimws(unlist(strsplit(data$sen_add_sn,', ')))))
    
    # Added sensor river location
    Add_RiverLoc= Empty_string(trimws(unlist(strsplit(data$sen_add_riverloc,', '))))
    if (Add_RiverLoc=='Other'){
      Add_RiverLoc=Empty_string(trimws(unlist(strsplit(data$sen_add_riverloc_other,', '))))
    }
    
    Duplicate= Old_data[which(Old_data$siteid==S & Old_data$install_date==Date),]
    G= Duplicate[which(Duplicate$probe_number==Add_Probe & Duplicate$sensor_type==Add_Type & Duplicate$serial_number==Add_SN & Duplicate$river_loc==Add_RiverLoc),]
    if (nrow(G)==1){
      return(c(1,Warning))
    } 
   
    # double check that this sensor is not already recorded as being active in the database
    Active_Stations=Old_data[which(Old_data$siteid==S & is.na(Old_data$install_date)==FALSE & is.na(Old_data$deactivation_date)==TRUE),]
    if (Add_Probe %in% Active_Stations$probe_number){
      Warning=sprintf('There is already an active sensor with probe number %s (DMID=%s). Please review the field data',Add_Probe,D)
      return(c(0,Warning))
    } 
    
    # create new record for the added sensor in the database
    query=sprintf("INSERT INTO chrl.sensors (siteid, probe_number,sensor_type,serial_number,river_loc,install_date) VALUES
                         (%s,%s,'%s','%s','%s','%s')",S,Add_Probe,trimws(Add_Type),trimws(Add_SN),trimws(Add_RiverLoc),Date)
    query <- gsub("\\n\\s+", " ", query)
    query <- gsub('NA',"NULL", query)
    query <- gsub("'NULL'","NULL",query)
    query <- gsub('NaN',"NULL",query)
    dbSendQuery(con,query)
  
  return (c(1,Warning))
    
  }
  
  
##------------------------------------------------------------------------------
##------------------------------------------------------------------------------
##------------------------------------------------------------------------------
  # select subset of visits where sensor change happened
  ss= working[which(working$ec_sensor_change=='yes'),]
  
  #update the device magic tabel for entries where ec_sensor_changes haven't occurred
  ss_other= working[which(working$ec_sensor_change=='no'),]
  for (D in ss_other$dmid){
    query= sprintf("UPDATE chrl.device_magic SET sensor_added='Yes' WHERE dmid=%s",D)
    dbSendQuery(con,query)
  }
  
  # select sensor data already in the database
  Old_data= dbGetQuery(con, "SELECT * FROM chrl.sensors")
  
  
  # determine which actions occurred for sensor changes
  # Note: for each action the database will change in a different way
  Action= unlist(strsplit(ss$action,','))
  Action= Action[Action!=" "]
  ss$action=Action 
  
  Warning= NA
  Res=c(-999); Com=c();
  

  #run the code for each different sensor action in the data
  for (x  in c(1:length(Action))){
    
    #define the action we are working with
    wk_action= Action[x]
    
    if (nrow(ss[ss$action==wk_action,])>1){
      next()
    }
    
    #define the dmid of the event for print messages
    D= ss[ss$action==wk_action,'dmid']
    
    # case where sensor is replaced in the river
    if (wk_action=='Replace'){
      # run the replace function
      Num= Replace(ss,Old_data)
      
      #if function ran smoothly, change the device magic table, and record a comment
      if (as.numeric(Num[1])==1){
        Com= append(Com,"Sensor was replaced")
        query= sprintf("UPDATE chrl.device_magic SET sensor_added='Yes' WHERE dmid=%s",D)
        dbSendQuery(con,query)
        
      # if there is an error in the sensor replacement print the error and record a zero
      } else {
        Com= append(Com,sprintf("Error in sensor replacement: %s",Num[2]))
        Res= append(Res,0)
      }
      
      # the case where the sensor is removed without replacement
    } else if (wk_action=='Remove'){
        # run the remove function
        Num= Remove(ss, Old_data)
        
        #if function ran smoothly, change the device magic table, and record a comment
        if (as.numeric(Num[1])==1){
          Com=append(Com,"Sensor was removed")
          query= sprintf("UPDATE chrl.device_magic SET sensor_added='Yes' WHERE dmid=%s",D)
          dbSendQuery(con,query)
          
          # if there is an error in the sensor replacement print the error and record a zero
        } else {
          Com= append(Com,sprintf("Error in sensor removal: %s",Num[2]))
          Res= append(Res,0)
        }
       
      #case where a sensor is added to the network   
    } else if (wk_action=='Add'){
        #run the add sensor function
        Num= Add(ss, Old_data)
        
        #if function ran smoothly, change the device magic table, and record a comment
        if  (as.numeric(Num[1])==1){
          Com=append(Com,"Sensor was Added")
          query= sprintf("UPDATE chrl.device_magic SET sensor_added='Yes' WHERE dmid=%s",D)
          dbSendQuery(con,query)
        
        # if there is an error in the sensor replacement print the error and record a zero
        } else {
            Com=append(Com,sprintf("Error in sensor addition: %s",Num[2]))
            Res= append(Res,0)
        }
    }
  }
  
  # if there was an error in one of the sensor functions, return a zero
  if ((0 %in% Res)==TRUE){
    return(c(0,Com))
    
  # if the functions run smoothly, return a one
  } else{
    return(c(1,Com))
  }
}

field_visit_update <- function (working, S){
  # Updates the field visit table in the database with data collected on the 
  # device magic form and combines new data with previously recorded data if need be. 
  
  # select data from the  field visit table in the database
  Old_data= dbGetQuery(con, "SELECT * FROM chrl.field_visits")
  
  # The technicians who were working on the site
  Techs=c()
  for(x in c(1:nrow(working))){
    Techs=append(Techs,unlist(strsplit(working[x,'technician'],', ')))
    Techs=append(Techs,unlist(strsplit(working[x,'technician_other'],', ')))
  }
  Technicians=paste(unique(Techs[Techs!='Other' & is.na(Techs)==FALSE]),collapse = '; ')
  
  
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
  Weather=paste(unique(trimws(working[which(is.na(working$notes_weather)==FALSE),'notes_weather'])),collapse = '; ')
  Repairs= paste(unique(trimws(working[which(is.na(working$notes_repairs)==FALSE),'notes_repairs'])),collapse = '; ')
  ToDo= paste(unique(trimws(working[which(is.na(working$notes_todo)==FALSE),'notes_todo'])),collapse = '; ')
  Other=paste(unique(trimws(working[which(is.na(working$notes_other)==FALSE),'notes_other'])),collapse = '; ')
  
  if (grepl("'", Weather)==TRUE){
    Notes=gsub("'","",Weather)
  }
  if (grepl("'", Repairs)==TRUE){
    Notes=gsub("'","",Repairs)
  }
  if (grepl("'", ToDo)==TRUE){
    Notes=gsub("'","",ToDo)
  }
  if (grepl("'", Other)==TRUE){
    Notes=gsub("'","",Other)
  }
  
  
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
    K=unique(append(trimws(unlist(strsplit(Weather, "; "))),trimws(unlist(strsplit(Old_wk$weather, "; ")))))
    Weather= paste(K[K!='NULL'& is.na(K)==FALSE], collapse = '; ')
    
    K= unique(append(trimws(unlist(strsplit(Repairs, "; "))),trimws(unlist(strsplit(Old_wk$repairs_adjustments, "; ")))))
    Repairs= paste(K[K!="NULL" & is.na(K)==FALSE], collapse = '; ')
    
    K=unique(append(trimws(unlist(strsplit(ToDo, "; "))),trimws(unlist(strsplit(Old_wk$todo, "; ")))))
    ToDo= paste(K[K!='NULL'& is.na(K)==FALSE], collapse = '; ')
    
    K=unique(append(trimws(unlist(strsplit(Other, "; "))),trimws(unlist(strsplit(Old_wk$other, "; ")))))
    Other=paste(K[K!="NULL"& is.na(K)==FALSE], collapse = '; ')
    
    
    DMI=paste(unique(append(trimws(unlist(strsplit(DMI,'; '))),trimws(unlist(strsplit(Old_wk$dmid,'; '))))), collapse = '; ')
    
    
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
    
   
  # add new entry to database if there is no previous records for the visit 
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

CF_event_check <-  function(working, S){
  Warning= NA
  
  PMP= Empty_string(trimws(unlist(strsplit(working$time_barrel_period,','))))
  Trials= as.numeric(Empty_string(trimws(unlist(strsplit(working$trials_cf,',')))))
  query= sprintf("Select * from chrl.calibration_events  WHERE SiteID=%s AND Date='%s'",S,Date)
  CF= dbGetQuery(con,query)
  
  if (nrow(CF)==0){
    Warning= sprintf('No CF sheets have been uploaded from the field visit at site %s on %s. Please upload all sheets.',S,Date)
    return(c(0, Warning))
  }
  
  if(any(working$barrel_fill=='yes')){
    if ('Mid' %in% PMP){
      Warning= sprintf("As there was a barrel fill on %s at site %s, a Mid CF event should not have taken place. Please  review the CF sheets and device magic note",Date, S)
      return(c(0, Warning))
    } 
  }
  
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
  } else {
    Warning= paste(c("The CF table shows",CF$pmp, " measurements and the device magic forms show",PMP,". Please determine the correct CF measurements"),collapse=" ")
    return(c(0, Warning))
  }
  
return(c(1,Warning))
}
  
