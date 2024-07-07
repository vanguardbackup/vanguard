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

Vanguard is a Laravel project that aims to provide an easy way to back up your files and databases. It is designed to be easy to use and friendly.  

Vanguard has official support for modern Ubuntu and Debian servers. There are no plans to support Windows. 

## Features

- Easy-to-use interface
- Flexible scheduling of backups
- Daily/weekly or custom via cron
- Supports both file and database backups
- Notifications via Email, Discord or Slack webhooks
- Ability to pause/resume scheduled backup tasks as needed
- Automatic server connection checks
- Multi-language support
- Encrypts sensitive information
- View statistics and aggregated data about your backups over time

Do you have an idea that isn't listed? [Create a post](https://github.com/vanguardbackup/vanguard/discussions/new?category=ideas) in our discussions section.

## Installation and Running Vanguard
As Vanguard is a Laravel project, you can install it like any other Laravel project. Here are the "general" steps to get you started:

Vanguard requires PHP 8.2+, Redis and Composer to be installed on your system. You will also need to have Node.js and NPM installed to build the frontend assets. Ideally we recommend using Laravel Valet, but you can use any other local development environment.

We will try our best to help you to get Vanguard running, but we always recommend you read the [Laravel documentation](https://laravel.com/docs/11.x/installation) if you get stuck.
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

> [!IMPORTANT]
> Make sure you have set your SSH passphrase in your `.env` file. Keep it safe!

## Commands

Vanguard has a few artisan commands that are specific to the project that can be run. Here is a list of the commands and what they do:

> [!NOTE]
> The `vanguard:generate-key`
command will generate an SSH key that will be used to authenticate with your remote servers. The generated keys will be stored in the `storage/app/ssh` directory. Make sure to keep the private key safe.

> [!WARNING]
> There are other commands, but they are not intended to be run manually and are used internally by Vanguard's scheduler.

| Command                                | Description                                                                                                                            |
|----------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------|
| `vanguard:generate-ssh-key`            | Generates an SSH key required for backup operations.                                                                                   |
| `vanguard:version (--check)`           | Checks the version of the application. Passing `--check` will see if there is a newer version published to Github.                     |
| `vanguard:validate-s3-connection {id}` | Able to check whether a backup destination that uses S3 can be reached. This takes the primary key of the backup destination as an id. |
| `vanguard:encrypt-database-passwords`  | Used to convert any previously non-encrypted database passwords to encrypted. This was only necessary once.                            |

> [!NOTE]
> You will not be allowed to generate another SSH key if you already have one configured.


<details>
<summary>Vanguard's terminology</summary>

## Terminology

### Backup Tasks

Backup Tasks are where you define your directory paths pointing to your backup, the times you wish the content to be backed up and where you would like it to be backed up to

### Backup Destinations

Backup Destinations are where you define destinations for your data once it has been backed up. This could be on an S3 bucket or perhaps on the same server just in another directory. The choice is yours.

### Remote Servers

Remote Servers are the Linux servers that hold the data you want to back up.
</details>

## Contributing

Thank you for considering contributing to Vanguard! Please read the [CONTRIBUTING.md](CONTRIBUTING.md) file for more information on how to contribute to the project.

## License

Vanguard is open-sourced software licensed under the [MIT licence](https://opensource.org/licenses/MIT).
