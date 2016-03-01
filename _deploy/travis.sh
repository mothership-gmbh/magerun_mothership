#!/bin/bash
set -e
set -x

# most of the script is based on the fantastic https://github.com/AOEpeople/MageTestStand project

# create a temporary directory
BUILDENV=`mktemp -d /tmp/mothership.XXXXXXXX`
CWD=$(pwd)

echo "BUILD = ${BUILDENV}"
echo "CWD   = ${CWD}"


mkdir -p ${BUILDENV}/.modman/mothership_magerun

cp -rf . "${BUILDENV}/.modman/mothership_magerun"

# Start building everything
cp -f ${CWD}/composer.json    ${BUILDENV}
cp -f ${CWD}/_deploy/.basedir ${BUILDENV}/.modman

# Get absolute path to main directory
ABSPATH=$(cd "${0%/*}" 2>/dev/null; echo "${PWD}/${0##*/}")
SOURCE_DIR=`dirname "${ABSPATH}"`

if [ -z $MAGENTO_DB_HOST ]; then MAGENTO_DB_HOST="localhost"; fi
if [ -z $MAGENTO_DB_PORT ]; then MAGENTO_DB_PORT="3306"; fi
if [ -z $MAGENTO_DB_USER ]; then MAGENTO_DB_USER="root"; fi
if [ -z $MAGENTO_DB_PASS ]; then MAGENTO_DB_PASS=""; fi
if [ -z $MAGENTO_DB_NAME ]; then MAGENTO_DB_NAME="mageteststand"; fi
if [ -z $MAGENTO_DB_ALLOWSAME ]; then MAGENTO_DB_ALLOWSAME="0"; fi

echo
echo "---------------------"
echo "- Mothership local -"
echo "---------------------"
echo
echo "Installing ${MAGENTO_VERSION} in ${SOURCE_DIR}/htdocs"
echo "using Database Credentials:"
echo "    Host: ${MAGENTO_DB_HOST}"
echo "    Port: ${MAGENTO_DB_PORT}"
echo "    User: ${MAGENTO_DB_USER}"
echo "    Pass: [hidden]"
echo "    Main DB: ${MAGENTO_DB_NAME}"
echo "    Test DB: ${MAGENTO_DB_NAME}_test"
echo "    Allow same db: ${MAGENTO_DB_ALLOWSAME}"
echo




cd ${BUILDENV}

# Download composer
composer self-update
bash < <(curl -s -L https://raw.github.com/colinmollenhour/modman/master/modman-installer)

cd ${BUILDENV}
wget http://files.magerun.net/n98-magerun-latest.phar
chmod +x ./n98-magerun-latest.phar


if [ ! -f htdocs/app/etc/local.xml ] ; then

    # Create main database
    MYSQLPASS=""
    if [ ! -z $MAGENTO_DB_PASS ]; then MYSQLPASS="-p${MAGENTO_DB_PASS}"; fi
    mysql -u${MAGENTO_DB_USER} ${MYSQLPASS} -h${MAGENTO_DB_HOST} -P${MAGENTO_DB_PORT} -e "DROP DATABASE IF EXISTS \`${MAGENTO_DB_NAME}\`; CREATE DATABASE \`${MAGENTO_DB_NAME}\`;"

    ./n98-magerun-latest.phar install \
      --dbHost="${MAGENTO_DB_HOST}" --dbUser="${MAGENTO_DB_USER}" --dbPass="${MAGENTO_DB_PASS}" --dbName="${MAGENTO_DB_NAME}" --dbPort="${MAGENTO_DB_PORT}" \
      --installSampleData=yes \
      --useDefaultConfigParams=yes \
      --magentoVersionByName="${MAGENTO_VERSION}" \
      --installationFolder="${BUILDENV}/htdocs" \
      --baseUrl="http://magento.local/" || { echo "Installing Magento failed"; exit 1; }
fi

# run composer update and install all requirements
composer self-update
composer install

# run modman and first debug
ls -lisah ${BUILDENV}
modman deploy-all --force --copy

./n98-magerun-latest.phar --root-dir=htdocs config:set dev/template/allow_symlink 1
./n98-magerun-latest.phar --root-dir=htdocs sys:setup:run
./n98-magerun-latest.phar --root-dir=htdocs cache:flush

./n98-magerun-latest.phar cache:clean
./n98-magerun-latest.phar sys:module:list
export N98_MAGERUN_TEST_MAGENTO_ROOT=${BUILDENV}/htdocs


cd ${BUILDENV}/htdocs
ls -lisah ${BUILDENV}/htdocs

ls -lisah ${BUILDENV}/vendor/
cat ${BUILDENV}/vendor/autoload.php
cat ${BUILDENV}/vendor/composer/autoload_psr4.php

phpunit --debug --verbose