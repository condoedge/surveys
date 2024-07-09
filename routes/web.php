<?php

Route::layout('layouts.dashboard')->middleware(['auth'])->group(function(){

    //Call them in own project
    //Route::get('surveys-list', Condoedge\Surveys\Kompo\SurveyEditor\SurveysList::class)->name('surveys.list');
    //Route::get('survey-edit/{id}', Condoedge\Surveys\Kompo\SurveyEditor\SurveyFormPage::class)->name('survey.edit');

});

