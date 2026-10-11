<?php

namespace Tests\Acceptance\Users;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class SignupCest extends BaseAcceptanceCest
{
    public function signupSuccessfully(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->click('Criar conta');

        $page->seeInCurrentUrl('/signup');
        $page->fillField('user[name]', 'Novo Discente');
        $page->fillField('user[email]', 'novo@utfpr.edu.br');
        $page->checkOption('#user_profile_discente');
        $page->fillField('user[password]', '12345678');
        $page->fillField('user[password_confirmation]', '12345678');
        $page->click('Cadastrar');

        $page->seeInCurrentUrl('/login');
        $page->see('Cadastro realizado com sucesso! Faça login para continuar.');

        $page->login('novo@utfpr.edu.br', '12345678');

        $page->seeInCurrentUrl('/student');
        $page->see('Área do Discente', '//h1');
    }

    public function signupUnsuccessfully(AcceptanceTester $page): void
    {
        $page->amOnPage('/signup');

        $page->fillField('user[email]', 'invalido');
        $page->fillField('user[password]', '12345678');
        $page->fillField('user[password_confirmation]', '87654321');
        $page->click('Cadastrar');

        $page->seeInCurrentUrl('/signup');
        $page->see('Existem dados incorretos! Por favor, verifique!');
        $page->see('não pode ser vazio!');
        $page->see('não é um e-mail válido!');
        $page->see('as senhas devem ser idênticas!');
        $page->see('não é um valor válido!');
    }
}
