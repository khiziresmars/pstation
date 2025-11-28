<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $seo->render() ?>

    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <!-- Critical CSS inline -->
    <style>
        :root {
            --primary: #0891b2;
            --primary-dark: #0e7490;
            --secondary: #f59e0b;
            --dark: #1e293b;
            --gray: #64748b;
            --light: #f1f5f9;
            --white: #ffffff;
            --success: #10b981;
            --error: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: var(--white);
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        img { max-width: 100%; height: auto; }

        a { color: var(--primary); text-decoration: none; }
        a:hover { color: var(--primary-dark); }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            color: var(--white);
        }

        .btn-secondary {
            background: var(--secondary);
            color: var(--white);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--white);
        }

        /* Hide lazy images until loaded */
        img.lazy { opacity: 0; transition: opacity 0.3s; }
        img.lazy.loaded { opacity: 1; }
    </style>

    <!-- Main CSS (loaded async) -->
    <link rel="stylesheet" href="<?= $view->asset('/css/main.css') ?>">

    <!-- Theme color for mobile browsers -->
    <meta name="theme-color" content="#0891b2">
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <!-- Main content -->
    <main>
        <?= $content ?>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <!-- Telegram CTA -->
    <?php include __DIR__ . '/../partials/telegram-cta.php'; ?>

    <!-- Back to top -->
    <button id="back-to-top" class="back-to-top" aria-label="Back to top">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 15l-6-6-6 6"/>
        </svg>
    </button>

    <!-- JavaScript -->
    <script src="<?= $view->asset('/js/main.js') ?>" defer></script>
</body>
</html>
