# Laravel ngrok + QR Code Demo Setup

## Quick Start

1. **Install ngrok** (one-time setup):
   - Download: https://ngrok.com/download
   - Or use: `choco install ngrok` (if you have Chocolatey)
   - Or use: `scoop install ngrok` (if you have Scoop)

2. **Verify ngrok is installed**:
   ```powershell
   ngrok version
   ```

3. **Run the demo script** from `laravel-backend` folder:
   ```
   Double-click: start-with-qr.bat
   ```

## What Happens

1. ✅ Laravel starts on `localhost:8000`
2. ✅ ngrok creates a public tunnel (e.g., `https://abc123.ngrok.io`)
3. ✅ QR code automatically opens in your browser
4. ✅ Your phone can scan the QR code and connect!

## How to Demo on Your Phone

### Option A: Scan QR Code
1. Run `start-with-qr.bat`
2. QR code will open in your browser
3. On your phone, open **Camera app** and point at the QR code
4. Tap the notification to open the link
5. Your app will now connect to your Laravel backend! 🎉

### Option B: Manually Enter URL
1. In your app's API configuration, enter the ngrok URL (shown in terminal)
2. Example: `https://abc123.ngrok.io`

## Configuration in Your Mobile App

You need to update your API base URL in your Angular/Ionic app:

**In your mobile app's environment file:**
```typescript
// For local development
export const apiUrl = 'http://192.168.1.x:8000';  // Local network

// For ngrok tunnel (production demo)
export const apiUrl = 'https://abc123.ngrok.io';   // Shown in terminal
```

Or make it **dynamic** to switch easily:
```typescript
// Ask user or read from QR code
const NGROK_URL = prompt('Enter ngrok URL:');
export const apiUrl = NGROK_URL || 'http://localhost:8000';
```

## Important Notes

⚠️ **ngrok URL changes every time you restart**
- The long URL part changes (abc123) but the process is the same
- Just scan the new QR code each time

⚠️ **Free ngrok has bandwidth limits**
- Good for demos and testing
- Upgrade if you need production use

✅ **Your Laravel .env is automatically used**
- Credentials, database, email settings all work normally

## Troubleshooting

**"ngrok is not installed"**
→ Follow installation steps above

**"Could not connect to backend"**
→ Make sure you updated your app's API URL to the ngrok URL
→ Check your Laravel is running (see terminal)

**"QR code didn't open"**
→ Manually open: `https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=https://your-ngrok-url`

## What's Included

- `start-with-qr.bat` → Easy click-to-start launcher
- `start-with-qr.ps1` → The actual script (handles everything)

Enjoy demoing! 🚀
