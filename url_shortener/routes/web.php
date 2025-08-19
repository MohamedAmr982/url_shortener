<?php

use App\Models\UrlMapping;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

Route::post("/shorten", function() {
    request()->validate([
        "url" => ["required", "url"]
    ]);
    try {
        // try inserting the url in the database, and return
        // the corresponding short_url
        $result = UrlMapping::add_new(request("url"));
    } catch (Exception $e) {
        // In case of unhandled exceptions (other than
        // UniqueConstraintViolationException, which is already handled)
        return view("errors.500", [
            "exception" => "Internal server error: " . $e->getMessage()
        ]);
    }
    return view("/view-short-url", ["result" => $result]);
});

Route::get("/{short_url}", function($short_url) {
    
    // check if exists in db
    // redirect if exists in db, otherwise return 404
    
    $long_url = UrlMapping::find($short_url)?->long_url;
    if ($long_url === null) {
        return view("errors.404");
    }
    return redirect()->away($long_url);
});
