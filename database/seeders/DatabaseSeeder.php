<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin if it doesn't exist
        // Allow skipping full DB seeding during automated deploys by setting
        // SKIP_DB_SEED=true in the environment. This is helpful when large
        // seeders or transient errors cause the deployment to fail; you can
        // then run `php artisan db:seed` manually once the app is stable.
        if (env('SKIP_DB_SEED', false)) {
            // Only run minimal seeders required for the app to boot, if any.
            $this->call([
                AdminSeeder::class,
                UserSeeder::class,
            ]);

            return;
        }

        // Default: run all seeders
        $this->call([
            // First create users
            AdminSeeder::class,
            UserSeeder::class,
            
            // Then create core data
            AuditQuestionSeeder::class,
            // AuditAnswerSeeder::class,
            
            // Test scenarios for risk calculation validation
            // AuditSubmissionTestSeeder::class,
            
            // Finally create submissions and their relationships
            // AuditSubmissionSeeder::class,
            // VulnerabilitySubmissionSeeder::class,
            // VulnerabilitySeeder::class,
        ]);
    }
}