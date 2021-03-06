# codeable-pluginception
Version of Pluginception modified to spin up quick, functionally programmed plugins for use with Codeable clients.

## Features
* Quickly spin up functional plugins for Codeable clients (or others)
* Pre-fill most commonly used information (Author name, e-mail, copyright, copyright year, sites, etc)
* Easily add a template CSS and / or JS file
* Includes standard best practices of avoiding direct file access and blank index.php files
* Checks if functions exist before declaring them
* Allows for easy prefixing of pre-generated functions

## Directions
* Download codeable-pluginception.php
* Modify the PHP constants on lines 36-42 as desired
* Upload modified file to (create if needed) yourdevsite/wp-content/mu-plugins/
* Under the Admin > Plugins submenu, pick "Create Codeable Plugin"
* Follow on-screen prompts
## Sample Plugin
To see a sample of a plugin generated with this plugin, see the [example here](https://github.com/duplaja/sample-codeable-gen-plugin)

## Thanks
Thank you to [Francesco Carlucci](https://github.com/FrancescoCarlucci/) for the idea that prompted this plugin, and to [Otto](http://ottopress.com/) for the base Pluginception plugin
