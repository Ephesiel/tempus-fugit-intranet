Creation of a new plugin :

1- Creation :
- Create a folder with the name of your plugin (no space, no special chars are not mandatory but recommanded to avoid strange behaviors)
- Inside this folder create a php file with the same name that you're folder.

Example :
> plugins
  > tfi
    > my_awesome_plugin
      > my_awesome_plugin.php   <- Here is my main file

This file, called "main file" is the file which will be called when the plugin will be activated.

In this main file, you can add a first to describe your plugin (like a wordpress plugin) for example :
/**
 * Plugin Name: My awesome plugin
 * Other Option: Option value
 * ...
 */

Available options are :
- Description -> A short description of your plugin uses
- Plugin Name -> The display name of the plugin
- Version     -> The current version of the plugin 

2- Hooks
There is multiple hooks handle by the tfi plugin which can be used by sub plugins to modify datas etc...
But there is hooks create specialy for those sub plugins :
    - tfi_plugins_activate_{plugin_name}    -> (action) This hook allows to do something when your plugin is activate
    - tfi_plugins_deactivate_{plugin_name}  -> (action) This hook allows to do something when your plugin is deactivate

Each time, you should replace {plugin_name} by the name of your plugin (alias: your plugin folder name)

3- Uninstall
If you have something special to do when your plugin is uninstall, add a file uninstall.php inside your plugin folder.
This file will be called when your plugin should be uninstall.

4- Obsolete
When a plugin is no more required, you should let the main file empty with the uninstall file and all its needs.
It allows retro compatibilty, you're plugin won't do anything (the main file is empty) but when uninstall, everything will be removed as expected.
The main file is required to know that your plugin is a plugin.

Be carefull because the plugin will still be inside the admin option panel. You can add a description which tell why the plugin is obsolete or just "old plugin"

5- Tips
On deactivate, only hide things, do not delete cache or things loke that.
On uninstall, you should remove everything, except if you want to keep datas which should be a burden to restore if the uninstall was an error.
But you should always let the user have the possibility to delete everything

Example :

// uninstall.php
if ( define( 'MY_AWESOME_EXT_CLEAR' ) && MY_AWESOME_EXT_CLEAR ) {
    // ... uninstall everything
}

The user can then define this constant on his config.php file and uninstall it for good