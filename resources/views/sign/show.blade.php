<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Document - {{ $document->title }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <style>
        .signature-pad-container {
            width: 100%;
            height: 200px;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            position: relative;
            background-color: white;
        }
        .signature-pad {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }
        .signature-field {
            position: absolute;
            border: 2px dashed #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #3b82f6;
        }
        .signature-field.signed {
            border: 2px solid #10b981;
            background-color: rgba(16, 185, 129, 0.05);
        }
        .signature-field.active {
            border: 2px solid #6366f1;
            background-color: rgba(99, 102, 241, 0.1);
        }
        .signature-field.required {
            border: 2px dashed #ef4444;
            background-color: rgba(239, 68, 68, 0.05);
        }
        .signature-image {
            max-width: 100%;
            max-height: 100%;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-white shadow">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold text-gray-800">OpenSign</h1>
                <div class="text-sm text-gray-600">
                    Signing as: <span class="font-medium">{{ $signer->name }}</span>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6">
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
        @endif

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Document: {{ $document->title }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-600">
                        You have been invited by <span class="font-medium">{{ $document->user->name }}</span> to sign this document.
                    </p>
                    @if($document->description)
                    <p class="text-sm text-gray-600 mt-2">
                        <span class="font-medium">Description:</span> {{ $document->description }}
                    </p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">
                        <span class="font-medium">Status:</span>
                        <span class="px-2 py-1 rounded text-xs bg-yellow-200 text-yellow-800">
                            Awaiting Your Signature
                        </span>
                    </p>
                </div>
            </div>

            <div class="flex justify-end space-x-2 mb-4">
                <a href="{{ Storage::url($document->file_path) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    View Full Document
                </a>
                <button id="download-btn" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
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
                        
                        <!-- Overlay for signature fields -->
                        <div id="signature-fields-overlay" class="absolute top-0 left-0 w-full h-full pointer-events-none"></div>
                    </div>
                </div>
            </div>
            
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6 sticky top-6">
                    <h2 class="text-xl font-semibold mb-4">Sign Document</h2>
                    
                    <div id="signature-instructions" class="mb-6">
                        <p class="text-gray-600 mb-4">Click on a signature field in the document to sign it.</p>
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                            <p class="text-sm text-gray-600">Blue fields require your signature</p>
                        </div>
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="w-4 h-4 rounded-full bg-green-500"></div>
                            <p class="text-sm text-gray-600">Green fields have been signed</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-4 h-4 rounded-full bg-gray-300"></div>
                            <p class="text-sm text-gray-600">Gray fields are for other signers</p>
                        </div>
                    </div>
                    
                    <div id="signature-pad-container" class="hidden mb-6">
                        <h3 class="text-lg font-medium mb-2">Draw Your Signature</h3>
                        <div class="signature-pad-container mb-2">
                            <canvas id="signature-pad" class="signature-pad"></canvas>
                        </div>
                        <div class="flex justify-between mb-4">
                            <button id="clear-signature" class="text-sm text-red-600 hover:text-red-800">Clear</button>
                            <button id="type-signature" class="text-sm text-blue-600 hover:text-blue-800">Type Instead</button>
                        </div>
                        <div class="flex space-x-2">
                            <button id="cancel-signature" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded">
                                Cancel
                            </button>
                            <button id="save-signature" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                                Sign
                            </button>
                        </div>
                    </div>
                    
                    <div id="type-signature-container" class="hidden mb-6">
                        <h3 class="text-lg font-medium mb-2">Type Your Signature</h3>
                        <div class="mb-4">
                            <input type="text" id="typed-signature" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Type your full name">
                        </div>
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">Preview:</p>
                            <div id="typed-signature-preview" class="p-4 border border-gray-300 rounded-md min-h-[60px] font-signature text-xl"></div>
                        </div>
                        <div class="flex space-x-2">
                            <button id="cancel-typed-signature" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded">
                                Cancel
                            </button>
                            <button id="save-typed-signature" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                                Sign
                            </button>
                        </div>
                    </div>
                    
                    <div id="signature-status" class="mb-6">
                        <h3 class="text-lg font-medium mb-2">Signature Status</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm">
                                <span id="signed-count" class="font-medium">0</span> of 
                                <span id="total-fields-count" class="font-medium">0</span> required fields signed
                            </p>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                <div id="signature-progress" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <form id="complete-signing-form" action="{{ route('sign.process', [$document->id, $signer->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" id="signatures-data" name="signatures" value="[]">
                        <button id="complete-signing" type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded" disabled>
                            Complete Signing
                        </button>
                    </form>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <button id="decline-signing" class="w-full text-red-600 hover:text-red-800 text-sm">
                            Decline to Sign
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Decline Modal -->
        <div id="decline-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full">
                <h3 class="text-lg font-semibold mb-4">Decline to Sign</h3>
                <p class="text-gray-600 mb-4">Please provide a reason for declining to sign this document:</p>
                <form id="decline-form" action="{{ route('sign.decline', [$document->id, $signer->id]) }}" method="POST">
                    @csrf
                    <textarea id="decline-reason" name="reason" rows="4" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-4" placeholder="Enter your reason here..." required></textarea>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancel-decline" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded">
                            Cancel
                        </button>
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded">
                            Confirm Decline
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-gray-200 py-4">
        <div class="container mx-auto px-4">
            <p class="text-center text-sm text-gray-600">
                &copy; {{ date('Y') }} OpenSign. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize variables
            const signatureFields = @json($signatureFields);
            const signedFields = @json($signedFields ?? []);
            const signerFields = signatureFields.filter(field => field.signer_id === {{ $signer->id }} || field.signer_id === null);
            const otherSignerFields = signatureFields.filter(field => field.signer_id !== {{ $signer->id }} && field.signer_id !== null);
            let activeFieldId = null;
            let signatures = [];
            
            // Initialize signature pad
            const canvas = document.getElementById('signature-pad');
            const signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgba(255, 255, 255, 0)',
                penColor: 'rgb(0, 0, 0)'
            });
            
            // Resize canvas
            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                signaturePad.clear();
            }
            
            window.addEventListener("resize", resizeCanvas);
            resizeCanvas();
            
            // Render signature fields
            const overlay = document.getElementById('signature-fields-overlay');
            
            function renderSignatureFields() {
                overlay.innerHTML = '';
                
                // Render this signer's fields
                signerFields.forEach(field => {
                    const fieldElement = document.createElement('div');
                    fieldElement.className = 'signature-field';
                    fieldElement.id = `field-${field.id}`;
                    fieldElement.style.left = `${field.x_position}px`;
                    fieldElement.style.top = `${field.y_position}px`;
                    fieldElement.style.width = `${field.width}px`;
                    fieldElement.style.height = `${field.height}px`;
                    
                    // Check if this field has been signed
                    const isSigned = signedFields.some(sf => sf.field_id === field.id) || 
                                    signatures.some(s => s.field_id === field.id);
                    
                    if (isSigned) {
                        fieldElement.classList.add('signed');
                        const signature = signatures.find(s => s.field_id === field.id) || 
                                         signedFields.find(sf => sf.field_id === field.id);
                        
                        if (signature) {
                            const img = document.createElement('img');
                            img.src = signature.signature_image || signature.value;
                            img.className = 'signature-image';
                            img.alt = 'Signature';
                            fieldElement.appendChild(img);
                        }
                    } else {
                        fieldElement.classList.add('required');
                        fieldElement.innerText = field.field_type.charAt(0).toUpperCase() + field.field_type.slice(1);
                        fieldElement.style.pointerEvents = 'auto';
                        
                        fieldElement.addEventListener('click', () => {
                            openSignaturePanel(field.id);
                        });
                    }
                    
                    overlay.appendChild(fieldElement);
                });
                
                // Render other signers' fields (non-interactive)
                otherSignerFields.forEach(field => {
                    const fieldElement = document.createElement('div');
                    fieldElement.className = 'signature-field';
                    fieldElement.style.left = `${field.x_position}px`;
                    fieldElement.style.top = `${field.y_position}px`;
                    fieldElement.style.width = `${field.width}px`;
                    fieldElement.style.height = `${field.height}px`;
                    fieldElement.style.borderColor = '#d1d5db';
                    fieldElement.style.backgroundColor = 'rgba(209, 213, 219, 0.1)';
                    fieldElement.style.color = '#6b7280';
                    
                    // Check if this field has been signed
                    const isSigned = signedFields.some(sf => sf.field_id === field.id);
                    
                    if (isSigned) {
                        fieldElement.classList.add('signed');
                        const signature = signedFields.find(sf => sf.field_id === field.id);
                        
                        if (signature) {
                            const img = document.createElement('img');
                            img.src = signature.value;
                            img.className = 'signature-image';
                            img.alt = 'Signature';
                            fieldElement.appendChild(img);
                        }
                    } else {
                        fieldElement.innerText = 'Other Signer';
                    }
                    
                    overlay.appendChild(fieldElement);
                });
                
                updateSignatureStatus();
            }
            
            // Open signature panel
            function openSignaturePanel(fieldId) {
                activeFieldId = fieldId;
                
                // Highlight active field
                document.querySelectorAll('.signature-field').forEach(field => {
                    field.classList.remove('active');
                });
                document.getElementById(`field-${fieldId}`).classList.add('active');
                
                // Show signature pad
                document.getElementById('signature-instructions').classList.add('hidden');
                document.getElementById('signature-pad-container').classList.remove('hidden');
                document.getElementById('type-signature-container').classList.add('hidden');
                
                // Clear signature pad
                signaturePad.clear();
            }
            
            // Save signature
            document.getElementById('save-signature').addEventListener('click', function() {
                if (signaturePad.isEmpty()) {
                    alert('Please provide a signature');
                    return;
                }
                
                const signatureData = signaturePad.toDataURL();
                saveSignature(signatureData);
            });
            
            // Clear signature
            document.getElementById('clear-signature').addEventListener('click', function() {
                signaturePad.clear();
            });
            
            // Cancel signature
            document.getElementById('cancel-signature').addEventListener('click', function() {
                closeSignaturePanel();
            });
            
            // Type signature instead
            document.getElementById('type-signature').addEventListener('click', function() {
                document.getElementById('signature-pad-container').classList.add('hidden');
                document.getElementById('type-signature-container').classList.remove('hidden');
            });
            
            // Typed signature preview
            const typedSignatureInput = document.getElementById('typed-signature');
            const typedSignaturePreview = document.getElementById('typed-signature-preview');
            
            typedSignatureInput.addEventListener('input', function() {
                typedSignaturePreview.textContent = this.value;
            });
            
            // Save typed signature
            document.getElementById('save-typed-signature').addEventListener('click', function() {
                const typedValue = typedSignatureInput.value.trim();
                
                if (!typedValue) {
                    alert('Please type your signature');
                    return;
                }
                
                // Convert typed signature to image (using canvas)
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.width = 300;
                canvas.height = 100;
                
                context.fillStyle = 'white';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.font = '32px cursive';
                context.fillStyle = 'black';
                context.textAlign = 'center';
                context.textBaseline = 'middle';
                context.fillText(typedValue, canvas.width / 2, canvas.height / 2);
                
                const signatureData = canvas.toDataURL();
                saveSignature(signatureData);
            });
            
            // Cancel typed signature
            document.getElementById('cancel-typed-signature').addEventListener('click', function() {
                closeSignaturePanel();
            });
            
            // Save signature data
            function saveSignature(signatureData) {
                // Find the field in the signature fields
                const field = signerFields.find(f => f.id === activeFieldId);
                
                if (!field) {
                    console.error('Field not found');
                    return;
                }
                
                // Add or update signature in the signatures array
                const existingIndex = signatures.findIndex(s => s.field_id === activeFieldId);
                
                if (existingIndex >= 0) {
                    signatures[existingIndex].value = signatureData;
                } else {
                    signatures.push({
                        field_id: activeFieldId,
                        value: signatureData,
                        field_type: field.field_type
                    });
                }
                
                // Update hidden input with signatures data
                document.getElementById('signatures-data').value = JSON.stringify(signatures);
                
                // Close signature panel
                closeSignaturePanel();
                
                // Re-render signature fields
                renderSignatureFields();
            }
            
            // Close signature panel
            function closeSignaturePanel() {
                activeFieldId = null;
                
                // Remove active class from all fields
                document.querySelectorAll('.signature-field').forEach(field => {
                    field.classList.remove('active');
                });
                
                // Hide signature panels
                document.getElementById('signature-instructions').classList.remove('hidden');
                document.getElementById('signature-pad-container').classList.add('hidden');
                document.getElementById('type-signature-container').classList.add('hidden');
                
                // Clear typed signature
                typedSignatureInput.value = '';
                typedSignaturePreview.textContent = '';
            }
            
            // Update signature status
            function updateSignatureStatus() {
                const totalFields = signerFields.length;
                const signedCount = signatures.length + (signedFields ? signedFields.filter(sf => signerFields.some(f => f.id === sf.field_id)).length : 0);
                
                document.getElementById('signed-count').textContent = signedCount;
                document.getElementById('total-fields-count').textContent = totalFields;
                
                const progressPercent = totalFields > 0 ? (signedCount / totalFields) * 100 : 0;
                document.getElementById('signature-progress').style.width = `${progressPercent}%`;
                
                // Enable/disable complete button
                const completeButton = document.getElementById('complete-signing');
                completeButton.disabled = signedCount < totalFields;
            }
            
            // Decline to sign
            document.getElementById('decline-signing').addEventListener('click', function() {
                document.getElementById('decline-modal').classList.remove('hidden');
            });
            
            // Cancel decline
            document.getElementById('cancel-decline').addEventListener('click', function() {
                document.getElementById('decline-modal').classList.add('hidden');
                document.getElementById('decline-reason').value = '';
            });
            
            // Download document
            document.getElementById('download-btn').addEventListener('click', function() {
                window.open("{{ Storage::url($document->file_path) }}", '_blank');
            });
            
            // Initial render
            renderSignatureFields();
        });
    </script>
</body>
</html>
