#!/bin/bash
#cd "$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd /home/speleoalex/public_html/flatnux/flatnux

rm -rf /home/speleoalex/public_html/flatnux/flatnux_minimal/*
while read F  ; do
#        ls  $F
if [ -d $F ]
then
#    echo "$F Directory"
    mkdir /home/speleoalex/public_html/flatnux/flatnux_minimal/$F
else
 #   echo "$F File"
    cp -p ./$F /home/speleoalex/public_html/flatnux/flatnux_minimal/$F
fi
done <minimal.txt
    
chmod 777 -R /home/speleoalex/public_html/flatnux/flatnux_minimal/

