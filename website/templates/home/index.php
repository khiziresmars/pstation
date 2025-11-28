<?php $view->layout('main'); ?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-bg">
        <picture>
            <source type="image/webp" srcset="/images/hero-phuket.webp">
            <img src="/images/hero-phuket.jpg" alt="Yacht in Phuket" loading="eager">
        </picture>
        <div class="hero-overlay"></div>
    </div>
    <div class="container">
        <div class="hero-content">
            <h1><?= $t['hero_title'] ?></h1>
            <p><?= $t['hero_subtitle'] ?></p>
            <div class="hero-buttons">
                <a href="<?= $view->url('/yachts') ?>" class="btn btn-primary btn-lg"><?= $t['hero_cta'] ?></a>
                <a href="<?= $view->url('/tours') ?>" class="btn btn-outline btn-lg"><?= $t['nav_tours'] ?></a>
            </div>
        </div>
    </div>

    <!-- Quick search -->
    <div class="hero-search">
        <div class="container">
            <form class="search-form" action="/yachts" method="GET">
                <div class="search-field">
                    <label><?= $t['filter_all'] ?></label>
                    <select name="type">
                        <option value=""><?= $t['filter_all'] ?></option>
                        <option value="yacht"><?= $t['filter_yacht'] ?></option>
                        <option value="speedboat"><?= $t['filter_speedboat'] ?></option>
                        <option value="catamaran"><?= $t['filter_catamaran'] ?></option>
                    </select>
                </div>
                <div class="search-field">
                    <label><?= $t['select_date'] ?></label>
                    <input type="date" name="date" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="search-field">
                    <label><?= $t['guests'] ?></label>
                    <select name="guests">
                        <?php for ($i = 1; $i <= 30; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?> <?= $t['guests'] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary"><?= $t['search'] ?></button>
            </form>
        </div>
    </div>
</section>

<!-- Featured Yachts -->
<section class="section featured-vessels">
    <div class="container">
        <div class="section-header">
            <h2><?= $t['featured_yachts'] ?></h2>
            <a href="<?= $view->url('/yachts') ?>" class="link-arrow"><?= $t['view_all'] ?></a>
        </div>

        <div class="vessels-grid">
            <?php foreach ($vessels as $vessel): ?>
                <?php
                $images = json_decode($vessel['images'] ?? '[]', true);
                $image = $images[0] ?? '/images/placeholder-yacht.jpg';
                ?>
                <article class="vessel-card">
                    <a href="<?= $view->url('/yachts/' . $vessel['slug']) ?>" class="vessel-image">
                        <img src="<?= $image ?>" alt="<?= $view->e($vessel['name']) ?>" loading="lazy">
                        <span class="vessel-type"><?= ucfirst($vessel['type']) ?></span>
                    </a>
                    <div class="vessel-content">
                        <h3><a href="<?= $view->url('/yachts/' . $vessel['slug']) ?>"><?= $view->e($vessel['name']) ?></a></h3>
                        <div class="vessel-specs">
                            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> <?= $vessel['capacity'] ?> <?= $t['guests'] ?></span>
                            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg> <?= $vessel['length_meters'] ?>m</span>
                        </div>
                        <?php if ($vessel['rating']): ?>
                            <div class="vessel-rating">
                                <span class="stars">★</span>
                                <span><?= number_format($vessel['rating'], 1) ?></span>
                                <span class="count">(<?= $vessel['review_count'] ?? 0 ?>)</span>
                            </div>
                        <?php endif; ?>
                        <div class="vessel-price">
                            <span class="from"><?= $t['from'] ?></span>
                            <span class="amount"><?= $view->price($vessel['price_per_hour']) ?></span>
                            <span class="period"><?= $t['per_hour'] ?></span>
                        </div>
                        <a href="<?= $view->url('/book/vessel/' . $vessel['slug']) ?>" class="btn btn-primary btn-block"><?= $t['book_now'] ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Tours -->
<section class="section featured-tours bg-light">
    <div class="container">
        <div class="section-header">
            <h2><?= $t['featured_tours'] ?></h2>
            <a href="<?= $view->url('/tours') ?>" class="link-arrow"><?= $t['view_all'] ?></a>
        </div>

        <div class="tours-grid">
            <?php foreach ($tours as $tour): ?>
                <?php
                $images = json_decode($tour['images'] ?? '[]', true);
                $image = $images[0] ?? '/images/placeholder-tour.jpg';
                ?>
                <article class="tour-card">
                    <a href="<?= $view->url('/tours/' . $tour['slug']) ?>" class="tour-image">
                        <img src="<?= $image ?>" alt="<?= $view->e($tour['name']) ?>" loading="lazy">
                        <span class="tour-duration"><?= $tour['duration_hours'] ?> <?= $t['hours'] ?></span>
                    </a>
                    <div class="tour-content">
                        <span class="tour-category"><?= $view->e($tour['category']) ?></span>
                        <h3><a href="<?= $view->url('/tours/' . $tour['slug']) ?>"><?= $view->e($tour['name']) ?></a></h3>
                        <p><?= $view->truncate($tour['description'], 100) ?></p>
                        <?php if ($tour['rating']): ?>
                            <div class="tour-rating">
                                <span class="stars">★</span>
                                <span><?= number_format($tour['rating'], 1) ?></span>
                                <span class="count">(<?= $tour['review_count'] ?? 0 ?>)</span>
                            </div>
                        <?php endif; ?>
                        <div class="tour-price">
                            <span class="from"><?= $t['from'] ?></span>
                            <span class="amount"><?= $view->price($tour['price_adult']) ?></span>
                            <span class="period"><?= $t['per_person'] ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="section why-us">
    <div class="container">
        <h2 class="section-title center"><?= $t['why_choose_us'] ?></h2>

        <div class="features-grid">
            <div class="feature">
                <div class="feature-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                        <line x1="4" y1="22" x2="4" y2="15"/>
                    </svg>
                </div>
                <h3><?= $t['why_1_title'] ?></h3>
                <p><?= $t['why_1_desc'] ?></p>
            </div>

            <div class="feature">
                <div class="feature-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <h3><?= $t['why_2_title'] ?></h3>
                <p><?= $t['why_2_desc'] ?></p>
            </div>

            <div class="feature">
                <div class="feature-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                        <path d="M8 14h.01"/>
                        <path d="M12 14h.01"/>
                        <path d="M16 14h.01"/>
                    </svg>
                </div>
                <h3><?= $t['why_3_title'] ?></h3>
                <p><?= $t['why_3_desc'] ?></p>
            </div>

            <div class="feature">
                <div class="feature-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
                <h3><?= $t['why_4_title'] ?></h3>
                <p><?= $t['why_4_desc'] ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Reviews -->
<?php if (!empty($reviews)): ?>
<section class="section reviews bg-light">
    <div class="container">
        <h2 class="section-title center"><?= $t['reviews'] ?></h2>

        <div class="reviews-slider">
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-rating">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <span class="star <?= $i < $review['rating'] ? 'filled' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <p class="review-text">"<?= $view->e($view->truncate($review['comment'], 200)) ?>"</p>
                    <div class="review-author">
                        <div class="author-avatar"><?= strtoupper(substr($review['first_name'], 0, 1)) ?></div>
                        <div class="author-info">
                            <strong><?= $view->e($review['first_name']) ?> <?= $view->e(substr($review['last_name'] ?? '', 0, 1)) ?>.</strong>
                            <span><?= $view->date($review['created_at'], 'M Y') ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
