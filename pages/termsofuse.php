<?php
declare(strict_types=1);

// Oturum başlatılmamışsa başlatıyoruz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proje bağımlılıkları ve header yüklemeleri
require_once __DIR__ . '/../api/config.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Capsule</title>
    <?php include ROOT_PATH . 'includes/meta.php'; ?>
    <?php include ROOT_PATH . 'includes/icon.php'; ?>
    <link rel="stylesheet" href="/assets/css/Capsule.css">
    
    <style>
        .terms-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border: 1px solid #c3c3c3;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
            font-family: "Segoe UI", Arial, sans-serif;
            color: #333;
        }
        .terms-header {
            border-bottom: 2px solid #7E7E7E;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .terms-header h1 {
            margin: 0;
            color: #000000;
            font-size: 24px;
            font-weight: bold;
        }
        .terms-header .last-updated {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }
        .terms-section {
            margin-bottom: 25px;
        }
        .terms-section h2 {
            font-size: 16px;
            color: #222;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .terms-section p {
            font-size: 13px;
            line-height: 1.6;
            margin: 0 0 10px 0;
            color: #444;
        }
        .terms-section ul {
            margin: 5px 0 10px 20px;
            padding: 0;
            font-size: 13px;
            line-height: 1.6;
            color: #444;
        }
        .terms-section li {
            margin-bottom: 5px;
        }
        .terms-footer {
            border-top: 1px solid #eee;
            padding-top: 15px;
            font-size: 12px;
            color: #666;
            text-align: right;
        }
        .btn-back {
            display: inline-block;
            background: #0055b3;
            color: #fff;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 3px;
        }
        .btn-back:hover {
            background: #003d80;
        }
    </style>
</head>
<body>
    <?php include ROOT_PATH . 'includes/header.php'; ?>

    <div class="main-container">
        <div class="terms-container">
            <div class="terms-header">
                <h1>Capsule Terms of Service</h1>
                <div class="last-updated">Last Updated: July 2026</div>
            </div>

            <div class="terms-section">
                <h2>1. Acceptance of Terms</h2>
                <p>By registering, accessing, or using the Capsule platform, you agree to be bound by these Terms of Service. If you do not agree with any part of these terms, you must not use our services.</p>
            </div>

            <div class="terms-section">
                <h2>2. User Conduct & Community Rules</h2>
                <p>To keep Capsule safe and enjoyable for everyone, all users must adhere to the following rules:</p>
                <ul>
                    <li><strong>No Harassment or Hate Speech:</strong> Bullying, threatening, or harassing other users is strictly prohibited.</li>
                    <li><strong>No Exploiting or Cheating:</strong> Attempting to reverse engineer, bypass access controls, or exploit platform vulnerabilities will result in an immediate permanent ban.</li>
                    <li><strong>Appropriate Content:</strong> Do not upload, post, or share inappropriate, illegal, or copyrighted assets without proper authorization.</li>
                    <li><strong>Account Safety:</strong> You are responsible for maintaining the security of your account credentials. Sharing or selling accounts is forbidden.</li>
                </ul>
            </div>

            <div class="terms-section">
                <h2>3. Moderation & Enforcement</h2>
                <p>Our moderation team actively enforces community guidelines. Depending on the severity of a violation, the following actions may be taken:</p>
                <ul>
                    <li><strong>Official Warnings:</strong> Issued for minor infractions. Users must acknowledge the warning to resume normal account activity.</li>
                    <li><strong>Temporary Suspensions:</strong> Accounts may be restricted for a specified duration. Access will remain locked until the penalty expires and terms are re-acknowledged.</li>
                    <li><strong>Account Termination (Permanent Ban):</strong> Reserved for severe or repeated offenses. Access to Capsule will be permanently revoked.</li>
                </ul>
            </div>

            <div class="terms-section">
                <h2>4. Intellectual Property</h2>
                <p>All platform infrastructure, branding, and original source code remain the exclusive property of Capsule. User-submitted assets remain the property of their respective creators, but by uploading content, you grant Capsule a non-exclusive license to host and display it within the service.</p>
            </div>

            <div class="terms-section">
                <h2>5. Modifications to Service</h2>
                <p>Capsule reserves the right to modify, suspend, or discontinue any aspect of the service at any time without prior notice. Terms of Service may be updated periodically, and continued use of the platform constitutes acceptance of updated terms.</p>
            </div>

            <div class="terms-footer">
                <a href="javascript:history.back()" class="btn-back">&laquo; Back</a>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . 'includes/bottom.php'; ?>
</body>
</html>