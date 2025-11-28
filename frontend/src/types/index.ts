// User types
export interface User {
  telegramId: number;
  username?: string;
  firstName: string;
  lastName?: string;
  languageCode: string;
  photoUrl?: string;
  phone?: string;
  email?: string;
  cashbackBalance?: number;
  preferredCurrency?: Currency;
  referralCode?: string;
  referralLink?: string;
}

// Vessel types
export type VesselType = 'yacht' | 'speedboat' | 'catamaran' | 'sailboat';

export interface Vessel {
  id: number;
  type: VesselType;
  name: string;
  slug: string;
  description_en: string;
  description_ru?: string;
  description_th?: string;
  short_description_en?: string;
  short_description_ru?: string;
  capacity: number;
  cabins?: number;
  length_meters: number;
  year_built?: number;
  manufacturer?: string;
  model?: string;
  features: string[];
  amenities: string[];
  crew_info?: {
    captain?: string;
    crew_size?: number;
    chef?: boolean;
    hostess?: boolean;
  };
  price_per_hour_thb: number;
  price_per_day_thb: number;
  price_half_day_thb?: number;
  min_rental_hours: number;
  location: string;
  captain_included: boolean;
  fuel_included: boolean;
  fuel_policy?: string;
  images: string[];
  thumbnail?: string;
  is_featured: boolean;
  rating: number;
  reviews_count: number;
  extras?: VesselExtra[];
}

export interface VesselExtra {
  id: number;
  name_en: string;
  name_ru?: string;
  name_th?: string;
  description_en?: string;
  price_thb: number;
  price_type: 'per_booking' | 'per_hour' | 'per_person';
}

// Tour types
export type TourCategory = 'islands' | 'snorkeling' | 'fishing' | 'sunset' | 'adventure' | 'private';

export interface Tour {
  id: number;
  category: TourCategory;
  name_en: string;
  name_ru?: string;
  name_th?: string;
  slug: string;
  description_en: string;
  description_ru?: string;
  description_th?: string;
  short_description_en?: string;
  short_description_ru?: string;
  duration_hours: number;
  departure_time: string;
  return_time?: string;
  includes: string[];
  excludes: string[];
  itinerary: ItineraryItem[];
  highlights: string[];
  meeting_point: string;
  meeting_point_coordinates?: {
    lat: number;
    lng: number;
  };
  pickup_available: boolean;
  pickup_fee_thb: number;
  min_participants: number;
  max_participants: number;
  min_age?: number;
  difficulty_level: 'easy' | 'moderate' | 'challenging';
  price_adult_thb: number;
  price_child_thb: number;
  child_age_from: number;
  child_age_to: number;
  infant_free: boolean;
  private_charter_price_thb?: number;
  images: string[];
  thumbnail?: string;
  schedule?: string[];
  is_featured: boolean;
  rating: number;
  reviews_count: number;
}

export interface ItineraryItem {
  time: string;
  activity: string;
  duration: number;
}

// Booking types
export type BookingStatus = 'pending' | 'confirmed' | 'paid' | 'completed' | 'cancelled' | 'refunded';
export type BookableType = 'vessel' | 'tour';

export interface Booking {
  id: number;
  booking_reference: string;
  bookable_type: BookableType;
  bookable_id: number;
  item_name: string;
  item_thumbnail?: string;
  item_slug?: string;
  booking_date: string;
  start_time?: string;
  end_time?: string;
  duration_hours?: number;
  adults_count: number;
  children_count: number;
  infants_count?: number;
  base_price_thb: number;
  extras_price_thb: number;
  extras_details?: BookingExtra[];
  pickup_fee_thb: number;
  pickup_address?: string;
  subtotal_thb: number;
  promo_discount_thb: number;
  cashback_used_thb: number;
  total_discount_thb: number;
  total_price_thb: number;
  cashback_earned_thb: number;
  status: BookingStatus;
  payment_method?: string;
  special_requests?: string;
  created_at: string;
}

export interface BookingExtra {
  id: number;
  name: string;
  quantity: number;
  price: number;
}

// Review types
export interface Review {
  id: number;
  user_id: number;
  first_name: string;
  photo_url?: string;
  rating: number;
  title?: string;
  comment?: string;
  is_verified: boolean;
  created_at: string;
}

// Promo code types
export interface PromoCode {
  code: string;
  type: 'percentage' | 'fixed';
  value: number;
  discount: number;
  description?: string;
}

// Currency types
export type Currency = 'THB' | 'USD' | 'EUR' | 'RUB';

export interface ExchangeRate {
  currency_code: Currency;
  currency_name: string;
  currency_symbol: string;
  rate_to_thb: number;
  rate_from_thb: number;
}

// Favorite types
export interface Favorite {
  id: number;
  favoritable_type: BookableType;
  favoritable_id: number;
  item_name: string;
  item_thumbnail?: string;
  price_thb: number;
  created_at: string;
}

// Cashback types
export interface CashbackTransaction {
  id: number;
  type: 'earned' | 'used' | 'expired' | 'adjusted';
  amount_thb: number;
  balance_after_thb: number;
  description?: string;
  created_at: string;
}

// Notification types
export interface Notification {
  id: number;
  type: string;
  title: string;
  message: string;
  data?: Record<string, unknown>;
  is_read: boolean;
  created_at: string;
}

// API Response types
export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
  error?: {
    code: string;
    message: string;
    details?: Record<string, string[]>;
  };
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  pagination: {
    total: number;
    per_page: number;
    current_page: number;
    total_pages: number;
    has_more: boolean;
  };
}

// Filter types
export interface VesselFilters {
  type?: VesselType;
  min_capacity?: number;
  max_capacity?: number;
  min_price?: number;
  max_price?: number;
  date?: string;
  sort?: 'popular' | 'price_asc' | 'price_desc' | 'rating' | 'newest';
}

export interface TourFilters {
  category?: TourCategory;
  min_price?: number;
  max_price?: number;
  max_duration?: number;
  date?: string;
  sort?: 'popular' | 'price_asc' | 'price_desc' | 'rating' | 'duration';
}

// Booking calculation types
export interface BookingCalculation {
  pricing: {
    base_price_thb: number;
    extras_price_thb: number;
    extras_details: BookingExtra[];
    pickup_fee_thb: number;
    subtotal_thb: number;
  };
  promo?: {
    code: string;
    type: string;
    value: number;
    discount: number;
  } | {
    valid: false;
    error: string;
  };
  cashback: {
    available: number;
    max_usage: number;
    to_use: number;
    will_earn: number;
    percent: number;
  };
  discounts: {
    promo: number;
    cashback: number;
    total: number;
  };
  total_thb: number;
}

// Settings types
export interface AppSettings {
  cashback_percent: number;
  referral_bonus_thb: number;
  min_booking_hours: number;
  max_booking_days_ahead: number;
  cancellation_hours: number;
  contact_phone: string;
  contact_email: string;
  contact_whatsapp: string;
  supported_languages: string[];
  supported_currencies: string[];
}

// Addon types
export interface AddonCategory {
  id: number;
  slug: string;
  name_en: string;
  name_ru?: string;
  name_th?: string;
  icon: string;
  applies_to: 'vessel' | 'tour' | 'all';
  addons?: Addon[];
}

export interface Addon {
  id: number;
  category_id: number;
  slug: string;
  name_en: string;
  name_ru?: string;
  name_th?: string;
  description_en?: string;
  description_ru?: string;
  price_thb: number;
  price_type: 'fixed' | 'per_person' | 'per_hour' | 'per_item';
  min_quantity?: number;
  max_quantity?: number;
  is_popular: boolean;
  icon?: string;
}

export interface SelectedAddon {
  addon_id: number;
  quantity: number;
  name?: string;
  price?: number;
}

// Package types
export interface Package {
  id: number;
  slug: string;
  name_en: string;
  name_ru?: string;
  name_th?: string;
  short_description_en?: string;
  description_en?: string;
  type: 'romantic' | 'family' | 'corporate' | 'adventure' | 'party' | 'wedding';
  applies_to: 'vessel' | 'tour' | 'all';
  included_addons: PackageAddon[];
  base_price_thb: number;
  discount_percent: number;
  final_price_thb: number;
  image?: string;
  badge?: string;
  is_featured: boolean;
}

export interface PackageAddon {
  addon_id: number;
  name: string;
  quantity: number;
  original_price: number;
  icon?: string;
}

// Gift Card types
export interface GiftCard {
  id: number;
  code: string;
  amount_thb: number;
  balance_thb: number;
  status: 'pending' | 'active' | 'used' | 'expired' | 'cancelled';
  design_template: string;
  purchaser_name?: string;
  recipient_name?: string;
  personal_message?: string;
  valid_until: string;
  created_at: string;
}

export interface GiftCardTransaction {
  id: number;
  gift_card_id: number;
  type: 'purchase' | 'redemption' | 'refund';
  amount_thb: number;
  balance_after_thb: number;
  booking_reference?: string;
  created_at: string;
}

// Loyalty types
export interface LoyaltyTier {
  id: number;
  name: string;
  slug: string;
  min_spent_thb: number;
  cashback_percent: number;
  discount_percent: number;
  priority_support: boolean;
  free_cancellation: boolean;
  icon: string;
  color: string;
}

export interface LoyaltyStatus {
  current_tier: LoyaltyTier;
  total_spent_thb: number;
  next_tier?: LoyaltyTier;
  amount_to_next_tier?: number;
  benefits: string[];
}

// Payment types
export interface PaymentMethod {
  id: string;
  name: string;
  type: 'card' | 'crypto' | 'telegram_stars' | 'bank_transfer';
  icon: string;
  enabled: boolean;
  min_amount?: number;
  max_amount?: number;
  fee_percent?: number;
}

export interface PaymentIntent {
  id: string;
  booking_reference: string;
  amount_thb: number;
  currency: string;
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled';
  payment_method: string;
  payment_url?: string;
  expires_at?: string;
  metadata?: Record<string, unknown>;
}

// Analytics types (for admin)
export interface AnalyticsSummary {
  period: string;
  revenue: {
    total_thb: number;
    change_percent: number;
    by_type: { vessels: number; tours: number };
  };
  bookings: {
    total: number;
    change_percent: number;
    by_status: Record<BookingStatus, number>;
  };
  users: {
    total: number;
    new: number;
    active: number;
  };
  popular_items: Array<{
    id: number;
    type: 'vessel' | 'tour';
    name: string;
    bookings_count: number;
    revenue_thb: number;
  }>;
}

export interface RevenueChart {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    color: string;
  }[];
}
