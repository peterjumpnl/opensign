<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Signer;
use App\Models\Signature;
use App\Models\SignatureField;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class PdfService
{
    /**
     * Flatten signatures onto the PDF document.
     *
     * @param int $documentId
     * @return string|null The path to the flattened PDF or null if an error occurred
     */
    public function flattenSignatures(int $documentId): ?string
    {
        try {
            // Get the document
            $document = Document::findOrFail($documentId);
            
            // Get all signatures for this document
            $signatures = Signature::whereHas('field', function ($query) use ($documentId) {
                $query->where('document_id', $documentId);
            })->with(['signer', 'field'])->get();
            
            if ($signatures->isEmpty()) {
                Log::warning("No signatures found for document ID: {$documentId}");
                return null;
            }
            
            // Get the original PDF path
            $originalPath = $document->file_path;
            if (!Storage::exists($originalPath)) {
                Log::error("Original PDF not found at path: {$originalPath}");
                return null;
            }
            
            // Create the directory for signed documents if it doesn't exist
            $signedDir = 'docs/signed';
            if (!Storage::exists($signedDir)) {
                Storage::makeDirectory($signedDir);
            }
            
            // Define the output path for the signed PDF
            $signedPath = $signedDir . '/' . pathinfo($originalPath, PATHINFO_FILENAME) . '_signed.pdf';
            
            // Create a temporary file to work with
            $tempOriginalPath = sys_get_temp_dir() . '/' . uniqid('original_') . '.pdf';
            $tempSignedPath = sys_get_temp_dir() . '/' . uniqid('signed_') . '.pdf';
            
            // Copy the original PDF to the temp directory
            file_put_contents($tempOriginalPath, Storage::get($originalPath));
            
            // Initialize FPDI
            $pdf = new Fpdi();
            
            // Get the number of pages in the original PDF
            $pageCount = $pdf->setSourceFile($tempOriginalPath);
            
            // Process each page
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                // Import the page
                $templateId = $pdf->importPage($pageNo);
                
                // Get the page dimensions
                $size = $pdf->getTemplateSize($templateId);
                
                // Add the page with the same orientation as the original
                if ($size['width'] > $size['height']) {
                    $pdf->AddPage('L', [$size['width'], $size['height']]);
                } else {
                    $pdf->AddPage('P', [$size['width'], $size['height']]);
                }
                
                // Use the imported page as a template
                $pdf->useTemplate($templateId);
                
                // Add signatures to this page
                $this->addSignaturesToPage($pdf, $signatures, $pageNo, $size);
            }
            
            // Save the PDF to the temp file
            $pdf->Output('F', $tempSignedPath);
            
            // Move the temp file to the storage
            Storage::put($signedPath, file_get_contents($tempSignedPath));
            
            // Clean up temp files
            @unlink($tempOriginalPath);
            @unlink($tempSignedPath);
            
            // Update the document with the signed file path
            $document->update([
                'signed_file_path' => $signedPath,
                'status' => 'completed'
            ]);
            
            return $signedPath;
        } catch (\Exception $e) {
            Log::error("Error flattening signatures: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Add signatures to a specific page of the PDF.
     *
     * @param Fpdi $pdf
     * @param \Illuminate\Support\Collection $signatures
     * @param int $pageNo
     * @param array $pageSize
     * @return void
     */
    private function addSignaturesToPage(Fpdi $pdf, $signatures, int $pageNo, array $pageSize): void
    {
        foreach ($signatures as $signature) {
            $field = $signature->field;
            
            // Skip if the field is not on this page
            if ($field->page_number != $pageNo) {
                continue;
            }
            
            // Skip if the signature value is empty
            if (empty($signature->value)) {
                continue;
            }
            
            // For data URI signatures (drawn or typed)
            if (strpos($signature->value, 'data:image') === 0) {
                $this->addDataUriSignature($pdf, $signature, $field, $pageSize);
            } 
            // For text-based signatures
            else {
                $this->addTextSignature($pdf, $signature, $field, $pageSize);
            }
        }
    }
    
    /**
     * Add a data URI signature to the PDF.
     *
     * @param Fpdi $pdf
     * @param Signature $signature
     * @param SignatureField $field
     * @param array $pageSize
     * @return void
     */
    private function addDataUriSignature(Fpdi $pdf, Signature $signature, SignatureField $field, array $pageSize): void
    {
        try {
            // Extract the image data from the data URI
            $dataUri = $signature->value;
            $imageData = substr($dataUri, strpos($dataUri, ',') + 1);
            $decodedImage = base64_decode($imageData);
            
            // Create a temporary file for the image
            $tempImagePath = sys_get_temp_dir() . '/' . uniqid('sig_') . '.png';
            file_put_contents($tempImagePath, $decodedImage);
            
            // Calculate position and size
            $x = $field->x_position;
            $y = $field->y_position;
            $width = $field->width;
            $height = $field->height;
            
            // Add the image to the PDF
            $pdf->Image($tempImagePath, $x, $y, $width, $height);
            
            // Clean up the temporary file
            @unlink($tempImagePath);
        } catch (\Exception $e) {
            Log::error("Error adding data URI signature: " . $e->getMessage());
        }
    }
    
    /**
     * Add a text signature to the PDF.
     *
     * @param Fpdi $pdf
     * @param Signature $signature
     * @param SignatureField $field
     * @param array $pageSize
     * @return void
     */
    private function addTextSignature(Fpdi $pdf, Signature $signature, SignatureField $field, array $pageSize): void
    {
        try {
            // Calculate position
            $x = $field->x_position;
            $y = $field->y_position + ($field->height / 2); // Center vertically
            
            // Set font
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->SetTextColor(0, 0, 0);
            
            // Add the text
            $pdf->SetXY($x, $y);
            $pdf->Write(0, $signature->value);
        } catch (\Exception $e) {
            Log::error("Error adding text signature: " . $e->getMessage());
        }
    }
    
    /**
     * Generate an audit trail PDF for a document.
     *
     * @param int $documentId
     * @return string|null The path to the audit trail PDF or null if an error occurred
     */
    public function generateAuditTrailPdf(int $documentId): ?string
    {
        try {
            // Get the document with related data
            $document = Document::with(['user', 'signers.auditLogs'])->findOrFail($documentId);
            
            // Create the directory for audit trails if it doesn't exist
            $auditDir = 'docs/audit';
            if (!Storage::exists($auditDir)) {
                Storage::makeDirectory($auditDir);
            }
            
            // Define the output path for the audit trail PDF
            $auditPath = $auditDir . '/' . pathinfo($document->file_path, PATHINFO_FILENAME) . '_audit.pdf';
            
            // Initialize FPDI
            $pdf = new \FPDF();
            $pdf->AddPage();
            
            // Set document title
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'Audit Trail: ' . $document->title, 0, 1, 'C');
            $pdf->Ln(10);
            
            // Document details
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Document Details', 0, 1, 'L');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(50, 8, 'Document ID:', 0, 0, 'L');
            $pdf->Cell(0, 8, $document->id, 0, 1, 'L');
            $pdf->Cell(50, 8, 'Title:', 0, 0, 'L');
            $pdf->Cell(0, 8, $document->title, 0, 1, 'L');
            $pdf->Cell(50, 8, 'Created By:', 0, 0, 'L');
            $pdf->Cell(0, 8, $document->user->name . ' (' . $document->user->email . ')', 0, 1, 'L');
            $pdf->Cell(50, 8, 'Created On:', 0, 0, 'L');
            $pdf->Cell(0, 8, $document->created_at->format('F j, Y, g:i a'), 0, 1, 'L');
            $pdf->Cell(50, 8, 'Completed On:', 0, 0, 'L');
            $pdf->Cell(0, 8, $document->updated_at->format('F j, Y, g:i a'), 0, 1, 'L');
            $pdf->Ln(10);
            
            // Signers section
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Signers', 0, 1, 'L');
            
            // Table header
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 8, 'Name', 1, 0, 'L');
            $pdf->Cell(60, 8, 'Email', 1, 0, 'L');
            $pdf->Cell(40, 8, 'Status', 1, 0, 'L');
            $pdf->Cell(40, 8, 'Signed On', 1, 1, 'L');
            
            // Table rows
            $pdf->SetFont('Arial', '', 10);
            foreach ($document->signers as $signer) {
                $pdf->Cell(60, 8, $signer->name, 1, 0, 'L');
                $pdf->Cell(60, 8, $signer->email, 1, 0, 'L');
                $pdf->Cell(40, 8, ucfirst($signer->status), 1, 0, 'L');
                $pdf->Cell(40, 8, $signer->signed_at ? $signer->signed_at->format('M j, Y, g:i a') : 'N/A', 1, 1, 'L');
            }
            $pdf->Ln(10);
            
            // Audit events section
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Audit Events', 0, 1, 'L');
            
            // Table header
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 8, 'Date & Time', 1, 0, 'L');
            $pdf->Cell(40, 8, 'Signer', 1, 0, 'L');
            $pdf->Cell(40, 8, 'Action', 1, 0, 'L');
            $pdf->Cell(40, 8, 'IP Address', 1, 0, 'L');
            $pdf->Cell(40, 8, 'User Agent', 1, 1, 'L');
            
            // Table rows
            $pdf->SetFont('Arial', '', 8);
            foreach ($document->signers as $signer) {
                foreach ($signer->auditLogs as $log) {
                    $pdf->Cell(40, 8, $log->created_at->format('M j, Y, g:i a'), 1, 0, 'L');
                    $pdf->Cell(40, 8, $signer->name, 1, 0, 'L');
                    $pdf->Cell(40, 8, ucfirst($log->action), 1, 0, 'L');
                    $pdf->Cell(40, 8, $log->ip_address, 1, 0, 'L');
                    
                    // Truncate user agent if too long
                    $userAgent = $log->user_agent;
                    if (strlen($userAgent) > 30) {
                        $userAgent = substr($userAgent, 0, 27) . '...';
                    }
                    $pdf->Cell(40, 8, $userAgent, 1, 1, 'L');
                }
            }
            
            // Save the PDF to storage
            $tempAuditPath = sys_get_temp_dir() . '/' . uniqid('audit_') . '.pdf';
            $pdf->Output('F', $tempAuditPath);
            Storage::put($auditPath, file_get_contents($tempAuditPath));
            
            // Clean up temp file
            @unlink($tempAuditPath);
            
            return $auditPath;
        } catch (\Exception $e) {
            Log::error("Error generating audit trail PDF: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return null;
        }
    }
}
