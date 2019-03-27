#cat test.txt | awk -F'[' '{print $3}' > test2.txt
#cat test2.txt | awk -F']' '{print $1}' > test3.txt
#cat test3.txt | awk '{print "tab["NR-1"]="$1}'

tab[0]=02BA0115
tab[1]=02CS3004
tab[2]=02CS3005
tab[3]=02FT0901
tab[4]=02LT0201
tab[5]=02LT0301
tab[6]=02PT0801
tab[7]=02TT3201
tab[8]=02TT3301
tab[9]=02VE0115
tab[10]=02ZA0602
tab[11]=02ZA0614
tab[12]=02ZA0615
tab[13]=02ZA0616
tab[14]=02ZA0621
tab[15]=02ZA0627
tab[16]=02ZA0635
tab[17]=02ZA0645
tab[18]=02ZA0648
tab[19]=03BA0116
tab[20]=04BA0119
tab[21]=04DG0102
tab[22]=04DG0104
tab[23]=04DG0110
tab[24]=04DG0112
tab[25]=04DG0114
tab[26]=04DG0116
tab[27]=04EX0523
tab[28]=04FT0903
tab[29]=04FT0905
tab[30]=04LT0202
tab[31]=04LT0204
tab[32]=04LT0205
tab[33]=04LT0207
tab[34]=04LT0302
tab[35]=04LT0303
tab[36]=04LT0304
tab[37]=04LT0305
tab[38]=04LT0307
tab[39]=04LT0308
tab[40]=04PT0803
tab[41]=04PT0804
tab[42]=04PT0805
tab[43]=04TT3203
tab[44]=04TT3303
tab[45]=04VE0116
tab[46]=04VE0118
tab[47]=04ZA0607
tab[48]=04ZA0612
tab[49]=04ZA0618
tab[50]=04ZA0620
tab[51]=04ZA0622
tab[52]=04ZA0624
tab[53]=04ZA0630
tab[54]=04ZA0632
tab[55]=04ZA0634
tab[56]=04ZA0636
tab[57]=04ZA0638
tab[58]=04ZA0640
tab[59]=04ZA0642
tab[60]=04ZA0644
tab[61]=05LT0206
tab[62]=05LT0209
tab[63]=05LT0306
tab[64]=05LT0309

len=${#tab[*]}

echo "len : "$len

idMessage=275
increment=1
for i in ${!tab[@]}
do 
	numeroGenre=${tab[i]:0:2}
	if [ $numeroGenre == '01' ]
	then
		idGenre=3
	elif [ $numeroGenre == '02' ]
	then
		idGenre=4
	elif [ $numeroGenre == '03' ]
        then
                idGenre=5
	elif [ $numeroGenre == '04' ]
        then
                idGenre=2
        elif [ $numeroGenre == '05' ]
        then
                idGenre=1
	fi
	
	categorie=${tab[i]:2:2}
	numeroModule=${tab[i]:4:2}
	numeroMessage=${tab[i]:6:2}
	message="Error_Module_$idMessage"
	idMessage=`expr $idMessage + $increment`
	echo "INSERT INTO t_module (genre_id, fichieripc_id, intitule_module, categorie, numero_module, numero_message, message) VALUES ('$idGenre', '6', 'ErrorModule', '$categorie', '$numeroModule', '$numeroMessage', '$message');" >> "./requete.txt"
done



