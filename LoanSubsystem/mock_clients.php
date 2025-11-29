<?php
// mock_clients.php

$mockClients = [
    [
        'full_name'      => 'Kurt Realisan',
        'account_number' => '1001234567890',
        'email'          => 'kurtrealisan@gmail.com',
        'contact_number' => '09123456789',
        'job'            => 'Data Analyst',
        'monthly_salary' => 20000,
        'password'       => password_hash('password123', PASSWORD_DEFAULT) // Store hashed!
    ],
    [
        'full_name'      => 'Jiro Pinto',
        'account_number' => '1002345678901',
        'email'          => 'jiropinto@gmail.com',
        'contact_number' => '09234567890',
        'job'            => 'Programmer',
        'monthly_salary' => 50000,
        'password'       => password_hash('password123', PASSWORD_DEFAULT)
    ],
    [
        'full_name'      => 'Angelo Gualva',
        'account_number' => '1003456789012',
        'email'          => 'angelogualva@gmail.com',
        'contact_number' => '09345678901',
        'job'            => 'Front End Developer',
        'monthly_salary' => 10000,
        'password'       => password_hash('password123', PASSWORD_DEFAULT)
    ],
    [
        'full_name'      => 'Mike Beringuela',
        'account_number' => '1004567890123',
        'email'          => 'mikeberinguela@gmail.com',
        'contact_number' => '09456789012',
        'job'            => 'Project Manager',
        'monthly_salary' => 70000,
        'password'       => password_hash('password123', PASSWORD_DEFAULT)
    ],
    [
        'full_name'      => 'Jestony Malunes',
        'account_number' => '1005678901234',
        'email'          => 'jestonymalunes@gmail.com',
        'contact_number' => '09567890123',
        'job'            => 'Backend Developer',
        'monthly_salary' => 20000,
        'password'       => password_hash('password123', PASSWORD_DEFAULT)
    ],
    [
        'full_name'      => 'Clarence Carpeso',
        'account_number' => '1006789012345',
        'email'          => 'clarencecarpeso@gmail.com',
        'contact_number' => '09678901234',
        'job'            => 'App Developer',
        'monthly_salary' => 20000,
        'password'       => password_hash('password123', PASSWORD_DEFAULT)
    ]
];