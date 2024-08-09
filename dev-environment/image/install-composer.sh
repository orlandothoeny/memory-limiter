#!/bin/sh
set -eu

installComposer() {
  expectedChecksum="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  actualChecksum="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

  if [ "${expectedChecksum}" != "${actualChecksum}" ]
  then
      echo "ERROR: Invalid Composer installer checksum, expected ${expectedChecksum} but got ${actualChecksum}"
      rm composer-setup.php
      exit 1
  fi

  php composer-setup.php --filename composer --install-dir /usr/local/bin --2
  result=$?

  rm composer-setup.php

  exit ${result}
}

installComposer