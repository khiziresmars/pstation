<?php $view->layout('main'); ?>

<!-- Page Header -->
<section class="page-header page-header-tours">
    <div class="container">
        <h1><?= $t['tours_title'] ?></h1>
        <p><?= $t['tours_subtitle'] ?></p>
    </div>
</section>

<!-- Tours Grid -->
<section class="section tours-list">
    <div class="container">
        <?php if (empty($tours)): ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
                <h3><?= $t['no_results'] ?></h3>
            </div>
        <?php else: ?>
            <div class="results-header">
                <span class="results-count"><?= $total ?> <?= $t['nav_tours'] ?></span>
                <form class="sort-form" method="GET">
                    <select name="sort" onchange="this.form.submit()">
                        <option value="popular" <?= $currentSort === 'popular' ? 'selected' : '' ?>><?= $t['sort_popular'] ?></option>
                        <option value="price_low" <?= $currentSort === 'price_low' ? 'selected' : '' ?>><?= $t['sort_price_low'] ?></option>
                        <option value="price_high" <?= $currentSort === 'price_high' ? 'selected' : '' ?>><?= $t['sort_price_high'] ?></option>
                        <option value="rating" <?= $currentSort === 'rating' ? 'selected' : '' ?>><?= $t['sort_rating'] ?></option>
                    </select>
                </form>
            </div>

            <div class="tours-grid tours-grid-lg">
                <?php foreach ($tours as $tour): ?>
                    <?php
                    $images = json_decode($tour['images'] ?? '[]', true);
                    $image = $images[0] ?? '/images/placeholder-tour.jpg';
                    $highlights = json_decode($tour['highlights'] ?? '[]', true);
                    ?>
                    <article class="tour-card tour-card-horizontal">
                        <a href="<?= $view->url('/tours/' . $tour['slug']) ?>" class="tour-image">
                            <img src="<?= $image ?>" alt="<?= $view->e($tour['name']) ?>" loading="lazy">
                            <span class="tour-duration"><?= $tour['duration_hours'] ?> <?= $t['hours'] ?></span>
                            <?php if ($tour['hotel_pickup']): ?>
                                <span class="badge badge-info"><?= $t['pickup_available'] ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="tour-content">
                            <div class="tour-header">
                                <span class="tour-category"><?= $view->e($tour['category']) ?></span>
                                <?php if ($tour['rating']): ?>
                                    <div class="tour-rating">
                                        <span class="stars">★</span>
                                        <span><?= number_format($tour['rating'], 1) ?></span>
                                        <span class="count">(<?= $tour['review_count'] ?? 0 ?>)</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <h2><a href="<?= $view->url('/tours/' . $tour['slug']) ?>"><?= $view->e($tour['name']) ?></a></h2>

                            <p class="tour-desc"><?= $view->truncate($tour['description'], 150) ?></p>

                            <?php if (!empty($highlights)): ?>
                                <ul class="tour-highlights">
                                    <?php foreach (array_slice($highlights, 0, 3) as $h): ?>
                                        <li><?= $view->e($h) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <div class="tour-footer">
                                <div class="tour-price">
                                    <span class="from"><?= $t['from'] ?></span>
                                    <span class="amount"><?= $view->price($tour['price_adult']) ?></span>
                                    <span class="period"><?= $t['per_person'] ?></span>
                                </div>
                                <div class="tour-actions">
                                    <a href="<?= $view->url('/tours/' . $tour['slug']) ?>" class="btn btn-outline"><?= $t['view_details'] ?></a>
                                    <a href="<?= $view->url('/book/tour/' . $tour['slug']) ?>" class="btn btn-primary"><?= $t['book_now'] ?></a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $currentSort !== 'popular' ? '&sort=' . $currentSort : '' ?>" class="btn btn-outline"><?= $t['previous'] ?></a>
                    <?php endif; ?>

                    <span class="pagination-info"><?= $page ?> / <?= $totalPages ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $currentSort !== 'popular' ? '&sort=' . $currentSort : '' ?>" class="btn btn-outline"><?= $t['next'] ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
