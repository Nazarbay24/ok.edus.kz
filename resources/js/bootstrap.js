window._ = require('lodash');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');
console.log(window.location.hostname);
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: false,
    // wsHost: window.location.hostname,
    wsHost: 'mobile.mektep.edu.kz',
    httpHost: 'mobile.mektep.edu.kz',
    wsPort: 6001,
    encrypted: false,
    enabledTransports: ['ws'],
    authEndpoint: 'https://mobile.mektep.edu.kz/api_ok_edus/public/broadcasting/auth',
    auth:{
        headers: {
            Authorization: 'Bearer 2231|kqWFOS8PHBqteWZMWLUs7hoJVsX6JajG7bRTx5dE',
            Accept: 'application/json'
        }
    }
});


