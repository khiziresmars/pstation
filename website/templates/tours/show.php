<?php
$view->layout('main');
$images = json_decode($tour['images'] ?? '[]', true);
?>

<!-- Tour Header -->
<section class="tour-hero">
    <div class="tour-gallery">
        <?php if (!empty($images)): ?>
            <div class="gallery-main">
                <img src="<?= $images[0] ?>" alt="<?= $view->e($tour['name']) ?>" id="mainImage">
            </div>
            <?php if (count($images) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach ($images as $i => $img): ?>
                        <button class="thumb <?= $i === 0 ? 'active' : '' ?>" onclick="setMainImage('<?= $img ?>', this)">
                            <img src="<?= $img ?>" alt="<?= $view->e($tour['name']) ?> - <?= $i + 1 ?>" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="gallery-main">
                <img src="/images/placeholder-tour.jpg" alt="<?= $view->e($tour['name']) ?>">
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="tour-detail">
    <div class="container">
        <div class="tour-layout">
            <!-- Main Content -->
            <div class="tour-main">
                <div class="tour-title-block">
                    <span class="tour-category-badge"><?= $view->e($tour['category']) ?></span>
                    <h1><?= $view->e($tour['name']) ?></h1>
                    <div class="tour-meta">
                        <span class="meta-item">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <?= $tour['duration_hours'] ?> <?= $t['hours'] ?>
                        </span>
                        <?php if ($tour['departure_time']): ?>
                            <span class="meta-item">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>
                                </svg>
                                <?= $t['departure'] ?>: <?= $tour['departure_time'] ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($tour['rating']): ?>
                            <span class="meta-item rating">
                                <span class="stars">★</span>
                                <?= number_format($tour['rating'], 1) ?>
                                <span class="count">(<?= $tour['review_count'] ?? 0 ?>)</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab active" data-tab="overview"><?= $t['overview'] ?></button>
                    <button class="tab" data-tab="itinerary"><?= $t['itinerary'] ?></button>
                    <button class="tab" data-tab="included"><?= $t['whats_included'] ?></button>
                    <button class="tab" data-tab="reviews"><?= $t['reviews'] ?></button>
                </div>

                <!-- Overview Tab -->
                <div class="tab-content active" id="overview">
                    <div class="tour-description">
                        <?= nl2br($view->e($tour['description'])) ?>
                    </div>

                    <?php if (!empty($highlights)): ?>
                        <div class="tour-highlights-section">
                            <h3><?= $t['highlights'] ?></h3>
                            <ul class="highlights-list">
                                <?php foreach ($highlights as $highlight): ?>
                                    <li>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        <?= $view->e($highlight) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($tour['hotel_pickup']): ?>
                        <div class="info-badge">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                <circle cx="7" cy="17" r="2"/>
                                <path d="M9 17h6"/>
                                <circle cx="17" cy="17" r="2"/>
                            </svg>
                            <?= $t['pickup_available'] ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Itinerary Tab -->
                <div class="tab-content" id="itinerary">
                    <?php if (!empty($itinerary)): ?>
                        <div class="itinerary-timeline">
                            <?php foreach ($itinerary as $index => $item): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker"><?= $index + 1 ?></div>
                                    <div class="timeline-content">
                                        <?php if (is_array($item)): ?>
                                            <h4><?= $view->e($item['time'] ?? '') ?></h4>
                                            <p><?= $view->e($item['activity'] ?? $item['description'] ?? '') ?></p>
                                        <?php else: ?>
                                            <p><?= $view->e($item) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p><?= $t['no_results'] ?></p>
                    <?php endif; ?>
                </div>

                <!-- Included Tab -->
                <div class="tab-content" id="included">
                    <div class="included-grid">
                        <?php if (!empty($includes)): ?>
                            <div class="included-section">
                                <h4><?= $t['whats_included'] ?></h4>
                                <ul class="included-list">
                                    <?php foreach ($includes as $item): ?>
                                        <li class="included">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                            <?= $view->e($item) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($excludes)): ?>
                            <div class="included-section">
                                <h4><?= $t['not_included'] ?></h4>
                                <ul class="included-list">
                                    <?php foreach ($excludes as $item): ?>
                                        <li class="excluded">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"/>
                                                <line x1="6" y1="6" x2="18" y2="18"/>
                                            </svg>
                                            <?= $view->e($item) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
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
            <aside class="tour-sidebar">
                <div class="booking-card">
                    <div class="booking-prices">
                        <div class="price-row">
                            <span class="label"><?= $t['adult'] ?></span>
                            <span class="amount"><?= $view->price($tour['price_adult']) ?></span>
                        </div>
                        <?php if ($tour['price_child'] && $tour['price_child'] > 0): ?>
                            <div class="price-row">
                                <span class="label"><?= $t['child'] ?></span>
                                <span class="amount"><?= $view->price($tour['price_child']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form action="<?= $view->url('/book/tour/' . $tour['slug']) ?>" method="GET" class="booking-form">
                        <div class="form-group">
                            <label><?= $t['select_date'] ?></label>
                            <input type="date" name="date" min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?= $t['adults'] ?></label>
                                <select name="adults" id="adultsSelect">
                                    <?php for ($i = 1; $i <= 20; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?= $t['children'] ?></label>
                                <select name="children" id="childrenSelect">
                                    <?php for ($i = 0; $i <= 10; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="price-estimate">
                            <span><?= $t['total_price'] ?>:</span>
                            <span class="estimated-total" id="estimatedTotal"><?= $view->price($tour['price_adult']) ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block"><?= $t['book_now'] ?></button>
                    </form>

                    <div class="booking-contact">
                        <p>Or contact us directly:</p>
                        <a href="https://t.me/<?= $_ENV['TELEGRAM_BOT_USERNAME'] ?? 'phuketyachts_bot' ?>" class="btn btn-outline btn-block">
                            <?= $t['book_via_telegram'] ?>
                        </a>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Similar Tours -->
        <?php if (!empty($similar)): ?>
            <div class="similar-section">
                <h2><?= $t['similar_vessels'] ?></h2>
                <div class="tours-grid">
                    <?php foreach ($similar as $s): ?>
                        <?php
                        $imgs = json_decode($s['images'] ?? '[]', true);
                        $img = $imgs[0] ?? '/images/placeholder-tour.jpg';
                        ?>
                        <article class="tour-card">
                            <a href="<?= $view->url('/tours/' . $s['slug']) ?>" class="tour-image">
                                <img src="<?= $img ?>" alt="<?= $view->e($s['name']) ?>" loading="lazy">
                                <span class="tour-duration"><?= $s['duration_hours'] ?>h</span>
                            </a>
                            <div class="tour-content">
                                <h3><a href="<?= $view->url('/tours/' . $s['slug']) ?>"><?= $view->e($s['name']) ?></a></h3>
                                <div class="tour-price">
                                    <span class="from"><?= $t['from'] ?></span>
                                    <span class="amount"><?= $view->price($s['price_adult']) ?></span>
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
const adultPrice = <?= $tour['price_adult'] ?>;
const childPrice = <?= $tour['price_child'] ?? 0 ?>;

function updateTotal() {
    const adults = parseInt(document.getElementById('adultsSelect').value) || 1;
    const children = parseInt(document.getElementById('childrenSelect').value) || 0;
    const total = (adults * adultPrice) + (children * childPrice);
    document.getElementById('estimatedTotal').textContent = '฿' + total.toLocaleString();
}

document.getElementById('adultsSelect')?.addEventListener('change', updateTotal);
document.getElementById('childrenSelect')?.addEventListener('change', updateTotal);
</script>
