<?php
/**
 * Hitbox
 *
 * This plugin embeds Hitbox streams from markdown
 *
 * Licensed under CC-BY, see LICENSE.
 */

namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;

class HitboxPlugin extends Plugin
{
    const HITBOX_REGEX = '(.{3,25})';

    /**
     * Return a list of subscribed events.
     *
     * @return array    The list of events of the plugin of the form
     *                      'name' => ['method_name', priority].
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    /**
     * Initialize configuration.
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }

        $this->enable([
            'onPageContentRaw' => ['onPageContentRaw', 0],
        ]);
    }

    /**
     * Add content after page content was read into the system.
     *
     * @param  Event  $event An event object, when `onPageContentRaw` is fired.
     */
    public function onPageContentRaw(Event $event)
    {
        /** @var Page $page */
        $page = $event['page'];
        $config = $this->mergeConfig($page);

        if ($config->get('enabled')) {
            // Get raw content and substitute all formulas by a unique token
            $raw = $page->getRawContent();

            // build an anonymous function to pass to `parseLinks()`
            $function = function ($matches) use (&$config) {
                $search = $matches[0];

                // double check to make sure we found a valid Hitbox channel name
                if (!isset($matches[1])) {
                    return $search;
                }
                $channel = $matches[1];

                // build the replacement embeded HTML string
                $player = '<iframe src="http://www.hitbox.tv/embed/'
                    . $channel
                    . '" frameborder="'
                    . $config->get('player.properties.frameborder')
                    . '" height="'
                    . $config->get('player.properties.height')
                    . '" width="'
                    . $config->get('player.properties.width')
                    . '" style="'
                    . $config->get('player.properties.style')
                    . '" allowfullscreen></iframe>';

                $chat = '<iframe src="http://www.hitbox.tv/embedchat/'
                    . $channel
                    . '" frameborder="'
                    . $config->get('chat.properties.frameborder')
                    . '" height="'
                    . $config->get('chat.properties.height')
                    . '" width="'
                    . $config->get('chat.properties.width')
                    . '" style="'
                    . $config->get('chat.properties.style')
                    . '" allowfullscreen></iframe>';

                $content = ($config->get('player.enabled') ? $player : '')
                    . ($config->get('chat.enabled') ? $chat : '');

                $replace = '<div style="overflow: auto; width: 100%" class="grav-hitbox">' . $content . '</div>';

                // do the replacement
                return str_replace($search, $replace, $search);
            };

            // set the parsed content back into as raw content
            $page->setRawContent($this->parseLinks($raw, $function, $this::HITBOX_REGEX));
        }
    }
}
