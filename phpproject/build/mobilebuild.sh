#!/bin/sh
php html.php cordova

echo "executing cordova build..."
( cd ../elixi_cordova ; cordova build )
echo "...done executing cordova build!"

echo "moving 3rd and archetype directories to android project..."
mkdir ../elixi_cordova/platforms/android/assets/www/3rdParty/
mkdir ../elixi_cordova/platforms/android/assets/www/archetype/
cp -rf ../../3rdParty/ ../elixi_cordova/platforms/android/assets/www/3rdParty/
cp -rf ../../archetype/archetype-MVC.js ../elixi_cordova/platforms/android/assets/www/archetype/
cp -rf ../../archetype/shiny/ ../elixi_cordova/platforms/android/assets/www/archetype/shiny/
cp -rf ../../archetype/plugins/ ../elixi_cordova/platforms/android/assets/www/archetype/plugins
rm -rf ../elixi_cordova/platforms/android/assets/www/3rdParty/.git/
rm -rf ../elixi_cordova/platforms/android/assets/www/archetype/.git/

echo "moving 3rd and archetype directories to ios project..."
mkdir ../elixi_cordova/platforms/ios/www/3rdParty/
mkdir ../elixi_cordova/platforms/ios/www/archetype/
cp -rf ../../3rdParty/ ../elixi_cordova/platforms/ios/www/3rdParty/
cp -rf ../../archetype/archetype-MVC.js ../elixi_cordova/platforms/ios/www/archetype/
cp -rf ../../archetype/shiny/ ../elixi_cordova/platforms/ios/www/archetype/shiny/
cp -rf ../../archetype/plugins ../elixi_cordova/platforms/ios/www/archetype/plugins
rm -rf ../elixi_cordova/platforms/ios/www/3rdParty/.git/
rm -rf ../elixi_cordova/platforms/ios/www/archetype/.git/

echo "executing cordova build..."
( cd ../elixi_cordova ; cordova compile )
( cd ../elixi_cordova ; cordova compile ios --device )
echo "...done executing cordova build!"
