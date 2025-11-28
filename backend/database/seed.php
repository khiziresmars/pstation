<?php

/**
 * Database Seeder
 * Populate database with initial data
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "===========================================\n";
echo "  Phuket Station - Database Seeder\n";
echo "===========================================\n\n";

try {
    // Database connection
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_DATABASE'] ?? 'phuket_yachts';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "[OK] Connected to database: {$dbname}\n\n";

    // ===========================================
    // EXCHANGE RATES
    // ===========================================
    echo "[SEED] Exchange rates... ";
    // Seed exchange rates with currency info
    $rates = [
        ['THB', 'Thai Baht', '฿', 1.0, 1.0],
        ['USD', 'US Dollar', '$', 35.50, 0.0282],
        ['EUR', 'Euro', '€', 38.20, 0.0262],
        ['GBP', 'British Pound', '£', 44.50, 0.0225],
        ['RUB', 'Russian Ruble', '₽', 0.38, 2.63],
        ['CNY', 'Chinese Yuan', '¥', 4.90, 0.204],
        ['AUD', 'Australian Dollar', 'A$', 23.00, 0.0435],
    ];
    foreach ($rates as $rate) {
        $stmt = $pdo->prepare("
            INSERT INTO exchange_rates (currency_code, currency_name, currency_symbol, rate_to_thb, rate_from_thb, is_active, last_updated_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE rate_to_thb = VALUES(rate_to_thb), rate_from_thb = VALUES(rate_from_thb), last_updated_at = NOW()
        ");
        $stmt->execute($rate);
    }
    echo "OK\n";

    // ===========================================
    // SETTINGS
    // ===========================================
    echo "[SEED] App settings... ";
    $settings = [
        ['key' => 'app_name', 'value' => 'Phuket Station', 'type' => 'string'],
        ['key' => 'default_currency', 'value' => 'THB', 'type' => 'string'],
        ['key' => 'cashback_percent', 'value' => '5', 'type' => 'number'],
        ['key' => 'referral_bonus_thb', 'value' => '500', 'type' => 'number'],
        ['key' => 'min_booking_hours', 'value' => '4', 'type' => 'number'],
        ['key' => 'max_booking_days_ahead', 'value' => '90', 'type' => 'number'],
        ['key' => 'cancellation_hours', 'value' => '48', 'type' => 'number'],
        ['key' => 'support_email', 'value' => 'info@phuket-yachts.com', 'type' => 'string'],
        ['key' => 'support_phone', 'value' => '+66 76 123 456', 'type' => 'string'],
        ['key' => 'whatsapp_number', 'value' => '+66812345678', 'type' => 'string'],
        ['key' => 'telegram_support', 'value' => '@PhuketYachtsSupport', 'type' => 'string'],
        ['key' => 'office_hours', 'value' => '08:00-20:00', 'type' => 'string'],
        ['key' => 'tax_rate', 'value' => '7', 'type' => 'number'],
        ['key' => 'deposit_percent', 'value' => '30', 'type' => 'number'],
    ];
    foreach ($settings as $s) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (`key`, `value`, `type`) VALUES (?, ?, ?)");
        $stmt->execute([$s['key'], $s['value'], $s['type']]);
    }
    echo "OK\n";

    // ===========================================
    // SAMPLE VESSELS
    // ===========================================
    echo "[SEED] Sample vessels... ";
    $vessels = [
        [
            'name_en' => 'Ocean Dream Yacht',
            'name_ru' => 'Яхта Океанская Мечта',
            'name_th' => 'เรือยอทช์ Ocean Dream',
            'slug' => 'ocean-dream-yacht',
            'type' => 'yacht',
            'description_en' => 'Luxurious 65ft motor yacht perfect for sunset cruises and island hopping. Features spacious deck, air-conditioned saloon, and professional crew.',
            'description_ru' => 'Роскошная 65-футовая моторная яхта, идеальная для закатных круизов и путешествий по островам. Просторная палуба, кондиционированный салон и профессиональная команда.',
            'description_th' => 'เรือยอทช์มอเตอร์หรูหราขนาด 65 ฟุต เหมาะสำหรับล่องเรือชมพระอาทิตย์ตกและเที่ยวเกาะ',
            'capacity' => 12,
            'length_meters' => 20,
            'price_per_hour_thb' => 15000,
            'price_per_day_thb' => 85000,
            'amenities' => json_encode(['Wi-Fi', 'Air Conditioning', 'Kitchen', 'BBQ', 'Snorkeling Gear', 'Kayak', 'Sound System']),
            'images' => json_encode(['/images/vessels/yacht1.jpg', '/images/vessels/yacht1-2.jpg']),
            'rating' => 4.9,
            'reviews_count' => 47,
            'is_featured' => 1,
        ],
        [
            'name_en' => 'Speed Star',
            'name_ru' => 'Спид Стар',
            'name_th' => 'สปีดสตาร์',
            'slug' => 'speed-star',
            'type' => 'speedboat',
            'description_en' => 'Fast and comfortable speedboat for quick island transfers and day trips. Perfect for Phi Phi and James Bond Island tours.',
            'description_ru' => 'Быстрый и комфортный катер для быстрых трансферов на острова и однодневных поездок.',
            'description_th' => 'สปีดโบ๊ทที่รวดเร็วและสะดวกสบายสำหรับการเดินทางไปเกาะ',
            'capacity' => 8,
            'length_meters' => 12,
            'price_per_hour_thb' => 6000,
            'price_per_day_thb' => 35000,
            'amenities' => json_encode(['Life Jackets', 'Snorkeling Gear', 'Cool Box', 'Sun Shade']),
            'images' => json_encode(['/images/vessels/speedboat1.jpg']),
            'rating' => 4.7,
            'reviews_count' => 89,
            'is_featured' => 1,
        ],
        [
            'name_en' => 'Sunset Catamaran',
            'name_ru' => 'Закатный Катамаран',
            'name_th' => 'คาตามารันพระอาทิตย์ตก',
            'slug' => 'sunset-catamaran',
            'type' => 'catamaran',
            'description_en' => 'Stable and spacious catamaran ideal for groups and families. Perfect for day cruises with excellent stability.',
            'description_ru' => 'Устойчивый и просторный катамаран, идеальный для групп и семей. Отличная стабильность для дневных круизов.',
            'description_th' => 'คาตามารันที่มั่นคงและกว้างขวาง เหมาะสำหรับกลุ่มและครอบครัว',
            'capacity' => 20,
            'length_meters' => 15,
            'price_per_hour_thb' => 12000,
            'price_per_day_thb' => 70000,
            'amenities' => json_encode(['Wi-Fi', 'BBQ', 'Snorkeling Gear', 'Paddle Boards', 'Sound System', 'Trampoline Net']),
            'images' => json_encode(['/images/vessels/catamaran1.jpg']),
            'rating' => 4.8,
            'reviews_count' => 62,
            'is_featured' => 1,
        ],
    ];

    foreach ($vessels as $v) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO vessels
            (name_en, name_ru, name_th, slug, type, description_en, description_ru, description_th,
             capacity, length_meters, price_per_hour_thb, price_per_day_thb, amenities, images,
             rating, reviews_count, is_featured, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->execute([
            $v['name_en'], $v['name_ru'], $v['name_th'], $v['slug'], $v['type'],
            $v['description_en'], $v['description_ru'], $v['description_th'],
            $v['capacity'], $v['length_meters'], $v['price_per_hour_thb'], $v['price_per_day_thb'],
            $v['amenities'], $v['images'], $v['rating'], $v['reviews_count'], $v['is_featured']
        ]);
    }
    echo "OK\n";

    // ===========================================
    // SAMPLE TOURS
    // ===========================================
    echo "[SEED] Sample tours... ";
    $tours = [
        [
            'name_en' => 'Phi Phi Islands Day Trip',
            'name_ru' => 'Однодневный тур на острова Пхи-Пхи',
            'name_th' => 'ทัวร์เกาะพีพีแบบวันเดียว',
            'slug' => 'phi-phi-islands-day-trip',
            'category' => 'island_hopping',
            'description_en' => 'Visit the famous Phi Phi Islands including Maya Bay. Snorkeling, swimming, and lunch included. The most popular tour in Phuket!',
            'description_ru' => 'Посетите знаменитые острова Пхи-Пхи, включая бухту Майя. В стоимость входит снорклинг, плавание и обед.',
            'description_th' => 'เยี่ยมชมหมู่เกาะพีพีที่มีชื่อเสียง รวมถึงอ่าวมาหยา รวมดำน้ำตื้น ว่ายน้ำ และอาหารกลางวัน',
            'duration_hours' => 8,
            'departure_time' => '08:00',
            'adult_price_thb' => 2500,
            'child_price_thb' => 1800,
            'max_capacity' => 40,
            'pickup_available' => 1,
            'pickup_fee_thb' => 300,
            'includes' => json_encode(['Speedboat transfer', 'National park fees', 'Lunch', 'Snorkeling equipment', 'Guide', 'Insurance']),
            'images' => json_encode(['/images/tours/phi-phi-1.jpg', '/images/tours/phi-phi-2.jpg']),
            'rating' => 4.8,
            'reviews_count' => 324,
            'is_featured' => 1,
        ],
        [
            'name_en' => 'James Bond Island Adventure',
            'name_ru' => 'Приключение на острове Джеймса Бонда',
            'name_th' => 'ผจญภัยเกาะเจมส์ บอนด์',
            'slug' => 'james-bond-island-adventure',
            'category' => 'island_hopping',
            'description_en' => 'Explore Phang Nga Bay and the iconic James Bond Island. Includes canoeing through caves and mangroves.',
            'description_ru' => 'Исследуйте залив Пханг Нга и знаменитый остров Джеймса Бонда. Включает каякинг по пещерам и мангровым зарослям.',
            'description_th' => 'สำรวจอ่าวพังงาและเกาะเจมส์ บอนด์ที่โด่งดัง รวมพายเรือคายัคผ่านถ้ำและป่าชายเลน',
            'duration_hours' => 9,
            'departure_time' => '07:30',
            'adult_price_thb' => 2800,
            'child_price_thb' => 2000,
            'max_capacity' => 35,
            'pickup_available' => 1,
            'pickup_fee_thb' => 300,
            'includes' => json_encode(['Longtail boat', 'Canoeing', 'Lunch', 'National park fees', 'Guide', 'Insurance']),
            'images' => json_encode(['/images/tours/james-bond-1.jpg']),
            'rating' => 4.7,
            'reviews_count' => 256,
            'is_featured' => 1,
        ],
        [
            'name_en' => 'Sunset Dinner Cruise',
            'name_ru' => 'Закатный круиз с ужином',
            'name_th' => 'ล่องเรือดินเนอร์ยามพระอาทิตย์ตก',
            'slug' => 'sunset-dinner-cruise',
            'category' => 'sunset_cruise',
            'description_en' => 'Romantic evening cruise along Phuket coastline with gourmet dinner and live music. Perfect for couples.',
            'description_ru' => 'Романтический вечерний круиз вдоль побережья Пхукета с изысканным ужином и живой музыкой.',
            'description_th' => 'ล่องเรือยามเย็นโรแมนติกตามแนวชายฝั่งภูเก็ต พร้อมอาหารค่ำรสเลิศและดนตรีสด',
            'duration_hours' => 3,
            'departure_time' => '17:30',
            'adult_price_thb' => 3500,
            'child_price_thb' => 2500,
            'max_capacity' => 60,
            'pickup_available' => 1,
            'pickup_fee_thb' => 200,
            'includes' => json_encode(['Welcome drink', '4-course dinner', 'Live music', 'Sunset viewing']),
            'images' => json_encode(['/images/tours/sunset-cruise-1.jpg']),
            'rating' => 4.9,
            'reviews_count' => 189,
            'is_featured' => 1,
        ],
    ];

    foreach ($tours as $t) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO tours
            (name_en, name_ru, name_th, slug, category, description_en, description_ru, description_th,
             duration_hours, departure_time, adult_price_thb, child_price_thb, max_capacity,
             pickup_available, pickup_fee_thb, includes, images, rating, reviews_count,
             is_featured, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->execute([
            $t['name_en'], $t['name_ru'], $t['name_th'], $t['slug'], $t['category'],
            $t['description_en'], $t['description_ru'], $t['description_th'],
            $t['duration_hours'], $t['departure_time'], $t['adult_price_thb'], $t['child_price_thb'],
            $t['max_capacity'], $t['pickup_available'], $t['pickup_fee_thb'], $t['includes'],
            $t['images'], $t['rating'], $t['reviews_count'], $t['is_featured']
        ]);
    }
    echo "OK\n";

    // ===========================================
    // SAMPLE ADDONS
    // ===========================================
    echo "[SEED] Sample addons... ";
    $addons = [
        ['name_en' => 'Professional Photographer', 'price_thb' => 5000, 'category' => 'photography', 'pricing_type' => 'fixed'],
        ['name_en' => 'Drone Photography', 'price_thb' => 3000, 'category' => 'photography', 'pricing_type' => 'fixed'],
        ['name_en' => 'Premium Champagne', 'price_thb' => 4500, 'category' => 'food_drinks', 'pricing_type' => 'fixed'],
        ['name_en' => 'Seafood BBQ', 'price_thb' => 800, 'category' => 'food_drinks', 'pricing_type' => 'per_person'],
        ['name_en' => 'Jet Ski (1 hour)', 'price_thb' => 2500, 'category' => 'water_sports', 'pricing_type' => 'per_hour'],
        ['name_en' => 'Paddle Board', 'price_thb' => 500, 'category' => 'water_sports', 'pricing_type' => 'per_hour'],
        ['name_en' => 'Fishing Equipment', 'price_thb' => 1500, 'category' => 'activities', 'pricing_type' => 'fixed'],
        ['name_en' => 'Birthday Decoration', 'price_thb' => 3000, 'category' => 'special_occasions', 'pricing_type' => 'fixed'],
    ];

    foreach ($addons as $a) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO addons (name_en, price_thb, category, pricing_type, is_active, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$a['name_en'], $a['price_thb'], $a['category'], $a['pricing_type']]);
    }
    echo "OK\n";

    // ===========================================
    // ADMIN USER
    // ===========================================
    echo "[SEED] Admin user... ";
    $adminPassword = password_hash('admin', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO admins (email, password_hash, name, role, is_active, created_at, updated_at)
        VALUES ('admin@admin.com', ?, 'Administrator', 'super_admin', 1, NOW(), NOW())
    ");
    $stmt->execute([$adminPassword]);
    echo "OK\n";

    echo "\n[DONE] Database seeded successfully!\n";
    echo "\nAdmin credentials:\n";
    echo "  Email:    admin@admin.com\n";
    echo "  Password: admin\n";
    echo "\n";

} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
