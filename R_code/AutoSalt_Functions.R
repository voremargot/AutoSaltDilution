Column_Names= function(H,S){
  # Extracts the raw data columns that are temperature corrected 
  # from the datasets  (only temperature corrected data is used)
  # in the analysis.
  
    
  ProbeNum= c('e_EC','2_EC','3_EC')
  N=c()
  c=1
  for (i in ProbeNum){
    x = colnames(H)[grep(i, colnames(H), ignore.case=T)]
    if (length(x)==2){
      W=paste(i,'T',sep='')
      N[c]= x[grep(W,x, ignore.case=T)]
    } else if (length(x)==0){
      next
    } else{
      N[c]=x[grep(i,x, ignore.case=T)]
    }
    c=c+1
  }
  if (length(N)==0){
    N=c()
    if('WQ_ECT' %in% colnames(H)){
      N=append(N,'WQ_ECT')
    }else if('WQ_EC' %in% colnames(H)){
      N=append(N,'WQ_EC')
    }
    
    if('WQ_ECT2' %in% colnames(H)){
      N=append(N,'WQ_ECT2')
    }else if('WQ_EC2' %in% colnames(H)){
      N=append(N,'WQ_EC2')
    }
    
    if('THRECS_ECT' %in% colnames(H)){
      N=append(N,'THRECS_ECT')
    }else if('THRECS_EC' %in% colnames(H)){
      N=append(N,'THRECS_EC')
    }
  }
  return(N)
}

##----------------------------------------------------------------------------------
##---------------------------------------------------------------------------------

AutoSalt_Mixing=function(Discharge_Results) {
  Mixing_array=c()
  for(c in unique(Discharge_Results$CalEventID)){
    Sub= Discharge_Results[which(Discharge_Results$CalEventID==c & Discharge_Results$Used=='Y'),]
    if (nrow(Sub) <2 | length(unique(Sub$SensorID))==1){
      Mixing_array=append(Mixing_array,NA)
      next()
    }
    row_count= c(1:nrow(Sub))
    Comb= combn(row_count,2)
    for (e in c(1:ncol(Comb))){
      working= Comb[,e]
      for (count in c(1,2)){
        if (Sub[working[1],'SensorID']==Sub[working[2],'SensorID']){
          next
        }
        D1= Sub[working[1],'Discharge']
        D2= Sub[working[2], 'Discharge']
        E1= Sub[working[1],'AbsErr']
        E2=Sub[working[2],'AbsErr']
        
        if (count==1){
          if (D1 <D2){
            Mixing= ((D2-E2)-(D1+E1))/(D2-E2)*100
          } else {
            Mixing= ((D1-E1)-(D2+E2))/(D2+E2)*100
          }
          if (Mixing <0){
            Mixing=0
          }
          Mixing_array=append(Mixing_array, Mixing)
          
        } else if (count==2){
          if (D2 <D1){
            Mixing= ((D1-E1)-(D2+E2))/(D1-E1)*100
          } else {
            Mixing= ((D2-E2)-(D1+E1))/(D1+E1)*100
          }
          
          if (Mixing <0){
            Mixing=0
          }
          Mixing_array=append(Mixing_array, Mixing)
        }
        
      }
    }
  }
  
  if (length(which(is.na(Mixing_array)==TRUE))==length(Mixing_array)){
    Mixing=NA
  } else if (length(which(Mixing_array==0))==length(Mixing_array)){
    Mixing= 0
  } else {
    Mixing_array=Mixing_array[(which(Mixing_array!=0))]
    Mixing= mean(Mixing_array,na.rm=TRUE)
  }
  
  if(is.nan(Mixing)==TRUE){
    Mixing= NA
  }
  
  return(Mixing)
  
}



