#!/bin/bash
set -e
set -x


BUILDENV="/srv/extension.vm";

cp -f composer.json ${BUILDENV}
cp -f _deploy/.basedir ${BUILDENV}/.modman

if [ -d "${BUILDENV}/.modman/extension" ] ; then
    rm -rf "${BUILDENV}/.modman/extension"
fi

cp -rf . "${BUILDENV}/.modman/extension"

cd ${BUILDENV}
php composer.phar install
modman deploy-all --force

magerun cache:clean
magerun sys:module:list |grep Mothership
export N98_MAGERUN_TEST_MAGENTO_ROOT=${BUILDENV}/htdocs