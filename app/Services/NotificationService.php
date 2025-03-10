<?php

namespace App\Services;

use App\Mail\DocumentCompleted;
use App\Mail\SignerInvitation;
use App\Mail\SigningCompleted;
use App\Mail\SigningRejected;
use App\Models\Document;
use App\Models\Signer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                'last_reminded_at' => now(),
            ]);
            
            // Log the invitation in audit log
            $signer->auditLogs()->create([
                'action' => 'invited',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                ]),
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
        try {
            Mail::to($document->user->email)->send(new SigningCompleted($signer, $document));
            
            // Log the notification in audit log
            $document->auditLogs()->create([
                'action' => 'notification_sent',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'notification_type' => 'signing_completed',
                    'signer_id' => $signer->id,
                    'signer_name' => $signer->name,
                    'signer_email' => $signer->email,
                ]),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send signing completed notification', [
                'signer_id' => $signer->id,
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
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
        try {
            Mail::to($document->user->email)->send(new SigningRejected($signer, $document, $reason));
            
            // Log the notification in audit log
            $document->auditLogs()->create([
                'action' => 'notification_sent',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'notification_type' => 'signing_rejected',
                    'signer_id' => $signer->id,
                    'signer_name' => $signer->name,
                    'signer_email' => $signer->email,
                    'reason' => $reason,
                ]),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send signing rejected notification', [
                'signer_id' => $signer->id,
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Send notification to document owner and all signers when the document is completed.
     *
     * @param \App\Models\Document $document
     * @param string|null $signedPdfPath
     * @param string|null $auditPdfPath
     * @return bool
     */
    public function sendDocumentCompletedNotification(Document $document, ?string $signedPdfPath = null, ?string $auditPdfPath = null)
    {
        try {
            // Get the document owner and all signers
            $recipients = collect([$document->user->email]);
            $document->signers->each(function ($signer) use ($recipients) {
                $recipients->push($signer->email);
            });
            
            // Remove duplicates
            $recipients = $recipients->unique()->values()->all();
            
            // Prepare attachments
            $attachments = [];
            
            if ($signedPdfPath && Storage::exists($signedPdfPath)) {
                $attachments['signed_pdf'] = [
                    'path' => Storage::path($signedPdfPath),
                    'name' => $document->title . ' (Signed).pdf',
                ];
            }
            
            if ($auditPdfPath && Storage::exists($auditPdfPath)) {
                $attachments['audit_pdf'] = [
                    'path' => Storage::path($auditPdfPath),
                    'name' => $document->title . ' (Audit Trail).pdf',
                ];
            }
            
            // Send email to all recipients
            Mail::to($recipients)->send(new DocumentCompleted($document, $attachments));
            
            // Log the notification in audit log
            $document->auditLogs()->create([
                'action' => 'notification_sent',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'notification_type' => 'document_completed',
                    'recipients' => $recipients,
                    'attachments' => array_keys($attachments),
                ]),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send document completed notification', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Send a reminder email to a signer who has not signed a document.
     *
     * @param \App\Models\Signer $signer
     * @param \App\Models\Document $document
     * @return array Result of email sending attempt
     */
    public function sendSignerReminder(Signer $signer, Document $document)
    {
        try {
            Mail::to($signer->email)->send(new SignerInvitation($signer, $document, true));
            
            return [
                'success' => true,
                'signer_id' => $signer->id,
                'email' => $signer->email,
                'message' => 'Reminder sent successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send reminder email', [
                'signer_id' => $signer->id,
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'signer_id' => $signer->id,
                'email' => $signer->email,
                'message' => 'Failed to send reminder: ' . $e->getMessage()
            ];
        }
    }
}
