# Upgrade from v1 to V2

Follow the steps below to upgrade the package.

## Optional dependencies

The following dependencies are not installed by default anymore:
* https://github.com/laravel/slack-notification-channel
* https://github.com/laravel-notification-channels/webhook

This allows you to avoid useless dependencies installations if you do not want to send Slack and webhook notifications.

In the opposite, if you intend to send Slack and/or webhook notifications, install the required dependencies by following the [installation](../../README.md#installation) instructions.

## See all changes

See all change with the [comparison tool](https://github.com/Okipa/laravel-table/compare/1.5.0...2.0.0).

## Undocumented changes

If you see any forgotten and undocumented change, please submit a PR to add them to this upgrade guide.
