export interface SEOConfig {
    site_name: string;
    default_locale: string;
    supported_locales: string[];
    masters_type: string;
    masters_type_key: string;
    city: string;
    region: string;
    country: string;
    currency: string;
    phone_country_code: string;
    social_media: {
        facebook?: string;
        instagram?: string;
        twitter?: string;
        linkedin?: string;
        youtube?: string;
    };
    contact_info: {
        email: string;
        phone: string;
        address: string;
        working_hours: string;
    };
    seo_settings: {
        enable_structured_data: boolean;
        enable_sitemap: boolean;
        enable_robots: boolean;
        enable_analytics: boolean;
        google_analytics_id?: string;
    };
}

// Default configuration for auto mechanics
export const defaultSEOConfig: SEOConfig = {
    site_name: 'AutoMasters Hub',
    default_locale: 'uk',
    supported_locales: ['uk', 'en'],
    masters_type: 'Автомайстри та автомайстерні',
    masters_type_key: 'auto_mechanics',
    city: 'Київ',
    region: 'Київська область',
    country: 'Україна',
    currency: 'UAH',
    phone_country_code: '+380',
    social_media: {
        facebook: 'https://facebook.com/automastershub',
        instagram: 'https://instagram.com/automastershub',
        twitter: 'https://twitter.com/automastershub',
    },
    contact_info: {
        email: 'info@automastershub.com',
        phone: '+380441234567',
        address: 'м. Київ, вул. Автомайстерська, 1',
        working_hours: 'Пн-Пт: 9:00-18:00, Сб: 9:00-15:00',
    },
    seo_settings: {
        enable_structured_data: true,
        enable_sitemap: true,
        enable_robots: true,
        enable_analytics: true,
        google_analytics_id: 'GA_MEASUREMENT_ID',
    },
};

// Configuration for beauty salons (example for easy cloning)
export const beautySalonSEOConfig: SEOConfig = {
    site_name: 'BeautyMasters Hub',
    default_locale: 'uk',
    supported_locales: ['uk', 'en'],
    masters_type: 'Майстри краси та салони краси',
    masters_type_key: 'beauty_salons',
    city: 'Київ',
    region: 'Київська область',
    country: 'Україна',
    currency: 'UAH',
    phone_country_code: '+380',
    social_media: {
        facebook: 'https://facebook.com/beautymastershub',
        instagram: 'https://instagram.com/beautymastershub',
        twitter: 'https://twitter.com/beautymastershub',
    },
    contact_info: {
        email: 'info@beautymastershub.com',
        phone: '+380441234567',
        address: 'м. Київ, вул. Красива, 1',
        working_hours: 'Пн-Вс: 9:00-21:00',
    },
    seo_settings: {
        enable_structured_data: true,
        enable_sitemap: true,
        enable_robots: true,
        enable_analytics: true,
        google_analytics_id: 'GA_MEASUREMENT_ID',
    },
};

// Function to get SEO config based on environment or type
export function getSEOConfig(type?: string): SEOConfig {
    const envType = (import.meta as any)?.env?.VITE_SEO_TYPE as
        | string
        | undefined;
    const resolvedType = type ?? envType ?? 'auto_mechanics';
    switch (resolvedType) {
        case 'beauty_salons':
            return beautySalonSEOConfig;
        case 'auto_mechanics':
        default:
            return defaultSEOConfig;
    }
}

// Function to generate structured data for masters listing page
export function generateStructuredData(
    config: SEOConfig,
    masters: any[],
    locale: string = 'uk',
) {
    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        name: config.masters_type,
        description: config.masters_type,
        url: `${window.location.origin}/masters`,
        numberOfItems: masters.length,
        itemListElement: masters.map((master, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            item: {
                '@type': 'LocalBusiness',
                name: master.name,
                description: master.description,
                address: {
                    '@type': 'PostalAddress',
                    addressLocality: config.city,
                    addressRegion: config.region,
                    addressCountry: config.country,
                    streetAddress: master.address,
                },
                telephone: master.phone,
                aggregateRating: {
                    '@type': 'AggregateRating',
                    ratingValue: master.rating,
                    reviewCount: master.reviews_count || 0,
                },
                priceRange: master.price_range || '$$',
                openingHours: config.contact_info.working_hours,
                url: `${window.location.origin}/masters/${master.slug}`,
            },
        })),
    };

    return structuredData;
}

// Function to generate meta tags for masters listing page
export function generateMetaTags(config: SEOConfig, locale: string = 'uk') {
    const isUkrainian = locale === 'uk';

    return {
        title: isUkrainian
            ? `${config.masters_type} - ${config.city}`
            : `${config.masters_type} - ${config.city}`,
        description: isUkrainian
            ? `Знайдіть найкращих ${config.masters_type.toLowerCase()} у ${config.city}. Якісні послуги, рейтинги, відгуки та контакти.`
            : `Find the best ${config.masters_type.toLowerCase()} in ${config.city}. Quality services, ratings, reviews and contacts.`,
        keywords: isUkrainian
            ? `${config.masters_type.toLowerCase()}, послуги, рейтинг, відгуки, ${config.city}, ${config.region}`
            : `${config.masters_type.toLowerCase()}, services, rating, reviews, ${config.city}, ${config.region}`,
        ogTitle: isUkrainian
            ? `${config.masters_type} - ${config.city}`
            : `${config.masters_type} - ${config.city}`,
        ogDescription: isUkrainian
            ? `Знайдіть найкращих ${config.masters_type.toLowerCase()} у ${config.city}`
            : `Find the best ${config.masters_type.toLowerCase()} in ${config.city}`,
        ogImage: `${window.location.origin}/images/${config.masters_type_key}-preview.jpg`,
        ogUrl: `${window.location.origin}/masters`,
        canonicalUrl: `${window.location.origin}/masters`,
    };
}

// Function to generate breadcrumb data
export function generateBreadcrumbData(
    config: SEOConfig,
    locale: string = 'uk',
) {
    const isUkrainian = locale === 'uk';

    return [
        {
            name: isUkrainian ? 'Головна' : 'Home',
            href: route('welcome'),
        },
        {
            name: config.masters_type,
            href: route('masters.index'),
        },
    ];
}
