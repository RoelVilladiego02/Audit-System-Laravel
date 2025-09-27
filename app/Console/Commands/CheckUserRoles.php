<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckUserRoles extends Command
{
    protected $signature = 'users:check-roles {--fix : Fix users without roles}';
    protected $description = 'Check user roles and optionally fix missing roles';

    public function handle()
    {
        $this->info('Checking user roles...');
        
        $users = User::all();
        $usersWithoutRoles = [];
        
        foreach ($users as $user) {
            $this->line("User ID: {$user->id}");
            $this->line("Email: {$user->email}");
            $this->line("Name: {$user->name}");
            $this->line("Role: " . ($user->role ?? 'NULL'));
            $this->line("Created: {$user->created_at}");
            $this->line("---");
            
            if (empty($user->role)) {
                $usersWithoutRoles[] = $user;
            }
        }
        
        if (count($usersWithoutRoles) > 0) {
            $this->warn("Found " . count($usersWithoutRoles) . " users without roles:");
            
            foreach ($usersWithoutRoles as $user) {
                $this->warn("- {$user->email} (ID: {$user->id})");
            }
            
            if ($this->option('fix')) {
                $this->info("Fixing users without roles...");
                
                foreach ($usersWithoutRoles as $user) {
                    // Set default role to 'user'
                    $user->role = 'user';
                    $user->save();
                    $this->info("Set role 'user' for {$user->email}");
                }
                
                $this->info("All users now have roles assigned.");
            } else {
                $this->info("Run with --fix option to assign default 'user' role to users without roles.");
            }
        } else {
            $this->info("All users have roles assigned.");
        }
        
        // Show role distribution
        $roleDistribution = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->get();
            
        $this->info("\nRole Distribution:");
        foreach ($roleDistribution as $role) {
            $this->line("- {$role->role}: {$role->count} users");
        }
        
        return 0;
    }
}
