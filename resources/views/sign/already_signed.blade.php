@extends('layouts.sign')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
        <div class="flex items-center justify-center mb-6">
            <div class="rounded-full bg-green-100 p-3">
                <svg class="h-8 w-8 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>
        
        <h1 class="text-2xl font-bold text-center text-gray-900 mb-6">You've Already Signed This Document</h1>
        
        <div class="text-center mb-8">
            <p class="text-gray-600 mb-4">
                You have already signed the document <span class="font-semibold">{{ $document->title }}</span>.
            </p>
            <p class="text-gray-600">
                Your signature has been recorded and no further action is required from you.
            </p>
        </div>
        
        <div class="border-t border-gray-200 pt-6">
            <div class="flex flex-col items-center">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Document Details</h3>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2 mb-6">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Document Title</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $document->title }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Signed On</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $signer->signed_at ? $signer->signed_at->format('F j, Y, g:i a') : 'Unknown' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
        
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                You can close this window now. If you have any questions, please contact the document owner.
            </p>
        </div>
    </div>
</div>
@endsection
