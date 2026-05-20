<?php

namespace App\Http\Services;

use App\Models\Booking;
use App\Models\CrmChatThread;
use App\Models\CrmGarageClient;
use App\Models\CrmGarageVehicle;
use App\Models\CrmMessage;
use App\Models\Master;
use App\Models\MasterBay;
use App\Models\MasterServiceCatalogItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterCrmService
{
    public function buildSnapshot(Master $master, string $businessDay): array
    {
        $date = Carbon::parse($businessDay)->startOfDay();

        $bays = MasterBay::where('master_id', $master->id)
            ->orderBy('display_order')
            ->get();

        $bookings = Booking::where('master_id', $master->id)
            ->whereNotNull('crm_uuid')
            ->whereDate('start_time', $date)
            ->orderBy('start_time')
            ->get();

        $clients = CrmGarageClient::where('master_id', $master->id)
            ->orderBy('name')
            ->get();

        $vehicles = CrmGarageVehicle::where('master_id', $master->id)
            ->orderBy('model_name')
            ->get();

        $serviceCatalog = MasterServiceCatalogItem::where('master_id', $master->id)
            ->orderBy('display_order')
            ->get();

        $threads = CrmChatThread::where('master_id', $master->id)
            ->orderByDesc('thread_updated_at')
            ->get();

        $threadUuids = $threads->pluck('uuid')->toArray();
        $messages = empty($threadUuids)
            ? collect()
            : CrmMessage::whereIn('thread_uuid', $threadUuids)
                ->orderBy('message_created_at')
                ->get()
                ->groupBy('thread_uuid');

        $bayMap = $bays->keyBy('id');
        $appointmentsByBay = $bookings->groupBy('bay_id');

        $garageSettings = $this->buildGarageSettings($master);

        return [
            'businessDay' => $date->toDateString(),
            'bays' => $bays->map(function (MasterBay $bay) use ($appointmentsByBay, $bayMap) {
                $bayBookings = $appointmentsByBay->get($bay->id, collect());
                return [
                    'id' => $bay->uuid ?? (string) $bay->id,
                    'title' => $bay->title,
                    'technicianName' => $bay->technician_name ?? '',
                    'status' => $bay->status ?? 'free',
                    'isArchived' => ! $bay->is_active,
                    'appointments' => $bayBookings->map(fn ($b) => $this->bookingToAppointment($b, $bayMap))->values()->all(),
                ];
            })->values()->all(),
            'clients' => $clients->map(fn ($c) => [
                'id' => $c->uuid,
                'name' => $c->name,
                'phone' => $c->phone ?? '',
            ])->values()->all(),
            'vehicles' => $vehicles->map(fn ($v) => [
                'id' => $v->uuid,
                'clientId' => $v->garage_client_uuid ?? '',
                'modelName' => $v->model_name,
                'plateNumber' => $v->plate_number,
            ])->values()->all(),
            'serviceCatalog' => $serviceCatalog->map(fn ($s) => [
                'id' => $s->uuid,
                'nameUk' => $s->name_uk,
                'nameEn' => $s->name_en,
                'durationMinutes' => $s->duration_minutes,
                'priceUah' => $s->price_uah,
                'displayOrder' => $s->display_order,
            ])->values()->all(),
            'chatThreads' => $threads->map(fn ($t) => [
                'id' => $t->uuid,
                'customerName' => $t->customer_name,
                'carModel' => $t->car_model ?? '',
                'plateNumber' => $t->plate_number ?? '',
                'lastMessagePreview' => $t->last_message_preview ?? '',
                'updatedAt' => $t->thread_updated_at
                    ? $t->thread_updated_at->toIso8601String()
                    : $t->updated_at->toIso8601String(),
                'unreadCount' => $t->unread_count,
                'hasPhotoRequest' => (bool) $t->has_photo_request,
            ])->values()->all(),
            'messagesByThreadId' => (object) $messages->map(fn ($msgs) => $msgs->map(fn ($m) => [
                'id' => $m->uuid,
                'threadId' => $m->thread_uuid,
                'direction' => $m->direction,
                'kind' => $m->kind,
                'body' => $m->body,
                'createdAt' => $m->message_created_at->toIso8601String(),
            ])->values()->all())->all(),
            'garageSettings' => $garageSettings,
            'lastSyncAt' => now()->toIso8601String(),
        ];
    }

    public function ensureDefaultBay(Master $master): void
    {
        $hasBay = MasterBay::withoutGlobalScope('app')
            ->where('master_id', $master->id)
            ->exists();

        if ($hasBay) {
            return;
        }

        $title = $master->app === 'floxcity' ? 'Крісло 1' : 'Бокс 1';

        MasterBay::withoutGlobalScopes()->create([
            'uuid'            => Str::uuid()->toString(),
            'master_id'       => $master->id,
            'title'           => $title,
            'technician_name' => $master->name ?? '',
            'is_active'       => true,
            'display_order'   => 0,
            'status'          => 'free',
            'app'             => $master->app ?? 'carbeat',
        ]);
    }

    public function applyChanges(Master $master, array $changes, string $businessDay): void
    {
        DB::transaction(function () use ($master, $changes) {
            foreach ($changes as $change) {
                $type = $change['type'] ?? '';
                $payload = $change['payload'] ?? [];
                $this->applyChange($master, $type, $payload);
            }
        });
    }

    private function applyChange(Master $master, string $type, array $payload): void
    {
        match ($type) {
            'set_bay_status' => $this->applySetBayStatus($master, $payload),
            'create_appointment' => $this->applyCreateAppointment($master, $payload),
            'update_appointment_payment' => $this->applyUpdateAppointmentPayment($master, $payload),
            'save_bay' => $this->applySaveBay($master, $payload),
            'delete_bay' => $this->applyDeleteBay($master, $payload),
            'save_client' => $this->applySaveClient($master, $payload),
            'delete_client' => $this->applyDeleteClient($master, $payload),
            'save_vehicle' => $this->applySaveVehicle($master, $payload),
            'delete_vehicle' => $this->applyDeleteVehicle($master, $payload),
            'save_service' => $this->applySaveService($master, $payload),
            'delete_service' => $this->applyDeleteService($master, $payload),
            'open_thread' => $this->applyOpenThread($master, $payload),
            'send_message' => $this->applySendMessage($master, $payload),
            default => null,
        };
    }

    private function applySetBayStatus(Master $master, array $payload): void
    {
        $bayUuid = $payload['bayId'] ?? null;
        $status = $payload['status'] ?? 'free';
        if (! $bayUuid) {
            return;
        }
        MasterBay::where('master_id', $master->id)
            ->where('uuid', $bayUuid)
            ->update(['status' => $status]);
    }

    private function applyCreateAppointment(Master $master, array $payload): void
    {
        $crmUuid = $payload['id'] ?? null;
        if (! $crmUuid) {
            return;
        }
        if (Booking::where('crm_uuid', $crmUuid)->exists()) {
            return;
        }

        $bay = $this->findBayByUuid($master, $payload['bayId'] ?? null);

        Booking::create([
            'master_id' => $master->id,
            'bay_id' => $bay?->id,
            'crm_uuid' => $crmUuid,
            'crm_garage_client_uuid' => $payload['clientId'] ?? null,
            'crm_vehicle_uuid' => $payload['vehicleId'] ?? null,
            'crm_service_catalog_uuid' => $payload['serviceCatalogId'] ?? null,
            'crm_kind' => $payload['kind'] ?? 'work',
            'start_time' => Carbon::parse($payload['startsAt']),
            'end_time' => Carbon::parse($payload['endsAt']),
            'service_name' => $payload['serviceName'] ?? null,
            'customer_name' => $payload['customerName'] ?? null,
            'customer_phone' => $payload['customerPhone'] ?? null,
            'car_model' => $payload['carModel'] ?? null,
            'plate_number' => $payload['plateNumber'] ?? null,
            'total_amount' => $payload['priceUah'] ?? 0,
            'paid_amount' => $payload['paidAmountUah'] ?? 0,
            'financial_status' => $payload['paymentStatus'] ?? 'pending',
            'crm_payment_method' => $payload['paymentMethod'] ?? 'none',
            'has_photo_request' => (bool) ($payload['hasPhotoRequest'] ?? false),
            'note' => $payload['notes'] ?? null,
            'status' => 'confirmed',
            'app' => $master->app,
        ]);
    }

    private function applyUpdateAppointmentPayment(Master $master, array $payload): void
    {
        $crmUuid = $payload['id'] ?? null;
        if (! $crmUuid) {
            return;
        }

        $total = (int) ($payload['priceUah'] ?? 0);
        $paid = (int) ($payload['paidAmountUah'] ?? 0);
        $paid = max(0, min($paid, $total));
        $status = match (true) {
            $paid <= 0 => 'pending',
            $paid >= $total => 'paid',
            default => 'partial',
        };

        Booking::where('master_id', $master->id)
            ->where('crm_uuid', $crmUuid)
            ->update([
                'crm_payment_method' => $payload['paymentMethod'] ?? 'none',
                'paid_amount' => $paid,
                'financial_status' => $status,
            ]);
    }

    private function applySaveBay(Master $master, array $payload): void
    {
        $uuid = $payload['id'] ?? null;
        if (! $uuid) {
            return;
        }

        MasterBay::updateOrCreate(
            ['master_id' => $master->id, 'uuid' => $uuid],
            [
                'title' => $payload['title'] ?? 'Бокс',
                'technician_name' => $payload['technicianName'] ?? null,
                'status' => $payload['status'] ?? 'free',
                'is_active' => ! ($payload['isArchived'] ?? false),
                'app' => $master->app,
            ]
        );
    }

    private function applyDeleteBay(Master $master, array $payload): void
    {
        $bayUuid = $payload['bayId'] ?? null;
        if (! $bayUuid) {
            return;
        }
        $bay = MasterBay::where('master_id', $master->id)->where('uuid', $bayUuid)->first();
        if (! $bay) {
            return;
        }

        $hasBookings = Booking::where('bay_id', $bay->id)
            ->whereNotNull('crm_uuid')
            ->exists();

        if ($hasBookings) {
            $bay->update(['is_active' => false]);
        } else {
            $bay->delete();
        }
    }

    private function applySaveClient(Master $master, array $payload): void
    {
        $uuid = $payload['id'] ?? null;
        if (! $uuid) {
            return;
        }

        CrmGarageClient::updateOrCreate(
            ['uuid' => $uuid],
            [
                'master_id' => $master->id,
                'name' => $payload['name'] ?? '',
                'phone' => $payload['phone'] ?? null,
                'app' => $master->app,
            ]
        );
    }

    private function applyDeleteClient(Master $master, array $payload): void
    {
        $clientUuid = $payload['clientId'] ?? null;
        if (! $clientUuid) {
            return;
        }
        CrmGarageClient::where('master_id', $master->id)->where('uuid', $clientUuid)->delete();
        CrmGarageVehicle::where('master_id', $master->id)->where('garage_client_uuid', $clientUuid)->delete();
    }

    private function applySaveVehicle(Master $master, array $payload): void
    {
        $uuid = $payload['id'] ?? null;
        if (! $uuid) {
            return;
        }

        CrmGarageVehicle::updateOrCreate(
            ['uuid' => $uuid],
            [
                'master_id' => $master->id,
                'garage_client_uuid' => $payload['clientId'] ?? null,
                'model_name' => $payload['modelName'] ?? '',
                'plate_number' => $payload['plateNumber'] ?? '',
                'app' => $master->app,
            ]
        );
    }

    private function applyDeleteVehicle(Master $master, array $payload): void
    {
        $vehicleUuid = $payload['vehicleId'] ?? null;
        if (! $vehicleUuid) {
            return;
        }
        CrmGarageVehicle::where('master_id', $master->id)->where('uuid', $vehicleUuid)->delete();
    }

    private function applySaveService(Master $master, array $payload): void
    {
        $uuid = $payload['id'] ?? null;
        if (! $uuid) {
            return;
        }

        MasterServiceCatalogItem::updateOrCreate(
            ['uuid' => $uuid],
            [
                'master_id' => $master->id,
                'name_uk' => $payload['nameUk'] ?? '',
                'name_en' => $payload['nameEn'] ?? '',
                'duration_minutes' => (int) ($payload['durationMinutes'] ?? 60),
                'price_uah' => (int) ($payload['priceUah'] ?? 0),
                'display_order' => (int) ($payload['displayOrder'] ?? 0),
                'app' => $master->app,
            ]
        );
    }

    private function applyDeleteService(Master $master, array $payload): void
    {
        $serviceUuid = $payload['serviceId'] ?? null;
        if (! $serviceUuid) {
            return;
        }
        MasterServiceCatalogItem::where('master_id', $master->id)->where('uuid', $serviceUuid)->delete();
    }

    private function applyOpenThread(Master $master, array $payload): void
    {
        $threadUuid = $payload['threadId'] ?? null;
        if (! $threadUuid) {
            return;
        }
        CrmChatThread::where('master_id', $master->id)
            ->where('uuid', $threadUuid)
            ->update(['unread_count' => 0]);
    }

    private function applySendMessage(Master $master, array $payload): void
    {
        $threadUuid = $payload['threadId'] ?? null;
        $messageData = $payload['message'] ?? null;
        if (! $threadUuid || ! $messageData) {
            return;
        }

        $thread = CrmChatThread::where('master_id', $master->id)->where('uuid', $threadUuid)->first();
        if (! $thread) {
            return;
        }

        $messageUuid = $messageData['id'] ?? null;
        if (! $messageUuid || CrmMessage::where('uuid', $messageUuid)->exists()) {
            return;
        }

        $createdAt = Carbon::parse($messageData['createdAt'] ?? now());

        CrmMessage::create([
            'uuid' => $messageUuid,
            'thread_uuid' => $threadUuid,
            'direction' => $messageData['direction'] ?? 'outgoing',
            'kind' => $messageData['kind'] ?? 'text',
            'body' => $messageData['body'] ?? '',
            'message_created_at' => $createdAt,
        ]);

        $thread->update([
            'last_message_preview' => $messageData['body'] ?? '',
            'thread_updated_at' => $createdAt,
            'unread_count' => 0,
        ]);
    }

    private function findBayByUuid(Master $master, ?string $uuid): ?MasterBay
    {
        if (! $uuid) {
            return null;
        }
        return MasterBay::where('master_id', $master->id)->where('uuid', $uuid)->first();
    }

    private function bookingToAppointment(Booking $booking, $bayMap): array
    {
        $bay = $booking->bay_id ? $bayMap->get($booking->bay_id) : null;
        $bayId = $bay ? ($bay->uuid ?? (string) $bay->id) : '';

        return [
            'id' => $booking->crm_uuid ?? (string) $booking->id,
            'bayId' => $bayId,
            'clientId' => $booking->crm_garage_client_uuid,
            'vehicleId' => $booking->crm_vehicle_uuid,
            'serviceCatalogId' => $booking->crm_service_catalog_uuid,
            'kind' => $booking->crm_kind ?? 'work',
            'startsAt' => $booking->start_time->toIso8601String(),
            'endsAt' => $booking->end_time->toIso8601String(),
            'customerName' => $booking->customer_name ?? '',
            'customerPhone' => $booking->customer_phone ?? '',
            'carModel' => $booking->car_model ?? '',
            'plateNumber' => $booking->plate_number ?? '',
            'serviceName' => $booking->service_name ?? '',
            'priceUah' => $booking->total_amount !== null ? (int) $booking->total_amount : null,
            'paymentStatus' => $booking->financial_status ?? 'pending',
            'paymentMethod' => $booking->crm_payment_method ?? 'none',
            'paidAmountUah' => (int) ($booking->paid_amount ?? 0),
            'notes' => $booking->note ?? '',
            'hasPhotoRequest' => (bool) $booking->has_photo_request,
        ];
    }

    private function buildGarageSettings(Master $master): array
    {
        $workingHours = '';
        if (is_array($master->working_hours) && ! empty($master->working_hours)) {
            $first = reset($master->working_hours);
            if (isset($first['from'], $first['to'])) {
                $workingHours = $first['from'] . '–' . $first['to'];
            }
        }

        $plan = $master->is_premium ? 'Premium' : 'Free';

        return [
            'garageName' => $master->name ?? '',
            'garagePhone' => $master->contact_phone ?? '',
            'address' => $master->address ?? '',
            'teamSize' => MasterBay::where('master_id', $master->id)->where('is_active', true)->count(),
            'workingHours' => $workingHours,
            'subscriptionPlan' => $plan,
        ];
    }
}
