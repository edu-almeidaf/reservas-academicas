<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function index(): void
    {
        $title = 'Área Administrativa';
        $this->render('admin/index', compact('title'));
    }
}
