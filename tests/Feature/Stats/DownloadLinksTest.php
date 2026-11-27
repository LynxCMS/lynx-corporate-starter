<?php

declare(strict_types=1);

use App\Downloads\Document;
use App\Stats\PageStats;
use Illuminate\Support\Facades\Artisan;

/*
 * Counted download links (App\Stats\DownloadLinks): one download the first time a real visitor
 * opens the link, whoever opens it again (a second click, an email's link scanner), then a redirect
 * to the file through Lynx's own signed address. Never throttled, never without a valid signature.
 */

function downloadLink(string $language = 'en', int $hours = 1): string
{
    $link = (new Document(aDownload()))->link($language, now()->addHours($hours));
    assert(is_string($link));

    return $link;
}

it('counts the first opening of a link and sends the visitor to the file', function (): void {
    $id = (int) aDownload()->id;

    test()->withHeader('User-Agent', CHROME)->get(downloadLink())
        ->assertRedirect()
        ->assertRedirectContains('/lynx-media/signed/'.$id.'/');

    expect(PageStats::connection()->table('stat_counts')->where(['page_id' => $id, 'event' => 'download', 'subject' => 'en'])->value('count'))->toBe(1);
});

it('counts a link once, however often it is opened', function (): void {
    $link = downloadLink();

    foreach (range(1, 3) as $time) {
        test()->withHeader('User-Agent', CHROME)->get($link)->assertRedirect();
    }

    expect(statTotal((int) aDownload()->id, PageStats::DOWNLOAD))->toBe(1);
});

it('sends a crawler to the file without counting it', function (): void {
    test()->withHeader('User-Agent', GOOGLEBOT)->get(downloadLink())->assertRedirect();

    expect(statTotal((int) aDownload()->id, PageStats::DOWNLOAD))->toBe(0);
});

it('refuses a link without a valid signature, and a file the page does not hold', function (): void {
    $link = downloadLink();

    test()->get(preg_replace('/signature=[^&]+/', 'signature=forged', $link) ?? '')->assertForbidden();
    test()->get(str_replace('/document/', '/image/', $link))->assertForbidden();
    test()->get(URL::temporarySignedRoute('stats.file', now()->addHour(), ['page' => aDownload()->id, 'field' => 'document', 'file' => 'missing.pdf']))->assertNotFound();
});

it('forgets links once they expire', function (): void {
    test()->withHeader('User-Agent', CHROME)->get(downloadLink(hours: 1));
    $this->travel(2)->hours();

    Artisan::call('stats:prune');

    expect(PageStats::connection()->table('stat_links')->count())->toBe(0);
});
