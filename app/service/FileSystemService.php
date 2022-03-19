<?php
namespace app\service;

class FileSystemService extends Service
{
    public function register()
    {
        //$this->app->bind('filesystem', FileSystem::class);
    }

    public function boot(Route $route)
    {
        
    }
}