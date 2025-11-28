import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { packagesApi } from '@/services/api';
import { usePrice } from '@/hooks/usePrice';
import type { Package } from '@/types';

interface PackageSelectorProps {
  type: 'vessel' | 'tour';
  itemId: number;
  guests: number;
  hours?: number;
  selectedPackage: Package | null;
  onPackageSelect: (pkg: Package | null) => void;
}

export function PackageSelector({
  type,
  itemId,
  guests,
  hours = 4,
  selectedPackage,
  onPackageSelect,
}: PackageSelectorProps) {
  const { t } = useTranslation();
  const { formatPrice } = usePrice();
  const [showDetails, setShowDetails] = useState<string | null>(null);

  // Fetch packages for item type
  const { data: packages, isLoading } = useQuery({
    queryKey: ['packages', type],
    queryFn: () => packagesApi.getByType(type),
  });

  // Calculate price for selected package
  const { data: calculation } = useQuery({
    queryKey: ['package-calc', selectedPackage?.id, guests, hours],
    queryFn: () =>
      packagesApi.calculate(selectedPackage!.id, {
        guests,
        hours,
        base_id: itemId,
      }),
    enabled: !!selectedPackage,
  });

  if (isLoading) {
    return (
      <div className="card p-4">
        <div className="animate-pulse space-y-3">
          <div className="h-5 bg-tg-secondary-bg rounded w-40" />
          <div className="flex gap-3 overflow-x-auto">
            <div className="h-40 w-48 bg-tg-secondary-bg rounded flex-shrink-0" />
            <div className="h-40 w-48 bg-tg-secondary-bg rounded flex-shrink-0" />
          </div>
        </div>
      </div>
    );
  }

  if (!packages?.length) {
    return null;
  }

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <h2 className="font-semibold text-tg-text flex items-center gap-2">
          🎁 {t('special_packages')}
        </h2>
        {selectedPackage && (
          <button
            onClick={() => onPackageSelect(null)}
            className="text-sm text-tg-link"
          >
            {t('clear')}
          </button>
        )}
      </div>

      {/* Package cards - horizontal scroll */}
      <div className="flex gap-3 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide">
        {packages.map((pkg: Package) => (
          <div
            key={pkg.id}
            className={`flex-shrink-0 w-64 card overflow-hidden cursor-pointer transition-all ${
              selectedPackage?.id === pkg.id
                ? 'ring-2 ring-tg-button'
                : 'hover:shadow-lg'
            }`}
            onClick={() =>
              onPackageSelect(selectedPackage?.id === pkg.id ? null : pkg)
            }
          >
            {/* Package image */}
            {pkg.image && (
              <div className="h-24 overflow-hidden">
                <img
                  src={pkg.image}
                  alt={pkg.name_en}
                  className="w-full h-full object-cover"
                />
              </div>
            )}

            <div className="p-3">
              {/* Badge */}
              {pkg.badge && (
                <span className="inline-block px-2 py-0.5 text-xs font-medium bg-tg-button text-white rounded-full mb-2">
                  {pkg.badge}
                </span>
              )}

              <h3 className="font-semibold text-tg-text">{pkg.name_en}</h3>

              {pkg.short_description_en && (
                <p className="text-xs text-tg-hint mt-1 line-clamp-2">
                  {pkg.short_description_en}
                </p>
              )}

              {/* Included items preview */}
              <div className="flex flex-wrap gap-1 mt-2">
                {pkg.included_addons?.slice(0, 3).map((addon: any, idx: number) => (
                  <span
                    key={idx}
                    className="text-xs bg-tg-secondary-bg px-2 py-0.5 rounded"
                  >
                    {addon.name}
                  </span>
                ))}
                {pkg.included_addons?.length > 3 && (
                  <span className="text-xs text-tg-hint">
                    +{pkg.included_addons.length - 3} more
                  </span>
                )}
              </div>

              {/* Price */}
              <div className="flex items-center justify-between mt-3">
                <div>
                  {pkg.discount_percent > 0 && (
                    <span className="text-xs line-through text-tg-hint mr-2">
                      {formatPrice(pkg.base_price_thb)}
                    </span>
                  )}
                  <span className="font-bold text-tg-accent-text">
                    {formatPrice(pkg.final_price_thb || pkg.base_price_thb)}
                  </span>
                </div>
                {pkg.discount_percent > 0 && (
                  <span className="text-xs font-medium text-green-600 bg-green-100 px-2 py-0.5 rounded">
                    -{pkg.discount_percent}%
                  </span>
                )}
              </div>

              {/* View details */}
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  setShowDetails(showDetails === pkg.slug ? null : pkg.slug);
                }}
                className="text-xs text-tg-link mt-2"
              >
                {showDetails === pkg.slug ? t('hide_details') : t('view_details')}
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Package details modal */}
      {showDetails && (
        <PackageDetails
          slug={showDetails}
          onClose={() => setShowDetails(null)}
          onSelect={(pkg) => {
            onPackageSelect(pkg);
            setShowDetails(null);
          }}
          isSelected={selectedPackage?.slug === showDetails}
        />
      )}

      {/* Selected package summary */}
      {selectedPackage && calculation && (
        <div className="card p-4 bg-gradient-to-r from-tg-button/10 to-tg-button/5 border border-tg-button/20">
          <div className="flex items-start justify-between">
            <div>
              <span className="text-xs text-tg-hint">{t('package_selected')}</span>
              <h4 className="font-semibold text-tg-text">{selectedPackage.name_en}</h4>
              <div className="text-xs text-tg-hint mt-1">
                {t('includes')}: {selectedPackage.included_addons?.map((a: any) => a.name).join(', ')}
              </div>
            </div>
            <div className="text-right">
              <div className="text-lg font-bold text-tg-accent-text">
                {formatPrice(calculation.final_price_thb)}
              </div>
              {calculation.savings_thb > 0 && (
                <div className="text-xs text-green-600">
                  {t('you_save')} {formatPrice(calculation.savings_thb)}
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// Package details modal component
function PackageDetails({
  slug,
  onClose,
  onSelect,
  isSelected,
}: {
  slug: string;
  onClose: () => void;
  onSelect: (pkg: Package) => void;
  isSelected: boolean;
}) {
  const { t } = useTranslation();
  const { formatPrice } = usePrice();

  const { data: pkg, isLoading } = useQuery({
    queryKey: ['package', slug],
    queryFn: () => packagesApi.getBySlug(slug),
  });

  if (isLoading || !pkg) {
    return (
      <div className="fixed inset-0 bg-black/50 z-50 flex items-end">
        <div className="bg-tg-bg w-full rounded-t-2xl p-6 animate-pulse">
          <div className="h-6 bg-tg-secondary-bg rounded w-48 mb-4" />
          <div className="h-32 bg-tg-secondary-bg rounded" />
        </div>
      </div>
    );
  }

  return (
    <div className="fixed inset-0 bg-black/50 z-50 flex items-end" onClick={onClose}>
      <div
        className="bg-tg-bg w-full rounded-t-2xl max-h-[80vh] overflow-y-auto"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header image */}
        {pkg.image && (
          <div className="h-48 overflow-hidden">
            <img src={pkg.image} alt={pkg.name_en} className="w-full h-full object-cover" />
          </div>
        )}

        <div className="p-6">
          <div className="flex justify-between items-start">
            <div>
              {pkg.badge && (
                <span className="inline-block px-2 py-0.5 text-xs font-medium bg-tg-button text-white rounded-full mb-2">
                  {pkg.badge}
                </span>
              )}
              <h2 className="text-xl font-bold text-tg-text">{pkg.name_en}</h2>
            </div>
            <button onClick={onClose} className="text-2xl text-tg-hint">×</button>
          </div>

          {pkg.description_en && (
            <p className="text-tg-hint mt-2">{pkg.description_en}</p>
          )}

          {/* Included addons */}
          <div className="mt-6">
            <h3 className="font-semibold text-tg-text mb-3">{t('whats_included')}</h3>
            <div className="space-y-2">
              {pkg.included_addons?.map((addon: any, idx: number) => (
                <div key={idx} className="flex items-center gap-3 p-3 bg-tg-secondary-bg rounded-lg">
                  <span className="text-xl">{addon.icon || '✓'}</span>
                  <div className="flex-1">
                    <span className="text-tg-text">{addon.name}</span>
                    {addon.quantity > 1 && (
                      <span className="text-tg-hint ml-2">×{addon.quantity}</span>
                    )}
                  </div>
                  <span className="text-sm text-tg-hint line-through">
                    {formatPrice(addon.original_price)}
                  </span>
                </div>
              ))}
            </div>
          </div>

          {/* Price breakdown */}
          <div className="mt-6 p-4 bg-tg-section-bg rounded-xl">
            <div className="flex justify-between text-sm">
              <span className="text-tg-hint">{t('items_value')}</span>
              <span className="text-tg-hint line-through">{formatPrice(pkg.base_price_thb)}</span>
            </div>
            <div className="flex justify-between text-sm mt-1">
              <span className="text-green-600">{t('package_discount')}</span>
              <span className="text-green-600">-{pkg.discount_percent}%</span>
            </div>
            <div className="border-t border-tg-secondary-bg my-2" />
            <div className="flex justify-between">
              <span className="font-semibold text-tg-text">{t('package_price')}</span>
              <span className="text-xl font-bold text-tg-accent-text">
                {formatPrice(pkg.final_price_thb)}
              </span>
            </div>
          </div>

          {/* Select button */}
          <button
            onClick={() => onSelect(pkg)}
            className={`w-full mt-6 py-4 rounded-xl font-semibold text-lg ${
              isSelected
                ? 'bg-tg-secondary-bg text-tg-hint'
                : 'bg-tg-button text-tg-button-text'
            }`}
          >
            {isSelected ? t('selected') : t('select_package')}
          </button>
        </div>
      </div>
    </div>
  );
}

export default PackageSelector;
