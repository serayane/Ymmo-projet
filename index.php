<?php
require 'config/db.php';

$ville = $_GET['ville'] ?? '';
$type = $_GET['type'] ?? '';
$prix_max = $_GET['prix_max'] ?? '';

$sql = "SELECT * FROM biens WHERE statut = 'disponible'";
$params = [];

if ($ville) { $sql .= " AND ville LIKE :ville"; $params[':ville'] = "%$ville%"; }
if ($type) { $sql .= " AND type = :type"; $params[':type'] = $type; }
if ($prix_max) { $sql .= " AND prix <= :prix_max"; $params[':prix_max'] = $prix_max; }

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$biens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ymmo — Biens immobiliers</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a class="navbar-brand" href="index.php">YM<span>MO</span></a>
    <a href="stats.php" class="btn-nav">Statistiques</a>
    <a href="login.php" class="btn-nav">Espace agent</a>
</nav>

<section class="hero">
    <h1>L'immobilier <span>d'exception</span><br>à votre portée</h1>
    <p>Découvrez nos biens sélectionnés dans toute la France</p>

    <form method="GET" class="filters">
        <input type="text" name="ville" placeholder="Ville" value="<?= htmlspecialchars($ville) ?>">
        <select name="type">
            <option value="">Tous les types</option>
            <option value="appartement" <?= $type=='appartement'?'selected':'' ?>>Appartement</option>
            <option value="maison" <?= $type=='maison'?'selected':'' ?>>Maison</option>
            <option value="bureau" <?= $type=='bureau'?'selected':'' ?>>Bureau</option>
            <option value="terrain" <?= $type=='terrain'?'selected':'' ?>>Terrain</option>
        </select>
        <input type="number" name="prix_max" placeholder="Prix max (€)" value="<?= htmlspecialchars($prix_max) ?>">
        <button type="submit" class="btn-filter">Rechercher</button>
    </form>
</section>

<div class="container">
    <?php if (empty($biens)): ?>
        <div class="empty-state">
            <p>Aucun bien ne correspond à votre recherche.</p>
        </div>
    <?php else: ?>
    <div class="biens-grid">
        <?php foreach ($biens as $bien): ?>
        <a href="bien.php?id=<?= $bien['id'] ?>" class="card">
            <div class="card-img">
                <?php if ($bien['photo']): ?>
                    <img src="uploads/<?= htmlspecialchars($bien['photo']) ?>" alt="">
                <?php else: ?>
                    <span class="card-img-placeholder">Photo à venir</span>
                <?php endif; ?>
                <span class="badge-type"><?= $bien['type'] ?></span>
            </div>
            <div class="card-body">
                <div class="card-title"><?= htmlspecialchars($bien['titre']) ?></div>
                <div class="card-location">📍 <?= htmlspecialchars($bien['ville']) ?></div>
                <div class="card-price"><?= number_format($bien['prix'], 0, ',', ' ') ?> €</div>
                <div class="card-surface"><?= $bien['surface'] ?> m²</div>
            </div>
            <div class="card-footer-bar">
                <span class="btn-voir">Voir le bien →</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</body>
</html>