<?php

require __DIR__ . '/../../config/bootstrap.php';

use Core\Database\Database;

Database::migrate();

$statement = Database::getDatabaseConn()->prepare(
    'INSERT INTO users (profile, name, email, password_hash) VALUES (?, ?, ?, ?)'
);

foreach ([
    ['discente', 'Discente Teste', 'discente@utfpr.edu.br'],
    ['docente', 'Docente Teste', 'docente@utfpr.edu.br'],
    ['tecnico', 'Técnico Teste', 'tecnico@utfpr.edu.br'],
] as [$profile, $name, $email]) {
    $statement->execute([$profile, $name, $email, password_hash('12345678', PASSWORD_DEFAULT)]);
}
