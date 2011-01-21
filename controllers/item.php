<?php
class fi_openkeidas_articles_controllers_item extends midgardmvc_core_controllers_baseclasses_crud
{
    public function load_object(array $args)
    {
        $this->object = new fi_openkeidas_articles_article($args['item']);

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
}
?>
