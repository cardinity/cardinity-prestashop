## Validation 

### php-cs-fixer

```
cd tools/validator

vendor/bin/php-cs-fixer fix
```

### phpstan

need to have a working copy of prestashpo e commerce

first path is prestashop root directory
2nd path is global composer directory that has phpstan
3rd is path to phpstan.neon


```
_PS_ROOT_DIR_=/home/shababhsiddique/Work/Docker/lamp72/www/prestashop php /home/shababhsiddique/.config/composer/vendor/bin/phpstan.phar --configuration=phpstan.neon analyse /home/shababhsiddique/Work/Docker/lamp74/www/cardinity-repos/cardinity-prestashop
```