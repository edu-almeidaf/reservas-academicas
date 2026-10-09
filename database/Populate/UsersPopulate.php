<?php

namespace Database\Populate;

use App\Models\User;

class UsersPopulate
{
    public static function populate(): void
    {
        $users = [
            ['profile' => User::PROFILE_STUDENT, 'name' => 'Discente Teste', 'email' => 'discente@utfpr.edu.br'],
            ['profile' => User::PROFILE_TEACHER, 'name' => 'Docente Teste', 'email' => 'docente@utfpr.edu.br'],
            ['profile' => User::PROFILE_ADMIN, 'name' => 'Técnico Teste', 'email' => 'tecnico@utfpr.edu.br'],
        ];

        foreach ($users as $data) {
            $data['password'] = '12345678';
            $data['password_confirmation'] = '12345678';

            $user = new User($data);
            $user->save();
        }

        $numberOfUsers = count($users);

        echo "Users populated with $numberOfUsers registers\n";
    }
}
