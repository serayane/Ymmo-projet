<?php
require 'config/db.php';

// Prix moyen par type
$stmt = $pdo->query("SELECT type, ROUND(AVG(prix), 0) as prix_moyen, COUNT(*) as nombre FROM biens GROUP BY type ORDER BY prix_moyen DESC");
$stats_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Biens par ville
$stmt = $pdo->query("SELECT ville, COUNT(*) as nombre, ROUND(AVG(prix), 0) as prix_moyen FROM biens GROUP BY ville ORDER BY nombre DESC");
$stats_ville = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total biens disponibles / vendus
$stmt = $pdo->query("SELECT statut, COUNT(*) as nombre FROM biens GROUP BY statut");
$stats_statut = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bien le plus cher
$stmt = $pdo->query("SELECT * FROM biens ORDER BY prix DESC LIMIT 1");
$bien_cher = $stmt->fetch(PDO::FETCH_ASSOC);

// Bien le moins cher
$stmt = $pdo->query("SELECT * FROM biens ORDER BY prix ASC LIMIT 1");
$bien_moins_cher = $stmt->fetch(PDO::FETCH_ASSOC);

// Prix moyen global
$stmt = $pdo->query("SELECT ROUND(AVG(prix), 0) as moyenne FROM biens");
$prix_moyen = $stmt->fetch(PDO::FETCH_ASSOC)['moyenne'];

// Demandes par bien (les plus populaires)
$stmt = $pdo->query("SELECT b.titre, b.ville, COUNT(d.id) as nb_demandes FROM biens b LEFT JOIN demandes d ON b.id = d.bien_id GROUP BY b.id ORDER BY nb_demandes DESC LIMIT 5");
$biens_populaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques — Ymmo</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<nav class="navbar">
    <a class="navbar-brand" href="index.php">YM<span>MO</span></a>
    <div style="display:flex;gap:1rem;">
        <a href="index.php" class="btn-nav">Annonces</a>
        <a href="login.php" class="btn-nav">Espace agent</a>
    </div>
</nav>

<div style="padding:3rem 2rem;max-width:1200px;margin:0 auto;">

    <h1 style="font-family:'Playfair Display',serif;font-size:2.5rem;margin-bottom:0.5rem;">
        Analyse du <span style="color:var(--gold)">marché</span>
    </h1>
    <p style="color:var(--text-muted);margin-bottom:3rem;">Statistiques et tendances du marché immobilier Ymmo</p>

    <!-- KPIs -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.5rem;margin-bottom:3rem;">

        <div style="background:var(--dark-3);border:1px solid rgba(201,169,110,0.2);padding:1.5rem;">
            <div style="color:var(--text-muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.5rem;">Prix moyen</div>
            <div style="font-size:1.8rem;color:var(--gold);font-weight:500;"><?= number_format($prix_moyen, 0, ',', ' ') ?> €</div>
        </div>

        <div style="background:var(--dark-3);border:1px solid rgba(201,169,110,0.2);padding:1.5rem;">
            <div style="color:var(--text-muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.5rem;">Bien le + cher</div>
            <div style="font-size:1.8rem;color:var(--gold);font-weight:500;"><?= number_format($bien_cher['prix'], 0, ',', ' ') ?> €</div>
            <div style="color:var(--text-muted);font-size:0.8rem;margin-top:0.3rem;"><?= htmlspecialchars($bien_cher['titre']) ?></div>
        </div>

        <div style="background:var(--dark-3);border:1px solid rgba(201,169,110,0.2);padding:1.5rem;">
            <div style="color:var(--text-muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.5rem;">Bien le - cher</div>
            <div style="font-size:1.8rem;color:var(--gold);font-weight:500;"><?= number_format($bien_moins_cher['prix'], 0, ',', ' ') ?> €</div>
            <div style="color:var(--text-muted);font-size:0.8rem;margin-top:0.3rem;"><?= htmlspecialchars($bien_moins_cher['titre']) ?></div>
        </div>

        <div style="background:var(--dark-3);border:1px solid rgba(201,169,110,0.2);padding:1.5rem;">
            <div style="color:var(--text-muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.5rem;">Total biens</div>
            <div style="font-size:1.8rem;color:var(--gold);font-weight:500;">
                <?php $total = array_sum(array_column($stats_statut, 'nombre')); echo $total; ?>
            </div>
        </div>

    </div>

    <!-- Graphiques -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:3rem;">

        <!-- Prix moyen par type -->
        <div style="background:var(--dark-3);border:1px solid rgba(255,255,255,0.06);padding:1.5rem;">
            <h3 style="font-family:'Playfair Display',serif;font-size:1.1rem;margin-bottom:1.5rem;color:var(--gold)">Prix moyen par type</h3>
            <canvas id="chartType"></canvas>
        </div>

        <!-- Biens par ville -->
        <div style="background:var(--dark-3);border:1px solid rgba(255,255,255,0.06);padding:1.5rem;">
            <h3 style="font-family:'Playfair Display',serif;font-size:1.1rem;margin-bottom:1.5rem;color:var(--gold)">Biens par ville</h3>
            <canvas id="chartVille"></canvas>
        </div>

    </div>

    <!-- Biens populaires -->
    <div style="background:var(--dark-3);border:1px solid rgba(255,255,255,0.06);padding:1.5rem;margin-bottom:3rem;">
        <h3 style="font-family:'Playfair Display',serif;font-size:1.1rem;margin-bottom:1.5rem;color:var(--gold)">Biens les plus demandés</h3>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.1);">
                    <th style="text-align:left;padding:0.8rem;color:var(--text-muted);font-size:0.8rem;text-transform:uppercase;letter-spacing:1px;font-weight:400;">Bien</th>
                    <th style="text-align:left;padding:0.8rem;color:var(--text-muted);font-size:0.8rem;text-transform:uppercase;letter-spacing:1px;font-weight:400;">Ville</th>
                    <th style="text-align:right;padding:0.8rem;color:var(--text-muted);font-size:0.8rem;text-transform:uppercase;letter-spacing:1px;font-weight:400;">Demandes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($biens_populaires as $b): ?>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                    <td style="padding:1rem 0.8rem;"><?= htmlspecialchars($b['titre']) ?></td>
                    <td style="padding:1rem 0.8rem;color:var(--text-muted);">📍 <?= htmlspecialchars($b['ville']) ?></td>
                    <td style="padding:1rem 0.8rem;text-align:right;color:var(--gold);font-weight:500;"><?= $b['nb_demandes'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
const gold = '#C9A96E';
const goldLight = '#E8C98A';
const dark3 = '#1C2333';
const textMuted = '#8892A4';

Chart.defaults.color = textMuted;
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

// Graphique prix par type
const ctxType = document.getElementById('chartType').getContext('2d');
new Chart(ctxType, {
    type: 'bar',
    data: {
        labels: [<?php foreach($stats_type as $s) echo '"'.ucfirst($s['type']).'",'; ?>],
        datasets: [{
            label: 'Prix moyen (€)',
            data: [<?php foreach($stats_type as $s) echo $s['prix_moyen'].','; ?>],
            backgroundColor: 'rgba(201,169,110,0.3)',
            borderColor: gold,
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: 'rgba(255,255,255,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Graphique biens par ville
const ctxVille = document.getElementById('chartVille').getContext('2d');
new Chart(ctxVille, {
    type: 'doughnut',
    data: {
        labels: [<?php foreach($stats_ville as $s) echo '"'.htmlspecialchars($s['ville']).'",'; ?>],
        datasets: [{
            data: [<?php foreach($stats_ville as $s) echo $s['nombre'].','; ?>],
            backgroundColor: ['rgba(201,169,110,0.8)','rgba(201,169,110,0.5)','rgba(201,169,110,0.3)','rgba(201,169,110,0.15)'],
            borderColor: dark3,
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 20 } }
        }
    }
});
</script>

</body>
</html>