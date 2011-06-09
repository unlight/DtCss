<?php if (!defined('APPLICATION')) die();

$PluginInfo['DtCss'] = array(
	'Name' => 'DtCss',
	'Description' => 'Adapts DtCSS to work with Garden. DtCSS is a PHP script that preprocesses your CSS file. It speeds up CSS coding by extending the features to CSS. Such as nested selectors, color mixing and more. DtCSS reads the CSS file with special syntax written for DtCSS, and outputs the standard CSS. <a href="http://code.google.com/p/dtcss/">DtCSS project home page</a>',
	'Version' => '1.3f',
	'Author' => 'DtCss',
	'Date' => 'Summer 2011',
	'AuthorUrl' => 'http://github.com/search?q=DtCss&type=Repositories',
	'RequiredApplications' => False,
	'RequiredTheme' => False,
	'RequiredPlugins' => False,
	'RegisterPermissions' => False,
	'SettingsPermission' => False,
	'License' => 'X.Net License'
);

/*
TODO:
- enable expressions
- parse error handling
*/

class DtCssPlugin extends Gdn_Plugin {
	
/*	public function Tick_Every_20_Days_Handler() {
		self::_EmptyCache();
	}
	
	public function SettingsController_AfterEnablePlugin_Handler() {
		self::_EmptyCache();
	}
	
	public function SettingsController_AfterDisablePlugin_Handler() { 
		self::_EmptyCache();
	}*/
	
	public static function _EmptyCache() {
		$DirectoryAry = array(PATH_APPLICATIONS, PATH_PLUGINS, PATH_THEMES);
		foreach($DirectoryAry as $DirectoryPath) {
			$Directory = new RecursiveDirectoryIterator($DirectoryPath);
			foreach(new RecursiveIteratorIterator($Directory) as $File){
				$Basename = $File->GetBasename();
				$Extension = pathinfo($Basename, 4);
				$Filename = pathinfo($Basename, 8);
				if ($Extension != 'css') continue;
				if (!preg_match('/^[\.\w\-]+\-c\-[a-z0-9]{5,7}$/', $Filename)) continue;
				$CachedFile = $File->GetRealPath();
				unlink($CachedFile);
			}
		}
	}
	
	public function PluginController_DtCssDemo_Create($Sender) {
		$Sender->AddCssFile( $this->GetWebResource('css/demo.dt.css') );
		$Sender->View = $this->GetView('demo.php');
		$Sender->Render();
	}
	
	
	protected static function GetHash($S) {
		$Crc = sprintf('%u', crc32($S));
		$Hash = base_convert($Crc, 10, 36);
		$Hash = sprintf("%06s", substr($Hash, -6)); // zero-padding for string length < 6
		return $Hash;
	}
	
	public static function MakeCssFile($CssPath, $CachedCssFile) {
		if (!function_exists('DtCSS')) require dirname(__FILE__) . '/DtCSS-R27f/libdtcss.php';
		$Filedata = file_get_contents($CssPath);
		// $CssPath need for #include directives, will use dirname($CssPath)/other.css
		$Data = DtCSS($CssPath, $Filedata);
		file_put_contents($CachedCssFile, $Data);
	}
	
	public function Base_BeforeAddCss_Handler($Sender) {
		if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL) return;
		$CssFiles =& $Sender->EventArguments['CssFiles'];
		foreach ($CssFiles as $Index => $CssInfo) {
			$CssFile = $CssInfo['FileName'];
			if (substr($CssFile, -7) != '.dt.css') continue;
			$AppFolder = $CssInfo['AppFolder'];
			if ($AppFolder == '') $AppFolder = $Sender->ApplicationFolder;
			$CssPaths = array();
			if(strpos($CssFile, '/') !== False) {
				$CssPaths[] = CombinePaths(array(PATH_ROOT, $CssFile));
			} else {
				if ($Sender->Theme) $CssPaths[] = PATH_THEMES . DS . $Sender->Theme . DS . 'design' . DS . $CssFile;
				$CssPaths[] = PATH_APPLICATIONS . DS . $AppFolder . DS . 'design' . DS . $CssFile;
				$CssPaths[] = PATH_APPLICATIONS . DS . 'dashboard' . DS . 'design' . DS . $CssFile;				
			}
			
			$CssPath = False;
			foreach($CssPaths as $Glob) {
				$Paths = SafeGlob($Glob);
				if(count($Paths) > 0) {
					$CssPath = $Paths[0];
					break;
				}
			}
			
			if($CssPath == False) continue; // not found

			$Basename = pathinfo(pathinfo($CssPath, 8), 8); // without .dt.css
			$Hash = self::GetHash($CssPath . filemtime($CssPath));
			
			$CacheFileName = sprintf('__%s-c-%s.css', $Basename, $Hash);
			$CachedCssFile = dirname($CssPath).DS.$CacheFileName;
			if (!file_exists($CachedCssFile)){
				self::MakeCssFile($CssPath, $CachedCssFile, True);
			}
			
			// TODO: use AbsoluteSource() from 2.0.18
	
			// ... and replace preprocessored dt.css file by css
			$CssInfo['FileName'] = substr($CachedCssFile, strlen(PATH_ROOT));
			$CssFiles[$Index] = $CssInfo; // AppFolder nevermind (will be ignored)

		}
		

	}

	public function Setup() {
		// Nothing to do
	}
	
	
	
}

