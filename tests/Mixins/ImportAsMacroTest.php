<?php

namespace Maatwebsite\Excel\Tests\Mixins;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Maatwebsite\Excel\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Maatwebsite\Excel\Tests\Data\Stubs\Database\User;

class ImportAsMacroTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'testing']);
    }

    /**
     * @test
     */
    public function can_import_directly_into_a_model_with_mapping()
    {
        User::query()->truncate();

        User::importAs('import-users.xlsx', function (array $row) {
            return [
                'name'     => $row[0],
                'email'    => $row[1],
                'password' => 'secret',
            ];
        });

        $this->assertCount(2, User::all());
        $this->assertEquals([
            'patrick@maatwebsite.nl',
            'taylor@laravel.com',
        ], User::query()->pluck('email')->all());
    }
}
