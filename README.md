# OpenSign

OpenSign is a modern digital document signing application built with Laravel. It allows users to upload, sign, and manage PDF documents securely with an intuitive interface.

## Features

- Google OAuth authentication
- PDF document upload and management
- Digital signature capabilities with drag-and-drop field placement
- Document sharing and collaboration
- Email notifications for document signing requests
- Automated reminders for pending signatures
- Document expiration and cleanup
- User profile management
- Clean, responsive UI design
- Direct signing links for easy sharing
- Multiple signer support with sequential signing order

## Implementation Details

### Authentication
- Google OAuth integration for seamless sign-in
- Secure session management

### Document Management
- Upload PDF documents
- Track document status (draft, pending, completed)
- Automatic document cleanup for expired documents
- Signed document storage and retrieval
- PDF preview with signature field positioning

### Signing Process
- Invite signers via email
- Position signature fields, initials, dates, and checkboxes on documents
- Track signing status and completion
- Email notifications when documents are completed
- Copy direct signing links for manual distribution
- Sequential signing workflow support

### User Interface
- Responsive design for all devices
- Light mode theme for optimal readability
- Intuitive document management dashboard
- Easy-to-use drag-and-drop signature field placement
- Status notifications with dismiss functionality
- Clean document viewing experience

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/opensign.git
   cd opensign
   ```

2. Install dependencies:
   ```
   composer install
   npm install
   ```

3. Configure environment variables:
   ```
   cp .env.example .env
   php artisan key:generate
   ```

4. Update the `.env` file with your database credentials and Google OAuth settings:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=opensign
   DB_USERNAME=your_db_username
   DB_PASSWORD=your_db_password

   GOOGLE_CLIENT_ID=your_google_client_id
   GOOGLE_CLIENT_SECRET=your_google_client_secret
   GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
   
   MAIL_MAILER=smtp
   MAIL_HOST=your_mail_host
   MAIL_PORT=your_mail_port
   MAIL_USERNAME=your_mail_username
   MAIL_PASSWORD=your_mail_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=your_from_email
   MAIL_FROM_NAME="${APP_NAME}"
   ```

5. Run migrations:
   ```
   php artisan migrate
   ```

6. Compile assets:
   ```
   npm run dev
   ```

7. Set up scheduled tasks for document management:
   ```
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

8. Start the development server:
   ```
   php artisan serve
   ```

## Usage

### Document Upload
1. Log in to your account
2. Navigate to the dashboard
3. Click "Upload New Document"
4. Fill in document details and upload your PDF

### Adding Signature Fields
1. Open a document
2. Drag and drop signature fields onto the document
3. Position fields precisely where signatures are needed
4. Save field positions

### Adding Signers
1. Navigate to the signers page for your document
2. Add signer details (name, email, signing order)
3. Send invitations or copy direct signing links
4. Track signing progress

### Signing Documents
1. Signers receive an email or direct link
2. They view the document and click on signature fields
3. They can draw or type their signature
4. Once all fields are completed, the document is finalized

## Dependencies

- Laravel 12.x
- Laravel Socialite - For Google OAuth authentication
- FPDI/FPDF - For PDF manipulation
- Alpine.js - For interactive UI components
- Tailwind CSS - For UI styling

## Recent Updates

- Added direct signing link copying functionality
- Fixed field positioning in the document editor
- Improved notification system with dismiss functionality
- Enhanced mobile responsiveness
- Optimized PDF rendering performance

## License

The OpenSign application is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
