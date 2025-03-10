# OpenSign

OpenSign is a digital document signing application built with Laravel. It allows users to upload, sign, and manage PDF documents securely.

## Features

- Google OAuth authentication
- PDF document upload and management
- Digital signature capabilities
- Document sharing and collaboration

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/opensign.git
   cd opensign
   ```

2. Install dependencies:
   ```
   composer install
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
   ```

5. Run migrations:
   ```
   php artisan migrate
   ```

6. Start the development server:
   ```
   php artisan serve
   ```

## Dependencies

- Laravel 12.x
- laravel/socialite - For Google OAuth authentication
- setasign/fpdi - For PDF manipulation

## License

The OpenSign application is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
