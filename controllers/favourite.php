<?php
class fi_openkeidas_articles_controllers_favourite
{
    // the current user's GUID
    private $user_guid = null;

    // all nodes of the current MVC application that may have favouritable objects
    private $nodes = array();

    // the current mvc app
    private $mvc = null;

    /**
     * Checks if there is a valid user logged in
     */
    public function __construct(midgardmvc_core_request $request)
    {
        $this->mvc = midgardmvc_core::get_instance();

        $this->request = $request;

        $this->user_guid = $this->mvc->authentication->get_person()->guid;

        $this->mvc->authorization->require_user();

        self::reset_counters();
    }

    /**
     * Initilizes internal counters
     */
    private function reset_counters()
    {
        $parent_node = $this->mvc->hierarchy->get_node_by_path('/' . $this->mvc->configuration->favouriting_root);
        $children = $parent_node->get_child_nodes();

        foreach ($children as $child)
        {
            $component = $child->get_component();
            if ($component == 'fi_openkeidas_articles')
            {
                $this->nodes[$child->name]['name'] = $child->name;
                $this->nodes[$child->name]['title'] = $child->title . ': ';
                $this->nodes[$child->name]['counter'] = 0;
                $this->nodes[$child->name]['favlist'] = $child->get_path() . 'favourites/list';
                $this->nodes[$child->name]['article_guids'] = array();
            }
        }
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
            $favourites = self::load_favourites($this->user_guid, $_POST['article_guid']);

            if (! count($favourites))
            {
                $favourite = new fi_openkeidas_articles_favourite();
                $favourite->article = $_POST['article_guid'];
                $success = $favourite->create();

                if ($success)
                {
                    $this->data['success'] = 1;
                    $this->data['reverse_caption'] = $this->mvc->i18n->get('unselect', 'fi_openkeidas_articles');
                    $this->data['reverse_action'] = $this->mvc->dispatcher->generate_url('item_dislike', array(), $this->request);
                }
            }
            self::get_counters($args);
            self::get_charturl($args);
        }
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
            $favourites = self::load_favourites($this->user_guid, $_POST['article_guid']);

            if (count($favourites))
            {
                $favourite = $favourites[0];
                $success = $favourite->delete();

                if ($success)
                {
                    $this->data['success'] = 1;
                    $this->data['reverse_caption'] = $this->mvc->i18n->get('select', 'fi_openkeidas_articles');
                    $this->data['reverse_action'] = $this->mvc->dispatcher->generate_url('item_like', array(), $this->request);
                }
            }
            self::get_counters($args);
            self::get_charturl($args);
        }
    }


    /**
     * Goes up in the tree until it reaches a parent node
     * whose component is not 'fi_openkeidas_articles'
     * or whose parent isn't the 0 node
     *
     * If such node found then that node is considered the root of the object.
     *
     * @param id ID or GUID of the article or a node
     *
     * @return midgardmvc_core_node
     */
    private function get_root_of_object($id)
    {
        $node = null;
        do
        {
            $node = new midgardmvc_core_node($id);
            $parent = new midgardmvc_core_node($node->up);
            $id = $node->up;
        } while (   $node->component == 'fi_openkeidas_articles'
                 && $parent->id != 0
                 && $parent->name != $this->mvc->configuration->favouriting_root);

        unset($parent);

        return $node;
    }

    /**
     * Updates the internal counters
     *
     */
    private function update_counters()
    {
        $favourites = self::load_favourites($this->user_guid);

        if (count($favourites))
        {
            foreach ($favourites as $favourite)
            {
                $node = null;
                try {
                    $article = new fi_openkeidas_articles_article($favourite->article);
                }
                catch (midgard_error_exception $e)
                {
                    continue;
                }
                $node = self::get_root_of_object($article->node);

                // update the appropriate counter
                if (   $node
                    && $node->component == 'fi_openkeidas_articles'
                    && array_key_exists($node->name, $this->nodes))
                {
                    ++$this->nodes[$node->name]['counter'];
                    array_push($this->nodes[$node->name]['article_guids'], $article->guid);
                }

                unset($node);
            }
        }
        else
        {
            self::reset_counters();
        }
    }

    /**
     * Prepares an HTML snippet that shows the counters
     *
     * @param array args
     *
     */
    public function get_counters(array $args)
    {
        self::update_counters();
        $this->data['nodes'] = $this->nodes;
    }

    /**
     * Sets the URL of the pie chart image that shows the number of favourited
     * articles in different categories
     *
     * @param array args
     */
    public function get_charturl(array $args)
    {
        self::update_counters();

        $this->data['charturl'] = $this->mvc->configuration->pie_chart['provider'];

        foreach ($this->nodes as $key => $node)
        {
            $this->data['charturl'] .= '&' . $key . '=' . $node['counter'];
        }

        $this->data['charturl'] .= '&' . time();
    }

    /**
     * Populates data to display a list of fav'ed items within a node
     *
     * @param array args
     */
    public function get_list(array $args)
    {
        $node = $this->request->get_node()->get_object();
        $node->rdfmapper = new midgardmvc_ui_create_rdfmapper($node);
        $this->data['node'] = $node;

        self::update_counters();

        $node = self::get_root_of_object($this->data['node']->id);

        $this->data['title'] = $node->title;
        $this->data['items'] = new midgardmvc_ui_create_container();
        $this->data['container_type'] = 'container_readonly';

        // get the article objects
        if (!isset($this->nodes[$node->name]))
        {
            return;
        }
        foreach ($this->nodes[$node->name]['article_guids'] as $guid)
        {
            $article = new fi_openkeidas_articles_article($guid);
            if ($article)
            {
                // add an extra property for linking
                $article->url = $this->mvc->dispatcher->generate_url(
                    'item_read',
                    array('item' => $article->guid),
                    $this->request
                );
                $this->data['items']->attach($article);
            }
        }
    }

    public static function count_favourites($article_guid)
    {
        $storage = new midgard_query_storage('fi_openkeidas_articles_favourite');
        $qc = new midgard_query_constraint
        (
            new midgard_query_property('article', $storage),
            '=',
            new midgard_query_value($article_guid)
        );
        $q = new midgard_query_select($storage);
        $q->set_constraint($qc);
        $q->execute();
        return $q->get_results_count();
    }

    /**
     * Loads favourited items
     *
     * @param user_guid GUID of user
     * @param article_guid GUID of article object
     * @return array of db entries
     *
     */
    public function load_favourites($user_guid = null, $article_guid = null)
    {
        if (! $user_guid)
        {
            return null;
        }
        $storage = new midgard_query_storage('fi_openkeidas_articles_favourite');

        if (! $article_guid)
        {
            $qc = new midgard_query_constraint(
                new midgard_query_property('metadata.creator', $storage),
                '=',
                new midgard_query_value($user_guid)
            );
        }
        else
        {
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
        }

        $q = new midgard_query_select($storage);
        $q->set_constraint($qc);
        $q->execute();

        return $q->list_objects();
    }

    /**
     * Logs the activity
     */
    private function log_activity($user_guid, $verb, $article_guid)
    {
        $activity = new midgard_activity();
        $activity->actor = $user_guid;
        $activity->verb = $verb;
        $activity->target = $article_guid;
        $activity->application = 'fi_openkeidas_articles';
        $activity->create();
    }
}
?>
