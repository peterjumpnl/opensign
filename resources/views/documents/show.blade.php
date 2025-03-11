@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ $document->title }}</h1>
        <div>
            <a href="{{ route('documents.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Documents
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Document Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p><span class="font-medium">Title:</span> {{ $document->title }}</p>
                <p><span class="font-medium">Status:</span> 
                    <span class="px-2 py-1 rounded text-xs 
                        @if($document->status == 'draft') bg-gray-200 text-gray-800
                        @elseif($document->status == 'pending') bg-yellow-200 text-yellow-800
                        @elseif($document->status == 'completed') bg-green-200 text-green-800
                        @else bg-red-200 text-red-800
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $document->status)) }}
                    </span>
                </p>
                @if($document->description)
                <p><span class="font-medium">Description:</span> {{ $document->description }}</p>
                @endif
            </div>
            <div>
                <p><span class="font-medium">File Name:</span> {{ $document->file_name }}</p>
                <p><span class="font-medium">File Size:</span> {{ number_format($document->file_size / 1024, 2) }} KB</p>
                <p><span class="font-medium">Uploaded:</span> {{ $document->created_at->format('M d, Y h:i A') }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-3">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Document Preview</h2>
                
                <div class="pdf-container relative border border-gray-300 rounded-lg" style="height: 800px;">
                    <!-- PDF Viewer -->
                    <object id="pdf-viewer" data="{{ Storage::url($document->file_path) }}" type="application/pdf" width="100%" height="100%" class="rounded-lg">
                        <div class="flex items-center justify-center h-full bg-gray-100 rounded-lg">
                            <div class="text-center p-6">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="mt-2 text-gray-600">Your browser doesn't support embedded PDFs. <a href="{{ Storage::url($document->file_path) }}" class="text-blue-500 hover:underline" target="_blank">Click here to view the PDF</a></p>
                            </div>
                        </div>
                    </object>
                    
                    <!-- Overlay for field placement -->
                    <div id="field-overlay" class="absolute top-0 left-0 w-full h-full pointer-events-none"></div>
                </div>
            </div>
        </div>
        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6 sticky top-6">
                <h2 class="text-xl font-semibold mb-4">Field Palette</h2>
                <p class="text-sm text-gray-600 mb-4">Drag and drop fields onto the document</p>
                
                <div class="space-y-3 mb-6">
                    <div class="field-item p-3 bg-blue-100 border border-blue-300 rounded cursor-move text-center" data-field-type="signature">
                        Signature
                    </div>
                    <div class="field-item p-3 bg-green-100 border border-green-300 rounded cursor-move text-center" data-field-type="initial">
                        Initial
                    </div>
                    <div class="field-item p-3 bg-purple-100 border border-purple-300 rounded cursor-move text-center" data-field-type="date">
                        Date
                    </div>
                    <div class="field-item p-3 bg-yellow-100 border border-yellow-300 rounded cursor-move text-center" data-field-type="checkbox">
                        Checkbox
                    </div>
                </div>
                
                <form id="save-fields-form">
                    @csrf
                    <input type="hidden" id="field-positions" name="field_positions" value="[]">
                    <button type="button" id="save-fields" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                        Save Fields
                    </button>
                </form>
                
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('documents.signers.create', $document->id) }}" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded inline-block text-center">
                        Manage Signers
                    </a>
                </div>
                
                @if($document->signers->count() > 0 && $document->signatureFields->count() > 0)
                <div class="mt-4">
                    <form action="{{ route('documents.send-invitations', $document->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white py-2 px-4 rounded">
                            Send Invitations
                        </button>
                    </form>
                </div>
                @endif
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Signers</h2>
                @if($signers->count() > 0)
                    <ul class="divide-y divide-gray-200">
                        @foreach($signers as $signer)
                            <li class="py-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium">{{ $signer->name }}</p>
                                        <p class="text-sm text-gray-600">{{ $signer->email }}</p>
                                        <p class="text-xs text-gray-500">Order: {{ $signer->order_index }}</p>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="px-2 py-1 rounded text-xs mb-2
                                            @if($signer->status == 'pending') bg-yellow-200 text-yellow-800
                                            @elseif($signer->status == 'invited') bg-blue-200 text-blue-800
                                            @elseif($signer->status == 'viewed') bg-purple-200 text-purple-800
                                            @elseif($signer->status == 'signed') bg-green-200 text-green-800
                                            @elseif($signer->status == 'declined') bg-red-200 text-red-800
                                            @else bg-gray-200 text-gray-800
                                            @endif">
                                            {{ ucfirst($signer->status) }}
                                        </span>
                                        
                                        @if($signer->status == 'pending' || $signer->status == 'invited')
                                        <form action="{{ route('documents.resend-invitation', [$document->id, $signer->id]) }}" method="POST" class="mt-1">
                                            @csrf
                                            <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">
                                                {{ $signer->invited_at ? 'Resend' : 'Send' }} Invitation
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4 text-center">
                        <a href="{{ route('documents.signers.create', $document->id) }}" class="text-blue-500 hover:underline text-sm">
                            Manage Signers
                        </a>
                    </div>
                @else
                    <p class="text-gray-600">No signers added yet.</p>
                    <div class="mt-4 text-center">
                        <a href="{{ route('documents.signers.create', $document->id) }}" class="text-blue-500 hover:underline">
                            Add Signers
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Pass signature fields to JavaScript -->
<script>
    var signatureFields = @json($signatureFields);
</script>

<!-- Include field drag script -->
<script src="{{ asset('js/field-drag.js') }}"></script>
@endsection

@section('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
