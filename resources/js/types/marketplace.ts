export interface IndustrySkill {
    id: number;
    name: string;
    sort_order: number;
}

export interface IndustryTemplate {
    id: number;
    title: string;
    description: string;
    skills: string[];
    sort_order: number;
}

export interface Industry {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    skills: IndustrySkill[];
    templates: IndustryTemplate[];
}

export interface Shop {
    id: number;
    user_id: number;
    name: string;
    description?: string;
    phone?: string;
    website?: string;
    operating_hours?: Record<string, any>;
    status: 'active' | 'inactive' | 'suspended';
    industry_id?: number | null;
    custom_skills?: string[];
    industry?: Industry;
    ein?: string | null;
    created_at: string;
    updated_at: string;
}

export interface ShopLocation {
    id: number;
    shop_id: number;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state: string;
    zip_code: string;
    latitude?: number;
    longitude?: number;
    is_primary: boolean;
    geocoded_at?: string;
    created_at: string;
    updated_at: string;
    shop?: Shop;
}

export interface Certification {
    id: string;
    type: string;
    name: string;
    issued_at?: string;
    expires_at?: string;
    issuer?: string;
    added_at: string;
}

export interface AvailabilityDay {
    available: boolean;
    start: string;
    end: string;
}

export interface Provider {
    id: number;
    user_id: number;
    user?: { id: number; name: string; email: string };
    bio?: string;
    skills?: string[];
    years_experience: number;
    average_rating: number;
    total_ratings: number;
    completed_bookings: number;
    is_active: boolean;
    availability_schedule?: Record<string, AvailabilityDay>;
    blackout_dates?: string[];
    min_notice_hours: number;
    certifications?: Certification[];
    service_area_max_miles: number;
    preferred_zip_codes?: string[];
    created_at: string;
    updated_at: string;
}

export interface Rating {
    id: number;
    booking_id: number;
    rater_id: number;
    ratee_id: number;
    rater_type: 'shop' | 'provider';
    rating: number;
    comment?: string;
    created_at: string;
    updated_at: string;
    booking?: Booking;
    rater?: { id: number; name: string };
}

export interface ServiceRequest {
    id: number;
    shop_location_id: number;
    title: string;
    description: string;
    skills_required?: string[];
    service_date: string;
    start_time: string;
    end_time: string;
    price: number;
    status: 'pending_payment' | 'open' | 'filled' | 'expired' | 'cancelled';
    status_label: string;
    status_variant: string;
    expires_at: string;
    created_at: string;
    updated_at: string;
    shop_location?: ShopLocation;
    booking?: Booking;
    distance?: number | null;
}

export interface Booking {
    id: number;
    service_request_id: number;
    provider_id: number;
    service_price: number;
    platform_fee: number;
    provider_payout: number;
    status: 'pending' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled';
    status_label: string;
    status_variant: string;
    accepted_at: string;
    completed_at?: string;
    cancelled_at?: string;
    cancellation_reason?: string;
    created_at: string;
    updated_at: string;
    service_request?: ServiceRequest;
    provider?: Provider;
}

export interface Payout {
    id: number;
    provider_id: number;
    booking_id: number;
    amount: number;
    status: 'pending' | 'paid' | 'failed';
    stripe_transfer_id?: string;
    paid_at?: string;
    created_at: string;
    updated_at: string;
    booking?: Booking;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
