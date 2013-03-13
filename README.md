serien-loader-client
====================

- download and install: https://getcomposer.org/Composer-Setup.exe
- init a new composer project in an empty directory (``composer init``)
- you might set your ``minimum-stability`` to dev in your composer.json
- require with composer "pscheit/serien-loader-client":@alpha"

in one command:
```
composer init --require="pscheit/serien-loader-client:@alpha" -n
```

After installation (may take a while). Run the index.php in www with your local webserver.
Edit the config in ``(Your Home Directory)\.serien-loader\inc.config.php``. Provide all paths with trailing backslash.