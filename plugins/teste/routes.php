<?php

use App\Http\Controllers\PluginViewController;

Route::get('/{page?}', [PluginViewController::class, 'show'])->where('page', '[a-zA-Z0-9_-]+');
