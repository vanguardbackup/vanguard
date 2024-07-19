<p align="center"><img src="https://raw.githubusercontent.com/vanguardbackup/assets/main/icon-200.png" width="120" alt="Vanguard Logo"></p>

<p align="center">
  <a href="https://github.com/vanguardbackup/vanguard/actions/workflows/main-ci.yml">
    <img src="https://github.com/vanguardbackup/vanguard/actions/workflows/main-ci.yml/badge.svg?branch=main" alt="CI Pipeline">
  </a>
  <a href="https://opensource.org/licenses/MIT">
    <img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License: MIT">
  </a>
  <img src="https://img.shields.io/github/v/release/vanguardbackup/vanguard" alt="GitHub Release">
</p>

## About Vanguard

Vanguard is a Laravel project that aims to provide an easy way to backup your files and databases. It is designed to be easy to use and friendly.

Vanguard has official support for modern Ubuntu and Debian servers.

For detailed documentation, please visit our [official documentation site](https://docs.vanguardbackup.com/).

## Features

- Easy-to-use interface
- Flexible scheduling of backups
- Schedule backups daily, weekly, or with custom cron expressions
- Back up both files and databases
- Receive notifications via Email, Discord, or Slack
- Weekly backup summaries
- Option to authenticate with GitHub
- Ability to pause/resume scheduled backup tasks as needed
- Automatic server connection checks
- Multi-language support
- Encrypts sensitive information
- View statistics and aggregated data about your backups over time

Do you have an idea that isn't listed? [Create a post](https://github.com/vanguardbackup/vanguard/discussions/new?category=ideas) in our discussions section.

## Installation and Running Vanguard

As Vanguard is a Laravel project, you can install it like any other Laravel project. For detailed installation instructions, please visit our [official documentation](https://docs.vanguardbackup.com/installation).

Here's a quick overview to get you started:

Vanguard requires PHP 8.2+, Redis and Composer to be installed on your system. You will also need to have Node.js and NPM installed to build the frontend assets. We recommend using Laravel Valet, but you can use any other local development environment.

We will try our best to help you get Vanguard running, but we always recommend you read the [Laravel documentation](https://laravel.com/docs/11.x/installation) if you get stuck.

1. Clone the repository
2. Run `composer install --no-dev --optimize-autoloader`
3. Run `npm install && npm run build`
4. Copy the `.env.example` file to `.env` and fill in the necessary details
5. Run `php artisan key:generate`
6. Run `php artisan migrate`
7. Run `php artisan vanguard:generate-ssh-key` to generate the SSH key
8. Run `php artisan horizon` to start the Horizon worker for job processing
9. Run `php artisan reverb:start` to start Laravel Reverb for websockets and real-time log viewing
10. Run `php artisan schedule:work` to start the scheduler

> [!IMPORTANT]
> Make sure you have set your SSH passphrase in your `.env` file. Keep it safe!

For more comprehensive installation instructions and troubleshooting tips, please refer to our [detailed installation guide](https://docs.vanguardbackup.com/installation).

## Contributing

Thank you for considering contributing to Vanguard! Please read the [CONTRIBUTING.md](CONTRIBUTING.md) file for more information on how to contribute to the project.

## License

Vanguard is open-sourced software licensed under the [MIT licence](https://opensource.org/licenses/MIT).
