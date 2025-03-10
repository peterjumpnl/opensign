<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SignerController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authentication Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

// Google OAuth Routes
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Protected Routes - Require Authentication
Route::middleware(['auth'])->group(function () {
    // Dashboard Route
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Document Management Routes
    Route::resource('documents', DocumentController::class)->except(['edit', 'update']);
    
    // Signature Field Routes
    Route::post('/documents/{document}/fields', [DocumentController::class, 'saveFields'])->name('documents.fields.save');
    
    // Signer Management Routes
    Route::get('/documents/{document}/signers', [SignerController::class, 'create'])->name('documents.signers.create');
    Route::post('/documents/{document}/signers', [SignerController::class, 'store'])->name('documents.signers.store');
    Route::delete('/documents/{document}/signers/{signer}', [SignerController::class, 'destroy'])->name('documents.signers.destroy');
    
    // Invitation Routes
    Route::post('/documents/{document}/send-invitations', [SignerController::class, 'sendInvitations'])->name('documents.send-invitations');
    Route::post('/documents/{document}/signers/{signer}/resend-invitation', [SignerController::class, 'resendInvitation'])->name('documents.resend-invitation');
    
    // Logout Route
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Public Signing Routes
Route::get('/sign/{document}/{signer}', [SignerController::class, 'showSigningPage'])->name('sign.show');
Route::post('/sign/{document}/{signer}', [SignerController::class, 'processSignature'])->name('sign.process');
Route::post('/sign/{document}/{signer}/decline', [SignerController::class, 'declineSignature'])->name('sign.decline');
