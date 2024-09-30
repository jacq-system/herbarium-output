# Symfony based JACQ

## OAuth2
* Generate a private/public key pair at the location defined in .env file, using the value of the  OAUTH_PASSPHRASE as passphrase: https://oauth2.thephpleague.com/installation/#generating-public-and-private-keys
```shell
mkdir config/jwt && cd config/jwt
openssl genrsa -aes128 -passout pass:jacq -out private.pem 2048
openssl rsa -in private.pem -passin pass:jacq -pubout -out public.pem
```
and facultative "encryption key" via ```php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'```

update al this info in .env file

