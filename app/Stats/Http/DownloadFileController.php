<?php

declare(strict_types=1);

namespace App\Stats\Http;

use App\Stats\PageStats;
use App\Stats\RealVisitor;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LynxCms\Core\Pages\Page;
use LynxCms\Media\Values\MediaList;
use LynxCms\Media\Values\Pagefile;

/**
 * A counted download link (DownloadLinks): a valid signature, a published page holding the file,
 * then one download counted on the link's first use by a real visitor, whoever opens it after
 * (an email link scanner, a second click), and a redirect to the file through Lynx's own signed
 * address for a few minutes. Never throttled: a visitor always gets the file.
 */
final class DownloadFileController
{
    private const int FILE_LINK_MINUTES = 5;

    public function __invoke(Request $request, int $page, string $field, string $file, PageStats $stats): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        $found = lynx()->pages->findOne("id={$page}, include=hidden");
        $pagefile = $found instanceof Page ? $this->file($found, $field, $file) : null;
        abort_if($pagefile === null, 404);

        if (RealVisitor::is($request) && $this->isFirstUse($request)) {
            $stats->record($page, PageStats::DOWNLOAD, subject: (string) $request->query('subject', ''));
        }

        return redirect()->away($pagefile->temporaryUrl(now()->addMinutes(self::FILE_LINK_MINUTES)));
    }

    private function file(Page $page, string $field, string $name): ?Pagefile
    {
        if ($page->templateField($field) === null) {
            return null;
        }

        $value = $page->get($field);
        $files = $value instanceof MediaList ? $value->all() : [$value];

        foreach ($files as $candidate) {
            if ($candidate instanceof Pagefile && $candidate->name() === $name) {
                return $candidate;
            }
        }

        return null;
    }

    /** Records the link, until it expires; true the first time only. */
    private function isFirstUse(Request $request): bool
    {
        $expires = CarbonImmutable::createFromTimestamp((int) $request->query('expires', '0'));

        return PageStats::connection()->table('stat_links')->insertOrIgnore([
            'hash' => hash('sha256', (string) $request->query('signature', '')),
            'expires_at' => $expires,
        ]) === 1;
    }
}
