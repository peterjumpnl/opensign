<?php

namespace App\Services;

use App\Mail\SignerInvitation;
use App\Models\Document;
use App\Models\Signer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send invitation emails to all signers of a document.
     *
     * @param \App\Models\Document $document
     * @return array Results of email sending attempts
     */
    public function sendDocumentInvitations(Document $document)
    {
        $results = [];
        $signers = $document->signers()->orderBy('order_index')->get();
        
        foreach ($signers as $signer) {
            $results[] = $this->sendSignerInvitation($signer, $document);
        }
        
        return $results;
    }
    
    /**
     * Send invitation email to a specific signer.
     *
     * @param \App\Models\Signer $signer
     * @param \App\Models\Document $document
     * @return array Result of email sending attempt
     */
    public function sendSignerInvitation(Signer $signer, Document $document)
    {
        try {
            Mail::to($signer->email)->send(new SignerInvitation($signer, $document));
            
            // Update signer status to invited
            $signer->update([
                'status' => 'invited',
                'invited_at' => now(),
            ]);
            
            return [
                'success' => true,
                'signer_id' => $signer->id,
                'email' => $signer->email,
                'message' => 'Invitation sent successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send invitation email', [
                'signer_id' => $signer->id,
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'signer_id' => $signer->id,
                'email' => $signer->email,
                'message' => 'Failed to send invitation: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send notification to document owner when a signer completes signing.
     *
     * @param \App\Models\Signer $signer
     * @param \App\Models\Document $document
     * @return bool
     */
    public function sendSigningCompletedNotification(Signer $signer, Document $document)
    {
        // To be implemented in future steps
        return true;
    }
    
    /**
     * Send notification to document owner when a signer rejects the document.
     *
     * @param \App\Models\Signer $signer
     * @param \App\Models\Document $document
     * @param string $reason
     * @return bool
     */
    public function sendSigningRejectedNotification(Signer $signer, Document $document, $reason = null)
    {
        // To be implemented in future steps
        return true;
    }
    
    /**
     * Send notification to document owner when all signers have completed.
     *
     * @param \App\Models\Document $document
     * @return bool
     */
    public function sendDocumentCompletedNotification(Document $document)
    {
        // To be implemented in future steps
        return true;
    }
}
