<?php

/*
 * This file is part of fof/oauth.
 *
 * Copyright (c) 2020 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\OAuth;

use Flarum\Frontend\Document;
use FoF\OAuth\Providers\Google;

/**
 * For performance, we only load the required fonts when the Google provider is enabled.
 */
class GoogleBrandFonts
{
    protected $provider;

    public function __construct(Google $provider)
    {
        $this->provider = $provider;
    }

    public function __invoke(Document $document)
    {
        if ($this->provider->enabled()) {
            $document->head[] = '<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500&amp;display=swap" rel="stylesheet">';
        }
    }
}
