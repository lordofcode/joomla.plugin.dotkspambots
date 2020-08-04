<?php
// no direct access
defined( '_JEXEC' ) or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;

class PlgSystemDotkspambots extends JPlugin
{
    
	public function onUserLoginFailure($response)
	{    
		try
		{
            if (isset($response["username"])){
                global $_SESSION;                
                if (isset($_SESSION["dotk_failed_logins"])) {
                    $_SESSION["dotk_failed_logins"] = intval($_SESSION["dotk_failed_logins"]) + 1;
                }
                else {
                    $_SESSION["dotk_failed_logins"] = 1;
                }
                $un = $response["username"];
                $db = Factory::getDbo();

                $query = $db->getQuery(true);
                $query->select ('COUNT(1) AS aantal');
                $query->from($db->quoteName('#__users', 'u'));
                $query->where('u.username = ' . $db->quote($un));

                $db->setQuery($query);
                if (($db->loadObject()->aantal == 0)||($_SESSION["dotk_failed_logins"] >= 5)) {
                    $htAccessCurrent = <<< EOT
# SPAM BOTS TOEGANG ONTZEGGEN
<Limit POST PUT>
    order allow,deny
    allow from all
#IPADDRESSES#    
</Limit>
EOT;
                    chdir(JPATH_ADMINISTRATOR);
                    if (file_exists(".htaccess")){
                        $htFile = file_get_contents(".htaccess");
                        $lines = explode("\n",$htFile);
                        while(list($k,$v)=each($lines)){
                            if (strpos("_".$v, "deny from")>0) {
                                $htAccessCurrent = str_replace("#IPADDRESSES#", $v."\n#IPADDRESSES#", $htAccessCurrent);                              
                            }
                        }
                    }
                    global $_SERVER;
                    $htAccessCurrent = str_replace("#IPADDRESSES#", "deny from ".$_SERVER["REMOTE_ADDR"], $htAccessCurrent);
                    file_put_contents(".htaccess", $htAccessCurrent);
                }
            }
        }
        catch (Exception $e)
        {
            // If the log file is unwriteable during login then we should not go to the error page
            return;
        }            
    }
}
?>