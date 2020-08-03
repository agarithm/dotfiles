<?php
namespace Agarithm;

class UI extends Singleton {
	const debug = false;
	public static $counter = 0;

	public function __construct(){
		$this->assignments = array();
		$this->itemLog = array(); //List of CMS items processed
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////
	// UI Decorators (always lower case)
	public static function badge($label,$color='danger'){
		return "<span class='badge badge-$color px-2 m-1'>$label</span>";
	}

	public static function border($html,$class='border-info',$divId='',$attributes=''){return static::box($html,$class,$divId,$attributes);}
	public static function panel($html,$class='border-info',$divId='',$attributes=''){return static::box($html,$class,$divId,$attributes);}
	public static function box($content,$class='border-info',$divId='',$attributes=''){
		$divId = mb_strlen($divId) < 1 ? '' : "id='$divId'";
		return "<div  class='col-md border $class p-2 m-1' $divId $attributes>$content</div>";
	}

	public static function background($imgsrc,$content,$class='',$divId='',$attributes=''){
		$attributes = "style=\"background-image:url('$imgsrc');background-repeat:no-repeat;background-size: cover;\"";
		return static::section($content,$class,$divId,$attributes);
	}

	public static function row($content,$class='',$divId='',$attributes=''){
		$divId = mb_strlen($divId) < 1 ? '' : "id='$divId'";
		return "<div class='row $class' $divId $attributes>$content</div>";
	}

	public static function container($content,$class='',$divId='',$attributes=''){
		$divId = mb_strlen($divId) < 1 ? '' : "id='$divId'";
		return "<div class='container-fluid $class' $divId $attributes>$content</div>";
	}

	public static function html($content,$class='',$divId=false,$attributes=''){
		//Convenience Wrapper to ease Article or Section use
		$divId = mb_strlen($divId) < 1 ? '' : "id='$divId'";
		return "<div class='col-xs-12 px-1 w-100 $class' $attributes $divId >$content</div>";
	}

	public static function alert($content,$class='alert-danger',$divId=false,$attributes=''){
		return '<div class="alert '.$class.' notification"><button data-dismiss="alert" class="close" type="button">&times;</button>'.$content.'</div>';
	}

	public static function article($content,$class='',$divId=false,$attributes=''){
		//use the 10 center columns
		$content = "<div class='col-md-1 col-xl-2'></div><div class='col-md-10 col-xl-8 px-2 py-4'>$content</div><div class='col-md-1 col-xl-2'></div>";
		return static::section($content,$class,$divId,$attributes);
	}

	public static function section($content,$class='',$divId=false,$attributes=''){
		return mb_strlen($content)>0 ? "{{container|{{row|$content}}|$class|$divId|$attributes}}" : "" ;
	}

	public static function log($level=LOGGER::INFO){
		return HTML_ERROR_LOG($level);
	}

	public static function RenderTextArray($arr,$name="array"){
		//Handy little tool for debuggin & displaying multi dimensional arrays / objects
		$out = "";

		$prefix = function(&$key)use(&$arr){return is_array($arr) ? "[".$key."]" : "->$key" ;};

		if(is_array($arr)||is_object($arr)){

			foreach($arr as $key => $value){
				if(is_array($value)||is_object($value)){
					$out .= static::RenderTextArray($value,$name.$prefix($key));
				}else if(is_null($value)){
					$out .=  $name.$prefix($key)." = (null)\n";
				}else if(is_bool($value)){
					$out .=  $name.$prefix($key)." = ".($value ? "(bool) true":"(bool) false")."\n";
				}else{
					$out .=  $name.$prefix($key)." = ".$value."\n";
				}
			}

		}else{
			$out .= "$name = $arr\n";
		}
		return "$out";
	}

	public static function last($macro_csv){
		$parts = explode(',',$macro_csv);
		$macro = implode('x-x-x',$parts);
		return 'x{x{x '.$macro.' x}x}x';
	}

	public  static function assign($key,$value){
		$self = static::instance();
		$self->assignments[$key] = $value;
	}

	public static function datatable($data=array(),$numRows=50,$sortColumn='created',$tools='fpBirtBip',$allowed=false,$visible=false){
		$out = empty($_POST) ? " " : "<h3>".__("None Found")."</h3>";
		$self = static::instance();
		$dtJS = " ";
		TRACE(__METHOD__." START");

		$keep = false;
		if(Strings::Contains($tools,'B')){
			//Removing PII if Downloadable
			//$data = REDACT($data,$allowed,true);
			//downloads can have hidden columns
			$keep = true;
			//change the possible file download name
			$self->assign('seo_title','RD_DATA_'.date('Y-m-d',time()));
		}

		$id = "dt_".mt_rand(1,9999999).time();

		//NOTE: Visible does not mean has access, if has access and visible, then visible = show column in report preview
		$visible = $visible ? $visible : array("client_id",'name','email','phone','modified','status','lender','partner'
			,'status','commission',"transaction_date","comments",'created','id','DTI','life_cycle','actions'
			,'amount','interest','monthly','applied','company','balance','posted_date'
			,'new_leads','abandon_rate','new_applicants','old_applicants','new_commission','old_commission','dark_commission','all_conversion_rate'
			,'last_login','ext_status'
			,'ext_pid','ext_gid'
			,'conversion_rate'
			,'last_seen'
			,'lender_rate_limit'
		);
		$is_visible = function(){return true;}; //function($key)use($visible){return in_array($key,$visible,true);};

		//TODO: Collumn responsive priorities

		if(is_array($data)){
			//Table
			$out = "<div class=w-100><table id='$id' class='table table-sm table-hover' style='width:100%;'>";

			//HEADING
			$out .= '<thead><tr role="row" >';

			$detectedSortColumn=0;
			foreach($data as $item){
				//Post the Header Row (break at end of first)
				$idx = 0;
				foreach($item as $key => $val){
					if(mb_strtolower($sortColumn)===mb_strtolower($key)){
						$detectedSortColumn=$idx;
						TRACE(__METHOD__." Sorting by $key (numRows = $numRows) $detectedSortColumn");
					}
					$class =  $is_visible($key) ? "" : "class='d-none'" ;
					if(empty($class)||$keep){
						$out .=	"<th $class>".ucwords($key)."</th>";
						$idx++;
					}
				}
				//Only do header row detection on first result
				break;
			}
			$out .= "</tr></thead><tbody>";

			//DATA
			foreach($data as $item){
				if(!is_array($item))continue;
				//Post the Value Rows
				$out .= "<tr>";
				foreach($item as $key => $val){
					$class =  $is_visible($key) ? "" : "class='d-none'" ;
					$out .= (empty($class)||$keep) ? "<td $class>".Strings::Human($val)."</td>" : "" ;
				}
				$out .= "</tr>";
			}

			//sortColumn 

			$out .= '</tbody></table></div>';
			$out = Strings::ReplaceAll('>null</td>','></td>',$out);

			//Add in the Datatables JS Stuff
			$out .= "
<script>
{{pack_js |

function initter_$id(){
	if((typeof jQuery !== 'undefined')){
		jQuery(function($) {
			if(typeof $('#$id').DataTable === 'function' ){
				$('#$id').DataTable({
					dom: '$tools',
					buttons: ['csv'],
					lengthMenu: [[$numRows, -1], [$numRows, 'All']],
					order: ['$detectedSortColumn','desc']
					} );
				$('.dt-buttons').addClass('float-left mx-1');
			}else{
				setTimeout(initter_$id,600);
			}
		});
	}else{
		setTimeout(initter_$id,600);
	}
}

initter_$id();
}}
</script>
		";
		}

		TRACE(__METHOD__." END");

		//Datatables can be large, make them paint last
		$tableblock = 'datatable_'.mt_rand(1000,199999);
		$self->assign($tableblock,$out);
		return ""."x{x{x".$tableblock."x}x}x";
	}


	public static function pack_js(){
		require_once(dirname(__FILE__)."/JavascriptPacker.php");

		$args = func_get_args();
		$js = implode('|',$args);
		$packer = new JavascriptPacker($js,(CLEAN::GET('IS_PRODUCTION')?62:0));
		return $packer->pack();
	}

	public static function lineChart($data,$title="",$subtitle="",$yAxis=""){
		$self = static::instance();
		$chartblock = 'chart_'.mt_rand(1000,199999);
		$out = "
		<div id='$chartblock'></div>
		<script>

		google.charts.load('current', {'packages':['corechart']});

		function $chartblock() {
			var data = new google.visualization.DataTable();
			{{COLUMNS}}
			{{ROWS}}
			var w = $('#$chartblock').width()*0.95;

			var options = {
				title: '$title',
				subtitle: '$subtitle',
				vAxis: {  title: '$yAxis'},
				legend: {position: 'none'},
				width: w,
			};

			var chart = new google.visualization.LineChart(document.getElementById('$chartblock'));

			chart.draw(data, options);
		}

		setTimeout($chartblock,1000);
		</script>
		";

		if(count($data)){
			$stuff = array();
			//Build out the Column names from the Associative keys
			$cols = "";
			foreach($data as $item){
				foreach($item as $key =>$value ){
					switch(true){
					case is_numeric($value):
						$cols .= "data.addColumn('number', '$key');".PHP_EOL;
						break;
					default:
						$cols .= "data.addColumn('string', '$key');".PHP_EOL;
						break;
					}
				}
				break;//only do the first item for the column names
			}
			$stuff['COLUMNS'] = $cols;

			//Build out the rows
			$rows = "data.addRows([".PHP_EOL;
			foreach($data as $item){
				$rows .= "[";
				foreach($item as $key =>$value ){
					switch(true){
					case is_numeric($value):
						$rows .= "$value, ";
						break;
					default:
						$rows .= "'$value', ";
						break;
					}
				}
				$rows = Strings::BeforeLast($rows,',',$rows)."], ".PHP_EOL;
			}
			$rows = Strings::BeforeLast($rows,',',$rows)."]);".PHP_EOL;

			$stuff['ROWS'] = $rows;
			$out = static::Paint($out,$stuff);
		}else{
			$out = "<div><h2$title <small>$subtitle</small></h3><p>".__("No data")."</div>";
		}
		//chart data can be large, make them paint last via UI::Finish()
		$self->assign($chartblock,$out);
		return ""."x{x{x".$chartblock."x}x}x";
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////
	// PAINTER TEMPLATE ENGINE = (BSV = BAR Separated Values)  aka Pipe
	//
	// {{ content_name }}
	// {{ func }}
	// {{ func | param 1 | param 2}}
	//
	private static function Macro($macro,&$data,$showPlaceHolders=true){
		$self = static::instance();
		if(static::debug)TRACE(__METHOD__." ".Strings::Shorten(Strings::Trim($macro)));
		$out = '';

		$parts = explode('|',$macro);
		foreach($parts as $key => $value)$parts[$key] = trim($value);
		switch(true){
		case (isset($data[$parts[0]])):
			//Pull the value from supplied data
			$out = $data[$parts[0]];
			break;
		case (is_scalar(FIND_VALUE_BY_KEY($data,$parts[0]))):
			//Pull the value from deep in associative array
			$out = FIND_VALUE_BY_KEY($data,$parts[0]);
			break;
		case (is_scalar(FIND_VALUE_BY_KEY($self->assignments,$parts[0]))):
			//Pull the value from cached block in the assignements
			$out = FIND_VALUE_BY_KEY($self->assignments,$parts[0]);
			break;
		case (method_exists("\Agarithm\UI",mb_strtolower($parts[0]))):
			//Decorate the contents
			$func = mb_strtolower($parts[0]);
			unset($parts[0]);
			$out = call_user_func_array(array($self,$func),array_values($parts));
			break;
		default:
			/*
			if($item = AB::Choose($macro,::Published($macro))){
				$self->itemLog[$item[]['name']] = $item[]['id'];
				$out = $item[]['content'];
				// grab the SEO Stuff from this content
				foreach(array('seo_title','seo_description','seo_schema') as $key){
					if(!empty($item[][$key])){
						//First one found wins.  Usually root the content item prefixed by '/'
						$self->startIfEmpty($key);
						echo Strings::Trim($item[][$key]);
						$self->end();
					}
				}
			}
			 */
			if(empty($out)&&!CLEAN::GET('IS_PRODUCTION')&&$showPlaceHolders)$out = static::escape("{{&nbsp;$macro&nbsp;}}");
			break;
		}

		return static::Paint($out,$data);//Recurse just in case the macro produced another macro
	}

	public static function Paint($page,$data=array(),$showPlaceHolders=true){
		//Very simple State Machine Parser for {{ }} macros
		if(static::$counter==0)INFO(__METHOD__." STARTED " );
		static::$counter++;
		if(static::debug)TRACE(__METHOD__." ".Strings::Shorten(Strings::Trim($template)));

		//Comments allow us to write notes in the template that will not appear in the rendered HTML
		while( ! Strings::isEmpty($comment = Strings::BetweenNested($page,"{{/*","*/}}")) ){
			$pre = Strings::Before($page,"{{/*".$comment."*/}}");
			$post = Strings::After($page,"{{/*".$comment."*/}}");
			$page = $pre.$post;//remove the comment
		}

		//Literals allow us to write the template lang as content and choose not to process it
		while( ! Strings::isEmpty($literal = Strings::BetweenNested($page,"{{!","!}}")) ){
			$pre = Strings::Before($page,"{{!".$literal."!}}");
			$post = Strings::After($page,"{{!".$literal."!}}");
			$page = $pre.static::escape($literal).$post;
		}

		//Now Paint what's left...
		while( ! Strings::isEmpty($macro = Strings::BetweenNested($page,"{{","}}")) ){
			$pre = Strings::Before($page,"{{".$macro."}}");
			$post = Strings::After($page,"{{".$macro."}}");
			$page = $pre.static::Macro($macro,$data,$showPlaceHolders).$post;
		}
		return static::escape($page);
	}

	public static function Finish($page,$data=array(),$showPlaceHolders=true){
		//1st Pass: paint the base page
		$page = static::Paint($page,$data,$showPlaceHolders);
		//2nd Pass: pick up the heavy items or items that must be painted last:  Logs, DataTables, etc.
		INFO(__METHOD__." Count = ".static::$counter);
		$page = Strings::ReplaceAll('x{x{x','{{',$page);
		$page = Strings::ReplaceAll('x}x}x','}}',$page);
		$page = Strings::ReplaceAll('x-x-x','|',$page);
		return static::unescape(static::Paint($page,$data,$showPlaceHolders));
	}

	public static function escape($value){
		// Template Injection Protection:
		// {{ , | , }} are used in the template language
		// MySQL holds most DATA, whereas SQLite holds the Templated Content
		// So, if a user tries to trigger Template escapes, their data will
		// be escaped here to prevent it while Admins using the templates are safe
		if(is_string($value)){
			$value = Strings::ReplaceAll('{{','&#123;&#123;',$value);
			$value = Strings::ReplaceAll('|','&#124;',$value);
			$value = Strings::ReplaceAll('}}','&#125;&#125;',$value);
			//CakePHP BUG: re-saving escaped strings accumulates &amp; for & everytime... &amp;amp;amp;
			$value = Strings::ReplaceAll('&amp;amp;','&amp;',$value);
			$value = Strings::ReplaceAll('&amp;#','&#',$value);
		}
		return $value;
	}

	public static function unescape($value){
		// Template Injection Protection:
		// {{ , | , }} are used in the template language
		// MySQL holds most DATA, whereas SQLite holds the Templated Content
		// So, if a user tries to trigger Template escapes, their data will
		// be escaped here to prevent it while Admins using the templates are safe
		if(is_string($value)){
			$value = Strings::ReplaceAll('&#123;&#123;','{{',$value);
			$value = Strings::ReplaceAll('&#124;','|',$value);
			$value = Strings::ReplaceAll('&#125;&#125;','}}',$value);
		}
		return $value;
	}
}
