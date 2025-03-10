<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Completed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4f46e5;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f9fafb;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #6b7280;
        }
        .button {
            display: inline-block;
            background-color: #4f46e5;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        h1 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Document Completed</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            
            <p>We're pleased to inform you that the document <strong>{{ $document->title }}</strong> has been successfully completed with all required signatures.</p>
            
            <p>Document Details:</p>
            <ul>
                <li><strong>Title:</strong> {{ $document->title }}</li>
                <li><strong>Completed On:</strong> {{ $document->updated_at->format('F j, Y, g:i a') }}</li>
                <li><strong>Total Signers:</strong> {{ $document->signers->count() }}</li>
            </ul>
            
            <p>The signed document and audit trail are attached to this email for your records. You can also access them through your OpenSign account.</p>
            
            @if($document->getSignedDocumentUrl())
            <p>
                <a href="{{ $document->getSignedDocumentUrl() }}" class="button">View Signed Document</a>
            </p>
            @endif
            
            <p>Thank you for using OpenSign for your document signing needs.</p>
            
            <p>Best regards,<br>The OpenSign Team</p>
        </div>
        <div class="footer">
            <p>This is an automated message from OpenSign. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} OpenSign. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
