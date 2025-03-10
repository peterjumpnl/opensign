<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\SignatureField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Display a listing of the user's documents.
     */
    public function index()
    {
        $documents = Document::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('documents.index', compact('documents'));
    }

    /**
     * Show the form for creating a new document.
     */
    public function create()
    {
        return view('documents.create');
    }

    /**
     * Store a newly uploaded document in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . Str::slug($request->title) . '.' . $file->getClientOriginalExtension();
            
            // Store file in the public disk under docs directory
            $filePath = $file->storeAs('docs', $fileName, 'public');
            
            // Create document record
            $document = Document::create([
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'user_id' => Auth::id(),
                'status' => 'draft',
            ]);
            
            return redirect()->route('documents.show', $document->id)
                ->with('success', 'Document uploaded successfully!');
        }
        
        return back()->with('error', 'Failed to upload document. Please try again.');
    }

    /**
     * Display the specified document.
     */
    public function show(Document $document)
    {
        // Check if the user owns this document
        if ($document->user_id !== Auth::id()) {
            return redirect()->route('documents.index')
                ->with('error', 'You do not have permission to view this document.');
        }
        
        // Get signers associated with this document
        $signers = $document->signers;
        
        // Get existing signature fields
        $signatureFields = $document->signatureFields;
        
        return view('documents.show', compact('document', 'signers', 'signatureFields'));
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Document $document)
    {
        // Check if the user owns this document
        if ($document->user_id !== Auth::id()) {
            return redirect()->route('documents.index')
                ->with('error', 'You do not have permission to delete this document.');
        }
        
        // Delete the file from storage
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
        
        // Delete the document record
        $document->delete();
        
        return redirect()->route('documents.index')
            ->with('success', 'Document deleted successfully!');
    }
    
    /**
     * Save signature fields for a document.
     */
    public function saveFields(Request $request, Document $document)
    {
        // Check if the user owns this document
        if ($document->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to modify this document.'
            ], 403);
        }
        
        $request->validate([
            'field_positions' => 'required|json',
        ]);
        
        try {
            // Decode the field positions JSON
            $fieldPositions = json_decode($request->field_positions, true);
            
            if (!is_array($fieldPositions)) {
                throw new \Exception('Invalid field positions data');
            }
            
            // Begin transaction
            \DB::beginTransaction();
            
            // Delete existing fields for this document
            SignatureField::where('document_id', $document->id)->delete();
            
            // Create new fields
            foreach ($fieldPositions as $field) {
                SignatureField::create([
                    'document_id' => $document->id,
                    'field_id' => $field['id'],
                    'type' => $field['type'],
                    'page' => $field['page'],
                    'x_position' => $field['x'],
                    'y_position' => $field['y'],
                    'width' => $field['width'],
                    'height' => $field['height'],
                ]);
            }
            
            // Update document status if needed
            if ($document->status === 'draft' && count($fieldPositions) > 0) {
                $document->update(['status' => 'awaiting_signatures']);
            }
            
            // Commit transaction
            \DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Signature fields saved successfully',
                'field_count' => count($fieldPositions)
            ]);
        } catch (\Exception $e) {
            // Rollback transaction
            \DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save signature fields: ' . $e->getMessage()
            ], 500);
        }
    }
}
