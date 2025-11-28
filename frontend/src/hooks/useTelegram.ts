import { useCallback } from 'react';
import WebApp from '@twa-dev/sdk';

export function useTelegram() {
  // Haptic feedback
  const hapticImpact = useCallback((style: 'light' | 'medium' | 'heavy' | 'rigid' | 'soft' = 'light') => {
    WebApp.HapticFeedback.impactOccurred(style);
  }, []);

  const hapticNotification = useCallback((type: 'error' | 'success' | 'warning') => {
    WebApp.HapticFeedback.notificationOccurred(type);
  }, []);

  const hapticSelection = useCallback(() => {
    WebApp.HapticFeedback.selectionChanged();
  }, []);

  // Main button
  const showMainButton = useCallback((text: string, onClick: () => void) => {
    WebApp.MainButton.text = text;
    WebApp.MainButton.onClick(onClick);
    WebApp.MainButton.show();
  }, []);

  const hideMainButton = useCallback(() => {
    WebApp.MainButton.hide();
    WebApp.MainButton.offClick(() => {});
  }, []);

  const setMainButtonLoading = useCallback((loading: boolean) => {
    if (loading) {
      WebApp.MainButton.showProgress();
    } else {
      WebApp.MainButton.hideProgress();
    }
  }, []);

  // Back button
  const showBackButton = useCallback((onClick: () => void) => {
    WebApp.BackButton.onClick(onClick);
    WebApp.BackButton.show();
  }, []);

  const hideBackButton = useCallback(() => {
    WebApp.BackButton.hide();
    WebApp.BackButton.offClick(() => {});
  }, []);

  // Popups
  const showAlert = useCallback((message: string, callback?: () => void) => {
    WebApp.showAlert(message, callback);
  }, []);

  const showConfirm = useCallback((message: string, callback: (confirmed: boolean) => void) => {
    WebApp.showConfirm(message, callback);
  }, []);

  const showPopup = useCallback((
    params: {
      title?: string;
      message: string;
      buttons?: Array<{
        id?: string;
        type?: 'default' | 'ok' | 'close' | 'cancel' | 'destructive';
        text?: string;
      }>;
    },
    callback?: (buttonId: string) => void
  ) => {
    WebApp.showPopup(params, callback);
  }, []);

  // Share
  const shareUrl = useCallback((url: string, text?: string) => {
    const shareText = text ? `${text}\n${url}` : url;
    WebApp.openTelegramLink(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(shareText)}`);
  }, []);

  // Open links
  const openLink = useCallback((url: string, options?: { try_instant_view?: boolean }) => {
    WebApp.openLink(url, options);
  }, []);

  const openTelegramLink = useCallback((url: string) => {
    WebApp.openTelegramLink(url);
  }, []);

  // Expand/Close
  const expand = useCallback(() => {
    WebApp.expand();
  }, []);

  const close = useCallback(() => {
    WebApp.close();
  }, []);

  // Theme
  const colorScheme = WebApp.colorScheme;
  const themeParams = WebApp.themeParams;

  // User info
  const user = WebApp.initDataUnsafe.user;
  const initData = WebApp.initData;

  // Platform
  const platform = WebApp.platform;
  const isVersionAtLeast = useCallback((version: string) => {
    return WebApp.isVersionAtLeast(version);
  }, []);

  // Cloud storage (if available)
  const setStorageItem = useCallback(async (key: string, value: string): Promise<boolean> => {
    return new Promise((resolve) => {
      if (WebApp.CloudStorage) {
        WebApp.CloudStorage.setItem(key, value, (error) => {
          resolve(!error);
        });
      } else {
        localStorage.setItem(key, value);
        resolve(true);
      }
    });
  }, []);

  const getStorageItem = useCallback(async (key: string): Promise<string | null> => {
    return new Promise((resolve) => {
      if (WebApp.CloudStorage) {
        WebApp.CloudStorage.getItem(key, (error, value) => {
          resolve(error ? null : value || null);
        });
      } else {
        resolve(localStorage.getItem(key));
      }
    });
  }, []);

  return {
    // Haptic
    hapticImpact,
    hapticNotification,
    hapticSelection,

    // Main button
    showMainButton,
    hideMainButton,
    setMainButtonLoading,

    // Back button
    showBackButton,
    hideBackButton,

    // Popups
    showAlert,
    showConfirm,
    showPopup,

    // Share/Links
    shareUrl,
    openLink,
    openTelegramLink,

    // App
    expand,
    close,

    // Theme
    colorScheme,
    themeParams,

    // User
    user,
    initData,

    // Platform
    platform,
    isVersionAtLeast,

    // Storage
    setStorageItem,
    getStorageItem,
  };
}
