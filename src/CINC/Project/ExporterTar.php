<?php

namespace CINC\Project;

/**
 * Provides Tar export functionality for configuration projects.
 *
 * @author Scott Reynen <scott@slicedbreadlabs.com>
 */
class ExporterTar {

  public function export(\CINC\Project $project, $parameters) {

    $filename = $project->name . '.tar';
    header('Content-type: application/x-tar');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $project_name = $project->name;
    if (isset($project->metadata['display name'])) {
      $project_name = $project->metadata['display name'];
    }

    if ($parameters['version'] == 'd7') {

      $info_array = array(
        'name = ' . $project_name,
        'core = 7.x',
      );
      if (isset($project->metadata['description'])) {
        $info_array[] = 'description = "' . $project->metadata['description'] . '"';
      }
      if (isset($project->metadata['version'])) {
        $info_array[] = 'version = ' . $project->metadata['version'];
      }
      $info_array[] = '';
      $info_array[] = 'dependencies[] = cinc_yaml';

      $info = implode("\n", $info_array);

      print $this->create_tar($project->name . '/' . $project->name . '.info', $info);
      print $this->create_tar($project->name . '/' . $project->name . '.module', '<?php' . "\n");

    }
    else {

      $info_array = array(
        'name: ' . $project_name,
        'type: module',
        'core: 8.x'
      );
      if (isset($project->metadata['description'])) {
        $info_array[] = 'description: "' . $project->metadata['description'] . '"';
      }
      if (isset($project->metadata['version'])) {
        $info_array[] = 'version: ' . $project->metadata['version'];
      }
      $info = implode("\n", $info_array);

      print $this->create_tar($project->name . '/' . $project->name . '.info.yml', $info);

    }

    foreach ($project->yaml as $name => $contents) {
      print $this->create_tar($project->name . '/config/install/' . $name, $contents);
    }

    print pack('a1024', '');
    exit;

  }

  /**
   * Creates a tar file of given name with contents.
   */
  public function create_tar($name, $contents) {
    /* http://www.mkssoftware.com/docs/man4/tar.4.asp */
    /* http://www.phpclasses.org/browse/file/21200.html */
    $tar = '';
    $bigheader = $header = '';
    if (strlen($name) > 100) {
      $bigheader = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",
          '././@LongLink', '0000000', '0000000', '0000000',
          sprintf("%011o", strlen($name)), '00000000000',
          '        ', 'L', '', 'ustar ', '0',
          '', '', '', '', '', '');

      $bigheader .= str_pad($name, floor((strlen($name) + 512 - 1) / 512) * 512, "\0");

      $checksum = 0;
      for ($i = 0; $i < 512; $i++) {
        $checksum += ord(substr($bigheader, $i, 1));
      }
      $bigheader = substr_replace($bigheader, sprintf("%06o", $checksum)."\0 ", 148, 8);
    }
   $header = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12", // book the memorie area
      substr($name,0,100),  //  0     100     File name
      '100644 ',            // File permissions
      '   765 ',            // UID,
      '   765 ',            // GID,
      sprintf("%11s ", decoct(strlen($contents))), // Filesize,
      sprintf("%11s", decoct(time())),       // Creation time
      '        ',        // 148     8         Check sum for header block
      '',                // 156     1         Link indicator / ustar Type flag
      '',                // 157     100     Name of linked file
      'ustar ',          // 257     6         USTAR indicator "ustar"
      ' ',               // 263     2         USTAR version "00"
      '',                // 265     32         Owner user name
      '',                // 297     32         Owner group name
      '',                // 329     8         Device major number
      '',                // 337     8         Device minor number
      '',                // 345     155     Filename prefix
      '');               // 500     12         ??

    $checksum = 0;
    for ($i = 0; $i < 512; $i++) {
      $checksum += ord(substr($header, $i, 1));
    }
    $header = substr_replace($header, sprintf("%06o", $checksum)."\0 ", 148, 8);
    $tar = $bigheader.$header;

    $buffer = str_split($contents, 512);
    foreach ($buffer as $item) {
      $tar .= pack("a512", $item);
    }
    return $tar;
  }


}
