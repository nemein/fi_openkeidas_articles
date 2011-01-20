<?php
class fi_openkeidas_articles_controllers_item extends midgardmvc_core_controllers_baseclasses_crud
{
    public function load_object(array $args)
    {
        $this->object = new fi_openkeidas_articles_article($args['item']);

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
            'item_update', array
            (
                'item' => $this->object->guid
            ),
            $this->request
        );
    }

    public function load_form()
    {
        $this->form = midgardmvc_helper_forms_mgdschema::create($this->object, false);

        // Make Category and Type proper selects instead
        unset($this->form->category);
        unset($this->form->type);

        $field = $this->form->add_field('category', 'text');
        $field->set_value($this->object->category);
        $widget = $field->set_widget('selectoption');
        $widget->set_label('category');
        $category_options = array();
        $categories = midgardmvc_core::get_instance()->configuration->categories;
        foreach ($categories as $category => $category_data)
        {
            $category_options[] = array
            (
                'description' => $category_data['title'],
                'value' => $category,
            );

            if (isset($category_data['categories']))
            {
                foreach ($category_data['categories'] as $subcategory => $subcategory_data)
                {
                    $category_options[] = array
                    (
                        'description' => "&gt; {$subcategory_data['title']}",
                        'value' => "{$category}/{$subcategory}",
                    );
                }
            }
        }

        $widget->set_options($category_options);
    }
}
?>
