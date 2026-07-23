<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ForgotPassword extends Component
{
    public $password, $password_confirm;

    #[Locked]
    public string $resetTokenHash;
    #[Layout('components.layouts.base'),Title('Reset Password')]
    public function render()
    {
        return view('livewire.auth.forgot-password');
    }

    //Initialize Variables and Checks
    public function mount($token)
    {
        $this->resetTokenHash = hash('sha256', (string) $token);

        if (!$this->validReset()) {
            abort(404);
        }
    }

    protected function validReset(): ?object
    {
        return DB::table('password_resets')
            ->where('token', $this->resetTokenHash)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->first();
    }
    //Reset and login user.
    public function login()
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8'],
            'password_confirm' => ['required', 'same:password'],
        ]);

        $reset = $this->validReset();
        if (!$reset) {
            abort(404);
        }

        $user = User::where('email', $reset->email)->firstOrFail();
        $user->password = Hash::make($this->password);
        $user->save();
        DB::table('password_resets')->where('email', $reset->email)->delete();
        Auth::login($user);
        request()->session()->regenerate();
        $user->update(['current_session_id' => session()->getId()]);
        return redirect()->route('admin.dashboard');
    }
}
