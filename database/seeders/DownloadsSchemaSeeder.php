<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Livewire\DownloadForm;
use Database\Seeders\Support\Pages;
use Illuminate\Database\Seeder;
use LynxCms\Core\Pages\Page;
use LynxCms\Forms\Actions\CreateForm;
use LynxCms\Forms\FormSchema;

/**
 * The downloads section: reports and ebooks whose PDFs exist in some of the site's languages, each
 * behind the "download" form. Editors change the form's questions and options in the admin.
 */
final class DownloadsSchemaSeeder extends Seeder
{
    /** Kinds of download, shown as the eyebrow of their cards and forms. */
    public const array KINDS = [
        'report' => ['en' => 'Report', 'pt' => 'Relatório', 'es' => 'Informe'],
        'ebook' => ['en' => 'Ebook', 'pt' => 'Ebook', 'es' => 'Ebook'],
    ];

    /** The languages a document may be in, named in each language. */
    private const array LANGUAGES = [
        'en' => ['en' => 'English', 'pt' => 'Inglês', 'es' => 'Inglés'],
        'pt' => ['en' => 'Portuguese', 'pt' => 'Português', 'es' => 'Portugués'],
        'es' => ['en' => 'Spanish', 'pt' => 'Espanhol', 'es' => 'Español'],
    ];

    private const array JOB_TITLES = [
        'executive' => ['en' => 'Executive (C-level, VP)', 'pt' => 'Administração (C-level, VP)', 'es' => 'Dirección ejecutiva (C-level, VP)'],
        'director' => ['en' => 'Director', 'pt' => 'Diretor', 'es' => 'Director'],
        'manager' => ['en' => 'Manager', 'pt' => 'Gestor', 'es' => 'Responsable'],
        'specialist' => ['en' => 'Specialist or engineer', 'pt' => 'Especialista ou engenheiro', 'es' => 'Especialista o ingeniero'],
        'student' => ['en' => 'Student or educator', 'pt' => 'Estudante ou professor', 'es' => 'Estudiante o docente'],
        'other' => ['en' => 'Other', 'pt' => 'Outro', 'es' => 'Otro'],
    ];

    private const array COMPANY_SIZES = [
        '1-10' => ['en' => '1 to 10 people', 'pt' => '1 a 10 pessoas', 'es' => '1 a 10 personas'],
        '11-50' => ['en' => '11 to 50 people', 'pt' => '11 a 50 pessoas', 'es' => '11 a 50 personas'],
        '51-250' => ['en' => '51 to 250 people', 'pt' => '51 a 250 pessoas', 'es' => '51 a 250 personas'],
        '251-1000' => ['en' => '251 to 1,000 people', 'pt' => '251 a 1000 pessoas', 'es' => '251 a 1000 personas'],
        '1000-plus' => ['en' => 'More than 1,000 people', 'pt' => 'Mais de 1000 pessoas', 'es' => 'Más de 1000 personas'],
    ];

    public function run(): void
    {
        lynx()->fields->add('download_kind', 'choice', label: 'Kind', settings: ['input' => 'select', 'options' => self::options(self::KINDS)]);
        lynx()->fields->add('document', 'files', label: 'Document', settings: ['private' => true, 'extensions' => 'pdf']);
        lynx()->fields->add('privacy_page', 'page', label: 'Privacy notice', settings: ['multiple' => false]);

        $title = ['name' => 'title', 'required' => true];
        lynx()->templates->add('download', label: 'Download', fields: [
            $title, ['name' => 'download_kind', 'required' => true], ['name' => 'summary', 'searchable' => true],
            'image', ['name' => 'body', 'searchable' => true], 'document', 'published_on', 'topics',
        ]);
        lynx()->templates->add('downloads', label: 'Downloads', fields: [$title, 'summary', 'privacy_page'], singleton: true, allowedTemplates: ['download']);

        $this->form();
    }

    /**
     * @param  array<string, array<string, string>>  $labels  value => label per language
     * @return list<array{value: string, label: array<string, string>}>
     */
    public static function options(array $labels): array
    {
        return array_map(fn (string $value, array $label): array => ['value' => $value, 'label' => self::known($label)], array_keys($labels), $labels);
    }

    /**
     * Texts of the languages the site has: the live site adds Spanish in the admin.
     *
     * @param  array<string, string>  $texts  language code => text
     * @return array<string, string>
     */
    public static function known(array $texts): array
    {
        return array_filter($texts, fn (string $code): bool => lynx()->languages->find($code) !== null, ARRAY_FILTER_USE_KEY);
    }

    private function form(): void
    {
        $label = self::known(['en' => 'Download request', 'pt' => 'Pedido de download', 'es' => 'Solicitud de descarga']);
        $form = lynx()->run(CreateForm::class, ['name' => DownloadForm::FORM, 'label' => $label])->valueAs(Page::class);
        $form->{FormSchema::FIELDS} = [
            self::field('select', 'document_language', ['en' => 'Document language', 'pt' => 'Idioma do documento', 'es' => 'Idioma del documento'], true, options: self::LANGUAGES),
            self::field('text', 'first_name', ['en' => 'First name', 'pt' => 'Nome', 'es' => 'Nombre'], true, width: 50),
            self::field('text', 'last_name', ['en' => 'Last name', 'pt' => 'Apelido', 'es' => 'Apellidos'], true, width: 50),
            self::field('email', 'email', ['en' => 'Work email', 'pt' => 'Email profissional', 'es' => 'Correo electrónico profesional'], true),
            self::field('select', 'job_title', ['en' => 'Job title', 'pt' => 'Cargo', 'es' => 'Cargo'], true, options: self::JOB_TITLES),
            self::field('text', 'job_title_other', ['en' => 'Your job title', 'pt' => 'O seu cargo', 'es' => 'Tu cargo'], true, showIf: ['job_title', 'other']),
            self::field('text', 'company', ['en' => 'Company or organization', 'pt' => 'Empresa ou organização', 'es' => 'Empresa u organización'], false),
            self::field('select', 'company_size', ['en' => 'Company size', 'pt' => 'Dimensão da empresa', 'es' => 'Tamaño de la empresa'], false, options: self::COMPANY_SIZES),
            self::field('checkbox', 'newsletter', [
                'en' => 'Yes, send me news about resources, events and more from Arcturus by email.',
                'pt' => 'Sim, quero receber por email novidades sobre recursos, eventos e mais da Arcturus.',
                'es' => 'Sí, quiero recibir por correo novedades sobre recursos, eventos y más de Arcturus.',
            ], false),
        ];
        $form->{FormSchema::SUCCESS} = 'Thank you. Your download is starting, and we sent the link to your email too.';
        $form->setLanguageValue('pt', FormSchema::SUCCESS, 'Obrigado. O download vai começar, e enviámos também o link para o seu email.');
        $form->save();
        lynx()->pages->setStatus(Pages::id($form), remove: ['unpublished']);
    }

    /**
     * One question of the builder form.
     *
     * @param  array<string, string>  $label
     * @param  array<string, array<string, string>>  $options
     * @param  array{string, string}|null  $showIf  shown only when this field has this answer
     * @return array{type: string, values: array<string, mixed>}
     */
    private static function field(string $type, string $name, array $label, bool $isRequired, int $width = 100, array $options = [], ?array $showIf = null): array
    {
        return ['type' => $type, 'values' => array_filter([
            FormSchema::LABEL => self::known($label),
            FormSchema::NAME => $name,
            FormSchema::REQUIRED => $isRequired,
            FormSchema::WIDTH => $width === 100 ? null : $width,
            FormSchema::OPTIONS => $options === [] ? null : self::options($options),
            FormSchema::SHOW_IF => $showIf[0] ?? null,
            FormSchema::SHOW_IF_VALUES => $showIf[1] ?? null,
        ], fn (mixed $value): bool => $value !== null)];
    }
}
