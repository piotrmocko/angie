#!/usr/bin/env bash

# This script will copy and symlink ANGIE git folders to ANGIE dev site
# Usage: ./link_angie_site.sh path/to/angie/dev/site

if [ -z "$1" ]
	then
		echo "Missing platform name to link"
		exit
fi

if [ ! -d "$1" ]
	then
		echo "Target site does not exist"
		exit
fi

__DIR__=`cd ${BASH_SOURCE%/*}; pwd`
SOURCE_DIR=${__DIR__}/angie/installation

DIRS=('angie' 'framework' 'platform' 'template' 'tmp')
FILES=('defines.php' 'index.php' 'version.php' )

# I can simply symlink the folders
for DIR in "${DIRS[@]}"
do
	TARGET_DIR=${1}/installation/${DIR}

	if [ -e ${TARGET_DIR} ]; then
		echo "Removing directory \"${TARGET_DIR}\""

		# Is the path a file? It means that's a symlink and I have to remove the file, not then contents
		if [ -f ${TARGET_DIR} ]; then
			rm ${TARGET_DIR}
		elif [ -d ${TARGET_DIR} ]; then
			rm -r ${TARGET_DIR}
		else
			echo "Can not detect the type of the path \"${TARGET_DIR}\". Stopping"
			exit
		fi
	fi

	ln -s ${SOURCE_DIR}/${DIR} ${TARGET_DIR}
	echo "Symlink created"
done

# With files the problem is different, since there are a lot of __DIR__ inside it and they play bad with links
# However, since they rarely change, I can simply copy them in the installation folder
for FILE in "${FILES[@]}"
do
	echo "Copying \"${FILE}\"..."
	cp ${SOURCE_DIR}/${FILE} ${1}/installation/${FILE}
done