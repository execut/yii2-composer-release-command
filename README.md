# Yii2 command for fast composer packages release
## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
$ php composer.phar require execut/yii2-composer-release-command "dev-master"
```

or add

```
"execut/yii2-composer-release-command": "dev-master"
```

to the ```require``` section of your `composer.json` file.

## Configuration example
Add to console config folowing rules:
```php
[
    'controllerMap' => [
        'release' => [
            'class' => \execut\release\ReleaseController::class,
            'vendorFolder' => 'execut', // Folder(s) inside @vendor for releasing, supported list 
        ],
    ],
];
```

## Usage
All released packages must be installed with .git via --prefer-source composer flag (see [composer documentation](https://getcomposer.org/doc/03-cli.md)).
Fast way for adding git inside installed packages:
1. Delete them
1. Run ```composer install --prefer-source``` for fresh install of package with git server

After running console command ```./yii release``` the happen next operations:
1. Each folder with .git, specified inside configuration file checked for new changes
1. If has changes happen the next operations:
   1. git add .
   1. git pull origin master
   1. git checkout master
   1. git pull
   1. git commit with message passed via console argument --message(m) or entered inside console dialog
   1. git push
   1. Calculating and tagging new version by next rule: (major version).(minor version).(path version). Console argument --level(l)
   set level of calculation next version. 0 - major, 1 - minor, 2 - path (default)
   1. git push --tags 

Console arguments:

Name | Short name | Description | Default value
-------------------- | ----------- | -------------- | ------
--message | -m | Commit message |  
--level | -l | level of calculated next version. 0 - major, 1 - minor, 2 - path | 2

## License

**yii2-composer-release-command** is released under the Apache License Version 2.0. See the bundled `LICENSE.md` for details.
