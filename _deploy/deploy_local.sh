#!/bin/bash
set -e
set -x

function cleanup {
  if [ -z $SKIP_CLEANUP ]; then
    echo "Removing build directory ${BUILDENV}"
    #rm -rf "${BUILDENV}"
  fi
}

trap cleanup EXIT

BUILDENV="/srv/extension.vm";

MAGENTO_DB_HOST="127.0.0.1";
MAGENTO_DB_PORT="3306";
MAGENTO_DB_USER="super";
MAGENTO_DB_PASS="super123";
MAGENTO_DB_NAME="extension";
MAGENTO_DB_ALLOWSAME="0";

CWD=$(pwd)


# Use the local mothership patched one
MAGENTO_VERSION=magento-mirror-1.8.1.0

#
if [ -d "${BUILDENV}/htdocs" ] ; then
  rm -rf ${BUILDENV}/htdocs
fi

mkdir -p ${BUILDENV}/htdocs


echo "Using build directory ${BUILDENV}"



if [ -d "${BUILDENV}/.modman/extension" ] ; then
    rm -rf "${BUILDENV}/.modman/extension"
fi

mkdir -p ${BUILDENV}/.modman/extension

cp -rf . "${BUILDENV}/.modman/extension"

# Start building everything
cp -f ${CWD}/composer.json ${BUILDENV}
cp -f ${CWD}/_deploy/.basedir ${BUILDENV}/.modman

# Get absolute path to main directory
ABSPATH=$(cd "${0%/*}" 2>/dev/null; echo "${PWD}/${0##*/}")
SOURCE_DIR=`dirname "${ABSPATH}"`

echo ${SOURCE_DIR}

echo
echo "-------------------------------"
echo "- Mothership local deployment -"
echo "-------------------------------"
echo

cd /tmp

mysql -u${MAGENTO_DB_USER} -p${MAGENTO_DB_PASS} -h${MAGENTO_DB_HOST} -P${MAGENTO_DB_PORT} -e "DROP DATABASE IF EXISTS \`${MAGENTO_DB_NAME}\`; CREATE DATABASE \`${MAGENTO_DB_NAME}\`;"

# Install Magento with sample data
magerun install \
  --dbHost="${MAGENTO_DB_HOST}" --dbUser="${MAGENTO_DB_USER}" --dbPass="${MAGENTO_DB_PASS}" --dbName="${MAGENTO_DB_NAME}" --dbPort="${MAGENTO_DB_PORT}" \
  --installSampleData=yes \
  --useDefaultConfigParams=yes \
  --magentoVersionByName="${MAGENTO_VERSION}" \
  --installationFolder="${BUILDENV}/htdocs" \
  --baseUrl="http://extension.vm/" || { echo "Installing Magento failed"; exit 1; }


cd ${BUILDENV}

php -r "readfile('https://getcomposer.org/installer');" > composer-setup.php
php composer-setup.php

php composer.phar install
modman deploy-all --force

# After running composer, we will have all the files needed. Now
#cp ${CWD}/phpunit.xml.dist "${BUILDENV}/htdocs/phpunit.xml.dist"

magerun cache:clean
magerun sys:module:list |grep Mothership

export N98_MAGERUN_TEST_MAGENTO_ROOT=${BUILDENV}/htdocs

cd ${BUILDENV}/htdocs
phpunit --group Mothership --debug --verbose

