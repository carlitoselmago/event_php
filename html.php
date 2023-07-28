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
            <title>'.$this->E->title().'</title>

            <!--CSS-->';
            $this->set_template_chunk($this->template."extra_head.php");
            echo '
            <link rel = "stylesheet/css" type="text/css" href="'.$this->assets.'css/fonts/onts.css" />
            <link rel = "stylesheet/less" type="text/css" href="'.$this->assets.'css/base.less" />
            <link rel = "stylesheet/less" type="text/css" href="'.$this->template.'fonts/fonts.css" />
            <link rel = "stylesheet/less" type="text/css" href="'.$this->template.'template.less" />
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

}