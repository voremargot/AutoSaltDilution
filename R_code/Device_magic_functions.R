##------------------------------------------------------------------------------
# Created by: Margot Vore 
# July 2021
# 
# This script contains functions that are used to check three different scenarios
# from the field: the salt solution reservoir was refilled, the sensor network was
# changed,  and a new site visit occurred. Each function checks for specific errors
# that may occur during data entry and warn the user if such problems occur.
##------------------------------------------------------------------------------


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
  
  # check if there is already barrel period data in the database from the date of field visit
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
  
  #if all data is a duplicate of what is in database, end function
  if (is.na(Duplicate)==FALSE){
    return(c(1,warning))
  }
  
  # if there are multiple device magic entries for the same visit,  make sure barrel
  # fill data is the same
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
  
  #remove ' from strings
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
  Replace <- function (ss_sub, Old_data) {
    ### This function selects the old and new sensors from the device magic form
    ### and checks that the data entered into the device magic form correctly corresponds
    ### to the data in the database. If the data in the device magic form does not match with that in 
    ### the database, the function will return a 1 and a detailed warning of the problem, otherwise the
    ### code produces a 1 and no warning.
    
    Warning=NA
    
    # subset data
    data= ss_sub[grepl('Replace',ss_sub$action)==TRUE,]
    
    #define dmid 
    D= data$dmid
    
    Date= as.Date(data$date_visit)
    
    # removed sensor type
    Remove_Type=trimws(unlist(strsplit(data$sen_r_removed_type,',')))
    if (any(Remove_Type=="Other")==TRUE){
      Oth= which(Remove_Type=='Other')
      for (O in Oth){
        Remove_Type[O]= toupper(trimws(unlist(strsplit(data$sen_r_removed_type_other,', '))[O]))
      }
    }
    Remove_Type=Empty_string(Remove_Type)
    
    # removed sensor's serial number
    Remove_SN=toupper(Empty_string(trimws(unlist(strsplit(data$sen_r_removed_sn,',')))))
    
    #removed sensor's probe number
    Remove_Probe= as.numeric(Empty_string(unlist(strsplit(data$sen_r_removed_probenum,','))))
    
    # the replacement probe type
    New_Type=trimws(unlist(strsplit(data$sen_r_new_type,',')))
    if (any(New_Type=="Other")==TRUE){
      Oth= which(New_Type=='Other')
      for (O in Oth){
        New_Type[O]= toupper(trimws(unlist(strsplit(data$sen_r_new_type_other,', '))[O]))
      }
    }
    New_Type=Empty_string(New_Type)
    
    
    # replacement probe serial number
    New_SN= toupper(trimws(Empty_string(unlist(strsplit(data$sen_r_new_sn,',')))))
    
    # replacement probe number
    New_Probe= as.numeric(Empty_string(unlist(strsplit(data$sen_r_new_probenum,','))))
    
    #replacement probe river location
    New_RL=trimws(unlist(strsplit(data$sen_r_new_rivloc,',')))
    if (any(New_RL=='Other')==TRUE){
      Oth= which(New_RL=='Other')
      for (O in Oth){
        New_RL[O]= trimws(unlist(strsplit(data$sen_r_new_rivloc_other,', '))[O])
      }
    }
    New_RL= Empty_string(New_RL)
    
    # make sure data is not a duplicate of what is already in database
    DF= data.frame(probe_number_remove=Remove_Probe,sensor_type_remove=Remove_Type,serial_number_remove= Remove_SN, probe_number_add= New_Probe, sensor_type_add= New_Type, serial_number_add= New_SN, riverloc_add= New_RL)
    
    if (is.null(nrow(Old_data[which(Old_data$siteid==S & Old_data$deactivation_date==Date),"probe_number"]))==TRUE){
      Duplicate=data.frame()
    } else{
      Duplicate= data.frame(probe_number_remove= Old_data[which(Old_data$siteid==S & Old_data$deactivation_date==Date),"probe_number"],
                            sensor_type_remove=Old_data[which(Old_data$siteid==S & Old_data$deactivation_date==Date),"sensor_type"],
                            serial_number_remove=Old_data[which(Old_data$siteid==S & Old_data$deactivation_date==Date),"serial_number"],
                            probe_number_add= Old_data[which(Old_data$siteid==S & Old_data$install_date==Date),"probe_number"],
                            sensor_type_add= Old_data[which(Old_data$siteid==S & Old_data$install_date==Date),"sensor_type"],
                            serial_number_add= Old_data[which(Old_data$siteid==S & Old_data$install_date==Date),"serial_number"],
                            riverloc_add=Old_data[which(Old_data$siteid==S & Old_data$install_date==Date),"river_loc"])
    }

    
    if (nrow(Duplicate)>0){
      new_rows= anti_join(DF,Duplicate,by=c("probe_number_remove","sensor_type_remove","serial_number_remove","probe_number_add","sensor_type_add","serial_number_add","riverloc_add"))
      
      #if all data are duplicates
      if (nrow(new_rows)==0){
        return(data.frame(Type='Replace',Work=1,Changed=0,Com=Warning))
        
        #remove any entries that are duplicated
      } else{
        Remove_Probe= new_rows$probe_number_remove
        Remove_Type= new_rows$sensor_type_remove
        Remove_SN= new_rows$serial_number_remove
        New_Probe= new_rows$probe_number_add
        New_SN= new_rows$serial_number_add
        New_RL= new_rows$riverloc_add
        New_Type= new_rows$sensor_type_add
      }
    }
    
  
    
    # check the probe that you are removing hasn't previously been removed
    for (m in c(1:length(Remove_Type))){
      wk_old= Old_data[which(Old_data$siteid==S & Old_data$sensor_type==Remove_Type[m] & Old_data$serial_number==Remove_SN[m]),]
      if (nrow(wk_old)==0){
        Warning= sprintf('Sensor %s that you are trying to deactivate doesnt exist in the stream at this site! Please review the field notes.',Remove_SN[m])
        return (data.frame(Type='Replace',Work=0,Changed=length(Remove_SN),Com=Warning))
        
      } else if (nrow(wk_old)>0){
        wk_old= wk_old[is.na(wk_old$deactivation_date)==TRUE,]
        if (nrow(wk_old)==0){
          Warning= sprintf('Sensor %s that you are trying to deactivate has previously been taken out! Please review the field notes.',Remove_SN[m])
          return (data.frame(Type='Replace',Work=0,Changed=length(Remove_SN),Com=Warning))
        }
      }
    }
    
  
    # make sure the removed probed and replacement probe have the same probe numbers
    if (any(New_Probe != Remove_Probe)==TRUE){
      Warning=paste(c(sprintf('The probe numbers you are replacing are different (New Probe -> ',D), New_Probe, "Old Probe ->", Remove_Probe,". Please check the data."),collapse=" ")
      return(data.frame(Type='Replace',Work=0,Changed=length(Remove_SN),Com=Warning))
    }
    
    # update the deactivation date of the replaced sensor
    for (m in c(1:length(Remove_Type))){
      wk_old= Old_data[which(Old_data$siteid==S & Old_data$sensor_type==Remove_Type[m] & Old_data$serial_number==Remove_SN[m] & is.na(Old_data$deactivation_date)==TRUE),]
      sensorid_old= wk_old$sensorid
      
      query= sprintf("UPDATE chrl.sensors SET deactivation_date='%s' WHERE sensorid=%s", Date,sensorid_old)
      query <- gsub("\\n\\s+", " ", query)
      query <- gsub('NA',"NULL", query)
      query <- gsub("'NULL'","NULL",query)
      query <- gsub('NaN',"NULL",query)
      
      dbSendQuery(con,query)
    }
    
    
    # add a new record for the new sensor that was placed in the stream
    query= sprintf("INSERT INTO chrl.sensors (siteid, probe_number,sensor_type,serial_number,river_loc,install_date) VALUES
                         (%s,%s,'%s','%s','%s','%s')", S,New_Probe,New_Type,New_SN,New_RL,Date)
    query <- gsub("\\n\\s+", " ", query)
    query <- gsub('NA',"NULL", query)
    query <- gsub("'NULL'","NULL",query)
    query <- gsub('NaN',"NULL",query)
    
    for (Q in query){
      dbSendQuery(con,Q)
    }
    
    
    return (data.frame(Type='Replace',Work=1,Changed=length(Remove_SN),Com=Warning))
  }
  
  # function that is used if a sensor is removed without replacement
  Remove <- function(ss_sub,Old_data){
    ### This function selects the sensor that was removed from the device magic form 
    ### and does checks that the data entered into the device magic form correctly corresponds
    ### to the data in the database. If the data in the device magic form does not match with that in 
    ### the database, the function will return a 1 and a detailed warning of the problem, otherwise the
    ### code produces a 1 and no warning
    
    Warning=NA
    
    # subset the data to events where sensors have been removed
    data=ss_sub[grepl('Remove',ss_sub$action)==TRUE,]
    
    #specify the dmid for warning messages
    D=data$dmid
    
    Date= as.Date(data$date_visit)
    
    # Sensor type that was removed
    Remove_Type= trimws(unlist(strsplit(data$sen_remove_type, ", ")))
    if (any(Remove_Type=='Other')==TRUE){
      Oth= which(Remove_Type=='Other')
      for (O in Oth){
        Remove_Type[O]= toupper(trimws(unlist(strsplit(data$sen_remove_type_other,', '))[O]))
      }
    }
    Remove_Type=Empty_string(Remove_Type)
    
    #probe number of sensor removed
    Remove_Probe= as.numeric(Empty_string(unlist(strsplit(data$sen_remove_probenum,", "))))
    
    # serial number of removed sensor
    Remove_SN= toupper(trimws(Empty_string(unlist(strsplit(data$sen_remove_sn,", ")))))
    
    # check if any new data is a duplicate of what is in database
    DF= data.frame(probe_number=Remove_Probe,sensor_type=Remove_Type,serial_number= Remove_SN)
    Duplicate= Old_data[which(Old_data$siteid==S & Old_data$deactivation_date==Date),c("probe_number","sensor_type","serial_number")]
    
    if (nrow(Duplicate)>0){
      new_rows= anti_join(DF,Duplicate,by=c("probe_number","sensor_type","serial_number"))
      if (nrow(new_rows)==0){
        return(data.frame(Type='Remove',Work=1,Changed=0,Com=Warning))
        
        #remove any entries that are duplicated
      } else{
        Remove_Probe= new_rows$probe_number
        Remove_Type= new_rows$sensor_type
        Remmove_SN= new_rows$serial_number
      }
    }
    
    # check that sensor being removed exists in stream and is active
    for (m in c(1:length(Remove_Type))){
      wk_old= Old_data[which(Old_data$siteid==S & Old_data$sensor_type==Remove_Type[m] & Old_data$serial_number==Remove_SN[m] & Old_data$probe_number==Remove_Probe[m]),]
      if (nrow(wk_old)==0){
        Warning= sprintf('Sensor %s that you are trying to deactivate doesnt exist in the stream at this site! Please review the field notes.',Remove_SN[m])
        return (data.frame(Type='Replace',Work=0,Changed=length(Remove_SN),Com=Warning))
      } else if (nrow(wk_old)>0){
        wk_old= wk_old[is.na(wk_old$deactivation_date)==TRUE,]
        if (nrow(wk_old)==0){
          Warning= sprintf('Sensor %s that you are trying to deactivate has previously been taken out! Please review the field notes.',Remove_SN[m])
          return (data.frame(Type='Replace',Work=0,Changed=length(Remove_SN),Com=Warning))
        }
      }
    }
    
   
  
    # update the deactivation date of the removed sensor in the database
    for (m in c(1:length(Remove_Type))){
      wk_old= Old_data[which(Old_data$siteid==S & Old_data$sensor_type==Remove_Type[m] & Old_data$serial_number==Remove_SN[m] & is.na(Old_data$deactivation_date)==TRUE),]
      SensorID= wk_old$sensorid
      query=sprintf("UPDATE chrl.sensors SET deactivation_date= '%s' WHERE sensorid=%s", Date,SensorID)
      dbSendQuery(con,query) 
    }
    
    
    return(data.frame(Type='Remove',Work=1,Changed=length(Remove_SN),Com=Warning))
  }
  
  # function that is used if a sensor is added to the network
  Add  <- function(ss_sub, Old_data){
    ### This function selects the added  sensor(s) from the device magic form
    ### and does checks that the data entered into the device magic form correctly corresponds
    ### to the data in the database. If the data in the device magic form does not match with that in 
    ### the database, the function will return a 1 and a detailed warning of the problem, otherwise the
    ### code produces a 1 and no warning.
    
    Warning=NA
    
    # Subset the data to events where sensors were added
    data=ss_sub[grepl('Add',ss_sub$action)==TRUE,]
    
    #specify the dmid for warning messages
    D=data$dmid
    
    Date= as.Date(data$date_visit)
    
    # define the sensor type of the new sensor
    Add_Type=trimws(unlist(strsplit(data$sen_add_type,', ')))
    if(any(Add_Type=='Other')==TRUE){
      Oth= which(Add_Type=='Other')
      for (O in Oth){
        Add_Type[O]= toupper(trimws(unlist(strsplit(data$sen_add_type_other,', '))[O]))
      }
    }
    Add_Type=Empty_string(Add_Type)
    
    # Added sensor probe number
    Add_Probe= as.numeric(Empty_string(unlist(strsplit(data$sen_add_probenum,', '))))
    
    # Added sensor serial number
    Add_SN= toupper(Empty_string(trimws(unlist(strsplit(data$sen_add_sn,', ')))))
    
    # Added sensor river location
    Add_RiverLoc= trimws(unlist(strsplit(data$sen_add_riverloc,', ')))
    if (any(Add_RiverLoc=='Other')==TRUE){
      Oth= which(Add_RiverLoc=='Other')
      for (O in Oth){
        Add_RiverLoc[O]= trimws(unlist(strsplit(data$sen_add_riverloc_other,', '))[O])
      }
    }
    Add_RiverLoc=Empty_string(Add_RiverLoc)
    
    # ensure new data is not a duplicate of data already in database
    DF= data.frame(probe_number=Add_Probe,sensor_type=Add_Type,serial_number= Add_SN,river_loc=Add_RiverLoc)
    Duplicate= Old_data[which(Old_data$siteid==S & Old_data$install_date==Date),c("probe_number","sensor_type","serial_number","river_loc")]
    
    if (nrow(Duplicate)>0){
      new_rows= anti_join(DF,Duplicate, by=c('probe_number','sensor_type','serial_number','river_loc'))
      if (nrow(new_rows)==0){
        return(data.frame(Type='Add',Work=1,Changed=0,Com=Warning))
        
        #remove any duplicate data
      } else{
        Add_Probe= new_rows$probe_number
        Add_Type= new_rows$sensor_type
        Add_SN= new_rows$serial_number
        Add_RiverLoc= new_rows$river_loc
      }
    }
    
   
    # check that the new probe number is not already in use at that site
    Active_Stations=Old_data[which(Old_data$siteid==S & is.na(Old_data$install_date)==FALSE & is.na(Old_data$deactivation_date)==TRUE),]
    if (any(Add_Probe %in% Active_Stations$probe_number)==TRUE){
      if (length(Add_Probe)>1){
        Warning=print(c('At least one of the sensors that are being added has an overlapping probe number[',Add_Probe,']. Please review the field data'),collapse=' ')
        return(data.frame(Type='Add',Work=0,Changed=length(Add_SN),Com=Warning))
      }else {
        Warning=sprintf('There is already an active sensor with probe number %s (DMID=%s). Please review the field data',Add_Probe,D)
        return(data.frame(Type='Add',Work=0,Changed=length(Add_SN),Com=Warning))
      }
    } 
    
    # check that the new sensor is not already active in the stream
    for (x in c(1:length(Add_SN))){
      US= Active_Stations[Active_Stations$serial_number==Add_SN[x],]
      if (nrow(US)>0){
        if (nrow(US[US$sensor_type==Add_Type[x],])>0){
          Warning=sprintf('There is already an active sensor with the serial number of %s in the stream. Please review the field data',Add_SN[x])
          return(data.frame(Type='Add',Work=0,Changed=length(Add_SN),Com=Warning))  
        }
      }
    }
    
    # create new record for the added sensor in the database
    query <- sprintf("INSERT INTO chrl.sensors (siteid, probe_number,sensor_type,serial_number,river_loc,install_date) VALUES
                         (%s,%s,'%s','%s','%s','%s')",S,Add_Probe,trimws(Add_Type),trimws(Add_SN),trimws(Add_RiverLoc),Date)
    query <- gsub("\\n\\s+", " ", query)
    query <- gsub('NA',"NULL", query)
    query <- gsub("'NULL'","NULL",query)
    query <- gsub('NaN',"NULL",query)
    
    for (Q in query){
      dbSendQuery(con,Q)
    }
  return (data.frame(Type='Add',Work=1,Changed=length(Add_SN),Com=Warning))
    
  }
  
  
##------------------------------------------------------------------------------
##------------------------------------------------------------------------------
##------------------------------------------------------------------------------
  # select subset of visits where sensor change happened
  ss= working[which(working$ec_sensor_change=='yes'),]
  
  #update the device magic table for entries where ec_sensor_changes haven't occurred
  ss_other= working[which(working$ec_sensor_change=='no'),]
  for (D in ss_other$dmid){
    query= sprintf("UPDATE chrl.device_magic SET sensor_added='Yes' WHERE dmid=%s",D)
    dbSendQuery(con,query)
  }
  
  
  # determine which actions occurred for sensor changes
  # Note: for each action the database will change in a different way
  Action= trimws(unlist(strsplit(ss$action,',')))
  Action= unique(Action[Action!=""])
  
  Warning= NA
  Res=c(); Com=c();
  

  #run the code for each different sensor action in the data
  run=0
  for (x  in c(1:length(Action))){
    run=run+1
    #define the action we are working with
    wk_action= Action[x]
    wk_ss= ss[which(grepl(wk_action,ss$action)==TRUE),]
    
    #prep the output dataframe
    Num=data.frame(Type=character(),
                   Work=integer(),
                   Changed= character(),
                   Com= character())
    
    for (y in c(1:nrow(wk_ss))){
      Old_data= dbGetQuery(con, "SELECT * FROM chrl.sensors")
      
      
      # case where sensor is replaced in the river
      if (wk_action=='Replace'){
        Num= rbind(Replace(wk_ss[y,],Old_data),Num)
      } else if (wk_action=='Remove'){
        Num=rbind(Num,Remove(wk_ss[y,], Old_data))
      } else if (wk_action=='Add'){
        Num=rbind(Num,Add(wk_ss[y,], Old_data))
      }
    }
    
    # words  for print message
    if (all(Num$Type=='Replace')==TRUE){
      word= 'replaced'
      word2= 'replacement'
    } else if (all(Num$Type=='Add')==TRUE){
      word= 'added'
      word2= 'addition'
    } else if (all(Num$Type=='Remove')==TRUE){
      word= 'removed'
      word2= 'removal'
    }
    
    # words for print message
    if (sum(Num$Changed)!=1){
      syn='sensors'
      wa ='were'
    } else {
      syn= 'sensor'
      wa='was'
    }
    
    # Save the print messahe and function outputs
    if (all(Num$Work==1)==TRUE){
      Total= sum(Num$Changed)
      Com= append(Com,sprintf("%s %s %s %s",Total,syn,wa,word))
      Res= append(1,Res)
    } else {
      Com= append(Com,sprintf("There was an error during sensor %s: %s",word2,Num[is.na(Num$Com)==FALSE,'Com']))
      Res= append(0,Res)
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
  
  #removing apostrophes
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
    
    #removing apostrophes
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


  
