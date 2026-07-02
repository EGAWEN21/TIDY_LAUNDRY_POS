<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationBell extends Component
{
    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $notifications = Auth::check() ? Auth::user()->unreadNotifications()->take(5)->get() : collect();
        $unreadCount = Auth::check() ? Auth::user()->unreadNotifications()->count() : 0;
        
        return view('livewire.components.notification-bell', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
    }
}
