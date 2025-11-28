<?php $view->layout('main'); ?>

<section class="page-hero">
    <div class="container">
        <h1><?= $t['faq'] ?></h1>
        <p class="page-subtitle"><?= $t['faq_subtitle'] ?? 'Find answers to commonly asked questions' ?></p>
    </div>
</section>

<section class="faq-section">
    <div class="container">
        <div class="faq-layout">
            <!-- FAQ Categories -->
            <aside class="faq-sidebar">
                <nav class="faq-nav">
                    <a href="#booking" class="faq-nav-item active"><?= $t['booking'] ?? 'Booking' ?></a>
                    <a href="#payments" class="faq-nav-item"><?= $t['payments'] ?? 'Payments' ?></a>
                    <a href="#cancellation" class="faq-nav-item"><?= $t['cancellation'] ?? 'Cancellation' ?></a>
                    <a href="#yachts" class="faq-nav-item"><?= $t['yachts'] ?? 'Yachts & Vessels' ?></a>
                    <a href="#tours" class="faq-nav-item"><?= $t['tours'] ?? 'Tours' ?></a>
                    <a href="#safety" class="faq-nav-item"><?= $t['safety'] ?? 'Safety' ?></a>
                </nav>

                <div class="faq-contact">
                    <h4><?= $t['still_questions'] ?? 'Still have questions?' ?></h4>
                    <p>Our team is here to help!</p>
                    <a href="/contact" class="btn btn-outline btn-block"><?= $t['contact_us'] ?></a>
                </div>
            </aside>

            <!-- FAQ Content -->
            <div class="faq-content">
                <!-- Booking Section -->
                <div class="faq-category" id="booking">
                    <h2><?= $t['booking'] ?? 'Booking' ?></h2>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>How do I make a booking?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Booking with us is easy! You can:</p>
                            <ul>
                                <li>Use our website to browse and book directly</li>
                                <li>Use our Telegram Mini App for quick bookings</li>
                                <li>Contact us directly via Telegram, WhatsApp, or email</li>
                            </ul>
                            <p>Simply select your preferred yacht or tour, choose your date and time, fill in your details, and confirm your booking.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>How far in advance should I book?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>We recommend booking at least 3-7 days in advance, especially during peak season (November-April). For special occasions or large groups, booking 2-3 weeks ahead is advisable.</p>
                            <p>Last-minute bookings may be possible subject to availability - contact us directly for same-day or next-day requests.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>Can I modify my booking after confirmation?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Yes, you can modify your booking up to 48 hours before your scheduled trip. Changes may include:</p>
                            <ul>
                                <li>Date or time changes (subject to availability)</li>
                                <li>Number of guests</li>
                                <li>Special requests</li>
                            </ul>
                            <p>Contact our team to make any changes. Note that upgrades or date changes during peak season may incur additional charges.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>Do you offer group discounts?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Yes! We offer special rates for:</p>
                            <ul>
                                <li>Groups of 10+ people: 10% discount</li>
                                <li>Corporate events: Custom packages available</li>
                                <li>Wedding parties: Special rates and services</li>
                                <li>Repeat customers: Loyalty rewards</li>
                            </ul>
                            <p>Contact us for a personalized quote for your group.</p>
                        </div>
                    </div>
                </div>

                <!-- Payments Section -->
                <div class="faq-category" id="payments">
                    <h2><?= $t['payments'] ?? 'Payments' ?></h2>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>What payment methods do you accept?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>We accept multiple payment methods for your convenience:</p>
                            <ul>
                                <li>Credit/Debit Cards (Visa, Mastercard, AMEX)</li>
                                <li>Bank Transfer</li>
                                <li>Cash (Thai Baht)</li>
                                <li>PayPal</li>
                                <li>Cryptocurrency (Bitcoin, USDT)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>Is a deposit required?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>For most bookings, we require a 30% deposit to secure your reservation. The remaining balance can be paid:</p>
                            <ul>
                                <li>Online before your trip</li>
                                <li>In person on the day of your trip</li>
                            </ul>
                            <p>For large yacht charters, a 50% deposit may be required.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>Are your prices in Thai Baht?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Yes, all prices displayed on our website are in Thai Baht (฿). We can also provide quotes in USD or EUR upon request. The exchange rate used will be the current market rate on the day of payment.</p>
                        </div>
                    </div>
                </div>

                <!-- Cancellation Section -->
                <div class="faq-category" id="cancellation">
                    <h2><?= $t['cancellation'] ?? 'Cancellation' ?></h2>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>What is your cancellation policy?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Our standard cancellation policy:</p>
                            <ul>
                                <li><strong>7+ days before:</strong> Full refund</li>
                                <li><strong>3-7 days before:</strong> 50% refund</li>
                                <li><strong>Less than 3 days:</strong> No refund</li>
                            </ul>
                            <p>Cancellations due to severe weather are fully refundable or can be rescheduled at no extra cost.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>What happens if the weather is bad?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Your safety is our priority. If we need to cancel due to unsafe weather conditions, you can choose:</p>
                            <ul>
                                <li>Reschedule to another date (no additional charge)</li>
                                <li>Receive a full refund</li>
                                <li>Credit for future booking</li>
                            </ul>
                            <p>Weather decisions are made by our experienced captains and are final.</p>
                        </div>
                    </div>
                </div>

                <!-- Yachts Section -->
                <div class="faq-category" id="yachts">
                    <h2><?= $t['yachts'] ?? 'Yachts & Vessels' ?></h2>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>What is included in a yacht charter?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Our standard yacht charter includes:</p>
                            <ul>
                                <li>Professional captain and crew</li>
                                <li>Fuel</li>
                                <li>Basic snorkeling equipment</li>
                                <li>Fresh fruits and drinking water</li>
                                <li>Safety equipment and insurance</li>
                            </ul>
                            <p>Additional services like catering, DJ, decorations, and water sports can be added at extra cost.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>Can I bring my own food and drinks?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Yes, you're welcome to bring your own food and beverages. However, we also offer:</p>
                            <ul>
                                <li>Onboard catering services (Thai and international cuisine)</li>
                                <li>BBQ packages</li>
                                <li>Premium bar packages</li>
                            </ul>
                            <p>Please note: If you bring your own alcohol, a corkage fee may apply on some vessels.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>What is the minimum charter duration?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Minimum charter durations vary by vessel:</p>
                            <ul>
                                <li><strong>Speedboats:</strong> 4 hours minimum</li>
                                <li><strong>Sailing yachts:</strong> 6 hours minimum</li>
                                <li><strong>Luxury motor yachts:</strong> 8 hours minimum</li>
                            </ul>
                            <p>Full-day and multi-day charters are available at discounted rates.</p>
                        </div>
                    </div>
                </div>

                <!-- Tours Section -->
                <div class="faq-category" id="tours">
                    <h2><?= $t['tours'] ?? 'Tours' ?></h2>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>Do you provide hotel pickup?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Yes! Most of our tours include free hotel pickup and drop-off within the following areas:</p>
                            <ul>
                                <li>Patong Beach</li>
                                <li>Kata Beach</li>
                                <li>Karon Beach</li>
                                <li>Phuket Town</li>
                            </ul>
                            <p>Pickup from other areas may be available for an additional fee.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>Are tours suitable for children?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Most of our tours are family-friendly! We provide:</p>
                            <ul>
                                <li>Life jackets for all sizes</li>
                                <li>Child-friendly meals available</li>
                                <li>Special rates for children (3-11 years)</li>
                                <li>Free entry for infants under 3</li>
                            </ul>
                            <p>Some activities may have age restrictions - please check individual tour details.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>What should I bring on a tour?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>We recommend bringing:</p>
                            <ul>
                                <li>Swimwear and towel</li>
                                <li>Sunscreen and sunglasses</li>
                                <li>Camera (waterproof recommended)</li>
                                <li>Light jacket (for air-conditioned boats)</li>
                                <li>Seasickness medication if needed</li>
                                <li>Some cash for personal expenses</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Safety Section -->
                <div class="faq-category" id="safety">
                    <h2><?= $t['safety'] ?? 'Safety' ?></h2>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>Are your boats insured?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Yes, all our vessels are fully insured with:</p>
                            <ul>
                                <li>Third-party liability insurance</li>
                                <li>Passenger accident insurance</li>
                                <li>Hull and machinery insurance</li>
                            </ul>
                            <p>We also recommend guests have their own travel insurance for additional coverage.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>What safety equipment is on board?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>All our vessels are equipped with:</p>
                            <ul>
                                <li>Life jackets for all passengers</li>
                                <li>Fire extinguishers</li>
                                <li>First aid kit</li>
                                <li>Emergency flares</li>
                                <li>Radio communication</li>
                                <li>GPS navigation</li>
                            </ul>
                            <p>Our crew is trained in first aid and emergency procedures.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            <span>Can I go on a trip if I can't swim?</span>
                            <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="faq-answer">
                            <p>Absolutely! Many of our guests are non-swimmers. We provide:</p>
                            <ul>
                                <li>Life jackets that can be worn at all times</li>
                                <li>Flotation devices for snorkeling</li>
                                <li>Shallow snorkeling areas</li>
                                <li>Crew assistance for water activities</li>
                            </ul>
                            <p>Please inform us when booking so we can ensure your comfort and safety.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// FAQ Accordion
document.querySelectorAll('.faq-question').forEach(button => {
    button.addEventListener('click', () => {
        const item = button.parentElement;
        const isOpen = item.classList.contains('open');

        // Close all items
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));

        // Open clicked item if it wasn't open
        if (!isOpen) {
            item.classList.add('open');
        }
    });
});

// Smooth scroll to sections
document.querySelectorAll('.faq-nav-item').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const target = document.querySelector(link.getAttribute('href'));

        // Update active state
        document.querySelectorAll('.faq-nav-item').forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        // Scroll to section
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

// Update active nav on scroll
window.addEventListener('scroll', () => {
    const categories = document.querySelectorAll('.faq-category');
    let current = '';

    categories.forEach(category => {
        const top = category.offsetTop;
        if (scrollY >= top - 200) {
            current = category.getAttribute('id');
        }
    });

    document.querySelectorAll('.faq-nav-item').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) {
            link.classList.add('active');
        }
    });
});
</script>

<style>
.faq-section {
    padding: 80px 0;
}

.faq-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 48px;
}

.faq-sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.faq-nav {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 16px;
    box-shadow: var(--shadow);
    margin-bottom: 24px;
}

.faq-nav-item {
    display: block;
    padding: 12px 16px;
    color: var(--gray-600);
    text-decoration: none;
    border-radius: var(--radius);
    transition: background 0.2s, color 0.2s;
}

.faq-nav-item:hover,
.faq-nav-item.active {
    background: var(--primary-light);
    color: var(--primary);
}

.faq-contact {
    background: var(--gray-100);
    border-radius: var(--radius-lg);
    padding: 24px;
    text-align: center;
}

.faq-contact h4 {
    margin-bottom: 8px;
}

.faq-contact p {
    color: var(--gray-600);
    margin-bottom: 16px;
}

.faq-category {
    margin-bottom: 48px;
}

.faq-category h2 {
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--gray-200);
}

.faq-item {
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    margin-bottom: 12px;
    overflow: hidden;
}

.faq-question {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    background: var(--white);
    border: none;
    cursor: pointer;
    text-align: left;
    font-size: 1rem;
    font-weight: 500;
    transition: background 0.2s;
}

.faq-question:hover {
    background: var(--gray-50);
}

.faq-icon {
    width: 20px;
    height: 20px;
    transition: transform 0.3s;
    flex-shrink: 0;
    margin-left: 16px;
}

.faq-item.open .faq-icon {
    transform: rotate(180deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
}

.faq-item.open .faq-answer {
    max-height: 500px;
    padding: 0 24px 24px;
}

.faq-answer p {
    color: var(--gray-600);
    line-height: 1.7;
    margin-bottom: 12px;
}

.faq-answer ul {
    margin: 12px 0;
    padding-left: 24px;
}

.faq-answer li {
    color: var(--gray-600);
    margin-bottom: 8px;
}

@media (max-width: 992px) {
    .faq-layout {
        grid-template-columns: 1fr;
    }

    .faq-sidebar {
        position: static;
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
    }

    .faq-nav {
        flex: 1;
        min-width: 200px;
    }

    .faq-contact {
        flex: 1;
        min-width: 200px;
    }
}

@media (max-width: 600px) {
    .faq-sidebar {
        flex-direction: column;
    }

    .faq-nav {
        display: flex;
        overflow-x: auto;
        gap: 8px;
        padding: 12px;
    }

    .faq-nav-item {
        white-space: nowrap;
        padding: 8px 16px;
    }

    .faq-question {
        padding: 16px;
        font-size: 0.95rem;
    }
}
</style>
