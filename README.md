# Symfony based JACQ

## First run
Docker installed as a prerequisity for described steps, for traditional installation please consult Symfony docs.

Create file /htdocs/.env.local with content to provide a replica connection
```DATABASE_RO_URL="mysql://user:password@replica:3306/herbarinput?serverVersion=10.11.9-MariaDB-ubu2204&charset=utf8mb4"```

Install and build assets:
```shell
cd htdocs && ./npm.sh && ./npm.sh run build
```

Install dependencies:
* ```docker exec -it app-sjacq bash```
    * install dependencies ```composer install``` recommended to do this from inside of container where Symfony bin is installed.


### OAuth2 server
Requires DATABASE_URL to be directed to writable database (like the one from docker-compose) with structure from /htdocs/database/demo.sql. (or use Migrations&Console, but the sql dump is easier).

* Generate a private/public key pair at the location defined in .env file, using the value of the  OAUTH_PASSPHRASE as passphrase: https://oauth2.thephpleague.com/installation/#generating-public-and-private-keys
```shell
cd htdocs/config/jwt
openssl genrsa -aes128 -passout pass:jacq -out private.pem 2048
openssl rsa -in private.pem -passin pass:jacq -pubout -out public.pem
```
and facultative "encryption key" via ```php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'``` + update al this info in .env file.


