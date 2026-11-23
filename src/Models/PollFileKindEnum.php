<?php

namespace Condoedge\Surveys\Models;

use Illuminate\Http\UploadedFile;

/** The kinds of files a Document question can accept. */
enum PollFileKindEnum: int
{
    use \Kompo\Models\Traits\EnumKompo;

    case IMAGE = 1;
    case PDF = 2;
    case OFFICE = 3;

    public function label()
    {
        return match ($this) {
            static::IMAGE => __('surveys-file-kind-image'),
            static::PDF => 'PDF',
            static::OFFICE => __('surveys-file-kind-office'),
        };
    }

    public function extensions()
    {
        return match ($this) {
            static::IMAGE => ['jpg', 'jpeg', 'png', 'heic', 'heif'],
            static::PDF => ['pdf'],
            static::OFFICE => ['doc', 'docx', 'xls', 'xlsx'],
        };
    }

    /** Read from the file's content, not from its name. */
    public static function ofFile(UploadedFile $file)
    {
        return collect(static::cases())->first(fn ($kind) => in_array($file->guessExtension(), $kind->extensions()));
    }
}
