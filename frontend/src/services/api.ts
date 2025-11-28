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

export default api;
