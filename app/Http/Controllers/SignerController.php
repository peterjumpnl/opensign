<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Signer;
use App\Models\Signature;
use App\Services\NotificationService;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignerController extends Controller
{
    /**
     * Display the form for adding signers to a document.
     */
    public function create(Document $document)
    {
        // Check if the authenticated user owns the document
        if ($document->user_id !== auth()->id()) {
            return redirect()->route('documents.index')
                ->with('error', 'You are not authorized to manage signers for this document.');
        }

        // Get existing signers for the document
        $signers = $document->signers;

        return view('documents.signers', compact('document', 'signers'));
    }

    /**
     * Store signers for a document.
     */
    public function store(Request $request, Document $document)
    {
        // Check if the authenticated user owns the document
        if ($document->user_id !== auth()->id()) {
            return redirect()->route('documents.index')
                ->with('error', 'You are not authorized to manage signers for this document.');
        }

        // Validate the request
        $request->validate([
            'signers' => 'required|array|min:1',
            'signers.*.name' => 'required|string|max:255',
            'signers.*.email' => 'required|email|max:255',
            'signers.*.order_index' => 'required|integer|min:1',
            'replace_existing' => 'nullable|boolean',
        ]);

        // Start a database transaction
        DB::beginTransaction();

        try {
            // If replace_existing is checked, delete all existing signers
            if ($request->has('replace_existing') && $request->replace_existing) {
                $document->signers()->delete();
            }

            // Add new signers
            foreach ($request->signers as $signerData) {
                $document->signers()->create([
                    'name' => $signerData['name'],
                    'email' => $signerData['email'],
                    'order_index' => $signerData['order_index'],
                    'status' => 'pending',
                    'access_token' => Str::uuid(),
                ]);
            }

            // Update document status to pending
            $document->update(['status' => 'pending']);

            // Commit the transaction
            DB::commit();

            return redirect()->route('documents.signers.create', $document->id)
                ->with('success', 'Signers added successfully.');
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();

            return redirect()->route('documents.signers.create', $document->id)
                ->with('error', 'Failed to add signers: ' . $e->getMessage());
        }
    }

    /**
     * Remove a signer from a document.
     */
    public function destroy(Document $document, Signer $signer)
    {
        // Check if the authenticated user owns the document
        if ($document->user_id !== auth()->id()) {
            return redirect()->route('documents.index')
                ->with('error', 'You are not authorized to manage signers for this document.');
        }

        // Delete the signer
        $signer->delete();

        // If no signers remain, update document status back to draft
        if ($document->signers()->count() === 0) {
            $document->update(['status' => 'draft']);
        }

        return redirect()->route('documents.signers.create', $document->id)
            ->with('success', 'Signer removed successfully.');
    }
    
    /**
     * Send invitation emails to signers.
     */
    public function sendInvitations(Document $document, NotificationService $notificationService)
    {
        // Check if the authenticated user owns the document
        if ($document->user_id !== auth()->id()) {
            return redirect()->route('documents.index')
                ->with('error', 'You are not authorized to send invitations for this document.');
        }
        
        // Check if the document has signature fields
        if ($document->signatureFields()->count() === 0) {
            return redirect()->route('documents.show', $document->id)
                ->with('error', 'Please add signature fields to the document before sending invitations.');
        }
        
        // Check if the document has signers
        if ($document->signers()->count() === 0) {
            return redirect()->route('documents.signers.create', $document->id)
                ->with('error', 'Please add signers to the document before sending invitations.');
        }
        
        // Send invitations
        $results = $notificationService->sendDocumentInvitations($document);
        
        // Count successful and failed invitations
        $successCount = count(array_filter($results, function($result) {
            return $result['success'];
        }));
        
        $failCount = count($results) - $successCount;
        
        // Prepare the success message
        $message = "{$successCount} invitation(s) sent successfully.";
        if ($failCount > 0) {
            $message .= " {$failCount} invitation(s) failed to send.";
        }
        
        return redirect()->route('documents.show', $document->id)
            ->with('success', $message);
    }
    
    /**
     * Resend invitation email to a specific signer.
     */
    public function resendInvitation(Document $document, Signer $signer, NotificationService $notificationService)
    {
        // Check if the authenticated user owns the document
        if ($document->user_id !== auth()->id()) {
            return redirect()->route('documents.index')
                ->with('error', 'You are not authorized to resend invitations for this document.');
        }
        
        // Resend the invitation
        $result = $notificationService->sendSignerInvitation($signer, $document);
        
        if ($result['success']) {
            return redirect()->route('documents.show', $document->id)
                ->with('success', "Invitation resent successfully to {$signer->email}.");
        } else {
            return redirect()->route('documents.show', $document->id)
                ->with('error', "Failed to resend invitation to {$signer->email}: {$result['message']}");
        }
    }
    
    /**
     * Show the signing page for a document.
     */
    public function showSigningPage(Document $document, Signer $signer, Request $request)
    {
        // Validate the access token
        if (!$request->has('token') || $request->token !== $signer->access_token) {
            abort(403, 'Invalid or expired signing link.');
        }
        
        // Check if the document is still pending signatures
        if ($document->status !== 'pending') {
            if ($document->status === 'completed') {
                return view('sign.completed', compact('document', 'signer'));
            } else {
                abort(403, 'This document is no longer available for signing.');
            }
        }
        
        // Check if this signer has already signed
        if ($signer->status === 'signed') {
            return view('sign.already_signed', compact('document', 'signer'));
        }
        
        // Check if this signer has declined
        if ($signer->status === 'declined') {
            return view('sign.declined', compact('document', 'signer'));
        }
        
        // Get signature fields for this document
        $signatureFields = $document->signatureFields;
        
        // Get already signed fields
        $signedFields = $signer->signatures;
        
        // Update signer status to viewed if this is the first view
        if (!$signer->viewed_at) {
            $signer->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
            
            // Log the view in audit log
            $signer->auditLogs()->create([
                'action' => 'viewed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => json_encode([
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                ]),
            ]);
        }
        
        return view('sign.show', compact('document', 'signer', 'signatureFields', 'signedFields'));
    }
    
    /**
     * Process the signature submission.
     */
    public function processSignature(Document $document, Signer $signer, Request $request)
    {
        // Validate the access token
        if (!$request->has('token') || $request->token !== $signer->access_token) {
            abort(403, 'Invalid or expired signing link.');
        }
        
        // Validate the request
        $request->validate([
            'signatures' => 'required|json',
        ]);
        
        // Parse signatures data
        $signaturesData = json_decode($request->signatures, true);
        
        if (empty($signaturesData)) {
            return back()->with('error', 'No signatures provided.');
        }
        
        // Start a database transaction
        DB::beginTransaction();
        
        try {
            // Process each signature
            foreach ($signaturesData as $signatureData) {
                // Validate signature data
                if (!isset($signatureData['field_id']) || !isset($signatureData['value'])) {
                    continue;
                }
                
                // Check if the field exists and belongs to this document
                $field = $document->signatureFields()->find($signatureData['field_id']);
                
                if (!$field) {
                    continue;
                }
                
                // Create or update the signature
                $signature = Signature::updateOrCreate(
                    [
                        'signer_id' => $signer->id,
                        'field_id' => $signatureData['field_id'],
                    ],
                    [
                        'value' => $signatureData['value'],
                        'field_type' => $signatureData['field_type'] ?? $field->field_type,
                        'signed_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );
            }
            
            // Update signer status
            $signer->update([
                'status' => 'signed',
                'signed_at' => now(),
            ]);
            
            // Log the signing in audit log
            $signer->auditLogs()->create([
                'action' => 'signed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => json_encode([
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                    'signature_count' => count($signaturesData),
                ]),
            ]);
            
            // Check if all signers have signed
            $allSigned = $this->checkAllSignersHaveSigned($document);
            
            if ($allSigned) {
                // Update document status to completed
                $document->update(['status' => 'completed']);
                
                // Generate final signed PDF
                $this->generateSignedPDF($document);
                
                // Notify document owner
                // $notificationService->sendDocumentCompletedNotification($document);
            } else {
                // Check if next signer should be notified
                $this->notifyNextSigner($document, $signer);
            }
            
            // Commit the transaction
            DB::commit();
            
            return view('sign.success', compact('document', 'signer'));
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();
            
            return back()->with('error', 'Failed to process signatures: ' . $e->getMessage());
        }
    }
    
    /**
     * Process the signature decline.
     */
    public function declineSignature(Document $document, Signer $signer, Request $request)
    {
        // Validate the access token
        if (!$request->has('token') || $request->token !== $signer->access_token) {
            abort(403, 'Invalid or expired signing link.');
        }
        
        // Validate the request
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);
        
        // Update signer status
        $signer->update([
            'status' => 'declined',
            'declined_at' => now(),
            'decline_reason' => $request->reason,
        ]);
        
        // Log the decline in audit log
        $signer->auditLogs()->create([
            'action' => 'declined',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => json_encode([
                'document_id' => $document->id,
                'document_title' => $document->title,
                'reason' => $request->reason,
            ]),
        ]);
        
        // Update document status to declined
        $document->update(['status' => 'declined']);
        
        // Notify document owner
        // $notificationService->sendSigningRejectedNotification($signer, $document, $request->reason);
        
        return view('sign.declined_confirmation', compact('document', 'signer'));
    }
    
    /**
     * Check if all signers have signed the document.
     */
    private function checkAllSignersHaveSigned(Document $document): bool
    {
        $signers = $document->signers;
        
        if ($signers->isEmpty()) {
            return false;
        }
        
        foreach ($signers as $signer) {
            if ($signer->status !== 'signed') {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Notify the next signer in the sequence.
     */
    private function notifyNextSigner(Document $document, Signer $currentSigner)
    {
        // Get all signers ordered by order_index
        $signers = $document->signers()->orderBy('order_index')->get();
        
        // Find the current signer's index
        $currentIndex = null;
        foreach ($signers as $index => $signer) {
            if ($signer->id === $currentSigner->id) {
                $currentIndex = $index;
                break;
            }
        }
        
        // If we couldn't find the current signer or they're the last one, return
        if ($currentIndex === null || $currentIndex >= count($signers) - 1) {
            return;
        }
        
        // Get the next signer
        $nextSigner = $signers[$currentIndex + 1];
        
        // Check if the next signer has already been invited or viewed the document
        if ($nextSigner->status === 'pending') {
            // Send invitation to the next signer
            $notificationService = app(NotificationService::class);
            $notificationService->sendSignerInvitation($nextSigner, $document);
        }
    }
    
    /**
     * Generate the final signed PDF.
     */
    private function generateSignedPDF(Document $document)
    {
        // This will be implemented with a PDF service in a future step
        // For now, we'll just create a copy of the original PDF
        
        // Create signed documents directory if it doesn't exist
        $signedDir = 'docs/signed';
        if (!Storage::exists($signedDir)) {
            Storage::makeDirectory($signedDir);
        }
        
        // Copy the original PDF to the signed directory
        $originalPath = $document->file_path;
        $signedPath = $signedDir . '/' . pathinfo($originalPath, PATHINFO_FILENAME) . '_signed.pdf';
        
        Storage::copy($originalPath, $signedPath);
        
        // Update document with the signed file path
        $document->update([
            'signed_file_path' => $signedPath,
        ]);
        
        return $signedPath;
    }
}
