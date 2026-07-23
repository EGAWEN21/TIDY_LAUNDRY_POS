<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Str;
use App\Models\User;
use App\Models\MasterSettings;
use Livewire\Attributes\Title;

class Login extends Component
{
    public $email,$password,$success=false,$forgetpassword=0;
    //Render Page
    #[Layout('components.layouts.base'),Title('Login')]
    public function render()
    {
        return view('livewire.auth.login');
    }
    //Process Login
    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password'  => 'required'
        ]);

        $throttleKey = 'web-login:'.strtolower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('login_error', "Too many login attempts. Please try again in {$seconds} seconds.");
            return;
        }

        // Check if account is deactivated (skip for Super Admins user_type = 1)
        $user = User::where('email', $this->email)->first();
        if ($user && $user->user_type != 1 && $user->is_active == 0) {
            $this->addError('login_error','Account is deactivated. Please contact administrator.');
            return;
        }

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password, 'user_type' => '1'])) {
            /* user type admin and login is successful */
            request()->session()->regenerate();
            DB::table('password_resets')->where('email',$this->email)->delete();
            Auth::user()->update(['current_session_id' => session()->getId()]);
            RateLimiter::clear($throttleKey);
            return redirect('admin/dashboard');
        }  
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password, 'user_type' => '2'])) {
            /* user type staff and login is successful */
            request()->session()->regenerate();
            DB::table('password_resets')->where('email',$this->email)->delete();
            Auth::user()->update(['current_session_id' => session()->getId()]);
            RateLimiter::clear($throttleKey);
            return redirect('admin/dashboard');
        }  
        else {
            /* if the credentials are incorrect */
            RateLimiter::hit($throttleKey, 60);
            $this->addError('login_error','Invalid Email/Password');
        }
    }
    //Initialize Variables
    public function mount()
    {
        if(Auth::user())
        {
            return redirect()->route('admin.dashboard');
        }
        $settings = new MasterSettings();
        $site = $settings->siteData();
        if(isset($site['forget_password_enable']))
        {
            if($site['forget_password_enable'] == 0)
            {
            }
            else{
                $this->forgetpassword =1;
            }
        }
    }
    //Process Forgot Password
    public function forgotpassword()
    {
        if($this->forgetpassword == 1)
        {
            $this->validate([
                'email' => 'required|email',
            ]);
            $throttleKey = 'forgot-password:'.strtolower($this->email).'|'.request()->ip();
            if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
                $this->success = true;
                return;
            }
            RateLimiter::hit($throttleKey, 300);

            $user = User::where('email',$this->email)->first();
            if($user)
            {
                $token = Str::random(64);
                DB::table('password_resets')->where('email',$this->email)->delete();
                DB::table('password_resets')->insert([
                    'email' => $this->email,
                    'token' => hash('sha256', $token),
                    'created_at' => Carbon::now()
                ]);
                $link = url('reset-password/'.$token);
                $data=[
                    'name'  => $user->name,
                    'link'  => $link,
                ];
                try{
                    Mail::to($user->email)->send(new \App\Mail\ForgotPassword($data));
    
                }
                catch(\Exception $e)
                {
                    $this->addError('login_error','Failed to send mail, Contact an Admin');
                    return 1;
                }
                $this->success = true;
            }
            else{
                // Use the same response for unknown addresses to prevent account enumeration.
                $this->success = true;
            }
        }
    }
}
