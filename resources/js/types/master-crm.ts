export type Flavor = 'carbeat' | 'floxcity';

export type AppointmentKind = 'work' | 'next' | 'request';
export type PaymentStatus = 'pending' | 'partial' | 'paid' | 'debt';
export type PaymentMethod =
    | 'none'
    | 'cash'
    | 'card'
    | 'qr'
    | 'transfer'
    | 'mixed';

export interface CrmAppointment {
    id: string;
    bayId: string;
    /** Only present on the flat /crm/appointments listing (not in the
     * per-day snapshot, where the bay is already the grouping key). */
    bayTitle?: string;
    clientId: string | null;
    vehicleId: string | null;
    serviceCatalogId: string | null;
    kind: AppointmentKind;
    startsAt: string;
    endsAt: string;
    customerName: string;
    customerPhone: string;
    carModel: string;
    plateNumber: string;
    serviceName: string;
    priceUah: number | null;
    paymentStatus: PaymentStatus;
    paymentMethod: PaymentMethod;
    paidAmountUah: number;
    notes: string;
    hasPhotoRequest: boolean;
}

export interface CrmBay {
    id: string;
    title: string;
    technicianName: string;
    status: 'free' | 'busy' | 'request' | 'next';
    isArchived: boolean;
    appointments: CrmAppointment[];
}

export interface CrmClient {
    id: string;
    name: string;
    phone: string;
}

export interface CrmVehicle {
    id: string;
    clientId: string;
    modelName: string;
    plateNumber: string;
}

export interface CrmServiceCatalogItem {
    id: string;
    nameUk: string;
    nameEn: string;
    durationMinutes: number;
    priceUah: number;
    displayOrder: number;
}

export interface CrmChatThread {
    id: string;
    customerName: string;
    carModel: string;
    plateNumber: string;
    lastMessagePreview: string;
    updatedAt: string;
    unreadCount: number;
    hasPhotoRequest: boolean;
}

export interface CrmGarageSettings {
    garageName: string;
    garagePhone: string;
    address: string;
    teamSize: number;
    workingHours: string;
    subscriptionPlan: string;
}

export interface CrmSnapshot {
    businessDay: string;
    bays: CrmBay[];
    clients: CrmClient[];
    vehicles: CrmVehicle[];
    serviceCatalog: CrmServiceCatalogItem[];
    chatThreads: CrmChatThread[];
    messagesByThreadId: Record<string, unknown[]>;
    garageSettings: CrmGarageSettings;
    lastSyncAt: string;
}

export interface CrmChange {
    type: string;
    payload: Record<string, unknown>;
}

export interface AppointmentsPage {
    data: CrmAppointment[];
    currentPage: number;
    lastPage: number;
    total: number;
}

export interface FinanceCash {
    cashRevenue: number;
    cardRevenue: number;
    qrRevenue: number;
    partialOrders: number;
    partialOutstanding: number;
    pendingOrders: number;
    pendingAmount: number;
    debtOrders: number;
    debtAmount: number;
    paidRevenue: number;
    outstandingRevenue: number;
    totalRevenue: number;
}

export interface FinanceProfitability {
    totalRevenue: number;
    completedOrders: number;
    averageCheck: number;
    topBayLabel: string;
    topBayRevenue: number;
    topTechnicianLabel: string;
    topTechnicianRevenue: number;
    revenueByBay: Record<string, number>;
    revenueByTechnician: Record<string, number>;
}

export interface FinanceKpi {
    completedOrders: number;
    averageCheck: number;
    partialOrders: number;
    partialOutstanding: number;
    debtOrders: number;
    debtAmount: number;
    revenueByTechnician: Record<string, number>;
}

export interface FinanceReport {
    from: string;
    to: string;
    cash: FinanceCash;
    profitability: FinanceProfitability;
    kpi: FinanceKpi;
}
