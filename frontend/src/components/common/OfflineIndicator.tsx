import React from 'react';
import { usePWA } from '../../hooks/usePWA';
import styles from './OfflineIndicator.module.css';

/**
 * Offline indicator shown when user loses network connection
 */
export const OfflineIndicator: React.FC = () => {
  const [{ isOnline }] = usePWA();

  if (isOnline) {
    return null;
  }

  return (
    <div className={styles.container}>
      <div className={styles.icon}>
        <svg viewBox="0 0 24 24" fill="currentColor">
          <path d="M23.64 7c-.45-.34-4.93-4-11.64-4-1.5 0-2.89.19-4.15.48L18.18 13.8 23.64 7zm-6.6 8.22L3.27 1.44 2 2.72l2.05 2.06C1.91 5.76.59 6.82.36 7l11.63 14.49.01.01.01-.01 3.9-4.86 3.32 3.32 1.27-1.27-3.46-3.46z"/>
        </svg>
      </div>
      <span className={styles.text}>You're offline</span>
    </div>
  );
};

/**
 * PWA update notification
 */
export const UpdateNotification: React.FC = () => {
  const [{ hasUpdate }, { applyUpdate }] = usePWA();

  if (!hasUpdate) {
    return null;
  }

  return (
    <div className={styles.updateContainer}>
      <div className={styles.updateContent}>
        <span>A new version is available!</span>
        <button
          className={styles.updateButton}
          onClick={applyUpdate}
        >
          Update Now
        </button>
      </div>
    </div>
  );
};

/**
 * Install prompt component
 */
export const InstallPrompt: React.FC = () => {
  const [{ isInstallable, isInstalled }, { promptInstall }] = usePWA();

  // Don't show if already installed or not installable
  if (isInstalled || !isInstallable) {
    return null;
  }

  return (
    <div className={styles.installContainer}>
      <div className={styles.installContent}>
        <div className={styles.installInfo}>
          <strong>Install App</strong>
          <span>Add to home screen for quick access</span>
        </div>
        <button
          className={styles.installButton}
          onClick={promptInstall}
        >
          Install
        </button>
      </div>
    </div>
  );
};

export default OfflineIndicator;
