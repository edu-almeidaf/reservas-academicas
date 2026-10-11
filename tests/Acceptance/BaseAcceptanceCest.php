<?php

namespace Tests\Acceptance;

use Core\Database\Database;
use Core\Env\EnvLoader;
use Database\Populate\UsersPopulate;
use Tests\Support\AcceptanceTester;

class BaseAcceptanceCest
{
    public function _before(AcceptanceTester $page): void
    {
        EnvLoader::init();
        Database::create();
        Database::migrate();
    }

    public function _after(AcceptanceTester $page): void
    {
        Database::drop();
    }

    protected function populateUsers(): void
    {
        ob_start();
        UsersPopulate::populate();
        ob_end_clean();
    }
}
