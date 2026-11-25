<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Visitor counts (app/Stats): daily totals per page, event, subject and source, never a row per
 * visitor; and the download links already counted, until they expire.
 */
return new class extends Migration
{
    public function getConnection(): ?string
    {
        $connection = config('stats.connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('stat_counts', function (Blueprint $table): void {
            $table->unsignedBigInteger('page_id');
            $table->string('event', 32);
            $table->string('subject', 64)->default('');
            $table->string('source', 32)->default('site');
            $table->date('day');
            $table->unsignedInteger('count')->default(0);
            $table->primary(['page_id', 'event', 'subject', 'source', 'day']);
            $table->index(['event', 'day']);
        });

        Schema::connection($this->getConnection())->create('stat_links', function (Blueprint $table): void {
            $table->string('hash', 64)->primary();
            $table->timestamp('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('stat_links');
        Schema::connection($this->getConnection())->dropIfExists('stat_counts');
    }
};
