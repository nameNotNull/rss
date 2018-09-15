#!/bin/bash
rm ./buildlib.sh
wget ftp://172.30.11.218/home/work/buildlib/buildlib.sh

MODULE='vr-api'
CASENAME='vrapicase'
ARGSDEV="$2"

source $HOME/.bash_profile 1>/dev/null 2>/dev/null
source ~/.bash_profile
source ./buildlib.sh

$1
