<?php

use Stevebauman\Purify\Definitions\Html5Definition;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Config
    |--------------------------------------------------------------------------
    |
    | This option defines the default config that is provided to HTMLPurifier.
    |
    */

    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Config sets
    |--------------------------------------------------------------------------
    |
    | Here you may configure various sets of configuration for differentiated use of HTMLPurifier.
    | A specific set of configuration can be applied by calling the "config($name)" method on
    | a Purify instance. Feel free to add/remove/customize these attributes as you wish.
    |
    | Documentation: http://htmlpurifier.org/live/configdoc/plain.html
    |
    |   Core.Encoding               The encoding to convert input to.
    |   HTML.Doctype                Doctype to use during filtering.
    |   HTML.Allowed                The allowed HTML Elements with their allowed attributes.
    |   HTML.ForbiddenElements      The forbidden HTML elements. Elements that are listed in this
    |                               string will be removed, however their content will remain.
    |   CSS.AllowedProperties       The Allowed CSS properties.
    |   AutoFormat.AutoParagraph    Newlines are converted in to paragraphs whenever possible.
    |   AutoFormat.RemoveEmpty      Remove empty elements that contribute no semantic information to the document.
    |
    */

    'configs' => [

        'default' => [
            'Core.Encoding' => 'utf-8',
            'HTML.Doctype' => 'HTML 4.01 Transitional',

            'HTML.Allowed' => env('RESTRICT_HTML_TYPES', true) ?
            'a[href|title|rel|class],p[class],span[class],br' :
            'a[href|title|rel|class],p[class],span[class],strong,em,del,b,i,s,strike,h1,h2,h3,h4,h5,h6,ul,ol,li,br',

            'HTML.ForbiddenElements' => '',
            'CSS.AllowedProperties' => '',

            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,

            'Attr.AllowedClasses' => [
	            'h-feed',
	            'h-entry',
	            'h-cite',
	            'h-card',
	            'p-author',
	            'p-name',
	            'p-in-reply-to',
	            'p-repost-of',
	            'p-comment',
	            'u-photo',
	            'u-uid',
	            'u-url',
	            'dt-published',
	            'e-content',
	            'mention',
	            'hashtag',
	            'ellipsis',
	            'invisible'
	        ],

	        'Attr.AllowedRel' => [
	            'noreferrer',
	            'noopener',
	            'nofollow'
	        ],

	        'HTML.TargetBlank' => true,

	        'HTML.Nofollow' => true,

	        'URI.DefaultScheme' => 'https',

	        'URI.DisableExternalResources' => true,

	        'URI.DisableResources' => true,

	        'URI.AllowedSchemes' => [
	            'http' => true,
	            'https' => true,
	        ],

	        'URI.HostBlacklist' => config('costar.enabled') ? config('costar.domain.block') : [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | HTMLPurifier definitions
    |--------------------------------------------------------------------------
    |
    | Here you may specify a class that augments the HTML definitions used by
    | HTMLPurifier. Additional HTML5 definitions are provided out of the box.
    | When specifying a custom class, make sure it implements the interface:
    |
    |   \Stevebauman\Purify\Definitions\Definition
    |
    | Note that these definitions are applied to every Purifier instance.
    |
    | Documentation: http://htmlpurifier.org/docs/enduser-customize.html
    |
    */

    'definitions' => Html5Definition::class,

    /*
    |--------------------------------------------------------------------------
    | Serializer
    |--------------------------------------------------------------------------
    |
    | The storage implementation where HTMLPurifier can store its serializer files.
    | If the filesystem cache is in use, the path must be writable through the
    | storage disk by the web server, otherwise an exception will be thrown.
    |
    */

    'serializer' => [
        'driver' => env('CACHE_DRIVER', 'file'),
        'cache' => \Stevebauman\Purify\Cache\CacheDefinitionCache::class,
    ],

    // 'serializer' => [
    //    'disk' => env('FILESYSTEM_DISK', 'local'),
    //    'path' => 'purify',
    //    'cache' => \Stevebauman\Purify\Cache\FilesystemDefinitionCache::class,
    // ],

];
