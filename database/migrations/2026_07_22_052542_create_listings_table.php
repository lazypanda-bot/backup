<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('posted_by');
            $table->boolean('is_open')->default(false);
            $table->enum('approval_status', ['approved', 'pending', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_duplicate_flagged')->default(false);
            $table->unsignedBigInteger('duplicate_of')->nullable();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 15, 2);
            $table->string('location');
            $table->enum('location_type', ['city', 'suburban', 'provincial']);
            $table->enum('property_type', ['condominium', 'house_and_lot', 'vacant_lot', 'commercial', 'farmland']);
            $table->string('developer')->nullable();
            $table->boolean('has_security')->default(false);
            $table->boolean('near_schools')->default(false);
            $table->decimal('lot_area', 10, 2)->nullable();
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->foreign('duplicate_of')->references('id')->on('listings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
