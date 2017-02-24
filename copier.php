<?php
/**
 * Class DataCopier
 *
 * @author João Pedro Pereira <joaopedro.pereira.pt@gmail.com>
 * @date 24 Fev 2017
 * @package helpers
 *
 * @description Lots of changes made in a project. The only access to client
 *              server is CPanel which makes it easy to mess up and time
 *              consuming to upload the changes made. After generating the
 *              change list and having the new folder on the same server
 *              just let the script copy the changes into place.
 *
 * Generate files to be copied using this command or an alternative:
 * $ rsync -rv --dry-run --exclude-from=exclude.patterns src/ dest/
 *      > configure exclude.pats your own way
 *
 */
class DataCopier {
    /**
     * @var string path to source folder (from)
     */
    public $dir = "live";
    /**
     * @var string path to destination folder (to)
     */
    public $dirNew = "update";

    /**
     * @var string $changeFile - list of files to be changed
     */
    protected $changeFile = 'newFiles.data';

    public $diffFiles = [];
    public $folders = [];

    public function start() {
        echo 'Starting process...';

        $this->getFiles();

        echo "Applying changes<br/>";
        $this->recursiveCopy($this->dir, $this->dirNew);

        echo 'Process ended';
    }

    /**
     * Parse file list to memory
     */
    protected function getFiles (){
        echo 'Loading Files...';

        $handle = fopen($this->changeFile, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                $folder = pathinfo($line);
                $folder = $folder['dirname'];
                if (!in_array($folder, $this->folders)) {
                    $this->folders[] = $folder;
                }
                $this->diffFiles[] = './' . $this->dir . '/' . $line;
            }

            fclose($handle);
        } else
            echo 'Error opening file... ';
    }

    /**
     * @param $src "from" path
     * @param $dst "to" path
     */
    public function recursiveCopy($src, $dst) {
        $dir = opendir($src);

        $newstring = substr_replace($dst, '', 0, strlen($this->dirNew)+1);

        if (in_array($newstring, $this->folders)) {
            if (!file_exists($dst)) {
                echo "<br/> > Creating folder: {$dst} <br/><br/>";
                mkdir($dst, 0755, true);
            } else {
                echo "<br/> > Using existing folder: {$dst} <br/><br/>";
            }
        }

        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file);
                } elseif (in_array(trim('./' . $src . '/' . $file), $this->diffFiles)) {
                    echo 'copying: ' . $dst . '/' . $file ;
                    if(copy($src . '/' . $file, $dst . '/' . $file)) {
                        echo " — SUCCESS <br/>";
                    } else {
                        echo " — ERROR <br/>";
                    }
                }
            }
        }
        closedir($dir);
    }
}

$var = new DataCopier();
$var->start();
