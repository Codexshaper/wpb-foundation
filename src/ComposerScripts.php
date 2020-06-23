<?php

namespace CodexShaper\WP;

use Composer\Script\Event;

class ComposerScripts
{
    /**
     * Handle the post-install Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postInstall(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';
    }

    /**
     * Handle the post-update Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postUpdate(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';
    }

    /**
     * Handle the post-autoload-dump Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postAutoloadDump(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        $dir = $event->getComposer()->getConfig()->get('vendor-dir').'/../';
        $root = dirname($event->getComposer()->getConfig()->get('vendor-dir'));

        $vendor_name = basename($root);
        $partials = explode('-', $vendor_name);
        $vendor_class = implode('_', array_filter($partials, function($partial){
            return ucfirst($partial);
        }));
        $snake_case = implode('_', array_filter($partials, function($partial){
            return strtolower($partial);
        }));

        $files = [
            '/wpb-framework.php',
            '/includes/class-wpb-framework-activator.php',
            '/includes/class-wpb-framework-deactivator.php',
            '/includes/class-wpb-framework-i18n.php',
            '/includes/class-wpb-framework-loader.php',
            '/includes/class-wpb-framework.php',
            '/admin/class-wpb-framework-admin.php',
            '/admin/partials/wpb-framework-admin-display.php',
            '/admin/css/wpb-framework-admin.css',
            '/admin/js/wpb-framework-admin.js',
            '/public/class-wpb-framework-public.php',
            '/public/partials/wpb-framework-public-display.php',
            '/public/css/wpb-framework-public.css',
            '/public/js/wpb-framework-public.js',
        ];

        foreach ($files as $file) {
            if(file_exists($root.$file)) {
                $contents = file_get_contents($root.$file);
                $contents = str_replace('wpb_', $snake_case.'_', $contents);
                $contents = str_replace('wpb', $vendor_name, $contents);
                $contents = str_replace('WPB', $vendor_class, $contents);
                // $contents = array_filter(explode("\n", $contents));
                file_put_contents(
                    $root.$file,
                    $contents
                );

                if (file_exists($root.$file)) {
                    $fileName = basename($file);
                    $newFileName = str_replace('wpb', $vendor_name, $fileName);
                    rename($root.$file, $root.'/'.$newFileName.'.php');
                }
            }
        }
    }
}
