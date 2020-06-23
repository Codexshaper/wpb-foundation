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

        $vendor_name = strtolower(basename($root));
        $partials = explode('-', $vendor_name);
        $camel_case_partials = [];
        foreach ($partials as $partial) {
           $camel_case_partials[] = ucfirst(strtolower($partial));
        }
        $camel_case = implode('_', $camel_case_partials);
        $snake_case = implode('_', $partials);

        $files = [
            '/wpb.php',
            '/bootstrap/app.php',
            '/includes/class-wpb-activator.php',
            '/includes/class-wpb-deactivator.php',
            '/includes/class-wpb-i18n.php',
            '/includes/class-wpb-loader.php',
            '/includes/class-wpb.php',
            '/admin/class-wpb-admin.php',
            '/admin/partials/wpb-admin-display.php',
            '/admin/css/wpb-admin.css',
            '/admin/js/wpb-admin.js',
            '/public/class-wpb-public.php',
            '/public/partials/wpb-public-display.php',
            '/public/css/wpb-public.css',
            '/public/js/wpb-public.js',
        ];

        foreach ($files as $file) {
            $file = $root.$file;
            if(file_exists($file)) {
                $contents = file_get_contents($file);
                $contents = str_replace('wpb_', $snake_case.'_', $contents);
                $contents = str_replace('wpb', $vendor_name, $contents);
                $contents = str_replace('WPB', $camel_case, $contents);
                file_put_contents(
                    $file,
                    $contents
                );

                $dir = dirname($file);
                $fileName = basename($file);
                $newFileName = str_replace('wpb', $vendor_name, $fileName);
                rename($file, $dir.'/'.$newFileName.'.php');
            }
        }
    }
}
