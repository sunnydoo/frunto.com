#!/bin/bash
PROTOCOL="ftp"
URL="192.168.253.11"
REMOTEDIR="/"
USER="tabtnbackup"
PASS="tabtnbackup"
REGEX="*.tsbak"
LOG="/home/user/script.log"
 
LOCALDIR = `tsm configuration get -k basefilepath.backuprestore`
echo $LOCALDIR
 
cd $LOCALDIR
 
if [  ! $? -eq 0 ]; then
    echo "$(date "+%d/%m/%Y-%T") Cant cd to $LOCALDIR. Please make sure this local directory is valid" >> $LOG
fi

echo `$PWD`
 
tsm maintenance backup -f tnong_backup -d
 
 
lftp  $PROTOCOL://$URL <<- UPLOAD_BACKUP
    user $USER "$PASS"
    cd $REMOTEDIR
    mput -E $REGEX
UPLOAD_BACKUP
 
if [ ! $? -eq 0 ]; then
    echo "$(date "+%d/%m/%Y-%T") Cant download files. Make sure the credentials and server information are correct" >> $LOG
fi