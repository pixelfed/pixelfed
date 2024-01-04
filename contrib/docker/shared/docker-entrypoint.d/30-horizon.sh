#!/bin/bash
set -o errexit -o nounset -o pipefail

source /lib.sh

as_www_user php artisan horizon:publish
