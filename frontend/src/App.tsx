import { useEffect } from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import WebApp from '@twa-dev/sdk';
import { useAppStore } from '@/store/appStore';
import Layout from '@/components/Layout';
import HomePage from '@/pages/HomePage';
import VesselsPage from '@/pages/VesselsPage';
import VesselDetailPage from '@/pages/VesselDetailPage';
import ToursPage from '@/pages/ToursPage';
import TourDetailPage from '@/pages/TourDetailPage';
import BookingPage from '@/pages/BookingPage';
import BookingConfirmPage from '@/pages/BookingConfirmPage';
import ProfilePage from '@/pages/ProfilePage';
import FavoritesPage from '@/pages/FavoritesPage';
import BookingsHistoryPage from '@/pages/BookingsHistoryPage';

function App() {
  const { setUser, setTheme, setLanguage } = useAppStore();

  useEffect(() => {
    // Initialize Telegram Web App
    WebApp.ready();
    WebApp.expand();

    // Set theme based on Telegram theme
    const colorScheme = WebApp.colorScheme;
    setTheme(colorScheme === 'dark' ? 'dark' : 'light');

    // Get user data from Telegram
    const tgUser = WebApp.initDataUnsafe.user;
    if (tgUser) {
      setUser({
        telegramId: tgUser.id,
        username: tgUser.username,
        firstName: tgUser.first_name,
        lastName: tgUser.last_name,
        languageCode: tgUser.language_code || 'en',
        photoUrl: tgUser.photo_url,
      });

      // Set language based on Telegram language
      if (tgUser.language_code) {
        const lang = ['ru', 'th'].includes(tgUser.language_code) ? tgUser.language_code : 'en';
        setLanguage(lang as 'en' | 'ru' | 'th');
      }
    }

    // Enable haptic feedback
    WebApp.HapticFeedback.impactOccurred('light');

    // Handle viewport changes
    const handleViewportChanged = () => {
      document.documentElement.style.setProperty(
        '--tg-viewport-height',
        `${WebApp.viewportHeight}px`
      );
    };

    WebApp.onEvent('viewportChanged', handleViewportChanged);
    handleViewportChanged();

    return () => {
      WebApp.offEvent('viewportChanged', handleViewportChanged);
    };
  }, [setUser, setTheme, setLanguage]);

  return (
    <BrowserRouter>
      <Layout>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/vessels" element={<VesselsPage />} />
          <Route path="/vessels/:slug" element={<VesselDetailPage />} />
          <Route path="/tours" element={<ToursPage />} />
          <Route path="/tours/:slug" element={<TourDetailPage />} />
          <Route path="/booking/:type/:id" element={<BookingPage />} />
          <Route path="/booking/confirm/:reference" element={<BookingConfirmPage />} />
          <Route path="/profile" element={<ProfilePage />} />
          <Route path="/favorites" element={<FavoritesPage />} />
          <Route path="/bookings" element={<BookingsHistoryPage />} />
        </Routes>
      </Layout>
    </BrowserRouter>
  );
}

export default App;
