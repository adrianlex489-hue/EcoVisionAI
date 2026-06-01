<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoVision AI - <?php echo $titulo ?? 'Sistema de Clasificación'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/animations.css" rel="stylesheet">
    <?php if (isset($css_extra)): ?>
        <link href="<?php echo BASE_URL; ?>/assets/css/<?php echo $css_extra; ?>" rel="stylesheet">
    <?php endif; ?>
    <?php if (isset($css_auth)): ?>
        <link href="<?php echo BASE_URL; ?>/assets/css/auth.css" rel="stylesheet">
    <?php endif; ?>
    <?php if (isset($css_admin)): ?>
        <link href="<?php echo BASE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
