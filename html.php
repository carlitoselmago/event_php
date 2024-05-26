<?php
class HTML{

    private $assets="/event_php/assets/";
    public $template="event_php/template/";

    function __construct($E) {
        $this->E=$E;
    }

    function head(){
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            

            '.$this->opengraph().'

            <!--CSS-->';
            $this->set_template_chunk($this->template."extra_head.php");
            echo '
            <link rel = "stylesheet/css" type="text/css" href="'.$this->assets.'css/fonts/fonts.css" />
            <link rel = "stylesheet/less" type="text/css" href="'.$this->assets.'css/base.less" />
            <link rel = "stylesheet/less" type="text/css" href="/'.$this->template.'fonts/fonts.css" />
            <link rel = "stylesheet/less" type="text/css" href="/'.$this->template.'template.less" />
            <script src="'.$this->assets.'js/less.js" ></script>
        </head>
        <body>
        <nav >';
        
        $this->set_template_chunk($this->template."head.html");

        echo'</nav>

        <div id="main">
        <article>
        
        ';
    }


    function opengraph(){
        $H='
        <title>'.$this->E->title().'</title>
        <meta property="og:title" content="'.$this->E->title().'" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="'.$this->E->getUrl().'" />';
        //check if ogimage exists and alert if it doesn't
        $ogimagepath="event_php/template/img/sm.jpg";
        if(!is_file($ogimagepath)){
            $this->E->var_dump("No SM ogimage detected");
        }
        $H.='<meta property="og:image" content="'.$this->E->getUrl().'event_php/template/img/sm.jpg" />';
        return $H;
    }

    function bottom(){
        
        echo '
        </article>
        </div>';

        $this->set_template_chunk($this->template."footer.html");

       
        echo'<script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>';
        echo' </body>
        </html>';
    }

    function set_template_chunk($file){
        if(!is_file($file)){
            $contents = 'Fill this template --> '.$file;           // Some simple example content.
            file_put_contents($file, $contents);     // Save our content to the file.
        }
        include_once($file);
    }

    /*** admin  ***/
    function admin_head(){
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <!--CSS-->';
            echo '
            <link rel = "stylesheet/css" type="text/css" href="'.$this->assets.'css/fonts/fonts.css" />
            <link rel = "stylesheet/less" type="text/css" href="'.$this->assets.'css/admin.less" />
            <script src="'.$this->assets.'js/less.js" ></script>
        </head>
        <body>
        <nav >';
    }

    function admin_bottom(){
        echo '
        </article>
        </div>';
        echo'<script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>';
        echo' </body>
        </html>';
    }

}
