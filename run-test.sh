#!/bin/bash

if [ $# -ne 2 ]; then
    echo "Please register with Unbabel and get an sandbox key to run these tests"
    echo "USAGE ./$0 <UNBABEL_USERNAME> <UNBABEL_SANDBOX_APIKEY>"
    exit -1
fi

bootname='bootstrap.php'

username=$1
apikey=$2

echo -e "
<?php
\$GLOBALS['username'] = '$username';   
\$GLOBALS['apikey'] = '$apikey';
?>
" > $bootname

echo ""
echo "WARNING: These test will take a while because of network traffic"
echo ""

./vendor/bin/phpunit --bootstrap $bootname

rm $bootname




