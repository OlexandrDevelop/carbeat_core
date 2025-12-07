<?php

namespace App\Http\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountService
{
    /**
     * Delete user account with all related data.
     * Uses database transaction to ensure data consistency.
     *
     * @throws \Exception
     */
    public function deleteAccount(User $user): void
    {
        DB::transaction(function () use ($user) {
            try {
                // Revoke all refresh tokens before deletion
                $this->revokeRefreshTokens($user);

                // Delete master and its related data if present
                if ($user->master) {
                    $this->deleteMasterData($user->master);
                }

                // Finally delete user
                $user->delete();
            } catch (\Throwable $e) {
                Log::error('Failed to delete account', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Revoke all refresh tokens for user.
     */
    private function revokeRefreshTokens(User $user): void
    {
        try {
            $user->refreshTokens()->update(['revoked' => true]);
        } catch (\Throwable $e) {
            Log::warning('Failed to revoke refresh tokens', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Continue with deletion even if token revocation fails
        }
    }

    /**
     * Delete master and all related data.
     */
    private function deleteMasterData($master): void
    {
        try {
            // Delete dependent relations to avoid foreign key constraints
            $this->deleteMasterRelations($master);
            $master->delete();
        } catch (\Throwable $e) {
            Log::warning('Failed to delete master data', [
                'master_id' => $master->id,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw to rollback transaction
        }
    }

    /**
     * Delete all master relations.
     */
    private function deleteMasterRelations($master): void
    {
        $relations = [
            'gallery' => fn($m) => $m->gallery()->delete(),
            'reviews' => fn($m) => $m->reviews()->delete(),
            'bookings' => fn($m) => $m->bookings()->delete(),
        ];

        foreach ($relations as $relationName => $deleteCallback) {
            try {
                $deleteCallback($master);
            } catch (\Throwable $e) {
                Log::warning("Failed to delete master {$relationName}", [
                    'master_id' => $master->id,
                    'relation' => $relationName,
                    'error' => $e->getMessage(),
                ]);
                // Continue with other relations
            }
        }
    }
}

