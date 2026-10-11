<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;

class StudentController extends Controller
{
    public function index(): void
    {
        $title = 'Área do Discente';
        $this->render('student/index', compact('title'));
    }
}
