<?php $view->layout('main'); ?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><?= $t['yachts_title'] ?></h1>
        <p><?= $t['yachts_subtitle'] ?></p>
    </div>
</section>

<!-- Filters -->
<section class="filters-section">
    <div class="container">
        <form class="filters-form" method="GET">
            <div class="filter-tabs">
                <a href="<?= $view->url('/yachts') ?>" class="filter-tab <?= !$currentType ? 'active' : '' ?>"><?= $t['filter_all'] ?></a>
                <a href="<?= $view->url('/yachts', ['type' => 'yacht']) ?>" class="filter-tab <?= $currentType === 'yacht' ? 'active' : '' ?>"><?= $t['filter_yacht'] ?></a>
                <a href="<?= $view->url('/yachts', ['type' => 'speedboat']) ?>" class="filter-tab <?= $currentType === 'speedboat' ? 'active' : '' ?>"><?= $t['filter_speedboat'] ?></a>
                <a href="<?= $view->url('/yachts', ['type' => 'catamaran']) ?>" class="filter-tab <?= $currentType === 'catamaran' ? 'active' : '' ?>"><?= $t['filter_catamaran'] ?></a>
                <a href="<?= $view->url('/yachts', ['type' => 'sailboat']) ?>" class="filter-tab <?= $currentType === 'sailboat' ? 'active' : '' ?>"><?= $t['filter_sailboat'] ?></a>
            </div>

            <div class="filter-sort">
                <select name="sort" onchange="this.form.submit()">
                    <option value="popular" <?= $currentSort === 'popular' ? 'selected' : '' ?>><?= $t['sort_popular'] ?></option>
                    <option value="price_low" <?= $currentSort === 'price_low' ? 'selected' : '' ?>><?= $t['sort_price_low'] ?></option>
                    <option value="price_high" <?= $currentSort === 'price_high' ? 'selected' : '' ?>><?= $t['sort_price_high'] ?></option>
                    <option value="rating" <?= $currentSort === 'rating' ? 'selected' : '' ?>><?= $t['sort_rating'] ?></option>
                </select>
                <?php if ($currentType): ?>
                    <input type="hidden" name="type" value="<?= $currentType ?>">
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>

<!-- Vessels Grid -->
<section class="section vessels-list">
    <div class="container">
        <?php if (empty($vessels)): ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                    <line x1="4" y1="22" x2="4" y2="15"/>
                </svg>
                <h3><?= $t['no_results'] ?></h3>
                <a href="<?= $view->url('/yachts') ?>" class="btn btn-primary"><?= $t['view_all'] ?></a>
            </div>
        <?php else: ?>
            <div class="results-count">
                <span><?= $total ?> <?= $t['nav_yachts'] ?></span>
            </div>

            <div class="vessels-grid vessels-grid-lg">
                <?php foreach ($vessels as $vessel): ?>
                    <?php
                    $images = json_decode($vessel['images'] ?? '[]', true);
                    $image = $images[0] ?? '/images/placeholder-yacht.jpg';
                    ?>
                    <article class="vessel-card vessel-card-horizontal">
                        <a href="<?= $view->url('/yachts/' . $vessel['slug']) ?>" class="vessel-image">
                            <img src="<?= $image ?>" alt="<?= $view->e($vessel['name']) ?>" loading="lazy">
                            <span class="vessel-type"><?= ucfirst($vessel['type']) ?></span>
                            <?php if ($vessel['captain_included']): ?>
                                <span class="badge badge-success"><?= $t['captain_included'] ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="vessel-content">
                            <div class="vessel-header">
                                <h2><a href="<?= $view->url('/yachts/' . $vessel['slug']) ?>"><?= $view->e($vessel['name']) ?></a></h2>
                                <?php if ($vessel['rating']): ?>
                                    <div class="vessel-rating">
                                        <span class="stars">★</span>
                                        <span><?= number_format($vessel['rating'], 1) ?></span>
                                        <span class="count">(<?= $vessel['review_count'] ?? 0 ?>)</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <p class="vessel-desc"><?= $view->truncate($vessel['description'], 150) ?></p>

                            <div class="vessel-specs">
                                <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> <?= $vessel['capacity'] ?> <?= $t['guests'] ?></span>
                                <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg> <?= $vessel['length_meters'] ?>m</span>
                                <?php if ($vessel['year_built']): ?>
                                    <span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?= $vessel['year_built'] ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="vessel-footer">
                                <div class="vessel-price">
                                    <span class="from"><?= $t['from'] ?></span>
                                    <span class="amount"><?= $view->price($vessel['price_per_hour']) ?></span>
                                    <span class="period"><?= $t['per_hour'] ?></span>
                                </div>
                                <div class="vessel-actions">
                                    <a href="<?= $view->url('/yachts/' . $vessel['slug']) ?>" class="btn btn-outline"><?= $t['view_details'] ?></a>
                                    <a href="<?= $view->url('/book/vessel/' . $vessel['slug']) ?>" class="btn btn-primary"><?= $t['book_now'] ?></a>
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
                        <a href="?page=<?= $page - 1 ?><?= $currentType ? '&type=' . $currentType : '' ?><?= $currentSort !== 'popular' ? '&sort=' . $currentSort : '' ?>" class="btn btn-outline"><?= $t['previous'] ?></a>
                    <?php endif; ?>

                    <span class="pagination-info"><?= $page ?> / <?= $totalPages ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $currentType ? '&type=' . $currentType : '' ?><?= $currentSort !== 'popular' ? '&sort=' . $currentSort : '' ?>" class="btn btn-outline"><?= $t['next'] ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
