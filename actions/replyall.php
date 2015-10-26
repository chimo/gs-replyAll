<?php

/**
 * TODO: Check if we sometimes @-mention people we don't follow.
 *       If so, we need to use the full webfinger @user@example.org
 */

if (!defined('GNUSOCIAL')) {
    exit(1);
}

class ReplyallAction extends NewnoticeAction
{
    function prepare($args)
    {
        parent::prepare($args);

        // Missing inreplyto argument
        if (empty($args['inreplyto'])) {
            throw new ClientException(_m('Missing notice ID'), 404);
        }

        // Get notice with the given notice ID
        $notice = Notice::getKV('id', $args['inreplyto']);

        // Error out if it doesn't exist
        if (empty($notice)) {
            throw new ClientException(_m('Notice doesn\'t exist'), 404);
        }

        $recipients = $this->getRecipients($notice);

        // Ajax
        // FIXME: This seems like a strange location this that
        if ($this->boolean('ajax')) {
            header('Content-Type: application/json; charset=utf-8');
            print json_encode(['mentions' => $recipients]);
            return;
        }

        $this->formOpts['content'] = implode(" ", $recipients);

        return true;
    }

    function getRecipients($notice)
    {
        $recipients = array();
        $notices = array();

        // If we have a conversation
        if ($notice->conversation) {
            $conv = new Conversation();
            $conv->id = $notice->conversation;

            // Try to get all notices from it
            if ($conv->find(true)) {
                $notices = $conv->getNotices()->fetchAll();
            } else {
                // If we fail, fallback to only this notice
                $notices[] = $notice;

            }
        } else {
            // If we don't have a conversation, just use this notice
            $notices[] = $notice;
        }

        foreach($notices as $notice) {
            $profile = $notice->getProfile();

            $recipients[] = '@' . $profile->getNickname();
        }

        // De-dupe
        $recipients = array_values(array_unique($recipients));

        return $recipients;
    }
}
