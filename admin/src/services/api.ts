import axios from 'axios';
import { useAuthStore } from '@/store/authStore';

const API_URL = import.meta.env.VITE_API_URL || '/api';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add auth token to requests
api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout();
      window.location.href = '/admin/login';
    }
    return Promise.reject(error);
  }
);

// Auth
export const authApi = {
  login: (email: string, password: string) =>
    api.post('/admin/auth/login', { email, password }),
  me: () => api.get('/auth/me'),
};

// Dashboard
export const dashboardApi = {
  getStats: () => api.get('/admin/dashboard'),
  getQuickStats: () => api.get('/admin/dashboard/stats'),
};

// Bookings
export const bookingsApi = {
  getAll: (params?: Record<string, unknown>) => api.get('/admin/bookings', { params }),
  getById: (reference: string) => api.get(`/admin/bookings/${reference}`),
  updateStatus: (reference: string, status: string) =>
    api.put(`/admin/bookings/${reference}/status`, { status }),
  confirm: (reference: string) => api.put(`/admin/bookings/${reference}/confirm`),
  cancel: (reference: string, reason?: string) =>
    api.put(`/admin/bookings/${reference}/cancel`, { reason }),
  export: (params?: Record<string, unknown>) =>
    api.get('/admin/bookings/export', { params, responseType: 'blob' }),
};

// Vessels
export const vesselsApi = {
  getAll: (params?: Record<string, unknown>) => api.get('/admin/vessels', { params }),
  getById: (id: number) => api.get(`/admin/vessels/${id}`),
  create: (data: Record<string, unknown>) => api.post('/admin/vessels', data),
  update: (id: number, data: Record<string, unknown>) => api.put(`/admin/vessels/${id}`, data),
  delete: (id: number) => api.delete(`/admin/vessels/${id}`),
};

// Tours
export const toursApi = {
  getAll: (params?: Record<string, unknown>) => api.get('/admin/tours', { params }),
  getById: (id: number) => api.get(`/admin/tours/${id}`),
  create: (data: Record<string, unknown>) => api.post('/admin/tours', data),
  update: (id: number, data: Record<string, unknown>) => api.put(`/admin/tours/${id}`, data),
  delete: (id: number) => api.delete(`/admin/tours/${id}`),
};

// Users
export const usersApi = {
  getAll: (params?: Record<string, unknown>) => api.get('/admin/users', { params }),
  getById: (id: number) => api.get(`/admin/users/${id}`),
  update: (id: number, data: Record<string, unknown>) => api.put(`/admin/users/${id}`, data),
  block: (id: number) => api.put(`/admin/users/${id}/block`),
  unblock: (id: number) => api.put(`/admin/users/${id}/unblock`),
};

// Payments
export const paymentsApi = {
  getPromptPayPending: () => api.get('/payments/promptpay/pending'),
  confirmPromptPay: (paymentId: string, transactionRef: string) =>
    api.post('/payments/promptpay/confirm', { payment_id: paymentId, transaction_ref: transactionRef }),
  refundYooKassa: (paymentId: string, amount?: number) =>
    api.post('/payments/yookassa/refund', { payment_id: paymentId, amount }),
};

// Promos
export const promosApi = {
  getAll: (params?: Record<string, unknown>) => api.get('/admin/promos', { params }),
  getById: (id: number) => api.get(`/admin/promos/${id}`),
  create: (data: Record<string, unknown>) => api.post('/admin/promos', data),
  update: (id: number, data: Record<string, unknown>) => api.put(`/admin/promos/${id}`, data),
  delete: (id: number) => api.delete(`/admin/promos/${id}`),
  generate: (data: Record<string, unknown>) => api.post('/admin/promos/generate', data),
};

// Reviews
export const reviewsApi = {
  getAll: (params?: Record<string, unknown>) => api.get('/admin/reviews', { params }),
  approve: (id: number) => api.put(`/admin/reviews/${id}/approve`),
  reject: (id: number) => api.put(`/admin/reviews/${id}/reject`),
  reply: (id: number, reply: string) => api.put(`/admin/reviews/${id}/reply`, { reply }),
  delete: (id: number) => api.delete(`/admin/reviews/${id}`),
};

// Analytics
export const analyticsApi = {
  getDashboard: (params?: Record<string, unknown>) => api.get('/admin/analytics/dashboard', { params }),
  getRevenue: (params?: Record<string, unknown>) => api.get('/admin/analytics/revenue', { params }),
  getBookings: (params?: Record<string, unknown>) => api.get('/admin/analytics/bookings', { params }),
  getPaymentMethods: (params?: Record<string, unknown>) => api.get('/admin/analytics/payment-methods', { params }),
};

// Settings
export const settingsApi = {
  getAll: () => api.get('/admin/settings'),
  update: (data: Record<string, unknown>) => api.put('/admin/settings', data),
  getExchangeRates: () => api.get('/admin/settings/exchange-rates'),
  updateExchangeRates: (data: Record<string, unknown>) =>
    api.put('/admin/settings/exchange-rates', data),
};

export default api;
