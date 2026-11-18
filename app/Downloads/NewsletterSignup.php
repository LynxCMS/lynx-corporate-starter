<?php

declare(strict_types=1);

namespace App\Downloads;

use App\Exceptions\NewsletterSignupFailed;
use Illuminate\Validation\ValidationException;
use LynxCms\Core\Support\Value;
use LynxCms\Forms\Actions\SubmitForm;
use LynxCms\Forms\Definition\Form;
use LynxCms\Forms\Models\Submission;

/**
 * Subscribes a visitor who ticked "send me news" on a download request to the newsletter form,
 * once per address. Editors may add questions to that form: each is answered from the request's
 * answer of the same name, and its tick boxes are ticked, since the visitor asked to subscribe.
 * A newsletter form those answers can't fill is reported, and the download is left alone.
 */
final class NewsletterSignup
{
    public const string FORM = 'newsletter';

    private const string TICK_BOX = 'checkbox';

    /** @param  array<string, mixed>  $answers  the download request's answers */
    public function subscribe(array $answers, string $language): void
    {
        $email = Value::string($answers['email'] ?? '');
        $form = lynx()->forms()->get(self::FORM);

        if ($email === '' || ! $form instanceof Form || $this->isSubscribed($email)) {
            return;
        }

        try {
            lynx()->run(SubmitForm::class, ['form' => self::FORM, 'data' => $this->answersFor($form, $answers), 'language' => $language]);
        } catch (ValidationException $e) {
            report(new NewsletterSignupFailed($e->errors(), $e));
        }
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    private function answersFor(Form $form, array $answers): array
    {
        $data = [];

        foreach ($form->fields() as $field) {
            if (array_key_exists($field->name, $answers)) {
                $data[$field->name] = $answers[$field->name];
            } elseif ($field->type === self::TICK_BOX) {
                $data[$field->name] = true;
            }
        }

        return $data;
    }

    private function isSubscribed(string $email): bool
    {
        return Submission::query()->where('form', self::FORM)->where('data->email', $email)->exists();
    }
}
