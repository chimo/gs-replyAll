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
    function prepare(array $args = array())
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
     * Given a Notice, return @-mentions for every user involved in the Conversation
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

        // For each notice in the Conversation
        foreach($notices as $notice) {
            // Add notice author to reply-all list
            $author = $notice->getProfile();
            $recipients[] = '@' . $author->getNickname();

            // Get users mentioned in the notice
            $mentions = $notice->getReplies();

            // Add mentioned users to the reply-all list
            foreach($mentions as $profileID) {
                $profile = Profile::getKV('id', $profileID);
                $recipients[] = '@' . $profile->getNickname();
            }
        }

        // De-dupe recipient list
        $recipients = array_values(array_unique($recipients));

        // Remove ourselves from list
        $user = common_current_user();
        $key = array_search('@' . $user->nickname, $recipients);

        if ($key !== false) {
            array_splice($recipients, $key, 1);
        }

        return $recipients;
    }
}
