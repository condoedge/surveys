<?php

namespace Condoedge\Surveys\Models\PollTypes;

use App\Models\Surveys\Poll;
use Condoedge\Surveys\Models\PollFileKindEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;
use Kompo\Core\FileHandler;
use Kompo\Core\IconGenerator;

/** One file sent with the answer (a logo, a proof, a certificate), kept where only the organization can open it. */
class PollTypeFile extends BasePollType
{
    public const DEFAULT_MAX_MB = 10;
    public const MAX_MB = 25;

    // These files are often personal: the private disk, whatever the kind, images included
    public const DISK = 'local';

    /** The Gate ability each app defines: who can open the files sent to a question, given that question. */
    public const VIEW_FILES_ABILITY = 'viewSurveyAnswerFiles';

    /** Why the file just sent was refused: said under the field, where Kompo puts a field's errors. */
    protected ?string $refusal = null;

    /* OPTIONS CHOICES */
    public function setDefaultOptionsForPollType($poll)
    {
        $poll->max_file_mb = $poll->max_file_mb ?: static::DEFAULT_MAX_MB;
        $poll->file_kinds = $poll->file_kinds ?: [PollFileKindEnum::IMAGE->value, PollFileKindEnum::PDF->value];
    }

	/* DISPLAY ELEMENTS */
	protected function mainInputEl($poll)
    {
        $kinds = static::kinds($poll);

        // A phone offers its camera next to its files as soon as images are accepted
        return _File()
            ->placeholder($kinds->contains(PollFileKindEnum::IMAGE) ? 'surveys-file-photo-or-file' : 'surveys-file-choose')
            ->attr(['accept' => $kinds->flatMap->extensions()->map(fn ($extension) => '.'.$extension)->implode(',')])
            ->comment(($this->refusal ? '<div class="vlFieldErrors !text-base -ml-1">'.e($this->refusal).'</div>' : '')
                .__('surveys-file-hint', ['kinds' => static::kindsLabel($poll), 'mb' => static::maxMb($poll)]).'<br>'.__('surveys-file-private'))
            ->class($this->refusal ? 'vlHasErrors' : '')
            // The × that removes the file is a 44 px touch target, the field keeps its height
            ->class('[&_div.vlSingle]:py-[3px] [&_div.vlSingle]:pl-0 [&_.vlCustomLabel]:min-h-11 [&_.icon-times]:self-stretch [&_.icon-times]:flex [&_.icon-times]:items-center [&_.icon-times]:px-3');
    }

    /** Sent alone, never with the form's other files, and answered by the question shown again (saveInline). */
    protected function savesWhenAnswered($inputEl, $poll)
    {
        // With arrows a required file opens the rest of the path, which is shown again instead
        if ($poll->getMainPoll()->survey?->managesEligibility()) {
            return parent::savesWhenAnswered($inputEl, $poll);
        }

        return $inputEl->onChange(fn ($e) => $e->selfPost('saveInlinePollAnswer', ['poll_id' => $poll->id]));
    }

    /** The file already sent, named with its size. */
    protected function fieldValue($answerPoll)
    {
        $file = $answerPoll?->getAnswerFile();

        return $file ? ['name' => $file['name'].' · '.static::sizeLabel($file['size'])] + $file : null;
    }

	/* EDIT ELEMENTS */
    protected function getQuestionOptionsEls($poll)
    {
        return _Rows(
            _MultiSelect('surveys-file-kinds')->name('file_kinds')->options(PollFileKindEnum::optionsWithLabels())->class('mb-2'),
            _InputNumber('surveys-file-max-mb')->name('max_file_mb')->min(1)->max(static::MAX_MB)
                ->comment(__('surveys-file-max-mb-hint', ['max' => static::MAX_MB]))->class('mb-2'),
            parent::getQuestionOptionsEls($poll),
        );
    }

    /* ACTIONS */
    /**
     * The question is shown again: the file kept, with its size, or why the new one was refused.
     * Its fresh field also drops the file Kompo would otherwise send again with the next save.
     */
    public function saveInline($poll, $answer)
    {
        $shown = clone $this; // the question type is shared by every read of this question, the refusal is this response's

        try {
            $answer->saveAnswerToSinglePoll($poll->id);
        } catch (ValidationException $e) {
            $shown->refusal = collect($e->errors())->flatten()->first();
        }

        $question = response()->panel($shown->questionEls($poll, Poll::DISPLAY_MODE_CONDITION_PASSED, $answer), 'poll-wrapper-'.$poll->id);

        return $shown->refusal ? $question : response()->kompoMulti([
            $question,
            response()->kompoAlert('', options: ['alertClass' => 'vlAlertSuccess', 'icon' => IconGenerator::toHtml('icon-check'), 'message' => __('survey.answer-saved')]),
        ]);
    }

    public function validatePollAnswer($poll, $value, $answer)
    {
        // A file removed while answering is deleted at once, even when required: « Continue » says it is missing
        if ($answer?->savingInline && !$value) {
            return;
        }

        parent::validatePollAnswer($poll, $value, $answer);
    }

    protected function validateSpecificToType($poll, $value, $answer)
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        $mainPoll = $poll->getMainPoll();

        if ($value->getError() == UPLOAD_ERR_INI_SIZE || $value->getSize() > static::maxMb($mainPoll) * 1024 * 1024) {
            throwValidationError($poll->getPollInputName(), __('surveys-file-too-big', ['mb' => static::maxMb($mainPoll)]));
        }

        if (!$value->isValid()) {
            throwValidationError($poll->getPollInputName(), 'survey-issue-refresh-try-again');
        }

        $kind = PollFileKindEnum::ofFile($value);

        if (!$kind || !static::kinds($mainPoll)->contains($kind)) {
            throwValidationError($poll->getPollInputName(), __('surveys-file-wrong-kind', ['kinds' => static::kindsLabel($mainPoll)]));
        }
    }

    /** A new upload replaces the file, an empty value removes it, anything else keeps the one already sent. */
    public function answerTextToSave($answerPoll, $value)
    {
        if ($value && !$value instanceof UploadedFile) {
            return $answerPoll->answer_text;
        }

        $answerPoll->deleteAnswerFile();

        if (!$value) {
            return null;
        }

        return json_encode(static::fileHandler()->fileToDB($value, $answerPoll, 'answer_text', true) + ['disk' => static::DISK]);
    }

    public static function fileHandler()
    {
        $fileHandler = new FileHandler();
        $fileHandler->setDisk(static::DISK);
        $fileHandler->visibility = 'private';

        return $fileHandler;
    }

    /** The question's limit, or the server's upload limit when it is lower, since PHP drops a bigger file first. */
    protected static function maxMb($poll)
    {
        return min($poll->max_file_mb ?: static::DEFAULT_MAX_MB, static::MAX_MB, intdiv(UploadedFile::getMaxFilesize(), 1024 * 1024));
    }

    /** None chosen accepts every kind. */
    protected static function kinds($poll)
    {
        return collect($poll->file_kinds ?: array_column(PollFileKindEnum::cases(), 'value'))
            ->map(fn ($kind) => PollFileKindEnum::from((int) $kind));
    }

    protected static function kindsLabel($poll)
    {
        return static::kinds($poll)->map->label()->implode(', ');
    }

    /** In the page's language: 604 o and 4,3 Ko in French. */
    protected static function sizeLabel($bytes)
    {
        $units = explode(' ', __('surveys-file-size-units'));

        for ($i = 0; $bytes / 1024 > 0.9 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return Number::format($bytes, maxPrecision: 1, locale: app()->getLocale()).' '.$units[$i];
    }
}
