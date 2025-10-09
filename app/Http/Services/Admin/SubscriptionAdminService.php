<?php

namespace App\Http\Services\Admin;

use App\Http\Services\SubscriptionService;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SubscriptionAdminService
{
    public function __construct(private readonly SubscriptionService $subscriptionService)
    {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $q = Subscription::query()->with('user');

        if (! empty($filters['platform'])) {
            $q->where('platform', $filters['platform']);
        }
        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (! empty($filters['user_id'])) {
            $q->where('user_id', (int) $filters['user_id']);
        }
        if (! empty($filters['phone'])) {
            $q->whereHas('user', fn (Builder $b) => $b->where('phone', 'like', '%'.$filters['phone'].'%'));
        }
        if (! empty($filters['product_id'])) {
            $q->where('product_id', 'like', '%'.$filters['product_id'].'%');
        }
        if (! empty($filters['expires_from'])) {
            $q->where('expires_at', '>=', Carbon::parse($filters['expires_from']));
        }
        if (! empty($filters['expires_to'])) {
            $q->where('expires_at', '<=', Carbon::parse($filters['expires_to']));
        }

        $sort = $filters['sort'] ?? 'created_at';
        $dir = strtolower($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $q->orderBy($sort, $dir);

        $perPage = (int) ($filters['per_page'] ?? 20);
        return $q->paginate($perPage);
    }

    public function get(int $id): Subscription
    {
        return Subscription::with('user')->findOrFail($id);
    }

    public function create(array $data): Subscription
    {
        return Subscription::create($data);
    }

    public function update(int $id, array $data): Subscription
    {
        $sub = Subscription::findOrFail($id);
        $sub->fill($data);
        $sub->save();
        return $sub->refresh();
    }

    public function destroy(int $id): void
    {
        Subscription::whereKey($id)->delete();
    }

    public function bulkDelete(array $ids): int
    {
        return Subscription::whereIn('id', $ids)->delete();
    }

    public function bulkStatus(array $ids, string $status): int
    {
        return Subscription::whereIn('id', $ids)->update(['status' => $status]);
    }

    public function verify(array $data): array
    {
        $status = $this->subscriptionService->verifyAndStore(
            $data['user_id'],
            $data['platform'],
            $data['receipt_token'],
            $data['product_id'] ?? null
        );

        return [
            'active' => (bool) $status->active,
            'platform' => $status->platform,
            'product_id' => $status->product_id,
            'expires_at' => $status->expires_at?->toIso8601String(),
        ];
    }

    public function exportCsv(array $filters): string
    {
        $paginator = $this->list($filters);
        $rows = [];
        $rows[] = ['id', 'user_id', 'user_phone', 'platform', 'product_id', 'external_id', 'status', 'expires_at', 'last_verified_at', 'created_at'];
        foreach ($paginator->items() as $s) {
            $rows[] = [
                $s->id,
                $s->user_id,
                $s->user?->phone,
                $s->platform,
                $s->product_id,
                $s->external_id,
                $s->status,
                optional($s->expires_at)->toDateTimeString(),
                optional($s->last_verified_at)->toDateTimeString(),
                optional($s->created_at)->toDateTimeString(),
            ];
        }

        $fh = fopen('php://temp', 'r+');
        foreach ($rows as $r) {
            fputcsv($fh, $r);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }
}
