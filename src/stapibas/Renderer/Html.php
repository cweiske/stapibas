<?php
namespace stapibas;

class Renderer_Html
{
    public $db;
    public $log;

    public function __construct(Dependencies $deps)
    {
        $this->deps = $deps;
        $this->db   = $deps->db;
        $this->log  = $deps->log;

        \Twig_Autoloader::register();
        $loader = new \Twig_Loader_Filesystem($this->deps->options['template_dir']);
        $this->deps->twig = new \Twig_Environment(
            $loader,
            array(
                //'cache' => '/path/to/compilation_cache',
                'debug' => true
            )
        );
    }

    public function render($url)
    {
        $arData = $this->loadData($url);
        header('Content-type: text/html');
        $this->renderHtml('mentions', array('arData' => $arData));
    }

    /**
     * Fetches all bookmarks, comments and links
     */
    protected function loadData($url)
    {
        $arData = array(
            'bookmarks' => array(),
            'comments'  => array(),
            'links'     => array(),
        );

        $stmt = $this->db->query(
            'SELECT * FROM pingbacks, rbookmarks'
            . ' WHERE p_id = rb_p_id AND p_use = 1'
            . ' AND p_target = ' . $this->db->quote($url)
            . ' ORDER BY p_time ASC'
        );
        $arData['bookmarks'] = $stmt->fetchAll();

        $stmt = $this->db->query(
            'SELECT * FROM pingbacks, rcomments'
            . ' WHERE p_id = rc_p_id AND p_use = 1'
            . ' AND p_target = ' . $this->db->quote($url)
            . ' ORDER BY p_time ASC'
        );
        $arData['comments'] = $stmt->fetchAll();

        $stmt = $this->db->query(
            'SELECT * FROM pingbacks, rlinks'
            . ' WHERE p_id = rl_p_id AND p_use = 1'
            . ' AND p_target = ' . $this->db->quote($url)
            . ' ORDER BY p_time ASC'
        );
        $arData['links'] = $stmt->fetchAll();

        return $arData;
    }

    protected function renderHtml($tplname, $vars = array())
    {
        $template = $this->deps->twig->loadTemplate($tplname . '.htm');
        echo $template->render($vars);
    }

}
?>
