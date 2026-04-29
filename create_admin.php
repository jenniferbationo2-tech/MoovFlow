<?php
use App\Models\User;

$user = User::create([
    'name' => 'Carine',
    'nom' => 'Carine',
    'prenom' => 'Admin',
    'email' => 'carine@moov.bf',
    'password' => bcrypt('password123'),
    'is_active' => true,
]);
$user->assignRole('admin');
echo "User created: " . $user->email . "\n";
