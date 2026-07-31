<?php

namespace App\Http\Services\Master;

class MasterFilterService
{
    public static function applyFilters(array $filters, string &$query, array &$queryParams): void
    {
        $whereClauses = [];

        // Filter by app/brand (CRITICAL for brand isolation)
        if (! empty($filters['app'])) {
            $whereClauses[] = 'masters.app = :app';
            $queryParams['app'] = $filters['app'];
        }

        if (! empty($filters['name'])) {
            // Single search box: match either the master's own name or the name
            // (translated) of any service the master offers, main or extra.
            $whereClauses[] = '(
                masters.name LIKE :name
                OR EXISTS (
                    SELECT 1 FROM service_translations st
                    WHERE st.name LIKE :name_service
                    AND (
                        st.service_id = masters.service_id
                        OR EXISTS (
                            SELECT 1 FROM master_services ms_name
                            WHERE ms_name.master_id = masters.id AND ms_name.service_id = st.service_id
                        )
                    )
                )
            )';
            $queryParams['name'] = '%'.$filters['name'].'%';
            $queryParams['name_service'] = '%'.$filters['name'].'%';
        }

        if (! empty($filters['service_id'])) {
            // Match masters that have this service either as main (masters.service_id)
            // or via the pivot table master_services
            $whereClauses[] = '(
                masters.service_id = :service_id
                OR EXISTS (
                    SELECT 1 FROM master_services ms
                    WHERE ms.master_id = masters.id AND ms.service_id = :service_id_pivot
                )
            )';
            $queryParams['service_id'] = $filters['service_id'];
            $queryParams['service_id_pivot'] = $filters['service_id'];
        }

        if (! empty($filters['rating'])) {
            $whereClauses[] = 'COALESCE(reviews_summary.rating, 0) >= :rating';
            $queryParams['rating'] = $filters['rating'];
        }

        if (! empty($whereClauses)) {
            $query .= ' AND '.implode(' AND ', $whereClauses);
        }
    }
}
