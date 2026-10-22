<?php

require __DIR__ . '/../../config/bootstrap.php';

use Core\Database\Database;
use Database\Populate\ReservationCategoriesPopulate;
use Database\Populate\ResourceCategoriesPopulate;
use Database\Populate\ResourcesPopulate;
use Database\Populate\UsersPopulate;

Database::migrate();

UsersPopulate::populate();
ResourceCategoriesPopulate::populate();
ResourcesPopulate::populate();
ReservationCategoriesPopulate::populate();
