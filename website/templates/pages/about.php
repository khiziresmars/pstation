<?php $view->layout('main'); ?>

<section class="page-hero">
    <div class="container">
        <h1><?= $t['about_us'] ?></h1>
        <p class="page-subtitle"><?= $t['about_subtitle'] ?? 'Your trusted partner for yacht charters and island tours in Phuket' ?></p>
    </div>
</section>

<section class="about-content">
    <div class="container">
        <div class="about-intro">
            <div class="about-text">
                <h2>Welcome to Phuket Yachts & Tours</h2>
                <p>We are a premier yacht charter and tour company based in the heart of Phuket, Thailand. With over 10 years of experience in the marine tourism industry, we have built a reputation for excellence, safety, and unforgettable experiences.</p>
                <p>Our mission is to provide our guests with the most memorable maritime adventures, whether it's a luxurious yacht charter to the stunning Phi Phi Islands, a sunset cruise along the Andaman coast, or an exciting snorkeling expedition to crystal-clear waters.</p>
            </div>
            <div class="about-image">
                <img src="/images/about-yacht.jpg" alt="Luxury Yacht in Phuket" loading="lazy">
            </div>
        </div>

        <div class="about-stats">
            <div class="stat-item">
                <span class="stat-number">10+</span>
                <span class="stat-label"><?= $t['years_experience'] ?? 'Years Experience' ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number">50+</span>
                <span class="stat-label"><?= $t['vessels_fleet'] ?? 'Vessels in Fleet' ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number">15K+</span>
                <span class="stat-label"><?= $t['happy_customers'] ?? 'Happy Customers' ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number">100+</span>
                <span class="stat-label"><?= $t['tours_offered'] ?? 'Tours Offered' ?></span>
            </div>
        </div>

        <div class="about-features">
            <h2><?= $t['why_choose_us'] ?? 'Why Choose Us' ?></h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <h3><?= $t['safety_first'] ?? 'Safety First' ?></h3>
                    <p>All our vessels are regularly inspected and equipped with the latest safety equipment. Our crew is trained in emergency procedures and first aid.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <h3><?= $t['flexible_booking'] ?? 'Flexible Booking' ?></h3>
                    <p>Book online 24/7, modify your reservations easily, and enjoy free cancellation up to 48 hours before your trip.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <h3><?= $t['professional_crew'] ?? 'Professional Crew' ?></h3>
                    <p>Our experienced captains and friendly crew ensure a smooth, enjoyable journey with personalized attention to your needs.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </div>
                    <h3><?= $t['best_destinations'] ?? 'Best Destinations' ?></h3>
                    <p>Explore hidden gems, pristine beaches, and stunning marine life at the most beautiful locations in the Andaman Sea.</p>
                </div>
            </div>
        </div>

        <div class="about-team">
            <h2><?= $t['our_team'] ?? 'Our Team' ?></h2>
            <p class="team-intro">Meet the passionate professionals behind your unforgettable experiences.</p>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-photo">
                        <img src="/images/team/captain.jpg" alt="Captain" loading="lazy">
                    </div>
                    <h4>Captain Somchai</h4>
                    <span class="member-role">Fleet Captain</span>
                    <p>20+ years sailing experience in Thai waters</p>
                </div>
                <div class="team-member">
                    <div class="member-photo">
                        <img src="/images/team/manager.jpg" alt="Operations Manager" loading="lazy">
                    </div>
                    <h4>Napat Thongsuk</h4>
                    <span class="member-role">Operations Manager</span>
                    <p>Ensuring smooth operations and customer satisfaction</p>
                </div>
                <div class="team-member">
                    <div class="member-photo">
                        <img src="/images/team/guide.jpg" alt="Tour Guide" loading="lazy">
                    </div>
                    <h4>Arisa Chanwong</h4>
                    <span class="member-role">Senior Tour Guide</span>
                    <p>Multilingual guide with local expertise</p>
                </div>
            </div>
        </div>

        <div class="about-cta">
            <h2><?= $t['ready_to_explore'] ?? 'Ready to Explore?' ?></h2>
            <p>Start planning your dream yacht experience today.</p>
            <div class="cta-buttons">
                <a href="/vessels" class="btn btn-primary"><?= $t['view_yachts'] ?></a>
                <a href="/tours" class="btn btn-outline"><?= $t['browse_tours'] ?></a>
            </div>
        </div>
    </div>
</section>

<style>
.page-hero {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: var(--white);
    padding: 80px 0;
    text-align: center;
}

.page-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 12px;
}

.page-subtitle {
    font-size: 1.25rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.about-content {
    padding: 80px 0;
}

.about-intro {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    align-items: center;
    margin-bottom: 80px;
}

.about-text h2 {
    margin-bottom: 20px;
}

.about-text p {
    color: var(--gray-600);
    line-height: 1.8;
    margin-bottom: 16px;
}

.about-image img {
    width: 100%;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
}

.about-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 32px;
    background: var(--gray-100);
    border-radius: var(--radius-xl);
    padding: 48px;
    margin-bottom: 80px;
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary);
}

.stat-label {
    color: var(--gray-600);
    font-size: 1rem;
}

.about-features h2 {
    text-align: center;
    margin-bottom: 48px;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 32px;
    margin-bottom: 80px;
}

.feature-card {
    text-align: center;
    padding: 32px 24px;
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    transition: transform 0.3s, box-shadow 0.3s;
}

.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.feature-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    background: var(--primary-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.feature-icon svg {
    width: 32px;
    height: 32px;
    stroke: var(--primary);
}

.feature-card h3 {
    margin-bottom: 12px;
}

.feature-card p {
    color: var(--gray-600);
    font-size: 0.9rem;
    line-height: 1.6;
}

.about-team {
    text-align: center;
    margin-bottom: 80px;
}

.about-team h2 {
    margin-bottom: 12px;
}

.team-intro {
    color: var(--gray-600);
    margin-bottom: 48px;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
}

.team-member {
    text-align: center;
}

.member-photo {
    width: 150px;
    height: 150px;
    margin: 0 auto 20px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--gray-200);
}

.member-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.team-member h4 {
    margin-bottom: 4px;
}

.member-role {
    color: var(--primary);
    font-weight: 500;
    display: block;
    margin-bottom: 8px;
}

.team-member p {
    color: var(--gray-600);
    font-size: 0.9rem;
}

.about-cta {
    text-align: center;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: var(--white);
    padding: 64px;
    border-radius: var(--radius-xl);
}

.about-cta h2 {
    color: var(--white);
    margin-bottom: 12px;
}

.about-cta p {
    opacity: 0.9;
    margin-bottom: 24px;
}

.cta-buttons {
    display: flex;
    gap: 16px;
    justify-content: center;
}

.about-cta .btn-outline {
    border-color: var(--white);
    color: var(--white);
}

.about-cta .btn-outline:hover {
    background: var(--white);
    color: var(--primary);
}

@media (max-width: 992px) {
    .about-intro {
        grid-template-columns: 1fr;
    }

    .about-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .team-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .page-hero {
        padding: 60px 20px;
    }

    .page-hero h1 {
        font-size: 2rem;
    }

    .about-stats {
        grid-template-columns: 1fr 1fr;
        padding: 32px 24px;
    }

    .stat-number {
        font-size: 2.5rem;
    }

    .features-grid,
    .team-grid {
        grid-template-columns: 1fr;
    }

    .about-cta {
        padding: 40px 24px;
    }

    .cta-buttons {
        flex-direction: column;
    }
}
</style>
