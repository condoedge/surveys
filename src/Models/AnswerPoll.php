<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\Choice;
use App\Models\Surveys\Answer;
use Condoedge\Surveys\Models\PollTypes\PollTypeFile;
use Illuminate\Support\Facades\URL;

class AnswerPoll extends ModelBaseForSurveys
{
	use \Condoedge\Surveys\Models\BelongsToPollTrait;

	public const FILE_LINK_MINUTES = 30;

	/* RELATIONS */
    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }

	/* SCOPES */

	/* CALCULATED FIELDS */
	public function getChoiceIdsAsArray()
	{
		if ($this->is_open_answer) {
			return [];
		}

		if (is_array($this->answer_text)) {
			return $this->answer_text;
		}

		if (static::isJsonEncodedAnswer($this->answer_text)) {
			return json_decode($this->answer_text, true);
		}

		return [$this->answer_text];
	}

	public function getAnswerTextForFieldValue()
	{
		return static::isJsonEncodedAnswer($this->answer_text) ? json_decode($this->answer_text, true) : $this->answer_text;
	}

	public static function isJsonEncodedAnswer($answerText)
	{
		return $answerText && (substr($answerText, 0, 2) == '["') && (substr($answerText, -2) == '"]');
	}

	public function getChoices()
	{
		return $this->memoize('choices', fn() =>
			Choice::whereIn('id', $this->getChoiceIdsAsArray())->get()
		);
	}

	public function getChoicesCost()
	{
		return $this->getChoices()->sum('choice_amount');
	}

	public function getAnswerDescriptionText()
	{
		if ($this->is_open_answer || static::sentFileIn($this->answer_text)) {
			return static::openAnswerLabel($this->answer_text);
		}

		return $this->getChoices()->map(fn($choice) => $choice->choice_content)->implode(', ');
	}

	/** An open answer as people read it: a sent file by its name, never its storage details. */
	public static function openAnswerLabel($answerText)
	{
		return static::sentFileIn($answerText)['name'] ?? $answerText;
	}

	/** The file sent to a Document question, to open or delete: name, path, mime_type, size and disk. */
	public function getAnswerFile()
	{
		return $this->poll?->getPollTypeClass() instanceof PollTypeFile ? static::sentFileIn($this->answer_text) : null;
	}

	/** Read from the stored text alone, so a row loaded without its question still reads well. */
	protected static function sentFileIn($answerText)
	{
		$file = is_string($answerText) && str_starts_with($answerText, '{') ? json_decode($answerText, true) : null;

		return isset($file['name'], $file['path'], $file['disk']) ? $file : null;
	}

	/** A short-lived link: the route checks the signature, then who is asking. */
	public function getAnswerFileUrl()
	{
		return URL::temporarySignedRoute('surveys.answer-file', now()->addMinutes(static::FILE_LINK_MINUTES), ['answerPoll' => $this->id]);
	}


	/* ACTIONS */
    public static function createOrGetAnswerPoll($answerId, $pollId)
    {
    	$ap = static::onlyGetAnswerPoll($answerId, $pollId);

    	if (!$ap) {
	        $ap = new static();
	        $ap->poll_id = $pollId;
	        $ap->answer_id = $answerId;
	        $ap->is_open_answer = $ap->poll->isOpenAnswer() ? 1 : 0;
	    }

        $ap->save();

	    return $ap;
    }

    public static function onlyGetAnswerPoll($answerId, $pollId)
    {
    	return static::forPoll($pollId)
    		->where('answer_id', $answerId)
    		->first();
    }

    public function delete()
    {
        $this->deleteAnswerFile();

        parent::delete();
    }

    public function deleteAnswerFile()
    {
        PollTypeFile::fileHandler()->unlinkFileIfExists($this->getAnswerFile());
    }

	/* ELEMENTS */
	/** For the organization's answer screens: a file opens through its link, any other answer is its text. */
	public function getAnswerDescriptionEl()
	{
		return $this->getAnswerFile() ?
			_Link($this->getAnswerFile()['name'])->href($this->getAnswerFileUrl())->inNewTab()->class('underline') :
			_Html($this->getAnswerDescriptionText());
	}
}
