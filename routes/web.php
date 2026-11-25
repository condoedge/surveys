<?php

Route::layout('layouts.dashboard')->middleware(['auth'])->group(function(){

    //Call them in own project
    //Route::get('surveys-list', Condoedge\Surveys\Kompo\SurveyEditor\SurveysList::class)->name('surveys.list');
    //Route::get('survey-edit/{id}', Condoedge\Surveys\Kompo\SurveyEditor\SurveyFormPage::class)->name('survey.edit');

});

// The preview window and the ZIP check who is asking when they open, so their links on a report never expire
Route::middleware(['auth'])->get('survey-answer-file-preview/{answer_poll_id}', Condoedge\Surveys\Kompo\SurveyAnswers\AnswerFileModal::class)->name('surveys.answer-file-preview');
Route::middleware(['auth'])->get('survey-poll-files/{poll}', [Condoedge\Surveys\Http\Controllers\AnswerFileController::class, 'zip'])->name('surveys.poll-files');

// Links expire: the signature is checked first, then the app's Gate decides who may open the file
Route::middleware(['auth', 'signed'])->get('survey-answer-files/{answerPoll}', [Condoedge\Surveys\Http\Controllers\AnswerFileController::class, 'show'])->name('surveys.answer-file');
