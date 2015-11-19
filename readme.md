Mothership Magerun Addons
=========================
This repository contains Mothership specific addons and is a collection of useful components. 


Installation
============

There are currently three different ways to include the magerun-components. I will describe two methods. Please check the offical [documentation](http://magerun.net/introducting-the-new-n98-magerun-module-system/).

Method 1 - the easiest one
--------------------------
This method needs you to have a home folder where all the custom modules will be located.

* Execute the following snippet

```
mkdir -p ~/.n98-magerun/modules/
```

* Clone the repository or symlink it. I prefer the symlink way as all my repositories are checked out in one place but this is up to you. The following example will clone the directory directly.

```
cd ~/.n98-magerun/modules/
git clone https://github.com/mothership-gmbh/magerun_mothership.git
```

Method 2 - still easy, but more environment specific
----------------------------------------------------
While there is one way to centralize all your modules i prefer to have environment specific modules. 

* To achieve this, you just need to create a folder within your Magento project folder.

```
// replace MAGENTO_ROOT with your directory
mkdir -p MAGENTO_ROOT/lib/n98-magerun/modules
```

The next step is the same like before. Just clone and/or symlink the repository.

```
cd MAGENTO_ROOT/lib/n98-magerun/modules
git clone https://github.com/mothership-gmbh/magerun_mothership.git
```

Method 3 - composer.json
---------------------------------------
just add this require to your *composer.json*

```
"require": {
        "mothership/mothership_magerun":"dev-master"
        }
```


Test it
=======
To test, if it works, just run ```magerun``` from your Magento-Project folder. You should see:

```
mothership:env:import
mothership:env:dump
```

Commands
========

mothership:env:dump
-------------------
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

mothership:env:import
---------------------
Import the configuration settings by overwriting the existing configurations. There is one example file ```settings.example.php```.
Just copy the file as ```settings.php``` and customize the settings for your needs.

 * File Structure

 You need to have some files in the directory ```Mothership\Environment\resource```. They should be named like ```environment_anyname```.
 If there is more then one file, name it like your environments. Please define one file as a fallback.
 
 
mothership:images:create-dummy
------------------------------

This command will create an image file for each entry in the table ```catalog_product_entity_media_gallery```. Just ensure, that you have one file called ```dummy.jpg``` in your ```media``` directory. This can be useful if you have to deal with large product data but do not want to download a gigazillion large directory. 

For more fun, use the official Mothership Image.

![Logo](https://fbcdn-profile-a.akamaihd.net/hprofile-ak-xap1/v/t1.0-1/p160x160/1461677_413147242145236_1945192833_n.png?oh=ef95d2bc628a458430a24a3c06dd66f0&oe=56890054&__gda__=1456143606_da6782209cad961eb54f9f020c624785)

mothership:images:resize
------------------------

Handy command, to minify the base-images. This will create a new directory named after the ```--dir``` parameter and create a smaller version of all existing images. Please be aware that in case you have a new image file with the same name, this command will not recognize that. In this case remove the existing file from the resized image directory and rerun the command

```magerun typehype:images:resize --dir=thumbnails --size=100```

mothership:reports:observerstimes
------------------------
This is a *magerun* command to create a *csv* reports to find all the events and relative observers called for each 
Magento page called in the browser during navigation.

```
magerun mothership:reports:observerstimes
```

[More doc](https://github.com/mothership-gmbh/magerun_mothership/tree/master/src/lib/n98-magerun/modules/mothership_addons/src/Mothership_Addons/Reports)
