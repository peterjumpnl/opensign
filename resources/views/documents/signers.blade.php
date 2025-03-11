@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Add Signers to Document</h1>
        <div>
            <a href="{{ route('documents.show', $document->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Document
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Document Details</h2>
                <div class="mb-4">
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
                    <p><span class="font-medium">Signature Fields:</span> {{ $document->signatureFields->count() }}</p>
                </div>
                
                <div class="mb-4">
                    <h3 class="text-lg font-medium mb-2">Current Signers</h3>
                    @if($signers->isEmpty())
                        <p class="text-gray-600 italic">No signers have been added yet.</p>
                    @else
                        <div class="bg-gray-50 rounded-lg p-4">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($signers->sortBy('order_index') as $signer)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $signer->order_index }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium">{{ $signer->name }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm">{{ $signer->email }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <span class="px-2 py-1 rounded text-xs 
                                                @if($signer->status == 'pending') bg-yellow-200 text-yellow-800
                                                @elseif($signer->status == 'signed') bg-green-200 text-green-800
                                                @elseif($signer->status == 'rejected') bg-red-200 text-red-800
                                                @else bg-gray-200 text-gray-800
                                                @endif">
                                                {{ ucfirst($signer->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm flex space-x-3">
                                            <button 
                                                type="button" 
                                                class="text-blue-600 hover:text-blue-800 copy-link-btn flex items-center" 
                                                data-url="{{ route('sign.show', [$document->id, $signer->id]) }}?token={{ $signer->access_token }}"
                                                title="Copy signing link">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                                                </svg>
                                                Copy Link
                                            </button>
                                            <form action="{{ route('documents.signers.destroy', [$document->id, $signer->id]) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to remove this signer?')">
                                                    Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-6">
                <h2 class="text-xl font-semibold mb-4">Add Signers</h2>
                <form action="{{ route('documents.signers.store', $document->id) }}" method="POST" id="signers-form">
                    @csrf
                    
                    @if($signers->isNotEmpty())
                    <div class="mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="replace_existing" id="replace_existing" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                            <label for="replace_existing" class="ml-2 block text-sm text-gray-900">
                                Replace existing signers
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Warning: This will remove all current signers.</p>
                    </div>
                    @endif
                    
                    <div id="signers-container">
                        <div class="signer-entry bg-gray-50 p-4 rounded-lg mb-4">
                            <div class="mb-3">
                                <label for="signers[0][name]" class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" name="signers[0][name]" id="signers[0][name]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            </div>
                            <div class="mb-3">
                                <label for="signers[0][email]" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="signers[0][email]" id="signers[0][email]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            </div>
                            <div class="mb-2">
                                <label for="signers[0][order_index]" class="block text-sm font-medium text-gray-700">Signing Order</label>
                                <input type="number" name="signers[0][order_index]" id="signers[0][order_index]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" min="1" value="1" required>
                            </div>
                            <button type="button" class="remove-signer text-red-600 text-sm hover:text-red-900" style="display: none;">Remove</button>
                        </div>
                    </div>
                    
                    <div class="flex justify-between mb-4">
                        <button type="button" id="add-signer" class="text-blue-600 hover:text-blue-900 text-sm">
                            + Add Another Signer
                        </button>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                        Save Signers
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const signersContainer = document.getElementById('signers-container');
        const addSignerButton = document.getElementById('add-signer');
        let signerCount = 1;
        
        // Function to add a new signer entry
        addSignerButton.addEventListener('click', function() {
            const newSignerEntry = document.createElement('div');
            newSignerEntry.className = 'signer-entry bg-gray-50 p-4 rounded-lg mb-4';
            newSignerEntry.innerHTML = `
                <div class="mb-3">
                    <label for="signers[${signerCount}][name]" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="signers[${signerCount}][name]" id="signers[${signerCount}][name]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div class="mb-3">
                    <label for="signers[${signerCount}][email]" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="signers[${signerCount}][email]" id="signers[${signerCount}][email]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div class="mb-2">
                    <label for="signers[${signerCount}][order_index]" class="block text-sm font-medium text-gray-700">Signing Order</label>
                    <input type="number" name="signers[${signerCount}][order_index]" id="signers[${signerCount}][order_index]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" min="1" value="${signerCount + 1}" required>
                </div>
                <button type="button" class="remove-signer text-red-600 text-sm hover:text-red-900">Remove</button>
            `;
            
            signersContainer.appendChild(newSignerEntry);
            signerCount++;
            
            // Show remove button on first signer if there are multiple signers
            if (signerCount > 1) {
                document.querySelector('.signer-entry .remove-signer').style.display = 'block';
            }
            
            // Add event listener to the new remove button
            newSignerEntry.querySelector('.remove-signer').addEventListener('click', function() {
                signersContainer.removeChild(newSignerEntry);
                signerCount--;
                
                // Hide remove button on first signer if it's the only one left
                if (signerCount === 1) {
                    document.querySelector('.signer-entry .remove-signer').style.display = 'none';
                }
                
                // Update order indices
                updateOrderIndices();
            });
        });
        
        // Function to update order indices
        function updateOrderIndices() {
            const signerEntries = document.querySelectorAll('.signer-entry');
            signerEntries.forEach((entry, index) => {
                const orderInput = entry.querySelector('input[name*="[order_index]"]');
                orderInput.value = index + 1;
            });
        }
        
        // Copy link functionality
        const copyButtons = document.querySelectorAll('.copy-link-btn');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                
                // Create a temporary input element
                const tempInput = document.createElement('input');
                tempInput.value = url;
                document.body.appendChild(tempInput);
                
                // Select and copy the text
                tempInput.select();
                document.execCommand('copy');
                
                // Remove the temporary element
                document.body.removeChild(tempInput);
                
                // Visual feedback
                const originalText = this.innerHTML;
                this.innerHTML = `
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Copied!
                `;
                this.classList.remove('text-blue-600', 'hover:text-blue-800');
                this.classList.add('text-green-600');
                
                // Reset after 2 seconds
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('text-green-600');
                    this.classList.add('text-blue-600', 'hover:text-blue-800');
                }, 2000);
            });
        });
    });
</script>
@endsection
