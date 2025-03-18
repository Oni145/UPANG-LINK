<?php
header('Content-Type: application/json');

// Sample response data
$sampleUser = [
    'id' => '1',
    'email' => 'jerickogarcia0@gmail.com',
    'first_name' => 'Jericko',
    'last_name' => 'Garcia',
    'profile_picture' => 'https://ui-avatars.com/api/?name=Jericko+Garcia',
    'student_number' => '2020-00123',
    'course' => 'BSIT',
    'year_level' => '4th Year',
    'section' => 'A',
    'contact_number' => '09123456789',
    'address' => 'Lucena City, Quezon',
    'created_at' => '2024-03-05 12:00:00'
];

echo json_encode([
    'success' => true,
    'data' => $sampleUser
]); 