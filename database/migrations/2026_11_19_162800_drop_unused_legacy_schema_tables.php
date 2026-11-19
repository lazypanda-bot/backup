<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('property_match_scores');
        Schema::dropIfExists('lifestyle_result_answers');
        Schema::dropIfExists('lifestyle_results');
        Schema::dropIfExists('lifestyle_options');
        Schema::dropIfExists('lifestyle_questions');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('listing_photos');
    }

    public function down(): void
    {
        // These placeholder tables were never used by application code and are not restored.
    }
};
