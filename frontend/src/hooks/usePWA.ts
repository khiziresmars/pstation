import { useState, useEffect, useCallback } from 'react';

interface PWAState {
  isOnline: boolean;
  isInstalled: boolean;
  isInstallable: boolean;
  hasUpdate: boolean;
  isLoading: boolean;
}

interface PWAActions {
  promptInstall: () => Promise<boolean>;
  applyUpdate: () => void;
  checkForUpdate: () => Promise<void>;
}

type BeforeInstallPromptEvent = Event & {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

let deferredPrompt: BeforeInstallPromptEvent | null = null;

/**
 * Custom hook for PWA functionality
 * Handles online/offline status, install prompt, and service worker updates
 */
export function usePWA(): [PWAState, PWAActions] {
  const [isOnline, setIsOnline] = useState(navigator.onLine);
  const [isInstalled, setIsInstalled] = useState(false);
  const [isInstallable, setIsInstallable] = useState(false);
  const [hasUpdate, setHasUpdate] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [registration, setRegistration] = useState<ServiceWorkerRegistration | null>(null);

  // Check if app is already installed
  useEffect(() => {
    const checkInstalled = () => {
      // Check if running in standalone mode (installed PWA)
      const isStandalone =
        window.matchMedia('(display-mode: standalone)').matches ||
        (window.navigator as any).standalone === true;

      setIsInstalled(isStandalone);
    };

    checkInstalled();

    // Listen for display mode changes
    const mediaQuery = window.matchMedia('(display-mode: standalone)');
    const handler = (e: MediaQueryListEvent) => setIsInstalled(e.matches);
    mediaQuery.addEventListener('change', handler);

    return () => mediaQuery.removeEventListener('change', handler);
  }, []);

  // Handle online/offline status
  useEffect(() => {
    const handleOnline = () => setIsOnline(true);
    const handleOffline = () => setIsOnline(false);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  // Handle beforeinstallprompt event
  useEffect(() => {
    const handleBeforeInstall = (e: Event) => {
      e.preventDefault();
      deferredPrompt = e as BeforeInstallPromptEvent;
      setIsInstallable(true);
    };

    const handleAppInstalled = () => {
      deferredPrompt = null;
      setIsInstallable(false);
      setIsInstalled(true);
    };

    window.addEventListener('beforeinstallprompt', handleBeforeInstall);
    window.addEventListener('appinstalled', handleAppInstalled);

    return () => {
      window.removeEventListener('beforeinstallprompt', handleBeforeInstall);
      window.removeEventListener('appinstalled', handleAppInstalled);
    };
  }, []);

  // Register service worker and listen for updates
  useEffect(() => {
    if (!('serviceWorker' in navigator)) {
      setIsLoading(false);
      return;
    }

    const registerSW = async () => {
      try {
        const reg = await navigator.serviceWorker.ready;
        setRegistration(reg);

        // Check for updates
        reg.addEventListener('updatefound', () => {
          const newWorker = reg.installing;
          if (newWorker) {
            newWorker.addEventListener('statechange', () => {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                setHasUpdate(true);
              }
            });
          }
        });

        // Listen for controlling service worker change
        let refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', () => {
          if (!refreshing) {
            refreshing = true;
            window.location.reload();
          }
        });
      } catch (error) {
        console.error('SW registration error:', error);
      } finally {
        setIsLoading(false);
      }
    };

    registerSW();
  }, []);

  // Prompt user to install PWA
  const promptInstall = useCallback(async (): Promise<boolean> => {
    if (!deferredPrompt) {
      console.log('Install prompt not available');
      return false;
    }

    try {
      await deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;

      if (outcome === 'accepted') {
        deferredPrompt = null;
        setIsInstallable(false);
        return true;
      }

      return false;
    } catch (error) {
      console.error('Install prompt error:', error);
      return false;
    }
  }, []);

  // Apply service worker update
  const applyUpdate = useCallback(() => {
    if (registration?.waiting) {
      registration.waiting.postMessage({ type: 'SKIP_WAITING' });
    }
  }, [registration]);

  // Check for service worker update
  const checkForUpdate = useCallback(async () => {
    if (registration) {
      await registration.update();
    }
  }, [registration]);

  const state: PWAState = {
    isOnline,
    isInstalled,
    isInstallable,
    hasUpdate,
    isLoading,
  };

  const actions: PWAActions = {
    promptInstall,
    applyUpdate,
    checkForUpdate,
  };

  return [state, actions];
}

/**
 * Hook for offline data sync
 */
export function useOfflineSync() {
  const requestSync = useCallback(async (tag: string) => {
    if (!('serviceWorker' in navigator) || !('sync' in ServiceWorkerRegistration.prototype)) {
      console.log('Background sync not supported');
      return false;
    }

    try {
      const registration = await navigator.serviceWorker.ready;
      await (registration as any).sync.register(tag);
      return true;
    } catch (error) {
      console.error('Sync registration failed:', error);
      return false;
    }
  }, []);

  const savePendingBooking = useCallback(async (bookingData: any) => {
    return new Promise<void>((resolve, reject) => {
      const request = indexedDB.open('phuket-yachts', 1);

      request.onerror = () => reject(request.error);

      request.onupgradeneeded = (event) => {
        const db = (event.target as IDBOpenDBRequest).result;
        if (!db.objectStoreNames.contains('pending-bookings')) {
          db.createObjectStore('pending-bookings', { keyPath: 'id', autoIncrement: true });
        }
      };

      request.onsuccess = () => {
        const db = request.result;
        const transaction = db.transaction(['pending-bookings'], 'readwrite');
        const store = transaction.objectStore('pending-bookings');

        const addRequest = store.add({
          data: bookingData,
          timestamp: Date.now(),
        });

        addRequest.onsuccess = () => {
          requestSync('sync-bookings');
          resolve();
        };
        addRequest.onerror = () => reject(addRequest.error);
      };
    });
  }, [requestSync]);

  return { requestSync, savePendingBooking };
}

export default usePWA;
