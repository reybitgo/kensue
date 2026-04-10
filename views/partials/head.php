<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? setting('site_name', APP_NAME)) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="<?= APP_URL ?>/assets/img/favicon.svg" type="image/svg+xml">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <!-- CSS -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/layout.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/components.css">
  <?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/<?= e($css) ?>">
  <?php endforeach; endif; ?>
</head>
<body>
