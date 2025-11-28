<?php $view->layout('main'); ?>

<section class="page-hero">
    <div class="container">
        <h1><?= $t['contact_us'] ?></h1>
        <p class="page-subtitle"><?= $t['contact_subtitle'] ?? 'We\'d love to hear from you. Get in touch with our team.' ?></p>
    </div>
</section>

<section class="contact-section">
    <div class="container">
        <div class="contact-layout">
            <!-- Contact Form -->
            <div class="contact-form-section">
                <h2><?= $t['send_message'] ?? 'Send us a Message' ?></h2>
                <p class="form-intro">Have a question or special request? Fill out the form below and we'll get back to you within 24 hours.</p>

                <form action="/contact/submit" method="POST" class="contact-form" id="contactForm">
                    <input type="hidden" name="_token" value="<?= $csrfToken ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label><?= $t['full_name'] ?> *</label>
                            <input type="text" name="name" required placeholder="John Smith">
                        </div>
                        <div class="form-group">
                            <label><?= $t['email'] ?> *</label>
                            <input type="email" name="email" required placeholder="john@example.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><?= $t['phone'] ?></label>
                            <input type="tel" name="phone" placeholder="+66 XX XXX XXXX">
                        </div>
                        <div class="form-group">
                            <label><?= $t['subject'] ?? 'Subject' ?> *</label>
                            <select name="subject" required>
                                <option value=""><?= $t['select_subject'] ?? 'Select a subject' ?></option>
                                <option value="booking"><?= $t['booking_inquiry'] ?? 'Booking Inquiry' ?></option>
                                <option value="charter"><?= $t['private_charter'] ?? 'Private Charter' ?></option>
                                <option value="corporate"><?= $t['corporate_events'] ?? 'Corporate Events' ?></option>
                                <option value="partnership"><?= $t['partnership'] ?? 'Partnership' ?></option>
                                <option value="feedback"><?= $t['feedback'] ?? 'Feedback' ?></option>
                                <option value="other"><?= $t['other'] ?? 'Other' ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= $t['message'] ?? 'Message' ?> *</label>
                        <textarea name="message" rows="5" required placeholder="<?= $t['message_placeholder'] ?? 'Tell us how we can help you...' ?>"></textarea>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="newsletter" value="1">
                            <span><?= $t['newsletter_subscribe'] ?? 'Subscribe to our newsletter for special offers' ?></span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg"><?= $t['send_message'] ?? 'Send Message' ?></button>
                </form>

                <div id="formMessage" class="form-message" style="display: none;"></div>
            </div>

            <!-- Contact Info -->
            <div class="contact-info-section">
                <div class="info-card">
                    <h3><?= $t['contact_info'] ?? 'Contact Information' ?></h3>

                    <div class="info-item">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h4><?= $t['address'] ?? 'Address' ?></h4>
                            <p>123 Chalong Pier Road<br>Phuket 83130, Thailand</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h4><?= $t['phone'] ?></h4>
                            <p>+66 76 123 456<br>+66 89 123 4567</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h4><?= $t['email'] ?></h4>
                            <p>info@phuketyachts.com<br>bookings@phuketyachts.com</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div class="info-content">
                            <h4><?= $t['working_hours'] ?? 'Working Hours' ?></h4>
                            <p>Mon - Sun: 8:00 AM - 8:00 PM<br>Thai Time (UTC+7)</p>
                        </div>
                    </div>
                </div>

                <div class="social-card">
                    <h3><?= $t['follow_us'] ?? 'Follow Us' ?></h3>
                    <div class="social-links">
                        <a href="https://t.me/<?= $_ENV['TELEGRAM_BOT_USERNAME'] ?? 'phuketyachts_bot' ?>" class="social-link telegram" target="_blank">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                            Telegram
                        </a>
                        <a href="#" class="social-link whatsapp" target="_blank">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            WhatsApp
                        </a>
                        <a href="#" class="social-link facebook" target="_blank">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Facebook
                        </a>
                        <a href="#" class="social-link instagram" target="_blank">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.757-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                            </svg>
                            Instagram
                        </a>
                    </div>
                </div>

                <div class="quick-contact">
                    <h3><?= $t['quick_response'] ?? 'Need Quick Response?' ?></h3>
                    <p>Chat with us directly on Telegram for instant support.</p>
                    <a href="https://t.me/<?= $_ENV['TELEGRAM_BOT_USERNAME'] ?? 'phuketyachts_bot' ?>" class="btn btn-primary btn-block" target="_blank">
                        <?= $t['chat_telegram'] ?? 'Chat on Telegram' ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Map -->
        <div class="map-section">
            <h2><?= $t['find_us'] ?? 'Find Us' ?></h2>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3952.1234567890!2d98.3456789!3d7.8234567!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zN8KwNDknMjQuNCJOIDk4wrAyMCc0NC40IkU!5e0!3m2!1sen!2sth!4v1234567890"
                    width="100%"
                    height="400"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const messageDiv = document.getElementById('formMessage');
    const submitBtn = form.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.textContent = '<?= $t['sending'] ?? 'Sending...' ?>';

    try {
        const res = await fetch('/contact/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();

        if (result.success) {
            messageDiv.className = 'form-message success';
            messageDiv.textContent = '<?= $t['message_sent'] ?? 'Thank you! Your message has been sent. We\'ll get back to you soon.' ?>';
            messageDiv.style.display = 'block';
            form.reset();
        } else {
            messageDiv.className = 'form-message error';
            messageDiv.textContent = result.error || '<?= $t['message_error'] ?? 'Failed to send message. Please try again.' ?>';
            messageDiv.style.display = 'block';
        }
    } catch (e) {
        messageDiv.className = 'form-message error';
        messageDiv.textContent = '<?= $t['message_error'] ?? 'An error occurred. Please try again.' ?>';
        messageDiv.style.display = 'block';
    }

    submitBtn.disabled = false;
    submitBtn.textContent = '<?= $t['send_message'] ?? 'Send Message' ?>';
});
</script>

<style>
.contact-section {
    padding: 80px 0;
}

.contact-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 48px;
    margin-bottom: 80px;
}

.contact-form-section h2 {
    margin-bottom: 12px;
}

.form-intro {
    color: var(--gray-600);
    margin-bottom: 32px;
}

.contact-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.contact-form .form-group {
    margin-bottom: 20px;
}

.contact-form label {
    display: block;
    font-weight: 500;
    margin-bottom: 8px;
}

.contact-form input,
.contact-form select,
.contact-form textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--gray-300);
    border-radius: var(--radius);
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.contact-form input:focus,
.contact-form select:focus,
.contact-form textarea:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 112, 243, 0.1);
}

.checkbox-group {
    margin-bottom: 24px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
}

.form-message {
    padding: 16px;
    border-radius: var(--radius);
    margin-top: 20px;
}

.form-message.success {
    background: #dcfce7;
    color: #166534;
}

.form-message.error {
    background: #fee2e2;
    color: #991b1b;
}

.info-card,
.social-card,
.quick-contact {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 32px;
    box-shadow: var(--shadow);
    margin-bottom: 24px;
}

.info-card h3,
.social-card h3,
.quick-contact h3 {
    margin-bottom: 24px;
}

.info-item {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
}

.info-item:last-child {
    margin-bottom: 0;
}

.info-icon {
    width: 48px;
    height: 48px;
    background: var(--primary-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.info-icon svg {
    width: 24px;
    height: 24px;
    stroke: var(--primary);
}

.info-content h4 {
    font-size: 1rem;
    margin-bottom: 4px;
}

.info-content p {
    color: var(--gray-600);
    font-size: 0.9rem;
    line-height: 1.6;
}

.social-links {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.social-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border-radius: var(--radius);
    text-decoration: none;
    font-weight: 500;
    transition: transform 0.2s;
}

.social-link:hover {
    transform: translateY(-2px);
}

.social-link svg {
    width: 20px;
    height: 20px;
}

.social-link.telegram {
    background: #e3f2fd;
    color: #0088cc;
}

.social-link.whatsapp {
    background: #e8f5e9;
    color: #25D366;
}

.social-link.facebook {
    background: #e3f2fd;
    color: #1877F2;
}

.social-link.instagram {
    background: #fce4ec;
    color: #E4405F;
}

.quick-contact p {
    color: var(--gray-600);
    margin-bottom: 16px;
}

.map-section h2 {
    text-align: center;
    margin-bottom: 32px;
}

.map-container {
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow);
}

@media (max-width: 992px) {
    .contact-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .contact-form .form-row {
        grid-template-columns: 1fr;
    }

    .social-links {
        grid-template-columns: 1fr;
    }

    .info-card,
    .social-card,
    .quick-contact {
        padding: 24px;
    }
}
</style>
