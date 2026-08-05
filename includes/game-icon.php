<?php
$gameOgTitle = $gameOgTitle ?? 'Capsule';
$gameOgDescription = $gameOgDescription ?? 'Play this game on Capsule.';
$gameOgUrl = $gameOgUrl ?? 'https://capsule.rf.gd/games/';
$gameOgImage = $gameOgImage ?? '/assets/images/capsuleTemplate.png';
?>
<!-- Game-specific Open Graph metadata -->
<meta property="og:site_name" content="Capsule">
<meta property="og:url" content="<?php echo htmlspecialchars($gameOgUrl, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($gameOgTitle, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($gameOgDescription, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:type" content="website">
<meta property="og:image" content="<?php echo htmlspecialchars($gameOgImage, ENT_QUOTES, 'UTF-8'); ?>">

<meta name="description" content="<?php echo htmlspecialchars($gameOgDescription, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($gameOgTitle, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($gameOgDescription, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($gameOgImage, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="theme-color" content="#FFFFFF">
<meta name="application-name" content="Capsule">
<meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($gameOgTitle, ENT_QUOTES, 'UTF-8'); ?>">

<link rel="icon" type="image/x-icon" href="/assets/images/favicons/favicon.ico">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicons/favicon-16x16.png">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicons/favicon-32x32.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/favicons/apple-touch-icon.png">
