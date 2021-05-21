readRenviron('C:/Program Files/R/R-3.6.2/.Renviron')
options(java.parameters = c("-XX:+UseConcMarkSweepGC", "-Xmx8192m"))

library(DBI)
library(curl)
library(tidyverse)

con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

Site=626
Rating_Curve_Version=5

query= sprintf("SELECT * FROM chrl.autosalt_summary WHERE SiteID=%s",Site)
autosalt_summary= dbGetQuery(con,query)
autosalt_summary= autosalt_summary[is.na(autosalt_summary$discharge_avg)==FALSE,]

query= sprintf("SELECT * FROM chrl.RC_summary WHERE SiteID=%s AND Version=%s",Site, (Rating_Curve_Version-1))
PreviousRC= dbGetQuery(con,query)
RCID= PreviousRC$rcid
EndDate= as.Date(PreviousRC$end_date)

query=sprintf("SELECT * FROM chrl.rcautosalt WHERE SiteID=%s AND RCID=%s",Site,RCID)
Autosalt_included= dbGetQuery(con,query)


for (r in c(1:nrow(autosalt_summary))){
  EventID= autosalt_summary[r,'eventid']
  Year=as.numeric(format(as.Date(autosalt_summary[r,'date']),'%Y'))
  Month= as.numeric(format(as.Date(autosalt_summary[r,'date']),'%m'))
  
  if (Month >=10){
    WY= sprintf('%s-%s',Year,(Year+1))
  } else {
    WY= sprintf('%s-%s',(Year-1),Year)
  }
  
  autosalt_summary[r,'WY']=WY
  if (nrow(Autosalt_included[Autosalt_included$eventid==EventID,])>=1){
    autosalt_summary[r,'Included']='Y'
    autosalt_summary[r,'EventNumber']=Autosalt_included[Autosalt_included$eventid==EventID,'eventno']
  } else {
    autosalt_summary[r,'Included']='N'
    autosalt_summary[r,'EventNumber']=NA
  }
}

autosalt_summary[autosalt_summary$date <= EndDate,'OldNew']='Old'
autosalt_summary[autosalt_summary$date > EndDate,'OldNew']='New'
autosalt_summary$Method='Autosalt'
autosalt_summary$MID=NA

Final=autosalt_summary[,c('eventid','MID','siteid','Method','date','start_time','WY','OldNew','EventNumber','Included','stage_average','stage_std','stage_dir',
                    'discharge_avg','uncert','mixing','ecb','notes')]


##-------------------------------------------------------------------------------------
##-------------------------Manual discharge--------------------------------------------
##-------------------------------------------------------------------------------------

query=sprintf("SELECT * FROM chrl.rcmanual WHERE SiteID=%s AND RCID=%s",Site,RCID)
Manual_included= dbGetQuery(con,query)

query= sprintf("SELECT * FROM chrl.manual_discharge WHERE SiteID=%s",Site)
manual_summary= dbGetQuery(con,query)
manual_summary= manual_summary[is.na(manual_summary$discharge)==FALSE,]


for (r in c(1:nrow(manual_summary))){
  Year=as.numeric(format(as.Date(manual_summary[r,'date']),'%Y'))
  Month= as.numeric(format(as.Date(manual_summary[r,'date']),'%m'))
  
  if (Month >=10){
    WY= sprintf('%s-%s',Year,(Year+1))
  } else {
    WY= sprintf('%s-%s',(Year-1),Year)
  }
  manual_summary[r,'WY']=WY
  MDisID= manual_summary[r,'mdisid']
  if (nrow(Manual_included[Manual_included$mdisid==MDisID,])>=1){
    manual_summary[r,'Included']='Y'
    manual_summary[r,'EventNumber']=Manual_included[Manual_included$mdisid==MDisID,'eventno']
  } else {
    manual_summary[r,'Included']='N'
    manual_summary[r,'EventNumber']=NA
  }
}

manual_summary[manual_summary$date <= EndDate,'OldNew']='Old'
manual_summary[manual_summary$date > EndDate,'OldNew']='New'

manual_summary$eventid=NA
manual_summary$stage_std=NA
manual_summary$stage_dir=NA
manual_summary$mixing= NA
manual_summary$ecb=NA

manual_summary= manual_summary %>% 
      rename(MID=  mdisid) %>%
      rename(start_time = time)  %>%
      rename(stage_average= stage)%>%
      rename(discharge_avg = discharge)%>%
      rename(notes= comment) %>%
      rename( Method= method )

Final= rbind(Final, manual_summary[,c('eventid','MID','siteid','Method','date','start_time','WY','OldNew','EventNumber','Included',
                               'stage_average','stage_std','stage_dir','discharge_avg','uncert','mixing','ecb','notes')])

Final = Final %>%
  rename(Old_New=OldNew) %>%
  rename(Event_no = EventNumber) %>%
  rename(Date= date) %>%
  rename(Final_rating_curve= Included)%>%
  rename(Start_time= start_time ) %>%
  rename(Stage_avg= stage_average) %>%
  rename(Stage_stdv= stage_std) %>%
  rename(Stage_delta= stage_dir) %>%
  rename(Q_meas= discharge_avg) %>% 
  rename(Q_rel_unc=  uncert) %>%
  rename(Mixing= mixing) %>%
  rename(Comments= notes) %>% 
  rename(EventID= eventid) %>%
  rename(SiteID= siteid)

write.csv(Final, sprintf('working_directory/Metadata_RC_%s_V%s.csv',Site,Rating_Curve_Version), row.names = FALSE)

