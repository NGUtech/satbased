#Environment variables

A default set of non-private environment configurations is provided here.

**DO NOT PLACE PRIVATE CONFIGURATION SECRETS HERE!**

##Docker
You can place custom configuration secrets in `var/secrets`.  Then modify your `docker-compose.yml` file to register your new secret file locations and provide access to containers if necessary. See https://docs.docker.com/compose/compose-file/#secrets for more information.
