#!/bin/sh
#Création des liens pour gérer la suppression des flag lors des demarrages system
ln -s /etc/init.d/boilerboxReboot /etc/rc3.d/S99boilerboxReboot
ln -s /etc/init.d/boilerboxReboot /etc/rc4.d/S99boilerboxReboot
ln -s /etc/init.d/boilerboxReboot /etc/rc5.d/S99boilerboxReboot

#Création des liens pour gérer la suppression des flag lors des reboot system
ln -s /etc/init.d/boilerboxReboot /etc/rc0.d/K02boilerboxReboot
ln -s /etc/init.d/boilerboxReboot /etc/rc6.d/K02boilerboxReboot


