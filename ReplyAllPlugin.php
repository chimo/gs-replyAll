<?php

if (!defined('GNUSOCIAL')) {
    exit(1);
}

class ReplyAllPlugin extends Plugin
{
    const VERSION = '0.0.2';

    function onRouterInitialized($m) {
        $m->connect(
            'notice/replyall', array(
                'action' => 'replyall'
                )
            );

        return true;
    }

    function onEndShowNoticeOptionItems($item) {
        $url = common_local_url('replyall',
                                null,
                                array('inreplyto' => $item->notice->getID()));

        if (common_logged_in()) {
            $item->out->element('a',
                                array('href' => $url, 'class' => 'gs-reply-all'),
                                'Reply all');
        }

        return true;
    }

    function onEndShowScripts($action) {
        if (common_logged_in()) {
            $action->script($this->path('js/reply-all.js'));
        }

        return true;
    }

    function onEndShowStyles($action) {
        if (common_logged_in()) {
            $action->cssLink($this->path('css/reply-all.css'));
        }

        return true;
    }

    function onPluginVersion(array &$versions)
    {
        $versions[] = array('name' => 'ReplyAll',
                            'version' => self::VERSION,
                            'author' => 'chimo',
                            'homepage' => 'https://github.com/chimo/gs-replyAll',
                            'description' =>
                            // TRANS: Plugin description.
                            _m('Adds a "reply all" button to each notice'));
        return true;
    }
}
