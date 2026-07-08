export type Flavor = 'carbeat' | 'floxcity';

export interface MasterService {
    id: number;
    name: string;
    is_primary?: boolean;
}

export interface MasterReview {
    id: number;
    rating: number | null;
    review?: string;
    user?: { id?: number; name?: string; phone?: string } | null;
    created_at?: string;
    replies?: MasterReview[];
}

export interface MasterDetails {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
    available?: boolean;
    main_thumb_url?: string | null;
    main_photo?: string | null;
    slug?: string | null;
    description?: string | null;
    address?: string | null;
    city?: string | null;
    rating?: number;
    reviews_count?: number;
    phone?: string | null;
    main_service_id?: number;
    services?: MasterService[];
    reviews?: MasterReview[];
    photos?: Array<{ id: number; url: string }>;
    working_hours?:
        | Array<Record<string, unknown>>
        | Record<string, unknown>
        | null;
    is_claimed?: boolean;
    claim_link?: string | null;
    [key: string]: unknown;
}

export interface SeoPayload {
    title: string;
    description: string;
    canonical: string;
    robots?: string;
    ogImage?: string | null;
    structuredData?: unknown;
}

export interface SeoLink {
    label: string;
    href: string;
    active?: boolean;
}

export interface SeoStat {
    label: string;
    value: string;
}

export interface SeoFaq {
    q: string;
    a: string;
}

export interface SeoSection {
    heading: string;
    body: string;
}

export interface SeoMasterCard {
    id: number;
    name: string;
    slug: string;
    address?: string | null;
    city?: string | null;
    rating?: number;
    reviews_count?: number;
    service_names?: string[];
}

export interface SeoContentPayload {
    type: 'master' | 'city' | 'city_service';
    title: string;
    intro?: string;
    sections?: SeoSection[];
    breadcrumbs: SeoLink[];
    stats: SeoStat[];
    serviceLinks: SeoLink[];
    topMasters: SeoMasterCard[];
    relatedLinks: SeoLink[];
    faq: SeoFaq[];
}
