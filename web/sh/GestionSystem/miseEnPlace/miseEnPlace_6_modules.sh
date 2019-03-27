#!/bin/sh
relance_apache=1

echo "Liste des modules installés"
echo `apache2ctl -M`
echo ""
echo ""

module_headers=`apache2ctl -M | grep "headers"`
if [ -z "$module_headers" ]
then
    a2enmod headers
    relance_apache=0
    echo "Activation du module apache Headers"
fi

module_deflate=`apache2ctl -M | grep "deflate"`
if [ -z "$module_deflate" ]
then
    a2enmod deflate
    relance_apache=0
    echo "Activation du module apache Deflate"
fi

module_expires=`apache2ctl -M | grep "expires"`
if [ -z "$module_expires" ]
then
    a2enmod expires
    relance_apache=0
    echo "Activation du module apache Expires"
fi

module_rewrite=`apache2ctl -M | grep "rewrite"`
if [ -z "$module_rewrite" ]
then
    a2enmod rewrite
    relance_apache=0
    echo "Activation du module apache Rewrite"
fi


if [ "$relance_apache" -eq 0 ]
then
   /etc/init.d/apache2 restart
    echo "Relance d'apache effectue"
fi
