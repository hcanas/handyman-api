<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any?}', function () {
    abort(404);
});
