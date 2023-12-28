#!/bin/bash

set -e
source /lib.sh

as_www_user php artisan horizon:publish
