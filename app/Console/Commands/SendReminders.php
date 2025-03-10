<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Signer;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensign:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to signers who have not signed documents for 3+ days';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Starting to send reminder emails...');
        
        // Get the cutoff date (3 days ago)
        $cutoffDate = Carbon::now()->subDays(3);
        
        // Get all pending documents
        $pendingDocuments = Document::where('status', 'pending')->get();
        
        $reminderCount = 0;
        $errorCount = 0;
        
        foreach ($pendingDocuments as $document) {
            // Get signers who have been invited but haven't signed yet
            $pendingSigners = $document->signers()
                ->where('status', '!=', 'signed')
                ->where('status', '!=', 'declined')
                ->where('invited_at', '<=', $cutoffDate)
                ->where(function ($query) use ($cutoffDate) {
                    $query->where('last_reminded_at', '<=', $cutoffDate)
                        ->orWhereNull('last_reminded_at');
                })
                ->get();
            
            foreach ($pendingSigners as $signer) {
                try {
                    // Send reminder email
                    $result = $notificationService->sendSignerReminder($signer, $document);
                    
                    if ($result['success']) {
                        $reminderCount++;
                        $this->info("Reminder sent to {$signer->email} for document {$document->title}");
                        
                        // Update last reminded timestamp
                        $signer->update([
                            'last_reminded_at' => Carbon::now(),
                        ]);
                        
                        // Log the reminder in audit log
                        $signer->auditLogs()->create([
                            'action' => 'reminder_sent',
                            'ip_address' => '127.0.0.1', // System generated
                            'user_agent' => 'OpenSign Scheduler',
                            'metadata' => json_encode([
                                'document_id' => $document->id,
                                'document_title' => $document->title,
                            ]),
                        ]);
                    } else {
                        $errorCount++;
                        $this->error("Failed to send reminder to {$signer->email}: {$result['message']}");
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("Error sending reminder to {$signer->email}: {$e->getMessage()}");
                    Log::error("Error sending reminder", [
                        'signer_id' => $signer->id,
                        'document_id' => $document->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
        $this->info("Reminder process completed. Sent {$reminderCount} reminders with {$errorCount} errors.");
        
        return 0;
    }
}
