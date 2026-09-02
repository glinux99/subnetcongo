<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Canal privé de notifications d'un utilisateur.
 */
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

/**
 * Canal public pour les live sessions (chat + présence).
 */
Broadcast::channel('live-session.{sessionId}', function ($user, $sessionId) {
    return ['id' => $user->id, 'name' => $user->name, 'avatar' => $user->profile_photo_url];
});

/**
 * Canal de présence pour le chat d'un live.
 */
Broadcast::channel('presence-live.{sessionId}', function ($user, $sessionId) {
    return ['id' => $user->id, 'name' => $user->name, 'avatar' => $user->profile_photo_url];
});