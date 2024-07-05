<p align="center"><img src="https://raw.githubusercontent.com/vanguardbackup/assets/main/icon-200.png" width="120" alt="Vanguard Logo"></p>

# Contributing to Vanguard

Thank you for considering contributing to Vanguard! We welcome any contributions to the project, whether it be bug fixes, new features, or improvements to the codebase. We aim to make the contribution process as easy as possible and have outlined some guidelines below to help you get started.

## Getting Started

To get started with contributing to Vanguard, you will need to fork the repository and clone it to your local machine. You can then create a new branch for your changes and submit a pull request to the main repository. We recommend that you create an issue before starting work on a new feature or bug fix to ensure that the changes are in line with the project's goals.

## Issues

If you find a bug or have a feature request, please create an issue on the GitHub repository. 

We aim to respond to issues as quickly as possible and will work with you to resolve any problems you encounter.

If you are submitting a bug report, please include as much information as possible, including the steps to reproduce the bug and any relevant error messages. This will help us to diagnose and fix the issue more quickly.

## Pull Requests

When submitting a pull request, please ensure that your changes are in line with the project's coding standards and that any new features are well documented in the PR. We require that you include tests for any new functionality to ensure that it works as expected and does not introduce any regressions in the codebase. Screenshots where  would be nice but are not required.

Pull requests will be reviewed by the project maintainers, and we may request changes or provide feedback before merging the changes. We aim to review pull requests as quickly as possible and will work with you to ensure that your changes are merged in a timely manner.

## Coding Standards

Please ensure you write tests for any new functionality you add to the project. Additionally, use Laravel's translation helpers for any text so that the base language strings are in English and can be localized.

We use Duster by Tighten to ensure a consistent code style across the project. You can run the code style fixer by running `./vendor/bin/duster fix` to resolve any issues. We do have a GitHub action that will automatically fix any code style issues on a pull requests and commits.

## Adding Languages

Vanguard defines its languages in `config/app.php` under the `available_languages` array. The array key must be an [ISO 639 language code](https://en.wikipedia.org/wiki/List_of_ISO_639_language_codes).

To add a new language you simply need to define the language in the array mentioned above, and create the related .json file under the `/lang` path. We welcome all language contributions.

For example, if you wanted to add Swedish, you would add `'sv' => 'Swedish'` to the config and create an `sv.json` file in the lang directory. Vanguard will automatically detect this new language and will let users choose it.
