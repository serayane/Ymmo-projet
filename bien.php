<?php
require 'config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) header('Location: index.php');

$stmt = $pdo->prepare("SELECT b.*, a.nom as agence_nom, a.telephone as agence_tel FROM biens b LEFT JOIN agences a ON b.agence_id = a.id WHERE b.id = :id");
$stmt->execute([':id' => $id]);
$bien = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bien) header('Location: index.php');

// Récupérer toutes les photos du bien
$stmt2 = $pdo->prepare("SELECT * FROM biens_photos WHERE bien_id = :id ORDER BY ordre ASC");
$stmt2->execute([':id' => $id]);
$photos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Si pas de photos dans la nouvelle table, utiliser l'ancienne
if (empty($photos) && $bien['photo']) {
    $photos = [['photo' => $bien['photo']]];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($bien['titre']) ?> — Ymmo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* CAROUSEL */
        .carousel {
            position: relative;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            overflow: hidden;
            background: var(--dark-2);
            border-radius: 4px;
        }

        .carousel-track {
            display: flex;
            transition: transform 0.4s ease;
        }

        .carousel-slide {
            min-width: 100%;
            height: 520px;
            cursor: zoom-in;
            overflow: hidden;
        }

        .carousel-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .carousel-slide:hover img { transform: scale(1.02); }

        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(10,15,30,0.8);
            border: 1px solid rgba(201,169,110,0.4);
            color: var(--gold);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s;
            z-index: 10;
        }

        .carousel-btn:hover { background: var(--gold); color: var(--dark); }
        .carousel-btn.prev { left: 1rem; }
        .carousel-btn.next { right: 1rem; }

        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            background: var(--dark-2);
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(201,169,110,0.3);
            cursor: pointer;
            transition: background 0.3s;
            border: none;
        }

        .dot.active { background: var(--gold); }

        .carousel-thumbs {
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem;
            background: var(--dark-2);
            overflow-x: auto;
            justify-content: center;
        }

        .thumb {
            width: 80px;
            height: 60px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border 0.3s;
            flex-shrink: 0;
            border-radius: 2px;
            opacity: 0.6;
        }

        .thumb.active {
            border-color: var(--gold);
            opacity: 1;
        }

        /* LIGHTBOX */
        .lightbox {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.97);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            cursor: zoom-out;
        }

        .lightbox.active { display: flex; }

        .lightbox img {
            max-width: 92%;
            max-height: 92vh;
            object-fit: contain;
            border: 1px solid rgba(201,169,110,0.3);
        }

        .lightbox-close {
            position: absolute;
            top: 1.5rem;
            right: 2rem;
            color: var(--gold);
            font-size: 2rem;
            cursor: pointer;
            z-index: 10000;
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(10,15,30,0.8);
            border: 1px solid rgba(201,169,110,0.4);
            color: var(--gold);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            z-index: 10000;
        }

        .lightbox-nav.prev { left: 1rem; }
        .lightbox-nav.next { right: 1rem; }
        .lightbox-counter {
            position: absolute;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            color: var(--text-muted);
            font-size: 0.85rem;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

<!-- Lightbox -->
<div class="lightbox" id="lightbox">
    <span class="lightbox-close" onclick="closeLightbox()">✕</span>
    <button class="lightbox-nav prev" onclick="lightboxNav(-1)">←</button>
    <img id="lightbox-img" src="" alt="">
    <button class="lightbox-nav next" onclick="lightboxNav(1)">→</button>
    <div class="lightbox-counter" id="lightbox-counter"></div>
</div>

<nav class="navbar">
    <a class="navbar-brand" href="index.php">YM<span>MO</span></a>
    <div style="display:flex;align-items:center;gap:1rem;">
        <a href="stats.php" class="btn-nav">Statistiques</a>
        <a href="login.php" class="btn-nav">Espace agent</a>
    </div>
</nav>

<!-- CAROUSEL -->
<div style="background:var(--dark-2);padding:1.5rem 0;">
    <div class="carousel" id="carousel">
        <div class="carousel-track" id="carouselTrack">
            <?php foreach ($photos as $i => $p): ?>
            <div class="carousel-slide" onclick="openLightbox(<?= $i ?>)">
                <img src="uploads/<?= htmlspecialchars($p['photo']) ?>" alt="">
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($photos) > 1): ?>
        <button class="carousel-btn prev" onclick="moveCarousel(-1)">←</button>
        <button class="carousel-btn next" onclick="moveCarousel(1)">→</button>
        <?php endif; ?>
    </div>

    <?php if (count($photos) > 1): ?>
    <!-- Miniatures -->
    <div class="carousel-thumbs" id="thumbs">
        <?php foreach ($photos as $i => $p): ?>
        <img src="uploads/<?= htmlspecialchars($p['photo']) ?>"
             class="thumb <?= $i === 0 ? 'active' : '' ?>"
             onclick="goToSlide(<?= $i ?>)" alt="">
        <?php endforeach; ?>
    </div>

    <!-- Dots -->
    <div class="carousel-dots" id="dots">
        <?php foreach ($photos as $i => $p): ?>
        <button class="dot <?= $i === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $i ?>)"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="detail-content">
    <div>
        <a href="index.php" class="back-link">← Retour aux annonces</a>
        <h1 class="detail-title"><?= htmlspecialchars($bien['titre']) ?></h1>
        <p class="detail-location">📍 <?= htmlspecialchars($bien['ville']) ?><?= $bien['adresse'] ? ' — ' . htmlspecialchars($bien['adresse']) : '' ?></p>
        <div class="detail-price"><?= number_format($bien['prix'], 0, ',', ' ') ?> €</div>

        <div class="detail-specs">
            <div class="spec-item">
                <div class="spec-label">Surface</div>
                <div class="spec-value"><?= $bien['surface'] ?> m²</div>
            </div>
            <div class="spec-item">
                <div class="spec-label">Type</div>
                <div class="spec-value"><?= ucfirst($bien['type']) ?></div>
            </div>
            <div class="spec-item">
                <div class="spec-label">Statut</div>
                <div class="spec-value" style="color:var(--success)"><?= ucfirst($bien['statut']) ?></div>
            </div>
            <?php if ($bien['agence_nom']): ?>
            <div class="spec-item">
                <div class="spec-label">Agence</div>
                <div class="spec-value"><?= htmlspecialchars($bien['agence_nom']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($bien['description']): ?>
        <div>
            <p style="color:var(--text-muted);line-height:1.8;"><?= nl2br(htmlspecialchars($bien['description'])) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="sidebar-card">
        <div class="sidebar-title">Demander une visite</div>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">Votre demande a bien été envoyée !</div>
        <?php endif; ?>
        <form method="POST" action="contact.php">
            <input type="hidden" name="bien_id" value="<?= $bien['id'] ?>">
            <div class="form-group">
                <label>Votre nom</label>
                <input type="text" name="nom" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="message" rows="4" placeholder="Je souhaite visiter ce bien..."></textarea>
            </div>
            <button type="submit" class="btn-gold">Envoyer la demande</button>
        </form>
        <?php if ($bien['agence_tel']): ?>
        <p style="text-align:center;margin-top:1.5rem;color:var(--text-muted);font-size:0.85rem;">
            Ou appelez le <strong style="color:var(--gold)"><?= htmlspecialchars($bien['agence_tel']) ?></strong>
        </p>
        <?php endif; ?>
    </div>
</div>

<script>
const photos = <?= json_encode(array_column($photos, 'photo')) ?>;
let current = 0;
let lightboxIndex = 0;

function updateCarousel() {
    document.getElementById('carouselTrack').style.transform = `translateX(-${current * 100}%)`;
    document.querySelectorAll('.dot').forEach((d, i) => d.classList.toggle('active', i === current));
    document.querySelectorAll('.thumb').forEach((t, i) => t.classList.toggle('active', i === current));
}

function moveCarousel(dir) {
    current = (current + dir + photos.length) % photos.length;
    updateCarousel();
}

function goToSlide(index) {
    current = index;
    updateCarousel();
}

function openLightbox(index) {
    lightboxIndex = index;
    updateLightbox();
    document.getElementById('lightbox').classList.add('active');
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
}

function lightboxNav(dir) {
    lightboxIndex = (lightboxIndex + dir + photos.length) % photos.length;
    updateLightbox();
    event.stopPropagation();
}

function updateLightbox() {
    document.getElementById('lightbox-img').src = 'uploads/' + photos[lightboxIndex];
    document.getElementById('lightbox-counter').textContent = (lightboxIndex + 1) + ' / ' + photos.length;
}

document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) closeLightbox();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') { moveCarousel(-1); if (document.getElementById('lightbox').classList.contains('active')) lightboxNav(-1); }
    if (e.key === 'ArrowRight') { moveCarousel(1); if (document.getElementById('lightbox').classList.contains('active')) lightboxNav(1); }
});
</script>

</body>
</html>