# Symfony based JACQ
A short description of the app structure is provided here, following the alphabetically sorted subfolders of the repository.

**.github**
setup GitHub Continues integrations, that is build&push of the image or obsolete dependency checks.
**Dockerfile**
this dockerfile is used for production image build

## htdocs
**assets**
JS and CSS(SASS) files are stored outside and compiled by Webpack. The app.js is central point of the build, so do not forget import new code there. When you work on UI use ```./npm.sh run watch``` - on files change the assets are recompiled nearly immediately. Do you need a new external library? - add it to package.json, run ```./npm.sh update && ./npm.sh run build```.

**bin**
An elegant way how to run shell-like scripts using the whole application code base.

**config**
Configuration, the only thing that is really framework-specific

**database**
Temporal storage used by Petr

**public**
This folder is publicly visible in production - logos, assets build etc.

**src**
PHP code of the application

**templates**
TWIG templates of individual pages. The file structure is optional, but a link to specific template is always hardcoded in the related route

The remaining is clear, let's look deeper in *src*.

## src folder
**Command**
For console commands, not used at this moment, but in future the regular cron proceeded tasks will live here.

**Controller**
Definition of routes, what should be done and what should be returned

**Entity**
Doctrine ORM entities, that is mapping of database tables to data.

**Enum**
PHP enums

**EventSubscriber**
Powerful but not relevant at this moment

**Facade**
some are preliminary proposed and used, but many opportunities for refactoring

**Repository**
Doctrine ORM repositories - that is "lookup and filter" operations. Not used as much one would like to see.

**Service**
Application logic, but a lot of the code should be migrated into Repositories

**Twig**
Extensions for templates, used for hacking the frontend - parts of legacy code that is used for displaying some values is stored here in to keep the templates clean.

### OAuth2 server
Requires DATABASE_URL to be directed to writable database (like the one from docker-compose) with structure from /htdocs/database/demo.sql. (or use Migrations&Console, but the sql dump is easier).

* Generate a private/public key pair at the location defined in .env file, using the value of the  OAUTH_PASSPHRASE as passphrase: https://oauth2.thephpleague.com/installation/#generating-public-and-private-keys
```shell
cd htdocs/config/jwt
openssl genrsa -aes128 -passout pass:jacq -out private.pem 2048
openssl rsa -in private.pem -passin pass:jacq -pubout -out public.pem
```
and facultative "encryption key" via ```php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'``` + update al this info in .env file.


