<?
/* <php>syntax hilited php code</php>
 * <c>html or php</c>
 * <r>result as var_dump</r>
 * <note>notiz</note>
 * <link>url</link>
 * <in>eingerück</in>
 */
	include "geshi\\geshi.php";

	$phpgeshi = new GeSHi('','php');
	$phpgeshi->enable_classes();
?>
<html>
<head>
   <title>kata FAQ</title>
   <meta name="generator" content="format.php">
</head>
<style type="text/css">
* {
	font-size:11pt;
}
a {
	color:black;
}
pre.php, span.xdebug-var-dump, span.code, pre.code {
   color:black;
   font-size:9pt;
   background-color:#e8e8f8;
   font-family:Courier, 'Courier New', monospace;
}
pre.php > * {
	font-size:9pt !important;
}
pre.php, h2, h3, div.code, pre.result {
	margin-top:4px;
	margin-bottom:4px;
	margin-left:0;
	margin-right:0;
}
span.result, pre.result {
   color:black;
   font-family:courier new;
   font-size:10pt;
   background-color:#f8e8e8;
}
h2 {
	font-weight:bold;
	text-decoration:underline;
	font-size:14pt !important;
        margin-bottom:10px;
}
h3 {
	font-weight:bold;
	text-decoration:underline;
	font-size:12pt !important;
        margin-bottom:10px;
}
div.indent {
	border-left:2px solid #e0e0e0;
	padding-left:5px;
	margin-left:30px;
}
div.newtopict {
     margin-top:50px;
}
div.newtopicb {
     border-top:1px solid grey;
     margin-bottom:70px;
}
div.newttopict {
     margin-top:50px;
}
div.newttopicb {
     border-top:3px solid grey;
     height:2px;
     border-bottom:3px solid grey;
     margin-bottom:70px;
}
.top {
         text-align:right;
         font-size:8pt !important;
         float:right;
}
div.liinner {
      float:left;
      padding-right:6px;
}
div.li {
       padding:2px;
       clear:left;
}
table.framed {
        border-collapse:collapse;
        border:2px solid black;
}
table.framed tr td {
	border:1px solid black;
	border-bottom:2px solid black;
	padding:4px 2px;
	color:black !important;
	background-color:#f8f8f8;
	vertical-align:top;
}
table.framed tr.odd td {
        background-color:#f0f0f0 !important;
}
<? echo $phpgeshi->get_stylesheet(); ?>
</style>
<body>

<img align="right" src="kata.png" alt="black belt" title="black belt" />
<a name="____top"><h2>kata FAQ</h2></a>
<?
$topictrenner = '<div class="newtopict"></div><a href="#____top"><div class="top">top</div></a><div class="newtopicb"></div>';
$haupttopictrenner = '<div class="newttopict"></div><a href="#____top"><div class="top">top</div></a><div class="newttopicb"></div>';

$lines = file($argv[1]);

$topics = array();

$liMode = false;
$topicidx = 0;
$php = '';

ob_start();
foreach ($lines as $lineno=>$line) {
   $line = rtrim($line);
   $isTopic = false;
   if (substr($line,0,3)=='---') {
      if (substr($line,0,5)=='-----') {
            $topic = '<h2>'.substr($line,5).'</h2>';
            $isTopic = true;
      } else {
            $topic = substr($line,3);
      }

      if ($topicidx>0) {
         if ($isTopic) {
            echo $haupttopictrenner;
         } else {
           echo $topictrenner;
         }
      }

      $topicArr = explode('|',$topic);
      if (count($topicArr) == 1) {
	      $topics['topic'.$topicidx]=$topic;
	      echo "<h3><a name=\"topic$topicidx\">".$topic."</a></h3>\n";
      } else {
	      $topics[ $topicArr[1] ]= $topicArr[0];
	      echo '<h3><a name="'.$topicArr[1].'">'.$topicArr[0].'</a></h3>'."\n";
      }

      $topicidx++;
      continue;
   }

	$line = htmlentities($line);

        $trString = ($lineno%2==0 ? '<tr><td>' : '<tr class="odd"><td>');

	$line = str_replace(
		array(
			'&lt;c&gt;','&lt;/c&gt;', //c
			'&lt;cc&gt;','&lt;/cc&gt;', //cc
			'&lt;r&gt;','&lt;/r&gt;', //r
			'&lt;rr&gt;','&lt;/rr&gt;', //rr
			'&lt;in&gt;','&lt;/in&gt;', //in
			'&lt;i&gt;','&lt;/i&gt;', //i
                        '&lt;b&gt;','&lt;/b&gt;', //b
                        '&lt;img&gt;','&lt;/img&gt;', //img
                        '&lt;ltd&gt;','&lt;mtd&gt;', '&lt;/ltd&gt;', //tr
                        '&lt;table&gt;','&lt;/table&gt;', //ftable
                        '&lt;br&gt;',
                        '@&lt;',
                        ),
		array(
			'<span class="code">','</span>',
			'<pre class="code">','</pre>',
			'<span class="result">','</span>',
			'<pre class="result">','</pre>',
			'<div class="indent">','</div>',
			'<i>','</i>',
                        '<b>','</b>',
                        '<img src="','" />',
                        $trString,'</td><td>', '</td></tr>',
                        '<table class="framed">','</table>',
                        '<br />',
                        '<',
		), $line);

       if (substr($line,0,2)=='- ') {
            echo '<div class="li"><div class="liinner">&bull;</div>'.substr($line,2).'</div>';
       } else {
            echo $line;
        }

        if (trim($line)=='') {
           echo "<br /><br />";
        }

	echo "\n";
}

$html = ob_get_clean();

while(1) {
	$x1 = strpos($html,'&lt;php&gt;');
	$x2	= strpos($html,'&lt;/php&gt;');

	if (false === $x1) {
		break;
	}
	if (false === $x2) {
		die('missing closing /php tag');
	}

	$php = html_entity_decode(substr($html,$x1+11,$x2-$x1-11));
	$php = str_replace('<br />','',$php);
	$phpgeshi->set_source($php);
	$php = $phpgeshi->parse_code();

	$html = substr($html,0,$x1).$php.substr($html,$x2+12);
}

$html = str_replace(array("</pre>\n<br />","</pre><br />"),"</pre>\n",$html);

//<link>
while (1) {
	$x1 = strpos($html,'&lt;link&gt;');
	$x2 = strpos($html,'&lt;/link&gt;');
	if (($x1!==false) && ($x2!==false)) {
		$url = substr($html,$x1+12,$x2-$x1-12);
		$urlArr = explode('|',$url);
		if (count($urlArr)==1) {
			$html = substr($html,0,$x1).'<a href="'.$url.'">'.$url.'</a>'.substr($html,$x2+13);
		} else {
			$html = substr($html,0,$x1).'<a href="'.$urlArr[0].'">'.$urlArr[1].'</a>'.substr($html,$x2+13);
		}
	} else {
		break;
	}
}



$htmlArr = explode('<pre',$html);
foreach ($htmlArr as &$h) {
   $x=strrpos($h,'</pre>');
   $h = str_replace('<br />','',substr($h,0,$x)).substr($h,$x);
}
$html = implode('<pre',$htmlArr);


echo '<div class="topics"><ul>';
foreach ($topics as $topicidx=>$topic) {
        if (substr($topic,0,1)=='<') {
        	echo '<br /><li><a href="#'.$topicidx.'">'.$topic.'</a></li>';
        } else {
        	echo '<li><a href="#'.$topicidx.'">'.$topic.'</a></li>';
        }
}
echo '</ul></div>';

echo $topictrenner;

echo '<div class="content">'.$html.'</div>';

?>

</body>
</html>
