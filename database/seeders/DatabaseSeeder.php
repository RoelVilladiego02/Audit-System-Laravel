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
        // Always refresh seeders for development
        $this->call([
            // First create users
            AdminSeeder::class,
            UserSeeder::class,
            
            // Then create core data
            AuditQuestionSeeder::class,
            AuditAnswerSeeder::class,
            
            // Test scenarios for risk calculation validation
            // AuditSubmissionTestSeeder::class,
            
            // Finally create submissions and their relationships
            // AuditSubmissionSeeder::class,
            // VulnerabilitySubmissionSeeder::class,
            // VulnerabilitySeeder::class,
        ]);
    }
}