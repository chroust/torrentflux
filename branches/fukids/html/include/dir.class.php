<?
class dir{
    var $name;
    var $subdirs;
    var $files;
    var $num;
    var $prio;
    function dir($name,$num,$prio){
        $this->name = $name;
        $this->num = $num;
        $this->prio = $prio;
        $this->files = array();
        $this->subdirs = array();
    }
    function &addFile($file){
        $this->files[] =& $file;
        return $file;
    }
    function &addDir($dir){
        $this->subdirs[] =& $dir;
        return $dir;
    }
    function &findDir($name){
        foreach (array_keys($this->subdirs) as $v){
            $dir =& $this->subdirs[$v];
            if($dir->name == $name){
                return $dir;
            }
        }
        return false;
    }

    function draw($parent,$subtree=0){
        //echo("d.add(".$this->num.",".$parent.",\"".$this->name."\",".$this->prio.",0);\n");
		$str=$subtree?'<li><div class="mui-tree"> <div class="mui-treetitle">'.$this->name.'</div><ul>':
		'<div class="mui-tree" id="t1"><div class="mui-treetitle"></div><ul>';
        foreach($this->subdirs as $v){
            $v->draw($this->num,1);
        }
        foreach($this->files as $v){
            if(is_object($v)){
              //echo("d.add(".$v->num.",".$this->num.",\"".$v->name."\",".$v->prio.",".$v->size.");\n");
			  $str.='<li>'.$v->name.''.$v->prio.'</li>';
            }
        }
		$str.=$subtree?'</ul></div></li>':'</ul></div>';
        return $str;
    }

}

class file {

    var $name;
    var $prio;
    var $size;
    var $num;

    function file($name,$num,$size,$prio)
    {
        $this->name = $name;
        $this->num  = $num;
        $this->size = $size;
        $this->prio = $prio;
    }
}

?>