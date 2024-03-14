<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/


Broadcast::channel('chat.{channel_id}', function ($user, $channelId) {
    $id = explode("_", $channelId)[0];
return true;
    return (int) $user->id === (int) $id;
});

Broadcast::channel('channels.{user_id}', function ($user, $userId) {
    return true;
    return (int) $user->id === (int) $userId;
});
