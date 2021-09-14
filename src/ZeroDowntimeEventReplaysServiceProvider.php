<?php

namespace Mannum\ZeroDowntimeEventReplays;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Mannum\ZeroDowntimeEventReplays\Commands\ZeroDowntimeEventReplaysCommand;

class ZeroDowntimeEventReplaysServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('zero-downtime-event-replays')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_zero-downtime-event-replays_table')
            ->hasCommand(ZeroDowntimeEventReplaysCommand::class);
    }
}
