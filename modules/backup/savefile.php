<?php
if (!$canRead)
	$AppUI->redirect( "m=public&a=access_denied" );

// ----------------------------------------------------------
// Backup part starting ...
$separator = ',';
$file = dPgetParam($_POST, 'sql_file', 'backup'); 
$file_type = dPgetParam($_POST, 'file_type', 'sql');
$zipped = dPgetParam($_POST, 'zipped', false);
$module = dPgetParam($_POST, 'module', 'all');
$item = dPgetParam($_POST, 'item', '-1');

//Functions

function valuesList($table, $row)
{
	$q = new DBQuery;
	$q->addTable($table);
	$q->addInsert(headerList($row), bodyList($row), true);
	return $q->prepare();
}

function bodyList($row)
{
  global $file_type, $separator;

  if ($file_type == 'sql')
	{
  	$separator = ',';
	  $q = "'";
	}
  else
    $q = '"';

  $sql = "";
  foreach($row as $key=>$col)
    if (!is_int($key))
		{
			if ($col == null)
				$sql .= 'NULL';
			else if (intval($col) != 0 || $col == '0')
				$sql .= intval($col);
			else
    		$sql .= $q . str_replace($q, "\$q", $col) . $q;

			$sql .= $separator;
		}
    // Substitute the entire line for csv if necessary.
  $sql = substr($sql, 0, -1); // remove last comma

  return $sql;
}

function headerList($row)
{
  global $file_type, $separator;
  if (empty($row))
    return;

  if ($file_type == 'sql')
	{
    //$q = "`";
		$separator = ',';
	}
  else
    $q = '';

  $out = '';
  foreach ($row as $key=>$col)
    if (!is_int($key))
      $out .= "$q$key$q$separator";

  return substr($out, 0, -1);
}

function xmlList($row)
{
	$out = '
	<item ';
	foreach($row as $key => $col)
		if (!is_int($key))
		{
//			if (strpos($key, '_') > -1)
//				$key = substr($key, strpos($key, '_')+1); 
			$out .=  $key.'="'.htmlspecialchars($col).'" ';
		}
	$out .= '/>';

	return $out;
}



function dumpContacts()
{
        global $AppUI;
        $sql = "SELECT * FROM contacts";
        $contacts = db_loadList( $sql );

        // include PEAR vCard class
        require_once( $AppUI->getLibraryClass( 'PEAR/Contact_Vcard_Build' ) );

        $output = '';
        foreach($contacts as $contact)
        {
          // instantiate a builder object
          // (defaults to version 3.0)
          $vcard = new Contact_Vcard_Build();
          $vcard->setFormattedName($contact['contact_first_name'].' '.$contact['contact_last_name']);
          $vcard->setName($contact['contact_last_name'], $contact['contact_first_name'], $contact['contact_type'],
                  $contact['contact_title'], '');
          $vcard->setSource($dPconfig['company_name'].' '.$dPconfig['page_title'].': '.$dPconfig['site_domain']);
          $vcard->setBirthday($contact['contact_birthday']);
         $contact['contact_notes'] = str_replace("\r", " ", $contact['contact_notes'] );
          $vcard->setNote($contact['contact_notes']);
          $vcard->addOrganization($contact['contact_company']);
          $vcard->addTelephone($contact['contact_phone']);
          $vcard->addParam('TYPE', 'PF');
          $vcard->addTelephone($contact['contact_phone2']);
          $vcard->addTelephone($contact['contact_mobile']);
          $vcard->addParam('TYPE', 'car');
          $vcard->addEmail($contact['contact_email']);
          //$vcard->addParam('TYPE', 'WORK');
          $vcard->addParam('TYPE', 'PF');
          $vcard->addEmail($contact['contact_email2']);
          //$vcard->addParam('TYPE', 'HOME');
          $vcard->addAddress('', $contact['contact_address2'], $contact['contact_address1'],
                  $contact['contact_city'], $contact['contact_state'], $contact['contact_zip'], $contact['contact_country']);
  
          $output .= $vcard->fetch();
          $output .= "\n\n";
        }

        return $output;
}



function dump($module, $item, $type)
{
	include('exports/' . $type . '.php');
	
  if ($type == 'vcf' && $module == 'contacts')
    return dumpContacts();
  if ($module == "all")
    return dumpAll();
  else if ($module == 'projects')
	    return dumpProject($item);
  else if ($module == "tasks")
    return dumpTasks(-1, $item);
  else if ($module == "companies")
    return dumpCompanies($item);
  else
    return tableInsert($module);
}

//if ($module == "all")
//  $output = dumpAll();
//else
$output = dump($module, $item, $file_type);
if ($file_type == 'xml' && $module != 'projects') // to be redone - msproject stuff should be separate.
	$output = '<xml>' . $output . '</xml>';

$file .= '.' . $file_type;

$mimes = array(
'csv' => 'text/csv',
'vcf' => 'text/x-vcard',
'sql' => 'text/sql',
'xml' => 'text/xml',
'msproject' => 'text/xml'); // application/xslt+xml
$mime_type = $mimes[$file_type];

if ($zipped)
{
  include('zip.lib.php');
  $zip = new zipfile;
  $zip->addFile($output,$file);
  $output = $zip->file();

  $file .= '.zip';
  $mime_type = 'application/x-zip';
}

$testing = false;
if (!$testing)
{
  header('Content-Disposition: inline; filename="' . $file . '"');
  header('Content-Type: ' . $mime_type);
}
else
{
	echo '<code>';
	print_r($_POST);
  $output .= '</code>';
}

echo $output;
?>
