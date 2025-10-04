<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle ?? 'Système de Présence'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="header">
        <h1><?php echo $headerTitle ?? 'Gestion'; ?></h1>
        <div class="nav-links">
            <?php if (isset($_SESSION['teacher_id'])): ?>
                <a href="/dashboard.php">Tableau de Bord</a>
                <a href="/controllers/auth/logout.php" class="logout">Déconnexion</a>
            <?php endif; ?>
        </div>
    </div>
