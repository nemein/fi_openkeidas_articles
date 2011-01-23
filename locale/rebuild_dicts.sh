#!/bin/sh

COMPONENT="fi_openkeidas_articles"
CURRDIR=`dirname $0`

echo  "Component: " $COMPONENT
echo

for dir in `find . -type d -name "LC_MESSAGES" | grep -v ".svn"`; do
  echo "Rebuild $dir"
  rm -rf $dir/*~
  msgfmt --statistics -f -c -v -o $dir/$COMPONENT.mo $dir/$COMPONENT.po
  echo
done