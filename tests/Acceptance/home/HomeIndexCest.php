<?php

namespace Tests\Acceptance\home;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class HomeIndexCest extends BaseAcceptanceCest
{
    public function redirectToLoginIfNotAuthenticated(AcceptanceTester $page): void
    {
        $page->amOnPage('/');
        $page->seeInCurrentUrl('/login');
        $page->see('Login', '//h4');
    }
}
