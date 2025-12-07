<?php

namespace App\Http\Services\Master;

use App\Models\MasterTimeOff;
use App\Models\MasterWorkSchedule;

class MasterScheduleService
{
    /**
     * Create work schedule rule for master.
     *
     * @param  array<string, mixed>  $data
     */
    public function createWorkScheduleRule(int $masterId, array $data): MasterWorkSchedule
    {
        $data['master_id'] = $masterId;
        
        return MasterWorkSchedule::create($data);
    }

    /**
     * Delete work schedule rule.
     */
    public function deleteWorkScheduleRule(int $masterId, int $ruleId): void
    {
        MasterWorkSchedule::where('master_id', $masterId)
            ->where('id', $ruleId)
            ->delete();
    }

    /**
     * Create time off period for master.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTimeOff(int $masterId, array $data): MasterTimeOff
    {
        $data['master_id'] = $masterId;
        
        return MasterTimeOff::create($data);
    }

    /**
     * Delete time off period.
     */
    public function deleteTimeOff(int $masterId, int $offId): void
    {
        MasterTimeOff::where('master_id', $masterId)
            ->where('id', $offId)
            ->delete();
    }
}

