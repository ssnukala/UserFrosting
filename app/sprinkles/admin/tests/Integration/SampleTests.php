<?php

namespace UserFrosting\Tests\Integration;

use Exception;
use UserFrosting\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Integration tests for the built-in Sprunje classes.
 */
class SprunjeTests extends TestCase
{
    protected $schema = 'test_integration';

    /**
     * A list of migrations to run when the test suite is initialized.
     *
     * @var array
     */
    protected $migrations;

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function setUp()
    {
        // Boot parent TestCase, which will set up the database and connections for us.
        parent::setUp();

        // Boot database
        $this->ci->db;

        $this->createSchema();
    }

    protected function createSchema()
    {
        $this->migrations = [
            new \UserFrosting\Sprinkle\Account\Database\Migrations\v400\UsersTable($this->schema, $this->io)
        ];

        foreach ($this->migrations as $migration) {
            $migration->up();
        }
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        foreach ($this->migrations as $migration) {
            $migration->down();
        }
    }

    /**
     * Tests...
     */
    public function testUserPermissionSprunje()
    {
    
    }
}

