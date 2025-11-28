-- ============================================
-- Phuket Yacht & Tours Seed Data
-- Realistic data for Phuket yacht rentals
-- ============================================

USE phuket_yachts;

-- ============================================
-- VESSELS DATA (8 units)
-- ============================================

-- 1. Luxury Yacht - Ocean Paradise (80ft)
INSERT INTO vessels (
    type, name, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    capacity, cabins, length_meters, year_built, manufacturer, model,
    features, amenities, crew_info,
    price_per_hour_thb, price_per_day_thb, price_half_day_thb,
    min_rental_hours, location, home_port,
    captain_included, fuel_included, fuel_policy, insurance_included,
    images, thumbnail, is_featured, is_active, rating, reviews_count
) VALUES (
    'yacht', 'Ocean Paradise', 'ocean-paradise',
    'Experience ultimate luxury aboard Ocean Paradise, an 80-foot masterpiece of maritime excellence. This stunning yacht features 4 spacious cabins, a state-of-the-art entertainment system, and panoramic views from the sun deck. Perfect for corporate events, celebrations, or an unforgettable day exploring Phuket''s crystal-clear waters. The professional crew ensures impeccable service throughout your journey.',
    'Испытайте непревзойденную роскошь на борту Ocean Paradise — 80-футового шедевра морского мастерства. Эта потрясающая яхта оснащена 4 просторными каютами, современной развлекательной системой и панорамным видом с солнечной палубы. Идеально подходит для корпоративных мероприятий, праздников или незабываемого дня, исследуя кристально чистые воды Пхукета.',
    'สัมผัสความหรูหราสูงสุดบน Ocean Paradise เรือยอทช์ขนาด 80 ฟุตที่เป็นผลงานชิ้นเอกแห่งความเป็นเลิศทางทะเล',
    'Luxury 80ft yacht with 4 cabins, perfect for exclusive events and island hopping',
    'Люксовая яхта 80 футов с 4 каютами, идеальна для эксклюзивных мероприятий',
    20, 4, 24.38, 2021, 'Sunseeker', 'Predator 80',
    '["Flybridge", "Jacuzzi", "Stabilizers", "Air Conditioning", "WiFi", "Satellite TV", "Water Sports Equipment", "Snorkeling Gear", "Jet Ski", "Tender Boat"]',
    '["Master Suite", "3 Guest Cabins", "Full Kitchen", "BBQ Grill", "Ice Maker", "Bluetooth Sound System", "Sun Loungers", "Shaded Dining Area", "Swimming Platform"]',
    '{"captain": "Captain James - 15 years experience", "crew_size": 4, "chef": true, "hostess": true}',
    45000.00, 320000.00, 180000.00,
    4, 'Royal Phuket Marina', 'Royal Phuket Marina',
    TRUE, FALSE, 'Fuel consumption approximately 150L/hour, charged separately', TRUE,
    '["https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?w=800", "https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?w=800", "https://images.unsplash.com/photo-1605281317010-fe5ece838f55?w=800", "https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=800"]',
    'https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?w=400',
    TRUE, TRUE, 4.9, 47
);

-- 2. Luxury Yacht - Royal Sunset (65ft)
INSERT INTO vessels (
    type, name, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    capacity, cabins, length_meters, year_built, manufacturer, model,
    features, amenities, crew_info,
    price_per_hour_thb, price_per_day_thb, price_half_day_thb,
    min_rental_hours, location, home_port,
    captain_included, fuel_included, fuel_policy, insurance_included,
    images, thumbnail, is_featured, is_active, rating, reviews_count
) VALUES (
    'yacht', 'Royal Sunset', 'royal-sunset',
    'Royal Sunset is a magnificent 65-foot motor yacht combining Italian elegance with modern performance. Featuring 3 luxurious cabins, a spacious salon, and an impressive flybridge, this vessel is ideal for intimate gatherings or romantic getaways. Watch breathtaking Andaman sunsets while enjoying world-class amenities and attentive crew service.',
    'Royal Sunset — великолепная 65-футовая моторная яхта, сочетающая итальянскую элегантность с современными характеристиками. С 3 роскошными каютами, просторным салоном и впечатляющим флайбриджем, это судно идеально подходит для камерных встреч или романтического отдыха.',
    'Royal Sunset เป็นเรือยอทช์มอเตอร์ขนาด 65 ฟุตที่งดงามผสมผสานความสง่างามแบบอิตาลีกับสมรรถนะที่ทันสมัย',
    'Elegant 65ft Italian yacht, perfect for romantic getaways and sunset cruises',
    'Элегантная итальянская яхта 65 футов для романтического отдыха',
    15, 3, 19.81, 2019, 'Azimut', '66 Fly',
    '["Flybridge", "Air Conditioning", "WiFi", "Apple TV", "Bose Sound System", "Snorkeling Equipment", "Paddleboards", "Fishing Gear"]',
    '["Master Cabin with Ensuite", "2 Guest Cabins", "Fully Equipped Galley", "Wet Bar", "Sun Pads", "Alfresco Dining", "Swim Platform with Ladder"]',
    '{"captain": "Captain Marco - 12 years experience", "crew_size": 3, "chef": true, "hostess": false}',
    35000.00, 250000.00, 140000.00,
    4, 'Ao Po Grand Marina', 'Ao Po Grand Marina',
    TRUE, FALSE, 'Fuel at charterer expense, approximately 120L/hour', TRUE,
    '["https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?w=800", "https://images.unsplash.com/photo-1540946485063-a40da27545f8?w=800", "https://images.unsplash.com/photo-1605281317010-fe5ece838f55?w=800"]',
    'https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?w=400',
    TRUE, TRUE, 4.8, 32
);

-- 3. Speedboat - Thunder Wave (38ft)
INSERT INTO vessels (
    type, name, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    capacity, cabins, length_meters, year_built, manufacturer, model,
    features, amenities, crew_info,
    price_per_hour_thb, price_per_day_thb, price_half_day_thb,
    min_rental_hours, location, home_port,
    captain_included, fuel_included, fuel_policy, insurance_included,
    images, thumbnail, is_featured, is_active, rating, reviews_count
) VALUES (
    'speedboat', 'Thunder Wave', 'thunder-wave',
    'Thunder Wave is a powerful 38-foot speedboat designed for those who crave adventure and speed. With twin 300HP engines, this boat can whisk you to Phi Phi Islands in just 45 minutes. Features comfortable seating for 12 guests, a sundeck, and all necessary safety equipment. Perfect for island hopping, snorkeling trips, or fishing expeditions.',
    'Thunder Wave — мощный 38-футовый скоростной катер для тех, кто жаждет приключений и скорости. С двумя двигателями по 300 л.с. этот катер доставит вас на острова Пхи-Пхи всего за 45 минут. Комфортные сиденья на 12 гостей, солнечная палуба и всё необходимое оборудование.',
    'Thunder Wave เป็นเรือสปีดโบ๊ทขนาด 38 ฟุตที่ทรงพลังออกแบบมาสำหรับผู้ที่ชื่นชอบการผจญภัยและความเร็ว',
    'Fast 38ft speedboat with twin engines, reach Phi Phi in 45 minutes',
    'Быстрый катер 38 футов, до Пхи-Пхи за 45 минут',
    12, 0, 11.58, 2022, 'Nor-Tech', '390 Sport',
    '["Twin 300HP Engines", "GPS Navigation", "Fish Finder", "Bluetooth Audio", "Snorkeling Gear", "Cooler Box", "Sunshade"]',
    '["Cushioned Seating", "Sundeck", "Fresh Water Shower", "Marine Toilet", "Ice Box", "First Aid Kit"]',
    '{"captain": "Captain Som - 8 years experience", "crew_size": 2, "chef": false, "hostess": false}',
    8000.00, 55000.00, 32000.00,
    4, 'Chalong Pier', 'Chalong Pier',
    TRUE, TRUE, 'Fuel included up to 8 hours', TRUE,
    '["https://images.unsplash.com/photo-1605281317010-fe5ece838f55?w=800", "https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=800", "https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800"]',
    'https://images.unsplash.com/photo-1605281317010-fe5ece838f55?w=400',
    TRUE, TRUE, 4.7, 89
);

-- 4. Speedboat - Sea Spirit (35ft)
INSERT INTO vessels (
    type, name, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    capacity, cabins, length_meters, year_built, manufacturer, model,
    features, amenities, crew_info,
    price_per_hour_thb, price_per_day_thb, price_half_day_thb,
    min_rental_hours, location, home_port,
    captain_included, fuel_included, fuel_policy, insurance_included,
    images, thumbnail, is_featured, is_active, rating, reviews_count
) VALUES (
    'speedboat', 'Sea Spirit', 'sea-spirit',
    'Sea Spirit offers the perfect balance of speed and comfort for your Phuket adventure. This well-maintained 35-foot speedboat comfortably accommodates 10 guests and is equipped with everything needed for a perfect day at sea. Ideal for family trips, snorkeling excursions, or visiting nearby islands.',
    'Sea Spirit предлагает идеальный баланс скорости и комфорта для вашего приключения на Пхукете. Этот ухоженный 35-футовый катер комфортно вмещает 10 гостей и оснащён всем необходимым для идеального дня на море.',
    'Sea Spirit มอบความสมดุลที่ลงตัวของความเร็วและความสะดวกสบายสำหรับการผจญภัยในภูเก็ตของคุณ',
    'Comfortable 35ft speedboat for 10 guests, ideal for family trips',
    'Комфортный катер 35 футов на 10 гостей, идеален для семей',
    10, 0, 10.67, 2021, 'Boston Whaler', '350 Realm',
    '["Twin 250HP Engines", "GPS", "VHF Radio", "Snorkeling Equipment", "Life Jackets", "Sunshade Canopy"]',
    '["Comfortable Bench Seating", "Bow Sundeck", "Fresh Water Rinse", "Portable Toilet", "Cooler", "USB Charging"]',
    '{"captain": "Captain Noi - 10 years experience", "crew_size": 2, "chef": false, "hostess": false}',
    6500.00, 45000.00, 26000.00,
    4, 'Chalong Pier', 'Chalong Pier',
    TRUE, TRUE, 'Fuel included', TRUE,
    '["https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800", "https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=800"]',
    'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=400',
    FALSE, TRUE, 4.6, 56
);

-- 5. Speedboat - Island Hopper (32ft)
INSERT INTO vessels (
    type, name, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    capacity, cabins, length_meters, year_built, manufacturer, model,
    features, amenities, crew_info,
    price_per_hour_thb, price_per_day_thb, price_half_day_thb,
    min_rental_hours, location, home_port,
    captain_included, fuel_included, fuel_policy, insurance_included,
    images, thumbnail, is_featured, is_active, rating, reviews_count
) VALUES (
    'speedboat', 'Island Hopper', 'island-hopper',
    'Island Hopper is your affordable gateway to Phuket''s stunning islands. This reliable 32-foot speedboat is perfect for budget-conscious travelers who don''t want to compromise on experience. Visit Coral Island, Racha Islands, or enjoy a sunset cruise without breaking the bank.',
    'Island Hopper — ваш доступный путь к потрясающим островам Пхукета. Этот надёжный 32-футовый катер идеален для путешественников с ограниченным бюджетом, которые не хотят жертвовать качеством.',
    'Island Hopper เป็นประตูสู่เกาะที่สวยงามของภูเก็ตในราคาที่เอื้อมถึง',
    'Budget-friendly 32ft speedboat, perfect for island day trips',
    'Бюджетный катер 32 фута для однодневных поездок на острова',
    8, 0, 9.75, 2020, 'Sea Ray', 'SLX 310',
    '["Twin 200HP Engines", "GPS Navigation", "Snorkeling Gear", "Safety Equipment"]',
    '["Sun Canopy", "Cushioned Seating", "Cooler Box", "Swimming Ladder"]',
    '{"captain": "Captain Chai - 6 years experience", "crew_size": 1, "chef": false, "hostess": false}',
    5000.00, 35000.00, 20000.00,
    4, 'Rawai Beach', 'Rawai Beach',
    TRUE, TRUE, 'Fuel included', TRUE,
    '["https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=800", "https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800"]',
    'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=400',
    FALSE, TRUE, 4.5, 124
);

-- 6. Catamaran - Blue Horizon (45ft)
INSERT INTO vessels (
    type, name, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    capacity, cabins, length_meters, year_built, manufacturer, model,
    features, amenities, crew_info,
    price_per_hour_thb, price_per_day_thb, price_half_day_thb,
    min_rental_hours, location, home_port,
    captain_included, fuel_included, fuel_policy, insurance_included,
    images, thumbnail, is_featured, is_active, rating, reviews_count
) VALUES (
    'catamaran', 'Blue Horizon', 'blue-horizon',
    'Blue Horizon is a spacious 45-foot sailing catamaran offering exceptional stability and comfort. With its twin hulls, this vessel provides a smooth, motion-sickness-free experience perfect for families and groups. Features 4 cabins, a large trampoline net for sunbathing, and plenty of deck space for socializing.',
    'Blue Horizon — просторный 45-футовый парусный катамаран с исключительной стабильностью и комфортом. Благодаря двум корпусам это судно обеспечивает плавное путешествие без морской болезни, идеально для семей и групп.',
    'Blue Horizon เป็นเรือใบคาตามารันขนาด 45 ฟุตที่กว้างขวางพร้อมความมั่นคงและความสะดวกสบายที่ยอดเยี่ยม',
    'Stable 45ft catamaran with 4 cabins, perfect for families',
    'Стабильный катамаран 45 футов с 4 каютами для семей',
    18, 4, 13.72, 2020, 'Lagoon', '450',
    '["Sailing Rig", "Electric Winches", "Solar Panels", "Watermaker", "Snorkeling Gear", "Kayaks", "Paddleboards"]',
    '["4 Double Cabins", "4 Bathrooms", "Spacious Salon", "Outdoor Dining", "Trampoline Net", "BBQ", "Full Kitchen"]',
    '{"captain": "Captain Pierre - 20 years experience", "crew_size": 3, "chef": true, "hostess": true}',
    18000.00, 130000.00, 75000.00,
    4, 'Yacht Haven Marina', 'Yacht Haven Marina',
    TRUE, FALSE, 'Fuel for engine use charged separately', TRUE,
    '["https://images.unsplash.com/photo-1540946485063-a40da27545f8?w=800", "https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=800", "https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?w=800"]',
    'https://images.unsplash.com/photo-1540946485063-a40da27545f8?w=400',
    TRUE, TRUE, 4.9, 38
);

-- 7. Catamaran - Wind Dancer (40ft)
INSERT INTO vessels (
    type, name, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    capacity, cabins, length_meters, year_built, manufacturer, model,
    features, amenities, crew_info,
    price_per_hour_thb, price_per_day_thb, price_half_day_thb,
    min_rental_hours, location, home_port,
    captain_included, fuel_included, fuel_policy, insurance_included,
    images, thumbnail, is_featured, is_active, rating, reviews_count
) VALUES (
    'catamaran', 'Wind Dancer', 'wind-dancer',
    'Wind Dancer combines the thrill of sailing with modern comfort. This 40-foot catamaran is perfect for those seeking an authentic sailing experience while enjoying Phuket''s beautiful coastline. Great for day trips or overnight adventures to nearby islands.',
    'Wind Dancer сочетает острые ощущения от парусного спорта с современным комфортом. Этот 40-футовый катамаран идеален для тех, кто ищет настоящий парусный опыт, наслаждаясь красивым побережьем Пхукета.',
    'Wind Dancer ผสมผสานความตื่นเต้นของการแล่นเรือใบกับความสะดวกสบายสมัยใหม่',
    'Authentic 40ft sailing catamaran for day trips or overnight',
    'Парусный катамаран 40 футов для дневных или ночных поездок',
    12, 3, 12.19, 2018, 'Fountaine Pajot', 'Lucia 40',
    '["Full Sailing Rig", "Autopilot", "Chartplotter", "Snorkeling Equipment", "Fishing Gear"]',
    '["3 Cabins", "2 Bathrooms", "Salon with Galley", "Cockpit Dining", "Sunbathing Areas"]',
    '{"captain": "Captain Alex - 15 years experience", "crew_size": 2, "chef": false, "hostess": false}',
    12000.00, 85000.00, 50000.00,
    4, 'Ao Po Grand Marina', 'Ao Po Grand Marina',
    TRUE, FALSE, 'Fuel for engine charged separately', TRUE,
    '["https://images.unsplash.com/photo-1540946485063-a40da27545f8?w=800", "https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?w=800"]',
    'https://images.unsplash.com/photo-1540946485063-a40da27545f8?w=400',
    FALSE, TRUE, 4.7, 29
);

-- 8. Sailboat - Sea Breeze (52ft)
INSERT INTO vessels (
    type, name, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    capacity, cabins, length_meters, year_built, manufacturer, model,
    features, amenities, crew_info,
    price_per_hour_thb, price_per_day_thb, price_half_day_thb,
    min_rental_hours, location, home_port,
    captain_included, fuel_included, fuel_policy, insurance_included,
    images, thumbnail, is_featured, is_active, rating, reviews_count
) VALUES (
    'sailboat', 'Sea Breeze', 'sea-breeze',
    'Sea Breeze is a classic 52-foot sailing yacht that embodies the romance of traditional sailing. Feel the wind in your sails as you glide through the Andaman Sea in style. This elegant vessel features 3 comfortable cabins, teak decks, and modern navigation equipment while maintaining its classic charm.',
    'Sea Breeze — классическая 52-футовая парусная яхта, воплощающая романтику традиционного мореплавания. Почувствуйте ветер в парусах, скользя по Андаманскому морю с элегантностью. 3 комфортные каюты, тиковые палубы и современное навигационное оборудование.',
    'Sea Breeze เป็นเรือใบคลาสสิกขนาด 52 ฟุตที่รวบรวมความโรแมนติกของการแล่นเรือใบแบบดั้งเดิม',
    'Classic 52ft sailing yacht for romantic sailing adventures',
    'Классическая парусная яхта 52 фута для романтических приключений',
    10, 3, 15.85, 2015, 'Beneteau', 'Oceanis 52',
    '["Full Sail Inventory", "Electric Winches", "Bow Thruster", "Chartplotter", "Radar", "Snorkeling Gear", "Dinghy with Outboard"]',
    '["3 Double Cabins", "2 Heads with Showers", "Fully Equipped Galley", "Teak Cockpit", "Bimini Top", "Cockpit Table"]',
    '{"captain": "Captain David - 25 years experience", "crew_size": 2, "chef": false, "hostess": false}',
    15000.00, 95000.00, 55000.00,
    4, 'Royal Phuket Marina', 'Royal Phuket Marina',
    TRUE, FALSE, 'Fuel for engine charged separately', TRUE,
    '["https://images.unsplash.com/photo-1540946485063-a40da27545f8?w=800", "https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=800", "https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?w=800"]',
    'https://images.unsplash.com/photo-1540946485063-a40da27545f8?w=400',
    FALSE, TRUE, 4.8, 21
);

-- ============================================
-- TOURS DATA (6 tours)
-- ============================================

-- 1. Phi Phi Islands Tour
INSERT INTO tours (
    category, name_en, name_ru, name_th, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    duration_hours, departure_time, return_time,
    includes, excludes, itinerary, highlights,
    meeting_point, meeting_point_coordinates, pickup_available, pickup_fee_thb,
    min_participants, max_participants, min_age, difficulty_level,
    price_adult_thb, price_child_thb, child_age_from, child_age_to, infant_free,
    private_charter_price_thb, images, thumbnail,
    schedule, is_featured, is_active, rating, reviews_count
) VALUES (
    'islands', 'Phi Phi Islands Premium Tour', 'Премиум тур на острова Пхи-Пхи', 'ทัวร์หมู่เกาะพีพีพรีเมียม', 'phi-phi-islands-premium',
    'Discover the world-famous Phi Phi Islands on our premium speedboat tour. Visit Maya Bay (made famous by "The Beach"), snorkel in crystal-clear waters at Pileh Lagoon, explore Viking Cave, and enjoy a delicious Thai lunch on Phi Phi Don. Our small group size ensures a personalized experience away from the crowds.',
    'Откройте для себя всемирно известные острова Пхи-Пхи в нашем премиальном туре на скоростном катере. Посетите бухту Майя (прославленную фильмом "Пляж"), поплавайте с маской в кристально чистых водах лагуны Пиле, исследуйте Пещеру Викингов и насладитесь вкусным тайским обедом на Пхи-Пхи Дон.',
    'ค้นพบหมู่เกาะพีพีที่มีชื่อเสียงระดับโลกในทัวร์สปีดโบ๊ทพรีเมียมของเรา',
    'Full-day speedboat tour to Phi Phi Islands with Maya Bay, snorkeling, and lunch',
    'Полнодневный тур на Пхи-Пхи с Майя Бэй, снорклингом и обедом',
    8.0, '07:30:00', '17:30:00',
    '["Hotel pickup & drop-off", "Speedboat transportation", "Professional English/Russian speaking guide", "National park fees", "Snorkeling equipment", "Life jackets", "Thai buffet lunch", "Soft drinks & water", "Fresh fruits", "Insurance"]',
    '["Alcoholic beverages", "Personal expenses", "Tips for crew", "Professional photos (available for purchase)"]',
    '[{"time": "07:30", "activity": "Hotel pickup from Phuket", "duration": 60}, {"time": "08:30", "activity": "Departure from Rassada Pier", "duration": 45}, {"time": "09:15", "activity": "Arrive at Maya Bay - swimming & photos", "duration": 45}, {"time": "10:00", "activity": "Pileh Lagoon - snorkeling in turquoise waters", "duration": 45}, {"time": "10:45", "activity": "Viking Cave - sightseeing from boat", "duration": 15}, {"time": "11:00", "activity": "Monkey Beach - optional swimming", "duration": 30}, {"time": "11:30", "activity": "Phi Phi Don - lunch & free time", "duration": 120}, {"time": "13:30", "activity": "Bamboo Island - beach relaxation & snorkeling", "duration": 90}, {"time": "15:00", "activity": "Return journey to Phuket", "duration": 45}, {"time": "17:30", "activity": "Arrive at pier & hotel transfer", "duration": 60}]',
    '["Maya Bay - The Beach filming location", "Snorkeling at Pileh Lagoon", "Viking Cave ancient paintings", "Lunch at Phi Phi Don village", "Bamboo Island white sand beach"]',
    'Rassada Pier, Phuket', '{"lat": 7.8673, "lng": 98.3923}', TRUE, 400.00,
    2, 25, 4, 'easy',
    2500.00, 1800.00, 4, 11, TRUE,
    45000.00,
    '["https://images.unsplash.com/photo-1537956965359-7573183d8f53?w=800", "https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?w=800", "https://images.unsplash.com/photo-1519451241324-20b4ea2c4220?w=800", "https://images.unsplash.com/photo-1506665531195-3566af2b4dfa?w=800"]',
    'https://images.unsplash.com/photo-1537956965359-7573183d8f53?w=400',
    '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]',
    TRUE, TRUE, 4.8, 342
);

-- 2. James Bond Island Tour
INSERT INTO tours (
    category, name_en, name_ru, name_th, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    duration_hours, departure_time, return_time,
    includes, excludes, itinerary, highlights,
    meeting_point, meeting_point_coordinates, pickup_available, pickup_fee_thb,
    min_participants, max_participants, min_age, difficulty_level,
    price_adult_thb, price_child_thb, child_age_from, child_age_to, infant_free,
    private_charter_price_thb, images, thumbnail,
    schedule, is_featured, is_active, rating, reviews_count
) VALUES (
    'islands', 'James Bond Island & Phang Nga Bay', 'Остров Джеймса Бонда и залив Пханг Нга', 'เกาะเจมส์บอนด์และอ่าวพังงา', 'james-bond-island',
    'Explore the stunning Phang Nga Bay, home to the iconic James Bond Island (Khao Phing Kan) featured in "The Man with the Golden Gun". Kayak through magical sea caves and mangrove forests, visit the floating Muslim village of Koh Panyee, and marvel at the dramatic limestone karsts rising from emerald waters.',
    'Исследуйте потрясающий залив Пханг Нга, где находится легендарный остров Джеймса Бонда (Као Пинг Кан) из фильма "Человек с золотым пистолетом". Каякинг через волшебные морские пещеры, посещение плавучей мусульманской деревни Ко Паньи и величественные известняковые скалы.',
    'สำรวจอ่าวพังงาที่สวยงาม บ้านของเกาะเจมส์บอนด์อันเป็นสัญลักษณ์',
    'Phang Nga Bay tour with James Bond Island, sea kayaking, and floating village',
    'Тур по заливу Пханг Нга с островом Джеймса Бонда и каякингом',
    9.0, '07:00:00', '18:00:00',
    '["Hotel pickup & drop-off", "Longtail boat & speedboat", "Sea kayaking with guide", "Professional guide", "National park fees", "Thai lunch at Koh Panyee", "Drinking water & soft drinks", "Insurance"]',
    '["Personal expenses", "Tips", "Professional photos"]',
    '[{"time": "07:00", "activity": "Hotel pickup", "duration": 60}, {"time": "08:00", "activity": "Arrive at Ao Po Pier", "duration": 15}, {"time": "08:15", "activity": "Speedboat to Phang Nga Bay", "duration": 30}, {"time": "08:45", "activity": "Sea kayaking through caves & mangroves", "duration": 90}, {"time": "10:15", "activity": "James Bond Island exploration", "duration": 60}, {"time": "11:15", "activity": "Koh Panyee floating village & lunch", "duration": 90}, {"time": "12:45", "activity": "Swimming & relaxation at Hong Island", "duration": 60}, {"time": "13:45", "activity": "Return journey with scenic views", "duration": 45}, {"time": "18:00", "activity": "Hotel drop-off", "duration": 0}]',
    '["James Bond Island - iconic limestone pinnacle", "Sea cave kayaking adventure", "Koh Panyee floating village", "Dramatic limestone karst scenery", "Mangrove forest exploration"]',
    'Ao Po Pier', '{"lat": 8.0589, "lng": 98.4347}', TRUE, 400.00,
    2, 20, 4, 'easy',
    2200.00, 1600.00, 4, 11, TRUE,
    38000.00,
    '["https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?w=800", "https://images.unsplash.com/photo-1519451241324-20b4ea2c4220?w=800", "https://images.unsplash.com/photo-1506665531195-3566af2b4dfa?w=800"]',
    'https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?w=400',
    '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]',
    TRUE, TRUE, 4.7, 256
);

-- 3. Similan Islands Tour
INSERT INTO tours (
    category, name_en, name_ru, name_th, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    duration_hours, departure_time, return_time,
    includes, excludes, itinerary, highlights,
    meeting_point, meeting_point_coordinates, pickup_available, pickup_fee_thb,
    min_participants, max_participants, min_age, difficulty_level,
    price_adult_thb, price_child_thb, child_age_from, child_age_to, infant_free,
    private_charter_price_thb, images, thumbnail,
    schedule, is_featured, is_active, rating, reviews_count
) VALUES (
    'snorkeling', 'Similan Islands Snorkeling Adventure', 'Снорклинг на Симиланских островах', 'ทัวร์ดำน้ำตื้นหมู่เกาะสิมิลัน', 'similan-islands',
    'Experience Thailand''s premier snorkeling destination - the Similan Islands National Park. These pristine islands offer some of the clearest waters and most diverse marine life in Southeast Asia. Swim alongside colorful tropical fish, sea turtles, and explore vibrant coral reefs. The journey includes visits to famous landmarks like Sail Rock and Donald Duck Bay.',
    'Испытайте лучшее место для снорклинга в Таиланде — Национальный парк Симиланских островов. Эти нетронутые острова предлагают одни из самых чистых вод и разнообразную морскую жизнь в Юго-Восточной Азии. Плавайте рядом с красочными тропическими рыбами, морскими черепахами и исследуйте коралловые рифы.',
    'สัมผัสประสบการณ์ดำน้ำตื้นระดับพรีเมียมของประเทศไทย - อุทยานแห่งชาติหมู่เกาะสิมิลัน',
    'Full-day snorkeling at pristine Similan Islands with crystal clear waters',
    'Снорклинг на нетронутых Симиланских островах с кристально чистой водой',
    11.0, '05:30:00', '19:30:00',
    '["Hotel pickup & drop-off", "Speedboat transportation", "Professional snorkeling guide", "National park fees", "Premium snorkeling equipment", "Breakfast & lunch", "Drinking water & soft drinks", "Fresh fruits", "Insurance"]',
    '["Wetsuit rental (300 THB)", "Underwater camera rental", "Tips"]',
    '[{"time": "05:30", "activity": "Hotel pickup from Phuket/Khao Lak", "duration": 90}, {"time": "07:00", "activity": "Breakfast at Tab Lamu Pier", "duration": 30}, {"time": "07:30", "activity": "Speedboat departure to Similan", "duration": 70}, {"time": "08:40", "activity": "Snorkeling at Island 9 (Koh Ba Ngu)", "duration": 45}, {"time": "09:25", "activity": "Island 8 - Sail Rock viewpoint", "duration": 60}, {"time": "10:25", "activity": "Snorkeling at Island 7", "duration": 45}, {"time": "11:10", "activity": "Lunch at Island 4 (Koh Miang)", "duration": 75}, {"time": "12:25", "activity": "Beach time & nature trail", "duration": 45}, {"time": "13:10", "activity": "Donald Duck Bay snorkeling", "duration": 60}, {"time": "14:10", "activity": "Return journey to mainland", "duration": 70}, {"time": "19:30", "activity": "Hotel drop-off", "duration": 0}]',
    '["World-class snorkeling with sea turtles", "Sail Rock iconic viewpoint", "Donald Duck Bay pristine beach", "Crystal clear visibility up to 30m", "Diverse coral reefs and marine life"]',
    'Tab Lamu Pier, Phang Nga', '{"lat": 8.5823, "lng": 98.2234}', TRUE, 500.00,
    4, 30, 6, 'moderate',
    3500.00, 2500.00, 6, 11, FALSE,
    75000.00,
    '["https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800", "https://images.unsplash.com/photo-1519451241324-20b4ea2c4220?w=800", "https://images.unsplash.com/photo-1506665531195-3566af2b4dfa?w=800"]',
    'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=400',
    '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]',
    TRUE, TRUE, 4.9, 189
);

-- 4. Coral Island Snorkeling
INSERT INTO tours (
    category, name_en, name_ru, name_th, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    duration_hours, departure_time, return_time,
    includes, excludes, itinerary, highlights,
    meeting_point, meeting_point_coordinates, pickup_available, pickup_fee_thb,
    min_participants, max_participants, min_age, difficulty_level,
    price_adult_thb, price_child_thb, child_age_from, child_age_to, infant_free,
    private_charter_price_thb, images, thumbnail,
    schedule, is_featured, is_active, rating, reviews_count
) VALUES (
    'snorkeling', 'Coral Island Beach & Snorkeling', 'Коралловый остров: пляж и снорклинг', 'เกาะเฮ ชายหาดและดำน้ำตื้น', 'coral-island-snorkeling',
    'Escape to the beautiful Coral Island (Koh Hae), just 15 minutes from Phuket! This family-friendly tour offers pristine white sand beaches, excellent snorkeling opportunities, and various water activities. Perfect for those who want a relaxing beach day without a long boat journey. Optional activities include parasailing, banana boat, and sea walking.',
    'Отправляйтесь на красивый Коралловый остров (Ко Хе), всего в 15 минутах от Пхукета! Этот семейный тур предлагает нетронутые белые песчаные пляжи, отличный снорклинг и различные водные развлечения. Идеально для тех, кто хочет расслабляющий пляжный день без долгого путешествия.',
    'หนีไปยังเกาะเฮที่สวยงาม ห่างจากภูเก็ตเพียง 15 นาที',
    'Half-day trip to Coral Island with beach time and snorkeling',
    'Полудневная поездка на Коралловый остров с пляжем и снорклингом',
    5.0, '09:00:00', '15:00:00',
    '["Hotel pickup & drop-off", "Speedboat transfer", "Snorkeling equipment", "Beach chairs & umbrella", "Lunch", "Drinking water", "Insurance"]',
    '["Water sports activities", "Alcoholic drinks", "Tips"]',
    '[{"time": "09:00", "activity": "Hotel pickup", "duration": 30}, {"time": "09:30", "activity": "Arrive at Chalong Pier", "duration": 15}, {"time": "09:45", "activity": "Speedboat to Coral Island", "duration": 15}, {"time": "10:00", "activity": "Snorkeling session with guide", "duration": 60}, {"time": "11:00", "activity": "Beach relaxation & swimming", "duration": 90}, {"time": "12:30", "activity": "Lunch at beach restaurant", "duration": 60}, {"time": "13:30", "activity": "Free time for activities or relaxation", "duration": 60}, {"time": "14:30", "activity": "Return to Phuket", "duration": 30}, {"time": "15:00", "activity": "Hotel drop-off", "duration": 0}]',
    '["15-minute boat ride from Phuket", "Beautiful white sand beach", "Excellent snorkeling for beginners", "Optional water sports", "Family-friendly atmosphere"]',
    'Chalong Pier', '{"lat": 7.8167, "lng": 98.3500}', TRUE, 300.00,
    1, 40, 0, 'easy',
    1500.00, 1000.00, 4, 11, TRUE,
    25000.00,
    '["https://images.unsplash.com/photo-1519451241324-20b4ea2c4220?w=800", "https://images.unsplash.com/photo-1506665531195-3566af2b4dfa?w=800"]',
    'https://images.unsplash.com/photo-1519451241324-20b4ea2c4220?w=400',
    '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]',
    FALSE, TRUE, 4.5, 423
);

-- 5. Sunset Cruise
INSERT INTO tours (
    category, name_en, name_ru, name_th, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    duration_hours, departure_time, return_time,
    includes, excludes, itinerary, highlights,
    meeting_point, meeting_point_coordinates, pickup_available, pickup_fee_thb,
    min_participants, max_participants, min_age, difficulty_level,
    price_adult_thb, price_child_thb, child_age_from, child_age_to, infant_free,
    private_charter_price_thb, images, thumbnail,
    schedule, is_featured, is_active, rating, reviews_count
) VALUES (
    'sunset', 'Romantic Sunset Sailing Cruise', 'Романтический круиз на закате', 'ล่องเรือชมพระอาทิตย์ตก', 'sunset-cruise',
    'Experience the magic of a Phuket sunset aboard our elegant sailing catamaran. Cruise along the stunning west coast as the sky transforms into a canvas of orange, pink, and purple. Enjoy premium canapés, free-flowing drinks, and live acoustic music as you watch the sun dip below the Andaman Sea. The perfect romantic evening or celebration.',
    'Испытайте магию заката на Пхукете на борту нашего элегантного парусного катамарана. Круиз вдоль потрясающего западного побережья, когда небо превращается в полотно оранжевых, розовых и фиолетовых оттенков. Премиальные закуски, напитки без ограничений и живая акустическая музыка.',
    'สัมผัสความมหัศจรรย์ของพระอาทิตย์ตกภูเก็ตบนเรือใบคาตามารันหรูหราของเรา',
    'Evening sailing cruise with sunset views, drinks, and live music',
    'Вечерний круиз под парусом с закатом, напитками и живой музыкой',
    3.5, '16:30:00', '20:00:00',
    '["Hotel pickup & drop-off (Patong, Kata, Karon)", "Sailing catamaran cruise", "Welcome cocktail", "Premium canapés & finger food", "Free-flowing drinks (beer, wine, spirits, soft drinks)", "Live acoustic music", "Professional crew", "Insurance"]',
    '["Premium champagne (available for purchase)", "Tips"]',
    '[{"time": "16:30", "activity": "Hotel pickup", "duration": 30}, {"time": "17:00", "activity": "Board catamaran at Ao Yon", "duration": 15}, {"time": "17:15", "activity": "Welcome drink & safety briefing", "duration": 15}, {"time": "17:30", "activity": "Sailing along Phuket coastline", "duration": 60}, {"time": "18:30", "activity": "Sunset viewing with drinks & canapés", "duration": 45}, {"time": "19:15", "activity": "Return sail with live music", "duration": 30}, {"time": "19:45", "activity": "Arrive at pier", "duration": 15}, {"time": "20:00", "activity": "Hotel drop-off", "duration": 0}]',
    '["Spectacular Andaman sunset views", "Premium drinks & gourmet canapés", "Live acoustic entertainment", "Romantic ambiance", "Luxury sailing experience"]',
    'Ao Yon Beach', '{"lat": 7.7873, "lng": 98.4012}', TRUE, 0.00,
    2, 30, 0, 'easy',
    2800.00, 1400.00, 4, 11, TRUE,
    65000.00,
    '["https://images.unsplash.com/photo-1506665531195-3566af2b4dfa?w=800", "https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=800", "https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?w=800"]',
    'https://images.unsplash.com/photo-1506665531195-3566af2b4dfa?w=400',
    '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]',
    TRUE, TRUE, 4.9, 178
);

-- 6. Fishing Trip
INSERT INTO tours (
    category, name_en, name_ru, name_th, slug,
    description_en, description_ru, description_th,
    short_description_en, short_description_ru,
    duration_hours, departure_time, return_time,
    includes, excludes, itinerary, highlights,
    meeting_point, meeting_point_coordinates, pickup_available, pickup_fee_thb,
    min_participants, max_participants, min_age, difficulty_level,
    price_adult_thb, price_child_thb, child_age_from, child_age_to, infant_free,
    private_charter_price_thb, images, thumbnail,
    schedule, is_featured, is_active, rating, reviews_count
) VALUES (
    'fishing', 'Deep Sea Fishing Adventure', 'Глубоководная рыбалка', 'ตกปลาทะเลลึก', 'deep-sea-fishing',
    'Join us for an exciting deep sea fishing adventure in the rich waters around Phuket. Target species include Sailfish, Marlin, Barracuda, Giant Trevally, and various Tuna species. Our experienced captain knows the best fishing spots, and all equipment is provided. Whether you''re a seasoned angler or first-timer, this trip promises an unforgettable experience.',
    'Присоединяйтесь к захватывающему приключению глубоководной рыбалки в богатых водах вокруг Пхукета. Целевые виды: парусник, марлин, барракуда, гигантский каранкс и различные виды тунца. Наш опытный капитан знает лучшие места для рыбалки, всё оборудование предоставляется.',
    'ร่วมผจญภัยตกปลาทะเลลึกที่น่าตื่นเต้นในน่านน้ำอุดมสมบูรณ์รอบภูเก็ต',
    'Full-day deep sea fishing with professional equipment and crew',
    'Глубоководная рыбалка на целый день с профессиональным оборудованием',
    8.0, '06:00:00', '16:00:00',
    '["Hotel pickup & drop-off", "Fishing boat charter", "Professional fishing crew", "All fishing equipment & tackle", "Live bait & lures", "Lunch & snacks", "Drinking water & soft drinks", "Ice for catch storage", "Insurance"]',
    '["Alcoholic beverages", "Tips", "Taxidermy services"]',
    '[{"time": "06:00", "activity": "Hotel pickup", "duration": 30}, {"time": "06:30", "activity": "Arrive at Chalong Pier, meet crew", "duration": 15}, {"time": "06:45", "activity": "Depart for fishing grounds", "duration": 75}, {"time": "08:00", "activity": "Trolling for pelagic fish", "duration": 180}, {"time": "11:00", "activity": "Lunch break on boat", "duration": 45}, {"time": "11:45", "activity": "Bottom fishing & jigging", "duration": 150}, {"time": "14:15", "activity": "Last fishing session", "duration": 45}, {"time": "15:00", "activity": "Return to pier", "duration": 60}, {"time": "16:00", "activity": "Hotel drop-off", "duration": 0}]',
    '["Target sailfish & marlin", "Professional fishing equipment", "Experienced fishing captain", "Keep your catch", "Suitable for all skill levels"]',
    'Chalong Pier', '{"lat": 7.8167, "lng": 98.3500}', TRUE, 400.00,
    2, 8, 8, 'moderate',
    4500.00, 3500.00, 8, 15, FALSE,
    35000.00,
    '["https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800", "https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=800"]',
    'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=400',
    '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]',
    FALSE, TRUE, 4.6, 87
);

-- ============================================
-- VESSEL EXTRAS
-- ============================================

INSERT INTO vessel_extras (vessel_id, name_en, name_ru, name_th, description_en, price_thb, price_type, sort_order) VALUES
(NULL, 'Professional Photographer', 'Профессиональный фотограф', 'ช่างภาพมืออาชีพ', 'Capture your memories with a professional photographer', 5000.00, 'per_booking', 1),
(NULL, 'Jet Ski (1 hour)', 'Гидроцикл (1 час)', 'เจ็ทสกี (1 ชั่วโมง)', 'Jet ski rental for exciting water adventures', 3000.00, 'per_hour', 2),
(NULL, 'Water Skiing Session', 'Водные лыжи', 'สกีน้ำ', 'Water skiing equipment and instruction', 2500.00, 'per_booking', 3),
(NULL, 'Wakeboarding Session', 'Вейкбординг', 'เวคบอร์ด', 'Wakeboarding equipment and instruction', 2500.00, 'per_booking', 4),
(NULL, 'Fishing Equipment', 'Рыболовное снаряжение', 'อุปกรณ์ตกปลา', 'Professional fishing gear with bait', 2000.00, 'per_booking', 5),
(NULL, 'BBQ Package', 'BBQ Пакет', 'แพ็คเกจบาร์บีคิว', 'Fresh seafood and meat BBQ on board', 3500.00, 'per_person', 6),
(NULL, 'Premium Drinks Package', 'Премиум напитки', 'แพ็คเกจเครื่องดื่มพรีเมียม', 'Champagne, wines, premium spirits', 8000.00, 'per_booking', 7),
(NULL, 'Flower Decoration', 'Цветочное оформление', 'ตกแต่งดอกไม้', 'Romantic flower decoration for celebrations', 4000.00, 'per_booking', 8),
(NULL, 'Cake & Celebration Setup', 'Торт и праздничное оформление', 'เค้กและการจัดงานเฉลิมฉลอง', 'Custom cake and celebration setup', 3500.00, 'per_booking', 9),
(NULL, 'Drone Photography', 'Съёмка дроном', 'ถ่ายภาพโดรน', 'Aerial drone photos and video', 6000.00, 'per_booking', 10);

-- ============================================
-- PROMO CODES
-- ============================================

INSERT INTO promo_codes (code, name, description, type, value, min_order_amount, max_discount_amount, applies_to, max_uses, max_uses_per_user, valid_from, valid_until, is_active, is_public) VALUES
('WELCOME10', 'Welcome Discount', '10% off for first-time customers', 'percentage', 10.00, 5000.00, 5000.00, 'all', NULL, 1, '2024-01-01 00:00:00', '2025-12-31 23:59:59', TRUE, TRUE),
('YACHT20', 'Yacht Special', '20% off on yacht rentals', 'percentage', 20.00, 50000.00, 30000.00, 'vessels', 100, 1, '2024-01-01 00:00:00', '2025-06-30 23:59:59', TRUE, TRUE),
('TOUR500', 'Tour Discount', '500 THB off any tour', 'fixed', 500.00, 2000.00, NULL, 'tours', 200, 2, '2024-01-01 00:00:00', '2025-12-31 23:59:59', TRUE, TRUE),
('SUMMER15', 'Summer Sale', '15% summer discount', 'percentage', 15.00, 10000.00, 15000.00, 'all', 500, 1, '2024-04-01 00:00:00', '2024-10-31 23:59:59', TRUE, TRUE),
('VIP25', 'VIP Client', '25% VIP discount', 'percentage', 25.00, 100000.00, 50000.00, 'all', 50, 1, '2024-01-01 00:00:00', '2025-12-31 23:59:59', TRUE, FALSE),
('REFER1000', 'Referral Bonus', '1000 THB referral discount', 'fixed', 1000.00, 5000.00, NULL, 'all', NULL, 1, '2024-01-01 00:00:00', '2025-12-31 23:59:59', TRUE, FALSE),
('PHIPHI300', 'Phi Phi Special', '300 THB off Phi Phi tour', 'fixed', 300.00, 1500.00, NULL, 'tours', 1000, 1, '2024-01-01 00:00:00', '2025-12-31 23:59:59', TRUE, TRUE),
('SUNSET10', 'Sunset Cruise Deal', '10% off sunset cruises', 'percentage', 10.00, 2000.00, 1000.00, 'tours', 200, 2, '2024-01-01 00:00:00', '2025-12-31 23:59:59', TRUE, TRUE);

-- ============================================
-- SAMPLE USERS
-- ============================================

INSERT INTO users (telegram_id, username, first_name, last_name, phone, email, language_code, referral_code, cashback_balance) VALUES
(123456789, 'john_doe', 'John', 'Doe', '+1234567890', 'john@example.com', 'en', 'JOHN2024A', 1500.00),
(987654321, 'ivan_petrov', 'Ivan', 'Petrov', '+79123456789', 'ivan@example.ru', 'ru', 'IVAN2024B', 2800.00),
(456789123, 'somchai_t', 'Somchai', 'Tanaka', '+66812345678', 'somchai@example.th', 'th', 'SOMC2024C', 500.00),
(789123456, 'maria_garcia', 'Maria', 'Garcia', '+34612345678', 'maria@example.es', 'en', 'MARI2024D', 3200.00),
(321654987, 'alex_wong', 'Alex', 'Wong', '+85212345678', 'alex@example.hk', 'en', 'ALEX2024E', 0.00);

-- ============================================
-- SAMPLE BOOKINGS
-- ============================================

INSERT INTO bookings (
    booking_reference, user_id, bookable_type, bookable_id,
    booking_date, start_time, end_time, duration_hours,
    adults_count, children_count,
    base_price_thb, subtotal_thb, total_price_thb,
    cashback_earned_thb, status, payment_method, source
) VALUES
('PYT-2024-0001', 1, 'vessel', 1, '2024-03-15', '09:00:00', '17:00:00', 8, 6, 2, 320000.00, 320000.00, 320000.00, 16000.00, 'completed', 'telegram_stars', 'telegram'),
('PYT-2024-0002', 2, 'tour', 1, '2024-03-16', '07:30:00', NULL, NULL, 2, 1, 6800.00, 6800.00, 6800.00, 340.00, 'completed', 'telegram_stars', 'telegram'),
('PYT-2024-0003', 3, 'vessel', 3, '2024-03-17', '10:00:00', '18:00:00', 8, 8, 0, 55000.00, 55000.00, 55000.00, 2750.00, 'completed', 'bank_transfer', 'telegram'),
('PYT-2024-0004', 4, 'tour', 5, '2024-03-18', '16:30:00', NULL, NULL, 2, 0, 5600.00, 5600.00, 5600.00, 280.00, 'paid', 'telegram_stars', 'telegram'),
('PYT-2024-0005', 1, 'tour', 3, '2024-03-20', '05:30:00', NULL, NULL, 2, 2, 12000.00, 12000.00, 12000.00, 600.00, 'confirmed', 'telegram_stars', 'telegram');

-- ============================================
-- SAMPLE REVIEWS
-- ============================================

INSERT INTO reviews (user_id, bookable_type, bookable_id, booking_id, rating, title, comment, is_verified, is_published) VALUES
(1, 'vessel', 1, 1, 5, 'Unforgettable Experience!', 'Ocean Paradise exceeded all our expectations. The crew was incredibly professional and attentive. The yacht itself was immaculate and had all the amenities we could ask for. We visited Phi Phi Islands and had lunch prepared by the onboard chef. Highly recommend for any special occasion!', TRUE, TRUE),
(2, 'tour', 1, 2, 5, 'Лучший тур на Пхи-Пхи!', 'Потрясающий тур! Гид говорил на русском, что было очень удобно. Майя Бэй просто невероятна, вода кристально чистая. Обед был вкусным, а команда очень дружелюбная. Обязательно поедем снова!', TRUE, TRUE),
(3, 'vessel', 3, 3, 4, 'Great speedboat trip', 'Thunder Wave was perfect for our group trip to the islands. Fast, comfortable, and the captain knew all the best spots. Only giving 4 stars because it got a bit choppy on the way back, but that''s not really their fault. Would book again!', TRUE, TRUE),
(4, 'tour', 5, 4, 5, 'Magical sunset experience', 'The sunset cruise was absolutely magical! The catamaran was beautiful, drinks kept flowing, and the acoustic music created the perfect atmosphere. The sunset views were breathtaking. Perfect for our anniversary celebration.', TRUE, TRUE),
(1, 'vessel', 6, NULL, 5, 'Best catamaran in Phuket', 'Blue Horizon is amazing! So stable and spacious. We had a family trip with kids and everyone was comfortable. The trampoline net at the front was a hit with the children. Crew was fantastic and the food was delicious.', FALSE, TRUE);

-- ============================================
-- SAMPLE FAVORITES
-- ============================================

INSERT INTO favorites (user_id, favoritable_type, favoritable_id) VALUES
(1, 'vessel', 1),
(1, 'vessel', 6),
(1, 'tour', 5),
(2, 'tour', 1),
(2, 'tour', 3),
(3, 'vessel', 3),
(4, 'tour', 5),
(4, 'vessel', 2);

-- ============================================
-- SAMPLE CASHBACK TRANSACTIONS
-- ============================================

INSERT INTO cashback_transactions (user_id, booking_id, type, amount_thb, balance_after_thb, description) VALUES
(1, 1, 'earned', 16000.00, 16000.00, 'Cashback from Ocean Paradise booking'),
(1, NULL, 'used', -14500.00, 1500.00, 'Applied to Similan Islands booking'),
(2, 2, 'earned', 340.00, 340.00, 'Cashback from Phi Phi tour'),
(2, NULL, 'earned', 2460.00, 2800.00, 'Referral bonus'),
(3, 3, 'earned', 2750.00, 2750.00, 'Cashback from Thunder Wave booking'),
(3, NULL, 'used', -2250.00, 500.00, 'Applied to next booking'),
(4, 4, 'earned', 280.00, 280.00, 'Cashback from Sunset Cruise'),
(4, NULL, 'earned', 2920.00, 3200.00, 'Referral bonuses');

-- ============================================
-- REFERRAL TRANSACTIONS
-- ============================================

INSERT INTO referral_transactions (referrer_id, referred_id, booking_id, bonus_amount_thb, status, credited_at) VALUES
(1, 2, 2, 200.00, 'credited', NOW()),
(2, 3, 3, 200.00, 'credited', NOW()),
(4, 5, NULL, 200.00, 'pending', NULL);

-- ============================================
-- SAMPLE AVAILABILITY (block some dates)
-- ============================================

INSERT INTO availability (available_type, available_id, date, is_available, note) VALUES
('vessel', 1, '2024-04-01', FALSE, 'Annual maintenance'),
('vessel', 1, '2024-04-02', FALSE, 'Annual maintenance'),
('vessel', 2, '2024-04-15', FALSE, 'Private event'),
('tour', 3, '2024-04-10', FALSE, 'National park closed'),
('tour', 3, '2024-04-11', FALSE, 'National park closed');

-- ============================================
-- NOTIFICATIONS
-- ============================================

INSERT INTO notifications (user_id, type, title, message, data, is_read) VALUES
(1, 'booking_confirmed', 'Booking Confirmed!', 'Your Similan Islands tour on March 20th has been confirmed. See you there!', '{"booking_id": 5}', FALSE),
(2, 'cashback_earned', 'Cashback Credited!', 'You''ve earned 340 THB cashback from your recent booking. It''s now available to use!', '{"amount": 340}', TRUE),
(4, 'promo_available', 'Special Offer!', 'Use code SUNSET10 for 10% off your next Sunset Cruise booking!', '{"promo_code": "SUNSET10"}', FALSE);
