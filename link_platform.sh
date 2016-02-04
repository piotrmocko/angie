#!/usr/bin/env bash

# This script will copy the platform folder inside angie installation folder

REL_DIR=${BASH_SOURCE%/*}
DIR=`cd ${REL_DIR}; pwd`

if [ -z "$1" ]
	then
		echo "Missing platform name to link"
		exit
fi

SYMLINK=${DIR}/angie/installation/platform
PLATFORM=${DIR}/angie/platforms/$1

if [ ! -d ${PLATFORM} ]
	then
		echo "Platform \"$1\" not found"
		exit
fi

if [ -e ${SYMLINK} ]
	then
		echo "Removing existing symlink"
		rm ${SYMLINK}
fi

ln -s ${PLATFORM} ${SYMLINK}

echo "Symlink created"