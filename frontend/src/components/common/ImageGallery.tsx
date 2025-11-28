import { useState } from 'react';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Pagination, Zoom } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/zoom';

interface ImageGalleryProps {
  images: string[];
  alt?: string;
  aspectRatio?: 'video' | 'square' | 'wide';
}

export function ImageGallery({ images, alt = 'Image', aspectRatio = 'video' }: ImageGalleryProps) {
  const [loadError, setLoadError] = useState<Record<number, boolean>>({});

  const aspectClasses = {
    video: 'aspect-video',
    square: 'aspect-square',
    wide: 'aspect-[2/1]',
  };

  const handleImageError = (index: number) => {
    setLoadError((prev) => ({ ...prev, [index]: true }));
  };

  const fallbackImage = 'https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?w=800';

  return (
    <div className={aspectClasses[aspectRatio]}>
      <Swiper
        modules={[Pagination, Zoom]}
        pagination={{ clickable: true }}
        zoom={{ maxRatio: 3 }}
        className="h-full w-full"
      >
        {images.map((image, index) => (
          <SwiperSlide key={index}>
            <div className="swiper-zoom-container h-full">
              <img
                src={loadError[index] ? fallbackImage : image}
                alt={`${alt} ${index + 1}`}
                className="w-full h-full object-cover"
                onError={() => handleImageError(index)}
                loading={index === 0 ? 'eager' : 'lazy'}
              />
            </div>
          </SwiperSlide>
        ))}
      </Swiper>
    </div>
  );
}
