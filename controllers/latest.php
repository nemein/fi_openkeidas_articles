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

    private function prepare_qb(midgardmvc_core_node $node, $limit, $offset = 0)
    {
        $qb = new midgard_query_builder('fi_openkeidas_articles_article');
        $qb->add_constraint('node', 'INTREE', $node->id);
        if (!midgardmvc_ui_create_injector::can_use())
        {
            // Regular user, hide unapproved articles
            // TODO: This check should be moved to authentication service when QB has signals
            $qb->add_constraint('metadata.isapproved', '=', true);
        }
        $qb->add_order('metadata.created', 'DESC');
        $qb->set_limit($limit);
        $qb->set_offset($offset);
        return $qb;
    }

    private function generate_abstract($string, $maxlength)
    {
        $string = str_replace('<br />', "\n", $string);
        $string = str_replace('</p>', "\n", $string);
        $string = str_replace('</div>', "\n", $string);
        $string = strip_tags($string);
        if (mb_strlen($string) <= $maxlength)
        {
            return $string;
        }

        $buffer = $maxlength * 0.1;
        $string = substr($string, 0, $maxlength + $buffer);

        $last_period = mb_strrpos($string, '.');
        if (   $last_period !== false
            && $last_period > ($maxlength * 0.8))
        {
            // Found a period in the last 20% of string, go with it.
            return mb_substr($string, 0, $last_period + 1);
        }

        $last_space = mb_strrpos($string, ' ');
        return mb_substr($string, 0, $last_space);
    }

    public function get_items(array $args)
    {
        $node = $this->request->get_node()->get_object();
        $this->data['title'] = $node->title;

        if (!isset($args['page']))
        {
            $args['page'] = 0;
        }
        elseif ($args['page'] == 0)
        {
            midgardmvc_core::get_instance()->head->relocate(midgardmvc_core::get_instance()->dispatcher->generate_url('index', array(), $this->request));
        }

        $items_per_page = midgardmvc_core::get_instance()->configuration->index_items;
        $offset = (int) $items_per_page * $args['page'];
        $qb = $this->prepare_qb($node, $items_per_page, $offset);

        if ($args['page'] > 0)
        {
            if ($args['page'] == 1)
            {
                $this->data['previous_page'] = midgardmvc_core::get_instance()->dispatcher->generate_url('index', array(), $this->request);
            }
            else
            {
                $this->data['previous_page'] = midgardmvc_core::get_instance()->dispatcher->generate_url('index_page', array('page' => $args['page'] - 1), $this->request);
            }
        }
        $next_qb = $this->prepare_qb($node, $items_per_page, $offset + $items_per_page);
        $next_items = $next_qb->execute();
        if (count($next_items) > 0)
        {
            $this->data['next_page'] = midgardmvc_core::get_instance()->dispatcher->generate_url('index_page', array('page' => $args['page'] + 1), $this->request);
        }

        $items = $qb->execute();

        if (   $args['page'] > 0
            && empty($items))
        {
            throw new midgardmvc_exception_notfound("Page {$args['page']} not found)");
        }

        $this->data['items'] = new midgardmvc_ui_create_container();
        foreach ($items as $item)
        {
            $item->abstract = $this->generate_abstract($item->content, 200);
            if ($item->node == $node->id)
            {
                // Local news item
                $item->url = midgardmvc_core::get_instance()->dispatcher->generate_url('item_read', array('item' => $item->guid), $this->request);
            }
            else
            {
                $subnode = $this->get_node($item->node);
                if ($subnode->get_component() != 'fi_openkeidas_articles')
                {
                    continue;
                }
                $item->url = midgardmvc_core::get_instance()->dispatcher->generate_url('item_read', array('item' => $item->guid), $subnode->get_path());
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
