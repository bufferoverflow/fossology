<?php
/***********************************************************
 Copyright (C) 2008-2013 Hewlett-Packard Development Company, L.P.
 Copyright (C) 2015 Siemens AG

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 version 2 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 ***********************************************************/

use Fossology\Lib\Db\DbManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

define("TITLE_ui_download", _("Download File"));

/**
 * \class ui_download extends FO_Plugin
 * \brief downlad file(s)
 */
class ui_download extends FO_Plugin
{
  var $Name       = "download";
  var $Title      = TITLE_ui_download;
  var $Version    = "1.0";
  var $Dependency = array();
  var $DBaccess   = PLUGIN_DB_WRITE;
  var $NoHTML     = 1;

  /**
   * \brief Customize submenus.
   */
  function RegisterMenus()
  {
    $text = _("Download this file");
    menu_insert("Browse-Pfile::Download",0,$this->Name,$text);
  } // RegisterMenus()

  /**
   * \brief Called if there is no file.  User is queried if they want
   * to reunpack.
   */
  function CheckRestore($Item, $Filename)
  {
    global $Plugins;

    $this->NoHeader = 0;
    header('Content-type: text/html');
    header("Pragma: no-cache"); /* for IE cache control */
    header('Cache-Control: no-cache, must-revalidate, maxage=1, post-check=0, pre-check=0'); /* prevent HTTP/1.1 caching */
    header('Expires: Expires: Thu, 19 Nov 1981 08:52:00 GMT'); /* mark it as expired (value from Apache default) */

    $V = "";
    if (($this->NoMenu == 0) && ($this->Name != "menus"))
    {
      $Menu = &$Plugins[plugin_find_id("menus")];
    }
    else { $Menu = NULL; }

    /* DOCTYPE is required for IE to use styles! (else: css menu breaks) */
    $V .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "xhtml1-frameset.dtd">' . "\n";

    $V .= "<html>\n";
    $V .= "<head>\n";
    $V .= "<meta name='description' content='The study of Open Source'>\n";
    if ($this->NoHeader == 0)
    {
      /** Known bug: DOCTYPE "should" be in the HEADER
       and the HEAD tags should come first.
       Also, IE will ignore <style>...</style> tags that are NOT
       in a <head>...</head>block.
       **/
      if (!empty($this->Title)) $V .= "<title>" . htmlentities($this->Title) . "</title>\n";
      $V .= "<link rel='stylesheet' href='css/fossology.css'>\n";
      if (!empty($Menu)) print $Menu->OutputCSS();
      $V .= "</head>\n";
      $V .= "<body class='text'>\n";
      print $V;
      if (!empty($Menu)) { $Menu->Output($this->Title); }
    }
     
    $P = &$Plugins[plugin_find_id("view")];
    $P->ShowView(NULL,"browse");
    exit;
  } // CheckRestore()

  
  function renderOutput()
  {
    try
    {
      $output = $this->getPathAndName();
      list($Filename, $Name) = $output;
      $response = $this->downloadFile($Filename, $Name);
    }
    catch(Exception $e)
    {
      $this->vars['content'] = $e->getMessage();
      $response = $this->render($this->getTemplateName());
    }
    $response->prepare($this->getRequest());
    $response->send();
  }

  /**
   * \brief This function is called when user output is
   * requested.  This function is responsible for content.
   */
  protected function getPathAndName()
  {
    if ($this->State != \PLUGIN_STATE_READY) {
      throw new Exception('Download plugin is not ready');
    }

    global $container;
    /** @var DbManager $dbManager */
    $dbManager = $container->get('db.manager');
    if (!$dbManager->getDriver())
    {
      throw new Exception("Missing database connection.");
    }

    $reportId = GetParm("report",PARM_INTEGER);
    $item = GetParm("item",PARM_INTEGER);

    if (!empty($reportId))
    {
      $row = $dbManager->getSingleRow("SELECT * FROM reportgen WHERE job_fk = $1", array($reportId), "reportFileName");
      if ($row === false)
      {
        throw new Exception("Missing report");
      }

      $path = $row['filepath'];
      $filename = basename($path);
      $uploadId = $row['upload_fk'];
    }
    else if (empty($item))
    {
      throw new Exception("Invalid item parameter");
    }
    else
    {
      $path = RepPathItem($item);
      if (empty($path))
      {
        throw new Exception("Invalid item parameter");
      }

      $fileHandle = @fopen( RepPathItem($item) ,"rb");
      /* note that CheckRestore() does not return. */
      if (empty($fileHandle))
      {
        $this->CheckRestore($item, $path);
      }

      $row = $dbManager->getSingleRow("SELECT ufile_name, upload_fk FROM uploadtree WHERE uploadtree_pk = $1",array($item));
      if ($row===false)
      {
        throw new Exception("Missing item");
      }
      $filename = $row['ufile_name'];
      $uploadId = $row['upload_fk'];
    }

    $uploadPerm = GetUploadPerm($uploadId);
    if ($uploadPerm < PERM_WRITE)
    {
      throw new Exception("No Permission: $uploadId");
    }
    if (!file_exists($path))
    {
      throw new Exception("File does not exist");
    }
    if (!is_file($path))
    {
      throw new Exception("Not a regular file");
    }
    return array($path, $filename);
  }

  /**
   * @global type $container
   * @param type $path
   * @param type $filename
   * @return BinaryFileResponse
   */
  protected function downloadFile($path, $filename)
  {
    global $container;
    $session = $container->get('session');
    $session->save();

    $response = new BinaryFileResponse($path);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
    if (pathinfo($filename, PATHINFO_EXTENSION) == 'docx')
    {
      $response->headers->set('Content-Type', ''); // otherwise mineType would be zip
    }
    return $response;
  }
}

$NewPlugin = new ui_download;
$NewPlugin->Initialize();
