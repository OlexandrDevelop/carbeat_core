<?php

namespace App\Http\Services\Admin;

use App\Jobs\CreateMasterThumbnails;
use App\Models\Master;
use Illuminate\Support\Facades\DB;

class SystemMaintenanceService
{
    /**
     * Truncate selected domain tables with foreign key checks disabled.
     *
     * @return array{status:string,tables:array<string,int>}
     */
    public function truncateDomainTables(): array
    {
        $tables = [
            'clients',
            'cities',
            'master_galleries',
            'master_services',
            'masters',
            'reviews',
            'services',
        ];

        $deletedPerTable = [];

        // Count current rows to report how many were removed
        foreach ($tables as $table) {
            try {
                $deletedPerTable[$table] = (int) DB::table($table)->count();
            } catch (\Throwable $e) {
                $deletedPerTable[$table] = 0;
            }
        }

        // Disable FK checks if supported and truncate
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        } catch (\Throwable $e) {
            // Ignore for drivers that do not support this statement
        }

        foreach ($tables as $table) {
            try {
                DB::table($table)->truncate();
            } catch (\Throwable $e) {
                // Fallback to delete for drivers that do not support truncate
                try {
                    DB::table($table)->delete();
                } catch (\Throwable $e2) {
                    // ignore
                }
            }
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        } catch (\Throwable $e) {
            // Ignore for drivers that do not support this statement
        }

        return [
            'status' => 'ok',
            'tables' => $deletedPerTable,
        ];
    }

    /**
     * Reset thumbnail flags and dispatch background jobs to regenerate all thumbnails.
     *
     * @param bool $resetFlags When true, reset master thumbnail flags before queueing
     * @return array{status:string,total:int,queued_chunks:int,chunk_size:int}
     */
    public function regenerateAllThumbnails(bool $resetFlags = true): array
    {
        if ($resetFlags) {
            Master::query()
                ->whereNotNull('photo')
                ->update(['main_thumb_generated' => false, 'main_thumb_url' => null]);
        }

        $query = Master::query()
            ->whereNotNull('photo')
            ->where(function ($q) {
                $q->where('main_thumb_generated', false)->orWhereNull('main_thumb_generated');
            })
            ->orderBy('id');

        $total = (int) $query->count();
        $chunkSize = 500;
        $queuedChunks = 0;

        $query->chunk($chunkSize, function ($masters) use (&$queuedChunks) {
            $ids = $masters->pluck('id')->all();
            if (!empty($ids)) {
                CreateMasterThumbnails::dispatch($ids);
                $queuedChunks++;
            }
        });

        return [
            'status' => 'ok',
            'total' => $total,
            'queued_chunks' => $queuedChunks,
            'chunk_size' => $chunkSize,
        ];
    }
}


