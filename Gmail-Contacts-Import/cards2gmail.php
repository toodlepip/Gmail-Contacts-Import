#!/usr/bin/php
<?php

# Detects line-endings so will properly handle lines on Mac, Linux and Windows,
# default option is off, which casues problems on Macs.
ini_set('auto_detect_line_endings', TRUE);

# Read in arguments from the command line
$opt = array();
$fields = array("Name", "Given Name", "Additional Name", "Family Name", "Yomi Name", "Given Name Yomi", "Additional Name Yomi", "Family Name Yomi", "Name Prefix", "Name Suffix", "Initials", "Nickname", "Short Name", "Maiden Name", "Birthday", "Gender", "Location", "Billing Information", "Directory Server", "Mileage", "Occupation", "Hobby", "Sensitivity", "Priority", "Subject", "Notes", "Group Membership", "E-mail 1 - Type", "E-mail 1 - Value", "E-mail 2 - Type", "E-mail 2 - Value", "E-mail 3 - Type", "E-mail 3 - Value", "IM 1 - Type", "IM 1 - Service", "IM 1 - Value", "Phone 1 - Type", "Phone 1 - Value", "Phone 2 - Type", "Phone 2 - Value", "Phone 3 - Type", "Phone 3 - Value", "Address 1 - Type", "Address 1 - Formatted", "Address 1 - Street", "Address 1 - City", "Address 1 - PO Box", "Address 1 - Region", "Address 1 - Postal Code", "Address 1 - Country", "Address 1 - Extended Address", "Address 2 - Type", "Address 2 - Formatted", "Address 2 - Street", "Address 2 - City", "Address 2 - PO Box", "Address 2 - Region", "Address 2 - Postal Code", "Address 2 - Country", "Address 2 - Extended Address", "Organization 1 - Type", "Organization 1 - Name", "Organization 1 - Yomi Name", "Organization 1 - Title", "Organization 1 - Department", "Organization 1 - Symbol", "Organization 1 - Location", "Organization 1 - Job Description", "Website 1 - Type", "Website 1 - Value", "Website 2 - Type", "Website 2 - Value", "Website 3 - Type", "Website 3 - Value", "Custom Field 1 - Type", "Custom Field 1 - Value");
$map = array(
  "Name" => "Name",
  "Work Email" => "E-mail 1 - Value",
  "Company" => "Organization 1 - Name",
  "Job Title" => "Organization 1 - Title",
  "Work Phone" => "Phone 1 - Value",
  "Mobile Phone" => "Phone 2 - Value",
  "Street" => "Address 1 - Street",
  "City" => "Address 1 - City",
  "Region" => "Address 1 - Region",
  "Postal Code" => "Address 1 - Postal Code",
  "Country" => "Address 1 - Country",
  "Group Membership" => "Group Membership", # Multiple values separated by :::
  "Notes" => "Notes",
  "Work Website" => "Website 1 - Value",
  "Skype" => "IM 1 - Value",
  "Twitter" => "Website 2 - Value",
);

print <<<END

### GMAIL CONTACTS MUNGER
by Sam Michel - http://toodlepip.co.uk
More info in the README.TXT
N.B. Use at your own risk. Backups are your friend!


END;

if (count($argv) < 2) {
  print <<<END
This script is designed to take a basic CSV file of contact information and
munge it to import into Gmail Contacts.
  
Usage: $argv[0] -f filename [-v] [-o output_filename]

The filename should be in a CSV format which can be read using PHP's fgetcsv
command - which should be fairly standard. It expects the files to be a CSV
file, use this Google spreadsheet as an example or create you're own using
the headings (they're all optional):

https://docs.google.com/spreadsheet/ccc?key=0AmLbS8Gq5NUHdDZpWlRfMnF0NWdLSjdzN1hxWU40Q1E

N.B. Don't forget to separate Group Memberships with ::: for example -
Work Contacts ::: Christmas List ::: Other People

END;
  exit;
}
while(count($argv) > 0) {
  $arg = array_shift($argv);
  switch($arg) {
    case '-v':
       $opt['verbose'] = TRUE;
       break;
    case '-f':
      $opt['inputfile'] = array_shift($argv);
      break;
    case '-o':
      $opt['outputfile'] = array_shift($argv);
      break;
  }
}
if (!isset($opt['outputfile'])) $opt['outputfile'] = "gmail-import.csv";


# Open files for input & output to minimise the amount of info that needs
# to be held in memory.

if (($in = @fopen($opt['inputfile'], "rb")) === FALSE) {
  printf("\nERROR: Failed to open %s for input.\n", $opt['inputfile']);
  exit;
}
printf("Using %s for input files.\n", $opt['inputfile']);

if (($out = @fopen($opt['outputfile'], "wb")) === FALSE) {
  printf("\nERROR: Failed to open %s for output.\n", $opt['outputfile']);
  exit;
}
printf("Using %s for output files.\n", $opt['outputfile']);

fputcsv($out, $fields); # Write Gmail Contact headers to output file

$header = array();
$stats = array();
while (($line = fgetcsv($in)) !== FALSE) {
  if (empty($header)) {
    $header = array_flip($line);
    continue;
  }
  
  # Munge line so that each column has an associated field name, makes
  # it easier for detailed processing below.
  foreach ($header as $head => $field) {
    //printf("Head %s: Field %s\n", $head, $field);
    $line[$head] = $line[$field];
  }

  # Fix Twitter address to a proper URL in case it's just listed as
  # @name or name
  if (!empty($line['Twitter'])) {
    # http://twitter.com/name
    # twitter.com/name
    # @name
    # name
    if (preg_match('/[@\/]([a-z0-9_]*)$/i', $line['Twitter'], $matches)) {
      $line['Twitter'] = "http://twitter.com/". $matches[1];
    }
    else {
      $line['Twitter'] = "http://twitter.com/". $line['Twitter'];
    }
  }  
  
  $data = array();
  foreach ($map as $source => $dest) {
    $data[$dest] = $line[$source];
  }
  
  
  # Munge for Gmail Contacts specific format
  foreach ($fields as $field) {
      
    # If empty, set to NULL value so array is correct length when
    # written to file  
    if (empty($data[$field])) {
      $data[$field] = "";
      continue;
    }
    
    switch($field) {
      case 'E-mail 1 - Value':
        $data['E-mail 1 - Type'] = "* Work";
        break;
      case 'Phone 1 - Value':
        $data['Phone 1 - Type'] = "Work";
        break;
      case 'Phone 2 - Value':
        $data['Phone 2 - Type'] = "Mobile";
        break;
      case 'Address 1 - Street':
        $data['Address 1 - Type'] = "Work";
        break;
      case 'Website 1 - Value':
        $data['Website 1 - Type'] = "Work";
        break;
      case 'Website 2 - Value':
        $data['Website 2 - Type'] = "Twitter";
        break;
      case 'IM 1 - Value':
        $data['IM 1 - Service'] = "Skype";
        break;
    }
     
  }

  # Write the fields into an array in the correct order for the CSV file
  $write = array();
  foreach ($fields as $field) {
    $write[] = $data[$field];
  }
  
  if (@fputcsv($out, $write) === FALSE) {
    $stats['error'][] = $data;
  }
  else {
    $stats['saved']++;
  }
  
}

fclose($in);
fclose($out);

printf("%d errors reported (use -v to get full dump).\n", count($opt['error']));
printf("Succesfully saved %d records to %s.\n\n", $stats['saved'], $opt['outputfile']);

if ($opt['verbose']) {
  print <<<END
### ERROR DUMP
There was errors writing the following records:

END;
  foreach ($stats['error'] as $error) {
    print implode(', ', $error) . "\n";
  }
}
