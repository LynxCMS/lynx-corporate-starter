<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** How often each page was opened from search results, with older visits counting less (App\Search\Popularity). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_popularity', function (Blueprint $table): void {
            $table->unsignedBigInteger('page_id')->primary();
            $table->double('score')->index();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_popularity');
    }
};
