<?php

/*
--------------------------------------------------------------------------------------------
| Scarlets Framework Root Folder
--------------------------------------------------------------------------------------------
|
| Here you can run any script before you load Scarlets Framework
| for your project. This 'root.php' must be placed on project
| root directory.
|
*/
include_once __DIR__."/../require.php";

/*
--------------------------------------------------------------------------------------------
| Directory Structure
--------------------------------------------------------------------------------------------
|
| - /root.php      : This file
| - /scarlets      : Console
| - /config/       : Configuration files that will automatically loaded on initialization
| - /public/       : Website root directory, set it on apache virtualhost or nginx server block
| - /resource/     : Required framework resources
|	- /lang/       : Languages
|	- /plate/      : Template files
|	- /static/     : Static HTML
|	- /view/       : Dynamic PHP
| - /routes/       : Route Handler
|	- console.php  : Console Router
|	- web.php      : Web URL Router
| - /storage/      : Application and framework storage like cache or log
|	- /app/        : App storage
|	- /framework/  : Framework storage
|	- /log/        : Logging files
*/