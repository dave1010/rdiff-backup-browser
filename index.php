<?

###################################################################
#
# rdiff-backup-browser
#
# Dean Cording (dean@cording.id.au)
# Based on rdiff-backup-web by: David Evans (goodevans@gmail.com)
#
# (c) 2007 Dale Stirling (rdiffbackupweb@puredistortion.com)
#
# Useless without rdiff-backup created by Ben Escoto
#
# rdiff-backup-browser is a web-based interface for rdiff-backup
# designed to greatly simplify backup restoring.
#
# rdiff-backup-browser is free software and is released under the 
# GNU General Public Licence. See licence.txt for details.
# 
###################################################################

include("./config.php");
if($_REQUEST['op']){
  $op = $_REQUEST['op'];
} else {
  $op = "";
}

if($_REQUEST['backup']){
  $backup=$_REQUEST['backup'];
} else {
  $backup="";
}

if($_REQUEST['increment']){
   $increment=$_REQUEST['increment'];
} else {
  $increment="";
}

if($_REQUEST['linktofile']){
   $linktofile=$_REQUEST['linktofile'];
} else {
  $linktofile="";
}

if ($op == "view_log") {

    $file="$BACKUP_LOCATION$backup/rdiff-backup-data/backup.log";

    header('Content-Type: text/plain');
    header('Content-Length: ' . filesize($file));
    ob_clean();
    flush();
    readfile($file);

}


/* Force download of file */
if ($linktofile != "" && $increment !="" && $backup != "") {
  
  $filename = split("/", $linktofile);

  $filename_in_array = sizeof($filename);
  $filename_in_array --;

  $file=$DOWNLOAD_LOCATION.$filename[$filename_in_array];
  if (file_exists($file)) { unlink($file);};

  $restore_command = "$RDIFF_BACKUP --restore-as-of '$increment'  $BACKUP_LOCATION$backup/".str_replace(" ", "\ ", $linktofile)." $DOWNLOAD_LOCATION".str_replace(" ", "\ ", $filename[$filename_in_array]);

  exec($restore_command);
  

  $ftype = 'application/octet-stream'; 
  $finfo = finfo_open(FILEINFO_MIME); 
  $fres = finfo_file($finfo, $file); 
  if (is_string($fres) && !empty($fres)) { 
     $ftype = $fres; 
  }
  finfo_close($finfo);

  header('Content-Description: File Transfer');
  header('Content-Type: '.strstr($ftype, " ", true));
  header('Content-Disposition: attachment; filename='.basename($file));
  header('Content-Transfer-Encoding: binary');
  header('Expires: 0');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Pragma: public');
  header('Content-Length: ' . filesize($file));
  ob_clean();
  flush();
  readfile($file);
  exit;
}

?>

<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <title>Rdiff-Backup-Browser</title>
    <link rel="stylesheet" type="text/css" href="main.css" />

    <script language="Javascript">
      function toggle(dir) {
        var node = dir.nextSibling;
        while (node != null) {
          if (node.tagName == "UL") {
            var d = (node.style.display == "none");
            node.style.display = (d) ? "block" : "none";
            dir.className = (d) ? "open" : "closed";
            return false;
          }
         node = node.nextSibling;
        }
      return false;
     }
   </script>
  </head>
  <body>
    <!-- Begin Wrapper -->
    <div id="wrapper">
      <!-- Begin Header -->
      <div id="header">
        <h2 align=right>
           Rdiff-Backup-Browser 
        </h2>
      </div>
      <!-- End Header -->
        <!-- Begin Faux Columns -->
      <div id="faux">
        <!-- Begin Left Column -->

        <div id="leftcolumn">
          <h3>Backup session:</h3>
          <ul class="vert-one">

<?

$rdiff_backup_list = scandir($BACKUP_LOCATION);

$output = "";

for($i=2; $i<count($rdiff_backup_list); $i++){
  if ($rdiff_backup_list[$i] == $backup) {
    $output.="<li ><a href=\"?op=select_backup&backup=$rdiff_backup_list[$i]\" id=\"vert-one-selected\">$rdiff_backup_list[$i]</a></li>";

    $command = "$RDIFF_BACKUP --list-increments $BACKUP_LOCATION$backup 2>&1"; 
    exec ($command, $rdiff_increment_list);
    $output.="<ul>";
    for($x=count($rdiff_increment_list)-1;$x>0; $x--){
      $description = substr($rdiff_increment_list[$x], -24);
      $incrementnum = (count($rdiff_increment_list)-$x-1)."B";
      $output.="<li><a href=\"?backup=$backup&increment=$incrementnum\"";

      if ($increment == $incrementnum) {
        $output.= " id=\"vert-one-selected\"";
      }
    
      $output.= ">$description</a></li>";
    }

    $output.="<li><a href=\"?op=statistics&backup=$backup\"";
    if ($op == "statistics") {
        $output.= " id=\"vert-one-selected\"";
    }
    
    $output.= ">Backup statistics</a></li>";

    $output.="<li><a href=\"?op=view_log&backup=$backup\"";
    if ($op == "view_log") {
        $output.= " id=\"vert-one-selected\"";
    }
    $output.= ">Backup log</a></li></ul>";
    
  } else {
    $output.="<li><a href=\"?backup=$rdiff_backup_list[$i]\">$rdiff_backup_list[$i]</a></li>";
  }
}

$output.="</ul></div>";


$output.="<div id=\"rightcolumn\">";


if ($backup != "" && $increment != "") {

    $command = "$RDIFF_BACKUP --list-at-time '$increment' $BACKUP_LOCATION$backup 2>&1"; 

    exec ($command, $rdiff_backup_file_list);
    array_shift($rdiff_backup_file_list);

    $output.= "<h3>Click on a file to restore:</h3>";
    $output.= "<ul class=\"php-file-tree\">\n";

    $currentdirectorylevel = 1;

    foreach( $rdiff_backup_file_list as $file ) {

      $filedetails = split("\t", $file);

      $filepath = split("/", rtrim($filedetails[0], " /\t\n\m\0"));
      $filename = htmlspecialchars(rtrim($filepath[sizeof($filepath) - 1]));
      $directorylevel = count($filepath);

      $details = "Owner: $filedetails[1] \nSize: $filedetails[2] \nModified: $filedetails[3]";

      while ($currentdirectorylevel > $directorylevel) {
        $output.= "</ul>\n</li>\n";
        $currentdirectorylevel--;
      }

      if ($filedetails[0][strlen($filedetails[0])-1] == "/") {
          // Directory
         $output.= "<li class=\"pft-directory closed\"><a href=\"#\" title=\"$details\" onclick=\"return toggle(this);\">$filename</a>\n<ul style=\"display:none\">\n";
         $currentdirectorylevel++;
      } else {
          // File
          // Get extension (prepend 'ext-' to prevent invalid classes from extensions that begin with numbers)
          $ext = "ext-" . substr($filename, strrpos($filename, ".") + 1); 
          $link = "?&backup=$backup&increment=$increment&linktofile=".htmlspecialchars(rtrim($filedetails[0]));
          $output.= "<li class=\"pft-file " . strtolower($ext) . "\" ><a href=\"$link\" title=\"$details\">$filename</a></li>\n";
      }

    }

   $output.= "</li>\n</ul>\n";
}


if ($op == "statistics") {

    $output.="<h3>Backup statistics:</h3>";
    $command = "$RDIFF_BACKUP_STATISTICS $BACKUP_LOCATION$backup 2>&1"; 
    exec($command, $stats);

    for ($x=0; $x<count($stats); $x++){
      $output.="$stats[$x]<br/>";
    }
}

$output.="</div>";
echo $output;
?>

        <div class="clear">
        </div>
      </div>
      <!-- End Faux Columns --> 

        <!-- Begin Footer -->
      <div id="footer">
        <p align=center>
          <small>
            <a href="http://www.nongnu.org/rdiff-backup/">rdiff-backup</a> created by <a href="mailto:bescoto@stanford.edu">Ben Escoto</a>
          </small>
        </p>
      </div>
      <!-- End Footer -->
    </div>
    <!-- End Wrapper -->
  </body>
</html>
