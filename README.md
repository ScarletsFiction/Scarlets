<a href="https://www.patreon.com/stefansarya"><img src="http://anisics.stream/assets/img/support-badge.png" height="20"></a>

[![Written by](https://img.shields.io/badge/Written%20by-ScarletsFiction-%231e87ff.svg)](https://github.com/ScarletsFiction/)
[![Software License](https://img.shields.io/badge/License-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://api.travis-ci.org/ScarletsFiction/Scarlets.svg?branch=master)](https://travis-ci.org/ScarletsFiction/Scarlets)
[![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=Scarlets%20is%20a%20web%20framework%20for%20PHP%20that%20can%20help%20you%20build%20a%20website%20with%20API%20and%20another%20build-in%20system.%20This%20framework%20does%20a%20lazyload%20of%20it's%20system%20to%20keep%20your%20website%20in%20a%20high%20performance%20state&url=https://github.com/ScarletsFiction/Scarlets&via=github&hashtags=scarlets,framework,php)

# Scarlets
> This framework still under development

Scarlets is a web framework for PHP that can help you build a website with API and another build-in system. This framework have a lazyload on it's system, so you can select which system that you want to use to keep your website in a high performance state.

## Installation instruction

Clone/download this repository and put it on a folder.<br>
Then copy the example folder and edit the framework path on `root.php`

### Install by using command prompt
Make sure you have installed PHP on your computer (Windows and OSX can use [XAMPP](https://www.apachefriends.org/index.html))<br>
and make sure the php command is available on the command prompt

```sh
$ php -v
```

If not, then you need to set it up on the [environment variables](https://www.youtube.com/watch?v=51IlfNzZVGo).

When the php command is available, open your command prompt and enter this line

```sh
$ php -r "copy('https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/net-install', 'net-install');"
$ php net-install
```

The framework will automatically installed, and the example files will be prepared on your project folder.

## Upgrade
Scarlets have internal upgrade feature
```sh
$ php scarlets upgrade
```

But if there are any error and the framework was unable to be loaded<br>
Please clone this repository and extract it to `/vendor/scarletsfiction/scarlets`

## Getting Started

### Setup your custom website domain
Before we started, we need to setup Apache or Nginx to route every HTTP request into `/public/` directory.
 - On Apache, you could setup [VirtualHost](https://gist.github.com/hoandang/8066175).
 - On Nginx, you will need to add [new site configuration](http://blog.manugarri.com/how-to-easily-set-up-subdomain-routing-in-nginx/).

If you're using Windows, you can use [Laragon](https://laragon.org/) to easily `Switch Document Root` that will automatically create new Apache VirtualHost and modify `drivers\etc\hosts` for you. So you can easily access your project with a custom domain.

This framework has a build-in server by calling
```sh
$ php scarlets serve (port) (address) (options)
```

> Address: localhost, network, IPAddress<br>
> Options: --log, --verbose<br>

![alt text](https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/images/serve_command.webp)

Even the build-in server was blazingly fast, it still have some problem because it's running in a single thread for every request. So it's very recommended to setup your website using Nginx. But if you want to deploy a small server into Raspberry PI, Android, or other linux devices it may be better to use the build-in server.

You can also create your own command for your project

![alt text](https://raw.githubusercontent.com/ScarletsFiction/Scarlets/master/images/interactive_console.webp)

The user defined command are editable on `/routes/console.php`<br>

### Documentation
The usage on how to use this framework is in the [Wiki](https://github.com/ScarletsFiction/Scarlets/wiki)

## Contribution
If you want to help in Scarlets framework, please fork this project and edit on your repository, then make a pull request to here.

## License
Scarlets is under the MIT license.<br>
Help improve this framework by support the author ＼(≧▽≦)／