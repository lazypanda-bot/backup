<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            if (Schema::hasColumn('property_listings', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }

            if (Schema::hasColumn('property_listings', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            if (! Schema::hasColumn('property_listings', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approval_status')
                    ->constrained('users', 'user_id')->nullOnDelete();
            }

            if (! Schema::hasColumn('property_listings', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }
};
