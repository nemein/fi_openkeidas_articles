<?php
class fi_openkeidas_articles_controllers_item extends midgardmvc_core_controllers_baseclasses_crud
{
    /**
     * Calls the CRUD constructor and
     * adds the component's localization domain and sets default language
     */
    public function __construct(midgardmvc_core_request $request)
    {
        parent::__construct($request);

        midgardmvc_core::get_instance()->i18n->set_translation_domain('fi_openkeidas_article');

        $default_language = midgardmvc_core::get_instance()->configuration->default_language;

        if (! isset($default_language))
        {
            $default_language = 'en_US';
        }

        midgardmvc_core::get_instance()->i18n->set_language($default_language, false);
    }

    public function load_object(array $args)
    {
        try {
            $this->object = new fi_openkeidas_articles_article($args['item']);
        }
        catch (midgard_error_exception $e)
        {
            throw new midgardmvc_exception_notfound($e->getMessage());
        }

        if (   !midgardmvc_ui_create_injector::can_use()
            && !$this->object->is_approved())
        {
            // Regular user, hide unapproved articles
            // TODO: This check should be moved to authentication
            throw new midgardmvc_exception_notfound("No article found");
        }
        $this->object = new midgardmvc_ui_create_decorator($this->object);
        midgardmvc_core::get_instance()->head->set_title($this->object->title);
    }

    public function prepare_new_object(array $args)
    {
        $this->object = new midgardmvc_ui_create_decorator(new fi_openkeidas_articles_article());
    }

    public function get_url_read()
    {
        return midgardmvc_core::get_instance()->dispatcher->generate_url
        (
            'item_read', array
            (
                'item' => $this->object->guid
            ),
            $this->request
        );
    }

    public function get_url_update()
    {
        return midgardmvc_core::get_instance()->dispatcher->generate_url
        (
            'item_read', array
            (
                'item' => $this->object->guid
            ),
            $this->request
        );
    }

    public function get_read(array $args)
    {
        parent::get_read($args);

        $this->data['class'] = "button row-2 fav";

        $this->data['favs'] = fi_openkeidas_articles_controllers_favourite::count_favourites($this->object->guid);

        // check if the item has already been favourited by this user
        $user_guid = '';
        $results = array();
        if (midgardmvc_core::get_instance()->authentication->is_user())
        {
            $user_guid = midgardmvc_core::get_instance()->authentication->get_person()->guid;
            $results = fi_openkeidas_articles_controllers_favourite::load_favourites($user_guid, $this->object->guid);
        }

        $results = fi_openkeidas_articles_controllers_favourite::load_favourites($user_guid, $this->object->guid);

        if (count($results))
        {
            // show unfavouriting button
            $this->data['caption'] = midgardmvc_core::get_instance()->i18n->get('unselect', 'fi_openkeidas_articles');
            $this->data['class'] .= " dislike";
            $this->data['url'] = midgardmvc_core::get_instance()->dispatcher->generate_url('item_dislike', array(), $this->request);
        }
        else
        {
            // show favouriting button
            $this->data['caption'] = midgardmvc_core::get_instance()->i18n->get('select', 'fi_openkeidas_articles');
            $this->data['class'] .= " like";
            $this->data['url'] = midgardmvc_core::get_instance()->dispatcher->generate_url('item_like', array(), $this->request);
        }

        midgardmvc_core::get_instance()->head->add_jsfile(MIDGARDMVC_STATIC_URL . '/fi_openkeidas_articles/js/buttons.js');

        $this->data['show_title'] = false;
        $this->data['allow_comments'] = false;
        $this->data['articleguid'] = '';
        $this->data['comments'] = array();
        $this->data['postaction'] = '';
        $this->data['relocate'] = '';

        $today = new DateTime('now');
        $today = $today->getTimestamp();
        $start = midgardmvc_core::get_instance()->configuration->commenting_timeframe['start'];
        $end = midgardmvc_core::get_instance()->configuration->commenting_timeframe['end'];

        // check if current date is within the configured time frame
        if (   $today >= $start
            && $today <= $end)
        {
            // check if current path is enabled for commenting
            foreach (midgardmvc_core::get_instance()->configuration->commenting_paths as $path => $info)
            {
                $result = substr_compare($this->request->get_path(), $path, 0, strlen($path));

                #echo $this->request->get_path() . ' vs ' . $path . ', result: ' . $result . "\n";
                #ob_flush();

                if ($result == 0)
                {
                    $this->data['allow_comments'] = true;
                }
            }

            // populate data for the template in case commenting is allowed
            if ($this->data['allow_comments'])
            {
                $this->data['articleguid'] = $this->object->guid;

                // get comments
                $this->data['comments'] = $this->get_comments($this->data['articleguid']);

                // set the form's action
                $this->data['postaction'] = midgardmvc_core::get_instance()->dispatcher->generate_url
                (
                    'article_comment_create', array
                    (
                        'articleguid' => $this->data['articleguid']
                    ),
                    'fi_openkeidas_website'
                );
            }

            // show the "Comments" title only if there are comments,
            // or commenting is allowed
            if (   count($this->data['comments'])
                || $this->data['allow_comments'])
            {
                $this->data['show_title'] = true;
            }

            // if post successful then relocate here
            $this->data['relocate'] = $this->request->get_path();
        }

        #echo 'path: ' . $this->request->get_path() . ', today: ' . $today . ', start: ' . $start . ', end: ' . $end . "\n";
        #ob_flush();
    }

    /**
     * Retrieves all comments from the database
     * @param guid object f which the comments should be gathered
     * @return array of comment objects
     */
     private function get_comments($guid = null)
     {
        $storage = new midgard_query_storage('com_meego_comments_comment_author');
        $q = new midgard_query_select($storage);
        $q->set_constraint
        (
            new midgard_query_constraint
            (
                new midgard_query_property('to'),
                '=',
                new midgard_query_value($guid)
            )
        );

        $q->add_order(new midgard_query_property('posted', $storage), SORT_DESC);
        $q->execute();

        $comments = $q->list_objects();

        $retval = array();

        foreach ($comments as $comment)
        {
            $object = $comment;

            $string = preg_replace_callback('|\\\(\d{3})|', function($matches) {
                return chr(octdec($matches[1]));
            }, $comment->content);

            $object->fixed_content = $string;
            $retval[] = $object;
        }

        return $retval;
    }

    /**
     * Process article comments and ratings (later)
     *
     * @param array args
     *
     */
    public function post_comment_article(array $args)
    {
        if (   ! is_array($_POST)
            || ! isset($_POST['articleguid']))
        {
            midgardmvc_core::get_instance()->head->relocate($_POST['relocate']);
        }

        $this->data['feedback'] = false;

        $guid = $_POST['articleguid'];

        $comment = null;

        // if comment is also given then create a new comment entry
        if (isset($_POST['comment']))
        {
            $content = $_POST['comment'];
            if (strlen($content))
            {
                // save comment only if the content is not empty
                $comment = new com_meego_comments_comment();

                $comment->to = $guid;
                $comment->content = $content;

                if (! $comment->create())
                {
                    die("can't create comment");
                }
            }
        }

        if (isset($_POST['rating']))
        {
            $rate = $_POST['rating'];

            if ($rate > $this->mvc->configuration->maxrate)
            {
                $rate = $this->mvc->configuration->maxrate;
            }

            $rating = new com_meego_ratings_rating();

            $rating->to = $guid;
            $rating->rating = $rate;

            if (is_object($comment))
            {
                $rating->comment = $comment->id;
            }

            if ($rating->create())
            {
                $this->data['feedback'] = 'ok';
            }
            else
            {
                $this->data['feedback'] = 'nok';
            }
        }

        if (isset($_POST['relocate']))
        {
            midgardmvc_core::get_instance()->head->relocate($_POST['relocate']);
        }
    }
}
?>
