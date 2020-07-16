#!/bin/bash
set -e
#set -x

destDir=build

bin/composer.phar install --no-dev

if [ ! -d $destDir ]
then
  mkdir -p $destDir
else
  rm -fr $destDir
fi

items="src vendor wp2matrix.php LICENSE readme.txt"
for item in $items
do
	if [ -d $item ] || [ -f $item ]
	then
		rsync -ra $item $destDir
	fi
done

zipFile=wp2matrix.zip
if [ -f $zipFile ]
then
  rm $zipFile
fi

cd $destDir
zip -r ../$zipFile *