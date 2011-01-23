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

        $this->object->rdfmapper = new midgardmvc_ui_create_rdfmapper($this->object);

        midgardmvc_core::get_instance()->head->set_title($this->object->title);
    }

    public function prepare_new_object(array $args)
    {
        $this->object = new fi_openkeidas_articles_article();

        $this->object->rdfmapper = new midgardmvc_ui_create_rdfmapper($this->object);
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

        // check if the item has already been favourited by this user
        $user_guid = midgardmvc_core::get_instance()->authentication->get_person()->guid;

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
    }
}
?>