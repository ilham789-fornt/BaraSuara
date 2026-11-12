<?php
/**
 * Layout pembuka halaman (head + header + flash message).
 * Variabel: $pageTitle, $pageDescription, $activePage, $extraHead (opsional, HTML mentah)
 */
$pageTitle = $pageTitle ?? 'Barasuara';
$pageDescription = $pageDescription ?? 'Portal Resmi Fans Barasuara.';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($pageTitle) ?> - Barasuara | Portal Fans Resmi</title>

    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>
    <?= $extraHead ?? '' ?>
</head>

<body>
    <?php require __DIR__ . '/nav.php'; ?>
    <?php require __DIR__ . '/flash.php'; ?>

