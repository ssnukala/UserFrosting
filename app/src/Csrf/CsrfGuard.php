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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Csrf\Guard;
use UserFrosting\Config\Config;
use UserFrosting\Session\Session;
use UserFrosting\Sprinkle\Core\Exceptions\CsrfMissingException;

/**
 * Custom CSRF Guard that handles CLI context properly.
 *
 * This class fixes the issue where passing null as storage causes a 
 * RuntimeException in Slim CSRF Guard. When running in CLI context 
 * (e.g., Bakery commands), we use array storage instead of session storage.
 */
class CsrfGuard extends Guard
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

        parent::__construct(
            $app->getResponseFactory(),
            $config->getString('csrf.name', 'csrf'),
            $storage,
            $onFailure,
            $config->getInt('csrf.storage_limit', 200),
            $config->getInt('csrf.strength', 16),
            $config->getBool('csrf.persistent_token', true)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Normalize path to always have a leading slash
        $path = '/' . ltrim($path, '/');

        // Normalize method to uppercase.
        $method = strtoupper($method);

        /** @var array<string,string[]> */
        $csrfBlacklist = $this->config->getArray('csrf.blacklist');
        $isBlacklisted = false;

        // Go through the blacklist and determine if the path and method match any of the blacklist entries.
        foreach ($csrfBlacklist as $pattern => $methods) {
            $methods = array_map('strtoupper', $methods);
            if (in_array($method, $methods, true) && $pattern !== '' && preg_match('~' . $pattern . '~', $path) == true) {
                $isBlacklisted = true;
                break;
            }
        }

        if ($isBlacklisted === false) {
            return parent::process($request, $handler);
        }

        return $handler->handle($request);
    }
}
