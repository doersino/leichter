# leichter

A bare-bones weight tracking app with a simple and responsive web interface. "Leichter" is German for *lighter*, which is about as imaginative a name as I was able to come up with in a minute.

![desktop](screenshot.png)

## Installation
1. [Make sure](http://stackoverflow.com/questions/1066521/php-with-sqlite3-support) to install on a webserver with PHP and SQLite3 support
2. Create a file `config.php` with the following content, replacing `""` with a password if you wish to password-protect your instance and setting a path to the database file, which will be created in the next step:

    ```php
    <?php

    // authentication (leave blank to disable)
    const PASSWORD = "";
    const SESSION_LENGTH = 30;  // number of days a login is valid

    // path to where you'd like the database to be located (make sure it's not publicly accessible)
    const DB_PATH = "....sqlite";
    ```

3. Access `index.php?reset` in a browser, type "yes" (after logging in if you've set a password in the previous step) and press enter to set up the database
4. Go to `index.php` and start tracking your weight! *Pro tip:* There's no logout button, but you can log out by appending "&logout" to any URL.

Note that the [license](https://github.com/doersino/leichter/blob/master/LICENSE) does not apply to files in `lib/`, those come with their own licenses.
