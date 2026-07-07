<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateMasterRatings extends Command
{
    protected $signature = 'masters:recalculate-ratings';

    protected $description = 'Recalculate the denormalized rating column on masters from the reviews table';

    public function handle(): int
    {
        $affected = DB::update(<<<'SQL'
            UPDATE masters m
            LEFT JOIN (
                SELECT master_id, ROUND(AVG(rating), 1) AS avg_rating
                FROM reviews
                GROUP BY master_id
            ) r ON r.master_id = m.id
            SET m.rating = COALESCE(r.avg_rating, 0)
            WHERE m.rating IS NULL
               OR m.rating != COALESCE(r.avg_rating, 0)
        SQL);

        $this->info("Recalculated rating for {$affected} master(s).");

        return self::SUCCESS;
    }
}
