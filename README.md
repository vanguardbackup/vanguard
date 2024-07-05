<p align="center"><img src="https://i.imgur.com/wyPXdbX.png" width="120" alt="Vanguard Logo"></p>

<p align="center">
  <a href="https://github.com/vanguardsh/vanguard/actions/workflows/main-ci.yml">
    <img src="https://github.com/vanguardsh/vanguard/actions/workflows/main-ci.yml/badge.svg?branch=main" alt="CI Pipeline">
  </a>
  <a href="https://opensource.org/licenses/MIT">
    <img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License: MIT">
  </a>
  <img src="https://img.shields.io/github/v/release/vanguardsh/vanguard" alt="GitHub Release">
</p>

## About Vanguard

Vanguard is a Laravel project that aims to provide an easy way to back up your Linux server files or databases to a remote destination such as an S3 bucket. It is designed to be easy to use and to be able to run on a schedule, enhanced with notifications, so you know what's happening with your backups.

## Features

- Backup files and databases to S3 buckets
- Schedule backups
- Notifications via Email or Discord / Slack webhooks
- View your log in real-time via the web interface
- Pause any backup tasks you don't want scheduled
- Supports multiple languages
- View statistics on your backup tasks over time

## Installation and Running
As Vanguard is a Laravel project, you can install it like any other Laravel project. Here are the "general" steps to get you started:

Vanguard requires PHP 8.3, Redis and Composer to be installed on your system. You will also need to have Node.js and NPM installed to build the frontend assets. Ideally we recommend using Laravel Valet, but you can use any other local development environment.

1. Clone the repository
2. Run `composer install --no-dev --optimize-autoloader`
3. Run `npm install && npm run build`
4. Copy the `.env.example` file to `.env` and fill in the necessary details
5. Run `php artisan key:generate`
6. Run `php artisan migrate`
7. Run `php artisan vanguard:generate-key` to generate the SSH key.
8. Run `php artisan horizon` to start the Horizon worker for job processing.
9. Run `php artisan reverb:run` to start Laravel Reverb for websockets and real-time log viewing.
10. Run `php artisan schedule:work` to start the scheduler.

**Note:** The `vanguard:generate-key`
command will generate an SSH key that will be used to authenticate with the remote server. The generated keys will be stored in the `storage/app/ssh` directory. Make sure to keep the private key safe.

**Note Two:** Please ensure you have set your SSH key passphrase, as Vanguard does not support SSH keys without a passphrase, and it will likely not work if the passphrase is not set.

## Tests

Vanguard has a test suite that can be run using Pest. You can run the test suite by running `php artisan test`. We always aim to keep the test suite up to date and passing and welcome any contributions to the test suite to ensure a high level of code quality.

## Code Style

Vanguard uses Duster by Tighten to ensure a consistent code style across the project. You can run the code style fixer by running `./vendor/bin/duster fix` to resolve any issues.  We do have a GitHub action that will automatically fix any code style issues on a pull request, so you can be sure that the code style is always up to date.

## Commands

Vanguard has a few artisan commands that are specific to the project that can be run. Here is a list of the commands and what they do:

| Command                                | Description                                                                                                                            |
|----------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------|
| `vanguard:generate-ssh-key`            | Generates an SSH key required for backup operations.                                                                                   |
| `vanguard:version`                     | Checks the version of Vanguard.                                                                                                        |
| `vanguard:validate-s3-connection {id}` | Able to check whether a backup destination that uses S3 can be reached. This takes the primary key of the backup destination as an id. |
| `vanguard:encrypt-database-passwords`  | Used to convert any previously non-encrypted database passwords to encrypted. This was only necessary once.                            |

**Note:** There are other commands, but they are not intended to be run manually and are used internally by Vanguard's scheduler.

## Contributing

Thank you for considering contributing to Vanguard! Please read the [CONTRIBUTING.md](CONTRIBUTING.md) file for more information on how to contribute to the project.

## License

Vanguard is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
