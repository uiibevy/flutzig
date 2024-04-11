# Contributing

Thanks for your interest in contributing to Flutzig! Contributions are welcome and will be credited.

To keep things running smoothly we ask you to follow a few guidelines when contributing. Please read and understand this contribution guide before creating an issue or pull request.

## Etiquette

Be kind.

## Viability

If you have an idea for a feature, we'd prefer you open a discussion before going to the trouble of writing code. We welcome your ideas, but we'd like to work with you to come up with solutions that work well for the project as a whole. We're usually pretty responsive, so it shouldn't take us long to figure out whether and how best to implement your idea.

## Procedure

Before filing an issue:

- Attempt to replicate the problem, to ensure that it wasn't a coincidence.
- Check to make sure your feature suggestion isn't already present within the project.
- Check the pull requests tab to ensure that your feature or bugfix isn't already in progress.

Before submitting a pull request:

- Check the codebase to ensure that your feature doesn't already exist.
- Check the pull requests to ensure that another person hasn't already submitted the feature or fix.

## Tests

Please write tests for any fixes or new features you contribute. We use [Orchestra Testbench](https://github.com/orchestral/testbench) as our base Pest test for PHP.

You can run PHP tests with `vendor/bin/pest`.

If you need any help with this please don't hesitate to ask.

> If your filesystem uses `CRLF` instead of `LF` line endings (e.g. Windows) you may see errors related to that when running tests. To fix this you can run `git config --global core.autocrlf input` to configure Git to preserve the line endings from the repository when cloning. You may have to clone this repository again.

## Releases

> [!NOTE]
> Flutzig publishes two different versions of its built assets to Packagist and pub.dev (the PubDev build does not bundle in our external PubDev dependencies). The ones that live in the repo are for Composer/Packagist, and the ones for PubDev are built automatically when running `pub get` and can be reverted/deleted after publishing.

To create and release a new version of Flutzig:

- Coming soon !

## Requirements

- **[PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)** - The easiest way to apply the conventions is to install [PHP Code Sniffer](https://github.com/squizlabs/PHP_CodeSniffer).
- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.
