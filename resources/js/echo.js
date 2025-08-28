import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'qlgzrt6nvzuux1a4eliu',
    wsHost: 'luckymillion.online',
    wsPort: 443,
    wssPort: 443,
    forceTLS: true, // Enable forceTLS for HTTPS
    enabledTransports: ['ws', 'wss'],
    authEndpoint: 'https://luckymillion.online/api/broadcasting/auth', // Use full HTTPS URL
});
