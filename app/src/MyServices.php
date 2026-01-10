<?php

/*
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @copyright Copyright (c) 2013-2024 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\App;

use UserFrosting\App\Csrf\CsrfGuard;
use UserFrosting\ServicesProvider\ServicesProviderInterface;
use UserFrosting\Sprinkle\Core\Csrf\CsrfGuard as CoreCsrfGuard;

class MyServices implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            // Override the Core CsrfGuard to fix CLI context issue
            CoreCsrfGuard::class => \DI\autowire(CsrfGuard::class),
        ];
    }
}
