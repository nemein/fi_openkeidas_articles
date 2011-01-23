<?php
class fi_openkeidas_articles_controllers_favourite
{
    /**
     * Checks if there is a valid user logged in
     */
    public function __construct(midgardmvc_core_request $request)
    {
        $this->request = $request;
        midgardmvc_core::get_instance()->authorization->require_user();
    }

    /**
     * Creates a new favourite entry if the user has not favourited the item
     * yet.
     *
     * @param array args
     * @return array JSON array about the status of the operation
     *
     */
    public function post_like(array $args)
    {
        $this->data['success'] = 0;
        if (isset($_POST['article_guid']))
        {
            echo "like: " . $_POST['article_guid'] . "\n";
            $this->data['success'] = 1;
            $this->data['reverse_caption'] = midgardmvc_core::get_instance()->i18n->get('unselect', 'fi_openkeidas_articles');
            $this->data['reverse_action'] = midgardmvc_core::get_instance()->dispatcher->generate_url('item_dislike', array(), $this->request);
        }
        ob_flush();
    }

    /**
     * Deletes a favourite entry
     *
     * @param array args
     * @return array JSON array about the status of the operation
     *
     */
    public function post_dislike(array $args)
    {
        $this->data['success'] = 0;
        if (isset($_POST['article_guid']))
        {
            echo "dislike: " . $_POST['article_guid'] . "\n";
            $this->data['success'] = 1;
            $this->data['reverse_caption'] = midgardmvc_core::get_instance()->i18n->get('select', 'fi_openkeidas_articles');
            $this->data['reverse_action'] = midgardmvc_core::get_instance()->dispatcher->generate_url('item_like', array(), $this->request);
        }
        ob_flush();
    }

    /**
     * Logs the activity
     */
    private function log_activity($user, $verb, $guid)
    {
        $activity = new midgard_activity();
        $activity->actor = $user;
        $activity->verb = $verb;
        $activity->target = $guid;
        $activity->application = 'fi_openkeidas_articles';
        $activity->create();
    }
}
?>