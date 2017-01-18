<?
class Ivona extends IPSModule
{
    
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        
        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        $this->RegisterPropertyString("accessKey", "");
        $this->RegisterPropertyString("secretKey", "");
        $this->RegisterPropertyString("voice", "Marlene");
		$this->RegisterPropertyString("region", "eu-west-1");
        $this->RegisterPropertyString("defaultPath", "");
        $this->RegisterPropertyBoolean("deleteFiles", true);
        $this->RegisterPropertyInteger("deleteMinutes", 15);
        $this->RegisterPropertyString("accessPath", "");
    }
    
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        
        IPS_SetHidden($this->InstanceID,true);

        $deleteFilesScript = '<?
$path = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "defaultPath");

if($path && IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "deleteFiles")){  

  $minutes = IPS_GetProperty(IPS_GetParent($_IPS["SELF"]), "deleteMinutes");
  if ($handle = opendir($path)) {

    while (false !== ($file = readdir($handle))) {
      if ((time()-fileatime($path."/".$file)) > $minutes*60) {
        if (preg_match("/\.mp3$/i", $file)) {
          unlink($path."/".$file);
        }
      }
    }
  }
}
?>';

        $deleteScriptID = @$this->GetIDForIdent("_deleteFiles");
        if ( $deleteScriptID === false ){
          $deleteScriptID = $this->RegisterScript("_deleteFiles", "_deleteFiles", $deleteFilesScript, 99);
        }else{
          IPS_SetScriptContent($deleteScriptID, $deleteFilesScript);
        }

        IPS_SetHidden($deleteScriptID,true);
        IPS_SetScriptTimer($deleteScriptID, 300); 
    }
    
    public function getMP3($text)
    {
        include_once(__DIR__ . "/polly.php");
        return (new POLLY_TTS( $this->ReadPropertyString("accessKey") , 
                               $this->ReadPropertyString("secretKey") , 
                               $this->ReadPropertyString("region") , 
                               $this->ReadPropertyString("voice") ))->get_mp3($text);
    }

    public function saveMP3($text)
    {
        $file_name = md5($text).".mp3";

        $path = $this->ReadPropertyString("defaultPath");
        if($path === '') $path = sys_get_temp_dir();
        $save_file = $path . "/" . $file_name;

        $access_path = $this->ReadPropertyString("accessPath");

        if( $access_path ){
          $return_file = $access_path."/".$file_name;
        }else{
          $return_file = $save_file;
        }

        if(file_exists($save_file)) return $return_file;

        include_once(__DIR__ . "/polly.php");
        (new POLLY_TTS( $this->ReadPropertyString("accessKey") ,
                        $this->ReadPropertyString("secretKey") , 
                        $this->ReadPropertyString("region") , 
                        $this->ReadPropertyString("voice") ))->save_mp3($text, $save_file);

        return $return_file;
    }
}
?>
