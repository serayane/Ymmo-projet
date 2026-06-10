<?php
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bien_id = $_POST['bien_id'] ?? null;
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';

    if ($bien_id && $nom && $email) {
        $stmt = $pdo->prepare("INSERT INTO demandes (bien_id, nom_client, email_client, message) VALUES (:bien_id, :nom, :email, :message)");
        $stmt->execute([
            ':bien_id' => $bien_id,
            ':nom' => $nom,
            ':email' => $email,
            ':message' => $message
        ]);
        header("Location: bien.php?id=$bien_id&success=1");
        exit;
    }
    header("Location: bien.php?id=$bien_id");
    exit;
}

header('Location: index.php');