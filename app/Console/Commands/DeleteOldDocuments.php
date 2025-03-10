<?php

namespace App\Console\Commands;

use App\Models\Document;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteOldDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opensign:delete-old-documents {--days=7 : Number of days after which to delete documents} {--dry-run : Run without actually deleting anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete documents older than the specified number of days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Starting to process documents older than {$days} days...");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE: No documents will be deleted.");
        }
        
        // Get the cutoff date
        $cutoffDate = Carbon::now()->subDays($days);
        
        // Get documents created before the cutoff date
        $oldDocuments = Document::where('created_at', '<', $cutoffDate)
            ->where(function ($query) {
                $query->where('status', 'completed')
                    ->orWhere('status', 'declined')
                    ->orWhere('status', 'cancelled');
            })
            ->get();
        
        $this->info("Found {$oldDocuments->count()} documents to process.");
        
        $deletedCount = 0;
        $errorCount = 0;
        
        foreach ($oldDocuments as $document) {
            try {
                $this->info("Processing document: {$document->title} (ID: {$document->id})");
                
                // Get file paths to delete
                $filesToDelete = [
                    $document->file_path,
                    $document->signed_file_path,
                    $document->audit_trail_path,
                ];
                
                if (!$dryRun) {
                    // Delete files from storage
                    foreach ($filesToDelete as $filePath) {
                        if ($filePath && Storage::exists($filePath)) {
                            Storage::delete($filePath);
                            $this->info("Deleted file: {$filePath}");
                        }
                    }
                    
                    // Delete related records
                    // This will cascade delete signers, signatures, and audit logs
                    // due to foreign key constraints
                    $document->delete();
                    
                    $deletedCount++;
                    $this->info("Deleted document: {$document->title} (ID: {$document->id})");
                } else {
                    $this->warn("Would delete document: {$document->title} (ID: {$document->id})");
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error deleting document {$document->id}: {$e->getMessage()}");
                Log::error("Error deleting document", [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $this->info("Document cleanup completed. Processed {$deletedCount} documents with {$errorCount} errors.");
        
        return 0;
    }
}
