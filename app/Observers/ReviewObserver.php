<?php

namespace App\Observers;

use App\Models\Master;
use App\Models\Review;

class ReviewObserver
{
    public function created(Review $review): void
    {
        $this->recalculate($review);
    }

    public function updated(Review $review): void
    {
        $this->recalculate($review);
    }

    public function deleted(Review $review): void
    {
        $this->recalculate($review);
    }

    public function restored(Review $review): void
    {
        $this->recalculate($review);
    }

    private function recalculate(Review $review): void
    {
        $master = Master::withoutGlobalScopes()->find($review->master_id);

        if (! $master) {
            return;
        }

        $master->update([
            'rating' => (float) round(
                $master->reviews()->whereNull('parent_id')->avg('rating') ?? 0,
                1
            ),
        ]);
    }
}
