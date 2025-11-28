import { useState, useEffect, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { addonsApi } from '@/services/api';
import { usePrice } from '@/hooks/usePrice';
import type { Addon, AddonCategory, SelectedAddon } from '@/types';

interface AddonsSelectorProps {
  type: 'vessel' | 'tour';
  itemId: number;
  itemType?: string; // yacht, speedboat, islands, etc.
  guests: number;
  hours?: number;
  selectedAddons: SelectedAddon[];
  onAddonsChange: (addons: SelectedAddon[]) => void;
}

export function AddonsSelector({
  type,
  itemId,
  itemType,
  guests,
  hours = 4,
  selectedAddons,
  onAddonsChange,
}: AddonsSelectorProps) {
  const { t } = useTranslation();
  const { formatPrice } = usePrice();
  const [expandedCategory, setExpandedCategory] = useState<number | null>(null);

  // Fetch addons for item
  const { data: categories, isLoading } = useQuery({
    queryKey: ['addons', type, itemId, itemType],
    queryFn: () =>
      type === 'vessel'
        ? addonsApi.forVessel(itemId, itemType)
        : addonsApi.forTour(itemId, itemType),
  });

  // Calculate total for selected addons
  const { data: calculation } = useQuery({
    queryKey: ['addons-calc', selectedAddons, guests, hours],
    queryFn: () => addonsApi.calculate(selectedAddons, guests, hours),
    enabled: selectedAddons.length > 0,
  });

  const isSelected = (addonId: number) =>
    selectedAddons.some((a) => a.addon_id === addonId);

  const getQuantity = (addonId: number) =>
    selectedAddons.find((a) => a.addon_id === addonId)?.quantity || 0;

  const toggleAddon = (addon: Addon) => {
    if (isSelected(addon.id)) {
      onAddonsChange(selectedAddons.filter((a) => a.addon_id !== addon.id));
    } else {
      onAddonsChange([
        ...selectedAddons,
        { addon_id: addon.id, quantity: 1, name: addon.name_en, price: addon.price_thb },
      ]);
    }
  };

  const updateQuantity = (addonId: number, quantity: number) => {
    if (quantity <= 0) {
      onAddonsChange(selectedAddons.filter((a) => a.addon_id !== addonId));
    } else {
      onAddonsChange(
        selectedAddons.map((a) =>
          a.addon_id === addonId ? { ...a, quantity } : a
        )
      );
    }
  };

  const calculateAddonPrice = (addon: Addon): number => {
    switch (addon.price_type) {
      case 'per_person':
        return addon.price_thb * guests;
      case 'per_hour':
        return addon.price_thb * hours;
      case 'per_item':
        return addon.price_thb;
      default:
        return addon.price_thb;
    }
  };

  if (isLoading) {
    return (
      <div className="card p-4">
        <div className="animate-pulse space-y-3">
          <div className="h-5 bg-tg-secondary-bg rounded w-32" />
          <div className="h-16 bg-tg-secondary-bg rounded" />
          <div className="h-16 bg-tg-secondary-bg rounded" />
        </div>
      </div>
    );
  }

  if (!categories?.length) {
    return null;
  }

  return (
    <div className="space-y-3">
      <h2 className="font-semibold text-tg-text flex items-center gap-2">
        ✨ {t('enhance_experience')}
      </h2>

      {/* Categories accordion */}
      {categories.map((category: AddonCategory) => (
        <div key={category.id} className="card overflow-hidden">
          <button
            onClick={() =>
              setExpandedCategory(
                expandedCategory === category.id ? null : category.id
              )
            }
            className="w-full p-4 flex items-center justify-between text-left"
          >
            <div className="flex items-center gap-3">
              <span className="text-2xl">{category.icon}</span>
              <div>
                <h3 className="font-medium text-tg-text">{category.name_en}</h3>
                <p className="text-xs text-tg-hint">
                  {category.addons?.length || 0} {t('options')}
                </p>
              </div>
            </div>
            <span
              className={`transition-transform ${
                expandedCategory === category.id ? 'rotate-180' : ''
              }`}
            >
              ▼
            </span>
          </button>

          {expandedCategory === category.id && category.addons && (
            <div className="border-t border-tg-secondary-bg divide-y divide-tg-secondary-bg">
              {category.addons.map((addon: Addon) => (
                <div
                  key={addon.id}
                  className={`p-4 ${
                    isSelected(addon.id) ? 'bg-tg-button/10' : ''
                  }`}
                >
                  <div className="flex items-start gap-3">
                    <button
                      onClick={() => toggleAddon(addon)}
                      className={`w-6 h-6 rounded-full border-2 flex items-center justify-center flex-shrink-0 mt-0.5 ${
                        isSelected(addon.id)
                          ? 'bg-tg-button border-tg-button text-white'
                          : 'border-tg-hint'
                      }`}
                    >
                      {isSelected(addon.id) && '✓'}
                    </button>

                    <div className="flex-1">
                      <div className="flex justify-between">
                        <h4 className="font-medium text-tg-text">
                          {addon.name_en}
                        </h4>
                        <span className="text-tg-accent-text font-semibold">
                          {formatPrice(calculateAddonPrice(addon))}
                        </span>
                      </div>

                      {addon.description_en && (
                        <p className="text-sm text-tg-hint mt-1">
                          {addon.description_en}
                        </p>
                      )}

                      <div className="flex items-center gap-2 mt-1">
                        <span className="text-xs text-tg-hint">
                          {addon.price_type === 'per_person' && `${formatPrice(addon.price_thb)}/person`}
                          {addon.price_type === 'per_hour' && `${formatPrice(addon.price_thb)}/hour`}
                          {addon.price_type === 'per_item' && `${formatPrice(addon.price_thb)}/item`}
                          {addon.price_type === 'fixed' && 'Fixed price'}
                        </span>
                      </div>

                      {/* Quantity selector for per_item */}
                      {isSelected(addon.id) && addon.price_type === 'per_item' && (
                        <div className="flex items-center gap-3 mt-3">
                          <button
                            onClick={() =>
                              updateQuantity(addon.id, getQuantity(addon.id) - 1)
                            }
                            className="w-8 h-8 rounded-full bg-tg-secondary-bg flex items-center justify-center"
                          >
                            -
                          </button>
                          <span className="w-8 text-center font-semibold">
                            {getQuantity(addon.id)}
                          </span>
                          <button
                            onClick={() =>
                              updateQuantity(addon.id, getQuantity(addon.id) + 1)
                            }
                            className="w-8 h-8 rounded-full bg-tg-secondary-bg flex items-center justify-center"
                          >
                            +
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      ))}

      {/* Selected addons summary */}
      {selectedAddons.length > 0 && calculation && (
        <div className="card p-4 bg-tg-button/5 border border-tg-button/20">
          <div className="flex justify-between items-center">
            <div>
              <span className="text-sm text-tg-hint">
                {selectedAddons.length} {t('addons_selected')}
              </span>
              <div className="text-xs text-tg-hint mt-1">
                {selectedAddons.map((a) => a.name).join(', ')}
              </div>
            </div>
            <span className="text-lg font-bold text-tg-accent-text">
              +{formatPrice(calculation.total_thb)}
            </span>
          </div>
        </div>
      )}
    </div>
  );
}

export default AddonsSelector;
