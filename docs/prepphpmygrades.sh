#!/usr/bin/bash
# prepares and packages phpmygrades for release
# Obviously prepared for my computer's setup; modify to suit yours.
VERSION=`cat ~/phpmygrades/docs/VERSION`
cd ~
cp -R phpmygrades/ phpmygrades_dist/
cd phpmygrades_dist
for i in $(find . | grep CVS);do rm -rf $i;done
cd docs
rm VERSION prepphpmygrades.sh
cd ../include
rm config.php
cd ..
rm sqltest.php
cd ~
tar cfz phpmygrades-$VERSION.tar.gz phpmygrades_dist
tar cfj phpmygrades-$VERSION.tar.bz2 phpmygrades_dist
zip -r phpmygrades-$VERSION.zip phpmygrades_dist > /dev/null
rm -rf phpmygrades_dist
