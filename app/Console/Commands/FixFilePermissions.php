<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FixFilePermissions extends Command
{
    protected $signature = 'files:fix-permissions';
    protected $description = 'Fix file and directory permissions for media storage';

    public function handle()
    {
        $this->info('Fixing file permissions...');
        
        // Ensure storage directories exist
        $directories = [
            storage_path('app/public'),
            storage_path('app/public/media'),
            public_path('storage'),
        ];
        
        foreach ($directories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->info("Created directory: {$dir}");
            }
        }
        
        // Fix permissions
        $this->call('storage:link');
        
        // Set proper permissions
        exec('chmod -R 755 ' . storage_path('app/public'));
        exec('find ' . storage_path('app/public') . ' -type f -exec chmod 644 {} \;');
        
        $this->info('File permissions fixed successfully!');
        
        return 0;
    }
}