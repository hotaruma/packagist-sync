<?php

declare(strict_types=1);

namespace Hotaruma\PackagistSync\Exception;

use Hotaruma\PackagistSync\Interfaces\Exception\SyncPackageExceptionInterface;
use RuntimeException;

class SyncPackageServicePackageNotFoundException extends RuntimeException implements SyncPackageExceptionInterface
{
    public function __construct(string $packageName)
    {
        parent::__construct(
            sprintf(
                'Could not find a package: %s',
                $packageName,
            ),
        );
    }
}
