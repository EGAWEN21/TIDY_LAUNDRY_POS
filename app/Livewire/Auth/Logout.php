<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Title;


class Logout extends Component
{
    public function render()
    {
        return view('livewire.auth.logout');
    }
     //Perform Logout
     public function mount()
     {
         // Clear the stored role session ID before logging out
         if (Auth::check()) {
             $user = Auth::user();
             if ($user->user_type == 1) {
                 \Illuminate\Support\Facades\Cache::forget('role_session_admin');
             } else {
                 \Illuminate\Support\Facades\Cache::forget('role_session_role_' . $user->role_id);
             }
         }
         Auth::logout();
         Session::flush();
         return redirect('/');
     }
       
}
