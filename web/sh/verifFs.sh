#!/bin/sh
repertoireLogs=${BOILERBOX}/web/
pourcentage=`df -kh $repertoireLogs | grep -v "Sys" | awk -F ' ' '{print $5}' | cut -c1-2`
# Si le pourcentage d'utilisation est plus elevé que 95% on arrête les scripts d'insertion et on retourne une erreurs 
if [ $pourcentage -gt 95 ]
then
	echo "plus de 50"
fi



