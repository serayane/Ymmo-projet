<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$success = '';
$error = '';

// Ajouter un bien
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'] ?? '';
    $type = $_POST['type'] ?? '';
    $prix = $_POST['prix'] ?? '';
    $surface = $_POST['surface'] ?? '';
    $ville = $_POST['ville'] ?? '';
    $adresse = $_POST['adresse'] ?? '';
    $description = $_POST['description'] ?? '';
    $photo_name = null;

    // Upload photo
    if (!empty($_FILES['photo']['name'])) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_name = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], 'uploads/' . $photo_name);
    }

    if ($titre && $type && $prix && $surface && $ville) {
        $stmt = $pdo->prepare("INSERT INTO biens (titre, type, prix, surface, ville, adresse, description, photo, agence_id, user_id) VALUES (:titre, :type, :prix, :surface, :ville, :adresse, :description, :photo, :agence_id, :user_id)");
        $stmt->execute([
            ':titre' => $titre,
            ':type' => $type,
            ':prix' => $prix,
            ':surface' => $surface,
            ':ville' => $ville,
            ':adresse' => $adresse,
            ':description' => $description,
            ':photo' => $photo_name,
            ':agence_id' => $user['agence_id'],
            ':user_id' => $user['id']
        ]);
        $success = "Bien ajouté avec succès !";
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Récupérer les biens de l'agent
$stmt = $pdo->prepare("SELECT * FROM biens WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute([':user_id' => $user['id']]);
$mes_biens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Ymmo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a class="navbar-brand" href="index.php">YM<span>MO</span></a>
    <div style="display:flex;align-items:center;gap:1rem;">
        <span style="color:var(--text-muted);font-size:0.85rem;">Bonjour, <?= htmlspecialchars($user['nom']) ?></span>
        <a href="stats.php" class="btn-nav">Statistiques</a>
        <a href="logout.php" class="btn-nav">Déconnexion</a>
    </div>
</nav>

<div class="dashboard-header">
    <div class="dashboard-title">Tableau de bord agent</div>
    <p style="color:var(--text-muted);margin-top:0.5rem;font-size:0.9rem;"><?= count($mes_biens) ?> bien(s) publié(s)</p>
</div>

<div class="dashboard-grid">

    <!-- Formulaire ajout bien -->
    <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:1.3rem;margin-bottom:1.5rem;color:var(--gold)">Ajouter un bien</h2>

        <?php if ($success): ?>
            <div class="alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Titre *</label>
                <input type="text" name="titre" placeholder="Ex: Bel appartement T3 centre-ville" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label>Type *</label>
                    <select name="type" style="width:100%;background:var(--dark-2);border:1px solid rgba(255,255,255,0.1);color:var(--text);padding:0.8rem 1rem;font-family:'DM Sans',sans-serif;outline:none;">
                        <option value="appartement">Appartement</option>
                        <option value="maison">Maison</option>
                        <option value="bureau">Bureau</option>
                        <option value="terrain">Terrain</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <select name="statut" style="width:100%;background:var(--dark-2);border:1px solid rgba(255,255,255,0.1);color:var(--text);padding:0.8rem 1rem;font-family:'DM Sans',sans-serif;outline:none;">
                        <option value="disponible">Disponible</option>
                        <option value="vendu">Vendu</option>
                        <option value="loué">Loué</option>
                    </select>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label>Prix (€) *</label>
                    <input type="number" name="prix" placeholder="Ex: 285000" required>
                </div>
                <div class="form-group">
                    <label>Surface (m²) *</label>
                    <input type="number" name="surface" placeholder="Ex: 68" required>
                </div>
            </div>
            <div class="form-group">
                <label>Ville *</label>
                <input type="text" name="ville" placeholder="Ex: Aix-en-Provence" required>
            </div>
            <div class="form-group">
                <label>Adresse</label>
                <input type="text" name="adresse" placeholder="Ex: 12 rue Mirabeau">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" placeholder="Décrivez le bien..."></textarea>
            </div>
            <div class="form-group">
                <label>Photo</label>
                <input type="file" name="photo" accept="image/*" style="width:100%;background:var(--dark-2);border:1px solid rgba(255,255,255,0.1);color:var(--text);padding:0.8rem 1rem;font-family:'DM Sans',sans-serif;">
            </div>
            <button type="submit" class="btn-gold">Publier le bien</button>
        </form>
    </div>

    <!-- Liste des biens de l'agent -->
    <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:1.3rem;margin-bottom:1.5rem;color:var(--gold)">Mes biens</h2>

        <?php if (empty($mes_biens)): ?>
            <div class="empty-state">
                <p>Aucun bien publié pour l'instant.</p>
            </div>
        <?php else: ?>
            <?php foreach ($mes_biens as $bien): ?>
            <div style="background:var(--dark-3);border:1px solid rgba(255,255,255,0.06);padding:1.2rem;margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font-family:'Playfair Display',serif;font-size:1rem;margin-bottom:0.3rem;"><?= htmlspecialchars($bien['titre']) ?></div>
                    <div style="color:var(--text-muted);font-size:0.8rem;">📍 <?= htmlspecialchars($bien['ville']) ?> — <?= number_format($bien['prix'], 0, ',', ' ') ?> €</div>
                </div>
                <div style="display:flex;gap:0.8rem;align-items:center;">
                    <span style="font-size:0.75rem;padding:0.3rem 0.8rem;border:1px solid <?= $bien['statut']=='disponible' ? 'var(--success)' : 'var(--text-muted)' ?>;color:<?= $bien['statut']=='disponible' ? 'var(--success)' : 'var(--text-muted)' ?>;">
                        <?= ucfirst($bien['statut']) ?>
                    </span>
                    <a href="bien.php?id=<?= $bien['id'] ?>" style="color:var(--gold);font-size:0.8rem;text-decoration:none;">Voir →</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>