<?php

namespace App\Tools;

use Illuminate\Support\Str;
use Knuckles\Scribe\Tools\MarkdownParser as BaseMarkdownParser;

class PatchedMarkdownParser extends BaseMarkdownParser
{
    protected function blockHeader($Line): ?array
    {
        $block = parent::blockHeader($Line);
        if (isset($block['element']['name'])) {
            $level = (int) mb_trim($block['element']['name'], 'h');

            // FIX: Handle missing 'text' key
            $text = $block['element']['text'] ?? '';
            $slug = Str::slug($text);

            $block['element']['attributes']['id'] = $slug;
            $this->headings[] = [
                'text' => $text,
                'level' => $level,
                'slug' => $slug,
            ];
        }

        return $block;
    }
}
