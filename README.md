<a href="https://www.patreon.com/stefansarya"><img src="http://anisics.stream/assets/img/support-badge.png" height="20"></a>

[![Written by](https://img.shields.io/badge/Written%20by-ScarletsFiction-%231e87ff.svg)](https://github.com/ScarletsFiction/)
[![Software License](https://img.shields.io/badge/License-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://api.travis-ci.org/ScarletsFiction/Scarlets.svg?branch=master)](https://travis-ci.org/ScarletsFiction/Scarlets)
[![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=Scarlets%20is%20a%20web%20framework%20for%20php%20that%20can%20help%20you%02build%20a%20website%20with%20API%20and%20another%20build-in%20system.%20This%20framework%20does%20a%20lazyload%20of%20it's%20system,%20so%20you%20can%20select%20which%20system%20that%20you%20want%20to%20use%20to%20keep%20your%20website%20in%20a%20high%20performance%20state&url=https://github.com/ScarletsFiction/Scarlets&via=github&hashtags=scarlets,framework,php,)

# Scarlets
> This framework still under development

Scarlets is a web framework for PHP that can help you build a website with API and another build-in system. This framework does a lazyload of it's system, so you can select which system that you want to use to keep your website in a high performance state.

Scarlets have a build-in traffic monitor for any hacking activity or another security problem. And it will suggest you a security option if you have a backdoor on your system.

## Installation instruction

Clone/download this repository and put it on a folder
Then copy the example folder and edit the framework path on `root.php`

### Install by using command prompt
Make sure you have installed PHP on your computer (Windows and OSX can use [XAMPP](https://www.apachefriends.org/index.html)) and the php command is available on the command prompt

> $ php -v

If not, then you need to set it up on the [environment variables](https://www.youtube.com/watch?v=51IlfNzZVGo).

When the php command is available, open your command prompt and enter this line

> $ php -r "copy('https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/net-install', 'net-install');"<br>
> $ php net-install

The framework will automatically installed, and the example files will be prepared on your project folder.

## Using the Scarlets Console

This framework has a build-in server by calling
> $ php scarlets serve (port) (address) (options)<br><br>
> Address: localhost, network, IPAddress<br>
> Options: --log, --verbose<br>

![alt text](https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/images/serve_command.webp)

You can also create your own command for your project

![alt text](https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/images/interactive_console.webp)

## Upgrade
Scarlets have internal upgrade feature
> $ php scarlets upgrade

But if there are any error and the framework was unable to be loaded

Please clone this repository and extract it to `vendor/scarletsfiction/scarlets`

## Contribution

If you want to help in Scarlets framework, please fork this project and edit on your repository, then make a pull request to here.

Keep the code simple and clear.

## Support

If you have any question please ask on stackoverflow with tags 'scarlets-php'.<br>
But if you found bug or feature request, you post an issue on this repository.

For any private support, you can contact the author of this framework:<br>
StefansArya (Indonesia, English)<br>
stefansarya1 at gmail

## License

Scarlets is under the MIT license.

But don't forget to put the a link to this repository.