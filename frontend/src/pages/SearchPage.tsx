import { useState, useEffect, useCallback } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { useAppStore } from '@/store/appStore';
import { api } from '@/services/api';
import VesselCard from '@/components/VesselCard';
import TourCard from '@/components/TourCard';
import { Skeleton } from '@/components/common/Skeleton';
import { EmptyState } from '@/components/common/EmptyState';
import styles from './SearchPage.module.css';

interface SearchResult {
  vessels: any[];
  tours: any[];
  total: number;
}

const SearchPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const { language } = useAppStore();

  const [query, setQuery] = useState(searchParams.get('q') || '');
  const [results, setResults] = useState<SearchResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState<'all' | 'vessels' | 'tours'>('all');
  const [recentSearches, setRecentSearches] = useState<string[]>([]);

  const translations: Record<string, Record<string, string>> = {
    en: {
      search: 'Search',
      searchPlaceholder: 'Search yachts, tours, destinations...',
      all: 'All',
      vessels: 'Yachts & Boats',
      tours: 'Tours',
      results: 'results',
      noResults: 'No results found',
      noResultsDesc: 'Try different keywords or browse our catalog',
      recentSearches: 'Recent Searches',
      popularSearches: 'Popular Searches',
      clear: 'Clear',
      browseVessels: 'Browse Yachts',
      browseTours: 'Browse Tours',
    },
    ru: {
      search: 'Поиск',
      searchPlaceholder: 'Поиск яхт, туров, направлений...',
      all: 'Все',
      vessels: 'Яхты и Катера',
      tours: 'Туры',
      results: 'результатов',
      noResults: 'Ничего не найдено',
      noResultsDesc: 'Попробуйте другие ключевые слова',
      recentSearches: 'Недавние поиски',
      popularSearches: 'Популярные запросы',
      clear: 'Очистить',
      browseVessels: 'Смотреть яхты',
      browseTours: 'Смотреть туры',
    },
    th: {
      search: 'ค้นหา',
      searchPlaceholder: 'ค้นหาเรือยอทช์ ทัวร์ จุดหมาย...',
      all: 'ทั้งหมด',
      vessels: 'เรือยอทช์และเรือ',
      tours: 'ทัวร์',
      results: 'ผลลัพธ์',
      noResults: 'ไม่พบผลลัพธ์',
      noResultsDesc: 'ลองคำค้นหาอื่น',
      recentSearches: 'การค้นหาล่าสุด',
      popularSearches: 'การค้นหายอดนิยม',
      clear: 'ล้าง',
      browseVessels: 'ดูเรือยอทช์',
      browseTours: 'ดูทัวร์',
    },
  };

  const t = translations[language] || translations.en;

  const popularSearches = [
    'Phi Phi Island',
    'Luxury Yacht',
    'James Bond Island',
    'Speedboat',
    'Sunset Cruise',
    'Similan Islands',
  ];

  // Load recent searches from localStorage
  useEffect(() => {
    const saved = localStorage.getItem('recentSearches');
    if (saved) {
      setRecentSearches(JSON.parse(saved));
    }
  }, []);

  // Save search to recent
  const saveRecentSearch = useCallback((searchQuery: string) => {
    if (!searchQuery.trim()) return;

    setRecentSearches(prev => {
      const updated = [searchQuery, ...prev.filter(s => s !== searchQuery)].slice(0, 5);
      localStorage.setItem('recentSearches', JSON.stringify(updated));
      return updated;
    });
  }, []);

  // Perform search
  const performSearch = useCallback(async (searchQuery: string) => {
    if (!searchQuery.trim()) {
      setResults(null);
      return;
    }

    setLoading(true);

    try {
      const response = await api.get<SearchResult>('/search', {
        params: { q: searchQuery, limit: 20 }
      });

      setResults(response.data);
      saveRecentSearch(searchQuery);
    } catch (error) {
      console.error('Search failed:', error);
      setResults({ vessels: [], tours: [], total: 0 });
    } finally {
      setLoading(false);
    }
  }, [saveRecentSearch]);

  // Search on query param change
  useEffect(() => {
    const q = searchParams.get('q');
    if (q) {
      setQuery(q);
      performSearch(q);
    }
  }, [searchParams, performSearch]);

  // Handle search submit
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (query.trim()) {
      setSearchParams({ q: query.trim() });
    }
  };

  // Handle quick search
  const handleQuickSearch = (searchQuery: string) => {
    setQuery(searchQuery);
    setSearchParams({ q: searchQuery });
  };

  // Clear recent searches
  const clearRecentSearches = () => {
    setRecentSearches([]);
    localStorage.removeItem('recentSearches');
  };

  // Filter results by tab
  const filteredVessels = activeTab === 'tours' ? [] : (results?.vessels || []);
  const filteredTours = activeTab === 'vessels' ? [] : (results?.tours || []);
  const totalFiltered = filteredVessels.length + filteredTours.length;

  return (
    <div className={styles.container}>
      {/* Search Header */}
      <div className={styles.header}>
        <form onSubmit={handleSubmit} className={styles.searchForm}>
          <div className={styles.searchInputWrapper}>
            <svg className={styles.searchIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
            <input
              type="search"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder={t.searchPlaceholder}
              className={styles.searchInput}
              autoFocus
            />
            {query && (
              <button
                type="button"
                onClick={() => {
                  setQuery('');
                  setResults(null);
                  setSearchParams({});
                }}
                className={styles.clearButton}
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <line x1="18" y1="6" x2="6" y2="18" />
                  <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
              </button>
            )}
          </div>
          <button type="submit" className={styles.searchButton}>
            {t.search}
          </button>
        </form>
      </div>

      {/* Results or Suggestions */}
      <div className={styles.content}>
        {loading ? (
          <div className={styles.loading}>
            <Skeleton height={200} />
            <Skeleton height={200} />
            <Skeleton height={200} />
          </div>
        ) : results ? (
          <>
            {/* Tabs */}
            <div className={styles.tabs}>
              <button
                className={`${styles.tab} ${activeTab === 'all' ? styles.active : ''}`}
                onClick={() => setActiveTab('all')}
              >
                {t.all} ({results.total})
              </button>
              <button
                className={`${styles.tab} ${activeTab === 'vessels' ? styles.active : ''}`}
                onClick={() => setActiveTab('vessels')}
              >
                {t.vessels} ({results.vessels.length})
              </button>
              <button
                className={`${styles.tab} ${activeTab === 'tours' ? styles.active : ''}`}
                onClick={() => setActiveTab('tours')}
              >
                {t.tours} ({results.tours.length})
              </button>
            </div>

            {/* Results */}
            {totalFiltered > 0 ? (
              <div className={styles.results}>
                <p className={styles.resultsCount}>
                  {totalFiltered} {t.results}
                </p>

                {filteredVessels.length > 0 && (
                  <div className={styles.section}>
                    {activeTab === 'all' && (
                      <h3 className={styles.sectionTitle}>{t.vessels}</h3>
                    )}
                    <div className={styles.grid}>
                      {filteredVessels.map((vessel) => (
                        <VesselCard key={vessel.id} vessel={vessel} />
                      ))}
                    </div>
                  </div>
                )}

                {filteredTours.length > 0 && (
                  <div className={styles.section}>
                    {activeTab === 'all' && (
                      <h3 className={styles.sectionTitle}>{t.tours}</h3>
                    )}
                    <div className={styles.grid}>
                      {filteredTours.map((tour) => (
                        <TourCard key={tour.id} tour={tour} />
                      ))}
                    </div>
                  </div>
                )}
              </div>
            ) : (
              <EmptyState
                icon="search"
                title={t.noResults}
                description={t.noResultsDesc}
                action={{
                  label: t.browseVessels,
                  onClick: () => navigate('/vessels'),
                }}
              />
            )}
          </>
        ) : (
          /* Search Suggestions */
          <div className={styles.suggestions}>
            {/* Recent Searches */}
            {recentSearches.length > 0 && (
              <div className={styles.suggestionSection}>
                <div className={styles.suggestionHeader}>
                  <h3>{t.recentSearches}</h3>
                  <button onClick={clearRecentSearches} className={styles.clearLink}>
                    {t.clear}
                  </button>
                </div>
                <div className={styles.suggestionTags}>
                  {recentSearches.map((search, index) => (
                    <button
                      key={index}
                      onClick={() => handleQuickSearch(search)}
                      className={styles.suggestionTag}
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                      </svg>
                      {search}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {/* Popular Searches */}
            <div className={styles.suggestionSection}>
              <h3>{t.popularSearches}</h3>
              <div className={styles.suggestionTags}>
                {popularSearches.map((search, index) => (
                  <button
                    key={index}
                    onClick={() => handleQuickSearch(search)}
                    className={styles.suggestionTag}
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                    </svg>
                    {search}
                  </button>
                ))}
              </div>
            </div>

            {/* Quick Links */}
            <div className={styles.quickLinks}>
              <button onClick={() => navigate('/vessels')} className={styles.quickLink}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
                  <line x1="4" y1="22" x2="4" y2="15" />
                </svg>
                {t.browseVessels}
              </button>
              <button onClick={() => navigate('/tours')} className={styles.quickLink}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <circle cx="12" cy="12" r="10" />
                  <line x1="2" y1="12" x2="22" y2="12" />
                  <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                </svg>
                {t.browseTours}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default SearchPage;
