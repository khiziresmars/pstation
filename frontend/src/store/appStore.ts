import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { User, Currency } from '@/types';

interface AppState {
  // User state
  user: User | null;
  isAuthenticated: boolean;
  setUser: (user: User | null) => void;

  // Theme
  theme: 'light' | 'dark';
  setTheme: (theme: 'light' | 'dark') => void;

  // Language
  language: 'en' | 'ru' | 'th';
  setLanguage: (lang: 'en' | 'ru' | 'th') => void;

  // Currency
  currency: Currency;
  setCurrency: (currency: Currency) => void;

  // UI state
  isLoading: boolean;
  setLoading: (loading: boolean) => void;

  // Favorites (local cache)
  favoriteIds: { vessels: number[]; tours: number[] };
  addFavorite: (type: 'vessels' | 'tours', id: number) => void;
  removeFavorite: (type: 'vessels' | 'tours', id: number) => void;
  isFavorite: (type: 'vessels' | 'tours', id: number) => boolean;

  // Cart / Current booking
  currentBooking: {
    type: 'vessel' | 'tour' | null;
    itemId: number | null;
    date: string | null;
    hours?: number;
    adults?: number;
    children?: number;
    extras?: Record<number, number>;
    promoCode?: string;
    useCashback?: number;
  };
  setCurrentBooking: (booking: Partial<AppState['currentBooking']>) => void;
  clearCurrentBooking: () => void;
}

export const useAppStore = create<AppState>()(
  persist(
    (set, get) => ({
      // User
      user: null,
      isAuthenticated: false,
      setUser: (user) => set({ user, isAuthenticated: !!user }),

      // Theme
      theme: 'light',
      setTheme: (theme) => {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        set({ theme });
      },

      // Language
      language: 'en',
      setLanguage: (language) => set({ language }),

      // Currency
      currency: 'THB',
      setCurrency: (currency) => set({ currency }),

      // UI
      isLoading: false,
      setLoading: (isLoading) => set({ isLoading }),

      // Favorites
      favoriteIds: { vessels: [], tours: [] },
      addFavorite: (type, id) =>
        set((state) => ({
          favoriteIds: {
            ...state.favoriteIds,
            [type]: [...state.favoriteIds[type], id],
          },
        })),
      removeFavorite: (type, id) =>
        set((state) => ({
          favoriteIds: {
            ...state.favoriteIds,
            [type]: state.favoriteIds[type].filter((fid) => fid !== id),
          },
        })),
      isFavorite: (type, id) => get().favoriteIds[type].includes(id),

      // Current booking
      currentBooking: {
        type: null,
        itemId: null,
        date: null,
      },
      setCurrentBooking: (booking) =>
        set((state) => ({
          currentBooking: { ...state.currentBooking, ...booking },
        })),
      clearCurrentBooking: () =>
        set({
          currentBooking: {
            type: null,
            itemId: null,
            date: null,
          },
        }),
    }),
    {
      name: 'phuket-yachts-storage',
      partialize: (state) => ({
        language: state.language,
        currency: state.currency,
        favoriteIds: state.favoriteIds,
      }),
    }
  )
);
