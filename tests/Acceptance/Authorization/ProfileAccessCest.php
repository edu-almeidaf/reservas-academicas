<?php

namespace Tests\Acceptance\Authorization;

use Codeception\Attribute\Examples;
use Codeception\Example;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class ProfileAccessCest extends BaseAcceptanceCest
{
    public function _before(AcceptanceTester $page): void
    {
        parent::_before($page);
        $this->populateUsers();
    }

    /**
     * @param Example<int, string> $example
     */
    #[Examples('student', 'discente@utfpr.edu.br', '/student', 'Área do Discente', '/teacher')]
    #[Examples('student', 'discente@utfpr.edu.br', '/student', 'Área do Discente', '/admin')]
    #[Examples('teacher', 'docente@utfpr.edu.br', '/teacher', 'Área do Docente', '/student')]
    #[Examples('teacher', 'docente@utfpr.edu.br', '/teacher', 'Área do Docente', '/admin')]
    #[Examples('admin', 'tecnico@utfpr.edu.br', '/admin', 'Área Administrativa', '/student')]
    #[Examples('admin', 'tecnico@utfpr.edu.br', '/admin', 'Área Administrativa', '/teacher')]
    public function cannotAccessOtherProfileArea(AcceptanceTester $page, Example $example): void
    {
        $page->login($example[1], '12345678');

        $page->amOnPage($example[4]);

        $page->seeInCurrentUrl($example[2]);
        $page->see($example[3], '//h1');
        $page->see('Você não tem permissão para acessar essa página');
    }
}
