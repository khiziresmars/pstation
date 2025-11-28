import axios, { AxiosError } from 'axios';
import WebApp from '@twa-dev/sdk';
import type {
  ApiResponse,
  PaginatedResponse,
  Vessel,
  Tour,
  Booking,
  Review,
  ExchangeRate,
  VesselFilters,
  TourFilters,
  BookingCalculation,
  User,
  Favorite,
  CashbackTransaction,
  Notification,
  AppSettings,
  AddonCategory,
  Addon,
  SelectedAddon,
  Package,
  GiftCard,
  GiftCardTransaction,
  LoyaltyStatus,
  LoyaltyTier,
  PaymentIntent,
  PaymentMethod,
} from '@/types';

const API_URL = import.meta.env.VITE_API_URL || '/api';

// Create axios instance
const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add auth header to requests
api.interceptors.request.use((config) => {
  const initData = WebApp.initData;
  if (initData) {
    config.headers.Authorization = `tma ${initData}`;
  }
  return config;
});

// Handle errors
api.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiResponse<unknown>>) => {
    const message = error.response?.data?.error?.message || 'Something went wrong';
    console.error('API Error:', message);
    throw error;
  }
);

// ==================
// Vessels API
// ==================

export const vesselsApi = {
  getAll: async (filters: VesselFilters = {}, page = 1, perPage = 12) => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        params.append(key, String(value));
      }
    });
    params.append('page', String(page));
    params.append('per_page', String(perPage));

    const { data } = await api.get<PaginatedResponse<Vessel>>(`/vessels?${params}`);
    return data;
  },

  getFeatured: async (limit = 4) => {
    const { data } = await api.get<ApiResponse<Vessel[]>>(`/vessels/featured?limit=${limit}`);
    return data.data;
  },

  getBySlug: async (slug: string) => {
    const { data } = await api.get<ApiResponse<Vessel>>(`/vessels/${slug}`);
    return data.data;
  },

  getAvailability: async (id: number, startDate: string, endDate: string) => {
    const { data } = await api.get<ApiResponse<{
      unavailable_dates: Record<string, string>;
      special_prices: Record<string, number>;
    }>>(`/vessels/${id}/availability?start_date=${startDate}&end_date=${endDate}`);
    return data.data;
  },

  getReviews: async (id: number, page = 1) => {
    const { data } = await api.get<PaginatedResponse<Review>>(`/vessels/${id}/reviews?page=${page}`);
    return data;
  },
};

// ==================
// Tours API
// ==================

export const toursApi = {
  getAll: async (filters: TourFilters = {}, page = 1, perPage = 12) => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        params.append(key, String(value));
      }
    });
    params.append('page', String(page));
    params.append('per_page', String(perPage));

    const { data } = await api.get<PaginatedResponse<Tour> & { categories: Array<{ category: string; count: number }> }>(
      `/tours?${params}`
    );
    return data;
  },

  getFeatured: async (limit = 4) => {
    const { data } = await api.get<ApiResponse<Tour[]>>(`/tours/featured?limit=${limit}`);
    return data.data;
  },

  getBySlug: async (slug: string) => {
    const { data } = await api.get<ApiResponse<Tour>>(`/tours/${slug}`);
    return data.data;
  },

  getAvailability: async (id: number, startDate: string, endDate: string) => {
    const { data } = await api.get<ApiResponse<Record<string, { available: boolean; slots_remaining: number; reason?: string }>>>(
      `/tours/${id}/availability?start_date=${startDate}&end_date=${endDate}`
    );
    return data.data;
  },

  getReviews: async (id: number, page = 1) => {
    const { data } = await api.get<PaginatedResponse<Review>>(`/tours/${id}/reviews?page=${page}`);
    return data;
  },
};

// ==================
// Bookings API
// ==================

export const bookingsApi = {
  create: async (bookingData: {
    type: 'vessel' | 'tour';
    item_id: number;
    date: string;
    start_time?: string;
    hours?: number;
    adults?: number;
    children?: number;
    extras?: Record<number, number>;
    promo_code?: string;
    use_cashback?: number;
    pickup?: boolean;
    pickup_address?: string;
    special_requests?: string;
    contact_phone?: string;
    contact_email?: string;
  }) => {
    const { data } = await api.post<ApiResponse<Booking>>('/bookings', bookingData);
    return data.data;
  },

  getByReference: async (reference: string) => {
    const { data } = await api.get<ApiResponse<Booking>>(`/bookings/${reference}`);
    return data.data;
  },

  cancel: async (reference: string, reason?: string) => {
    const { data } = await api.post<ApiResponse<null>>(`/bookings/${reference}/cancel`, { reason });
    return data;
  },

  calculate: async (calcData: {
    type: 'vessel' | 'tour';
    item_id: number;
    date: string;
    hours?: number;
    adults?: number;
    children?: number;
    extras?: Record<number, number>;
    pickup?: boolean;
    promo_code?: string;
    use_cashback?: number;
  }) => {
    const { data } = await api.post<ApiResponse<BookingCalculation>>('/bookings/calculate', calcData);
    return data.data;
  },
};

// ==================
// User API
// ==================

export const userApi = {
  getProfile: async () => {
    const { data } = await api.get<ApiResponse<User & { cashback_formatted: { amount: number; formatted: string } }>>('/user/profile');
    return data.data;
  },

  updateProfile: async (profileData: Partial<Pick<User, 'phone' | 'email' | 'languageCode' | 'preferredCurrency'>>) => {
    const { data } = await api.put<ApiResponse<null>>('/user/profile', {
      phone: profileData.phone,
      email: profileData.email,
      language_code: profileData.languageCode,
      preferred_currency: profileData.preferredCurrency,
    });
    return data;
  },

  getBookings: async (page = 1) => {
    const { data } = await api.get<PaginatedResponse<Booking>>(`/user/bookings?page=${page}`);
    return data;
  },

  getFavorites: async () => {
    const { data } = await api.get<ApiResponse<Favorite[]>>('/user/favorites');
    return data.data;
  },

  addFavorite: async (type: 'vessel' | 'tour', id: number) => {
    const { data } = await api.post<ApiResponse<null>>('/user/favorites', { type, id });
    return data;
  },

  removeFavorite: async (type: 'vessel' | 'tour', id: number) => {
    const { data } = await api.delete<ApiResponse<null>>(`/user/favorites/${type}/${id}`);
    return data;
  },

  getCashbackHistory: async () => {
    const { data } = await api.get<ApiResponse<CashbackTransaction[]>>('/user/cashback');
    return data.data;
  },

  getReferrals: async () => {
    const { data } = await api.get<ApiResponse<{
      referral_code: string;
      referral_link: string;
      total_referrals: number;
      total_earned_thb: number;
      referrals: Array<{
        first_name: string;
        created_at: string;
        bonus_amount_thb: number;
        status: string;
      }>;
    }>>('/user/referrals');
    return data.data;
  },

  getNotifications: async () => {
    const { data } = await api.get<ApiResponse<Notification[]>>('/user/notifications');
    return data.data;
  },

  markNotificationRead: async (id: number) => {
    const { data } = await api.put<ApiResponse<null>>(`/user/notifications/${id}/read`);
    return data;
  },

  getGiftCards: async () => {
    const { data } = await api.get<ApiResponse<GiftCard[]>>('/user/gift-cards');
    return data.data;
  },

  changePassword: async (currentPassword: string, newPassword: string) => {
    const { data } = await api.post<ApiResponse<{ success: boolean }>>('/user/change-password', {
      current_password: currentPassword,
      new_password: newPassword,
    });
    return data.data;
  },
};

// ==================
// Promo API
// ==================

export const promoApi = {
  validate: async (code: string, type: 'vessel' | 'tour', itemId: number, amount: number) => {
    const { data } = await api.post<ApiResponse<{
      code: string;
      type: string;
      value: number;
      discount: number;
      description?: string;
    }>>('/promo/validate', { code, type, item_id: itemId, amount });
    return data.data;
  },
};

// ==================
// Payment API
// ==================

export const paymentApi = {
  createTelegramStarsInvoice: async (bookingReference: string) => {
    const { data } = await api.post<ApiResponse<{
      invoice_id: string;
      stars_amount: number;
      thb_amount: number;
    }>>('/payments/telegram-stars/create', { booking_reference: bookingReference });
    return data.data;
  },
};

// ==================
// Exchange Rates API
// ==================

export const exchangeRatesApi = {
  getAll: async () => {
    const { data } = await api.get<ApiResponse<ExchangeRate[]>>('/exchange-rates');
    return data.data;
  },
};

// ==================
// Settings API
// ==================

export const settingsApi = {
  getPublic: async () => {
    const { data } = await api.get<ApiResponse<AppSettings>>('/settings');
    return data.data;
  },
};

// ==================
// Auth API
// ==================

export const authApi = {
  // Telegram auth
  authenticate: async () => {
    const initData = WebApp.initData;
    if (!initData) {
      throw new Error('No Telegram init data');
    }

    const { data } = await api.post<ApiResponse<{ user: User; token: string }>>('/auth/telegram', {
      init_data: initData,
    });
    return data.data;
  },

  // Email auth
  login: async (email: string, password: string) => {
    const { data } = await api.post<ApiResponse<{ user: User; token: string; auth_method: string }>>('/auth/login', {
      email,
      password,
    });
    return data.data;
  },

  register: async (userData: {
    email: string;
    password: string;
    first_name?: string;
    last_name?: string;
    phone?: string;
    whatsapp?: string;
  }) => {
    const { data } = await api.post<ApiResponse<{ user: User; token: string; auth_method: string; requires_verification?: boolean }>>('/auth/register', userData);
    return data.data;
  },

  verifyEmail: async (token: string) => {
    const { data } = await api.post<ApiResponse<{ success: boolean }>>('/auth/verify-email', { token });
    return data.data;
  },

  forgotPassword: async (email: string) => {
    const { data } = await api.post<ApiResponse<{ success: boolean; message: string }>>('/auth/forgot-password', { email });
    return data.data;
  },

  resetPassword: async (token: string, password: string) => {
    const { data } = await api.post<ApiResponse<{ success: boolean }>>('/auth/reset-password', { token, password });
    return data.data;
  },

  // Google auth
  getGoogleUrl: async (redirectUri: string) => {
    const { data } = await api.get<ApiResponse<{ url: string }>>(`/auth/google?redirect_uri=${encodeURIComponent(redirectUri)}`);
    return data.data;
  },

  googleCallback: async (code: string, redirectUri: string) => {
    const { data } = await api.post<ApiResponse<{ user: User; token: string; auth_method: string }>>('/auth/google/callback', {
      code,
      redirect_uri: redirectUri,
    });
    return data.data;
  },

  // Current user
  me: async () => {
    const { data } = await api.get<ApiResponse<{ user: User }>>('/auth/me');
    return data.data;
  },
};

// ==================
// Reviews API
// ==================

export const reviewsApi = {
  create: async (reviewData: {
    bookable_type: 'vessel' | 'tour';
    bookable_id: number;
    rating: number;
    title?: string;
    comment?: string;
  }) => {
    const { data } = await api.post<ApiResponse<{ id: number }>>('/reviews', reviewData);
    return data.data;
  },
};

// ==================
// Addons API
// ==================

export const addonsApi = {
  getCategories: async (appliesTo: string = 'all') => {
    const { data } = await api.get<ApiResponse<AddonCategory[]>>(`/addons/categories?applies_to=${appliesTo}`);
    return data.data;
  },

  forVessel: async (vesselId: number, vesselType?: string) => {
    const params = new URLSearchParams({ vessel_id: String(vesselId) });
    if (vesselType) params.append('vessel_type', vesselType);
    const { data } = await api.get<ApiResponse<AddonCategory[]>>(`/addons/vessel?${params}`);
    return data.data;
  },

  forTour: async (tourId: number, tourCategory?: string) => {
    const params = new URLSearchParams({ tour_id: String(tourId) });
    if (tourCategory) params.append('tour_category', tourCategory);
    const { data } = await api.get<ApiResponse<AddonCategory[]>>(`/addons/tour?${params}`);
    return data.data;
  },

  getPopular: async (limit = 8) => {
    const { data } = await api.get<ApiResponse<Addon[]>>(`/addons/popular?limit=${limit}`);
    return data.data;
  },

  calculate: async (addons: SelectedAddon[], guests: number, hours: number) => {
    const { data } = await api.post<ApiResponse<{ total_thb: number; breakdown: any[] }>>('/addons/calculate', {
      addons,
      guests,
      hours,
    });
    return data.data;
  },

  getById: async (id: number) => {
    const { data } = await api.get<ApiResponse<Addon>>(`/addons/${id}`);
    return data.data;
  },

  getRecommended: async (selectedIds: number[], appliesTo: string = 'all') => {
    const { data } = await api.get<ApiResponse<Addon[]>>(
      `/addons/recommended?selected=${selectedIds.join(',')}&applies_to=${appliesTo}`
    );
    return data.data;
  },
};

// ==================
// Packages API
// ==================

export const packagesApi = {
  getAll: async () => {
    const { data } = await api.get<ApiResponse<Package[]>>('/packages');
    return data.data;
  },

  getByType: async (type: string) => {
    const { data } = await api.get<ApiResponse<Package[]>>(`/packages?type=${type}`);
    return data.data;
  },

  getFeatured: async (limit = 4) => {
    const { data } = await api.get<ApiResponse<Package[]>>(`/packages/featured?limit=${limit}`);
    return data.data;
  },

  getBySlug: async (slug: string) => {
    const { data } = await api.get<ApiResponse<Package>>(`/packages/${slug}`);
    return data.data;
  },

  calculate: async (packageId: number, options: { guests: number; hours: number; base_id?: number; extra_addons?: SelectedAddon[] }) => {
    const { data } = await api.post<ApiResponse<{
      package_price_thb: number;
      addons_value_thb: number;
      discount_percent: number;
      savings_thb: number;
      final_price_thb: number;
    }>>('/packages/calculate', { package_id: packageId, ...options });
    return data.data;
  },

  getVessels: async (packageId: number) => {
    const { data } = await api.get<ApiResponse<Vessel[]>>(`/packages/${packageId}/vessels`);
    return data.data;
  },

  getTours: async (packageId: number) => {
    const { data } = await api.get<ApiResponse<Tour[]>>(`/packages/${packageId}/tours`);
    return data.data;
  },

  getTypes: async () => {
    const { data } = await api.get<ApiResponse<{ slug: string; name: string; icon: string }[]>>('/packages/types');
    return data.data;
  },
};

// ==================
// Gift Cards API
// ==================

export const giftCardsApi = {
  getDesigns: async () => {
    const { data } = await api.get<ApiResponse<{ id: string; name: string; gradient: string }[]>>('/gift-cards/designs');
    return data.data;
  },

  getAmounts: async () => {
    const { data } = await api.get<ApiResponse<number[]>>('/gift-cards/amounts');
    return data.data;
  },

  validate: async (code: string, orderAmount: number, appliesTo: string = 'all') => {
    const { data } = await api.get<ApiResponse<{
      valid: boolean;
      code: string;
      balance: number;
      applicable_amount: number;
      error?: string;
    }>>(`/gift-cards/validate?code=${code}&order_amount=${orderAmount}&applies_to=${appliesTo}`);
    return data.data;
  },

  check: async (code: string) => {
    const { data } = await api.get<ApiResponse<GiftCard>>(`/gift-cards/check?code=${code}`);
    return data.data;
  },

  purchase: async (purchaseData: {
    amount_thb: number;
    design_template: string;
    recipient_name?: string;
    recipient_email?: string;
    purchaser_name?: string;
    personal_message?: string;
    delivery_method: 'email' | 'telegram';
    valid_months?: number;
    applies_to?: string;
  }) => {
    const { data } = await api.post<ApiResponse<{ gift_card: GiftCard }>>('/user/gift-cards/purchase', purchaseData);
    return data.data;
  },

  getUserCards: async () => {
    const { data } = await api.get<ApiResponse<GiftCard[]>>('/user/gift-cards');
    return data.data;
  },

  getTransactions: async (giftCardId: number) => {
    const { data } = await api.get<ApiResponse<GiftCardTransaction[]>>(`/user/gift-cards/${giftCardId}/transactions`);
    return data.data;
  },
};

// ==================
// Loyalty API
// ==================

export const loyaltyApi = {
  getStatus: async () => {
    const { data } = await api.get<ApiResponse<LoyaltyStatus>>('/user/loyalty');
    return data.data;
  },

  getTiers: async () => {
    const { data } = await api.get<ApiResponse<LoyaltyTier[]>>('/user/loyalty/tiers');
    return data.data;
  },
};

// ==================
// Payments API (Extended)
// ==================

export const paymentsApi = {
  getMethods: async () => {
    const { data } = await api.get<ApiResponse<PaymentMethod[]>>('/payments/methods');
    return data.data;
  },

  createIntent: async (bookingReference: string, method: string) => {
    const { data } = await api.post<ApiResponse<PaymentIntent>>('/payments/create', {
      booking_reference: bookingReference,
      method,
    });
    return data.data;
  },

  // Stripe
  createStripeIntent: async (bookingReference: string) => {
    const { data } = await api.post<ApiResponse<{
      client_secret: string;
      payment_intent_id: string;
      amount: number;
      currency: string;
    }>>('/payments/stripe/create', { booking_reference: bookingReference });
    return data.data;
  },

  confirmStripe: async (paymentIntentId: string) => {
    const { data } = await api.post<ApiResponse<{ success: boolean }>>('/payments/stripe/confirm', {
      payment_intent_id: paymentIntentId,
    });
    return data.data;
  },

  // Crypto (NowPayments)
  createCryptoPayment: async (bookingReference: string, currency: string = 'btc') => {
    const { data } = await api.post<ApiResponse<{
      payment_id: string;
      payment_url: string;
      pay_address: string;
      pay_amount: number;
      pay_currency: string;
      expires_at: string;
      qr_code: string;
    }>>('/payments/crypto/create', {
      booking_reference: bookingReference,
      currency,
    });
    return data.data;
  },

  getCryptoStatus: async (paymentId: string) => {
    const { data } = await api.get<ApiResponse<{
      status: string;
      actually_paid: number;
      outcome_amount: number;
    }>>(`/payments/crypto/status/${paymentId}`);
    return data.data;
  },

  getCryptoCurrencies: async () => {
    const { data } = await api.get<ApiResponse<{
      id: string;
      name: string;
      symbol: string;
      logo: string;
      min_amount: number;
    }[]>>('/payments/crypto/currencies');
    return data.data;
  },

  // Telegram Stars
  createTelegramStars: async (bookingReference: string) => {
    const { data } = await api.post<ApiResponse<{
      invoice_id: string;
      stars_amount: number;
      thb_amount: number;
    }>>('/payments/telegram-stars/create', { booking_reference: bookingReference });
    return data.data;
  },

  confirmTelegramStars: async (invoiceId: string, telegramPaymentChargeId: string) => {
    const { data } = await api.post<ApiResponse<{ success: boolean }>>('/payments/telegram-stars/confirm', {
      invoice_id: invoiceId,
      telegram_payment_charge_id: telegramPaymentChargeId,
    });
    return data.data;
  },

  // PromptPay (Thai QR)
  createPromptPay: async (bookingReference: string) => {
    const { data } = await api.post<ApiResponse<{
      payment_id: number;
      qr_payload: string;
      qr_image_url: string;
      amount: number;
      currency: string;
      account_name: string;
      account_id_masked: string;
      expires_at: string;
      instructions: { th: string; en: string; ru: string };
    }>>('/payments/promptpay/create', { booking_reference: bookingReference });
    return data.data;
  },

  // YooKassa (Russian Payments)
  createYooKassa: async (bookingReference: string, paymentMethod: string = 'bank_card') => {
    const { data } = await api.post<ApiResponse<{
      payment_id: string;
      confirmation_url: string;
      status: string;
      amount: number;
      currency: string;
    }>>('/payments/yookassa/create', {
      booking_reference: bookingReference,
      payment_method: paymentMethod,
    });
    return data.data;
  },

  getYooKassaStatus: async (paymentId: string) => {
    const { data } = await api.get<ApiResponse<{
      id: string;
      status: string;
      paid: boolean;
      amount: { value: string; currency: string };
    }>>(`/payments/yookassa/status/${paymentId}`);
    return data.data;
  },

  getYooKassaMethods: async () => {
    const { data } = await api.get<ApiResponse<{
      id: string;
      name: string;
      name_ru: string;
      icon: string;
      description: string;
    }[]>>('/payments/yookassa/methods');
    return data.data;
  },
};

export default api;
