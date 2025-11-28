import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

const resources = {
  en: {
    translation: {
      // ===========================================
      // NAVIGATION
      // ===========================================
      home: 'Home',
      vessels: 'Yachts & Boats',
      tours: 'Tours',
      profile: 'Profile',
      favorites: 'Favorites',
      bookings: 'My Bookings',
      gift_cards: 'Gift Cards',
      search: 'Search',

      // ===========================================
      // HOME PAGE
      // ===========================================
      welcome: 'Welcome to Phuket',
      tagline: 'Premium Yachts & Island Tours',
      featured_vessels: 'Featured Yachts',
      featured_tours: 'Popular Tours',
      view_all: 'View All',
      yachts_boats_subtitle: 'Yachts & Boats',
      island_adventures: 'Island Adventures',
      cashback_promo_title: 'Get 5% Cashback!',
      cashback_promo_description: 'On every booking. Invite friends & earn more!',

      // ===========================================
      // VESSELS
      // ===========================================
      yacht: 'Yacht',
      speedboat: 'Speedboat',
      catamaran: 'Catamaran',
      sailboat: 'Sailboat',
      all_types: 'All Types',
      capacity: 'Capacity',
      guests: 'guests',
      guest: 'guest',
      length: 'Length',
      per_hour: '/hour',
      per_day: '/day',
      from: 'From',
      captain_included: 'Captain Included',
      fuel_included: 'Fuel Included',
      features: 'Features',
      amenities: 'Amenities',
      reviews: 'Reviews',
      vessels_found: 'vessels found',
      vessel_found: 'vessel found',
      no_vessels_found: 'No vessels found',
      try_adjusting_filters: 'Try adjusting your filters',
      clear_filters: 'Clear Filters',

      // Sort options
      most_popular: 'Most Popular',
      price_low_high: 'Price: Low to High',
      price_high_low: 'Price: High to Low',
      highest_rated: 'Highest Rated',
      newest_first: 'Newest First',

      // ===========================================
      // TOURS
      // ===========================================
      duration: 'Duration',
      hours: 'hours',
      hour: 'hour',
      departure: 'Departure',
      includes: "What's Included",
      excludes: 'Not Included',
      itinerary: 'Itinerary',
      highlights: 'Highlights',
      meeting_point: 'Meeting Point',
      adult: 'Adult',
      child: 'Child',
      pickup_available: 'Hotel Pickup Available',
      tours_found: 'tours found',
      tour_found: 'tour found',
      no_tours_found: 'No tours found',

      // ===========================================
      // BOOKING
      // ===========================================
      book_now: 'Book Now',
      select_date: 'Select Date',
      select_time: 'Select Time',
      start_time: 'Start Time',
      rental_hours: 'Rental Duration',
      number_of_guests: 'Number of Guests',
      adults: 'Adults',
      children: 'Children',
      extras: 'Additional Services',
      promo_code: 'Promo Code',
      enter_code: 'Enter code',
      apply: 'Apply',
      use_cashback: 'Use Cashback',
      available_cashback: 'Available',
      special_requests: 'Special Requests',
      special_requests_placeholder: 'Any special requirements...',
      continue: 'Continue',
      confirm_booking: 'Confirm Booking',
      total: 'Total',
      subtotal: 'Subtotal',
      discount: 'Discount',
      cashback_earned: 'Cashback to Earn',

      // Price summary
      price_summary: 'Price Summary',
      base_price: 'Base Price',
      addons: 'Add-ons',
      package: 'Package',
      pickup: 'Hotel Pickup',
      promo_discount: 'Promo Discount',
      cashback_used: 'Cashback Used',
      gift_card: 'Gift Card',
      discount_applied: 'discount applied!',
      discounts_and_payments: 'Discounts & Payments',

      // Booking history
      no_bookings_yet: 'No bookings yet',
      booking_history_empty: 'Your booking history will appear here',

      // ===========================================
      // ADDONS & PACKAGES
      // ===========================================
      enhance_experience: 'Enhance Your Experience',
      options: 'options',
      addons_selected: 'add-ons selected',
      per_person: '/person',
      per_item: '/item',
      fixed_price: 'Fixed price',

      // Packages
      special_packages: 'Special Packages',
      package_selected: 'Selected Package',
      view_details: 'View details',
      hide_details: 'Hide details',
      whats_included: "What's Included",
      items_value: 'Items Value',
      package_discount: 'Package Discount',
      package_price: 'Package Price',
      select_package: 'Select This Package',
      selected: 'Selected',
      you_save: 'You save',
      more_items: 'more',

      // ===========================================
      // GIFT CARDS
      // ===========================================
      gift_cards_subtitle: 'The perfect gift for any occasion',
      buy_gift_card: 'Buy Gift Card',
      my_gift_cards: 'My Gift Cards',
      select_amount: 'Select Amount',
      or_enter_custom: 'Or enter custom amount',
      choose_design: 'Choose Design',
      recipient_details: 'Recipient Details',
      recipient_name: 'Recipient Name',
      recipient_email: 'Recipient Email',
      your_name: 'Your Name',
      personal_message: 'Personal Message (optional)',
      message_placeholder: 'Write a personal message...',
      delivery_method: 'Delivery Method',
      preview: 'Preview',
      review_gift_card: 'Review Your Gift Card',
      order_summary: 'Order Summary',
      gift_card_value: 'Gift Card Value',
      recipient: 'Recipient',
      purchase: 'Purchase',

      // Gift card designs
      design_ocean: 'Ocean Breeze',
      design_sunset: 'Sunset Cruise',
      design_tropical: 'Tropical Paradise',
      design_luxury: 'Luxury Gold',
      design_adventure: 'Adventure Time',
      design_celebration: 'Celebration',

      // Gift card text
      gift_card_label: 'Gift Card',
      valid_for_months: 'Valid for {{months}} months',
      for_recipient: 'For',
      from_sender: 'From',
      phuket_yacht_tours: 'Phuket Yacht & Tours',

      // Gift card input
      have_gift_card: 'Have a gift card?',
      enter_gift_card_code: 'Enter gift card code',
      verify: 'Verify',
      gift_card_applied: 'Gift card applied',
      will_use: 'Will use',
      remove: 'Remove',
      remaining_balance: 'Remaining balance after payment',
      invalid_gift_card: 'Invalid or expired gift card',

      // ===========================================
      // PAYMENT
      // ===========================================
      complete_payment: 'Complete Payment',
      select_payment_method: 'Select Payment Method',
      credit_debit_card: 'Credit/Debit Card',
      card_brands: 'Visa, Mastercard, American Express',
      cryptocurrency: 'Cryptocurrency',
      crypto_brands: 'BTC, ETH, USDT, USDC, LTC, TRX',
      telegram_stars: 'Telegram Stars',
      pay_with_telegram: 'Pay directly in Telegram',
      total_to_pay: 'Total to pay',
      crypto_rate_info: 'Exchange rate will be calculated at checkout',
      pay: 'Pay',
      processing: 'Processing...',

      // Crypto payment
      crypto_payment: 'Crypto Payment',
      send_exact_amount: 'Send the exact amount to complete payment',
      amount_to_send: 'Amount to send',
      copy: 'Copy',
      send_to_address: 'Send to this address',
      time_remaining: 'Time remaining',
      minutes: 'minutes',
      waiting_for_payment: 'Waiting for payment...',
      check_status: 'Check Status',
      open_in_wallet: 'Open in wallet',
      select_currency: 'Select Currency',

      // Payment status
      pay_with_stars: 'Pay with Telegram Stars',
      bank_transfer: 'Bank Transfer',
      payment_pending: 'Payment Pending',
      payment_success: 'Payment Successful!',
      payment_failed: 'Payment Failed',

      // ===========================================
      // PROFILE
      // ===========================================
      cashback_balance: 'Cashback Balance',
      cashback_info: 'Earn 5% cashback on every booking!',
      referral_program: 'Referral Program',
      invite_friends: 'Invite Friends',
      your_referral_link: 'Your Referral Link',
      copy_link: 'Copy Link',
      copied: 'Copied!',
      total_referrals: 'Total Referrals',
      total_earned: 'Total Earned',
      settings: 'Settings',
      language: 'Language',
      currency: 'Currency',
      logout: 'Log Out',
      share_referral_text: 'Book premium yachts & island tours in Phuket! Use my link to get started:',

      // ===========================================
      // SEARCH
      // ===========================================
      search_placeholder: 'Search yachts, tours...',
      recent_searches: 'Recent Searches',
      popular_searches: 'Popular Searches',
      no_results: 'No results found',
      no_results_message: 'Try different keywords or browse categories',

      // ===========================================
      // STATUS
      // ===========================================
      pending: 'Pending',
      confirmed: 'Confirmed',
      paid: 'Paid',
      completed: 'Completed',
      cancelled: 'Cancelled',
      in_progress: 'In Progress',
      refunded: 'Refunded',

      // ===========================================
      // COMMON
      // ===========================================
      loading: 'Loading...',
      error: 'Something went wrong',
      retry: 'Retry',
      back: 'Back',
      close: 'Close',
      save: 'Save',
      cancel: 'Cancel',
      filter: 'Filter',
      sort: 'Sort',
      clear: 'Clear',
      rating: 'Rating',
      date: 'Date',
      time: 'Time',
      price: 'Price',
      all: 'All',
      yes: 'Yes',
      no: 'No',
      ok: 'OK',
      confirm: 'Confirm',
      delete: 'Delete',
      edit: 'Edit',
      share: 'Share',
      more: 'More',
      less: 'Less',
      see_more: 'See more',
      see_less: 'See less',

      // Offline
      offline: 'You are offline',
      offline_message: 'Please check your internet connection',
      update_available: 'Update available',
      update_now: 'Update now',

      // Errors
      error_loading: 'Error loading data',
      error_network: 'Network error',
      error_unauthorized: 'Please log in to continue',
      error_not_found: 'Not found',
      error_try_again: 'Please try again',
      error_login: 'Invalid email or password',
      error_register: 'Registration failed',
      error_google_auth: 'Google authentication failed',

      // ===========================================
      // AUTH
      // ===========================================
      login: 'Sign In',
      register: 'Sign Up',
      create_account: 'Create Account',
      password: 'Password',
      confirm_password: 'Confirm Password',
      first_name: 'First Name',
      last_name: 'Last Name',
      forgot_password: 'Forgot password?',
      send_reset_link: 'Send Reset Link',
      back_to_login: 'Back to Login',
      continue_with_google: 'Continue with Google',
      or: 'or',
      by_registering: 'By registering, you agree to our',
      terms_of_service: 'Terms of Service',
      privacy_policy: 'Privacy Policy',
      and: 'and',
      passwords_dont_match: 'Passwords do not match',
      password_too_short: 'Password must be at least 6 characters',
      optional: 'optional',

      // ===========================================
      // CONTACT INFO
      // ===========================================
      contact_info: 'Contact Information',
      contact_info_subtitle: 'How should we contact you?',
      preferred_contact_method: 'Preferred Contact Method',
      phone: 'Phone',
      phone_number: 'Phone Number',
      number: 'Number',
      if_different: 'if different from phone',
      leave_empty_same_as_phone: 'Leave empty to use phone number',
      add_to_calendar: 'Add to Calendar',
      calendar_reminder_info: 'Get a reminder before your booking',
      use_profile_info: 'Use info from profile',
    },
  },
  ru: {
    translation: {
      // ===========================================
      // NAVIGATION
      // ===========================================
      home: 'Главная',
      vessels: 'Яхты и Катера',
      tours: 'Туры',
      profile: 'Профиль',
      favorites: 'Избранное',
      bookings: 'Мои Бронирования',
      gift_cards: 'Подарочные Карты',
      search: 'Поиск',

      // ===========================================
      // HOME PAGE
      // ===========================================
      welcome: 'Добро пожаловать на Пхукет',
      tagline: 'Премиальные Яхты и Островные Туры',
      featured_vessels: 'Популярные Яхты',
      featured_tours: 'Популярные Туры',
      view_all: 'Смотреть все',
      yachts_boats_subtitle: 'Яхты и Катера',
      island_adventures: 'Островные Приключения',
      cashback_promo_title: 'Получите 5% кэшбэка!',
      cashback_promo_description: 'С каждого бронирования. Приглашайте друзей и зарабатывайте больше!',

      // ===========================================
      // VESSELS
      // ===========================================
      yacht: 'Яхта',
      speedboat: 'Катер',
      catamaran: 'Катамаран',
      sailboat: 'Парусная яхта',
      all_types: 'Все типы',
      capacity: 'Вместимость',
      guests: 'гостей',
      guest: 'гость',
      length: 'Длина',
      per_hour: '/час',
      per_day: '/день',
      from: 'От',
      captain_included: 'Капитан включён',
      fuel_included: 'Топливо включено',
      features: 'Особенности',
      amenities: 'Удобства',
      reviews: 'Отзывы',
      vessels_found: 'яхт найдено',
      vessel_found: 'яхта найдена',
      no_vessels_found: 'Яхты не найдены',
      try_adjusting_filters: 'Попробуйте изменить фильтры',
      clear_filters: 'Сбросить фильтры',

      // Sort options
      most_popular: 'По популярности',
      price_low_high: 'Цена: по возрастанию',
      price_high_low: 'Цена: по убыванию',
      highest_rated: 'По рейтингу',
      newest_first: 'Сначала новые',

      // ===========================================
      // TOURS
      // ===========================================
      duration: 'Длительность',
      hours: 'часов',
      hour: 'час',
      departure: 'Отправление',
      includes: 'Включено',
      excludes: 'Не включено',
      itinerary: 'Маршрут',
      highlights: 'Основные моменты',
      meeting_point: 'Место встречи',
      adult: 'Взрослый',
      child: 'Ребёнок',
      pickup_available: 'Трансфер из отеля',
      tours_found: 'туров найдено',
      tour_found: 'тур найден',
      no_tours_found: 'Туры не найдены',

      // ===========================================
      // BOOKING
      // ===========================================
      book_now: 'Забронировать',
      select_date: 'Выберите дату',
      select_time: 'Выберите время',
      start_time: 'Время начала',
      rental_hours: 'Продолжительность аренды',
      number_of_guests: 'Количество гостей',
      adults: 'Взрослые',
      children: 'Дети',
      extras: 'Дополнительные услуги',
      promo_code: 'Промокод',
      enter_code: 'Введите код',
      apply: 'Применить',
      use_cashback: 'Использовать кэшбэк',
      available_cashback: 'Доступно',
      special_requests: 'Особые пожелания',
      special_requests_placeholder: 'Ваши особые пожелания...',
      continue: 'Продолжить',
      confirm_booking: 'Подтвердить бронирование',
      total: 'Итого',
      subtotal: 'Подитог',
      discount: 'Скидка',
      cashback_earned: 'Кэшбэк за заказ',

      // Price summary
      price_summary: 'Детали цены',
      base_price: 'Базовая цена',
      addons: 'Дополнения',
      package: 'Пакет',
      pickup: 'Трансфер',
      promo_discount: 'Скидка по промокоду',
      cashback_used: 'Использован кэшбэк',
      gift_card: 'Подарочная карта',
      discount_applied: 'скидка применена!',
      discounts_and_payments: 'Скидки и оплата',

      // Booking history
      no_bookings_yet: 'Бронирований пока нет',
      booking_history_empty: 'Здесь появится история ваших бронирований',

      // ===========================================
      // ADDONS & PACKAGES
      // ===========================================
      enhance_experience: 'Улучшите впечатления',
      options: 'опций',
      addons_selected: 'дополнений выбрано',
      per_person: '/чел.',
      per_item: '/шт.',
      fixed_price: 'Фиксированная цена',

      // Packages
      special_packages: 'Специальные пакеты',
      package_selected: 'Выбранный пакет',
      view_details: 'Подробнее',
      hide_details: 'Скрыть',
      whats_included: 'Что включено',
      items_value: 'Стоимость услуг',
      package_discount: 'Скидка пакета',
      package_price: 'Цена пакета',
      select_package: 'Выбрать пакет',
      selected: 'Выбрано',
      you_save: 'Вы экономите',
      more_items: 'ещё',

      // ===========================================
      // GIFT CARDS
      // ===========================================
      gift_cards_subtitle: 'Идеальный подарок на любой случай',
      buy_gift_card: 'Купить карту',
      my_gift_cards: 'Мои карты',
      select_amount: 'Выберите сумму',
      or_enter_custom: 'Или введите свою сумму',
      choose_design: 'Выберите дизайн',
      recipient_details: 'Данные получателя',
      recipient_name: 'Имя получателя',
      recipient_email: 'Email получателя',
      your_name: 'Ваше имя',
      personal_message: 'Личное сообщение (опционально)',
      message_placeholder: 'Напишите личное сообщение...',
      delivery_method: 'Способ доставки',
      preview: 'Предпросмотр',
      review_gift_card: 'Проверьте карту',
      order_summary: 'Детали заказа',
      gift_card_value: 'Номинал карты',
      recipient: 'Получатель',
      purchase: 'Купить',

      // Gift card designs
      design_ocean: 'Океанский бриз',
      design_sunset: 'Закатный круиз',
      design_tropical: 'Тропический рай',
      design_luxury: 'Золотая роскошь',
      design_adventure: 'Время приключений',
      design_celebration: 'Праздник',

      // Gift card text
      gift_card_label: 'Подарочная карта',
      valid_for_months: 'Действительна {{months}} мес.',
      for_recipient: 'Для',
      from_sender: 'От',
      phuket_yacht_tours: 'Phuket Yacht & Tours',

      // Gift card input
      have_gift_card: 'Есть подарочная карта?',
      enter_gift_card_code: 'Введите код карты',
      verify: 'Проверить',
      gift_card_applied: 'Карта применена',
      will_use: 'Будет использовано',
      remove: 'Удалить',
      remaining_balance: 'Остаток после оплаты',
      invalid_gift_card: 'Недействительная или просроченная карта',

      // ===========================================
      // PAYMENT
      // ===========================================
      complete_payment: 'Завершить оплату',
      select_payment_method: 'Выберите способ оплаты',
      credit_debit_card: 'Банковская карта',
      card_brands: 'Visa, Mastercard, American Express',
      cryptocurrency: 'Криптовалюта',
      crypto_brands: 'BTC, ETH, USDT, USDC, LTC, TRX',
      telegram_stars: 'Telegram Stars',
      pay_with_telegram: 'Оплата прямо в Telegram',
      total_to_pay: 'К оплате',
      crypto_rate_info: 'Курс будет рассчитан при оплате',
      pay: 'Оплатить',
      processing: 'Обработка...',

      // Crypto payment
      crypto_payment: 'Оплата криптовалютой',
      send_exact_amount: 'Отправьте точную сумму для завершения оплаты',
      amount_to_send: 'Сумма к отправке',
      copy: 'Копировать',
      send_to_address: 'Адрес для отправки',
      time_remaining: 'Осталось времени',
      minutes: 'минут',
      waiting_for_payment: 'Ожидание оплаты...',
      check_status: 'Проверить статус',
      open_in_wallet: 'Открыть в кошельке',
      select_currency: 'Выберите валюту',

      // Payment status
      pay_with_stars: 'Оплатить Telegram Stars',
      bank_transfer: 'Банковский перевод',
      payment_pending: 'Ожидание оплаты',
      payment_success: 'Оплата прошла успешно!',
      payment_failed: 'Ошибка оплаты',

      // ===========================================
      // PROFILE
      // ===========================================
      cashback_balance: 'Баланс кэшбэка',
      cashback_info: 'Получайте 5% кэшбэка с каждого бронирования!',
      referral_program: 'Реферальная программа',
      invite_friends: 'Пригласить друзей',
      your_referral_link: 'Ваша реферальная ссылка',
      copy_link: 'Скопировать',
      copied: 'Скопировано!',
      total_referrals: 'Всего рефералов',
      total_earned: 'Всего заработано',
      settings: 'Настройки',
      language: 'Язык',
      currency: 'Валюта',
      logout: 'Выйти',
      share_referral_text: 'Бронируйте яхты и туры на Пхукете! Воспользуйтесь моей ссылкой:',

      // ===========================================
      // SEARCH
      // ===========================================
      search_placeholder: 'Поиск яхт, туров...',
      recent_searches: 'Недавние поиски',
      popular_searches: 'Популярные запросы',
      no_results: 'Ничего не найдено',
      no_results_message: 'Попробуйте другие ключевые слова',

      // ===========================================
      // STATUS
      // ===========================================
      pending: 'Ожидание',
      confirmed: 'Подтверждено',
      paid: 'Оплачено',
      completed: 'Завершено',
      cancelled: 'Отменено',
      in_progress: 'В процессе',
      refunded: 'Возвращено',

      // ===========================================
      // COMMON
      // ===========================================
      loading: 'Загрузка...',
      error: 'Что-то пошло не так',
      retry: 'Повторить',
      back: 'Назад',
      close: 'Закрыть',
      save: 'Сохранить',
      cancel: 'Отмена',
      filter: 'Фильтр',
      sort: 'Сортировка',
      clear: 'Очистить',
      rating: 'Рейтинг',
      date: 'Дата',
      time: 'Время',
      price: 'Цена',
      all: 'Все',
      yes: 'Да',
      no: 'Нет',
      ok: 'ОК',
      confirm: 'Подтвердить',
      delete: 'Удалить',
      edit: 'Редактировать',
      share: 'Поделиться',
      more: 'Ещё',
      less: 'Меньше',
      see_more: 'Показать больше',
      see_less: 'Показать меньше',

      // Offline
      offline: 'Нет подключения',
      offline_message: 'Проверьте интернет-соединение',
      update_available: 'Доступно обновление',
      update_now: 'Обновить',

      // Errors
      error_loading: 'Ошибка загрузки данных',
      error_network: 'Ошибка сети',
      error_unauthorized: 'Войдите для продолжения',
      error_not_found: 'Не найдено',
      error_try_again: 'Попробуйте ещё раз',
      error_login: 'Неверный email или пароль',
      error_register: 'Ошибка регистрации',
      error_google_auth: 'Ошибка авторизации Google',

      // ===========================================
      // AUTH
      // ===========================================
      login: 'Войти',
      register: 'Регистрация',
      create_account: 'Создать аккаунт',
      password: 'Пароль',
      confirm_password: 'Подтвердите пароль',
      first_name: 'Имя',
      last_name: 'Фамилия',
      forgot_password: 'Забыли пароль?',
      send_reset_link: 'Отправить ссылку',
      back_to_login: 'Вернуться к входу',
      continue_with_google: 'Продолжить с Google',
      or: 'или',
      by_registering: 'Регистрируясь, вы соглашаетесь с',
      terms_of_service: 'Условиями использования',
      privacy_policy: 'Политикой конфиденциальности',
      and: 'и',
      passwords_dont_match: 'Пароли не совпадают',
      password_too_short: 'Пароль должен быть не менее 6 символов',
      optional: 'необязательно',

      // ===========================================
      // CONTACT INFO
      // ===========================================
      contact_info: 'Контактные данные',
      contact_info_subtitle: 'Как с вами связаться?',
      preferred_contact_method: 'Предпочтительный способ связи',
      phone: 'Телефон',
      phone_number: 'Номер телефона',
      number: 'Номер',
      if_different: 'если отличается от телефона',
      leave_empty_same_as_phone: 'Оставьте пустым для использования номера телефона',
      add_to_calendar: 'Добавить в календарь',
      calendar_reminder_info: 'Получите напоминание перед бронированием',
      use_profile_info: 'Использовать данные профиля',
    },
  },
  th: {
    translation: {
      // ===========================================
      // NAVIGATION
      // ===========================================
      home: 'หน้าแรก',
      vessels: 'เรือยอทช์และเรือ',
      tours: 'ทัวร์',
      profile: 'โปรไฟล์',
      favorites: 'รายการโปรด',
      bookings: 'การจองของฉัน',
      gift_cards: 'บัตรของขวัญ',
      search: 'ค้นหา',

      // ===========================================
      // HOME PAGE
      // ===========================================
      welcome: 'ยินดีต้อนรับสู่ภูเก็ต',
      tagline: 'เรือยอทช์พรีเมียมและทัวร์เกาะ',
      featured_vessels: 'เรือยอทช์แนะนำ',
      featured_tours: 'ทัวร์ยอดนิยม',
      view_all: 'ดูทั้งหมด',
      yachts_boats_subtitle: 'เรือยอทช์และเรือ',
      island_adventures: 'ผจญภัยเกาะ',
      cashback_promo_title: 'รับเงินคืน 5%!',
      cashback_promo_description: 'ทุกการจอง เชิญเพื่อนรับเพิ่ม!',

      // ===========================================
      // VESSELS
      // ===========================================
      yacht: 'เรือยอทช์',
      speedboat: 'สปีดโบ๊ท',
      catamaran: 'คาตามารัน',
      sailboat: 'เรือใบ',
      all_types: 'ทุกประเภท',
      capacity: 'ความจุ',
      guests: 'ผู้โดยสาร',
      guest: 'ผู้โดยสาร',
      length: 'ความยาว',
      per_hour: '/ชั่วโมง',
      per_day: '/วัน',
      from: 'เริ่มต้น',
      captain_included: 'รวมกัปตัน',
      fuel_included: 'รวมน้ำมัน',
      features: 'คุณสมบัติ',
      amenities: 'สิ่งอำนวยความสะดวก',
      reviews: 'รีวิว',
      vessels_found: 'เรือที่พบ',
      vessel_found: 'เรือที่พบ',
      no_vessels_found: 'ไม่พบเรือ',
      try_adjusting_filters: 'ลองปรับตัวกรอง',
      clear_filters: 'ล้างตัวกรอง',

      // Sort options
      most_popular: 'ยอดนิยม',
      price_low_high: 'ราคา: ต่ำ-สูง',
      price_high_low: 'ราคา: สูง-ต่ำ',
      highest_rated: 'คะแนนสูงสุด',
      newest_first: 'ใหม่ล่าสุด',

      // ===========================================
      // TOURS
      // ===========================================
      duration: 'ระยะเวลา',
      hours: 'ชั่วโมง',
      hour: 'ชั่วโมง',
      departure: 'ออกเดินทาง',
      includes: 'รวมในราคา',
      excludes: 'ไม่รวมในราคา',
      itinerary: 'กำหนดการ',
      highlights: 'ไฮไลท์',
      meeting_point: 'จุดนัดพบ',
      adult: 'ผู้ใหญ่',
      child: 'เด็ก',
      pickup_available: 'รับส่งโรงแรม',
      tours_found: 'ทัวร์ที่พบ',
      tour_found: 'ทัวร์ที่พบ',
      no_tours_found: 'ไม่พบทัวร์',

      // ===========================================
      // BOOKING
      // ===========================================
      book_now: 'จองเลย',
      select_date: 'เลือกวันที่',
      select_time: 'เลือกเวลา',
      start_time: 'เวลาเริ่ม',
      rental_hours: 'ระยะเวลาเช่า',
      number_of_guests: 'จำนวนผู้โดยสาร',
      adults: 'ผู้ใหญ่',
      children: 'เด็ก',
      extras: 'บริการเพิ่มเติม',
      promo_code: 'โค้ดส่วนลด',
      enter_code: 'ใส่โค้ด',
      apply: 'ใช้โค้ด',
      use_cashback: 'ใช้เงินคืน',
      available_cashback: 'เงินคืนที่ใช้ได้',
      special_requests: 'คำขอพิเศษ',
      special_requests_placeholder: 'ความต้องการพิเศษ...',
      continue: 'ดำเนินการต่อ',
      confirm_booking: 'ยืนยันการจอง',
      total: 'รวมทั้งหมด',
      subtotal: 'ยอดรวม',
      discount: 'ส่วนลด',
      cashback_earned: 'เงินคืนที่จะได้รับ',

      // Price summary
      price_summary: 'สรุปราคา',
      base_price: 'ราคาพื้นฐาน',
      addons: 'บริการเสริม',
      package: 'แพ็คเกจ',
      pickup: 'รับส่งโรงแรม',
      promo_discount: 'ส่วนลดโปรโมชั่น',
      cashback_used: 'เงินคืนที่ใช้',
      gift_card: 'บัตรของขวัญ',
      discount_applied: 'ส่วนลดถูกใช้แล้ว!',
      discounts_and_payments: 'ส่วนลดและการชำระเงิน',

      // Booking history
      no_bookings_yet: 'ยังไม่มีการจอง',
      booking_history_empty: 'ประวัติการจองจะแสดงที่นี่',

      // ===========================================
      // ADDONS & PACKAGES
      // ===========================================
      enhance_experience: 'เพิ่มประสบการณ์',
      options: 'ตัวเลือก',
      addons_selected: 'บริการเสริมที่เลือก',
      per_person: '/คน',
      per_item: '/ชิ้น',
      fixed_price: 'ราคาคงที่',

      // Packages
      special_packages: 'แพ็คเกจพิเศษ',
      package_selected: 'แพ็คเกจที่เลือก',
      view_details: 'ดูรายละเอียด',
      hide_details: 'ซ่อน',
      whats_included: 'รวมอะไรบ้าง',
      items_value: 'มูลค่าบริการ',
      package_discount: 'ส่วนลดแพ็คเกจ',
      package_price: 'ราคาแพ็คเกจ',
      select_package: 'เลือกแพ็คเกจนี้',
      selected: 'เลือกแล้ว',
      you_save: 'คุณประหยัด',
      more_items: 'เพิ่มเติม',

      // ===========================================
      // GIFT CARDS
      // ===========================================
      gift_cards_subtitle: 'ของขวัญที่สมบูรณ์แบบทุกโอกาส',
      buy_gift_card: 'ซื้อบัตรของขวัญ',
      my_gift_cards: 'บัตรของฉัน',
      select_amount: 'เลือกจำนวนเงิน',
      or_enter_custom: 'หรือใส่จำนวนเอง',
      choose_design: 'เลือกดีไซน์',
      recipient_details: 'ข้อมูลผู้รับ',
      recipient_name: 'ชื่อผู้รับ',
      recipient_email: 'อีเมลผู้รับ',
      your_name: 'ชื่อของคุณ',
      personal_message: 'ข้อความส่วนตัว (ไม่บังคับ)',
      message_placeholder: 'เขียนข้อความส่วนตัว...',
      delivery_method: 'วิธีจัดส่ง',
      preview: 'ดูตัวอย่าง',
      review_gift_card: 'ตรวจสอบบัตรของขวัญ',
      order_summary: 'สรุปคำสั่งซื้อ',
      gift_card_value: 'มูลค่าบัตร',
      recipient: 'ผู้รับ',
      purchase: 'ซื้อ',

      // Gift card designs
      design_ocean: 'สายลมทะเล',
      design_sunset: 'ล่องเรือยามเย็น',
      design_tropical: 'สวรรค์เขตร้อน',
      design_luxury: 'ทองหรูหรา',
      design_adventure: 'เวลาผจญภัย',
      design_celebration: 'เฉลิมฉลอง',

      // Gift card text
      gift_card_label: 'บัตรของขวัญ',
      valid_for_months: 'ใช้ได้ {{months}} เดือน',
      for_recipient: 'สำหรับ',
      from_sender: 'จาก',
      phuket_yacht_tours: 'Phuket Yacht & Tours',

      // Gift card input
      have_gift_card: 'มีบัตรของขวัญ?',
      enter_gift_card_code: 'ใส่รหัสบัตร',
      verify: 'ตรวจสอบ',
      gift_card_applied: 'ใช้บัตรแล้ว',
      will_use: 'จะใช้',
      remove: 'ลบ',
      remaining_balance: 'ยอดคงเหลือหลังชำระ',
      invalid_gift_card: 'บัตรไม่ถูกต้องหรือหมดอายุ',

      // ===========================================
      // PAYMENT
      // ===========================================
      complete_payment: 'ชำระเงิน',
      select_payment_method: 'เลือกวิธีชำระเงิน',
      credit_debit_card: 'บัตรเครดิต/เดบิต',
      card_brands: 'Visa, Mastercard, American Express',
      cryptocurrency: 'คริปโตเคอร์เรนซี',
      crypto_brands: 'BTC, ETH, USDT, USDC, LTC, TRX',
      telegram_stars: 'Telegram Stars',
      pay_with_telegram: 'ชำระใน Telegram โดยตรง',
      total_to_pay: 'ยอดชำระ',
      crypto_rate_info: 'อัตราแลกเปลี่ยนจะคำนวณตอนชำระ',
      pay: 'ชำระเงิน',
      processing: 'กำลังดำเนินการ...',

      // Crypto payment
      crypto_payment: 'ชำระด้วยคริปโต',
      send_exact_amount: 'ส่งจำนวนที่ถูกต้องเพื่อเสร็จสิ้นการชำระ',
      amount_to_send: 'จำนวนที่ต้องส่ง',
      copy: 'คัดลอก',
      send_to_address: 'ส่งไปที่',
      time_remaining: 'เวลาที่เหลือ',
      minutes: 'นาที',
      waiting_for_payment: 'รอการชำระเงิน...',
      check_status: 'ตรวจสอบสถานะ',
      open_in_wallet: 'เปิดในวอลเล็ท',
      select_currency: 'เลือกสกุลเงิน',

      // Payment status
      pay_with_stars: 'ชำระด้วย Telegram Stars',
      bank_transfer: 'โอนเงินผ่านธนาคาร',
      payment_pending: 'รอการชำระเงิน',
      payment_success: 'ชำระเงินสำเร็จ!',
      payment_failed: 'การชำระเงินล้มเหลว',

      // ===========================================
      // PROFILE
      // ===========================================
      cashback_balance: 'ยอดเงินคืน',
      cashback_info: 'รับเงินคืน 5% ทุกการจอง!',
      referral_program: 'โปรแกรมแนะนำเพื่อน',
      invite_friends: 'เชิญเพื่อน',
      your_referral_link: 'ลิงก์แนะนำของคุณ',
      copy_link: 'คัดลอกลิงก์',
      copied: 'คัดลอกแล้ว!',
      total_referrals: 'จำนวนผู้ถูกแนะนำ',
      total_earned: 'รายได้ทั้งหมด',
      settings: 'ตั้งค่า',
      language: 'ภาษา',
      currency: 'สกุลเงิน',
      logout: 'ออกจากระบบ',
      share_referral_text: 'จองเรือยอทช์และทัวร์ภูเก็ต! ใช้ลิงก์ของฉัน:',

      // ===========================================
      // SEARCH
      // ===========================================
      search_placeholder: 'ค้นหาเรือยอทช์ ทัวร์...',
      recent_searches: 'ค้นหาล่าสุด',
      popular_searches: 'ค้นหายอดนิยม',
      no_results: 'ไม่พบผลลัพธ์',
      no_results_message: 'ลองใช้คำอื่นหรือเลือกหมวดหมู่',

      // ===========================================
      // STATUS
      // ===========================================
      pending: 'รอดำเนินการ',
      confirmed: 'ยืนยันแล้ว',
      paid: 'ชำระเงินแล้ว',
      completed: 'เสร็จสิ้น',
      cancelled: 'ยกเลิก',
      in_progress: 'กำลังดำเนินการ',
      refunded: 'คืนเงินแล้ว',

      // ===========================================
      // COMMON
      // ===========================================
      loading: 'กำลังโหลด...',
      error: 'เกิดข้อผิดพลาด',
      retry: 'ลองอีกครั้ง',
      back: 'กลับ',
      close: 'ปิด',
      save: 'บันทึก',
      cancel: 'ยกเลิก',
      filter: 'กรอง',
      sort: 'เรียงลำดับ',
      clear: 'ล้าง',
      rating: 'คะแนน',
      date: 'วันที่',
      time: 'เวลา',
      price: 'ราคา',
      all: 'ทั้งหมด',
      yes: 'ใช่',
      no: 'ไม่',
      ok: 'ตกลง',
      confirm: 'ยืนยัน',
      delete: 'ลบ',
      edit: 'แก้ไข',
      share: 'แชร์',
      more: 'เพิ่มเติม',
      less: 'น้อยลง',
      see_more: 'ดูเพิ่มเติม',
      see_less: 'ดูน้อยลง',

      // Offline
      offline: 'ออฟไลน์',
      offline_message: 'กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต',
      update_available: 'มีอัปเดตใหม่',
      update_now: 'อัปเดตเลย',

      // Errors
      error_loading: 'โหลดข้อมูลผิดพลาด',
      error_network: 'เครือข่ายผิดพลาด',
      error_unauthorized: 'กรุณาเข้าสู่ระบบ',
      error_not_found: 'ไม่พบ',
      error_try_again: 'กรุณาลองอีกครั้ง',
      error_login: 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
      error_register: 'การลงทะเบียนล้มเหลว',
      error_google_auth: 'การยืนยันตัวตน Google ล้มเหลว',

      // ===========================================
      // AUTH
      // ===========================================
      login: 'เข้าสู่ระบบ',
      register: 'ลงทะเบียน',
      create_account: 'สร้างบัญชี',
      password: 'รหัสผ่าน',
      confirm_password: 'ยืนยันรหัสผ่าน',
      first_name: 'ชื่อ',
      last_name: 'นามสกุล',
      forgot_password: 'ลืมรหัสผ่าน?',
      send_reset_link: 'ส่งลิงก์รีเซ็ต',
      back_to_login: 'กลับสู่การเข้าสู่ระบบ',
      continue_with_google: 'ดำเนินการต่อด้วย Google',
      or: 'หรือ',
      by_registering: 'การลงทะเบียนหมายความว่าคุณยอมรับ',
      terms_of_service: 'เงื่อนไขการใช้บริการ',
      privacy_policy: 'นโยบายความเป็นส่วนตัว',
      and: 'และ',
      passwords_dont_match: 'รหัสผ่านไม่ตรงกัน',
      password_too_short: 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร',
      optional: 'ไม่บังคับ',

      // ===========================================
      // CONTACT INFO
      // ===========================================
      contact_info: 'ข้อมูลติดต่อ',
      contact_info_subtitle: 'เราควรติดต่อคุณอย่างไร?',
      preferred_contact_method: 'วิธีติดต่อที่ต้องการ',
      phone: 'โทรศัพท์',
      phone_number: 'หมายเลขโทรศัพท์',
      number: 'หมายเลข',
      if_different: 'ถ้าต่างจากโทรศัพท์',
      leave_empty_same_as_phone: 'เว้นว่างเพื่อใช้หมายเลขโทรศัพท์',
      add_to_calendar: 'เพิ่มในปฏิทิน',
      calendar_reminder_info: 'รับการแจ้งเตือนก่อนการจอง',
      use_profile_info: 'ใช้ข้อมูลจากโปรไฟล์',
    },
  },
};

i18n.use(initReactI18next).init({
  resources,
  lng: 'en',
  fallbackLng: 'en',
  interpolation: {
    escapeValue: false,
  },
});

export default i18n;
