<?php

namespace Tests\Acceptance\Authentication;

use Codeception\Attribute\Examples;
use Codeception\Example;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class LoginCest extends BaseAcceptanceCest
{
    public function _before(AcceptanceTester $page): void
    {
        parent::_before($page);
        $this->populateUsers();
    }

    /**
     * @param Example<int, string> $example
     */
    #[Examples('student', 'discente@utfpr.edu.br', '/student', 'Área do Discente')]
    #[Examples('teacher', 'docente@utfpr.edu.br', '/teacher', 'Área do Docente')]
    #[Examples('admin', 'tecnico@utfpr.edu.br', '/admin', 'Área Administrativa')]
    public function loginSuccessfully(AcceptanceTester $page, Example $example): void
    {
        $page->login($example[1], '12345678');

        $page->seeInCurrentUrl($example[2]);
        $page->see($example[3], '//h1');
        $page->see('Login realizado com sucesso!');
    }

    /**
     * @param Example<int, string> $example
     */
    #[Examples('student', 'discente@utfpr.edu.br')]
    #[Examples('teacher', 'docente@utfpr.edu.br')]
    #[Examples('admin', 'tecnico@utfpr.edu.br')]
    public function loginUnsuccessfullyWithWrongPassword(AcceptanceTester $page, Example $example): void
    {
        $page->login($example[1], 'wrong_password');

        $page->seeInCurrentUrl('/login');
        $page->see('Email e/ou senha inválidos!');
    }

    public function loginUnsuccessfullyWithUnknownEmail(AcceptanceTester $page): void
    {
        $page->login('ninguem@utfpr.edu.br', '12345678');

        $page->seeInCurrentUrl('/login');
        $page->see('Email e/ou senha inválidos!');
    }
}
