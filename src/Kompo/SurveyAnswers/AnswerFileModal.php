<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\AnswerPoll;
use Condoedge\Surveys\Models\PollTypes\PollTypeFile;
use Condoedge\Utils\Kompo\Files\DisplayFileModal;
use Condoedge\Utils\Models\Files\FileTypeEnum;
use Illuminate\Support\Facades\Gate;

/**
 * The file sent to a Document question, previewed in the same window as the files of utils.
 * Its contents come from our own route, which checks who is asking.
 */
class AnswerFileModal extends DisplayFileModal
{
    protected $answerPoll;
    protected $file;

    public function created()
    {
        $this->answerPoll = AnswerPoll::findOrFail($this->prop('answer_poll_id'));

        Gate::authorize(PollTypeFile::VIEW_FILES_ABILITY, $this->answerPoll->poll->getMainPoll());

        $this->file = $this->answerPoll->getAnswerFile() ?? abort(404);
        $this->fileTypeEnum = FileTypeEnum::fromMimeType($this->file['mime_type'] ?? '');

        if ($this->isPaged()) {
            $this->removeClass('max-w-xl');
        }
    }

    public function body()
    {
        return _Rows(
            _Html($this->file['name'])->class('font-medium mb-4 break-all'),
            $this->previewEl(),
        )->style('overflow-y: auto;')->class('px-8 py-6');
    }

    public function headerButtons()
    {
        return _LinkButton('files.download')->href($this->answerPoll->getAnswerFileUrl(download: true));
    }

    // A kind the browser cannot show (HEIC, Excel) is only downloaded
    protected function previewEl()
    {
        $url = $this->answerPoll->getAnswerFileUrl();

        return match ($this->fileTypeEnum) {
            FileTypeEnum::IMAGE => _Img($url)->class('w-full h-auto'),
            FileTypeEnum::PDF => _Html('<embed src="'.e($url).'" style="width: 100%; height: calc(95vh - 16rem);">'),
            FileTypeEnum::DOCUMENT => _DocxPreview($url),
            default => _Html('utils.no-preview-available')->class('text-gray-500 text-center py-8'),
        };
    }
}
