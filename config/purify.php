<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | The configuration settings array is passed directly to HTMLPurifier.
    |
    | Feel free to add / remove / customize these attributes as you wish.
    |
    | Documentation: http://htmlpurifier.org/live/configdoc/plain.html
    |
    */

    'settings' => [

        /*
        |--------------------------------------------------------------------------
        | Core.Encoding
        |--------------------------------------------------------------------------
        |
        | The encoding to convert input to.
        |
        | http://htmlpurifier.org/live/configdoc/plain.html#Core.Encoding
        |
        */

        'Core.Encoding' => 'utf-8',

        /*
        |--------------------------------------------------------------------------
        | Core.SerializerPath
        |--------------------------------------------------------------------------
        |
        | The HTML purifier serializer cache path.
        |
        | http://htmlpurifier.org/live/configdoc/plain.html#Cache.SerializerPath
        |
        */

        'Cache.SerializerPath' => storage_path('purify'),

        /*
        |--------------------------------------------------------------------------
        | HTML.Doctype
        |--------------------------------------------------------------------------
        |
        | Doctype to use during filtering.
        |
        | http://htmlpurifier.org/live/configdoc/plain.html#HTML.Doctype
        |
        */

        'HTML.Doctype' => 'XHTML 1.0 Transitional',

        /*
        |--------------------------------------------------------------------------
        | HTML.Allowed
        |--------------------------------------------------------------------------
        |
        | The allowed HTML Elements with their allowed attributes.
        |
        | http://htmlpurifier.org/live/configdoc/plain.html#HTML.Allowed
        |
        */

        'HTML.Allowed' => env('RESTRICT_HTML_TYPES', true) ? 
            'a[href|title|rel|class],p[class],span[class],br' :
            'a[href|title|rel|class],p[class],span[class],strong,em,del,b,i,s,strike,h1,h2,h3,h4,h5,h6,ul,ol,li,br',


        /*
        |--------------------------------------------------------------------------
        | HTML.ForbiddenElements
        |--------------------------------------------------------------------------
        |
        | The forbidden HTML elements. Elements that are listed in
        | this string will be removed, however their content will remain.
        |
        | For example if 'p' is inside the string, the string: '<p>Test</p>',
        |
        | Will be cleaned to: 'Test'
        |
        | http://htmlpurifier.org/live/configdoc/plain.html#HTML.ForbiddenElements
        |
        */

        'HTML.ForbiddenElements' => '',

        /*
        |--------------------------------------------------------------------------
        | CSS.AllowedProperties
        |--------------------------------------------------------------------------
        |
        | The Allowed CSS properties.
        |
        | http://htmlpurifier.org/live/configdoc/plain.html#CSS.AllowedProperties
        |
        */

        'CSS.AllowedProperties' => '',

        /*
        |--------------------------------------------------------------------------
        | AutoFormat.AutoParagraph
        |--------------------------------------------------------------------------
        |
        | The Allowed CSS properties.
        |
        | This directive turns on auto-paragraphing, where double
        | newlines are converted in to paragraphs whenever possible.
        |
        | http://htmlpurifier.org/live/configdoc/plain.html#AutoFormat.AutoParagraph
        |
        */

        'AutoFormat.AutoParagraph' => false,

        /*
        |--------------------------------------------------------------------------
        | AutoFormat.RemoveEmpty
        |--------------------------------------------------------------------------
        |
        | When enabled, HTML Purifier will attempt to remove empty
        | elements that contribute no semantic information to the document.
        |
        | http://htmlpurifier.org/live/configdoc/plain.html#AutoFormat.RemoveEmpty
        |
        */

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

];
