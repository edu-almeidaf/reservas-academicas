<?php

namespace Tests\Acceptance\Authentication;

use Codeception\Attribute\Examples;
use Codeception\Example;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class RestrictedAccessCest extends BaseAcceptanceCest
{
    public function rootRedirectsToLoginIfNotAuthenticated(AcceptanceTester $page): void
    {
        $page->amOnPage('/');
        $page->seeInCurrentUrl('/login');
        $page->see('Login', '//h4');
    }

    /**
     * @param Example<int, string> $example
     */
    #[Examples('student', '/student')]
    #[Examples('teacher', '/teacher')]
    #[Examples('admin', '/admin')]
    public function areaRequiresAuthentication(AcceptanceTester $page, Example $example): void
    {
        $page->amOnPage($example[1]);
        $page->seeInCurrentUrl('/login');
        $page->see('Você deve estar logado para acessar essa página');
    }
}
