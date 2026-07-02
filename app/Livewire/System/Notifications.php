<?php

namespace App\Livewire\System;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;

class Notifications extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'bootstrap';

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function deleteAll()
    {
        Auth::user()->notifications()->delete();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'All notifications deleted successfully.']);
    }

    #[Title('System Notifications')]
    public function render()
    {
        $notifications = Auth::user()->notifications()->paginate(20);
        return view('livewire.system.notifications', [
            'notifications' => $notifications
        ]);
    }
}
