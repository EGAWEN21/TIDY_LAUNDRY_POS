<?php

namespace App\Livewire\Installer;

use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Title;
use Livewire\Component;

class UpdaterApp extends Component
{
    public $running = false;
    //Check if update file is found, if not redirect
    public function mount()
    {
        $installFile = File::exists(base_path('update'));
        if (!$installFile) {
            return redirect('');
        }
    }

    #[Layout('components.layouts.install-layout'),Title('Tidy LMS Updater')]
    public function render()
    {
        return view('livewire.installer.updater-app');
    }

    public function updateApp()
    {
        abort_unless(File::exists(base_path('update')), 404);

        $this->running = true;
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('optimize:clear');
        Artisan::call('config:cache');
        Artisan::call('db:seed', ['--force' => true]);
        File::delete(base_path('update'));
        $this->running = false;

        return url('/');
    }
}
