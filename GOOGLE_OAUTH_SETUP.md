# Google OAuth Implementation Guide

## Overview
Google OAuth has been successfully integrated into your Symfony application. This guide explains what has been implemented and how to set it up.

## What Has Been Implemented

### 1. **Database Changes**
- Added two new fields to the `User` entity:
  - `google_oauth_id`: Stores the unique Google ID
  - `oauth_provider`: Stores the OAuth provider name (e.g., 'google')

### 2. **Backend Components**
- **GoogleOAuthAuthenticator** (`src/Security/GoogleOAuthAuthenticator.php`): Handles OAuth authentication flow
- **GoogleOAuthController** (`src/Controller/GoogleOAuthController.php`): Handles OAuth routes
  - `/oauth/google`: Initiates Google OAuth redirect
  - `/oauth/google/callback`: Handles Google callback

### 3. **Frontend Components**
- **Login template** (`templates/security/login.html.twig`): Added Google OAuth button
- **Registration template** (`templates/registration/register.html.twig`): Added Google OAuth button
- Both templates include an styled Google icon and sign-in button

### 4. **Configuration**
- Updated `config/packages/security.yaml` to include the OAuth authenticator
- Updated `config/services.yaml` with OAuth service configuration
- Added environment variables to `.env`

## Setup Instructions

### Step 1: Get Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the OAuth 2.0 API
4. Create OAuth 2.0 credentials (OAuth 2.0 Client ID)
   - Choose "Web application"
   - Add authorized redirect URIs:
     - `http://localhost/oauth/google/callback` (development)
     - `https://yourdomain.com/oauth/google/callback` (production)

5. Copy the Client ID and Client Secret

### Step 2: Configure Environment Variables

Open `.env` and update the Google OAuth credentials:

```env
GOOGLE_OAUTH_CLIENT_ID=your_client_id_here
GOOGLE_OAUTH_CLIENT_SECRET=your_client_secret_here
GOOGLE_OAUTH_REDIRECT_URL=http://localhost/oauth/google/callback
```

For development, use `http://localhost/oauth/google/callback`
For production, update to your actual domain: `https://yourdomain.com/oauth/google/callback`

### Step 3: Run Database Migration

Execute the migration to add OAuth fields to the User table:

```bash
bin/console doctrine:migrations:migrate
```

### Step 4: Test the Implementation

1. Clear the Symfony cache:
```bash
bin/console cache:clear
```

2. Start your Symfony server:
```bash
symfony server:start
```

3. Navigate to the login or registration page and click "Sign in with Google" or "Sign up with Google"

## How It Works

### Authentication Flow

1. User clicks "Sign in with Google" button
2. Redirected to `/oauth/google` route
3. User is redirected to Google's authorization page
4. User grants permission
5. Google redirects back to `/oauth/google/callback` with authorization code
6. OAuth authenticator exchanges code for access token
7. User information is fetched from Google
8. System checks if user exists:
   - If user with Google ID exists: Log them in
   - If user with email exists: Link Google account
   - If new user: Create account with Google info
9. User is logged in and redirected to home page

### Key Features

- **Automatic Account Linking**: If a user registers with email first, then tries OAuth with the same email, the accounts are automatically linked
- **Session Management**: Uses standard Symfony security system
- **Password Less Auth**: OAuth users don't need a password (random one is generated)
- **User Data**: First name, last name, and email are populated from Google profile

## Security Considerations

1. **CSRF Protection**: The authenticator includes state parameter validation
2. **HTTPS**: Always use HTTPS in production (required by Google)
3. **Secrets Management**: Never commit `.env.local` with real credentials
4. **Scope**: Only requests 'email' and 'profile' scopes (minimal permissions)

## Troubleshooting

### Error: "Could not find a matching version of package symfony/oauth2-client"
- This package doesn't exist; use `league/oauth2-google` instead (already installed)

### Error: "Invalid redirect_uri"
- Make sure the redirect URL in `.env` matches exactly with what's configured in Google Cloud Console
- Include the protocol (http:// or https://)

### User can't log in after OAuth
1. Check that `GOOGLE_OAUTH_CLIENT_ID` and `GOOGLE_OAUTH_CLIENT_SECRET` are correct
2. Verify the redirect URL matches Google Console settings
3. Check Symfony logs: `var/log/dev.log`

### Migrations Failed
- Ensure database is running and accessible
- The migration file is `migrations/Version20260221000000.php`

## API Reference

### GoogleOAuthAuthenticator

```php
// Constructor parameters:
- UserRepository $userRepository
- EntityManagerInterface $entityManager
- string $googleClientId
- string $googleClientSecret
- string $redirectUrl
```

### User Entity OAuth Methods

```php
$user->getGoogleOAuthId(): ?string
$user->setGoogleOAuthId(?string $googleOAuthId): static

$user->getOauthProvider(): ?string
$user->setOauthProvider(?string $oauthProvider): static
```

## Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `GOOGLE_OAUTH_CLIENT_ID` | Google OAuth Client ID | `123456789.apps.googleusercontent.com` |
| `GOOGLE_OAUTH_CLIENT_SECRET` | Google OAuth Client Secret | `GOCSPX-abc123...` |
| `GOOGLE_OAUTH_REDIRECT_URL` | Callback URL | `http://localhost/oauth/google/callback` |

## Files Modified/Created

### New Files:
- `src/Security/GoogleOAuthAuthenticator.php`
- `src/Controller/GoogleOAuthController.php`
- `migrations/Version20260221000000.php`

### Modified Files:
- `src/Entity/User.php`
- `config/packages/security.yaml`
- `config/services.yaml`
- `.env`
- `templates/security/login.html.twig`
- `templates/registration/register.html.twig`

## Next Steps (Optional)

1. **Add More OAuth Providers**: Use the same pattern to add Facebook, GitHub, etc.
2. **Social Linking**: Allow users to link multiple OAuth accounts
3. **User Profile**: Enhance user profile with OAuth provider information
4. **Two-Factor Auth**: You already have 2FA setup; consider making it optional for OAuth users

## Support

For issues with Google OAuth implementation, check:
1. Google Cloud Console credentials
2. Redirect URLs configuration
3. Symfony logs: `var/log/dev.log`
4. Browser console for JavaScript errors

---

Implementation Date: February 21, 2026
Symfony Version: 7.4
PHP Version: 8.2+
