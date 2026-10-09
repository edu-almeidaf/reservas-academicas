<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;

class TeacherController extends Controller
{
    public function index(): void
    {
        $title = 'Área do Docente';
        $this->render('teacher/index', compact('title'));
    }
}
