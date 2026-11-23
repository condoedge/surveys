<?php

Route::layout('layouts.dashboard')->middleware(['auth'])->group(function(){

    //Call them in own project
    //Route::get('surveys-list', Condoedge\Surveys\Kompo\SurveyEditor\SurveysList::class)->name('surveys.list');
    //Route::get('survey-edit/{id}', Condoedge\Surveys\Kompo\SurveyEditor\SurveyFormPage::class)->name('survey.edit');

});

// The preview window checks who is asking when it opens, so the link on the page never expires
Route::middleware(['auth'])->get('survey-answer-file-preview/{answer_poll_id}', Condoedge\Surveys\Kompo\SurveyAnswers\AnswerFileModal::class)->name('surveys.answer-file-preview');

// Links expire: the signature is checked first, then the app's Gate decides who may open the files
Route::middleware(['auth', 'signed'])->group(function () {
    Route::get('survey-answer-files/{answerPoll}', [Condoedge\Surveys\Http\Controllers\AnswerFileController::class, 'show'])->name('surveys.answer-file');
    Route::get('survey-poll-files/{poll}', [Condoedge\Surveys\Http\Controllers\AnswerFileController::class, 'zip'])->name('surveys.poll-files');
});
