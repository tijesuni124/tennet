<?php
$host = "db.fr-pari1.bengt.wasmernet.com";
$dbname = "virtual_numbers";
$user = "840ba4847428800010fa366c22c0";
$pass = "068d840b-a484-7614-8000-2117c3a5f3d4";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("DB Connection failed: " . $e->getMessage());
}