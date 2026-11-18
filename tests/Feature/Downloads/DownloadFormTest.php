<?php

declare(strict_types=1);

use App\Livewire\DownloadForm;
use Database\Seeders\Support\Downloads;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use LynxCms\Core\Pages\Page;
use LynxCms\Forms\Models\Submission;

/*
 * The form beside a download: the visitor picks one of the languages the document exists in
 * (their own when it has it), leaves their details, and gets a link to that file at once.
 */

beforeEach(function (): void {
    spanish();
    RateLimiter::clear('lynx-forms');
    $this->everywhere = Downloads::create('Cloud outlook', 'report', ['en', 'pt', 'es']);
    $this->english = Downloads::create('Zero trust guide', 'ebook', ['en']);
});

function downloadForm(Page $page, string $language = 'en'): Testable
{
    return lynx()->language($language, fn (): Testable => Livewire::test(DownloadForm::class, ['form' => 'download', 'pageId' => $page->id]));
}

function fillDownload(Testable $form): Testable
{
    return $form->set('data.first_name', 'Ana')->set('data.last_name', 'Lima')->set('data.email', 'ana@example.com')
        ->set('data.job_title', 'manager');
}

it('offers the document\'s languages, starting with the visitor\'s', function (): void {
    downloadForm($this->everywhere, 'pt')->assertSet('data.document_language', 'pt')
        ->assertSeeHtml('value="en"')->assertSeeHtml('value="es"');
});

it('lets a visitor pick another language than the site\'s', function (): void {
    $form = fillDownload(downloadForm($this->everywhere, 'pt'))->set('data.document_language', 'en');
    $this->travel(5)->seconds();

    $form->call('submit')->assertSet('isSent', true);

    expect($form->get('downloadUrl'))->toContain('/lynx-media/signed/'.$this->everywhere->id.'/')
        ->and(basename(parse_url($form->get('downloadUrl'), PHP_URL_PATH)))->toStartWith('cloud-outlook-en');
});

it('hides the language choice when the document has one language', function (): void {
    downloadForm($this->english, 'pt')->assertSet('data.document_language', 'en')
        ->assertDontSeeHtml('wire:model.live="data.document_language"')->assertSee('Disponível em inglês');
});

it('refuses a language the document does not have', function (): void {
    $form = fillDownload(downloadForm($this->english))->set('data.document_language', 'pt');
    $this->travel(5)->seconds();

    $form->call('submit')->assertHasErrors('data.document_language')->assertSet('isSent', false);
    expect(Submission::query()->where('form', 'download')->count())->toBe(0);
});

it('stores the lead with its page and language, and hands back a link that downloads the file', function (): void {
    $form = fillDownload(downloadForm($this->everywhere, 'pt'));
    $this->travel(5)->seconds();

    $form->call('submit')->assertSet('isSent', true)->assertDispatched('download-ready');

    $submission = Submission::query()->where('form', 'download')->sole();
    expect($submission->page_id)->toBe($this->everywhere->id)
        ->and($submission->data['document_language'])->toBe('pt');

    $response = $this->get($form->get('downloadUrl'))->assertOk();
    expect($response->streamedContent())->toBe(Downloads::contents('Cloud outlook', 'pt'));
});

it('refuses the link once it expires', function (): void {
    $form = fillDownload(downloadForm($this->everywhere));
    $this->travel(5)->seconds();
    $form->call('submit');

    $this->travel(config('downloads.page_link_hours') + 1)->hours();

    $this->get($form->get('downloadUrl'))->assertForbidden();
});

it('says so instead of showing the form when the document has no file', function (): void {
    downloadForm(Downloads::create('Empty', 'report', []))->assertSee('not available')->assertDontSee('First name');
});

it('asks for the job title in words when the visitor chooses Other', function (): void {
    downloadForm($this->everywhere)->assertDontSee('Your job title')
        ->set('data.job_title', 'other')->assertSee('Your job title')
        ->set('data.job_title', 'manager')->assertDontSee('Your job title');
});

it('needs the job title in words once Other is chosen', function (): void {
    $form = fillDownload(downloadForm($this->everywhere))->set('data.job_title', 'other');
    $this->travel(5)->seconds();

    $form->call('submit')->assertHasErrors('data.job_title_other')->assertSet('isSent', false);

    $form->set('data.job_title_other', 'Chief dreamer')->call('submit')->assertSet('isSent', true);
    expect(Submission::query()->where('form', 'download')->sole()->data['job_title_other'])->toBe('Chief dreamer');
});
