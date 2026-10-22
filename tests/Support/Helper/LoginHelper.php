<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;

class LoginHelper extends Module
{
    public function login(string $username, string $password): void
    {
        /** @var \Tests\Support\AcceptanceTester $page */
        $page = $this->getModule('WebDriver');
        $page->amOnPage('/login');
        $page->fillField('user[email]', $username);
        $page->fillField('user[password]', $password);
        $page->click('Entrar');

        if (str_starts_with($page->grabFromCurrentUrl(), '/login/code')) {
            preg_match('/\d{8}/', $page->grabTextFrom('.alert-success'), $matches);
            $page->fillField('code', $matches[0]);
            $page->click('Verificar');
        }
    }

    public function logout(): void
    {
        /** @var \Tests\Support\AcceptanceTester $page */
        $page = $this->getModule('WebDriver');
        $page->click('Sair');
    }
}
