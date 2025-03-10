/**
 * field-drag.js - Handles drag and drop functionality for signature fields
 * For OpenSign document signing application
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const fieldItems = document.querySelectorAll('.field-item');
    const fieldOverlay = document.getElementById('field-overlay');
    const pdfViewer = document.getElementById('pdf-viewer');
    const pdfContainer = document.querySelector('.pdf-container');
    const fieldPositionsInput = document.getElementById('field-positions');
    const saveFieldsButton = document.getElementById('save-fields');
    
    // State
    let placedFields = [];
    let currentPage = 1; // Default to first page
    let isDragging = false;
    let draggedElement = null;
    let offsetX, offsetY;
    let fieldIdCounter = 1;
    
    // Colors for different field types
    const fieldColors = {
        'signature': 'rgba(59, 130, 246, 0.2)', // blue
        'initial': 'rgba(16, 185, 129, 0.2)',   // green
        'date': 'rgba(139, 92, 246, 0.2)',      // purple
        'checkbox': 'rgba(245, 158, 11, 0.2)'   // yellow
    };
    
    // Initialize draggable field items
    fieldItems.forEach(item => {
        item.addEventListener('mousedown', startDrag);
        item.addEventListener('touchstart', startDrag, { passive: false });
    });
    
    // Make the overlay accept dropped elements
    fieldOverlay.addEventListener('mouseup', dropField);
    fieldOverlay.addEventListener('touchend', dropField);
    
    // Track mouse movement for dragging
    document.addEventListener('mousemove', dragField);
    document.addEventListener('touchmove', dragField, { passive: false });
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchend', endDrag);
    
    // Save field positions
    saveFieldsButton.addEventListener('click', saveFieldPositions);
    
    // Load existing fields if available
    loadExistingFields();
    
    // Listen for PDF page changes (if PDF viewer supports this)
    if (pdfViewer.contentWindow) {
        pdfViewer.addEventListener('load', function() {
            try {
                // This might not work in all browsers due to same-origin policy
                pdfViewer.contentWindow.addEventListener('pagechange', function(e) {
                    currentPage = e.pageNumber;
                });
            } catch (e) {
                console.warn('Could not add pagechange listener to PDF viewer:', e);
            }
        });
    }
    
    /**
     * Start dragging a field
     */
    function startDrag(e) {
        e.preventDefault();
        
        // Determine if this is a new field or existing one
        const isNewField = this.classList.contains('field-item');
        
        if (isNewField) {
            // Create a new draggable field
            const fieldType = this.getAttribute('data-field-type');
            draggedElement = createDraggableField(fieldType);
            document.body.appendChild(draggedElement);
        } else {
            // We're moving an existing field
            draggedElement = this;
        }
        
        isDragging = true;
        
        // Calculate offset for smooth dragging
        const clientX = e.clientX || (e.touches && e.touches[0].clientX);
        const clientY = e.clientY || (e.touches && e.touches[0].clientY);
        
        const rect = draggedElement.getBoundingClientRect();
        offsetX = clientX - rect.left;
        offsetY = clientY - rect.top;
        
        // Set initial position
        positionElement(e);
        
        // Add active dragging class
        draggedElement.classList.add('dragging');
    }
    
    /**
     * Drag the field with mouse/touch movement
     */
    function dragField(e) {
        if (!isDragging || !draggedElement) return;
        
        e.preventDefault();
        positionElement(e);
    }
    
    /**
     * Position the dragged element at cursor/touch position
     */
    function positionElement(e) {
        const clientX = e.clientX || (e.touches && e.touches[0].clientX);
        const clientY = e.clientY || (e.touches && e.touches[0].clientY);
        
        if (clientX && clientY) {
            draggedElement.style.left = (clientX - offsetX) + 'px';
            draggedElement.style.top = (clientY - offsetY) + 'px';
        }
    }
    
    /**
     * End dragging when mouse/touch is released
     */
    function endDrag(e) {
        if (!isDragging) return;
        
        // If we're not over the PDF container, remove the element
        if (!isOverPdfContainer(e) && draggedElement.parentNode === document.body) {
            document.body.removeChild(draggedElement);
        }
        
        isDragging = false;
        draggedElement.classList.remove('dragging');
        draggedElement = null;
    }
    
    /**
     * Drop a field onto the PDF overlay
     */
    function dropField(e) {
        if (!isDragging || !draggedElement) return;
        
        e.preventDefault();
        
        // Check if we're over the PDF container
        if (isOverPdfContainer(e)) {
            // Get position relative to the PDF container
            const containerRect = pdfContainer.getBoundingClientRect();
            const clientX = e.clientX || (e.changedTouches && e.changedTouches[0].clientX);
            const clientY = e.clientY || (e.changedTouches && e.changedTouches[0].clientY);
            
            const relativeX = clientX - containerRect.left;
            const relativeY = clientY - containerRect.top;
            
            // If this is a new field (from palette)
            if (draggedElement.parentNode === document.body) {
                // Remove from body
                document.body.removeChild(draggedElement);
                
                // Create a new field in the overlay
                const fieldType = draggedElement.getAttribute('data-field-type');
                const newField = createDraggableField(fieldType);
                
                // Position the new field
                newField.style.position = 'absolute';
                newField.style.left = (relativeX - offsetX) + 'px';
                newField.style.top = (relativeY - offsetY) + 'px';
                
                // Add to overlay
                fieldOverlay.appendChild(newField);
                
                // Make the new field draggable
                newField.addEventListener('mousedown', startDrag);
                newField.addEventListener('touchstart', startDrag, { passive: false });
                
                // Add delete button
                addDeleteButton(newField);
                
                // Add to placed fields array
                placedFields.push({
                    id: newField.id,
                    type: fieldType,
                    page: currentPage,
                    x: parseFloat(newField.style.left),
                    y: parseFloat(newField.style.top),
                    width: newField.offsetWidth,
                    height: newField.offsetHeight
                });
            } else {
                // Update position of existing field
                draggedElement.style.left = (relativeX - offsetX) + 'px';
                draggedElement.style.top = (relativeY - offsetY) + 'px';
                
                // Update in placed fields array
                const fieldId = draggedElement.id;
                const fieldIndex = placedFields.findIndex(f => f.id === fieldId);
                
                if (fieldIndex !== -1) {
                    placedFields[fieldIndex].x = parseFloat(draggedElement.style.left);
                    placedFields[fieldIndex].y = parseFloat(draggedElement.style.top);
                    placedFields[fieldIndex].page = currentPage;
                }
            }
            
            // Update the hidden input with field positions
            updateFieldPositionsInput();
        }
        
        // End the drag operation
        endDrag(e);
    }
    
    /**
     * Check if the event is over the PDF container
     */
    function isOverPdfContainer(e) {
        const containerRect = pdfContainer.getBoundingClientRect();
        const clientX = e.clientX || (e.changedTouches && e.changedTouches[0].clientX);
        const clientY = e.clientY || (e.changedTouches && e.changedTouches[0].clientY);
        
        return (
            clientX >= containerRect.left &&
            clientX <= containerRect.right &&
            clientY >= containerRect.top &&
            clientY <= containerRect.bottom
        );
    }
    
    /**
     * Create a new draggable field element
     */
    function createDraggableField(fieldType) {
        const field = document.createElement('div');
        field.id = 'field-' + fieldIdCounter++;
        field.className = 'draggable-field';
        field.setAttribute('data-field-type', fieldType);
        
        // Style based on field type
        field.style.position = 'absolute';
        field.style.backgroundColor = fieldColors[fieldType];
        field.style.border = '2px dashed ' + fieldColors[fieldType].replace('0.2', '0.6');
        field.style.borderRadius = '4px';
        field.style.padding = '10px';
        field.style.cursor = 'move';
        field.style.zIndex = '1000';
        field.style.pointerEvents = 'auto';
        
        // Set dimensions based on field type
        if (fieldType === 'signature') {
            field.style.width = '200px';
            field.style.height = '60px';
        } else if (fieldType === 'initial') {
            field.style.width = '100px';
            field.style.height = '60px';
        } else if (fieldType === 'date') {
            field.style.width = '150px';
            field.style.height = '40px';
        } else if (fieldType === 'checkbox') {
            field.style.width = '40px';
            field.style.height = '40px';
        }
        
        // Add field type label
        const label = document.createElement('div');
        label.className = 'field-label';
        label.textContent = fieldType.charAt(0).toUpperCase() + fieldType.slice(1);
        label.style.fontSize = '12px';
        label.style.textAlign = 'center';
        label.style.fontWeight = 'bold';
        label.style.textTransform = 'capitalize';
        field.appendChild(label);
        
        return field;
    }
    
    /**
     * Add a delete button to a field
     */
    function addDeleteButton(field) {
        const deleteBtn = document.createElement('div');
        deleteBtn.className = 'delete-field';
        deleteBtn.innerHTML = '&times;';
        deleteBtn.style.position = 'absolute';
        deleteBtn.style.top = '-10px';
        deleteBtn.style.right = '-10px';
        deleteBtn.style.width = '20px';
        deleteBtn.style.height = '20px';
        deleteBtn.style.borderRadius = '50%';
        deleteBtn.style.backgroundColor = '#ff4d4d';
        deleteBtn.style.color = 'white';
        deleteBtn.style.textAlign = 'center';
        deleteBtn.style.lineHeight = '18px';
        deleteBtn.style.fontWeight = 'bold';
        deleteBtn.style.cursor = 'pointer';
        deleteBtn.style.zIndex = '1001';
        
        deleteBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Remove from DOM
            field.parentNode.removeChild(field);
            
            // Remove from placed fields array
            const fieldIndex = placedFields.findIndex(f => f.id === field.id);
            if (fieldIndex !== -1) {
                placedFields.splice(fieldIndex, 1);
                updateFieldPositionsInput();
            }
        });
        
        field.appendChild(deleteBtn);
    }
    
    /**
     * Update the hidden input with field positions
     */
    function updateFieldPositionsInput() {
        fieldPositionsInput.value = JSON.stringify(placedFields);
    }
    
    /**
     * Load existing fields from the server
     */
    function loadExistingFields() {
        // Check if there are existing fields in the signatureFields variable
        if (typeof signatureFields !== 'undefined' && signatureFields.length > 0) {
            signatureFields.forEach(field => {
                const newField = createDraggableField(field.type);
                
                // Position the field
                newField.style.position = 'absolute';
                newField.style.left = field.x_position + 'px';
                newField.style.top = field.y_position + 'px';
                newField.style.width = field.width + 'px';
                newField.style.height = field.height + 'px';
                
                // Add to overlay
                fieldOverlay.appendChild(newField);
                
                // Make the field draggable
                newField.addEventListener('mousedown', startDrag);
                newField.addEventListener('touchstart', startDrag, { passive: false });
                
                // Add delete button
                addDeleteButton(newField);
                
                // Add to placed fields array
                placedFields.push({
                    id: newField.id,
                    type: field.type,
                    page: field.page,
                    x: field.x_position,
                    y: field.y_position,
                    width: field.width,
                    height: field.height
                });
            });
            
            // Update the hidden input
            updateFieldPositionsInput();
        }
    }
    
    /**
     * Save field positions to the server
     */
    function saveFieldPositions() {
        // Get the document ID from the URL
        const pathParts = window.location.pathname.split('/');
        const documentId = pathParts[pathParts.length - 1];
        
        // Show loading state
        saveFieldsButton.disabled = true;
        saveFieldsButton.textContent = 'Saving...';
        saveFieldsButton.classList.add('opacity-75');
        
        // Create form data
        const formData = new FormData();
        formData.append('field_positions', fieldPositionsInput.value);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        // Send to server via AJAX
        fetch(`/documents/${documentId}/fields`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Reset button state
            saveFieldsButton.disabled = false;
            saveFieldsButton.textContent = 'Save Fields';
            saveFieldsButton.classList.remove('opacity-75');
            
            if (data.success) {
                // Show success message
                const successMessage = document.createElement('div');
                successMessage.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50';
                successMessage.innerHTML = `
                    <div class="flex">
                        <div class="py-1"><svg class="h-6 w-6 text-green-500 mr-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg></div>
                        <div>
                            <p class="font-bold">Success!</p>
                            <p class="text-sm">${data.message}</p>
                        </div>
                    </div>
                `;
                document.body.appendChild(successMessage);
                
                // Remove after 3 seconds
                setTimeout(() => {
                    successMessage.remove();
                }, 3000);
                
                // Reload page if status changed
                if (data.status_changed) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                // Show error message
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Reset button state
            saveFieldsButton.disabled = false;
            saveFieldsButton.textContent = 'Save Fields';
            saveFieldsButton.classList.remove('opacity-75');
            
            // Show error message
            alert('An error occurred while saving fields. Please try again.');
        });
    }
});
