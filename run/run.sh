#!/bin/bash

#SETUP SCRIPT
DATE=$(date +"%d-%m-%Y_%H%M")
LOG=~/apps/modanellsons/import/temp/download.log
REMOTE="moda:APPS/Moda/1-COLECCOES"
LOCAL=~/apps/modanellsons/public/import
READY=~/apps/modanellsons/import/temp/ready.txt
IMPORT=~/apps/modanellsons/import/run/import.php

#Delete all files from import folder
#rm -rf ~/apps/modanellsons/public/import/*

mkdir -p ~/apps/modanellsons/public/import/run/temp

#DOWNLOAD FILES FROM DROPBOX
rclone copy $REMOTE $LOCAL

#SEE WHICH DIRECTORIES ARE READY TO BE IMPORTED. THIS FILE WILL BE PARSED BY IMPORT SCRIPT
find $LOCAL -name "ready.txt" | grep -o '.*/' | sort | uniq > $READY

#ALLOW PUBLIC ACCESS TO IMAGES
chmod -R 755 $LOCAL

#RUN IMPORT SCRIPT TO GENERATE A CSV THAT WILL BE IMPORTED BY WPALLIMPORT
php $IMPORT
