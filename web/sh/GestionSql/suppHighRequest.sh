#!/bin/bash

# Script permettant d'interrompre les requêtes trop longues à l'execution

linecount=0
user='cargo'
password='adm5667'
tempAttente=260
fichierlog=${BOILERBOX}/web/logs/system.log

while true
do
processes=$(echo "show full processlist" | mysql -ucargo -padm5667)
oldIfs=$IFS
IFS='
'
#echo "Checking for slow MySQL queries..."
for line in $processes
do
	#echo "Line $line"
    	if [ "$linecount" -gt 0 ]
    	then
           	pid=$(echo "$line" | cut -f1)
           	length=$(echo "$line" | cut -f6)
           	query=$(echo "$line" | cut -f8)

           	#Id User    Host    db  Command Time    State   Info
		if [ "$(echo $length | grep "^[ [:digit:] ]*$")" ]
		then
            		if [ "$length" -gt $tempAttente ]
            		then
				requeteSelect=$(echo "$query" | grep "SELECT")
				if [ ! -z "$requeteSelect" ]
                		then
					datekill=`date "+%Y-%m-%d %H:%M:%S"`
                			killoutput=$(echo "kill query $pid" | mysql -ucargo -padm5667)
					echo "$datekill;La requête a atteind la limite de $tempAttente secondes;Arrêt de la requête : $query" >> $fichierlog
                		fi
            		fi
		fi
    	fi
linecount=`expr $linecount + 1`
done
IFS=$oldIfs
done
