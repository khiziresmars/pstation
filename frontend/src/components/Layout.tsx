import { ReactNode } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import { useTranslation } from 'react-i18next';
import clsx from 'clsx';

interface LayoutProps {
  children: ReactNode;
}

export default function Layout({ children }: LayoutProps) {
  const location = useLocation();
  const navigate = useNavigate();
  const { t } = useTranslation();

  const navItems = [
    { path: '/', icon: '🏠', label: t('home') },
    { path: '/vessels', icon: '🚤', label: t('vessels') },
    { path: '/tours', icon: '🏝️', label: t('tours') },
    { path: '/profile', icon: '👤', label: t('profile') },
  ];

  const isDetailPage = location.pathname.includes('/vessels/') ||
                       location.pathname.includes('/tours/') ||
                       location.pathname.includes('/booking');

  return (
    <div className="flex flex-col min-h-screen bg-tg-bg">
      {/* Main content */}
      <main className={clsx('flex-1', !isDetailPage && 'pb-20')}>
        <AnimatePresence mode="wait">
          <motion.div
            key={location.pathname}
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.2 }}
          >
            {children}
          </motion.div>
        </AnimatePresence>
      </main>

      {/* Bottom navigation - hide on detail pages */}
      {!isDetailPage && (
        <nav className="fixed bottom-0 left-0 right-0 bg-tg-section-bg border-t border-tg-secondary-bg safe-bottom z-50">
          <div className="flex justify-around items-center h-16">
            {navItems.map((item) => {
              const isActive = location.pathname === item.path ||
                (item.path !== '/' && location.pathname.startsWith(item.path));

              return (
                <button
                  key={item.path}
                  onClick={() => navigate(item.path)}
                  className={clsx(
                    'flex flex-col items-center justify-center w-full h-full',
                    'transition-colors duration-200',
                    isActive ? 'text-tg-button' : 'text-tg-hint'
                  )}
                >
                  <span className="text-xl mb-0.5">{item.icon}</span>
                  <span className="text-[10px] font-medium">{item.label}</span>
                </button>
              );
            })}
          </div>
        </nav>
      )}
    </div>
  );
}
