#!/bin/bash

APPKEY=""
EXCLUDES=""

# Create the storage tree if needed
cp -a storage.skel/* storage/

# run nginx workers with the 'application' user
sed -i 's/^user.*;$/user application;/' /etc/nginx/nginx.conf

# Check for APP_KEY in .env_app_key
if ! grep -q -E "^APP_KEY=['\"]?base64:" /app/storage/.env_app_key 2>/dev/null; then
  APPKEY="$(php artisan key:generate --show)"
  echo "APP_KEY=\"${APPKEY}\"" > /app/storage/.env_app_key
else
  echo " * skipping generation of APP_KEY, existing key found in .env_app_key"
fi

# Check for APP_HASHID_SALT in .env_app_key
if ! grep -q -E "^APP_HASHID_SALT=['\"]?[A-Za-z0-9]+" /app/storage/.env_app_hashid_salt 2>/dev/null; then
  echo 'APP_HASHID_SALT="'$(tr -dc A-Za-z0-9 </dev/urandom | head -c 129)'"' > /app/storage/.env_app_hashid_salt
else
  echo " * skipping generation of APP_HASHID_SALT, existing salt found in .env_app_hashid_salt"
fi

# check for TRUST_PROXIES and configure nginx real_ip module
TRUST_PROXIES="${TRUST_PROXIES:=false}"
if [[ "$TRUST_PROXIES" == "false" ]]; then
  echo "set_real_ip_from 0.0.0.0/0;" >> /opt/docker/etc/nginx/vhost.common.d/10-realip.conf
else
  for ip in ${TRUST_PROXIES//,/ }; do
    echo "set_real_ip_from ${ip};" >> /opt/docker/etc/nginx/vhost.common.d/10-realip.conf
  done
fi

# sanitize env variables and transfer to temporary .env file
EXCLUDES_DEF="APPLICATION|CONTAINER|DOCKER|GPG_KEYS|HOME|HOSTNAME|LANG|LC_|LOG_|NSS|OLDPWD|PATH|PHP|PWD|SERVICE_|SHLVL|TERM|WEB|_"
EXCLUDES_K8S="KUBERNETES|OPENSHIFT|_SERVICE|tcp:|_TCP|udp:|_UDP"
env | grep -Ev "^(${EXCLUDES_DEF})|${EXCLUDES_K8S}" > /app/storage/.env_temp

# combine APP_KEY, APP_HASHID_SALT and ENVs int .env file
cat /app/storage/.env_app_key \
    /app/storage/.env_app_hashid_salt \
    /app/storage/.env_temp \
    | sort > /app/storage/.env

# Create actors if AP is enabled
ACTIVITY_PUB="${ACTIVITY_PUB:=false}"
if [[ ${ACTIVITY_PUB} == "true" ]]; then
  su application -c 'php artisan instance:actor'
fi

# OAuth handling
# Check ENV and files for existing keys
OAUTH_ENABLED="${OAUTH_ENABLED:=false}" OAUTH_KEYFILES="false"
PASSPORT_PRIVATE_KEY="${PASSPORT_PRIVATE_KEY:=false}" OAUTH_PRIV_FILE="false" OAUTH_PRIV_ENV="false" OAUTH_KEYFILES="false"
PASSPORT_PUBLIC_KEY="${PASSPORT_PUBLIC_KEY:=false}" OAUTH_PUB_FILE="false" OAUTH_PUB_ENV="false" OAUTH_KEYENV="false"

# evaluate oauth key files on disk
if [[ -r /app/storage/oauth-private.key ]]; then OAUTH_PRIV_FILE="true"; fi
if [[ -r /app/storage/oauth-public.key ]]; then OAUTH_PUB_FILE="true"; fi
if [[ ${OAUTH_PRIV_FILE} == "true" ]] && [[ ${OAUTH_PUB_FILE} == "true" ]]; then OAUTH_KEYFILES="true"; fi

# evaluate oauth key files in ENV
if [[ ${OAUTH_ENABLED} == "true" ]] && [[ ${PASSPORT_PRIVATE_KEY} =~ ^\"?-+BEGIN\ RSA\ PRIVATE\ KEY-+[-A-Za-z0-9+/=] ]]; then OAUTH_PRIV_ENV="true"; fi
if [[ ${OAUTH_ENABLED} == "true" ]] && [[ ${PASSPORT_PUBLIC_KEY} =~ ^\"?-+BEGIN\ PUBLIC\ KEY-+[-A-Za-z0-9+/=] ]]; then OAUTH_PUB_ENV="true"; fi
if [[ ${OAUTH_PRIV_ENV} == "true" ]] && [[ ${OAUTH_PUB_ENV} == "true" ]]; then OAUTH_KEYENV="true"; fi

#decide OAuth key handling action depending on outcome of the evaluation
#  - duplicate definition - throw error and exit
if [[ ${OAUTH_ENABLED} == "true" ]]; then

  if  [[ ${OAUTH_KEYFILES} == "true" ]] && [[ ${OAUTH_KEYENV} == "true" ]]; then
    echo " * OAuth public/private keypair files already exist AND are defined as ENV as well - only use either one! Exiting."
    exit 1

  # - files exist - do nothing
  elif [[ ${OAUTH_ENABLED} == "true" ]] && [[ ${OAUTH_KEYFILES} == "true" ]] && [[ ${OAUTH_KEYENV} == "false" ]]; then
    echo " * OAuth public/private keypair files already exist, skipping generation."; break

  # - ENVs exist - do nothing
  elif [[ ${OAUTH_ENABLED} == "true" ]] && [[ ${OAUTH_KEYFILES} == "false" ]] && [[ ${OAUTH_KEYENV} == "true" ]]; then
    echo " * OAuth public/private keypair found in ENVs, skipping generation."; break

  # - only one half of the keypair exists - throw error and exit
  elif [[ ${OAUTH_PRIV_FILE} == "true" ]] && [[ ${OAUTH_PUB_FILE} == "false" ]]; then
      echo " * OAuth enabled, but found only one of the OAuth key files - please investigate. Exiting"; exit 1
  elif [[ ${OAUTH_PRIV_FILE} == "false" ]] && [[ ${OAUTH_PUB_FILE} == "true" ]]; then
    echo " * OAuth enabled, but found only one of the OAuth key files - please investigate. Exiting"; exit 1
   
  elif [[ ${OAUTH_PRIV_ENV} == "true" ]] && [[ ${OAUTH_PUB_ENV} == "false" ]]; then
      echo " * OAuth enabled, but found only one of the OAuth ENVs - please investigate. Exiting"; exit 1
  elif [[ ${OAUTH_PRIV_ENV} == "false" ]] && [[ ${OAUTH_PUB_ENV} == "true" ]]; then
    echo " * OAuth enabled, but found only one of the OAuth ENVs - please investigate. Exiting"; exit 1
  fi
fi

# - OAuth set, but neither files nor ENVs found - generate new keypair
if [[ ${OAUTH_ENABLED} == "true" ]] && [[ ${OAUTH_KEYFILES} == "false" ]] && [[ ${OAUTH_KEYENV} == "false" ]]; then
  echo " * OAuth enabled, but no public/private keypair found - generating new set."
  su application -c 'php artisan passport:keys' || (echo " * Error generating OAuth keys, please investigate. Exiting."; exit 1)
fi

su application -c 'php artisan storage:link'
su application -c 'php artisan horizon:publish'
su application -c 'php artisan route:cache'
su application -c 'php artisan view:cache'
su application -c 'php artisan config:cache'
