<?php
/**
 * @package fi_openkeidas_articles
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package fi_openkeidas_articles
 */
class fi_openkeidas_articles_injector
{
    public function inject_process(midgardmvc_core_request $request)
    {
        static $connected = false;

        if (!$connected)
        {
            // Subscribe to content changed signals from Midgard
            midgard_object_class::connect_default('fi_openkeidas_articles_article', 'action-create', array('fi_openkeidas_articles_injector', 'check_node'), array($request));
            $connected = true;
        }

        $component = $request->get_node()->get_component();
        if ($component == 'fi_openkeidas_articles')
        {
            midgardmvc_core::get_instance()->authorization->require_user();
        }
    }

    public function inject_template(midgardmvc_core_request $request)
    {
        // We inject the template to provide Open Keidas styling
        $request->add_component_to_chain(midgardmvc_core::get_instance()->component->get('fi_openkeidas_articles'), true);
    }

    public static function check_node(fi_openkeidas_articles_article $article, $params)
    {
        if ($article->node)
        {
            return;
        }

        $request = midgardmvc_core::get_instance()->context->get_request();
        $node = $request->get_node();
        if (!$node)
        {
            return;
        }

        $node_object = $node->get_object();
        if (!$node_object instanceof midgardmvc_core_node)
        {
            return;
        }

        $article->node = $node_object->id;
    }
}
?>