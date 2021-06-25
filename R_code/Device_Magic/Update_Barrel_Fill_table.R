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

Old_data= dbGetQuery(con, "SELECT * FROM chrl.barrel_periods")
for (D in Visit_Dates){
  Subset= Field[which(Field$date_visit==D),]
  Date= as.Date(unique(Subset$date_visit))
  
  for (S in unique(Subset$siteid)){
    working= Subset[which(Subset$siteid==S),] 
    
    if (any(working$barrel_fill=='yes')==TRUE){
      ss= working[working$barrel_fill=='yes',]
      if (nrow(ss)>1){
        Distin= distinct(ss[,c('siteid','barrel_fill','volume_solution','salt_added','water_added','volume_depart','salt_remaining_site')])
        if (nrow(Distin)>1){
          print(sprintf("Multiple barrel fills with differing data were recorded on %s at site %s: Please check the field records",Date,S))
          next()
        } else {
          ss=ss[1,]
        }
        
      }
      
      Volume_at_start=ss$volume_solution
      Added_Salt= ss$salt_added
      Volume_at_depart= ss$volume_depart
      Salt_remaining_at_site= ss$salt_remaining_site
      Notes= ss$barrel_fill_notes
      
      PeriodID= Old_data[which(is.na(Old_data$ending_date)==TRUE & Old_data$siteid==S),'periodid']
      query=sprintf("UPDATE chrl.barrel_periods SET ending_date='%s', solution_at_end= %s WHERE periodid=%s",Date,Volume_at_start, PeriodID)
      # dbSendQuery(con,query) 
      
      query=sprintf("INSERT INTO chrl.barrel_periods (SiteID,starting_date,ending_date,solution_at_start,solution_at_end,salt_added,salt_remaining_on_site,notes) VALUES (
      %s,'%s','NULL',%s,'NULL',%s,%s,'%s')",S,Date,Volume_at_depart,Added_Salt,Salt_remaining_at_site,Notes)
      
      query <- gsub("\\n\\s+", " ", query)
      query <- gsub('NA',"NULL", query)
      query <- gsub("'NULL'","NULL",query)
      query <- gsub('NaN',"NULL",query)
      # dbSendQuery(con,query)
    }
  }
  
}