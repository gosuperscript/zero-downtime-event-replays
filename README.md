# Zero downtime event replay for Spatie's Laravel event sourcing package

[comment]: <> ([![Latest Version on Packagist]&#40;https://img.shields.io/packagist/v/gosuperscript/zero-downtime-event-replays.svg?style=flat-square&#41;]&#40;https://packagist.org/packages/gosuperscript/zero-downtime-event-replays&#41;)

[comment]: <> ([![GitHub Tests Action Status]&#40;https://img.shields.io/github/workflow/status/gosuperscript/zero-downtime-event-replays/run-tests?label=tests&#41;]&#40;https://github.com/gosuperscript/zero-downtime-event-replays/actions?query=workflow%3Arun-tests+branch%3Amain&#41;)

[comment]: <> ([![GitHub Code Style Action Status]&#40;https://img.shields.io/github/workflow/status/gosuperscript/zero-downtime-event-replays/Check%20&%20fix%20styling?label=code%20style&#41;]&#40;https://github.com/gosuperscript/zero-downtime-event-replays/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain&#41;)

[comment]: <> ([![Total Downloads]&#40;https://img.shields.io/packagist/dt/gosuperscript/zero-downtime-event-replays.svg?style=flat-square&#41;]&#40;https://packagist.org/packages/gosuperscript/zero-downtime-event-replays&#41;)

---

Usually you have to deal with a few problems that might cause downtime when replaying events. 
Your read models will be truncated at the beginning of a replay, and won't have the correct data until the replay has finished.
Besides that, you'd have to wait with projecting newly recorded events until the replay has finished, to protect the replay order. 

This package solves both problems, allowing a replay to happen to a copy of your read models. 
Once the replay is up to speed, newly recorded events will be played to both projections, so that your copy is kept up to date with the live read model. 
After verifying that the new replay is correct, the package enabled to promote your replay to live. Resulting in a (near) zero downtime release. 

Usually running a new reply will look like this:
1. Create a new replay, ang give it key to identify it. For example "add_extra_field_to_balance_projection". You also specify the projectors you want to play to in this step.

```php
$manager = resolve(\Gosuperscript\ZeroDowntimeEventReplays\ReplayManager::class);
// Create a replay
$manager->createReplay('add_extra_field_to_balance_projection', [
    "App\Projectors\BalanceProjector"
]);
``` 

2. The replay can be started. When replaying, it calls the `useConnection` method on the projector. So the projector knows where it should write its data to.
This package comes with an EloquentZeroDowntimeProjector that gives you some magic for dealing with different connections.
```php
$manager->startReplay('add_extra_field_to_balance_projection');
```

3. Once the replay is finished, but there is still some lag to production because of newly recorded events. 
You can start the replay again, it will start from the latest projected event. Its always possible to monitor the state of the replay and the lag compared to production.
```php
// get the state & progress of your replay 
$manager->getReplay('add_extra_field_to_balance_projection');

// how many events is the replay behind the event stream?
$manager->getReplayLag('add_extra_field_to_balance_projection');
```

4. Once there is no lag, we can start projecting new events to replays.

```php
$manager->startProjectingToReplay('add_extra_field_to_balance_projection');
```

5. Once every thing checks out, you can promote your replay to production.
```php
    $manager->putReplayLive('add_extra_field_to_balance_projection');
```

6. Lastly you can cleanup your replay
```php
    $manager->removeReplay('add_extra_field_to_balance_projection');
```

## Installation

You can install the package via composer:

```bash
composer require gosuperscript/zero-downtime-event-replays
```

[comment]: <> (You can publish and run the migrations with:)

[comment]: <> (```bash)

[comment]: <> (php artisan vendor:publish --provider="Gosuperscript\ZeroDowntimeEventReplays\ZeroDowntimeEventReplaysServiceProvider" --tag="zero-downtime-event-replays-migrations")

[comment]: <> (php artisan migrate)

[comment]: <> (```)

[comment]: <> (You can publish the config file with:)

[comment]: <> (```bash)

[comment]: <> (php artisan vendor:publish --provider="Gosuperscript\ZeroDowntimeEventReplays\ZeroDowntimeEventReplaysServiceProvider" --tag="zero-downtime-event-replays-config")

[comment]: <> (```)

[comment]: <> (This is the contents of the published config file:)

[comment]: <> (```php)

[comment]: <> (return [)

[comment]: <> (];)

[comment]: <> (```)

## Usage

```php
$manager = resolve(\Gosuperscript\ZeroDowntimeEventReplays\ReplayManager::class);
// Create a replay
$manager->createReplay('your_replay_key', ['projectorA', 'projectorB']);

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

## ZeroDowntime projectors
In order to make projectors work with zero downtime replays, they have to implement the `ZeroDowntimeProjector` interface. This interface asks you to implement the following methods: 
```php 
interface ZeroDowntimeProjector
{
    // This method lets the projector know that its replaying on a replay
    public function forReplay(): self;

    // Sets the connection to replay to, using the replay key. Each connection must be treated as a clone of the production schema.
    public function useConnection(string $connection): self;

    // Promote your connection to production
    public function promoteConnectionToProduction(): void;

    // cleanup/remove connection
    public function removeConnection();
}
```

Since most projections probably are replaying to eloquent, this package includes a `EloquentZeroDowntimeProjector` abstract class and a `Projectable` trait to be used on your eloquent read models. 

To make your projectors work with this package: 
1. Make sure your projector extends the `EloquentZeroDowntimeProjector`. 
2. On all read models used by the projector, add the `Projectable` trait. 
3. Implement a `models` method on your projector, that returns all models that the projector writes to. This is used by the EloquentZeroDowntimeProjector in order to setup the right db scheme and promote the right models to production.  
```php 
    public function models(): array
    {
        return [
            new BalanceProjector(),
        ];
    }
```
4. Everywhere where you query or update your read model, use the `forProjection` method. 
```php
    // when truncating
    Balance::forProjection($this->connection)->truncate();
    
    // when querying
    Balance::forProjection($this->connection)->where('user_id', $event->user_id)->first();
    
    // when updating
    Balance::forProjection($this->connection)->where('user_id', $event->user_id)->increment('total', $event->amount);
    
    // when newing an instance
    $balance = Balance::newForProjection($this->connection, ['id' => $event->user_id, 'total' => $event->amount]);
    $balance->save();
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

- [Robertbaelde](https://github.com/robertbaelde)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
