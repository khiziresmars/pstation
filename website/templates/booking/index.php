<?php
$view->layout('main');
$images = json_decode($item['images'] ?? '[]', true);
$image = $images[0] ?? '/images/placeholder.jpg';
?>

<section class="booking-page">
    <div class="container">
        <div class="booking-layout">
            <!-- Booking Form -->
            <div class="booking-form-section">
                <h1><?= $t['booking_title'] ?></h1>

                <form action="/book/submit" method="POST" class="booking-form" id="bookingForm">
                    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="type" value="<?= $type ?>">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">

                    <!-- Item Summary -->
                    <div class="form-card item-summary">
                        <img src="<?= $image ?>" alt="<?= $view->e($item['name']) ?>" class="item-image">
                        <div class="item-info">
                            <h3><?= $view->e($item['name']) ?></h3>
                            <p class="item-type"><?= ucfirst($type) ?></p>
                        </div>
                    </div>

                    <!-- Date & Time -->
                    <div class="form-card">
                        <h3><?= $t['select_date'] ?></h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label><?= $t['select_date'] ?> *</label>
                                <input type="date" name="date" required min="<?= date('Y-m-d') ?>" value="<?= $_GET['date'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label><?= $t['select_time'] ?></label>
                                <select name="time">
                                    <option value="08:00">08:00 AM</option>
                                    <option value="09:00" selected>09:00 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="13:00">01:00 PM</option>
                                    <option value="14:00">02:00 PM</option>
                                </select>
                            </div>
                        </div>

                        <?php if ($type === 'vessel'): ?>
                            <div class="form-group">
                                <label><?= $t['rental_hours'] ?></label>
                                <select name="hours" id="hoursSelect">
                                    <?php for ($h = 4; $h <= 12; $h++): ?>
                                        <option value="<?= $h ?>" <?= ($h == ($_GET['hours'] ?? 4)) ? 'selected' : '' ?>><?= $h ?> <?= $t['hours'] ?></option>
                                    <?php endfor; ?>
                                    <option value="24">Full Day (24h)</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Guests -->
                    <div class="form-card">
                        <h3><?= $t['select_guests'] ?></h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label><?= $t['adults'] ?></label>
                                <select name="adults" id="adultsSelect">
                                    <?php for ($i = 1; $i <= 20; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($i == ($_GET['adults'] ?? 1)) ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?= $t['children'] ?></label>
                                <select name="children" id="childrenSelect">
                                    <?php for ($i = 0; $i <= 10; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($i == ($_GET['children'] ?? 0)) ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="form-card">
                        <h3><?= $t['your_details'] ?></h3>
                        <div class="form-group">
                            <label><?= $t['full_name'] ?> *</label>
                            <input type="text" name="name" required placeholder="John Smith">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><?= $t['email'] ?> *</label>
                                <input type="email" name="email" required placeholder="john@example.com">
                            </div>
                            <div class="form-group">
                                <label><?= $t['phone'] ?></label>
                                <input type="tel" name="phone" placeholder="+66 XX XXX XXXX">
                            </div>
                        </div>
                    </div>

                    <!-- Promo & Special Requests -->
                    <div class="form-card">
                        <div class="form-group">
                            <label><?= $t['promo_code'] ?></label>
                            <div class="promo-input">
                                <input type="text" name="promo_code" id="promoCode" placeholder="Enter code">
                                <button type="button" class="btn btn-outline" onclick="applyPromo()"><?= $t['apply'] ?></button>
                            </div>
                            <div id="promoMessage" class="promo-message"></div>
                        </div>
                        <div class="form-group">
                            <label><?= $t['special_requests'] ?></label>
                            <textarea name="special_requests" rows="3" placeholder="Any special requirements..."></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block"><?= $t['confirm_booking'] ?></button>
                </form>
            </div>

            <!-- Booking Summary Sidebar -->
            <aside class="booking-summary-section">
                <div class="summary-card sticky">
                    <h3><?= $t['booking_summary'] ?></h3>

                    <div class="summary-item">
                        <img src="<?= $image ?>" alt="<?= $view->e($item['name']) ?>">
                        <div class="summary-item-info">
                            <strong><?= $view->e($item['name']) ?></strong>
                            <span id="summaryDate">-</span>
                        </div>
                    </div>

                    <div class="summary-details">
                        <div class="summary-row">
                            <span><?= $t['guests'] ?></span>
                            <span id="summaryGuests">1</span>
                        </div>
                        <?php if ($type === 'vessel'): ?>
                            <div class="summary-row">
                                <span><?= $t['rental_hours'] ?></span>
                                <span id="summaryHours">4 <?= $t['hours'] ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="summary-pricing">
                        <div class="summary-row">
                            <span><?= $t['subtotal'] ?></span>
                            <span id="summarySubtotal"><?= $view->price($type === 'vessel' ? $item['price_per_hour'] * 4 : $item['price_adult']) ?></span>
                        </div>
                        <div class="summary-row discount" id="discountRow" style="display: none;">
                            <span><?= $t['discount'] ?></span>
                            <span id="summaryDiscount">-฿0</span>
                        </div>
                        <div class="summary-row total">
                            <span><?= $t['total'] ?></span>
                            <span id="summaryTotal"><?= $view->price($type === 'vessel' ? $item['price_per_hour'] * 4 : $item['price_adult']) ?></span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
const itemType = '<?= $type ?>';
const pricePerHour = <?= $item['price_per_hour'] ?? 0 ?>;
const priceAdult = <?= $item['price_adult'] ?? 0 ?>;
const priceChild = <?= $item['price_child'] ?? 0 ?>;

let discount = 0;

function updateSummary() {
    const date = document.querySelector('input[name="date"]').value;
    const adults = parseInt(document.getElementById('adultsSelect').value) || 1;
    const children = parseInt(document.getElementById('childrenSelect').value) || 0;
    const hours = itemType === 'vessel' ? parseInt(document.getElementById('hoursSelect')?.value || 4) : 0;

    // Update display
    document.getElementById('summaryDate').textContent = date || '-';
    document.getElementById('summaryGuests').textContent = adults + children;

    if (document.getElementById('summaryHours')) {
        document.getElementById('summaryHours').textContent = hours + ' hours';
    }

    // Calculate price
    let subtotal = 0;
    if (itemType === 'vessel') {
        subtotal = pricePerHour * hours;
    } else {
        subtotal = (adults * priceAdult) + (children * priceChild);
    }

    const total = Math.max(0, subtotal - discount);

    document.getElementById('summarySubtotal').textContent = '฿' + subtotal.toLocaleString();
    document.getElementById('summaryTotal').textContent = '฿' + total.toLocaleString();

    if (discount > 0) {
        document.getElementById('discountRow').style.display = 'flex';
        document.getElementById('summaryDiscount').textContent = '-฿' + discount.toLocaleString();
    } else {
        document.getElementById('discountRow').style.display = 'none';
    }
}

async function applyPromo() {
    const code = document.getElementById('promoCode').value;
    const msg = document.getElementById('promoMessage');

    if (!code) return;

    try {
        const res = await fetch('/api/promo/validate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code })
        });
        const data = await res.json();

        if (data.success && data.data.valid) {
            discount = data.data.discount_amount || 0;
            msg.className = 'promo-message success';
            msg.textContent = 'Promo applied: ' + data.data.description;
            updateSummary();
        } else {
            msg.className = 'promo-message error';
            msg.textContent = data.data?.message || 'Invalid promo code';
        }
    } catch (e) {
        msg.className = 'promo-message error';
        msg.textContent = 'Error validating code';
    }
}

// Event listeners
document.querySelector('input[name="date"]').addEventListener('change', updateSummary);
document.getElementById('adultsSelect').addEventListener('change', updateSummary);
document.getElementById('childrenSelect').addEventListener('change', updateSummary);
document.getElementById('hoursSelect')?.addEventListener('change', updateSummary);

// Form submission
document.getElementById('bookingForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    try {
        const res = await fetch('/book/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();

        if (result.success) {
            window.location.href = result.redirect;
        } else {
            alert(result.errors?.join('\n') || result.error || 'Booking failed');
        }
    } catch (e) {
        alert('An error occurred. Please try again.');
    }
});

// Initial update
updateSummary();
</script>
