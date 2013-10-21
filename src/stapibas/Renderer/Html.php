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
            'SELECT * FROM linkbacks, rbookmarks'
            . ' WHERE l_id = rb_l_id AND l_use = 1'
            . ' AND l_target = ' . $this->db->quote($url)
            . ' ORDER BY l_time ASC'
        );
        $arData['bookmarks'] = $stmt->fetchAll();

        $stmt = $this->db->query(
            'SELECT * FROM linkbacks, rcomments'
            . ' WHERE l_id = rc_l_id AND l_use = 1'
            . ' AND l_target = ' . $this->db->quote($url)
            . ' ORDER BY l_time ASC'
        );
        $arData['comments'] = $stmt->fetchAll();

        $stmt = $this->db->query(
            'SELECT * FROM linkbacks, rlinks'
            . ' WHERE l_id = rl_l_id AND l_use = 1'
            . ' AND l_target = ' . $this->db->quote($url)
            . ' ORDER BY l_time ASC'
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
