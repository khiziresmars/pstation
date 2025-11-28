<?php $view->layout('main'); ?>

<section class="confirmation-page">
    <div class="container">
        <div class="confirmation-card">
            <div class="confirmation-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>

            <h1><?= $t['booking_confirmed'] ?></h1>
            <p class="reference">
                <?= $t['booking_reference'] ?>: <strong><?= $view->e($booking['reference']) ?></strong>
            </p>

            <div class="confirmation-details">
                <div class="detail-row">
                    <span class="label">Booking</span>
                    <span class="value"><?= $view->e($itemName) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Date</span>
                    <span class="value"><?= $view->date($booking['booking_date'], 'l, F j, Y') ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Time</span>
                    <span class="value"><?= date('g:i A', strtotime($booking['start_time'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Guests</span>
                    <span class="value"><?= $booking['total_guests'] ?> (<?= $booking['adults'] ?> adults<?= $booking['children'] > 0 ? ', ' . $booking['children'] . ' children' : '' ?>)</span>
                </div>
                <?php if ($booking['hours']): ?>
                    <div class="detail-row">
                        <span class="label">Duration</span>
                        <span class="value"><?= $booking['hours'] ?> hours</span>
                    </div>
                <?php endif; ?>
                <div class="detail-row total">
                    <span class="label">Total</span>
                    <span class="value"><?= $view->price($booking['total_amount']) ?></span>
                </div>
            </div>

            <div class="confirmation-contact">
                <p><?= $t['confirmation_sent'] ?></p>
                <p><strong><?= $view->e($booking['email']) ?></strong></p>
            </div>

            <div class="confirmation-actions">
                <a href="/" class="btn btn-primary">Back to Home</a>
                <a href="https://t.me/<?= $_ENV['TELEGRAM_BOT_USERNAME'] ?? 'phuketyachts_bot' ?>" class="btn btn-outline" target="_blank">
                    Contact Us on Telegram
                </a>
            </div>

            <div class="next-steps">
                <h3>What's Next?</h3>
                <ul>
                    <li>
                        <span class="step-number">1</span>
                        <span>Check your email for booking confirmation</span>
                    </li>
                    <li>
                        <span class="step-number">2</span>
                        <span>We'll contact you to confirm details</span>
                    </li>
                    <li>
                        <span class="step-number">3</span>
                        <span>Complete payment before your trip</span>
                    </li>
                    <li>
                        <span class="step-number">4</span>
                        <span>Enjoy your experience!</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<style>
.confirmation-page {
    padding: 60px 0;
    background: var(--gray-100);
    min-height: calc(100vh - 200px);
}

.confirmation-card {
    max-width: 600px;
    margin: 0 auto;
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: 48px;
    text-align: center;
    box-shadow: var(--shadow-lg);
}

.confirmation-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: #dcfce7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #16a34a;
}

.confirmation-icon svg {
    width: 40px;
    height: 40px;
}

.confirmation-card h1 {
    color: #16a34a;
    margin-bottom: 8px;
}

.reference {
    font-size: 18px;
    color: var(--gray-500);
    margin-bottom: 32px;
}

.reference strong {
    color: var(--dark);
    font-family: monospace;
    font-size: 20px;
}

.confirmation-details {
    text-align: left;
    background: var(--gray-100);
    border-radius: var(--radius-lg);
    padding: 24px;
    margin-bottom: 24px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--gray-200);
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-row .label {
    color: var(--gray-500);
}

.detail-row .value {
    font-weight: 500;
}

.detail-row.total {
    margin-top: 8px;
    padding-top: 16px;
    border-top: 2px solid var(--gray-300);
}

.detail-row.total .value {
    font-size: 20px;
    color: var(--primary);
    font-weight: 700;
}

.confirmation-contact {
    margin-bottom: 24px;
    color: var(--gray-500);
}

.confirmation-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 32px;
}

.next-steps {
    text-align: left;
    padding-top: 32px;
    border-top: 1px solid var(--gray-200);
}

.next-steps h3 {
    margin-bottom: 16px;
}

.next-steps ul {
    list-style: none;
}

.next-steps li {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
}

.step-number {
    width: 28px;
    height: 28px;
    background: var(--primary);
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
    flex-shrink: 0;
}

@media (max-width: 600px) {
    .confirmation-card {
        padding: 32px 20px;
        margin: 0 16px;
    }

    .confirmation-actions {
        flex-direction: column;
    }

    .confirmation-actions .btn {
        width: 100%;
    }
}
</style>
