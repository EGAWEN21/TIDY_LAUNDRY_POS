<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Customer;

class AssignNullCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:assign-null {name? : The name of the staff member to assign the customers to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns all orphaned customers (created_by is NULL) to a specific staff member by name.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');

        if (!$name) {
            $name = $this->ask('Please enter the name (or part of the name) of the staff member (e.g. MANAGER)');
        }

        $users = User::where('name', 'like', '%' . $name . '%')->get();

        if ($users->count() === 0) {
            $this->error("Could not find any user matching '{$name}'.");
            return;
        }

        if ($users->count() > 1) {
            $this->error("Found multiple users matching '{$name}'. Please be more specific.");
            foreach ($users as $u) {
                $this->line("- ID: {$u->id} | Name: {$u->name}");
            }
            return;
        }

        $manager = $users->first();
        
        if (!$this->confirm("Found user: {$manager->name} (ID: {$manager->id}). Do you want to assign ALL orphaned customers to them?")) {
            $this->info('Operation cancelled.');
            return;
        }

        $nullCount = Customer::whereNull('created_by')->count();

        if ($nullCount === 0) {
            $this->info("There are no orphaned customers (created_by is NULL) in the database.");
            return;
        }

        $this->info("Assigning {$nullCount} customers to {$manager->name}...");

        $updated = Customer::whereNull('created_by')->update([
            'created_by' => $manager->id
        ]);

        $this->info("Successfully assigned {$updated} customers!");
    }
}
