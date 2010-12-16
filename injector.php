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
        // Subscribe to content changed signals from Midgard
        //midgard_object_class::connect_default('fi_openkeidas_articles_article', 'action-create', array('fi_openkeidas_articles_injector', 'check'), array($request));
    }

    public static function check($article, $params)
    {
        return;
    }
}
?>
