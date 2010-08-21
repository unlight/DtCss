<?php if (!defined('APPLICATION')) die();

$PluginInfo['DtCss'] = array(
	'Name' => 'DtCss',
	'Description' => 'Adapts DtCSS to work with Garden.',
	'Version' => '1.1',
	'Date' => '22 Aug 2010',
	'AuthorUrl' => 'http://code.google.com/p/dtcss/',
	'RequiredApplications' => False,
	'RequiredTheme' => False, 
	'RequiredPlugins' => False,
	'RegisterPermissions' => False,
	'SettingsPermission' => False
);

/*
TODO:
- enable expressions
- parse error handling
- empty cache while enabling/disabling plugin
*/

/*
CHANGELOG
1.1 / 22 Aug 2010

1.0 / 21 Aug 2010
- save cached css file to same directory where .dt.css file

0.9 / 20 Aug 2010
- first release

*/

class DtCssPlugin extends Gdn_Plugin {
	
/*	public function SettingsController_DashboardData_Handler() {
		static $bWait = False;
		// remove cached files
		$LogFile = dirname(__FILE__).DS.'log.php';
		if(!file_exists($LogFile)) return;
		$UntouchedTime = time() - filemtime($LogFile);
		$DeleteTime = strtotime('+5 days', 0);
		if($UntouchedTime < $DeleteTime) return;
		$LoggedData = array_map('trim', file($LogFile));
		for($Count = count($LoggedData), $i = 1; $i < $Count; $i++){
			$File = $LoggedData[$i];
			if(file_exists($File)) unlink($File);
		}
		unlink($LogFile);
	}*/
	
	public static function SaveLog($CachedCssFile) {
		static $LogFile;
		if (is_null($LogFile)){
			$LogFile = dirname(__FILE__).DS.'log.php';
			if(!file_exists($LogFile)) Gdn_FileSystem::SaveFile($LogFile, "<?php exit();\n");
		}
		file_put_contents($LogFile, $CachedCssFile."\n", FILE_APPEND);
	}
	
	public function PluginController_DtCssDemo_Create($Sender) {
		$Sender->AddCssFile( $this->GetWebResource('css/demo.dt.css') );
		$Sender->View = $this->GetView('demo.php');
		$Sender->Render();
	}
	
	
	public static function GetHash($S) {
		$Crc = sprintf('%u', crc32($S));
		$Hash = base_convert($Crc, 10, 36);
		$Hash = substr($Hash, -6);
		return $Hash;
	}
	
	public static function MakeCssFile($CssPath, $CachedCssFile) {
		if (!function_exists('DtCSS')) require dirname(__FILE__).DS.'DtCSS-R27f'.DS.'libdtcss.php';
		$Filedata = file_get_contents($CssPath);
		// $CssPath need for #include directives, will use dirname($CssPath)/other.css
		$Data = DtCSS($CssPath, $Filedata);
		file_put_contents($CachedCssFile, $Data);
	}
	
	public function Base_BeforeAddCss_Handler($Sender) {
		if($Sender->DeliveryType() != DELIVERY_TYPE_ALL) return;
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
			
			$CacheFileName = sprintf('%s-c-%s.css', $Basename, $Hash);
			$CachedCssFile = dirname($CssPath).DS.$CacheFileName;
			if (!file_exists($CachedCssFile)){
				self::SaveLog($CachedCssFile); // for deleting
				self::MakeCssFile($CssPath, $CachedCssFile, True);
			}
	
			// ... and replace preprocessored dt.css file by css
			$CssInfo['FileName'] = substr($CachedCssFile, strlen(PATH_ROOT));
			$CssFiles[$Index] = $CssInfo; // AppFolder nevermind (will be ignored)

		}
		

	}

	public function Setup() {
	}
	
	
	
}
