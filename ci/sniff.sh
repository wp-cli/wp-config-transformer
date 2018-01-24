#!/bin/bash

set -ex

vendor/bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs

vendor/bin/phpcs -v
