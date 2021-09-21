# Zero downtime event replay for Spatie's Laravel event sourcing package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mannum/zero-downtime-event-replays.svg?style=flat-square)](https://packagist.org/packages/mannum/zero-downtime-event-replays)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/mannum/zero-downtime-event-replays/run-tests?label=tests)](https://github.com/mannum/zero-downtime-event-replays/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/mannum/zero-downtime-event-replays/Check%20&%20fix%20styling?label=code%20style)](https://github.com/mannum/zero-downtime-event-replays/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mannum/zero-downtime-event-replays.svg?style=flat-square)](https://packagist.org/packages/mannum/zero-downtime-event-replays)

---

Migration:
1. run replay on clone read models, store id of latest processed event 
   1. For this, pass a replay_prefix to projector
   2. Before start of replay, a perepare replay environment method should be called on projector
2. Once replay is finished, run replay again, to add newly created events
   1. Repeat until replay copy is (almost) up-to-date
3. Projections should now be written to both replays
4. switch over

## Installation

You can install the package via composer:

```bash
composer require mannum/zero-downtime-event-replays
```

[comment]: <> (You can publish and run the migrations with:)

[comment]: <> (```bash)

[comment]: <> (php artisan vendor:publish --provider="Mannum\ZeroDowntimeEventReplays\ZeroDowntimeEventReplaysServiceProvider" --tag="zero-downtime-event-replays-migrations")

[comment]: <> (php artisan migrate)

[comment]: <> (```)

[comment]: <> (You can publish the config file with:)

[comment]: <> (```bash)

[comment]: <> (php artisan vendor:publish --provider="Mannum\ZeroDowntimeEventReplays\ZeroDowntimeEventReplaysServiceProvider" --tag="zero-downtime-event-replays-config")

[comment]: <> (```)

[comment]: <> (This is the contents of the published config file:)

[comment]: <> (```php)

[comment]: <> (return [)

[comment]: <> (];)

[comment]: <> (```)

## Usage

```php
$manager = resolve(\Mannum\ZeroDowntimeEventReplays\ReplayManager::class);
// Create a replay
$manager->createReplay('your_replay_key');

// Start replay history
$manager->startReplay('your_replay_key');

// get the state & progress of your replay 
$manager->getReplay('your_replay_key');

// how many events is the replay behind the event stream?
$manager->getReplayLag('your_replay_key');

// once a replay is up to date with the event stream, we can project events to it when they happen 
$manager->startProjectingToReplay('your_replay_key');

// Once the replay is approved, we can promote it to production
$manager->putReplayLive('your_replay_key');

// Or we can delete the replay
$manager->removeReplay('your_replay_key');

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Robertbaelde](https://github.com/mannum)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
