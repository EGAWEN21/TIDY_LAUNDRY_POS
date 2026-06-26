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
         // Clear the stored session ID before logging out
         if (Auth::check()) {
             $user = Auth::user();
             $user->current_session_id = null;
             $user->save();
         }
         Auth::logout();
         Session::flush();
         return redirect('/');
     }
       
}
