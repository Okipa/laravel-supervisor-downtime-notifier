# Get notified when supervisor is down

[![Source Code](https://img.shields.io/badge/source-okipa/laravel--supervisor--downtime--notifier-blue.svg)](https://github.com/Okipa/laravel-supervisor-downtime-notifier)
[![Latest Version](https://img.shields.io/github/release/okipa/laravel-supervisor-downtime-notifier.svg?style=flat-square)](https://github.com/Okipa/laravel-supervisor-downtime-notifier/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/okipa/laravel-supervisor-downtime-notifier.svg?style=flat-square)](https://packagist.org/packages/okipa/laravel-supervisor-downtime-notifier)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![Build status](https://github.com/Okipa/laravel-supervisor-downtime-notifier/workflows/CI/badge.svg)](https://github.com/Okipa/laravel-supervisor-downtime-notifier/actions)
[![Coverage Status](https://coveralls.io/repos/github/Okipa/laravel-supervisor-downtime-notifier/badge.svg?branch=master)](https://coveralls.io/github/Okipa/laravel-supervisor-downtime-notifier?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/Okipa/laravel-supervisor-downtime-notifier.svg?style=flat-square)](https://scrutinizer-ci.com/g/Okipa/laravel-supervisor-downtime-notifier/?branch=master)

Get notified and execute PHP callback when:
* the supervisor service is not running on your server.
* your environment supervisor processes are down.
  
Notifications can be sent by mail, Slack and webhooks (chats often provide a webhook API).

## Compatibility

| Laravel version | PHP version | Package version |
|---|---|---|
| ^6.0 | ^7.4 | ^1.0 |

## Table of Contents
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Credits](#credits)
- [Licence](#license)

## Requirements

By default, this package monitors supervisor downtime for projects running on Linux servers.

The user running PHP CLI will execute the following commands:

* `systemctl is-active supervisor`
* `supervisorctl status "<your-process-name>"`

As so, make sure you give him permission to execute these actions (`sudo visudo -f /etc/sudoers.d/<user>`) :

* `<user> ALL=NOPASSWD:/bin/systemctl is-active supervisor`
* `<user> ALL=NOPASSWD:/usr/bin/supervisorctl status *`

That being said, you still can use this package for other servers OS by using your own `SupervisorChecker` class and defining OS-specific commands.

## Installation

Install the package with composer:

```bash
composer require "okipa/laravel-supervisor-downtime-notifier:^1.0"
```

In case you want to use `Slack` notifications you'll also have to install:

```bash
composer require guzzlehttp/guzzle
```

## Configuration
  
Publish the package configuration: 

```bash
php artisan vendor:publish --tag=supervisor-downtime-notifier:config
```

## Usage

Just add this command in the `schedule()` method of your `\App\Console\Kernel` class :

```php
$schedule->command('supervisor:downtime:notify')->everyFifteenMinutes();
```

And you will be notified if your supervisor service is not running, or if your environment supervisor processes are down when the command will be executed.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Arthur LORENT](https://github.com/okipa)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
