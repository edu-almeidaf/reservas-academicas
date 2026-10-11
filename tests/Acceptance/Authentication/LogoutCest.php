<?php

namespace Tests\Acceptance\Authentication;

use Codeception\Attribute\Examples;
use Codeception\Example;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class LogoutCest extends BaseAcceptanceCest
{
    public function _before(AcceptanceTester $page): void
    {
        parent::_before($page);
        $this->populateUsers();
    }

    /**
     * @param Example<int, string> $example
     */
    #[Examples('student', 'discente@utfpr.edu.br', '/student')]
    #[Examples('teacher', 'docente@utfpr.edu.br', '/teacher')]
    #[Examples('admin', 'tecnico@utfpr.edu.br', '/admin')]
    public function logoutSuccessfully(AcceptanceTester $page, Example $example): void
    {
        $page->login($example[1], '12345678');
        $page->seeInCurrentUrl($example[2]);

        $page->logout();

        $page->seeInCurrentUrl('/login');
        $page->see('Logout realizado com sucesso!');

        $page->amOnPage($example[2]);
        $page->seeInCurrentUrl('/login');
        $page->see('Você deve estar logado para acessar essa página');
    }
}
