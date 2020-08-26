#!/bin/bash
#cd "$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd /home/speleoalex/public_html/flatnux/flatnux


######################### rimozione sitemap ####################################
rm ./*sitemap*.xml
rm ./index-*.html
rm ./trovaprezzi.txt
######################### rimozione altri files  ###############################
rm ./.directory
rm ./.htaccess
rm ./fncommerce
rm ./controlcenter/sections/fnEcommerce
rm ./sections/home/section.*
rm ./sections/gallery/section.*
rm ./sections/forum/section.*
rm ./sections/login/section.*
rm ./sections/news/section.*
rm ./sections/news_archive/section.*
rm ./sections/search/section.*
rm ./sections/sitemap/section.*
rm -rf ./sections/infocookie

rm ./sections/fncommerce
rm ./modules/fncommerce

find . -iname \*~ | xargs rm -f
find . -iname \.directory | xargs rm -f
find . -iname \*.menu | xargs rm -f
find . -iname \*.menu.xml | xargs rm -f
rm ./sections/90_doc
rm ./sections/78_CMR
######################### creazione firstinstall   #############################
rm -rf ./misc/*
touch ./misc/firstinstall
chmod 777 ./misc/firstinstall
rm include/config.vars.local.php

###########################    fix dei permessi   ##############################
for i in $(find . -type f); do
	if echo "$i" | egrep -qe "\.(php|sh)$"; then
		chmod a+x $i
	else
		chmod a-x $i
	fi;
done




######################### scrittura versione ###################################
pwd=$(basename $PWD) 

oldpwd=$PWD
echo $pwd-$(date +"%Y-%m.%d") > VERSION
echo $pwd-$(date +"%Y-%m.%d") > ../FLATNUXDEVEL
echo $pwd-$(date +"%Y-%m.%d") > ../FLATNUXSTABLE

###########################         minimal       ##############################
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


########################    creazione zip   ####################################
cd ..
if [ -d "$pwd" ]; then
	name=$pwd-$(date +"%Y-%m.%d").tar.gz
	nameminimal=$pwd-minimal-$(date +"%Y-%m.%d").tar.gz
	namezip=$pwd-$(date +"%Y-%m.%d").zip
	namezipminimal=$pwd-minimal-$(date +"%Y-%m.%d").zip
	mkdir ./fnexlude
	declare n=0
	while [ -e $namezip ]; do
		let n++
		name=$pwd-$(date +"%Y-%m.%d").$n.tar.gz
		namezip=$pwd-$(date +"%Y-%m.%d").$n.zip
		nameminimal=$pwd-minimal-$(date +"%Y-%m.%d").$n.tar.gz
		namezipminimal=$pwd-minimal-$(date +"%Y-%m.%d").$n.zip
        echo $pwd-$(date +"%Y-%m.%d").$n > FLATNUXDEVEL
        echo $pwd-$(date +"%Y-%m.%d").$n > FLATNUXSTABLE
        echo $pwd-$(date +"%Y-%m.%d").$n > flatnux/VERSION
	done
    
        #sposto esclusi
	mv ./flatnux/.svn ./fnexlude/
	mv ./flatnux/.git ./fnexlude/

        #creo zip devel
	zip -r $namezip"-devel.zip" $pwd
	mkdir ./fnbuild

        #sposto devel
	mv ./flatnux/nbproject ./fnbuild/
	mv ./flatnux/.project ./fnbuild/
	mv ./flatnux/.settings ./fnbuild/
	mv ./flatnux/docs ./fnbuild/
        #moduli extra
	mv ./flatnux/modules/ApplicationRest ./fnbuild/
	mv ./flatnux/modules/DocX ./fnbuild/
	mv ./flatnux/modules/youtubechannel ./fnbuild/



	echo "PULIZIA COMPLETATA";
        #creo zip release
	zip -r $namezip $pwd -x "flatnux/.*"  flatnux/nbproject flatnux/.director\* flatnux/.project\* flatnux/.buildpath\* flatnux/\*.sh flatnux/.settings flatnux/.svn/\*
	zip -r $namezipminimal flatnux_minimal 

        echo '<?php' > flatnux/include/config.vars.local.php
        echo '$_FN_display_errors = "on";' >> flatnux/include/config.vars.local.php
        echo '?>' >> flatnux/include/config.vars.local.php

	echo "----UPDATE SVN----";
	cd /home/speleoalex/Documents/SVN-flatnux/trunk/
	#svn commit 
## per creare versione stabile ##
#	#svn copy ./trunk https://flatnux.svn.sourceforge.net/svnroot/flatnux/tags/flatnux_1.0 -m flatnux_1.0
## per creare versione testing ##
#	#svn copy ./trunk https://flatnux.svn.sourceforge.net/svnroot/flatnux/testing/$pwd-$(date +"%Y-%m.%d").$n -m $(date +"%Y-%m.%d")

	cd /home/speleoalex/public_html/flatnux/


        #ripristino esclusi
	mv ./fnexlude/.svn ./flatnux/
	mv ./fnexlude/.git ./flatnux/
        #ripristino devel
	mv ./fnbuild/nbproject ./flatnux/
	mv ./fnbuild/.project ./flatnux/
	mv ./fnbuild/.settings ./flatnux/
	mv ./fnbuild/docs ./flatnux/
        #moduli extra
	mv ./fnbuild/ApplicationRest ./flatnux/modules/ 
	mv ./fnbuild/DocX ./flatnux/modules/ 
	mv ./fnbuild/youtubechannel ./flatnux/modules/ 


fi
#########################ripristino i permessi##################################
chown -R speleoalex ./flatnux/*
chown -R speleoalex ./flatnux_minimal
chmod 777 -R  ./flatnux_minimal/

###############################     fine    ####################################
echo creato: $namezip
chmod 777 -R ../.svn/



#git add *
#git commit
#git push