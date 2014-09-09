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


Test it
=======
To test, if it works, just run ```magerun``` from your Magento-Project folder. You should see:

```
mothership:env:import
mothership:env:dump
```

Commands
========

mothership:env:import
---------------------
Import a specific configuration.


mothership:env:dump
-------------------
Dump a specific configuration.