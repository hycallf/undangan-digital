<?php

namespace App\Helpers;

class HtmlHelper
{
    public static function processOembed(string $htmlString): string
    {
        $processedHtml = $htmlString;

        // Ganti <oembed> YouTube menjadi <iframe>
        $processedHtml = preg_replace_callback(
            '/<oembed url="https?:\/\/(?:www\.)?youtube\.com\/watch\?v=([^"]+)"><\/oembed>/',
            function ($matches) {
                $videoId = $matches[1];
                $uniqueId = 'ytplayer-' . uniqid();
                // Kita juga perlu menambahkan ?enablejsapi=1 ke URL
                return "<div class='aspect-w-16 aspect-h-9'><iframe id='{$uniqueId}' src='https://www.youtube.com/embed/{$videoId}?enablejsapi=1' frameborder='0' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe></div>";
            },
            $processedHtml
        );

        // Ganti <oembed> Vimeo menjadi <iframe>
        $processedHtml = preg_replace(
            '/<oembed url="https?:\/\/vimeo\.com\/([^"]+)"><\/oembed>/',
            '<div class="aspect-w-16 aspect-h-9"><iframe src="https://player.vimeo.com/video/$1" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe></div>',
            $processedHtml
        );

        return $processedHtml;
    }
}
