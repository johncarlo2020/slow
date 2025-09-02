# Real-time Gallery Setup Instructions

## 1. Install Pusher PHP Library

Run this command in your project root directory:
```bash
composer install
```

## 2. Get Pusher Credentials

1. Go to https://pusher.com and create a free account
2. Create a new app
3. Get your credentials from the app dashboard:
   - App ID
   - Key
   - Secret
   - Cluster

## 3. Update Configuration

Edit `config/pusher.php` and replace the placeholder values with your actual Pusher credentials:

```php
return [
    'app_id' => 'YOUR_ACTUAL_APP_ID',
    'key' => 'YOUR_ACTUAL_APP_KEY',
    'secret' => 'YOUR_ACTUAL_APP_SECRET',
    'cluster' => 'YOUR_ACTUAL_CLUSTER',  // e.g., 'us2', 'eu', 'ap3'
    'useTLS' => true
];
```

## 4. Update JavaScript Configuration

Edit `js/video-gallery.js` and replace the Pusher initialization with your actual credentials:

```javascript
this.pusher = new Pusher('YOUR_ACTUAL_APP_KEY', {
    cluster: 'YOUR_ACTUAL_CLUSTER'
});
```

## 5. Test the Real-time Updates

1. Open the gallery page in one browser tab
2. Open the upload page in another tab
3. Upload and process a video
4. The gallery should automatically update with the new video and show a notification

## Features Implemented

- **Real-time Updates**: Gallery refreshes automatically when new videos are processed
- **Push Notifications**: Toast notifications appear when new videos are ready
- **No Polling**: Uses WebSocket connections for efficient real-time updates
- **Error Handling**: Graceful fallback if Pusher is not available

## Troubleshooting

- Check browser console for any JavaScript errors
- Check PHP error logs for Pusher connection issues
- Verify your Pusher credentials are correct
- Ensure the `vendor` directory exists after running `composer install`
