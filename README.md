### Install
```
composer global require vsfdqfpdpg/composer-link
```

### Usage
```
composer-link link
```
> You need administrator privilege to run this command and this will treat current directory as a package and create a symbolic link to your composer bin folder.

```
composer-link "package name" version
```
> This command will install required link package and update composer.json file.