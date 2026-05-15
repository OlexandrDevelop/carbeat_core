<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('crm_uuid', 36)->nullable()->unique()->after('id');
            $table->string('crm_garage_client_uuid', 36)->nullable()->after('client_id');
            $table->string('crm_vehicle_uuid', 36)->nullable()->after('crm_garage_client_uuid');
            $table->string('crm_service_catalog_uuid', 36)->nullable()->after('crm_vehicle_uuid');
            $table->string('crm_kind', 20)->nullable()->after('crm_service_catalog_uuid'); // work | next | request
            $table->boolean('has_photo_request')->default(false)->after('crm_kind');
            $table->string('service_name')->nullable()->after('has_photo_request');
            $table->string('crm_payment_method', 30)->nullable()->after('service_name'); // cash | card | qr | transfer | mixed | none
            $table->string('customer_name')->nullable()->after('crm_payment_method');
            $table->string('customer_phone', 50)->nullable()->after('customer_name');
            $table->string('car_model')->nullable()->after('customer_phone');
            $table->string('plate_number', 50)->nullable()->after('car_model');

            $table->index(['crm_garage_client_uuid'], 'bookings_crm_garage_client_idx');
            $table->index(['crm_vehicle_uuid'], 'bookings_crm_vehicle_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_crm_garage_client_idx');
            $table->dropIndex('bookings_crm_vehicle_idx');
            $table->dropUnique(['crm_uuid']);
            $table->dropColumn([
                'crm_uuid', 'crm_garage_client_uuid', 'crm_vehicle_uuid',
                'crm_service_catalog_uuid', 'crm_kind', 'has_photo_request',
                'service_name', 'crm_payment_method', 'customer_name',
                'customer_phone', 'car_model', 'plate_number',
            ]);
        });
    }
};
