<?php

namespace App\Livewire\Installer;

use App\Classes\Requirement;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Livewire\Attributes\Layout;
use Livewire\Component;
class InstallApp extends Component
{
    public $extensions,$directories,$errormessage,$page,$step=1,$requirement_satisfied =false;
    public $host='localhost',$port=3306,$username,$password,$name,$dberror =true;
    
    #[Layout('components.layouts.install-layout')]
    public function render()
    {
        return view('livewire.installer.install-app');
    }

    //Check if install file is found, if not redirect
    public function mount()
    {
        $installFile = File::exists(base_path('install'));
        if (!$installFile) {
            return redirect('');
        }
    }

    //check server requirements
    public function checkRequirementsServer()
    {
        $requirement = new Requirement();
        $this->extensions = $requirement->extensions();
        $this->directories = $requirement->directories();
        $this->requirement_satisfied = $requirement->satisfied();
        if($this->requirement_satisfied == true)
        {
            return true;
        }
        return false;
    }

    //test database connection
    public function checkDatabase()
    {
        $this->dberror = true;
        $this->validate([
            'host'  => 'required',
            'port'  => 'required|numeric',
            'username'  => 'required',
            'name'  => 'required'
        ]);
        $error =false;
        try{
            $connection = mysqli_connect($this->host,$this->username,$this->password,$this->name,$this->port);
        }
        catch(\Exception $e)
        {
            $error = $e->getMessage();
        }
        if($error == false)
        {
            $this->step = 3;
            $this->dberror = false;
        }
        else{
            $this->dberror = true;
        }
        $this->errormessage = $error;
        if(!$error)
        {
            return true;
        }
    }

    //install : run db migrations
    public function startInstallation()
    {
        abort_unless(File::exists(base_path('install')), 404);

        $this->validate([
            'host' => ['required', 'string'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'username' => ['required', 'string'],
            'password' => ['nullable', 'string'],
            'name' => ['required', 'string'],
        ]);

        $requirement = new Requirement();
        abort_unless($requirement->satisfied(), 403, 'Server requirements have not been met.');

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $this->host,
            'database.connections.mysql.port' => $this->port,
            'database.connections.mysql.database' => $this->name,
            'database.connections.mysql.username' => $this->username,
            'database.connections.mysql.password' => $this->password
        ]);
        DB::purge('mysql');

        try {
            DB::connection('mysql')->getPdo();
        } catch (\Throwable $exception) {
            report($exception);
            $this->dberror = true;
            $this->errormessage = 'Unable to connect to the database with the supplied credentials.';

            return false;
        }

        $this->dberror = false;

        $editor = DotenvEditor::setKeys([
            'DB_HOST'   => $this->host,
            'DB_PORT'   => $this->port,
            'DB_DATABASE'   => $this->name,
            'DB_USERNAME'   => $this->username,
            'DB_PASSWORD'   => $this->password
        ]);
        $editor->save();
        DB::purge('mysql');
        DB::connection('mysql')->getPdo();
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);
        Artisan::call('optimize:clear');
        Artisan::call('config:cache');
        File::delete(base_path('install'));
        $this->step = 4;
        Auth::check();
        Auth::user();
        return true;
    }

    //auto login admin user and redirect to dashboard
    public function goToDashboard()
    {
        Auth::check();
        Auth::user();
        return redirect()->route('admin.dashboard');
    }
}