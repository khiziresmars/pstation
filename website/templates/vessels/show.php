<?php
$view->layout('main');
$images = json_decode($vessel['images'] ?? '[]', true);
?>

<!-- Vessel Header -->
<section class="vessel-hero">
    <div class="vessel-gallery">
        <?php if (!empty($images)): ?>
            <div class="gallery-main">
                <img src="<?= $images[0] ?>" alt="<?= $view->e($vessel['name']) ?>" id="mainImage">
            </div>
            <?php if (count($images) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach ($images as $i => $img): ?>
                        <button class="thumb <?= $i === 0 ? 'active' : '' ?>" onclick="setMainImage('<?= $img ?>', this)">
                            <img src="<?= $img ?>" alt="<?= $view->e($vessel['name']) ?> - <?= $i + 1 ?>" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="gallery-main">
                <img src="/images/placeholder-yacht.jpg" alt="<?= $view->e($vessel['name']) ?>">
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="vessel-detail">
    <div class="container">
        <div class="vessel-layout">
            <!-- Main Content -->
            <div class="vessel-main">
                <div class="vessel-title-block">
                    <span class="vessel-type-badge"><?= ucfirst($vessel['type']) ?></span>
                    <h1><?= $view->e($vessel['name']) ?></h1>
                    <?php if ($vessel['rating']): ?>
                        <div class="vessel-rating-lg">
                            <span class="stars">★</span>
                            <span class="rating"><?= number_format($vessel['rating'], 1) ?></span>
                            <span class="count">(<?= $vessel['review_count'] ?? 0 ?> <?= $t['reviews'] ?>)</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab active" data-tab="overview"><?= $t['overview'] ?></button>
                    <button class="tab" data-tab="specs"><?= $t['specifications'] ?></button>
                    <button class="tab" data-tab="amenities"><?= $t['amenities'] ?></button>
                    <button class="tab" data-tab="reviews"><?= $t['reviews'] ?></button>
                </div>

                <!-- Overview Tab -->
                <div class="tab-content active" id="overview">
                    <div class="vessel-description">
                        <?= nl2br($view->e($vessel['description'])) ?>
                    </div>

                    <div class="quick-specs">
                        <div class="spec">
                            <span class="spec-label"><?= $t['capacity'] ?></span>
                            <span class="spec-value"><?= $vessel['capacity'] ?> <?= $t['guests'] ?></span>
                        </div>
                        <div class="spec">
                            <span class="spec-label"><?= $t['length'] ?></span>
                            <span class="spec-value"><?= $vessel['length_meters'] ?>m</span>
                        </div>
                        <?php if ($vessel['year_built']): ?>
                            <div class="spec">
                                <span class="spec-label"><?= $t['year'] ?></span>
                                <span class="spec-value"><?= $vessel['year_built'] ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="included-badges">
                        <?php if ($vessel['captain_included']): ?>
                            <span class="badge badge-success">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                <?= $t['captain_included'] ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($vessel['fuel_included']): ?>
                            <span class="badge badge-success">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                <?= $t['fuel_included'] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Specs Tab -->
                <div class="tab-content" id="specs">
                    <table class="specs-table">
                        <tr><td><?= $t['capacity'] ?></td><td><?= $vessel['capacity'] ?> <?= $t['guests'] ?></td></tr>
                        <tr><td><?= $t['length'] ?></td><td><?= $vessel['length_meters'] ?> meters</td></tr>
                        <?php if ($vessel['year_built']): ?>
                            <tr><td><?= $t['year'] ?></td><td><?= $vessel['year_built'] ?></td></tr>
                        <?php endif; ?>
                        <tr><td><?= $t['captain_included'] ?></td><td><?= $vessel['captain_included'] ? 'Yes' : 'No' ?></td></tr>
                        <tr><td><?= $t['fuel_included'] ?></td><td><?= $vessel['fuel_included'] ? 'Yes' : 'No' ?></td></tr>
                    </table>
                </div>

                <!-- Amenities Tab -->
                <div class="tab-content" id="amenities">
                    <?php $amenities = json_decode($vessel['amenities'] ?? '[]', true); ?>
                    <?php if (!empty($amenities)): ?>
                        <ul class="amenities-list">
                            <?php foreach ($amenities as $amenity): ?>
                                <li>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    <?= $view->e($amenity) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?= $t['no_results'] ?></p>
                    <?php endif; ?>
                </div>

                <!-- Reviews Tab -->
                <div class="tab-content" id="reviews">
                    <?php if (!empty($reviews)): ?>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="review-author">
                                            <div class="author-avatar"><?= strtoupper(substr($review['first_name'], 0, 1)) ?></div>
                                            <div class="author-info">
                                                <strong><?= $view->e($review['first_name']) ?> <?= $view->e(substr($review['last_name'] ?? '', 0, 1)) ?>.</strong>
                                                <span><?= $view->date($review['created_at'], 'M j, Y') ?></span>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <?php for ($i = 0; $i < 5; $i++): ?>
                                                <span class="star <?= $i < $review['rating'] ? 'filled' : '' ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="review-text"><?= $view->e($review['comment']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-reviews"><?= $t['no_results'] ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Booking Sidebar -->
            <aside class="vessel-sidebar">
                <div class="booking-card">
                    <div class="booking-price">
                        <span class="from"><?= $t['from'] ?></span>
                        <span class="amount"><?= $view->price($vessel['price_per_hour']) ?></span>
                        <span class="period"><?= $t['per_hour'] ?></span>
                    </div>

                    <form action="<?= $view->url('/book/vessel/' . $vessel['slug']) ?>" method="GET" class="booking-form">
                        <div class="form-group">
                            <label><?= $t['select_date'] ?></label>
                            <input type="date" name="date" min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label><?= $t['rental_hours'] ?></label>
                            <select name="hours">
                                <?php for ($h = 4; $h <= 12; $h++): ?>
                                    <option value="<?= $h ?>"><?= $h ?> <?= $t['hours'] ?></option>
                                <?php endfor; ?>
                                <option value="24">Full Day (24h)</option>
                            </select>
                        </div>

                        <div class="price-estimate">
                            <span><?= $t['total_price'] ?>:</span>
                            <span class="estimated-total" id="estimatedTotal"><?= $view->price($vessel['price_per_hour'] * 4) ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block"><?= $t['check_availability'] ?></button>
                    </form>

                    <div class="booking-contact">
                        <p>Or contact us directly:</p>
                        <a href="https://t.me/<?= $_ENV['TELEGRAM_BOT_USERNAME'] ?? 'phuketyachts_bot' ?>" class="btn btn-outline btn-block">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                            <?= $t['book_via_telegram'] ?>
                        </a>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Similar Vessels -->
        <?php if (!empty($similar)): ?>
            <div class="similar-section">
                <h2><?= $t['similar_vessels'] ?></h2>
                <div class="vessels-grid">
                    <?php foreach ($similar as $v): ?>
                        <?php
                        $imgs = json_decode($v['images'] ?? '[]', true);
                        $img = $imgs[0] ?? '/images/placeholder-yacht.jpg';
                        ?>
                        <article class="vessel-card">
                            <a href="<?= $view->url('/yachts/' . $v['slug']) ?>" class="vessel-image">
                                <img src="<?= $img ?>" alt="<?= $view->e($v['name']) ?>" loading="lazy">
                            </a>
                            <div class="vessel-content">
                                <h3><a href="<?= $view->url('/yachts/' . $v['slug']) ?>"><?= $view->e($v['name']) ?></a></h3>
                                <div class="vessel-price">
                                    <span class="from"><?= $t['from'] ?></span>
                                    <span class="amount"><?= $view->price($v['price_per_hour']) ?></span>
                                    <span class="period"><?= $t['per_hour'] ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function setMainImage(src, thumb) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

// Tab switching
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const target = tab.dataset.tab;
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(target).classList.add('active');
    });
});

// Price estimate
const pricePerHour = <?= $vessel['price_per_hour'] ?>;
document.querySelector('select[name="hours"]')?.addEventListener('change', function() {
    const total = pricePerHour * parseInt(this.value);
    document.getElementById('estimatedTotal').textContent = '฿' + total.toLocaleString();
});
</script>
