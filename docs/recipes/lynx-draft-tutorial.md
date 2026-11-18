# Drafting a schema on a fresh Lynx install

This tutorial builds the templates and fields of a small events site from one YAML file, with `php artisan lynx:draft`.

> [!NOTE]
> `lynx:draft` needs Lynx 0.1.42 or later. This tutorial follows 0.1.45, where text, textarea and URL fields are translatable unless given `translatable:false`. On 0.1.42 to 0.1.44 they have one value for every language unless marked `translatable`, so add that word to each of them.

## 1. Install Lynx

```bash
laravel new events-site && cd events-site
composer require lynxcms/core lynxcms/admin
php artisan migrate
php artisan lynx:install
```

`lynx:install` creates the `home` template, the `title` field, the home page and an admin user. Remove Laravel's `/` welcome route from `routes/web.php` so Lynx serves the home page.

## 2. Export the schema first

```bash
php artisan lynx:schema:export
```

This creates `lynx/schema`. A draft build only updates schema files when that folder already exists, so export first and every build is recorded in Git.

Optionally run `php artisan lynx:phpstan` and `php artisan lynx:ide-helper` now. A build only refreshes type files that already exist.

## 3. Turn on editor checking

```bash
php artisan lynx:draft --json-schema
```

This writes `lynx/draft.schema.json`, next to the draft. With the YAML extension in VS Code, or in PhpStorm, the editor then checks drafts in that folder as you type: it underlines unknown types and keys and suggests types, settings and template keys. A draft needs this first line for it, which `--stub` writes for you:

```yaml
# yaml-language-server: $schema=./draft.schema.json
```

The short form (`decimal:2 min:0`) is one string to the editor, so only its type is checked there; `--dry-run` checks the rest. Run `--json-schema` again after installing a module, so its field types are included.

## 4. Write the draft

```bash
php artisan lynx:draft --stub
```

This writes `lynx/draft.yaml`. Edit it, for example:

```yaml
# yaml-language-server: $schema=./draft.schema.json
fields:
  intro: textarea
  body: textarea:html
  starts_on: date
  venue: text:120
  ticket_price: decimal:2 min:0 label:"Ticket price (EUR)"
  seats: integer min:1
  sold_out: toggle
  topics: choice:Design,Development,"Product management" multiple
  speaker_name: text translatable:false
  speakers:
    type: repeater
    fields: [speaker_name*, intro]
    max: 6

templates:
  events:
    singleton: true
    children: [event]
    fields: [title*, intro]
  event:
    parents: [events]
    fields:
      - title*
      - intro
      - starts_on*
      - section: Practical details
        fields: [venue, ticket_price, seats, sold_out, topics]
      - section: Speakers
        tab: people
        fields: [speakers]
    tabs: { people: People }
  site-settings:
    url: false
    singleton: true
    fields: [contact_email: email]
```

How to read it:

- A field is `type[:argument]`, then flags and `key:value` settings. The argument fills the type's main setting: `text` (maximum length), `textarea` (format), `decimal` (places), `choice` (options), `page` and `page_table` (templates).
- Pick the type that matches the data: `toggle` for yes or no, `integer` for whole numbers such as a year, `decimal` for prices. They store and filter as what they are (`sold_out=0`, `year>=2020`), and a field's type can't change later. Field Types in the docs lists them all.
- Text, textarea and URL fields get a value per language, ready for a second language even if the site has one today. `translatable:false` makes a shared field instead: one value, the same in every language, as for a product code. A shared field can be made translatable later (Lynx 0.1.46 or later), but not the other way. A word on its own, such as `multiple`, turns a setting on. `label:"..."` sets the label; without one, `ticket_price` is labelled "Ticket price".
- Repeaters, matrices and labels in several languages use the long form, a map of `type`, `label`, `translatable` (`false` for one value in every language) and settings. Choice options are translated there too, from Lynx 0.1.46: `options: {Design: {pt: Design}, Development: {pt: Desenvolvimento}}`.
- Every field a template or repeater lists must be defined in the draft or already exist in the site. `title` already exists after install.
- In a template, `title*` makes the field required there, and `contact_email: email` defines a field in place.
- `url: false` makes a template without a URL (settings, reusable content), so it gets no view. `singleton: true` allows one page. `children`, `parents` and `allowed` set where pages may go.

## 5. Preview

```bash
php artisan lynx:draft --dry-run
```

The plan lists the fields and templates to create or update, the views to write, and the schema and type files to refresh. Nothing changes.

Every check runs here too. A mistyped type gives an error such as `there is no field type [decmal]. Did you mean [decimal]?`

## 6. Build

```bash
php artisan lynx:draft
```

In a terminal it shows the plan and asks "Build this draft?". Pass `--no-interaction` in scripts. The build runs in one transaction, so it all happens or none of it does. Then it:

- writes `resources/views/templates/events.blade.php` and `event.blade.php` (not `site-settings`, which has no URL)
- writes the draft's fields and templates into `lynx/schema`
- refreshes the type files, if the site has them

Pass `--no-views` to skip the views.

## 7. Check the result

```bash
php artisan lynx:templates
git status
```

In the admin, open **Settings › Templates**. Create the Events page under Home, then an event under it: only **Event** is offered there, because of the family rules.

The `site-settings` template has no `title`, so its page can't take a URL name from one. When you create it, type a URL name such as `site-settings` in the New page form.

> [!NOTE]
> In 0.1.42, leaving that name empty fails with "Template [site-settings] has no field [title]" instead of asking for it. Later releases show "A name in the default language is required." under the input.

## 8. Change things

Edit the draft and build again. Building an unchanged draft changes nothing.

- Labels and settings update. A label or setting the draft leaves out stays as it is.
- A field's type, and whether it is translatable, can't change, in a draft or anywhere else. Make a new field instead.
- Existing views are kept. `--force-views` only replaces views of templates the draft creates in that run.

For a template the site already has, the draft's `fields:` list becomes the template's field list, in the draft's order. A draft that leaves out a field the template has is refused, and the error names the fields:

```
Template [home]: the draft leaves out sections, seo_title, ..., and building it would remove them from the template.
```

List them to keep them. To take them off the template on purpose, build with `--prune-fields`; the plan then shows `remove field seo_title from template home` for each one. The fields and their values stay in the site.

> [!TIP]
> Modules attach fields to templates too (the SEO module adds `seo_*` fields), so a template's list in the draft must include them. `php artisan lynx:draft --from-site` shows each template's full list to copy from.

### Adding fields to an existing template

To add fields to a template the site already has, such as `home`, list its current fields by name and put the new ones where you want them. First see what `home` holds:

```bash
php artisan lynx:draft lynx/site.yaml --from-site
```

On a fresh install, `home` holds only `title`. A draft that adds a hero text and the `intro` field from step 4:

```yaml
fields:
  hero_text: textarea

templates:
  home:
    fields: [title, hero_text, intro]
```

The dry run should show `create field hero_text` and `update template home`, and nothing removed. `lynx:install` writes no view for `home`, so the plan also shows `write view templates/home.blade.php`; a template that already has a view keeps it. Up to 0.1.43, drafts write views only for new templates: run `php artisan lynx:make:view home` instead.

A field you only name keeps the settings it has on that template, such as whether it is required, its label, tab or help text. That matters once modules add fields: with the SEO module, `home` also holds `seo_title`, `seo_description`, `seo_image`, `seo_noindex` and `seo_canonical`, each on an SEO tab with help text. List them by name and they keep all of it:

```yaml
templates:
  home:
    fields: [title, hero_text, intro, seo_title, seo_description, seo_image, seo_noindex, seo_canonical]
```

To change one setting, give it and leave the rest: `{ name: seo_image, label: Share image }`. To clear one, set it to `null`. The draft's order and sections decide where each field sits.

> [!NOTE]
> Keeping these settings for named fields comes after 0.1.42. In 0.1.42, a field listed by name only loses its settings on that template, so copy each entry in full from `--from-site` instead.

## 9. Advanced fields

A second draft adds a conference template with media, page tables, a repeater and a matrix (a page builder).

The `images` and `files` field types come with the media package, which step 1 didn't install. Install it first, then refresh the editor schema so it knows the new types:

```bash
composer require lynxcms/media
php artisan storage:link
php artisan lynx:draft --json-schema
```

`storage:link` makes uploaded files reachable from the web ([Images and Files](https://lynxcms.org/cms/docs/basics/images-and-files)). Without the package, the build stops with `there is no field type [images]`.

Put the draft in its own file, such as `lynx/draft-conference.yaml`, and build it with `php artisan lynx:draft lynx/draft-conference.yaml`. Fields the site already has, like `title` and `intro`, can be listed without being defined again.

```yaml
# yaml-language-server: $schema=./draft.schema.json
fields:
  # Media (lynxcms/media)
  cover: images max_files:1 min_width:1200
  handouts: files max_files:10 extensions:"pdf docx pptx"

  # Dates, references and page tables
  doors_open: date time
  featured_speakers: page:speaker multiple max:6 input:checkboxes
  sessions: page_table:session columns:title,session_starts remove:trash
  session_starts: date time

  # Fields the blocks and the agenda use
  block_heading: text:120
  block_text: textarea:html
  block_image: images max_files:1
  block_caption: text
  quote_text: textarea
  quote_author: text translatable:false
  video_url: url

  # A page builder: each block has a type, and that type's fields
  blocks:
    type: matrix
    label: Content blocks
    add_label: Add a block
    collapse: collapsed
    max: 30
    types:
      - slug: text
        label: Text
        item_label: "{block_heading}"
        fields: [block_heading, block_text*]
      - slug: image
        label: Image
        item_label: "{block_caption}"
        fields: [block_image*, block_caption]
      - slug: quote
        label: Quote
        item_label: "{quote_author}"
        fields: [quote_text*, quote_author]
      - slug: video
        label: Video
        fields: [video_url*, block_caption]

  # The same fields on every item, with settings per item field
  agenda:
    type: repeater
    item_label: "{block_heading}"
    add_label: Add a slot
    min: 1
    fields:
      - block_heading*
      - name: block_text
        label: Details
        width: 50

templates:
  speaker:
    fields: [title*, intro, cover]
  session:
    url: false
    fields: [title*, session_starts*]
  conference:
    fields:
      - title*
      - intro
      - doors_open*
      - name: cover
        label: Hero image
      - section: Content
        fields: [blocks, agenda]
      - section: People and files
        tab: extras
        fields: [featured_speakers, sessions, handouts]
    tabs: { extras: Extras }
  conference-news:
    fields:
      - title*
      - name: blocks
        allowed_types: [text, quote]
```

What each part does:

- **Media.** `max_files:1` makes a template read one image instead of a list. A value with spaces is quoted: `extensions:"pdf docx pptx"`.
- **Date and time.** `date time` turns on the `time` setting, so the field keeps a time as well.
- **Page references.** `page:speaker` limits the choice to speaker pages. `multiple` allows a list, `max:6` caps it, and `input:checkboxes` picks the editor control (`search`, `select`, `radios` or `checkboxes`).
- **Page tables.** `page_table:session` lists child pages made for this page in a table. `columns` picks what the table shows, and `remove:trash` sends a removed row's page to the trash instead of keeping it. The `session` template has `url: false`, since sessions only appear inside the conference.
- **Sections and tabs.** A section groups fields under a label. `tab: extras` moves it to the Extras tab, which `tabs` names. A section goes in the editor's main area unless you add `column: sidebar`, which puts it in the narrow column beside it; keep that for short fields such as a toggle or a date.
- **Repeater.** Every item has the same fields. An item field is a name, `name*` for a required one, or a map with settings for that use (`label`, `width`, `description`, `notes`). `item_label` is the item's header in the admin, with field names in braces.
- **Matrix.** Each block type has a `slug`, a `label` and its `fields`, written like a repeater's. The slug identifies the type for good, so renaming or reordering types never changes what a block is. A field can serve several types, as `block_caption` does.
- **Blocks per template.** A template entry with `allowed_types` offers only some block types there: news posts get text and quotes, not images or video.

Every field a repeater or block type names must be defined in the draft or already exist in the site, and the build stops before changing anything if one is missing:

```
Field [gallery] uses the field [photo_file], which neither the draft nor the site defines.
```

A repeater or matrix can't list another repeater or matrix, because schema sync creates item fields one level deep:

```
Field [outer]: [inner] holds item fields itself, and a repeater or matrix can't list another one.
```

### Rendering blocks

The starting view of `conference` renders the blocks with `@matrix`:

```blade
@matrix($page->blocks)
```

Each block renders the view of its type, which receives `$block`, `$page` and `$loop`. The build writes a starting view for each block type, at the path `@matrix` looks for: the plan lists `write view templates/fields/blocks/text.blade.php` and one line each for `image`, `quote` and `video`. The quote block's starts like this:

```blade
{{-- resources/views/templates/fields/blocks/quote.blade.php --}}
<div>
    {{-- Quote block of blocks ($block, $page, $loop): written by lynx:make:view, yours to change. --}}

    @if ($block->quote_text)
        <p>@field($block->quote_text)</p>
    @endif

    @if ($block->quote_author)
        <p>@field($block->quote_author)</p>
    @endif
</div>
```

Make it yours, for example:

```blade
<blockquote>
    @field($block->quote_text)
    <cite>{{ $block->quote_author }}</cite>
</blockquote>
```

Block views belong to the field, so `conference` and `conference-news` share them. To change one for a single template, add `templates/fields/blocks/conference/quote.blade.php`. A draft only writes block views that are missing and never replaces one. A block type added later gets its view from `php artisan lynx:make:view conference`, which fills in the missing ones and keeps the rest.

> [!NOTE]
> Writing block views comes after 0.1.42. In 0.1.42, write them by hand; with `APP_DEBUG` on, a block without a view leaves an HTML comment naming the file to create.

## 10. Commit

```bash
git add lynx/schema resources/views/templates
git commit -m "feat: events schema"
```

`lynx/schema` is the record: other machines run `php artisan lynx:schema:apply`. The draft is a starting point, so keep it or delete it.

## Extras

- **Pages.** After 0.1.43, a draft can also create pages, so the site has its tree to fill in. List them by URL path; the template defaults to the one named like the last segment:

  ```yaml
  pages:
    /events: Events
    /events/launch: { template: event, title: Launch party, starts_on: 2026-12-01 }
    /site-settings:
  ```

  They are created unpublished, with every other field empty. Required fields need a value (`starts_on` above), and a path the site already has is left as it is. `site-settings` has no `title` field, so its page takes none; the URL name comes from the path.
- `php artisan lynx:draft --from-site lynx/site.yaml` writes the site's own schema as a draft, in the short form where it fits. It is a quick way to read a site's schema, and building it back changes nothing.
- An agent connected through MCP can build a draft with the `schema.draft` tool. It needs the `schema.manage` permission, refuses to take fields off a template unless `prune_fields` is given, and changes only the database. Where schema editing is off (`LYNX_SCHEMA_EDITING=false`, typically production), the tool is refused like every schema change by an agent, so build drafts on your machine and deploy the schema files. Run `lynx:schema:export` and `lynx:make:view` afterwards for the files.
