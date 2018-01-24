#!/bin/bash

set -ex

if [[ ${TRAVIS_PHP_VERSION:0:2} == "7." ]]; then
    composer require "phpunit/phpunit=6.5.*" --dev
elif [[ ${TRAVIS_PHP_VERSION:0:3} == "5.6" ]]; then
    composer require "phpunit/phpunit=5.7.*" --dev
else
    composer require "phpunit/phpunit=4.8.*" --dev
fi

composer install --no-interaction --prefer-source
