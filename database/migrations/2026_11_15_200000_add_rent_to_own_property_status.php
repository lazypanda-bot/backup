<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROPERTY_STATUSES = [
        'Available',
        'For Sale',
        'For Rent',
        'Rent to Own',
        'Reserved',
        'Sold',
        'Unavailable',
        'Pending',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('property_listings', function (Blueprint $table): void {
            $table->enum('property_status', self::PROPERTY_STATUSES)->default('Available')->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        if (DB::table('property_listings')->where('property_status', 'Rent to Own')->exists()) {
            throw new \RuntimeException('Cannot remove the Rent to Own status while listings still use it.');
        }

        Schema::table('property_listings', function (Blueprint $table): void {
            $table->enum('property_status', [
                'Available',
                'For Sale',
                'For Rent',
                'Reserved',
                'Sold',
                'Unavailable',
                'Pending',
            ])->default('Available')->change();
        });
    }
};
