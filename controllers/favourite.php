<?php
class fi_openkeidas_articles_controllers_favourite
{
    private $user_guid = null;

    /**
     * Checks if there is a valid user logged in
     */
    public function __construct(midgardmvc_core_request $request)
    {
        $this->request = $request;
        $this->user_guid = midgardmvc_core::get_instance()->authentication->get_person()->guid;
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
            $results = self::load_favourites($this->user_guid, $_POST['article_guid']);

            if (! count($results))
            {
                $favourite = new fi_openkeidas_articles_favourite();
                $favourite->article = $_POST['article_guid'];
                $success = $favourite->create();

                if ($success)
                {
                    $this->data['success'] = 1;
                    $this->data['reverse_caption'] = midgardmvc_core::get_instance()->i18n->get('unselect', 'fi_openkeidas_articles');
                    $this->data['reverse_action'] = midgardmvc_core::get_instance()->dispatcher->generate_url('item_dislike', array(), $this->request);
                }
            }
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
            $results = self::load_favourites($this->user_guid, $_POST['article_guid']);

            if (count($results))
            {
                $favourite = $results[0];
                $success = $favourite->delete();

                if ($success)
                {
                    $this->data['success'] = 1;
                    $this->data['reverse_caption'] = midgardmvc_core::get_instance()->i18n->get('select', 'fi_openkeidas_articles');
                    $this->data['reverse_action'] = midgardmvc_core::get_instance()->dispatcher->generate_url('item_like', array(), $this->request);
                }
            }
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

    /**
     * Loads favourited items
     *
     * @param user_guid GUID of user
     * @param article_guid GUID of article object
     * @return array of db entries
     *
     */
    public function load_favourites($user_guid, $article_guid)
    {
        $storage = new midgard_query_storage('fi_openkeidas_articles_favourite');

        $qc = new midgard_query_constraint_group('AND');
        $qc->add_constraint(new midgard_query_constraint(
            new midgard_query_property('metadata.creator', $storage),
            '=',
            new midgard_query_value($user_guid)
        ));
        $qc->add_constraint(new midgard_query_constraint(
            new midgard_query_property('article', $storage),
            '=',
            new midgard_query_value($article_guid)
        ));

        $q = new midgard_query_select($storage);
        $q->set_constraint($qc);
        $q->execute();

        return $q->list_objects();
    }
}
?>