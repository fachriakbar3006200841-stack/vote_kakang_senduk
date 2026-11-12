<?php

echo "Masukkan password admin baru: ";
$password = trim(fgets(STDIN));

if (strlen($password) < 8) {
    exit("Password minimal 8 karakter.\n");
}

echo "\nHash password:\n";
echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;