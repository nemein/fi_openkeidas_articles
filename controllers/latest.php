<?php
class fi_openkeidas_articles_controllers_latest
{
    public function __construct(midgardmvc_core_request $request)
    {
        $this->request = $request;
    }

    public function get_items(array $args)
    {
        $this->data['title'] = 'Uusimmat artikkelit';
        $qb = new midgard_query_builder('fi_openkeidas_articles_article');
        
        $node = $this->request->get_node()->get_object();
        $qb->add_constraint('node', 'INTREE', $node->id);

        $qb->add_order('metadata.created', 'DESC');
        $qb->set_limit(midgardmvc_core::get_instance()->configuration->index_items);
        $items = $qb->execute();

        $this->data['items'] = array();
        foreach ($items as $item)
        {
            // Local news item, generate link
            $item->url = midgardmvc_core::get_instance()->dispatcher->generate_url('item_read', array('item' => $item->guid), $this->request);
            $this->data['items'][] = $item;
        }
    }
}
