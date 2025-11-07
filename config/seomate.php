<?php

use craft\elements\Entry;

return [

    'siteName' => '{{ seo.siteName }}',
    'sitemapName' => 'sitemap',
    'sitemapEnabled' => true,
    'sitemapLimit' => 50000,
    'sitemapConfig' => [
        'elements' => [
            // main chamber homepage
            'home' => ['changefreq' => 'daily', 'priority' => 1],
            // listing pages
            'blogListing' => ['changefreq' => 'weekly', 'priority' => .8],
            // singles
            'howTheyVoted' => ['changefreq' => 'monthly', 'priority' => .5],
            // channels
            'pages' => ['changefreq' => 'daily', 'priority' => .7],
            'blog' => ['changefreq' => 'daily', 'priority' => .7],
        ],
    ],

    'defaultMeta' => [
        'title' => ['seo.pageTitle'],
        'description' => ['seo.metaDescription'],
        'image' => ['seo.image'],
    ],

    'defaultProfile' => 'standard',

    'fieldProfiles' => [
        'standard' => [
            'title' => ['title'],
            'description' => [ 'summary', 'articleBody'],
            'image' => ['featuredImage'],
        ],
    ],

    'additionalMeta' => [
        'og:type' => 'website',
    ],

];
