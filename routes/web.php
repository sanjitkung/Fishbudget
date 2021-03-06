<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('transactions/import', 'TransactionImportController@create')->middleware('auth');
Route::post('transactions/import', 'TransactionImportController@store')->middleware('auth')->name('transactions.import');
Route::get('transactions/import/mapping', 'ImportMappingController@create')->name('transactions.import.mapping');

Route::get('transactions', 'TransactionController@index')->middleware('auth');
Route::get('transactions/{transaction}', 'TransactionController@show')->middleware('auth');
Route::post('transactions', 'TransactionController@store')->middleware('auth');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
