<?php

namespace Condoedge\Surveys\Http\Controllers;

use App\Models\Surveys\AnswerPoll;
use App\Models\Surveys\Poll;
use Condoedge\Surveys\Models\PollTypes\PollTypeFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Opens the files sent to Document questions, for the people the app's Gate allows. */
class AnswerFileController
{
    public function show(AnswerPoll $answerPoll)
    {
        Gate::authorize(PollTypeFile::VIEW_FILES_ABILITY, $answerPoll->poll->getMainPoll());

        $file = $answerPoll->getAnswerFile() ?? abort(404);

        return Storage::disk($file['disk'])->{request('download') ? 'download' : 'response'}($file['path'], $file['name']);
    }

    /** Every file sent to the question, each named after the person who sent it. */
    public function zip(Poll $poll)
    {
        Gate::authorize(PollTypeFile::VIEW_FILES_ABILITY, $poll);

        $answerPolls = AnswerPoll::whereIn('poll_id', $poll->subPolls()->pluck('id')->push($poll->id))
            ->whereHas('answer', fn ($q) => $q->lockedAnswer())
            ->with('answer', 'poll')->get();

        $zipPath = tempnam(sys_get_temp_dir(), 'survey-files');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::OVERWRITE);

        foreach ($answerPolls as $answerPoll) {
            $file = $answerPoll->getAnswerFile();

            if ($file && Storage::disk($file['disk'])->exists($file['path'])) {
                $name = $answerPoll->answer->getAnswererName().' - '.$file['name'];
                $zip->addFile(Storage::disk($file['disk'])->path($file['path']), $zip->locateName($name) === false ? $name : $answerPoll->id.' - '.$name);
            }
        }

        $zip->close();

        return response()->download($zipPath, Str::slug(strip_tags($poll->body_po)).'.zip')->deleteFileAfterSend();
    }
}
