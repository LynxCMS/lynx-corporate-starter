<?php

declare(strict_types=1);

use App\Downloads\Document;
use Illuminate\Support\Facades\DB;

/*
 * Visitor counts keep no personal data: after a view, an event and a download, no table holds the
 * visitor's address or user agent, and neither the beacon nor the download link sets a cookie.
 */

it('keeps neither the address nor the user agent anywhere', function (): void {
    $address = '203.0.113.77';
    $id = (int) aDownload()->id;
    $headers = ['User-Agent' => CHROME, 'Sec-Fetch-Site' => 'same-origin'];
    test()->withServerVariables(['REMOTE_ADDR' => $address]);

    test()->withHeaders($headers)->post('/stats/hit', ['page' => $id])->assertNoContent();
    test()->withHeaders($headers)->post('/stats/hit', ['page' => (int) home()->id, 'event' => 'hero-cta'])->assertNoContent();
    $download = test()->withHeaders($headers)->get((string) (new Document(aDownload()))->link('en', now()->addHour()));

    expect($download->headers->getCookies())->toBe([]);

    foreach (DB::connection()->getSchemaBuilder()->getTableListing() as $table) {
        $dump = json_encode(DB::table(str_contains($table, '.') ? substr($table, strpos($table, '.') + 1) : $table)->get());

        expect($dump)->not->toContain($address)->not->toContain('Chrome/131');
    }
});
