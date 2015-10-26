<?php

/**
 * TODO: Check if we sometimes @-mention people we don't follow.
 *       If so, we need to use the full webfinger @user@example.org
 *       We may have a candidate here: http://sn.chromic.org/notice/replyall?inreplyto=886970
 *       w/ modemjunkie@quitter.is (@mcscx@quitter.se repeated it, so it show up in the convo)
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

        // Get the users we should mention
        $recipients = $this->getRecipients($notice);

        // Ajax
        // FIXME: This seems like a strange location for that
        if ($this->boolean('ajax')) {
            header('Content-Type: application/json; charset=utf-8');
            print json_encode(['mentions' => $recipients]);
            return;
        }

        // Pre-fill the textarea
        $this->formOpts['content'] = implode(" ", $recipients);

        return true;
    }

    /**
     * Given a Notice, return all the relevant @-mentions
     *
     * @param  Notice $notice The notice we're Replying-all to
     * @return Array A list of @-mention strings (e.g.: ["@chimo", "@mmn"])
     */
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
            }
        }

        // Include the original notice
        $notices[] = $notice;

        foreach($notices as $notice) {
            $profile = $notice->getProfile();

            $recipients[] = '@' . $profile->getNickname();
        }

        // De-dupe
        $recipients = array_values(array_unique($recipients));

        return $recipients;
    }
}
