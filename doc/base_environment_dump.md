# About

# Options

Dump all configuration-settings from the core_config Table as PHP array. You need to have one file named config.php. Just copy the file

```
cd PATH/src/Mothership/Environment/resource
cp config.example.php config.php
```

Now edit the config.php and config your settings. You might exclude one or several configuration settings with a regex argument. This might be useful if you want to
build a new template for your import.

Example:

```
// config.php

return array(

    'dump' => array(
        /**
         * Excluded paths from the core_config_data table.
         * You can use regex to exclude the paths as the script will use preg_match
         */
        'excluded_paths' => array(
            '/^carriers.*/',
            '/^google.*/',
            '/^sales.*/',
            '/^sales_pdf.*/',
            '/^sales_email.*/',
            '/^catalog.*/',
            '/^newsletter.*/',
            '/^payment.*/',
        )
    ),

    'import' => array(

    )
);
```