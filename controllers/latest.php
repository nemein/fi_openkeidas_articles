<?php
class fi_openkeidas_articles_controllers_latest
{
    public function __construct(midgardmvc_core_request $request)
    {
        $this->request = $request;
    }

    private function check_categories(midgard_query_builder $qb, array $args)
    {
        $categories = midgardmvc_core::get_instance()->configuration->categories;

        if (!isset($args['category']))
        {
            return;
        }

        // Check that the user-provided category is valid
        if (!isset($categories[$args['category']]))
        {
            throw new midgardmvc_exception_notfound("Category {$args['category']} not found");
        }

        if (isset($args['subcategory']))
        {
            if (!isset($categories[$args['category']]['categories'][$args['subcategory']]))
            {
                throw new midgardmvc_exception_notfound("Category {$args['category']}/{$args['subcategory']} not found");
            }

            $qb->add_constraint('category', '=', "{$args['category']}/{$args['subcategory']}");
            $this->data['title'] = $categories[$args['category']]['categories'][$args['subcategory']]['title'];

        }
        else
        {
            $qb->add_constraint('category', 'LIKE', "{$args['category']}/%");
            $this->data['title'] = $categories[$args['category']]['title'];
        }
    }

    public function get_items(array $args)
    {
        $this->data['title'] = 'Uusimmat artikkelit';
        $qb = new midgard_query_builder('fi_openkeidas_articles_article');
        $this->check_categories($qb, $args);
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
