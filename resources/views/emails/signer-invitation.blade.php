<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document Signing Request</title>
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
            background-color: #4F46E5;
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
            background-color: #4F46E5;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 4px;
            margin: 20px 0;
            font-weight: bold;
        }
        .info {
            background-color: #e0f2fe;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
        }
        .reminder {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>OpenSign</h1>
            <p>Secure Document Signing</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $signer->name }},</h2>
            
            @if($isReminder)
            <div class="reminder">
                <h3>⚠️ Reminder: Your signature is still needed</h3>
                <p>This is a friendly reminder that you have a pending document that requires your signature. Please take a moment to review and sign the document at your earliest convenience.</p>
            </div>
            @endif
            
            <p>You have been invited by {{ $document->user->name }} to sign a document:</p>
            
            <h3>{{ $document->title }}</h3>
            
            @if($document->description)
            <p><strong>Description:</strong> {{ $document->description }}</p>
            @endif
            
            <p>Please click the button below to review and sign the document:</p>
            
            <div style="text-align: center;">
                <a href="{{ $signingUrl }}" class="button">Review & Sign Document</a>
            </div>
            
            <div class="info">
                <p><strong>Note:</strong> This link is unique to you and should not be shared with others. The link will expire once the document has been signed by all parties.</p>
            </div>
            
            <p>If you have any questions about this document, please contact the sender directly.</p>
            
            <p>Thank you,<br>
            The OpenSign Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email from OpenSign. Please do not reply to this message.</p>
            <p>&copy; {{ date('Y') }} OpenSign. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
