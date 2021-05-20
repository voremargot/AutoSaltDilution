readRenviron('C:/Program Files/R/R-3.6.2/.Renviron')
options(java.parameters = c("-XX:+UseConcMarkSweepGC", "-Xmx8192m"))

library(DBI)
library(curl)

con <- dbConnect(RPostgres::Postgres(), dbname=Sys.getenv('dbname'),host=Sys.getenv('host'),user=Sys.getenv('user'),password=Sys.getenv('password'))

Site=626
Rating_Curve_Version=5

query= sprintf("SELECT * FROM chrl.autosalt_summary WHERE SiteID=%s",Site)
autosalt_summary= dbGetQuery(con,query)
autosalt_summary= autosalt_summary[is.na(autosalt_summary$discharge_avg)==FALSE,]

query= sprintf("SELECT * FROM chrl.RC_summary WHERE SiteID=%s AND Version=%s",Site, (Rating_Curve_Version-1))
PreviousRC= dbGetQuery(con,query)
RCID= PreviousRC$rcid

query=sprintf("SELECT * FROM chrl.rcautosalt WHERE SiteID=%s AND RCID=%s",Site,RCID)
Autosalt_included= dbGetQuery(con,query)


for (r in c(1:nrow(autosalt_summary))){
  EventID= autosalt_summary[r,'eventid']
  if (nrow(Autosalt_included[Autosalt_included$eventid==EventID,])>=1){
    autosalt_summary[r,'Included']='Y'
  } else {
    autosalt_summary[r,'Included']='N'
  }
}