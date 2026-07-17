<?php

namespace App\Livewire\Auth;

use App\Livewire\Installer\InstallController;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
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
        // Check if account is deactivated
        $user = User::where('email', $this->email)->first();
        if ($user && $user->is_active == 0) {
            $this->addError('login_error','Account is deactivated. Please contact administrator.');
            return;
        }

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password, 'user_type' => '1'])) {
            /* user type admin and login is successful */
            DB::table('password_resets')->where('email',$this->email)->delete();
            // Store session ID for single-session per role enforcement
            Cache::forever('role_session_admin', session()->getId());
            return redirect('admin/dashboard');
        }  
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password, 'user_type' => '2'])) {
            /* user type staff and login is successful */
            DB::table('password_resets')->where('email',$this->email)->delete();
            // Store session ID for single-session per role enforcement
            $user = Auth::user();
            Cache::forever('role_session_role_' . $user->role_id, session()->getId());
            return redirect('admin/dashboard');
        }  
        else {
            /* if the credentials are incorrect */
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
            $user = User::where('email',$this->email)->first();
            if($user)
            {
                $token = Str::random(60);
                DB::table('password_resets')->where('email',$this->email)->delete();
                DB::table('password_resets')->insert([
                    'email' => $this->email,
                    'token' => $token,
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
                $this->addError('login_error','No Accounts are registered with this email');
                return 1;
            }
        }
    }
}
