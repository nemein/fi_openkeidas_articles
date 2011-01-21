<?php
class fi_openkeidas_articles_controllers_latest
{
    public function __construct(midgardmvc_core_request $request)
    {
        $this->request = $request;
    }

    private function get_node($node_id)
    {
        static $nodes = array();
        if (!isset($nodes[$node_id]))
        {
            $node = new midgardmvc_core_node();
            $node->get_by_id($node_id);
            $nodes[$node_id] = midgardmvc_core_providers_hierarchy_node_midgard2::get_instance($node);
        }
        return $nodes[$node_id];
    }

    public function get_items(array $args)
    {
        $qb = new midgard_query_builder('fi_openkeidas_articles_article');
        
        $node = $this->request->get_node()->get_object();
        $this->data['title'] = $node->title;
        $qb->add_constraint('node', 'INTREE', $node->id);

        if (!midgardmvc_ui_create_injector::can_use())
        {
            // Regular user, hide unapproved articles
            // TODO: This check should be moved to authentication service when QB has signals
            $qb->add_constraint('metadata.isapproved', '=', true);
        }

        $qb->add_order('metadata.created', 'DESC');
        $qb->set_limit(midgardmvc_core::get_instance()->configuration->index_items);
        $items = $qb->execute();

        $this->data['items'] = new midgardmvc_ui_create_container();
        foreach ($items as $item)
        {
            if ($item->node == $this->request->get_node()->get_object()->id)
            {
                // Local news item
                $item->url = midgardmvc_core::get_instance()->dispatcher->generate_url('item_read', array('item' => $item->guid), $this->request);
            }
            else
            {
                $node = $this->get_node($item->node);
                $item->url = midgardmvc_core::get_instance()->dispatcher->generate_url('item_read', array('item' => $item->guid), $node->get_path());
            }

            $this->data['items']->attach($item);
        }

        // Read container type from config to know whether items can be created to this node
        $this->data['container_type'] = midgardmvc_core::get_instance()->configuration->index_container;

        $this->data['subnodes'] = array();
        if ($this->request->get_node()->get_parent_node() != midgardmvc_core::get_instance()->hierarchy->get_root_node())
        {
            $this->data['subnodes'] = $this->request->get_node()->get_child_nodes();
        }

        // Define placeholder to be used with UI on empty containers
        $dummy = new fi_openkeidas_articles_article();
        $dummy->url = 'http://example.net';
        $this->data['items']->set_placeholder($dummy);

        midgardmvc_core::get_instance()->head->set_title($this->data['title']);
    }
}
