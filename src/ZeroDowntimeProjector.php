<?php

namespace Mannum\ZeroDowntimeEventReplays;

interface ZeroDowntimeProjector
{
    public function useConnection(string $connection): void;

    public function promoteConnectionToProduction(): void;
}
