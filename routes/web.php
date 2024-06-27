<?php

Route::layout('layouts.guest')->group(function(){



	Route::middleware(['signed'])->group(function(){




    });

});

Route::middleware(['signed', 'throttle:10,1'])->group(function(){



});


Route::layout('layouts.dashboard')->middleware(['auth'])->group(function(){


});

