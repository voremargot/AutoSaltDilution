# These are the R packages that need to be installed in order to run the autosalt project scripts on your local computer.
# Please note that some scripts require the user defined functions located in AutoSalt_Functions.R script in the repository
# so this file will need to be downloaded into the same folder as your other scripts for the project. 

install.packages('googledrive')
install.packages('RPostgres')
install.packages('DBI')
install.packages('openxlsx')
install.packages('lubridate')
install.packages('stringi')
install.packages('prodlim')
install.packages('dplyr')

install.packages('XLConnect') 
# Note that the new version of XLConnect needs the cat.exe command to run. If you are working on windows you may need to 
# get the cat.exe program and put its location in your PATH variables on your computer. 
# cat.exe is a common linux command.This is a bug in the updated version of XL connect and will hopefully be fixed soon! 
