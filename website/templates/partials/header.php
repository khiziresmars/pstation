<header class="header">
    <div class="container">
        <nav class="nav">
            <!-- Logo -->
            <a href="<?= $view->url('/') ?>" class="logo">
                <img src="/images/logo.svg" alt="<?= $app_name ?>" width="150" height="40">
            </a>

            <!-- Desktop Navigation -->
            <ul class="nav-menu">
                <li><a href="<?= $view->url('/') ?>" class="<?= $view->isActive('/') && !$view->isActive('/yachts') && !$view->isActive('/tours') ? 'active' : '' ?>"><?= $t['nav_home'] ?></a></li>
                <li><a href="<?= $view->url('/yachts') ?>" class="<?= $view->isActive('/yachts') || $view->isActive('/vessels') ? 'active' : '' ?>"><?= $t['nav_yachts'] ?></a></li>
                <li><a href="<?= $view->url('/tours') ?>" class="<?= $view->isActive('/tours') ? 'active' : '' ?>"><?= $t['nav_tours'] ?></a></li>
                <li><a href="<?= $view->url('/about') ?>" class="<?= $view->isActive('/about') ? 'active' : '' ?>"><?= $t['nav_about'] ?></a></li>
                <li><a href="<?= $view->url('/contact') ?>" class="<?= $view->isActive('/contact') ? 'active' : '' ?>"><?= $t['nav_contact'] ?></a></li>
            </ul>

            <!-- Right side -->
            <div class="nav-actions">
                <!-- Language switcher -->
                <div class="lang-switcher">
                    <button class="lang-current" aria-label="Select language">
                        <?= strtoupper($lang) ?>
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                            <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" fill="none"/>
                        </svg>
                    </button>
                    <div class="lang-dropdown">
                        <a href="/lang/en" class="<?= $lang === 'en' ? 'active' : '' ?>">English</a>
                        <a href="/lang/ru" class="<?= $lang === 'ru' ? 'active' : '' ?>">Русский</a>
                        <a href="/lang/th" class="<?= $lang === 'th' ? 'active' : '' ?>">ไทย</a>
                    </div>
                </div>

                <!-- Telegram button -->
                <a href="https://t.me/<?= $_ENV['TELEGRAM_BOT_USERNAME'] ?? 'phuketyachts_bot' ?>" class="btn btn-primary btn-telegram" target="_blank" rel="noopener">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                    </svg>
                    <span class="btn-text"><?= $t['open_telegram'] ?></span>
                </a>

                <!-- Mobile menu toggle -->
                <button class="menu-toggle" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    </div>

    <!-- Mobile menu -->
    <div class="mobile-menu">
        <ul>
            <li><a href="<?= $view->url('/') ?>"><?= $t['nav_home'] ?></a></li>
            <li><a href="<?= $view->url('/yachts') ?>"><?= $t['nav_yachts'] ?></a></li>
            <li><a href="<?= $view->url('/tours') ?>"><?= $t['nav_tours'] ?></a></li>
            <li><a href="<?= $view->url('/about') ?>"><?= $t['nav_about'] ?></a></li>
            <li><a href="<?= $view->url('/contact') ?>"><?= $t['nav_contact'] ?></a></li>
            <li><a href="<?= $view->url('/faq') ?>"><?= $t['nav_faq'] ?></a></li>
        </ul>
        <div class="mobile-lang">
            <a href="/lang/en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
            <a href="/lang/ru" class="<?= $lang === 'ru' ? 'active' : '' ?>">RU</a>
            <a href="/lang/th" class="<?= $lang === 'th' ? 'active' : '' ?>">TH</a>
        </div>
    </div>
</header>
