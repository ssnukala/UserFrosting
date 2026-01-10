<?php

declare(strict_types=1);

/*
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @copyright Copyright (c) 2013-2024 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\App\Csrf;

use Slim\App;
use UserFrosting\Config\Config;
use UserFrosting\Session\Session;
use UserFrosting\Sprinkle\Core\Csrf\CsrfGuard as BaseCsrfGuard;
use UserFrosting\Sprinkle\Core\Exceptions\CsrfMissingException;

/**
 * Custom CSRF Guard that handles CLI context properly.
 *
 * This class extends the base CsrfGuard to fix the issue where passing null
 * as storage causes a RuntimeException in Slim CSRF Guard. When running in
 * CLI context (e.g., Bakery commands), we use array storage instead of
 * session storage.
 */
class CsrfGuard extends BaseCsrfGuard
{
    /**
     * Overwrites the default constructor to inject dependencies.
     *
     * @param Config             $config
     * @param Session            $session
     * @param App<\DI\Container> $app
     */
    public function __construct(
        protected Config $config,
        protected Session $session,
        App $app,
    ) {
        // Determine if we're running in CLI context
        $isCli = php_sapi_name() === 'cli' || defined('STDIN');

        // Use array storage in CLI context, otherwise use session storage
        if ($isCli) {
            $storage = [];
        } else {
            // Use default session storage, but make sure it's active first
            $session->start();
            $storage = null;
        }

        // Define onFailure callback to throw a CsrfMissingException
        $onFailure = function ($request, $response) {
            throw new CsrfMissingException('The CSRF code was invalid or not provided.');
        };

        // Call the Slim\Csrf\Guard constructor directly to avoid the parent's constructor
        \Slim\Csrf\Guard::__construct(
            $app->getResponseFactory(),
            $config->getString('csrf.name', 'csrf'),
            $storage,
            $onFailure,
            $config->getInt('csrf.storage_limit', 200),
            $config->getInt('csrf.strength', 16),
            $config->getBool('csrf.persistent_token', true)
        );
    }
}
